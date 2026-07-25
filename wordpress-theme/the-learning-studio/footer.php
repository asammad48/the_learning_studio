<?php
/**
 * Site footer.
 *
 * @package TheLearningStudio
 */
?>
</main>
<footer class="footer">
	<div class="wrap footgrid">
		<div>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="logo script" aria-hidden="true">L</span>
				<span class="script"><?php bloginfo( 'name' ); ?></span>
			</a>
			<p class="muted"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		</div>
		<div>
			<b class="mono"><?php esc_html_e( 'Explore', 'the-learning-studio' ); ?></b>
			<?php wp_nav_menu( array( 'theme_location' => 'footer_explore', 'container' => false, 'fallback_cb' => 'tls_footer_explore_fallback' ) ); ?>
		</div>
		<div>
			<b class="mono"><?php esc_html_e( 'Studio', 'the-learning-studio' ); ?></b>
			<?php wp_nav_menu( array( 'theme_location' => 'footer_studio', 'container' => false, 'fallback_cb' => 'tls_footer_studio_fallback' ) ); ?>
		</div>
		<div>
			<b class="mono"><?php esc_html_e( 'Legal', 'the-learning-studio' ); ?></b>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer_legal',
					'container'      => false,
					'fallback_cb'    => 'tls_footer_legal_fallback',
				)
			);
			?>
		</div>
	</div>
	<div class="wrap copy">
		<span>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
		<span><?php esc_html_e( 'Made for learners everywhere', 'the-learning-studio' ); ?> &#127758;</span>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
