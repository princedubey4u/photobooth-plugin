<?php

class PBQR_Calendar_Manager {
    
    public static function register_menu() {
        add_submenu_page(
            'pbqr_quotes',
            'Calendar Management',
            'Calendar Management',
            'manage_options',
            'pbqr_calendar',
            ['PBQR_Calendar_Manager', 'render_page']
        );
    }

    public static function render_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbqr_blocked_dates';

        // Handle add blocked date
        if (isset($_POST['add_blocked_date']) && isset($_POST['blocked_date'])) {
            check_admin_referer('pbqr_calendar_nonce', 'nonce');
            $blocked_date = sanitize_text_field($_POST['blocked_date']);
            $reason = sanitize_text_field($_POST['reason']);
            
            $wpdb->insert($table_name, [
                'blocked_date' => $blocked_date,
                'reason' => $reason,
                'created_at' => current_time('mysql')
            ]);
            
            echo '<div class="notice notice-success"><p>Date blocked successfully.</p></div>';
        }

        // Handle delete blocked date
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('pbqr_delete_date_nonce', 'nonce');
            $id = intval($_GET['id']);
            $wpdb->delete($table_name, ['id' => $id]);
            echo '<div class="notice notice-success"><p>Blocked date removed successfully.</p></div>';
        }

        // Get all blocked dates
        $blocked_dates = $wpdb->get_results("SELECT * FROM $table_name ORDER BY blocked_date ASC");
        ?>
        <div class="wrap">
            <h1>📅 Calendar Management</h1>
            <p>Manage blocked dates for photobooth bookings. Customers will not be able to select these dates.</p>

            <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc; border-radius: 8px; max-width: 600px;">
                <h2>Block a Date</h2>
                <form method="POST">
                    <?php wp_nonce_field('pbqr_calendar_nonce', 'nonce'); ?>
                    <input type="hidden" name="add_blocked_date" value="1">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="blocked_date">Date *</label></th>
                            <td>
                                <input type="date" 
                                       name="blocked_date" 
                                       id="blocked_date" 
                                       required 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       style="width: 100%; max-width: 300px;">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reason">Reason (optional)</label></th>
                            <td>
                                <input type="text" 
                                       name="reason" 
                                       id="reason" 
                                       placeholder="e.g., Equipment maintenance, Holiday, Already booked"
                                       style="width: 100%; max-width: 300px;">
                            </td>
                        </tr>
                    </table>
                    
                    <button type="submit" class="button button-primary">🚫 Block This Date</button>
                </form>
            </div>

            <h2>Blocked Dates</h2>
            
            <?php if ($blocked_dates): ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Blocked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_dates as $row): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(date('F d, Y', strtotime($row->blocked_date))); ?></strong>
                                    <?php if (strtotime($row->blocked_date) < strtotime('today')): ?>
                                        <span style="color: #999; font-size: 11px;">(Past)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($row->reason ? $row->reason : '-'); ?></td>
                                <td><?php echo esc_html(date('M d, Y', strtotime($row->created_at))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_calendar&action=delete&id=' . $row->id . '&nonce=' . wp_create_nonce('pbqr_delete_date_nonce'))); ?>" 
                                       class="button button-small button-link-delete" 
                                       onclick="return confirm('Remove this blocked date?');">
                                        Unblock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No blocked dates found.</p>
            <?php endif; ?>

            <div style="background: #f0f6fc; padding: 15px; margin-top: 30px; border-left: 4px solid #0073aa; border-radius: 4px;">
                <h3 style="margin-top: 0;">💡 Tips</h3>
                <ul>
                    <li>Block dates for holidays, equipment maintenance, or when you're already booked</li>
                    <li>Customers will see an error if they try to select a blocked date</li>
                    <li>You can remove blocked dates anytime by clicking "Unblock"</li>
                    <li>Past dates are kept for record-keeping but don't affect new bookings</li>
                </ul>
            </div>
        </div>
        <?php
    }
}
