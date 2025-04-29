<?php

namespace UpnRunn\OrderBumpsForWooCommerce;


// use UpnRunn\OrderBumpsForWooCommerce\ProductDisplayCondition;


if (!defined('ABSPATH')) {
    exit;
}

/* ConditionProvider Class */
class ConditionProvider {
    protected $conditions = [];

    /**
     * Register conditions for a specific order bump.
     *
     * @param int   $bump_id
     * @param array $conditions Array of ProductDisplayCondition objects.
     */
    public function register_conditions($bump_id, $conditions) {
        if (!isset($this->conditions[$bump_id])) {
            $this->conditions[$bump_id] = [];
        }
        foreach ($conditions as $condition) {
            if ($condition instanceof ProductDisplayCondition) {
                $this->conditions[$bump_id][] = $condition;
            }
        }
    }

    /**
     * Get conditions for a specific order bump.
     *
     * @param int $bump_id
     * @return array Array of ProductDisplayCondition objects.
     */
    public function get_conditions($bump_id) {
        return $this->conditions[$bump_id] ?? [];
    }
}