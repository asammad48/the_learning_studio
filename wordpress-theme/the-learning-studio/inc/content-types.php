<?php
/**
 * Lesson content type, subject taxonomy, and editor fields.
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tls_register_content_types(): void {
	register_post_type(
		'lesson',
		array(
			'labels' => array(
				'name'          => __( 'Lessons', 'the-learning-studio' ),
				'singular_name' => __( 'Lesson', 'the-learning-studio' ),
				'add_new_item'  => __( 'Add New Lesson', 'the-learning-studio' ),
				'edit_item'     => __( 'Edit Lesson', 'the-learning-studio' ),
				'all_items'     => __( 'All Lessons', 'the-learning-studio' ),
				'menu_name'     => __( 'Lessons', 'the-learning-studio' ),
			),
			'public'       => true,
			'show_in_rest' => true,
			'has_archive'  => 'lessons',
			'rewrite'      => array( 'slug' => 'lessons' ),
			'menu_icon'    => 'dashicons-welcome-learn-more',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
		)
	);

	register_taxonomy(
		'subject',
		array( 'lesson' ),
		array(
			'labels' => array(
				'name'          => __( 'Subjects', 'the-learning-studio' ),
				'singular_name' => __( 'Subject', 'the-learning-studio' ),
				'add_new_item'  => __( 'Add New Subject', 'the-learning-studio' ),
				'edit_item'     => __( 'Edit Subject', 'the-learning-studio' ),
			),
			'public'            => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'subjects' ),
		)
	);

	$meta = array(
		'_tls_lesson_format' => array( 'type' => 'string', 'default' => 'written' ),
		'_tls_duration'      => array( 'type' => 'string', 'default' => '' ),
		'_tls_youtube_url'   => array( 'type' => 'string', 'default' => '' ),
		'_tls_featured'      => array( 'type' => 'boolean', 'default' => false ),
		'_tls_priority'      => array( 'type' => 'integer', 'default' => 0 ),
		'_tls_quiz'          => array( 'type' => 'array', 'default' => array() ),
	);

	foreach ( $meta as $key => $args ) {
		register_post_meta(
			'lesson',
			$key,
			array_merge(
				$args,
				array(
					'single'       => true,
					'show_in_rest' => false,
					'auth_callback' => static fn() => current_user_can( 'edit_posts' ),
				)
			)
		);
	}
}
add_action( 'init', 'tls_register_content_types' );

function tls_add_lesson_meta_boxes(): void {
	add_meta_box( 'tls-lesson-details', __( 'Lesson Details', 'the-learning-studio' ), 'tls_render_lesson_details', 'lesson', 'side', 'high' );
	add_meta_box( 'tls-lesson-quiz', __( 'Quick Quiz', 'the-learning-studio' ), 'tls_render_lesson_quiz', 'lesson', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'tls_add_lesson_meta_boxes' );

function tls_render_lesson_details( WP_Post $post ): void {
	wp_nonce_field( 'tls_save_lesson', 'tls_lesson_nonce' );
	$format   = get_post_meta( $post->ID, '_tls_lesson_format', true ) ?: 'written';
	$duration = get_post_meta( $post->ID, '_tls_duration', true );
	$youtube  = get_post_meta( $post->ID, '_tls_youtube_url', true );
	$featured = (bool) get_post_meta( $post->ID, '_tls_featured', true );
	$priority = (int) get_post_meta( $post->ID, '_tls_priority', true );
	?>
	<p><label for="tls_lesson_format"><strong><?php esc_html_e( 'Lesson format', 'the-learning-studio' ); ?></strong></label></p>
	<select id="tls_lesson_format" name="tls_lesson_format" class="widefat">
		<option value="written" <?php selected( $format, 'written' ); ?>><?php esc_html_e( 'Written lesson', 'the-learning-studio' ); ?></option>
		<option value="video" <?php selected( $format, 'video' ); ?>><?php esc_html_e( 'Video lesson', 'the-learning-studio' ); ?></option>
		<option value="video-written" <?php selected( $format, 'video-written' ); ?>><?php esc_html_e( 'Video + written lesson', 'the-learning-studio' ); ?></option>
	</select>
	<p><label for="tls_duration"><strong><?php esc_html_e( 'Duration / read time', 'the-learning-studio' ); ?></strong></label></p>
	<input id="tls_duration" name="tls_duration" class="widefat" value="<?php echo esc_attr( $duration ); ?>" placeholder="<?php esc_attr_e( 'e.g. 10 min read', 'the-learning-studio' ); ?>">
	<p><label for="tls_youtube_url"><strong><?php esc_html_e( 'YouTube URL', 'the-learning-studio' ); ?></strong></label></p>
	<input type="url" id="tls_youtube_url" name="tls_youtube_url" class="widefat" value="<?php echo esc_attr( $youtube ); ?>" placeholder="https://youtube.com/watch?v=...">
	<p><label><input type="checkbox" name="tls_featured" value="1" <?php checked( $featured ); ?>> <?php esc_html_e( 'Feature this lesson', 'the-learning-studio' ); ?></label></p>
	<p><label for="tls_priority"><strong><?php esc_html_e( 'Featured order priority', 'the-learning-studio' ); ?></strong></label></p>
	<input type="number" id="tls_priority" name="tls_priority" class="widefat" min="-100" max="100" step="1" value="<?php echo esc_attr( (string) $priority ); ?>">
	<p class="description"><?php esc_html_e( 'Higher numbers appear first among featured lessons. Lessons with the same priority fall back to newest first.', 'the-learning-studio' ); ?></p>
	<?php
}

/**
 * Render one quiz question/answer fieldset.
 *
 * Each row needs its own explicit index shared by its question and answer
 * field so PHP groups them together on submit -- unindexed `tls_quiz[][x]`
 * names would give the question and answer of one row different array
 * positions, since PHP auto-increments `[]` per bracket path, not per row.
 * The template row uses the literal placeholder "__new__", which JS
 * replaces with a value guaranteed not to collide with server-rendered rows.
 *
 * @param string $index       Array index/key used in the field names.
 * @param string $question    Question text.
 * @param string $answer      Answer text.
 * @param int    $number      1-based row number for the legend, ignored when $is_template.
 * @param bool   $is_template Whether this is the hidden template row cloned by JS.
 */
