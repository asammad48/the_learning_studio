<?php
/** Lesson archive. @package TheLearningStudio */
get_header();
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Lesson library', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php post_type_archive_title(); ?></h1><p class="lead"><?php esc_html_e( 'Written lessons, videos, notes and quizzes—all in one place.', 'the-learning-studio' ); ?></p></div></section>
<section class="section"><div class="wrap"><div class="grid">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); tls_lesson_card(); endwhile; else : ?><p><?php esc_html_e( 'No lessons have been published yet.', 'the-learning-studio' ); ?></p><?php endif; ?>
</div><?php the_posts_pagination(); ?></div></section>
<?php get_footer(); ?>
