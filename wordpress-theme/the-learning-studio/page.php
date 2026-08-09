<?php
/** Standard page. @package TheLearningStudio */
get_header();
while ( have_posts() ) : the_post(); ?>
<section class="page-hero"><div class="wrap"><h1 class="h2 script"><?php the_title(); ?></h1><?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?><?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'large', array( 'class' => 'media-thumb lesson-hero-image' ) ); endif; ?></div></section>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'article' ); ?>><?php the_content(); wp_link_pages(); ?></article>
<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; endwhile; get_footer(); ?>
