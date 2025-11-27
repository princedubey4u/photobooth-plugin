<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WooCommerce')) {
    echo '<p>WooCommerce wird für dieses Formular benötigt.</p>';
    return;
}

// Get blocked dates
global $wpdb;
$blocked_dates_table = $wpdb->prefix . 'pbqr_blocked_dates';
$blocked_dates_result = $wpdb->get_col("SELECT blocked_date FROM $blocked_dates_table");
$blocked_dates_json = json_encode($blocked_dates_result);

// Get all packages (products with category 'Photobooth Packages')
$packages = get_posts([
    'post_type' => 'product',
    'numberposts' => -1,
    'meta_key' => 'pbqr_package_type',
    'meta_value' => 'package',
]);

// Get all extras
$extras = get_posts([
    'post_type' => 'product',
    'numberposts' => -1,
    'meta_key' => 'pbqr_package_type',
    'meta_value' => 'extra',
]);

// Get selected default package if specified
$default_package_id = isset($GLOBALS['pbqr_default_package_id']) ? $GLOBALS['pbqr_default_package_id'] : 0;

// Check for form result messages
$success = isset($_GET['pbqr_success']) ? true : false;
$error = isset($_GET['pbqr_error']) ? true : false;
?>

<div class="pbqr-wrapper">
    <?php if ($success): ?>
        <div class="pbqr-alert pbqr-alert-success">
            ✓ Vielen Dank! Ihre Angebotsanfrage wurde erfolgreich eingereicht. Wir werden uns in Kürze mit Ihnen in Verbindung setzen.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="pbqr-alert" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
            ✗ Bitte füllen Sie alle erforderlichen Felder aus.
        </div>
    <?php endif; ?>

    <form method="POST" class="pbqr-form">
        <?php wp_nonce_field('pbqr_form', 'pbqr_nonce'); ?>
        <input type="hidden" name="pbqr_submit" value="1">

        <!-- PACKAGE SELECTION -->
        <div class="pbqr-section">
            <h2 class="pbqr-section-title">📦 Paketauswahl</h2>
            
            <?php if (empty($packages)): ?>
                <p>Keine Pakete verfügbar. Bitte kontaktieren Sie den Administrator.</p>
            <?php else: ?>
                <div class="pbqr-card-grid">
                    <?php foreach ($packages as $package): 
                        $product = wc_get_product($package->ID);
                        $image = wp_get_attachment_image_url($product->get_image_id(), 'medium');
                        $price = $product->get_price();
                        $description = $product->get_short_description() ?: $product->get_description();
                        $is_selected = ($default_package_id == $product->get_id()) ? 'checked' : '';
                    ?>
                        <label class="pbqr-card <?php echo $is_selected ? 'pbqr-card--active' : ''; ?>">
                            <input type="radio" 
                                   name="package_id" 
                                   value="<?php echo $product->get_id(); ?>" 
                                   class="pbqr-card-radio"
                                   <?php echo $is_selected; ?>>
                            
                            <?php if ($image): ?>
                                <div style="width: 100%; height: 180px; background: #f0f0f0; border-radius: 12px; overflow: hidden; margin-bottom: 12px;">
                                    <img src="<?php echo esc_url($image); ?>" 
                                         alt="<?php echo esc_attr($product->get_name()); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            
                            <div class="pbqr-card-header">
                                <div class="pbqr-card-title"><?php echo esc_html($product->get_name()); ?></div>
                                <div class="pbqr-card-price"><?php echo wc_price($price); ?></div>
                            </div>
                            
                            <?php if ($description): ?>
                                <div class="pbqr-card-body">
                                    <?php echo wp_kses_post(wpautop($description)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="pbqr-card-footer">
                                <span class="pbqr-card-cta">Auswählen</span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- EXTRAS SELECTION -->
        <?php if (!empty($extras)): ?>
            <div class="pbqr-section">
                <h2 class="pbqr-section-title">✨ Zusatzoptionen (Optional)</h2>
                <div class="pbqr-extra-grid">
                    <?php foreach ($extras as $extra): 
                        $product = wc_get_product($extra->ID);
                        $image = wp_get_attachment_image_url($product->get_image_id(), 'medium');
                        $price = $product->get_price();
                        $description = $product->get_short_description() ?: $product->get_description();
                    ?>
                        <label class="pbqr-extra-card">
                            <input type="checkbox" 
                                   name="extras[]" 
                                   value="<?php echo $product->get_id(); ?>"
                                   class="pbqr-extra-checkbox">
                            
                            <?php if ($image): ?>
                                <div class="pbqr-extra-image">
                                    <img src="<?php echo esc_url($image); ?>" 
                                         alt="<?php echo esc_attr($product->get_name()); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="pbqr-extra-content">
                                <div class="pbqr-extra-title-row">
                                    <div class="pbqr-extra-title"><?php echo esc_html($product->get_name()); ?></div>
                                    <div class="pbqr-extra-price">+<?php echo wc_price($price); ?></div>
                                </div>
                                
                                <?php if ($description): ?>
                                    <div class="pbqr-extra-desc"><?php echo wp_kses_post(wp_trim_words($description, 15)); ?></div>
                                <?php endif; ?>
                                
                                <div class="pbqr-extra-cta">
                                    <span class="pbqr-extra-toggle-label">Hinzufügen</span>
                                    <div class="pbqr-extra-toggle"></div>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- EVENT DETAILS -->
        <div class="pbqr-section">
            <h2 class="pbqr-section-title">📅 Veranstaltungsdetails</h2>
            
            <div class="pbqr-event-grid">
                <div class="pbqr-field">
                    <label for="event_type">Veranstaltungstyp *</label>
                    <select name="event_type" id="event_type" required style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #ddd; border-radius: 10px;">
                        <option value="">-- Bitte wählen --</option>
                        <option value="Wedding">Hochzeit</option>
                        <option value="Birthday">Geburtstag</option>
                        <option value="Corporate Event">Unternehmensveranstaltung</option>
                        <option value="Trade Fair">Messe</option>
                        <option value="Other">Sonstige</option>
                    </select>
                </div>

                <div class="pbqr-field">
                    <label for="event_date">Veranstaltungsdatum *</label>
                    <input type="date" 
                           name="event_date" 
                           id="event_date" 
                           required
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="pbqr-field">
                    <label for="event_time">Uhrzeit *</label>
                    <input type="time" 
                           name="event_time" 
                           id="event_time" 
                           required>
                </div>

                <div class="pbqr-field">
                    <label for="event_hours">Dauer (in Stunden) *</label>
                    <input type="number" 
                           name="event_hours" 
                           id="event_hours" 
                           min="0.5" 
                           step="0.5" 
                           required
                           placeholder="z.B. 2, 3, 4">
                </div>

                <div class="pbqr-field">
                    <label for="event_location">Veranstaltungsort *</label>
                    <input type="text" 
                           name="event_location" 
                           id="event_location" 
                           required
                           placeholder="Straße, Stadt, Saal">
                </div>
            </div>

            <div class="pbqr-availability-hint">
                ℹ️ Bitte beachten Sie, dass bestimmte Daten nicht verfügbar sein können. Sie erhalten eine Bestätigung, wenn Ihr Datum reserviert werden kann.
            </div>
        </div>

        <!-- CONTACT INFORMATION -->
        <div class="pbqr-section">
            <h2 class="pbqr-section-title">👤 Kontaktinformationen</h2>
            
            <div class="pbqr-contact-grid">
                <div class="pbqr-field">
                    <label for="customer_first_name">Vorname *</label>
                    <input type="text" 
                           name="customer_first_name" 
                           id="customer_first_name" 
                           required>
                </div>

                <div class="pbqr-field">
                    <label for="customer_last_name">Nachname *</label>
                    <input type="text" 
                           name="customer_last_name" 
                           id="customer_last_name" 
                           required>
                </div>

                <div class="pbqr-field">
                    <label for="customer_company">Firma (Optional)</label>
                    <input type="text" 
                           name="customer_company" 
                           id="customer_company">
                </div>

                <div class="pbqr-field">
                    <label for="customer_email">E-Mail *</label>
                    <input type="email" 
                           name="customer_email" 
                           id="customer_email" 
                           required>
                </div>

                <div class="pbqr-field">
                    <label for="customer_phone">Telefonnummer *</label>
                    <input type="tel" 
                           name="customer_phone" 
                           id="customer_phone" 
                           required>
                </div>

                <div class="pbqr-field">
                    <label for="customer_street">Straße & Hausnummer *</label>
                    <input type="text" 
                           name="customer_street" 
                           id="customer_street" 
                           required
                           placeholder="z.B. Hauptstraße 123">
                </div>

                <div class="pbqr-field">
                    <label for="customer_postal_code">Postleitzahl *</label>
                    <input type="text" 
                           name="customer_postal_code" 
                           id="customer_postal_code" 
                           required
                           placeholder="z.B. 10115">
                </div>

                <div class="pbqr-field">
                    <label for="customer_city">Stadt *</label>
                    <input type="text" 
                           name="customer_city" 
                           id="customer_city" 
                           required>
                </div>

                <div class="pbqr-field">
                    <label for="customer_country">Land *</label>
                    <input type="text" 
                           name="customer_country" 
                           id="customer_country" 
                           required
                           placeholder="z.B. Deutschland">
                </div>
            </div>
        </div>

        <!-- MESSAGE -->
        <div class="pbqr-section">
            <h2 class="pbqr-section-title">💬 Nachricht (Optional)</h2>
            <div class="pbqr-field">
                <textarea name="message" 
                          placeholder="Teilen Sie uns zusätzliche Details oder spezielle Anforderungen mit..."
                          style="width: 100%;"></textarea>
            </div>
        </div>

        <!-- SUBMIT -->
        <div class="pbqr-section-submit">
            <button type="submit" class="pbqr-btn-primary">Angebotsanfrage einreichen</button>
            <p class="pbqr-small">* Erforderliche Felder</p>
        </div>
    </form>
</div>

<script>
    // Block dates on date input
    const blockedDates = <?php echo $blocked_dates_json; ?>;
    
    document.getElementById('event_date').addEventListener('input', function() {
        const selectedDate = new Date(this.value);
        const dateStr = selectedDate.toISOString().split('T')[0];
        
        if (blockedDates.includes(dateStr)) {
            alert('Das gewählte Datum ist nicht verfügbar. Bitte wählen Sie ein anderes Datum.');
            this.value = '';
        }
    });
</script>
