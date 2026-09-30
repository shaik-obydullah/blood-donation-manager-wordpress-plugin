<?php
if (!defined('ABSPATH')) exit;

// Defence in depth: add_submenu_page() already gates this screen on
// manage_options, so this only fires if the file is ever included directly.
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have permission to access this page.', 'obydullah-blood-bank-manager'));
}

$obdm = Obdm_Blood_Bank_Manager::admin_dashboard_data();

$obdm_total_donors       = $obdm['total_donors'];
$obdm_available_donors   = $obdm['available_donors'];
$obdm_total_requests     = $obdm['total_requests'];
$obdm_pending_requests   = $obdm['pending_requests'];
$obdm_fulfilled_requests = $obdm['fulfilled_requests'];
$obdm_total_blood_banks  = $obdm['total_blood_banks'];
$obdm_blood_type_stats   = $obdm['blood_type_stats'];
$obdm_recent_donors      = $obdm['recent_donors'];
$obdm_recent_requests    = $obdm['recent_requests'];

$obdm_max_blood_count = 0;
foreach ($obdm_blood_type_stats as $obdm_stat) {
    $obdm_max_blood_count = max($obdm_max_blood_count, (int) $obdm_stat->total);
}

$obdm_stats = [
    [
        'label' => __('Total Donors', 'obydullah-blood-bank-manager'),
        'value' => $obdm_total_donors,
        'tone'  => 'red',
        'meta'  => sprintf(
            /* translators: %s: number of available donors. */
            __('%s available to donate', 'obydullah-blood-bank-manager'),
            number_format_i18n($obdm_available_donors)
        ),
        'url'   => admin_url('admin.php?page=obdm-donors'),
    ],
    [
        'label' => __('Available Donors', 'obydullah-blood-bank-manager'),
        'value' => $obdm_available_donors,
        'tone'  => 'green',
        'meta'  => sprintf(
            /* translators: %s: total number of donors. */
            __('of %s registered', 'obydullah-blood-bank-manager'),
            number_format_i18n($obdm_total_donors)
        ),
        'url'   => admin_url('admin.php?page=obdm-donors'),
    ],
    [
        'label' => __('Total Requests', 'obydullah-blood-bank-manager'),
        'value' => $obdm_total_requests,
        'tone'  => 'blue',
        'meta'  => sprintf(
            /* translators: %s: number of fulfilled requests. */
            __('%s fulfilled', 'obydullah-blood-bank-manager'),
            number_format_i18n($obdm_fulfilled_requests)
        ),
        'url'   => admin_url('admin.php?page=obdm-requests'),
    ],
    [
        'label' => __('Pending Requests', 'obydullah-blood-bank-manager'),
        'value' => $obdm_pending_requests,
        'tone'  => 'amber',
        'meta'  => __('awaiting a match', 'obydullah-blood-bank-manager'),
        'url'   => admin_url('admin.php?page=obdm-requests&status=pending'),
    ],
    [
        'label' => __('Fulfilled Requests', 'obydullah-blood-bank-manager'),
        'value' => $obdm_fulfilled_requests,
        'tone'  => 'violet',
        'meta'  => __('lives saved', 'obydullah-blood-bank-manager'),
        'url'   => admin_url('admin.php?page=obdm-requests&status=fulfilled'),
    ],
    [
        'label' => __('Active Blood Banks', 'obydullah-blood-bank-manager'),
        'value' => $obdm_total_blood_banks,
        'tone'  => 'teal',
        'meta'  => __('collection points', 'obydullah-blood-bank-manager'),
        'url'   => admin_url('admin.php?page=obdm-blood-banks'),
    ],
];
?>

