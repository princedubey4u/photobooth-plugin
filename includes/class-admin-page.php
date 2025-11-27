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
                    'customer_first_name' => sanitize_text_field($_POST['customer_first_name']),
                    'customer_last_name' => sanitize_text_field($_POST['customer_last_name']),
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
            
            echo '<div class="notice notice-success"><p>✓ Angebot erfolgreich aktualisiert.</p></div>';
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
            
            echo '<div class="notice notice-success"><p>✓ Notiz hinzugefügt' . (isset($_POST['send_email']) ? ' und E-Mail an Kunden gesendet' : '') . '.</p></div>';
        }

        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('pbqr_delete_nonce', 'nonce');
            $id = intval($_GET['id']);
            $wpdb->delete($table_name, ['id' => $id]);
            echo '<div class="notice notice-success"><p>✓ Angebot erfolgreich gelöscht.</p></div>';
        }

        // Handle convert to order action
        if (isset($_GET['action']) && $_GET['action'] === 'convert_order' && isset($_GET['id'])) {
            check_admin_referer('pbqr_convert_nonce', 'nonce');
            $id = intval($_GET['id']);
            $quote = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id");
            
            if ($quote) {
                $order_id = self::create_order_from_quote($quote);
                if ($order_id) {
                    // Update quote status to converted
                    $wpdb->update(
                        $table_name,
                        ['status' => 'converted', 'order_id' => $order_id],
                        ['id' => $id]
                    );
                    echo '<div class="notice notice-success"><p>✓ Bestellung #' . $order_id . ' erfolgreich aus dem Angebot erstellt! <a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '">Bestellung anzeigen</a></p></div>';
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
            $where .= $wpdb->prepare(" AND (customer_first_name LIKE %s OR customer_last_name LIKE %s OR customer_email LIKE %s OR event_location LIKE %s)", 
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>📋 Photobooth Angebotsanfragen</h1>
            
            <!-- Filters -->
            <div style="background: white; padding: 15px; margin: 15px 0; border: 1px solid #ccc; border-radius: 6px;">
                <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
                    <input type="hidden" name="page" value="pbqr_quotes">
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Suche</label>
                        <input type="text" 
                               name="s" 
                               value="<?php echo esc_attr($search); ?>" 
                               placeholder="Name, E-Mail, Ort..."
                               style="width: 200px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Status</label>
                        <select name="filter_status" style="width: 150px;">
                            <option value="">Alle Status</option>
                            <option value="pending" <?php selected($filter_status, 'pending'); ?>>Ausstehend</option>
                            <option value="reviewed" <?php selected($filter_status, 'reviewed'); ?>>Überprüft</option>
                            <option value="quoted" <?php selected($filter_status, 'quoted'); ?>>Angeboten</option>
                            <option value="converted" <?php selected($filter_status, 'converted'); ?>>Konvertiert</option>
                            <option value="declined" <?php selected($filter_status, 'declined'); ?>>Abgelehnt</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Veranstaltung ab</label>
                        <input type="date" 
                               name="filter_date_from" 
                               value="<?php echo esc_attr($filter_date_from); ?>"
                               style="width: 160px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 4px;">Veranstaltung bis</label>
                        <input type="date" 
                               name="filter_date_to" 
                               value="<?php echo esc_attr($filter_date_to); ?>"
                               style="width: 160px;">
                    </div>
                    
                    <button type="submit" class="button button-primary">Filter anwenden</button>
                    <a href="<?php echo admin_url('admin.php?page=pbqr_quotes'); ?>" class="button">Filter zurücksetzen</a>
                </form>
            </div>

            <?php if (empty($results)): ?>
                <p>Keine Angebote gefunden.</p>
            <?php else: ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th width="150">Kunde</th>
                            <th width="120">E-Mail</th>
                            <th width="100">Datum</th>
                            <th width="150">Ort</th>
                            <th width="100">Paket</th>
                            <th width="80">Status</th>
                            <th width="150">Erstellt am</th>
                            <th width="120">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $quote): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($quote->customer_first_name . ' ' . $quote->customer_last_name); ?></strong>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo esc_attr($quote->customer_email); ?>">
                                        <?php echo esc_html($quote->customer_email); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo esc_html(date('d.m.Y', strtotime($quote->event_date))); ?>
                                    <?php if (strtotime($quote->event_date) < strtotime('today')): ?>
                                        <span style="color: #999; font-size: 11px;"> (Vergangen)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($quote->event_location); ?></td>
                                <td><?php echo esc_html($quote->package_name); ?></td>
                                <td>
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                        <?php 
                                            $status_colors = [
                                                'pending' => 'background: #fff3cd; color: #856404;',
                                                'reviewed' => 'background: #d1ecf1; color: #0c5460;',
                                                'quoted' => 'background: #d4edda; color: #155724;',
                                                'converted' => 'background: #cce5ff; color: #004085;',
                                                'declined' => 'background: #f8d7da; color: #721c24;'
                                            ];
                                            echo isset($status_colors[$quote->status]) ? $status_colors[$quote->status] : '';
                                        ?>">
                                        <?php 
                                            $status_labels = [
                                                'pending' => 'Ausstehend',
                                                'reviewed' => 'Überprüft',
                                                'quoted' => 'Angeboten',
                                                'converted' => 'Konvertiert',
                                                'declined' => 'Abgelehnt'
                                            ];
                                            echo isset($status_labels[$quote->status]) ? $status_labels[$quote->status] : $quote->status;
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('d.m.Y H:i', strtotime($quote->created_at))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=edit&id=' . $quote->id)); ?>" 
                                       class="button button-small">
                                        Details
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=delete&id=' . $quote->id . '&nonce=' . wp_create_nonce('pbqr_delete_nonce'))); ?>" 
                                       class="button button-small button-link-delete" 
                                       onclick="return confirm('Dieses Angebot wirklich löschen?');">
                                        Löschen
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_edit_page($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbqr_quotes';
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$quote) {
            echo '<div class="notice notice-error"><p>Angebot nicht gefunden.</p></div>';
            return;
        }

        $notes_table = $wpdb->prefix . 'pbqr_quote_notes';
        $notes = $wpdb->get_results($wpdb->prepare("
            SELECT n.*, u.display_name 
            FROM $notes_table n 
            LEFT JOIN {$wpdb->users} u ON n.created_by = u.ID 
            WHERE n.quote_id = %d 
            ORDER BY n.created_at DESC
        ", $id));
        ?>
        <div class="wrap">
            <h1>📋 Angebot #<?php echo $id; ?></h1>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Main content -->
                <div>
                    <!-- Edit Form -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px;">
                        <h2>Angebotsdetails</h2>
                        <form method="POST">
                            <?php wp_nonce_field('pbqr_edit_nonce', 'nonce'); ?>
                            <input type="hidden" name="update_quote" value="1">
                            <input type="hidden" name="quote_id" value="<?php echo $id; ?>">
                            
                            <table class="form-table">
                                <tr>
                                    <th><label for="customer_first_name">Vorname</label></th>
                                    <td><input type="text" name="customer_first_name" id="customer_first_name" value="<?php echo esc_attr($quote->customer_first_name); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="customer_last_name">Nachname</label></th>
                                    <td><input type="text" name="customer_last_name" id="customer_last_name" value="<?php echo esc_attr($quote->customer_last_name); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="customer_email">E-Mail</label></th>
                                    <td><input type="email" name="customer_email" id="customer_email" value="<?php echo esc_attr($quote->customer_email); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="customer_phone">Telefon</label></th>
                                    <td><input type="tel" name="customer_phone" id="customer_phone" value="<?php echo esc_attr($quote->customer_phone); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="event_date">Veranstaltungsdatum</label></th>
                                    <td><input type="date" name="event_date" id="event_date" value="<?php echo esc_attr($quote->event_date); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="event_time">Uhrzeit</label></th>
                                    <td><input type="time" name="event_time" id="event_time" value="<?php echo esc_attr($quote->event_time); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="event_location">Ort</label></th>
                                    <td><input type="text" name="event_location" id="event_location" value="<?php echo esc_attr($quote->event_location); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="event_hours">Dauer (Stunden)</label></th>
                                    <td><input type="text" name="event_hours" id="event_hours" value="<?php echo esc_attr($quote->event_hours); ?>" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th><label for="status">Status</label></th>
                                    <td>
                                        <select name="status" id="status" style="width: 100%;">
                                            <option value="pending" <?php selected($quote->status, 'pending'); ?>>Ausstehend</option>
                                            <option value="reviewed" <?php selected($quote->status, 'reviewed'); ?>>Überprüft</option>
                                            <option value="quoted" <?php selected($quote->status, 'quoted'); ?>>Angeboten</option>
                                            <option value="converted" <?php selected($quote->status, 'converted'); ?>>Konvertiert</option>
                                            <option value="declined" <?php selected($quote->status, 'declined'); ?>>Abgelehnt</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="message">Nachricht</label></th>
                                    <td><textarea name="message" id="message" rows="5" style="width: 100%;"><?php echo esc_textarea($quote->message); ?></textarea></td>
                                </tr>
                            </table>
                            
                            <button type="submit" class="button button-primary">Änderungen speichern</button>
                        </form>
                    </div>

                    <!-- Quote Summary -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
                        <h2>Angebotszusammenfassung</h2>
                        <p>
                            <strong>Paket:</strong> <?php echo esc_html($quote->package_name); ?><br>
                            <strong>Extras:</strong> <?php echo $quote->extras_names ? esc_html($quote->extras_names) : 'Keine'; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div>
                    <!-- Quick Actions -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">⚡ Schnellaktionen</h3>
                        <?php if ($quote->status !== 'converted' && !$quote->order_id): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=pbqr_quotes&action=convert_order&id=' . $id . '&nonce=' . wp_create_nonce('pbqr_convert_nonce'))); ?>" 
                               class="button button-primary" 
                               style="width: 100%; text-align: center; margin-bottom: 10px; box-sizing: border-box;">
                                ✓ In Bestellung umwandeln
                            </a>
                            <p style="font-size: 12px; color: #666;">Dies erstellt eine WooCommerce-Bestellung aus diesem Angebot und ändert den Status zu "Konvertiert".</p>
                        <?php elseif ($quote->order_id): ?>
                            <a href="<?php echo admin_url('post.php?post=' . $quote->order_id . '&action=edit'); ?>" 
                               class="button button-primary" 
                               style="width: 100%; text-align: center; margin-bottom: 10px; box-sizing: border-box;">
                                📋 Bestellung #<?php echo $quote->order_id; ?> anzeigen
                            </a>
                            <p style="font-size: 12px; color: #666; background: #d4edda; padding: 10px; border-radius: 4px; margin-top: 10px;">
                                ✓ <strong>Bestellung erstellt</strong><br>
                                Dieses Angebot wurde in eine WooCommerce-Bestellung konvertiert.
                            </p>
                        <?php else: ?>
                            <p style="font-size: 12px; color: #666; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                                Ändern Sie den Status auf "Angeboten" oder konvertieren Sie direkt zu einer Bestellung.
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Customer Info Card -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">👤 Kundeninformationen</h3>
                        <p style="margin: 8px 0;">
                            <strong><?php echo esc_html($quote->customer_first_name . ' ' . $quote->customer_last_name); ?></strong><br>
                            <?php if ($quote->customer_company): ?>
                                <small><?php echo esc_html($quote->customer_company); ?></small><br>
                            <?php endif; ?>
                            <a href="mailto:<?php echo esc_attr($quote->customer_email); ?>"><?php echo esc_html($quote->customer_email); ?></a><br>
                            <a href="tel:<?php echo esc_attr($quote->customer_phone); ?>"><?php echo esc_html($quote->customer_phone); ?></a>
                        </p>
                    </div>
                    
                    <!-- Event Info Card -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">📅 Veranstaltungsdetails</h3>
                        <p style="margin: 8px 0; font-size: 13px;">
                            <strong><?php echo esc_html(date('d.m.Y', strtotime($quote->event_date))); ?></strong> um <strong><?php echo esc_html($quote->event_time); ?></strong><br>
                            <small><?php echo esc_html($quote->event_location); ?></small><br>
                            <small><?php echo esc_html($quote->event_hours); ?> Stunden</small>
                        </p>
                    </div>
                    
                    <!-- Notes -->
                    <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
                        <h3 style="margin-top: 0;">📝 Notizen</h3>
                        
                        <form method="POST" style="margin-bottom: 20px;">
                            <?php wp_nonce_field('pbqr_note_nonce', 'nonce'); ?>
                            <input type="hidden" name="add_note" value="1">
                            <input type="hidden" name="quote_id" value="<?php echo $id; ?>">
                            
                            <textarea name="note" 
                                      rows="3" 
                                      placeholder="Notiz hinzufügen..." 
                                      required
                                      style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                            
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="send_email" value="1">
                                Diese Notiz dem Kunden per E-Mail senden
                            </label>
                            
                            <button type="submit" class="button button-primary" style="width: 100%;">Notiz hinzufügen</button>
                        </form>
                        
                        <?php if ($notes): ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($notes as $note): ?>
                                    <div style="background: #f9f9f9; padding: 12px; margin-bottom: 10px; border-left: 3px solid #0073aa; border-radius: 4px;">
                                        <div style="font-size: 12px; color: #666; margin-bottom: 6px;">
                                            <strong><?php echo esc_html($note->display_name ? $note->display_name : 'Unbekannter Benutzer'); ?></strong> •
                                            <?php echo esc_html(date('d.m.Y H:i', strtotime($note->created_at))); ?>
                                        </div>
                                        <div style="white-space: pre-wrap; font-size: 13px;"><?php echo esc_html($note->note); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic; font-size: 13px;">Noch keine Notizen.</p>
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
        $order->set_billing_first_name($quote->customer_first_name);
        $order->set_billing_last_name($quote->customer_last_name);
        $order->set_billing_email($quote->customer_email);
        $order->set_billing_phone($quote->customer_phone);
        
        if (!empty($quote->customer_street)) {
            $order->set_billing_address_1($quote->customer_street);
        }
        if (!empty($quote->customer_postal_code)) {
            $order->set_billing_postcode($quote->customer_postal_code);
        }
        if (!empty($quote->customer_city)) {
            $order->set_billing_city($quote->customer_city);
        }
        if (!empty($quote->customer_country)) {
            $order->set_billing_country($quote->customer_country);
        }
        
        $order->set_shipping_first_name($quote->customer_first_name);
        $order->set_shipping_last_name($quote->customer_last_name);

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
                if ($extra_id > 0) {
                    $product = wc_get_product($extra_id);
                    if ($product) {
                        $order->add_product($product, 1);
                    }
                }
            }
        }

        // Add order note with quote details
        $order_note = "📋 Aus Angebotsanfrage erstellt - Angebot #" . $quote->id . "\n\n";
        $order_note .= "Veranstaltungsdetails:\n";
        $order_note .= "• Typ: " . $quote->event_type . "\n";
        $order_note .= "• Datum: " . date('d.m.Y', strtotime($quote->event_date)) . " um " . $quote->event_time . "\n";
        $order_note .= "• Ort: " . $quote->event_location . "\n";
        $order_note .= "• Dauer: " . $quote->event_hours . " Stunden\n";
        if ($quote->message) {
            $order_note .= "\nKundennachricht:\n" . $quote->message . "\n";
        }
        $order->add_order_note($order_note);

        // Set order status to pending
        $order->set_status('pending');

        // Calculate totals
        $order->calculate_totals();

        // Save order
        $order->save();
        
        return $order->get_id();
    }

    private static function send_note_email($quote, $note) {
        $site_name = get_bloginfo('name');
        $subject = 'Update zu Ihrer Angebotsanfrage - ' . $site_name;
        
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
            <h1 class=\"h1\">📝 Update zu Ihrer Anfrage</h1>
        </div>
        
        <p>Hallo " . esc_html($quote->customer_first_name) . ",</p>
        
        <p>wir haben ein Update zu Ihrer Fotobox-Angebotsanfrage für <strong>" . date('d.m.Y', strtotime($quote->event_date)) . "</strong>.</p>
        
        <div class=\"note-box\">
            <strong>Update:</strong><br><br>
            " . nl2br(esc_html($note)) . "
        </div>
        
        <p>Haben Sie Fragen? Zögern Sie nicht, sich mit uns in Verbindung zu setzen.</p>
        
        <p>Beste Grüße,<br>
        das Team von " . $site_name . "</p>
        
        <div class=\"footer\">
            <p>&copy; " . date('Y') . " $site_name. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($quote->customer_email, $subject, $body, $headers);
    }
}
