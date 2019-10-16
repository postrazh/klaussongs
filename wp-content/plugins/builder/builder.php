<?php

/**
 * Plugin Name: Builder
 * Description: This is a badass plugin
 * Version: 1.0.1 
 * Author: Ali
 */

defined( 'ABSPATH' ) or die( "Access denied !" );


/**
 * 
 * @var plugin URL
 */
define( "BUILDER_URL", trailingslashit( plugin_dir_url( __FILE__ ) ) );


/**
 *
 * @var plugin path
 */
define( "BUILDER_PATH", plugin_dir_path( __FILE__ ) );

// entry point
setup_builder_plugin();

/**
 * init plugin either in admin mode or as frontend
 *
 */
function setup_builder_plugin() {
	if ( is_admin() ) {

		require_once BUILDER_PATH . 'admin/class-admin.php';
		add_action( 'plugins_loaded', 'load_builder_admin' );
	} else {

		require_once BUILDER_PATH . 'frontend/class-frontend.php';
		add_action( 'plugins_loaded', 'load_builder_frontend' );
	}
}

/**
 * load and setup Rt_Admin class
 *
 */
function load_builder_admin() {
	$rt_admin = new Builder_Admin();
	$rt_admin->setup();
}

/**
 * load and setup Rt_Frontend class
 *
 */
function load_builder_frontend() {
	$rt_frontend = new Builder_Frontend();
	$rt_frontend->setup();
}