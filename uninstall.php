<?php
if (! defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;
$table = $wpdb->prefix . 'post_engage_stats';
$wpdb->query("DROP TABLE IF EXISTS $table");
