<?php

namespace UpnRunn\OrderBumpsForWooCommerce;


// use UpnRunn\OrderBumpsForWooCommerce\ProductDisplayCondition;


if (!defined('ABSPATH')) {
    exit;
}

class CartSubTotalCondition implements ProductDisplayCondition {
    private $minimumCartSubTotal;

    // Constructor to accept the minimum subtotal
    public function __construct($minimumCartSubTotal) {
        $this->minimumCartSubTotal = $minimumCartSubTotal;
    }

    // Check if the cart subtotal meets the minimum condition
    public function isSatisfied(): bool {
        // Get the cart subtotal (excluding taxes and shipping)
        $cartSubTotal = WC()->cart->get_subtotal();
        return $cartSubTotal >= $this->minimumCartSubTotal;
    }
}

