<div v-if="currentView === 'checkout'" class="bg-white min-h-screen font-display">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-12">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 tracking-wider">Secure Checkout</h1>
        <form @submit.prevent="placeOrder" id="checkout-form">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div class="md:col-span-1 order-1 md:order-2">
                    <div class="bg-white rounded-lg border-2 p-6 md:sticky md:top-28">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 tracking-wider">Order Summary</h2>
                        <ul>
                            <template v-for="(item, index) in checkoutItems" :key="item.productId">
                                <li class="py-3 flex items-center gap-4" :class="{ 'border-t border-gray-200': index > 0 }">
                                    <div class="flex-shrink-0 w-16 h-16 rounded-md flex items-center justify-center bg-gray-100 border"><img :src="basePath + '/' + (getProductById(item.productId)?.image || '')" class="product-image rounded-md"></div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800">{{ getProductById(item.productId)?.name }}</p>
                                        <p class="text-sm text-gray-500">{{ 'Qty: ' + item.quantity }}</p>
                                    </div>
                                    <p class="font-semibold text-gray-800">{{ formatCheckoutPrice(getProductById(item.productId)?.pricing[item.durationIndex].price * item.quantity) }}</p>
                                </li>
                            </template>
                        </ul>
                        <div class="mt-4 pt-4 border-t">
                            <div class="flex justify-between text-gray-600 mb-2"><span>Subtotal</span><span>{{ formatCheckoutPrice(checkoutTotals.subtotal) }}</span></div>
                            <template v-if="appliedCoupon">
                                <div class="flex justify-between text-green-600 mb-2 font-semibold">
                                    <span>Discount ({{ appliedCoupon.code }} - {{ appliedCoupon.discount_percentage }}%)</span>
                                    <span>{{ '-' + formatCheckoutPrice(checkoutTotals.discount) }}</span>
                                </div>
                            </template>
                            <div class="flex justify-between text-gray-600 mb-4"><span>Shipping</span><span>Free</span></div>
                            <div class="flex justify-between text-xl font-bold text-gray-900 mb-6"><span>Total</span><span>{{ formatCheckoutPrice(checkoutTotals.total) }}</span></div>
                            <div class="mt-4">
                                <label for="coupon" class="block text-sm font-medium text-gray-700 mb-1">Coupon Code</label>
                                <div class="flex gap-2">
                                    <input type="text" v-model="couponCode" @input="appliedCoupon = null; couponMessage = ''" placeholder="ENTER CODE" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase">
                                    <button @click.prevent="applyCoupon" type="button" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-md hover:bg-gray-300">Apply</button>
                                </div>
                                <p v-show="couponMessage" class="mt-2 text-sm" :class="appliedCoupon ? 'text-green-600' : 'text-red-600'">{{ couponMessage }}</p>
                            </div>
                            <button form="checkout-form" type="submit" :disabled="checkoutItems.length === 0" class="w-full mt-6 px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-darker)] disabled:opacity-50 hidden md:block">Place Order</button>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2 space-y-8 order-2 md:order-1">
                    <div class="bg-white rounded-lg border-2 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 tracking-wider">Billing Information</h2>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="name" v-model="checkoutForm.name" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)]">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" id="phone" v-model="checkoutForm.phone" required maxlength="11" pattern="01[3-9]\d{8}" title="Please enter a valid 11-digit Bangladeshi mobile number." class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)]">
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" id="email" v-model="checkoutForm.email" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)]">
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border-2 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 tracking-wider">Payment Details</h2>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Payment Method</label>
                            <div class="flex items-center gap-6">
                                <template v-for="(method, name) in paymentMethods" :key="name">
                                    <button type="button" @click="selectPayment(method, name)" 
                                            :class="{'border-violet-500': selectedPayment && selectedPayment.name === name, 'border-gray-300': !selectedPayment || selectedPayment.name !== name}" 
                                            class="w-20 h-20 border-2 rounded-lg flex items-center justify-center transition overflow-hidden">
                                        <img :src="basePath + '/' + method.logo_url" :alt="name" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <template v-if="selectedPayment">
                            <div class="mt-4 pt-4 border-t font-display">
                                <div class="flex items-center mb-2">
                                    <p class="text-lg font-bold text-gray-800">{{ paymentDisplayLabel }}:&nbsp;</p>
                                    <span class="text-lg font-bold text-gray-800 tracking-wider">{{ selectedPayment.pay_id || selectedPayment.number || selectedPayment.account_number }}</span>
                                    <button type="button" @click="copyToClipboard(selectedPayment.pay_id || selectedPayment.number || selectedPayment.account_number)" :class="copySuccess ? 'text-violet-600' : 'text-gray-500'" class="ml-3 hover:text-violet-600 transition">
                                        <i class="far fa-copy text-base"></i>
                                    </button>
                                </div>
                                <div>
                                    <ul class="text-base text-gray-700 space-y-2 list-decimal list-inside">
                                        <li v-for="instruction in standardizedInstructions" :key="instruction"><span v-html="instruction"></span></li>
                                    </ul>
                                </div>
                            </div>
                        </template>
                        
                        <div class="mt-4">
                            <label for="transactionId" class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
                            <input type="text" id="transactionId" v-model="paymentForm.trx_id" required placeholder="Enter the Transaction ID" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)]">
                        </div>
                        
                        <div class="mt-6 space-y-3">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="save-info" name="save-info" class="h-4 w-4 rounded border-gray-300 text-[var(--primary-color)] focus:ring-[var(--primary-color)]">
                                <label for="save-info" class="text-base text-gray-700">Save this information for next time</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="agree-terms" name="agree-terms" required class="h-4 w-4 rounded border-gray-300 text-[var(--primary-color)] focus:ring-[var(--primary-color)]">
                                <label for="agree-terms" class="text-base text-gray-700">I agree to the <a :href="basePath + '/terms-and-conditions'" @click.prevent="setView('termsAndConditions')" class="font-semibold text-[var(--primary-color)] hover:underline">Terms and Conditions</a></label>
                            </div>
                        </div>

                        <button form="checkout-form" type="submit" :disabled="checkoutItems.length === 0" class="w-full mt-6 px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-darker)] disabled:opacity-50">Place Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>