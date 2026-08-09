<?php
/** Generic post card. @package TheLearningStudio */
?>
<a <?php post_class( 'card' ); ?> href="<?php the_permalink(); ?>">
	<?php if ( has_post_thumbnail() ) : ?>
		<?php the_post_thumbnail( 'medium_large', array( 'class' => 'media-thumb' ) ); ?>
	<?php endif; ?>
	<span class="mono pill"><?php echo esc_html( get_the_date() ); ?></span>
	<b class="script"><?php the_title(); ?></b>
	<?php $excerpt = get_the_excerpt(); ?>
	<?php if ( $excerpt ) : ?><span class="muted"><?php echo esc_html( $excerpt ); ?></span><?php endif; ?>
</a>
