<?php
/**
 * Reusable frontend presentation helpers.
 *
 * @package TheLearningStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output the configured custom logo, falling back to the "L" mark.
 *
 * Shared by header.php and footer.php so both stay in sync with Customizer.
 */
function tls_site_logo(): void {
	if ( has_custom_logo() ) {
		echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'thumbnail', false, array( 'class' => 'custom-logo' ) );
		return;
	}
	echo '<span class="logo script" aria-hidden="true">L</span>';
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
				'meta_key'   => '_tls_featured',
				'meta_value' => '1',
			)
		)
	);
	$subjects = is_wp_error( $featured ) ? array() : $featured;
	usort(
		$subjects,
		static function ( WP_Term $a, WP_Term $b ): int {
			$priority_a = (int) get_term_meta( $a->term_id, '_tls_priority', true );
			$priority_b = (int) get_term_meta( $b->term_id, '_tls_priority', true );
			return $priority_b <=> $priority_a;
		}
	);
	$subjects = array_slice( $subjects, 0, $limit );

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
				'posts_per_page' => -1,
				'meta_key'       => '_tls_featured',
				'meta_value'     => '1',
			)
		)
	);
	usort(
		$lessons,
		static function ( WP_Post $a, WP_Post $b ): int {
			$priority_a = (int) get_post_meta( $a->ID, '_tls_priority', true );
			$priority_b = (int) get_post_meta( $b->ID, '_tls_priority', true );
			return $priority_b <=> $priority_a;
		}
	);
	$lessons = array_slice( $lessons, 0, $limit );

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
		<?php $excerpt = get_the_excerpt( $lesson ); ?>
		<?php if ( $excerpt ) : ?><span class="muted"><?php echo esc_html( $excerpt ); ?></span><?php endif; ?>
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
		<?php if ( $subject->description ) : ?><span class="muted"><?php echo esc_html( wp_trim_words( $subject->description, 20, '…' ) ); ?></span><?php endif; ?>
		<span class="pill mono"><?php echo esc_html( sprintf( _n( '%s lesson', '%s lessons', $subject->count, 'the-learning-studio' ), number_format_i18n( $subject->count ) ) ); ?></span>
	</a>
	<?php
}

/**
 * Group top-level Subjects by their `_tls_subject_group` term meta.
 *
 * Ungrouped subjects are collected under a translated "Other subjects"
 * bucket, which is always sorted last. Named groups sort alphabetically.
 *
 * @return array<string, WP_Term[]> Group label => top-level Subject terms.
 */
function tls_get_grouped_subjects(): array {
	$parents = get_terms(
		array(
			'taxonomy'   => 'subject',
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $parents ) || ! $parents ) {
		return array();
	}

	$other_label = __( 'Other subjects', 'the-learning-studio' );
	$groups      = array();
	$other       = array();
	foreach ( $parents as $parent ) {
		$group = trim( (string) get_term_meta( $parent->term_id, '_tls_subject_group', true ) );
		if ( '' === $group ) {
			$other[] = $parent;
			continue;
		}
		$groups[ $group ][] = $parent;
	}

	ksort( $groups, SORT_NATURAL | SORT_FLAG_CASE );
	if ( $other ) {
		$groups[ $other_label ] = $other;
	}

	return $groups;
}

/**
 * Render a nested list of a Subject's descendant terms, to any depth.
 *
 * @param int $parent_id Parent term ID.
 */
function tls_render_subject_children( int $parent_id ): void {
	$children = get_terms(
		array(
			'taxonomy'   => 'subject',
			'hide_empty' => false,
			'parent'     => $parent_id,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $children ) || ! $children ) {
		return;
	}
	echo '<ul class="subject-children">';
	foreach ( $children as $child ) {
		echo '<li><a href="' . esc_url( get_term_link( $child ) ) . '">' . esc_html( $child->name ) . '</a>';
		tls_render_subject_children( $child->term_id );
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Find Subject terms whose name, slug, or description contains the query.
 *
 * Plain substring matching (not SQL LIKE or regex) keeps this safe against
 * any characters a visitor types, including SQL/regex metacharacters.
 *
 * @param string $query Raw, unescaped search text.
 * @param int    $limit Maximum number of matching Subjects to return.
 * @return WP_Term[]
 */
function tls_search_subjects( string $query, int $limit = 20 ): array {
	$query = trim( $query );
	if ( '' === $query ) {
		return array();
	}

	$terms = get_terms( array( 'taxonomy' => 'subject', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	$matches = array_values(
		array_filter(
			$terms,
			static function ( WP_Term $term ) use ( $query ): bool {
				foreach ( array( $term->name, $term->slug, $term->description ) as $haystack ) {
					if ( '' !== $haystack && false !== mb_stripos( (string) $haystack, $query ) ) {
						return true;
					}
				}
				return false;
			}
		)
	);

	usort( $matches, static fn( WP_Term $a, WP_Term $b ): int => strcasecmp( $a->name, $b->name ) );

	return array_slice( $matches, 0, $limit );
}

function tls_youtube_embed_url( string $url ): string {
	if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $matches ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $matches[1] );
	}
	return '';
}
