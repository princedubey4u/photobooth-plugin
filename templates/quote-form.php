<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WooCommerce')) {
    echo '<p>WooCommerce wird für dieses Formular benötigt.</p>';
    return;
}

// Get blocked dates
global $wpdb;
$blocked_dates_table = $wpdb->prefix . 'pbqr_blocked_dates';
$blocked_dates = $wpdb->get_col("SELECT blocked_date FROM $blocked_dates_table WHERE blocked_date >= CURDATE()");
$blocked_dates_json = json_encode($blocked_dates);

// Packages with images
$packages = wc_get_products([
    'status'   => 'publish',
    'limit'    => -1,
    'category' => ['photobooth-packages'],
]);

// Extras with images
$extras = wc_get_products([
    'status'   => 'publish',
    'limit'    => -1,
    'category' => ['photobooth-extras'],
]);

$default_package_id = isset($GLOBALS['pbqr_default_package_id']) ? $GLOBALS['pbqr_default_package_id'] : 0;

// Success message
if (isset($_GET['pbqr_success']) && $_GET['pbqr_success'] == '1') {
    echo '<div class="pbqr-alert pbqr-alert-success">Vielen Dank! Ihre Angebotsanfrage wurde erfolgreich übermittelt.</div>';
}

// Get pre-selected package/extra from URL
$preselected_package = isset($_GET['package']) ? intval($_GET['package']) : $default_package_id;
$preselected_extra = isset($_GET['extra']) ? intval($_GET['extra']) : 0;
?>

<style>
.pbqr-step-wizard {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    position: relative;
}
.pbqr-step-wizard::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e0e0e0;
    z-index: 0;
}
.pbqr-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}
.pbqr-step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #666;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}
.pbqr-step.active .pbqr-step-circle {
    background: #ffb21a;
    color: #181920;
    box-shadow: 0 4px 12px rgba(255, 178, 26, 0.4);
}
.pbqr-step.completed .pbqr-step-circle {
    background: #4caf50;
    color: white;
}
.pbqr-step-label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 500;
}
.pbqr-step.active .pbqr-step-label {
    color: #333;
    font-weight: 600;
}
.pbqr-form-step {
    display: none;
}
.pbqr-form-step.active {
    display: block;
    animation: fadeIn 0.4s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.pbqr-step-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    gap: 1rem;
}
.pbqr-btn-secondary {
    background: #666;
    color: white;
    border: none;
    border-radius: 999px;
    padding: 0.9rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pbqr-btn-secondary:hover {
    background: #555;
    transform: translateY(-2px);
}
.pbqr-progress-bar {
    height: 4px;
    background: #e0e0e0;
    border-radius: 999px;
    margin-bottom: 2rem;
    overflow: hidden;
}
.pbqr-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ffb21a, #ffa500);
    transition: width 0.4s ease;
    border-radius: 999px;
}
.pbqr-date-blocked {
    background: #ffebee !important;
    color: #c62828 !important;
    cursor: not-allowed !important;
}
.pbqr-product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 1rem;
}
.pbqr-card-description {
    font-size: 0.9rem;
    color: #666;
    margin: 0.8rem 0;
    line-height: 1.5;
}
</style>

