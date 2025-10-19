<?php

// index.php

// --- Helper Function to generate slugs (in case it's missing in JSON) ---
function slugify($text) {
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . rand(100, 999);
    }
    return $text;
}

// --- Base Path (for subdirectory support) ---
define('BASE_PATH', rtrim(str_replace('index.php', '', $_SERVER['SCRIPT_NAME']), '/'));

// --- Load All Product and Category Data ---
$products_file_path = 'products.json';
if (!file_exists($products_file_path)) {
    file_put_contents($products_file_path, '[]');
}
$all_data = json_decode(file_get_contents($products_file_path), true);

// --- Load Coupon Data ---
$coupons_file_path = 'coupons.json';
if (!file_exists($coupons_file_path)) {
    file_put_contents($coupons_file_path, '[]');
}
$all_coupons_data = json_decode(file_get_contents($coupons_file_path), true);

// --- Load Site Config Data ---
$config_file_path = 'config.json';
if (!file_exists($config_file_path)) {
    file_put_contents($config_file_path, '{"hero_banner":[],"favicon":"","contact_info":{"phone":"","whatsapp":"","email":""},"admin_password":"password123", "usd_to_bdt_rate": 110, "site_logo":"", "hero_slider_interval": 5000, "hot_deals_speed": 40, "payment_methods":{}, "smtp_settings": {}}');
}
$site_config = json_decode(file_get_contents($config_file_path), true);

$hero_banner_paths_raw = $site_config['hero_banner'] ?? [];
$hero_banner_paths = array_map(function($path) {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}, $hero_banner_paths_raw);

$favicon_path = $site_config['favicon'] ?? '';
$contact_info = $site_config['contact_info'] ?? ['phone' => '', 'whatsapp' => '', 'email' => ''];
$usd_to_bdt_rate = $site_config['usd_to_bdt_rate'] ?? 110;
$site_logo_path = $site_config['site_logo'] ?? '';
$hero_slider_interval = $site_config['hero_slider_interval'] ?? 5000;
$hot_deals_speed = $site_config['hot_deals_speed'] ?? 40;
$payment_methods = $site_config['payment_methods'] ?? [];


// --- Load Hot Deals Data ---
$hotdeals_file_path = 'hotdeals.json';
if (!file_exists($hotdeals_file_path)) {
    file_put_contents($hotdeals_file_path, '[]');
}
$all_hotdeals_data = json_decode(file_get_contents($hotdeals_file_path), true);


// --- Prepare Data ---
$all_categories = [];
$all_products_flat = [];
$products_by_category = [];
$product_slug_map = [];
$category_slug_map = [];
$static_pages = ['cart', 'checkout', 'order-history', 'products', 'about-us', 'privacy-policy', 'terms-and-conditions', 'refund-policy'];


if (is_array($all_data)) {
    foreach ($all_data as $category) {
        $category_slug = $category['slug'] ?? slugify($category['name']);
        $category_slug_map[$category_slug] = $category['name'];

        $all_categories[] = [
            'name' => $category['name'],
            'slug' => $category_slug,
            'icon' => $category['icon']
        ];

        if (isset($category['products']) && is_array($category['products'])) {
            $category_products_temp = [];
            foreach ($category['products'] as $product) {
                $product_slug = $product['slug'] ?? slugify($product['name']);
                $product['category'] = $category['name'];
                $product['category_slug'] = $category_slug;
                $product['slug'] = $product_slug;
                $all_products_flat[] = $product;
                $category_products_temp[] = $product;
                
                $product_slug_map[$category_slug . '/' . $product_slug] = $product['id'];
            }
            
            if (!empty($category_products_temp)) {
                $products_by_category[$category['name']] = $category_products_temp;
            }
        }
    }
}


// --- URL ROUTING LOGIC (PHP) ---
$request_path = trim($_GET['path'] ?? '', '/');
$path_parts = explode('/', $request_path);

$initial_view = 'home'; 
$initial_params = new stdClass();

if ($request_path) {
    $view_map = [
        'order-history' => 'orderHistory', 'about-us' => 'aboutUs',
        'privacy-policy' => 'privacyPolicy', 'terms-and-conditions' => 'termsAndConditions',
        'refund-policy' => 'refundPolicy'
    ];
    $view_key = $path_parts[0];
    
    if (isset($product_slug_map[$request_path])) {
        $initial_view = 'productDetail';
        $initial_params->productId = $product_slug_map[$request_path];
    } elseif ($path_parts[0] === 'products' && isset($path_parts[1], $path_parts[2]) && $path_parts[1] === 'category' && isset($category_slug_map[$path_parts[2]])) {
        $initial_view = 'products';
        $initial_params->filterType = 'category';
        $initial_params->filterValue = $category_slug_map[$path_parts[2]];
    } elseif (in_array($view_key, $static_pages) && !isset($path_parts[1])) {
        $initial_view = $view_map[$view_key] ?? $view_key;
    }
}

// --- Start HTML Document ---
include 'partials/header.php';
?>

