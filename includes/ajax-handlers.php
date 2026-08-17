<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_bdm_register_donor', 'bdm_ajax_register_donor');
add_action('wp_ajax_nopriv_bdm_register_donor', 'bdm_ajax_register_donor');
add_action('wp_ajax_bdm_submit_request', 'bdm_ajax_submit_request');
add_action('wp_ajax_nopriv_bdm_submit_request', 'bdm_ajax_submit_request');

function bdm_ajax_register_donor() {
    check_ajax_referer('bdm_frontend_nonce', 'nonce');
    
    global $wpdb;
    
    $required_fields = array('first_name', 'last_name', 'email', 'phone', 'blood_type');
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(array('message' => sprintf(__('Please fill in all required fields. Missing: %s', 'blood-donation'), $field)));
        }
    }
    
    $email = sanitize_email($_POST['email']);
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}bdm_donors WHERE email = %s",
        $email
    ));
    
    if ($existing > 0) {
        wp_send_json_error(array('message' => __('A donor with this email already exists.', 'blood-donation')));
    }
    
    $settings = get_option('bdm_settings', array());
    $min_weight = isset($settings['min_weight']) ? floatval($settings['min_weight']) : 50;
    $weight = floatval($_POST['weight']);
    
    if ($weight > 0 && $weight < $min_weight) {
        wp_send_json_error(array('message' => sprintf(__('Minimum weight required is %s kg.', 'blood-donation'), $min_weight)));
    }
    
    $data = array(
        'user_id'            => get_current_user_id(),
        'first_name'         => sanitize_text_field($_POST['first_name']),
        'last_name'          => sanitize_text_field($_POST['last_name']),
        'email'              => $email,
        'phone'              => sanitize_text_field($_POST['phone']),
        'blood_type'         => sanitize_text_field($_POST['blood_type']),
        'date_of_birth'      => sanitize_text_field($_POST['date_of_birth']),
        'gender'             => sanitize_text_field($_POST['gender']),
        'weight'             => $weight,
        'address'            => sanitize_textarea_field($_POST['address']),
        'city'               => sanitize_text_field($_POST['city']),
        'state'              => sanitize_text_field($_POST['state']),
        'zip_code'           => sanitize_text_field($_POST['zip_code']),
        'country'            => sanitize_text_field($_POST['country']),
        'last_donation_date' => sanitize_text_field($_POST['last_donation_date']),
        'medical_conditions' => sanitize_textarea_field($_POST['medical_conditions']),
        'is_available'       => intval($_POST['is_available']),
    );
    
    $result = $wpdb->insert("{$wpdb->prefix}bdm_donors", $data);
    
    if ($result) {
        bdm_send_donor_welcome_email($data);
        wp_send_json_success(array('message' => __('Thank you for registering as a blood donor!', 'blood-donation')));
    } else {
        wp_send_json_error(array('message' => __('Registration failed. Please try again.', 'blood-donation')));
    }
}

function bdm_ajax_submit_request() {
    check_ajax_referer('bdm_frontend_nonce', 'nonce');
    
    global $wpdb;
    
    $required_fields = array('requester_name', 'requester_email', 'requester_phone', 'patient_name', 'blood_type_needed', 'hospital_name');
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(array('message' => sprintf(__('Please fill in all required fields. Missing: %s', 'blood-donation'), $field)));
        }
    }
    
    $data = array(
        'requester_name'    => sanitize_text_field($_POST['requester_name']),
        'requester_email'   => sanitize_email($_POST['requester_email']),
        'requester_phone'   => sanitize_text_field($_POST['requester_phone']),
        'patient_name'      => sanitize_text_field($_POST['patient_name']),
        'blood_type_needed' => sanitize_text_field($_POST['blood_type_needed']),
        'units_needed'      => intval($_POST['units_needed']),
        'hospital_name'     => sanitize_text_field($_POST['hospital_name']),
        'hospital_address'  => sanitize_textarea_field($_POST['hospital_address']),
        'city'              => sanitize_text_field($_POST['city']),
        'urgency'           => sanitize_text_field($_POST['urgency']),
        'needed_by'         => sanitize_text_field($_POST['needed_by']),
        'additional_info'   => sanitize_textarea_field($_POST['additional_info']),
    );
    
    $result = $wpdb->insert("{$wpdb->prefix}bdm_requests", $data);
    
    if ($result) {
        bdm_send_request_notification($data);
        wp_send_json_success(array('message' => __('Your blood request has been submitted successfully.', 'blood-donation')));
    } else {
        wp_send_json_error(array('message' => __('Request submission failed. Please try again.', 'blood-donation')));
    }
}

