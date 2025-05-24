<?php
/**
 * File: compare-shortcode.php
 *
 * Shortcode to display supplement comparison.
 *
 * Usage: [compare_supplements ids="123,456,789"]
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function compare_supplements_shortcode( $atts ) {
	// Parse attributes.
	$atts = shortcode_atts(
		array(
			'ids' => '', // Comma-separated list of supplement IDs.
		),
		$atts
	);

	// Convert comma-separated IDs to array.
	$ids = array_filter( array_map( 'trim', explode( ',', $atts['ids'] ) ) );

	// If less than 2 IDs provided, return empty.
	if ( count( $ids ) < 2 ) {
		return '';
	}

	// Start output buffering.
	ob_start();
	?>
	<!-- Required JavaScript Libraries -->
	<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

	<!-- Main Comparison Interface -->
	<div x-data="comparePage()" class="compare-shotrcode-container" style="--num-products: <?php echo count( $ids ); ?>">
		<!-- Comparison Slots -->
		<div class="header-grid">
			<template x-for="(product, index) in selectedProducts" :key="'slot-' + index">
				<div class="supplement-header">
					<div class="slot">
						<img :src="product.image" />
						<div class="title-wrapper">
							<p class="brand" x-text="product.brand"></p>
							<h2 class="title" x-text="product.title"></h2>
						</div>
						<a :href="product.affiliate_url" target="_blank" class="buy-btn btn btn-primary">
							<i class="bi bi-amazon"></i>View on Amazon
						</a>
					</div>
				</div>
			</template>
		</div>

		<!-- Comparison Table -->
		<div class="tables-wrapper">
			<!-- Overview Section -->
			<div class="section overview">
				<h3 class="section-title"><i class="bi bi-check-circle"></i> Overview</h3>
				<template x-for="(field, fieldIndex) in ['Servings per container','Price', 'Price per serving', 'Rating','Calories']" :key="'overview-field-' + fieldIndex">
					<div class="row">
						<div class="row-title" x-text="field"></div>
						<div class="grid">
							<template x-for="(product, pIndex) in selectedProducts" :key="'overview-product-' + pIndex">
								<div class="column">
									<span x-text="getOverviewValue(product, field)"></span>
								</div>
							</template>
						</div>
					</div>
				</template>

				<!-- Taxonomy Rows -->
				<template x-for="(field, fieldIndex) in ['Category', 'Form', 'Certification', 'Dietary']" :key="'taxonomy-field-' + fieldIndex">
					<div class="row">
						<div class="row-title" x-text="field"></div>
						<div class="grid">
							<template x-for="(product, pIndex) in selectedProducts" :key="'taxonomy-product-' + pIndex">
								<div class="column">
									<span x-text="getTaxonomyValue(product, field)"></span>
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
						<div class="grid">
							<template x-for="(product, pIndex) in selectedProducts" :key="'category-product-' + pIndex">
								<div class="column">
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
				<div :key="'ingredients-' + isPriceNormalized + '-' + selectedProducts.map(p => p.id).join('-')">
					<template x-for="(ingredient, index) in sortedIngredients" :key="'ingredient-' + index">
						<div class="row">
							<div class="row-title">
								<span class="tooltip-wrapper">
									<a :href="ingredient.permalink" class="text-primary hover:underline" x-text="ingredient.name"></a>
									<span class="tooltip-text" x-text="ingredient.excerpt || 'No description available'"></span>
								</span>
							</div>
							<div class="grid">
								<template x-for="(product, pIndex) in selectedProducts" :key="'ingredient-product-' + pIndex">
									<div class="column">
										<template x-if="getIngredientAmount(ingredient, product) !== '—'">
											<span
												x-text="getIngredientAmount(ingredient, product)"
												:class="{ 'text-green-600 font-bold': shouldHighlightAmount(ingredient, product) }"
											></span>
										</template>
										<template x-if="getIngredientAmount(ingredient, product) === '—'">
											<span>—</span>
										</template>
									</div>
								</template>
							</div>
						</div>
					</template>
				</div>
			</div>
		</div>
	</div>

	<script>
	function comparePage() {
		return {
			selectedProducts: [],
			sortedIngredients: [],
			isPriceNormalized: false,

			init() {
				// Load products from shortcode attributes.
				const productIds = <?php echo json_encode( $ids ); ?>;
				
				// Create an array of promises for loading each product.
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
							
							// Create an array of promises for fetching ingredient details.
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

							// Wait for all ingredient details to be fetched.
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

				// Load all products in parallel and maintain order.
				Promise.all(loadPromises).then(products => {
					this.selectedProducts = products;
					this.recalculateIngredients();
				});
			},

			recalculateIngredients() {
				const ingredientsMap = {};

				this.selectedProducts.forEach(p => {
					(p.ingredients || []).forEach(ing => {
						const key = ing.name.toLowerCase();
						if (!ingredientsMap[key]) {
							ingredientsMap[key] = { 
								name: ing.name, 
								permalink: ing.permalink,
								excerpt: ing.excerpt,
								amounts: {}, 
								originalAmounts: {} // Store original amounts.
							};
						}
						const numericAmount = parseFloat(ing.amount) || 0;
						ingredientsMap[key].amounts[p.id] = `${numericAmount} ${ing.unit}`;
						ingredientsMap[key].originalAmounts[p.id] = `${numericAmount} ${ing.unit}`; // Store original.
					});
				});

				// Sort ingredients by frequency and name.
				this.sortedIngredients = Object.values(ingredientsMap).sort((a, b) => {
					const aCount = Object.keys(a.amounts).length;
					const bCount = Object.keys(b.amounts).length;
					return bCount - aCount || a.name.localeCompare(b.name);
				});

				// If price normalized is active, recalculate amounts.
				if (this.isPriceNormalized) {
					this.togglePriceNormalized();
				}
			},

			togglePriceNormalized() {
				this.isPriceNormalized = !this.isPriceNormalized;
				
				if (this.isPriceNormalized) {
					// Find the product with highest price per serving.
					const products = this.selectedProducts;
					
					// Group products by price per serving.
					const priceGroups = products.reduce((groups, product) => {
						const price = parseFloat(product.price_per_serving) || 0;
						if (!groups[price]) {
							groups[price] = [];
						}
						groups[price].push(product);
						return groups;
					}, {});

					// Get the highest price.
					const highestPrice = Math.max(...Object.keys(priceGroups).map(Number));

					// Recalculate amounts based on price ratio.
					this.sortedIngredients.forEach(ingredient => {
						products.forEach(product => {
							// Skip products that already have the highest price.
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
					// Restore original amounts.
					this.sortedIngredients.forEach(ingredient => {
						ingredient.amounts = { ...ingredient.originalAmounts };
					});
				}
			},

			shouldHighlightAmount(ingredient, product) {
				if (!product || !ingredient.amounts[product.id]) return false;
				
				const currentAmount = parseFloat(ingredient.amounts[product.id]);
				if (isNaN(currentAmount) || currentAmount <= 0) return false;

				// Get all valid amounts.
				const validAmounts = this.selectedProducts
					.filter(p => p && ingredient.amounts[p.id])
					.map(p => parseFloat(ingredient.amounts[p.id]))
					.filter(amount => !isNaN(amount) && amount > 0);

				// If no valid amounts, return false.
				if (validAmounts.length === 0) return false;

				// If there's only one valid amount, highlight it.
				if (validAmounts.length === 1) {
					return currentAmount === validAmounts[0];
				}

				// Find the maximum amount.
				const maxAmount = Math.max(...validAmounts);

				// If this is the maximum amount, check if it's the first occurrence.
				if (currentAmount === maxAmount) {
					const firstMaxIndex = this.selectedProducts.findIndex(p => 
						p && parseFloat(ingredient.amounts[p.id]) === maxAmount
					);
					return firstMaxIndex === this.selectedProducts.findIndex(p => p && p.id === product.id);
				}

				return false;
			},

			getIngredientAmount(ingredient, product) {
				if (!product || !ingredient.amounts[product.id]) return '—';
				const amount = parseFloat(ingredient.amounts[product.id]);
				if (isNaN(amount) || amount <= 0) return '—';
				return ingredient.amounts[product.id];
			},

			getOverviewValue(product, field) {
				switch(field) {
					case 'Calories':
						return product?.calories || '—';
					case 'Servings per container':
						return product?.servings || '—';
					case 'Rating':
						return product?.amazon_rating + ' out of 5' || '—';
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
				// Check if any product has a non-empty value for this field.
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
	return ob_get_clean();
}
add_shortcode( 'compare_supplements', 'compare_supplements_shortcode' );
