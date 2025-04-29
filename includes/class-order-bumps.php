<?php

namespace UpnRunn\OrderBumpsForWooCommerce;

/**
 * Class Order_Bumps
 *
 * Handles the display and functionality of multiple order bumps in WooCommerce checkout.
 */
class Order_Bumps {
    // Singleton instance.
    private static $instance = null;
    // Array to hold order bump configurations.
    private $order_bumps = [];
    // Instance of ConditionProvider to manage bump conditions.
    private $condition_provider;

    private $order_bumps_js;

    /**
     * Returns the singleton instance.
     *
     * @return Order_Bumps
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * Loads necessary classes, order bump configurations, enqueues assets,
     * and registers hooks for displaying bumps, applying discounts, and handling AJAX.
     */
    private function __construct() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        // Include required interfaces and condition classes.
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/interfaces/interface-product-display-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-cart-total-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-cart-item-count-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-condition-provider.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-composite-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-user-logged-in-condition.php'; // Uncomment if needed

        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/class-order-bumps-js.php'; // Uncomment if needed

        if (is_admin()) {
            include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/admin/class-order-bumps-menu.php';
        }


        // Initialize the ConditionProvider.
        $this->condition_provider = new ConditionProvider();
        
        // Load order bump configuration from file and process conditions.
        $this->load_order_bumps();

        // Display order bumps during checkout initialization.
        add_action('woocommerce_checkout_init', [$this, 'display_order_bumps']);


        // Initialize the Order_Bumps_AJAX. Register AJAX actions to fetch bump products and add products to cart.
        $this->order_bumps_js = new Order_Bumps_JS();
    }

    /**
     * Loads order bumps from a configuration file and registers their conditions.
     */
    private function load_order_bumps() {
        $config_path = ORDER_BUMPS_PLUGIN_DIR . '/config/order-bumps.php';
        if (file_exists($config_path)) {
            // Load configuration into $this->order_bumps.
            $this->order_bumps = include $config_path;
            // Process each bump's conditions.
            foreach ($this->order_bumps as &$bump) {
                if (!empty($bump['conditions'])) {
                    $processed_conditions = [];
                    // Convert each condition config to an instance.
                    foreach ($bump['conditions'] as $condition_data) {
                        $condition_instance = $this->create_condition_instance($condition_data);
                        if ($condition_instance) {
                            $processed_conditions[] = $condition_instance;
                        }
                    }
                    // Replace raw condition data with condition objects.
                    $bump['conditions'] = $processed_conditions;

                    // Register conditions with the ConditionProvider.
                    $this->condition_provider->register_conditions($bump['id'], $bump['conditions']);
                }
            }
        }
    }


    /**
     * Creates a condition instance from configuration data.
     * Supports both single conditions and grouped (composite) conditions.
     *
     * @param array $condition_data The condition configuration.
     * @return ProductDisplayCondition|null Returns a condition instance or null if invalid.
     */
    private function create_condition_instance($condition_data) {
        // Check if this is a grouped condition (composite) that contains sub-conditions.
        if (isset($condition_data['conditions']) && is_array($condition_data['conditions'])) {
            $composite = new CompositeCondition();
            // Set the logic for this group; default to 'AND' if not provided.
            $logic = isset($condition_data['logic']) ? $condition_data['logic'] : 'AND';
            $composite->setLogic($logic);
            // Recursively process each sub-condition.
            foreach ($condition_data['conditions'] as $subCondition) {
                $instance = $this->create_condition_instance($subCondition);
                if ($instance) {
                    $composite->addCondition($instance);
                }
            }
            return $composite;
        } else {
            // Load the condition map from a separate config file.
            $condition_map = include ORDER_BUMPS_PLUGIN_DIR . '/config/condition-map.php';
            // Ensure required keys exist.
            if (!isset($condition_data['type'], $condition_data['value'])) {
                return null;
            }
            $type = $condition_data['type'];
            $value = $condition_data['value'];
            // Map the condition type to a class name and instantiate if possible.
            if (isset($condition_map[$type])) {
                $class_name = $condition_map[$type];
                if (class_exists($class_name)) {
                    return new $class_name($value);
                }
            }
            return null;
        }
    }

    /**
     * Displays order bumps on the checkout page if their conditions are met.
     */
    public function display_order_bumps() {
        foreach ($this->order_bumps as $bump) {
            if ($this->should_display_bump($bump)) {
                // Hook into WooCommerce at the specified display location.
                add_action($this->get_woocommerce_hook($bump['display_location']), function() use ($bump) {
                    echo $this->render_order_bump($bump);
                });
            }
        }
    }



    /**
     * Returns the WooCommerce hook based on the provided display location.
     *
     * @param string $location The configured location (e.g., before_order_review).
     * @return string The corresponding WooCommerce hook.
     */
    private function get_woocommerce_hook($location) {
        $hooks = [
            'before_order_review' => 'woocommerce_checkout_before_order_review',
            'after_order_review'  => 'woocommerce_checkout_after_order_review',
            'before_payment'      => 'woocommerce_review_order_before_payment',
            'after_payment'       => 'woocommerce_review_order_after_payment',
        ];
        return $hooks[$location] ?? 'woocommerce_checkout_before_order_review';
    }


    /**
     * Determines whether a given order bump should be displayed based on its conditions.
     *
     * @param array $bump The order bump configuration.
     * @return bool True if the bump should be displayed; false otherwise.
     */
    private function should_display_bump($bump) {
        $compositeCondition = new CompositeCondition();
        // Retrieve conditions specific to this bump.
        $conditions = $this->condition_provider->get_conditions($bump['id']);
        
        $compositeCondition->addConditions($conditions);
        
        // Use per-bump logic override if set, or default to a global filter.
        $logic = isset($bump['logic']) ? $bump['logic'] : apply_filters('order_bumps_conditions_logic', 'AND');
        $compositeCondition->setLogic($logic);

        // Check if the condition is satisfied and log the result
        $result = $compositeCondition->isSatisfied();
        
        return $result;
    }

    /**
     * Renders an empty container for an order bump.
     * JavaScript will later populate this container with product data.
     *
     * @param array $bump The order bump configuration.
     * @return string HTML container.
     */
    private function render_order_bump($bump) {
        $layout = $bump['layout'] ?? 'list';
        return '<div class="order-bump-container" data-bump-id="' . esc_attr($bump['id']) . '" data-layout="' . esc_attr($layout) . '"></div>';

    }

}