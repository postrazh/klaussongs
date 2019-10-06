<?php

/* 
 * Common Helper functions
 */

/**
 * Helper class which contains common functions for both import and export.
 */
class wf_piep_helper {

	/**
	 * Get File name by url
	 * @param string $file_url URL of the file.
	 * @return string the base name of the given URL (File name).
	 */
	public static function xa_wc_get_filename_from_url( $file_url ) {
	    $parts = parse_url( $file_url );
	    if ( isset( $parts['path'] ) ) {
		return basename( $parts['path'] );
	    }
	}
         
        /**
	 * Get info like language code, parent product ID etc by product id.
	 * @param int Product ID.
	 * @return array/false.
	 */
        public static function wt_get_wpml_original_post_language_info($element_id){
            $get_language_args = array('element_id' => $element_id, 'element_type' => 'post_product');
            $original_post_language_info = apply_filters('wpml_element_language_details', null, $get_language_args);
            return $original_post_language_info;
        }
        
        public static function wt_get_product_id_by_sku($sku){
            global $wpdb;
            $post_exists_sku = $wpdb->get_var($wpdb->prepare("
	    		SELECT $wpdb->posts.ID
	    		FROM $wpdb->posts
	    		LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
	    		WHERE $wpdb->posts.post_status IN ( 'publish', 'private', 'draft', 'pending', 'future' )
	    		AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
	    		", $sku));   
            if ($post_exists_sku) {
                return $post_exists_sku;
            }
            return false;
            
        }
        
        /**
	 * To strip the specific string from the array key as well as value.
	 * @param array $array.
         * @param string $data.
	 * @return array.
	 */
        public static function wt_array_walk($array , $data) {
            $new_array =array();
            foreach ($array as $key => $value) {
                $new_array[str_replace($data, '', $key)] = str_replace($data, '', $value);
            }
            return $new_array;
        }

}