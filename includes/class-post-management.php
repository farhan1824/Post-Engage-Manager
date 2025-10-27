<?php

/**
 * Post Management functionality for Post Engage Manager
 */
class PEM_Post_Management
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_submenu_page']);
    }

    public static function add_submenu_page()
    {
        add_submenu_page(
            'post-engage-manager',
            'Post Management',
            'Post Management',
            'manage_options',
            'post-engage-manager-posts',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page()
    {
        // Get posts with engagement data
        $posts = self::get_posts_with_stats();
?>
        <div class="wrap pem-post-management">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Modern Card Grid Layout -->
            <div class="pem-post-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="pem-post-card">
                        <div class="pem-post-thumbnail">
                            <?php
                            if (has_post_thumbnail($post->ID)) {
                                echo get_the_post_thumbnail($post->ID, 'medium');
                            } else {
                                echo '<div class="pem-no-thumbnail"><span class="dashicons dashicons-format-image"></span></div>';
                            }
                            ?>
                        </div>
                        <div class="pem-post-content">
                            <h3><?php echo esc_html($post->post_title); ?></h3>
                            <div class="pem-post-meta">
                                <span class="pem-views">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo number_format($post->views); ?>
                                </span>
                                <span class="pem-likes">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                    <?php echo number_format($post->likes); ?>
                                </span>
                                <span class="pem-dislikes">
                                    <span class="dashicons dashicons-thumbs-down"></span>
                                    <?php echo number_format($post->dislikes); ?>
                                </span>
                            </div>
                            <div class="pem-post-actions">
                                <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button">
                                    <span class="dashicons dashicons-edit"></span>
                                    Edit
                                </a>
                                <a href="<?php echo get_permalink($post->ID); ?>" class="button" target="_blank">
                                    <span class="dashicons dashicons-external"></span>
                                    View
                                </a>
                                <button class="button pem-copy-shortcode" data-post-id="<?php echo $post->ID; ?>">
                                    <span class="dashicons dashicons-shortcode"></span>
                                    Copy Shortcode
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Shortcode Guide Sidebar -->
            <div class="pem-shortcode-guide">
                <div class="pem-sidebar-card">
                    <h3><span class="dashicons dashicons-info"></span> Shortcode Guide</h3>
                    <div class="pem-guide-content">
                        <p>Use these shortcodes to display post engagement elements:</p>
                        <div class="pem-shortcode-item">
                            <code>[pem_post id="X"]</code>
                            <p>Displays a post with engagement stats</p>
                            <h4>Parameters:</h4>
                            <ul>
                                <li><strong>id</strong> - Post ID (required)</li>
                                <li><strong>show_views</strong> - true/false (optional)</li>
                                <li><strong>show_likes</strong> - true/false (optional)</li>
                                <li><strong>animation</strong> - fade/slide/zoom (optional)</li>
                            </ul>
                        </div>
                        <div class="pem-shortcode-example">
                            <h4>Example:</h4>
                            <code>[pem_post id="123" show_views="true" animation="fade"]</code>
                        </div>
                        <div class="pem-pro-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <p>Pro Tip: Click the "Copy Shortcode" button on any post card to quickly get its shortcode.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


<?php
    }

    private static function get_posts_with_stats()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'post_engage_stats';

        return $wpdb->get_results("
            SELECT 
                p.*,
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
