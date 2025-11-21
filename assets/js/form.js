jQuery(document).ready(function($) {
    // Package card selection
    $('.pbqr-card-radio').change(function() {
        $('.pbqr-card').removeClass('pbqr-card--active');
        $(this).parent('label').addClass('pbqr-card--active');
    });

    // Extra checkbox toggle
    $('.pbqr-extra-checkbox').change(function() {
        $(this).closest('.pbqr-extra-card').toggleClass('pbqr-extra-card--checked');
    });

    // Form validation
    $('.pbqr-wrapper').on('submit', function(e) {
        var packageSelected = $('input[name="package_id"]:checked').length > 0;
        var emailValid = $('input[name="customer_email"]').val().length > 0;
        var nameValid = $('input[name="customer_name"]').val().length > 0;
        var phoneValid = $('input[name="customer_phone"]').val().length > 0;
        var dateValid = $('input[name="event_date"]').val().length > 0;
        var locationValid = $('input[name="event_location"]').val().length > 0;
        var timeValid = $('input[name="event_time"]').val().length > 0;
        var hoursValid = $('input[name="event_hours"]').val().length > 0;

        if (!packageSelected) {
            alert('Please select a package.');
            e.preventDefault();
            return false;
        }

        if (!dateValid || !locationValid || !timeValid || !hoursValid) {
            alert('Please fill in all event details.');
            e.preventDefault();
            return false;
        }

        if (!nameValid || !emailValid || !phoneValid) {
            alert('Please fill in all contact details.');
            e.preventDefault();
            return false;
        }
    });
});
