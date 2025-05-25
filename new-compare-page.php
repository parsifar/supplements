<?php
/**
 * Template Name: New Compare Page
 *
 * This template creates a supplement comparison page that allows users to:
 * - Search for supplements
 * - Compare up to 3 supplements side by side
 * - View detailed information including calories, servings, ratings, and prices
 * - Compare ingredients across selected supplements
 * - View category-specific information (e.g., caffeine for pre-workouts, protein content for protein supplements)
 */

get_header();
?>

<!-- Required JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<!-- Alpine.js Rating Bar Directive -->
<script>
document.addEventListener('alpine:init', () => {
	Alpine.directive('rating-bar', (el, { expression }) => {
		const rating = parseFloat(el.getAttribute("data-rating"));
		const barFill = el.querySelector(".bar-fill");

		// Segment widths in percentage (total 100%)
		const segments = [
			{ min: 0, max: 1, width: 14.2857 },
			{ min: 1, max: 2, width: 14.2857 },
			{ min: 2, max: 3, width: 14.2857 },
			{ min: 3, max: 4, width: 14.2857 },
			{ min: 4, max: 5, width: 42.8571 },
		];

		let fillPercent = 0;
		for (let i = 0; i < segments.length; i++) {
			const seg = segments[i];
			if (rating >= seg.max) {
				fillPercent += seg.width;
			} else if (rating > seg.min) {
				const portion = (rating - seg.min) / (seg.max - seg.min);
				fillPercent += seg.width * portion;
				break;
			} else {
				break;
			}
		}

		barFill.style.width = `${fillPercent}%`;
	});
});
</script>

