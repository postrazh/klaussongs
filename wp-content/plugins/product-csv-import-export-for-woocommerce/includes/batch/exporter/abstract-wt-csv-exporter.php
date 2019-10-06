<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


abstract class WT_pipe_Exporter {

	protected $export_type = '';
	protected $filename = 'wt-product-export.csv';
	protected $limit = 100;
	protected $exported_row_count = 0;
	protected $row_data = array();
	protected $total_rows = 0;
	protected $column_names = array();
	protected $columns_to_export = array();
        protected $product_taxonomies = array();
        protected $all_meta_keys = array();
        protected $product_attributes = array();
        protected $exclude_hidden_meta_columns = array();
        protected $found_product_meta = array();
        protected $enable_meta_export = false;
        
        



        /**
	 * Prepare data that will be exported.
	 */
	abstract public function prepare_data_to_export();

	/**
	 * Return an array of supported column names and ids.
	 */
	public function get_column_names() {
		return apply_filters( "wt_batch_export_column_names", $this->column_names, $this );
	}

	public function wt_set_column_names( $column,$column_names ) { //WT Custom functions

                foreach ($column as $value) {
                    $selected_column[$value['value']]=$value['value'];                
                }

                foreach ($column_names as $value) {
                    $column_name_suggested[substr($value['name'], 13,-1)]=$value['value'];
                }

		$this->column_names = array();
                
		foreach ( $selected_column as $column_key => $column_name ) {                                        
			$this->column_names[ wc_clean( $column_key ) ] = wc_clean( $column_name_suggested[$column_key] );
		}

	}
        
        public function set_column_names( $column_names ) {

		$this->column_names = array();
                
		foreach ( $column_names as $column_id => $column_name ) {
			$this->column_names[ wc_clean( $column_id ) ] = wc_clean( $column_name );
		}
                
	}

	public function get_columns_to_export() {
            
		return $this->columns_to_export;
	}

	public function set_columns_to_export( $columns ) {
		$this->columns_to_export = array_map( 'wc_clean', $columns );
	}

	/**
	 * See if a column is to be exported or not.
	 */
	public function is_column_exporting( $column_id ) {
            
		$column_id         = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
		$columns_to_export = $this->get_columns_to_export();

		if ( empty( $columns_to_export ) ) {
			return true;
		}

		if ( in_array( $column_id, $columns_to_export, true ) || 'meta' === $column_id ) {
			return true;
		}

		return false;
	}

	public function get_default_column_names() {
            echo 'reched WT_pipe_Exporter::get_default_column_names';
		return array();
	}


	public function export() {
            echo 1234567;die;
            
		$this->prepare_data_to_export();
		$this->send_headers();
                
                // Add BOM - Byte Order Mark
		$this->send_content( chr( 239 ) . chr( 187 ) . chr( 191 ) . $this->export_column_headers() . $this->get_csv_data() );
		die();
	}

