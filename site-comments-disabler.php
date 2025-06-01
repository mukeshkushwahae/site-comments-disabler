<?php
/**
 * Plugin Name: Site Comments Disabler
 * Plugin URI: https://github.com/mukeshkushwahae/site-comments-disabler
 * Description: Fully disables all comment-related features across the WordPress site. This includes frontend comment areas, admin menus, discussion settings, feeds, and all support in post types. Works globally and silently using MU plugin architecture.
 * Version: 1.0.0
 * Author: Mukesh Kushwaha
 * Author URI: https://github.com/mukeshkushwahae
 * License: GPL-2.0
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This plugin is intended to be used as a must-use plugin (MU Plugin).
 * Place it in the `/wp-content/mu-plugins/` directory.
 * It will run automatically without activation and remain hidden in the WordPress plugin list.
 *
 * References:
 * - https://developer.wordpress.org/reference/functions/remove_post_type_support/
 * - https://developer.wordpress.org/reference/hooks/comments_open/
 * - https://developer.wordpress.org/reference/hooks/pings_open/
 * - https://developer.wordpress.org/reference/functions/remove_menu_page/
 * - https://developer.wordpress.org/reference/functions/remove_meta_box/
 * - https://developer.wordpress.org/reference/functions/add_filter/
 */

defined('ABSPATH') || exit; // Prevent direct file access

/**
 * STEP 1: Remove comment support from all post types
 * This removes the Comments & Trackbacks boxes from post/page editors.
 */
add_action('admin_init', function () {
	foreach (get_post_types() as $post_type) {
		if (post_type_supports($post_type, 'comments')) {
			remove_post_type_support($post_type, 'comments');
			remove_post_type_support($post_type, 'trackbacks');
		}
	}
});

/**
 * STEP 2: Disable comment and ping status forcibly
 * Even if a theme or plugin tries to enable them, these filters return false.
 */
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

/**
 * STEP 3: Hide existing comments from the frontend
 * This filters the comments array to be empty, even if comments exist in the DB.
 */
add_filter('comments_array', '__return_empty_array', 10, 2);

/**
 * STEP 4: Remove the "Comments" menu from the admin dashboard
 */
add_action('admin_menu', function () {
	remove_menu_page('edit-comments.php');
});

/**
 * STEP 5: Remove "Discussion" settings from the WordPress Settings menu
 */
add_action('admin_init', function () {
	remove_submenu_page('options-general.php', 'options-discussion.php');
});

/**
 * STEP 6: Remove comment-related meta boxes from dashboard and post screens
 */
add_action('admin_init', function () {
	remove_meta_box('commentstatusdiv', 'post', 'normal');     // Comment status
	remove_meta_box('commentsdiv', 'post', 'normal');          // Existing comments
	remove_meta_box('commentstatusdiv', 'page', 'normal');
	remove_meta_box('commentsdiv', 'page', 'normal');
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
});

/**
 * STEP 7: Redirect direct access to comment management pages (e.g., wp-admin/comment.php)
 */
add_action('admin_init', function () {
	global $pagenow;
	if ($pagenow === 'edit-comments.php' || $pagenow === 'comment.php') {
		wp_redirect(admin_url());
		exit;
	}
});

/**
 * STEP 8: Disable comment RSS/Atom feeds
 */
add_filter('feed_links_show_comments_feed', '__return_false');

/**
 * STEP 9: Remove frontend comment HTML containers via output buffering
 * This strips divs like <div class="comments-area"> from being rendered in themes.
 */
add_action('template_redirect', function () {
	ob_start(function ($buffer) {
		// Remove common comment containers: <div class="comments-area">...</div>
		$buffer = preg_replace('~<div[^>]+class=["\']?comments-area["\']?[^>]*>.*?</div>~is', '', $buffer);
		// Remove any <section id="comments">...</section>
		$buffer = preg_replace('~<section[^>]+id=["\']?comments["\']?[^>]*>.*?</section>~is', '', $buffer);
		return $buffer;
	});
});