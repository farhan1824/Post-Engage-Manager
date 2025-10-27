<?php
class PEM_Likes
{
    public static function init()
    {
        add_action('wp_ajax_pem_like_post', [__CLASS__, 'like_post']);
        add_action('wp_ajax_nopriv_pem_like_post', [__CLASS__, 'like_post']);
        add_action('wp_ajax_pem_dislike_post', [__CLASS__, 'dislike_post']);
        add_action('wp_ajax_nopriv_pem_dislike_post', [__CLASS__, 'dislike_post']);
    }

    public static function like_post()
    {
        // check_ajax_referer('pem_like_nonce', 'nonce');
        if (is_user_logged_in()) {
            check_ajax_referer('pem_like_nonce', 'nonce');
        }
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
            return;
        }

        // Check if already voted (server-side validation)
        if (self::already_voted($post_id, 'like')) {
            wp_send_json_error('Already liked');
            return;
        }

        // If user previously disliked, remove that dislike first
        if (self::already_voted($post_id, 'dislike')) {
            self::decrease_count($post_id, 'dislikes');
            self::clear_voted($post_id, 'dislike');
        }

        // Ensure row exists and increase like count
        self::increase_count($post_id, 'likes');
        self::set_voted($post_id, 'like');

        // Get updated counts
        $likes = self::get_count($post_id, 'likes');
        $dislikes = self::get_count($post_id, 'dislikes');

