<?php
if (!defined('ABSPATH')) exit;

// Defence in depth: add_submenu_page() already gates this screen on
// manage_options, so this only fires if the file is ever included directly.
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have permission to access this page.', 'obydullah-blood-bank-manager'));
}

$obdm = Obdm_Blood_Bank_Manager::admin_requests_data();

$obdm_action  = Obdm_Blood_Bank_Manager::admin_record_action();
$obdm_id      = Obdm_Blood_Bank_Manager::admin_record_id();
$obdm_missing = false;

if ($obdm_action === 'edit' && $obdm_id) {
    $obdm_record = Obdm_Blood_Bank_Manager::admin_request_record($obdm_id);

    if (!$obdm_record->id) {
        $obdm_missing = true;
        $obdm_action  = 'list';
    }
} else {
    $obdm_record = Obdm_Blood_Bank_Manager::admin_request_record(0);
}

$obdm_total          = $obdm['total'];
$obdm_requests       = $obdm['items'];
$obdm_total_pages    = $obdm['total_pages'];
$obdm_page           = $obdm['page'];
$obdm_pending_count  = $obdm['pending_count'];
$obdm_search         = $obdm['search'];
$obdm_filter_blood   = $obdm['blood_type'];
$obdm_filter_status  = $obdm['status'];
$obdm_filter_urgency = $obdm['urgency'];
$obdm_message        = Obdm_Blood_Bank_Manager::admin_notice_message();
$obdm_blood_types    = Obdm_Blood_Bank_Manager::get_blood_types();
$obdm_urgency_labels = Obdm_Blood_Bank_Manager::get_urgency_labels();
$obdm_status_labels  = Obdm_Blood_Bank_Manager::get_status_labels();
$obdm_has_filters    = '' !== $obdm_search || '' !== $obdm_filter_blood || '' !== $obdm_filter_status || '' !== $obdm_filter_urgency;
?>

