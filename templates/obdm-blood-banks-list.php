<?php
if (!defined('ABSPATH')) exit;

$obdm = Obdm_Blood_Bank_Manager::public_blood_banks_data();

$obdm_blood_banks = $obdm['items'];
$obdm_cities      = $obdm['cities'];
$obdm_search      = $obdm['search'];
$obdm_filter_city = $obdm['city'];
?>

<div class="obdm-list-wrapper obdm-blood-banks-list">
    <div class="obdm-list-header">
        <span class="obdm-section-icon"><span class="dashicons dashicons-building"></span></span>
        <div class="obdm-section-text">
            <h2><?php esc_html_e('Blood Banks', 'obydullah-blood-bank-manager'); ?></h2>
            <p><?php esc_html_e('Find blood banks near you.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    </div>

    <div class="obdm-filters">
        <form method="get" action="" class="obdm-filter-form">
            <div class="obdm-filter-row">
                <input type="text" name="obdm_search" value="<?php echo esc_attr($obdm_search); ?>" placeholder="<?php esc_attr_e('Search blood banks...', 'obydullah-blood-bank-manager'); ?>">
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

    <?php if (empty($obdm_blood_banks)): ?>
        <div class="obdm-no-results">
            <p class="obdm-no-results__title"><?php esc_html_e('No blood banks found', 'obydullah-blood-bank-manager'); ?></p>
            <p><?php esc_html_e('Try another search term or city.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    <?php else: ?>
        <div class="obdm-grid obdm-banks-grid">
            <?php foreach ($obdm_blood_banks as $obdm_bank): ?>
                <div class="obdm-card obdm-bank-card">
                    <div class="obdm-card-header">
                        <h3><?php echo esc_html($obdm_bank->name); ?></h3>
                    </div>
                    <div class="obdm-card-body">
                        <?php if ($obdm_bank->description): ?>
                            <p class="obdm-bank-description"><?php echo esc_html($obdm_bank->description); ?></p>
                        <?php endif; ?>

                        <div class="obdm-bank-info">
                            <div class="obdm-info-item">
                                <span class="dashicons dashicons-location"></span>
                                <span><?php echo esc_html($obdm_bank->address); ?>, <?php echo esc_html($obdm_bank->city); ?><?php echo $obdm_bank->state ? ', ' . esc_html($obdm_bank->state) : ''; ?> <?php echo esc_html($obdm_bank->zip_code); ?></span>
                            </div>
                            <div class="obdm-info-item">
                                <span class="dashicons dashicons-phone"></span>
                                <a href="tel:<?php echo esc_attr($obdm_bank->phone); ?>"><?php echo esc_html($obdm_bank->phone); ?></a>
                            </div>
                            <?php if ($obdm_bank->email): ?>
                                <div class="obdm-info-item">
                                    <span class="dashicons dashicons-email"></span>
                                    <a href="mailto:<?php echo esc_attr($obdm_bank->email); ?>"><?php echo esc_html($obdm_bank->email); ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if ($obdm_bank->website): ?>
                                <div class="obdm-info-item">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <a href="<?php echo esc_url($obdm_bank->website); ?>" target="_blank" rel="noopener"><?php esc_html_e('Visit Website', 'obydullah-blood-bank-manager'); ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if ($obdm_bank->operating_hours): ?>
                                <div class="obdm-info-item">
                                    <span class="dashicons dashicons-clock"></span>
                                    <span><?php echo nl2br(esc_html($obdm_bank->operating_hours)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
