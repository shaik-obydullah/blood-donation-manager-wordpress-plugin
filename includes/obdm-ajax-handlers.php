<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_obdm_register_donor', 'obdm_ajax_register_donor');
add_action('wp_ajax_nopriv_obdm_register_donor', 'obdm_ajax_register_donor');
add_action('wp_ajax_obdm_submit_request', 'obdm_ajax_submit_request');
add_action('wp_ajax_nopriv_obdm_submit_request', 'obdm_ajax_submit_request');

/**
 * Reads a POST value without triggering undefined index notices.
 *
 * Only ever used for presence checks inside handlers that have already
 * verified the request nonce, so the nonce is not re-checked here.
 *
 * @param string $key POST key to read.
 * @return string Sanitized value, or an empty string when absent or non-scalar.
 */
function obdm_post_value($key) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified by the calling handler; the raw value is sanitized by the return statement below.
    $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';

    return is_scalar($value) ? sanitize_text_field((string) $value) : '';
}

/**
 * Normalises a submitted date for a DATE column.
 *
 * An absent field has to be stored as NULL. Passing an empty string through
 * to MySQL would land as the zero date 0000-00-00, which is not a real date
 * and silently breaks age and expiry comparisons. Anything that is not a
 * well-formed Y-m-d value is dropped the same way, so a mistyped date is never
 * persisted as a zero date.
 *
 * @param string $value Raw, untrusted date.
 * @return string|null Y-m-d date, or null when absent or malformed.
 */
function obdm_post_date($value) {
    $value = obdm_post_value($value);

    if ('' === $value) {
        return null;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return ($date && $date->format('Y-m-d') === $value) ? $value : null;
}

/**
 * Sends a JSON error naming the first unusable field.
 *
 * @param array $fields Map of POST key to value that has already been sanitised.
 * @return void
 */
function obdm_require_fields($fields) {
    foreach ($fields as $field => $value) {
        if ('' === $value || null === $value) {
            wp_send_json_error(['message' => sprintf(
                /* translators: %s: name of the missing form field. */
                __('Please fill in all required fields. Missing: %s', 'obydullah-blood-bank-manager'),
                $field
            )]);
        }
    }
}

function obdm_ajax_register_donor() {
    $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));

    if (!wp_verify_nonce($nonce, 'obdm_frontend_nonce')) {
        wp_send_json_error(__('Security verification failed', 'obydullah-blood-bank-manager'));
    }

    global $wpdb;

    // Sanitise first, validate second. The previous order checked the raw POST
    // values and then sanitised them, so a field that sanitised down to an
    // empty string (sanitize_email() does exactly that for a malformed address)
    // still reached the INSERT while the visitor was told it had worked.
    $user_id            = get_current_user_id();
    $first_name         = obdm_post_value('first_name');
    $last_name          = obdm_post_value('last_name');
    $email              = obdm_post_value('email');
    $phone              = obdm_post_value('phone');
    $blood_type         = Obdm_Blood_Bank_Manager::validate_blood_type(obdm_post_value('blood_type'));
    $date_of_birth      = obdm_post_date('date_of_birth');
    $gender             = obdm_post_value('gender');
    $weight             = floatval(obdm_post_value('weight'));
    $address            = sanitize_textarea_field(obdm_post_value('address'));
    $city               = obdm_post_value('city');
    $state              = obdm_post_value('state');
    $zip_code           = obdm_post_value('zip_code');
    $country            = obdm_post_value('country');
    $last_donation_date = obdm_post_date('last_donation_date');
    $medical_conditions = sanitize_textarea_field(obdm_post_value('medical_conditions'));
    $is_available       = '1' === obdm_post_value('is_available') ? 1 : 0;
    $settings           = get_option('obdm_settings', []);
    $min_weight         = isset($settings['min_weight']) ? floatval($settings['min_weight']) : 50;

    obdm_require_fields([
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'phone'      => $phone,
    ]);

    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Please enter a valid email address.', 'obydullah-blood-bank-manager')]);
    }

    // Only normalised once it is known to be an address, so a malformed one is
    // reported as invalid rather than as a missing field.
    $email = sanitize_email($email);

    // The blood type drives donor matching, searching and the compatibility
    // lookups, so only the canonical set is accepted. An unrecognised value is
    // rejected here rather than stored as free text.
    if (null === $blood_type) {
        wp_send_json_error(['message' => __('Please choose a valid blood type.', 'obydullah-blood-bank-manager')]);
    }

    $existing = Obdm_Blood_Bank_Manager::donors_count_by_email($email);

    if ($existing > 0) {
        wp_send_json_error(['message' => __('A donor with this email already exists.', 'obydullah-blood-bank-manager')]);
    }

    if ($weight > 0 && $weight < $min_weight) {
        wp_send_json_error(['message' => sprintf(
            /* translators: %s: minimum donor weight in kilograms. */
            __('Minimum weight required is %s kg.', 'obydullah-blood-bank-manager'),
            $min_weight
        )]);
    }
    
    $data = [
        'user_id'            => $user_id,
        'first_name'         => $first_name,
        'last_name'          => $last_name,
        'email'              => $email,
        'phone'              => $phone,
        'blood_type'         => $blood_type,
        'date_of_birth'      => $date_of_birth,
        'gender'             => $gender,
        'weight'             => $weight,
        'address'            => $address,
        'city'               => $city,
        'state'              => $state,
        'zip_code'           => $zip_code,
        'country'            => $country,
        'last_donation_date' => $last_donation_date,
        'medical_conditions' => $medical_conditions,
        'is_available'       => $is_available,
    ];

    $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'];

    $donors_table = Obdm_Blood_Bank_Manager::donors_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct write that bypasses the manager class; cached reads are invalidated immediately below.
    $result = $wpdb->insert($donors_table, $data, $formats);
    Obdm_Blood_Bank_Manager::flush_query_cache();
    
    if ($result) {
        obdm_send_donor_welcome_email($data);
        wp_send_json_success(['message' => __('Thank you for registering as a blood donor!', 'obydullah-blood-bank-manager')]);
    } else {
        wp_send_json_error(['message' => __('Registration failed. Please try again.', 'obydullah-blood-bank-manager')]);
    }
}

