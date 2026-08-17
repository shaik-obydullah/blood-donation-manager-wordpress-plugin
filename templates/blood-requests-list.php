<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$search = isset($_GET['bdm_search']) ? sanitize_text_field($_GET['bdm_search']) : '';
$filter_blood = isset($_GET['bdm_blood_type']) ? sanitize_text_field($_GET['bdm_blood_type']) : '';
$filter_status = isset($_GET['bdm_status']) ? sanitize_text_field($_GET['bdm_status']) : '';
$filter_urgency = isset($_GET['bdm_urgency']) ? sanitize_text_field($_GET['bdm_urgency']) : '';

$where = "WHERE status != 'cancelled'";
if ($search) {
    $where .= $wpdb->prepare(" AND (patient_name LIKE %s OR hospital_name LIKE %s)", "%$search%", "%$search%");
}
if ($filter_blood) {
    $where .= $wpdb->prepare(" AND blood_type_needed = %s", $filter_blood);
}
if ($filter_status) {
    $where .= $wpdb->prepare(" AND status = %s", $filter_status);
}
if ($filter_urgency) {
    $where .= $wpdb->prepare(" AND urgency = %s", $filter_urgency);
}

$requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_requests $where ORDER BY 
    CASE urgency WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END, created_at DESC");
$blood_types = Blood_Donation_Manager::get_blood_types();
$urgency_labels = Blood_Donation_Manager::get_urgency_labels();
$status_labels = Blood_Donation_Manager::get_status_labels();
?>

<div class="bdm-list-wrapper bdm-requests-list">
    <div class="bdm-list-header">
        <h2><?php _e('Blood Donation Requests', 'blood-donation'); ?></h2>
        <p><?php _e('People who need blood donations.', 'blood-donation'); ?></p>
    </div>

    <div class="bdm-filters">
        <form method="get" action="" class="bdm-filter-form">
            <div class="bdm-filter-row">
                <input type="text" name="bdm_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search requests...', 'blood-donation'); ?>">
                <select name="bdm_blood_type">
                    <option value=""><?php _e('All Blood Types', 'blood-donation'); ?></option>
                    <?php foreach ($blood_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php selected($filter_blood, $type); ?>><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="bdm_status">
                    <option value=""><?php _e('All Statuses', 'blood-donation'); ?></option>
                    <?php foreach ($status_labels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php selected($filter_status, $key); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="bdm_urgency">
                    <option value=""><?php _e('All Urgency Levels', 'blood-donation'); ?></option>
                    <?php foreach ($urgency_labels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php selected($filter_urgency, $key); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bdm-btn"><?php _e('Search', 'blood-donation'); ?></button>
            </div>
        </form>
    </div>

    <?php if (empty($requests)): ?>
        <div class="bdm-no-results">
            <p><?php _e('No blood requests found.', 'blood-donation'); ?></p>
        </div>
    <?php else: ?>
        <div class="bdm-grid bdm-requests-grid">
            <?php foreach ($requests as $req): ?>
                <div class="bdm-card bdm-request-card bdm-urgency-<?php echo $req->urgency; ?>">
                    <div class="bdm-card-header">
                        <div class="bdm-request-urgency">
                            <span class="bdm-urgency-badge bdm-urgency-<?php echo $req->urgency; ?>"><?php echo $urgency_labels[$req->urgency]; ?></span>
                            <span class="bdm-status-badge bdm-status-<?php echo $req->status; ?>"><?php echo $status_labels[$req->status]; ?></span>
                        </div>
                    </div>
                    <div class="bdm-card-body">
                        <div class="bdm-request-blood-needed">
                            <span class="bdm-blood-type-large bdm-blood-<?php echo strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $req->blood_type_needed))); ?>">
                                <?php echo esc_html($req->blood_type_needed); ?>
                            </span>
                            <span class="bdm-units-needed"><?php echo sprintf(_n('%d unit', '%d units', $req->units_needed, 'blood-donation'), $req->units_needed); ?></span>
                        </div>
                        <h3><?php echo esc_html($req->patient_name); ?></h3>
                        <div class="bdm-request-info">
                            <span class="bdm-info-item">
                                <span class="dashicons dashicons-building"></span>
                                <?php echo esc_html($req->hospital_name); ?>
                            </span>
                            <?php if ($req->city): ?>
                                <span class="bdm-info-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($req->city); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($req->needed_by): ?>
                                <span class="bdm-info-item">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php printf(__('Needed by: %s', 'blood-donation'), date_i18n(get_option('date_format'), strtotime($req->needed_by))); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($req->status === 'pending'): ?>
                            <div class="bdm-request-contact">
                                <a href="mailto:<?php echo esc_attr($req->requester_email); ?>" class="bdm-btn bdm-btn-sm">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php _e('Contact', 'blood-donation'); ?>
                                </a>
                                <a href="tel:<?php echo esc_attr($req->requester_phone); ?>" class="bdm-btn bdm-btn-sm">
                                    <span class="dashicons dashicons-phone"></span>
                                    <?php _e('Call', 'blood-donation'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
