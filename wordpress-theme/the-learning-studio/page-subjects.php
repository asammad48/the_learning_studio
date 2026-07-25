<?php
/** Subject directory page. @package TheLearningStudio */
get_header();
$subjects = get_terms( array( 'taxonomy' => 'subject', 'hide_empty' => false ) );
?>
<section class="page-hero"><div class="wrap"><span class="mono"><?php esc_html_e( 'Browse the library', 'the-learning-studio' ); ?></span><h1 class="h2 script"><?php esc_html_e( 'All subjects', 'the-learning-studio' ); ?></h1><p class="lead"><?php esc_html_e( 'Explore every subject and find exactly what you want to learn.', 'the-learning-studio' ); ?></p></div></section>
<section class="section"><div class="wrap"><div class="grid"><?php if ( ! is_wp_error( $subjects ) && $subjects ) : foreach ( $subjects as $subject ) : tls_subject_card( $subject ); endforeach; else : ?><p class="muted"><?php esc_html_e( 'No subjects have been added yet.', 'the-learning-studio' ); ?></p><?php endif; ?></div></div></section>
<?php get_footer(); ?>
