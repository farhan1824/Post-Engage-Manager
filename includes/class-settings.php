<?php

/**
 * Settings handler for Post Engage Manager
 */
class PEM_Settings
{
    public static function init()
    {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    public static function register_settings()
    {
        register_setting('pem_settings', 'pem_excerpt_length', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 55,
        ));

        register_setting('pem_settings', 'pem_animation', array(
            'type'              => 'string',
            'sanitize_callback' => array(__CLASS__, 'sanitize_animation'),
            'default'           => 'none',
        ));
    }

    public static function sanitize_animation($val)
    {
        $allowed = array('none', 'fade', 'slide', 'zoom');
        return in_array($val, $allowed, true) ? $val : 'none';
    }

    public static function get_excerpt_length()
    {
        $v = get_option('pem_excerpt_length');
        return $v ? intval($v) : 55;
    }

    public static function get_animation()
    {
        $v = get_option('pem_animation');
        return $v ? $v : 'none';
    }
}
