<?php
/*
Plugin Name: Post Engage Manager
Plugin URI:  https://yourwebsite.com/post-engage-manager
Description: Post Engage Manager is a comprehensive WordPress plugin that tracks and displays post engagement, including views, likes, and dislikes. It provides a modern admin dashboard for analytics, customizable post display, and AJAX-powered like/dislike buttons for instant updates without page reloads.
Version: 1.0.0
Author: Farhan Ahmed
Author URI: https://yourwebsite.com
Text Domain: post-engage-manager
Domain Path: /languages
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.4
Requires at least: 6.0
Tags: post, engagement, analytics, likes, dislikes, ajax, dashboard, statistics
*/

/*
=== Features ===
1. Track post views, likes, and dislikes automatically.
2. Display a modern admin dashboard with charts and tables for post engagement analytics.
3. AJAX-powered like and dislike buttons for instant updates on the front-end.
4. Option to override default WordPress post display with featured image background and overlayed stats/content.
5. Customizable animations and excerpt length for post displays.
6. Export engagement data to CSV for reports.
7. Fully compatible with WordPress 6.x+ and modern themes.
8. Lightweight and optimized for speed.
9. Fully translatable using WordPress localization standards.
*/


if (! defined('ABSPATH')) exit;

define('PEM_VERSION', '1.0.0');
define('PEM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PEM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PEM_PLUGIN_DIR . 'includes/class-activator.php';
require_once PEM_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once PEM_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, array('PEM_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('PEM_Deactivator', 'deactivate'));

// Bootstrap plugin
PEM_Plugin::get_instance();
