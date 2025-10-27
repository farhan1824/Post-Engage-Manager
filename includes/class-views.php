<?php
class PEM_Views
{
    public static function init()
    {
        add_action('template_redirect', array(__CLASS__, 'track_view'));
    }

    public static function track_view()
    {
        if (! is_singular('post')) return;

        $post_id = get_queried_object_id();
        if (! $post_id) return;

        // Prevent duplicate counts per user (cookie-based)
        $cookie_name = 'pem_viewed_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) return;

        self::increase_view_count($post_id);
        setcookie($cookie_name, '1', time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
    }

    public static function increase_view_count($post_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", $post_id));
        if ($row) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET views = views + 1 WHERE post_id = %d", $post_id));
        } else {
            $wpdb->insert($table, array('post_id' => $post_id, 'views' => 1));
        }
    }

    public static function get_view_count($post_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';
        return (int) $wpdb->get_var($wpdb->prepare("SELECT views FROM $table WHERE post_id = %d", $post_id));
    }
}
