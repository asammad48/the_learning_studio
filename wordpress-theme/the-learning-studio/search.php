<?php
/** Search results. @package TheLearningStudio */
get_header();
$tls_subject_matches = tls_search_subjects( get_search_query( false ) );
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Search', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php echo esc_html( sprintf( __( 'Results for “%s”', 'the-learning-studio' ), get_search_query() ) ); ?></h1><?php get_search_form(); ?></div></section>
<?php if ( $tls_subject_matches ) : ?>
<section class="section"><div class="wrap">
	<div class="section-head"><div><span class="mono"><?php esc_html_e( 'Subjects', 'the-learning-studio' ); ?></span><h2 class="h2 script"><?php esc_html_e( 'Matching subjects', 'the-learning-studio' ); ?></h2></div></div>
	<div class="grid"><?php foreach ( $tls_subject_matches as $subject ) : tls_subject_card( $subject ); endforeach; ?></div>
</div></section>
<?php endif; ?>
<section class="section<?php echo $tls_subject_matches ? ' alt' : ''; ?>"><div class="wrap">
	<?php if ( $tls_subject_matches ) : ?><div class="section-head"><div><span class="mono"><?php esc_html_e( 'More results', 'the-learning-studio' ); ?></span><h2 class="h2 script"><?php esc_html_e( 'Lessons, posts and pages', 'the-learning-studio' ); ?></h2></div></div><?php endif; ?>
	<div class="grid">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			if ( 'lesson' === get_post_type() ) {
				tls_lesson_card();
			} else {
				get_template_part( 'template-parts/content', 'card' );
			}
		endwhile;
	elseif ( $tls_subject_matches ) :
		?>
		<p class="muted"><?php esc_html_e( 'No lessons, posts, or pages matched your search.', 'the-learning-studio' ); ?></p>
		<?php
	else :
		get_template_part( 'template-parts/content', 'none' );
	endif;
	?>
	</div>
	<?php the_posts_pagination(); ?>
</div></section>
<?php get_footer(); ?>
