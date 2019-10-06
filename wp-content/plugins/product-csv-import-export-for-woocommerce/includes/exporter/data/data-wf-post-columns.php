<?php

if (!defined('WPINC')) {
    exit;
}

if (function_exists('wc_get_filename_from_url')) {
    $file_path_header = 'downloadable_files';
} else {
    $file_path_header = 'file_paths';
}


$post_columns = array(
    'post_title' => 'Product Name',
    'post_name' => 'Product Slug',
    'post_parent' => 'Parent ID',
    'ID' => 'ID',
    'post_excerpt' => 'Short Description',
    'post_content' => 'Description',
    'post_status' => 'Status',
    'post_password' => 'post_password',
    'menu_order' => 'menu_order',
    'post_date' => 'post_date',
    'post_author' => 'post_author',
    'comment_status' => 'comment_status',
    
    // Meta
    '_sku' => 'sku',
    'parent_sku' => 'parent_sku',
    'parent' => 'Parent Title',
    '_children' => 'children', //For Grouped products
    '_downloadable' => 'downloadable',
    '_virtual' => 'virtual',
    '_stock' => 'stock',
    '_regular_price' => 'Regular Price',
    '_sale_price' => 'Sale Price',
    '_weight' => 'weight',
    '_length' => 'length',
    '_width' => 'width',
    '_height' => 'height',
    '_tax_class' => 'tax_class',
    '_visibility' => 'visibility',
    '_stock_status' => 'stock_status',
    '_backorders' => 'backorders',
    '_manage_stock' => 'manage_stock',
    '_tax_status' => 'tax_status',
    '_upsell_ids' => 'upsell_ids',
    '_crosssell_ids' => 'crosssell_ids',
    '_featured' => 'featured',
    '_sale_price_dates_from' => 'sale_price_dates_from',
    '_sale_price_dates_to' => 'sale_price_dates_to',
    
    // Downloadable products
    '_download_limit' => 'download_limit',
    '_download_expiry' => 'download_expiry',
    
    // Virtual products
    '_product_url' => 'product_url',
    '_button_text' => 'button_text',
    
    // YOAST
    'meta:_yoast_wpseo_focuskw' => 'meta:_yoast_wpseo_focuskw',
    'meta:_yoast_wpseo_title' => 'meta:_yoast_wpseo_title',
    'meta:_yoast_wpseo_metadesc' => 'meta:_yoast_wpseo_metadesc',
    'meta:_yoast_wpseo_metakeywords' => 'meta:_yoast_wpseo_metakeywords',

    'images' => 'Images (featured and gallery)',
    "$file_path_header" => 'Downloadable file paths',
    'product_page_url' => 'Product Page URL',
    'taxonomies' => 'Taxonomies (cat/tags/shipping-class)',
    'meta' => 'Meta (custom fields)',
    'attributes' => 'Attributes',
);

if (apply_filters('wpml_setting', false, 'setup_complete')) {

    $post_columns['wpml:language_code'] = 'wpml:language_code';
    $post_columns['wpml:original_product_id'] = 'wpml:original_product_id';
    $post_columns['wpml:original_product_sku'] = 'wpml:original_product_sku';
}

return apply_filters('woocommerce_csv_product_post_columns', $post_columns);
