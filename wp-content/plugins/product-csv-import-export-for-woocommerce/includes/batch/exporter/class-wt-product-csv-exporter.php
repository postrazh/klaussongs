<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WT_pipe_Batch_Exporter', false ) ) {
	include_once  'abstract-wt-csv-batch-exporter.php';
}


class WT_Batch_CSV_Exporter extends WT_pipe_Batch_Exporter {


	protected $export_type = 'product';
//	protected $enable_meta_export = false;


	public function __construct() {
            
		parent::__construct();
	}


        public static function format_export_meta($meta_value, $meta) {
            
            switch ($meta) {
                case '_sale_price_dates_from' :
                case '_sale_price_dates_to' :
                    return $meta_value ? date('Y-m-d', $meta_value) : '';
                    break;
                case '_upsell_ids' :
                case '_crosssell_ids' :
                    return implode('|', array_filter((array) json_decode($meta_value)));
                    break;
                default :
                    return $meta_value;
                    break;
            }
        }
        
        

	/**
	 * Return an array of columns to export.
	 */
        
	public function get_default_column_names() {
            
                $csv_columns = include( WT_PIPE_BASE_PATH.'includes/exporter/data/data-wf-post-columns.php' );
		return apply_filters( "wt_product_export_default_columns", $csv_columns);
	}

        
        
        /**
	 * should meta be exported?
	 */
//	public function enable_meta_export( $enable_meta_export ) {
//            
//		$this->enable_meta_export = (bool) $enable_meta_export;
//	}
        
	/**
	 * Prepare data for export.
	 */
	public function prepare_data_to_export() {
                        
            
            $types = array_merge( array_keys( wc_get_product_types() ), array( 'variation' ) ); // 'simple','grouped','external','variable','subscription','variable-subscription','variation',

            $args = array(
			'status'   => array( 'private', 'publish', 'draft', 'future', 'pending' ),
                        'type' => $types,
			'limit'    => $this->get_limit(),
			'page'     => $this->get_page(),
			'orderby'  => array(
				'ID' => 'ASC',
			),
//			'return'   => 'ids',
                        'return' => 'objects',
			'paginate' => true,
		);

		$products = wc_get_products( apply_filters( "woocommerce_product_export_{$this->export_type}_query_args", $args ) );

		$this->total_rows  = $products->total;
		$this->row_data    = array();
		$variable_products = array();

		foreach ( $products->products as $product ) {    
                    
			// Check if the category is set, this means we need to fetch variations seperately as they are not tied to a category.
//			if ( ! empty( $args['category'] ) && $product->is_type( 'variable' ) ) {
//			if ( $product->is_type( 'variable' ) ) {
//                            
//				$variable_products[] = $product->get_id();
//			}
			$this->row_data[] = $this->generate_row_data( $product );
		}

                
		// If a category was selected we loop through the variations as they are not tied to a category so will be excluded by default.
		if ( ! empty( $variable_products ) ) {
			foreach ( $variable_products as $parent_id ) {
				$products = wc_get_products( array(
					'parent' => $parent_id,
					'type'   => array( 'variation' ),
					'return' => 'objects',
					'limit'  => -1,
				) );

				if ( ! $products ) {
					continue;
				}

				foreach ( $products as $product ) {
					$this->row_data[] = $this->generate_row_data( $product );
				}
			}
		}
	}


