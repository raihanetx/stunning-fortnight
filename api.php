<?php
// api.php - ক্যাটাগরি, প্রোডাক্ট, কুপন এবং অর্ডার এর সমস্ত ব্যাকএন্ড লজিক হ্যান্ডেল করে

// --- Import PHPMailer classes into the global namespace ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Load PHPMailer files ---
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

session_start();

// --- File Paths ---
$products_file_path = 'products.json';
$coupons_file_path = 'coupons.json';
$orders_file_path = 'orders.json';
$config_file_path = 'config.json';
$hotdeals_file_path = 'hotdeals.json';
$profiles_file_path = 'customer_profiles.json'; // New Profile File
$upload_dir = 'uploads/';

// --- Helper Functions ---
function get_data($file_path) {
    if (!file_exists($file_path)) file_put_contents($file_path, '[]');
    return json_decode(file_get_contents($file_path), true);
}

function save_data($file_path, $data) {
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . rand(100, 999);
    }
    return $text;
}

function handle_image_upload($file_input, $upload_dir, $prefix = '') {
    if (isset($file_input) && $file_input['error'] === UPLOAD_ERR_OK) {
        $original_filename = basename($file_input['name']);
        $safe_filename = preg_replace("/[^a-zA-Z0-9-_\.]/", "", $original_filename);
        $destination = $upload_dir . $prefix . time() . '-' . uniqid() . '-' . $safe_filename;
        if (move_uploaded_file($file_input['tmp_name'], $destination)) {
            return $destination;
        }
    }
    return null;
}

