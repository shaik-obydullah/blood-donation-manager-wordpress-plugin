<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bdm_donor_nonce'])) {
    if (!wp_verify_nonce($_POST['bdm_donor_nonce'], 'bdm_donor_action')) {
        wp_die(__('Security check failed.', 'blood-donation'));
    }

    if ($action === 'delete' && $id) {
        $wpdb->delete("{$wpdb->prefix}bdm_donors", array('id' => $id));
        wp_redirect(admin_url('admin.php?page=bdm-donors&deleted=1'));
        exit;
    }

    $data = array(
        'first_name'          => sanitize_text_field($_POST['first_name']),
        'last_name'           => sanitize_text_field($_POST['last_name']),
        'email'               => sanitize_email($_POST['email']),
        'phone'               => sanitize_text_field($_POST['phone']),
        'blood_type'          => sanitize_text_field($_POST['blood_type']),
        'date_of_birth'       => sanitize_text_field($_POST['date_of_birth']),
        'gender'              => sanitize_text_field($_POST['gender']),
        'weight'              => floatval($_POST['weight']),
        'address'             => sanitize_textarea_field($_POST['address']),
        'city'                => sanitize_text_field($_POST['city']),
        'state'               => sanitize_text_field($_POST['state']),
        'zip_code'            => sanitize_text_field($_POST['zip_code']),
        'country'             => sanitize_text_field($_POST['country']),
        'last_donation_date'  => sanitize_text_field($_POST['last_donation_date']),
        'medical_conditions'  => sanitize_textarea_field($_POST['medical_conditions']),
        'is_available'        => isset($_POST['is_available']) ? 1 : 0,
    );

    if ($id) {
        $wpdb->update("{$wpdb->prefix}bdm_donors", $data, array('id' => $id));
        $message = __('Donor updated successfully.', 'blood-donation');
    } else {
        $wpdb->insert("{$wpdb->prefix}bdm_donors", $data);
        $id = $wpdb->insert_id;
        $message = __('Donor added successfully.', 'blood-donation');
    }

    wp_redirect(admin_url('admin.php?page=bdm-donors&message=' . urlencode($message)));
    exit;
}

if ($action === 'edit' && $id) {
    $donor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bdm_donors WHERE id = %d", $id));
    if (!$donor) {
        wp_redirect(admin_url('admin.php?page=bdm-donors'));
        exit;
    }
} elseif ($action === 'add' || $action === 'edit') {
    $donor = new stdClass();
    $donor->id = 0;
    $donor->first_name = '';
    $donor->last_name = '';
    $donor->email = '';
    $donor->phone = '';
    $donor->blood_type = '';
    $donor->date_of_birth = '';
    $donor->gender = '';
    $donor->weight = '';
    $donor->address = '';
    $donor->city = '';
    $donor->state = '';
    $donor->zip_code = '';
    $donor->country = '';
    $donor->last_donation_date = '';
    $donor->medical_conditions = '';
    $donor->is_available = 1;
}

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filter_blood = isset($_GET['blood_type']) ? sanitize_text_field($_GET['blood_type']) : '';
$filter_city = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';

$where = "WHERE 1=1";
if ($search) {
    $where .= $wpdb->prepare(" AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)", "%$search%", "%$search%", "%$search%", "%$search%");
}
if ($filter_blood) {
    $where .= $wpdb->prepare(" AND blood_type = %s", $filter_blood);
}
if ($filter_city) {
    $where .= $wpdb->prepare(" AND city = %s", $filter_city);
}

$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_donors $where");
$donors = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_donors $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$total_pages = ceil($total / $per_page);

$cities = $wpdb->get_col("SELECT DISTINCT city FROM {$wpdb->prefix}bdm_donors WHERE city IS NOT NULL AND city != '' ORDER BY city");
$blood_types = Blood_Donation_Manager::get_blood_types();
?>

