<?php
/** Quiz archive. @package TheLearningStudio */
get_header();
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Test what you know', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php post_type_archive_title(); ?></h1><p class="lead"><?php esc_html_e( 'Multiple-choice and true/false quizzes. Log in to take one and track your score.', 'the-learning-studio' ); ?></p></div></section>
<section class="section"><div class="wrap"><div class="grid">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); tls_quiz_card(); endwhile; else : ?><p><?php esc_html_e( 'No quizzes have been published yet.', 'the-learning-studio' ); ?></p><?php endif; ?>
</div><?php the_posts_pagination(); ?></div></section>
<?php get_footer(); ?>
