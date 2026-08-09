<?php
/**
 * WP-CLI + admin importer for the legacy JSON content.
 *
 * Usage: wp tls import --source=/absolute/path/to/the_learning_studio [--dry-run] [--update-existing]
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import, or preview importing, the bundled legacy JSON content.
 *
 * @param string $source          Directory containing subjects.json, lessons.json, pages.json, and optionally posts.json (or a /data subdirectory of them).
 * @param bool   $dry_run         When true, no database writes happen; the same create/update/skip decisions are reported instead.
 * @param bool   $update_existing When true, records whose slug already exists are updated; when false they are left untouched and reported as skipped.
 * @return array{dry_run:bool,subjects:array<int,array<string,string>>,lessons:array<int,array<string,string>>,pages:array<int,array<string,string>>,posts:array<int,array<string,string>>}
 */
function tls_import_json_content( string $source, bool $dry_run = false, bool $update_existing = false ): array {
	$source         = untrailingslashit( $source );
	$data_directory = is_dir( $source . '/data' ) ? $source . '/data' : $source;
	$files          = array( 'subjects', 'lessons', 'pages' );
	$data           = array();

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

	// posts.json is optional: older exports and hand-built --source directories
	// may not have blog posts at all.
	$data['posts'] = array();
	$posts_path    = $data_directory . '/posts.json';
	if ( is_readable( $posts_path ) ) {
		$decoded = json_decode( (string) file_get_contents( $posts_path ), true );
		if ( ! is_array( $decoded ) ) {
			throw new RuntimeException( sprintf( '%s is not valid JSON.', $posts_path ) );
		}
		$data['posts'] = $decoded;
	}

	$result = array(
		'dry_run'  => $dry_run,
		'subjects' => array(),
		'lessons'  => array(),
		'pages'    => array(),
		'posts'    => array(),
	);
	$changed = false;

	foreach ( $data['subjects'] as $subject ) {
		$slug     = sanitize_title( $subject['slug'] ?? '' );
		$name     = sanitize_text_field( $subject['name'] ?? '' );
		$existing = $slug ? term_exists( $slug, 'subject' ) : null;
		$term_id  = $existing ? (int) ( is_array( $existing ) ? $existing['term_id'] : $existing ) : 0;

		if ( $term_id && ! $update_existing ) {
			$result['subjects'][] = array(
				'action'  => 'skipped',
				'title'   => $name,
				'slug'    => $slug,
				'message' => __( 'A Subject with this slug already exists.', 'the-learning-studio' ),
			);
			continue;
		}

		$action = $term_id ? 'updated' : 'created';
		if ( $dry_run ) {
			$result['subjects'][] = array( 'action' => $action, 'title' => $name, 'slug' => $slug, 'message' => '' );
			continue;
		}

		$args = array(
			'slug'        => $slug,
			'description' => sanitize_textarea_field( $subject['description'] ?? '' ),
		);
		$term = $term_id
			? wp_update_term( $term_id, 'subject', array_merge( $args, array( 'name' => $name ) ) )
			: wp_insert_term( $name, 'subject', $args );

		if ( is_wp_error( $term ) ) {
			$result['subjects'][] = array( 'action' => 'error', 'title' => $name, 'slug' => $slug, 'message' => $term->get_error_message() );
			continue;
		}

		$term_id = (int) $term['term_id'];
		update_term_meta( $term_id, '_tls_subject_group', sanitize_text_field( $subject['category'] ?? '' ) );
		update_term_meta( $term_id, '_tls_featured', ! empty( $subject['featured'] ) );
		update_term_meta( $term_id, '_tls_import_source', $slug );
		update_term_meta( $term_id, '_tls_import_date', current_time( 'mysql' ) );
		update_term_meta( $term_id, '_tls_import_hash', md5( (string) wp_json_encode( $subject ) ) );
		$changed               = true;
		$result['subjects'][] = array( 'action' => $action, 'title' => $name, 'slug' => $slug, 'message' => '' );
	}

	foreach ( $data['lessons'] as $lesson ) {
		$slug     = sanitize_title( $lesson['slug'] ?? '' );
		$title    = sanitize_text_field( $lesson['title'] ?? '' );
		$existing = $slug ? get_page_by_path( $slug, OBJECT, 'lesson' ) : null;

		if ( $existing && ! $update_existing ) {
			$result['lessons'][] = array(
				'action'  => 'skipped',
				'title'   => $title,
				'slug'    => $slug,
				'message' => __( 'A Lesson with this slug already exists.', 'the-learning-studio' ),
			);
			continue;
		}

		$action = $existing ? 'updated' : 'created';
		if ( $dry_run ) {
			$result['lessons'][] = array( 'action' => $action, 'title' => $title, 'slug' => $slug, 'message' => '' );
			continue;
		}

		$body = $lesson['contentHtml'] ?? '';
		if ( ! $body && ! empty( $lesson['body'] ) ) {
			$body = implode( "\n\n", array_map( static fn( $paragraph ) => '<p>' . esc_html( $paragraph ) . '</p>', $lesson['body'] ) );
		}
		$post_args = array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'lesson',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => sanitize_textarea_field( $lesson['excerpt'] ?? '' ),
			'post_content' => wp_kses_post( $body ),
		);
		if ( ! $existing ) {
			// Only new content gets a publication status; updates preserve
			// whatever status an administrator already set (draft, private, ...).
			$post_args['post_status'] = 'publish';
		}
		$post_id = wp_insert_post( $post_args, true );

		if ( is_wp_error( $post_id ) ) {
			$result['lessons'][] = array( 'action' => 'error', 'title' => $title, 'slug' => $slug, 'message' => $post_id->get_error_message() );
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
		update_post_meta( $post_id, '_tls_import_source', $slug );
		update_post_meta( $post_id, '_tls_import_date', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_tls_import_hash', md5( (string) wp_json_encode( $lesson ) ) );
		$changed              = true;
		$result['lessons'][] = array( 'action' => $action, 'title' => $title, 'slug' => $slug, 'message' => '' );
	}

	foreach ( $data['pages'] as $page ) {
		$slug     = sanitize_title( $page['slug'] ?? '' );
		$title    = sanitize_text_field( $page['title'] ?? '' );
		$existing = $slug ? get_page_by_path( $slug, OBJECT, 'page' ) : null;

		if ( $existing && ! $update_existing ) {
			$result['pages'][] = array(
				'action'  => 'skipped',
				'title'   => $title,
				'slug'    => $slug,
				'message' => __( 'A Page with this slug already exists.', 'the-learning-studio' ),
			);
			continue;
		}

		$action = $existing ? 'updated' : 'created';
		if ( $dry_run ) {
			$result['pages'][] = array( 'action' => $action, 'title' => $title, 'slug' => $slug, 'message' => '' );
			continue;
		}

		$post_args = array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'page',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => sanitize_textarea_field( $page['description'] ?? '' ),
			'post_content' => wpautop( esc_html( $page['body'] ?? '' ) ),
		);
		if ( ! $existing ) {
			$post_args['post_status'] = 'publish';
		}
		$post_id = wp_insert_post( $post_args, true );

		if ( is_wp_error( $post_id ) ) {
			$result['pages'][] = array( 'action' => 'error', 'title' => $title, 'slug' => $slug, 'message' => $post_id->get_error_message() );
			continue;
		}

		update_post_meta( $post_id, '_tls_import_source', $slug );
		update_post_meta( $post_id, '_tls_import_date', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_tls_import_hash', md5( (string) wp_json_encode( $page ) ) );
		$changed            = true;
		$result['pages'][] = array( 'action' => $action, 'title' => $title, 'slug' => $slug, 'message' => '' );
	}

	foreach ( $data['posts'] as $post ) {
		$slug     = sanitize_title( $post['slug'] ?? '' );
		$title    = sanitize_text_field( $post['title'] ?? '' );
		$existing = $slug ? get_page_by_path( $slug, OBJECT, 'post' ) : null;

		if ( $existing && ! $update_existing ) {
			$result['posts'][] = array(
				'action'  => 'skipped',
				'title'   => $title,
				'slug'    => $slug,
				'message' => __( 'A Post with this slug already exists.', 'the-learning-studio' ),
			);
			continue;
		}

		$action = $existing ? 'updated' : 'created';
		if ( $dry_run ) {
			$result['posts'][] = array( 'action' => $action, 'title' => $title, 'slug' => $slug, 'message' => '' );
			continue;
		}

		$body = implode(
			"\n\n",
			array_map( static fn( $paragraph ) => '<p>' . esc_html( $paragraph ) . '</p>', $post['body'] ?? array() )
		);
		$post_args = array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'post',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => sanitize_textarea_field( $post['excerpt'] ?? '' ),
			'post_content' => wp_kses_post( $body ),
		);
		if ( ! $existing ) {
			$post_args['post_status'] = 'publish';
		}
		$post_id = wp_insert_post( $post_args, true );

		if ( is_wp_error( $post_id ) ) {
			$result['posts'][] = array( 'action' => 'error', 'title' => $title, 'slug' => $slug, 'message' => $post_id->get_error_message() );
			continue;
		}

		$categories = array_map( 'sanitize_text_field', $post['categories'] ?? array() );
		if ( $categories ) {
			wp_set_object_terms( $post_id, $categories, 'category' );
		}
		$tags = array_map( 'sanitize_text_field', $post['tags'] ?? array() );
		if ( $tags ) {
			wp_set_object_terms( $post_id, $tags, 'post_tag' );
		}
		update_post_meta( $post_id, '_tls_import_source', $slug );
		update_post_meta( $post_id, '_tls_import_date', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_tls_import_hash', md5( (string) wp_json_encode( $post ) ) );
		$changed            = true;
		$result['posts'][] = array( 'action' => $action, 'title' => $title, 'slug' => $slug, 'message' => '' );
	}

	if ( ! $dry_run && ! get_page_by_path( 'subjects', OBJECT, 'page' ) ) {
		wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => __( 'Subjects', 'the-learning-studio' ),
				'post_name'   => 'subjects',
			)
		);
		$changed = true;
	}

	if ( $changed ) {
		flush_rewrite_rules();
	}

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