<div class="wrap bdm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <h1><?php echo $id ? __('Edit Donor', 'blood-donation') : __('Add New Donor', 'blood-donation'); ?></h1>

        <?php if (isset($message)): ?>
            <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('bdm_donor_action', 'bdm_donor_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="first_name"><?php _e('First Name', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($donor->first_name); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="last_name"><?php _e('Last Name', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($donor->last_name); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="email"><?php _e('Email', 'blood-donation'); ?> *</label></th>
                    <td><input type="email" name="email" id="email" value="<?php echo esc_attr($donor->email); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="phone"><?php _e('Phone', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="phone" id="phone" value="<?php echo esc_attr($donor->phone); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="blood_type"><?php _e('Blood Type', 'blood-donation'); ?> *</label></th>
                    <td>
                        <select name="blood_type" id="blood_type" required>
                            <option value=""><?php _e('Select Blood Type', 'blood-donation'); ?></option>
                            <?php foreach ($blood_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php selected($donor->blood_type, $type); ?>><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="date_of_birth"><?php _e('Date of Birth', 'blood-donation'); ?></label></th>
                    <td><input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo esc_attr($donor->date_of_birth); ?>"></td>
                </tr>
                <tr>
                    <th><label for="gender"><?php _e('Gender', 'blood-donation'); ?></label></th>
                    <td>
                        <select name="gender" id="gender">
                            <option value=""><?php _e('Select Gender', 'blood-donation'); ?></option>
                            <option value="male" <?php selected($donor->gender, 'male'); ?>><?php _e('Male', 'blood-donation'); ?></option>
                            <option value="female" <?php selected($donor->gender, 'female'); ?>><?php _e('Female', 'blood-donation'); ?></option>
                            <option value="other" <?php selected($donor->gender, 'other'); ?>><?php _e('Other', 'blood-donation'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="weight"><?php _e('Weight (kg)', 'blood-donation'); ?></label></th>
                    <td><input type="number" name="weight" id="weight" value="<?php echo esc_attr($donor->weight); ?>" step="0.01" min="0"></td>
                </tr>
                <tr>
                    <th><label for="address"><?php _e('Address', 'blood-donation'); ?></label></th>
                    <td><textarea name="address" id="address" rows="3" class="large-text"><?php echo esc_textarea($donor->address); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="city"><?php _e('City', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="city" id="city" value="<?php echo esc_attr($donor->city); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="state"><?php _e('State', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="state" id="state" value="<?php echo esc_attr($donor->state); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="zip_code"><?php _e('ZIP Code', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="zip_code" id="zip_code" value="<?php echo esc_attr($donor->zip_code); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="country"><?php _e('Country', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="country" id="country" value="<?php echo esc_attr($donor->country); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="last_donation_date"><?php _e('Last Donation Date', 'blood-donation'); ?></label></th>
                    <td><input type="date" name="last_donation_date" id="last_donation_date" value="<?php echo esc_attr($donor->last_donation_date); ?>"></td>
                </tr>
                <tr>
                    <th><label for="medical_conditions"><?php _e('Medical Conditions', 'blood-donation'); ?></label></th>
                    <td><textarea name="medical_conditions" id="medical_conditions" rows="3" class="large-text"><?php echo esc_textarea($donor->medical_conditions); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="is_available"><?php _e('Available to Donate', 'blood-donation'); ?></label></th>
                    <td><input type="checkbox" name="is_available" id="is_available" value="1" <?php checked($donor->is_available, 1); ?>></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Donor', 'blood-donation'); ?>">
                <a href="<?php echo admin_url('admin.php?page=bdm-donors'); ?>" class="button"><?php _e('Cancel', 'blood-donation'); ?></a>
            </p>
        </form>

    <?php else: ?>
        <h1 class="wp-heading-inline"><?php _e('Donors', 'blood-donation'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=bdm-donors&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'blood-donation'); ?></a>
        <hr class="wp-header-end">

        <?php if (isset($_GET['deleted'])): ?>
            <div class="notice notice-success is-dismissible"><p><?php _e('Donor deleted.', 'blood-donation'); ?></p></div>
        <?php endif; ?>
        <?php if (isset($_GET['message'])): ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html($_GET['message']); ?></p></div>
        <?php endif; ?>

        <div class="bdm-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="bdm-donors">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search donors...', 'blood-donation'); ?>">
                <select name="blood_type">
                    <option value=""><?php _e('All Blood Types', 'blood-donation'); ?></option>
                    <?php foreach ($blood_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php selected($filter_blood, $type); ?>><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
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
                    <th><?php _e('Email', 'blood-donation'); ?></th>
                    <th><?php _e('Phone', 'blood-donation'); ?></th>
                    <th><?php _e('Blood Type', 'blood-donation'); ?></th>
                    <th><?php _e('City', 'blood-donation'); ?></th>
                    <th><?php _e('Available', 'blood-donation'); ?></th>
                    <th><?php _e('Registered', 'blood-donation'); ?></th>
                    <th><?php _e('Actions', 'blood-donation'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($donors)): ?>
                    <tr><td colspan="9"><?php _e('No donors found.', 'blood-donation'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($donors as $donor): ?>
                        <tr>
                            <td><?php echo $donor->id; ?></td>
                            <td><strong><?php echo esc_html($donor->first_name . ' ' . $donor->last_name); ?></strong></td>
                            <td><?php echo esc_html($donor->email); ?></td>
                            <td><?php echo esc_html($donor->phone); ?></td>
                            <td><span class="bdm-blood-badge bdm-blood-<?php echo strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $donor->blood_type))); ?>"><?php echo esc_html($donor->blood_type); ?></span></td>
                            <td><?php echo esc_html($donor->city); ?></td>
                            <td><?php echo $donor->is_available ? '<span class="bdm-available">&#10003;</span>' : '<span class="bdm-unavailable">&#10007;</span>'; ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($donor->created_at)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=bdm-donors&action=edit&id=' . $donor->id); ?>" class="button button-small"><?php _e('Edit', 'blood-donation'); ?></a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=bdm-donors&action=delete&id=' . $donor->id), 'bdm_delete_donor_' . $donor->id); ?>" class="button button-small bdm-delete-btn" onclick="return confirm('<?php _e('Are you sure you want to delete this donor?', 'blood-donation'); ?>')"><?php _e('Delete', 'blood-donation'); ?></a>
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
