<?php
/**
 * Quiz and Survey content types: multiple-choice/true-false questions,
 * user-based attempts and responses stored in dedicated DB tables.
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tls_register_quiz_survey_types(): void {
	register_post_type(
		'quiz',
		array(
			'labels' => array(
				'name'          => __( 'Quizzes', 'the-learning-studio' ),
				'singular_name' => __( 'Quiz', 'the-learning-studio' ),
				'add_new_item'  => __( 'Add New Quiz', 'the-learning-studio' ),
				'edit_item'     => __( 'Edit Quiz', 'the-learning-studio' ),
				'all_items'     => __( 'All Quizzes', 'the-learning-studio' ),
				'menu_name'     => __( 'Quizzes', 'the-learning-studio' ),
			),
			'public'       => true,
			'show_in_rest' => false,
			'has_archive'  => 'quizzes',
			'rewrite'      => array( 'slug' => 'quizzes' ),
			'menu_icon'    => 'dashicons-forms',
			'supports'     => array( 'title', 'editor', 'excerpt' ),
		)
	);

	register_post_type(
		'survey',
		array(
			'labels' => array(
				'name'          => __( 'Surveys', 'the-learning-studio' ),
				'singular_name' => __( 'Survey', 'the-learning-studio' ),
				'add_new_item'  => __( 'Add New Survey', 'the-learning-studio' ),
				'edit_item'     => __( 'Edit Survey', 'the-learning-studio' ),
				'all_items'     => __( 'All Surveys', 'the-learning-studio' ),
				'menu_name'     => __( 'Surveys', 'the-learning-studio' ),
			),
			'public'       => true,
			'show_in_rest' => false,
			'has_archive'  => 'surveys',
			'rewrite'      => array( 'slug' => 'surveys' ),
			'menu_icon'    => 'dashicons-clipboard',
			'supports'     => array( 'title', 'editor', 'excerpt' ),
		)
	);
}
add_action( 'init', 'tls_register_quiz_survey_types' );

/**
 * The two tables this feature needs: one row per quiz attempt (with a score),
 * one row per survey response (no score - just the raw answers). Both are
 * tied to a WordPress user, since taking a quiz or survey requires being
 * logged in.
 */
function tls_install_quiz_survey_tables(): void {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset_collate = $wpdb->get_charset_collate();

	$attempts = $wpdb->prefix . 'tls_quiz_attempts';
	dbDelta(
		"CREATE TABLE {$attempts} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			score INT UNSIGNED NOT NULL,
			total INT UNSIGNED NOT NULL,
			answers LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY quiz_user (quiz_id, user_id)
		) {$charset_collate};"
	);

	$responses = $wpdb->prefix . 'tls_survey_responses';
	dbDelta(
		"CREATE TABLE {$responses} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			survey_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			answers LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY survey_user (survey_id, user_id)
		) {$charset_collate};"
	);

	update_option( 'tls_quiz_survey_db_version', '1.0' );
}
add_action( 'after_switch_theme', 'tls_install_quiz_survey_tables' );

/**
 * Safety net: create the tables on the next admin load if they were never
 * created (e.g. the theme was already active before this feature shipped).
 */
function tls_maybe_install_quiz_survey_tables(): void {
	if ( '1.0' !== get_option( 'tls_quiz_survey_db_version' ) ) {
		tls_install_quiz_survey_tables();
	}
}
add_action( 'admin_init', 'tls_maybe_install_quiz_survey_tables' );

/**
 * Get a post's question list, normalized to a predictable shape.
 *
 * @param int $post_id Quiz or Survey post ID.
 * @return array<int,array<string,mixed>>
 */
function tls_get_questions( int $post_id ): array {
	$questions = get_post_meta( $post_id, '_tls_questions', true );
	return is_array( $questions ) ? $questions : array();
}

/**
 * Render one question row in the admin editor.
 *
 * @param string               $index        Array index/key used in field names.
 * @param array<string,mixed>  $question     Existing question data.
 * @param int                  $number       1-based row number for the legend.
 * @param bool                 $with_correct Whether to show "correct answer" controls (Quiz only, not Survey).
 * @param bool                 $is_template  Whether this is the hidden template row cloned by JS.
 */
