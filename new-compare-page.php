<?php
/* Template Name: New Compare Page */
get_header();
?>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<!-- Comparison Page Wrapper -->
<div x-data="comparePage()" class="container mx-auto px-4 py-8">
	<h1 class="text-2xl font-bold mb-4">Compare Supplements</h1>

	<!-- Search Bar -->
	<div class="mb-6">
	<input
		x-model="searchQuery"
		@input.debounce.300ms="fetchSearchResults"
		type="text"
		class="border border-gray-300 p-2 w-full"
		placeholder="Search for supplements..."
	/>
	<ul x-show="searchResults.length" class="border mt-2 bg-white">
		<template x-for="(result, index) in searchResults" :key="'search-' + index">
		<li>
			<button
			@click="addToCompare(result.id)"
			class="block w-full text-left p-2 hover:bg-gray-100"
			>
			<span x-text="result.title.rendered"></span>
			</button>
		</li>
		</template>
	</ul>
	</div>

	<!-- Comparison Slots -->
	<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
	<template x-for="(product, index) in selectedProducts" :key="'slot-' + index">
		<div class="border p-4 min-h-[150px]">
		<template x-if="product">
			<div>
			<h2 class="font-bold text-lg mb-2" x-text="product.title"></h2>
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
	<div x-show="selectedProducts.filter(p => p).length">
	<!-- Overview Section -->
	<div class="mb-6">
		<h3 class="font-semibold text-xl mb-2">Overview</h3>
		<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
		<div class="font-bold">Field</div>
		<template x-for="(product, index) in selectedProducts" :key="'overview-' + index">
			<div x-show="product">
			<div><strong>Calories:</strong> <span x-text="product?.calories || '—'"></span></div>
			<div><strong>Servings:</strong> <span x-text="product?.servings || '—'"></span></div>
			<div><strong>Rating:</strong> <span x-text="product?.amazon_rating || '—'"></span></div>
			<div><strong>Price:</strong> $<span x-text="product?.price || '—'"></span></div>
			<div><strong>Price/Serving:</strong> $<span x-text="product?.price_per_serving || '—'"></span></div>
			</div>
		</template>
		</div>
	</div>

	<!-- Conditional Fields -->
	<div class="mb-6">
		<h3 class="font-semibold text-xl mb-2">Category-Specific Info</h3>
		<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
		<div class="font-bold">Field</div>
		<template x-for="(product, index) in selectedProducts" :key="'category-' + index">
			<div x-show="product">
			<template x-if="product?.category === 8">
				<div><strong>Caffeine:</strong> <span x-text="product?.total_caffeine_content + ' mg'"></span></div>
			</template>
			<template x-if="product?.category === 'protein'">
				<div><strong>Protein/Serving:</strong> <span x-text="product?.protein_per_serving + ' g'"></span></div>
			</template>
			</div>
		</template>
		</div>
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

<script>
function comparePage() {
	return {
	searchQuery: '',
	searchResults: [],
	selectedProducts: [null, null, null],
	sortedIngredients: [],

	fetchSearchResults() {
		if (!this.searchQuery) return;
		fetch(`/wp-json/wp/v2/supplement?search=${this.searchQuery}`)
		.then(res => res.json())
		.then(data => {
			this.searchResults = data;
		});
	},

	addToCompare(id) {
		if (this.selectedProducts.filter(Boolean).length >= 3) return;

		fetch(`/wp-json/wp/v2/supplement/${id}?_embed`).then(res => res.json()).then(data => {
		const index = this.selectedProducts.findIndex(p => p === null);
		if (index !== -1) {
			const acf = data.acf || {};
			const category = data['supplement-category']?.[0] || '';
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
			total_caffeine_content: acf.total_caffeine_content || '',
			protein_per_serving: acf.protein_per_serving || '',
			ingredients
			};

			this.recalculateIngredients();
		}
		});
	},

	removeFromCompare(index) {
		this.selectedProducts[index] = null;
		this.recalculateIngredients();
	},

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
	}
	}
}
</script>

<style>
.container {
	max-width: 1200px;
}
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
</style>



<?php
get_footer();
