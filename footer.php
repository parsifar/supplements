<?php astra_content_bottom(); ?>

</div> <!-- ast-container (Astra) -->
</div><!-- #content (Astra) -->

<footer class="site-footer">
	<div class="footer-inner ast-container">
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

		<div class="footer-tagline">
			<p>Smarter Supplement Shopping Starts Here</p>
		</div>
		
		<nav class="footer-nav">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer_menu',
					'container'      => false,
					'menu_class'     => 'footer-menu',
					'items_wrap'     => '<ul class="footer-menu">%3$s</ul>',
					'fallback_cb'    => false,
					'depth'          => 2,
				)
			);
			?>
		</nav>

		<div class="disclaimer">
			<h6>Disclaimer</h6>
			<p>We strive to provide accurate and up-to-date information about dietary supplements, including ingredients, pricing, and availability. Product data—such as price, servings, and ratings—is updated daily and may reflect information from Amazon and other third-party sources. However, we cannot guarantee the accuracy, completeness, or reliability of any information presented on this site.</p>
			<p>This website does not provide medical advice. All content is for informational purposes only and should not be used as a substitute for professional medical advice, diagnosis, or treatment. Always consult your physician or other qualified health provider before starting any new supplement, especially if you have a medical condition or are taking medication.</p>
			<p>The inclusion of a product on this site does not constitute an endorsement or guarantee. Product availability, ingredients, pricing, and ratings may change without notice. Please refer to the manufacturer's or retailer's official site for the most current product information.</p>
			<p>Affiliate Disclosure: Some links on this site may be affiliate links, meaning we may earn a commission if you purchase through them, at no extra cost to you.</p>
			<p>Use this site at your own risk. We assume no liability for any errors or omissions, or for any actions taken based on the content provided.</p>
		</div>

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