<div class="wrap obdm-wrap">
    <?php if ($obdm_missing): ?>
        <div class="notice notice-error"><p><?php esc_html_e('That record could not be found.', 'obydullah-blood-bank-manager'); ?></p></div>
    <?php endif; ?>
    <?php if ($obdm_action === 'add' || $obdm_action === 'edit'): ?>

        <?php
        Obdm_Blood_Bank_Manager::ui_page_header([
            'title'    => $obdm_id ? __('Edit Blood Request', 'obydullah-blood-bank-manager') : __('New Blood Request', 'obydullah-blood-bank-manager'),
            'subtitle' => __('Record a request and notify matching donors.', 'obydullah-blood-bank-manager'),
            'back_url' => admin_url('admin.php?page=obdm-requests'),
        ]);
        ?>

        <form method="post" action="" class="obdm-form">
            <?php wp_nonce_field('obdm_request_action', 'obdm_request_nonce'); ?>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Requester', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field">
                        <label for="requester_name"><?php esc_html_e('Requester Name', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="requester_name" id="requester_name" value="<?php echo esc_attr($obdm_record->requester_name); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="requester_email"><?php esc_html_e('Requester Email', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="email" name="requester_email" id="requester_email" value="<?php echo esc_attr($obdm_record->requester_email); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="requester_phone"><?php esc_html_e('Requester Phone', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="requester_phone" id="requester_phone" value="<?php echo esc_attr($obdm_record->requester_phone); ?>" required>
                    </p>
                </div>
            </div>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Patient & Requirement', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field">
                        <label for="patient_name"><?php esc_html_e('Patient Name', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="patient_name" id="patient_name" value="<?php echo esc_attr($obdm_record->patient_name); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="blood_type_needed"><?php esc_html_e('Blood Type Needed', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <select name="blood_type_needed" id="blood_type_needed" required>
                            <option value=""><?php esc_html_e('Select Blood Type', 'obydullah-blood-bank-manager'); ?></option>
                            <?php foreach ($obdm_blood_types as $obdm_type): ?>
                                <option value="<?php echo esc_attr($obdm_type); ?>" <?php selected($obdm_record->blood_type_needed, $obdm_type); ?>><?php echo esc_html($obdm_type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p class="obdm-field">
                        <label for="units_needed"><?php esc_html_e('Units Needed', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="number" name="units_needed" id="units_needed" value="<?php echo esc_attr($obdm_record->units_needed); ?>" min="1" max="10" required>
                    </p>
                    <p class="obdm-field">
                        <label for="urgency"><?php esc_html_e('Urgency', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <select name="urgency" id="urgency" required>
                            <?php foreach ($obdm_urgency_labels as $obdm_key => $obdm_label): ?>
                                <option value="<?php echo esc_attr($obdm_key); ?>" <?php selected($obdm_record->urgency, $obdm_key); ?>><?php echo esc_html($obdm_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p class="obdm-field">
                        <label for="needed_by"><?php esc_html_e('Needed By', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="date" name="needed_by" id="needed_by" value="<?php echo esc_attr($obdm_record->needed_by); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="status"><?php esc_html_e('Status', 'obydullah-blood-bank-manager'); ?></label>
                        <select name="status" id="status">
                            <?php foreach ($obdm_status_labels as $obdm_key => $obdm_label): ?>
                                <option value="<?php echo esc_attr($obdm_key); ?>" <?php selected($obdm_record->status, $obdm_key); ?>><?php echo esc_html($obdm_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p class="obdm-field obdm-field--full">
                        <label for="additional_info"><?php esc_html_e('Additional Info', 'obydullah-blood-bank-manager'); ?></label>
                        <textarea name="additional_info" id="additional_info" rows="3"><?php echo esc_textarea($obdm_record->additional_info); ?></textarea>
                    </p>
                </div>
            </div>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Hospital', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field obdm-field--full">
                        <label for="hospital_name"><?php esc_html_e('Hospital Name', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="hospital_name" id="hospital_name" value="<?php echo esc_attr($obdm_record->hospital_name); ?>" required>
                    </p>
                    <p class="obdm-field obdm-field--full">
                        <label for="hospital_address"><?php esc_html_e('Hospital Address', 'obydullah-blood-bank-manager'); ?></label>
                        <textarea name="hospital_address" id="hospital_address" rows="3"><?php echo esc_textarea($obdm_record->hospital_address); ?></textarea>
                    </p>
                    <p class="obdm-field">
                        <label for="city"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="city" id="city" value="<?php echo esc_attr($obdm_record->city); ?>">
                    </p>
                </div>
            </div>

            <div class="obdm-form__actions">
                <input type="submit" class="obdm-button obdm-button--primary" value="<?php esc_attr_e('Save Request', 'obydullah-blood-bank-manager'); ?>">
                <a class="obdm-button obdm-button--ghost" href="<?php echo esc_url(admin_url('admin.php?page=obdm-requests')); ?>"><?php esc_html_e('Cancel', 'obydullah-blood-bank-manager'); ?></a>
            </div>
        </form>

    <?php else: ?>

        <?php
        Obdm_Blood_Bank_Manager::ui_page_header([
            'title'        => __('Blood Requests', 'obydullah-blood-bank-manager'),
            'subtitle'     => sprintf(
                /* translators: 1: total number of requests, 2: number of pending requests. */
                __('%1$s total, %2$s still pending.', 'obydullah-blood-bank-manager'),
                number_format_i18n($obdm_total),
                number_format_i18n($obdm_pending_count)
            ),
            'action_url'   => admin_url('admin.php?page=obdm-requests&action=add'),
            'action_label' => __('New request', 'obydullah-blood-bank-manager'),
        ]);
        ?>

        <?php if ($obdm_message): ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html($obdm_message); ?></p></div>
        <?php endif; ?>

        <div class="obdm-toolbar">
            <form method="get" action="" class="obdm-toolbar__form">
                <input type="hidden" name="page" value="obdm-requests">
                <label class="obdm-toolbar__field obdm-toolbar__field--search">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Search', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-search')); ?>
                        <input type="search" name="s" value="<?php echo esc_attr($obdm_search); ?>" placeholder="<?php esc_attr_e('Patient, hospital or city', 'obydullah-blood-bank-manager'); ?>">
                    </span>
                </label>
                <label class="obdm-toolbar__field">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Blood type', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <select name="blood_type">
                            <option value=""><?php esc_html_e('All blood types', 'obydullah-blood-bank-manager'); ?></option>
                            <?php foreach ($obdm_blood_types as $obdm_type): ?>
                                <option value="<?php echo esc_attr($obdm_type); ?>" <?php selected($obdm_filter_blood, $obdm_type); ?>><?php echo esc_html($obdm_type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
                <label class="obdm-toolbar__field">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Status', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <select name="status">
                            <option value=""><?php esc_html_e('All statuses', 'obydullah-blood-bank-manager'); ?></option>
                            <?php foreach ($obdm_status_labels as $obdm_key => $obdm_label): ?>
                                <option value="<?php echo esc_attr($obdm_key); ?>" <?php selected($obdm_filter_status, $obdm_key); ?>><?php echo esc_html($obdm_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
                <label class="obdm-toolbar__field">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Urgency', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <select name="urgency">
                            <option value=""><?php esc_html_e('All levels', 'obydullah-blood-bank-manager'); ?></option>
                            <?php foreach ($obdm_urgency_labels as $obdm_key => $obdm_label): ?>
                                <option value="<?php echo esc_attr($obdm_key); ?>" <?php selected($obdm_filter_urgency, $obdm_key); ?>><?php echo esc_html($obdm_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
                <div class="obdm-toolbar__actions">
                    <button type="submit" class="obdm-button obdm-button--primary"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-filter')); ?><?php esc_html_e('Apply filters', 'obydullah-blood-bank-manager'); ?></button>
                    <?php if ($obdm_search || $obdm_filter_blood || $obdm_filter_status || $obdm_filter_urgency): ?>
                        <a class="obdm-button obdm-button--ghost" href="<?php echo esc_url(admin_url('admin.php?page=obdm-requests')); ?>"><?php esc_html_e('Reset', 'obydullah-blood-bank-manager'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="obdm-table-card">
            <table class="obdm-table">
                <thead>
                    <tr>
                        <th class="obdm-col-name"><?php esc_html_e('Patient', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-blood"><?php esc_html_e('Blood type', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-units"><?php esc_html_e('Units', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-hospital"><?php esc_html_e('Hospital', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-urgency"><?php esc_html_e('Urgency', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-status"><?php esc_html_e('Status', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-date"><?php esc_html_e('Requested', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-actions"><?php esc_html_e('Actions', 'obydullah-blood-bank-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($obdm_requests)): ?>
                        <tr>
                            <td colspan="8">
                                <?php
                                $obdm_has_filters = $obdm_search || $obdm_filter_blood || $obdm_filter_status || $obdm_filter_urgency;
                                Obdm_Blood_Bank_Manager::ui_empty_state([
                                    'icon'         => 'dashicons-email-alt',
                                    'title'        => $obdm_has_filters
                                        ? __('No requests match these filters', 'obydullah-blood-bank-manager')
                                        : __('No blood requests yet', 'obydullah-blood-bank-manager'),
                                    'description'  => $obdm_has_filters
                                        ? __('Try clearing the filters to see every request.', 'obydullah-blood-bank-manager')
                                        : __('Requests submitted from the front end will appear here for matching.', 'obydullah-blood-bank-manager'),
                                    'action_url'   => $obdm_has_filters ? '' : admin_url('admin.php?page=obdm-requests&action=add'),
                                    'action_label' => $obdm_has_filters ? '' : __('Create request', 'obydullah-blood-bank-manager'),
                                ]);
                                ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($obdm_requests as $obdm_req): ?>
                            <tr>
                                <td class="obdm-col-name">
                                    <div class="obdm-identity">
                                        <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_avatar($obdm_req->patient_name)); ?>
                                        <div class="obdm-identity__text">
                                            <span class="obdm-identity__name"><?php echo esc_html($obdm_req->patient_name); ?></span>
                                            <span class="obdm-identity__meta"><?php echo esc_html($obdm_req->requester_name); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="obdm-col-blood"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_blood_badge($obdm_req->blood_type_needed)); ?></td>
                                <td class="obdm-col-units"><?php echo esc_html(number_format_i18n($obdm_req->units_needed)); ?></td>
                                <td class="obdm-col-hospital"><?php echo esc_html($obdm_req->hospital_name); ?></td>
                                <td class="obdm-col-urgency"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_urgency_badge($obdm_req->urgency)); ?></td>
                                <td class="obdm-col-status"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_status_badge($obdm_req->status)); ?></td>
                                <td class="obdm-col-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($obdm_req->created_at))); ?></td>
                                <td class="obdm-col-actions">
                                    <?php
                                    Obdm_Blood_Bank_Manager::ui_row_actions(
                                        admin_url('admin.php?page=obdm-requests&action=edit&id=' . $obdm_req->id),
                                        wp_nonce_url(admin_url('admin.php?page=obdm-requests&action=delete&id=' . $obdm_req->id), 'obdm_delete_request_' . $obdm_req->id),
                                        __('Are you sure you want to delete this request?', 'obydullah-blood-bank-manager')
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php Obdm_Blood_Bank_Manager::ui_pagination($obdm_page, $obdm_total_pages, $obdm_total); ?>
    <?php endif; ?>
</div>