function tls_render_question_row( string $index, array $question, int $number, bool $with_correct, bool $is_template = false ): void {
	$type    = $question['type'] ?? 'multiple_choice';
	$text    = (string) ( $question['question'] ?? '' );
	$options = is_array( $question['options'] ?? null ) ? $question['options'] : array();
	$correct = $question['correct'] ?? '';
	$name    = 'tls_questions[' . $index . ']';
	?>
	<fieldset class="tls-q-row" style="margin:0 0 16px;padding:12px;border:1px solid #dcdcde">
		<legend><strong><?php echo esc_html( $is_template ? __( 'New question', 'the-learning-studio' ) : sprintf( __( 'Question %d', 'the-learning-studio' ), $number ) ); ?></strong></legend>
		<p><label><?php esc_html_e( 'Question text', 'the-learning-studio' ); ?><br>
			<input class="widefat" name="<?php echo esc_attr( $name ); ?>[question]" value="<?php echo esc_attr( $text ); ?>">
		</label></p>
		<p><label><?php esc_html_e( 'Type', 'the-learning-studio' ); ?><br>
			<select class="tls-q-type" name="<?php echo esc_attr( $name ); ?>[type]">
				<option value="multiple_choice" <?php selected( $type, 'multiple_choice' ); ?>><?php esc_html_e( 'Multiple choice', 'the-learning-studio' ); ?></option>
				<option value="true_false" <?php selected( $type, 'true_false' ); ?>><?php esc_html_e( 'True / False', 'the-learning-studio' ); ?></option>
			</select>
		</label></p>
		<div class="tls-q-options-wrap"<?php echo 'true_false' === $type ? ' hidden' : ''; ?>>
			<p><label><?php esc_html_e( 'Options (one per line)', 'the-learning-studio' ); ?><br>
				<textarea class="widefat" rows="4" name="<?php echo esc_attr( $name ); ?>[options]"><?php echo esc_textarea( implode( "\n", $options ) ); ?></textarea>
			</label></p>
			<?php if ( $with_correct ) : ?>
			<p><label><?php esc_html_e( 'Correct option number (1-based)', 'the-learning-studio' ); ?><br>
				<input type="number" min="1" class="small-text" name="<?php echo esc_attr( $name ); ?>[correct]" value="<?php echo esc_attr( 'multiple_choice' === $type ? (string) $correct : '' ); ?>">
			</label></p>
			<?php endif; ?>
		</div>
		<?php if ( $with_correct ) : ?>
		<div class="tls-q-tf-wrap"<?php echo 'true_false' !== $type ? ' hidden' : ''; ?>>
			<p><label><?php esc_html_e( 'Correct answer', 'the-learning-studio' ); ?><br>
				<select name="<?php echo esc_attr( $name ); ?>[correct_tf]">
					<option value="true" <?php selected( 'true_false' === $type ? (string) $correct : '', 'true' ); ?>><?php esc_html_e( 'True', 'the-learning-studio' ); ?></option>
					<option value="false" <?php selected( 'true_false' === $type ? (string) $correct : '', 'false' ); ?>><?php esc_html_e( 'False', 'the-learning-studio' ); ?></option>
				</select>
			</label></p>
		</div>
		<?php endif; ?>
		<p><button type="button" class="button-link-delete tls-q-remove"><?php esc_html_e( 'Remove question', 'the-learning-studio' ); ?></button></p>
	</fieldset>
	<?php
}

/**
 * Render the full question-repeater meta box for a Quiz or Survey.
 *
 * @param WP_Post $post         Current post.
 * @param bool    $with_correct Whether to show "correct answer" controls.
 */
function tls_render_questions_box( WP_Post $post, bool $with_correct ): void {
	wp_nonce_field( 'tls_save_questions', 'tls_questions_nonce' );
	$questions = tls_get_questions( $post->ID );
	if ( ! $questions ) {
		$questions = array( array( 'type' => 'multiple_choice', 'question' => '', 'options' => array( '', '' ) ) );
	}
	?>
	<div class="tls-q-rows" data-with-correct="<?php echo $with_correct ? '1' : '0'; ?>">
		<?php foreach ( array_values( $questions ) as $index => $question ) : ?>
			<?php tls_render_question_row( (string) $index, $question, $index + 1, $with_correct ); ?>
		<?php endforeach; ?>
	</div>
	<p><button type="button" class="button tls-q-add"><?php esc_html_e( 'Add question', 'the-learning-studio' ); ?></button></p>
	<template id="tls-q-row-template"><?php tls_render_question_row( '__new__', array(), 0, $with_correct, true ); ?></template>
	<p class="description">
		<?php if ( $with_correct ) : ?>
			<?php esc_html_e( 'A row is saved only when it has question text. Visitors must be logged in to take the quiz; their score is calculated automatically from the correct answers set here.', 'the-learning-studio' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'A row is saved only when it has question text. Surveys collect opinions, not correct answers - responses are stored per logged-in user.', 'the-learning-studio' ); ?>
		<?php endif; ?>
	</p>
	<?php
}

