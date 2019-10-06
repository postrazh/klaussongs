<?php

if (!defined('WPINC')) {
    exit;
}

$allowed_product_types = array(
    'simple' => __('Simple product', 'woocommerce'),
    'grouped' => __('Grouped product', 'woocommerce'),
    'external' => __('External/Affiliate product', 'woocommerce'),
    'variable' => __('Variable product', 'woocommerce'),
);


// Subscription product types
if (class_exists('WC_Subscriptions')) {
    $subscription_term = get_term_by('slug', 'subscription', 'product_type');
    $variable_subscription_term = get_term_by('slug', 'variable-subscription', 'product_type');

    $allowed_product_types['subscription'] = $subscription_term->name;
    $allowed_product_types['variable-subscription'] = $variable_subscription_term->name;
}

// Composite product type
if (class_exists('WC_Composite_Products')) {
    $composite_term = get_term_by('name', 'composite', 'product_type');

    if ($composite_term) {
        $allowed_product_types['composite'] = $composite_term->name;
    }
}
// Simple Auction product type
if (class_exists('WooCommerce_simple_auction')) {
    $auction_term = get_term_by('name', 'auction', 'product_type');

    if ($auction_term) {
        $allowed_product_types['auction'] = $auction_term->name;
    }
}

// Bundle product type
if (class_exists('WC_Bundles')) {
    $bundle_term = get_term_by('name', 'bundle', 'product_type');

    if ($bundle_term) {
        $allowed_product_types['bundle'] = $bundle_term->name;
    }
}


// Booking product types
if (class_exists('WC_Booking')) {
    $booking_term = get_term_by('slug', 'booking', 'product_type');

    if ($booking_term) {
        $allowed_product_types['booking'] = $booking_term->name;
    }
}

// Photography product types
if (class_exists('WC_Photography')) {
    $photography_term = get_term_by('slug', 'photography', 'product_type');

    if ($photography_term) {
        $allowed_product_types['photography'] = $photography_term->name;
    }
}

return apply_filters('wf_csv_allowed_product_types', $allowed_product_types);