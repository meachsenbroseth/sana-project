<?php

return [
    'event_weights' => [
        'view' => 1,
        'search_click' => 2,
        'wishlist' => 3,
        'add_to_cart' => 5,
        'purchase' => 10,
    ],

    'decay_half_life_days' => 30,

    'similarity_lookback_days' => 90,

    'similarity_min_score' => 0.1,
];
