<?php
/**
 * Opt-in post-import site setup: required Pages, the four theme menus, and
 * optional Reading settings. Nothing here runs unless an administrator
 * visits Tools -> Learning Studio Setup and submits the form.
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create or reuse the Pages this theme expects, matched by slug so repeated
 * runs never duplicate them.
 *
 * @return array{ids:array<string,int>,report:array<int,array<string,string>>}
 */
function tls_setup_pages(): array {
	$page_specs = array(
		'subjects' => __( 'Subjects', 'the-learning-studio' ),
		'about'    => __( 'About', 'the-learning-studio' ),
		'contact'  => __( 'Contact', 'the-learning-studio' ),
		'privacy'  => __( 'Privacy Policy', 'the-learning-studio' ),
		'terms'    => __( 'Terms of Use', 'the-learning-studio' ),
		'blog'     => __( 'Blog', 'the-learning-studio' ),
		'home'     => __( 'Home', 'the-learning-studio' ),
	);

	$ids    = array();
	$report = array();
	foreach ( $page_specs as $slug => $title ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			$report[]     = array( 'action' => 'reused', 'title' => $title, 'slug' => $slug, 'message' => '' );
			continue;
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			$report[] = array( 'action' => 'error', 'title' => $title, 'slug' => $slug, 'message' => $post_id->get_error_message() );
			continue;
		}
		$ids[ $slug ] = $post_id;
		$report[]     = array( 'action' => 'created', 'title' => $title, 'slug' => $slug, 'message' => '' );
	}

	return array( 'ids' => $ids, 'report' => $report );
}

/**
 * Definitions for the four theme navigation menus and the items each needs.
 *
 * @return array<string,array{name:string,items:array<int,array<string,string>>}>
 */
function tls_setup_menu_specs(): array {
	return array(
		'primary'        => array(
			'name'  => __( 'Primary Navigation', 'the-learning-studio' ),
			'items' => array(
				array( 'label' => __( 'Lessons', 'the-learning-studio' ), 'type' => 'archive' ),
				array( 'label' => __( 'Subjects', 'the-learning-studio' ), 'type' => 'page', 'page' => 'subjects' ),
				array( 'label' => __( 'Blog', 'the-learning-studio' ), 'type' => 'page', 'page' => 'blog' ),
			),
		),
		'footer_explore' => array(
			'name'  => __( 'Footer: Explore', 'the-learning-studio' ),
			'items' => array(
				array( 'label' => __( 'Lessons', 'the-learning-studio' ), 'type' => 'archive' ),
				array( 'label' => __( 'Subjects', 'the-learning-studio' ), 'type' => 'page', 'page' => 'subjects' ),
			),
		),
		'footer_studio'  => array(
			'name'  => __( 'Footer: Studio', 'the-learning-studio' ),
			'items' => array(
				array( 'label' => __( 'About', 'the-learning-studio' ), 'type' => 'page', 'page' => 'about' ),
				array( 'label' => __( 'Contact', 'the-learning-studio' ), 'type' => 'page', 'page' => 'contact' ),
			),
		),
		'footer_legal'   => array(
			'name'  => __( 'Footer: Legal', 'the-learning-studio' ),
			'items' => array(
				array( 'label' => __( 'Privacy Policy', 'the-learning-studio' ), 'type' => 'page', 'page' => 'privacy' ),
				array( 'label' => __( 'Terms of Use', 'the-learning-studio' ), 'type' => 'page', 'page' => 'terms' ),
			),
		),
	);
}

/**
 * Add one menu item if an equivalent one is not already present.
 *
 * @param int                  $menu_id        Menu term ID.
 * @param array<int,WP_Post>   $existing_items Current items on the menu.
 * @param array<string,string> $item           Item spec from tls_setup_menu_specs().
 * @param array<string,int>    $page_ids       Slug => Page ID map from tls_setup_pages().
 * @return bool True if a new item was added.
 */
