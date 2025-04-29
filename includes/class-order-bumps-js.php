<?php
namespace UpnRunn\OrderBumpsForWooCommerce;

use UpnRunn\OrderBumpsForWooCommerce\Order_Bumps;

/**
 * Class Order_Bumps_AJAX
 *
 * Handles all AJAX and JavaScript functionality for order bumps.
 */
class Order_Bumps_JS {

    private $order_bumps;

    /**
     * Registers AJAX actions and enqueues necessary assets.
     */
    public function __construct() {
        // Enqueue front-end JavaScript and CSS assets.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Register AJAX actions to fetch bump products and add products to cart.
        add_action('wp_ajax_get_order_bump_products', [$this, 'get_order_bumps_products']);
        add_action('wp_ajax_nopriv_get_order_bump_products', [$this, 'get_order_bumps_products']);
        add_action('wp_ajax_add_product_to_cart', [$this, 'add_product_to_cart']);
        add_action('wp_ajax_nopriv_add_product_to_cart', [$this, 'add_product_to_cart']);

        // Apply discount pricing to cart items based on bump meta.
        add_action('woocommerce_before_calculate_totals', [$this, 'apply_order_bump_discount'], 20, 1);
    }


    /**
     * Enqueues JavaScript assets and localizes script variables.
     */
    public function enqueue_assets() {
        wp_enqueue_script(
            'order-bumps-js',
            ORDER_BUMPS_PLUGIN_URL . '/assets/order-bumps.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Force the script tag to have type="module"
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'order-bumps-js') {
                return str_replace('<script', '<script type="module"', $tag);
            }
            return $tag;
        }, 10, 2);

        // Directory where your template files are stored
        $templates_dir = ORDER_BUMPS_PLUGIN_DIR . '/templates/';

        // Use glob to find all files matching "template-*.html"
        $template_files = glob($templates_dir . 'template-*.html');

        $templates_array = [];

        if (!empty($template_files)) {
            foreach ($template_files as $filepath) {
                if (file_exists($filepath)) {
                    // Extract the template name (e.g., "template-1" from "template-1.html")
                    // "template-1.html" => "template-1"
                    $filename = basename($filepath, '.html');

                    $content = file_get_contents($filepath);
                    
                    // Extract CSS inside <style> tags
                    preg_match('/<style>(.*?)<\/style>/s', $content, $css_match);
                    $css = $css_match[1] ?? '';

                    // Remove <style> from template HTML
                    $content = preg_replace('/<style>.*?<\/style>/s', '', $content);

                    $templates_array[$filename] = [
                            'html' => trim($content),
                            'css'  => trim($css)
                        ];
                    }
            }
        }

        wp_localize_script('order-bumps-js', 'orderBumpConfig', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('order_bump_nonce'),
            'templates' => $templates_array, // e.g. { 'template-1': '...', 'template-2': '...' }
        ]);
    }



    /**
     * AJAX handler to fetch products for an order bump.
     * Retrieves product data, calculates discounted price if applicable,
     * and returns product details along with any extra parameters.
     */
    public function get_order_bumps_products() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'order_bump_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed']);
            return;
        }

        $config_path = ORDER_BUMPS_PLUGIN_DIR . '/config/order-bumps.php';

        if (file_exists($config_path)) {
            // Load configuration into $this->order_bumps.
            $this->order_bumps = include $config_path;
        }

        if (isset($_GET['bump_id'])) {
            $bump_id = sanitize_text_field($_GET['bump_id']);
            foreach ($this->order_bumps as $bump) {
                if ($bump_id != $bump['id']) {
                    continue;
                }
                
                $products = [];
                foreach ($bump['products'] as $productData) {
                    // If product data is an array, extract extra parameters.
                    if (is_array($productData)) {
                        $product_id = $productData['id'];
                    } else {
                        $product_id = $productData;
                        $productData = [];
                    }
                    
                    $product = wc_get_product($product_id);
                    if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                        $image_data = wp_get_attachment_image_src($product->get_image_id(), 'thumbnail');
                        
                        // Get the regular and sale price.
                        $regular_price = $product->get_regular_price();
                        $sale_price = $product->get_price();
                        
                        // Calculate discounted price if discount info is provided.
                        if (isset($productData['discount_type']) && isset($productData['discount'])) {
                            if ($productData['discount_type'] === 'fixed') {
                                $discounted_price = max(0, floatval($regular_price) - floatval($productData['discount']));
                            } elseif ($productData['discount_type'] === 'percent') {
                                $discounted_price = floatval($regular_price) * (1 - (floatval($productData['discount']) / 100));
                            } else {
                                $discounted_price = $sale_price;
                            }
                        } else {
                            $discounted_price = $sale_price;
                        }
                        
                        // Set default quantity if not provided.
                        if (!isset($productData['quantity'])) {
                            $productData['quantity'] = 1;
                        }
                        
                        $products[] = array_merge([
                            'id'            => $product->get_id(),
                            'name'          => $product->get_name(),
                            'regular_price' => wc_price($regular_price),
                            'price'         => wc_price($discounted_price),
                            'quantity'      => $productData['quantity'],
                            'image'         => $image_data ? $image_data[0] : '',
                            //'layout'        => $productData['layout'],
                        ], $productData);
                    }
                }
                
                if (!empty($products)) {
                    wp_send_json_success($products);
                } else {
                    wp_send_json_error(['message' => 'No products available for order bumps.']);
                }
            }
        } else {
            wp_send_json_error(['message' => 'Missing bump ID']);
            return;
        }
    }

    
    /**
     * Adds a product to the cart via AJAX, including discount metadata.
     */
    public function add_product_to_cart() {
        $product_id = intval($_POST['product_id']);
        $quantity   = intval($_POST['quantity'] ?? 1);
        
        // Retrieve discount parameters from the request.
        $discount_type = isset($_POST['discount_type']) ? sanitize_text_field($_POST['discount_type']) : '';
        $discount      = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
        
        // Prepare meta data for the cart item.
        $cart_item_data = [];
        if (!empty($discount_type) && $discount > 0) {
            $product = wc_get_product($product_id);
            if ($product) {
                // Get the product's regular price.
                $original_price = floatval($product->get_regular_price());
                $cart_item_data['discount_type']  = $discount_type;
                $cart_item_data['discount']       = $discount;
                $cart_item_data['original_price'] = $original_price;
            }
        }
        
        // Add the product to the cart with the provided quantity and meta.
        if (WC()->cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'Unable to add the product to the cart.']);
        }
    }

    /**
     * Applies discount pricing to cart items before totals are calculated.
     * This adjusts the item price based on discount metadata.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    function apply_order_bump_discount($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['discount_type'], $cart_item['discount'], $cart_item['original_price'])) {
                $original_price = $cart_item['original_price'];
                $discount = $cart_item['discount'];
                
                if ($cart_item['discount_type'] === 'fixed') {
                    $new_price = max(0, $original_price - $discount);
                } elseif ($cart_item['discount_type'] === 'percent') {
                    $new_price = $original_price * (1 - ($discount / 100));
                } else {
                    $new_price = $cart_item['data']->get_price();
                }
                
                // Update the cart item price.
                $cart_item['data']->set_price($new_price);
            }
        }
    }
}