	protected function generate_row_data($product_object) {
                    
        $product = get_post($product_object->get_id());
        
        $csv_columns = $this->get_column_names();
        $csv_columns = wf_piep_helper::wt_array_walk($csv_columns,'meta:'); // Remove string 'meta:' from keys and values, YOAST support


//        $exclude_hidden_meta_columns = include( WT_PIPE_BASE_PATH.'includes/exporter/data/data-wf-hidden-meta-columns.php' );
        $exclude_hidden_meta_columns = $this->wt_get_exclude_hidden_meta_columns();

//        $csv_columns['images'] = 'images';
//        $csv_columns['file_paths'] = 'file_paths';
//        $csv_columns['taxonomies'] = 'taxonomies';
//        $csv_columns['attributes'] = 'attributes';
//        $csv_columns['meta'] = 'meta';
//        $csv_columns['product_page_url'] = 'product_page_url';


        $export_columns = !empty($csv_columns) ? $csv_columns : array();
        
        $include_hidden_meta = $this->enable_meta_export;
        $export_shortcodes = !empty($_POST['v_export_do_shortcode']) ? true : false;

//        $product_ptaxonomies = get_object_taxonomies('product', 'name');
//        $product_vtaxonomies = get_object_taxonomies('product_variation', 'name');
//        $product_taxonomies = array_merge($product_ptaxonomies, $product_vtaxonomies);

        $product_taxonomies = $this->wt_get_product_taxonomies();


        // Headers
//        $all_meta_pkeys = self::get_all_metakeys('product');
//        $all_meta_vkeys = self::get_all_metakeys('product_variation');
//        $all_meta_keys = array_merge($all_meta_pkeys, $all_meta_vkeys);
//        $all_meta_keys = array_unique($all_meta_keys);
        
//        $all_meta_keys = $this->wt_get_all_meta_keys();


//        $found_pattributes = self::get_all_product_attributes('product');
//        $found_vattributes = self::get_all_product_attributes('product_variation');
//        $found_attributes = array_merge($found_pattributes, $found_vattributes);
//        $found_attributes = array_unique($found_attributes);
        
        $found_attributes = $this->wt_get_product_attributes();

//        // Loop products and load meta data
//        $found_product_meta = array();
//        // Some of the values may not be usable (e.g. arrays of arrays) but the worse
//        // that can happen is we get an empty column.
//        foreach ($all_meta_keys as $meta) {
//            if (!$meta)
//                continue;
//            if (!$include_hidden_meta && !in_array($meta, array_keys($csv_columns)) && substr((string) $meta, 0, 1) == '_')
//                continue;
//            if ($include_hidden_meta && ( in_array($meta, $exclude_hidden_meta_columns) || in_array($meta, array_keys($csv_columns)) ))
//                continue;
//            $found_product_meta[] = $meta;
//        }
//
//        $found_product_meta = array_diff($found_product_meta, array_keys($csv_columns));
//        
        $found_product_meta = $this->wt_get_found_product_meta();
        
        $row = array();

        if ($product->post_parent == 0)
            $product->post_parent = '';
        $row = array();

        // Pre-process data
        $meta_data = get_post_custom($product->ID);

        $product->meta = new stdClass;
        $product->attributes = new stdClass;
        // Meta data
        foreach ($meta_data as $meta => $value) {

            if (!$meta) {
                continue;
            }

            if (!$include_hidden_meta && !in_array($meta, array_keys($csv_columns)) && substr($meta, 0, 1) == '_') {   //skipping _wc_
                continue;
            }

            if ($include_hidden_meta && in_array($meta, $exclude_hidden_meta_columns)) {
                continue;
            }

            $meta_value = maybe_unserialize(maybe_unserialize($value[0]));


            if (is_array($meta_value)) {
                $meta_value = json_encode($meta_value);
            }

            if (strstr($meta, 'attribute_pa_')) {
                if ($meta_value) {
                    $get_name_by_slug = get_term_by('slug', $meta_value, str_replace('attribute_', '', $meta));
                    if ($get_name_by_slug) {
                        $product->meta->$meta = self::format_export_meta($get_name_by_slug->name, $meta);
                    } else {
                        $product->meta->$meta = self::format_export_meta($meta_value, $meta);
                    }
                } else {
                    $product->meta->$meta = self::format_export_meta($meta_value, $meta);
                }
            } else {
                $product->meta->$meta = self::format_export_meta($meta_value, $meta);
            }
        }
        // Product attributes
        if (isset($meta_data['_product_attributes'][0])) {

            $attributes = maybe_unserialize(maybe_unserialize($meta_data['_product_attributes'][0]));

            if (!empty($attributes) && is_array($attributes)) {
                foreach ($attributes as $key => $attribute) {
                    if (!$key) {
                        continue;
                    }

                    if ($attribute['is_taxonomy'] == 1) {
                        $terms = wp_get_post_terms($product->ID, $key, array("fields" => "names"));
                        if (!is_wp_error($terms)) {
                            $attribute_value = implode('|', $terms);
                        } else {
                            $attribute_value = '';
                        }
                    } else {
                        if (empty($attribute['name'])) {
                            continue;
                        }
                        $key = $attribute['name'];
                        $attribute_value = $attribute['value'];
                    }

                    if (!isset($attribute['position'])) {
                        $attribute['position'] = 0;
                    }
                    if (!isset($attribute['is_visible'])) {
                        $attribute['is_visible'] = 0;
                    }
                    if (!isset($attribute['is_variation'])) {
                        $attribute['is_variation'] = 0;
                    }

                    $attribute_data = $attribute['position'] . '|' . $attribute['is_visible'] . '|' . $attribute['is_variation'];
                    $_default_attributes = isset($meta_data['_default_attributes'][0]) ? maybe_unserialize(maybe_unserialize($meta_data['_default_attributes'][0])) : '';

                    if (is_array($_default_attributes)) {
                        $_default_attribute = isset($_default_attributes[$key]) ? $_default_attributes[$key] : '';
                    } else {
                        $_default_attribute = '';
                    }

                    $product->attributes->$key = array(
                        'value' => $attribute_value,
                        'data' => $attribute_data,
                        'default' => $_default_attribute
                    );
                }
            }
        }
//        // GPF
//        if (isset($meta_data['_woocommerce_gpf_data'][0])) {
//            $product->gpf_data = $meta_data['_woocommerce_gpf_data'][0];
//        }
//
//        if ($product->post_parent) {
//
//            $post_parent_title = get_the_title($product->post_parent);
//
//            if ($post_parent_title) {
//                $row['post_parent'] = self::format_data($post_parent_title);
//                $parent_sku = get_post_meta($product->post_parent, '_sku', true);
//                $row['parent_sku'] = $parent_sku;
//            } else {
//                $row['post_parent'] = '';
//                $row['parent_sku'] = '';
//            }
//        } else {
//            $row['post_parent'] = '';
//            $row['parent_sku'] = '';
//        }
        
        
//        echo '<pre>$csv_columns:-';
//        print_r($csv_columns);
//        echo '</pre>';

        foreach ($csv_columns as $column => $value) {

            if (!$export_columns || in_array($value, $export_columns) || in_array($column, $export_columns)) {
                if ('_regular_price' == $column && empty($product->meta->$column)) {
                    $column = '_price';
                }
                if (!WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
                    if ('_visibility' == $column) {
                        $product_terms = get_the_terms($product->ID, 'product_visibility');
                        if (!empty($product_terms)) {
                            if (!is_wp_error($product_terms)) {
                                $term_slug = '';
                                foreach ($product_terms as $i => $term) {
                                    $term_slug .= $term->slug . (isset($product_terms[$i + 1]) ? '|' : '');
                                }
                                $row[$column] = $term_slug;
                            }
                        } else {
                            $row[$column] = '';
                        }
                        continue;
                    }
                }
                
                
                if ( 'Parent' == $column ) {
                    if ($product->post_parent) {
                        $post_parent_title = get_the_title($product->post_parent);
                        if ($post_parent_title) {
                            $row[$column] = self::format_data($post_parent_title);                                    
                        } else {
                            $row[$column] = '';                                    
                        }
                    } else {
                        $row[$column] = '';                                
                    }
                    continue;
                }

                if ( 'parent_sku' == $column ) {
                    if ($product->post_parent) {
                        $row[$column] = get_post_meta($product->post_parent, '_sku', true);
                    } else {
                        $row[$column] = '';
                    }
                    continue;
                }

                
                // Export images/gallery
                if ('images' == $column ) {

                    $export_image_metadata = apply_filters('hf_export_image_metadata_flag', TRUE); //filter for disable export image meta datas such as alt,title,content,caption...
                    $image_file_names = array();

                    // Featured image
                    if (( $featured_image_id = get_post_thumbnail_id($product->ID))) {
                        $image_object = get_post($featured_image_id);
                        $img_url = wp_get_attachment_image_src($featured_image_id,'full');

                        $image_meta = '';
                        if ($image_object && $export_image_metadata) {
                            $image_metadata = get_post_meta($featured_image_id);
                            $image_meta = " ! alt : " . ( isset($image_metadata['_wp_attachment_image_alt'][0]) ? $image_metadata['_wp_attachment_image_alt'][0] : '' ) . " ! title : " . $image_object->post_title . " ! desc : " . $image_object->post_content . " ! caption : " . $image_object->post_excerpt;
                        }
                        if ($image_object && $image_object->guid) {
                            $temp_images_export_to_csv = $img_url[0] . ($export_image_metadata ? $image_meta : '');
                        }
                        if (!empty($temp_images_export_to_csv)) {
                            $image_file_names[] = $temp_images_export_to_csv;
                        }
                    }

                    // Images
                    $images = isset($meta_data['_product_image_gallery'][0]) ? explode(',', maybe_unserialize(maybe_unserialize($meta_data['_product_image_gallery'][0]))) : false;
                    $results = array();
                    if ($images) {
                        foreach ($images as $image_id) {
                            if ($featured_image_id == $image_id) {
                                continue;
                            }
                            $temp_gallery_images_export_to_csv = '';
                            $gallery_image_meta = '';
                            $gallery_image_object = get_post($image_id);
                            $gallery_img_url = wp_get_attachment_image_src($image_id, 'full');

                            if ($gallery_image_object && $export_image_metadata) {
                                $gallery_image_metadata = get_post_meta($image_id);
                                $gallery_image_meta = " ! alt : " . ( isset($gallery_image_metadata['_wp_attachment_image_alt'][0]) ? $gallery_image_metadata['_wp_attachment_image_alt'][0] : '' ) . " ! title : " . $gallery_image_object->post_title . " ! desc : " . $gallery_image_object->post_content . " ! caption : " . $gallery_image_object->post_excerpt;
                            }
                            if ($gallery_image_object && $gallery_image_object->guid) {
                                $temp_gallery_images_export_to_csv = $gallery_img_url[0] . ($export_image_metadata ? $gallery_image_meta : '');
                            }
                            if (!empty($temp_gallery_images_export_to_csv)) {
                                $image_file_names[] = $temp_gallery_images_export_to_csv;
                            }
                        }
                    }


                    if (!empty($image_file_names)) {
                        $row[$column] = implode(' | ', $image_file_names);
                    } else {
                        $row[$column] = '';
                    }
                    continue;
                }


                // Downloadable files
                if ('file_paths' == $column || 'downloadable_files' == $column) {
                    $file_paths_to_export = array();
                    if (!function_exists('wc_get_filename_from_url')) {
                        $file_paths = maybe_unserialize(maybe_unserialize($meta_data['_file_paths'][0]));

                        if ($file_paths) {
                            foreach ($file_paths as $file_path) {
                                $file_paths_to_export[] = $file_path;
                            }
                        }

                        $file_paths_to_export = implode(' | ', $file_paths_to_export);
                        $row[] = self::format_data($file_paths_to_export);
                    } elseif (isset($meta_data['_downloadable_files'][0])) {
                        $file_paths = maybe_unserialize(maybe_unserialize($meta_data['_downloadable_files'][0]));

                        if (is_array($file_paths) || is_object($file_paths)) {
                            foreach ($file_paths as $file_path) {
                                $file_paths_to_export[] = (!empty($file_path['name']) ? $file_path['name'] : wf_piep_helper::xa_wc_get_filename_from_url($file_path['file']) ) . '::' . $file_path['file'];
                            }
                        }
                        $file_paths_to_export = implode(' | ', $file_paths_to_export);
                    }
                    if(!empty($file_paths_to_export)){
                        $row[$column] = !empty($file_paths_to_export) ? self::format_data($file_paths_to_export) : '';
                    } else {
                        $row[$column] = '';
                    }
                    continue;
                }
                
                
                // Export taxonomies
                if ( 'taxonomies' == $column ) {

                    foreach ($product_taxonomies as $taxonomy) {

                        if (strstr($taxonomy->name, 'pa_'))
                            continue; // Skip attributes

                        if (is_taxonomy_hierarchical($taxonomy->name)) {
                            $terms = wp_get_post_terms($product->ID, $taxonomy->name, array("fields" => "all"));

                            $formatted_terms = array();

                            foreach ($terms as $term) {
                                $ancestors = array_reverse(get_ancestors($term->term_id, $taxonomy->name));
                                $formatted_term = array();

                                foreach ($ancestors as $ancestor)
                                    $formatted_term[] = get_term($ancestor, $taxonomy->name)->name;

                                $formatted_term[] = $term->name;

                                $formatted_terms[] = implode(' > ', $formatted_term);
                            }

                            $row['tax:' . self::format_data($taxonomy->name)] = self::format_data(implode('|', $formatted_terms));
                        } else {
                            $terms = wp_get_post_terms($product->ID, $taxonomy->name, array("fields" => "slugs"));

                            $row['tax:' . self::format_data($taxonomy->name)] = self::format_data(implode('|', $terms));
                        }
                    }
                    continue;
                }

                // Export meta data
                if ( 'meta' == $column ) {
                    foreach ($found_product_meta as $product_meta) {
                        if (isset($product->meta->$product_meta)) {
                            $row['meta:' . self::format_data($product_meta)] = self::format_data($product->meta->$product_meta);
                        } else {
                            $row['meta:' . self::format_data($product_meta)] = '';
                        }
                    }
                    continue;
                }

                // Find and export attributes
                if ('attributes' == $column ) {
                    foreach ($found_attributes as $attribute) {
                        if (isset($product->attributes) && isset($product->attributes->$attribute)) {
                            $values = $product->attributes->$attribute;
                            $row['attribute:' . self::format_data($attribute)] = self::format_data($values['value']);
                            $row['attribute_data:' . self::format_data($attribute)] = self::format_data($values['data']);
                            $row['attribute_default:' . self::format_data($attribute)] = self::format_data($values['default']);
                        } else {
                            $row['attribute:' . self::format_data($attribute)] = '';
                            $row['attribute_data:' . self::format_data($attribute)] = '';
                            $row['attribute_default:' . self::format_data($attribute)] = '';
                        }
                    }
                    continue;
                }


                // WF: Adding product permalink.
                if ( 'product_page_url' == $column ) {
                    $product_page_url = '';
                    if (!empty($product->ID)) {
                        $product_page_url = get_permalink($product->ID);
                    }
                    if (!empty($product->post_parent)) {
                        $product_page_url = get_permalink($product->post_parent);
                    }
                    $row[$column] = !empty($product_page_url) ? $product_page_url : '';
                    continue;
                } 
                
                
                /**
                * WPML
                */
                if (apply_filters('wpml_setting', false, 'setup_complete')) {
                    if (in_array($column, array('wpml:language_code', 'wpml:original_product_id', 'wpml:original_product_sku'))) {
                        if ('wpml:language_code' == $column) {
                            $original_post_language_info = wf_piep_helper::wt_get_wpml_original_post_language_info($product->ID);
                            $row[$column] = (isset($original_post_language_info->language_code) && !empty($original_post_language_info->language_code) ? $original_post_language_info->language_code : '');
                            continue;
                        }

                        /*
                         * To get the ID of the original product post 
                         * https://wpml.org/forums/topic/translated-product-get-id-of-original-lang-for-custom-fields/             
                         */

                        global $sitepress;
                        $original_product_id = icl_object_id($product->ID, 'product', false, $sitepress->get_default_language());
                        if ('wpml:original_product_id' == $column) {
                            $row[$column] = ($original_product_id ? $original_product_id : '');
                            continue;
                        }
                        if ('wpml:original_product_sku' == $column) {
                            $sku = get_post_meta($original_product_id, '_sku', true);
                            $row[$column] = ($sku ? $sku : '');
                            continue;
                        }
                    }
                }

                        
                        
                if (isset($product->meta->$column)) {
                    if ('_children' == $column) {
//                        if ($export_children_sku) {
//                            $children_id_array = str_replace('"', '', explode(',', trim($product->meta->$column, '[' . ']')));
//                            if (!empty($children_id_array) && $children_id_array[0] != '""') {
//                                foreach ($children_id_array as $children_id_array_key => $children_id) {
//                                    $children_sku = !empty($children_sku) ? "{$children_sku}|" . get_post_meta($children_id, '_sku', TRUE) : get_post_meta($children_id, '_sku', TRUE);
//                                }
//                            }
//                            $row[$column] = !empty($children_sku) ? $children_sku : '';
//                        } else {
                            $row[$column] = str_replace('"', '', implode('|', explode(',', trim($product->meta->$column, '[' . ']'))));
//                        }
                    } elseif ('_stock_status' == $column) {
                        $stock_status = self::format_data($product->meta->$column);
                        $product_type = ( WC()->version < '3.0' ) ? $product_object->product_type : $product_object->get_type();
                        $row[$column] = !empty($stock_status) ? $stock_status : ( ( 'variable' == $product_type || 'variable-subscription' == $product_type ) ? '' : 'instock' );
                    } else {
                        $row[$column] = self::format_data($product->meta->$column);
                    }
                } elseif (isset($product->$column) && !is_array($product->$column)) {
                    if ($export_shortcodes && ( 'post_content' == $column || 'post_excerpt' == $column )) {
                        //Convert Shortcodes to html for Description and Short Description
                        $row[$column] = do_shortcode($product->$column);
                    } elseif ('post_title' === $column) {
                        $row[$column] = sanitize_text_field($product->$column);
                    } else {
                        $row[$column] = self::format_data($product->$column);
                    }
                } else {
                    $row[$column] = '';
                }
            }
        }
        
        
        /*
        // Export images/gallery
        if (!$export_columns || in_array('images', $export_columns)) {
            if ($column == 'images') {
                error_log("entered images");
                $export_image_metadata = apply_filters('hf_export_image_metadata_flag', TRUE); //filter for disable export image meta datas such as alt,title,content,caption...
                $image_file_names = array();

                // Featured image
                if (( $featured_image_id = get_post_thumbnail_id($product->ID))) {
                    $image_object = get_post($featured_image_id);

                    $image_meta = '';
                    if ($export_image_metadata) {
                        $image_metadata = get_post_meta($featured_image_id);
                        $image_meta = " ! alt : " . ( isset($image_metadata['_wp_attachment_image_alt'][0]) ? $image_metadata['_wp_attachment_image_alt'][0] : '' ) . " ! title : " . $image_object->post_title . " ! desc : " . $image_object->post_content . " ! caption : " . $image_object->post_excerpt;
                    }
                    if ($image_object && $image_object->guid) {
                        $temp_images_export_to_csv = ( $image_object->guid ) . ($export_image_metadata ? $image_meta : '');
                    }
                    if (!empty($temp_images_export_to_csv)) {
                        $image_file_names[] = $temp_images_export_to_csv;
                    }
                }

                // Images
                $images = isset($meta_data['_product_image_gallery'][0]) ? explode(',', maybe_unserialize(maybe_unserialize($meta_data['_product_image_gallery'][0]))) : false;
                $results = array();
                if ($images) {
                    foreach ($images as $image_id) {
                        if ($featured_image_id == $image_id) {
                            continue;
                        }
                        $temp_gallery_images_export_to_csv = '';
                        $gallery_image_meta = '';
                        $gallery_image_object = get_post($image_id);

                        if ($gallery_image_object && $export_image_metadata) {
                            $gallery_image_metadata = get_post_meta($image_id);
                            $gallery_image_meta = " ! alt : " . ( isset($gallery_image_metadata['_wp_attachment_image_alt'][0]) ? $gallery_image_metadata['_wp_attachment_image_alt'][0] : '' ) . " ! title : " . $gallery_image_object->post_title . " ! desc : " . $gallery_image_object->post_content . " ! caption : " . $gallery_image_object->post_excerpt;
                        }
                        if ($gallery_image_object && $gallery_image_object->guid) {
                            $temp_gallery_images_export_to_csv = ($export_images_zip ? basename($gallery_image_object->guid) : $gallery_image_object->guid) . ($export_image_metadata ? $gallery_image_meta : '');
                        }
                        if (!empty($temp_gallery_images_export_to_csv)) {
                            $image_file_names[] = $temp_gallery_images_export_to_csv;
                        }
                    }
                }
                $row[$column] = implode(' | ', $image_file_names);
            }
        }

        // Downloadable files
        if (!$export_columns || in_array('file_paths', $export_columns)) {
            if (!function_exists('wc_get_filename_from_url')) {
                $file_paths = maybe_unserialize(maybe_unserialize($meta_data['_file_paths'][0]));
                $file_paths_to_export = array();

                if ($file_paths) {
                    foreach ($file_paths as $file_path) {
                        $file_paths_to_export[] = $file_path;
                    }
                }

                $file_paths_to_export = implode(' | ', $file_paths_to_export);
                $row[$column] = self::format_data($file_paths_to_export);
            } elseif (isset($meta_data['_downloadable_files'][0])) {
                $file_paths = maybe_unserialize(maybe_unserialize($meta_data['_downloadable_files'][0]));
                $file_paths_to_export = array();

                if (is_array($file_paths) || is_object($file_paths)) {
                    foreach ($file_paths as $file_path) {
                        $file_paths_to_export[] = (!empty($file_path['name']) ? $file_path['name'] : $piep_helper_object->xa_wc_get_filename_from_url($file_path['file']) ) . '::' . $file_path['file'];
                    }
                }
                $file_paths_to_export = implode(' | ', $file_paths_to_export);
                $row[$column] = self::format_data($file_paths_to_export);
            } else {
                $row[$column] = '';
            }
        }

        // Export taxonomies
        if (!$export_columns || in_array('taxonomies', $export_columns)) {

            foreach ($product_taxonomies as $taxonomy) {

                if (strstr($taxonomy->name, 'pa_'))
                    continue; // Skip attributes

                if (is_taxonomy_hierarchical($taxonomy->name)) {
                    $terms = wp_get_post_terms($product->ID, $taxonomy->name, array("fields" => "all"));

                    $formatted_terms = array();

                    foreach ($terms as $term) {
                        $ancestors = array_reverse(get_ancestors($term->term_id, $taxonomy->name));
                        $formatted_term = array();

                        foreach ($ancestors as $ancestor)
                            $formatted_term[] = get_term($ancestor, $taxonomy->name)->name;

                        $formatted_term[] = $term->name;

                        $formatted_terms[] = implode(' > ', $formatted_term);
                    }

                    $row[$column] = self::format_data(implode('|', $formatted_terms));
                } else {
                    $terms = wp_get_post_terms($product->ID, $taxonomy->name, array("fields" => "slugs"));

                    $row[$column] = self::format_data(implode('|', $terms));
                }
            }
        }

        // Export meta data
        if (!$export_columns || in_array('meta', $export_columns)) {
            foreach ($found_product_meta as $product_meta) {
                if (isset($product->meta->$product_meta)) {
                    $row[$product_meta] = self::format_data($product->meta->$product_meta);
                } else {
                    $row[$product_meta] = '';
                }
            }
        }

        
          // Find and export attributes
          if (!$export_columns || in_array('attributes', $export_columns)) {
          foreach ($found_attributes as $attribute) {
          if (isset($product->attributes) && isset($product->attributes->$attribute)) {
          $values = $product->attributes->$attribute;
          $row[] = self::format_data($values['value']);
          $row[] = self::format_data($values['data']);
          $row[] = self::format_data($values['default']);
          } else {
          $row[] = '';
          $row[] = '';
          $row[] = '';
          }
          }
          }
         


        // WF: Adding product permalink.
        if (!$export_columns || in_array('product_page_url', $export_columns)) {
            $product_page_url = '';
            if (!empty($product->ID)) {
                $product_page_url = get_permalink($product->ID);
            }
            if (!empty($product->post_parent)) {
                $product_page_url = get_permalink($product->post_parent);
            }
            $row['product_page_url'] = $product_page_url;
        }
        */
        
        //}
        
        
//        $this->prepare_attributes_for_export( $product_object, $row );
//        $this->prepare_meta_for_export( $product_object, $row );
        
        
//        echo '<pre>$row:-';
//        print_r($row);
//        echo '</pre>';
        return apply_filters('wt_batch_product_export_row_data', $row, $product);
    }

