<?php get_header(); ?>

<main class="supplements-main">

	<header class="supplements-header">
		<?php if ( is_tax( 'supplement-category' ) ) : ?>
			<h1 class="archive-title"><?php single_term_title(); ?></h1>
			<p class="archive-subtitle">Browse supplements in this category</p>
		<?php else : ?>
			<h1 class="archive-title"><?php post_type_archive_title(); ?></h1>
			<p class="archive-subtitle">Browse all supplements</p>
		<?php endif; ?>
	</header>

	<!-- MOBILE FILTER TOGGLE BUTTON -->
	<button class="mobile-filter-toggle btn btn-primary" onclick="document.querySelector('.filters-sidebar').classList.toggle('open')">
	<i class="bi bi-sliders"></i>	
	Show Filters
	</button>

	<div class="supplements-layout">
		<aside class="filters-sidebar">

			<button class="mobile-filter-close" onclick="document.querySelector('.filters-sidebar').classList.remove('open')">
				Close ✕
			</button>

			<?php get_template_part( 'template-parts/archive-filters' ); ?>
			
			
		</aside>

		<section class="supplements-content">

			<!-- Ajax Search -->
			<div class="supplement-search-wrapper">
				<input type="text" id="supplement-search" placeholder="Search supplements...">
				<button type="button" id="supplement-search-clear">×</button>
				<div id="supplement-search-results"></div>
			</div>



			<?php if ( have_posts() ) : ?>
				<div class="supplement-grid">
					<?php
					while ( have_posts() ) {
						the_post();
						get_template_part( 'template-parts/supplement-card' );
					}
					?>
				</div>

				<div class="pagination-wrapper">
					<?php
					the_posts_pagination(
						array(
							'mid_size'           => 1,
							'prev_text'          => '« Previous',
							'next_text'          => 'Next »',
							'screen_reader_text' => '',
						)
					);
					?>
				</div>
			<?php else : ?>
				<p class="no-results">No supplements found.</p>
			<?php endif; ?>
		</section>
	</div>
</main>


<?php get_footer(); ?>
