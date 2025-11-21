<?php

class PBQR_Admin_Page {
    
    public static function register_menu() {
        add_menu_page(
            'Photobooth Quotes',
            'Photobooth Quotes',
            'manage_options',
            'pbqr_quotes',
            ['PBQR_Admin_Page', 'render_page'],
            'dashicons-format-gallery',
            26
        );
    }

    public static function render_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbqr_quotes';

        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('pbqr_delete_nonce', 'nonce');
            $id = intval($_GET['id']);
            $wpdb->delete($table_name, ['id' => $id]);
            echo '<div class="notice notice-success"><p>Quote deleted successfully.</p></div>';
        }

        // Handle convert to order action
        if (isset($_GET['action']) && $_GET['action'] === 'convert_order' && isset($_GET['id'])) {
            check_admin_referer('pbqr_convert_nonce', 'nonce');
            $id = intval($_GET['id']);
            $quote = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id");
            
            if ($quote) {
                self::create_order_from_quote($quote);
                echo '<div class="notice notice-success"><p>Order created successfully from quote!</p></div>';
            }
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Photobooth Quote Requests</h1>
            
            <?php if ($results): ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Customer Name</th>
                            <th>Email / Phone</th>
                            <th>Event Date</th>
                            <th>Location</th>
                            <th>Package</th>
                            <th>Extras</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->id); ?></td>
                                <td><?php echo esc_html(date('M d, Y H:i', strtotime($row->created_at))); ?></td>
                                <td><?php echo esc_html($row->customer_name); ?></td>
                                <td>
                                    <strong><?php echo esc_html($row->customer_email); ?></strong><br>
                                    <?php echo esc_html($row->customer_phone); ?>
                                </td>
                                <td><?php echo esc_html($row->event_date); ?></td>
                                <td><?php echo esc_html($row->event_location); ?></td>
                                <td>
                                    <?php if ($row->package_id): ?>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $row->package_id . '&action=edit')); ?>" target="_blank" style="color: #0073aa; text-decoration: none;">
                                            <?php echo esc_html($row->package_name); ?> ↗
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($row->package_name); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($row->extras_ids) {
                                        $extras_ids = explode(',', $row->extras_ids);
                                        $extras_names = explode(', ', $row->extras_names);
                                        foreach ($extras_ids as $key => $extra_id) {
                                            $extra_id = intval($extra_id);
                                            echo '<a href="' . esc_url(admin_url('post.php?post=' . $extra_id . '&action=edit')) . '" target="_blank" style="color: #0073aa; text-decoration: none; display: block;">';
                                            echo esc_html($extras_names[$key]) . ' ↗';
                                            echo '</a>';
                                        }
                                    } else {
                                        echo 'None';
                                    }
                                    ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=convert_order&id=' . $row->id . '&nonce=' . wp_create_nonce('pbqr_convert_nonce'))); ?>" class="button button-primary button-small" style="margin-bottom: 5px;">Convert to Order</a><br>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=delete&id=' . $row->id . '&nonce=' . wp_create_nonce('pbqr_delete_nonce'))); ?>" class="button button-link-delete button-small" onclick="return confirm('Are you sure?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No quote requests found.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function create_order_from_quote($quote) {
        if (!class_exists('WooCommerce')) return;

        $order = wc_create_order();

        // Customer details
        $order->set_billing_first_name($quote->customer_name);
        $order->set_billing_email($quote->customer_email);
        $order->set_billing_phone($quote->customer_phone);
        
        $order->set_shipping_first_name($quote->customer_name);

        // Add package product to order
        if ($quote->package_id) {
            $product = wc_get_product($quote->package_id);
            if ($product) {
                $order->add_product($product, 1);
            }
        }

        // Add extras to order
        if (!empty($quote->extras_ids)) {
            $extras_ids = explode(',', $quote->extras_ids);
            foreach ($extras_ids as $extra_id) {
                $extra_id = intval($extra_id);
                $product = wc_get_product($extra_id);
                if ($product) {
                    $order->add_product($product, 1);
                }
            }
        }

        // Add order note with quote details
        $order_note = "Quote Request Details:\n";
        $order_note .= "Event Date: " . $quote->event_date . "\n";
        $order_note .= "Event Location: " . $quote->event_location . "\n";
        $order_note .= "Event Time: " . $quote->event_time . "\n";
        $order_note .= "Event Hours: " . $quote->event_hours . "\n";
        if ($quote->message) {
            $order_note .= "Customer Message: " . $quote->message . "\n";
        }
        $order->add_order_note($order_note);

        // Calculate totals
        $order->calculate_totals();

        // Save order
        $order->save();
    }
}
