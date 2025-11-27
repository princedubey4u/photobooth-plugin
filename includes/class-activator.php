<?php

class PBQR_Activator {
    
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Quotes table
        $quotes_table = $wpdb->prefix . 'pbqr_quotes';
        $sql1 = "CREATE TABLE IF NOT EXISTS $quotes_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_name VARCHAR(200) NOT NULL,
            customer_first_name VARCHAR(100) NOT NULL,
            customer_last_name VARCHAR(100) NOT NULL,
            customer_company VARCHAR(200) NULL,
            customer_email VARCHAR(200) NOT NULL,
            customer_phone VARCHAR(50) NOT NULL,
            customer_street VARCHAR(255) NULL,
            customer_postal_code VARCHAR(20) NULL,
            customer_city VARCHAR(100) NULL,
            customer_country VARCHAR(100) NULL,
            event_type VARCHAR(100) NULL,
            event_date DATE NOT NULL,
            event_location VARCHAR(255) NOT NULL,
            event_time VARCHAR(50) NOT NULL,
            event_hours VARCHAR(50) NOT NULL,
            package_id BIGINT(20) UNSIGNED NOT NULL,
            package_name VARCHAR(255) NOT NULL,
            extras_ids TEXT NULL,
            extras_names TEXT NULL,
            message TEXT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            order_id BIGINT(20) UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY event_date (event_date),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Blocked dates table
        $blocked_dates_table = $wpdb->prefix . 'pbqr_blocked_dates';
        $sql2 = "CREATE TABLE IF NOT EXISTS $blocked_dates_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            blocked_date DATE NOT NULL,
            reason VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY blocked_date (blocked_date)
        ) $charset_collate;";

        // Quote notes table
        $notes_table = $wpdb->prefix . 'pbqr_quote_notes';
        $sql3 = "CREATE TABLE IF NOT EXISTS $notes_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quote_id BIGINT(20) UNSIGNED NOT NULL,
            note TEXT NOT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY quote_id (quote_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
    }
}
