jQuery(document).ready(function($) {
    // Package card selection
    $('.pbqr-card-radio').change(function() {
        $('.pbqr-card').removeClass('pbqr-card--active');
        $(this).closest('label').addClass('pbqr-card--active');
    });

    // Extra checkbox toggle
    $('.pbqr-extra-checkbox').change(function() {
        $(this).closest('.pbqr-extra-card').toggleClass('pbqr-extra-card--checked');
    });

    // Form validation
    $('.pbqr-wrapper').on('submit', function(e) {
        var packageSelected = $('input[name="package_id"]:checked').length > 0;
        var emailValid = $('input[name="customer_email"]').val().length > 0;
        var firstNameValid = $('input[name="customer_first_name"]').val().length > 0;
        var lastNameValid = $('input[name="customer_last_name"]').val().length > 0;
        var phoneValid = $('input[name="customer_phone"]').val().length > 0;
        var dateValid = $('input[name="event_date"]').val().length > 0;
        var locationValid = $('input[name="event_location"]').val().length > 0;
        var timeValid = $('input[name="event_time"]').val().length > 0;
        var hoursValid = $('input[name="event_hours"]').val().length > 0;
        var eventTypeValid = $('select[name="event_type"]').val().length > 0;
        var streetValid = $('input[name="customer_street"]').val().length > 0;
        var postalValid = $('input[name="customer_postal_code"]').val().length > 0;
        var cityValid = $('input[name="customer_city"]').val().length > 0;
        var countryValid = $('input[name="customer_country"]').val().length > 0;

        if (!packageSelected) {
            alert('Bitte wählen Sie ein Paket aus.');
            e.preventDefault();
            return false;
        }

        if (!eventTypeValid) {
            alert('Bitte wählen Sie einen Veranstaltungstyp aus.');
            e.preventDefault();
            return false;
        }

        if (!dateValid || !locationValid || !timeValid || !hoursValid) {
            alert('Bitte füllen Sie alle Veranstaltungsdetails aus.');
            e.preventDefault();
            return false;
        }

        if (!firstNameValid || !lastNameValid || !emailValid || !phoneValid) {
            alert('Bitte füllen Sie alle erforderlichen Kontaktdaten aus.');
            e.preventDefault();
            return false;
        }

        if (!streetValid || !postalValid || !cityValid || !countryValid) {
            alert('Bitte füllen Sie alle Adressinformationen aus.');
            e.preventDefault();
            return false;
        }
    });
});
