<?php

// Reserved column names
$post_columns =  array(
                'id' => 'Product ID | Product ID',
                'post_title' => 'Product Title | Product Title. ie Name of the product ',
                'post_name' => 'Product Permalink | Unique part of the product URL',
                'post_status' => 'Product Status | Product Status ( published , draft ...)',
                'post_content' => 'Product Description | Description about the Product',
                'post_excerpt' => 'Product Short Description | Short description about the Product',
                'post_date' => 'Post Date | Product posted date',
                //'post_date_gmt' => 'Post Date GMT | Tooltip data Status',
                'sku' => 'Product SKU | Product SKU - This will unique and Product identifier',
                'post_parent' => 'Parent ID | Parent Product ID , if you are importing variation Product',
                'parent_sku' => 'Parent SKU | Parent Product SKU , if you are importing variation Product',
                'parent' => 'Parent Title | Parent Product Title , if you are importing variation Product',
		'children'	=> 'Children Product ID | Linked Products id if you are importing Grouped products',
                'post_password' => 'Post Password | To Protect a post with password',
                'post_author' => 'Prodcut Author | Prodcut Author ( 1 - Admin )',
                'menu_order' => 'Menu Order | If menu enabled , menu order',
                'comment_status' => 'Comment Status | Comment Status ( Open or Closed comments for this prodcut)',
                'downloadable' => 'Type: Downloadable | Is Product is downloadable eg:- Book',
                'virtual' => 'Type: Virtual | Is Product is virtual',
                'visibility' => 'Visibility: Visibility | Visibility status ( hidden or visible)',
                'featured' => 'Visibility: Featured | Featured Product',
                'stock' => 'Inventory: Stock | Stock quantity',
                'stock_status' => 'Inventory: Stock Status | InStock or OutofStock',
                'backorders' => 'Inventory: Backorders | Backorders',
                'manage_stock' => 'Inventory: Manage Stock | yes to enable no to disable',
                'sale_price' => 'Price: Sale Price | Sale Price ',
                'regular_price' => 'Price: Regular Price | Regular Price',
                'sale_price_dates_from' => 'Sale Price Dates: From | Sale Price Dates effect from',
                'sale_price_dates_to' => 'Sale Price Dates: To | Sale Price Dates effect to',
                'weight' => 'Dimensions: Weight | Wight of product in LB , OZ , KG as of your woocommerce Unit',
                'length' => 'Dimensions: length | Length',
                'width' => 'Dimensions: width | Width',
                'height' => 'Dimensions: height | Height',
                'tax_status' => 'Tax: Tax Status | Taxable product or not',
                'tax_class' => 'Tax: Tax Class | Tax class ( eg:- reduced rate)',
                'upsell_ids' => 'Related Products: Upsell IDs | Upsell Product ids',
                'crosssell_ids' => 'Related Products: Crosssell IDs | Crosssell Product ids',
                'file_paths' => 'Downloads: File Paths (WC 2.0.x) | File Paths',
                'downloadable_files' => 'Downloads: Downloadable Files (WC 2.1.x) | Downloadable Files',
                'download_limit' => 'Downloads: Download Limit | Download Limit',
                'download_expiry' => 'Downloads: Download Expiry | Download Expiry ',
                'product_url' => 'External: Product URL | Product URL if the Product is external',
                'button_text' => 'External: Button Text | Buy button text for Product , if the Product is external',
                'images' => 'Images/Gallery | Image URLs seperated with &#124;',
                'product_page_url' => 'Product Page URL | Product Page URL ',
                'meta:total_sales' => 'meta:total_sales | Total sales for the Product',
                'tax:product_type' => 'Product Type | ( eg:- simple , variable)',
                'tax:product_cat' =>  'Product Categories | Product related categories',
                'tax:product_tag' => 'Product Tags | Product related tags',
                'tax:product_shipping_class' => 'Product Shipping Class | Allow you to group similar products for shipping',
                'tax:product_visibility' => 'Product Visibility: Featured | Featured Product',
                // YOAST
                'meta:_yoast_wpseo_focuskw' => 'meta:_yoast_wpseo_focuskw | yoast SEO',
                'meta:_yoast_wpseo_title' => 'meta:_yoast_wpseo_title | yoast SEO',
                'meta:_yoast_wpseo_metadesc' => 'meta:_yoast_wpseo_metadesc | yoast SEO',
                'meta:_yoast_wpseo_metakeywords' => 'meta:_yoast_wpseo_metakeywords | yoast SEO',

    
);


if (apply_filters('wpml_setting', false, 'setup_complete')) {

    $post_columns['wpml:language_code'] = 'wpml:language_code | WPML language code';
    $post_columns['wpml:original_product_id'] = 'wpml:original_product_id | WPML Original Product ID';
    $post_columns['wpml:original_product_sku'] = 'wpml:original_product_sku | WPML Original Product SKU';
}

return apply_filters('woocommerce_csv_product_import_reserved_fields_pair', $post_columns);