<?php
/**
 * Removes DashWP data when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'dashwp_shortcuts' );
delete_option( 'dashwp_settings' );
delete_transient( 'dashwp_dashicons' );
