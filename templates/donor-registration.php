<?php
if (!defined('ABSPATH')) exit;

$blood_types = Blood_Donation_Manager::get_blood_types();
$settings = get_option('bdm_settings', array());
$custom_message = isset($settings['custom_message']) ? $settings['custom_message'] : '';
?>
<div class="bdm-form-wrapper bdm-donor-registration">
    <div class="bdm-form-header">
        <h2><?php _e('Register as a Blood Donor', 'blood-donation'); ?></h2>
        <p><?php _e('Join our community of life-savers. Register as a blood donor and help those in need.', 'blood-donation'); ?></p>
    </div>

    <?php if ($custom_message): ?>
        <div class="bdm-custom-message"><?php echo wp_kses_post($custom_message); ?></div>
    <?php endif; ?>

    <div id="bdm-donor-message" class="bdm-message" style="display:none;"></div>

    <form id="bdm-donor-form" class="bdm-form">
        <div class="bdm-form-row bdm-form-row-2">
            <div class="bdm-form-group">
                <label for="bdm-first-name"><?php _e('First Name', 'blood-donation'); ?> *</label>
                <input type="text" id="bdm-first-name" name="first_name" required>
            </div>
            <div class="bdm-form-group">
                <label for="bdm-last-name"><?php _e('Last Name', 'blood-donation'); ?> *</label>
                <input type="text" id="bdm-last-name" name="last_name" required>
            </div>
        </div>

        <div class="bdm-form-row bdm-form-row-2">
            <div class="bdm-form-group">
                <label for="bdm-email"><?php _e('Email', 'blood-donation'); ?> *</label>
                <input type="email" id="bdm-email" name="email" required>
            </div>
            <div class="bdm-form-group">
                <label for="bdm-phone"><?php _e('Phone', 'blood-donation'); ?> *</label>
                <input type="tel" id="bdm-phone" name="phone" required>
            </div>
        </div>

        <div class="bdm-form-row bdm-form-row-2">
            <div class="bdm-form-group">
                <label for="bdm-blood-type"><?php _e('Blood Type', 'blood-donation'); ?> *</label>
                <select id="bdm-blood-type" name="blood_type" required>
                    <option value=""><?php _e('Select Blood Type', 'blood-donation'); ?></option>
                    <?php foreach ($blood_types as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="bdm-form-group">
                <label for="bdm-dob"><?php _e('Date of Birth', 'blood-donation'); ?></label>
                <input type="date" id="bdm-dob" name="date_of_birth">
            </div>
        </div>

        <div class="bdm-form-row bdm-form-row-2">
            <div class="bdm-form-group">
                <label for="bdm-gender"><?php _e('Gender', 'blood-donation'); ?></label>
                <select id="bdm-gender" name="gender">
                    <option value=""><?php _e('Select Gender', 'blood-donation'); ?></option>
                    <option value="male"><?php _e('Male', 'blood-donation'); ?></option>
                    <option value="female"><?php _e('Female', 'blood-donation'); ?></option>
                    <option value="other"><?php _e('Other', 'blood-donation'); ?></option>
                </select>
            </div>
            <div class="bdm-form-group">
                <label for="bdm-weight"><?php _e('Weight (kg)', 'blood-donation'); ?></label>
                <input type="number" id="bdm-weight" name="weight" step="0.1" min="0">
            </div>
        </div>

        <div class="bdm-form-group">
            <label for="bdm-address"><?php _e('Address', 'blood-donation'); ?></label>
            <textarea id="bdm-address" name="address" rows="2"></textarea>
        </div>

        <div class="bdm-form-row bdm-form-row-3">
            <div class="bdm-form-group">
                <label for="bdm-city"><?php _e('City', 'blood-donation'); ?></label>
                <input type="text" id="bdm-city" name="city">
            </div>
            <div class="bdm-form-group">
                <label for="bdm-state"><?php _e('State', 'blood-donation'); ?></label>
                <input type="text" id="bdm-state" name="state">
            </div>
            <div class="bdm-form-group">
                <label for="bdm-zip"><?php _e('ZIP Code', 'blood-donation'); ?></label>
                <input type="text" id="bdm-zip" name="zip_code">
            </div>
        </div>

        <div class="bdm-form-row bdm-form-row-2">
            <div class="bdm-form-group">
                <label for="bdm-country"><?php _e('Country', 'blood-donation'); ?></label>
                <input type="text" id="bdm-country" name="country">
            </div>
            <div class="bdm-form-group">
                <label for="bdm-last-donation"><?php _e('Last Donation Date', 'blood-donation'); ?></label>
                <input type="date" id="bdm-last-donation" name="last_donation_date">
            </div>
        </div>

        <div class="bdm-form-group">
            <label for="bdm-medical"><?php _e('Medical Conditions', 'blood-donation'); ?></label>
            <textarea id="bdm-medical" name="medical_conditions" rows="2" placeholder="<?php _e('Please list any medical conditions...', 'blood-donation'); ?>"></textarea>
        </div>

        <div class="bdm-form-group bdm-checkbox-group">
            <label>
                <input type="checkbox" name="is_available" value="1" checked>
                <?php _e('I am available to donate blood', 'blood-donation'); ?>
            </label>
        </div>

        <div class="bdm-form-submit">
            <button type="submit" class="bdm-btn bdm-btn-primary" id="bdm-donor-submit">
                <span class="bdm-btn-text"><?php _e('Register as Donor', 'blood-donation'); ?></span>
                <span class="bdm-btn-loading" style="display:none;"><?php _e('Submitting...', 'blood-donation'); ?></span>
            </button>
        </div>
    </form>
</div>