function tls_add_quiz_survey_meta_boxes(): void {
	add_meta_box( 'tls-quiz-questions', __( 'Questions', 'the-learning-studio' ), 'tls_render_quiz_questions_box', 'quiz', 'normal', 'default' );
	add_meta_box( 'tls-survey-questions', __( 'Questions', 'the-learning-studio' ), 'tls_render_survey_questions_box', 'survey', 'normal', 'default' );
	add_meta_box( 'tls-quiz-attempts', __( 'Attempts', 'the-learning-studio' ), 'tls_render_quiz_attempts_box', 'quiz', 'side', 'default' );
	add_meta_box( 'tls-survey-results', __( 'Response summary', 'the-learning-studio' ), 'tls_render_survey_results_box', 'survey', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'tls_add_quiz_survey_meta_boxes' );

function tls_render_quiz_questions_box( WP_Post $post ): void {
	tls_render_questions_box( $post, true );
}

function tls_render_survey_questions_box( WP_Post $post ): void {
	tls_render_questions_box( $post, false );
}

/**
 * Sidebar summary of who has attempted this Quiz, most recent first.
 *
 * @param WP_Post $post Current Quiz post.
 */
function tls_render_quiz_attempts_box( WP_Post $post ): void {
	global $wpdb;
	$table = $wpdb->prefix . 'tls_quiz_attempts';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a fixed prefix + literal, not user input.
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, score, total, created_at FROM {$table} WHERE quiz_id = %d ORDER BY created_at DESC LIMIT 20", $post->ID ) );
	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'No attempts yet.', 'the-learning-studio' ) . '</p>';
		return;
	}
	echo '<ul style="margin:0;padding:0;list-style:none">';
	foreach ( $rows as $row ) {
		$user = get_userdata( (int) $row->user_id );
		printf(
			'<li style="margin-bottom:8px"><strong>%s</strong> - %d/%d<br><span style="color:#787c82">%s</span></li>',
			esc_html( $user ? $user->display_name : __( 'Unknown user', 'the-learning-studio' ) ),
			(int) $row->score,
			(int) $row->total,
			esc_html( mysql2date( 'M j, Y g:i a', $row->created_at ) )
		);
	}
	echo '</ul>';
}

/**
 * Sidebar per-question tally of Survey responses.
 *
 * @param WP_Post $post Current Survey post.
 */
function tls_render_survey_results_box( WP_Post $post ): void {
	$totals = tls_get_survey_aggregate( $post->ID );
	if ( ! $totals['count'] ) {
		echo '<p>' . esc_html__( 'No responses yet.', 'the-learning-studio' ) . '</p>';
		return;
	}
	printf( '<p>%s</p>', esc_html( sprintf( _n( '%d response', '%d responses', $totals['count'], 'the-learning-studio' ), $totals['count'] ) ) );
	foreach ( $totals['questions'] as $q_index => $q_summary ) {
		echo '<p style="margin-bottom:4px"><strong>' . esc_html( sprintf( __( 'Q%d', 'the-learning-studio' ), $q_index + 1 ) ) . '</strong></p>';
		echo '<ul style="margin:0 0 12px;padding-left:16px">';
		foreach ( $q_summary as $option_label => $option_count ) {
			echo '<li>' . esc_html( $option_label ) . ': ' . esc_html( (string) $option_count ) . '</li>';
		}
		echo '</ul>';
	}
}

/**
 * Enqueue the question-repeater admin script only on Quiz/Survey edit screens.
 *
 * @param string $hook Current admin page hook suffix.
 */
