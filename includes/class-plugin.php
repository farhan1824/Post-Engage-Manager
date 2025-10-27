<?php
require_once PEM_PLUGIN_DIR . 'includes/class-views.php';
require_once PEM_PLUGIN_DIR . 'includes/class-likes.php';
require_once PEM_PLUGIN_DIR . 'includes/class-settings.php';
require_once PEM_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once PEM_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
require_once PEM_PLUGIN_DIR . 'includes/class-widgets-reports.php';
require_once PEM_PLUGIN_DIR . 'includes/class-post-management.php';

class PEM_Plugin
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Filter to override theme featured image HTML on frontend single posts.
     * Returns empty string unless the plugin explicitly allows showing the thumbnail
     * (we set $GLOBALS['pem_allow_thumbnail'] = true when plugin code wants it).
     */
    public function filter_post_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        // Never interfere in admin screens
        if (is_admin()) {
            return $html;
        }

        // If plugin code explicitly set allow flag, return original HTML
        if (! empty($GLOBALS['pem_allow_thumbnail'])) {
            return $html;
        }

        // On single post view, suppress theme's featured image so plugin can render its own UI
        if (is_singular('post')) {
            return '';
        }

        return $html;
    }

    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_filter('the_content', array($this, 'append_view_count'));
        // Override theme featured image output on single posts (frontend)
        add_filter('post_thumbnail_html', array($this, 'filter_post_thumbnail'), 10, 5);
        PEM_Views::init();
        PEM_Likes::init();
        PEM_Settings::init();
        PEM_Shortcode::init();
        PEM_Admin_Dashboard::init();
        PEM_Widgets_Reports::init();
        PEM_Post_Management::init();
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Engagement Manager',
            'Engagement Manager',
            'manage_options',
            'post-engage-manager',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar',
            30
        );
    }

    public function render_admin_page()
    {
        $excerpt_length = PEM_Settings::get_excerpt_length();
        $animation = PEM_Settings::get_animation();
?>
        <div class="wrap pem-settings-page">
            <h1>⚡ Post Engage Manager</h1>

            <form method="post" action="options.php">
                <?php settings_fields('pem_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pem_excerpt_length">Excerpt Length</label>
                        </th>
                        <td>
                            <input type="number"
                                id="pem_excerpt_length"
                                name="pem_excerpt_length"
                                value="<?php echo esc_attr($excerpt_length); ?>"
                                min="0"
                                style="width:100px;padding:6px 10px;border-radius:6px;border:1px solid #ccc;" />
                            <p class="description">Number of words shown in post excerpts.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="pem_animation">Animation Style</label>
                        </th>
                        <td>
                            <select id="pem_animation" name="pem_animation" style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;">
                                <option value="none" <?php selected($animation, 'none'); ?>>None</option>
                                <option value="fade" <?php selected($animation, 'fade'); ?>>Fade</option>
                                <option value="slide" <?php selected($animation, 'slide'); ?>>Slide</option>
                                <option value="zoom" <?php selected($animation, 'zoom'); ?>>Zoom</option>
                            </select>
                            <p class="description">Choose how post content appears on load.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <div class="pem-footer" style="margin-top:20px;font-style:italic;">
                Made with 💜 by <a href="#">Your Plugin Team</a>
            </div>
        </div>
<?php
    }


    public function enqueue_admin_assets()
    {
        $screen = get_current_screen();

        // Main admin styles
        wp_enqueue_style('pem-admin', PEM_PLUGIN_URL . 'admin/css/admin.css', array(), PEM_VERSION);

        // Widget styles
        if ($screen->id === 'dashboard') {
            wp_enqueue_style('pem-widget', PEM_PLUGIN_URL . 'admin/css/widget.css', array(), PEM_VERSION);
            wp_enqueue_script('pem-widget', PEM_PLUGIN_URL . 'admin/js/widget.js', array('jquery'), PEM_VERSION, true);
        }

        // Main admin scripts
        wp_enqueue_script('pem-admin', PEM_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), PEM_VERSION, true);
    }

    public function enqueue_public_assets()
    {
        wp_enqueue_style('pem-public', PEM_PLUGIN_URL . 'public/css/public.css', [], PEM_VERSION);
        wp_enqueue_script('pem-public', PEM_PLUGIN_URL . 'public/js/public.js', ['jquery'], PEM_VERSION, true);
        wp_localize_script('pem-public', 'pemAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('pem_like_nonce')
        ]);
    }
    public function append_view_count($content)
    {
        if (is_singular('post')) {
            $post_id  = get_the_ID();
            $views    = PEM_Views::get_view_count($post_id);
            $likes    = PEM_Likes::get_count($post_id, 'likes');
            $dislikes = PEM_Likes::get_count($post_id, 'dislikes');

            $animation = PEM_Settings::get_animation();
            $anim_class = ($animation && 'none' !== $animation) ? 'pem-anim pem-anim-' . esc_attr($animation) : '';

            // Get featured image URL for background
            $bg_image = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'full') : '';

            // Build HTML structure
            $html  = '<div class="pem-single-post ' . esc_attr($anim_class) . '" style="position: relative; background-image: url(' . esc_url($bg_image) . '); background-size: cover; background-position: center; color: #fff; padding: 2em;">';

            // Overlay
            $html .= '<div class="pem-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);"></div>';

            // Content + stats
            $html .= '<div class="pem-content" style="position: relative; z-index: 2;">';
            $html .= '<div class="pem-post-content">' . $content . '</div>';
            $html .= '<div class="pem-post-stats" style="margin-top: 1em; font-weight: 700;">';
            $html .= 'Views: ' . intval($views) . ' • Likes: <span class="pem-total-likes">' . intval($likes) . '</span> • Dislikes: <span class="pem-total-dislikes">' . intval($dislikes) . '</span>';
            $html .= '</div>';

            // Like/Dislike buttons - FIXED: Changed class to match JS expectations
            $html .= '<div class="pem-engage-buttons" style="margin-top: 1em;">'; // Changed from pem-like-buttons

            // Check if user already voted (read from cookies)
            $liked_class = isset($_COOKIE['pem_like_' . $post_id]) ? ' active' : '';
            $disliked_class = isset($_COOKIE['pem_dislike_' . $post_id]) ? ' active' : '';

            $html .= '<button class="pem-like' . $liked_class . '" data-post="' . $post_id . '">👍 Like <span class="pem-like-count">' . intval($likes) . '</span></button> ';
            $html .= '<button class="pem-dislike' . $disliked_class . '" data-post="' . $post_id . '">👎 Dislike <span class="pem-dislike-count">' . intval($dislikes) . '</span></button>';
            $html .= '</div>'; // pem-engage-buttons

            $html .= '</div>'; // pem-content
            $html .= '</div>'; // pem-single-post

            return $html;
        }

        return $content;
    }
}
