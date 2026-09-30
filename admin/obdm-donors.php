<?php
if (!defined('ABSPATH')) exit;

$obdm = Obdm_Blood_Bank_Manager::admin_donors_data();

$obdm_action  = Obdm_Blood_Bank_Manager::admin_record_action();
$obdm_id      = Obdm_Blood_Bank_Manager::admin_record_id();
$obdm_missing = false;
$obdm_donor   = null;

if ($obdm_action === 'edit' && $obdm_id) {
    $obdm_donor = Obdm_Blood_Bank_Manager::admin_donor_record($obdm_id);

    if (!$obdm_donor->id) {
        $obdm_missing = true;
        $obdm_action  = 'list';
    }
} else {
    $obdm_donor = Obdm_Blood_Bank_Manager::admin_donor_record(0);
}

$obdm_total       = $obdm['total'];
$obdm_donors      = $obdm['items'];
$obdm_total_pages = $obdm['total_pages'];
$obdm_page        = $obdm['page'];
$obdm_cities      = $obdm['cities'];
$obdm_search      = $obdm['search'];
$obdm_filter_blood = $obdm['blood_type'];
$obdm_filter_city  = $obdm['city'];
$obdm_message     = Obdm_Blood_Bank_Manager::admin_notice_message();
$obdm_blood_types = Obdm_Blood_Bank_Manager::get_blood_types();
$obdm_has_filters = '' !== $obdm_search || '' !== $obdm_filter_blood || '' !== $obdm_filter_city;
?>
<div class="wrap obdm-wrap">
    <?php if ($obdm_missing): ?>
        <div class="notice notice-error"><p><?php esc_html_e('That record could not be found.', 'obydullah-blood-bank-manager'); ?></p></div>
    <?php endif; ?>
    <?php if ($obdm_action === 'add' || $obdm_action === 'edit'): ?>

        <?php
        Obdm_Blood_Bank_Manager::ui_page_header([
            'title'    => $obdm_id ? __('Edit Donor', 'obydullah-blood-bank-manager') : __('Add New Donor', 'obydullah-blood-bank-manager'),
            'subtitle' => __('Donor details are used for matching and email notifications.', 'obydullah-blood-bank-manager'),
            'back_url' => admin_url('admin.php?page=obdm-donors'),
        ]);
        ?>

        <?php if ($obdm_message): ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html($obdm_message); ?></p></div>
        <?php endif; ?>

        <form method="post" action="" class="obdm-form">
            <?php wp_nonce_field('obdm_donor_action', 'obdm_donor_nonce'); ?>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Identity', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field">
                        <label for="first_name"><?php esc_html_e('First Name', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($obdm_donor->first_name); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="last_name"><?php esc_html_e('Last Name', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($obdm_donor->last_name); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="email"><?php esc_html_e('Email', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="email" name="email" id="email" value="<?php echo esc_attr($obdm_donor->email); ?>" required>
                    </p>
                    <p class="obdm-field">
                        <label for="phone"><?php esc_html_e('Phone', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <input type="text" name="phone" id="phone" value="<?php echo esc_attr($obdm_donor->phone); ?>" required>
                    </p>
                </div>
            </div>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Donation Profile', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field">
                        <label for="blood_type"><?php esc_html_e('Blood Type', 'obydullah-blood-bank-manager'); ?> <span class="obdm-req">*</span></label>
                        <select name="blood_type" id="blood_type" required>
                            <option value=""><?php esc_html_e('Select Blood Type', 'obydullah-blood-bank-manager'); ?></option>
                            <?php foreach ($obdm_blood_types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($obdm_donor->blood_type, $type); ?>><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p class="obdm-field">
                        <label for="weight"><?php esc_html_e('Weight (kg)', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="number" name="weight" id="weight" value="<?php echo esc_attr($obdm_donor->weight); ?>" step="0.01" min="0">
                    </p>
                    <p class="obdm-field">
                        <label for="date_of_birth"><?php esc_html_e('Date of Birth', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo esc_attr($obdm_donor->date_of_birth); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="gender"><?php esc_html_e('Gender', 'obydullah-blood-bank-manager'); ?></label>
                        <select name="gender" id="gender">
                            <option value=""><?php esc_html_e('Select Gender', 'obydullah-blood-bank-manager'); ?></option>
                            <option value="male" <?php selected($obdm_donor->gender, 'male'); ?>><?php esc_html_e('Male', 'obydullah-blood-bank-manager'); ?></option>
                            <option value="female" <?php selected($obdm_donor->gender, 'female'); ?>><?php esc_html_e('Female', 'obydullah-blood-bank-manager'); ?></option>
                            <option value="other" <?php selected($obdm_donor->gender, 'other'); ?>><?php esc_html_e('Other', 'obydullah-blood-bank-manager'); ?></option>
                        </select>
                    </p>
                    <p class="obdm-field">
                        <label for="last_donation_date"><?php esc_html_e('Last Donation Date', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="date" name="last_donation_date" id="last_donation_date" value="<?php echo esc_attr($obdm_donor->last_donation_date); ?>">
                    </p>
                    <p class="obdm-field obdm-field--switch">
                        <label for="is_available">
                            <input type="checkbox" name="is_available" id="is_available" value="1" <?php checked($obdm_donor->is_available, 1); ?>>
                            <span class="obdm-switch__text">
                                <strong><?php esc_html_e('Available to Donate', 'obydullah-blood-bank-manager'); ?></strong>
                                <em><?php esc_html_e('Unavailable donors are hidden from compatibility matching.', 'obydullah-blood-bank-manager'); ?></em>
                            </span>
                        </label>
                    </p>
                </div>
            </div>

            <div class="obdm-form__card">
                <h2 class="obdm-form__section"><?php esc_html_e('Location & Health', 'obydullah-blood-bank-manager'); ?></h2>
                <div class="obdm-form__grid">
                    <p class="obdm-field obdm-field--full">
                        <label for="address"><?php esc_html_e('Address', 'obydullah-blood-bank-manager'); ?></label>
                        <textarea name="address" id="address" rows="3"><?php echo esc_textarea($obdm_donor->address); ?></textarea>
                    </p>
                    <p class="obdm-field">
                        <label for="city"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="city" id="city" value="<?php echo esc_attr($obdm_donor->city); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="state"><?php esc_html_e('State', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="state" id="state" value="<?php echo esc_attr($obdm_donor->state); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="zip_code"><?php esc_html_e('ZIP Code', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="zip_code" id="zip_code" value="<?php echo esc_attr($obdm_donor->zip_code); ?>">
                    </p>
                    <p class="obdm-field">
                        <label for="country"><?php esc_html_e('Country', 'obydullah-blood-bank-manager'); ?></label>
                        <input type="text" name="country" id="country" value="<?php echo esc_attr($obdm_donor->country); ?>">
                    </p>
                    <p class="obdm-field obdm-field--full">
                        <label for="medical_conditions"><?php esc_html_e('Medical Conditions', 'obydullah-blood-bank-manager'); ?></label>
                        <textarea name="medical_conditions" id="medical_conditions" rows="3"><?php echo esc_textarea($obdm_donor->medical_conditions); ?></textarea>
                    </p>
                </div>
            </div>

            <div class="obdm-form__actions">
                <input type="submit" class="obdm-button obdm-button--primary" value="<?php esc_attr_e('Save Donor', 'obydullah-blood-bank-manager'); ?>">
                <a class="obdm-button obdm-button--ghost" href="<?php echo esc_url(admin_url('admin.php?page=obdm-donors')); ?>"><?php esc_html_e('Cancel', 'obydullah-blood-bank-manager'); ?></a>
            </div>
        </form>

    <?php else: ?>

        <?php
        Obdm_Blood_Bank_Manager::ui_page_header([
            'title'        => __('Donors', 'obydullah-blood-bank-manager'),
            'subtitle'     => sprintf(
                /* translators: %s: number of donors. */
                _n('%s donor in the database.', '%s donors in the database.', (int) $obdm_total, 'obydullah-blood-bank-manager'),
                number_format_i18n($obdm_total)
            ),
            'action_url'   => admin_url('admin.php?page=obdm-donors&action=add'),
            'action_label' => __('Add donor', 'obydullah-blood-bank-manager'),
        ]);
        ?>

        <?php if ($obdm_message): ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html($obdm_message); ?></p></div>
        <?php endif; ?>

        <div class="obdm-toolbar">
            <form method="get" action="" class="obdm-toolbar__form">
                <input type="hidden" name="page" value="obdm-donors">
                <label class="obdm-toolbar__field obdm-toolbar__field--search">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Search', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-search')); ?>
                        <input type="search" name="s" value="<?php echo esc_attr($obdm_search); ?>" placeholder="<?php esc_attr_e('Name, email or phone', 'obydullah-blood-bank-manager'); ?>">
                    </span>
                </label>
                <label class="obdm-toolbar__field">
                    <span class="obdm-toolbar__label"><?php esc_html_e('Blood type', 'obydullah-blood-bank-manager'); ?></span>
                    <span class="obdm-toolbar__control">
                        <select name="blood_type">
                            <option value=""><?php esc_html_e('All blood types', 'obydullah-blood-bank-manager'); ?></option>
                            <?php foreach ($obdm_blood_types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($obdm_filter_blood, $type); ?>><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                <div class="obdm-toolbar__actions">
                    <button type="submit" class="obdm-button obdm-button--primary"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_icon('dashicons-filter')); ?><?php esc_html_e('Apply filters', 'obydullah-blood-bank-manager'); ?></button>
                    <?php if ($obdm_has_filters): ?>
                        <a class="obdm-button obdm-button--ghost" href="<?php echo esc_url(admin_url('admin.php?page=obdm-donors')); ?>"><?php esc_html_e('Reset', 'obydullah-blood-bank-manager'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="obdm-table-card">
            <table class="obdm-table">
                <thead>
                    <tr>
                        <th class="obdm-col-name"><?php esc_html_e('Donor', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-phone"><?php esc_html_e('Phone', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-blood"><?php esc_html_e('Blood type', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-city"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-status"><?php esc_html_e('Availability', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-date"><?php esc_html_e('Registered', 'obydullah-blood-bank-manager'); ?></th>
                        <th class="obdm-col-actions"><?php esc_html_e('Actions', 'obydullah-blood-bank-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($obdm_donors)): ?>
                        <tr>
                            <td colspan="7">
                                <?php
                                Obdm_Blood_Bank_Manager::ui_empty_state([
                                    'icon'         => 'dashicons-groups',
                                    'title'        => $obdm_has_filters
                                        ? __('No donors match these filters', 'obydullah-blood-bank-manager')
                                        : __('No donors registered yet', 'obydullah-blood-bank-manager'),
                                    'description'  => $obdm_has_filters
                                        ? __('Try widening your search or clearing the filters.', 'obydullah-blood-bank-manager')
                                        : __('Donors who register themselves or are added here will show up in this list.', 'obydullah-blood-bank-manager'),
                                    'action_url'   => $obdm_has_filters ? '' : admin_url('admin.php?page=obdm-donors&action=add'),
                                    'action_label' => $obdm_has_filters ? '' : __('Add donor', 'obydullah-blood-bank-manager'),
                                ]);
                                ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($obdm_donors as $obdm_donor_row): ?>
                            <tr>
                                <td class="obdm-col-name">
                                    <div class="obdm-identity">
                                        <?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_avatar($obdm_donor_row->first_name . ' ' . $obdm_donor_row->last_name)); ?>
                                        <div class="obdm-identity__text">
                                            <span class="obdm-identity__name"><?php echo esc_html($obdm_donor_row->first_name . ' ' . $obdm_donor_row->last_name); ?></span>
                                            <span class="obdm-identity__meta"><?php echo esc_html($obdm_donor_row->email); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="obdm-col-phone"><?php echo esc_html($obdm_donor_row->phone); ?></td>
                                <td class="obdm-col-blood"><?php echo wp_kses_post(Obdm_Blood_Bank_Manager::ui_blood_badge($obdm_donor_row->blood_type)); ?></td>
                                <td class="obdm-col-city"><?php echo esc_html($obdm_donor_row->city); ?></td>
                                <td class="obdm-col-status">
                                    <?php if ($obdm_donor_row->is_available): ?>
                                        <span class="obdm-badge obdm-badge--available"><span class="obdm-badge__dot"></span><?php esc_html_e('Available', 'obydullah-blood-bank-manager'); ?></span>
                                    <?php else: ?>
                                        <span class="obdm-badge obdm-badge--muted"><span class="obdm-badge__dot"></span><?php esc_html_e('Paused', 'obydullah-blood-bank-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="obdm-col-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($obdm_donor_row->created_at))); ?></td>
                                <td class="obdm-col-actions">
                                    <?php
                                    Obdm_Blood_Bank_Manager::ui_row_actions(
                                        admin_url('admin.php?page=obdm-donors&action=edit&id=' . $obdm_donor_row->id),
                                        wp_nonce_url(admin_url('admin.php?page=obdm-donors&action=delete&id=' . $obdm_donor_row->id), 'obdm_delete_donor_' . $obdm_donor_row->id),
                                        __('Are you sure you want to delete this donor?', 'obydullah-blood-bank-manager')
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
