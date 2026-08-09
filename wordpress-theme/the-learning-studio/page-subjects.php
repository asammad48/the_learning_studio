<?php
/** Subject directory page. @package TheLearningStudio */
get_header();
$subject_groups = tls_get_grouped_subjects();
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Browse the library', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php esc_html_e( 'All subjects', 'the-learning-studio' ); ?></h1><p class="lead"><?php esc_html_e( 'Explore every subject and find exactly what you want to learn.', 'the-learning-studio' ); ?></p></div></section>
<section class="section"><div class="wrap">
<?php if ( $subject_groups ) : ?>
	<?php foreach ( $subject_groups as $group_label => $parents ) : ?>
		<div class="subject-group">
			<h2 class="h2 script subject-group-title"><?php echo esc_html( $group_label ); ?></h2>
			<div class="grid">
				<?php foreach ( $parents as $parent ) : ?>
					<div class="subject-entry">
						<?php
						tls_subject_card( $parent );
						tls_render_subject_children( $parent->term_id );
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
<?php else : ?>
	<p class="muted"><?php esc_html_e( 'No subjects have been added yet.', 'the-learning-studio' ); ?></p>
<?php endif; ?>
</div></section>
<?php get_footer(); ?>
