<?php
if (!defined('ABSPATH')) exit;

// Defence in depth: add_submenu_page() already gates this screen on
// manage_options, so this only fires if the file is ever included directly.
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have permission to access this page.', 'obydullah-blood-bank-manager'));
}

$obdm_settings = get_option('obdm_settings', [
    'notify_email'         => get_option('admin_email'),
    'min_weight'           => 50,
    'min_age'              => 18,
    'max_days_between'     => 90,
    'enable_notifications' => 1,
    'delete_data_on_uninstall' => 0,
    'custom_message'       => '',
    'donor_email_subject'  => __('Welcome to Our Blood Donation Community', 'obydullah-blood-bank-manager'),
    'request_email_subject' => __('New Blood Donation Request', 'obydullah-blood-bank-manager'),
]);

$obdm_message = Obdm_Blood_Bank_Manager::admin_notice_message();

$obdm_shortcodes = [
    ['code' => '[obdm_donor_registration]', 'label' => __('Donor registration form', 'obydullah-blood-bank-manager')],
    ['code' => '[obdm_donation_request]', 'label' => __('Blood request form', 'obydullah-blood-bank-manager')],
    ['code' => '[obdm_donors_list]', 'label' => __('Donor directory', 'obydullah-blood-bank-manager')],
    ['code' => '[obdm_blood_requests]', 'label' => __('Active blood requests', 'obydullah-blood-bank-manager')],
    ['code' => '[obdm_blood_banks]', 'label' => __('Blood bank directory', 'obydullah-blood-bank-manager')],
    ['code' => '[obdm_dashboard]', 'label' => __('Donation dashboard', 'obydullah-blood-bank-manager')],
];
?>