<div class="wrap obdm-wrap">

    <?php
    Obdm_Blood_Bank_Manager::ui_page_header([
        'title'    => __('Dashboard', 'obydullah-blood-bank-manager'),
        'subtitle' => __('A live overview of donors, requests and blood bank coverage.', 'obydullah-blood-bank-manager'),
    ]);
    ?>

    <div class="obdm-stats">
        <?php foreach ($obdm_stats as $obdm_stat): ?>
            <a class="obdm-stat obdm-stat--<?php echo esc_attr($obdm_stat['tone']); ?>" href="<?php echo esc_url($obdm_stat['url']); ?>">
                <span class="obdm-stat__body">
                    <span class="obdm-stat__label"><?php echo esc_html($obdm_stat['label']); ?></span>
                    <span class="obdm-stat__value"><?php echo esc_html(number_format_i18n($obdm_stat['value'])); ?></span>
                    <span class="obdm-stat__meta"><?php echo esc_html($obdm_stat['meta']); ?></span>
                </span>
                <span class="obdm-stat__chevron"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-arrow-right-alt')); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="obdm-grid">
        <section class="obdm-card obdm-grid__main">
            <header class="obdm-card__header">
                <h2 class="obdm-card__title"><?php esc_html_e('Donors by Blood Type', 'obydullah-blood-bank-manager'); ?></h2>
                <span class="obdm-card__hint"><?php esc_html_e('Registered donors', 'obydullah-blood-bank-manager'); ?></span>
            </header>

            <div class="obdm-card__body">
                <?php if (empty($obdm_blood_type_stats)): ?>
                    <?php
                    Obdm_Blood_Bank_Manager::ui_empty_state([
                        'icon'        => 'dashicons-groups',
                        'title'       => __('No donors registered yet', 'obydullah-blood-bank-manager'),
                        'description' => __('Register your first donor to start tracking blood type coverage.', 'obydullah-blood-bank-manager'),
                        'action_url'  => admin_url('admin.php?page=obdm-donors&action=add'),
                        'action_label'=> __('Add donor', 'obydullah-blood-bank-manager'),
                    ]);
                    ?>
                <?php else: ?>
                    <div class="obdm-chart">
                        <?php foreach ($obdm_blood_type_stats as $obdm_stat): ?>
                            <?php
                            $obdm_percent = $obdm_max_blood_count > 0 ? ((int) $obdm_stat->total / $obdm_max_blood_count) * 100 : 0;
                            ?>
                            <div class="obdm-chart__row">
                                <span class="obdm-chart__label">
                                    <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_blood_badge($obdm_stat->label)); ?>
                                </span>
                                <span class="obdm-chart__track">
                                    <span class="obdm-chart__bar" style="width:<?php echo esc_attr(number_format_i18n($obdm_percent, 2)); ?>%"></span>
                                </span>
                                <span class="obdm-chart__value"><?php echo esc_html(number_format_i18n($obdm_stat->total)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="obdm-card">
            <header class="obdm-card__header">
                <h2 class="obdm-card__title"><?php esc_html_e('Recent Blood Requests', 'obydullah-blood-bank-manager'); ?></h2>
                <a class="obdm-card__action" href="<?php echo esc_url(admin_url('admin.php?page=obdm-requests')); ?>">
                    <?php esc_html_e('View all', 'obydullah-blood-bank-manager'); ?>
                </a>
            </header>

            <div class="obdm-card__body obdm-card__body--flush">
                <?php if (empty($obdm_recent_requests)): ?>
                    <?php
                    Obdm_Blood_Bank_Manager::ui_empty_state([
                        'icon'        => 'dashicons-email-alt',
                        'title'       => __('No requests yet', 'obydullah-blood-bank-manager'),
                        'description' => __('Requests submitted from the front end will appear here.', 'obydullah-blood-bank-manager'),
                    ]);
                    ?>
                <?php else: ?>
                    <ul class="obdm-feed">
                        <?php foreach ($obdm_recent_requests as $obdm_request): ?>
                            <li class="obdm-feed__item">
                                <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_avatar($obdm_request->patient_name)); ?>
                                <div class="obdm-feed__body">
                                    <p class="obdm-feed__title"><?php echo esc_html($obdm_request->patient_name); ?></p>
                                    <p class="obdm-feed__meta">
                                        <?php
                                        echo esc_html(
                                            sprintf(
                                                /* translators: 1: number of units, 2: hospital name. */
                                                __('%1$s units at %2$s', 'obydullah-blood-bank-manager'),
                                                number_format_i18n($obdm_request->units_needed),
                                                $obdm_request->hospital_name
                                            )
                                        );
                                        ?>
                                    </p>
                                </div>
                                <div class="obdm-feed__side">
                                    <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_blood_badge($obdm_request->blood_type_needed)); ?>
                                    <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_urgency_badge($obdm_request->urgency)); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="obdm-card">
            <header class="obdm-card__header">
                <h2 class="obdm-card__title"><?php esc_html_e('Recent Donors', 'obydullah-blood-bank-manager'); ?></h2>
                <a class="obdm-card__action" href="<?php echo esc_url(admin_url('admin.php?page=obdm-donors')); ?>">
                    <?php esc_html_e('View all', 'obydullah-blood-bank-manager'); ?>
                </a>
            </header>

            <div class="obdm-card__body obdm-card__body--flush">
                <?php if (empty($obdm_recent_donors)): ?>
                    <?php
                    Obdm_Blood_Bank_Manager::ui_empty_state([
                        'icon'        => 'dashicons-groups',
                        'title'       => __('No donors yet', 'obydullah-blood-bank-manager'),
                        'description' => __('Donors who sign up will be listed here.', 'obydullah-blood-bank-manager'),
                        'action_url'  => admin_url('admin.php?page=obdm-donors&action=add'),
                        'action_label'=> __('Add donor', 'obydullah-blood-bank-manager'),
                    ]);
                    ?>
                <?php else: ?>
                    <ul class="obdm-feed">
                        <?php foreach ($obdm_recent_donors as $obdm_donor): ?>
                            <li class="obdm-feed__item">
                                <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_avatar($obdm_donor->first_name . ' ' . $obdm_donor->last_name)); ?>
                                <div class="obdm-feed__body">
                                    <p class="obdm-feed__title"><?php echo esc_html($obdm_donor->first_name . ' ' . $obdm_donor->last_name); ?></p>
                                    <p class="obdm-feed__meta"><?php echo esc_html($obdm_donor->city ? $obdm_donor->city : $obdm_donor->email); ?></p>
                                </div>
                                <div class="obdm-feed__side">
                                    <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_blood_badge($obdm_donor->blood_type)); ?>
                                    <?php if ($obdm_donor->is_available): ?>
                                        <span class="obdm-badge obdm-badge--available"><span class="obdm-badge__dot"></span><?php esc_html_e('Available', 'obydullah-blood-bank-manager'); ?></span>
                                    <?php else: ?>
                                        <span class="obdm-badge obdm-badge--muted"><span class="obdm-badge__dot"></span><?php esc_html_e('Paused', 'obydullah-blood-bank-manager'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="obdm-card obdm-card--cta">
        <div class="obdm-cta">
            <div class="obdm-cta__body">
                <h2 class="obdm-cta__title"><?php esc_html_e('Publish your donation pages', 'obydullah-blood-bank-manager'); ?></h2>
                <p class="obdm-cta__text"><?php esc_html_e('Add the registration, request and directory shortcodes to any page to start collecting donors.', 'obydullah-blood-bank-manager'); ?></p>
            </div>
            <a class="obdm-button obdm-button--primary" href="<?php echo esc_url(admin_url('admin.php?page=obdm-settings')); ?>">
                <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-shortcode')); ?>
                <?php esc_html_e('View shortcodes', 'obydullah-blood-bank-manager'); ?>
            </a>
        </div>
    </section>
</div>
