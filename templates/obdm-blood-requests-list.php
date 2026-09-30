<?php
if (!defined('ABSPATH')) exit;

$obdm = Obdm_Blood_Bank_Manager::public_requests_data();

$obdm_requests      = $obdm['items'];
$obdm_search        = $obdm['search'];
$obdm_filter_blood  = $obdm['blood_type'];
$obdm_filter_status = $obdm['status'];
$obdm_filter_urgency = $obdm['urgency'];
$obdm_blood_types   = Obdm_Blood_Bank_Manager::get_blood_types();
$obdm_urgency_labels = Obdm_Blood_Bank_Manager::get_urgency_labels();
$obdm_status_labels  = Obdm_Blood_Bank_Manager::get_status_labels();
?>

<div class="obdm-list-wrapper obdm-requests-list">
    <div class="obdm-list-header">
        <span class="obdm-section-icon"><span class="dashicons dashicons-email-alt"></span></span>
        <div class="obdm-section-text">
            <h2><?php esc_html_e('Blood Donation Requests', 'obydullah-blood-bank-manager'); ?></h2>
            <p><?php esc_html_e('People who need blood donations.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    </div>

    <div class="obdm-filters">
        <form method="get" action="" class="obdm-filter-form">
            <div class="obdm-filter-row">
                <input type="text" name="obdm_search" value="<?php echo esc_attr($obdm_search); ?>" placeholder="<?php esc_attr_e('Search requests...', 'obydullah-blood-bank-manager'); ?>">
                <select name="obdm_blood_type">
                    <option value=""><?php esc_html_e('All Blood Types', 'obydullah-blood-bank-manager'); ?></option>
                    <?php foreach ($obdm_blood_types as $obdm_type): ?>
                        <option value="<?php echo esc_attr($obdm_type); ?>" <?php selected($obdm_filter_blood, $obdm_type); ?>><?php echo esc_html($obdm_type); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="obdm_status">
                    <option value=""><?php esc_html_e('All Statuses', 'obydullah-blood-bank-manager'); ?></option>
                    <?php foreach ($obdm_status_labels as $obdm_key => $obdm_label): ?>
                        <option value="<?php echo esc_attr($obdm_key); ?>" <?php selected($obdm_filter_status, $obdm_key); ?>><?php echo esc_html($obdm_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="obdm_urgency">
                    <option value=""><?php esc_html_e('All Urgency Levels', 'obydullah-blood-bank-manager'); ?></option>
                    <?php foreach ($obdm_urgency_labels as $obdm_key => $obdm_label): ?>
                        <option value="<?php echo esc_attr($obdm_key); ?>" <?php selected($obdm_filter_urgency, $obdm_key); ?>><?php echo esc_html($obdm_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="obdm-btn"><?php esc_html_e('Search', 'obydullah-blood-bank-manager'); ?></button>
            </div>
        </form>
    </div>

    <?php if (empty($obdm_requests)): ?>
        <div class="obdm-no-results">
            <p class="obdm-no-results__title"><?php esc_html_e('No blood requests found', 'obydullah-blood-bank-manager'); ?></p>
            <p><?php esc_html_e('Clear the filters to see every open request.', 'obydullah-blood-bank-manager'); ?></p>
        </div>
    <?php else: ?>
        <div class="obdm-grid obdm-requests-grid">
            <?php foreach ($obdm_requests as $obdm_req): ?>
                <div class="obdm-card obdm-request-card obdm-urgency-<?php echo esc_attr($obdm_req->urgency); ?>">
                    <div class="obdm-card-header">
                        <div class="obdm-request-urgency">
                            <span class="obdm-urgency-badge obdm-urgency-<?php echo esc_attr($obdm_req->urgency); ?>"><?php echo esc_html(isset($obdm_urgency_labels[$obdm_req->urgency]) ? $obdm_urgency_labels[$obdm_req->urgency] : $obdm_req->urgency); ?></span>
                            <span class="obdm-status-badge obdm-status-<?php echo esc_attr($obdm_req->status); ?>"><?php echo esc_html(isset($obdm_status_labels[$obdm_req->status]) ? $obdm_status_labels[$obdm_req->status] : $obdm_req->status); ?></span>
                        </div>
                    </div>
                    <div class="obdm-card-body">
                        <div class="obdm-request-blood-needed">
                            <span class="obdm-blood-type-large obdm-blood-<?php echo esc_attr(strtolower(str_replace('+', 'pos', str_replace('-', 'neg', $obdm_req->blood_type_needed)))); ?>">
                                <?php echo esc_html($obdm_req->blood_type_needed); ?>
                            </span>
                            <span class="obdm-units-needed"><?php echo esc_html(sprintf(
                                /* translators: %d: number of required blood units. */
                                _n('%d unit', '%d units', (int) $obdm_req->units_needed, 'obydullah-blood-bank-manager'),
                                (int) $obdm_req->units_needed
                            )); ?></span>
                        </div>
                        <h3><?php echo esc_html($obdm_req->patient_name); ?></h3>
                        <div class="obdm-request-info">
                            <span class="obdm-info-item">
                                <span class="dashicons dashicons-building"></span>
                                <?php echo esc_html($obdm_req->hospital_name); ?>
                            </span>
                            <?php if ($obdm_req->city): ?>
                                <span class="obdm-info-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($obdm_req->city); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($obdm_req->needed_by): ?>
                                <span class="obdm-info-item">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php echo esc_html(sprintf(
                                    /* translators: %s: date the blood is needed by. */
                                    __('Needed by: %s', 'obydullah-blood-bank-manager'),
                                    date_i18n(get_option('date_format'), strtotime($obdm_req->needed_by))
                                )); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($obdm_req->status === 'pending'): ?>
                            <div class="obdm-request-contact">
                                <a href="mailto:<?php echo esc_attr($obdm_req->requester_email); ?>" class="obdm-btn obdm-btn-sm">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php esc_html_e('Contact', 'obydullah-blood-bank-manager'); ?>
                                </a>
                                <a href="tel:<?php echo esc_attr($obdm_req->requester_phone); ?>" class="obdm-btn obdm-btn-sm">
                                    <span class="dashicons dashicons-phone"></span>
                                    <?php esc_html_e('Call', 'obydullah-blood-bank-manager'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
