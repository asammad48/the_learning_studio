<?php
/**
 * One-time WP-CLI importer for the legacy JSON content.
 *
 * Usage: wp tls import --source=/absolute/path/to/the_learning_studio
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tls_import_json_content( string $source ): array {
	$source = untrailingslashit( $source );
	$data_directory = is_dir( $source . '/data' ) ? $source . '/data' : $source;
	$files  = array( 'subjects', 'lessons', 'pages' );
	$data   = array();

	foreach ( $files as $name ) {
		$path = $data_directory . '/' . $name . '.json';
		if ( ! is_readable( $path ) ) {
			throw new RuntimeException( sprintf( 'Cannot read %s.', $path ) );
		}
		$decoded = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $decoded ) ) {
			throw new RuntimeException( sprintf( '%s is not valid JSON.', $path ) );
		}
		$data[ $name ] = $decoded;
	}

	$result = array( 'subjects' => 0, 'lessons' => 0, 'pages' => 0 );
	foreach ( $data['subjects'] as $subject ) {
		$existing = term_exists( sanitize_title( $subject['slug'] ), 'subject' );
		$args     = array(
			'slug'        => sanitize_title( $subject['slug'] ),
			'description' => sanitize_textarea_field( $subject['description'] ?? '' ),
		);
		if ( $existing ) {
			$term = wp_update_term( (int) ( is_array( $existing ) ? $existing['term_id'] : $existing ), 'subject', array_merge( $args, array( 'name' => sanitize_text_field( $subject['name'] ) ) ) );
		} else {
			$term = wp_insert_term( sanitize_text_field( $subject['name'] ), 'subject', $args );
		}
		if ( ! is_wp_error( $term ) ) {
			$term_id = (int) $term['term_id'];
			update_term_meta( $term_id, '_tls_subject_group', sanitize_text_field( $subject['category'] ?? '' ) );
			update_term_meta( $term_id, '_tls_featured', ! empty( $subject['featured'] ) );
			$result['subjects']++;
		}
	}

	foreach ( $data['lessons'] as $lesson ) {
		$existing = get_page_by_path( sanitize_title( $lesson['slug'] ), OBJECT, 'lesson' );
		$body     = $lesson['contentHtml'] ?? '';
		if ( ! $body && ! empty( $lesson['body'] ) ) {
			$body = implode( "\n\n", array_map( static fn( $paragraph ) => '<p>' . esc_html( $paragraph ) . '</p>', $lesson['body'] ) );
		}
		$post_id = wp_insert_post(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'lesson',
				'post_status'  => 'publish',
				'post_title'   => sanitize_text_field( $lesson['title'] ),
				'post_name'    => sanitize_title( $lesson['slug'] ),
				'post_excerpt' => sanitize_textarea_field( $lesson['excerpt'] ?? '' ),
				'post_content' => wp_kses_post( $body ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			continue;
		}
		$subject = get_term_by( 'slug', sanitize_title( $lesson['subjectSlug'] ?? '' ), 'subject' );
		if ( $subject ) {
			wp_set_object_terms( $post_id, array( $subject->term_id ), 'subject' );
		}
		$has_video = ! empty( $lesson['youtubeUrl'] );
		update_post_meta( $post_id, '_tls_lesson_format', $has_video && $body ? 'video-written' : ( $has_video ? 'video' : 'written' ) );
		update_post_meta( $post_id, '_tls_duration', sanitize_text_field( $lesson['duration'] ?? '' ) );
		update_post_meta( $post_id, '_tls_youtube_url', esc_url_raw( $lesson['youtubeUrl'] ?? '' ) );
		update_post_meta( $post_id, '_tls_featured', ! empty( $lesson['featured'] ) );
		$quiz = array_map(
			static fn( $item ) => array(
				'question' => sanitize_text_field( $item['question'] ?? '' ),
				'answer'   => sanitize_textarea_field( $item['answer'] ?? '' ),
			),
			$lesson['quiz'] ?? array()
		);
		update_post_meta( $post_id, '_tls_quiz', $quiz );
		$result['lessons']++;
	}

	foreach ( $data['pages'] as $page ) {
		$existing = get_page_by_path( sanitize_title( $page['slug'] ), OBJECT, 'page' );
		$post_id  = wp_insert_post(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => sanitize_text_field( $page['title'] ),
				'post_name'    => sanitize_title( $page['slug'] ),
				'post_excerpt' => sanitize_textarea_field( $page['description'] ?? '' ),
				'post_content' => wpautop( esc_html( $page['body'] ?? '' ) ),
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			$result['pages']++;
		}
	}

	if ( ! get_page_by_path( 'subjects', OBJECT, 'page' ) ) {
		wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => __( 'Subjects', 'the-learning-studio' ),
				'post_name'   => 'subjects',
			)
		);
	}

	flush_rewrite_rules();
	return $result;
}

function tls_register_import_page(): void {
	add_management_page(
		__( 'Import Learning Studio content', 'the-learning-studio' ),
		__( 'Learning Studio Import', 'the-learning-studio' ),
		'manage_options',
		'tls-content-import',
		'tls_render_import_page'
	);
}
add_action( 'admin_menu', 'tls_register_import_page' );

function tls_render_import_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import content.', 'the-learning-studio' ) );
	}
	$result = null;
	$error  = '';
	if ( isset( $_POST['tls_import_submit'] ) ) {
		check_admin_referer( 'tls_import_content', 'tls_import_nonce' );
		try {
			$result = tls_import_json_content( get_template_directory() . '/legacy-data' );
		} catch ( RuntimeException $exception ) {
			$error = $exception->getMessage();
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import Learning Studio content', 'the-learning-studio' ); ?></h1>
		<p><?php esc_html_e( 'Import the subjects, lessons, quizzes, and pages bundled with this theme. Existing records with matching slugs are updated.', 'the-learning-studio' ); ?></p>
		<?php if ( $result ) : ?><div class="notice notice-success"><p><?php echo esc_html( sprintf( __( 'Imported %1$d subjects, %2$d lessons, and %3$d pages.', 'the-learning-studio' ), $result['subjects'], $result['lessons'], $result['pages'] ) ); ?></p></div><?php endif; ?>
		<?php if ( $error ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'tls_import_content', 'tls_import_nonce' ); ?>
			<?php submit_button( __( 'Import bundled content', 'the-learning-studio' ), 'primary', 'tls_import_submit' ); ?>
		</form>
	</div>
	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/** Import command exposed only inside WP-CLI. */
	final class TLS_Import_Command {
		/**
		 * Import the static site's JSON data into WordPress.
		 *
		 * ## OPTIONS
		 *
		 * --source=<path>
		 * : Absolute path to the original project directory.
		 */
		public function __invoke( array $args, array $assoc_args ): void {
			if ( empty( $assoc_args['source'] ) ) {
				WP_CLI::error( 'Provide --source=/absolute/path/to/the_learning_studio.' );
			}
			try {
				$result = tls_import_json_content( $assoc_args['source'] );
				WP_CLI::success( sprintf( 'Imported %d subjects, %d lessons, and %d pages.', $result['subjects'], $result['lessons'], $result['pages'] ) );
			} catch ( RuntimeException $error ) {
				WP_CLI::error( $error->getMessage() );
			}
		}
	}
	WP_CLI::add_command( 'tls import', 'TLS_Import_Command' );
}
