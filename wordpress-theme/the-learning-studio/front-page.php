<?php
/**
 * Home page.
 *
 * @package TheLearningStudio
 */

get_header();
$subjects = tls_get_homepage_subjects();
$lessons  = tls_get_homepage_lessons();
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
			<span class="mono pill"><?php esc_html_e( 'Start exploring', 'the-learning-studio' ); ?></span>
			<h2 class="script"><?php echo esc_html( get_theme_mod( 'tls_panel_title', __( 'Lessons for curious minds', 'the-learning-studio' ) ) ); ?></h2>
			<p class="muted"><?php echo esc_html( get_theme_mod( 'tls_panel_text', __( 'Definitions, examples, videos, notes and quick quizzes in one growing library.', 'the-learning-studio' ) ) ); ?></p>
			<a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'lesson' ) ); ?>"><?php esc_html_e( 'Start learning', 'the-learning-studio' ); ?></a>
		</div>
	</div>
</section>
<section class="section">
	<div class="wrap">
		<div class="section-head"><div><span class="mono"><?php esc_html_e( 'Featured subjects', 'the-learning-studio' ); ?></span><h2 class="h2 script"><?php esc_html_e( 'Choose what to learn', 'the-learning-studio' ); ?></h2></div></div>
		<div class="grid">
			<?php if ( $subjects ) : foreach ( $subjects as $subject ) : tls_subject_card( $subject ); endforeach; else : ?><p class="muted"><?php esc_html_e( 'Subjects will appear here after they are added.', 'the-learning-studio' ); ?></p><?php endif; ?>
		</div>
	</div>
</section>
<section class="section alt">
	<div class="wrap">
		<span class="mono"><?php esc_html_e( 'Featured and latest lessons', 'the-learning-studio' ); ?></span>
		<h2 class="h2 script"><?php esc_html_e( 'Selected written notes and videos', 'the-learning-studio' ); ?></h2>
		<div class="grid">
			<?php if ( $lessons ) : foreach ( $lessons as $lesson ) : tls_lesson_card( $lesson ); endforeach; else : ?><p class="muted"><?php esc_html_e( 'Lessons will appear here after they are published.', 'the-learning-studio' ); ?></p><?php endif; ?>
		</div>
	</div>
</section>
<?php get_footer(); ?>
