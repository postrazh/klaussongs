<?php
if ( ! defined( 'WPINC' ) ) {
	exit;
}

// New postmeta allowed
return array(
	'downloadable' 	=> array( 'yes', 'no' ),
	'virtual' 	=> array( 'yes', 'no' ),
	'visibility'	=> array( 'featured','visible', 'catalog', 'search', 'hidden', 'exclude-from-catalog','exclude-from-search','exclude-from-catalog|exclude-from-search' ),
	'stock_status'	=> array( 'instock', 'outofstock','onbackorder' ),
	'backorders'	=> array( 'yes', 'no', 'notify' ),
	'manage_stock'	=> array( 'yes', 'no' ),
	'tax_status'	=> array( 'taxable', 'shipping', 'none' ),
	'featured'	=> array( 'yes', 'no' ),
);