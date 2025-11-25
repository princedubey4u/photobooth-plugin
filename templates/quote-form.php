<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WooCommerce')) {
    echo '<p>WooCommerce is required for this form.</p>';
    return;
}

// Get blocked dates
global $wpdb;
$blocked_dates_table = $wpdb->prefix . 'pbqr_blocked_dates';
$blocked_dates = $wpdb->get_col("SELECT blocked_date FROM $blocked_dates_table WHERE blocked_date >= CURDATE()");
$blocked_dates_json = json_encode($blocked_dates);

// Packages
$packages = wc_get_products([
    'status'   => 'publish',
    'limit'    => -1,
    'category' => ['photobooth-packages'],
]);

// Extras
$extras = wc_get_products([
    'status'   => 'publish',
    'limit'    => -1,
    'category' => ['photobooth-extras'],
]);

$default_package_id = isset($GLOBALS['pbqr_default_package_id']) ? $GLOBALS['pbqr_default_package_id'] : 0;

// Success message
if (isset($_GET['pbqr_success']) && $_GET['pbqr_success'] == '1') {
    echo '<div class="pbqr-alert pbqr-alert-success">Thank you! Your quote request has been submitted successfully.</div>';
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
            <div class="pbqr-step-label">Event Details</div>
        </div>
        <div class="pbqr-step" data-step="2">
            <div class="pbqr-step-circle">2</div>
            <div class="pbqr-step-label">Package</div>
        </div>
        <div class="pbqr-step" data-step="3">
            <div class="pbqr-step-circle">3</div>
            <div class="pbqr-step-label">Add-ons</div>
        </div>
        <div class="pbqr-step" data-step="4">
            <div class="pbqr-step-circle">4</div>
            <div class="pbqr-step-label">Contact Info</div>
        </div>
        <div class="pbqr-step" data-step="5">
            <div class="pbqr-step-circle">5</div>
            <div class="pbqr-step-label">Review</div>
        </div>
    </div>

    <!-- STEP 1: EVENT DETAILS -->
    <div class="pbqr-form-step active" data-step="1">
        <section class="pbqr-section pbqr-section-event">
            <h2 class="pbqr-section-title">1. Event Details</h2>

            <div class="pbqr-event-grid">
                <div class="pbqr-field">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" id="event_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="pbqr-field">
                    <label>Event Location *</label>
                    <input type="text" name="event_location" placeholder="e.g., New York, Hall A" required>
                </div>
                <div class="pbqr-field">
                    <label>Start Time *</label>
                    <input type="time" name="event_time" required>
                </div>
                <div class="pbqr-field">
                    <label>Number of Hours *</label>
                    <input type="number" name="event_hours" min="1" max="24" required>
                </div>
            </div>

            <div class="pbqr-availability-hint">
                🎉 Once you select a date, we'll check the availability of your preferred photobooth.
            </div>

            <div class="pbqr-step-buttons">
                <div></div>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Next Step →</button>
            </div>
        </section>
    </div>

    <!-- STEP 2: PACKAGE SELECTION -->
    <div class="pbqr-form-step" data-step="2">
        <section class="pbqr-section pbqr-section-packages">
            <h2 class="pbqr-section-title">2. Select Your Package</h2>

            <?php if ($packages): ?>
                <div class="pbqr-card-grid">
                    <?php foreach ($packages as $package): ?>
                        <?php
                            $pid = $package->get_id();
                            $checked = ($preselected_package && $preselected_package == $pid) ? 'checked' : '';
                            $is_checked = $checked ? ' pbqr-card--active' : '';
                        ?>
                        <label class="pbqr-card<?php echo $is_checked; ?>" data-package-id="<?php echo esc_attr($pid); ?>">
                            <input type="radio"
                                   name="package_id"
                                   value="<?php echo esc_attr($pid); ?>"
                                   class="pbqr-card-radio"
                                   required
                                   <?php echo $checked; ?>>
                            <div class="pbqr-card-header">
                                <span class="pbqr-card-title">
                                    <?php echo esc_html($package->get_name()); ?>
                                </span>
                                <span class="pbqr-card-price">
                                    <?php echo wc_price($package->get_price()); ?>
                                </span>
                            </div>

                            <div class="pbqr-card-body">
                                <?php
                                $short_desc = $package->get_short_description();
                                if ($short_desc) {
                                    echo wp_kses_post(wpautop($short_desc));
                                }
                                ?>
                            </div>

                            <div class="pbqr-card-footer">
                                <span class="pbqr-card-cta">Choose</span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No photobooth packages found.</p>
            <?php endif; ?>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Previous</button>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Next Step →</button>
            </div>
        </section>
    </div>

    <!-- STEP 3: EXTRAS -->
    <div class="pbqr-form-step" data-step="3">
        <section class="pbqr-section pbqr-section-extras">
            <h2 class="pbqr-section-title">3. Add-ons & Extras</h2>

            <?php if ($extras): ?>
                <div class="pbqr-extra-grid">
                    <?php foreach ($extras as $extra): ?>
                        <?php
                            $eid = $extra->get_id();
                            $checked = ($preselected_extra && $preselected_extra == $eid) ? 'checked' : '';
                        ?>
                        <label class="pbqr-extra-card" data-extra-id="<?php echo esc_attr($eid); ?>">
                            <input type="checkbox"
                                   name="extras[]"
                                   value="<?php echo $eid; ?>"
                                   class="pbqr-extra-checkbox"
                                   <?php echo $checked; ?>>
                            <div class="pbqr-extra-image">
                                <?php echo $extra->get_image('medium'); ?>
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
                                    $short = $extra->get_short_description();
                                    if ($short) {
                                        echo wp_kses_post(wpautop($short));
                                    }
                                    ?>
                                </div>
                                <div class="pbqr-extra-cta">
                                    <span class="pbqr-extra-toggle-label">Add</span>
                                    <span class="pbqr-extra-toggle"></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No add-ons available.</p>
            <?php endif; ?>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Previous</button>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Next Step →</button>
            </div>
        </section>
    </div>

    <!-- STEP 4: CONTACT DATA -->
    <div class="pbqr-form-step" data-step="4">
        <section class="pbqr-section pbqr-section-contact">
            <h2 class="pbqr-section-title">4. Your Contact Information</h2>

            <div class="pbqr-contact-grid">
                <div class="pbqr-field">
                    <label>Email Address *</label>
                    <input type="email" name="customer_email" required>
                </div>
                <div class="pbqr-field">
                    <label>Phone Number *</label>
                    <input type="text" name="customer_phone" required>
                </div>
                <div class="pbqr-field">
                    <label>First Name *</label>
                    <input type="text" name="customer_name" required>
                </div>
                <div class="pbqr-field">
                    <label>Company (optional)</label>
                    <input type="text" name="company">
                </div>
            </div>

            <div class="pbqr-field">
                <label>Additional Information / Special Requests (optional)</label>
                <textarea name="message" rows="4"
                          placeholder="e.g., preferred layout, special wishes, etc."></textarea>
            </div>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Previous</button>
                <button type="button" class="pbqr-btn-primary pbqr-next-btn">Review →</button>
            </div>
        </section>
    </div>

    <!-- STEP 5: REVIEW -->
    <div class="pbqr-form-step" data-step="5">
        <section class="pbqr-section pbqr-section-review">
            <h2 class="pbqr-section-title">5. Review Your Request</h2>

            <div id="reviewContent"></div>

            <div class="pbqr-step-buttons">
                <button type="button" class="pbqr-btn-secondary pbqr-prev-btn">← Previous</button>
                <button type="submit" class="pbqr-btn-primary">
                    📩 Submit Quote Request
                </button>
            </div>
            
            <p class="pbqr-small">
                By clicking "Submit Quote Request", you submit a non-binding inquiry to us.
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
        
        // Get selected package
        var selectedPackage = $('input[name="package_id"]:checked').val();
        if (selectedPackage) {
            params.set('package', selectedPackage);
        } else {
            params.delete('package');
        }
        
        // Get selected extras
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

    // Block dates in date picker
    $('#event_date').on('change', function() {
        var selectedDate = $(this).val();
        if (blockedDates.includes(selectedDate)) {
            alert('This date is not available. Please select another date.');
            $(this).val('');
        }
    });

    // Package selection - update URL
    $('.pbqr-card-radio').on('change', function() {
        $('.pbqr-card').removeClass('pbqr-card--active');
        $(this).closest('.pbqr-card').addClass('pbqr-card--active');
        updateURL();
    });

    // Extra selection - update URL
    $('.pbqr-extra-checkbox').on('change', function() {
        updateURL();
    });

    // Step navigation
    function showStep(step) {
        currentStep = step;
        
        // Hide all steps
        $('.pbqr-form-step').removeClass('active');
        
        // Show current step
        $('.pbqr-form-step[data-step="' + step + '"]').addClass('active');
        
        // Update wizard
        $('.pbqr-step').removeClass('active completed');
        $('.pbqr-step[data-step="' + step + '"]').addClass('active');
        $('.pbqr-step').each(function() {
            if ($(this).data('step') < step) {
                $(this).addClass('completed');
            }
        });
        
        // Update progress bar
        var progress = (step / totalSteps) * 100;
        $('#progressFill').css('width', progress + '%');
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: $('.pbqr-wrapper').offset().top - 100
        }, 300);

        // If step 5 (review), populate review content
        if (step === 5) {
            populateReview();
        }
    }

    function populateReview() {
        var html = '<div style="background: #fafafa; padding: 1.5rem; border-radius: 12px;">';
        
        // Event Details
        html += '<h3 style="margin-top: 0; color: #333;">Event Details</h3>';
        html += '<p><strong>Date:</strong> ' + $('input[name="event_date"]').val() + '</p>';
        html += '<p><strong>Location:</strong> ' + $('input[name="event_location"]').val() + '</p>';
        html += '<p><strong>Time:</strong> ' + $('input[name="event_time"]').val() + '</p>';
        html += '<p><strong>Duration:</strong> ' + $('input[name="event_hours"]').val() + ' hours</p>';
        
        // Package
        html += '<h3 style="color: #333; margin-top: 1.5rem;">Selected Package</h3>';
        var selectedPackage = $('input[name="package_id"]:checked');
        if (selectedPackage.length) {
            var packageCard = selectedPackage.closest('.pbqr-card');
            html += '<p><strong>' + packageCard.find('.pbqr-card-title').text() + '</strong> - ';
            html += packageCard.find('.pbqr-card-price').text() + '</p>';
        }
        
        // Extras
        var selectedExtras = $('input[name="extras[]"]:checked');
        if (selectedExtras.length) {
            html += '<h3 style="color: #333; margin-top: 1.5rem;">Selected Add-ons</h3>';
            html += '<ul style="margin: 0; padding-left: 1.2rem;">';
            selectedExtras.each(function() {
                var extraCard = $(this).closest('.pbqr-extra-card');
                html += '<li>' + extraCard.find('.pbqr-extra-title').text() + ' - ';
                html += extraCard.find('.pbqr-extra-price').text() + '</li>';
            });
            html += '</ul>';
        } else {
            html += '<h3 style="color: #333; margin-top: 1.5rem;">Selected Add-ons</h3>';
            html += '<p>None</p>';
        }
        
        // Contact Info
        html += '<h3 style="color: #333; margin-top: 1.5rem;">Contact Information</h3>';
        html += '<p><strong>Name:</strong> ' + $('input[name="customer_name"]').val() + '</p>';
        html += '<p><strong>Email:</strong> ' + $('input[name="customer_email"]').val() + '</p>';
        html += '<p><strong>Phone:</strong> ' + $('input[name="customer_phone"]').val() + '</p>';
        
        var company = $('input[name="company"]').val();
        if (company) {
            html += '<p><strong>Company:</strong> ' + company + '</p>';
        }
        
        var message = $('textarea[name="message"]').val();
        if (message) {
            html += '<p><strong>Message:</strong><br>' + message + '</p>';
        }
        
        html += '</div>';
        
        $('#reviewContent').html(html);
    }

    // Validate current step
    function validateStep(step) {
        var isValid = true;
        var currentStepDiv = $('.pbqr-form-step[data-step="' + step + '"]');
        
        currentStepDiv.find('input[required], textarea[required], select[required]').each(function() {
            if ($(this).attr('type') === 'radio') {
                var name = $(this).attr('name');
                if (!$('input[name="' + name + '"]:checked').length) {
                    isValid = false;
                    alert('Please select a package.');
                    return false;
                }
            } else if (!$(this).val()) {
                isValid = false;
                $(this).focus();
                alert('Please fill in all required fields.');
                return false;
            }
        });
        
        return isValid;
    }

    // Next button
    $('.pbqr-next-btn').on('click', function() {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
    });

    // Previous button
    $('.pbqr-prev-btn').on('click', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    // Form submission
    $('#pbqrForm').on('submit', function(e) {
        if (!validateStep(currentStep)) {
            e.preventDefault();
            return false;
        }
    });

});
</script>
