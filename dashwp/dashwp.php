<?php
/**
 * Plugin Name:       DashWP – Custom Dashboard
 * Description:       Replaces the WordPress dashboard with a clean grid of custom shortcut blocks (icon, name, color, link) and hides notices, update nags and the default dashboard widgets.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            DashWP
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dashwp
 */

defined( 'ABSPATH' ) || exit;

define( 'DASHWP_VERSION', '1.0.0' );
define( 'DASHWP_FILE', __FILE__ );
define( 'DASHWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'DASHWP_URL', plugin_dir_url( __FILE__ ) );
define( 'DASHWP_FA_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css' );

require_once DASHWP_DIR . 'includes/class-dashwp-store.php';
require_once DASHWP_DIR . 'includes/class-dashwp-icons.php';
require_once DASHWP_DIR . 'includes/class-dashwp-cleanup.php';
require_once DASHWP_DIR . 'includes/class-dashwp-dashboard.php';
require_once DASHWP_DIR . 'includes/class-dashwp-admin.php';

register_activation_hook( __FILE__, array( 'DashWP_Store', 'activate' ) );

// Hooked on init (not plugins_loaded) so translated defaults are not requested too early.
add_action(
	'init',
	function () {
		DashWP_Cleanup::init();

		if ( is_admin() ) {
			DashWP_Dashboard::init();
			DashWP_Admin::init();
		}
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( DashWP_Admin::page_url() ) . '">' . esc_html__( 'Manage shortcuts', 'dashwp' ) . '</a>' );
		return $links;
	}
);
