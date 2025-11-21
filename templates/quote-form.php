<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WooCommerce')) {
    echo '<p>WooCommerce is required for this form.</p>';
    return;
}

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
?>

<form method="POST" class="pbqr-wrapper">
    <?php wp_nonce_field('pbqr_form', 'pbqr_nonce'); ?>
    <input type="hidden" name="pbqr_submit" value="1">

    <!-- STEP 1: EVENT DETAILS -->
    <section class="pbqr-section pbqr-section-event">
        <h2 class="pbqr-section-title">1. Event Details</h2>

        <div class="pbqr-event-grid">
            <div class="pbqr-field">
                <label>Event Date *</label>
                <input type="date" name="event_date" required>
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
    </section>

    <!-- STEP 2: PACKAGE SELECTION -->
    <section class="pbqr-section pbqr-section-packages">
        <h2 class="pbqr-section-title">2. Select Your Package</h2>

        <?php if ($packages): ?>
            <div class="pbqr-card-grid">
                <?php foreach ($packages as $package): ?>
                    <?php
                        $pid        = $package->get_id();
                        $checked    = ($default_package_id && $default_package_id == $pid) ? 'checked' : '';
                        $is_checked = $checked ? ' pbqr-card--active' : '';
                    ?>
                    <label class="pbqr-card<?php echo $is_checked; ?>">
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
    </section>

    <!-- STEP 3: EXTRAS -->
    <section class="pbqr-section pbqr-section-extras">
        <h2 class="pbqr-section-title">3. Add-ons & Extras</h2>

        <?php if ($extras): ?>
            <div class="pbqr-extra-grid">
                <?php foreach ($extras as $extra): ?>
                    <label class="pbqr-extra-card">
                        <input type="checkbox"
                               name="extras[]"
                               value="<?php echo $extra->get_id(); ?>"
                               class="pbqr-extra-checkbox">
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
    </section>

    <!-- STEP 4: CONTACT DATA -->
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
    </section>

    <!-- STEP 5: SUBMIT -->
    <section class="pbqr-section pbqr-section-submit">
        <button type="submit" class="pbqr-btn-primary">
            📩 Request a Quote
        </button>
        <p class="pbqr-small">
            By clicking "Request a Quote", you submit a non-binding inquiry to us.
        </p>
    </section>
</form>
