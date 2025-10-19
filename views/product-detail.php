<div v-if="currentView === 'productDetail'">
    <template v-if="selectedProduct">
        <div class="bg-white min-h-screen" :key="selectedProduct.id">
            <div class="max-w-6xl mx-auto px-6 sm:px-20 lg:px-[110px] pt-6 pb-12">
                <div class="max-w-5xl mx-auto">
                    <div class="product-detail-content">
                        <div ref="imageContainer" class="product-detail-image-container rounded-lg shadow-lg overflow-hidden border">
                            <img :src="basePath + '/' + (selectedProduct.image || 'https://via.placeholder.com/400x400.png?text=No+Image')" 
                                 :alt="selectedProduct.name" 
                                 class="w-full h-full object-cover rounded-lg">
                        </div>
                        <div ref="infoContainer" class="product-detail-info-container mt-6 md:mt-0">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <h1 class="product-detail-title font-bold text-gray-800 font-display tracking-wider">{{ selectedProduct.name }}</h1>
                                <span v-show="!selectedProduct.stock_out" class="text-sm font-semibold text-green-600 whitespace-nowrap">[In Stock]</span>
                                <span v-show="selectedProduct.stock_out" class="text-sm font-semibold text-red-600 whitespace-nowrap">[Stock Out]</span>
                            </div>
                            <p class="mt-2 text-gray-600 preserve-whitespace">{{ selectedProduct.description }}</p>
                            <div class="mt-6">
                                <span class="text-3xl font-bold text-[var(--primary-color)]">{{ selectedPriceFormatted }}</span>
                            </div>
                            <div class="mt-6" v-show="selectedProduct.pricing.length > 1 || selectedProduct.pricing[0].duration !== 'Default'">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Select an option</label>
                                <div class="flex flex-wrap gap-3">
                                    <template v-for="(p, index) in selectedProduct.pricing" :key="index">
                                        <button type="button" @click="selectedDurationIndex = index" :class="{ 'border-[var(--primary-color)] text-[var(--primary-color)] font-bold duration-button-selected': selectedDurationIndex === index, 'border-gray-300 text-gray-700': selectedDurationIndex !== index }" class="relative py-2 px-4 border rounded-md text-sm flex items-center justify-center transition duration-button"><span>{{ p.duration }}</span></button>
                                    </template>
                                </div>
                            </div>
                            <div class="mt-8 flex">
                                <div class="flex w-full flex-row gap-4">
                                    <button @click="addToCart(selectedProduct.id, 1)" class="flex-1 whitespace-nowrap rounded-lg border-2 border-[var(--primary-color)] px-4 sm:px-8 py-3 text-base sm:text-lg font-semibold text-[var(--primary-color)] shadow-md transition-colors hover:bg-[var(--primary-color)] hover:text-white">Add to Cart</button>
                                    <button :disabled="selectedProduct.stock_out" @click="buyNowAndCheckout(selectedProduct.id)" class="flex-1 whitespace-nowrap rounded-lg bg-[var(--primary-color)] px-4 sm:px-8 py-3 text-base sm:text-lg font-semibold text-white shadow-md transition-colors hover:bg-[var(--primary-color-darker)] disabled:cursor-not-allowed disabled:opacity-50">Buy Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-12">
                        <div class="flex border-b justify-center">
                            <button @click="activeTab = 'description'" :class="{ 'border-[var(--primary-color)] text-[var(--primary-color)]': activeTab === 'description', 'border-transparent text-gray-500': activeTab !== 'description' }" class="py-3 px-6 font-medium border-b-2">Description</button>
                            <button @click="activeTab = 'reviews'" :class="{ 'border-[var(--primary-color)] text-[var(--primary-color)]': activeTab === 'reviews', 'border-transparent text-gray-500': activeTab !== 'reviews' }" class="py-3 px-6 font-medium border-b-2">Reviews</button>
                        </div>
                        <div class="pt-6 tab-content">
                            <div v-show="activeTab === 'description'" class="w-full max-w-4xl mx-auto">
                                <div class="text-gray-700 leading-relaxed text-justify preserve-whitespace" :class="{ 'line-clamp-4': !isDescriptionExpanded }" v-html="formattedLongDescription"></div>
                                <button @click="isDescriptionExpanded = !isDescriptionExpanded" class="text-[var(--primary-color)] font-bold mt-2" v-if="selectedProduct.long_description && selectedProduct.long_description.length > 300">
                                    <span v-show="!isDescriptionExpanded">See More</span>
                                    <span v-show="isDescriptionExpanded" style="display: none;">See Less</span>
                                </button>
                            </div>
                            <div v-show="activeTab === 'reviews'" class="w-full max-w-4xl mx-auto">
                                <div @click="reviewModalOpen = true" class="flex items-center gap-4 p-2 mb-6 cursor-pointer">
                                    <i class="fas fa-user-circle text-4xl text-gray-400"></i>
                                    <div class="flex-1 p-3 bg-gray-100 rounded-xl text-gray-500 font-medium hover:bg-gray-200 transition">Write your review...</div>
                                </div>
                                <div class="space-y-4">
                                    <template v-if="!selectedProduct.reviews || selectedProduct.reviews.length === 0">
                                        <p class="text-center text-gray-500 py-4">No reviews yet. Be the first to write one!</p>
                                    </template>
                                    <template v-for="review in selectedProduct.reviews" :key="review.id">
                                        <div class="flex items-start gap-4 py-4 border-b border-gray-200 last:border-b-0">
                                            <i class="fas fa-user-circle text-4xl text-gray-400"></i>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between"><h4 class="font-bold text-gray-800 font-display tracking-wider">{{ review.name }}</h4></div>
                                                <div class="flex items-center my-1"><template v-for="i in 5"><i class="fas fa-star" :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"></i></template></div>
                                                <p class="text-gray-600">{{ review.comment }}</p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-16 w-full flex flex-col items-center">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-8 text-center font-display tracking-wider">Related Products</h2>
                        <div id="related-products-container" class="inline-grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                            <template v-for="product in relatedProducts" :key="product.id">
                                 <div @click.prevent="setView('productDetail', { productId: product.id })" class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden transition hover:border-violet-300 flex flex-col cursor-pointer">
                                    <a :href="basePath + '/' + product.category_slug + '/' + product.slug" class="contents">
                                        <div class="product-card-image-container relative">
                                            <img :src="basePath + '/' + (product.image || 'https://via.placeholder.com/400x300.png?text=No+Image')" :alt="product.name" class="product-image">
                                            <div v-show="product.stock_out" class="absolute top-2 right-2 bg-white text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md">Stock Out</div>
                                        </div>
                                        <div class="p-4 flex flex-col flex-grow">
                                            <h3 class="font-bold text-sm mb-1 line-clamp-1 font-display tracking-wider">{{ product.name }}</h3>
                                            <p class="text-xs text-gray-500 mb-2 line-clamp-2 preserve-whitespace">{{ product.description }}</p>
                                            <p class="font-bold text-base text-[var(--primary-color)] mt-auto">{{ formatPrice(product.pricing[0].price) }}</p>
                                        </div>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <div v-show="reviewModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.away="reviewModalOpen = false" style="display: none;">
        <div @click.stop class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-xl font-bold mb-4 font-display tracking-wider">Write a Review</h3>
            <div class="mb-4">
                <label for="reviewerName" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                <input type="text" id="reviewerName" v-model="newReview.name" placeholder="Enter your name" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
                <div class="flex items-center gap-1" @mouseleave="hoverRating = 0">
                    <template v-for="(star, index) in 5" :key="index"><button @click="newReview.rating = index + 1" @mouseenter="hoverRating = index + 1" class="text-2xl cursor-pointer transition"><i class="fas fa-star" :class="{'text-yellow-400': (hoverRating || newReview.rating) > index, 'text-gray-300': (hoverRating || newReview.rating) <= index}"></i></button></template>
                </div>
            </div>
            <div class="mb-6">
                <label for="reviewText" class="block text-sm font-medium text-gray-700 mb-1">Your Review</label>
                <textarea id="reviewText" v-model="newReview.comment" placeholder="Share your thoughts..." class="w-full p-2 border border-gray-300 rounded-md" rows="4"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button @click="reviewModalOpen = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button @click="submitReview()" class="px-6 py-2 bg-[var(--primary-color)] text-white font-semibold rounded-md hover:bg-[var(--primary-color-darker)]">Submit</button>
            </div>
        </div>
    </div>
</div>