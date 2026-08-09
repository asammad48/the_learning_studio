<?php
/**
 * Site header.
 *
 * @package TheLearningStudio
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'the-learning-studio' ); ?></a>
<header class="top">
	<nav class="wrap nav" aria-label="<?php esc_attr_e( 'Main navigation', 'the-learning-studio' ); ?>">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php tls_site_logo(); ?>
			<span class="script"><?php bloginfo( 'name' ); ?></span>
		</a>
		<button class="menu-toggle" type="button" aria-controls="primary-menu" aria-expanded="false"><span class="menu-toggle-label"><?php esc_html_e( 'Menu', 'the-learning-studio' ); ?></span><span aria-hidden="true">&#9776;</span></button>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'links',
				'menu_id'        => 'primary-menu',
				'fallback_cb'    => 'tls_fallback_menu',
			)
		);
		?>
	</nav>
</header>
<main id="main-content">
