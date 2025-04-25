<footer class="site-footer">
	<div class="footer-inner">
	<div class="footer-logo">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php
		if ( has_custom_logo() ) {
			the_custom_logo();
		} else {
			echo '<span class="site-name">' . get_bloginfo( 'name' ) . '</span>';
		}
		?>
		</a>
	</div>
	
	<nav class="footer-nav">
		<ul>
		<li><a href="<?php echo esc_url( home_url( '/about' ) ); ?>">About</a></li>
		<li><a href="<?php echo esc_url( home_url( '/blog' ) ); ?>">Blog</a></li>
		<li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact</a></li>
		<li><a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>">Privacy</a></li>
		</ul>
	</nav>

	<div class="footer-copy">
		&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.
	</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