<!-- Main Comparison Interface -->
<div x-data="comparePage()" class="compare-container mx-auto py-8">
	<h1 class="mb-4">Compare Supplements Side-by-Side</h1>

	<!-- Search Interface -->
	<!-- Allows users to search for supplements and displays results in a dropdown -->
	<div class="search-wrapper mb-6">
		<div class="search-field-wrapper">
			<input
				x-model="searchQuery"
				@input.debounce.300ms="fetchSearchResults"
				type="text"
				class="search-field p-2 w-full"
				placeholder="Search for supplements..."
				:disabled="selectedProducts.filter(Boolean).length >= 3"
			/>
			<button 
				@click="clearSearch" 
				class="search-icon"
				x-show="searchQuery"
				:disabled="selectedProducts.filter(Boolean).length >= 3"
			>
				<i class="bi bi-x-lg"></i>
			</button>
			<button 
				class="search-icon"
				x-show="!searchQuery"
				:disabled="selectedProducts.filter(Boolean).length >= 3"
			>
				<i class="bi bi-search"></i>
			</button>
		</div>
		<p 
			x-show="selectedProducts.filter(Boolean).length >= 3" 
			class="search-message"
		>
			Please remove a supplement in order to select a different one
		</p>
		<ul x-show="searchResults.length" id="search-results" class="bg-white">
			<template x-for="(result, index) in searchResults" :key="'search-' + index">
			<li class="search-result">
				<button
				@click="addToCompare(result.id)"
				class="block w-full text-left p-2 flex items-center"
				>
				<img :src="getThumbnailUrl(result)" class="object-contain" />
				
				<span class="title-wrapper">
					<span class="brand" x-text="getBrandName(result)"></span>
					<span class="title" x-text="result.title.rendered"></span>
				</span>
				
				</button>
			</li>
			</template>
		</ul>
	</div>

	<!-- Comparison Slots -->
	<!-- Displays up to 3 selected supplements with their basic information -->
	<div class="header-grid grid grid-cols-3 gap-4 mb-8">
		<template x-for="(product, index) in selectedProducts" :key="'slot-' + index">
			<div class="supplement-header min-h-[150px]">
				
				<template x-if="product">
					<div class="full slot">
						<button @click="removeFromCompare(index)" class="remove-btn">Remove supplement</button>

						<img :src="product.image" class="h-32 object-contain mx-auto mb-2" />

						<div class="title-wrapper">
							<p class="brand" x-text="product.brand"></p>
							<h2 class="title" x-text="product.title"></h2>
						</div>
						<a :href="product.affiliate_url" target="_blank" class="buy-btn btn btn-primary"><i class="bi bi-amazon"></i>View on Amazon</a>
						
						
					</div>
				</template>

				<template x-if="!product">
					<div class="empty slot text-gray-400">Empty slot</div>
				</template>
			</div>
		</template>
	</div>

	<!-- Comparison Table -->
	<!-- Only shows when at least one product is selected -->
	<div class="tables-wrapper" x-show="selectedProducts.filter(p => p).length" x-effect="initializeRatingBars()">
		<!-- Overview Section -->
		<div  class="section overview">
			<h3 class="section-title"><i class="bi bi-check-circle"></i> </i>Overview</h3>
			<template x-for="(field, fieldIndex) in ['Servings per container','Price',  'Price per serving',  'Rating','Calories']" :key="'overview-field-' + fieldIndex">
				<div class="row">
					<div class="row-title" x-text="field"></div>
					<div class="grid grid-cols-3 gap-4">
						<template x-for="(product, pIndex) in selectedProducts" :key="'overview-product-' + pIndex">
							<div class="column">
								<template x-if="product && field === 'Rating'">
									<div x-html="getOverviewValue(product, field)"></div>
								</template>
								<template x-if="product && field !== 'Rating'">
									<span x-text="getOverviewValue(product, field)"></span>
								</template>
							</div>
						</template>
					</div>
				</div>
			</template>

			<!-- Taxonomy Rows -->
			<template x-for="(field, fieldIndex) in ['Category', 'Form', 'Certification', 'Dietary']" :key="'taxonomy-field-' + fieldIndex">
				<div class="row">
					<div class="row-title" x-text="field"></div>
					<div class="grid grid-cols-3 gap-4">
						<template x-for="(product, pIndex) in selectedProducts" :key="'taxonomy-product-' + pIndex">
							<div class="column">
								<template x-if="product">
									<span x-text="getTaxonomyValue(product, field)"></span>
								</template>
							</div>
						</template>
					</div>
				</div>
			</template>
		</div>

		<!-- Category-Specific Information -->
		<div class="section highlights">
			<h3 class="section-title"><i class="bi bi-rocket-takeoff"></i> Highlights</h3>
			<template x-for="(field, fieldIndex) in ['Total Caffeine per serving', 'Protein/Serving']" :key="'category-field-' + fieldIndex">
				<div x-show="shouldShowCategoryField(field)" class="row">
					<div class="row-title" x-text="field"></div>
					<div class="grid grid-cols-3 gap-4">
						<template x-for="(product, pIndex) in selectedProducts" :key="'category-product-' + pIndex">
							<div class="column">
								<template x-if="product">
									<span 
										x-text="getCategoryValue(product, field)"
										:class="{ 'text-green-600 font-bold': isMaxCategoryValue(product, field) }"
									></span>
								</template>
							</div>
						</template>
					</div>
				</div>
			</template>
		</div>

		<!-- Ingredients Comparison -->
		<div class="section ingredients">
			<div class="section-header">
				<h3 class="section-title">
					<i class="bi bi-flask"></i> 
					<span x-text="isPriceNormalized ? 'Ingredients (Price Normalized)' : 'Ingredients'"></span>
				</h3>
				<button 
					@click="togglePriceNormalized()" 
					class="normalize-btn btn btn-secondary"
					:class="{ 'active': isPriceNormalized }"
				>
					<i class="bi bi-calculator-fill"></i>
					<span x-text="isPriceNormalized ? 'Show Actual Amounts' : 'Compare at Equal Price'"></span>
				</button>
			</div>
			<div :key="'ingredients-' + isPriceNormalized + '-' + selectedProducts.filter(Boolean).map(p => p.id).join('-')">
				<template x-for="(ingredient, index) in sortedIngredients" :key="'ingredient-' + index">
					<div class="row">
						<div class="row-title">
							<span class="tooltip-wrapper">
								<a :href="ingredient.permalink" class="text-primary hover:underline" x-text="ingredient.name"></a>
								<span class="tooltip-text" x-text="ingredient.excerpt || 'No description available'"></span>
							</span>
						</div>
						<div class="grid grid-cols-3 gap-4">
							<template x-for="(product, pIndex) in selectedProducts" :key="'ingredient-product-' + pIndex">
								<div class="column">
									<template x-if="product">
										<template x-if="getIngredientAmount(ingredient, product) !== '—'">
											<span
												x-text="getIngredientAmount(ingredient, product)"
												:class="shouldHighlightAmount(ingredient, product) ? 'text-green-600 font-bold' : ''"
											></span>
										</template>
										<template x-if="getIngredientAmount(ingredient, product) === '—'">
											<span>—</span>
										</template>
									</template>
								</div>
							</template>
						</div>
					</div>
				</template>
			</div>
		</div>
	</div>

	<!-- Page Content -->
	<div class="page-content">
		<?php the_content(); ?>
	</div>
