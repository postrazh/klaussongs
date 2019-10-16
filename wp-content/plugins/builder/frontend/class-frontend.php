<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

require_once BUILDER_PATH . 'frontend/class-round-price.php';

/**
 * Plugins frontend
 *
 */
class Builder_Frontend {

	/**
	 * add actions, filters and shortcode
	 *
	 */
	public function setup () {
		$round_price = new Builder_RoundPrice();
		$round_price->setup();
	}
}