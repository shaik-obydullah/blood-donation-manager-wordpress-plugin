<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$total_donors = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_donors WHERE is_available = 1");
$total_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_requests WHERE status = 'pending'");
$total_blood_banks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_blood_banks WHERE is_active = 1");

$blood_type_stats = $wpdb->get_results("SELECT blood_type, COUNT(*) as count FROM {$wpdb->prefix}bdm_donors WHERE is_available = 1 GROUP BY blood_type ORDER BY blood_type");

$recent_requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_requests WHERE status = 'pending' ORDER BY 
    CASE urgency WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END, created_at DESC LIMIT 6");

$urgency_labels = Blood_Donation_Manager::get_urgency_labels();
$status_labels = Blood_Donation_Manager::get_status_labels();
?>
<div class="bdm-dashboard-wrapper">
    <div class="bdm-dashboard-header">
        <h2><?php _e('Blood Donation Dashboard', 'blood-donation'); ?></h2>
    </div>

    <div class="bdm-stats-row">
        <div class="bdm-stat-card bdm-stat-donors">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="bdm-stat-info">
                <span class="bdm-stat-number"><?php echo $total_donors; ?></span>
                <span class="bdm-stat-label"><?php _e('Available Donors', 'blood-donation'); ?></span>
            </div>
        </div>
        <div class="bdm-stat-card bdm-stat-requests">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-email-alt"></span></div>
            <div class="bdm-stat-info">
                <span class="bdm-stat-number"><?php echo $total_requests; ?></span>
                <span class="bdm-stat-label"><?php _e('Pending Requests', 'blood-donation'); ?></span>
            </div>
        </div>
        <div class="bdm-stat-card bdm-stat-banks">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-building"></span></div>
            <div class="bdm-stat-info">
                <span class="bdm-stat-number"><?php echo $total_blood_banks; ?></span>
                <span class="bdm-stat-label"><?php _e('Blood Banks', 'blood-donation'); ?></span>
            </div>
        </div>
    </div>

    <div class="bdm-dashboard-columns">
        <div class="bdm-dashboard-col">
            <div class="bdm-card">
                <h3><?php _e('Donors by Blood Type', 'blood-donation'); ?></h3>
                <div class="bdm-blood-type-chart">
                    <?php foreach ($blood_type_stats as $stat): ?>
                        <div class="bdm-blood-bar">
                            <span class="bdm-blood-label"><?php echo esc_html($stat->blood_type); ?></span>
                            <div class="bdm-blood-bar-fill" style="width: <?php echo $total_donors > 0 ? ($stat->count / $total_donors * 100) : 0; ?>%">
                                <span><?php echo $stat->count; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($blood_type_stats)): ?>
                        <p class="bdm-no-data"><?php _e('No donor data available.', 'blood-donation'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bdm-dashboard-col">
            <div class="bdm-card">
                <h3><?php _e('Urgent Requests', 'blood-donation'); ?></h3>
                <?php if (empty($recent_requests)): ?>
                    <p class="bdm-no-data"><?php _e('No pending requests.', 'blood-donation'); ?></p>
                <?php else: ?>
                    <div class="bdm-requests-compact">
                        <?php foreach ($recent_requests as $req): ?>
                            <div class="bdm-request-item bdm-urgency-<?php echo $req->urgency; ?>">
                                <div class="bdm-request-blood">
                                    <span class="bdm-blood-badge bdm-blood-<?php echo strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $req->blood_type_needed))); ?>">
                                        <?php echo esc_html($req->blood_type_needed); ?>
                                    </span>
                                    <span class="bdm-req-units"><?php echo $req->units_needed; ?>u</span>
                                </div>
                                <div class="bdm-request-details">
                                    <strong><?php echo esc_html($req->patient_name); ?></strong>
                                    <small><?php echo esc_html($req->hospital_name); ?></small>
                                </div>
                                <span class="bdm-urgency-dot bdm-urgency-<?php echo $req->urgency; ?>"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bdm-dashboard-actions">
        <a href="<?php echo wp_lostpassword_url(); ?>" class="bdm-btn bdm-btn-primary">
            <span class="dashicons dashicons-admin-users"></span>
            <?php _e('Register as Donor', 'blood-donation'); ?>
        </a>
        <a href="#" class="bdm-btn bdm-btn-secondary">
            <span class="dashicons dashicons-email-alt"></span>
            <?php _e('Request Blood', 'blood-donation'); ?>
        </a>
    </div>
</div>
