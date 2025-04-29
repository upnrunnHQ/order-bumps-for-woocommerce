<?php

return [
    1 => [
        'id' => 1,
        'name' => 'Bump 1',
        'layout' => 'template-1', // or "grid"
        'display_location' => 'before_order_review',
        'conditions' => [
            ['type' => 'cart_total', 'value' => 5],
            ['type' => 'cart_item_count', 'value' => 1]
        ],
        'products' => [
            [
                'id' => 187,
                'discount_type' => 'percent',  // or 'percent'
                'discount' => 10,            // fixed amount or percentage
                'quantity' => 1
            ],
            [
                'id' => 59,
                'discount_type' => 'percent',
                'discount' => 15,            // 15% discount
                'quantity' => 1
            ]
        ],
        'rules' => []
    ],
    2 => [
        'id' => 2,
        'name' => 'Bump 2',
        'layout' => 'grid', // or "list"
        'display_location' => 'before_payment',
        'conditions' => [
            ['type' => 'cart_total', 'value' => 1000]
        ],
        'products' => [59],
        'rules' => []
    ],
    3 => [
        'id' => 3,
        'name' => 'Bump 3',
        'layout' => 'list', // or "grid"
        'display_location' => 'before_order_review', // after_order_review
        'conditions' => [
            [
                'logic' => 'AND',
                'conditions' => [
                    ['type' => 'cart_total', 'value' => 400, 'logic' => 'AND'],
                    ['type' => 'cart_item_count', 'value' => 1, 'logic' => 'AND'],
                ]
            ],
            [
                'logic' => 'AND',
                'conditions' => [
                    ['type' => 'cart_sub_total', 'value' => 300, 'logic' => 'AND'],
                    ['type' => 'cart_item_count', 'value' => 1, 'logic' => 'AND'],
                ]
            ]
        ],
        'products' => [59],
        'rules' => []
    ]
];

