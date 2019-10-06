<?php
if ( ! defined( 'WPINC' ) ) {
	exit;
}

class WF_ProdImpExpCsv_AJAX_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_woocommerce_csv_import_request', array( $this, 'csv_import_request' ) );
		add_action( 'wp_ajax_woocommerce_csv_import_regenerate_thumbnail', array( $this, 'regenerate_thumbnail' ) );
                add_action( 'wp_ajax_product_csv_export_mapping_change', array( $this, 'export_mapping_change_columns' ) );
		add_action( 'wp_ajax_product_test_ftp_connection', array( $this, 'test_ftp_credentials' ) );
                add_action( 'wp_ajax_product_csv_export_mapping_save', array( $this, 'save_export_mapping_profile' ) );
                add_action( 'wp_ajax_product_csv_import_mapping_save', array( $this, 'save_import_mapping_profile' ) );
                add_action( 'wp_ajax_product_csv_export_mapping_delete', array( $this, 'delete_export_mapping_profile' ) );
                

	}
        
        public function save_import_mapping_profile(){

            $profile = !empty($_POST['profile_name']) ? $_POST['profile_name'] : '';            
            $update = false;
            if ($profile !== '') {
                $mapping = array();
                $eval_field = array();

                foreach ($_POST['map_from'] as $key => $value) {
                    $mapping[$this->xa_get_string_between($value['name'],'map_from[',']') ] = $value['value'];                                        
                }
                   
                foreach ($_POST['eval_field'] as $key => $value) {
                    $eval_field[$this->xa_get_string_between($value['name'],'eval_field[',']') ] = $value['value'];                                        
                }
                    
                $profile_array = get_option('wf_prod_csv_imp_exp_mapping');
                
                $profile_array[$profile] = array($mapping, $eval_field);
              
                $update = update_option('wf_prod_csv_imp_exp_mapping', $profile_array);
                
            }else{
                die("<span id= 'prod_save_mapping_msg' style = 'color : red'>".__('Enter a valid Profile name.','wf_csv_import_export')."</span>");
            }

            if( $update == TRUE ){
                die("<span id= 'prod_save_mapping_msg' style = 'color : green'>".__('Mapping profile saved.','wf_csv_import_export')."</span>"); //
            }else{
                die("<span id= 'prod_save_mapping_msg' style = 'color : red'>".__('Profile exists already.','wf_csv_import_export')."</span>");
            }

            
        }
	
        public function save_export_mapping_profile(){

            $new_profile = !empty($_POST['profile_name']) ? $_POST['profile_name'] : '';
            $update = false;
            if ($new_profile !== '') {
                $export_columns = array();
                $user_columns_name = array();

                foreach ($_POST['columns'] as $key => $value) {
                    $export_columns[$this->xa_get_string_between($value['name'],'columns[',']') ] = $value['value'];                                        
                }
                   
                foreach ($_POST['columns_name'] as $key => $value) {
                    $user_columns_name[$this->xa_get_string_between($value['name'],'columns_name[',']') ] = $value['value'];                                        
                }

                $mapped = array();
                if (!empty($export_columns)) {
                    foreach ($export_columns as $key => $value) {
                        $mapped[$key] = $user_columns_name[$key];
                    }
                }

                $export_profile_array = get_option('xa_prod_csv_export_mapping');
                $export_profile_array[$new_profile] = $mapped;                
                $update =  update_option('xa_prod_csv_export_mapping', $export_profile_array);
                
            } else {
                die("<span id= 'prod_save_mapping_msg' style = 'color : red'>".__('Enter a valid Profile name.','wf_csv_import_export')."</span>");    
            }

            if( $update == TRUE ){
                die("<span id= 'prod_save_mapping_msg' style = 'color : green'>".__('Mapping profile saved.','wf_csv_import_export')."</span>");
            }else{
                die("<span id= 'prod_save_mapping_msg' style = 'color : red'>".__('Profile exists already.','wf_csv_import_export')."</span>");
            }

            
        }
        
        
        public function delete_export_mapping_profile(){

            $profile = !empty($_POST['profile_name']) ? $_POST['profile_name'] : '';
            $update = false;
            if ($profile !== '') {

                $export_profile_array = get_option('xa_prod_csv_export_mapping');
                unset($export_profile_array[$profile]);                
                $update =  update_option('xa_prod_csv_export_mapping', $export_profile_array);
                
            } else {
                die("<span id= 'prod_delete_mapping_msg' style = 'color : red'>".__('Selected Profile is not exists.','wf_csv_import_export')."</span>");    
            }

            if( $update == TRUE ){
                die("<span id= 'prod_delete_mapping_msg' style = 'color : green'>".__('Mapping profile deleted.','wf_csv_import_export')."</span>");
            }else{
                die("<span id= 'prod_delete_mapping_msg' style = 'color : red'>".__('Selected Profile is invalid.','wf_csv_import_export')."</span>");
            }
            
        }
        
        
        
        public function xa_get_string_between($string, $start, $end) {
            $string = ' ' . $string;
            $ini = strpos($string, $start);
            if ($ini == 0)
                return '';
            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;
            return substr($string, $ini, $len);
        }

