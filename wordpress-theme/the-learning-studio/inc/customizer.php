<?php
/** Homepage presentation settings. @package TheLearningStudio */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tls_customize_register( WP_Customize_Manager $customizer ): void {
	$customizer->add_section( 'tls_home', array( 'title' => __( 'Homepage content', 'the-learning-studio' ), 'priority' => 30 ) );
	$settings = array(
		'tls_hero_eyebrow' => array( __( 'Hero eyebrow', 'the-learning-studio' ), __( 'A global learning library', 'the-learning-studio' ), 'sanitize_text_field' ),
		'tls_hero_title'   => array( __( 'Hero title', 'the-learning-studio' ), __( 'Learn complex subjects, simply.', 'the-learning-studio' ), 'sanitize_text_field' ),
		'tls_hero_intro'   => array( __( 'Hero introduction', 'the-learning-studio' ), __( 'Study business, AI, technology, health, finance, marketing, psychology and operations through clear lessons made for real understanding.', 'the-learning-studio' ), 'sanitize_textarea_field' ),
		'tls_panel_title'  => array( __( 'Feature panel title', 'the-learning-studio' ), __( 'Lessons for curious minds', 'the-learning-studio' ), 'sanitize_text_field' ),
		'tls_panel_text'   => array( __( 'Feature panel text', 'the-learning-studio' ), __( 'Definitions, examples, videos, notes and quick quizzes in one growing library.', 'the-learning-studio' ), 'sanitize_textarea_field' ),
	);
	foreach ( $settings as $id => $details ) {
		$customizer->add_setting( $id, array( 'default' => $details[1], 'sanitize_callback' => $details[2], 'transport' => 'refresh' ) );
		$customizer->add_control( $id, array( 'section' => 'tls_home', 'label' => $details[0], 'type' => str_contains( $id, 'intro' ) || str_contains( $id, 'text' ) ? 'textarea' : 'text' ) );
	}
}
add_action( 'customize_register', 'tls_customize_register' );
