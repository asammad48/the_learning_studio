<?php
/** Individual lesson. @package TheLearningStudio */
get_header();
while ( have_posts() ) :
	the_post();
	$duration = get_post_meta( get_the_ID(), '_tls_duration', true );
	$youtube  = tls_youtube_embed_url( (string) get_post_meta( get_the_ID(), '_tls_youtube_url', true ) );
	$quiz     = get_post_meta( get_the_ID(), '_tls_quiz', true );
	$terms    = get_the_terms( get_the_ID(), 'subject' );
	?>
	<section class="page-hero"><div class="wrap">
		<span class="eyebrow mono"><?php echo esc_html( tls_get_lesson_format_label() . ( $duration ? ' · ' . $duration : '' ) ); ?></span>
		<h1 class="h2 script"><?php the_title(); ?></h1>
		<?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
		<?php if ( $terms && ! is_wp_error( $terms ) ) : ?><p><a href="<?php echo esc_url( get_term_link( $terms[0] ) ); ?>"><?php echo esc_html( $terms[0]->name ); ?></a></p><?php endif; ?>
		<?php if ( $youtube ) : ?><div class="video-frame"><iframe src="<?php echo esc_url( $youtube ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" allowfullscreen loading="lazy"></iframe></div><?php elseif ( has_post_thumbnail() ) : the_post_thumbnail( 'large', array( 'class' => 'media-thumb lesson-hero-image' ) ); endif; ?>
	</div></section>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'article' ); ?>>
		<?php the_content(); ?>
		<?php wp_link_pages(); ?>
		<?php if ( ! post_password_required() && is_array( $quiz ) && $quiz ) : ?>
			<section class="quiz"><h2 class="script"><?php esc_html_e( 'Quick quiz', 'the-learning-studio' ); ?></h2>
			<?php foreach ( $quiz as $item ) : ?><details><summary><?php echo esc_html( $item['question'] ); ?></summary><p><?php echo esc_html( $item['answer'] ); ?></p></details><?php endforeach; ?>
			</section>
		<?php endif; ?>
	</article>
	<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>
	<?php
endwhile;
get_footer();
