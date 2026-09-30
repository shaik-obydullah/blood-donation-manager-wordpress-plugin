<?php
if (!defined('ABSPATH')) exit;

$obdm = Obdm_Blood_Bank_Manager::public_donors_data();

$obdm_donors      = $obdm['items'];
$obdm_cities      = $obdm['cities'];
$obdm_search      = $obdm['search'];
$obdm_filter_blood = $obdm['blood_type'];
$obdm_filter_city  = $obdm['city'];
$obdm_blood_types = Obdm_Blood_Bank_Manager::get_blood_types();
?>

<div class="obdm-list-wrapper obdm-donors-list">
    <div class="obdm-list-header">
        <span class="obdm-section-icon"><span class="dashicons dashicons-groups"></span></span>
        <div class="obdm-section-text">
            <h2><?php esc_html_e('Available Blood Donors', 'obydullah-blood-bank-manager'); ?></h2>
            <p><?php esc_html_e('Find blood donors in your area.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    </div>

    <div class="obdm-filters">
        <form method="get" action="" class="obdm-filter-form">
            <div class="obdm-filter-row">
                <input type="text" name="obdm_search" value="<?php echo esc_attr($obdm_search); ?>" placeholder="<?php esc_attr_e('Search donors...', 'obydullah-blood-bank-manager'); ?>">
                <select name="obdm_blood_type">
                    <option value=""><?php esc_html_e('All Blood Types', 'obydullah-blood-bank-manager'); ?></option>
                    <?php foreach ($obdm_blood_types as $obdm_type): ?>
                        <option value="<?php echo esc_attr($obdm_type); ?>" <?php selected($obdm_filter_blood, $obdm_type); ?>><?php echo esc_html($obdm_type); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="obdm_city">
                    <option value=""><?php esc_html_e('All Cities', 'obydullah-blood-bank-manager'); ?></option>
                    <?php foreach ($obdm_cities as $obdm_city_option): ?>
                        <option value="<?php echo esc_attr($obdm_city_option); ?>" <?php selected($obdm_filter_city, $obdm_city_option); ?>><?php echo esc_html($obdm_city_option); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="obdm-btn"><?php esc_html_e('Search', 'obydullah-blood-bank-manager'); ?></button>
            </div>
        </form>
    </div>

    <?php if (empty($obdm_donors)): ?>
        <div class="obdm-no-results">
            <p class="obdm-no-results__title"><?php esc_html_e('No donors found', 'obydullah-blood-bank-manager'); ?></p>
            <p><?php esc_html_e('Try a different search or check back once more donors register.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    <?php else: ?>
        <div class="obdm-grid obdm-donors-grid">
            <?php foreach ($obdm_donors as $obdm_donor): ?>
                <div class="obdm-card obdm-donor-card">
                    <div class="obdm-donor-avatar">
                        <span class="obdm-blood-type-large obdm-blood-<?php echo esc_attr(strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $obdm_donor->blood_type)))); ?>">
                            <?php echo esc_html($obdm_donor->blood_type); ?>
                        </span>
                    </div>
                    <div class="obdm-card-body">
                        <h3><?php echo esc_html($obdm_donor->first_name . ' ' . $obdm_donor->last_name); ?></h3>
                        <div class="obdm-donor-info">
                            <?php if ($obdm_donor->city): ?>
                                <span class="obdm-info-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($obdm_donor->city); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($obdm_donor->last_donation_date): ?>
                                <span class="obdm-info-item">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html(sprintf(
                                        /* translators: %s: date of the last donation. */
                                        __('Last donated: %s', 'obydullah-blood-bank-manager'),
                                        date_i18n(get_option('date_format'), strtotime($obdm_donor->last_donation_date))
                                    )); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
