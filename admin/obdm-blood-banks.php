<?php
if (!defined('ABSPATH')) exit;

// Defence in depth: add_submenu_page() already gates this screen on
// manage_options, so this only fires if the file is ever included directly.
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have permission to access this page.', 'obydullah-blood-bank-manager'));
}

$obdm = Obdm_Blood_Bank_Manager::admin_blood_banks_data();

$obdm_action  = Obdm_Blood_Bank_Manager::admin_record_action();
$obdm_id      = Obdm_Blood_Bank_Manager::admin_record_id();
$obdm_missing = false;

if ($obdm_action === 'edit' && $obdm_id) {
    $obdm_record = Obdm_Blood_Bank_Manager::admin_blood_bank_record($obdm_id);

    if (!$obdm_record->id) {
        $obdm_missing = true;
        $obdm_action  = 'list';
    }
} else {
    $obdm_record = Obdm_Blood_Bank_Manager::admin_blood_bank_record(0);
}

$obdm_total        = $obdm['total'];
$obdm_banks        = $obdm['items'];
$obdm_total_pages  = $obdm['total_pages'];
$obdm_page         = $obdm['page'];
$obdm_cities       = $obdm['cities'];
$obdm_search       = $obdm['search'];
$obdm_filter_city  = $obdm['city'];
$obdm_filter_status = $obdm['status'];
$obdm_message      = Obdm_Blood_Bank_Manager::admin_notice_message();
$obdm_has_filters  = '' !== $obdm_search || '' !== $obdm_filter_city || '' !== $obdm_filter_status;
?>

