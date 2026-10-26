<?php
/**
 * Replaces wp-admin/index.php with the shortcut grid.
 */

defined( 'ABSPATH' ) || exit;

class DashWP_Dashboard {

	public static function init() {
		if ( ! DashWP_Store::setting( 'replace_dashboard' ) ) {
			return;
		}
		// Single-site dashboard only; network and user admin keep their own.
		add_action( 'load-index.php', array( __CLASS__, 'render' ), PHP_INT_MAX );
	}

	/**
	 * Takes over the request before core builds its dashboard, so no default
	 * or plugin widgets, welcome panel, Screen Options or Help tabs are loaded.
	 */
	public static function render() {
		if ( is_network_admin() || is_user_admin() ) {
			return;
		}

		global $title, $parent_file, $submenu_file;
		$title        = __( 'Dashboard' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- core string.
		$parent_file  = 'index.php';
		$submenu_file = 'index.php';

		$items = DashWP_Store::visible();

		add_action(
			'admin_enqueue_scripts',
			function () use ( $items ) {
				wp_enqueue_style( 'dashwp-dashboard', DASHWP_URL . 'assets/css/dashboard.css', array( 'dashicons' ), DASHWP_VERSION );
				if ( DashWP_Store::uses_font_awesome( $items ) ) {
					DashWP_Icons::enqueue_font_awesome();
				}
			}
		);

		add_filter(
			'admin_body_class',
			function ( $classes ) {
				return $classes . ' dashwp-dashboard';
			}
		);

		// Notices are always hidden on the dashboard.
		add_action( 'in_admin_header', array( 'DashWP_Cleanup', 'remove_all_notices' ), PHP_INT_MAX );

		require_once ABSPATH . 'wp-admin/admin-header.php';
		self::output( $items );
		require_once ABSPATH . 'wp-admin/admin-footer.php';
		exit;
	}

	private static function output( array $items ) {
		$user     = wp_get_current_user();
		$heading  = str_replace( '{name}', $user->display_name, (string) DashWP_Store::setting( 'heading' ) );
		$subtitle = (string) DashWP_Store::setting( 'subtitle' );
		$can_edit = current_user_can( DashWP_Admin::capability() );
		?>
		<div class="wrap dashwp-wrap">
			<div class="dashwp-header">
				<div>
					<h1 class="dashwp-heading"><?php echo esc_html( '' !== $heading ? $heading : __( 'Dashboard' ) ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain ?></h1>
					<?php if ( '' !== $subtitle ) : ?>
						<p class="dashwp-subtitle"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $can_edit ) : ?>
					<a class="button dashwp-manage" href="<?php echo esc_url( DashWP_Admin::page_url() ); ?>">
						<span class="dashicons dashicons-edit" aria-hidden="true"></span>
						<?php esc_html_e( 'Manage shortcuts', 'dashwp' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( empty( $items ) ) : ?>
				<div class="dashwp-empty">
					<span class="dashicons dashicons-screenoptions" aria-hidden="true"></span>
					<p><?php esc_html_e( 'No shortcuts yet.', 'dashwp' ); ?></p>
					<?php if ( $can_edit ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( DashWP_Admin::page_url() ); ?>"><?php esc_html_e( 'Add your first shortcut', 'dashwp' ); ?></a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<nav class="dashwp-grid" aria-label="<?php esc_attr_e( 'Shortcuts', 'dashwp' ); ?>">
					<?php foreach ( $items as $item ) : ?>
						<?php self::tile( $item ); ?>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function tile( array $item, $tag = 'a' ) {
		$style = sprintf( '--dashwp-color:%s;--dashwp-text:%s', $item['color'], DashWP_Store::text_color( $item['color'] ) );
		$attrs = 'a' === $tag
			? sprintf(
				' href="%s"%s',
				esc_url( DashWP_Store::resolve_url( $item['url'] ) ),
				$item['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : ''
			)
			: '';
		?>
		<<?php echo tag_escape( $tag ); ?> class="dashwp-tile" style="<?php echo esc_attr( $style ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
			<span class="dashwp-tile__icon"><span class="<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span></span>
			<span class="dashwp-tile__title"><?php echo esc_html( $item['title'] ); ?></span>
			<?php if ( ! empty( $item['description'] ) ) : ?>
				<span class="dashwp-tile__desc"><?php echo esc_html( $item['description'] ); ?></span>
			<?php endif; ?>
		</<?php echo tag_escape( $tag ); ?>>
		<?php
	}
}
