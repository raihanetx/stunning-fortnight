<section class="hero-section aspect-[2/1] md:aspect-[5/2] rounded-lg overflow-hidden">
    <div class="relative w-full h-full">
        <template v-for="(slide, index) in heroSlides.slides" :key="index">
            <div class="hero-slide" :class="{ 'active': heroSlides.activeSlide === index }">
                <img v-if="heroSlides.hasImages" :src="slide.url" alt="Promotional Banner" class="w-full h-full object-cover">
                <div v-if="!heroSlides.hasImages" class="absolute inset-0 flex items-center justify-center h-full w-full" :class="slide.bgColor">
                    <span class="text-2xl md:text-4xl font-bold text-white/80 tracking-wider">{{ slide.text }}</span>
                </div>
            </div>
        </template>
    </div>
    
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2 z-10">
        <template v-for="(slide, index) in heroSlides.slides" :key="index">
            <button @click="heroSlides.activeSlide = index" :class="{'bg-white': heroSlides.activeSlide === index, 'bg-white/50': heroSlides.activeSlide !== index}" class="w-2.5 h-2.5 rounded-full transition"></button>
        </template>
    </div>
</section>

<section class="relative">
    <div class="text-center mt-6 mb-6 md:mt-8 md:mb-8">
        <h2 class="text-2xl font-bold text-gray-800 font-display tracking-wider">Product Categories</h2>
    </div>
     <div class="max-w-7xl mx-auto">
        <div class="relative flex items-center justify-center gap-2 md:px-0">
            <button @click="scrollCategories(-1)" class="hidden md:flex p-2 flex-shrink-0 z-10 items-center justify-center">
                <i class="fas fa-chevron-left text-2xl text-gray-500 hover:text-[var(--primary-color)] transition-colors"></i>
            </button>
            <div ref="categoryScrollerWrapper" class="overflow-hidden">
                <div class="horizontal-scroll smooth-scroll" ref="categoryScroller">
                    <div class="category-scroll-container">
                        <?php foreach ($all_categories as $category): ?>
                            <a :href="basePath + '/products/category/<?= htmlspecialchars($category['slug']) ?>'" @click.prevent="setView('products', { filterType: 'category', filterValue: '<?= htmlspecialchars($category['name']) ?>' })" class="category-icon">
                                <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                                <span><?= htmlspecialchars($category['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button @click="scrollCategories(1)" class="hidden md:flex p-2 flex-shrink-0 z-10 items-center justify-center">
                <i class="fas fa-chevron-right text-2xl text-gray-500 hover:text-[var(--primary-color)] transition-colors"></i>
            </button>
        </div>
    </div>
</section>

<?php if (!empty($all_hotdeals_data)): ?>
<section class="py-6 md:py-8">
    <div class="text-center mb-6 md:mb-8">
        <h2 class="text-2xl font-bold font-display tracking-wider">Hot Deals</h2>
    </div>
    <div class="hot-deals-container">
        <div class="hot-deals-scroller" style="animation-duration: <?= htmlspecialchars($hot_deals_speed) ?>s;">
            <?php
                $product_map_by_id = array_column($all_products_flat, null, 'id');
                $hot_deals_to_render = [];
                foreach ($all_hotdeals_data as $deal) {
                    if (isset($product_map_by_id[$deal['productId']])) {
                        $product = $product_map_by_id[$deal['productId']];
                        $hot_deals_to_render[] = [
                            'href' => BASE_PATH . '/' . ($product['category_slug'] ?? '') . '/' . ($product['slug'] ?? ''),
                            'click_event' => "setView('productDetail', { productId: " . json_encode($product['id']) . " })",
                            'image' => BASE_PATH . '/' . ($product['image'] ?: 'https://via.placeholder.com/120x120.png?text=No+Image'),
                            'name' => !empty($deal['customTitle']) ? $deal['customTitle'] : $product['name']
                        ];
                    }
                }
            ?>
            <?php if(!empty($hot_deals_to_render)): 
                $duplicated_deals = array_merge($hot_deals_to_render, $hot_deals_to_render);
                foreach ($duplicated_deals as $item): 
            ?>
                <a href="<?= htmlspecialchars($item['href']) ?>" @click.prevent="<?= htmlspecialchars($item['click_event']) ?>" class="hot-deal-card">
                    <div class="hot-deal-image-container">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="hot-deal-image">
                    </div>
                    <span class="hot-deal-title"><?= htmlspecialchars($item['name']) ?></span>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php foreach ($products_by_category as $category_name => $products): ?>
<section class="py-6">
    <div class="flex justify-between items-center mb-4 px-4 md:px-6">
        <h2 class="text-2xl font-bold font-display tracking-wider"><?= htmlspecialchars($category_name) ?></h2>
        <a :href="basePath + '/products/category/<?= htmlspecialchars($products[0]['category_slug'] ?? '') ?>'" @click.prevent="setView('products', { filterType: 'category', filterValue: '<?= htmlspecialchars($category_name) ?>' })" class="text-[var(--primary-color)] font-bold hover:text-[var(--primary-color-darker)] flex items-center text-lg">View all <span class="ml-2 text-2xl font-bold">&raquo;</span></a>
    </div>
    <div class="horizontal-scroll smooth-scroll">
        <div class="product-scroll-container">
            <?php foreach ($products as $product): ?>
                <div class="product-card" @click.prevent="setView('productDetail', { productId: <?= htmlspecialchars(json_encode($product['id'])) ?> })">
                    <a :href="basePath + '/<?= htmlspecialchars($product['category_slug'] ?? '') ?>/<?= htmlspecialchars($product['slug'] ?? '') ?>'" class="contents">
                        <div class="product-card-image-container relative">
                            <img src="<?= BASE_PATH ?>/<?= htmlspecialchars($product['image'] ?: 'https://via.placeholder.com/400x300.png?text=No+Image') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                            <?php if (!empty($product['stock_out'])): ?>
                                <div class="absolute top-2 right-2 bg-white text-red-600 text-xs font-bold px-3 py-1 rounded-full shadow-md">Stock Out</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="font-bold text-sm md:text-base mb-1 line-clamp-1 font-display tracking-wider"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-gray-600 text-xs md:text-sm mb-2 line-clamp-2 preserve-whitespace"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="text-[var(--primary-color)] font-bold text-lg mb-2 mt-auto">{{ formatPrice(<?= $product['pricing'][0]['price'] ?>) }}</div>
                            <button class="w-full text-[var(--primary-color)] bg-transparent hover:bg-violet-50 font-semibold py-1 px-2 rounded-lg transition md:py-1 md:text-base text-sm flex items-center justify-center gap-2 border-2 border-[var(--primary-color)]">
                                View Details <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                            </button>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>

<section class="why-choose-us px-4 py-6">
    <h2 class="text-3xl font-bold text-center mb-12 font-display tracking-wider">Why Choose Us</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 max-w-7xl mx-auto">
        <div class="feature-card bg-white p-4 rounded-xl text-center flex flex-col justify-center md:aspect-square border border-gray-200 shadow-sm">
            <div class="icon bg-[var(--primary-color-light)] w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2"><i class="fas fa-dollar-sign text-2xl text-[var(--primary-color)]"></i></div>
            <h3 class="text-lg font-bold mb-1 font-display tracking-wider">Affordable Price</h3>
            <p class="text-sm text-gray-600 mt-2 md:text-center text-left">Get top-tier content without breaking the bank. Quality education for everyone.</p>
        </div>
        <div class="feature-card bg-white p-4 rounded-xl text-center flex flex-col justify-center md:aspect-square border border-gray-200 shadow-sm">
            <div class="icon bg-[var(--primary-color-light)] w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2"><i class="fas fa-award text-2xl text-[var(--primary-color)]"></i></div>
            <h3 class="text-lg font-bold mb-1 font-display tracking-wider">Premium Quality</h3>
            <p class="text-sm text-gray-600 mt-2 md:text-center text-left">Expert-curated content to ensure the best learning experience and outcomes.</p>
        </div>
        <div class="feature-card bg-white p-4 rounded-xl text-center flex flex-col justify-center md:aspect-square border border-gray-200 shadow-sm">
            <div class="icon bg-[var(--primary-color-light)] w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2"><i class="fas fa-shield-alt text-2xl text-[var(--primary-color)]"></i></div>
            <h3 class="text-lg font-bold mb-1 font-display tracking-wider">Trusted</h3>
            <p class="text-sm text-gray-600 mt-2 md:text-center text-left">Join thousands of satisfied learners on our platform, building skills and careers.</p>
        </div>
        <div class="feature-card bg-white p-4 rounded-xl text-center flex flex-col justify-center md:aspect-square border border-gray-200 shadow-sm">
            <div class="icon bg-[var(--primary-color-light)] w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2"><i class="fas fa-lock text-2xl text-[var(--primary-color)]"></i></div>
            <h3 class="text-lg font-bold mb-1 font-display tracking-wider">Secure Payment</h3>
            <p class="text-sm text-gray-600 mt-2 md:text-center text-left">Your transactions are protected with encrypted payment gateways for peace of mind.</p>
        </div>
    </div>
</section>