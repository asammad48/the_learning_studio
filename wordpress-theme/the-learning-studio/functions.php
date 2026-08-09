<?php
/**
 * Theme bootstrap.
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/content-types.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/importer.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/setup.php';

function tls_theme_setup(): void {
	load_theme_textdomain( 'the-learning-studio', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 76, 'width' => 76, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/site.css' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	register_nav_menus(
		array(
			'primary'        => __( 'Primary navigation', 'the-learning-studio' ),
			'footer_explore' => __( 'Footer: Explore', 'the-learning-studio' ),
			'footer_studio'  => __( 'Footer: Studio', 'the-learning-studio' ),
			'footer_legal'   => __( 'Footer: Legal', 'the-learning-studio' ),
		)
	);
}
add_action( 'after_setup_theme', 'tls_theme_setup' );

/**
 * Add an optional description below navigation menu item labels.
 *
 * WordPress stores menu descriptions but its default classic-menu walker does
 * not include them in the rendered link text. Limit the additional markup to
 * this theme's registered menu locations so other menus remain unaffected.
 *
 * @param string   $title The filtered menu item title.
 * @param WP_Post  $item  The current menu item.
 * @param stdClass $args  wp_nav_menu() arguments.
 * @return string
 */
function tls_nav_menu_item_title( string $title, WP_Post $item, stdClass $args ): string {
	$theme_locations = array( 'primary', 'footer_explore', 'footer_studio', 'footer_legal' );
	if ( empty( $item->description ) || empty( $args->theme_location ) || ! in_array( $args->theme_location, $theme_locations, true ) ) {
		return $title;
	}

	return $title . '<span class="menu-item-description">' . esc_html( $item->description ) . '</span>';
}
add_filter( 'nav_menu_item_title', 'tls_nav_menu_item_title', 10, 3 );

function tls_enqueue_assets(): void {
	$css_path = get_template_directory() . '/assets/site.css';
	$js_path  = get_template_directory() . '/assets/navigation.js';
	wp_enqueue_style( 'tls-site', get_template_directory_uri() . '/assets/site.css', array(), (string) filemtime( $css_path ) );
	wp_enqueue_script( 'tls-navigation', get_template_directory_uri() . '/assets/navigation.js', array(), (string) filemtime( $js_path ), true );
	wp_localize_script(
		'tls-navigation',
		'tlsNavigation',
		array(
			'expandSubmenu'   => __( 'Expand submenu for %s', 'the-learning-studio' ),
			'collapseSubmenu' => __( 'Collapse submenu for %s', 'the-learning-studio' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'tls_enqueue_assets' );

function tls_flush_rewrites_after_switch(): void {
	tls_register_content_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'tls_flush_rewrites_after_switch' );

/**
 * Resolve the URL of a Page by slug, falling back to a static path when the
 * Page does not exist (e.g. renamed or not yet created after import).
 *
 * @param string $slug          Page slug to look up.
 * @param string $fallback_path Path appended to home_url() when the Page is missing.
 * @return string
 */
function tls_fallback_page_url( string $slug, string $fallback_path ): string {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page instanceof WP_Post ) {
		$permalink = get_permalink( $page );
		if ( $permalink ) {
			return $permalink;
		}
	}

	return home_url( $fallback_path );
}

/**
 * Resolve the blog/posts listing URL, preferring the configured posts page.
 *
 * @return string
 */
function tls_fallback_blog_url(): string {
	$page_for_posts = (int) get_option( 'page_for_posts' );
	if ( $page_for_posts > 0 ) {
		$permalink = get_permalink( $page_for_posts );
		if ( $permalink ) {
			return $permalink;
		}
	}

	return tls_fallback_page_url( 'blog', '/blog/' );
}

/**
 * Resolve the privacy policy URL, preferring the configured privacy page.
 *
 * @return string
 */
function tls_fallback_privacy_url(): string {
	$privacy_url = get_privacy_policy_url();
	if ( $privacy_url ) {
		return $privacy_url;
	}

	return tls_fallback_page_url( 'privacy', '/privacy/' );
}

function tls_fallback_menu(): void {
	echo '<ul id="primary-menu" class="menu links">';
	echo '<li><a href="' . esc_url( get_post_type_archive_link( 'lesson' ) ) . '">' . esc_html__( 'Lessons', 'the-learning-studio' ) . '</a></li>';
	echo '<li><a href="' . esc_url( tls_fallback_page_url( 'subjects', '/subjects/' ) ) . '">' . esc_html__( 'Subjects', 'the-learning-studio' ) . '</a></li>';
	echo '<li><a href="' . esc_url( tls_fallback_blog_url() ) . '">' . esc_html__( 'Blog', 'the-learning-studio' ) . '</a></li>';
	echo '</ul>';
}

function tls_footer_explore_fallback(): void {
	echo '<ul class="menu">';
	echo '<li><a href="' . esc_url( get_post_type_archive_link( 'lesson' ) ) . '">' . esc_html__( 'Lessons', 'the-learning-studio' ) . '</a></li>';
	echo '<li><a href="' . esc_url( tls_fallback_page_url( 'subjects', '/subjects/' ) ) . '">' . esc_html__( 'Subjects', 'the-learning-studio' ) . '</a></li>';
	echo '</ul>';
}

function tls_footer_studio_fallback(): void {
	echo '<ul class="menu">';
	echo '<li><a href="' . esc_url( tls_fallback_page_url( 'about', '/about/' ) ) . '">' . esc_html__( 'About', 'the-learning-studio' ) . '</a></li>';
	echo '<li><a href="' . esc_url( tls_fallback_page_url( 'contact', '/contact/' ) ) . '">' . esc_html__( 'Contact', 'the-learning-studio' ) . '</a></li>';
	echo '</ul>';
}

function tls_footer_legal_fallback(): void {
	echo '<ul class="menu">';
	echo '<li><a href="' . esc_url( tls_fallback_privacy_url() ) . '">' . esc_html__( 'Privacy Policy', 'the-learning-studio' ) . '</a></li>';
	echo '<li><a href="' . esc_url( tls_fallback_page_url( 'terms', '/terms/' ) ) . '">' . esc_html__( 'Terms of Use', 'the-learning-studio' ) . '</a></li>';
	echo '</ul>';
}
