<?php

class PBQR_Activator {
    
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pbqr_quotes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_name VARCHAR(200) NOT NULL,
            customer_email VARCHAR(200) NOT NULL,
            customer_phone VARCHAR(50) NOT NULL,
            event_date DATE NOT NULL,
            event_location VARCHAR(255) NOT NULL,
            event_time VARCHAR(50) NOT NULL,
            event_hours VARCHAR(50) NOT NULL,
            package_id BIGINT(20) UNSIGNED NOT NULL,
            package_name VARCHAR(255) NOT NULL,
            extras_ids TEXT NULL,
            extras_names TEXT NULL,
            message TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