function bdm_send_donor_welcome_email($donor_data) {
    $settings = get_option('bdm_settings', array());
    
    if (empty($settings['enable_notifications'])) {
        return;
    }
    
    $to = $donor_data['email'];
    $subject = isset($settings['donor_email_subject']) ? $settings['donor_email_subject'] : __('Welcome to Our Blood Donation Community', 'blood-donation');
    
    $message = sprintf(__('Dear %s,', 'blood-donation'), $donor_data['first_name']) . "\n\n";
    $message .= __('Thank you for registering as a blood donor. Your registration has been received successfully.', 'blood-donation') . "\n\n";
    $message .= __('Your Details:', 'blood-donation') . "\n";
    $message .= sprintf(__('Blood Type: %s', 'blood-donation'), $donor_data['blood_type']) . "\n";
    $message .= sprintf(__('City: %s', 'blood-donation'), $donor_data['city']) . "\n\n";
    $message .= __('We will contact you when there is a blood request in your area.', 'blood-donation') . "\n\n";
    $message .= __('Best regards,', 'blood-donation') . "\n";
    $message .= get_bloginfo('name');
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($to, $subject, nl2br($message), $headers);
    
    $notify_email = isset($settings['notify_email']) ? $settings['notify_email'] : get_option('admin_email');
    if ($notify_email && $notify_email !== $to) {
        $admin_subject = __('New Blood Donor Registration', 'blood-donation');
        $admin_message = sprintf(__('A new donor has registered:', 'blood-donation')) . "\n\n";
        $admin_message .= sprintf(__('Name: %s %s', 'blood-donation'), $donor_data['first_name'], $donor_data['last_name']) . "\n";
        $admin_message .= sprintf(__('Email: %s', 'blood-donation'), $donor_data['email']) . "\n";
        $admin_message .= sprintf(__('Blood Type: %s', 'blood-donation'), $donor_data['blood_type']) . "\n";
        $admin_message .= sprintf(__('City: %s', 'blood-donation'), $donor_data['city']) . "\n";
        
        wp_mail($notify_email, $admin_subject, nl2br($admin_message), $headers);
    }
}

function bdm_send_request_notification($request_data) {
    $settings = get_option('bdm_settings', array());
    
    if (empty($settings['enable_notifications'])) {
        return;
    }
    
    global $wpdb;
    
    $compatible_types = Blood_Donation_Manager::get_compatible_blood_types($request_data['blood_type_needed']);
    $placeholders = implode(',', array_fill(0, count($compatible_types), '%s'));
    
    $available_donors = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bdm_donors WHERE blood_type IN ($placeholders) AND is_available = 1",
        ...$compatible_types
    ));
    
    if (!empty($available_donors)) {
        $to_emails = array();
        foreach ($available_donors as $donor) {
            $to_emails[] = $donor->email;
        }
        
        $to = implode(', ', array_unique($to_emails));
        $subject = isset($settings['request_email_subject']) ? $settings['request_email_subject'] : __('Blood Donation Needed - Urgent Request', 'blood-donation');
        
        $urgency_labels = Blood_Donation_Manager::get_urgency_labels();
        $urgency = isset($urgency_labels[$request_data['urgency']]) ? $urgency_labels[$request_data['urgency']] : $request_data['urgency'];
        
        $message = sprintf(__('Dear Donor,', 'blood-donation')) . "\n\n";
        $message .= sprintf(__('A new blood donation request has been posted that matches your blood type (%s).', 'blood-donation'), $request_data['blood_type_needed']) . "\n\n";
        $message .= __('Request Details:', 'blood-donation') . "\n";
        $message .= sprintf(__('Patient: %s', 'blood-donation'), $request_data['patient_name']) . "\n";
        $message .= sprintf(__('Blood Type Needed: %s', 'blood-donation'), $request_data['blood_type_needed']) . "\n";
        $message .= sprintf(__('Units Needed: %d', 'blood-donation'), $request_data['units_needed']) . "\n";
        $message .= sprintf(__('Hospital: %s', 'blood-donation'), $request_data['hospital_name']) . "\n";
        $message .= sprintf(__('Urgency: %s', 'blood-donation'), $urgency) . "\n\n";
        
        if ($request_data['needed_by']) {
            $message .= sprintf(__('Needed By: %s', 'blood-donation'), date_i18n(get_option('date_format'), strtotime($request_data['needed_by']))) . "\n\n";
        }
        
        $message .= sprintf(__('Contact: %s - %s', 'blood-donation'), $request_data['requester_name'], $request_data['requester_phone']) . "\n\n";
        $message .= __('If you can donate, please contact the requester directly.', 'blood-donation') . "\n\n";
        $message .= __('Best regards,', 'blood-donation') . "\n";
        $message .= get_bloginfo('name');
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    $notify_email = isset($settings['notify_email']) ? $settings['notify_email'] : get_option('admin_email');
    if ($notify_email) {
        $admin_subject = __('New Blood Donation Request', 'blood-donation');
        $admin_message = sprintf(__('A new blood request has been submitted:', 'blood-donation')) . "\n\n";
        $admin_message .= sprintf(__('Patient: %s', 'blood-donation'), $request_data['patient_name']) . "\n";
        $admin_message .= sprintf(__('Blood Type: %s', 'blood-donation'), $request_data['blood_type_needed']) . "\n";
        $admin_message .= sprintf(__('Hospital: %s', 'blood-donation'), $request_data['hospital_name']) . "\n";
        $admin_message .= sprintf(__('Urgency: %s', 'blood-donation'), $request_data['urgency']) . "\n";
        $admin_message .= sprintf(__('Contact: %s - %s', 'blood-donation'), $request_data['requester_name'], $request_data['requester_phone']) . "\n";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($notify_email, $admin_subject, nl2br($admin_message), $headers);
    }
}
