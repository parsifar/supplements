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


<script>
(function () {
	const maxCompare = 4;
	const compareBar = document.getElementById('compare-bar');
	const compareLink = document.getElementById('compare-link');
	const compareMessage = document.getElementById('compare-message');
	const compareList = document.getElementById('compare-list');
	const compareWrapper = document.getElementById('compare-list-wrapper');
	const toggleListBtn = document.getElementById('toggle-compare-list');
	const removeAllBtn = document.getElementById('remove-all');

	let selectedIds = JSON.parse(localStorage.getItem('compareIds') || '[]');

	function saveState() {
		localStorage.setItem('compareIds', JSON.stringify(selectedIds));
	}

	function updateUI() {
		if (selectedIds.length > 0) {
			compareBar.style.display = 'block';
			compareMessage.textContent = `${selectedIds.length} product${selectedIds.length > 1 ? 's' : ''} selected for comparison`;
			compareLink.href = `/compare/?ids=${selectedIds.join(',')}`;
		} else {
			compareBar.style.display = 'none';
			compareWrapper.classList.remove('open');
			toggleListBtn.textContent = 'Show selected products';
		}

		compareList.innerHTML = '';
		selectedIds.forEach(id => {
			const li = document.createElement('li');
			const title = localStorage.getItem(`compareTitle-${id}`) || `Product ${id}`;
			li.textContent = title;
			const removeBtn = document.createElement('button');
			removeBtn.textContent = '×';
			removeBtn.setAttribute('data-id', id);
			removeBtn.addEventListener('click', () => {
				selectedIds = selectedIds.filter(i => i !== id);
				saveState();
				updateUI();
				updateCheckboxes();
			});
			li.appendChild(removeBtn);
			compareList.appendChild(li);
		});
	}

	function updateCheckboxes() {
		document.querySelectorAll('.compare-checkbox').forEach(box => {
			box.checked = selectedIds.includes(box.value);
		});
	}

	document.addEventListener('change', function (e) {
		if (!e.target.classList.contains('compare-checkbox')) return;
		const id = e.target.value;

		if (e.target.checked) {
			if (selectedIds.length < maxCompare) {
				selectedIds.push(id);
				localStorage.setItem(`compareTitle-${id}`, e.target.dataset.title);
			} else {
				e.target.checked = false;
				alert(`You can only compare up to ${maxCompare} products.`);
			}
		} else {
			selectedIds = selectedIds.filter(i => i !== String(id));
			localStorage.removeItem(`compareTitle-${id}`);
		}
		saveState();
		updateUI();
	});

	toggleListBtn.addEventListener('click', () => {
		const isOpen = compareWrapper.classList.toggle('open');
		toggleListBtn.textContent = isOpen ? 'Hide selected products' : 'Show selected products';
	});

	removeAllBtn.addEventListener('click', () => {
		const oldIds = [...selectedIds]; // clone before clearing
		selectedIds = [];
		oldIds.forEach(id => localStorage.removeItem(`compareTitle-${id}`));
		saveState();
		updateUI();
		updateCheckboxes();
	});

	// Init
	updateUI();
	updateCheckboxes();
})();
</script>



</body>
</html>