<body class="bg-gray-50 flex flex-col min-h-screen">
    
    <div id="app" v-cloak>
        <!-- Custom Modal Popup -->
        <div v-show="modal.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeModal" style="display: none;">
            <div @click.stop class="bg-white rounded-lg shadow-xl w-full max-w-sm text-center p-6" v-if="modal.visible">
                <div class="mb-4">
                    <i class="fas text-5xl" :class="{ 'fa-check-circle text-green-500': modal.type === 'success', 'fa-exclamation-circle text-red-500': modal.type === 'error', 'fa-info-circle text-blue-500': modal.type === 'info' }"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">{{ modal.title }}</h3>
                <p class="text-gray-600 mb-6">{{ modal.message }}</p>
                <button @click="closeModal" class="w-full bg-[var(--primary-color)] text-white font-semibold py-2 px-4 rounded-lg hover:bg-[var(--primary-color-darker)] transition">
                    OK
                </button>
            </div>
        </div>

        <!-- Side Menu -->
        <div v-show="isSideMenuOpen" class="fixed inset-0 z-50 flex" style="display: none;">
            <div @click="isSideMenuOpen = false" v-show="isSideMenuOpen" class="fixed inset-0 bg-black bg-opacity-50"></div>
            <div v-show="isSideMenuOpen" class="relative w-72 max-w-xs bg-white h-full shadow-xl p-6">
                <button @click="isSideMenuOpen = false" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800 text-2xl"><i class="fas fa-times"></i></button>
                <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-8 font-display tracking-wider">Menu</h2>
                <nav class="flex flex-col space-y-4">
                    <a :href="basePath + '/'" @click.prevent="setView('home'); isSideMenuOpen = false;" class="text-lg text-gray-700 hover:text-[var(--primary-color)]">Home</a>
                    <a :href="basePath + '/about-us'" @click.prevent="setView('aboutUs'); isSideMenuOpen = false;" class="text-lg text-gray-700 hover:text-[var(--primary-color)]">About Us</a>
                    <a :href="basePath + '/privacy-policy'" @click.prevent="setView('privacyPolicy'); isSideMenuOpen = false;" class="text-lg text-gray-700 hover:text-[var(--primary-color)]">Privacy Policy</a>
                    <a :href="basePath + '/terms-and-conditions'" @click.prevent="setView('termsAndConditions'); isSideMenuOpen = false;" class="text-lg text-gray-700 hover:text-[var(--primary-color)]">Terms & Conditions</a>
                    <a :href="basePath + '/refund-policy'" @click.prevent="setView('refundPolicy'); isSideMenuOpen = false;" class="text-lg text-gray-700 hover:text-[var(--primary-color)]">Refund Policy</a>
                </nav>
                <hr class="my-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 font-display tracking-wider">Categories</h3>
                <nav class="flex flex-col space-y-3">
                     <?php foreach ($all_categories as $category): ?>
                        <a :href="basePath + '/products/category/<?= htmlspecialchars($category['slug']) ?>'" @click.prevent="setView('products', { filterType: 'category', filterValue: '<?= htmlspecialchars($category['name']) ?>' }); isSideMenuOpen = false;" class="text-gray-600 hover:text-[var(--primary-color)]"><?= htmlspecialchars($category['name']) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
        
        <?php include 'partials/navbar.php'; ?>
        
        <main class="flex-grow">
            <div v-if="currentView === 'home'">
                <?php include 'views/home.php'; ?>
            </div>
            
            <div class="pb-16" v-else>
                <?php include 'views/products.php'; ?>
                <?php include 'views/product-detail.php'; ?>
                <?php include 'views/cart.php'; ?>
                <?php include 'views/checkout.php'; ?>
                <?php include 'views/order-history.php'; ?>
                <?php include 'views/static-pages.php'; ?>
            </div>
        </main>

        <?php include 'partials/footer.php'; ?>
        
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 flex justify-around items-center h-16 shadow-[0_-2px_5px_rgba(0,0,0,0.05)]">
            <a :href="basePath + '/'" @click.prevent="setView('home')" class="flex flex-col items-center justify-center transition w-full" :class="currentView === 'home' ? 'text-[var(--primary-color)]' : 'text-gray-500'"><i class="fas fa-home text-2xl"></i><span class="text-xs mt-1">Home</span></a>
            <a :href="basePath + '/products'" @click.prevent="setView('products')" class="flex flex-col items-center justify-center transition w-full" :class="currentView === 'products' ? 'text-[var(--primary-color)]' : 'text-gray-500'"><i class="fas fa-box-open text-2xl"></i><span class="text-xs mt-1">Products</span></a>
            <a :href="basePath + '/order-history'" @click.prevent="setView('orderHistory')" class="relative flex flex-col items-center justify-center transition w-full" :class="currentView === 'orderHistory' ? 'text-[var(--primary-color)]' : 'text-gray-500'">
                <div class="relative">
                    <i class="fas fa-receipt text-2xl"></i>
                    <span v-show="newNotificationCount > 0" class="notification-badge" style="top: -2px; right: -8px;">{{ newNotificationCount }}</span>
                </div>
                <span class="text-xs mt-1">Orders</span>
            </a>
        </nav>
        
        <div class="fixed bottom-20 md:bottom-6 right-4 z-40">
            <div v-show="fabOpen" class="flex flex-col items-center space-y-3 mb-3" style="display: none;">
                <a href="tel:<?= htmlspecialchars($contact_info['phone']) ?>" class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg text-[var(--primary-color)] border"><i class="fas fa-phone-alt text-xl transform -scale-x-100"></i></a>
                <a href="https://wa.me/<?= htmlspecialchars($contact_info['whatsapp']) ?>" target="_blank" class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg text-green-500 border"><i class="fab fa-whatsapp text-2xl"></i></a>
                <a href="mailto:<?= htmlspecialchars($contact_info['email']) ?>" class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg text-red-500 border"><i class="fas fa-envelope text-xl"></i></a>
            </div>
            <button @click="fabOpen = !fabOpen" class="flex flex-col items-center text-gray-700">
                <div class="w-14 h-14 bg-[var(--primary-color)] text-white rounded-full flex items-center justify-center shadow-lg"><i class="fas fa-headset text-2xl fab-icon" :class="{'rotate-45': fabOpen}"></i></div>
                <span class="text-xs font-semibold mt-2">Need Help?</span>
            </button>
        </div>
    </div>

<?php include 'partials/scripts.php'; ?>

</body>
</html>