<?php
class PEM_Activator
{
    public static function activate()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            views bigint(20) DEFAULT 0,
            likes bigint(20) DEFAULT 0,
            dislikes bigint(20) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
