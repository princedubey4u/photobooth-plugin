<?php

class PBQR_Form_Handler {
    
    public static function handle_submit() {
        if (!isset($_POST['pbqr_submit'])) return;
        if (!class_exists('WooCommerce')) return;

        if (!isset($_POST['pbqr_nonce']) || !wp_verify_nonce($_POST['pbqr_nonce'], 'pbqr_form')) {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        // Contact details
        $customer_first_name = sanitize_text_field($_POST['customer_first_name'] ?? '');
        $customer_last_name  = sanitize_text_field($_POST['customer_last_name'] ?? '');
        $customer_company    = sanitize_text_field($_POST['customer_company'] ?? '');
        $customer_email      = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone      = sanitize_text_field($_POST['customer_phone'] ?? '');
        $customer_street     = sanitize_text_field($_POST['customer_street'] ?? '');
        $customer_postal_code = sanitize_text_field($_POST['customer_postal_code'] ?? '');
        $customer_city       = sanitize_text_field($_POST['customer_city'] ?? '');
        $customer_country    = sanitize_text_field($_POST['customer_country'] ?? '');
        
        // Event details
        $event_type          = sanitize_text_field($_POST['event_type'] ?? '');
        $event_date          = sanitize_text_field($_POST['event_date'] ?? '');
        $event_location      = sanitize_text_field($_POST['event_location'] ?? '');
        $event_time          = sanitize_text_field($_POST['event_time'] ?? '');
        $event_hours         = sanitize_text_field($_POST['event_hours'] ?? '');
        
        $package_id          = intval($_POST['package_id'] ?? 0);
        $extras_ids_arr      = isset($_POST['extras']) ? array_map('intval', (array)$_POST['extras']) : [];
        $message             = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$customer_first_name || !$customer_last_name || !$customer_email || !$package_id) {
            wp_safe_redirect(add_query_arg('pbqr_error', '1', wp_get_referer()));
            exit;
        }

        $customer_name = $customer_first_name . ' ' . $customer_last_name;

        global $wpdb;
        $table_name = $wpdb->prefix . 'pbqr_quotes';

        // Get product names
        $package_name  = '';
        $extras_names  = [];

        $package_product = wc_get_product($package_id);
        if ($package_product) {
            $package_name = $package_product->get_name();
        }

        if (!empty($extras_ids_arr)) {
            foreach ($extras_ids_arr as $eid) {
                $p = wc_get_product($eid);
                if ($p) {
                    $extras_names[] = $p->get_name();
                }
            }
        }

        $extras_ids_str   = !empty($extras_ids_arr) ? implode(',', $extras_ids_arr) : '';
        $extras_names_str = !empty($extras_names) ? implode(', ', $extras_names) : '';

        // Insert into database
        $wpdb->insert($table_name, [
            'customer_name'   => $customer_name,
            'customer_email'  => $customer_email,
            'customer_phone'  => $customer_phone,
            'event_type'      => $event_type,
            'event_date'      => $event_date,
            'event_location'  => $event_location,
            'event_time'      => $event_time,
            'event_hours'     => $event_hours,
            'package_id'      => $package_id,
            'package_name'    => $package_name,
            'extras_ids'      => $extras_ids_str,
            'extras_names'    => $extras_names_str,
            'message'         => $message,
            'customer_first_name'  => $customer_first_name,
            'customer_last_name'   => $customer_last_name,
            'customer_company'     => $customer_company,
            'customer_street'      => $customer_street,
            'customer_postal_code' => $customer_postal_code,
            'customer_city'        => $customer_city,
            'customer_country'     => $customer_country,
            'created_at'      => current_time('mysql'),
            'status'          => 'pending',
        ]);

        // Send email to admin
        self::send_admin_email($customer_name, $customer_email, $customer_phone, $event_type, $event_date, 
                               $event_time, $event_location, $event_hours, $package_name, 
                               $extras_names_str, $message, $customer_company, $customer_street, 
                               $customer_postal_code, $customer_city, $customer_country);

        // Send confirmation email to customer
        self::send_customer_email($customer_name, $customer_email, $customer_phone, $event_date,
                                  $event_location, $package_name, $extras_names_str);

        // Redirect with success message
        wp_safe_redirect(add_query_arg('pbqr_success', '1', wp_get_referer()));
        exit;
    }

    private static function send_admin_email($name, $email, $phone, $event_type, $date, $time, $location, 
                                             $hours, $package, $extras, $message, $company, $street, 
                                             $postal_code, $city, $country) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        
        $user_ip = self::get_user_ip();
        $referrer = isset($_SERVER['HTTP_REFERER']) ? sanitize_url($_SERVER['HTTP_REFERER']) : 'Direkt';
        
        $subject = 'Neue Fotobox-Angebotsanfrage - ' . $name;
        