function obdm_ajax_submit_request() {
    $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));

    if (!wp_verify_nonce($nonce, 'obdm_frontend_nonce')) {
        wp_send_json_error(__('Security verification failed', 'obydullah-blood-bank-manager'));
    }

    global $wpdb;

    // See obdm_ajax_register_donor(): sanitise, then validate. These values
    // reach ENUM and DATE columns, where a rejected or malformed value is
    // coerced by MySQL into '' or the zero date instead of raising an error.
    $requester_name    = obdm_post_value('requester_name');
    $requester_email   = obdm_post_value('requester_email');
    $requester_phone   = obdm_post_value('requester_phone');
    $patient_name      = obdm_post_value('patient_name');
    $blood_type_needed = Obdm_Blood_Bank_Manager::validate_blood_type(obdm_post_value('blood_type_needed'));
    $units_needed      = absint(obdm_post_value('units_needed'));
    $hospital_name     = obdm_post_value('hospital_name');
    $hospital_address  = sanitize_textarea_field(obdm_post_value('hospital_address'));
    $city              = obdm_post_value('city');
    $urgency           = obdm_post_value('urgency');
    $needed_by         = obdm_post_date('needed_by');
    $additional_info   = sanitize_textarea_field(obdm_post_value('additional_info'));
    $status            = 'pending';

    obdm_require_fields([
        'requester_name'    => $requester_name,
        'requester_email'   => $requester_email,
        'requester_phone'   => $requester_phone,
        'patient_name'      => $patient_name,
        'hospital_name'     => $hospital_name,
    ]);

    if (!is_email($requester_email)) {
        wp_send_json_error(['message' => __('Please enter a valid email address.', 'obydullah-blood-bank-manager')]);
    }

    $requester_email = sanitize_email($requester_email);

    if (null === $blood_type_needed) {
        wp_send_json_error(['message' => __('Please choose a valid blood type.', 'obydullah-blood-bank-manager')]);
    }

    // urgency is an ENUM: an unrecognised value would be stored as '' and then
    // sort wrong in the dashboard's urgency ordering.
    $urgency_options = array_keys(Obdm_Blood_Bank_Manager::get_urgency_labels());

    if ('' !== $urgency && !in_array($urgency, $urgency_options, true)) {
        wp_send_json_error(['message' => __('Please choose a valid urgency level.', 'obydullah-blood-bank-manager')]);
    }

    if ('' === $urgency) {
        $urgency = 'normal';
    }

    // A request always needs at least one unit, otherwise the ENUM-backed
    // default is bypassed by an explicit zero.
    $units_needed = max(1, $units_needed);

    $data = [
        'requester_name'    => $requester_name,
        'requester_email'   => $requester_email,
        'requester_phone'   => $requester_phone,
        'patient_name'      => $patient_name,
        'blood_type_needed' => $blood_type_needed,
        'units_needed'      => $units_needed,
        'hospital_name'     => $hospital_name,
        'hospital_address'  => $hospital_address,
        'city'              => $city,
        'urgency'           => $urgency,
        'needed_by'         => $needed_by,
        'additional_info'   => $additional_info,
        'status'            => $status,
    ];

    $formats = ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

    $requests_table = Obdm_Blood_Bank_Manager::requests_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct write that bypasses the manager class; cached reads are invalidated immediately below.
    $result = $wpdb->insert($requests_table, $data, $formats);
    Obdm_Blood_Bank_Manager::flush_query_cache();
    
    if ($result) {
        obdm_send_request_notification($data);
        wp_send_json_success(['message' => __('Your blood request has been submitted successfully.', 'obydullah-blood-bank-manager')]);
    } else {
        wp_send_json_error(['message' => __('Request submission failed. Please try again.', 'obydullah-blood-bank-manager')]);
    }
}

