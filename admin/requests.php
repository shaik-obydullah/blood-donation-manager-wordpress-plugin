<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bdm_request_nonce'])) {
    if (!wp_verify_nonce($_POST['bdm_request_nonce'], 'bdm_request_action')) {
        wp_die(__('Security check failed.', 'blood-donation'));
    }

    if ($action === 'delete' && $id) {
        $wpdb->delete("{$wpdb->prefix}bdm_requests", array('id' => $id));
        wp_redirect(admin_url('admin.php?page=bdm-requests&deleted=1'));
        exit;
    }

    $data = array(
        'requester_name'    => sanitize_text_field($_POST['requester_name']),
        'requester_email'   => sanitize_email($_POST['requester_email']),
        'requester_phone'   => sanitize_text_field($_POST['requester_phone']),
        'patient_name'      => sanitize_text_field($_POST['patient_name']),
        'blood_type_needed' => sanitize_text_field($_POST['blood_type_needed']),
        'units_needed'      => intval($_POST['units_needed']),
        'hospital_name'     => sanitize_text_field($_POST['hospital_name']),
        'hospital_address'  => sanitize_textarea_field($_POST['hospital_address']),
        'city'              => sanitize_text_field($_POST['city']),
        'urgency'           => sanitize_text_field($_POST['urgency']),
        'needed_by'         => sanitize_text_field($_POST['needed_by']),
        'additional_info'   => sanitize_textarea_field($_POST['additional_info']),
        'status'            => sanitize_text_field($_POST['status']),
    );

    if ($id) {
        $wpdb->update("{$wpdb->prefix}bdm_requests", $data, array('id' => $id));
        $message = __('Request updated successfully.', 'blood-donation');
    } else {
        $wpdb->insert("{$wpdb->prefix}bdm_requests", $data);
        $id = $wpdb->insert_id;
        $message = __('Request created successfully.', 'blood-donation');

        $this->send.NewRequestNotification($data);
    }

    wp_redirect(admin_url('admin.php?page=bdm-requests&message=' . urlencode($message)));
    exit;
}

if ($action === 'edit' && $id) {
    $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bdm_requests WHERE id = %d", $id));
    if (!$request) {
        wp_redirect(admin_url('admin.php?page=bdm-requests'));
        exit;
    }
} else {
    $request = new stdClass();
    $request->id = 0;
    $request->requester_name = '';
    $request->requester_email = '';
    $request->requester_phone = '';
    $request->patient_name = '';
    $request->blood_type_needed = '';
    $request->units_needed = 1;
    $request->hospital_name = '';
    $request->hospital_address = '';
    $request->city = '';
    $request->urgency = 'normal';
    $request->needed_by = '';
    $request->additional_info = '';
    $request->status = 'pending';
}

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filter_blood = isset($_GET['blood_type']) ? sanitize_text_field($_GET['blood_type']) : '';
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$filter_urgency = isset($_GET['urgency']) ? sanitize_text_field($_GET['urgency']) : '';

$where = "WHERE 1=1";
if ($search) {
    $where .= $wpdb->prepare(" AND (patient_name LIKE %s OR requester_name LIKE %s OR hospital_name LIKE %s)", "%$search%", "%$search%", "%$search%");
}
if ($filter_blood) {
    $where .= $wpdb->prepare(" AND blood_type_needed = %s", $filter_blood);
}
if ($filter_status) {
    $where .= $wpdb->prepare(" AND status = %s", $filter_status);
}
if ($filter_urgency) {
    $where .= $wpdb->prepare(" AND urgency = %s", $filter_urgency);
}

$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bdm_requests $where");
$requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bdm_requests $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$total_pages = ceil($total / $per_page);

$blood_types = Blood_Donation_Manager::get_blood_types();
$urgency_labels = Blood_Donation_Manager::get_urgency_labels();
$status_labels = Blood_Donation_Manager::get_status_labels();
?>

