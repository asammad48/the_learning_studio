<?php
/** Homepage presentation settings. @package TheLearningStudio */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clamp a homepage item count to a safe range for Customizer settings.
 *
 * @param mixed $value Raw Customizer value.
 * @return int
 */
function tls_sanitize_count( $value ): int {
	return max( 1, min( 12, (int) $value ) );
}

/**
 * Sanitize a Customizer checkbox value to a real boolean.
 *
 * @param mixed $value Raw Customizer value.
 * @return bool
 */
function tls_sanitize_checkbox( $value ): bool {
	return (bool) $value;
}

function tls_customize_register( WP_Customize_Manager $customizer ): void {
	$customizer->add_section( 'tls_home', array( 'title' => __( 'Homepage content', 'the-learning-studio' ), 'priority' => 30 ) );

	$settings = array(
		'tls_hero_eyebrow'     => array( __( 'Hero eyebrow', 'the-learning-studio' ), __( 'A global learning library', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_hero_title'       => array( __( 'Hero title', 'the-learning-studio' ), __( 'Learn complex subjects, simply.', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_hero_intro'       => array( __( 'Hero introduction', 'the-learning-studio' ), __( 'Study business, AI, technology, health, finance, marketing, psychology and operations through clear lessons made for real understanding.', 'the-learning-studio' ), 'sanitize_textarea_field', 'textarea' ),
		'tls_panel_eyebrow'    => array( __( 'Feature panel eyebrow', 'the-learning-studio' ), __( 'Start exploring', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_panel_title'      => array( __( 'Feature panel title', 'the-learning-studio' ), __( 'Lessons for curious minds', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_panel_text'       => array( __( 'Feature panel text', 'the-learning-studio' ), __( 'Definitions, examples, videos, notes and quick quizzes in one growing library.', 'the-learning-studio' ), 'sanitize_textarea_field', 'textarea' ),
		'tls_panel_cta_label'  => array( __( 'Feature panel button label', 'the-learning-studio' ), __( 'Start learning', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_panel_cta_url'    => array( __( 'Feature panel button URL (defaults to the Lesson library)', 'the-learning-studio' ), '', 'esc_url_raw', 'url' ),
		'tls_show_subjects'    => array( __( 'Show the Featured subjects section', 'the-learning-studio' ), true, 'tls_sanitize_checkbox', 'checkbox' ),
		'tls_subjects_eyebrow' => array( __( 'Subjects section eyebrow', 'the-learning-studio' ), __( 'Featured subjects', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_subjects_heading' => array( __( 'Subjects section heading', 'the-learning-studio' ), __( 'Choose what to learn', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_subjects_count'   => array( __( 'Number of subjects to show (1-12)', 'the-learning-studio' ), 8, 'tls_sanitize_count', 'number' ),
		'tls_show_lessons'     => array( __( 'Show the Featured lessons section', 'the-learning-studio' ), true, 'tls_sanitize_checkbox', 'checkbox' ),
		'tls_lessons_eyebrow'  => array( __( 'Lessons section eyebrow', 'the-learning-studio' ), __( 'Featured and latest lessons', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_lessons_heading'  => array( __( 'Lessons section heading', 'the-learning-studio' ), __( 'Selected written notes and videos', 'the-learning-studio' ), 'sanitize_text_field', 'text' ),
		'tls_lessons_count'    => array( __( 'Number of lessons to show (1-12)', 'the-learning-studio' ), 3, 'tls_sanitize_count', 'number' ),
	);

	foreach ( $settings as $id => $details ) {
		list( $label, $default, $sanitize_callback, $type ) = $details;
		$customizer->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => $sanitize_callback, 'transport' => 'refresh' ) );
		$control_args = array( 'section' => 'tls_home', 'label' => $label, 'type' => $type );
		if ( 'number' === $type ) {
			$control_args['input_attrs'] = array( 'min' => 1, 'max' => 12, 'step' => 1 );
		}
		$customizer->add_control( $id, $control_args );
	}
}
add_action( 'customize_register', 'tls_customize_register' );