/**
 * Render one result table for the admin import screen.
 *
 * @param string                        $label Section heading.
 * @param array<int,array<string,string>> $items Result rows for this content type.
 */
function tls_render_import_result_table( string $label, array $items ): void {
	?>
	<h2><?php echo esc_html( $label ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Title', 'the-learning-studio' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'the-learning-studio' ); ?></th>
				<th><?php esc_html_e( 'Result', 'the-learning-studio' ); ?></th>
				<th><?php esc_html_e( 'Detail', 'the-learning-studio' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $items ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No items.', 'the-learning-studio' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $items as $item ) : ?>
					<tr>
						<td><?php echo esc_html( $item['title'] ); ?></td>
						<td><code><?php echo esc_html( $item['slug'] ); ?></code></td>
						<td><?php echo esc_html( ucfirst( $item['action'] ) ); ?></td>
						<td><?php echo esc_html( $item['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
}

function tls_render_import_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import content.', 'the-learning-studio' ) );
	}
	$result = null;
	$error  = '';
	if ( isset( $_POST['tls_import_submit'] ) ) {
		check_admin_referer( 'tls_import_content', 'tls_import_nonce' );
		$dry_run         = ! empty( $_POST['tls_dry_run'] );
		$update_existing = ! empty( $_POST['tls_update_existing'] );
		try {
			$result = tls_import_json_content( get_template_directory() . '/legacy-data', $dry_run, $update_existing );
		} catch ( RuntimeException $exception ) {
			$error = $exception->getMessage();
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import Learning Studio content', 'the-learning-studio' ); ?></h1>
		<p><?php esc_html_e( 'Import the subjects, lessons, quizzes, pages, and blog posts bundled with this theme.', 'the-learning-studio' ); ?></p>
		<?php if ( $error ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>
		<?php if ( $result ) : ?>
			<div class="notice <?php echo $result['dry_run'] ? 'notice-info' : 'notice-success'; ?>">
				<p>
					<?php
					echo $result['dry_run']
						? esc_html__( 'Dry run complete. No content was changed.', 'the-learning-studio' )
						: esc_html__( 'Import complete.', 'the-learning-studio' );
					?>
				</p>
			</div>
			<?php
			tls_render_import_result_table( __( 'Subjects', 'the-learning-studio' ), $result['subjects'] );
			tls_render_import_result_table( __( 'Lessons', 'the-learning-studio' ), $result['lessons'] );
			tls_render_import_result_table( __( 'Pages', 'the-learning-studio' ), $result['pages'] );
			tls_render_import_result_table( __( 'Posts', 'the-learning-studio' ), $result['posts'] );
			?>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'tls_import_content', 'tls_import_nonce' ); ?>
			<p><label><input type="checkbox" name="tls_dry_run" value="1" checked> <?php esc_html_e( 'Dry run (preview only, no changes are saved)', 'the-learning-studio' ); ?></label></p>
			<p><label><input type="checkbox" name="tls_update_existing" value="1"> <?php esc_html_e( 'Update content that already exists (matched by slug)', 'the-learning-studio' ); ?></label></p>
			<p class="description"><?php esc_html_e( 'Leave "Update existing" unchecked to only create new Subjects, Lessons, Pages, and Posts, leaving anything already present untouched.', 'the-learning-studio' ); ?></p>
			<?php submit_button( __( 'Run import', 'the-learning-studio' ), 'primary', 'tls_import_submit' ); ?>
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
		 *
		 * [--dry-run]
		 * : Preview the import without writing any changes.
		 *
		 * [--update-existing]
		 * : Update Subjects, Lessons, Pages, and Posts that already exist with a matching slug. Without this flag, existing items are left untouched and reported as skipped.
		 */
		public function __invoke( array $args, array $assoc_args ): void {
			if ( empty( $assoc_args['source'] ) ) {
				WP_CLI::error( 'Provide --source=/absolute/path/to/the_learning_studio.' );
			}
			try {
				$result = tls_import_json_content(
					$assoc_args['source'],
					isset( $assoc_args['dry-run'] ),
					isset( $assoc_args['update-existing'] )
				);
			} catch ( RuntimeException $error ) {
				WP_CLI::error( $error->getMessage() );
				return;
			}
			foreach ( array( 'subjects', 'lessons', 'pages', 'posts' ) as $key ) {
				foreach ( $result[ $key ] as $item ) {
					$detail = $item['message'] ? ' - ' . $item['message'] : '';
					WP_CLI::log( sprintf( '[%s] %s (%s): %s%s', ucfirst( $key ), $item['title'], $item['slug'], $item['action'], $detail ) );
				}
			}
			WP_CLI::success( $result['dry_run'] ? 'Dry run complete.' : 'Import complete.' );
		}
	}
	WP_CLI::add_command( 'tls import', 'TLS_Import_Command' );
}
