<?php
/** Search results. @package TheLearningStudio */
get_header();
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Search', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php echo esc_html( sprintf( __( 'Results for “%s”', 'the-learning-studio' ), get_search_query() ) ); ?></h1><?php get_search_form(); ?></div></section>
<section class="section"><div class="wrap"><div class="grid">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); if ( 'lesson' === get_post_type() ) { tls_lesson_card(); } else { get_template_part( 'template-parts/content', 'card' ); } endwhile; else : get_template_part( 'template-parts/content', 'none' ); endif; ?>
</div><?php the_posts_pagination(); ?></div></section>
<?php get_footer(); ?>
