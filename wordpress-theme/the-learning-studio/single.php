<?php
/** Standard blog post. @package TheLearningStudio */
get_header();
while ( have_posts() ) :
	the_post();
	$categories = get_the_category_list( ', ' );
	$tags       = get_the_tag_list( '', ', ' );
	?>
<section class="page-hero"><div class="wrap">
	<span class="mono"><?php echo esc_html( get_the_date() ); ?> <?php esc_html_e( 'by', 'the-learning-studio' ); ?> <?php echo esc_html( get_the_author() ); ?></span>
	<h1 class="h2 script"><?php the_title(); ?></h1>
	<?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
	<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'large', array( 'class' => 'media-thumb lesson-hero-image' ) ); endif; ?>
</div></section>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'article' ); ?>>
	<?php the_content(); wp_link_pages(); ?>
	<?php if ( $categories || $tags ) : ?>
	<footer class="post-meta">
		<?php if ( $categories ) : ?><p class="muted"><?php esc_html_e( 'Filed under:', 'the-learning-studio' ); ?> <?php echo wp_kses_post( $categories ); ?></p><?php endif; ?>
		<?php if ( $tags ) : ?><p class="muted"><?php esc_html_e( 'Tagged:', 'the-learning-studio' ); ?> <?php echo wp_kses_post( $tags ); ?></p><?php endif; ?>
	</footer>
	<?php endif; ?>
</article>
<?php the_post_navigation(); if ( comments_open() || get_comments_number() ) : comments_template(); endif; endwhile; get_footer(); ?>
