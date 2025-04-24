<?php get_header(); ?>



<main class="supplements-main">

	<header class="supplements-header">
		<h1 class="archive-title"><?php post_type_archive_title(); ?></h1>
		<p class="archive-subtitle">Browse all supplements</p>
	</header>

	<!-- MOBILE FILTER TOGGLE BUTTON -->
	<button class="mobile-filter-toggle" onclick="document.querySelector('.filters-sidebar').classList.toggle('open')">
		Filter Results
	</button>

	<div class="supplements-layout">
		<aside class="filters-sidebar">

			<button class="mobile-filter-close" onclick="document.querySelector('.filters-sidebar').classList.remove('open')">
				Close ✕
			</button>

			<?php get_template_part( 'template-parts/archive-filters' ); ?>
			
			
		</aside>

		<section class="supplements-content">
			<?php if ( have_posts() ) : ?>
				<div class="supplement-grid">
					<?php
					while ( have_posts() ) {
						the_post();
						get_template_part( 'template-parts/supplement-card' );
					}
					?>
				</div>

				<div class="pagination">
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

<div id="compare-bar" class="compare-bar">
	<a id="compare-link" href="#" class="compare-link">Compare (<span id="compare-count">0</span>)</a>
</div>

<script>
	const maxCompare = 4;
	const compareBar = document.getElementById('compare-bar');
	const compareLink = document.getElementById('compare-link');
	const compareCount = document.getElementById('compare-count');
	const checkboxes = document.querySelectorAll('.compare-checkbox');
	let selectedIds = [];

	function updateUI() {
		compareCount.textContent = selectedIds.length;
		compareBar.style.display = selectedIds.length > 0 ? 'block' : 'none';
		compareLink.href = `/compare/?ids=${selectedIds.join(',')}`;
	}

	checkboxes.forEach(box => {
		box.addEventListener('change', () => {
			const id = box.value;
			if (box.checked) {
				if (selectedIds.length < maxCompare) {
					selectedIds.push(id);
				} else {
					box.checked = false;
					alert(`You can only compare up to ${maxCompare} supplements.`);
				}
			} else {
				selectedIds = selectedIds.filter(i => i !== id);
			}
			updateUI();
		});
	});
</script>

<?php get_footer(); ?>