//$username = get_string_between($url, '://', ':');
        /**
	 * Ajax event for importing a CSV
	 */
	public function csv_import_request() {
		define( 'WP_LOAD_IMPORTERS', true );
                WF_ProdImpExpCsv_Importer::product_importer();
	}

	/**
	 * From regenerate thumbnails plugin
	 */
	public function regenerate_thumbnail() {
		@error_reporting( 0 ); // Don't break the JSON result

		header( 'Content-type: application/json' );

		$id    = (int) $_REQUEST['id'];
		$image = get_post( $id );

		if ( ! $image || 'attachment' != $image->post_type || 'image/' != substr( $image->post_mime_type, 0, 6 ) )
			die( json_encode( array( 'error' => sprintf( __( 'Failed resize: %s is an invalid image ID.', 'wf_csv_import_export' ), esc_html( $_REQUEST['id'] ) ) ) ) );

		if ( ! current_user_can( 'manage_woocommerce' ) )
			$this->die_json_error_msg( $image->ID, __( "Your user account doesn't have permission to resize images", 'wf_csv_import_export' ) );

		$fullsizepath = get_attached_file( $image->ID );

		if ( false === $fullsizepath || ! file_exists( $fullsizepath ) )
			$this->die_json_error_msg( $image->ID, sprintf( __( 'The originally uploaded image file cannot be found at %s', 'wf_csv_import_export' ), '<code>' . esc_html( $fullsizepath ) . '</code>' ) );

		@set_time_limit( 120 ); // 2 minutes per image should be PLENTY

		$metadata = wp_generate_attachment_metadata( $image->ID, $fullsizepath );
 
		if ( is_wp_error( $metadata ) )
			$this->die_json_error_msg( $image->ID, $metadata->get_error_message() );
		if ( empty( $metadata ) )
			$this->die_json_error_msg( $image->ID, __( 'Unknown failure reason.', 'wf_csv_import_export' ) );

		// If this fails, then it just means that nothing was changed (old value == new value)
		wp_update_attachment_metadata( $image->ID, $metadata );

		die( json_encode( array( 'success' => sprintf( __( '&quot;%1$s&quot; (ID %2$s) was successfully resized in %3$s seconds.', 'wf_csv_import_export' ), esc_html( get_the_title( $image->ID ) ), $image->ID, timer_stop() ) ) ) );
	}	

	/**
	 * Die with a JSON formatted error message
	 */
	public function die_json_error_msg( $id, $message ) {
        die( json_encode( array( 'error' => sprintf( __( '&quot;%1$s&quot; (ID %2$s) failed to resize. The error message was: %3$s', 'regenerate-thumbnails' ), esc_html( get_the_title( $id ) ), $id, $message ) ) ) );
    }
    
                    
    /**
     * Ajax event for changing mapping of export CSV
     */
    public function export_mapping_change_columns() {

        $selected_profile = !empty($_POST['v_new_profile']) ? $_POST['v_new_profile'] : '';
                
        $post_columns = include( 'exporter/data/data-wf-post-columns.php' );
        
//        if (!$selected_profile) {
//            $post_columns = include( 'exporter/data/data-wf-post-columns.php' );
//
//            $post_columns['images'] = 'Images (featured and gallery)';
//            $post_columns['file_paths'] = 'Downloadable file paths';
//            $post_columns['taxonomies'] = 'Taxonomies (cat/tags/shipping-class)';
//            $post_columns['attributes'] = 'Attributes';
//            $post_columns['meta'] = 'Meta (custom fields)';
//            $post_columns['product_page_url'] = 'Product Page URL';
//            if (function_exists('woocommerce_gpf_install'))
//                $post_columns['gpf'] = 'Google Product Feed fields';
//        }

        $export_profile_array = get_option('xa_prod_csv_export_mapping');
        $post_columns_from_saved_map = array();
        if (!empty($export_profile_array[$selected_profile])) {
            $post_columns_from_saved_map = $export_profile_array[$selected_profile];
        }

        $res = "<tr>
                      <td style='padding: 10px;'>
                          <a href='#' id='pselectall' onclick='return false;' >Select all</a> &nbsp;/&nbsp;
                          <a href='#' id='punselectall' onclick='return false;'>Unselect all</a>
                      </td>
                  </tr>
                  
                <th style='text-align: left;'>
                    <label for='v_columns'>Column</label>
                </th>
                <th style='text-align: left;'>
                    <label for='v_columns_name'>Column Name</label>
                </th>";
        
        foreach ($post_columns as $pkey => $pcolumn) {
            $tmpkey = $pkey;
            if (strpos($pkey, 'yoast') === false) {
                $tmpkey = ltrim($pkey, '_');
            }
            $checked = (array_key_exists($pkey, $post_columns_from_saved_map))?'checked' : '';
            $columns_name_val = (array_key_exists($pkey, $post_columns_from_saved_map))?$post_columns_from_saved_map[$pkey] : $tmpkey;
            $res.="<tr>
                <td>
                    <input name= 'columns[$pkey]' id= 'columns[$pkey]' type='checkbox' value='$pkey' $checked>
                    <label for='columns[$pkey]'>$pcolumn</label>
                </td>
                <td>";

            $res.="<input type='text' name='columns_name[$pkey]'  value='$columns_name_val' class='input-text' />
                </td>
            </tr>";
        }

        echo $res;
        exit;
    }
    
    /**
     * Ajax event to test FTP details
     */
    public function test_ftp_credentials(){
		$wf_prod_ftp_details			= array();
		$wf_prod_ftp_details['host']		= ! empty($_POST['ftp_host']) ? $_POST['ftp_host'] : '';
		$wf_prod_ftp_details['port']		= ! empty($_POST['ftp_port']) ? $_POST['ftp_port'] : 21;
		$wf_prod_ftp_details['userid']		= ! empty($_POST['ftp_userid']) ? $_POST['ftp_userid'] : '';
		$wf_prod_ftp_details['password']	= ! empty($_POST['ftp_password']) ? $_POST['ftp_password'] : '';
		$wf_prod_ftp_details['use_ftps']	= ! empty($_POST['use_ftps']) ? $_POST['use_ftps'] : 0;
		$ftp_conn = (!empty($wf_prod_ftp_details['use_ftps'])) ? @ftp_ssl_connect($wf_prod_ftp_details['host'], $wf_prod_ftp_details['port']) : @ftp_connect($wf_prod_ftp_details['host'], $wf_prod_ftp_details['port']);
		if($ftp_conn == false)
		{
			die("<div id= 'prod_ftp_test_msg' style = 'color : red'>Could not connect to Host. Server host / IP or Port may be wrong.</div>");
		}
		if( @ftp_login($ftp_conn,$wf_prod_ftp_details['userid'],$wf_prod_ftp_details['password']) )
		{
			die("<div id= 'prod_ftp_test_msg' style = 'color : green'>Successfully logged in.</div>");
		}
		else
		{
			die("<div id= 'prod_ftp_test_msg' style = 'color : blue'>Connected to host but could not login. Server UserID or Password may be wrong or Try with / without FTPS .</div>");
		}
    }

}

new WF_ProdImpExpCsv_AJAX_Handler();