function tls_enqueue_quiz_survey_admin( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'quiz', 'survey' ), true ) ) {
		return;
	}
	$js_path = get_template_directory() . '/assets/admin-question-repeater.js';
	wp_enqueue_script( 'tls-admin-question-repeater', get_template_directory_uri() . '/assets/admin-question-repeater.js', array(), (string) filemtime( $js_path ), true );
}
add_action( 'admin_enqueue_scripts', 'tls_enqueue_quiz_survey_admin' );

/**
 * Parse and sanitize the posted question rows for either post type.
 *
 * @param bool $with_correct Whether to read/keep "correct answer" fields.
 * @return array<int,array<string,mixed>>
 */
function tls_sanitize_posted_questions( bool $with_correct ): array {
	$questions = array();
	if ( ! isset( $_POST['tls_questions'] ) || ! is_array( $_POST['tls_questions'] ) ) {
		return $questions;
	}
	foreach ( wp_unslash( $_POST['tls_questions'] ) as $item ) {
		$question_text = sanitize_text_field( $item['question'] ?? '' );
		if ( '' === $question_text ) {
			continue;
		}
		$type = ( 'true_false' === ( $item['type'] ?? '' ) ) ? 'true_false' : 'multiple_choice';
		$row  = array(
			'type'     => $type,
			'question' => $question_text,
		);
		if ( 'multiple_choice' === $type ) {
			$options = array_values(
				array_filter(
					array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', (string) ( $item['options'] ?? '' ) ) ),
					static fn( string $option ): bool => '' !== $option
				)
			);
			$row['options'] = $options;
			if ( $with_correct ) {
				$correct       = max( 1, (int) ( $item['correct'] ?? 1 ) );
				$row['correct'] = $options ? min( $correct, count( $options ) ) : 1;
			}
		} elseif ( $with_correct ) {
			$row['correct'] = ( 'false' === ( $item['correct_tf'] ?? 'true' ) ) ? 'false' : 'true';
		}
		$questions[] = $row;
	}
	return $questions;
}

/**
 * Sanitize a question list coming from JSON import data (data/quizzes.json
 * or data/surveys.json), as opposed to a submitted admin form. Options are
 * already an array here, and "correct" is given directly rather than split
 * across correct/correct_tf fields.
 *
 * @param array<int,array<string,mixed>> $questions    Raw question definitions from JSON.
 * @param bool                           $with_correct Whether to read/keep "correct answer" fields.
 * @return array<int,array<string,mixed>>
 */
function tls_sanitize_imported_questions( array $questions, bool $with_correct ): array {
	$sanitized = array();
	foreach ( $questions as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$question_text = sanitize_text_field( $item['question'] ?? '' );
		if ( '' === $question_text ) {
			continue;
		}
		$type = ( 'true_false' === ( $item['type'] ?? '' ) ) ? 'true_false' : 'multiple_choice';
		$row  = array(
			'type'     => $type,
			'question' => $question_text,
		);
		if ( 'multiple_choice' === $type ) {
			$options = array_values(
				array_filter(
					array_map( 'sanitize_text_field', is_array( $item['options'] ?? null ) ? $item['options'] : array() ),
					static fn( string $option ): bool => '' !== $option
				)
			);
			$row['options'] = $options;
			if ( $with_correct ) {
				$correct        = max( 1, (int) ( $item['correct'] ?? 1 ) );
				$row['correct'] = $options ? min( $correct, count( $options ) ) : 1;
			}
		} elseif ( $with_correct ) {
			$row['correct'] = ( 'false' === (string) ( $item['correct'] ?? 'true' ) ) ? 'false' : 'true';
		}
		$sanitized[] = $row;
	}
	return $sanitized;
}

function tls_save_quiz_meta( int $post_id ): void {
	if ( ! isset( $_POST['tls_questions_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tls_questions_nonce'] ) ), 'tls_save_questions' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_tls_questions', tls_sanitize_posted_questions( true ) );
}
add_action( 'save_post_quiz', 'tls_save_quiz_meta' );

function tls_save_survey_meta( int $post_id ): void {
	if ( ! isset( $_POST['tls_questions_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tls_questions_nonce'] ) ), 'tls_save_questions' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_tls_questions', tls_sanitize_posted_questions( false ) );
}
add_action( 'save_post_survey', 'tls_save_survey_meta' );

/**
 * Score a submitted Quiz attempt against its stored correct answers.
 *
 * @param array<int,array<string,mixed>> $questions Quiz question definitions.
 * @param array<int,string>              $answers   Submitted answers, keyed by question index.
 * @return array{score:int,total:int}
 */