<div class="wrap bdm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <h1><?php echo $id ? __('Edit Blood Request', 'blood-donation') : __('New Blood Request', 'blood-donation'); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('bdm_request_action', 'bdm_request_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="requester_name"><?php _e('Requester Name', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="requester_name" id="requester_name" value="<?php echo esc_attr($request->requester_name); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="requester_email"><?php _e('Requester Email', 'blood-donation'); ?> *</label></th>
                    <td><input type="email" name="requester_email" id="requester_email" value="<?php echo esc_attr($request->requester_email); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="requester_phone"><?php _e('Requester Phone', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="requester_phone" id="requester_phone" value="<?php echo esc_attr($request->requester_phone); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="patient_name"><?php _e('Patient Name', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="patient_name" id="patient_name" value="<?php echo esc_attr($request->patient_name); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="blood_type_needed"><?php _e('Blood Type Needed', 'blood-donation'); ?> *</label></th>
                    <td>
                        <select name="blood_type_needed" id="blood_type_needed" required>
                            <option value=""><?php _e('Select Blood Type', 'blood-donation'); ?></option>
                            <?php foreach ($blood_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php selected($request->blood_type_needed, $type); ?>><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="units_needed"><?php _e('Units Needed', 'blood-donation'); ?> *</label></th>
                    <td><input type="number" name="units_needed" id="units_needed" value="<?php echo esc_attr($request->units_needed); ?>" min="1" max="10" required></td>
                </tr>
                <tr>
                    <th><label for="hospital_name"><?php _e('Hospital Name', 'blood-donation'); ?> *</label></th>
                    <td><input type="text" name="hospital_name" id="hospital_name" value="<?php echo esc_attr($request->hospital_name); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="hospital_address"><?php _e('Hospital Address', 'blood-donation'); ?></label></th>
                    <td><textarea name="hospital_address" id="hospital_address" rows="3" class="large-text"><?php echo esc_textarea($request->hospital_address); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="city"><?php _e('City', 'blood-donation'); ?></label></th>
                    <td><input type="text" name="city" id="city" value="<?php echo esc_attr($request->city); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="urgency"><?php _e('Urgency', 'blood-donation'); ?> *</label></th>
                    <td>
                        <select name="urgency" id="urgency" required>
                            <?php foreach ($urgency_labels as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php selected($request->urgency, $key); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="needed_by"><?php _e('Needed By', 'blood-donation'); ?></label></th>
                    <td><input type="date" name="needed_by" id="needed_by" value="<?php echo esc_attr($request->needed_by); ?>"></td>
                </tr>
                <tr>
                    <th><label for="additional_info"><?php _e('Additional Info', 'blood-donation'); ?></label></th>
                    <td><textarea name="additional_info" id="additional_info" rows="3" class="large-text"><?php echo esc_textarea($request->additional_info); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="status"><?php _e('Status', 'blood-donation'); ?></label></th>
                    <td>
                        <select name="status" id="status">
                            <?php foreach ($status_labels as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php selected($request->status, $key); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Request', 'blood-donation'); ?>">
                <a href="<?php echo admin_url('admin.php?page=bdm-requests'); ?>" class="button"><?php _e('Cancel', 'blood-donation'); ?></a>
            </p>
        </form>

    <?php else: ?>
        <h1 class="wp-heading-inline"><?php _e('Blood Requests', 'blood-donation'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=bdm-requests&action=add'); ?>" class="page-title-action"><?php _e('Add New', 'blood-donation'); ?></a>
        <hr class="wp-header-end">

        <div class="bdm-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="bdm-requests">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search requests...', 'blood-donation'); ?>">
                <select name="blood_type">
                    <option value=""><?php _e('All Blood Types', 'blood-donation'); ?></option>
                    <?php foreach ($blood_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php selected($filter_blood, $type); ?>><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'blood-donation'); ?></option>
                    <?php foreach ($status_labels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php selected($filter_status, $key); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="urgency">
                    <option value=""><?php _e('All Urgency Levels', 'blood-donation'); ?></option>
                    <?php foreach ($urgency_labels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php selected($filter_urgency, $key); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="<?php _e('Filter', 'blood-donation'); ?>">
            </form>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-id"><?php _e('ID', 'blood-donation'); ?></th>
                    <th><?php _e('Patient', 'blood-donation'); ?></th>
                    <th><?php _e('Blood Type', 'blood-donation'); ?></th>
                    <th><?php _e('Units', 'blood-donation'); ?></th>
                    <th><?php _e('Hospital', 'blood-donation'); ?></th>
                    <th><?php _e('Urgency', 'blood-donation'); ?></th>
                    <th><?php _e('Status', 'blood-donation'); ?></th>
                    <th><?php _e('Requested', 'blood-donation'); ?></th>
                    <th><?php _e('Actions', 'blood-donation'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="9"><?php _e('No blood requests found.', 'blood-donation'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?php echo $req->id; ?></td>
                            <td><strong><?php echo esc_html($req->patient_name); ?></strong><br><small><?php echo esc_html($req->requester_name); ?></small></td>
                            <td><span class="bdm-blood-badge bdm-blood-<?php echo strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $req->blood_type_needed))); ?>"><?php echo esc_html($req->blood_type_needed); ?></span></td>
                            <td><?php echo $req->units_needed; ?></td>
                            <td><?php echo esc_html($req->hospital_name); ?></td>
                            <td><span class="bdm-urgency-<?php echo $req->urgency; ?>"><?php echo $urgency_labels[$req->urgency]; ?></span></td>
                            <td><span class="bdm-status-<?php echo $req->status; ?>"><?php echo $status_labels[$req->status]; ?></span></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($req->created_at)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=bdm-requests&action=edit&id=' . $req->id); ?>" class="button button-small"><?php _e('Edit', 'blood-donation'); ?></a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=bdm-requests&action=delete&id=' . $req->id), 'bdm_delete_request_' . $req->id); ?>" class="button button-small bdm-delete-btn" onclick="return confirm('<?php _e('Are you sure you want to delete this request?', 'blood-donation'); ?>')"><?php _e('Delete', 'blood-donation'); ?></a>
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