	public function send_headers() {
            
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}
		@ini_set( 'zlib.output_compression', 'Off' );
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'output_handler', '' );
		ignore_user_abort( true );
		wc_set_time_limit( 0 );
		wc_nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	public function set_filename( $filename ) {
            
		$this->filename = sanitize_file_name( str_replace( '.csv', '', $filename ) . '.csv' );
	}

	public function get_filename() {
            
		return sanitize_file_name( apply_filters( "woocommerce_{$this->export_type}_export_get_filename", $this->filename ) );
	}

	public function send_content( $csv_data ) {
            
		echo $csv_data;
	}

	protected function get_csv_data() {
            
		return $this->export_rows();
	}

	protected function export_column_headers() {
            
		$columns    = $this->get_column_names();

                
//                $columns['images'] = 'images';
//                $columns['file_paths'] = 'file_paths';
//                $columns['taxonomies'] = 'taxonomies';
//                $columns['attributes'] = 'attributes';
//                $columns['meta'] = 'meta';
//                $columns['product_page_url'] = 'product_page_url';
                
                
                $product_taxonomies = $this->wt_get_product_taxonomies();
                $found_attributes = $this->wt_get_product_attributes();
                $found_product_meta = $this->wt_get_found_product_meta();
                
		$export_row = array();
		$buffer     = fopen( 'php://output', 'w' );
		ob_start();

		foreach ( $columns as $column_id => $column_name ) {
//			if ( ! $this->is_column_exporting( $column_id ) ) {
//				continue;
//			}
                    
                    
                    if ('taxonomies' == $column_id) {
                        foreach ($product_taxonomies as $taxonomy) {
                            if (strstr($taxonomy->name, 'pa_'))
                                continue; // Skip attributes

                            $export_row[] = 'tax:' . self::format_data($taxonomy->name);
                        }
                        continue;
                    }

                    if ('meta' == $column_id) {
                        foreach ($found_product_meta as $product_meta) {
                            $export_row[] = 'meta:' . self::format_data($product_meta);
                        }
                        continue;
                    }

                    if ('attributes' == $column_id) {
                        foreach ($found_attributes as $attribute) {
                            $export_row[] = 'attribute:' . self::format_data($attribute);
                            $export_row[] = 'attribute_data:' . self::format_data($attribute);
                            $export_row[] = 'attribute_default:' . self::format_data($attribute);
                        }
                        continue;
                    }
                    
                    
                    
			$export_row[] = $this->format_data( $column_name );
		}

		$this->fputcsv( $buffer, $export_row );

		return ob_get_clean();
	}

	protected function get_data_to_export() {
		return $this->row_data;
	}
        
	protected function export_rows() {
		$data   = $this->get_data_to_export();
		$buffer = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		ob_start();

		array_walk( $data, array( $this, 'export_row' ), $buffer );

		return apply_filters( "woocommerce_{$this->export_type}_export_rows", ob_get_clean(), $this );
	}

	protected function export_row( $row_data, $key, $buffer ) {
            
            
                $product_taxonomies = $this->wt_get_product_taxonomies();
                $found_attributes = $this->wt_get_product_attributes();
                $found_product_meta = $this->wt_get_found_product_meta();
            
		$columns    = $this->get_column_names();
                $columns = wf_piep_helper::wt_array_walk($columns,'meta:'); // Remove string 'meta:' from keys and values, YOAST support


		$export_row = array();

		foreach ( $columns as $column_id => $column_name ) {
//			if ( ! $this->is_column_exporting( $column_id ) ) {
//				continue;
//			}
                    
                    
                    
                        if ('taxonomies' == $column_id) {
                            foreach ($product_taxonomies as $taxonomy) {
                                if (strstr($taxonomy->name, 'pa_'))
                                    continue; // Skip attributes

                                $export_row[] = $row_data['tax:' . self::format_data($taxonomy->name)];
                            }
                            continue;
                        }

                        if ('meta' == $column_id) {
                            foreach ($found_product_meta as $product_meta) {
                                $export_row[] = $row_data['meta:' . self::format_data($product_meta)];
                            }
                            continue;
                        }

                        if ('attributes' == $column_id) {
                            foreach ($found_attributes as $attribute) {
                                $export_row[] = $row_data['attribute:' . self::format_data($attribute)];
                                $export_row[] = $row_data['attribute_data:' . self::format_data($attribute)];
                                $export_row[] = $row_data['attribute_default:' . self::format_data($attribute)];
                            }
                            continue;
                        }
                    
                    
			if ( isset( $row_data[ $column_id ] ) ) {
				$export_row[] = $this->format_data( $row_data[ $column_id ] );
			} else {
				$export_row[] = '';
			}
		}

		$this->fputcsv( $buffer, $export_row );

		++ $this->exported_row_count;
	}


	public function get_limit() {
            
		return apply_filters( "wt_export_batch_limit", $this->limit, $this );
	}

	public function set_limit( $limit ) {
		$this->limit = absint( $limit );
	}

	public function get_total_exported() {
		return $this->exported_row_count;
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * Malicious input can inject formulas into CSV files, opening up the possibility
	 * for phishing attacks and disclosure of sensitive information.
	 *
	 * Additionally, Excel exposes the ability to launch arbitrary commands through
	 * the DDE protocol.
	 *
	 * @see http://www.contextis.com/resources/blog/comma-separated-vulnerabilities/
	 * @see https://hackerone.com/reports/72785
	 *
	 */
	public function escape_data( $data ) {
            
		$active_content_triggers = array( '=', '+', '-', '@' );

		if ( in_array( mb_substr( $data, 0, 1 ), $active_content_triggers, true ) ) {
			$data = "'" . $data . "'";
		}

		return $data;
	}

	public function format_data( $data ) {
            
		if ( ! is_scalar( $data ) ) {
			if ( is_a( $data, 'WC_Datetime' ) ) {
				$data = $data->date( 'Y-m-d G:i:s' );
			} else {
				$data = ''; // Not supported.
			}
		} elseif ( is_bool( $data ) ) {
			$data = $data ? 1 : 0;
		}

		$use_mb = function_exists( 'mb_convert_encoding' );
		$data   = (string) urldecode( $data );

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			$data     = 'UTF-8' === $encoding ? $data : utf8_encode( $data );
		}

		return $this->escape_data( $data );
	}

	/**
	 * Format term ids to names.
	 *
	 */
	public function format_term_ids( $term_ids, $taxonomy ) {
            
		$term_ids = wp_parse_id_list( $term_ids );

		if ( ! count( $term_ids ) ) {
			return '';
		}

		$formatted_terms = array();

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			foreach ( $term_ids as $term_id ) {
				$formatted_term = array();
				$ancestor_ids   = array_reverse( get_ancestors( $term_id, $taxonomy ) );

				foreach ( $ancestor_ids as $ancestor_id ) {
					$term = get_term( $ancestor_id, $taxonomy );
					if ( $term && ! is_wp_error( $term ) ) {
						$formatted_term[] = $term->name;
					}
				}

				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$formatted_term[] = $term->name;
				}

				$formatted_terms[] = implode( ' > ', $formatted_term );
			}
		} else {
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$formatted_terms[] = $term->name;
				}
			}
		}

		return $this->implode_values( $formatted_terms );
	}


	protected function implode_values( $values ) {
            
		$values_to_implode = array();

		foreach ( $values as $value ) {
			$value               = (string) is_scalar( $value ) ? $value : '';
			$values_to_implode[] = str_replace( ',', '\\,', $value );
		}

		return implode( ', ', $values_to_implode );
	}

	/**
	 * Write to the CSV file, ensuring escaping works across versions of
	 * PHP.
	 *
	 * PHP 5.5.4 uses '\' as the default escape character. This is not RFC-4180 compliant.
	 * \0 disables the escape character.
	 *
	 * @see https://bugs.php.net/bug.php?id=43225
	 * @see https://bugs.php.net/bug.php?id=50686
	 * @see https://github.com/woocommerce/woocommerce/issues/19514
         *
	 */
	protected function fputcsv( $buffer, $export_row ) {
            
		if ( version_compare( PHP_VERSION, '5.5.4', '<' ) ) {
			ob_start();
			$temp = fopen( 'php://output', 'w' );
    		fputcsv( $temp, $export_row, ",", '"' );
			fclose( $temp );
			$row = ob_get_clean();
			$row = str_replace( '\\"', '\\""', $row );
			fwrite( $buffer, $row );
		} else {
			fputcsv( $buffer, $export_row, ",", '"', "\0" );
		}
	}
        
        
        /* WT Custom functions  */
        public function wt_set_product_taxonomies() {
            $product_ptaxonomies = get_object_taxonomies('product', 'name');
            $product_vtaxonomies = get_object_taxonomies('product_variation', 'name');
            $product_taxonomies = array_merge($product_ptaxonomies, $product_vtaxonomies);

            $this->product_taxonomies = $product_taxonomies;
        }

        public function wt_get_product_taxonomies() {
            return $this->product_taxonomies;
        }
        
        public function wt_set_all_meta_keys() {
            
            $all_meta_pkeys = WT_Batch_CSV_Exporter::get_all_metakeys('product');
            $all_meta_vkeys = WT_Batch_CSV_Exporter::get_all_metakeys('product_variation');
            $all_meta_keys = array_merge($all_meta_pkeys, $all_meta_vkeys);
            $all_meta_keys = array_unique($all_meta_keys);

            $this->all_meta_keys = $all_meta_keys;
        }

        public function wt_get_all_meta_keys() {
            return $this->all_meta_keys;
        }

        public function wt_set_product_attributes () {
          
            $found_pattributes = WT_Batch_CSV_Exporter::get_all_product_attributes('product');
            $found_vattributes = WT_Batch_CSV_Exporter::get_all_product_attributes('product_variation');
            $found_attributes = array_merge($found_pattributes, $found_vattributes);
            $found_attributes = array_unique($found_attributes);

            $this->product_attributes  = $found_attributes;
        }

        public function wt_get_product_attributes () {
            return $this->product_attributes ;
        }
        
        public function wt_set_exclude_hidden_meta_columns () {
          
            $exclude_hidden_meta_columns = include( WT_PIPE_BASE_PATH.'includes/exporter/data/data-wf-hidden-meta-columns.php' );

            $this->exclude_hidden_meta_columns  = $exclude_hidden_meta_columns;
        }

        public function wt_get_exclude_hidden_meta_columns () {
            return $this->exclude_hidden_meta_columns ;
        }

        
        public function wt_set_found_product_meta() {

        // Loop products and load meta data
            $found_product_meta = array();
            // Some of the values may not be usable (e.g. arrays of arrays) but the worse
            // that can happen is we get an empty column.
            
            
            $all_meta_keys = $this->wt_get_all_meta_keys();
            $csv_columns = $this->get_column_names();
            $exclude_hidden_meta_columns = $this->wt_get_exclude_hidden_meta_columns();
            $include_hidden_meta = $this->enable_meta_export;
            foreach ($all_meta_keys as $meta) {
                if (!$meta)
                    continue;
                if (!$include_hidden_meta && !in_array($meta, array_keys($csv_columns)) && substr((string) $meta, 0, 1) == '_')
                    continue;
                if ($include_hidden_meta && ( in_array($meta, $exclude_hidden_meta_columns) || in_array($meta, array_keys($csv_columns))|| in_array('meta:'.$meta, array_keys($csv_columns)) ))
                    continue;
                $found_product_meta[] = $meta;
            }

            $found_product_meta = array_diff($found_product_meta, array_keys($csv_columns));

            $this->found_product_meta = $found_product_meta;
        }

        public function wt_get_found_product_meta() {
            return $this->found_product_meta;
        }
        
        /**
	 * should meta be exported?
	 */
	public function enable_meta_export( $enable_meta_export ) {
            
		$this->enable_meta_export = (bool) $enable_meta_export;
	}

}
