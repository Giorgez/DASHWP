<?php
/**
 * Storage for shortcut blocks and plugin settings.
 */

defined( 'ABSPATH' ) || exit;

class DashWP_Store {

	const OPTION   = 'dashwp_shortcuts';
	const SETTINGS = 'dashwp_settings';

	const DEFAULT_COLOR = '#2271b1';
	const DEFAULT_ICON  = 'dashicons dashicons-admin-links';

	/**
	 * All shortcuts, in display order.
	 *
	 * @return array[]
	 */
	public static function all() {
		$items = get_option( self::OPTION, array() );
		return is_array( $items ) ? array_values( array_filter( $items, 'is_array' ) ) : array();
	}

	/**
	 * Shortcuts the current user is allowed to see.
	 *
	 * @return array[]
	 */
	public static function visible() {
		return array_values( array_filter( self::all(), array( __CLASS__, 'is_visible' ) ) );
	}

	public static function get( $id ) {
		foreach ( self::all() as $item ) {
			if ( $item['id'] === $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Inserts or updates a shortcut. Returns its ID.
	 */
	public static function save( array $raw ) {
		$item  = self::sanitize( $raw );
		$items = self::all();
		$found = false;

		foreach ( $items as $i => $existing ) {
			if ( $existing['id'] === $item['id'] ) {
				$items[ $i ] = $item;
				$found       = true;
				break;
			}
		}

		if ( ! $found ) {
			$items[] = $item;
		}

		update_option( self::OPTION, $items, false );
		return $item['id'];
	}

	public static function delete( $id ) {
		$items = array_filter(
			self::all(),
			function ( $item ) use ( $id ) {
				return $item['id'] !== $id;
			}
		);
		update_option( self::OPTION, array_values( $items ), false );
	}

	/**
	 * Re-orders shortcuts to match the given list of IDs. Unknown IDs are ignored
	 * and shortcuts missing from the list keep their relative order at the end.
	 */
	public static function reorder( array $ids ) {
		$by_id = array();
		foreach ( self::all() as $item ) {
			$by_id[ $item['id'] ] = $item;
		}

		$sorted = array();
		foreach ( $ids as $id ) {
			if ( isset( $by_id[ $id ] ) ) {
				$sorted[] = $by_id[ $id ];
				unset( $by_id[ $id ] );
			}
		}

		update_option( self::OPTION, array_merge( $sorted, array_values( $by_id ) ), false );
	}

	public static function sanitize( array $raw ) {
		$id = isset( $raw['id'] ) ? sanitize_key( $raw['id'] ) : '';
		if ( '' === $id ) {
			$id = 'sc' . strtolower( wp_generate_password( 10, false ) );
		}

		$color = isset( $raw['color'] ) ? sanitize_hex_color( $raw['color'] ) : '';

		$roles = array();
		if ( ! empty( $raw['roles'] ) && is_array( $raw['roles'] ) ) {
			$known = array_keys( wp_roles()->roles );
			$roles = array_values( array_intersect( array_map( 'sanitize_key', $raw['roles'] ), $known ) );
		}

		return array(
			'id'          => $id,
			'title'       => isset( $raw['title'] ) ? sanitize_text_field( $raw['title'] ) : '',
			'description' => isset( $raw['description'] ) ? sanitize_text_field( $raw['description'] ) : '',
			'url'         => self::sanitize_url( isset( $raw['url'] ) ? $raw['url'] : '' ),
			'icon'        => self::sanitize_icon( isset( $raw['icon'] ) ? $raw['icon'] : '' ),
			'color'       => $color ? $color : self::DEFAULT_COLOR,
			'new_tab'     => ! empty( $raw['new_tab'] ),
			'roles'       => $roles,
		);
	}

	/**
	 * Links to this site's admin are stored relative to wp-admin (e.g.
	 * "edit.php?post_type=estate_property") so they survive domain changes.
	 */
	public static function sanitize_url( $url ) {
		$url = trim( wp_strip_all_tags( (string) $url ) );
		if ( '' === $url ) {
			return '';
		}

		foreach ( array( admin_url(), set_url_scheme( admin_url(), 'http' ), set_url_scheme( admin_url(), 'https' ) ) as $base ) {
			if ( 0 === stripos( $url, $base ) ) {
				$url = substr( $url, strlen( $base ) );
				break;
			}
		}

		if ( preg_match( '#^[a-z][a-z0-9+.-]*:#i', $url ) ) {
			return esc_url_raw( $url );
		}

		// Relative link: keep it as a path, strip a leading "/wp-admin/".
		$url = preg_replace( '#^/?wp-admin/#i', '', $url );
		return str_replace( array( '"', "'", '<', '>', ' ' ), array( '', '', '', '', '%20' ), $url );
	}

	/**
	 * Turns a stored link into a full URL.
	 */
	public static function resolve_url( $url ) {
		if ( '' === $url ) {
			return admin_url();
		}
		if ( preg_match( '#^[a-z][a-z0-9+.-]*:#i', $url ) ) {
			return $url;
		}
		if ( '/' === $url[0] ) {
			return home_url( $url );
		}
		return admin_url( $url );
	}

	public static function sanitize_icon( $icon ) {
		$icon = trim( preg_replace( '/[^a-z0-9 _-]/i', '', (string) $icon ) );
		return '' === $icon ? self::DEFAULT_ICON : $icon;
	}

	public static function is_visible( $item ) {
		if ( empty( $item['roles'] ) ) {
			return true;
		}
		$user = wp_get_current_user();
		return (bool) array_intersect( $item['roles'], (array) $user->roles );
	}

	/**
	 * Black or white text, whichever reads better on the given background.
	 */
	public static function text_color( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '#ffffff';
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return ( ( $r * 299 + $g * 587 + $b * 114 ) / 1000 ) > 160 ? '#1d2327' : '#ffffff';
	}

	public static function uses_font_awesome( array $items ) {
		foreach ( $items as $item ) {
			if ( preg_match( '/\bfa-/', $item['icon'] ) ) {
				return true;
			}
		}
		return false;
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------- */

	public static function setting_defaults() {
		return array(
			'replace_dashboard'       => true,
			'hide_notices_everywhere' => false,
			'hide_update_badges'      => true,
			'hide_footer'             => true,
			/* translators: {name} is replaced with the current user's display name. */
			'heading'                 => __( 'Hello, {name}', 'dashwp' ),
			'subtitle'                => '',
		);
	}

	public static function settings() {
		$saved = get_option( self::SETTINGS, array() );
		return array_merge( self::setting_defaults(), is_array( $saved ) ? $saved : array() );
	}

	public static function setting( $key ) {
		$settings = self::settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	public static function save_settings( array $raw ) {
		update_option(
			self::SETTINGS,
			array(
				'replace_dashboard'       => ! empty( $raw['replace_dashboard'] ),
				'hide_notices_everywhere' => ! empty( $raw['hide_notices_everywhere'] ),
				'hide_update_badges'      => ! empty( $raw['hide_update_badges'] ),
				'hide_footer'             => ! empty( $raw['hide_footer'] ),
				'heading'                 => isset( $raw['heading'] ) ? sanitize_text_field( $raw['heading'] ) : '',
				'subtitle'                => isset( $raw['subtitle'] ) ? sanitize_text_field( $raw['subtitle'] ) : '',
			)
		);
	}

	/**
	 * Seeds a few example shortcuts on first activation.
	 */
	public static function activate() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$examples = array();

		if ( post_type_exists( 'estate_property' ) ) {
			$examples[] = array(
				'title' => 'განცხადებები',
				'url'   => 'edit.php?post_type=estate_property',
				'icon'  => 'fa-solid fa-house-chimney',
				'color' => '#e8710a',
			);
		}

		$examples[] = array(
			'title' => __( 'Posts', 'dashwp' ),
			'url'   => 'edit.php',
			'icon'  => 'dashicons dashicons-admin-post',
			'color' => '#2271b1',
		);
		$examples[] = array(
			'title' => __( 'Pages', 'dashwp' ),
			'url'   => 'edit.php?post_type=page',
			'icon'  => 'dashicons dashicons-admin-page',
			'color' => '#00a32a',
		);
		$examples[] = array(
			'title' => __( 'Media', 'dashwp' ),
			'url'   => 'upload.php',
			'icon'  => 'dashicons dashicons-admin-media',
			'color' => '#8c5ae8',
		);

		update_option( self::OPTION, array_map( array( __CLASS__, 'sanitize' ), $examples ), false );
	}
}
