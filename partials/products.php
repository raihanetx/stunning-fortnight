<div v-if="currentView === 'products'" class="bg-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-12">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 font-display tracking-wider">{{ productsTitle }}</h1>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <template v-for="product in filteredProducts" :key="product.id">
                <div class="product-grid-card" @click.prevent="setView('productDetail', { productId: product.id })">
                    <a :href="basePath + '/' + product.category_slug + '/' + product.slug" class="contents">
                        <div class="product-card-image-container relative">
                            <img :src="basePath + '/' + (product.image || 'https://via.placeholder.com/400x300.png?text=No+Image')" :alt="product.name" class="product-image">
                            <div v-show="product.stock_out" class="absolute top-2 right-2 bg-white text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md">Stock Out</div>
                        </div>
                        <div class="p-3 sm:p-4 flex flex-col flex-grow">
                            <h3 class="text-sm md:text-base font-bold text-gray-800 mb-1 line-clamp-1 font-display tracking-wider">{{ product.name }}</h3>
                            <p class="text-xs md:text-sm text-gray-600 mb-2 line-clamp-2 preserve-whitespace">{{ product.description }}</p>
                            <p class="text-lg md:text-xl font-extrabold text-[var(--primary-color)] mt-auto">{{ formatPrice(product.pricing[0].price) }}</p>
                            <div class="flex flex-row gap-2 mt-2">
                                <button @click.stop.prevent="addToCart(product.id, 1)" class="w-full border-2 border-[var(--primary-color)] text-[var(--primary-color)] bg-transparent hover:bg-[var(--primary-color)] hover:text-white transition py-1.5 px-2 sm:py-2 rounded-md text-xs sm:text-sm font-semibold">Add to Cart</button>
                                <button class="w-full bg-gray-200 text-gray-700 py-1.5 px-2 sm:py-2 rounded-md hover:bg-gray-300 transition text-xs sm:text-sm font-semibold">View Details</button>
                            </div>
                        </div>
                    </a>
                </div>
            </template>
        </div>
    </div>
</div>