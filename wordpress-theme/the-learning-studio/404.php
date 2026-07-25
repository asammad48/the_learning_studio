<?php
/** Not-found page. @package TheLearningStudio */
get_header();
?>
<section class="page-hero error-404"><div class="wrap"><span class="mono">404</span><h1 class="h2 script"><?php esc_html_e( 'Page not found', 'the-learning-studio' ); ?></h1><p class="lead"><?php esc_html_e( 'The page may have moved or no longer exists. Search the library or return home.', 'the-learning-studio' ); ?></p><?php get_search_form(); ?><p><a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return home', 'the-learning-studio' ); ?></a></p></div></section>
<?php get_footer(); ?>
