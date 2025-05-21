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

<!-- Main Comparison Interface -->
<div x-data="comparePage()" class="container mx-auto px-4 py-8">
	<h1 class="text-2xl font-bold mb-4">Compare Supplements</h1>

	<!-- Search Interface -->
	<!-- Allows users to search for supplements and displays results in a dropdown -->
	<div class="mb-6">
	<input
		x-model="searchQuery"
		@input.debounce.300ms="fetchSearchResults"
		type="text"
		class="border border-gray-300 p-2 w-full"
		placeholder="Search for supplements..."
	/>
	<ul x-show="searchResults.length" id="search-results" class="border mt-2 bg-white">
		<template x-for="(result, index) in searchResults" :key="'search-' + index">
		<li>
			<button
			@click="addToCompare(result.id)"
			class="block w-full text-left p-2 hover:bg-gray-100 flex items-center gap-2"
			>
			<img :src="getThumbnailUrl(result)" class="w-8 h-8 object-contain" />
			<span x-text="getBrandName(result) + ' - ' + result.title.rendered"></span>
			</button>
		</li>
		</template>
	</ul>
	</div>

	<!-- Comparison Slots -->
	<!-- Displays up to 3 selected supplements with their basic information -->
	<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
	<template x-for="(product, index) in selectedProducts" :key="'slot-' + index">
		<div class="border p-4 min-h-[150px]">
		<template x-if="product">
			<div>
			<h2 class="font-bold text-lg mb-2" x-text="product.brand + ' - ' + product.title"></h2>
			<img :src="product.image" class="h-32 object-contain mx-auto mb-2" />
			<a :href="product.affiliate_url" target="_blank" class="text-blue-500">Buy</a>
			<button @click="removeFromCompare(index)" class="block text-sm text-red-500 mt-2">Remove</button>
			</div>
		</template>
		<template x-if="!product">
			<div class="text-gray-400">Empty slot</div>
		</template>
		</div>
	</template>
	</div>

	<!-- Comparison Table -->
	<!-- Only shows when at least one product is selected -->
	<div x-show="selectedProducts.filter(p => p).length">
	<!-- Overview Section -->
	<div class="mb-6">
		<h3 class="font-semibold text-xl mb-2">Overview</h3>
		<template x-for="(field, fieldIndex) in ['Calories', 'Servings', 'Rating', 'Price', 'Price/Serving']" :key="'overview-field-' + fieldIndex">
			<div class="border-b py-2">
				<div class="font-bold" x-text="field"></div>
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
					<template x-for="(product, pIndex) in selectedProducts" :key="'overview-product-' + pIndex">
						<div x-show="product">
							<span x-text="getOverviewValue(product, field)"></span>
						</div>
					</template>
				</div>
			</div>
		</template>
	</div>

	<!-- Category-Specific Information -->
	<div class="mb-6">
		<h3 class="font-semibold text-xl mb-2">Category-Specific Info</h3>
		<template x-for="(field, fieldIndex) in ['Caffeine', 'Protein/Serving']" :key="'category-field-' + fieldIndex">
			<div x-show="shouldShowCategoryField(field)" class="border-b py-2">
				<div class="font-bold" x-text="field"></div>
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
					<template x-for="(product, pIndex) in selectedProducts" :key="'category-product-' + pIndex">
						<div x-show="product">
							<span 
								x-text="getCategoryValue(product, field)"
								:class="{ 'text-green-600 font-bold': isMaxCategoryValue(product, field) }"
							></span>
						</div>
					</template>
				</div>
			</div>
		</template>
	</div>

	<!-- Ingredients Comparison -->
	<div>
		<h3 class="font-semibold text-xl mb-2">Ingredients</h3>
		<template x-for="(ingredient, index) in sortedIngredients" :key="'ingredient-' + index">
		<div class="border-b py-2">
			<div class="font-bold" x-text="ingredient.name"></div>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<template x-for="(product, pIndex) in selectedProducts" :key="'ingredient-product-' + pIndex">
				<div x-show="product">
				<span
					x-text="ingredient.amounts[product?.id] || '—'"
					:class="{ 'text-green-600 font-bold': ingredient.max === parseFloat(ingredient.amounts[product?.id]) }"
				></span>
				</div>
			</template>
			</div>
		</div>
		</template>
	</div>
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
	// State variables
	searchQuery: '', // Current search input
	searchResults: [], // Results from the search API
	selectedProducts: [null, null, null], // Array of up to 3 selected products
	sortedIngredients: [], // Sorted list of ingredients for comparison

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

		fetch(`/wp-json/wp/v2/supplement/${id}?_embed`).then(res => res.json()).then(data => {
		const index = this.selectedProducts.findIndex(p => p === null);
		if (index !== -1) {
			const acf = data.acf || {};
			const category = data['supplement-category']?.[0]?.name || '';
			const brand = data.brand?.[0]?.name || '';
			const dosages = Array.isArray(acf.dosages) ? acf.dosages : [];
			const ingredients = dosages.map(d => ({
			name: d.ingredient?.post_title || 'Unknown',
			amount: parseFloat(d.amount) || 0,
			unit: d.unit || ''
			}));

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
			total_caffeine_content: acf.total_caffeine_content || '',
			protein_per_serving: acf.protein_per_serving || '',
			ingredients
			};

			this.recalculateIngredients();
		}
		});
	},

	/**
	 * Removes a product from the comparison
	 */
	removeFromCompare(index) {
		this.selectedProducts[index] = null;
		this.recalculateIngredients();
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
			ingredientsMap[key] = { name: ing.name, amounts: {}, max: 0 };
			}
			const numericAmount = parseFloat(ing.amount) || 0;
			ingredientsMap[key].amounts[p.id] = `${numericAmount} ${ing.unit}`;
			if (numericAmount > ingredientsMap[key].max) {
			ingredientsMap[key].max = numericAmount;
			}
		});
		});

		this.sortedIngredients = Object.values(ingredientsMap).sort((a, b) => {
		const aCount = Object.keys(a.amounts).length;
		const bCount = Object.keys(b.amounts).length;
		return bCount - aCount || a.name.localeCompare(b.name);
		});
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
			case 'Servings':
				return product?.servings || '—';
			case 'Rating':
				return product?.amazon_rating || '—';
			case 'Price':
				return product?.price ? '$' + product.price : '—';
			case 'Price/Serving':
				return product?.price_per_serving ? '$' + product.price_per_serving : '—';
			default:
				return '—';
		}
	},

	getCategoryValue(product, field) {
		if (!product) return '—';
		
		switch(field) {
			case 'Caffeine':
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
				case 'Caffeine':
					return product.category === 'Pre-Workout' && product.total_caffeine_content;
				case 'Protein/Serving':
					return product.category === 'protein' && product.protein_per_serving;
				default:
					return false;
			}
		});
	}
	}
}
</script>

<style>
/* Basic styling for the comparison interface */

input[type="text"] {
	border-radius: 4px;
	font-size: 1rem;
}
.border, .border {
	border: 1px solid #ddd;
	border-radius: 6px;
}
button {
	cursor: pointer;
}

ul#search-results{
	margin:0;
	list-style: none;

	button{
	background: none;
	}
}
</style>

<?php
get_footer();
