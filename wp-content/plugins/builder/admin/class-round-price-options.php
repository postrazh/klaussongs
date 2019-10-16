<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

class Builder_RoundPriceOptions {

	/**
	 * initializes the Round Price modules
	 *
	 */
	function setup () {
		/* Register Use Settings */
		add_action( 'admin_init', 
			array(
				$this,
				'init_common_options' 
			) );

		/* Add Admin Page */
		add_action( 'admin_menu', 
			array(
				$this,
				'register_settings_page'
			) );

		// add scripts - added to the header
		add_action( 'admin_enqueue_scripts',
			array(
				$this,
				'add_scripts'
			) );
	}

	
	function init_common_options() {
	    register_setting(
	        'rp_settings_form', // A settings group name.
	        'rp_settings_form_data' //The name of an option to sanitize and save.
	    );

	    add_settings_section( 'rp_section_general',
	        '',
	        '' ,
	        'rp_common_options'
	    );

	    add_settings_field( 'rp_field_text',
	        "Round Price",
	        array(
	        	$this,
	        	'rp_field_output'
        	),
	        'rp_common_options',
	        'rp_section_general'
	    );

	}

	function register_settings_page() {
	    add_menu_page( 'Round Price Table',
	        'Round Price',
	        'manage_options',
	        'rp-panel',
	        array(
	        	$this,
	        	'render_settings_page'
	        ) );
	}

	function rp_field_output() {
	    $options = get_option( 'rp_settings_form_data' );
	    ?>

	    <?php
		    $round_image = content_url() . '/uploads/2019/09/round.png';
	    ?>

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
				<td rowspan="4"><img src="<?php echo $round_image; ?>"></td>
				<td>0.71</td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][0]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][1]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][2]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][3]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][4]; ?>'></td>
			</tr>
			<tr>
				<td>1.00</td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][5]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][6]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][7]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][8]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][9]; ?>'></td>
			</tr>
			<tr>
				<td>1.50</td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][10]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][11]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][12]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][13]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][14]; ?>'></td>
			</tr>
			<tr>
				<td>2.00</td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][15]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][16]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][17]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][18]; ?>'></td>
				<td><input type='text' name='rp_settings_form_data[rp_field_text][]' value='<?php echo $options['rp_field_text'][19]; ?>'></td>
			</tr>
			</tbody>
		</table>

	    <?php

	}

	function render_settings_page() {
	    ?>
	    <div class="inner-panel">
	        <h3>Settings</h3>
	        <form id="rp-panel" method="post" action="options.php">
	            <?php
	            settings_fields( 'rp_settings_form' );
	            ?>

	            <?php
	            do_settings_sections( 'rp_common_options' );
	            ?>

	            <?php
	            submit_button( 'Save' );
	            ?>
	        </form>
	    </div>
	    <?php
	}

	function add_scripts () {
		wp_register_style( 'rp_style', BUILDER_URL . '/assets/css/round-price.css' );
		wp_register_script( 'rp_script', BUILDER_URL . '/assets/js/round-price.js',
			array('jquery'), '', true );
		
		// enqueue script, css
		wp_enqueue_style( 'rp_style' );
		wp_enqueue_script( 'rp_script' );

	}
}

