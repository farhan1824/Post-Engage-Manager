<?php

/**
 * Shortcode handler for Post Engage Manager
 * Usage: [pem_post id=123 length=20 animation=fade]
 */
class PEM_Shortcode
{
    public static function init()
    {
        add_shortcode('pem_post', array(__CLASS__, 'render_post'));
    }

    public static function render_post($atts)
    {
        $atts = shortcode_atts(array(
            'id'        => 0,
            'length'    => 0,
            'animation' => '',
        ), $atts, 'pem_post');

        $post_id = intval($atts['id']);
        if (! $post_id) {
            return '';
        }

        $post = get_post($post_id);
        if (! $post) {
            return '';
        }

        $excerpt_len = intval($atts['length']) > 0 ? intval($atts['length']) : PEM_Settings::get_excerpt_length();
        $animation = $atts['animation'] ? $atts['animation'] : PEM_Settings::get_animation();
        $anim_class = $animation && 'none' !== $animation ? 'pem-anim pem-anim-' . esc_attr($animation) : '';

    // Allow plugin to fetch the thumbnail HTML even though we suppress theme thumbnails on single posts
    $GLOBALS['pem_allow_thumbnail'] = true;
    $thumb = get_the_post_thumbnail($post_id, 'post-thumb');
    unset($GLOBALS['pem_allow_thumbnail']);
        $excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words(wp_strip_all_tags($post->post_content), $excerpt_len);

        $views = method_exists('PEM_Views', 'get_view_count') ? PEM_Views::get_view_count($post_id) : 0;
        $likes = method_exists('PEM_Likes', 'get_count') ? PEM_Likes::get_count($post_id, 'likes') : 0;
        $dislikes = method_exists('PEM_Likes', 'get_count') ? PEM_Likes::get_count($post_id, 'dislikes') : 0;

        ob_start();
?>
        <div class="pem-shortcode <?php echo esc_attr($anim_class); ?>">
            <h2 class="pem-shortcode-title"><a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html($post->post_title); ?></a></h2>
            <div class="pem-shortcode-thumb"><?php echo $thumb; ?></div>
            <div class="pem-shortcode-excerpt"><?php echo wp_kses_post(wpautop($excerpt)); ?></div>
            <div class="pem-shortcode-stats">Views: <?php echo intval($views); ?> • Likes: <?php echo intval($likes); ?> • Dislikes: <?php echo intval($dislikes); ?></div>
        </div>
<?php
        return ob_get_clean();
    }
}
