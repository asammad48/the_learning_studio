<?php
/** Subject landing page. @package TheLearningStudio */
get_header();
$subject = get_queried_object();
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Subject', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php single_term_title(); ?></h1><?php if ( term_description() ) : ?><div class="lead"><?php echo wp_kses_post( term_description() ); ?></div><?php endif; ?></div></section>
<section class="section"><div class="wrap"><div class="section-head"><div><span class="mono"><?php esc_html_e( 'Lessons', 'the-learning-studio' ); ?></span><h2 class="h2 script"><?php echo esc_html( sprintf( __( 'Learn %s', 'the-learning-studio' ), $subject->name ) ); ?></h2></div></div><div class="grid">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); tls_lesson_card(); endwhile; else : ?><p><?php esc_html_e( 'No lessons have been assigned to this subject yet.', 'the-learning-studio' ); ?></p><?php endif; ?>
</div><?php the_posts_pagination(); ?></div></section>
<?php get_footer(); ?>