function tls_setup_add_menu_item( int $menu_id, array $existing_items, array $item, array $page_ids ): bool {
	if ( 'page' === $item['type'] ) {
		if ( empty( $page_ids[ $item['page'] ] ) ) {
			return false;
		}
		$object_id = $page_ids[ $item['page'] ];
		foreach ( $existing_items as $existing_item ) {
			if ( 'post_type' === $existing_item->type && (int) $existing_item->object_id === (int) $object_id ) {
				return false;
			}
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $item['label'],
				'menu-item-object-id' => $object_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
		return true;
	}

	$url = (string) get_post_type_archive_link( 'lesson' );
	if ( ! $url ) {
		return false;
	}
	foreach ( $existing_items as $existing_item ) {
		if ( 'custom' === $existing_item->type && untrailingslashit( $existing_item->url ) === untrailingslashit( $url ) ) {
			return false;
		}
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => $item['label'],
			'menu-item-url'    => $url,
			'menu-item-type'   => 'custom',
			'menu-item-status' => 'publish',
		)
	);
	return true;
}

/**
 * Create or reuse the theme's four menus and assign them to their locations.
 *
 * Locations that already have a menu attached are left untouched unless
 * $reassign is true, so this never silently replaces an administrator's
 * existing navigation.
 *
 * @param array<string,int> $page_ids Slug => Page ID map from tls_setup_pages().
 * @param bool              $reassign Whether to touch locations that already have a menu.
 * @return array<int,array<string,string>>
 */
function tls_setup_menus( array $page_ids, bool $reassign ): array {
	$report    = array();
	$locations = get_nav_menu_locations();

	foreach ( tls_setup_menu_specs() as $location => $spec ) {
		if ( ! empty( $locations[ $location ] ) && ! $reassign ) {
			$report[] = array(
				'action'  => 'skipped',
				'title'   => $spec['name'],
				'slug'    => $location,
				'message' => __( 'A menu is already assigned to this location.', 'the-learning-studio' ),
			);
			continue;
		}

		$menu_object = wp_get_nav_menu_object( $spec['name'] );
		if ( $menu_object ) {
			$menu_id     = $menu_object->term_id;
			$menu_action = 'reused';
		} else {
			$created = wp_create_nav_menu( $spec['name'] );
			if ( is_wp_error( $created ) ) {
				$report[] = array( 'action' => 'error', 'title' => $spec['name'], 'slug' => $location, 'message' => $created->get_error_message() );
				continue;
			}
			$menu_id     = $created;
			$menu_action = 'created';
		}

		$existing_items = wp_get_nav_menu_items( $menu_id );
		$existing_items = $existing_items ? $existing_items : array();
		$added          = 0;
		foreach ( $spec['items'] as $item ) {
			if ( tls_setup_add_menu_item( $menu_id, $existing_items, $item, $page_ids ) ) {
				++$added;
			}
		}

		$locations[ $location ] = $menu_id;
		$report[]                = array(
			'action'  => $menu_action,
			'title'   => $spec['name'],
			'slug'    => $location,
			/* translators: %d: number of menu items added. */
			'message' => sprintf( _n( '%d menu item added.', '%d menu items added.', $added, 'the-learning-studio' ), $added ),
		);
	}

	set_theme_mod( 'nav_menu_locations', $locations );

	return $report;
}

/**
 * Optionally set the static homepage, posts page, and privacy policy page.
 *
 * Skipped by default when a static homepage is already configured, unless
 * $overwrite is explicitly set.
 *
 * @param array<string,int> $page_ids  Slug => Page ID map from tls_setup_pages().
 * @param bool               $overwrite Whether to replace an already-configured homepage.
 * @return array<int,array<string,string>>
 */