function tls_score_quiz_answers( array $questions, array $answers ): array {
	$score = 0;
	foreach ( $questions as $index => $question ) {
		$given = (string) ( $answers[ $index ] ?? '' );
		if ( 'true_false' === ( $question['type'] ?? '' ) ) {
			if ( $given === (string) ( $question['correct'] ?? '' ) ) {
				++$score;
			}
		} elseif ( $given !== '' && (int) $given === (int) ( $question['correct'] ?? 0 ) ) {
			++$score;
		}
	}
	return array( 'score' => $score, 'total' => count( $questions ) );
}

/**
 * Handle a Quiz attempt or Survey response submitted from the front end.
 * Uses the Post/Redirect/Get pattern so refreshing the result page never
 * resubmits the form.
 */
function tls_handle_quiz_survey_submissions(): void {
	if ( isset( $_POST['tls_quiz_submit'] ) && is_singular( 'quiz' ) ) {
		tls_process_quiz_submission( get_queried_object_id() );
	}
	if ( isset( $_POST['tls_survey_submit'] ) && is_singular( 'survey' ) ) {
		tls_process_survey_submission( get_queried_object_id() );
	}
}
add_action( 'template_redirect', 'tls_handle_quiz_survey_submissions' );

function tls_process_quiz_submission( int $quiz_id ): void {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( get_permalink( $quiz_id ) ) );
		exit;
	}
	if ( ! isset( $_POST['tls_quiz_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tls_quiz_nonce'] ) ), 'tls_take_quiz_' . $quiz_id ) ) {
		wp_safe_redirect( get_permalink( $quiz_id ) );
		exit;
	}

	$questions = tls_get_questions( $quiz_id );
	$submitted = isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ? wp_unslash( $_POST['answers'] ) : array();
	$answers   = array();
	foreach ( $submitted as $index => $value ) {
		$answers[ (int) $index ] = sanitize_text_field( (string) $value );
	}

	$result = tls_score_quiz_answers( $questions, $answers );

	global $wpdb;
	$table = $wpdb->prefix . 'tls_quiz_attempts';
	$wpdb->insert(
		$table,
		array(
			'quiz_id'    => $quiz_id,
			'user_id'    => get_current_user_id(),
			'score'      => $result['score'],
			'total'      => $result['total'],
			'answers'    => wp_json_encode( $answers ),
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%d', '%s', '%s' )
	);

	wp_safe_redirect( add_query_arg( 'tls_result', $wpdb->insert_id, get_permalink( $quiz_id ) ) );
	exit;
}

function tls_process_survey_submission( int $survey_id ): void {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( get_permalink( $survey_id ) ) );
		exit;
	}
	if ( ! isset( $_POST['tls_survey_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tls_survey_nonce'] ) ), 'tls_take_survey_' . $survey_id ) ) {
		wp_safe_redirect( get_permalink( $survey_id ) );
		exit;
	}

	$submitted = isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ? wp_unslash( $_POST['answers'] ) : array();
	$answers   = array();
	foreach ( $submitted as $index => $value ) {
		$answers[ (int) $index ] = sanitize_text_field( (string) $value );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'tls_survey_responses';
	$wpdb->insert(
		$table,
		array(
			'survey_id'  => $survey_id,
			'user_id'    => get_current_user_id(),
			'answers'    => wp_json_encode( $answers ),
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s' )
	);

	wp_safe_redirect( add_query_arg( 'tls_submitted', '1', get_permalink( $survey_id ) ) );
	exit;
}

/**
 * Fetch one Quiz attempt, only if it belongs to the current user and quiz.
 *
 * @param int $attempt_id Attempt row ID from the ?tls_result= query var.
 * @param int $quiz_id    Quiz post ID.
 * @return object|null
 */
function tls_get_quiz_attempt( int $attempt_id, int $quiz_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'tls_quiz_attempts';
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d AND quiz_id = %d AND user_id = %d",
			$attempt_id,
			$quiz_id,
			get_current_user_id()
		)
	);
}

/**
 * All of the current user's past attempts at a Quiz, most recent first.
 *
 * @param int $quiz_id Quiz post ID.
 * @return array<int,object>
 */
