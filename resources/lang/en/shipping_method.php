<?php

return [
    'sections' => [
        'information' => 'Shipping Method Information',
        'province_fees' => 'Province Shipping Fees',
        'province_fees_description' => 'Configure shipping fees per Cambodian province. Provinces without a fee entry will fall back to the base cost above.',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'fields' => [
        'note' => 'Customer note',
        'note_helper' => 'Shown to customers at checkout when this method is selected.',
        'requires_direct_arrangement' => 'Direct courier arrangement',
        'requires_direct_arrangement_helper' => 'Enable if delivery cost is arranged directly with the courier (shipping cost will show as free in checkout).',
    ],
    'province_fees' => [
        'province' => 'Province',
        'fee' => 'Fee',
        'add' => 'Add Province Fee',
        'covered_provinces' => 'Covered Provinces',
        'provinces_suffix' => 'provinces',
        'duplicate_error' => 'Duplicate province(s) in fees: :provinces. Each province may only appear once.',
    ],
];