function send_email($to, $subject, $body, $config) {
    $mail = new PHPMailer(true);
    $smtp_settings = $config['smtp_settings'] ?? [];
    $admin_email = $smtp_settings['admin_email'] ?? '';
    $app_password = $smtp_settings['app_password'] ?? '';

    if (empty($admin_email) || empty($app_password)) {
        return false;
    }

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $admin_email;
        $mail->Password   = $app_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->setFrom($admin_email, 'Submonth');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get_orders_by_ids' && isset($_GET['ids'])) {
        $order_ids_to_find = json_decode($_GET['ids'], true);
        if (is_array($order_ids_to_find)) {
            $all_orders = get_data($orders_file_path);
            $found_orders = array_filter($all_orders, fn($order) => in_array($order['order_id'], $order_ids_to_find));
            header('Content-Type: application/json');
            echo json_encode(array_values($found_orders));
        } else {
            header('Content-Type: application/json', true, 400);
            echo json_encode([]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $json_data = null;
    
    if (!$action) {
        $json_data = json_decode(file_get_contents('php://input'), true);
        $action = $json_data['action'] ?? null;
    }
    
    if (!$action) { http_response_code(400); echo "Action not specified."; exit; }

    $admin_actions = [
        'add_category', 'delete_category', 'edit_category', 
        'add_product', 'delete_product', 'edit_product', 
        'add_coupon', 'delete_coupon', 
        'update_review_status', 'update_order_status',
        'update_hero_banner', 'update_favicon', 'update_currency_rate', 
        'update_contact_info', 'update_admin_password', 'update_site_logo',
        'update_hot_deals', 'update_smtp_settings', 'send_manual_email', 'update_site_pages',
        'add_payment_method', 'edit_payment_method', 'delete_payment_method', 'toggle_payment_method_status',
        'add_customer_profile', 'delete_customer_profile' // New Actions
    ];

    if (in_array($action, $admin_actions)) {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(403);
            echo "Forbidden: You must be logged in to perform this action.";
            exit;
        }
    }

    $redirect_url = 'admin.php';
    
    if ($action === 'add_customer_profile') {
        $order_id = $_POST['order_id'];
        $all_orders = get_data($orders_file_path);
        $order_to_add = null;
        foreach ($all_orders as $order) {
            if ($order['order_id'] == $order_id) {
                $order_to_add = $order;
                break;
            }
        }

        if ($order_to_add) {
            $all_profiles = get_data($profiles_file_path);
            $customer_phone = $order_to_add['customer']['phone'];
            $existing_profile_key = null;

            // Check if a profile with the same phone number already exists
            foreach ($all_profiles as $key => $profile) {
                if (isset($profile['customer']['phone']) && $profile['customer']['phone'] == $customer_phone) {
                    $existing_profile_key = $key;
                    break;
                }
            }

            if ($existing_profile_key !== null) {
                // Update existing profile with the latest order info
                $all_profiles[$existing_profile_key]['order_id'] = $order_to_add['order_id'];
                $all_profiles[$existing_profile_key]['order_date'] = $order_to_add['order_date'];
                $all_profiles[$existing_profile_key]['customer'] = $order_to_add['customer'];
                $all_profiles[$existing_profile_key]['items'] = $order_to_add['items'];
            } else {
                // Add a new profile
                $new_profile = [
                    'id' => time() . rand(100, 999),
                    'order_id' => $order_to_add['order_id'],
                    'order_date' => $order_to_add['order_date'],
                    'customer' => $order_to_add['customer'],
                    'items' => $order_to_add['items']
                ];
                $all_profiles[] = $new_profile;
            }
            save_data($profiles_file_path, $all_profiles);
        }
        $redirect_url = 'admin.php?view=profiles';
    }


    if ($action === 'delete_customer_profile') {
        $profile_id = $_POST['profile_id'];
        $all_profiles = get_data($profiles_file_path);
        $all_profiles = array_values(array_filter($all_profiles, fn($p) => $p['id'] != $profile_id));
        save_data($profiles_file_path, $all_profiles);
        $redirect_url = 'admin.php?view=profiles';
    }

    if ($action === 'update_hero_banner') {
        $config = get_data($config_file_path);
        if (isset($_POST['hero_slider_interval'])) { $config['hero_slider_interval'] = (int)$_POST['hero_slider_interval'] * 1000; }
        $current_banners = $config['hero_banner'] ?? [];
        if (!is_array($current_banners)) { $current_banners = $current_banners ? [$current_banners] : []; }
        if (isset($_POST['delete_hero_banners']) && is_array($_POST['delete_hero_banners'])) {
            foreach ($_POST['delete_hero_banners'] as $index => $value) {
                if ($value === 'true' && isset($current_banners[$index])) {
                    if (file_exists($current_banners[$index]) && is_file($current_banners[$index])) {
                        unlink($current_banners[$index]);
                    }
                    $current_banners[$index] = null;
                }
            }
        }
        $max_banners = 10;
        for ($i = 0; $i < $max_banners; $i++) {
            if (isset($_FILES['hero_banners']['tmp_name'][$i]) && is_uploaded_file($_FILES['hero_banners']['tmp_name'][$i])) {
                if (isset($current_banners[$i]) && file_exists($current_banners[$i]) && is_file($current_banners[$i])) {
                    unlink($current_banners[$i]);
                }
                $file_to_upload = [
                    'name' => $_FILES['hero_banners']['name'][$i],
                    'type' => $_FILES['hero_banners']['type'][$i],
                    'tmp_name' => $_FILES['hero_banners']['tmp_name'][$i],
                    'error' => $_FILES['hero_banners']['error'][$i],
                    'size' => $_FILES['hero_banners']['size'][$i]
                ];
                $destination = handle_image_upload($file_to_upload, $upload_dir, 'hero-');
                if ($destination) {
                    $current_banners[$i] = $destination;
                }
            }
        }
        $config['hero_banner'] = array_values(array_filter($current_banners));
        save_data($config_file_path, $config);
        $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'update_site_logo') {
        $config = get_data($config_file_path); if (isset($_POST['delete_site_logo']) && !empty($config['site_logo'])) { if (file_exists($config['site_logo']) && is_file($config['site_logo'])) unlink($config['site_logo']); $config['site_logo'] = ''; } if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) { if (!empty($config['site_logo']) && file_exists($config['site_logo']) && is_file($config['site_logo'])) unlink($config['site_logo']); $destination = handle_image_upload($_FILES['site_logo'], $upload_dir, 'logo-'); if($destination) $config['site_logo'] = $destination; } save_data($config_file_path, $config); $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'update_favicon') {
        $config = get_data($config_file_path); if (isset($_POST['delete_favicon']) && !empty($config['favicon'])) { if (file_exists($config['favicon']) && is_file($config['favicon'])) unlink($config['favicon']); $config['favicon'] = ''; } if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) { if (!empty($config['favicon']) && file_exists($config['favicon']) && is_file($config['favicon'])) unlink($config['favicon']); $destination = handle_image_upload($_FILES['favicon'], $upload_dir, 'favicon-'); if($destination) $config['favicon'] = $destination; } save_data($config_file_path, $config); $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'add_payment_method') {
        $config = get_data($config_file_path);
        $method_name = htmlspecialchars(trim($_POST['method_name']));
        if (!empty($method_name) && !isset($config['payment_methods'][$method_name])) {
            $logo_path = handle_image_upload($_FILES['logo'] ?? null, $upload_dir, 'payment-');
            $new_method = [
                'logo_url' => $logo_path ?? '',
                'is_active' => isset($_POST['is_active']),
                'is_default' => false
            ];

            if ($_POST['method_type'] === 'number') {
                $new_method['number'] = htmlspecialchars(trim($_POST['number_or_id']));
            } elseif ($_POST['method_type'] === 'pay_id') {
                $new_method['pay_id'] = htmlspecialchars(trim($_POST['number_or_id']));
            } elseif ($_POST['method_type'] === 'account_number') {
                $new_method['account_number'] = htmlspecialchars(trim($_POST['number_or_id']));
            }

            $config['payment_methods'][$method_name] = $new_method;
            save_data($config_file_path, $config);
        }
        $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'edit_payment_method') {
        $config = get_data($config_file_path);
        $original_name = $_POST['original_method_name'];
        $new_name = htmlspecialchars(trim($_POST['new_method_name']));
        
        if (isset($config['payment_methods'][$original_name])) {
            $method_data = $config['payment_methods'][$original_name];

            if (isset($_POST['delete_logo']) && !empty($method_data['logo_url']) && file_exists($method_data['logo_url'])) {
                unlink($method_data['logo_url']);
                $method_data['logo_url'] = '';
            }

            $new_logo = handle_image_upload($_FILES['logo'] ?? null, $upload_dir, 'payment-');
            if ($new_logo) {
                if (!empty($method_data['logo_url']) && file_exists($method_data['logo_url'])) {
                    unlink($method_data['logo_url']);
                }
                $method_data['logo_url'] = $new_logo;
            }
            
            unset($method_data['number'], $method_data['pay_id'], $method_data['account_number']);

            if (isset($_POST['number'])) {
                $method_data['number'] = htmlspecialchars(trim($_POST['number']));
            } elseif (isset($_POST['pay_id'])) {
                $method_data['pay_id'] = htmlspecialchars(trim($_POST['pay_id']));
            } elseif (isset($_POST['account_number'])) {
                $method_data['account_number'] = htmlspecialchars(trim($_POST['account_number']));
            }
            
            unset($config['payment_methods'][$original_name]);
            $config['payment_methods'][$new_name] = $method_data;
            save_data($config_file_path, $config);
        }
        $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'delete_payment_method') {
        $config = get_data($config_file_path);
        $method_name = $_POST['method_name'];
        if (isset($config['payment_methods'][$method_name])) {
            $method_data = $config['payment_methods'][$method_name];
            if (isset($method_data['is_default']) && $method_data['is_default']) {
                // Cannot delete default methods
            } else {
                if (!empty($method_data['logo_url']) && file_exists($method_data['logo_url'])) {
                    unlink($method_data['logo_url']);
                }
                unset($config['payment_methods'][$method_name]);
                save_data($config_file_path, $config);
            }
        }
        $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'toggle_payment_method_status') {
        $config = get_data($config_file_path);
        $method_name = $_POST['method_name'];
        if (isset($config['payment_methods'][$method_name])) {
            $config['payment_methods'][$method_name]['is_active'] = !$config['payment_methods'][$method_name]['is_active'];
            save_data($config_file_path, $config);
        }
        $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'update_smtp_settings') {
        $config = get_data($config_file_path);
        if (isset($_POST['admin_email'])) {
            $config['smtp_settings']['admin_email'] = htmlspecialchars(trim($_POST['admin_email']));
        }
        if (!empty(trim($_POST['app_password']))) {
            $config['smtp_settings']['app_password'] = trim($_POST['app_password']);
        }
        save_data($config_file_path, $config);
        $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'update_currency_rate') {
        $config = get_data($config_file_path); if (isset($_POST['usd_to_bdt_rate'])) { $config['usd_to_bdt_rate'] = (float)$_POST['usd_to_bdt_rate']; } save_data($config_file_path, $config); $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'update_contact_info') {
        $config = get_data($config_file_path); $config['contact_info']['phone'] = htmlspecialchars(trim($_POST['phone_number'])); $config['contact_info']['whatsapp'] = htmlspecialchars(trim($_POST['whatsapp_number'])); $config['contact_info']['email'] = htmlspecialchars(trim($_POST['email_address'])); save_data($config_file_path, $config); $redirect_url = 'admin.php?view=settings';
    }
    if ($action === 'update_admin_password') {
        $config = get_data($config_file_path); if (!empty(trim($_POST['new_password']))) { $config['admin_password'] = trim($_POST['new_password']); } save_data($config_file_path, $config); $redirect_url = 'admin.php?view=settings';
    }
    if (in_array($action, ['add_category', 'delete_category', 'edit_category', 'add_product', 'delete_product', 'edit_product'])) {
        $all_data = get_data($products_file_path); if ($action === 'add_category') { $name = htmlspecialchars(trim($_POST['name'])); $all_data[] = ['name' => $name, 'slug' => slugify($name), 'icon' => htmlspecialchars(trim($_POST['icon'])), 'products' => []]; $redirect_url = 'admin.php?view=categories'; } if ($action === 'delete_category') { $all_data = array_values(array_filter($all_data, fn($cat) => $cat['name'] !== $_POST['name'])); $redirect_url = 'admin.php?view=categories'; } if ($action === 'edit_category') { $old_name = $_POST['original_name']; $new_name = htmlspecialchars(trim($_POST['name'])); $new_icon = htmlspecialchars(trim($_POST['icon'])); $new_slug = slugify($new_name); foreach ($all_data as &$category) { if ($category['name'] === $old_name) { $category['name'] = $new_name; $category['slug'] = $new_slug; $category['icon'] = $new_icon; break; } } unset($category); $all_coupons = get_data($coupons_file_path); foreach ($all_coupons as &$coupon) { if (($coupon['scope'] ?? '') === 'category' && $coupon['scope_value'] === $old_name) { $coupon['scope_value'] = $new_name; } } unset($coupon); save_data($coupons_file_path, $all_coupons); $redirect_url = 'admin.php?view=categories'; } if ($action === 'add_product' || $action === 'edit_product') { function parse_pricing_data() { $p = []; if (!empty($_POST['durations'])) { for ($i = 0; $i < count($_POST['durations']); $i++) { $p[] = ['duration' => htmlspecialchars(trim($_POST['durations'][$i])), 'price' => (float)$_POST['duration_prices'][$i]]; } } else { $p[] = ['duration' => 'Default', 'price' => (float)$_POST['price']]; } return $p; } function sanitize_description($desc) { return str_replace(['<', '>'], ['&lt;', '&gt;'], $desc); } if ($action === 'add_product') { $image_path = handle_image_upload($_FILES['image'] ?? null, $upload_dir, 'product-'); $name = htmlspecialchars(trim($_POST['name'])); $long_description_safe = sanitize_description(trim($_POST['long_description'] ?? '')); $new_product = [ 'id' => time() . rand(100, 999), 'name' => $name, 'slug' => slugify($name), 'description' => htmlspecialchars(trim($_POST['description'])), 'long_description' => $long_description_safe, 'image' => $image_path ?? '', 'pricing' => parse_pricing_data(), 'stock_out' => ($_POST['stock_out'] ?? 'false') === 'true', 'featured' => isset($_POST['featured']), 'reviews' => [] ]; foreach ($all_data as &$category) { if ($category['name'] === $_POST['category_name']) { if (!isset($category['products']) || !is_array($category['products'])) { $category['products'] = []; } $category['products'][] = $new_product; break; } } unset($category); $redirect_url = 'admin.php?category=' . urlencode($_POST['category_name']); } if ($action === 'edit_product') { for ($i = 0; $i < count($all_data); $i++) { if ($all_data[$i]['name'] === $_POST['category_name']) { for ($j = 0; $j < count($all_data[$i]['products']); $j++) { if ($all_data[$i]['products'][$j]['id'] == $_POST['product_id']) { $cp = &$all_data[$i]['products'][$j]; if (isset($_POST['delete_image']) && !empty($cp['image']) && file_exists($cp['image'])) { unlink($cp['image']); $cp['image'] = ''; } $nip = handle_image_upload($_FILES['image'] ?? null, 'uploads/', 'product-'); if ($nip) { if (!empty($cp['image']) && file_exists($cp['image'])) { unlink($cp['image']); } $cp['image'] = $nip; } $name = htmlspecialchars(trim($_POST['name'])); $cp['name'] = $name; $cp['slug'] = slugify($name); $cp['description'] = htmlspecialchars(trim($_POST['description'])); $cp['long_description'] = sanitize_description(trim($_POST['long_description'] ?? '')); $cp['pricing'] = parse_pricing_data(); $cp['stock_out'] = $_POST['stock_out'] === 'true'; $cp['featured'] = isset($_POST['featured']); break 2; } } } } $redirect_url = 'admin.php?category=' . urlencode($_POST['category_name']); } } if ($action === 'delete_product') { for ($i = 0; $i < count($all_data); $i++) { if ($all_data[$i]['name'] === $_POST['category_name']) { foreach($all_data[$i]['products'] as $p) { if ($p['id'] == $_POST['product_id'] && !empty($p['image']) && file_exists($p['image'])) { unlink($p['image']); break; } } $all_data[$i]['products'] = array_values(array_filter($all_data[$i]['products'], fn($prod) => $prod['id'] != $_POST['product_id'])); break; } } $redirect_url = 'admin.php?category=' . urlencode($_POST['category_name']); } save_data($products_file_path, $all_data);
    }
    if (in_array($action, ['add_coupon', 'delete_coupon'])) {
        $all_coupons = get_data($coupons_file_path); if ($action === 'add_coupon') { $scope = $_POST['scope'] ?? 'all_products'; $scope_value = null; if ($scope === 'category') { $scope_value = $_POST['scope_value_category'] ?? null; } elseif ($scope === 'single_product') { $scope_value = $_POST['scope_value_product'] ?? null; } $all_coupons[] = [ 'id' => time() . rand(100, 999), 'code' => strtoupper(htmlspecialchars(trim($_POST['code']))), 'discount_percentage' => (int)$_POST['discount_percentage'], 'is_active' => isset($_POST['is_active']), 'scope' => $scope, 'scope_value' => $scope_value ]; } if ($action === 'delete_coupon') { $all_coupons = array_values(array_filter($all_coupons, fn($c) => $c['id'] != $_POST['coupon_id'])); } save_data($coupons_file_path, $all_coupons); $redirect_url = 'admin.php?view=dashboard';
    }
    if ($action === 'update_hot_deals') {
        $config = get_data($config_file_path); if (isset($_POST['hot_deals_speed'])) { $config['hot_deals_speed'] = (int)$_POST['hot_deals_speed']; } save_data($config_file_path, $config); $new_deals_data = []; $selected_product_ids = $_POST['selected_deals'] ?? []; foreach($selected_product_ids as $productId) { $custom_title = htmlspecialchars(trim($_POST['custom_titles'][$productId] ?? '')); $new_deals_data[] = [ 'productId' => $productId, 'customTitle' => $custom_title ]; } save_data($hotdeals_file_path, $new_deals_data); $redirect_url = 'admin.php?view=hotdeals';
    }
    if ($action === 'update_site_pages') {
        $config = get_data($config_file_path);
        if (isset($_POST['site_pages']) && is_array($_POST['site_pages'])) {
            $config['site_pages']['about_us'] = $_POST['site_pages']['about_us'] ?? '';
            $config['site_pages']['privacy_policy'] = $_POST['site_pages']['privacy_policy'] ?? '';
            $config['site_pages']['terms_and_conditions'] = $_POST['site_pages']['terms_and_conditions'] ?? '';
            $config['site_pages']['refund_policy'] = $_POST['site_pages']['refund_policy'] ?? '';
        }
        save_data($config_file_path, $config);
        $redirect_url = 'admin.php?view=pages';
    }
    if ($action === 'add_review') {
        $review_data = $json_data['review']; $product_id = $review_data['productId']; $all_products = get_data($products_file_path); $product_found = false; for ($i = 0; $i < count($all_products); $i++) { if (empty($all_products[$i]['products'])) continue; for ($j = 0; $j < count($all_products[$i]['products']); $j++) { if ($all_products[$i]['products'][$j]['id'] == $product_id) { if (!isset($all_products[$i]['products'][$j]['reviews'])) { $all_products[$i]['products'][$j]['reviews'] = []; } $new_review = [ 'id' => time() . '-' . rand(100, 999), 'name' => htmlspecialchars($review_data['name']), 'rating' => (int)$review_data['rating'], 'comment' => htmlspecialchars($review_data['comment']), ]; array_unshift($all_products[$i]['products'][$j]['reviews'], $new_review); $product_found = true; break 2; } } } if ($product_found) { save_data($products_file_path, $all_products); header('Content-Type: application/json'); echo json_encode(['success' => true, 'message' => 'Review added successfully!']); } else { header('Content-Type: application/json', true, 404); echo json_encode(['success' => false, 'message' => 'Product not found.']); } exit;
    }
    if ($action === 'update_review_status') {
        $product_id = $_POST['product_id']; $review_id = $_POST['review_id']; $new_status = $_POST['new_status']; $all_products = get_data($products_file_path); if ($new_status === 'deleted') { for ($i = 0; $i < count($all_products); $i++) { if (empty($all_products[$i]['products'])) continue; for ($j = 0; $j < count($all_products[$i]['products']); $j++) { if ($all_products[$i]['products'][$j]['id'] == $product_id) { $all_products[$i]['products'][$j]['reviews'] = array_values( array_filter( $all_products[$i]['products'][$j]['reviews'] ?? [], fn($review) => $review['id'] !== $review_id ) ); break 2; } } } save_data($products_file_path, $all_products); } $redirect_url = 'admin.php?view=reviews';
    }
    if ($action === 'place_order') {
        $order_data = $json_data['order'];
        
        // Server-side Transaction ID validation
        $trx_id_patterns = [
            'bKash'       => '/^(?=.{10}$)(?=.*[A-Z])(?=.*\d)[A-Z0-9]+$/',
            'Upay'        => '/^(?=.{10}$)(?=.*[A-Z])(?=.*\d)[A-Z0-9]+$/',
            'Nagad'       => '/^(?=.*\d)(?=.*[A-Z])[A-Z0-9]{8}$/',
            'Rocket'      => '/^\d{10}$/',
            'Binance Pay' => '/^[A-Za-z0-9]{17}$/'
        ];
        
        $payment_method = $order_data['paymentInfo']['method'] ?? '';
        $trx_id = $order_data['paymentInfo']['trx_id'] ?? '';
        
        $is_valid_trx = true; // Assume true by default
        if (isset($trx_id_patterns[$payment_method])) {
            // If a specific pattern exists, validate against it
            if (!preg_match($trx_id_patterns[$payment_method], $trx_id)) {
                $is_valid_trx = false;
            }
        } elseif (empty(trim($trx_id))) {
            // For other (non-defined) methods, just ensure it's not empty
            $is_valid_trx = false;
        }

        if (!$is_valid_trx) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please enter a valid Transaction ID.']);
            exit;
        }

        $all_orders = get_data($orders_file_path); $subtotal = 0; $all_products_data = get_data($products_file_path); $all_products_flat = []; foreach($all_products_data as $cat) { if(isset($cat['products'])) { foreach($cat['products'] as $p) { $p['category'] = $cat['name']; $all_products_flat[$p['id']] = $p; } } } foreach($order_data['items'] as $item) { $subtotal += $item['pricing']['price'] * $item['quantity']; } $discount = 0; if (!empty($order_data['coupon']) && isset($order_data['coupon']['discount_percentage'])) { $coupon = $order_data['coupon']; $eligible_subtotal = 0; if (!isset($coupon['scope']) || $coupon['scope'] === 'all_products') { $eligible_subtotal = $subtotal; } else { foreach($order_data['items'] as $item) { $product_id = $item['id']; if (isset($all_products_flat[$product_id])) { $product_details = $all_products_flat[$product_id]; if ($coupon['scope'] === 'category' && $product_details['category'] === $coupon['scope_value']) { $eligible_subtotal += $item['pricing']['price'] * $item['quantity']; } elseif ($coupon['scope'] === 'single_product' && $product_id == $coupon['scope_value']) { $eligible_subtotal += $item['pricing']['price'] * $item['quantity']; } } } } $discount = $eligible_subtotal * ($coupon['discount_percentage'] / 100); } $total = $subtotal - $discount; $new_order = ['order_id' => time(), 'order_date' => date('Y-m-d H:i:s'), 'customer' => $order_data['customerInfo'], 'payment' => $order_data['paymentInfo'], 'items' => $order_data['items'], 'coupon' => $order_data['coupon'] ?? [], 'totals' => ['subtotal' => $subtotal, 'discount' => $discount, 'total' => $total,], 'status' => 'Pending',]; $all_orders[] = $new_order; save_data($orders_file_path, $all_orders);
        $config = get_data($config_file_path); $admin_email = $config['smtp_settings']['admin_email'] ?? ''; $email_subject = "New Order Received: #" . $new_order['order_id']; $email_body = '<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; padding: 20px;">'; $email_body .= '<div style="max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 5px;">'; $email_body .= '<h2>New Order Notification</h2>'; $email_body .= '<p>A new order has been placed on your website.</p>'; $email_body .= '<h3>Order Details:</h3>'; $email_body .= '<p><strong>Order ID:</strong> ' . $new_order['order_id'] . '</p>'; $email_body .= '<p><strong>Customer Name:</strong> ' . htmlspecialchars($new_order['customer']['name']) . '</p>'; $email_body .= '<p><strong>Customer Phone:</strong> ' . htmlspecialchars($new_order['customer']['phone']) . '</p>'; $email_body .= '<p><strong>Customer Email:</strong> ' . htmlspecialchars($new_order['customer']['email']) . '</p>'; $email_body .= '<h3>Items Ordered:</h3>'; $email_body .= '<table style="width: 100%; border-collapse: collapse;"><tr style="background-color: #f2f2f2;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Product</th><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Quantity</th><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Price</th></tr>'; foreach($new_order['items'] as $item) { $email_body .= "<tr><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($item['name']) . " (" . htmlspecialchars($item['pricing']['duration']) . ")</td><td style='padding: 8px; border: 1px solid #ddd;'>" . $item['quantity'] . "</td><td style='padding: 8px; border: 1px solid #ddd;'>৳" . number_format($item['pricing']['price'], 2) . "</td></tr>"; } $email_body .= "</table>"; $email_body .= "<p style='text-align: right;'><strong>Subtotal:</strong> ৳" . number_format($new_order['totals']['subtotal'], 2) . "</p>"; if($new_order['totals']['discount'] > 0) { $email_body .= "<p style='text-align: right;'><strong>Discount:</strong> -৳" . number_format($new_order['totals']['discount'], 2) . "</p>"; } $email_body .= "<p style='text-align: right; font-size: 1.1em;'><strong>Total:</strong> ৳" . number_format($new_order['totals']['total'], 2) . "</p>"; $email_body .= '<p>Please log in to the admin panel to review and process this order.</p>'; $email_body .= '</div></body></html>'; if(!empty($admin_email)) send_email($admin_email, $email_subject, $email_body, $config); header('Content-Type: application/json'); echo json_encode(['success' => true, 'order_id' => $new_order['order_id'], 'message' => 'Order placed successfully!']); exit;
    }
    if ($action === 'update_order_status') {
        $order_id = $_POST['order_id']; $new_status = $_POST['new_status']; $all_orders = get_data($orders_file_path);
        foreach ($all_orders as &$order) { if ($order['order_id'] == $order_id) { $order['status'] = $new_status; break; } } save_data($orders_file_path, $all_orders); $redirect_url = 'admin.php?view=orders';
    }
    if ($action === 'send_manual_email') {
        $order_id = $_POST['order_id']; $customer_email = $_POST['customer_email']; $access_details = $_POST['access_details']; $all_orders = get_data($orders_file_path); $order_to_email = null;
        $config = get_data($config_file_path);
        foreach ($all_orders as &$order) { if ($order['order_id'] == $order_id) { $order_to_email = $order; break; } } unset($order);

        if ($order_to_email) {
            $email_subject = "Your Submonth Order #" . $order_to_email['order_id'] . " is Confirmed!";
            $email_body = '<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; padding: 20px;">'; $email_body .= '<div style="max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 5px;">'; $email_body .= '<h2>Your Order is Confirmed!</h2>'; $email_body .= '<p>Dear ' . htmlspecialchars($order_to_email['customer']['name']) . ',</p>'; $email_body .= '<p>Thank you for your purchase. Your order #' . $order_to_email['order_id'] . ' has been confirmed and your access details are below.</p>'; $email_body .= '<h3>Your Access Details:</h3>'; $email_body .= '<div style="padding: 15px; background-color: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 5px; margin: 15px 0; white-space: pre-wrap; font-family: monospace;">' . nl2br(htmlspecialchars($access_details)) . '</div>'; $email_body .= '<h3>Order Summary:</h3>'; $email_body .= '<table style="width: 100%; border-collapse: collapse;"><tr style="background-color: #f2f2f2;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Product</th><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Quantity</th><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Price</th></tr>'; foreach($order_to_email['items'] as $item) { $email_body .= "<tr><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($item['name']) . " (" . htmlspecialchars($item['pricing']['duration']) . ")</td><td style='padding: 8px; border: 1px solid #ddd;'>" . $item['quantity'] . "</td><td style='padding: 8px; border: 1px solid #ddd;'>৳" . number_format($item['pricing']['price'], 2) . "</td></tr>"; } $email_body .= "</table>"; $email_body .= "<p style='text-align: right; font-size: 1.1em;'><strong>Total Paid:</strong> ৳" . number_format($order_to_email['totals']['total'], 2) . "</p>"; $email_body .= '<p>If you have any questions, feel free to contact our support.</p>'; $email_body .= '<p>Thank you for choosing Submonth!</p>'; $email_body .= '</div></body></html>';

            if (send_email($customer_email, $email_subject, $email_body, $config)) {
                foreach ($all_orders as &$order_to_update) { if ($order_to_update['order_id'] == $order_id) { $order_to_update['access_email_sent'] = true; break; } }
                save_data($orders_file_path, $all_orders);
            }
        }
        $redirect_url = 'admin.php?view=orders';
    }

    header('Location: ' . $redirect_url);
    exit;
}

http_response_code(403);
echo "Invalid Access";
?>