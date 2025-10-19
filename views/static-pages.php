<div v-if="currentView === 'aboutUs'" class="bg-white min-h-screen">
    <div class="container mx-auto max-w-4xl p-6 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center border-b pb-4 mb-8">About Us</h1>
        <div class="preserve-whitespace leading-relaxed text-gray-700" v-html="formattedAboutUs"></div>
    </div>
</div>

<div v-if="currentView === 'privacyPolicy'" class="bg-white min-h-screen">
    <div class="container mx-auto max-w-4xl p-6 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center border-b pb-4 mb-8">Privacy Policy</h1>
        <div class="preserve-whitespace leading-relaxed text-gray-700" v-html="formattedPrivacyPolicy"></div>
    </div>
</div>

<div v-if="currentView === 'termsAndConditions'" class="bg-white min-h-screen">
     <div class="container mx-auto max-w-4xl p-6 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center border-b pb-4 mb-8">Terms and Conditions</h1>
        <div class="preserve-whitespace leading-relaxed text-gray-700" v-html="formattedTerms"></div>
    </div>
</div>

<div v-if="currentView === 'refundPolicy'" class="bg-white min-h-screen">
    <div class="container mx-auto max-w-4xl p-6 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center border-b pb-4 mb-8">Refund Policy</h1>
        <div class="preserve-whitespace leading-relaxed text-gray-700" v-html="formattedRefund"></div>
    </div>
</div>
