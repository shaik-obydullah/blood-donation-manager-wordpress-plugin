<?php
if (!defined('ABSPATH')) exit;

$obdm = Obdm_Blood_Bank_Manager::public_dashboard_data();

$obdm_total_donors      = $obdm['total_donors'];
$obdm_total_requests    = $obdm['total_requests'];
$obdm_total_blood_banks = $obdm['total_blood_banks'];
$obdm_blood_type_stats  = $obdm['blood_type_stats'];
$obdm_recent_requests   = $obdm['recent_requests'];
$obdm_urgency_labels    = Obdm_Blood_Bank_Manager::get_urgency_labels();
$obdm_status_labels     = Obdm_Blood_Bank_Manager::get_status_labels();
?>
<div class="obdm-dashboard-wrapper">
    <div class="obdm-dashboard-header">
        <span class="obdm-section-icon"><span class="dashicons dashicons-heart"></span></span>
        <div class="obdm-section-text">
            <h2><?php esc_html_e('Blood Donation Dashboard', 'obydullah-blood-bank-manager'); ?></h2>
            <p><?php esc_html_e('Live community numbers at a glance.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    </div>

    <div class="obdm-stats-row">
        <div class="obdm-stat-card obdm-stat-donors">
            <div class="obdm-stat-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="obdm-stat-info">
                <span class="obdm-stat-number"><?php echo esc_html(number_format_i18n($obdm_total_donors)); ?></span>
                <span class="obdm-stat-label"><?php esc_html_e('Available Donors', 'obydullah-blood-bank-manager'); ?></span>
            </div>
        </div>
        <div class="obdm-stat-card obdm-stat-requests">
            <div class="obdm-stat-icon"><span class="dashicons dashicons-email-alt"></span></div>
            <div class="obdm-stat-info">
                <span class="obdm-stat-number"><?php echo esc_html(number_format_i18n($obdm_total_requests)); ?></span>
                <span class="obdm-stat-label"><?php esc_html_e('Pending Requests', 'obydullah-blood-bank-manager'); ?></span>
            </div>
        </div>
        <div class="obdm-stat-card obdm-stat-banks">
            <div class="obdm-stat-icon"><span class="dashicons dashicons-building"></span></div>
            <div class="obdm-stat-info">
                <span class="obdm-stat-number"><?php echo esc_html(number_format_i18n($obdm_total_blood_banks)); ?></span>
                <span class="obdm-stat-label"><?php esc_html_e('Blood Banks', 'obydullah-blood-bank-manager'); ?></span>
            </div>
        </div>
    </div>

    <div class="obdm-dashboard-columns">
        <div class="obdm-dashboard-col">
            <div class="obdm-card">
                <h3><?php esc_html_e('Donors by Blood Type', 'obydullah-blood-bank-manager'); ?></h3>
                <div class="obdm-blood-type-chart">
                    <?php foreach ($obdm_blood_type_stats as $obdm_stat): ?>
                        <div class="obdm-blood-bar">
                            <span class="obdm-blood-label"><?php echo esc_html($obdm_stat->label); ?></span>
                            <div class="obdm-blood-bar-fill" style="width: <?php echo esc_attr($obdm_total_donors > 0 ? ($obdm_stat->total / $obdm_total_donors * 100) : 0); ?>%">
                                <span><?php echo esc_html(number_format_i18n($obdm_stat->total)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($obdm_blood_type_stats)): ?>
                        <p class="obdm-no-data"><?php esc_html_e('No donor data available yet.', 'obydullah-blood-bank-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="obdm-dashboard-col">
            <div class="obdm-card">
                <h3><?php esc_html_e('Urgent Requests', 'obydullah-blood-bank-manager'); ?></h3>
                <?php if (empty($obdm_recent_requests)): ?>
                    <p class="obdm-no-data"><?php esc_html_e('No pending requests.', 'obydullah-blood-bank-manager'); ?></p>
                <?php else: ?>
                    <div class="obdm-requests-compact">
                        <?php foreach ($obdm_recent_requests as $obdm_req): ?>
                            <div class="obdm-request-item obdm-urgency-<?php echo esc_attr($obdm_req->urgency); ?>">
                                <div class="obdm-request-blood">
                                    <span class="obdm-blood-badge obdm-blood-<?php echo esc_attr(strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $obdm_req->blood_type_needed)))); ?>">
                                        <?php echo esc_html($obdm_req->blood_type_needed); ?>
                                    </span>
                                    <span class="obdm-req-units"><?php echo esc_html(number_format_i18n($obdm_req->units_needed)); ?>u</span>
                                </div>
                                <div class="obdm-request-details">
                                    <strong><?php echo esc_html($obdm_req->patient_name); ?></strong>
                                    <small><?php echo esc_html($obdm_req->hospital_name); ?></small>
                                </div>
                                <span class="obdm-urgency-dot obdm-urgency-<?php echo esc_attr($obdm_req->urgency); ?>"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="obdm-dashboard-actions">
        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="obdm-btn obdm-btn-primary">
            <span class="dashicons dashicons-admin-users"></span>
            <?php esc_html_e('Register as Donor', 'obydullah-blood-bank-manager'); ?>
        </a>
        <a href="#" class="obdm-btn obdm-btn-secondary">
            <span class="dashicons dashicons-email-alt"></span>
            <?php esc_html_e('Request Blood', 'obydullah-blood-bank-manager'); ?>
        </a>
    </div>
</div>
