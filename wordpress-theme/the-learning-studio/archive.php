<?php
/** General archive. @package TheLearningStudio */
get_header();
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Archive', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php the_archive_title(); ?></h1><?php the_archive_description( '<div class="lead">', '</div>' ); ?></div></section>
<section class="section"><div class="wrap"><div class="grid">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', 'card' ); endwhile; else : get_template_part( 'template-parts/content', 'none' ); endif; ?>
</div><?php the_posts_pagination(); ?></div></section>
<?php get_footer(); ?>
