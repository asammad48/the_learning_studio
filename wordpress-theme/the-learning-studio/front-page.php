<?php
/**
 * Home page.
 *
 * @package TheLearningStudio
 */

get_header();

$show_subjects   = (bool) get_theme_mod( 'tls_show_subjects', true );
$show_lessons    = (bool) get_theme_mod( 'tls_show_lessons', true );
$subjects_count  = (int) get_theme_mod( 'tls_subjects_count', 8 );
$lessons_count   = (int) get_theme_mod( 'tls_lessons_count', 3 );
$subjects        = $show_subjects ? tls_get_homepage_subjects( $subjects_count ) : array();
$lessons         = $show_lessons ? tls_get_homepage_lessons( $lessons_count ) : array();
$cta_url         = get_theme_mod( 'tls_panel_cta_url', '' );
if ( ! $cta_url ) {
	$cta_url = get_post_type_archive_link( 'lesson' );
}
?>
<section class="hero">
	<div class="wrap hero-grid">
		<div>
			<span class="eyebrow mono"><?php echo esc_html( get_theme_mod( 'tls_hero_eyebrow', __( 'A global learning library', 'the-learning-studio' ) ) ); ?></span>
			<h1 class="h1 script"><?php echo esc_html( get_theme_mod( 'tls_hero_title', __( 'Learn complex subjects, simply.', 'the-learning-studio' ) ) ); ?></h1>
			<p class="lead"><?php echo esc_html( get_theme_mod( 'tls_hero_intro', __( 'Study business, AI, technology, health, finance, marketing, psychology and operations through clear lessons made for real understanding.', 'the-learning-studio' ) ) ); ?></p>
			<?php get_search_form(); ?>
		</div>
		<div class="panel">
			<span class="mono pill"><?php echo esc_html( get_theme_mod( 'tls_panel_eyebrow', __( 'Start exploring', 'the-learning-studio' ) ) ); ?></span>
			<h2 class="script"><?php echo esc_html( get_theme_mod( 'tls_panel_title', __( 'Lessons for curious minds', 'the-learning-studio' ) ) ); ?></h2>
			<p class="muted"><?php echo esc_html( get_theme_mod( 'tls_panel_text', __( 'Definitions, examples, videos, notes and quick quizzes in one growing library.', 'the-learning-studio' ) ) ); ?></p>
			<a class="btn" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( get_theme_mod( 'tls_panel_cta_label', __( 'Start learning', 'the-learning-studio' ) ) ); ?></a>
		</div>
	</div>
</section>
<?php
/*
 * Content entered in the WordPress editor for the Page assigned as the
 * static front page (Settings -> Reading) renders here, between the hero
 * and the Featured subjects section. It is skipped entirely when empty, or
 * when the site is not set to a static front page, so sites relying only
 * on Customizer fields keep the original layout.
 */
$tls_front_page_id = ( 'page' === get_option( 'show_on_front' ) ) ? (int) get_option( 'page_on_front' ) : 0;
$tls_front_page     = $tls_front_page_id ? get_post( $tls_front_page_id ) : null;
if ( $tls_front_page instanceof WP_Post && '' !== trim( (string) $tls_front_page->post_content ) ) :
	global $post;
	$post = $tls_front_page;
	setup_postdata( $post );
	?>
	<section class="section homepage-content"><div class="article"><?php the_content(); ?></div></section>
	<?php
	wp_reset_postdata();
endif;
?>
<?php if ( $show_subjects ) : ?>
<section class="section">
	<div class="wrap">
		<div class="section-head"><div><span class="mono"><?php echo esc_html( get_theme_mod( 'tls_subjects_eyebrow', __( 'Featured subjects', 'the-learning-studio' ) ) ); ?></span><h2 class="h2 script"><?php echo esc_html( get_theme_mod( 'tls_subjects_heading', __( 'Choose what to learn', 'the-learning-studio' ) ) ); ?></h2></div></div>
		<div class="grid">
			<?php if ( $subjects ) : foreach ( $subjects as $subject ) : tls_subject_card( $subject ); endforeach; else : ?><p class="muted"><?php esc_html_e( 'Subjects will appear here after they are added.', 'the-learning-studio' ); ?></p><?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>
<?php if ( $show_lessons ) : ?>
<section class="section alt">
	<div class="wrap">
		<span class="mono"><?php echo esc_html( get_theme_mod( 'tls_lessons_eyebrow', __( 'Featured and latest lessons', 'the-learning-studio' ) ) ); ?></span>
		<h2 class="h2 script"><?php echo esc_html( get_theme_mod( 'tls_lessons_heading', __( 'Selected written notes and videos', 'the-learning-studio' ) ) ); ?></h2>
		<div class="grid">
			<?php if ( $lessons ) : foreach ( $lessons as $lesson ) : tls_lesson_card( $lesson ); endforeach; else : ?><p class="muted"><?php esc_html_e( 'Lessons will appear here after they are published.', 'the-learning-studio' ); ?></p><?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>
<?php get_footer(); ?>
