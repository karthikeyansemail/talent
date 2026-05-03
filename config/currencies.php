<?php

/**
 * Supported currencies for organization-level display formatting.
 * Symbol-based, no conversion — values are stored as-is and rendered
 * with the org's chosen symbol.
 *
 * Add new currencies as needed (ISO 4217 codes).
 */

return [
    'USD' => ['symbol' => '$',    'name' => 'US Dollar',         'position' => 'before'],
    'EUR' => ['symbol' => '€',    'name' => 'Euro',              'position' => 'before'],
    'GBP' => ['symbol' => '£',    'name' => 'British Pound',     'position' => 'before'],
    'INR' => ['symbol' => '₹',    'name' => 'Indian Rupee',      'position' => 'before'],
    'AED' => ['symbol' => 'AED ', 'name' => 'UAE Dirham',        'position' => 'before'],
    'AUD' => ['symbol' => 'A$',   'name' => 'Australian Dollar', 'position' => 'before'],
    'CAD' => ['symbol' => 'C$',   'name' => 'Canadian Dollar',   'position' => 'before'],
    'SGD' => ['symbol' => 'S$',   'name' => 'Singapore Dollar',  'position' => 'before'],
    'JPY' => ['symbol' => '¥',    'name' => 'Japanese Yen',      'position' => 'before'],
    'CNY' => ['symbol' => '¥',    'name' => 'Chinese Yuan',      'position' => 'before'],
    'CHF' => ['symbol' => 'CHF ', 'name' => 'Swiss Franc',       'position' => 'before'],
    'ZAR' => ['symbol' => 'R',    'name' => 'South African Rand','position' => 'before'],
    'BRL' => ['symbol' => 'R$',   'name' => 'Brazilian Real',    'position' => 'before'],
    'MXN' => ['symbol' => 'Mex$', 'name' => 'Mexican Peso',      'position' => 'before'],
    'SAR' => ['symbol' => 'SAR ', 'name' => 'Saudi Riyal',       'position' => 'before'],
];
