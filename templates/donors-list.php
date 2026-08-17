<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$search = isset($_GET['bdm_search']) ? sanitize_text_field($_GET['bdm_search']) : '';
$filter_blood = isset($_GET['bdm_blood_type']) ? sanitize_text_field($_GET['bdm_blood_type']) : '';
$filter_city = isset($_GET['bdm_city']) ? sanitize_text_field($_GET['bdm_city']) : '';

$where = "WHERE is_available = 1";
if ($search) {
    $where .= $wpdb->prepare(" AND (first_name LIKE %s OR last_name LIKE %s OR city LIKE %s)", "%$search%", "%$search%", "%$search%");
}
if ($filter_blood) {
    $where .= $wpdb->prepare(" AND blood_type = %s", $filter_blood);
}
if ($filter_city) {
    $where .= $wpdb->prepare(" AND city = %s", $filter_city);
}

$donors = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_donors $where ORDER BY created_at DESC");
$cities = $wpdb->get_col("SELECT DISTINCT city FROM {$wpdb->prefix}bdm_donors WHERE is_available = 1 AND city IS NOT NULL AND city != '' ORDER BY city");
$blood_types = Blood_Donation_Manager::get_blood_types();
?>

<div class="bdm-list-wrapper bdm-donors-list">
    <div class="bdm-list-header">
        <h2><?php _e('Available Blood Donors', 'blood-donation'); ?></h2>
        <p><?php _e('Find blood donors in your area.', 'blood-donation'); ?></p>
    </div>

    <div class="bdm-filters">
        <form method="get" action="" class="bdm-filter-form">
            <div class="bdm-filter-row">
                <input type="text" name="bdm_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search donors...', 'blood-donation'); ?>">
                <select name="bdm_blood_type">
                    <option value=""><?php _e('All Blood Types', 'blood-donation'); ?></option>
                    <?php foreach ($blood_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php selected($filter_blood, $type); ?>><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
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

    <?php if (empty($donors)): ?>
        <div class="bdm-no-results">
            <p><?php _e('No donors found.', 'blood-donation'); ?></p>
        </div>
    <?php else: ?>
        <div class="bdm-grid bdm-donors-grid">
            <?php foreach ($donors as $donor): ?>
                <div class="bdm-card bdm-donor-card">
                    <div class="bdm-donor-avatar">
                        <span class="bdm-blood-type-large bdm-blood-<?php echo strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $donor->blood_type))); ?>">
                            <?php echo esc_html($donor->blood_type); ?>
                        </span>
                    </div>
                    <div class="bdm-card-body">
                        <h3><?php echo esc_html($donor->first_name . ' ' . $donor->last_name); ?></h3>
                        <div class="bdm-donor-info">
                            <?php if ($donor->city): ?>
                                <span class="bdm-info-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($donor->city); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($donor->last_donation_date): ?>
                                <span class="bdm-info-item">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php printf(__('Last donated: %s', 'blood-donation'), date_i18n(get_option('date_format'), strtotime($donor->last_donation_date))); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
