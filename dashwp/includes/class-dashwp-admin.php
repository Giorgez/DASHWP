<?php
/**
 * "DashWP" admin page: create, edit, delete and re-order shortcuts, and settings.
 */

defined( 'ABSPATH' ) || exit;

class DashWP_Admin {

	const SLUG = 'dashwp';

	private static $hook = '';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_dashwp_save_shortcut', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_dashwp_delete_shortcut', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_dashwp_save_settings', array( __CLASS__, 'handle_settings' ) );
		add_action( 'wp_ajax_dashwp_reorder', array( __CLASS__, 'ajax_reorder' ) );
	}

	public static function capability() {
		return apply_filters( 'dashwp_capability', 'manage_options' );
	}

	public static function page_url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	public static function menu() {
		self::$hook = add_menu_page(
			__( 'Dashboard shortcuts', 'dashwp' ),
			__( 'DashWP', 'dashwp' ),
			self::capability(),
			self::SLUG,
			array( __CLASS__, 'page' ),
			'dashicons-screenoptions',
			3
		);
	}

	public static function assets( $hook ) {
		if ( $hook !== self::$hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'dashwp-dashboard', DASHWP_URL . 'assets/css/dashboard.css', array( 'dashicons' ), DASHWP_VERSION );
		wp_enqueue_style( 'dashwp-admin', DASHWP_URL . 'assets/css/admin.css', array( 'dashwp-dashboard' ), DASHWP_VERSION );
		DashWP_Icons::enqueue_font_awesome();

		wp_enqueue_script( 'dashwp-admin', DASHWP_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), DASHWP_VERSION, true );
		wp_localize_script(
			'dashwp-admin',
			'dashwpAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'dashwp_reorder' ),
				'dashicons' => DashWP_Icons::dashicons(),
				'fa'        => DashWP_Icons::font_awesome(),
				'i18n'      => array(
					'confirmDelete' => __( 'Delete this shortcut?', 'dashwp' ),
					'saved'         => __( 'Order saved', 'dashwp' ),
					'saveError'     => __( 'Could not save the order. Reload the page and try again.', 'dashwp' ),
					'noResults'     => __( 'No icons match your search.', 'dashwp' ),
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Request handlers
	 * ------------------------------------------------------------------- */

	private static function guard( $nonce_action ) {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'dashwp' ), 403 );
		}
		check_admin_referer( $nonce_action );
	}

	public static function handle_save() {
		self::guard( 'dashwp_save_shortcut' );

		$raw = isset( $_POST['shortcut'] ) && is_array( $_POST['shortcut'] ) ? wp_unslash( $_POST['shortcut'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in DashWP_Store::sanitize().

		$item = DashWP_Store::sanitize( $raw );
		if ( '' === $item['title'] || '' === $item['url'] ) {
			set_transient( 'dashwp_form_' . get_current_user_id(), $raw, 5 * MINUTE_IN_SECONDS );
			wp_safe_redirect( self::page_url( array( 'msg' => 'missing' ) + ( empty( $raw['id'] ) ? array() : array( 'edit' => sanitize_key( $raw['id'] ) ) ) ) );
			exit;
		}

		$is_new = empty( $raw['id'] ) || null === DashWP_Store::get( sanitize_key( $raw['id'] ) );
		DashWP_Store::save( $raw );

		wp_safe_redirect( self::page_url( array( 'msg' => $is_new ? 'added' : 'updated' ) ) );
		exit;
	}

	public static function handle_delete() {
		self::guard( 'dashwp_delete_shortcut' );

		$id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
		if ( $id ) {
			DashWP_Store::delete( $id );
		}

		wp_safe_redirect( self::page_url( array( 'msg' => 'deleted' ) ) );
		exit;
	}

	public static function handle_settings() {
		self::guard( 'dashwp_save_settings' );

		$raw = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in DashWP_Store::save_settings().
		DashWP_Store::save_settings( $raw );

		wp_safe_redirect( self::page_url( array( 'tab' => 'settings', 'msg' => 'settings' ) ) );
		exit;
	}

	public static function ajax_reorder() {
		check_ajax_referer( 'dashwp_reorder' );
		if ( ! current_user_can( self::capability() ) ) {
			wp_send_json_error( null, 403 );
		}

		$ids = isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['ids'] ) ) : array();
		DashWP_Store::reorder( $ids );
		wp_send_json_success();
	}

	/* ---------------------------------------------------------------------
	 * Page
	 * ------------------------------------------------------------------- */

	public static function page() {
		$tab = isset( $_GET['tab'] ) && 'settings' === $_GET['tab'] ? 'settings' : 'shortcuts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap dashwp-admin">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Dashboard shortcuts', 'dashwp' ); ?></h1>
			<a class="page-title-action" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'View dashboard', 'dashwp' ); ?></a>
			<hr class="wp-header-end">

			<?php self::message(); ?>

			<nav class="nav-tab-wrapper">
				<a class="nav-tab <?php echo 'shortcuts' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::page_url() ); ?>"><?php esc_html_e( 'Shortcuts', 'dashwp' ); ?></a>
				<a class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::page_url( array( 'tab' => 'settings' ) ) ); ?>"><?php esc_html_e( 'Settings', 'dashwp' ); ?></a>
			</nav>

			<?php
			if ( 'settings' === $tab ) {
				self::settings_tab();
			} else {
				self::shortcuts_tab();
			}
			?>
		</div>
		<?php
	}

	private static function message() {
		$msg = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$map = array(
			'added'    => array( 'success', __( 'Shortcut added.', 'dashwp' ) ),
			'updated'  => array( 'success', __( 'Shortcut updated.', 'dashwp' ) ),
			'deleted'  => array( 'success', __( 'Shortcut deleted.', 'dashwp' ) ),
			'settings' => array( 'success', __( 'Settings saved.', 'dashwp' ) ),
			'missing'  => array( 'error', __( 'Please enter both a name and a link.', 'dashwp' ) ),
		);
		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible dashwp-notice"><p>%s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
		}
	}

	private static function shortcuts_tab() {
		$edit_id = isset( $_GET['edit'] ) ? sanitize_key( $_GET['edit'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$editing = $edit_id ? DashWP_Store::get( $edit_id ) : null;

		$failed = get_transient( 'dashwp_form_' . get_current_user_id() );
		if ( is_array( $failed ) ) {
			delete_transient( 'dashwp_form_' . get_current_user_id() );
			$editing = DashWP_Store::sanitize( $failed );
			if ( empty( $failed['id'] ) ) {
				$editing['id'] = '';
			}
		}

		$item = $editing ? $editing : array(
			'id'          => '',
			'title'       => '',
			'description' => '',
			'url'         => '',
			'icon'        => DashWP_Store::DEFAULT_ICON,
			'color'       => DashWP_Store::DEFAULT_COLOR,
			'new_tab'     => false,
			'roles'       => array(),
		);
		$is_edit = '' !== $item['id'];
		$items   = DashWP_Store::all();
		?>
		<div class="dashwp-layout">
			<form class="dashwp-card dashwp-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<h2><?php echo $is_edit ? esc_html__( 'Edit shortcut', 'dashwp' ) : esc_html__( 'Add a shortcut', 'dashwp' ); ?></h2>
				<input type="hidden" name="action" value="dashwp_save_shortcut">
				<input type="hidden" name="shortcut[id]" value="<?php echo esc_attr( $item['id'] ); ?>">
				<?php wp_nonce_field( 'dashwp_save_shortcut' ); ?>

				<div class="dashwp-preview" aria-hidden="true">
					<?php DashWP_Dashboard::tile( $item, 'div' ); ?>
				</div>

				<p class="dashwp-field">
					<label for="dashwp-title"><?php esc_html_e( 'Name', 'dashwp' ); ?> <span class="required">*</span></label>
					<input type="text" id="dashwp-title" name="shortcut[title]" class="widefat" required value="<?php echo esc_attr( $item['title'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. განცხადებები', 'dashwp' ); ?>">
				</p>

				<p class="dashwp-field">
					<label for="dashwp-url"><?php esc_html_e( 'Link', 'dashwp' ); ?> <span class="required">*</span></label>
					<input type="text" id="dashwp-url" name="shortcut[url]" class="widefat code" required value="<?php echo esc_attr( $item['url'] ); ?>" placeholder="edit.php?post_type=estate_property">
					<span class="description"><?php esc_html_e( 'A full URL, or an admin page such as edit.php?post_type=estate_property or admin.php?page=plugin-page. Links to this site\'s admin are saved relative, so they keep working if the domain changes.', 'dashwp' ); ?></span>
				</p>

				<div class="dashwp-field">
					<label><?php esc_html_e( 'Icon', 'dashwp' ); ?></label>
					<div class="dashwp-icon-field">
						<span class="dashwp-icon-current"><span class="<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span></span>
						<input type="hidden" id="dashwp-icon" name="shortcut[icon]" value="<?php echo esc_attr( $item['icon'] ); ?>">
						<button type="button" class="button dashwp-icon-open"><?php esc_html_e( 'Choose icon', 'dashwp' ); ?></button>
						<code class="dashwp-icon-name"><?php echo esc_html( $item['icon'] ); ?></code>
					</div>
				</div>

				<p class="dashwp-field">
					<label for="dashwp-color"><?php esc_html_e( 'Color', 'dashwp' ); ?></label>
					<input type="text" id="dashwp-color" name="shortcut[color]" value="<?php echo esc_attr( $item['color'] ); ?>" data-default-color="<?php echo esc_attr( DashWP_Store::DEFAULT_COLOR ); ?>">
				</p>

				<details class="dashwp-more" <?php echo ( $item['description'] || $item['new_tab'] || $item['roles'] ) ? 'open' : ''; ?>>
					<summary><?php esc_html_e( 'More options', 'dashwp' ); ?></summary>

					<p class="dashwp-field">
						<label for="dashwp-desc"><?php esc_html_e( 'Short description (optional)', 'dashwp' ); ?></label>
						<input type="text" id="dashwp-desc" name="shortcut[description]" class="widefat" value="<?php echo esc_attr( $item['description'] ); ?>">
					</p>

					<p class="dashwp-field">
						<label><input type="checkbox" name="shortcut[new_tab]" value="1" <?php checked( $item['new_tab'] ); ?>> <?php esc_html_e( 'Open in a new tab', 'dashwp' ); ?></label>
					</p>

					<fieldset class="dashwp-field">
						<legend><?php esc_html_e( 'Show only to these roles (leave empty for everyone)', 'dashwp' ); ?></legend>
						<div class="dashwp-roles">
							<?php foreach ( wp_roles()->get_names() as $role => $name ) : ?>
								<label><input type="checkbox" name="shortcut[roles][]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $item['roles'], true ) ); ?>> <?php echo esc_html( translate_user_role( $name ) ); ?></label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				</details>

				<p class="dashwp-actions">
					<button type="submit" class="button button-primary"><?php echo $is_edit ? esc_html__( 'Update shortcut', 'dashwp' ) : esc_html__( 'Save shortcut', 'dashwp' ); ?></button>
					<?php if ( $is_edit ) : ?>
						<a class="button" href="<?php echo esc_url( self::page_url() ); ?>"><?php esc_html_e( 'Cancel', 'dashwp' ); ?></a>
					<?php endif; ?>
				</p>
			</form>

			<div class="dashwp-card dashwp-list-card">
				<h2><?php esc_html_e( 'Your shortcuts', 'dashwp' ); ?> <span class="count">(<?php echo (int) count( $items ); ?>)</span></h2>
				<?php if ( empty( $items ) ) : ?>
					<p class="description"><?php esc_html_e( 'No shortcuts yet. Use the form to add one.', 'dashwp' ); ?></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Drag the handle to change the order on the dashboard.', 'dashwp' ); ?> <span class="dashwp-order-status" aria-live="polite"></span></p>
					<ul class="dashwp-list">
						<?php foreach ( $items as $row ) : ?>
							<li class="dashwp-row<?php echo $row['id'] === $item['id'] ? ' is-editing' : ''; ?>" data-id="<?php echo esc_attr( $row['id'] ); ?>">
								<span class="dashwp-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'dashwp' ); ?>"></span>
								<span class="dashwp-row__icon" style="<?php echo esc_attr( sprintf( 'background:%s;color:%s', $row['color'], DashWP_Store::text_color( $row['color'] ) ) ); ?>"><span class="<?php echo esc_attr( $row['icon'] ); ?>" aria-hidden="true"></span></span>
								<span class="dashwp-row__text">
									<strong><?php echo esc_html( $row['title'] ); ?></strong>
									<a class="dashwp-row__url" href="<?php echo esc_url( DashWP_Store::resolve_url( $row['url'] ) ); ?>"><?php echo esc_html( $row['url'] ); ?></a>
									<?php if ( $row['roles'] ) : ?>
										<span class="dashwp-row__roles"><?php echo esc_html( implode( ', ', $row['roles'] ) ); ?></span>
									<?php endif; ?>
								</span>
								<span class="dashwp-row__actions">
									<a class="button button-small" href="<?php echo esc_url( self::page_url( array( 'edit' => $row['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'dashwp' ); ?></a>
									<a class="button button-small button-link-delete dashwp-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=dashwp_delete_shortcut&id=' . rawurlencode( $row['id'] ) ), 'dashwp_delete_shortcut' ) ); ?>"><?php esc_html_e( 'Delete', 'dashwp' ); ?></a>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>

		<div class="dashwp-modal" id="dashwp-icon-modal" hidden>
			<div class="dashwp-modal__backdrop" data-close></div>
			<div class="dashwp-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="dashwp-modal-title">
				<div class="dashwp-modal__head">
					<h2 id="dashwp-modal-title"><?php esc_html_e( 'Choose an icon', 'dashwp' ); ?></h2>
					<button type="button" class="dashwp-modal__close" data-close aria-label="<?php esc_attr_e( 'Close', 'dashwp' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
				</div>
				<div class="dashwp-modal__tools">
					<div class="dashwp-modal__tabs" role="tablist">
						<button type="button" class="button is-active" data-lib="dashicons"><?php esc_html_e( 'Dashicons', 'dashwp' ); ?></button>
						<button type="button" class="button" data-lib="fa"><?php esc_html_e( 'Font Awesome', 'dashwp' ); ?></button>
					</div>
					<input type="search" class="dashwp-modal__search" placeholder="<?php esc_attr_e( 'Search icons…', 'dashwp' ); ?>">
				</div>
				<div class="dashwp-modal__grid" tabindex="-1"></div>
				<div class="dashwp-modal__custom">
					<label for="dashwp-custom-icon"><?php esc_html_e( 'Or type any Font Awesome Free / Dashicons class:', 'dashwp' ); ?></label>
					<input type="text" id="dashwp-custom-icon" class="code" placeholder="fa-solid fa-house">
					<button type="button" class="button dashwp-custom-apply"><?php esc_html_e( 'Use', 'dashwp' ); ?></button>
					<a href="https://fontawesome.com/search?o=r&amp;m=free" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Browse Font Awesome Free', 'dashwp' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	private static function settings_tab() {
		$s = DashWP_Store::settings();
		$checkbox = function ( $key, $label, $help = '' ) use ( $s ) {
			printf(
				'<label class="dashwp-toggle"><input type="checkbox" name="settings[%1$s]" value="1" %2$s> <span>%3$s</span></label>%4$s',
				esc_attr( $key ),
				checked( ! empty( $s[ $key ] ), true, false ),
				esc_html( $label ),
				$help ? '<p class="description">' . esc_html( $help ) . '</p>' : ''
			);
		};
		?>
		<form class="dashwp-card dashwp-settings" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="dashwp_save_settings">
			<?php wp_nonce_field( 'dashwp_save_settings' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Dashboard', 'dashwp' ); ?></th>
					<td>
						<?php $checkbox( 'replace_dashboard', __( 'Replace the WordPress dashboard with the shortcut grid', 'dashwp' ), __( 'Removes the welcome panel, At a Glance, Activity, News, Site Health and every plugin widget, and hides all notices on the dashboard.', 'dashwp' ) ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dashwp-heading"><?php esc_html_e( 'Heading', 'dashwp' ); ?></label></th>
					<td>
						<input type="text" id="dashwp-heading" name="settings[heading]" class="regular-text" value="<?php echo esc_attr( $s['heading'] ); ?>">
						<p class="description"><?php esc_html_e( '{name} is replaced with the user\'s display name.', 'dashwp' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dashwp-subtitle"><?php esc_html_e( 'Subtitle', 'dashwp' ); ?></label></th>
					<td><input type="text" id="dashwp-subtitle" name="settings[subtitle]" class="large-text" value="<?php echo esc_attr( $s['subtitle'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Clean-up', 'dashwp' ); ?></th>
					<td>
						<?php $checkbox( 'hide_update_badges', __( 'Hide update nags and update counters', 'dashwp' ), __( 'Removes the "WordPress x.y is available" nag, the plugin/theme update bubbles in the menu and the updates icon in the toolbar. Updates are still available under Dashboard → Updates.', 'dashwp' ) ); ?>
						<br>
						<?php $checkbox( 'hide_notices_everywhere', __( 'Hide admin notices on every admin page, not only the dashboard', 'dashwp' ), __( 'Use with care: some plugins show important warnings this way.', 'dashwp' ) ); ?>
						<br>
						<?php $checkbox( 'hide_footer', __( 'Hide the "Thank you for creating with WordPress" footer and version number', 'dashwp' ) ); ?>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}
}