</div>

<!-- Alpine.js Component -->
<script>
/**
 * Main comparison page component
 * Handles all the logic for the supplement comparison functionality
 */
function comparePage() {
	return {
	searchQuery: '',
	searchResults: [],
	selectedProducts: [null, null, null],
	sortedIngredients: [],
	isPriceNormalized: false,
	originalIngredients: [],

	clearSearch() {
		this.searchQuery = '';
		this.searchResults = [];
	},

	init() {
		// First check URL for product IDs
		const urlParams = new URLSearchParams(window.location.search);
		const urlIds = urlParams.get('ids');
		
		let productIds = [];
		
		if (urlIds) {
			// If URL has IDs, use those and update local storage
			productIds = urlIds.split(',').map(id => id.trim());
			localStorage.setItem('compareIds', JSON.stringify(productIds));
		} else {
			// If no URL IDs, check local storage
			productIds = JSON.parse(localStorage.getItem('compareIds') || '[]');
		}

		if (productIds.length > 0) {
			// Create an array of promises for loading each product
			const loadPromises = productIds.map(id => 
				fetch(`/wp-json/wp/v2/supplement/${id}?_embed`)
					.then(res => res.json())
					.then(data => {
						const acf = data.acf || {};
						const category = data['supplement-category']?.map(term => term.name).join(', ') || '';
						const brand = data.brand?.[0]?.name || '';
						const product_form = data['product-form']?.map(term => term.name).join(', ') || '';
						const certification = data.certification?.map(term => term.name).join(', ') || '';
						const dietary_tag = data['dietary-tag']?.map(term => term.name).join(', ') || '';
						const dosages = Array.isArray(acf.dosages) ? acf.dosages : [];
						
						// Create an array of promises for fetching ingredient details
						const ingredientPromises = dosages.map(d => {
							if (!d.ingredient?.ID) return Promise.resolve(null);
							return fetch(`/wp-json/wp/v2/ingredient/${d.ingredient.ID}`)
								.then(res => res.json())
								.then(ingredientData => ({
									name: d.ingredient?.post_title || 'Unknown',
									amount: parseFloat(d.amount) || 0,
									unit: d.unit || '',
									permalink: ingredientData.link || '',
									excerpt: ingredientData.excerpt?.rendered ? 
										ingredientData.excerpt.rendered.replace(/<[^>]*>/g, '') : 
										'No description available'
								}));
						});

						// Wait for all ingredient details to be fetched
						return Promise.all(ingredientPromises)
							.then(ingredients => {
								return {
									id: data.id,
									title: data.title?.rendered || 'Untitled',
									image: data._embedded?.['wp:featuredmedia']?.[0]?.source_url || '',
									calories: acf.calories || '',
									servings: acf.servings_per_container || '',
									amazon_rating: acf.amazon_rating || '',
									price: acf.price || '',
									price_per_serving: acf.price_per_serving || '',
									affiliate_url: acf.affiliate_url || '',
									category,
									brand,
									product_form,
									certification,
									dietary_tag,
									total_caffeine_content: acf.total_caffeine_content || '',
									protein_per_serving: acf.protein_per_serving || '',
									ingredients: ingredients.filter(Boolean)
								};
							});
					})
			);

			// Load all products in parallel and maintain order
			Promise.all(loadPromises).then(products => {
				// Fill the selectedProducts array in order
				products.forEach((product, index) => {
					this.selectedProducts[index] = product;
				});
				this.recalculateIngredients();
			});
		}
	},

	updateLocalStorage() {
		// Get all non-null product IDs
		const productIds = this.selectedProducts
			.filter(p => p !== null)
			.map(p => p.id.toString());
		
		// Update local storage with IDs
		localStorage.setItem('compareIds', JSON.stringify(productIds));

		// Store titles for each product
		this.selectedProducts.forEach(product => {
			if (product) {
				localStorage.setItem(`compareTitle-${product.id}`, product.title);
			}
		});
	},

	/**
	 * Fetches search results from the WordPress API
	 * Triggered on search input with debounce
	 */
	fetchSearchResults() {
		if (!this.searchQuery) return;
		
		// First search in titles
		const titleSearch = fetch(`/wp-json/wp/v2/supplement?search=${this.searchQuery}&_embed&acf=true&per_page=20`)
			.then(res => res.json());

		// Then search in brands
		const brandSearch = fetch(`/wp-json/wp/v2/brand?search=${this.searchQuery}&per_page=20`)
			.then(res => res.json())
			.then(brands => {
				if (brands.length === 0) return Promise.resolve([]);
				const brandIds = brands.map(brand => brand.id);
				return fetch(`/wp-json/wp/v2/supplement?brand=${brandIds.join(',')}&_embed&acf=true&per_page=20`)
					.then(res => res.json());
			});

		// Combine both results
		Promise.all([titleSearch, brandSearch])
			.then(([titleResults, brandResults]) => {
				// Combine results and remove duplicates
				const allResults = [...titleResults, ...brandResults];
				const uniqueResults = allResults.filter((result, index, self) =>
					index === self.findIndex((r) => r.id === result.id)
				);
				this.searchResults = uniqueResults;
			});
	},

	/**
	 * Adds a product to the comparison
	 * Fetches full product details and updates the comparison
	 */
	addToCompare(id) {
		if (this.selectedProducts.filter(Boolean).length >= 3) return;

		// Remove all highlights first
		this.removeHighlights();

		fetch(`/wp-json/wp/v2/supplement/${id}?_embed`).then(res => res.json()).then(data => {
			const index = this.selectedProducts.findIndex(p => p === null);
			if (index !== -1) {
				const acf = data.acf || {};
				const category = data['supplement-category']?.map(term => term.name).join(', ') || '';
				const brand = data.brand?.[0]?.name || '';
				const product_form = data['product-form']?.map(term => term.name).join(', ') || '';
				const certification = data.certification?.map(term => term.name).join(', ') || '';
				const dietary_tag = data['dietary-tag']?.map(term => term.name).join(', ') || '';
				const dosages = Array.isArray(acf.dosages) ? acf.dosages : [];
				
				// First add the supplement with basic information
				this.selectedProducts[index] = {
					id: data.id,
					title: data.title?.rendered || 'Untitled',
					image: data._embedded?.['wp:featuredmedia']?.[0]?.source_url || '',
					calories: acf.calories || '',
					servings: acf.servings_per_container || '',
					amazon_rating: acf.amazon_rating || '',
					price: acf.price || '',
					price_per_serving: acf.price_per_serving || '',
					affiliate_url: acf.affiliate_url || '',
					category,
					brand,
					product_form,
					certification,
					dietary_tag,
					total_caffeine_content: acf.total_caffeine_content || '',
					protein_per_serving: acf.protein_per_serving || '',
					ingredients: dosages.map(d => ({
						name: d.ingredient?.post_title || 'Unknown',
						amount: parseFloat(d.amount) || 0,
						unit: d.unit || '',
						permalink: '',
						excerpt: 'Loading...'
					}))
				};

				// Clear search field and results
				this.searchQuery = '';
				this.searchResults = [];

				// Update local storage
				this.updateLocalStorage();

				// Initialize rating bars immediately
				this.$nextTick(() => {
					window.initializeRatingBars();
				});

				// Then fetch ingredient details in the background
				const ingredientPromises = dosages.map(d => {
					if (!d.ingredient?.ID) return Promise.resolve(null);
					return fetch(`/wp-json/wp/v2/ingredient/${d.ingredient.ID}`)
						.then(res => res.json())
						.then(ingredientData => ({
							name: d.ingredient?.post_title || 'Unknown',
							amount: parseFloat(d.amount) || 0,
							unit: d.unit || '',
							permalink: ingredientData.link || '',
							excerpt: ingredientData.excerpt?.rendered ? 
								ingredientData.excerpt.rendered.replace(/<[^>]*>/g, '') : 
								'No description available'
						}));
				});

				// Update ingredients when details are loaded
				Promise.all(ingredientPromises)
					.then(ingredients => {
						this.selectedProducts[index].ingredients = ingredients.filter(Boolean);
						this.recalculateIngredients();
					});
			}
		});
	},

	/**
	 * Removes a product from the comparison
	 */
	removeFromCompare(index) {
		// Remove all highlights first
		this.removeHighlights();
		
		// Get the product before removing it
		const product = this.selectedProducts[index];
		
		// Remove the product
		this.selectedProducts[index] = null;
		
		// If there was a product, remove its title from local storage
		if (product) {
			localStorage.removeItem(`compareTitle-${product.id}`);
		}

		this.recalculateIngredients();

		// Update local storage
		this.updateLocalStorage();

		// Force a re-render of the ingredients section and initialize rating bars
		this.$nextTick(() => {
			this.recalculateIngredients();
			window.initializeRatingBars();
		});
	},

	/**
	 * Recalculates the ingredients comparison
	 * Creates a map of all ingredients and their amounts across products
	 * Sorts ingredients by frequency and name
	 */
	recalculateIngredients() {
		const ingredientsMap = {};

		this.selectedProducts.filter(Boolean).forEach(p => {
			(p.ingredients || []).forEach(ing => {
				const key = ing.name.toLowerCase();
				if (!ingredientsMap[key]) {
					ingredientsMap[key] = { 
						name: ing.name, 
						permalink: ing.permalink,
						excerpt: ing.excerpt,
						amounts: {}, 
						originalAmounts: {} // Store original amounts
					};
				}
				const numericAmount = parseFloat(ing.amount) || 0;
				ingredientsMap[key].amounts[p.id] = `${numericAmount} ${ing.unit}`;
				ingredientsMap[key].originalAmounts[p.id] = `${numericAmount} ${ing.unit}`; // Store original
			});
		});

		// Sort ingredients by frequency and name
		this.sortedIngredients = Object.values(ingredientsMap).sort((a, b) => {
			const aCount = Object.keys(a.amounts).length;
			const bCount = Object.keys(b.amounts).length;
			return bCount - aCount || a.name.localeCompare(b.name);
		});

		// If price normalized is active, recalculate amounts
		if (this.isPriceNormalized) {
			this.togglePriceNormalized();
		}
	},

	togglePriceNormalized() {
		this.isPriceNormalized = !this.isPriceNormalized;
		
		if (this.isPriceNormalized) {
			// Find the product with highest price per serving
			const products = this.selectedProducts.filter(Boolean);
			
			// Group products by price per serving
			const priceGroups = products.reduce((groups, product) => {
				const price = parseFloat(product.price_per_serving) || 0;
				if (!groups[price]) {
					groups[price] = [];
				}
				groups[price].push(product);
				return groups;
			}, {});

			// Get the highest price
			const highestPrice = Math.max(...Object.keys(priceGroups).map(Number));

			// Recalculate amounts based on price ratio
			this.sortedIngredients.forEach(ingredient => {
				products.forEach(product => {
					// Skip products that already have the highest price
					if (parseFloat(product.price_per_serving) === highestPrice) return;

					const originalAmount = ingredient.originalAmounts[product.id];
					if (!originalAmount) return;

					const [amount, unit] = originalAmount.split(' ');
					const numericAmount = parseFloat(amount);
					if (isNaN(numericAmount)) return;

					const priceRatio = highestPrice / parseFloat(product.price_per_serving);
					const normalizedAmount = Math.round(numericAmount * priceRatio);
					
					ingredient.amounts[product.id] = `${normalizedAmount} ${unit}`;
				});
			});
		} else {
			// Restore original amounts
			this.sortedIngredients.forEach(ingredient => {
				ingredient.amounts = { ...ingredient.originalAmounts };
			});
		}
	},

	shouldHighlightAmount(ingredient, product) {
		if (!product || !ingredient.amounts[product.id]) return false;
		
		const currentAmount = parseFloat(ingredient.amounts[product.id]);
		if (isNaN(currentAmount) || currentAmount <= 0) return false;

		// Get all valid amounts
		const validAmounts = this.selectedProducts
			.filter(p => p && ingredient.amounts[p.id])
			.map(p => parseFloat(ingredient.amounts[p.id]))
			.filter(amount => !isNaN(amount) && amount > 0);

		// If no valid amounts, return false
		if (validAmounts.length === 0) return false;

		// If there's only one valid amount, highlight it
		if (validAmounts.length === 1) {
			return currentAmount === validAmounts[0];
		}

		// Find the maximum amount
		const maxAmount = Math.max(...validAmounts);

		// If this is the maximum amount, check if it's the first occurrence
		if (currentAmount === maxAmount) {
			const firstMaxIndex = this.selectedProducts.findIndex(p => 
				p && parseFloat(ingredient.amounts[p.id]) === maxAmount
			);
			return firstMaxIndex === this.selectedProducts.findIndex(p => p && p.id === product.id);
		}

		return false;
	},

	removeHighlights() {
		// Remove all highlight classes from the DOM
		document.querySelectorAll('.text-green-600').forEach(el => {
			el.classList.remove('text-green-600', 'font-bold');
		});
	},

	getIngredientAmount(ingredient, product) {
		if (!product || !ingredient.amounts[product.id]) return '—';
		const amount = parseFloat(ingredient.amounts[product.id]);
		if (isNaN(amount) || amount <= 0) return '—';
		return ingredient.amounts[product.id];
	},

	getIngredientClass(ingredient, product) {
		return {
			'text-green-600 font-bold': this.isMaxIngredientAmount(ingredient, product)
		};
	},

	getBrandName(result) {
		return result.brand?.[0]?.name || 'Unknown Brand';
	},

	getThumbnailUrl(result) {
		if (!result._embedded || !result._embedded['wp:featuredmedia']) {
			return '/wp-content/themes/supp-pick/assets/images/placeholder.png';
		}
		return result._embedded['wp:featuredmedia'][0].source_url;
	},

	getOverviewValue(product, field) {
		switch(field) {
			case 'Calories':
				return product?.calories || '—';
			case 'Servings per container':
				return product?.servings || '—';
			case 'Rating':
				if (!product?.amazon_rating) return '—';
				return `<div class="rating-bar" data-rating="${product.amazon_rating}" x-rating-bar>
					<div class="bar-label">${product.amazon_rating} out of 5</div>
					<div class="bar-wrapper">
						<div class="bar-bg">
							<div class="bar-fill"></div>
							<div class="bar-ticks">
								<div class="segment" data-label="1"></div>
								<div class="segment" data-label="2"></div>
								<div class="segment" data-label="3"></div>
								<div class="segment" data-label="4"></div>
								<div class="segment last" data-label="5"></div>
							</div>
						</div>
					</div>
				</div>`;
			case 'Price':
				return product?.price ? '$' + product.price : '—';
			case 'Price per serving':
				return product?.price_per_serving ? '$' + product.price_per_serving : '—';
			default:
				return '—';
		}
	},

	getCategoryValue(product, field) {
		if (!product) return '—';
		
		switch(field) {
			case 'Total Caffeine per serving':
				return product?.category === 'Pre-Workout' ? (product?.total_caffeine_content ? product.total_caffeine_content + ' mg' : '—') : '—';
			case 'Protein/Serving':
				return product?.category === 'protein' ? (product?.protein_per_serving ? product.protein_per_serving + ' g' : '—') : '—';
			default:
				return '—';
		}
	},

	getCategoryNumericValue(product, field) {
		if (!product) return 0;
		
		switch(field) {
			case 'Caffeine':
				return product?.category === 'Pre-Workout' ? parseFloat(product?.total_caffeine_content) || 0 : 0;
			case 'Protein/Serving':
				return product?.category === 'protein' ? parseFloat(product?.protein_per_serving) || 0 : 0;
			default:
				return 0;
		}
	},

	isMaxCategoryValue(product, field) {
		if (!product) return false;
		
		const currentValue = this.getCategoryNumericValue(product, field);
		if (currentValue === 0) return false;

		return this.selectedProducts.every(p => {
			if (!p) return true;
			return currentValue >= this.getCategoryNumericValue(p, field);
		});
	},

	shouldShowCategoryField(field) {
		// Check if any product has a non-empty value for this field
		return this.selectedProducts.some(product => {
			if (!product) return false;
			
			switch(field) {
				case 'Total Caffeine per serving':
					return product.category === 'Pre-Workout' && product.total_caffeine_content;
				case 'Protein/Serving':
					return product.category === 'protein' && product.protein_per_serving;
				default:
					return false;
			}
		});
	},

	getTaxonomyValue(product, field) {
		if (!product) return '—';
		
		switch(field) {
			case 'Category':
				return product?.category || '—';
			case 'Form':
				return product?.product_form || '—';
			case 'Certification':
				return product?.certification || '—';
			case 'Dietary':
				return product?.dietary_tag || '—';
			default:
				return '—';
		}
	}
	}
}
</script>


<?php
get_footer();