        wp_send_json_success([
            'likes'    => $likes,
            'dislikes' => $dislikes,
            'voted'    => 'like',
            'message'  => 'Post liked successfully'
        ]);
    }

    public static function dislike_post()
    {
        // check_ajax_referer('pem_like_nonce', 'nonce');
        if (is_user_logged_in()) {
            check_ajax_referer('pem_like_nonce', 'nonce');
        }
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
            return;
        }

        // Check if already voted (server-side validation)
        if (self::already_voted($post_id, 'dislike')) {
            wp_send_json_error('Already disliked');
            return;
        }

        // If user previously liked, remove that like first
        if (self::already_voted($post_id, 'like')) {
            self::decrease_count($post_id, 'likes');
            self::clear_voted($post_id, 'like');
        }

        // Ensure row exists and increase dislike count
        self::increase_count($post_id, 'dislikes');
        self::set_voted($post_id, 'dislike');

        // Get updated counts
        $likes = self::get_count($post_id, 'likes');
        $dislikes = self::get_count($post_id, 'dislikes');

        wp_send_json_success([
            'likes'    => $likes,
            'dislikes' => $dislikes,
            'voted'    => 'dislike',
            'message'  => 'Post disliked successfully'
        ]);
    }
    // public static function like_post()
    // {
    //     if (is_user_logged_in()) {
    //         check_ajax_referer('pem_like_nonce', 'nonce');
    //     }

    //     $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    //     if (!$post_id) wp_send_json_error('Invalid post ID');

    //     $cookie_like = 'pem_like_' . $post_id;
    //     $cookie_dislike = 'pem_dislike_' . $post_id;

    //     if (isset($_COOKIE[$cookie_like])) {
    //         wp_send_json_error('Already liked');
    //     }

    //     global $wpdb;
    //     $table = $wpdb->prefix . 'post_engage_stats';

    //     // Create summary row if not exists
    //     $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d AND user_id IS NULL", $post_id));
    //     if ($row) {
    //         $wpdb->query($wpdb->prepare("UPDATE $table SET likes = likes + 1 WHERE post_id = %d AND user_id IS NULL", $post_id));
    //     } else {
    //         $wpdb->insert($table, [
    //             'post_id' => $post_id,
    //             'user_id' => NULL,
    //             'views' => 0,
    //             'likes' => 1,
    //             'dislikes' => 0
    //         ]);
    //     }

    //     // Remove previous dislike
    //     if (isset($_COOKIE[$cookie_dislike])) {
    //         $wpdb->query($wpdb->prepare("UPDATE $table SET dislikes = GREATEST(dislikes - 1, 0) WHERE post_id = %d AND user_id IS NULL", $post_id));
    //         setcookie($cookie_dislike, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    //     }

    //     // Set like cookie
    //     setcookie($cookie_like, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN);

    //     $likes = $wpdb->get_var($wpdb->prepare("SELECT likes FROM $table WHERE post_id = %d AND user_id IS NULL", $post_id));
    //     $dislikes = $wpdb->get_var($wpdb->prepare("SELECT dislikes FROM $table WHERE post_id = %d AND user_id IS NULL", $post_id));

    //     wp_send_json_success([
    //         'likes' => (int)$likes,
    //         'dislikes' => (int)$dislikes,
    //         'voted' => 'like'
    //     ]);
    // }

    // public static function dislike_post()
    // {
    //     if (is_user_logged_in()) {
    //         check_ajax_referer('pem_like_nonce', 'nonce');
    //     }

    //     $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    //     if (!$post_id) wp_send_json_error('Invalid post ID');

    //     $cookie_like = 'pem_like_' . $post_id;
    //     $cookie_dislike = 'pem_dislike_' . $post_id;

    //     if (isset($_COOKIE[$cookie_dislike])) {
    //         wp_send_json_error('Already disliked');
    //     }

    //     global $wpdb;
    //     $table = $wpdb->prefix . 'post_engage_stats';

    //     // Create summary row if not exists
    //     $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d AND user_id IS NULL", $post_id));
    //     if ($row) {
    //         $wpdb->query($wpdb->prepare("UPDATE $table SET dislikes = dislikes + 1 WHERE post_id = %d AND user_id IS NULL", $post_id));
    //     } else {
    //         $wpdb->insert($table, [
    //             'post_id' => $post_id,
    //             'user_id' => NULL,
    //             'views' => 0,
    //             'likes' => 0,
    //             'dislikes' => 1
    //         ]);
    //     }

    //     // Remove previous like
    //     if (isset($_COOKIE[$cookie_like])) {
    //         $wpdb->query($wpdb->prepare("UPDATE $table SET likes = GREATEST(likes - 1, 0) WHERE post_id = %d AND user_id IS NULL", $post_id));
    //         setcookie($cookie_like, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    //     }

    //     // Set dislike cookie
    //     setcookie($cookie_dislike, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN);

    //     $likes = $wpdb->get_var($wpdb->prepare("SELECT likes FROM $table WHERE post_id = %d AND user_id IS NULL", $post_id));
    //     $dislikes = $wpdb->get_var($wpdb->prepare("SELECT dislikes FROM $table WHERE post_id = %d AND user_id IS NULL", $post_id));

    //     wp_send_json_success([
    //         'likes' => (int)$likes,
    //         'dislikes' => (int)$dislikes,
    //         'voted' => 'dislike'
    //     ]);
    // }

    private static function increase_count($post_id, $type)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        // Ensure a row exists for this post
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", $post_id));

        if ($row) {
            // Update existing row
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET $type = $type + 1 WHERE post_id = %d",
                $post_id
            ));
        } else {
            // Insert new row
            $data = [
                'post_id' => $post_id,
                'views' => 0,
                'likes' => 0,
                'dislikes' => 0
            ];
            $data[$type] = 1;
            $wpdb->insert($table, $data);
        }
    }

    private static function decrease_count($post_id, $type)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        $current = self::get_count($post_id, $type);
        if ($current > 0) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET $type = %d WHERE post_id = %d",
                $current - 1,
                $post_id
            ));
        }
    }

    // Expose get_count publicly so other parts of the plugin can read counts
    public static function get_count($post_id, $type)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT $type FROM $table WHERE post_id = %d",
            $post_id
        ));
        return $result !== null ? (int) $result : 0;
    }

    // private static function already_voted($post_id, $type)
    // {
    //     $cookie = 'pem_' . $type . '_' . $post_id;
    //     return isset($_COOKIE[$cookie]);
    // }
    private static function already_voted($post_id, $type)
    {
        if (is_user_logged_in()) {
            // For logged-in users, check if user already voted
            $cookie = 'pem_' . $type . '_' . $post_id;
            return isset($_COOKIE[$cookie]);
        } else {
            // For anonymous, ignore cookie, let the DB handle counts
            return false;
        }
    }

    private static function set_voted($post_id, $type)
    {
        $cookie = 'pem_' . $type . '_' . $post_id;
        setcookie($cookie, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
    }

    private static function clear_voted($post_id, $type)
    {
        $cookie = 'pem_' . $type . '_' . $post_id;
        // Remove cookie by setting expiry in the past
        setcookie($cookie, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}
