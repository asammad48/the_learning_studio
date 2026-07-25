<?php
/** Standard blog post. @package TheLearningStudio */
get_header();
while ( have_posts() ) : the_post(); ?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php echo esc_html( get_the_date() ); ?></span><h1 class="h2 script"><?php the_title(); ?></h1><?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?></div></section>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'article' ); ?>><?php the_content(); wp_link_pages(); ?></article>
<?php the_post_navigation(); if ( comments_open() || get_comments_number() ) : comments_template(); endif; endwhile; get_footer(); ?>
