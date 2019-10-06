<?php
/**
 * WooCommerce CSV Importer class for managing parsing of CSV files.
 */
class WF_CSV_Parser_Review {

	var $row;
	var $post_type;
	var $post_defaults;		// Default post data
	var $postmeta_defaults;		// default post meta
	var $postmeta_allowed;		// post meta validation

	/**
	 * Constructor
	 */
	public function __construct( $post_type = 'product' ) {
		$this->post_type         = $post_type;
		$this->post_defaults     = include( 'data-review/data-wf-post-defaults.php' );
		$this->postmeta_defaults = include( 'data-review/data-wf-postmeta-defaults.php' );
		$this->postmeta_allowed  = include( 'data-review/data-wf-postmeta-allowed.php' );

	}

	/**
	 * Format data from the csv file
	 * @param  string $data
	 * @param  string $enc
	 * @return string
	 */
	public function format_data_from_csv( $data, $enc ) {
		return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
	}

	/**
	 * Parse the data
	 * @param  string  $file      [description]
	 * @param  string  $delimiter [description]
	 * @param  array  $mapping   [description]
	 * @param  integer $start_pos [description]
	 * @param  integer  $end_pos   [description]
	 * @return array
	 */
	public function parse_data( $file, $delimiter, $mapping, $start_pos = 0, $end_pos = null, $eval_field ) {
		// Set locale
		$enc = mb_detect_encoding( $file, 'UTF-8, ISO-8859-1', true );
		if ( $enc )
			setlocale( LC_ALL, 'en_US.' . $enc );
		@ini_set( 'auto_detect_line_endings', true );

		$parsed_data = array();
		$raw_headers = array();

		// Put all CSV data into an associative array
		if ( ( $handle = @fopen( $file, "r" ) ) !== FALSE ) {

			$header   = fgetcsv( $handle, 0, $delimiter , '"', '"'  );
			if ( $start_pos != 0 )
				fseek( $handle, $start_pos );

		    while ( ( $postmeta = fgetcsv( $handle, 0, $delimiter , '"', '"' ) ) !== FALSE ) {
	            $row = array();
				
	            foreach ( $header as $key => $heading ) {
					$s_heading = $heading;

	            	// Check if this heading is being mapped to a different field
            		if ( isset( $mapping[$s_heading] ) ) {
            				$s_heading = esc_attr( $mapping[$s_heading] );
            		}
                        foreach ($mapping as $mkey => $mvalue) {
                                if(trim($mvalue) === trim($heading)){
                                    $s_heading =  $mkey;
                                }
                        }

            		if ( $s_heading == '' )
            			continue;

	            	// Add the heading to the parsed data
					$row[$s_heading] = ( isset( $postmeta[$key] ) ) ? $this->format_data_from_csv( $postmeta[$key], $enc ) : '';

					$row[$s_heading] = $this->evaluate_field($row[$s_heading], $eval_field[$s_heading]);
					
	               	// Raw Headers stores the actual column name in the CSV
					$raw_headers[ $s_heading ] = $heading;
	            }
	            $parsed_data[] = $row;

	            unset( $postmeta, $row );

	            $position = ftell( $handle );

	            if ( $end_pos && $position >= $end_pos )
	            	break;
		    }
		    fclose( $handle );
		}
		return array( $parsed_data, $raw_headers, $position );
	}
	
	private function evaluate_field($value, $evaluation_field){
		$processed_value = $value;
		if(!empty($evaluation_field)){
			$operator = substr($evaluation_field, 0, 1);
			if(in_array($operator, array('=', '+', '-', '*', '/', '&'))){
				$eval_val = substr($evaluation_field, 1);
				switch($operator){
					case '=':
							$processed_value = trim($eval_val); 
							break;
					case '+':
							$processed_value = $this->hf_currency_formatter($value) + $eval_val; 
							break;
					case '-': 
							$processed_value = $value - $eval_val; 
							break;
					case '*': 
							$processed_value = $value * $eval_val; 
							break;
					case '/': 
							$processed_value = $value / $eval_val; 
							break;
					case '&': 
							if (strpos($eval_val, '[VAL]') !== false) {
								$processed_value = str_replace('[VAL]',$value,$eval_val);								 
							}
							else{
								$processed_value = $value . $eval_val;
							}
							break;					
				}
			}	
		}
		return $processed_value;	
	}

