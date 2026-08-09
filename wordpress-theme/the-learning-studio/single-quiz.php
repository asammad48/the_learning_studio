<?php
/** Take a Quiz, see results, and review past attempts. @package TheLearningStudio */
get_header();
while ( have_posts() ) :
	the_post();
	$quiz_id   = get_the_ID();
	$questions = tls_get_questions( $quiz_id );
	$result_id = isset( $_GET['tls_result'] ) ? absint( $_GET['tls_result'] ) : 0;
	$attempt   = $result_id ? tls_get_quiz_attempt( $result_id, $quiz_id ) : null;
	$attempts  = tls_get_user_quiz_attempts( $quiz_id );
	?>
	<section class="page-hero"><div class="wrap">
		<span class="mono eyebrow"><?php esc_html_e( 'Quiz', 'the-learning-studio' ); ?></span>
		<h1 class="h2 script"><?php the_title(); ?></h1>
		<?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
	</div></section>
	<section class="section"><div class="wrap">
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="note-box">
				<p><?php esc_html_e( 'You need to be logged in to take this quiz and track your score.', 'the-learning-studio' ); ?></p>
				<p>
					<a class="btn" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'the-learning-studio' ); ?></a>
					<?php if ( get_option( 'users_can_register' ) ) : ?>
						<a class="btn" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Register', 'the-learning-studio' ); ?></a>
					<?php endif; ?>
				</p>
			</div>
		<?php elseif ( $attempt ) : ?>
			<div class="quiz-result">
				<p class="pill mono"><?php echo esc_html( sprintf( __( 'Score: %1$d / %2$d', 'the-learning-studio' ), (int) $attempt->score, (int) $attempt->total ) ); ?></p>
				<?php
				$given_answers = json_decode( (string) $attempt->answers, true );
				$given_answers = is_array( $given_answers ) ? $given_answers : array();
				foreach ( $questions as $index => $question ) :
					$given         = (string) ( $given_answers[ $index ] ?? '' );
					$is_true_false = 'true_false' === ( $question['type'] ?? '' );
					$is_correct    = $is_true_false
						? $given === (string) ( $question['correct'] ?? '' )
						: ( '' !== $given && (int) $given === (int) ( $question['correct'] ?? 0 ) );
					?>
					<div class="quiz-question <?php echo $is_correct ? 'is-correct' : 'is-incorrect'; ?>">
						<p><strong><?php echo esc_html( ( $index + 1 ) . '. ' . ( $question['question'] ?? '' ) ); ?></strong></p>
						<p class="muted">
							<?php
							if ( $is_correct ) {
								esc_html_e( 'Correct', 'the-learning-studio' );
							} else {
								echo esc_html(
									sprintf(
										/* translators: %s: the correct answer */
										__( 'Incorrect - correct answer: %s', 'the-learning-studio' ),
										tls_answer_label( $question, (string) ( $question['correct'] ?? '' ) )
									)
								);
							}
							?>
						</p>
					</div>
				<?php endforeach; ?>
				<p><a class="btn" href="<?php echo esc_url( get_permalink() ); ?>"><?php esc_html_e( 'Take again', 'the-learning-studio' ); ?></a></p>
			</div>
		<?php elseif ( $questions ) : ?>
			<form method="post" class="quiz-form">
				<?php wp_nonce_field( 'tls_take_quiz_' . $quiz_id, 'tls_quiz_nonce' ); ?>
				<?php foreach ( $questions as $index => $question ) : ?>
					<fieldset class="quiz-question">
						<legend><?php echo esc_html( ( $index + 1 ) . '. ' . ( $question['question'] ?? '' ) ); ?></legend>
						<?php if ( 'true_false' === ( $question['type'] ?? '' ) ) : ?>
							<label class="quiz-option"><input type="radio" name="answers[<?php echo esc_attr( (string) $index ); ?>]" value="true" required> <?php esc_html_e( 'True', 'the-learning-studio' ); ?></label>
							<label class="quiz-option"><input type="radio" name="answers[<?php echo esc_attr( (string) $index ); ?>]" value="false" required> <?php esc_html_e( 'False', 'the-learning-studio' ); ?></label>
						<?php else : ?>
							<?php foreach ( ( $question['options'] ?? array() ) as $opt_index => $option ) : ?>
								<label class="quiz-option"><input type="radio" name="answers[<?php echo esc_attr( (string) $index ); ?>]" value="<?php echo esc_attr( (string) ( $opt_index + 1 ) ); ?>" required> <?php echo esc_html( $option ); ?></label>
							<?php endforeach; ?>
						<?php endif; ?>
					</fieldset>
				<?php endforeach; ?>
				<button type="submit" name="tls_quiz_submit" value="1" class="btn"><?php esc_html_e( 'Submit Quiz', 'the-learning-studio' ); ?></button>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'This quiz has no questions yet.', 'the-learning-studio' ); ?></p>
		<?php endif; ?>

		<?php if ( is_user_logged_in() && $attempts && ! $attempt ) : ?>
			<div class="quiz-history">
				<h2 class="script"><?php esc_html_e( 'Your previous attempts', 'the-learning-studio' ); ?></h2>
				<ul>
					<?php foreach ( $attempts as $past ) : ?>
						<li><?php echo esc_html( sprintf( '%1$d / %2$d - %3$s', (int) $past->score, (int) $past->total, mysql2date( 'M j, Y g:i a', $past->created_at ) ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div></section>
	<?php
endwhile;
get_footer();
