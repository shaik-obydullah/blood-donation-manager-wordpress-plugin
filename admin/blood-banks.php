<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bdm_bank_nonce'])) {
    if (!wp_verify_nonce($_POST['bdm_bank_nonce'], 'bdm_bank_action')) {
        wp_die(__('Security check failed.', 'blood-donation'));
    }

    if ($action === 'delete' && $id) {
        $wpdb->delete("{$wpdb->prefix}bdm_blood_banks", array('id' => $id));
        wp_redirect(admin_url('admin.php?page=bdm-blood-banks&deleted=1'));
        exit;
    }

    $data = array(
        'name'            => sanitize_text_field($_POST['name']),
        'description'     => sanitize_textarea_field($_POST['description']),
        'address'         => sanitize_textarea_field($_POST['address']),
        'city'            => sanitize_text_field($_POST['city']),
        'state'           => sanitize_text_field($_POST['state']),
        'zip_code'        => sanitize_text_field($_POST['zip_code']),
        'country'         => sanitize_text_field($_POST['country']),
        'phone'           => sanitize_text_field($_POST['phone']),
        'email'           => sanitize_email($_POST['email']),
        'website'         => esc_url_raw($_POST['website']),
        'latitude'        => floatval($_POST['latitude']),
        'longitude'       => floatval($_POST['longitude']),
        'operating_hours' => sanitize_textarea_field($_POST['operating_hours']),
        'is_active'       => isset($_POST['is_active']) ? 1 : 0,
    );

    if ($id) {
        $wpdb->update("{$wpdb->prefix}bdm_blood_banks", $data, array('id' => $id));
        $message = __('Blood bank updated successfully.', 'blood-donation');
    } else {
        $wpdb->insert("{$wpdb->prefix}bdm_blood_banks", $data);
        $id = $wpdb->insert_id;
        $message = __('Blood bank added successfully.', 'blood-donation');
    }

    wp_redirect(admin_url('admin.php?page=bdm-blood-banks&message=' . urlencode($message)));
    exit;
}

if ($action === 'edit' && $id) {
    $bank = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bdm_blood_banks WHERE id = %d", $id));
    if (!$bank) {
        wp_redirect(admin_url('admin.php?page=bdm-blood-banks'));
        exit;
    }
} else {
    $bank = new stdClass();
    $bank->id = 0;
    $bank->name = '';
    $bank->description = '';
    $bank->address = '';
    $bank->city = '';
    $bank->state = '';
    $bank->zip_code = '';
    $bank->country = '';
    $bank->phone = '';
    $bank->email = '';
    $bank->website = '';
    $bank->latitude = '';
    $bank->longitude = '';
    $bank->operating_hours = '';
    $bank->is_active = 1;
}

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filter_city = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';

$where = "WHERE 1=1";
if ($search) {
    $where .= $wpdb->prepare(" AND (name LIKE %s OR city LIKE %s OR address LIKE %s)", "%$search%", "%$search%", "%$search%");
}
if ($filter_city) {
    $where .= $wpdb->prepare(" AND city = %s", $filter_city);
}

$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_blood_banks $where");
$banks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_blood_banks $where ORDER BY name ASC LIMIT $per_page OFFSET $offset");
$total_pages = ceil($total / $per_page);

$cities = $wpdb->get_col("SELECT DISTINCT city FROM {$wpdb->prefix}bdm_blood_banks WHERE city IS NOT NULL AND city != '' ORDER BY city");
?>

