<?php
/** Comments list and form. @package TheLearningStudio */
if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area article">
	<?php if ( have_comments() ) : ?>
		<h2 class="script"><?php echo esc_html( sprintf( _n( '%s comment', '%s comments', get_comments_number(), 'the-learning-studio' ), number_format_i18n( get_comments_number() ) ) ); ?></h2>
		<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 48 ) ); ?></ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php if ( ! comments_open() && get_comments_number() ) : ?><p><?php esc_html_e( 'Comments are closed.', 'the-learning-studio' ); ?></p><?php endif; ?>
	<?php comment_form(); ?>
</section>