        $body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
        .header { background: #f5f5f5; padding: 20px; margin: -20px -20px 20px -20px; }
        .h1 { color: #ffb21a; font-size: 24px; margin: 0; }
        .section { margin: 20px 0; padding: 15px; background: #fafafa; border-left: 4px solid #ffb21a; }
        .section-title { font-weight: bold; color: #333; font-size: 14px; margin-bottom: 10px; }
        .row { display: flex; justify-content: space-between; margin: 8px 0; }
        .label { font-weight: bold; color: #666; width: 150px; }
        .value { color: #333; }
        .meta { font-size: 12px; color: #999; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
        .action-button { display: inline-block; background: #0073aa; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px 10px 0; font-weight: bold; }
        .footer { background: #f5f5f5; padding: 15px; margin: 20px -20px -20px -20px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h1 class=\"h1\">📩 Neue Fotobox-Angebotsanfrage</h1>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">KUNDENINFORMATIONEN</div>
            <div class=\"row\">
                <span class=\"label\">Name:</span>
                <span class=\"value\">$name</span>
            </div>
            " . ($company ? "<div class=\"row\">
                <span class=\"label\">Firma:</span>
                <span class=\"value\">$company</span>
            </div>" : "") . "
            <div class=\"row\">
                <span class=\"label\">E-Mail:</span>
                <span class=\"value\"><a href=\"mailto:$email\">$email</a></span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Telefon:</span>
                <span class=\"value\"><a href=\"tel:$phone\">$phone</a></span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Adresse:</span>
                <span class=\"value\">$street, $postal_code $city, $country</span>
            </div>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">VERANSTALTUNGSDETAILS</div>
            <div class=\"row\">
                <span class=\"label\">Art:</span>
                <span class=\"value\">$event_type</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Datum:</span>
                <span class=\"value\">$date</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Uhrzeit:</span>
                <span class=\"value\">$time</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Ort:</span>
                <span class=\"value\">$location</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Dauer:</span>
                <span class=\"value\">$hours Stunden</span>
            </div>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">GEWÄHLTES PAKET & EXTRAS</div>
            <div class=\"row\">
                <span class=\"label\">Paket:</span>
                <span class=\"value\"><strong>$package</strong></span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Extras:</span>
                <span class=\"value\">" . (!empty($extras) ? $extras : 'Keine') . "</span>
            </div>
        </div>
        
        " . (!empty($message) ? "
        <div class=\"section\">
            <div class=\"section-title\">KUNDENNACHRICHT</div>
            <div style=\"color: #333; white-space: pre-wrap;\">$message</div>
        </div>
        " : "") . "
        
        <div style=\"text-align: center; margin: 20px 0;\">
            <a href=\"" . admin_url('admin.php?page=pbqr_quotes') . "\" class=\"action-button\">Im Admin-Dashboard ansehen</a>
        </div>
        
        <div class=\"meta\">
            <strong>Zusätzliche Informationen:</strong><br>
            Benutzer-IP-Adresse: $user_ip<br>
            Einreichungsquelle: $referrer<br>
            Eingereicht: " . current_time('d.m.Y \u\m H:i \U\h\r') . "<br>
            Website: <a href=\"$site_url\">$site_name</a>
        </div>
        
        <div class=\"footer\">
            <p>Dies ist eine automatische E-Mail. Bitte antworten Sie nicht auf diese Adresse.</p>
            <p>&copy; " . date('Y') . " $site_name. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin_email, $subject, $body, $headers);
    }

    private static function send_customer_email($name, $email, $phone, $date, $location, $package, $extras) {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        
        $subject = 'Angebotsanfrage erhalten - ' . $site_name;
        
        $body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
        .header { background: #f5f5f5; padding: 20px; margin: -20px -20px 20px -20px; }
        .h1 { color: #ffb21a; font-size: 24px; margin: 0; }
        .section { margin: 20px 0; padding: 15px; background: #fafafa; border-left: 4px solid #ffb21a; }
        .section-title { font-weight: bold; color: #333; font-size: 14px; margin-bottom: 10px; }
        .row { display: flex; justify-content: space-between; margin: 8px 0; }
        .label { font-weight: bold; color: #666; width: 150px; }
        .value { color: #333; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success-text { color: #155724; }
        .footer { background: #f5f5f5; padding: 15px; margin: 20px -20px -20px -20px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h1 class=\"h1\">✓ Angebotsanfrage erhalten</h1>
        </div>
        
        <p>Hallo $name,</p>
        
        <div class=\"success-box\">
            <p class=\"success-text\"><strong>Vielen Dank für Ihre Fotobox-Anfrage!</strong> Wir haben Ihre Angebotsanfrage erhalten und werden sie in Kürze prüfen.</p>
        </div>
        
        <p>Wir freuen uns über Ihr Interesse an $site_name. Unser Team wird Ihre Anfrage bearbeiten und sich innerhalb von 24 Stunden mit einem detaillierten Angebot bei Ihnen melden.</p>
        
        <div class=\"section\">
            <div class=\"section-title\">IHRE ANFRAGE - ZUSAMMENFASSUNG</div>
            <div class=\"row\">
                <span class=\"label\">Datum:</span>
                <span class=\"value\">$date</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Ort:</span>
                <span class=\"value\">$location</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Paket:</span>
                <span class=\"value\"><strong>$package</strong></span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Extras:</span>
                <span class=\"value\">" . (!empty($extras) ? $extras : 'Keine') . "</span>
            </div>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">WIE GEHT ES WEITER?</div>
            <p>1. Unser Team prüft Ihre Angebotsanfrage<br>
               2. Wir erstellen ein detailliertes Angebot mit Preisen<br>
               3. Sie erhalten das Angebot per E-Mail<br>
               4. Sie können bestätigen oder mit uns besprechen</p>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">FRAGEN?</div>
            <p>Wenn Sie Fragen haben oder Ihre Anfrage ändern möchten, zögern Sie nicht, uns zu kontaktieren:</p>
            <p>E-Mail: <a href=\"mailto:" . get_option('admin_email') . "\">" . get_option('admin_email') . "</a></p>
        </div>
        
        <div class=\"footer\">
            <p>&copy; " . date('Y') . " $site_name. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $body, $headers);
    }

    private static function get_user_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = trim($ips[0]);
        }
        elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        } elseif (!empty($ip)) {
            return $ip . ' (IPv6)';
        }
        
        return 'Unbekannt';
    }
}
