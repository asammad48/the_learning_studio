<?php
/** Take a Survey and see aggregate results. @package TheLearningStudio */
get_header();
while ( have_posts() ) :
	the_post();
	$survey_id = get_the_ID();
	$questions = tls_get_questions( $survey_id );
	$submitted = isset( $_GET['tls_submitted'] ) || tls_user_has_responded( $survey_id );
	?>
	<section class="page-hero"><div class="wrap">
		<span class="mono eyebrow"><?php esc_html_e( 'Survey', 'the-learning-studio' ); ?></span>
		<h1 class="h2 script"><?php the_title(); ?></h1>
		<?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
	</div></section>
	<section class="section"><div class="wrap">
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="note-box">
				<p><?php esc_html_e( 'You need to be logged in to take this survey.', 'the-learning-studio' ); ?></p>
				<p>
					<a class="btn" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'the-learning-studio' ); ?></a>
					<?php if ( get_option( 'users_can_register' ) ) : ?>
						<a class="btn" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Register', 'the-learning-studio' ); ?></a>
					<?php endif; ?>
				</p>
			</div>
		<?php elseif ( $submitted ) : ?>
			<div class="note-box">
				<p><?php esc_html_e( 'Thanks - your response has been recorded.', 'the-learning-studio' ); ?></p>
			</div>
			<?php $aggregate = tls_get_survey_aggregate( $survey_id ); ?>
			<?php if ( $aggregate['count'] ) : ?>
				<div class="survey-results">
					<h2 class="script"><?php echo esc_html( sprintf( _n( 'Results so far (%d response)', 'Results so far (%d responses)', $aggregate['count'], 'the-learning-studio' ), $aggregate['count'] ) ); ?></h2>
					<?php foreach ( $questions as $q_index => $question ) : ?>
						<div class="survey-result-question">
							<p><strong><?php echo esc_html( ( $q_index + 1 ) . '. ' . ( $question['question'] ?? '' ) ); ?></strong></p>
							<?php
							$q_totals = $aggregate['questions'][ $q_index ] ?? array();
							$max      = $q_totals ? max( $q_totals ) : 0;
							?>
							<?php foreach ( $q_totals as $label => $count ) : ?>
								<div class="survey-bar-row">
									<span class="survey-bar-label"><?php echo esc_html( $label ); ?> (<?php echo esc_html( (string) $count ); ?>)</span>
									<span class="survey-bar-track"><span class="survey-bar" style="width:<?php echo esc_attr( (string) ( $max ? round( $count / $max * 100 ) : 0 ) ); ?>%"></span></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php elseif ( $questions ) : ?>
			<form method="post" class="quiz-form">
				<?php wp_nonce_field( 'tls_take_survey_' . $survey_id, 'tls_survey_nonce' ); ?>
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
				<button type="submit" name="tls_survey_submit" value="1" class="btn"><?php esc_html_e( 'Submit Survey', 'the-learning-studio' ); ?></button>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'This survey has no questions yet.', 'the-learning-studio' ); ?></p>
		<?php endif; ?>
	</div></section>
	<?php
endwhile;
get_footer();
