<?php
/**
 * Icon libraries offered in the icon picker: WordPress Dashicons (bundled with
 * core) and Font Awesome Free (loaded from cdnjs).
 */

defined( 'ABSPATH' ) || exit;

class DashWP_Icons {

	/**
	 * Every Dashicon available in this WordPress install, read from core's
	 * own stylesheet so the list always matches the installed version.
	 *
	 * @return string[] Full class strings, e.g. "dashicons dashicons-admin-home".
	 */
	public static function dashicons() {
		$cached = get_transient( 'dashwp_dashicons' );
		if ( is_array( $cached ) && isset( $cached['v'], $cached['icons'] ) && $cached['v'] === get_bloginfo( 'version' ) ) {
			return $cached['icons'];
		}

		$names = array();
		$file  = ABSPATH . WPINC . '/css/dashicons.css';
		if ( is_readable( $file ) ) {
			preg_match_all( '/\.dashicons-([a-z0-9-]+):before/', (string) file_get_contents( $file ), $m ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$names = array_unique( $m[1] );
		}

		if ( empty( $names ) ) {
			$names = array( 'admin-home', 'admin-post', 'admin-page', 'admin-media', 'admin-users', 'admin-settings', 'admin-tools', 'admin-plugins', 'admin-comments', 'admin-links', 'building', 'cart', 'chart-bar', 'email', 'location', 'phone', 'star-filled', 'heart', 'calendar', 'products' );
		}

		sort( $names );
		$icons = array_map(
			function ( $name ) {
				return 'dashicons dashicons-' . $name;
			},
			$names
		);

		set_transient(
			'dashwp_dashicons',
			array(
				'v'     => get_bloginfo( 'version' ),
				'icons' => $icons,
			),
			WEEK_IN_SECONDS
		);

		return $icons;
	}

	/**
	 * A curated set of Font Awesome Free icons that suit an admin dashboard.
	 * Any other Font Awesome Free class can be typed into the picker by hand.
	 *
	 * @return string[]
	 */
	public static function font_awesome() {
		$solid = array(
			'house', 'house-chimney', 'house-user', 'house-signal', 'building', 'building-user', 'building-columns', 'city', 'hotel', 'warehouse', 'store', 'shop', 'landmark', 'tree-city',
			'key', 'door-open', 'door-closed', 'bed', 'bath', 'couch', 'stairs', 'elevator', 'ruler-combined', 'vector-square',
			'map', 'map-location-dot', 'map-pin', 'location-dot', 'location-crosshairs', 'compass', 'route', 'road', 'car', 'bus', 'plane', 'train', 'truck',
			'sign-hanging', 'signs-post', 'tag', 'tags', 'money-bill', 'money-bill-wave', 'sack-dollar', 'coins', 'wallet', 'credit-card', 'dollar-sign', 'euro-sign', 'percent', 'receipt', 'hand-holding-dollar', 'piggy-bank', 'scale-balanced', 'gavel', 'stamp',
			'file', 'file-lines', 'file-invoice', 'file-invoice-dollar', 'file-contract', 'file-signature', 'file-pdf', 'file-image', 'file-excel', 'file-word', 'folder', 'folder-open', 'copy', 'paste', 'clipboard', 'clipboard-list', 'clipboard-check', 'box-archive',
			'list', 'list-check', 'table', 'table-list', 'table-cells', 'grip', 'layer-group', 'cubes', 'cube', 'box', 'boxes-stacked', 'inbox', 'sitemap', 'puzzle-piece', 'plug',
			'envelope', 'envelope-open', 'paper-plane', 'phone', 'mobile-screen', 'comment', 'comments', 'comment-dots', 'message', 'bell', 'bullhorn', 'newspaper', 'rss', 'globe', 'language', 'link', 'share-nodes', 'headset', 'life-ring',
			'image', 'images', 'camera', 'video', 'film', 'photo-film', 'music', 'microphone',
			'pen', 'pen-to-square', 'pencil', 'eraser', 'trash', 'trash-can', 'plus', 'circle-plus', 'square-plus', 'check', 'circle-check', 'xmark', 'magnifying-glass', 'filter', 'sliders',
			'gear', 'gears', 'wrench', 'screwdriver-wrench', 'hammer', 'toolbox', 'paint-roller', 'palette', 'brush', 'code', 'terminal', 'database', 'server', 'cloud', 'cloud-arrow-up', 'upload', 'download', 'print', 'robot',
			'eye', 'eye-slash', 'lock', 'unlock', 'shield-halved', 'user-shield', 'circle-info', 'circle-question', 'circle-exclamation', 'triangle-exclamation',
			'user', 'users', 'user-plus', 'user-gear', 'user-tie', 'user-group', 'people-group', 'address-book', 'address-card', 'id-card', 'id-badge', 'handshake', 'briefcase',
			'chart-line', 'chart-pie', 'chart-bar', 'chart-simple', 'gauge', 'gauge-high', 'calendar', 'calendar-days', 'calendar-check', 'clock', 'hourglass',
			'star', 'heart', 'thumbs-up', 'award', 'trophy', 'crown', 'gift', 'cart-shopping', 'basket-shopping', 'bag-shopping',
			'wifi', 'bolt', 'fire', 'sun', 'moon', 'umbrella-beach', 'mountain-sun', 'tree', 'leaf', 'water',
			'book', 'book-open', 'bookmark', 'graduation-cap', 'lightbulb', 'rocket', 'flag', 'bars', 'arrow-right', 'arrow-up-right-from-square', 'right-to-bracket', 'right-from-bracket', 'power-off', 'rotate',
		);

		$regular = array( 'file', 'folder', 'envelope', 'user', 'calendar', 'clock', 'star', 'heart', 'image', 'comment', 'bell', 'bookmark', 'building', 'map' );

		$brands = array( 'facebook', 'facebook-messenger', 'instagram', 'youtube', 'whatsapp', 'telegram', 'viber', 'tiktok', 'linkedin', 'x-twitter', 'google', 'wordpress', 'pinterest', 'airbnb' );

		$icons = array();
		foreach ( $solid as $name ) {
			$icons[] = 'fa-solid fa-' . $name;
		}
		foreach ( $regular as $name ) {
			$icons[] = 'fa-regular fa-' . $name;
		}
		foreach ( $brands as $name ) {
			$icons[] = 'fa-brands fa-' . $name;
		}

		return apply_filters( 'dashwp_font_awesome_icons', $icons );
	}

	public static function enqueue_font_awesome() {
		wp_enqueue_style( 'dashwp-font-awesome', DASHWP_FA_URL, array(), '6.5.2' );
	}
}