    /**
	 * Get images value.
	 *
	 * @since 3.1.0
	 * @param WC_Product $product Product being exported.
	 * @return string
	 */
	protected function get_column_value_images( $product ) {
		$image_ids = array_merge( array( $product->get_image_id( 'edit' ) ), $product->get_gallery_image_ids( 'edit' ) );
		$images    = array();

		foreach ( $image_ids as $image_id ) {
			$image = wp_get_attachment_image_src( $image_id, 'full' );

			if ( $image ) {
				$images[] = $image[0];
			}
		}

		return $this->implode_values( $images );
	}


	/**
	 * Export attributes data.
	 */
        
	protected function prepare_attributes_for_export( $product, &$row ) {
            
//		if ( $this->is_column_exporting( 'attributes' ) ) {
			$attributes         = $product->get_attributes();
			$default_attributes = $product->get_default_attributes();

			if ( count( $attributes ) ) {
				$i = 1;
				foreach ( $attributes as $attribute_name => $attribute ) {
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:name' . $i ] = sprintf( __( 'Attribute %d name', 'woocommerce' ), $i );
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:value' . $i ] = sprintf( __( 'Attribute %d value(s)', 'woocommerce' ), $i );
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:visible' . $i ] = sprintf( __( 'Attribute %d visible', 'woocommerce' ), $i );
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:taxonomy' . $i ] = sprintf( __( 'Attribute %d global', 'woocommerce' ), $i );

					if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
						$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute->get_name(), $product );

						if ( $attribute->is_taxonomy() ) {
							$terms  = $attribute->get_terms();
							$values = array();

							foreach ( $terms as $term ) {
								$values[] = $term->name;
							}

							$row[ 'attributes:value' . $i ]    = $this->implode_values( $values );
							$row[ 'attributes:taxonomy' . $i ] = 1;
						} else {
							$row[ 'attributes:value' . $i ]    = $this->implode_values( $attribute->get_options() );
							$row[ 'attributes:taxonomy' . $i ] = 0;
						}

						$row[ 'attributes:visible' . $i ] = $attribute->get_visible();
					} else {
						$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute_name, $product );

						if ( 0 === strpos( $attribute_name, 'pa_' ) ) {
							$option_term = get_term_by( 'slug', $attribute, $attribute_name ); // @codingStandardsIgnoreLine.
							$row[ 'attributes:value' . $i ]    = $option_term && ! is_wp_error( $option_term ) ? str_replace( ',', '\\,', $option_term->name ) : $attribute;
							$row[ 'attributes:taxonomy' . $i ] = 1;
						} else {
							$row[ 'attributes:value' . $i ]    = $attribute;
							$row[ 'attributes:taxonomy' . $i ] = 0;
						}

						$row[ 'attributes:visible' . $i ] = '';
					}

