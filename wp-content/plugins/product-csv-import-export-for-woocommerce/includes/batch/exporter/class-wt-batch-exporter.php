<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WT_Batch_Exporter {


	protected $exporters = array();

	public function __construct() {


		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
		add_action( 'admin_head', array( $this, 'hide_from_menus' ) );
		
		add_action( 'admin_init', array( $this, 'download_export_file' ) );
		add_action( 'wp_ajax_wt_batch_product_export', array( $this, 'batch_product_export' ) );

		$this->exporters['product_exporter'] = array(
			'menu'       => 'edit.php?post_type=product',
			'name'       => __( 'Product Export', 'woocommerce' ),
			'capability' => 'export',
			'callback'   => array( $this, 'product_exporter' ),
		);
	}

        public function has_export_permision(){
            
                $current_user = wp_get_current_user();
                $wt_roles = apply_filters('wt_export_permission_roles', array('administrator', 'shop_manager'));
                
                $user_has_previlege = false;
                
                if ($current_user instanceof WP_User) {
                    $users_can = array_intersect($wt_roles, $current_user->roles);
                    if (!empty($users_can)) {
                        $user_has_previlege = true;
                    }
                }
                return $user_has_previlege;
        }


	public function add_to_menus() {
            
		foreach ( $this->exporters as $id => $exporter ) {
			add_submenu_page( $exporter['menu'], $exporter['name'], $exporter['name'], $exporter['capability'], $id, $exporter['callback'] );
		}
	}


	public function hide_from_menus() {
            
		global $submenu;

		foreach ( $this->exporters as $id => $exporter ) {
			if ( isset( $submenu[ $exporter['menu'] ] ) ) {
				foreach ( $submenu[ $exporter['menu'] ] as $key => $menu ) {
					if ( $id === $menu[2] ) {
						unset( $submenu[ $exporter['menu'] ][ $key ] );
					}
				}
			}
		}
	}


	public function product_exporter() {
		include_once  'class-wt-product-csv-exporter.php';
	}


	public function download_export_file() {
            
		if ( isset( $_GET['action'], $_GET['nonce'] ) && wp_verify_nonce( wp_unslash( $_GET['nonce'] ), 'product-csv' ) && 'download_product_csv' === wp_unslash( $_GET['action'] ) ) {
			
                        include_once 'class-wt-product-csv-exporter.php';
			$exporter = new WT_Batch_CSV_Exporter();

			if ( ! empty( $_GET['filename'] ) ) {
				$exporter->set_filename( wp_unslash( $_GET['filename'] ) );
			}

			$exporter->export();
		}
	}


        
	public function batch_product_export() {
               
		check_ajax_referer( 'wt-batch-export', 'security' );
               

		if ( ! $this->has_export_permision() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient privilege to export products.', 'wf_csv_import_export' ) ) );
		}

		include_once  'class-wt-product-csv-exporter.php';
                require_once(dirname(dirname(dirname(__FILE__))) . '/class-wf-piep-helper.php');


		$step     = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;
		$exporter = new WT_Batch_CSV_Exporter();
    
                if ( ! empty( $_POST['columns'] ) ) {
			$exporter->wt_set_column_names( wp_unslash( $_POST['columns']), wp_unslash( $_POST['columns_name']) );
		}

//		if ( ! empty( $_POST['selected_columns'] ) ) {
//                        echo 123;die;
//			$exporter->set_columns_to_export( wp_unslash( $_POST['selected_columns'] ) );
//		}

                if ( ! empty( $_POST['include_hidden_meta'] ) ) {
			$exporter->enable_meta_export( true );
		}

		if ( ! empty( $_POST['filename'] ) ) {
			$exporter->set_filename( wp_unslash( $_POST['filename'] ) );
		}
                
                if ( ! empty( $_POST['batch_count'] ) ) {
			$exporter->set_limit( wp_unslash( $_POST['batch_count'] ) );
		}

                
                $exporter->wt_set_product_taxonomies();
                $exporter->wt_set_all_meta_keys();
                $exporter->wt_set_product_attributes();
                $exporter->wt_set_exclude_hidden_meta_columns();
                $exporter->wt_set_found_product_meta();
                                
		$exporter->set_page( $step );
		$exporter->generate_file();

		$query_args = apply_filters(
			'wt_batch_export_download_product_csv_args',
			array(
				'nonce'    => wp_create_nonce( 'product-csv' ),
				'action'   => 'download_product_csv',
				'filename' => $exporter->get_filename(),
			)
		);

		if ( 100 === $exporter->get_percent_complete() ) {
                    
			wp_send_json_success(
				array(
					'step'       => 'done',
					'percentage' => 100,
					'url'        => add_query_arg( $query_args, admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
				)
			);
                        
		} else {
                    
			wp_send_json_success(
				array(
					'step'       => ++$step,
					'percentage' => $exporter->get_percent_complete(),
					'columns'    => $exporter->get_column_names(),
				)
			);
                        
		}
	}
}

new WT_Batch_Exporter();