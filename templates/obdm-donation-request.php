<?php
if (!defined('ABSPATH')) exit;

$obdm_blood_types = Obdm_Blood_Bank_Manager::get_blood_types();
$obdm_urgency_labels = Obdm_Blood_Bank_Manager::get_urgency_labels();
?>
<div class="obdm-form-wrapper obdm-donation-request">
    <div class="obdm-form-header">
        <span class="obdm-section-icon"><span class="dashicons dashicons-email-alt"></span></span>
        <div class="obdm-section-text">
            <h2><?php esc_html_e('Request Blood Donation', 'obydullah-blood-bank-manager'); ?></h2>
            <p><?php esc_html_e('Need blood? Submit a request and we will connect you with available donors.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    </div>

    <div id="obdm-request-message" class="obdm-message" style="display:none;"></div>

    <form id="obdm-request-form" class="obdm-form">
        <div class="obdm-form-section">
            <h3><?php esc_html_e('Requester Information', 'obydullah-blood-bank-manager'); ?></h3>
            <div class="obdm-form-row obdm-form-row-2">
                <div class="obdm-form-group">
                    <label for="obdm-req-name"><?php esc_html_e('Your Name', 'obydullah-blood-bank-manager'); ?> *</label>
                    <input type="text" id="obdm-req-name" name="requester_name" required>
                </div>
                <div class="obdm-form-group">
                    <label for="obdm-req-email"><?php esc_html_e('Your Email', 'obydullah-blood-bank-manager'); ?> *</label>
                    <input type="email" id="obdm-req-email" name="requester_email" required>
                </div>
            </div>
            <div class="obdm-form-group">
                <label for="obdm-req-phone"><?php esc_html_e('Your Phone', 'obydullah-blood-bank-manager'); ?> *</label>
                <input type="tel" id="obdm-req-phone" name="requester_phone" required>
            </div>
        </div>

        <div class="obdm-form-section">
            <h3><?php esc_html_e('Patient Information', 'obydullah-blood-bank-manager'); ?></h3>
            <div class="obdm-form-row obdm-form-row-2">
                <div class="obdm-form-group">
                    <label for="obdm-patient-name"><?php esc_html_e('Patient Name', 'obydullah-blood-bank-manager'); ?> *</label>
                    <input type="text" id="obdm-patient-name" name="patient_name" required>
                </div>
                <div class="obdm-form-group">
                    <label for="obdm-blood-needed"><?php esc_html_e('Blood Type Needed', 'obydullah-blood-bank-manager'); ?> *</label>
                    <select id="obdm-blood-needed" name="blood_type_needed" required>
                        <option value=""><?php esc_html_e('Select Blood Type', 'obydullah-blood-bank-manager'); ?></option>
                        <?php foreach ($obdm_blood_types as $obdm_type): ?>
                            <option value="<?php echo esc_attr($obdm_type); ?>"><?php echo esc_html($obdm_type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="obdm-form-row obdm-form-row-2">
                <div class="obdm-form-group">
                    <label for="obdm-units"><?php esc_html_e('Units Needed', 'obydullah-blood-bank-manager'); ?> *</label>
                    <input type="number" id="obdm-units" name="units_needed" value="1" min="1" max="10" required>
                </div>
                <div class="obdm-form-group">
                    <label for="obdm-urgency"><?php esc_html_e('Urgency', 'obydullah-blood-bank-manager'); ?> *</label>
                    <select id="obdm-urgency" name="urgency" required>
                        <?php foreach ($obdm_urgency_labels as $obdm_key => $obdm_label): ?>
                            <option value="<?php echo esc_attr($obdm_key); ?>"><?php echo esc_html($obdm_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="obdm-form-section">
            <h3><?php esc_html_e('Hospital Information', 'obydullah-blood-bank-manager'); ?></h3>
            <div class="obdm-form-row obdm-form-row-2">
                <div class="obdm-form-group">
                    <label for="obdm-hospital"><?php esc_html_e('Hospital Name', 'obydullah-blood-bank-manager'); ?> *</label>
                    <input type="text" id="obdm-hospital" name="hospital_name" required>
                </div>
                <div class="obdm-form-group">
                    <label for="obdm-hospital-city"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?></label>
                    <input type="text" id="obdm-hospital-city" name="city">
                </div>
            </div>
            <div class="obdm-form-group">
                <label for="obdm-hospital-address"><?php esc_html_e('Hospital Address', 'obydullah-blood-bank-manager'); ?></label>
                <textarea id="obdm-hospital-address" name="hospital_address" rows="2"></textarea>
            </div>
            <div class="obdm-form-group">
                <label for="obdm-needed-by"><?php esc_html_e('Needed By Date', 'obydullah-blood-bank-manager'); ?></label>
                <input type="date" id="obdm-needed-by" name="needed_by">
            </div>
        </div>

        <div class="obdm-form-group">
            <label for="obdm-additional"><?php esc_html_e('Additional Information', 'obydullah-blood-bank-manager'); ?></label>
            <textarea id="obdm-additional" name="additional_info" rows="3" placeholder="<?php esc_attr_e('Any additional details...', 'obydullah-blood-bank-manager'); ?>"></textarea>
        </div>

        <div class="obdm-form-submit">
            <button type="submit" class="obdm-btn obdm-btn-primary" id="obdm-request-submit">
                <span class="obdm-btn-text"><?php esc_html_e('Submit Request', 'obydullah-blood-bank-manager'); ?></span>
                <span class="obdm-btn-loading" style="display:none;"><?php esc_html_e('Submitting...', 'obydullah-blood-bank-manager'); ?></span>
            </button>
        </div>
    </form>
</div>
