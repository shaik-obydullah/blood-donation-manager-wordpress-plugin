<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$search = isset($_GET['bdm_search']) ? sanitize_text_field($_GET['bdm_search']) : '';
$filter_city = isset($_GET['bdm_city']) ? sanitize_text_field($_GET['bdm_city']) : '';

$where = "WHERE is_active = 1";
if ($search) {
    $where .= $wpdb->prepare(" AND (name LIKE %s OR address LIKE %s)", "%$search%", "%$search%");
}
if ($filter_city) {
    $where .= $wpdb->prepare(" AND city = %s", $filter_city);
}

$blood_banks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_blood_banks $where ORDER BY name ASC");
$cities = $wpdb->get_col("SELECT DISTINCT city FROM {$wpdb->prefix}bdm_blood_banks WHERE is_active = 1 AND city IS NOT NULL AND city != '' ORDER BY city");
?>

<div class="bdm-list-wrapper bdm-blood-banks-list">
    <div class="bdm-list-header">
        <h2><?php _e('Blood Banks', 'blood-donation'); ?></h2>
        <p><?php _e('Find blood banks near you.', 'blood-donation'); ?></p>
    </div>

    <div class="bdm-filters">
        <form method="get" action="" class="bdm-filter-form">
            <div class="bdm-filter-row">
                <input type="text" name="bdm_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search blood banks...', 'blood-donation'); ?>">
                <select name="bdm_city">
                    <option value=""><?php _e('All Cities', 'blood-donation'); ?></option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo esc_attr($city); ?>" <?php selected($filter_city, $city); ?>><?php echo esc_html($city); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bdm-btn"><?php _e('Search', 'blood-donation'); ?></button>
            </div>
        </form>
    </div>

    <?php if (empty($blood_banks)): ?>
        <div class="bdm-no-results">
            <p><?php _e('No blood banks found.', 'blood-donation'); ?></p>
        </div>
    <?php else: ?>
        <div class="bdm-grid bdm-banks-grid">
            <?php foreach ($blood_banks as $bank): ?>
                <div class="bdm-card bdm-bank-card">
                    <div class="bdm-card-header">
                        <h3><?php echo esc_html($bank->name); ?></h3>
                    </div>
                    <div class="bdm-card-body">
                        <?php if ($bank->description): ?>
                            <p class="bdm-bank-description"><?php echo esc_html($bank->description); ?></p>
                        <?php endif; ?>

                        <div class="bdm-bank-info">
                            <div class="bdm-info-item">
                                <span class="dashicons dashicons-location"></span>
                                <span><?php echo esc_html($bank->address); ?>, <?php echo esc_html($bank->city); ?><?php echo $bank->state ? ', ' . esc_html($bank->state) : ''; ?> <?php echo esc_html($bank->zip_code); ?></span>
                            </div>
                            <div class="bdm-info-item">
                                <span class="dashicons dashicons-phone"></span>
                                <a href="tel:<?php echo esc_attr($bank->phone); ?>"><?php echo esc_html($bank->phone); ?></a>
                            </div>
                            <?php if ($bank->email): ?>
                                <div class="bdm-info-item">
                                    <span class="dashicons dashicons-email"></span>
                                    <a href="mailto:<?php echo esc_attr($bank->email); ?>"><?php echo esc_html($bank->email); ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if ($bank->website): ?>
                                <div class="bdm-info-item">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <a href="<?php echo esc_url($bank->website); ?>" target="_blank" rel="noopener"><?php _e('Visit Website', 'blood-donation'); ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if ($bank->operating_hours): ?>
                                <div class="bdm-info-item">
                                    <span class="dashicons dashicons-clock"></span>
                                    <span><?php echo nl2br esc_html($bank->operating_hours); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
