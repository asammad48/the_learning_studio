<?php
/** Posts page. @package TheLearningStudio */
get_header();
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'From the studio', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php single_post_title(); ?></h1></div></section>
<section class="section"><div class="wrap"><div class="grid">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', 'card' ); endwhile; else : get_template_part( 'template-parts/content', 'none' ); endif; ?>
</div><?php the_posts_pagination(); ?></div></section>
<?php get_footer(); ?>
