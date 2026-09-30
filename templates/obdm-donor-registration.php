<?php
if (!defined('ABSPATH')) exit;

$obdm_blood_types = Obdm_Blood_Bank_Manager::get_blood_types();
$obdm_settings = get_option('obdm_settings', []);
$obdm_custom_message = isset($obdm_settings['custom_message']) ? $obdm_settings['custom_message'] : '';
?>
<div class="obdm-form-wrapper obdm-donor-registration">
    <div class="obdm-form-header">
        <span class="obdm-section-icon"><span class="dashicons dashicons-admin-users"></span></span>
        <div class="obdm-section-text">
            <h2><?php esc_html_e('Register as a Blood Donor', 'obydullah-blood-bank-manager'); ?></h2>
            <p><?php esc_html_e('Join our community of life-savers. Register as a blood donor and help those in need.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    </div>

    <?php if ($obdm_custom_message): ?>
        <div class="obdm-custom-message"><?php echo wp_kses_post($obdm_custom_message); ?></div>
    <?php endif; ?>

    <div id="obdm-donor-message" class="obdm-message" style="display:none;"></div>

    <form id="obdm-donor-form" class="obdm-form">
        <div class="obdm-form-row obdm-form-row-2">
            <div class="obdm-form-group">
                <label for="obdm-first-name"><?php esc_html_e('First Name', 'obydullah-blood-bank-manager'); ?> *</label>
                <input type="text" id="obdm-first-name" name="first_name" required>
            </div>
            <div class="obdm-form-group">
                <label for="obdm-last-name"><?php esc_html_e('Last Name', 'obydullah-blood-bank-manager'); ?> *</label>
                <input type="text" id="obdm-last-name" name="last_name" required>
            </div>
        </div>

        <div class="obdm-form-row obdm-form-row-2">
            <div class="obdm-form-group">
                <label for="obdm-email"><?php esc_html_e('Email', 'obydullah-blood-bank-manager'); ?> *</label>
                <input type="email" id="obdm-email" name="email" required>
            </div>
            <div class="obdm-form-group">
                <label for="obdm-phone"><?php esc_html_e('Phone', 'obydullah-blood-bank-manager'); ?> *</label>
                <input type="tel" id="obdm-phone" name="phone" required>
            </div>
        </div>

        <div class="obdm-form-row obdm-form-row-2">
            <div class="obdm-form-group">
                <label for="obdm-blood-type"><?php esc_html_e('Blood Type', 'obydullah-blood-bank-manager'); ?> *</label>
                <select id="obdm-blood-type" name="blood_type" required>
                    <option value=""><?php esc_html_e('Select Blood Type', 'obydullah-blood-bank-manager'); ?></option>
                    <?php foreach ($obdm_blood_types as $obdm_type): ?>
                        <option value="<?php echo esc_attr($obdm_type); ?>"><?php echo esc_html($obdm_type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="obdm-form-group">
                <label for="obdm-dob"><?php esc_html_e('Date of Birth', 'obydullah-blood-bank-manager'); ?></label>
                <input type="date" id="obdm-dob" name="date_of_birth">
            </div>
        </div>

        <div class="obdm-form-row obdm-form-row-2">
            <div class="obdm-form-group">
                <label for="obdm-gender"><?php esc_html_e('Gender', 'obydullah-blood-bank-manager'); ?></label>
                <select id="obdm-gender" name="gender">
                    <option value=""><?php esc_html_e('Select Gender', 'obydullah-blood-bank-manager'); ?></option>
                    <option value="male"><?php esc_html_e('Male', 'obydullah-blood-bank-manager'); ?></option>
                    <option value="female"><?php esc_html_e('Female', 'obydullah-blood-bank-manager'); ?></option>
                    <option value="other"><?php esc_html_e('Other', 'obydullah-blood-bank-manager'); ?></option>
                </select>
            </div>
            <div class="obdm-form-group">
                <label for="obdm-weight"><?php esc_html_e('Weight (kg)', 'obydullah-blood-bank-manager'); ?></label>
                <input type="number" id="obdm-weight" name="weight" step="0.1" min="0">
            </div>
        </div>

        <div class="obdm-form-group">
            <label for="obdm-address"><?php esc_html_e('Address', 'obydullah-blood-bank-manager'); ?></label>
            <textarea id="obdm-address" name="address" rows="2"></textarea>
        </div>

        <div class="obdm-form-row obdm-form-row-3">
            <div class="obdm-form-group">
                <label for="obdm-city"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?></label>
                <input type="text" id="obdm-city" name="city">
            </div>
            <div class="obdm-form-group">
                <label for="obdm-state"><?php esc_html_e('State', 'obydullah-blood-bank-manager'); ?></label>
                <input type="text" id="obdm-state" name="state">
            </div>
            <div class="obdm-form-group">
                <label for="obdm-zip"><?php esc_html_e('ZIP Code', 'obydullah-blood-bank-manager'); ?></label>
                <input type="text" id="obdm-zip" name="zip_code">
            </div>
        </div>

        <div class="obdm-form-row obdm-form-row-2">
            <div class="obdm-form-group">
                <label for="obdm-country"><?php esc_html_e('Country', 'obydullah-blood-bank-manager'); ?></label>
                <input type="text" id="obdm-country" name="country">
            </div>
            <div class="obdm-form-group">
                <label for="obdm-last-donation"><?php esc_html_e('Last Donation Date', 'obydullah-blood-bank-manager'); ?></label>
                <input type="date" id="obdm-last-donation" name="last_donation_date">
            </div>
        </div>

        <div class="obdm-form-group">
            <label for="obdm-medical"><?php esc_html_e('Medical Conditions', 'obydullah-blood-bank-manager'); ?></label>
            <textarea id="obdm-medical" name="medical_conditions" rows="2" placeholder="<?php esc_attr_e('Please list any medical conditions...', 'obydullah-blood-bank-manager'); ?>"></textarea>
        </div>

        <div class="obdm-form-group obdm-checkbox-group">
            <label>
                <input type="checkbox" name="is_available" value="1" checked>
                <?php esc_html_e('I am available to donate blood', 'obydullah-blood-bank-manager'); ?>
            </label>
        </div>

        <div class="obdm-form-submit">
            <button type="submit" class="obdm-btn obdm-btn-primary" id="obdm-donor-submit">
                <span class="obdm-btn-text"><?php esc_html_e('Register as Donor', 'obydullah-blood-bank-manager'); ?></span>
                <span class="obdm-btn-loading" style="display:none;"><?php esc_html_e('Submitting...', 'obydullah-blood-bank-manager'); ?></span>
            </button>
        </div>
    </form>
</div>
