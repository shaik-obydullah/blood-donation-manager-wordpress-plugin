<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$total_donors = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_donors");
$available_donors = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_donors WHERE is_available = 1");
$total_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_requests");
$pending_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_requests WHERE status = 'pending'");
$fulfilled_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_requests WHERE status = 'fulfilled'");
$total_blood_banks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_blood_banks WHERE is_active = 1");

$blood_type_stats = $wpdb->get_results("SELECT blood_type, COUNT(*) as count FROM {$wpdb->prefix}bdm_donors GROUP BY blood_type ORDER BY blood_type");

$recent_donors = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_donors ORDER BY created_at DESC LIMIT 5");
$recent_requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_requests ORDER BY created_at DESC LIMIT 5");
?>

<div class="wrap bdm-wrap">
    <h1><span class="dashicons dashicons-heart"></span> <?php _e('Blood Donation Dashboard', 'blood-donation'); ?></h1>

    <div class="bdm-stats-grid">
        <div class="bdm-stat-card bdm-stat-donors">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="bdm-stat-content">
                <h3><?php echo $total_donors; ?></h3>
                <p><?php _e('Total Donors', 'blood-donation'); ?></p>
            </div>
        </div>

        <div class="bdm-stat-card bdm-stat-available">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="bdm-stat-content">
                <h3><?php echo $available_donors; ?></h3>
                <p><?php _e('Available Donors', 'blood-donation'); ?></p>
            </div>
        </div>

        <div class="bdm-stat-card bdm-stat-requests">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-email-alt"></span></div>
            <div class="bdm-stat-content">
                <h3><?php echo $total_requests; ?></h3>
                <p><?php _e('Total Requests', 'blood-donation'); ?></p>
            </div>
        </div>

        <div class="bdm-stat-card bdm-stat-pending">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="bdm-stat-content">
                <h3><?php echo $pending_requests; ?></h3>
                <p><?php _e('Pending Requests', 'blood-donation'); ?></p>
            </div>
        </div>

        <div class="bdm-stat-card bdm-stat-fulfilled">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-yes"></span></div>
            <div class="bdm-stat-content">
                <h3><?php echo $fulfilled_requests; ?></h3>
                <p><?php _e('Fulfilled Requests', 'blood-donation'); ?></p>
            </div>
        </div>

        <div class="bdm-stat-card bdm-stat-banks">
            <div class="bdm-stat-icon"><span class="dashicons dashicons-building"></span></div>
            <div class="bdm-stat-content">
                <h3><?php echo $total_blood_banks; ?></h3>
                <p><?php _e('Blood Banks', 'blood-donation'); ?></p>
            </div>
        </div>
    </div>

    <div class="bdm-dashboard-columns">
        <div class="bdm-dashboard-column">
            <div class="bdm-card">
                <h2><?php _e('Donors by Blood Type', 'blood-donation'); ?></h2>
                <div class="bdm-blood-type-chart">
                    <?php foreach ($blood_type_stats as $stat): ?>
                        <div class="bdm-blood-type-bar">
                            <span class="bdm-blood-type-label"><?php echo esc_html($stat->blood_type); ?></span>
                            <div class="bdm-blood-type-bar-fill" style="width: <?php echo $total_donors > 0 ? ($stat->count / $total_donors * 100) : 0; ?>%">
                                <span><?php echo $stat->count; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bdm-card">
                <h2><?php _e('Recent Donors', 'blood-donation'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'blood-donation'); ?></th>
                            <th><?php _e('Blood Type', 'blood-donation'); ?></th>
                            <th><?php _e('City', 'blood-donation'); ?></th>
                            <th><?php _e('Date', 'blood-donation'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_donors)): ?>
                            <tr><td colspan="4"><?php _e('No donors registered yet.', 'blood-donation'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_donors as $donor): ?>
                                <tr>
                                    <td><?php echo esc_html($donor->first_name . ' ' . $donor->last_name); ?></td>
                                    <td><span class="bdm-blood-badge bdm-blood-<?php echo strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $donor->blood_type))); ?>"><?php echo esc_html($donor->blood_type); ?></span></td>
                                    <td><?php echo esc_html($donor->city); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($donor->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bdm-dashboard-column">
            <div class="bdm-card">
                <h2><?php _e('Recent Blood Requests', 'blood-donation'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Patient', 'blood-donation'); ?></th>
                            <th><?php _e('Blood Type', 'blood-donation'); ?></th>
                            <th><?php _e('Urgency', 'blood-donation'); ?></th>
                            <th><?php _e('Status', 'blood-donation'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_requests)): ?>
                            <tr><td colspan="4"><?php _e('No blood requests yet.', 'blood-donation'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $request): ?>
                                <tr>
                                    <td><?php echo esc_html($request->patient_name); ?></td>
                                    <td><span class="bdm-blood-badge bdm-blood-<?php echo strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $request->blood_type_needed))); ?>"><?php echo esc_html($request->blood_type_needed); ?></span></td>
                                    <td><span class="bdm-urgency-<?php echo $request->urgency; ?>"><?php echo Blood_Donation_Manager::get_urgency_labels()[$request->urgency]; ?></span></td>
                                    <td><span class="bdm-status-<?php echo $request->status; ?>"><?php echo Blood_Donation_Manager::get_status_labels()[$request->status]; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
