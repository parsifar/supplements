<?php astra_content_bottom(); ?>

</div> <!-- ast-container (Astra) -->
</div><!-- #content (Astra) -->

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



<!-- Compare Bar -->
<div id="compare-bar" class="compare-bar" style="display:none;">
	<p id="compare-message" class="compare-message">0 products selected for comparison</p>
	<div class="compare-controls">
		<a id="compare-link" href="#" class="compare-link btn btn-primary">Compare selected products</a>
		<button id="toggle-compare-list" class="toggle-btn btn btn-secondary small">Show selected products</button>
		<button id="remove-all" class="remove-all-btn btn btn-secondary small">Remove All</button>
	</div>
	<div id="compare-list-wrapper" class="compare-list-wrapper">
		<ul id="compare-list" class="compare-list"></ul>
	</div>
</div>



</div><!-- #page (Astra)-->

<?php
	astra_body_bottom();
	wp_footer();
?>

</body>
</html>