<div class="wrap obdm-wrap">

    <?php
    Obdm_Blood_Bank_Manager::ui_page_header([
        'title'    => __('Settings', 'obydullah-blood-bank-manager'),
        'subtitle' => __('Notification rules, eligibility criteria and the message sent to donors.', 'obydullah-blood-bank-manager'),
    ]);
    ?>

    <?php if ($obdm_message): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($obdm_message); ?></p></div>
    <?php endif; ?>

    <form method="post" action="" class="obdm-form">
        <?php wp_nonce_field('obdm_settings_action', 'obdm_settings_nonce'); ?>

        <div class="obdm-grid">
            <div class="obdm-grid__main">
                <div class="obdm-form__card">
                    <h2 class="obdm-form__section"><?php esc_html_e('Notifications', 'obydullah-blood-bank-manager'); ?></h2>
                    <div class="obdm-form__grid">
                        <p class="obdm-field obdm-field--full">
                            <label for="notify_email"><?php esc_html_e('Notification Email', 'obydullah-blood-bank-manager'); ?></label>
                            <input type="email" name="notify_email" id="notify_email" value="<?php echo esc_attr($obdm_settings['notify_email']); ?>">
                            <span class="obdm-field__hint"><?php esc_html_e('New registrations and requests are reported to this address.', 'obydullah-blood-bank-manager'); ?></span>
                        </p>
                        <p class="obdm-field obdm-field--full">
                            <label for="donor_email_subject"><?php esc_html_e('Donor Welcome Subject', 'obydullah-blood-bank-manager'); ?></label>
                            <input type="text" name="donor_email_subject" id="donor_email_subject" value="<?php echo esc_attr($obdm_settings['donor_email_subject']); ?>">
                        </p>
                        <p class="obdm-field obdm-field--full">
                            <label for="request_email_subject"><?php esc_html_e('New Request Subject', 'obydullah-blood-bank-manager'); ?></label>
                            <input type="text" name="request_email_subject" id="request_email_subject" value="<?php echo esc_attr($obdm_settings['request_email_subject']); ?>">
                        </p>
                        <p class="obdm-field obdm-field--switch obdm-field--full">
                            <label for="enable_notifications">
                                <input type="checkbox" name="enable_notifications" id="enable_notifications" value="1" <?php checked($obdm_settings['enable_notifications'], 1); ?>>
                                <span class="obdm-switch__text">
                                    <strong><?php esc_html_e('Send notification emails', 'obydullah-blood-bank-manager'); ?></strong>
                                    <em><?php esc_html_e('Turn this off if emails are not configured yet.', 'obydullah-blood-bank-manager'); ?></em>
                                </span>
                            </label>
                        </p>
                    </div>
                </div>

                <div class="obdm-form__card">
                    <h2 class="obdm-form__section"><?php esc_html_e('Donor Eligibility', 'obydullah-blood-bank-manager'); ?></h2>
                    <div class="obdm-form__grid">
                        <p class="obdm-field">
                            <label for="min_weight"><?php esc_html_e('Minimum Weight (kg)', 'obydullah-blood-bank-manager'); ?></label>
                            <input type="number" name="min_weight" id="min_weight" value="<?php echo esc_attr($obdm_settings['min_weight']); ?>" step="0.01" min="0">
                        </p>
                        <p class="obdm-field">
                            <label for="min_age"><?php esc_html_e('Minimum Age', 'obydullah-blood-bank-manager'); ?></label>
                            <input type="number" name="min_age" id="min_age" value="<?php echo esc_attr($obdm_settings['min_age']); ?>" min="16" max="100">
                        </p>
                        <p class="obdm-field">
                            <label for="max_days_between"><?php esc_html_e('Days Between Donations', 'obydullah-blood-bank-manager'); ?></label>
                            <input type="number" name="max_days_between" id="max_days_between" value="<?php echo esc_attr($obdm_settings['max_days_between']); ?>" min="1" max="365">
                        </p>
                        <p class="obdm-field obdm-field--full">
                            <label for="custom_message"><?php esc_html_e('Custom Message', 'obydullah-blood-bank-manager'); ?></label>
                            <textarea name="custom_message" id="custom_message" rows="4" placeholder="<?php esc_attr_e('Shown to donors in confirmation emails.', 'obydullah-blood-bank-manager'); ?>"><?php echo esc_textarea($obdm_settings['custom_message']); ?></textarea>
                        </p>
                    </div>
                </div>

                <div class="obdm-form__card">
                    <h2 class="obdm-form__section"><?php esc_html_e('Data on Uninstall', 'obydullah-blood-bank-manager'); ?></h2>
                    <div class="obdm-form__grid">
                        <p class="obdm-field obdm-field--switch obdm-field--full">
                            <label for="delete_data_on_uninstall">
                                <input type="checkbox" name="delete_data_on_uninstall" id="delete_data_on_uninstall" value="1" <?php checked(isset($obdm_settings['delete_data_on_uninstall']) ? (int) $obdm_settings['delete_data_on_uninstall'] : 0, 1); ?>>
                                <span class="obdm-switch__text">
                                    <strong><?php esc_html_e('Delete all data when the plugin is deleted', 'obydullah-blood-bank-manager'); ?></strong>
                                    <em><?php esc_html_e('Off by default: donors, requests and blood banks are kept so reinstalling restores everything. When on, deleting the plugin drops the tables and options permanently. This cannot be undone.', 'obydullah-blood-bank-manager'); ?></em>
                                </span>
                            </label>
                        </p>
                    </div>
                </div>

                <div class="obdm-form__actions">
                    <input type="submit" class="obdm-button obdm-button--primary" value="<?php esc_attr_e('Save Settings', 'obydullah-blood-bank-manager'); ?>">
                </div>
            </div>

            <aside class="obdm-grid__aside">
                <div class="obdm-form__card obdm-form__card--flush">
                    <h2 class="obdm-form__section"><?php esc_html_e('Shortcodes', 'obydullah-blood-bank-manager'); ?></h2>
                    <ul class="obdm-shortcodes">
                        <?php foreach ($obdm_shortcodes as $obdm_shortcode): ?>
                            <li class="obdm-shortcodes__item">
                                <span class="obdm-shortcodes__label"><?php echo esc_html($obdm_shortcode['label']); ?></span>
                                <code class="obdm-shortcodes__code" data-obdm-copy><?php echo esc_html($obdm_shortcode['code']); ?></code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="obdm-form__hint"><?php esc_html_e('Paste any of these into a page or post.', 'obydullah-blood-bank-manager'); ?></p>
                </div>

                <div class="obdm-form__card obdm-form__card--flush">
                    <h2 class="obdm-form__section"><?php esc_html_e('Campaigns', 'obydullah-blood-bank-manager'); ?></h2>
                    <p class="obdm-form__hint"><?php esc_html_e('Create themed campaigns to group requests and drive participation.', 'obydullah-blood-bank-manager'); ?></p>
                    <a class="obdm-button obdm-button--secondary obdm-button--block" href="<?php echo esc_url(admin_url('edit.php?post_type=obdm_campaign')); ?>">
                        <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-megaphone')); ?>
                        <?php esc_html_e('Manage campaigns', 'obydullah-blood-bank-manager'); ?>
                    </a>
                </div>
            </aside>
        </div>
    </form>
</div>
