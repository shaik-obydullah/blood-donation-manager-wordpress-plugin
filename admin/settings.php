<?php
if (!defined('ABSPATH')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bdm_settings_nonce'])) {
    if (!wp_verify_nonce($_POST['bdm_settings_nonce'], 'bdm_settings_action')) {
        wp_die(__('Security check failed.', 'blood-donation'));
    }

    $settings = array(
        'notify_email'         => sanitize_email($_POST['notify_email']),
        'min_weight'           => floatval($_POST['min_weight']),
        'min_age'              => intval($_POST['min_age']),
        'max_days_between'     => intval($_POST['max_days_between']),
        'enable_notifications' => isset($_POST['enable_notifications']) ? 1 : 0,
        'custom_message'       => sanitize_textarea_field($_POST['custom_message']),
        'donor_email_subject'  => sanitize_text_field($_POST['donor_email_subject']),
        'request_email_subject' => sanitize_text_field($_POST['request_email_subject']),
    );

    update_option('bdm_settings', $settings);
    $message = __('Settings saved.', 'blood-donation');
}

$settings = get_option('bdm_settings', array(
    'notify_email'         => get_option('admin_email'),
    'min_weight'           => 50,
    'min_age'              => 18,
    'max_days_between'     => 90,
    'enable_notifications' => 1,
    'custom_message'       => '',
    'donor_email_subject'  => __('Welcome to Our Blood Donation Community', 'blood-donation'),
    'request_email_subject' => __('New Blood Donation Request', 'blood-donation'),
));
?>

<div class="wrap bdm-wrap">
    <h1><?php _e('Blood Donation Settings', 'blood-donation'); ?></h1>

    <?php if (isset($message)): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bdm_settings_action', 'bdm_settings_nonce'); ?>

        <h2><?php _e('Notification Settings', 'blood-donation'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="notify_email"><?php _e('Notification Email', 'blood-donation'); ?></label></th>
                <td><input type="email" name="notify_email" id="notify_email" value="<?php echo esc_attr($settings['notify_email']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="enable_notifications"><?php _e('Enable Email Notifications', 'blood-donation'); ?></label></th>
                <td><input type="checkbox" name="enable_notifications" id="enable_notifications" value="1" <?php checked($settings['enable_notifications'], 1); ?>></td>
            </tr>
            <tr>
                <th><label for="donor_email_subject"><?php _e('Donor Welcome Email Subject', 'blood-donation'); ?></label></th>
                <td><input type="text" name="donor_email_subject" id="donor_email_subject" value="<?php echo esc_attr($settings['donor_email_subject']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="request_email_subject"><?php _e('Request Notification Email Subject', 'blood-donation'); ?></label></th>
                <td><input type="text" name="request_email_subject" id="request_email_subject" value="<?php echo esc_attr($settings['request_email_subject']); ?>" class="regular-text"></td>
            </tr>
        </table>

        <h2><?php _e('Donor Eligibility Settings', 'blood-donation'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="min_weight"><?php _e('Minimum Weight (kg)', 'blood-donation'); ?></label></th>
                <td><input type="number" name="min_weight" id="min_weight" value="<?php echo esc_attr($settings['min_weight']); ?>" step="0.1" min="0"></td>
            </tr>
            <tr>
                <th><label for="min_age"><?php _e('Minimum Age (years)', 'blood-donation'); ?></label></th>
                <td><input type="number" name="min_age" id="min_age" value="<?php echo esc_attr($settings['min_age']); ?>" min="0"></td>
            </tr>
            <tr>
                <th><label for="max_days_between"><?php _e('Minimum Days Between Donations', 'blood-donation'); ?></label></th>
                <td><input type="number" name="max_days_between" id="max_days_between" value="<?php echo esc_attr($settings['max_days_between']); ?>" min="0"></td>
            </tr>
        </table>

        <h2><?php _e('Custom Messages', 'blood-donation'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="custom_message"><?php _e('Custom Message (shown on forms)', 'blood-donation'); ?></label></th>
                <td><textarea name="custom_message" id="custom_message" rows="4" class="large-text"><?php echo esc_textarea($settings['custom_message']); ?></textarea></td>
            </tr>
        </table>

        <h2><?php _e('Shortcodes', 'blood-donation'); ?></h2>
        <div class="bdm-card">
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'blood-donation'); ?></th>
                        <th><?php _e('Description', 'blood-donation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[bdm_donor_registration]</code></td>
                        <td><?php _e('Display the donor registration form', 'blood-donation'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[bdm_donation_request]</code></td>
                        <td><?php _e('Display the blood donation request form', 'blood-donation'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[bdm_blood_banks]</code></td>
                        <td><?php _e('Display the list of blood banks', 'blood-donation'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[bdm_donors_list]</code></td>
                        <td><?php _e('Display the list of available donors', 'blood-donation'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[bdm_blood_requests]</code></td>
                        <td><?php _e('Display the list of blood requests', 'blood-donation'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[bdm_dashboard]</code></td>
                        <td><?php _e('Display the public dashboard', 'blood-donation'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Settings', 'blood-donation'); ?>">
        </p>
    </form>
</div>
