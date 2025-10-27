=== Post Engage Manager ===
Contributors: Farhan Ahmed
Tags: views, likes, dislikes, engagement, analytics, admin, dashboard, posts
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Post Engage Manager tracks post views, likes, and dislikes while providing a modern admin UI for analytics and engagement management.

== Description ==
Post Engage Manager is a lightweight and powerful WordPress plugin designed to help website owners monitor and enhance engagement on their posts. It tracks views, likes, and dislikes automatically, provides a modern admin dashboard with analytics, and adds AJAX-powered like/dislike buttons for instant front-end updates.

=== Key Features ===
* Track post views, likes, and dislikes automatically.
* Display a modern admin dashboard with charts and tables for post engagement analytics.
* AJAX-powered like and dislike buttons for instant updates without page reloads.
* Override default WordPress post display with featured image backgrounds and overlayed stats/content.
* Customizable animations and excerpt lengths for post displays.
* Export engagement data to CSV for reporting.
* Fully compatible with WordPress 5.x and 6.x.
* Lightweight and optimized for performance.
* Fully translatable using WordPress localization standards.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/post-engage-manager` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure settings from the **Post Engage Manager** menu in the WordPress admin dashboard.
4. Use the shortcode `[pem_post id=POST_ID length=20 animation=fade]` to display posts with engagement stats on the front-end.

== Frequently Asked Questions ==

= How do I display the engagement stats on the front-end? =
Use the shortcode `[pem_post id=POST_ID length=20 animation=fade]` in any post, page, or widget area. Replace `POST_ID` with the ID of the post you want to display.

= Can I customize the animation or excerpt length? =
Yes, you can set the default animation and excerpt length in the plugin settings. Shortcode attributes override these defaults.

= Will it work with any WordPress theme? =
Yes, the plugin is designed to be compatible with most modern WordPress themes. Some CSS adjustments may be needed for full styling integration.

== Screenshots ==
1. Modern admin dashboard showing engagement analytics.
2. Top posts chart by views, likes, and engagement.
3. AJAX-powered like and dislike buttons in action on the front-end.

== Changelog ==
= 1.0.0 =
* Initial release
* Tracks post views, likes, dislikes
* Admin dashboard with analytics charts
* AJAX-powered like/dislike buttons
* Customizable post display with animation and excerpt length
* Export stats to CSV

== Upgrade Notice ==
= 1.0.0 =
Initial release. No previous versions.

== Additional Notes ==
- Ensure your theme properly supports featured images for full functionality.
- Use caching plugins carefully; ensure AJAX requests are not cached to maintain real-time updates.