function tls_get_user_quiz_attempts( int $quiz_id ): array {
	if ( ! is_user_logged_in() ) {
		return array();
	}
	global $wpdb;
	$table = $wpdb->prefix . 'tls_quiz_attempts';
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE quiz_id = %d AND user_id = %d ORDER BY created_at DESC",
			$quiz_id,
			get_current_user_id()
		)
	);
}

/**
 * Whether the current user has already responded to a Survey.
 *
 * @param int $survey_id Survey post ID.
 */
function tls_user_has_responded( int $survey_id ): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	global $wpdb;
	$table = $wpdb->prefix . 'tls_survey_responses';
	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE survey_id = %d AND user_id = %d",
			$survey_id,
			get_current_user_id()
		)
	);
	return (int) $count > 0;
}

/**
 * Aggregate response counts per option, for every question in a Survey.
 *
 * @param int $survey_id Survey post ID.
 * @return array{count:int,questions:array<int,array<string,int>>}
 */
function tls_get_survey_aggregate( int $survey_id ): array {
	global $wpdb;
	$table     = $wpdb->prefix . 'tls_survey_responses';
	$questions = tls_get_questions( $survey_id );
	$rows      = $wpdb->get_results( $wpdb->prepare( "SELECT answers FROM {$table} WHERE survey_id = %d", $survey_id ) );

	$totals = array();
	foreach ( array_keys( $questions ) as $q_index ) {
		$totals[ $q_index ] = array();
	}

	foreach ( $rows as $row ) {
		$answers = json_decode( (string) $row->answers, true );
		if ( ! is_array( $answers ) ) {
			continue;
		}
		foreach ( $answers as $q_index => $value ) {
			$q_index  = (int) $q_index;
			$question = $questions[ $q_index ] ?? null;
			if ( ! $question ) {
				continue;
			}
			$label = tls_answer_label( $question, (string) $value );
			if ( '' === $label ) {
				continue;
			}
			$totals[ $q_index ][ $label ] = ( $totals[ $q_index ][ $label ] ?? 0 ) + 1;
		}
	}

	return array( 'count' => count( $rows ), 'questions' => $totals );
}

/**
 * Turn a raw stored answer value into its human-readable option label.
 *
 * @param array<string,mixed> $question Question definition.
 * @param string              $value    Raw stored answer ("true"/"false" or a 1-based option number).
 */
function tls_answer_label( array $question, string $value ): string {
	if ( 'true_false' === ( $question['type'] ?? '' ) ) {
		if ( 'true' === $value ) {
			return __( 'True', 'the-learning-studio' );
		}
		if ( 'false' === $value ) {
			return __( 'False', 'the-learning-studio' );
		}
		return '';
	}
	$options = is_array( $question['options'] ?? null ) ? $question['options'] : array();
	$index   = (int) $value - 1;
	return $options[ $index ] ?? '';
}

/**
 * Card for a Quiz on the /quizzes/ archive.
 */
function tls_quiz_card(): void {
	$quiz_id      = get_the_ID();
	$question_count = count( tls_get_questions( $quiz_id ) );
	?>
	<a <?php post_class( 'card' ); ?> href="<?php the_permalink(); ?>">
		<span class="mono pill"><?php echo esc_html( sprintf( _n( '%d question', '%d questions', $question_count, 'the-learning-studio' ), $question_count ) ); ?></span>
		<b class="script"><?php the_title(); ?></b>
		<?php $excerpt = get_the_excerpt(); ?>
		<?php if ( $excerpt ) : ?><span class="muted"><?php echo esc_html( $excerpt ); ?></span><?php endif; ?>
	</a>
	<?php
}

/**
 * Card for a Survey on the /surveys/ archive.
 */
function tls_survey_card(): void {
	$survey_id      = get_the_ID();
	$question_count = count( tls_get_questions( $survey_id ) );
	?>
	<a <?php post_class( 'card' ); ?> href="<?php the_permalink(); ?>">
		<span class="mono pill"><?php echo esc_html( sprintf( _n( '%d question', '%d questions', $question_count, 'the-learning-studio' ), $question_count ) ); ?></span>
		<b class="script"><?php the_title(); ?></b>
		<?php $excerpt = get_the_excerpt(); ?>
		<?php if ( $excerpt ) : ?><span class="muted"><?php echo esc_html( $excerpt ); ?></span><?php endif; ?>
	</a>
	<?php
}
