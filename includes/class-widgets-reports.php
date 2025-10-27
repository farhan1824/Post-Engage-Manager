<?php

/**
 * Widgets and Reports functionality for Post Engage Manager
 */
class PEM_Widgets_Reports
{

    public static function init()
    {
        // Initialize dashboard widget
        add_action('wp_dashboard_setup', [__CLASS__, 'add_dashboard_widget']);

        // Add export functionality
        add_action('admin_post_pem_export_stats', [__CLASS__, 'handle_export']);

        // Add AJAX handlers for widget refresh
        add_action('wp_ajax_pem_refresh_widget', [__CLASS__, 'get_widget_data']);
    }

    /**
     * Add the dashboard widget
     */
    public static function add_dashboard_widget()
    {
        wp_add_dashboard_widget(
            'pem_stats_widget',
            'Post Engagement Overview',
            [__CLASS__, 'render_dashboard_widget'],
            null,
            null,
            'normal',
            'high'
        );
    }

    /**
     * Render the dashboard widget content
     */
    public static function render_dashboard_widget()
    {
        $stats = self::get_quick_stats();
?>
        <div class="pem-widget-content">
            <div class="pem-widget-stats">
                <div class="pem-widget-stat">
                    <span class="pem-widget-label">Total Views</span>
                    <span class="pem-widget-value"><?php echo number_format($stats['total_views']); ?></span>
                </div>
                <div class="pem-widget-stat">
                    <span class="pem-widget-label">Today's Views</span>
                    <span class="pem-widget-value"><?php echo number_format($stats['today_views']); ?></span>
                </div>
                <div class="pem-widget-stat">
                    <span class="pem-widget-label">Engagement Rate</span>
                    <span class="pem-widget-value"><?php echo $stats['engagement_rate']; ?>%</span>
                </div>
            </div>

            <h4>Top Performing Posts (Last 7 Days)</h4>
            <table class="pem-widget-table">
                <tr>
                    <th>Post</th>
                    <th>Views</th>
                    <th>Likes</th>
                </tr>
                <?php foreach ($stats['top_posts'] as $post): ?>
                    <tr>
                        <td><a href="<?php echo get_permalink($post->ID); ?>"><?php echo wp_trim_words($post->post_title, 5); ?></a></td>
                        <td><?php echo number_format($post->views); ?></td>
                        <td><?php echo number_format($post->likes); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="pem-widget-footer">
                <a href="<?php echo admin_url('admin.php?page=post-engage-manager'); ?>" class="button">View Full Report</a>
                <button class="button pem-refresh-widget" data-nonce="<?php echo wp_create_nonce('pem_refresh_widget'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
        </div>
<?php
    }

    /**
     * Get quick stats for the dashboard widget
     */
    private static function get_quick_stats()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        // Get total views
        $total_views = (int) $wpdb->get_var("SELECT SUM(views) FROM {$table}");

        // Get today's views
        $today_views = (int) $wpdb->get_var($wpdb->prepare("
            SELECT SUM(s.views)
            FROM {$table} s
            JOIN {$wpdb->posts} p ON p.ID = s.post_id
            WHERE DATE(p.post_date) = CURDATE()
        "));

        // Calculate engagement rate
        $total_interactions = (int) $wpdb->get_var("
            SELECT SUM(likes) + SUM(dislikes) 
            FROM {$table}
        ");
        $engagement_rate = $total_views > 0 ? round(($total_interactions / $total_views) * 100, 1) : 0;

        // Get top posts from last 7 days
        $top_posts = $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title,
                COALESCE(s.views, 0) as views,
                COALESCE(s.likes, 0) as likes
            FROM {$wpdb->posts} p
            LEFT JOIN {$table} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY s.views DESC
            LIMIT 5
        ");

        return [
            'total_views' => $total_views,
            'today_views' => $today_views,
            'engagement_rate' => $engagement_rate,
            'top_posts' => $top_posts
        ];
    }

    /**
     * Handle the export functionality
     */
    public static function handle_export()
    {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'pem_export_stats')) {
            wp_die('Invalid export request');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Get the data
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        $results = $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                COALESCE(s.views, 0) as views,
                COALESCE(s.likes, 0) as likes,
                COALESCE(s.dislikes, 0) as dislikes
            FROM {$wpdb->posts} p
            LEFT JOIN {$table} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
        ");

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=post-engagement-stats.csv');
        header('Cache-Control: max-age=0');

        // Create CSV file
        $fp = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($fp, ['Post ID', 'Title', 'Date', 'Views', 'Likes', 'Dislikes', 'Engagement Rate']);

        // Add data rows
        foreach ($results as $row) {
            $engagement_rate = $row->views > 0 ?
                round((($row->likes + $row->dislikes) / $row->views) * 100, 1) : 0;

            fputcsv($fp, [
                $row->ID,
                $row->post_title,
                $row->post_date,
                $row->views,
                $row->likes,
                $row->dislikes,
                $engagement_rate . '%'
            ]);
        }

        fclose($fp);
        exit;
    }

    /**
     * AJAX handler for widget refresh
     */
    public static function get_widget_data()
    {
        check_ajax_referer('pem_refresh_widget', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        ob_start();
        self::render_dashboard_widget();
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
}