<div class="wrap obdm-wrap">
    <?php if ($obdm_missing): ?>
        <div class="notice notice-error"><p><?php esc_html_e('That record could not be found.', 'obydullah-blood-bank-manager'); ?></p></div>
    <?php endif; ?>
    <?php if ($obdm_action === 'add' || $obdm_action === 'edit'): ?>

        <?php
        Obdm_Blood_Bank_Manager::ui_page_header([
            'title'    => $obdm_id ? __('Edit Blood Bank', 'obydullah-blood-bank-manager') : __('Add New Blood Bank', 'obydullah-blood-bank-manager'),
            'subtitle' => __('Add a collection point with contact details and service hours.', 'obydullah-blood-bank-manager'),
            'back_url' => admin_url('admin.php?page=obdm-blood-banks'),
        ]);
        ?>

        <form method="post" action="" class="obdm-form">
            <?php wp_nonce_field('obdm_bank_action', 'obdm_bank_nonce'); ?>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Blood Bank Details', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field obdm-field--full">
                        <label for="name"><?php esc_html_e('Blood Bank Name', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="name" id="name" value="<?php echo esc_attr($obdm_record->name); ?>" required>
                    </p>
                    <p class="obdm-field obdm-field--full">
                        <label for="description"><?php esc_html_e('Description', 'obydullah-blood-bank-manager'); ?></label>
                        <textarea name="description" id="description" rows="3" placeholder="<?php esc_attr_e('Shown to visitors in the blood bank directory.', 'obydullah-blood-bank-manager'); ?>"><?php echo esc_textarea($obdm_record->description); ?></textarea>
                    </p>
                </div>
            </div>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Location', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field obdm-field--full">
                        <label for="address"><?php esc_html_e('Address', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <textarea name="address" id="address" rows="3" required><?php echo esc_textarea($obdm_record->address); ?></textarea>
                    </p>
                    <p class="obdm-field">
                        <label for="city"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="city" id="city" value="<?php echo esc_attr($obdm_record->city); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="state"><?php esc_html_e('State', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="state" id="state" value="<?php echo esc_attr($obdm_record->state); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="zip_code"><?php esc_html_e('ZIP Code', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="zip_code" id="zip_code" value="<?php echo esc_attr($obdm_record->zip_code); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="country"><?php esc_html_e('Country', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="country" id="country" value="<?php echo esc_attr($obdm_record->country); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="latitude"><?php esc_html_e('Latitude', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="latitude" id="latitude" value="<?php echo esc_attr($obdm_record->latitude); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="longitude"><?php esc_html_e('Longitude', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="longitude" id="longitude" value="<?php echo esc_attr($obdm_record->longitude); ?>">
                    </p>
                </div>
            </div>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Contact & Hours', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field">
                        <label for="phone"><?php esc_html_e('Phone', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="phone" id="phone" value="<?php echo esc_attr($obdm_record->phone); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="email"><?php esc_html_e('Email', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="email" name="email" id="email" value="<?php echo esc_attr($obdm_record->email); ?>" required>
                    </p>
                    <p class="obdm-field obdm-field--full">
                        <label for="website"><?php esc_html_e('Website', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="url" name="website" id="website" value="<?php echo esc_attr($obdm_record->website); ?>" placeholder="https://">
                    </p>
                    <p class="obdm-field obdm-field--full">
                        <label for="operating_hours"><?php esc_html_e('Operating Hours', 'obydullah-blood-bank-manager'); ?></label>
                        <textarea name="operating_hours" id="operating_hours" rows="2" placeholder="<?php esc_attr_e('Mon-Sun, 9:00 AM - 8:00 PM', 'obydullah-blood-bank-manager'); ?>"><?php echo esc_textarea($obdm_record->operating_hours); ?></textarea>
                    </p>
                    <p class="obdm-field obdm-field--switch obdm-field--full">
                        <label for="is_active">
                            <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($obdm_record->is_active, 1); ?>>
                            <span class="obdm-switch__text">
                                <strong><?php esc_html_e('Active Listing', 'obydullah-blood-bank-manager'); ?></strong>
                                <em><?php esc_html_e('Inactive blood banks are hidden from the front end directory.', 'obydullah-blood-bank-manager'); ?></em>
                            </span>
                        </label>
                    </p>
                </div>
            </div>

            <div class="obdm-form__actions">
                <input type="submit" class="obdm-button obdm-button--primary" value="<?php esc_attr_e('Save Blood Bank', 'obydullah-blood-bank-manager'); ?>">
                <a class="obdm-button obdm-button--ghost" href="<?php echo esc_url(admin_url('admin.php?page=obdm-blood-banks')); ?>"><?php esc_html_e('Cancel', 'obydullah-blood-bank-manager'); ?></a>
            </div>
        </form>

    <?php else: ?>

        <?php
        Obdm_Blood_Bank_Manager::ui_page_header([
            'title'        => __('Blood Banks', 'obydullah-blood-bank-manager'),
            'subtitle'     => sprintf(
                /* translators: %s: number of blood banks. */
                _n('%s collection point registered.', '%s collection points registered.', (int) $obdm_total, 'obydullah-blood-bank-manager'),
                number_format_i18n($obdm_total)
            ),
            'action_url'   => admin_url('admin.php?page=obdm-blood-banks&action=add'),
            'action_label' => __('Add blood bank', 'obydullah-blood-bank-manager'),
        ]);
        ?>

        <?php if ($obdm_message): ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html($obdm_message); ?></p></div>
        <?php endif; ?>

        <div class="obdm-toolbar">
            <form method="get" action="" class="obdm-toolbar__form">
                <input type="hidden" name="page" value="obdm-blood-banks">
                <label class="obdm-toolbar__field obdm-toolbar__field--search">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Search', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-search')); ?>
                        <input type="search" name="s" value="<?php echo esc_attr($obdm_search); ?>" placeholder="<?php esc_attr_e('Name, city or email', 'obydullah-blood-bank-manager'); ?>">
                    </span>
                </label>
                <label class="obdm-toolbar__field">
                    <span class="obdm-toolbar__label"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <select name="city">
                            <option value=""><?php esc_html_e('All cities', 'obydullah-blood-bank-manager'); ?></option>
                            <?php foreach ($obdm_cities as $obdm_city_option): ?>
                                <option value="<?php echo esc_attr($obdm_city_option); ?>" <?php selected($obdm_filter_city, $obdm_city_option); ?>><?php echo esc_html($obdm_city_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
                <label class="obdm-toolbar__field">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Status', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <select name="status">
                            <option value=""><?php esc_html_e('All', 'obydullah-blood-bank-manager'); ?></option>
                            <option value="active" <?php selected($obdm_filter_status, 'active'); ?>><?php esc_html_e('Active', 'obydullah-blood-bank-manager'); ?></option>
                            <option value="inactive" <?php selected($obdm_filter_status, 'inactive'); ?>><?php esc_html_e('Inactive', 'obydullah-blood-bank-manager'); ?></option>
                        </select>
                    </span>
                </label>
                <div class="obdm-toolbar__actions">
                    <button type="submit" class="obdm-button obdm-button--primary"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-filter')); ?><?php esc_html_e('Apply filters', 'obydullah-blood-bank-manager'); ?></button>
                    <?php if ($obdm_search || $obdm_filter_city || $obdm_filter_status): ?>
                        <a class="obdm-button obdm-button--ghost" href="<?php echo esc_url(admin_url('admin.php?page=obdm-blood-banks')); ?>"><?php esc_html_e('Reset', 'obydullah-blood-bank-manager'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="obdm-table-card">
            <table class="obdm-table">
                <thead>
                    <tr>
                        <th class="obdm-col-name"><?php esc_html_e('Blood bank', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-city"><?php esc_html_e('Location', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-phone"><?php esc_html_e('Contact', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-hours"><?php esc_html_e('Hours', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-status"><?php esc_html_e('Status', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-actions"><?php esc_html_e('Actions', 'obydullah-blood-bank-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($obdm_banks)): ?>
                        <tr>
                            <td colspan="6">
                                <?php
                                $obdm_has_filters = $obdm_search || $obdm_filter_city || $obdm_filter_status;
                                Obdm_Blood_Bank_Manager::ui_empty_state([
                                    'icon'         => 'dashicons-building',
                                    'title'        => $obdm_has_filters
                                        ? __('No blood banks match these filters', 'obydullah-blood-bank-manager')
                                        : __('No blood banks yet', 'obydullah-blood-bank-manager'),
                                    'description'  => $obdm_has_filters
                                        ? __('Try clearing the filters to see every location.', 'obydullah-blood-bank-manager')
                                        : __('Add the hospitals and collection points that help your community donate.', 'obydullah-blood-bank-manager'),
                                    'action_url'   => $obdm_has_filters ? '' : admin_url('admin.php?page=obdm-blood-banks&action=add'),
                                    'action_label' => $obdm_has_filters ? '' : __('Add blood bank', 'obydullah-blood-bank-manager'),
                                ]);
                                ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($obdm_banks as $obdm_bank_row): ?>
                            <tr>
                                <td class="obdm-col-name">
                                    <div class="obdm-identity">
                                        <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_avatar($obdm_bank_row->name)); ?>
                                        <div class="obdm-identity__text">
                                            <span class="obdm-identity__name"><?php echo esc_html($obdm_bank_row->name); ?></span>
                                            <span class="obdm-identity__meta"><?php echo esc_html($obdm_bank_row->description ? wp_trim_words($obdm_bank_row->description, 10) : $obdm_bank_row->address); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="obdm-col-city"><?php echo esc_html(trim($obdm_bank_row->city . ', ' . $obdm_bank_row->state)); ?></td>
                                <td class="obdm-col-phone">
                                    <span class="obdm-identity__name"><?php echo esc_html($obdm_bank_row->phone); ?></span>
                                    <span class="obdm-identity__meta"><?php echo esc_html($obdm_bank_row->email); ?></span>
                                </td>
                                <td class="obdm-col-hours"><?php echo esc_html($obdm_bank_row->operating_hours); ?></td>
                                <td class="obdm-col-status"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_status_badge($obdm_bank_row->is_active ? 'active' : 'inactive')); ?></td>
                                <td class="obdm-col-actions">
                                    <?php
                                    Obdm_Blood_Bank_Manager::ui_row_actions(
                                        admin_url('admin.php?page=obdm-blood-banks&action=edit&id=' . $obdm_bank_row->id),
                                        wp_nonce_url(admin_url('admin.php?page=obdm-blood-banks&action=delete&id=' . $obdm_bank_row->id), 'obdm_delete_blood_bank_' . $obdm_bank_row->id),
                                        __('Are you sure you want to delete this blood bank?', 'obydullah-blood-bank-manager')
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php Obdm_Blood_Bank_Manager::ui_pagination($obdm_page, $obdm_total_pages, $obdm_total); ?>
    <?php endif; ?>
</div>