function tls_render_quiz_row( string $index, string $question, string $answer, int $number, bool $is_template = false ): void {
	?>
	<fieldset class="tls-quiz-row" style="margin:0 0 16px;padding:12px;border:1px solid #dcdcde">
		<legend><strong><?php echo esc_html( $is_template ? __( 'New question', 'the-learning-studio' ) : sprintf( __( 'Question %d', 'the-learning-studio' ), $number ) ); ?></strong></legend>
		<p><label><?php esc_html_e( 'Question', 'the-learning-studio' ); ?><br><input class="widefat" name="tls_quiz[<?php echo esc_attr( $index ); ?>][question]" value="<?php echo esc_attr( $question ); ?>"></label></p>
		<p><label><?php esc_html_e( 'Answer', 'the-learning-studio' ); ?><br><textarea class="widefat" rows="2" name="tls_quiz[<?php echo esc_attr( $index ); ?>][answer]"><?php echo esc_textarea( $answer ); ?></textarea></label></p>
		<p><button type="button" class="button-link-delete tls-quiz-remove"><?php esc_html_e( 'Remove question', 'the-learning-studio' ); ?></button></p>
	</fieldset>
	<?php
}

function tls_render_lesson_quiz( WP_Post $post ): void {
	$quiz = get_post_meta( $post->ID, '_tls_quiz', true );
	$quiz = ( is_array( $quiz ) && $quiz ) ? $quiz : array( array( 'question' => '', 'answer' => '' ) );
	?>
	<div class="tls-quiz-rows">
		<?php foreach ( array_values( $quiz ) as $index => $item ) : ?>
			<?php tls_render_quiz_row( (string) $index, (string) ( $item['question'] ?? '' ), (string) ( $item['answer'] ?? '' ), $index + 1 ); ?>
		<?php endforeach; ?>
	</div>
	<p><button type="button" class="button tls-quiz-add"><?php esc_html_e( 'Add question', 'the-learning-studio' ); ?></button></p>
	<template id="tls-quiz-row-template"><?php tls_render_quiz_row( '__new__', '', '', 0, true ); ?></template>
	<p class="description"><?php esc_html_e( 'Add or remove rows as needed. A row is saved only when it has both a question and an answer. Answers are revealed by visitors on the lesson page.', 'the-learning-studio' ); ?></p>
	<?php
}

/**
 * Enqueue the quiz row add/remove script only on the Lesson edit screen.
 *
 * @param string $hook Current admin page hook suffix.
 */
function tls_enqueue_lesson_quiz_admin( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'lesson' !== $screen->post_type ) {
		return;
	}
	$js_path = get_template_directory() . '/assets/admin-lesson-quiz.js';
	wp_enqueue_script( 'tls-admin-lesson-quiz', get_template_directory_uri() . '/assets/admin-lesson-quiz.js', array(), (string) filemtime( $js_path ), true );
}
add_action( 'admin_enqueue_scripts', 'tls_enqueue_lesson_quiz_admin' );

