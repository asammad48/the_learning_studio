<?php
/**
 * Reusable frontend presentation helpers.
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tls_get_lesson_format_label( int $post_id = 0 ): string {
	$labels = array(
		'written'       => __( 'Written lesson', 'the-learning-studio' ),
		'video'         => __( 'Video lesson', 'the-learning-studio' ),
		'video-written' => __( 'Video + notes', 'the-learning-studio' ),
	);
	$format = get_post_meta( $post_id ?: get_the_ID(), '_tls_lesson_format', true ) ?: 'written';
	return $labels[ $format ] ?? $labels['written'];
}

/**
 * Get featured top-level subjects, filling unused positions with other terms.
 *
 * @param int $limit Maximum number of subjects to return.
 * @return WP_Term[]
 */
function tls_get_homepage_subjects( int $limit = 8 ): array {
	$limit = max( 1, $limit );
	$common_args = array(
		'taxonomy'   => 'subject',
		'hide_empty' => false,
		'parent'     => 0,
		'orderby'    => 'name',
		'order'      => 'ASC',
	);
	$featured = get_terms(
		array_merge(
			$common_args,
			array(
				'number'     => $limit,
				'meta_key'   => '_tls_featured',
				'meta_value' => '1',
			)
		)
	);
	$subjects = is_wp_error( $featured ) ? array() : $featured;

	if ( count( $subjects ) < $limit ) {
		$fallback = get_terms(
			array_merge(
				$common_args,
				array(
					'number'  => $limit - count( $subjects ),
					'exclude' => wp_list_pluck( $subjects, 'term_id' ),
				)
			)
		);
		if ( ! is_wp_error( $fallback ) ) {
			$subjects = array_merge( $subjects, $fallback );
		}
	}

	return $subjects;
}

/**
 * Get featured lessons, filling unused positions with the latest lessons.
 *
 * @param int $limit Maximum number of lessons to return.
 * @return WP_Post[]
 */
function tls_get_homepage_lessons( int $limit = 3 ): array {
	$limit = max( 1, $limit );
	$common_args = array(
		'post_type'      => 'lesson',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	$lessons = get_posts(
		array_merge(
			$common_args,
			array(
				'meta_key'   => '_tls_featured',
				'meta_value' => '1',
			)
		)
	);

	if ( count( $lessons ) < $limit ) {
		$fallback = get_posts(
			array_merge(
				$common_args,
				array(
					'posts_per_page' => $limit - count( $lessons ),
					'post__not_in'   => wp_list_pluck( $lessons, 'ID' ),
				)
			)
		);
		$lessons = array_merge( $lessons, $fallback );
	}

	return $lessons;
}

function tls_lesson_card( ?WP_Post $lesson = null ): void {
	$lesson  = $lesson ?: get_post();
	$terms   = get_the_terms( $lesson, 'subject' );
	$subject = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : __( 'Lesson', 'the-learning-studio' );
	$duration = get_post_meta( $lesson->ID, '_tls_duration', true );
	?>
	<a <?php post_class( 'card', $lesson->ID ); ?> href="<?php echo esc_url( get_permalink( $lesson ) ); ?>">
		<?php if ( has_post_thumbnail( $lesson ) ) : ?>
			<?php echo get_the_post_thumbnail( $lesson, 'medium_large', array( 'class' => 'media-thumb' ) ); ?>
		<?php endif; ?>
		<span class="mono pill"><?php echo esc_html( $subject ); ?></span>
		<b class="script"><?php echo esc_html( get_the_title( $lesson ) ); ?></b>
		<?php if ( has_excerpt( $lesson ) ) : ?><span class="muted"><?php echo esc_html( get_the_excerpt( $lesson ) ); ?></span><?php endif; ?>
		<span class="pill mono"><?php echo esc_html( $duration ?: tls_get_lesson_format_label( $lesson->ID ) ); ?></span>
	</a>
	<?php
}

function tls_subject_card( WP_Term $subject ): void {
	$image_id = (int) get_term_meta( $subject->term_id, '_tls_image_id', true );
	$image_url = (string) get_term_meta( $subject->term_id, '_tls_image_url', true );
	?>
	<a class="card" href="<?php echo esc_url( get_term_link( $subject ) ); ?>">
		<?php if ( $image_id ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'medium_large', false, array( 'class' => 'media-thumb' ) ); ?>
		<?php elseif ( $image_url ) : ?>
			<img class="media-thumb" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $subject->name ); ?>">
		<?php endif; ?>
		<span class="letter script"><?php echo esc_html( strtoupper( function_exists( 'mb_substr' ) ? mb_substr( $subject->name, 0, 1 ) : substr( $subject->name, 0, 1 ) ) ); ?></span>
		<b class="script"><?php echo esc_html( $subject->name ); ?></b>
		<span class="muted"><?php echo esc_html( $subject->description ); ?></span>
		<span class="pill mono"><?php echo esc_html( sprintf( _n( '%s lesson', '%s lessons', $subject->count, 'the-learning-studio' ), number_format_i18n( $subject->count ) ) ); ?></span>
	</a>
	<?php
}

function tls_youtube_embed_url( string $url ): string {
	if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $matches ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $matches[1] );
	}
	return '';
}
