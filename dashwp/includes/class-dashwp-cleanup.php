<?php
/**
 * Hides admin notices, update nags/badges and WordPress footer text.
 */

defined( 'ABSPATH' ) || exit;

class DashWP_Cleanup {

	const NOTICE_HOOKS = array( 'admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices' );

	public static function init() {
		if ( DashWP_Store::setting( 'hide_update_badges' ) ) {
			add_action( 'admin_bar_menu', array( __CLASS__, 'remove_admin_bar_updates' ), 999 );
			add_action( 'admin_init', array( __CLASS__, 'remove_update_nags' ) );
			add_action( 'admin_head', array( __CLASS__, 'hide_update_badges_css' ) );
		}

		if ( DashWP_Store::setting( 'hide_notices_everywhere' ) ) {
			// Runs right before notices are printed, after every plugin has added its own.
			add_action( 'in_admin_header', array( __CLASS__, 'remove_all_notices' ), PHP_INT_MAX );
		}

		if ( DashWP_Store::setting( 'hide_footer' ) ) {
			add_filter( 'admin_footer_text', '__return_empty_string', PHP_INT_MAX );
			add_filter( 'update_footer', '__return_empty_string', PHP_INT_MAX );
		}
	}

	public static function remove_all_notices() {
		foreach ( self::NOTICE_HOOKS as $hook ) {
			remove_all_actions( $hook );
		}
	}

	public static function remove_update_nags() {
		remove_action( 'admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
		remove_action( 'admin_notices', 'maintenance_nag', 10 );
		remove_action( 'network_admin_notices', 'maintenance_nag', 10 );
	}

	public static function remove_admin_bar_updates( $wp_admin_bar ) {
		$wp_admin_bar->remove_node( 'updates' );
	}

	public static function hide_update_badges_css() {
		echo '<style id="dashwp-hide-updates">#adminmenu .update-plugins,#adminmenu .plugin-count,.update-nag{display:none!important}</style>';
	}
}