function tls_setup_reading_settings( array $page_ids, bool $overwrite ): array {
	$already_configured = 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) > 0;

	if ( $already_configured && ! $overwrite ) {
		return array(
			array(
				'action'  => 'skipped',
				'title'   => __( 'Reading settings', 'the-learning-studio' ),
				'slug'    => '',
				'message' => __( 'A static homepage is already configured.', 'the-learning-studio' ),
			),
		);
	}

	if ( ! empty( $page_ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_ids['home'] );
	}
	if ( ! empty( $page_ids['blog'] ) ) {
		update_option( 'page_for_posts', $page_ids['blog'] );
	}
	if ( ! empty( $page_ids['privacy'] ) && ! get_option( 'wp_page_for_privacy_policy' ) ) {
		update_option( 'wp_page_for_privacy_policy', $page_ids['privacy'] );
	}

	return array(
		array(
			'action'  => $already_configured ? 'updated' : 'created',
			'title'   => __( 'Reading settings', 'the-learning-studio' ),
			'slug'    => '',
			'message' => __( 'Homepage, posts page, and privacy policy page were set.', 'the-learning-studio' ),
		),
	);
}

/**
 * Run the full opt-in setup: Pages, menus, and (optionally) Reading settings.
 *
 * @param bool $reassign_menus    Reassign menu locations that already have a menu.
 * @param bool $configure_reading Also set the homepage/posts/privacy Reading options.
 * @param bool $overwrite_reading Overwrite Reading settings even if already configured.
 * @return array{pages:array,menus:array,reading:array}
 */
function tls_run_site_setup( bool $reassign_menus = false, bool $configure_reading = false, bool $overwrite_reading = false ): array {
	$pages = tls_setup_pages();

	$report = array(
		'pages'   => $pages['report'],
		'menus'   => tls_setup_menus( $pages['ids'], $reassign_menus ),
		'reading' => $configure_reading ? tls_setup_reading_settings( $pages['ids'], $overwrite_reading ) : array(),
	);

	flush_rewrite_rules();

	return $report;
}

function tls_register_setup_page(): void {
	add_management_page(
		__( 'Learning Studio Setup', 'the-learning-studio' ),
		__( 'Learning Studio Setup', 'the-learning-studio' ),
		'manage_options',
		'tls-site-setup',
		'tls_render_setup_page'
	);
}
add_action( 'admin_menu', 'tls_register_setup_page' );

function tls_render_setup_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run site setup.', 'the-learning-studio' ) );
	}
	$report = null;
	if ( isset( $_POST['tls_setup_submit'] ) ) {
		check_admin_referer( 'tls_site_setup', 'tls_setup_nonce' );
		$report = tls_run_site_setup(
			! empty( $_POST['tls_reassign_menus'] ),
			! empty( $_POST['tls_configure_reading'] ),
			! empty( $_POST['tls_overwrite_reading'] )
		);
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Learning Studio Setup', 'the-learning-studio' ); ?></h1>
		<p><?php esc_html_e( 'Create or reuse the Pages this theme expects, build and assign its four navigation menus, and optionally configure the homepage, posts page, and privacy policy page. Running this more than once will not create duplicates.', 'the-learning-studio' ); ?></p>
		<?php if ( $report ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Setup complete.', 'the-learning-studio' ); ?></p></div>
			<?php
			tls_render_import_result_table( __( 'Pages', 'the-learning-studio' ), $report['pages'] );
			tls_render_import_result_table( __( 'Menus', 'the-learning-studio' ), $report['menus'] );
			if ( $report['reading'] ) {
				tls_render_import_result_table( __( 'Reading settings', 'the-learning-studio' ), $report['reading'] );
			}
			?>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'tls_site_setup', 'tls_setup_nonce' ); ?>
			<p><label><input type="checkbox" name="tls_reassign_menus" value="1"> <?php esc_html_e( 'Reassign menu locations that already have a menu attached', 'the-learning-studio' ); ?></label></p>
			<p><label><input type="checkbox" name="tls_configure_reading" value="1"> <?php esc_html_e( 'Also set the homepage, posts page, and privacy policy page (Settings -> Reading)', 'the-learning-studio' ); ?></label></p>
			<p><label><input type="checkbox" name="tls_overwrite_reading" value="1"> <?php esc_html_e( 'Overwrite Reading settings even if a homepage is already configured', 'the-learning-studio' ); ?></label></p>
			<?php submit_button( __( 'Run setup', 'the-learning-studio' ), 'primary', 'tls_setup_submit' ); ?>
		</form>
	</div>
	<?php
}
