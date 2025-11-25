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

        // Handle edit/update
        if (isset($_POST['update_quote']) && isset($_POST['quote_id'])) {
            check_admin_referer('pbqr_edit_nonce', 'nonce');
            $id = intval($_POST['quote_id']);
            
            $wpdb->update(
                $table_name,
                [
                    'customer_name' => sanitize_text_field($_POST['customer_name']),
                    'customer_email' => sanitize_email($_POST['customer_email']),
                    'customer_phone' => sanitize_text_field($_POST['customer_phone']),
                    'event_date' => sanitize_text_field($_POST['event_date']),
                    'event_location' => sanitize_text_field($_POST['event_location']),
                    'event_time' => sanitize_text_field($_POST['event_time']),
                    'event_hours' => sanitize_text_field($_POST['event_hours']),
                    'message' => sanitize_textarea_field($_POST['message']),
                    'status' => sanitize_text_field($_POST['status']),
                ],
                ['id' => $id]
            );
            
            echo '<div class="notice notice-success"><p>Quote updated successfully.</p></div>';
        }

        // Handle add note
        if (isset($_POST['add_note']) && isset($_POST['quote_id'])) {
            check_admin_referer('pbqr_note_nonce', 'nonce');
            $id = intval($_POST['quote_id']);
            $note = sanitize_textarea_field($_POST['note']);
            
            $notes_table = $wpdb->prefix . 'pbqr_quote_notes';
            $wpdb->insert($notes_table, [
                'quote_id' => $id,
                'note' => $note,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ]);
            
            // Send email to customer if checkbox is checked
            if (isset($_POST['send_email'])) {
                $quote = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id");
                if ($quote) {
                    self::send_note_email($quote, $note);
                }
            }
            
            echo '<div class="notice notice-success"><p>Note added successfully' . (isset($_POST['send_email']) ? ' and email sent to customer' : '') . '.</p></div>';
        }

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
                $order_id = self::create_order_from_quote($quote);
                if ($order_id) {
                    // Update quote status
                    $wpdb->update(
                        $table_name,
                        ['status' => 'converted', 'order_id' => $order_id],
                        ['id' => $id]
                    );
                    echo '<div class="notice notice-success"><p>Order #' . $order_id . ' created successfully from quote! <a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '">View Order</a></p></div>';
                }
            }
        }

        // Handle view/edit single quote
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            self::render_edit_page(intval($_GET['id']));
            return;
        }

        // Get filter values
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
        $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Build query with filters
        $where = "WHERE 1=1";
        if ($filter_status) {
            $where .= $wpdb->prepare(" AND status = %s", $filter_status);
        }
        if ($filter_date_from) {
            $where .= $wpdb->prepare(" AND event_date >= %s", $filter_date_from);
        }
        if ($filter_date_to) {
            $where .= $wpdb->prepare(" AND event_date <= %s", $filter_date_to);
        }
        if ($search) {
            $where .= $wpdb->prepare(" AND (customer_name LIKE %s OR customer_email LIKE %s OR event_location LIKE %s)", 
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Photobooth Quote Requests</h1>
            
            <!-- Filters -->
            <div style="background: white; padding: 15px; margin: 15px 0; border: 1px solid #ccc; border-radius: 6px;">
                <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
                    <input type="hidden" name="page" value="pbqr_quotes">
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Search</label>
                        <input type="text" 
                               name="s" 
                               value="<?php echo esc_attr($search); ?>" 
                               placeholder="Name, email, location..."
                               style="width: 200px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Status</label>
                        <select name="filter_status" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php selected($filter_status, 'pending'); ?>>Pending</option>
                            <option value="reviewed" <?php selected($filter_status, 'reviewed'); ?>>Reviewed</option>
                            <option value="quoted" <?php selected($filter_status, 'quoted'); ?>>Quoted</option>
                            <option value="converted" <?php selected($filter_status, 'converted'); ?>>Converted</option>
                            <option value="declined" <?php selected($filter_status, 'declined'); ?>>Declined</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Event Date From</label>
                        <input type="date" 
                               name="filter_date_from" 
                               value="<?php echo esc_attr($filter_date_from); ?>"
                               style="width: 160px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Event Date To</label>
                        <input type="date" 
                               name="filter_date_to" 
                               value="<?php echo esc_attr($filter_date_to); ?>"
                               style="width: 160px;">
                    </div>
                    
                    <div style="display: flex; gap: 6px;">
                        <button type="submit" class="button button-primary">Filter</button>
                        <a href="<?php echo admin_url('admin.php?page=pbqr_quotes'); ?>" class="button">Clear</a>
                    </div>
                </form>
            </div>

            <?php if ($results): ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date Submitted</th>
                            <th>Customer Name</th>
                            <th>Email / Phone</th>
                            <th>Event Date</th>
                            <th>Location</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <?php
                            $status_colors = [
                                'pending' => '#ffa500',
                                'reviewed' => '#2196f3',
                                'quoted' => '#9c27b0',
                                'converted' => '#4caf50',
                                'declined' => '#f44336'
                            ];
                            $status_color = isset($status_colors[$row->status]) ? $status_colors[$row->status] : '#666';
                            ?>
                            <tr>
                                <td><?php echo esc_html($row->id); ?></td>
                                <td><?php echo esc_html(date('M d, Y H:i', strtotime($row->created_at))); ?></td>
                                <td><?php echo esc_html($row->customer_name); ?></td>
                                <td>
                                    <strong><?php echo esc_html($row->customer_email); ?></strong><br>
                                    <?php echo esc_html($row->customer_phone); ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html(date('M d, Y', strtotime($row->event_date))); ?></strong><br>
                                    <span style="color: #666; font-size: 12px;"><?php echo esc_html($row->event_time); ?> (<?php echo esc_html($row->event_hours); ?>h)</span>
                                </td>
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
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; color: white; background: <?php echo $status_color; ?>;">
                                        <?php echo esc_html(ucfirst($row->status)); ?>
                                    </span>
                                    <?php if ($row->order_id): ?>
                                        <br><a href="<?php echo admin_url('post.php?post=' . $row->order_id . '&action=edit'); ?>" style="font-size: 11px;">Order #<?php echo $row->order_id; ?></a>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=edit&id=' . $row->id)); ?>" class="button button-small">View/Edit</a><br>
                                    <?php if ($row->status !== 'converted'): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=convert_order&id=' . $row->id . '&nonce=' . wp_create_nonce('pbqr_convert_nonce'))); ?>" class="button button-primary button-small" style="margin-top: 5px;">Convert to Order</a><br>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=delete&id=' . $row->id . '&nonce=' . wp_create_nonce('pbqr_delete_nonce'))); ?>" class="button button-link-delete button-small" style="margin-top: 5px;" onclick="return confirm('Are you sure?');">Delete</a>
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

    private static function render_edit_page($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbqr_quotes';
        $notes_table = $wpdb->prefix . 'pbqr_quote_notes';
        
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        
        if (!$quote) {
            echo '<div class="wrap"><h1>Quote not found</h1></div>';
            return;
        }
        
        // Get notes
        $notes = $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, u.display_name 
             FROM $notes_table n 
             LEFT JOIN {$wpdb->users} u ON n.created_by = u.ID 
             WHERE n.quote_id = %d 
             ORDER BY n.created_at DESC", 
            $id
        ));
        
        ?>
        <div class="wrap">
            <h1>
                Edit Quote Request #<?php echo $id; ?>
                <a href="<?php echo admin_url('admin.php?page=pbqr_quotes'); ?>" class="page-title-action">← Back to List</a>
            </h1>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Main Content -->
                <div>
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px;">
                        <h2>Quote Details</h2>
                        <form method="POST">
                            <?php wp_nonce_field('pbqr_edit_nonce', 'nonce'); ?>
                            <input type="hidden" name="update_quote" value="1">
                            <input type="hidden" name="quote_id" value="<?php echo $id; ?>">
                            
                            <table class="form-table">
                                <tr>
                                    <th><label>Customer Name</label></th>
                                    <td><input type="text" name="customer_name" value="<?php echo esc_attr($quote->customer_name); ?>" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label>Email</label></th>
                                    <td><input type="email" name="customer_email" value="<?php echo esc_attr($quote->customer_email); ?>" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label>Phone</label></th>
                                    <td><input type="text" name="customer_phone" value="<?php echo esc_attr($quote->customer_phone); ?>" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label>Event Date</label></th>
                                    <td><input type="date" name="event_date" value="<?php echo esc_attr($quote->event_date); ?>" required></td>
                                </tr>
                                <tr>
                                    <th><label>Event Time</label></th>
                                    <td><input type="time" name="event_time" value="<?php echo esc_attr($quote->event_time); ?>" required></td>
                                </tr>
                                <tr>
                                    <th><label>Event Location</label></th>
                                    <td><input type="text" name="event_location" value="<?php echo esc_attr($quote->event_location); ?>" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label>Duration (hours)</label></th>
                                    <td><input type="number" name="event_hours" value="<?php echo esc_attr($quote->event_hours); ?>" min="1" required></td>
                                </tr>
                                <tr>
                                    <th><label>Status</label></th>
                                    <td>
                                        <select name="status" required>
                                            <option value="pending" <?php selected($quote->status, 'pending'); ?>>Pending</option>
                                            <option value="reviewed" <?php selected($quote->status, 'reviewed'); ?>>Reviewed</option>
                                            <option value="quoted" <?php selected($quote->status, 'quoted'); ?>>Quoted</option>
                                            <option value="converted" <?php selected($quote->status, 'converted'); ?>>Converted</option>
                                            <option value="declined" <?php selected($quote->status, 'declined'); ?>>Declined</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Customer Message</label></th>
                                    <td><textarea name="message" rows="4" class="large-text"><?php echo esc_textarea($quote->message); ?></textarea></td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary">Update Quote</button>
                            </p>
                        </form>
                    </div>
                    
                    <!-- Package & Extras -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
                        <h2>Package & Extras</h2>
                        <p><strong>Package:</strong> 
                            <?php if ($quote->package_id): ?>
                                <a href="<?php echo admin_url('post.php?post=' . $quote->package_id . '&action=edit'); ?>" target="_blank"><?php echo esc_html($quote->package_name); ?> ↗</a>
                            <?php else: ?>
                                <?php echo esc_html($quote->package_name); ?>
                            <?php endif; ?>
                        </p>
                        <p><strong>Extras:</strong><br>
                            <?php 
                            if ($quote->extras_ids) {
                                $extras_ids = explode(',', $quote->extras_ids);
                                $extras_names = explode(', ', $quote->extras_names);
                                foreach ($extras_ids as $key => $extra_id) {
                                    $extra_id = intval($extra_id);
                                    echo '<a href="' . admin_url('post.php?post=' . $extra_id . '&action=edit') . '" target="_blank" style="display: block; margin: 5px 0;">';
                                    echo '• ' . esc_html($extras_names[$key]) . ' ↗';
                                    echo '</a>';
                                }
                            } else {
                                echo 'None';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div>
                    <!-- Quick Actions -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">Quick Actions</h3>
                        <?php if ($quote->status !== 'converted'): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=convert_order&id=' . $id . '&nonce=' . wp_create_nonce('pbqr_convert_nonce'))); ?>" 
                           class="button button-primary" 
                           style="width: 100%; text-align: center; margin-bottom: 10px;">
                            Convert to Order
                        </a>
                        <?php elseif ($quote->order_id): ?>
                        <a href="<?php echo admin_url('post.php?post=' . $quote->order_id . '&action=edit'); ?>" 
                           class="button button-primary" 
                           style="width: 100%; text-align: center; margin-bottom: 10px;">
                            View Order #<?php echo $quote->order_id; ?>
                        </a>
                        <?php endif; ?>
                        <p style="font-size: 12px; color: #666; margin: 10px 0;">
                            <strong>Submitted:</strong> <?php echo date('M d, Y \a\t g:i A', strtotime($quote->created_at)); ?>
                        </p>
                    </div>
                    
                    <!-- Notes -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
                        <h3 style="margin-top: 0;">Notes</h3>
                        
                        <form method="POST" style="margin-bottom: 20px;">
                            <?php wp_nonce_field('pbqr_note_nonce', 'nonce'); ?>
                            <input type="hidden" name="add_note" value="1">
                            <input type="hidden" name="quote_id" value="<?php echo $id; ?>">
                            
                            <textarea name="note" 
                                      rows="4" 
                                      placeholder="Add a note..." 
                                      required
                                      style="width: 100%; margin-bottom: 10px;"></textarea>
                            
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="send_email" value="1">
                                Send this note to customer via email
                            </label>
                            
                            <button type="submit" class="button button-primary" style="width: 100%;">Add Note</button>
                        </form>
                        
                        <?php if ($notes): ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($notes as $note): ?>
                                    <div style="background: #f9f9f9; padding: 12px; margin-bottom: 10px; border-left: 3px solid #0073aa; border-radius: 4px;">
                                        <div style="font-size: 12px; color: #666; margin-bottom: 6px;">
                                            <strong><?php echo esc_html($note->display_name ? $note->display_name : 'Unknown User'); ?></strong> • 
                                            <?php echo date('M d, Y \a\t g:i A', strtotime($note->created_at)); ?>
                                        </div>
                                        <div style="white-space: pre-wrap;"><?php echo esc_html($note->note); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic;">No notes yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function create_order_from_quote($quote) {
        if (!class_exists('WooCommerce')) return false;

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
        $order_note = "Quote Request Details (Quote #" . $quote->id . "):\n";
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
        
        return $order->get_id();
    }

    private static function send_note_email($quote, $note) {
        $site_name = get_bloginfo('name');
        $subject = 'Update on Your Quote Request - ' . $site_name;
        
        $body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
        .header { background: #f5f5f5; padding: 20px; margin: -20px -20px 20px -20px; }
        .h1 { color: #ffb21a; font-size: 24px; margin: 0; }
        .note-box { background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .footer { background: #f5f5f5; padding: 15px; margin: 20px -20px -20px -20px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h1 class=\"h1\">📝 Quote Update</h1>
        </div>
        
        <p>Hi " . esc_html($quote->customer_name) . ",</p>
        
        <p>We have an update regarding your photobooth quote request for <strong>" . date('F d, Y', strtotime($quote->event_date)) . "</strong>.</p>
        
        <div class=\"note-box\">
            <strong>Update:</strong><br>
            " . nl2br(esc_html($note)) . "
        </div>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>
        " . $site_name . " Team</p>
        
        <div class=\"footer\">
            <p>&copy; " . date('Y') . " $site_name. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($quote->customer_email, $subject, $body, $headers);
    }
}