					if ( $product->is_type( 'variable' ) && isset( $default_attributes[ sanitize_title( $attribute_name ) ] ) ) {
						/* translators: %s: attribute number */
						$this->column_names[ 'attributes:default' . $i ] = sprintf( __( 'Attribute %d default', 'woocommerce' ), $i );
						$default_value                                   = $default_attributes[ sanitize_title( $attribute_name ) ];

						if ( 0 === strpos( $attribute_name, 'pa_' ) ) {
							$option_term = get_term_by( 'slug', $default_value, $attribute_name ); // @codingStandardsIgnoreLine.
							$row[ 'attributes:default' . $i ] = $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $default_value;
						} else {
							$row[ 'attributes:default' . $i ] = $default_value;
						}
					}
					$i++;
				}
			}
//		}
	}

	/**
	 * Export meta data.
	 */
        
	protected function prepare_meta_for_export( $product, &$row ) {
            
		if ( $this->enable_meta_export ) {
			$meta_data = $product->get_meta_data();

			if ( count( $meta_data ) ) {
				$meta_keys_to_skip = apply_filters( 'woocommerce_product_export_skip_meta_keys', array(), $product );

				$i = 1;
				foreach ( $meta_data as $meta ) {
					if ( in_array( $meta->key, $meta_keys_to_skip, true ) ) {
						continue;
					}

					// Allow 3rd parties to process the meta, e.g. to transform non-scalar values to scalar.
					$meta_value = apply_filters( 'woocommerce_product_export_meta_value', $meta->value, $meta, $product, $row );

					if ( ! is_scalar( $meta_value ) ) {
						continue;
					}

					$column_key = 'meta:' . esc_attr( $meta->key );
					/* translators: %s: meta data name */
					$this->column_names[ $column_key ] = sprintf( __( 'Meta: %s', 'woocommerce' ), $meta->key );
					$row[ $column_key ]                = $meta_value;
					$i ++;
				}
			}
		}
	}
        
        
        
        
        public static function get_all_metakeys($post_type = 'product') {
            
        global $wpdb;

        $meta = $wpdb->get_col($wpdb->prepare(
                        "SELECT DISTINCT pm.meta_key
            FROM {$wpdb->postmeta} AS pm
            LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status IN ( 'publish', 'pending', 'private', 'draft' )", $post_type
        ));

        sort($meta);

        return $meta;
    }

    /**
     * Get a list of all the product attributes for a post type.
     * These require a bit more digging into the values.
     */
    public static function get_all_product_attributes($post_type = 'product') {
        
        global $wpdb;

        $results = $wpdb->get_col($wpdb->prepare(
                        "SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} AS pm
            LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status IN ( 'publish', 'pending', 'private', 'draft' )
            AND pm.meta_key = '_product_attributes'", $post_type
        ));

        // Go through each result, and look at the attribute keys within them.
        $result = array();

        if (!empty($results)) {
            foreach ($results as $_product_attributes) {
                $attributes = maybe_unserialize(maybe_unserialize($_product_attributes));
                if (!empty($attributes) && is_array($attributes)) {
                    foreach ($attributes as $key => $attribute) {
                        if (!$key) {
                            continue;
                        }
                        if (!strstr($key, 'pa_')) {
                            if (empty($attribute['name'])) {
                                continue;
                            }
                            $key = $attribute['name'];
                        }

                        $result[$key] = $key;
                    }
                }
            }
        }

        sort($result);

        return $result;
    }
    
//    public static function format_data($data) {
//        if (!is_array($data));
//        $data = (string) urldecode($data);
//        $enc = mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true);
//        $data = ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);
//        return $data;
//    }
        
        
}