<form method="POST" class="pbqr-wrapper" id="pbqrForm">
    <?php wp_nonce_field('pbqr_form', 'pbqr_nonce'); ?>
    <input type="hidden" name="pbqr_submit" value="1">

    <!-- Progress Bar -->
    <div class="pbqr-progress-bar">
        <div class="pbqr-progress-fill" id="progressFill" style="width: 20%;"></div>
    </div>

    <!-- Step Wizard -->
    <div class="pbqr-step-wizard">
        <div class="pbqr-step active" data-step="1">
            <div class="pbqr-step-circle">1</div>
            <div class="pbqr-step-label">Veranstaltung</div>
        </div>
        <div class="pbqr-step" data-step="2">
            <div class="pbqr-step-circle">2</div>
            <div class="pbqr-step-label">Paket</div>
        </div>
        <div class="pbqr-step" data-step="3">
            <div class="pbqr-step-circle">3</div>
            <div class="pbqr-step-label">Extras</div>
        </div>
        <div class="pbqr-step" data-step="4">
            <div class="pbqr-step-circle">4</div>
            <div class="pbqr-step-label">Kontakt</div>
        </div>
        <div class="pbqr-step" data-step="5">
            <div class="pbqr-step-circle">5</div>
            <div class="pbqr-step-label">Überprüfung</div>
        </div>
    </div>

    <!-- STEP 1: EVENT DETAILS -->
    <div class="pbqr-form-step active" data-step="1">
        <section class="pbqr-section pbqr-section-event">
            <h2 class="pbqr-section-title">1. Veranstaltungsdetails</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Bitte geben Sie alle Details zu Ihrer Veranstaltung an.</p>

            <div class="pbqr-event-grid">
                <div class="pbqr-field">
                    <label>Art der Veranstaltung *</label>
                    <select name="event_type" required style="width: 100%; border-radius: 10px; border: 1px solid #ddd; background: #ffffff; padding: 0.6rem 0.75rem; color: #333333; font-size: 0.95rem;">
                        <option value="">Bitte wählen...</option>
                        <option value="Hochzeit">Hochzeit</option>
                        <option value="Geburtstag">Geburtstag</option>
                        <option value="Firmenveranstaltung">Firmenveranstaltung</option>
                        <option value="Messe">Messe</option>
                        <option value="Sonstiges">Sonstiges</option>
                    </select>
                </div>
                <div class="pbqr-field">
                    <label>Datum der Veranstaltung *</label>
                    <input type="date" name="event_date" id="event_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="pbqr-field">
                    <label>Startzeit *</label>
                    <input type="time" name="event_time" required>
                </div>
                <div class="pbqr-field">
                    <label>Anzahl der Stunden *</label>
                    <input type="number" name="event_hours" min="1" max="24" required>
                </div>
                <div class="pbqr-field" style="grid-column: 1 / -1;">
                    <label>Veranstaltungsort *</label>
                    <input type="text" name="event_location" placeholder="z.B. Berlin, Halle A" required>
                </div>
            </div>

            <div class="pbqr-availability-hint">
                🎉 Sobald Sie ein Datum auswählen, prüfen wir die Verfügbarkeit Ihrer bevorzugten Fotobox.
            </div>

            <div class="pbqr-step-buttons">
                <div></div>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Nächster Schritt →</button>
            </div>
        </section>
    </div>

    <!-- STEP 2: PACKAGE SELECTION -->
    <div class="pbqr-form-step" data-step="2">
        <section class="pbqr-section pbqr-section-packages">
            <h2 class="pbqr-section-title">2. Wählen Sie Ihr Paket</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Wählen Sie das Paket aus, das am besten zu Ihrer Veranstaltung passt.</p>

            <?php if ($packages): ?>
                <div class="pbqr-card-grid">
                    <?php foreach ($packages as $package): ?>
                        <?php
                            $pid = $package->get_id();
                            $checked = ($preselected_package && $preselected_package == $pid) ? 'checked' : '';
                            $is_checked = $checked ? ' pbqr-card--active' : '';
                            $image_id = $package->get_image_id();
                            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                        ?>
                        <label class="pbqr-card<?php echo $is_checked; ?>" data-package-id="<?php echo esc_attr($pid); ?>">
                            <input type="radio"
                                   name="package_id"
                                   value="<?php echo esc_attr($pid); ?>"
                                   class="pbqr-card-radio"
                                   required
                                   <?php echo $checked; ?>>
                            
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($package->get_name()); ?>" class="pbqr-product-image">
                            <?php endif; ?>
                            
                            <div class="pbqr-card-header">
                                <span class="pbqr-card-title">
                                    <?php echo esc_html($package->get_name()); ?>
                                </span>
                                <span class="pbqr-card-price">
                                    <?php echo wc_price($package->get_price()); ?>
                                </span>
                            </div>

                            <div class="pbqr-card-body">
                                <div class="pbqr-card-description">
                                    <?php
                                    $description = $package->get_description();
                                    if ($description) {
                                        echo wp_kses_post(wpautop($description));
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="pbqr-card-footer">
                                <span class="pbqr-card-cta">Auswählen</span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Keine Fotobox-Pakete gefunden.</p>
            <?php endif; ?>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Zurück</button>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Nächster Schritt →</button>
            </div>
        </section>
    </div>

    <!-- STEP 3: EXTRAS -->
    <div class="pbqr-form-step" data-step="3">
        <section class="pbqr-section pbqr-section-extras">
            <h2 class="pbqr-section-title">3. Extras & Zusatzleistungen</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Wählen Sie zusätzliche Optionen, um Ihre Veranstaltung noch besonderer zu machen.</p>

            <?php if ($extras): ?>
                <div class="pbqr-extra-grid">
                    <?php foreach ($extras as $extra): ?>
                        <?php
                            $eid = $extra->get_id();
                            $checked = ($preselected_extra && $preselected_extra == $eid) ? 'checked' : '';
                            $image_id = $extra->get_image_id();
                            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                        ?>
                        <label class="pbqr-extra-card" data-extra-id="<?php echo esc_attr($eid); ?>">
                            <input type="checkbox"
                                   name="extras[]"
                                   value="<?php echo $eid; ?>"
                                   class="pbqr-extra-checkbox"
                                   <?php echo $checked; ?>>
                            <div class="pbqr-extra-image">
                                <?php if ($image_url): ?>
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($extra->get_name()); ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 120px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #999;">Kein Bild</div>
                                <?php endif; ?>
                            </div>
                            <div class="pbqr-extra-content">
                                <div class="pbqr-extra-title-row">
                                    <span class="pbqr-extra-title">
                                        <?php echo esc_html($extra->get_name()); ?>
                                    </span>
                                    <span class="pbqr-extra-price">
                                        <?php echo wc_price($extra->get_price()); ?>
                                    </span>
                                </div>
                                <div class="pbqr-extra-desc">
                                    <?php
                                    $description = $extra->get_description();
                                    if ($description) {
                                        echo wp_kses_post(wpautop($description));
                                    }
                                    ?>
                                </div>
                                <div class="pbqr-extra-cta">
                                    <span class="pbqr-extra-toggle-label">Hinzufügen</span>
                                    <span class="pbqr-extra-toggle"></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Keine Extras verfügbar.</p>
            <?php endif; ?>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Zurück</button>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Nächster Schritt →</button>
            </div>
        </section>
    </div>

    <!-- STEP 4: CONTACT DATA -->
    <div class="pbqr-form-step" data-step="4">
        <section class="pbqr-section pbqr-section-contact">
            <h2 class="pbqr-section-title">4. Ihre Kontaktinformationen</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Bitte füllen Sie alle Kontaktdaten aus, damit wir Ihnen ein Angebot erstellen können.</p>

            <div class="pbqr-contact-grid">
                <div class="pbqr-field">
                    <label>Vorname *</label>
                    <input type="text" name="customer_first_name" required>
                </div>
                <div class="pbqr-field">
                    <label>Nachname *</label>
                    <input type="text" name="customer_last_name" required>
                </div>
                <div class="pbqr-field">
                    <label>Firma (optional)</label>
                    <input type="text" name="customer_company">
                </div>
                <div class="pbqr-field">
                    <label>E-Mail-Adresse *</label>
                    <input type="email" name="customer_email" required>
                </div>
                <div class="pbqr-field">
                    <label>Telefonnummer *</label>
                    <input type="text" name="customer_phone" required>
                </div>
                <div class="pbqr-field">
                    <label>Straße & Hausnummer *</label>
                    <input type="text" name="customer_street" required>
                </div>
                <div class="pbqr-field">
                    <label>Postleitzahl *</label>
                    <input type="text" name="customer_postal_code" required>
                </div>
                <div class="pbqr-field">
                    <label>Stadt *</label>
                    <input type="text" name="customer_city" required>
                </div>
                <div class="pbqr-field" style="grid-column: 1 / -1;">
                    <label>Land *</label>
                    <input type="text" name="customer_country" value="Deutschland" required>
                </div>
            </div>

            <div class="pbqr-field" style="margin-top: 1rem;">
                <label>Zusätzliche Informationen / Besondere Wünsche (optional)</label>
                <textarea name="message" rows="4"
                          placeholder="z.B. bevorzugtes Layout, spezielle Wünsche, etc."></textarea>
            </div>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Zurück</button>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Überprüfen →</button>
            </div>
        </section>
    </div>

    <!-- STEP 5: REVIEW -->
    <div class="pbqr-form-step" data-step="5">
        <section class="pbqr-section pbqr-section-review">
            <h2 class="pbqr-section-title">5. Überprüfen Sie Ihre Anfrage</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Bitte überprüfen Sie alle Angaben, bevor Sie die Anfrage absenden.</p>

            <div id="reviewContent"></div>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Zurück</button>
                <button type="submit" class="pbqr-btn-primary">
                    📩 Angebotsanfrage absenden
                </button>
            </div>
            
            <p class="pbqr-small">
                Mit dem Klick auf "Angebotsanfrage absenden" senden Sie uns eine unverbindliche Anfrage.
            </p>
        </section>
    </div>
</form>

<script>
jQuery(document).ready(function($) {
    var currentStep = 1;
    var totalSteps = 5;
    var blockedDates = <?php echo $blocked_dates_json; ?>;

    // Update URL without reloading page
    function updateURL() {
        var params = new URLSearchParams(window.location.search);
        
        var selectedPackage = $('input[name="package_id"]:checked').val();
        if (selectedPackage) {
            params.set('package', selectedPackage);
        } else {
            params.delete('package');
        }
        
        var selectedExtras = [];
        $('input[name="extras[]"]:checked').each(function() {
            selectedExtras.push($(this).val());
        });
        if (selectedExtras.length > 0) {
            params.set('extra', selectedExtras.join(','));
        } else {
            params.delete('extra');
        }
        
        var newURL = window.location.pathname + '?' + params.toString();
        window.history.pushState({}, '', newURL);
    }

    $('#event_date').on('change', function() {
        var selectedDate = $(this).val();
        if (blockedDates.includes(selectedDate)) {
            alert('Dieses Datum ist nicht verfügbar. Bitte wählen Sie ein anderes Datum.');
            $(this).val('');
        }
    });

    $('.pbqr-card-radio').on('change', function() {
        $('.pbqr-card').removeClass('pbqr-card--active');
        $(this).closest('.pbqr-card').addClass('pbqr-card--active');
        updateURL();
    });

    $('.pbqr-extra-checkbox').on('change', function() {
        updateURL();
    });

    function showStep(step) {
        currentStep = step;
        
        $('.pbqr-form-step').removeClass('active');
        $('.pbqr-form-step[data-step="' + step + '"]').addClass('active');
        
        $('.pbqr-step').removeClass('active completed');
        $('.pbqr-step[data-step="' + step + '"]').addClass('active');
        $('.pbqr-step').each(function() {
            if ($(this).data('step') < step) {
                $(this).addClass('completed');
            }
        });
        
        var progress = (step / totalSteps) * 100;
        $('#progressFill').css('width', progress + '%');
        
        $('html, body').animate({
            scrollTop: $('.pbqr-wrapper').offset().top - 100
        }, 300);

        if (step === 5) {
            populateReview();
        }
    }

    function populateReview() {
        var html = '<div style="background: #fafafa; padding: 1.5rem; border-radius: 12px;">';
        
        html += '<h3 style="margin-top: 0; color: #333;">Veranstaltungsdetails</h3>';
        html += '<p><strong>Art:</strong> ' + $('select[name="event_type"]').val() + '</p>';
        html += '<p><strong>Datum:</strong> ' + $('input[name="event_date"]').val() + '</p>';
        html += '<p><strong>Ort:</strong> ' + $('input[name="event_location"]').val() + '</p>';
        html += '<p><strong>Zeit:</strong> ' + $('input[name="event_time"]').val() + '</p>';
        html += '<p><strong>Dauer:</strong> ' + $('input[name="event_hours"]').val() + ' Stunden</p>';
        
        html += '<h3 style="color: #333; margin-top: 1.5rem;">Gewähltes Paket</h3>';
        var selectedPackage = $('input[name="package_id"]:checked');
        if (selectedPackage.length) {
            var packageCard = selectedPackage.closest('.pbqr-card');
            html += '<p><strong>' + packageCard.find('.pbqr-card-title').text() + '</strong> - ';
            html += packageCard.find('.pbqr-card-price').text() + '</p>';
        }
        
        var selectedExtras = $('input[name="extras[]"]:checked');
        if (selectedExtras.length) {
            html += '<h3 style="color: #333; margin-top: 1.5rem;">Gewählte Extras</h3>';
            html += '<ul style="margin: 0; padding-left: 1.2rem;">';
            selectedExtras.each(function() {
                var extraCard = $(this).closest('.pbqr-extra-card');
                html += '<li>' + extraCard.find('.pbqr-extra-title').text() + ' - ';
                html += extraCard.find('.pbqr-extra-price').text() + '</li>';
            });
            html += '</ul>';
        } else {
            html += '<h3 style="color: #333; margin-top: 1.5rem;">Gewählte Extras</h3>';
            html += '<p>Keine</p>';
        }
        
        html += '<h3 style="color: #333; margin-top: 1.5rem;">Kontaktinformationen</h3>';
        html += '<p><strong>Name:</strong> ' + $('input[name="customer_first_name"]').val() + ' ' + $('input[name="customer_last_name"]').val() + '</p>';
        
        var company = $('input[name="customer_company"]').val();
        if (company) {
            html += '<p><strong>Firma:</strong> ' + company + '</p>';
        }
        
        html += '<p><strong>E-Mail:</strong> ' + $('input[name="customer_email"]').val() + '</p>';
        html += '<p><strong>Telefon:</strong> ' + $('input[name="customer_phone"]').val() + '</p>';
        html += '<p><strong>Adresse:</strong> ' + $('input[name="customer_street"]').val() + ', ';
        html += $('input[name="customer_postal_code"]').val() + ' ' + $('input[name="customer_city"]').val() + ', ';
        html += $('input[name="customer_country"]').val() + '</p>';
        
        var message = $('textarea[name="message"]').val();
        if (message) {
            html += '<p><strong>Nachricht:</strong><br>' + message + '</p>';
        }
        
        html += '</div>';
        
        $('#reviewContent').html(html);
    }

    function validateStep(step) {
        var isValid = true;
        var currentStepDiv = $('.pbqr-form-step[data-step="' + step + '"]');
        
        currentStepDiv.find('input[required], textarea[required], select[required]').each(function() {
            if ($(this).attr('type') === 'radio') {
                var name = $(this).attr('name');
                if (!$('input[name="' + name + '"]:checked').length) {
                    isValid = false;
                    alert('Bitte wählen Sie ein Paket aus.');
                    return false;
                }
            } else if (!$(this).val()) {
                isValid = false;
                $(this).focus();
                alert('Bitte füllen Sie alle Pflichtfelder aus.');
                return false;
            }
        });
        
        return isValid;
    }

    $('.pbqr-next-btn').on('click', function() {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
    });

    $('.pbqr-prev-btn').on('click', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    $('#pbqrForm').on('submit', function(e) {
        if (!validateStep(currentStep)) {
            e.preventDefault();
            return false;
        }
    });

});
</script>