	/**
	 * Parse product review
	 * @param  array  $item
	 * @param  integer $merge_empty_cells
	 * @return array
	 */
	public function parse_product_review( $item, $use_sku = 0 ) {
		global $WF_CSV_Product_Review_Import, $wpdb;
		$this->row++;
		$terms_array = $postmeta = $product_review = array();
		$attributes = $default_attributes = $gpf_data = null;
		// Merging
		$merging = ( ! empty( $_GET['merge'] ) && $_GET['merge'] ) ? true : false;
		$post_id = ( ! empty( $item['comment_ID'] ) ) ? $item['comment_ID'] : 0;
		if ( $merging ) {
			$product_review['merging'] = true;
			$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', sprintf( __('> Row %s - preparing for merge.', 'wf_csv_import_export'), $this->row ) );
			
			// Required fields
			if ( ! $post_id )
			{
				$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', __( '> > Cannot merge without id. Importing instead.', 'wf_csv_import_export') );
				$merging = false;
			} 
			else 
			{
				// Check product to merge exists
				$db_query = $wpdb->prepare("
						SELECT comment_ID
						FROM $wpdb->comments
						WHERE comment_ID = %d",$post_id);
				$found_review_id = $wpdb->get_var($db_query);
				if ( ! $found_review_id ) 
				{
					$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', sprintf(__( '> > Skipped. Cannot find product reviews with ID %s. Importing instead.', 'wf_csv_import_export'), $item['comment_ID']) );
					$merging = false;
				} 
				else 
				{

					$post_id = $found_review_id;
					$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', sprintf(__( '> > Found product reviews with ID %s.', 'wf_csv_import_export'), $post_id) );
				}
			}
		}

		if ( ! $merging ) {

			$product_review['merging'] = false;
			$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', sprintf( __('> Row %s - preparing for import.', 'wf_csv_import_export'), $this->row ) );
			// Required fields
			if ( $item['comment_content'] === '')
			{
				$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', __( '> > Skipped. No comment content set for new product reviews.', 'wf_csv_import_export') );
				return new WP_Error( 'parse-error', __( 'No comment content set for new product reviews.', 'wf_csv_import_export' ) );
			}
			
			if($use_sku == 1 && $item['product_SKU'] === '')
			{
					$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', __( '> > Skipped. No Product SKU given, for which new comment is to be imported', 'wf_csv_import_export') );
					return new WP_Error( 'parse-error', __( 'Product SKU is empty, Skipped the review.', 'wf_csv_import_export' ) );
			}
			elseif ( $item['comment_post_ID'] === '' )
			{
					$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', __( '> > Skipped. No post(product) id found, for which new comment is to be imported', 'wf_csv_import_export') );
					return new WP_Error( 'parse-error', __( 'No product id found, Skipped the review.', 'wf_csv_import_export' ) );
			}
		}

		if($use_sku == 1 && $item['product_SKU'])
		{
			$temp_product_id = wc_get_product_id_by_sku( $item['product_SKU'] );
			if(! $temp_product_id)
			{
				$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', __( '> > Skipped. No Product found for given SKU, for which new comment is to be imported', 'wf_csv_import_export') );
				return new WP_Error( 'parse-error', __( 'No Product found for given SKU, Skipped the review.', 'wf_csv_import_export' ) );
			}
		}
		elseif($item['comment_post_ID'] )
		{
			$temp_post = get_post( $item['comment_post_ID'] );
			if(! $temp_post || $temp_post->post_type != 'product')
			{
				$WF_CSV_Product_Review_Import->hf_log_data_change( 'review-csv-import', __( '> > Skipped. No product found for given product id, for which new comment is to be imported', 'wf_csv_import_export') );
				return new WP_Error( 'parse-error', __( 'Post is not a product, Skipped the review.', 'wf_csv_import_export' ) );
			}
		}
			
		$product_review['post_id'] = $post_id; 
		
		// Get post fields
		foreach ( $this->post_defaults as $column => $default ) {
			if ( isset( $item[ $column ] ) )
				$product_review[ $column ] = $item[ $column ];
			if($column == 'comment_post_ID' && $use_sku == 1)
				$product_review[ $column ] = !empty($temp_product_id) ? $temp_product_id : null;
		}
		
		// Get custom fields
		foreach ( $this->postmeta_defaults as $column => $default ) {
		    
			if ( isset( $item[$column] ) )
				$postmeta[$column] = (string) $item[$column];
			elseif ( isset( $item[$column] ) )
				$postmeta[$column] = (string) $item[$column];

			// Check custom fields are valid
			if ( isset( $postmeta[$column] ) && isset( $this->postmeta_allowed[$column] ) && ! in_array( $postmeta[$column], $this->postmeta_allowed[$column] ) ) {
				$postmeta[$column] = $this->postmeta_defaults[$column];
			}
		}

		if ( ! $merging ) {
			// Merge post meta with defaults
			$product_review  = wp_parse_args( $product_review, $this->post_defaults );
			$postmeta = wp_parse_args( $postmeta, $this->postmeta_defaults );
		}
		
		// Put set core product postmeta into product array
		foreach ( $postmeta as $key => $value ) {
			$product_review['postmeta'][] = array( 'key' 	=> esc_attr($key), 'value' => $value );
		}

		/**
		 * Handle other columns
		 */
		foreach ( $item as $key => $value ) 
		{

			if ( empty($item['post_parent']) && $value == "" )
				continue;


			/**
			 * Handle meta: columns - import as custom fields
			 */
			elseif ( strstr( $key, 'meta:' ) ) {

				// Get meta key name
				$meta_key = ( isset( $WF_CSV_Product_Review_Import->raw_headers[$key] ) ) ? $WF_CSV_Product_Review_Import->raw_headers[$key] : $key;
				$meta_key = trim( str_replace( 'meta:', '', $meta_key ) );
                                
				// Add to postmeta array
				$product_review['postmeta'][] = array(
					'key' 	=> esc_attr( $meta_key ),
					'value' => $value
				);
			}

			

		}

		// Remove empty attribues
                if(!empty($attributes))
			foreach ( $attributes as $key => $value ) {
				if ( ! isset($value['name']) ) unset( $attributes[$key] );
			}

		$product_review['comment_content'] = ( ! empty( $item['comment_content'] ) ) ? $item['comment_content'] : '';
		unset( $item, $terms_array, $postmeta, $attributes, $gpf_data, $images );
                
		return $product_review;
	}
    
}