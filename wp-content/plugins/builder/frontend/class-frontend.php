<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Plugins frontend
 *
 */
class Builder_Frontend {

	/**
	 *
	 * @var shortcode name
	 */
	var $shortcode = 'round-table';

	/**
	 * add actions, filters and shortcode
	 *
	 */
	public function setup () {
		// add scripts - added to the header
		add_action( 'wp_enqueue_scripts',
			array(
				$this,
				'add_scripts'
			) );

		// enable shortcode
		add_shortcode( $this->shortcode,
			array(
				$this,
				'enable_shortcode'
			) );
	}

	/**
	 * callback to process shortcode
	 *
	 * @param array $atts
	 * 				properties set in shortcode by user
	 * @param string $content
	 *				content of the shortcode
	 * @return string - contents
	 */
	function enable_shortcode ( $atts, $content ) {
		$option = get_option('rp_settings_form_data');

		$round_image = content_url() . '/uploads/2019/09/round.png';

		$str = <<<EOD
<table class="pricetable">
	<thead>
	<tr>
		<th style="width: 30%; ">Round</th>
		<th style="width: 20%; ">Avg. Weight</th>
		<th colspan="5">Retails Starting at</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td rowspan="4" style="background: white; "><img src="{$round_image}"></td>
		<td>0.71</td>
		<td><span>$ </span>{$option['rp_field_text'][0]}</td>
		<td><span>$ </span>{$option['rp_field_text'][1]}</td>
		<td><span>$ </span>{$option['rp_field_text'][2]}</td>
		<td><span>$ </span>{$option['rp_field_text'][3]}</td>
		<td><span>$ </span>{$option['rp_field_text'][4]}</td>
	</tr>
	<tr>
		<td>1.00</td>
		<td><span>$ </span>{$option['rp_field_text'][5]}</td>
		<td><span>$ </span>{$option['rp_field_text'][6]}</td>
		<td><span>$ </span>{$option['rp_field_text'][7]}</td>
		<td><span>$ </span>{$option['rp_field_text'][8]}</td>
		<td><span>$ </span>{$option['rp_field_text'][9]}</td>
	</tr>
	<tr>
		<td>1.50</td>
		<td><span>$ </span>{$option['rp_field_text'][10]}</td>
		<td><span>$ </span>{$option['rp_field_text'][11]}</td>
		<td><span>$ </span>{$option['rp_field_text'][12]}</td>
		<td><span>$ </span>{$option['rp_field_text'][13]}</td>
		<td><span>$ </span>{$option['rp_field_text'][14]}</td>
	</tr>
	<tr>
		<td>2.00</td>
		<td><span>$ </span>{$option['rp_field_text'][15]}</td>
		<td><span>$ </span>{$option['rp_field_text'][16]}</td>
		<td><span>$ </span>{$option['rp_field_text'][17]}</td>
		<td><span>$ </span>{$option['rp_field_text'][18]}</td>
		<td><span>$ </span>{$option['rp_field_text'][19]}</td>
	</tr>
	</tbody>
</table>
EOD;
		return trim( $str );
	}

	/**
	 * register and enqueue scripts
	 *
	 */
	function add_scripts () {
		global $post;

		if ( false == isset( $post ) ) {
			return;
		}

		wp_register_style( 'rp_style', BUILDER_URL . '/css/round-price.css' );
		wp_register_script( 'rp_script', BUILDER_URL . '/js/round-price.js',
			array('jquery'), '', true );


		// enqueue script only if shortcode is present in post/page
		if ( has_shortcode( $post->post_content, $this->shortcode ) ) {

			// enqueue script, css
			wp_enqueue_style( 'rp_style' );
			wp_enqueue_script( 'rp_script' );
		}
	}
}