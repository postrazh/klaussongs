<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

require_once BUILDER_PATH . 'admin/class-round-price-options.php';

class Builder_Admin {

	/**
	 * initializes the admin modules
	 *
	 */
	function setup () {
		$round_price_options = new Builder_RoundPriceOptions();
		$round_price_options->setup();
	}

}