function obdm_send_donor_welcome_email($donor_data) {
    $settings = get_option('obdm_settings', []);
    
    if (empty($settings['enable_notifications'])) {
        return;
    }
    
    $to = $donor_data['email'];
    $subject = isset($settings['donor_email_subject']) ? $settings['donor_email_subject'] : __('Welcome to Our Blood Donation Community', 'obydullah-blood-bank-manager');
    
    $message = sprintf(
        /* translators: %s: donor first name. */
        __('Dear %s,', 'obydullah-blood-bank-manager'),
        $donor_data['first_name']
    ) . "\n\n";
    $message .= __('Thank you for registering as a blood donor. Your registration has been received successfully.', 'obydullah-blood-bank-manager') . "\n\n";
    $message .= __('Your Details:', 'obydullah-blood-bank-manager') . "\n";
    $message .= sprintf(
        /* translators: %s: donor blood type. */
        __('Blood Type: %s', 'obydullah-blood-bank-manager'),
        $donor_data['blood_type']
    ) . "\n";
    $message .= sprintf(
        /* translators: %s: donor city. */
        __('City: %s', 'obydullah-blood-bank-manager'),
        $donor_data['city']
    ) . "\n\n";
    $message .= __('We will contact you when there is a blood request in your area.', 'obydullah-blood-bank-manager') . "\n\n";
    $message .= __('Best regards,', 'obydullah-blood-bank-manager') . "\n";
    $message .= get_bloginfo('name');
    
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    wp_mail($to, $subject, nl2br($message), $headers);
    
    $notify_email = isset($settings['notify_email']) ? $settings['notify_email'] : get_option('admin_email');
    if ($notify_email && $notify_email !== $to) {
        $admin_subject = __('New Blood Donor Registration', 'obydullah-blood-bank-manager');
        $admin_message = __('A new donor has registered:', 'obydullah-blood-bank-manager') . "\n\n";
        $admin_message .= sprintf(
            /* translators: 1: donor first name, 2: donor last name. */
            __('Name: %1$s %2$s', 'obydullah-blood-bank-manager'),
            $donor_data['first_name'],
            $donor_data['last_name']
        ) . "\n";
        $admin_message .= sprintf(
            /* translators: %s: donor email address. */
            __('Email: %s', 'obydullah-blood-bank-manager'),
            $donor_data['email']
        ) . "\n";
        $admin_message .= sprintf(
            /* translators: %s: donor blood type. */
            __('Blood Type: %s', 'obydullah-blood-bank-manager'),
            $donor_data['blood_type']
        ) . "\n";
        $admin_message .= sprintf(
            /* translators: %s: donor city. */
            __('City: %s', 'obydullah-blood-bank-manager'),
            $donor_data['city']
        ) . "\n";
        
        wp_mail($notify_email, $admin_subject, nl2br($admin_message), $headers);
    }
}

