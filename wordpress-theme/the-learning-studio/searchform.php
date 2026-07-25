<?php $tls_search_id = wp_unique_id( 'tls-search-' ); ?>
<form role="search" method="get" class="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $tls_search_id ); ?>"><?php esc_html_e( 'Search for:', 'the-learning-studio' ); ?></label>
	<input id="<?php echo esc_attr( $tls_search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search subjects, lessons, topics…', 'the-learning-studio' ); ?>">
	<button class="btn" type="submit"><?php esc_html_e( 'Search', 'the-learning-studio' ); ?></button>
</form>