function tls_save_lesson_meta( int $post_id ): void {
	if ( ! isset( $_POST['tls_lesson_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tls_lesson_nonce'] ) ), 'tls_save_lesson' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$formats = array( 'written', 'video', 'video-written' );
	$format  = isset( $_POST['tls_lesson_format'] ) ? sanitize_key( wp_unslash( $_POST['tls_lesson_format'] ) ) : 'written';
	update_post_meta( $post_id, '_tls_lesson_format', in_array( $format, $formats, true ) ? $format : 'written' );
	update_post_meta( $post_id, '_tls_duration', isset( $_POST['tls_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['tls_duration'] ) ) : '' );
	update_post_meta( $post_id, '_tls_youtube_url', isset( $_POST['tls_youtube_url'] ) ? esc_url_raw( wp_unslash( $_POST['tls_youtube_url'] ) ) : '' );
	update_post_meta( $post_id, '_tls_featured', isset( $_POST['tls_featured'] ) );
	$priority = isset( $_POST['tls_priority'] ) ? (int) wp_unslash( $_POST['tls_priority'] ) : 0;
	update_post_meta( $post_id, '_tls_priority', max( -100, min( 100, $priority ) ) );

	$quiz = array();
	if ( isset( $_POST['tls_quiz'] ) && is_array( $_POST['tls_quiz'] ) ) {
		foreach ( wp_unslash( $_POST['tls_quiz'] ) as $item ) {
			$question = sanitize_text_field( $item['question'] ?? '' );
			$answer   = sanitize_textarea_field( $item['answer'] ?? '' );
			if ( $question && $answer ) {
				$quiz[] = compact( 'question', 'answer' );
			}
		}
	}
	update_post_meta( $post_id, '_tls_quiz', $quiz );
}
add_action( 'save_post_lesson', 'tls_save_lesson_meta' );

/**
 * Render the Media Library picker used for a Subject's image.
 *
 * @param int $term_id Term ID being edited, or 0 on the "Add Subject" screen.
 */
function tls_render_subject_image_field( int $term_id = 0 ): void {
	$image_id  = $term_id ? (int) get_term_meta( $term_id, '_tls_image_id', true ) : 0;
	$image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
	?>
	<div class="tls-subject-media">
		<input type="hidden" name="tls_subject_image_id" value="<?php echo esc_attr( (string) $image_id ); ?>">
		<div class="tls-media-preview"><?php if ( $image_src ) : ?><img src="<?php echo esc_url( $image_src ); ?>" alt="" style="max-width:150px;height:auto;display:block;border-radius:6px"><?php endif; ?></div>
		<p>
			<button type="button" class="button tls-media-select" data-title="<?php esc_attr_e( 'Select subject image', 'the-learning-studio' ); ?>"><?php esc_html_e( 'Select image', 'the-learning-studio' ); ?></button>
			<button type="button" class="button tls-media-remove"<?php echo $image_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove image', 'the-learning-studio' ); ?></button>
		</p>
	</div>
	<?php
}

/**
 * Enqueue the Media Library and its picker script only on Subject term screens.
 *
 * @param string $hook Current admin page hook suffix.
 */
function tls_enqueue_subject_media_admin( string $hook ): void {
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'subject' !== $screen->taxonomy ) {
		return;
	}
	wp_enqueue_media();
	$js_path = get_template_directory() . '/assets/admin-subject-media.js';
	wp_enqueue_script( 'tls-admin-subject-media', get_template_directory_uri() . '/assets/admin-subject-media.js', array( 'media-editor' ), (string) filemtime( $js_path ), true );
}
add_action( 'admin_enqueue_scripts', 'tls_enqueue_subject_media_admin' );

function tls_subject_add_fields(): void {
	wp_nonce_field( 'tls_save_subject', 'tls_subject_nonce' );
	?>
	<div class="form-field"><label for="tls_subject_group"><?php esc_html_e( 'Subject group', 'the-learning-studio' ); ?></label><input id="tls_subject_group" name="tls_subject_group" placeholder="<?php esc_attr_e( 'e.g. AI & Data', 'the-learning-studio' ); ?>"></div>
	<div class="form-field"><label><?php esc_html_e( 'Image', 'the-learning-studio' ); ?></label><?php tls_render_subject_image_field(); ?></div>
	<div class="form-field"><label for="tls_subject_image_url"><?php esc_html_e( 'Fallback image URL', 'the-learning-studio' ); ?></label><input type="url" id="tls_subject_image_url" name="tls_subject_image_url" placeholder="https://..."><p><?php esc_html_e( 'Only used when no Media Library image is selected above.', 'the-learning-studio' ); ?></p></div>
	<div class="form-field"><label><input type="checkbox" name="tls_subject_featured" value="1"> <?php esc_html_e( 'Featured subject', 'the-learning-studio' ); ?></label></div>
	<div class="form-field"><label for="tls_subject_priority"><?php esc_html_e( 'Featured order priority', 'the-learning-studio' ); ?></label><input type="number" id="tls_subject_priority" name="tls_subject_priority" min="-100" max="100" step="1" value="0"><p><?php esc_html_e( 'Higher numbers appear first among featured subjects.', 'the-learning-studio' ); ?></p></div>
	<?php
}
add_action( 'subject_add_form_fields', 'tls_subject_add_fields' );

function tls_subject_edit_fields( WP_Term $term ): void {
	$group     = get_term_meta( $term->term_id, '_tls_subject_group', true );
	$image_url = get_term_meta( $term->term_id, '_tls_image_url', true );
	$featured  = (bool) get_term_meta( $term->term_id, '_tls_featured', true );
	$priority  = (int) get_term_meta( $term->term_id, '_tls_priority', true );
	?>
	<input type="hidden" name="tls_subject_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tls_save_subject' ) ); ?>">
	<tr class="form-field"><th><label for="tls_subject_group"><?php esc_html_e( 'Subject group', 'the-learning-studio' ); ?></label></th><td><input id="tls_subject_group" name="tls_subject_group" value="<?php echo esc_attr( $group ); ?>"></td></tr>
	<tr class="form-field"><th><?php esc_html_e( 'Image', 'the-learning-studio' ); ?></th><td><?php tls_render_subject_image_field( $term->term_id ); ?></td></tr>
	<tr class="form-field"><th><label for="tls_subject_image_url"><?php esc_html_e( 'Fallback image URL', 'the-learning-studio' ); ?></label></th><td><input type="url" id="tls_subject_image_url" name="tls_subject_image_url" value="<?php echo esc_attr( $image_url ); ?>"><p class="description"><?php esc_html_e( 'Only used when no Media Library image is selected above.', 'the-learning-studio' ); ?></p></td></tr>
	<tr class="form-field"><th><?php esc_html_e( 'Featured', 'the-learning-studio' ); ?></th><td><label><input type="checkbox" name="tls_subject_featured" value="1" <?php checked( $featured ); ?>> <?php esc_html_e( 'Feature this subject', 'the-learning-studio' ); ?></label></td></tr>
	<tr class="form-field"><th><label for="tls_subject_priority"><?php esc_html_e( 'Featured order priority', 'the-learning-studio' ); ?></label></th><td><input type="number" id="tls_subject_priority" name="tls_subject_priority" min="-100" max="100" step="1" value="<?php echo esc_attr( (string) $priority ); ?>"><p class="description"><?php esc_html_e( 'Higher numbers appear first among featured subjects. Subjects with the same priority fall back to alphabetical order.', 'the-learning-studio' ); ?></p></td></tr>
	<?php
}
add_action( 'subject_edit_form_fields', 'tls_subject_edit_fields' );

function tls_save_subject_fields( int $term_id ): void {
	if ( ! current_user_can( 'manage_categories' ) || ! isset( $_POST['tls_subject_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tls_subject_nonce'] ) ), 'tls_save_subject' ) ) {
		return;
	}
	$priority = isset( $_POST['tls_subject_priority'] ) ? (int) wp_unslash( $_POST['tls_subject_priority'] ) : 0;
	update_term_meta( $term_id, '_tls_priority', max( -100, min( 100, $priority ) ) );
	update_term_meta( $term_id, '_tls_subject_group', isset( $_POST['tls_subject_group'] ) ? sanitize_text_field( wp_unslash( $_POST['tls_subject_group'] ) ) : '' );
	update_term_meta( $term_id, '_tls_image_url', isset( $_POST['tls_subject_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['tls_subject_image_url'] ) ) : '' );
	$image_id = isset( $_POST['tls_subject_image_id'] ) ? absint( wp_unslash( $_POST['tls_subject_image_id'] ) ) : 0;
	if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
		$image_id = 0;
	}
	update_term_meta( $term_id, '_tls_image_id', $image_id );
	update_term_meta( $term_id, '_tls_featured', isset( $_POST['tls_subject_featured'] ) );
}
add_action( 'created_subject', 'tls_save_subject_fields' );
add_action( 'edited_subject', 'tls_save_subject_fields' );
