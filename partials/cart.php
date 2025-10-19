<div v-if="currentView === 'cart'" class="bg-white min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-12">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 font-display tracking-wider">Shopping Cart</h1>
        <template v-if="cart.length === 0">
            <div class="py-16 text-center">
                <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-semibold text-gray-700 mb-2 font-display tracking-wider">Your cart is empty</h3>
                <p class="text-gray-500 mb-6">Looks like you haven't added anything to your cart yet.</p>
                <a :href="basePath + '/products'" @click.prevent="setView('products')" class="inline-block px-8 py-3 bg-[var(--primary-color)] text-white font-semibold rounded-lg shadow-md hover:bg-[var(--primary-color-darker)] transition">Browse Products</a>
            </div>
        </template>
        <template v-if="cart.length > 0">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <div class="lg:col-span-2 bg-white rounded-lg border-2 p-4">
                    <ul class="">
                        <template v-for="(cartItem, index) in cart" :key="cartItem.productId">
                            <li class="py-6 flex items-start gap-4" :class="{ 'border-t border-gray-200': index > 0 }">
                                <div class="flex-shrink-0 w-24 h-24 rounded-md flex items-center justify-center bg-gray-100 border"><img :src="basePath + '/' + (getProductById(cartItem.productId)?.image || '')" class="product-image rounded-md"></div>
                                <div class="flex-1 flex flex-col">
                                    <div>
                                        <div class="flex justify-between">
                                            <h3 class="text-lg font-semibold text-gray-800 font-display tracking-wider">{{ getProductById(cartItem.productId)?.name || 'Unknown Product' }}</h3>
                                            <p class="text-lg font-bold text-[var(--primary-color)]">{{ formatPrice(getProductById(cartItem.productId)?.pricing[cartItem.durationIndex].price * cartItem.quantity) }}</p>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">{{ 'Duration: ' + getProductById(cartItem.productId)?.pricing[cartItem.durationIndex].duration }}</p>
                                        <p v-show="getProductById(cartItem.productId)?.stock_out" class="text-red-600 text-xs mt-2 font-semibold">This item is out of stock and will be excluded from checkout.</p>
                                    </div>
                                    <div class="mt-4 flex items-center justify-between">
                                        <div class="flex items-center border rounded-md">
                                            <button @click="updateCartQuantity(cartItem.productId, -1)" :disabled="cartItem.quantity <= 1" class="px-3 py-1 text-gray-600 disabled:opacity-50"><i class="fas fa-minus text-xs"></i></button>
                                            <span class="px-4 py-1 border-l border-r">{{ cartItem.quantity }}</span>
                                            <button @click="updateCartQuantity(cartItem.productId, 1)" :disabled="getProductById(cartItem.productId)?.stock_out" class="px-3 py-1 text-gray-600 disabled:opacity-50"><i class="fas fa-plus text-xs"></i></button>
                                        </div>
                                        <button @click="removeFromCart(cartItem.productId)" class="font-medium text-red-500 hover:text-red-700 text-sm flex items-center gap-1"><i class="fas fa-trash-alt"></i> Remove</button>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg border-2 p-6 sticky top-28">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 font-display tracking-wider">Order Summary</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ formatPrice(cartTotal) }}</span></div>
                            <div class="flex justify-between text-gray-600"><span>Shipping</span><span>Free</span></div>
                            <div class="pt-4 border-t flex justify-between text-xl font-bold text-gray-900"><span>Total</span><span>{{ formatPrice(cartTotal) }}</span></div>
                        </div>
                        <button @click="proceedToCheckout()" :disabled="!isCartCheckoutable" class="w-full mt-6 px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-darker)] transition disabled:opacity-50 disabled:cursor-not-allowed">Proceed to Checkout</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>