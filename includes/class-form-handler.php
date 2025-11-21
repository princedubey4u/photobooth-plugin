<?php

class PBQR_Form_Handler {
    
    public static function handle_submit() {
        if (!isset($_POST['pbqr_submit'])) return;
        if (!class_exists('WooCommerce')) return;

        if (!isset($_POST['pbqr_nonce']) || !wp_verify_nonce($_POST['pbqr_nonce'], 'pbqr_form')) {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        $customer_name   = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email  = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone  = sanitize_text_field($_POST['customer_phone'] ?? '');
        $event_date      = sanitize_text_field($_POST['event_date'] ?? '');
        $event_location  = sanitize_text_field($_POST['event_location'] ?? '');
        $event_time      = sanitize_text_field($_POST['event_time'] ?? '');
        $event_hours     = sanitize_text_field($_POST['event_hours'] ?? '');
        $package_id      = intval($_POST['package_id'] ?? 0);
        $extras_ids_arr  = isset($_POST['extras']) ? array_map('intval', (array)$_POST['extras']) : [];
        $message         = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$customer_name || !$customer_email || !$package_id) {
            wp_safe_redirect(add_query_arg('pbqr_error', '1', wp_get_referer()));
            exit;
        }

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
            'event_date'      => $event_date,
            'event_location'  => $event_location,
            'event_time'      => $event_time,
            'event_hours'     => $event_hours,
            'package_id'      => $package_id,
            'package_name'    => $package_name,
            'extras_ids'      => $extras_ids_str,
            'extras_names'    => $extras_names_str,
            'message'         => $message,
            'created_at'      => current_time('mysql'),
        ]);

        // Send email to admin
        self::send_admin_email($customer_name, $customer_email, $customer_phone, $event_date, 
                               $event_time, $event_location, $event_hours, $package_name, 
                               $extras_names_str, $message);

        // Send confirmation email to customer
        self::send_customer_email($customer_name, $customer_email, $customer_phone, $event_date,
                                  $event_location, $package_name, $extras_names_str);

        // Redirect with success message
        wp_safe_redirect(add_query_arg('pbqr_success', '1', wp_get_referer()));
        exit;
    }

    private static function send_admin_email($name, $email, $phone, $date, $time, $location, 
                                             $hours, $package, $extras, $message) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        
        // Get user IP
        $user_ip = self::get_user_ip();
        
        // Get referrer page
        $referrer = isset($_SERVER['HTTP_REFERER']) ? sanitize_url($_SERVER['HTTP_REFERER']) : 'Direct';
        
        $subject = 'New Photobooth Quote Request - ' . $name;
        
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
            <h1 class=\"h1\">📩 New Photobooth Quote Request</h1>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">CUSTOMER INFORMATION</div>
            <div class=\"row\">
                <span class=\"label\">Name:</span>
                <span class=\"value\">$name</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Email:</span>
                <span class=\"value\"><a href=\"mailto:$email\">$email</a></span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Phone:</span>
                <span class=\"value\"><a href=\"tel:$phone\">$phone</a></span>
            </div>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">EVENT DETAILS</div>
            <div class=\"row\">
                <span class=\"label\">Event Date:</span>
                <span class=\"value\">$date</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Event Time:</span>
                <span class=\"value\">$time</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Location:</span>
                <span class=\"value\">$location</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Duration:</span>
                <span class=\"value\">$hours hours</span>
            </div>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">SELECTED PACKAGE & EXTRAS</div>
            <div class=\"row\">
                <span class=\"label\">Package:</span>
                <span class=\"value\"><strong>$package</strong></span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Extras:</span>
                <span class=\"value\">" . (!empty($extras) ? $extras : 'None') . "</span>
            </div>
        </div>
        
        " . (!empty($message) ? "
        <div class=\"section\">
            <div class=\"section-title\">CUSTOMER MESSAGE</div>
            <div style=\"color: #333; white-space: pre-wrap;\">$message</div>
        </div>
        " : "") . "
        
        <div style=\"text-align: center; margin: 20px 0;\">
            <a href=\"" . admin_url('admin.php?page=pbqr_quotes') . "\" class=\"action-button\">View in Admin Dashboard</a>
        </div>
        
        <div class=\"meta\">
            <strong>Additional Information:</strong><br>
            User IP Address: $user_ip<br>
            Submission Source: $referrer<br>
            Submitted: " . current_time('F d, Y \a\t g:i A') . "<br>
            Website: <a href=\"$site_url\">$site_name</a>
        </div>
        
        <div class=\"footer\">
            <p>This is an automated email. Please do not reply to this address.</p>
            <p>&copy; " . date('Y') . " $site_name. All rights reserved.</p>
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
        
        $subject = 'Quote Request Received - ' . $site_name;
        
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
            <h1 class=\"h1\">✓ Quote Request Received</h1>
        </div>
        
        <p>Hi $name,</p>
        
        <div class=\"success-box\">
            <p class=\"success-text\"><strong>Thank you for your photobooth inquiry!</strong> We have received your quote request and will review it shortly.</p>
        </div>
        
        <p>We appreciate your interest in $site_name. Our team will process your request and get back to you within 24 hours with a detailed quote.</p>
        
        <div class=\"section\">
            <div class=\"section-title\">YOUR REQUEST SUMMARY</div>
            <div class=\"row\">
                <span class=\"label\">Event Date:</span>
                <span class=\"value\">$date</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Location:</span>
                <span class=\"value\">$location</span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Package:</span>
                <span class=\"value\"><strong>$package</strong></span>
            </div>
            <div class=\"row\">
                <span class=\"label\">Extras:</span>
                <span class=\"value\">" . (!empty($extras) ? $extras : 'None') . "</span>
            </div>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">WHAT HAPPENS NEXT?</div>
            <p>1. Our team reviews your quote request<br>
               2. We prepare a detailed quote with pricing<br>
               3. We send you the quote via email<br>
               4. You can confirm or discuss with us</p>
        </div>
        
        <div class=\"section\">
            <div class=\"section-title\">QUESTIONS?</div>
            <p>If you have any questions or want to modify your request, please don't hesitate to contact us:</p>
            <p>Email: <a href=\"mailto:" . get_option('admin_email') . "\">" . get_option('admin_email') . "</a></p>
        </div>
        
        <div class=\"footer\">
            <p>&copy; " . date('Y') . " $site_name. All rights reserved.</p>
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
        
        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        // AWS, Heroku, etc.
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = trim($ips[0]);
        }
        // Direct connection
        elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        // Validate IPv4 format
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        } elseif (!empty($ip)) {
            return $ip . ' (IPv6)';
        }
        
        return 'Unknown';
    }
}
