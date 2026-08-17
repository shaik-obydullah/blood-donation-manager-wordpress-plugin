jQuery(document).ready(function($) {
    // Donor Registration Form
    $('#bdm-donor-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#bdm-donor-submit');
        var $message = $('#bdm-donor-message');
        
        $submitBtn.find('.bdm-btn-text').hide();
        $submitBtn.find('.bdm-btn-loading').show();
        $submitBtn.prop('disabled', true);
        
        var formData = {
            action: 'bdm_register_donor',
            nonce: bdmFrontend.nonce,
            first_name: $form.find('[name="first_name"]').val(),
            last_name: $form.find('[name="last_name"]').val(),
            email: $form.find('[name="email"]').val(),
            phone: $form.find('[name="phone"]').val(),
            blood_type: $form.find('[name="blood_type"]').val(),
            date_of_birth: $form.find('[name="date_of_birth"]').val(),
            gender: $form.find('[name="gender"]').val(),
            weight: $form.find('[name="weight"]').val(),
            address: $form.find('[name="address"]').val(),
            city: $form.find('[name="city"]').val(),
            state: $form.find('[name="state"]').val(),
            zip_code: $form.find('[name="zip_code"]').val(),
            country: $form.find('[name="country"]').val(),
            last_donation_date: $form.find('[name="last_donation_date"]').val(),
            medical_conditions: $form.find('[name="medical_conditions"]').val(),
            is_available: $form.find('[name="is_available"]').is(':checked') ? 1 : 0
        };
        
        $.post(bdmFrontend.ajax_url, formData, function(response) {
            $submitBtn.find('.bdm-btn-text').show();
            $submitBtn.find('.bdm-btn-loading').hide();
            $submitBtn.prop('disabled', false);
            
            if (response.success) {
                $message.removeClass('bdm-message-error').addClass('bdm-message-success').text(response.data.message).show();
                $form[0].reset();
            } else {
                $message.removeClass('bdm-message-success').addClass('bdm-message-error').text(response.data.message).show();
            }
        }).fail(function() {
            $submitBtn.find('.bdm-btn-text').show();
            $submitBtn.find('.bdm-btn-loading').hide();
            $submitBtn.prop('disabled', false);
            $message.removeClass('bdm-message-success').addClass('bdm-message-error').text('An error occurred. Please try again.').show();
        });
    });
    
    // Donation Request Form
    $('#bdm-request-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#bdm-request-submit');
        var $message = $('#bdm-request-message');
        
        $submitBtn.find('.bdm-btn-text').hide();
        $submitBtn.find('.bdm-btn-loading').show();
        $submitBtn.prop('disabled', true);
        
        var formData = {
            action: 'bdm_submit_request',
            nonce: bdmFrontend.nonce,
            requester_name: $form.find('[name="requester_name"]').val(),
            requester_email: $form.find('[name="requester_email"]').val(),
            requester_phone: $form.find('[name="requester_phone"]').val(),
            patient_name: $form.find('[name="patient_name"]').val(),
            blood_type_needed: $form.find('[name="blood_type_needed"]').val(),
            units_needed: $form.find('[name="units_needed"]').val(),
            hospital_name: $form.find('[name="hospital_name"]').val(),
            hospital_address: $form.find('[name="hospital_address"]').val(),
            city: $form.find('[name="city"]').val(),
            urgency: $form.find('[name="urgency"]').val(),
            needed_by: $form.find('[name="needed_by"]').val(),
            additional_info: $form.find('[name="additional_info"]').val()
        };
        
        $.post(bdmFrontend.ajax_url, formData, function(response) {
            $submitBtn.find('.bdm-btn-text').show();
            $submitBtn.find('.bdm-btn-loading').hide();
            $submitBtn.prop('disabled', false);
            
            if (response.success) {
                $message.removeClass('bdm-message-error').addClass('bdm-message-success').text(response.data.message).show();
                $form[0].reset();
            } else {
                $message.removeClass('bdm-message-success').addClass('bdm-message-error').text(response.data.message).show();
            }
        }).fail(function() {
            $submitBtn.find('.bdm-btn-text').show();
            $submitBtn.find('.bdm-btn-loading').hide();
            $submitBtn.prop('disabled', false);
            $message.removeClass('bdm-message-success').addClass('bdm-message-error').text('An error occurred. Please try again.').show();
        });
    });
});
