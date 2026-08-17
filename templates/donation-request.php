<?php
if (!defined('ABSPATH')) exit;

$blood_types = Blood_Donation_Manager::get_blood_types();
$urgency_labels = Blood_Donation_Manager::get_urgency_labels();
?>
<div class="bdm-form-wrapper bdm-donation-request">
    <div class="bdm-form-header">
        <h2><?php _e('Request Blood Donation', 'blood-donation'); ?></h2>
        <p><?php _e('Need blood? Submit a request and we will connect you with available donors.', 'blood-donation'); ?></p>
    </div>

    <div id="bdm-request-message" class="bdm-message" style="display:none;"></div>

    <form id="bdm-request-form" class="bdm-form">
        <div class="bdm-form-section">
            <h3><?php _e('Requester Information', 'blood-donation'); ?></h3>
            <div class="bdm-form-row bdm-form-row-2">
                <div class="bdm-form-group">
                    <label for="bdm-req-name"><?php _e('Your Name', 'blood-donation'); ?> *</label>
                    <input type="text" id="bdm-req-name" name="requester_name" required>
                </div>
                <div class="bdm-form-group">
                    <label for="bdm-req-email"><?php _e('Your Email', 'blood-donation'); ?> *</label>
                    <input type="email" id="bdm-req-email" name="requester_email" required>
                </div>
            </div>
            <div class="bdm-form-group">
                <label for="bdm-req-phone"><?php _e('Your Phone', 'blood-donation'); ?> *</label>
                <input type="tel" id="bdm-req-phone" name="requester_phone" required>
            </div>
        </div>

        <div class="bdm-form-section">
            <h3><?php _e('Patient Information', 'blood-donation'); ?></h3>
            <div class="bdm-form-row bdm-form-row-2">
                <div class="bdm-form-group">
                    <label for="bdm-patient-name"><?php _e('Patient Name', 'blood-donation'); ?> *</label>
                    <input type="text" id="bdm-patient-name" name="patient_name" required>
                </div>
                <div class="bdm-form-group">
                    <label for="bdm-blood-needed"><?php _e('Blood Type Needed', 'blood-donation'); ?> *</label>
                    <select id="bdm-blood-needed" name="blood_type_needed" required>
                        <option value=""><?php _e('Select Blood Type', 'blood-donation'); ?></option>
                        <?php foreach ($blood_types as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="bdm-form-row bdm-form-row-2">
                <div class="bdm-form-group">
                    <label for="bdm-units"><?php _e('Units Needed', 'blood-donation'); ?> *</label>
                    <input type="number" id="bdm-units" name="units_needed" value="1" min="1" max="10" required>
                </div>
                <div class="bdm-form-group">
                    <label for="bdm-urgency"><?php _e('Urgency', 'blood-donation'); ?> *</label>
                    <select id="bdm-urgency" name="urgency" required>
                        <?php foreach ($urgency_labels as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="bdm-form-section">
            <h3><?php _e('Hospital Information', 'blood-donation'); ?></h3>
            <div class="bdm-form-row bdm-form-row-2">
                <div class="bdm-form-group">
                    <label for="bdm-hospital"><?php _e('Hospital Name', 'blood-donation'); ?> *</label>
                    <input type="text" id="bdm-hospital" name="hospital_name" required>
                </div>
                <div class="bdm-form-group">
                    <label for="bdm-hospital-city"><?php _e('City', 'blood-donation'); ?></label>
                    <input type="text" id="bdm-hospital-city" name="city">
                </div>
            </div>
            <div class="bdm-form-group">
                <label for="bdm-hospital-address"><?php _e('Hospital Address', 'blood-donation'); ?></label>
                <textarea id="bdm-hospital-address" name="hospital_address" rows="2"></textarea>
            </div>
            <div class="bdm-form-group">
                <label for="bdm-needed-by"><?php _e('Needed By Date', 'blood-donation'); ?></label>
                <input type="date" id="bdm-needed-by" name="needed_by">
            </div>
        </div>

        <div class="bdm-form-group">
            <label for="bdm-additional"><?php _e('Additional Information', 'blood-donation'); ?></label>
            <textarea id="bdm-additional" name="additional_info" rows="3" placeholder="<?php _e('Any additional details...', 'blood-donation'); ?>"></textarea>
        </div>

        <div class="bdm-form-submit">
            <button type="submit" class="bdm-btn bdm-btn-primary" id="bdm-request-submit">
                <span class="bdm-btn-text"><?php _e('Submit Request', 'blood-donation'); ?></span>
                <span class="bdm-btn-loading" style="display:none;"><?php _e('Submitting...', 'blood-donation'); ?></span>
            </button>
        </div>
    </form>
</div>
