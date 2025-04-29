<?php

namespace UpnRunn\OrderBumpsForWooCommerce;

// use UpnRunn\OrderBumpsForWooCommerce\ProductDisplayCondition;


if (!defined('ABSPATH')) {
    exit;
}

class User_Logged_In_Condition implements ProductDisplayCondition {
    public function isSatisfied(): bool {
        return is_user_logged_in();
    }
}