function obdm_send_request_notification($request_data) {
    $settings = get_option('obdm_settings', []);
    
    if (empty($settings['enable_notifications'])) {
        return;
    }
    
    $compatible_types = Obdm_Blood_Bank_Manager::get_compatible_blood_types($request_data['blood_type_needed']);
    
    $available_donors = Obdm_Blood_Bank_Manager::donor_emails_for_types($compatible_types);
    
    if (!empty($available_donors)) {
        $to_emails = [];
        foreach ($available_donors as $donor) {
            $to_emails[] = $donor->email;
        }
        
        $to = implode(', ', array_unique($to_emails));
        $subject = isset($settings['request_email_subject']) ? $settings['request_email_subject'] : __('Blood Donation Needed - Urgent Request', 'obydullah-blood-bank-manager');
        
        $urgency_labels = Obdm_Blood_Bank_Manager::get_urgency_labels();
        $urgency = isset($urgency_labels[$request_data['urgency']]) ? $urgency_labels[$request_data['urgency']] : $request_data['urgency'];
        
        $message = __('Dear Donor,', 'obydullah-blood-bank-manager') . "\n\n";
        $message .= sprintf(
            /* translators: %s: blood type needed by the patient. */
            __('A new blood donation request has been posted that matches your blood type (%s).', 'obydullah-blood-bank-manager'),
            $request_data['blood_type_needed']
        ) . "\n\n";
        $message .= __('Request Details:', 'obydullah-blood-bank-manager') . "\n";
        $message .= sprintf(
            /* translators: %s: patient name. */
            __('Patient: %s', 'obydullah-blood-bank-manager'),
            $request_data['patient_name']
        ) . "\n";
        $message .= sprintf(
            /* translators: %s: blood type needed. */
            __('Blood Type Needed: %s', 'obydullah-blood-bank-manager'),
            $request_data['blood_type_needed']
        ) . "\n";
        $message .= sprintf(
            /* translators: %d: number of required blood units. */
            __('Units Needed: %d', 'obydullah-blood-bank-manager'),
            (int) $request_data['units_needed']
        ) . "\n";
        $message .= sprintf(
            /* translators: %s: hospital name. */
            __('Hospital: %s', 'obydullah-blood-bank-manager'),
            $request_data['hospital_name']
        ) . "\n";
        $message .= sprintf(
            /* translators: %s: urgency level of the request. */
            __('Urgency: %s', 'obydullah-blood-bank-manager'),
            $urgency
        ) . "\n\n";
        
        if ($request_data['needed_by']) {
            $message .= sprintf(
                /* translators: %s: date the blood is needed by. */
                __('Needed By: %s', 'obydullah-blood-bank-manager'),
                date_i18n(get_option('date_format'), strtotime($request_data['needed_by']))
            ) . "\n\n";
        }
        
        $message .= sprintf(
            /* translators: 1: requester name, 2: requester phone number. */
            __('Contact: %1$s - %2$s', 'obydullah-blood-bank-manager'),
            $request_data['requester_name'],
            $request_data['requester_phone']
        ) . "\n\n";
        $message .= __('If you can donate, please contact the requester directly.', 'obydullah-blood-bank-manager') . "\n\n";
        $message .= __('Best regards,', 'obydullah-blood-bank-manager') . "\n";
        $message .= get_bloginfo('name');
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    $urgency_labels_raw = Obdm_Blood_Bank_Manager::get_urgency_labels();
    $urgency_labels_raw = isset($urgency_labels_raw[$request_data['urgency']])
        ? $urgency_labels_raw[$request_data['urgency']]
        : $request_data['urgency'];

    $notify_email = isset($settings['notify_email']) ? $settings['notify_email'] : get_option('admin_email');
    if ($notify_email) {
        $admin_subject = __('New Blood Donation Request', 'obydullah-blood-bank-manager');
        $admin_message = __('A new blood request has been submitted:', 'obydullah-blood-bank-manager') . "\n\n";
        $admin_message .= sprintf(
            /* translators: %s: patient name. */
            __('Patient: %s', 'obydullah-blood-bank-manager'),
            $request_data['patient_name']
        ) . "\n";
        $admin_message .= sprintf(
            /* translators: %s: blood type needed. */
            __('Blood Type: %s', 'obydullah-blood-bank-manager'),
            $request_data['blood_type_needed']
        ) . "\n";
        $admin_message .= sprintf(
            /* translators: %s: hospital name. */
            __('Hospital: %s', 'obydullah-blood-bank-manager'),
            $request_data['hospital_name']
        ) . "\n";
        $admin_message .= sprintf(
            /* translators: %s: urgency level of the request. */
            __('Urgency: %s', 'obydullah-blood-bank-manager'),
            $urgency_labels_raw
        ) . "\n";
        $admin_message .= sprintf(
            /* translators: 1: requester name, 2: requester phone number. */
            __('Contact: %1$s - %2$s', 'obydullah-blood-bank-manager'),
            $request_data['requester_name'],
            $request_data['requester_phone']
        ) . "\n";
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($notify_email, $admin_subject, nl2br($admin_message), $headers);
    }
}