<div class="wrap bdm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <h1><?php echo $id ? __('Edit Blood Bank', 'blood-donation') : __('Add New Blood Bank', 'blood-donation'); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('bdm_bank_action', 'bdm_bank_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="name"><?php _e('Blood Bank Name', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo esc_attr($bank->name); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="description"><?php _e('Description', 'blood-donation'); ?></label></th>
                    <td><textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea($bank->description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="address"><?php _e('Address', 'blood-donation'); ?> *</label></th>
                    <td><textarea name="address" id="address" rows="2" class="large-text" required><?php echo esc_textarea($bank->address); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="city"><?php _e('City', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="city" id="city" value="<?php echo esc_attr($bank->city); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="state"><?php _e('State', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="state" id="state" value="<?php echo esc_attr($bank->state); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="zip_code"><?php _e('ZIP Code', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="zip_code" id="zip_code" value="<?php echo esc_attr($bank->zip_code); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="country"><?php _e('Country', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="country" id="country" value="<?php echo esc_attr($bank->country); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="phone"><?php _e('Phone', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="phone" id="phone" value="<?php echo esc_attr($bank->phone); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="email"><?php _e('Email', 'blood-donation'); ?></label></th>
                    <td><input type="email" name="email" id="email" value="<?php echo esc_attr($bank->email); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="website"><?php _e('Website', 'blood-donation'); ?></label></th>
                    <td><input type="url" name="website" id="website" value="<?php echo esc_attr($bank->website); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="latitude"><?php _e('Latitude', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="latitude" id="latitude" value="<?php echo esc_attr($bank->latitude); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="longitude"><?php _e('Longitude', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="longitude" id="longitude" value="<?php echo esc_attr($bank->longitude); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="operating_hours"><?php _e('Operating Hours', 'blood-donation'); ?></label></th>
                    <td><textarea name="operating_hours" id="operating_hours" rows="3" class="large-text"><?php echo esc_textarea($bank->operating_hours); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="is_active"><?php _e('Active', 'blood-donation'); ?></label></th>
                    <td><input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($bank->is_active, 1); ?>></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Blood Bank', 'blood-donation'); ?>">
                <a href="<?php echo admin_url('admin.php?page=bdm-blood-banks'); ?>" class="button"><?php _e('Cancel', 'blood-donation'); ?></a>
            </p>
        </form>

    <?php else: ?>
        <h1 class="wp-heading-inline"><?php _e('Blood Banks', 'blood-donation'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=bdm-blood-banks&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'blood-donation'); ?></a>
        <hr class="wp-header-end">

        <div class="bdm-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="bdm-blood-banks">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search blood banks...', 'blood-donation'); ?>">
                <select name="city">
                    <option value=""><?php _e('All Cities', 'blood-donation'); ?></option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo esc_attr($city); ?>" <?php selected($filter_city, $city); ?>><?php echo esc_html($city); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="<?php _e('Filter', 'blood-donation'); ?>">
            </form>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-id"><?php _e('ID', 'blood-donation'); ?></th>
                    <th><?php _e('Name', 'blood-donation'); ?></th>
                    <th><?php _e('City', 'blood-donation'); ?></th>
                    <th><?php _e('Phone', 'blood-donation'); ?></th>
                    <th><?php _e('Email', 'blood-donation'); ?></th>
                    <th><?php _e('Status', 'blood-donation'); ?></th>
                    <th><?php _e('Actions', 'blood-donation'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($banks)): ?>
                    <tr><td colspan="7"><?php _e('No blood banks found.', 'blood-donation'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($banks as $bankItem): ?>
                        <tr>
                            <td><?php echo $bankItem->id; ?></td>
                            <td><strong><?php echo esc_html($bankItem->name); ?></strong></td>
                            <td><?php echo esc_html($bankItem->city); ?></td>
                            <td><?php echo esc_html($bankItem->phone); ?></td>
                            <td><?php echo esc_html($bankItem->email); ?></td>
                            <td><?php echo $bankItem->is_active ? '<span class="bdm-available">&#10003; ' . __('Active', 'blood-donation') . '</span>' : '<span class="bdm-unavailable">&#10007; ' . __('Inactive', 'blood-donation') . '</span>'; ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=bdm-blood-banks&action=edit&id=' . $bankItem->id); ?>" class="button button-small"><?php _e('Edit', 'blood-donation'); ?></a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=bdm-blood-banks&action=delete&id=' . $bankItem->id), 'bdm_delete_bank_' . $bankItem->id); ?>" class="button button-small bdm-delete-btn" onclick="return confirm('<?php _e('Are you sure you want to delete this blood bank?', 'blood-donation'); ?>')"><?php _e('Delete', 'blood-donation'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $pagination = paginate_links(array(
                        'base'    => add_query_arg('paged', '%#%'),
                        'format'  => '',
                        'current' => $current_page,
                        'total'   => $total_pages,
                        'type'    => 'array',
                    ));
                    if (is_array($pagination)) {
                        echo '<ul class="page-numbers">';
                        foreach ($pagination as $page) {
                            echo '<li>' . $page . '</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
