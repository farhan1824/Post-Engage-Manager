<?php

/**
 * Admin Dashboard functionality for Post Engage Manager
 */
class PEM_Admin_Dashboard
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        // Add AJAX handlers for chart data updates
        add_action('wp_ajax_pem_get_engagement_data', [__CLASS__, 'ajax_get_engagement_data']);
        // AJAX handler for filters
        add_action('wp_ajax_pem_filter_data', [__CLASS__, 'ajax_filter_data']);
    }

    /**
     * AJAX handler for getting filtered engagement data
     */
    public static function ajax_get_engagement_data()
    {
        check_ajax_referer('pem_admin_nonce', 'nonce');

        $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
        $sort_by = isset($_GET['sort_by']) ? sanitize_key($_GET['sort_by']) : 'views';

        // Get stats for the selected date range
        $stats = self::get_engagement_stats($days);

        // Get and sort top posts
        $top_posts = self::get_top_posts(10, $sort_by);

        wp_send_json_success([
            'stats' => array_map(function ($stat) {
                return [
                    'date' => $stat->date,
                    'views' => absint($stat->views),
                    'likes' => absint($stat->likes),
                    'dislikes' => absint($stat->dislikes)
                ];
            }, $stats),
            'topPosts' => array_map(function ($post) {
                return [
                    'id' => absint($post->ID),
                    'title' => $post->post_title,
                    'views' => absint($post->views),
                    'likes' => absint($post->likes),
                    'dislikes' => absint($post->dislikes)
                ];
            }, $top_posts)
        ]);
    }

    public static function add_menu_page()
    {
        add_submenu_page(
            'post-engage-manager', // Parent slug
            'Engagement Analytics', // Page title
            'Engagement Analytics', // Menu title
            'manage_options',  // Capability
            'engagement-analytics', // Menu slug
            [__CLASS__, 'render_dashboard'] // Callback
        );
    }

    public static function enqueue_scripts($hook)
    {
        // Debug hook name
        error_log('PEM Admin: Current page hook is: ' . $hook);

        // Only load on our analytics page
        if ($hook !== 'post-engage-manager_page_engagement-analytics') {
            // return;
            error_log('Current admin hook: ' . $hook);
        }

        // Remove error handlers
        remove_action('admin_notices', '_doing_it_wrong_hook');
        remove_action('admin_notices', 'doing_it_wrong_trigger_error');

        // Get plugin admin URL
        $admin_url = plugin_dir_url(__FILE__);

        // Styles first
        wp_enqueue_style(
            'pem-admin',
            $admin_url . 'css/admin.css',
            [],
            PEM_VERSION
        );

        // Then Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            ['jquery'],
            '4.4.0',
            false // Load in header to ensure it's available
        );

        // Finally admin JS
        wp_enqueue_script(
            'pem-admin',
            $admin_url . 'js/admin.js',
            ['jquery', 'chartjs'],
            PEM_VERSION,
            true
        );

        // Get data for charts
        $engagement_stats = self::get_engagement_stats();
        $top_posts = self::get_top_posts();

        // Debug data
        error_log('PEM Admin: Engagement stats count: ' . count($engagement_stats));
        error_log('PEM Admin: Top posts count: ' . count($top_posts));

        // Ensure we have arrays
        $engagement_stats = is_array($engagement_stats) ? $engagement_stats : array();
        $top_posts = is_array($top_posts) ? $top_posts : array();

        // Localize script with clean data
        wp_localize_script('pem-admin', 'pemAdmin', array(
            'nonce'    => wp_create_nonce('pem_admin_nonce'),
            'ajaxurl'  => admin_url('admin-ajax.php'),

            // Clean stats data for charts
            'stats'    => array_map(function ($stat) {
                return array(
                    'date'     => $stat->date,
                    'views'    => absint($stat->views),
                    'likes'    => absint($stat->likes),
                    'dislikes' => absint($stat->dislikes)
                );
            }, $engagement_stats),

            // Clean post data for charts
            'topPosts' => array_map(function ($post) {
                return array(
                    'id'       => absint($post->ID),
                    'title'    => $post->post_title,
                    'views'    => absint($post->views),
                    'likes'    => absint($post->likes),
                    'dislikes' => absint($post->dislikes)
                );
            }, $top_posts)
        ));
    }
    public static function ajax_filter_data()
    {
        check_ajax_referer('pem_admin_nonce', 'nonce');

        $days = isset($_POST['days']) ? sanitize_text_field($_POST['days']) : '30';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'views';

        $stats = self::get_engagement_stats($days);
        $posts = self::get_top_posts(10, $sort);

        wp_send_json_success([
            'stats' => $stats,
            'topPosts' => $posts,
        ]);
    }
    public static function render_dashboard()
    {
        // Default values (these match your select defaults)
        $default_days = 30;
        $default_sort = 'views';

        // Get stats and posts for initial load
        $stats = self::get_engagement_stats($default_days);
        $top_posts = self::get_top_posts(10, $default_sort);
?>
        <div class="wrap pem-dashboard">
            <h1>Engagement Analytics</h1>

            <!-- Hidden fields to track default state for JS -->
            <input type="hidden" id="pem-default-days" value="<?php echo esc_attr($default_days); ?>">
            <input type="hidden" id="pem-default-sort" value="<?php echo esc_attr($default_sort); ?>">

            <!-- Actions Bar -->
            <div class="pem-actions-bar">
                <div class="pem-filters">
                    <select id="pem-date-range">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="all">All Time</option>
                    </select>
                    <select id="pem-sort-by">
                        <option value="views" selected>Sort by Views</option>
                        <option value="likes">Sort by Likes</option>
                        <option value="engagement">Sort by Engagement</option>
                    </select>
                </div>
                <div class="pem-actions">
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=pem_export_stats'), 'pem_export_stats'); ?>"
                        class="button">
                        <span class="dashicons dashicons-download"></span>
                        Export to CSV
                    </a>
                    <button id="pem-refresh-data" class="button">
                        <span class="dashicons dashicons-update"></span>
                        Refresh Data
                    </button>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="pem-overview">
                <div class="pem-card">
                    <h3>Total Views</h3>
                    <div class="pem-stat" id="total-views">
                        <?php echo number_format(self::get_total_views()); ?>
                    </div>
                </div>
                <div class="pem-card">
                    <h3>Total Likes</h3>
                    <div class="pem-stat" id="total-likes">
                        <?php echo number_format(self::get_total_likes()); ?>
                    </div>
                </div>
                <div class="pem-card">
                    <h3>Engagement Rate</h3>
                    <div class="pem-stat" id="engagement-rate">
                        <?php echo self::get_engagement_rate(); ?>%
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="pem-charts">
                <div class="pem-card">
                    <h3>Engagement Over Time</h3>
                    <canvas id="engagement-chart"
                        data-stats='<?php echo esc_attr(wp_json_encode($stats)); ?>'>
                    </canvas>
                </div>
                <div class="pem-card">
                    <h3>Top Posts by Engagement</h3>
                    <canvas id="top-posts-chart"
                        data-posts='<?php echo esc_attr(wp_json_encode($top_posts)); ?>'>
                    </canvas>
                </div>
            </div>

            <!-- Analytics Table -->
            <div class="pem-card">
                <h3>Most Engaged Posts</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Post Title</th>
                            <th>Views</th>
                            <th>Likes</th>
                            <th>Dislikes</th>
                            <th>Engagement Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_posts as $post): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_permalink($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($post->views); ?></td>
                                <td><?php echo number_format($post->likes); ?></td>
                                <td><?php echo number_format($post->dislikes); ?></td>
                                <td><?php echo $post->views > 0 ? round(($post->likes + $post->dislikes) / $post->views * 100, 1) . '%' : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php
    }


    private static function get_engagement_stats($days = 30)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        // Handle "all time" case
        $date_clause = $days > 0
            ? "AND p.post_date >= DATE_SUB(NOW(), INTERVAL {$days} DAY)"
            : "";

        // Get daily stats for the specified range
        $stats = $wpdb->get_results("
            SELECT 
                DATE(p.post_date) as date,
                SUM(s.views) as views,
                SUM(s.likes) as likes,
                SUM(s.dislikes) as dislikes
            FROM {$wpdb->posts} p
            LEFT JOIN {$table} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            {$date_clause}
            GROUP BY DATE(p.post_date)
            ORDER BY date ASC
        ");

        return $stats;
    }

    private static function get_top_posts($limit = 10, $sort_by = 'views')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        // Determine sort column and calculate engagement if needed
        $order_clause = 'COALESCE(s.views, 0) DESC';
        switch ($sort_by) {
            case 'likes':
                $order_clause = 'COALESCE(s.likes, 0) DESC';
                break;
            case 'engagement':
                $order_clause = '(COALESCE(s.likes, 0) + COALESCE(s.dislikes, 0)) DESC';
                break;
        }

        $posts = $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title,
                COALESCE(s.views, 0) as views,
                COALESCE(s.likes, 0) as likes,
                COALESCE(s.dislikes, 0) as dislikes
            FROM {$wpdb->posts} p
            LEFT JOIN {$table} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            ORDER BY {$order_clause}, p.post_date DESC
            LIMIT {$limit}
        ");

        // Ensure we have valid data objects
        return array_map(function ($post) {
            $post->views = (int)$post->views;
            $post->likes = (int)$post->likes;
            $post->dislikes = (int)$post->dislikes;
            return $post;
        }, $posts);
    }

    private static function get_total_views()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';
        return (int) $wpdb->get_var("SELECT SUM(views) FROM {$table}");
    }

    private static function get_total_likes()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';
        return (int) $wpdb->get_var("SELECT SUM(likes) FROM {$table}");
    }

    private static function get_engagement_rate()
    {
        $views = self::get_total_views();
        if (!$views) return 0;

        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';
        $interactions = (int) $wpdb->get_var("SELECT SUM(likes) + SUM(dislikes) FROM {$table}");

        return round(($interactions / $views) * 100, 1);
    }

    private static function get_posts_with_engagement()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        return $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title,
                p.post_excerpt,
                COALESCE(s.views, 0) as views,
                COALESCE(s.likes, 0) as likes,
                COALESCE(s.dislikes, 0) as dislikes
            FROM {$wpdb->posts} p
            LEFT JOIN {$table} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
        ");
    }
}
