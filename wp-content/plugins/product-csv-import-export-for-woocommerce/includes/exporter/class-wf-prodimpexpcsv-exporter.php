<?php

if (!defined('WPINC')) {
    exit;
}

class WF_ProdImpExpCsv_Exporter {

    /**
     * Product Exporter Tool
     */
    public static function do_export($post_type = 'product', $prod_ids = array(), $xmldata = '0' ) { // $xmldata:- introduced XML export 
        global $wpdb;
        require_once(dirname(dirname(__FILE__)) . '/class-wf-piep-helper.php');

        if (!empty($prod_ids)) {
            $selected_product_ids = implode(', ', $prod_ids);
        } else {
            $selected_product_ids = '';
        }
        $prod_categories = !empty($_POST['prod_categories']) ? $_POST['prod_categories'] : array();
        $prod_tags = !empty($_POST['prod_tags']) ? $_POST['prod_tags'] : array();
        $prod_types = !empty($_POST['prod_types']) ? $_POST['prod_types'] : array();
        $prod_status = !empty($_POST['prod_status']) ? $_POST['prod_status'] : array('publish', 'private', 'draft', 'pending', 'future');
        $export_limit = !empty($_POST['limit']) ? intval($_POST['limit']) : 999999999;
        $export_count = 0;
        $limit = apply_filters('wt_woocommerce_csv_export_limit_per_request',100);
        
        if ($limit > $export_limit)
            $limit = $export_limit;
        
        $current_offset = !empty($_POST['offset']) ? intval($_POST['offset']) : 0;
        $sortcolumn = !empty($_POST['sortcolumn']) ? implode(', ', $_POST['sortcolumn'] ): 'post_parent, ID';
        $delimiter = !empty($_POST['delimiter']) ? $_POST['delimiter'] : ',';
        $csv_columns = include( 'data/data-wf-post-columns.php' );

        $user_columns_name = !empty($_POST['columns_name']) ? $_POST['columns_name'] : $csv_columns; 
        
        /* setting  $user_columns_name for bulk action or cron export. */
        if(!empty($prod_ids)){            
            $user_columns_name = array_combine(array_keys($csv_columns), array_keys($csv_columns));
        }
                
        $product_ptaxonomies = get_object_taxonomies('product', 'name');
        $product_vtaxonomies = get_object_taxonomies('product_variation', 'name');
        $product_taxonomies = array_merge($product_ptaxonomies, $product_vtaxonomies);
        $export_columns = !empty($_POST['columns']) ? $_POST['columns'] : array();
                
        $include_hidden_meta = !empty($_POST['include_hidden_meta']) ? true : false;
        $export_children_sku = !empty($_POST['v_export_children_sku']) ? true : false;
        $export_shortcodes = !empty($_POST['v_export_do_shortcode']) ? true : false;
        $export_images_zip = !empty($_POST['v_export_images_zip']) ? true : false;

        if (!empty($_POST['auto_export_profile'])) {
            $export_profile_array = get_option('xa_prod_csv_export_mapping');
            $user_columns_name = array();
            $user_columns_name = $export_profile_array[$_POST['auto_export_profile']];

            foreach ($user_columns_name as $column => $value) {
                $export_columns[$column] = $column;
            }
        }
        
        $exclude_hidden_meta_columns = include( 'data/data-wf-hidden-meta-columns.php' );

        $settings = get_option('woocommerce_' . WF_PROD_IMP_EXP_ID . '_settings', null);        
        $enable_ftp_ie = isset($settings['pro_enable_ftp_ie']) ? $settings['pro_enable_ftp_ie'] : '';
        
        $wpdb->hide_errors();
        @set_time_limit(0);
        if (function_exists('apache_setenv'))
            @apache_setenv('no-gzip', 1);
        
        @ini_set('zlib.output_compression', 0);        
        @ob_end_clean(); // to prevent issue that unidentified characters when opened in MS-Excel in some servers

        if ($enable_ftp_ie) {
            if (!isset($_POST['offset'])) {
                $export_shortcodes = (!empty($settings['pro_auto_export_do_shortcode']) ) ? true : false;
                $prod_categories = ( isset($settings['pro_auto_export_categories']) ) ? $settings['pro_auto_export_categories'] : $prod_categories;
                $include_hidden_meta = (!empty($settings['pro_auto_export_include_hidden_meta']) ) ? true : false;
            }
            $upload_path = wp_upload_dir();
            $file_path = $upload_path['path'] . '/';
            $file = (!empty($settings['pro_auto_export_ftp_file_name']) ) ? $file_path . $settings['pro_auto_export_ftp_file_name'] : $file_path . $post_type . "-export-" . date('Y_m_d_H_i_s', current_time('timestamp')) . ".csv";
            $fp = fopen($file, 'w');
        } elseif($xmldata == '0') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=woocommerce-product-export.csv');
            header('Pragma: no-cache');
            header('Expires: 0');

            $fp = fopen('php://output', 'w');
        }

        // Headers
        $all_meta_pkeys = self::get_all_metakeys('product');
        $all_meta_vkeys = self::get_all_metakeys('product_variation');
        $all_meta_keys = array_merge($all_meta_pkeys, $all_meta_vkeys);
        $all_meta_keys = array_unique($all_meta_keys);

        $found_pattributes = self::get_all_product_attributes('product');
        $found_vattributes = self::get_all_product_attributes('product_variation');
        $found_attributes = array_merge($found_pattributes, $found_vattributes);
        $found_attributes = array_unique($found_attributes);
        // Loop products and load meta data
        $found_product_meta = array();
        // Some of the values may not be usable (e.g. arrays of arrays) but the worse
        // that can happen is we get an empty column.
        foreach ($all_meta_keys as $meta) {
            if (!$meta)
                continue;
            if (!$include_hidden_meta && !in_array($meta, array_keys($csv_columns)) && substr((string) $meta, 0, 1) == '_')
                continue;
            if ($include_hidden_meta && ( in_array($meta, $exclude_hidden_meta_columns) || in_array($meta, array_keys($csv_columns)) || in_array('meta:'.$meta, array_keys($csv_columns)) ))
                continue;
            $found_product_meta[] = $meta;
        }

        $found_product_meta = array_diff($found_product_meta, array_keys($csv_columns));

        // Variable to hold the CSV data we're exporting
        $row = array();
           
        // Export header rows       
        foreach ($csv_columns as $column => $value) {

            if (!isset($user_columns_name[$column])) {

                continue;
            }
            if (!$export_columns || in_array($value, $export_columns) || in_array($column, $export_columns)) {
                
                if ('taxonomies' == $column) {
                    foreach ($product_taxonomies as $taxonomy) {
                        if (strstr($taxonomy->name, 'pa_'))
                            continue; // Skip attributes

                        $row[] = 'tax:' . self::format_data($taxonomy->name);
                    }
                    continue;
                }

                if ('meta' == $column) {
                    foreach ($found_product_meta as $product_meta) {
                        $row[] = 'meta:' . self::format_data($product_meta);
                    }
                    continue;
                }

                if ('attributes' == $column) {
                    foreach ($found_attributes as $attribute) {
                        $row[] = 'attribute:' . self::format_data($attribute);
                        $row[] = 'attribute_data:' . self::format_data($attribute);
                        $row[] = 'attribute_default:' . self::format_data($attribute);
                    }
                    continue;
                }

                $temp_head = esc_attr($user_columns_name[$column]);
                if (strpos($temp_head, 'yoast') === false) {
                    $temp_head = ltrim($temp_head, '_');
                }

                $row[] = $temp_head;
            }
        }

        $row = apply_filters('hf_alter_product_export_csv_columns', $row);
        $row = array_map('WF_ProdImpExpCsv_Exporter::wrap_column', $row);    
        if($xmldata == '0') // introduced XML export, escaping writing to csv file
        fwrite($fp, implode($delimiter, $row) . "\n");
        $header_row = $row; 
        unset($row);
        
        
        $csv_columns = wf_piep_helper::wt_array_walk($csv_columns,'meta:'); // Remove string 'meta:' from keys and values, YOAST support
        $export_columns = wf_piep_helper::wt_array_walk($export_columns,'meta:'); // Remove string 'meta:' from keys and values, YOAST support


        ini_set('max_execution_time', -1);
        ini_set('memory_limit', -1);
        while ($export_count < $export_limit) {

            $product_args = array(
                'numberposts' => $limit,
                'post_status' => $prod_status,
                'post_type' => array('product', 'product_variation'),
                'orderby' => $sortcolumn,
                'suppress_filters' => FALSE,
                'order' => 'ASC',
                'offset' => $current_offset
            );
            
            if(apply_filters('wpml_setting', false, 'setup_complete')) {
                $product_args['suppress_filters']= TRUE;
            }

            if ((!empty($prod_categories) ) || (!empty($prod_types)) || (!empty($prod_tags))) {

                //If only product type has been selected
                if (!empty($prod_types)) {
                    $product_args['tax_query'][] = 
                        array(
                            'taxonomy' => 'product_type',
                            'field' => 'slug',
                            'terms' => $prod_types,
                            'operator' => 'IN',
                    );
                }

                //If only product categories has been selected
                if (!empty($prod_categories)) {
                    $product_args['tax_query'][] = 
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'id',
                            'terms' => $prod_categories,
                            'operator' => 'IN',
                    );
                }

                if (!empty($prod_tags)) {
                    $product_args['tax_query'][] = 
                        array(
                            'taxonomy' => 'product_tag',
                            'field' => 'id',
                            'terms' => $prod_tags,
                            'operator' => 'IN',
                    );
                }

            }
         

            if ($selected_product_ids) {
                $parent_ids = array_map('intval', explode(',', $selected_product_ids));
                $child_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_parent IN (" . implode(',', $parent_ids) . ");");
                $sel_ids = array_merge($parent_ids, $child_ids);
                $product_args['post__in'] = $sel_ids;
            }
            
            $product_args = apply_filters('woocommerce_csv_product_export_args',$product_args );
            $products = get_posts($product_args);

            //if product type selected is variable or product categories selected , get variations of variable product and variations subscription of variable subscription product also    
            if (!empty($prod_categories) || (!empty($prod_types) && in_array('variable', $prod_types) ) || (!empty($prod_types) && in_array('variable-subscription', $prod_types) )) {
                $products = self::get_childs_of_selected_parents($products);
            }
            if (!$products || is_wp_error($products))
                break;
            // Loop products
            foreach ($products as $product) {
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
                                $terms = wp_get_post_terms($product->ID, $attribute['name'], array("fields" => "names"));
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
                                            $term_slug .= $term->slug . (isset($product_terms[$i+1])?'|':'');
                                        }
                                        $row[] = $term_slug;
                                    }
                                } else {
                                    $row[] = '';
                                }
                                continue;
                            }
                        }
                        
                        if ( 'parent' == $column ) {
                            if ($product->post_parent) {
                                $post_parent_title = get_the_title($product->post_parent);
                                if ($post_parent_title) {
                                    $row[] = self::format_data($post_parent_title);                                    
                                } else {
                                    $row[] = '';                                    
                                }
                            } else {
                                $row[] = '';                                
                            }
                            continue;
                        }
                        
                        if ( 'parent_sku' == $column ) {
                            if ($product->post_parent) {
                                $row[] = get_post_meta($product->post_parent, '_sku', true);
                            } else {
                                $row[] = '';
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
                                    $temp_images_export_to_csv = ($export_images_zip ? basename($img_url[0]) : $img_url[0]) . ($export_image_metadata ? $image_meta : '');
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
                                        $temp_gallery_images_export_to_csv = ($export_images_zip ? basename($gallery_img_url[0]) : $gallery_img_url[0]) . ($export_image_metadata ? $gallery_image_meta : '');
                                    }
                                    if (!empty($temp_gallery_images_export_to_csv)) {
                                        $image_file_names[] = $temp_gallery_images_export_to_csv;
                                    }
                                }
                            }
                            

                            if (!empty($image_file_names)) {
                                $row[] = implode(' | ', $image_file_names);
                            } else {
                                $row[] = '';
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
                                $row[] = !empty($file_paths_to_export) ? self::format_data($file_paths_to_export) : '';
                            } else {
                                $row[] = '';
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

                                    $row[] = self::format_data(implode('|', $formatted_terms));
                                } else {
                                    $terms = wp_get_post_terms($product->ID, $taxonomy->name, array("fields" => "slugs"));

                                    $row[] = self::format_data(implode('|', $terms));
                                }
                            }
                            continue;
                        }

                        // Export meta data
                        if ( 'meta' == $column ) {
                            foreach ($found_product_meta as $product_meta) {
                                if (isset($product->meta->$product_meta)) {
                                    $row[] = self::format_data($product->meta->$product_meta);
                                } else {
                                    $row[] = '';
                                }
                            }
                            continue;
                        }

                        // Find and export attributes
                        if ('attributes' == $column ) {
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
                            $row[] = !empty($product_page_url) ? $product_page_url : '';
                            continue;
                        }

                        /**
                        * WPML
                        */
                        if (apply_filters('wpml_setting', false, 'setup_complete')) {
                            if (in_array($column, array('wpml:language_code', 'wpml:original_product_id', 'wpml:original_product_sku'))) {
                                if ('wpml:language_code' == $column) {
                                    $original_post_language_info = wf_piep_helper::wt_get_wpml_original_post_language_info($product->ID);
                                    $row[] = (isset($original_post_language_info->language_code) && !empty($original_post_language_info->language_code) ? $original_post_language_info->language_code : '');
                                    continue;
                                }

                                /*
                                 * To get the ID of the original product post 
                                 * https://wpml.org/forums/topic/translated-product-get-id-of-original-lang-for-custom-fields/             
                                 */

                                global $sitepress;
                                $original_product_id = icl_object_id($product->ID, 'product', false, $sitepress->get_default_language());
                                if ('wpml:original_product_id' == $column) {
                                    $row[] = ($original_product_id ? $original_product_id : '');
                                    continue;
                                }
                                if ('wpml:original_product_sku' == $column) {
                                    $sku = get_post_meta($original_product_id, '_sku', true);
                                    $row[] = ($sku ? $sku : '');
                                    continue;
                                }
                            }
                        }
                        

                        if (isset($product->meta->$column)) {
                            if ('_children' == $column) {
                                if ($export_children_sku) {
                                    $children_sku = '';
                                    $children_id_array = str_replace('"', '', explode(',', trim($product->meta->$column, '[' . ']')));
                                    if (!empty($children_id_array) && $children_id_array[0] != '""') {
                                        foreach ($children_id_array as $children_id_array_key => $children_id) {
                                            $children_sku = !empty($children_sku) ? "{$children_sku}|" . get_post_meta($children_id, '_sku', TRUE) : get_post_meta($children_id, '_sku', TRUE);
                                        }
                                    }
                                    $row[] = !empty($children_sku) ? $children_sku : '';
                                } else {
                                    $row[] = str_replace('"', '', implode('|', explode(',', trim($product->meta->$column, '[' . ']'))));
                                }
                            } elseif ('_stock_status' == $column) {
                                $stock_status = self::format_data($product->meta->$column);
                                
                                if( !function_exists('WC') && class_exists( 'Woocommerce' )){ // Lower version of WC
                                    if(function_exists('wc_get_product')){ // WC()->version < '3.0'
                                        $product_object = wc_get_product($product->ID);
                                    }elseif (function_exists('get_product')) { // Woocommerce 2.0.19 PIEPFW-407
                                        $product_object = get_product($product->ID);
                                    }
                                    if(!empty($product_object)){
                                        $product_type = $product_object->product_type;
                                    }else{
                                        $product_type = '';
                                    }                                    
                                }else{
                                    $product_type = WC_Product_Factory::get_product_type( $product->ID );
                                }                                
                                $row[] = !empty($stock_status) ? $stock_status : ( ( 'variable' == $product_type || 'variable-subscription' == $product_type ) ? '' : 'instock' );
                            } else {
                                $row[] = self::format_data($product->meta->$column);
                            }
                        } elseif (isset($product->$column) && !is_array($product->$column)) {
                            if ($export_shortcodes && ( 'post_content' == $column || 'post_excerpt' == $column )) {
                                //Convert Shortcodes to html for Description and Short Description
                                $row[] = do_shortcode($product->$column);
                            } elseif ('post_title' === $column) {
                                $row[] = sanitize_text_field($product->$column);
                            } else {
                                $row[] = self::format_data($product->$column);
                            }
                        } else {
                            $row[] = '';
                        }
                    }
                }

          
                $row = apply_filters('hf_alter_product_export_csv_data', $row, $product->ID,$header_row);
                if ($xmldata == '1') {  // introduced XML export, escaping writing to csv file
                    $row_data[]=$row;                 
                    continue;
                }
                // Add to csv
                $row = array_map('WF_ProdImpExpCsv_Exporter::wrap_column', $row);
                if($xmldata == '0')
                    fwrite($fp, implode($delimiter, $row) . "\n");
                unset($row);
            }
            $current_offset += $limit;
            $export_count += $limit;
            unset($products);
        }

        if ($xmldata == '1') {  // introduced XML export,  writing to XML file
            include_once( 'class-wt-product-xml-base-exporter.php' );
            $xml_base_exporter = new ProductExpXMLBase_Exporter();
            $xml_base_exporter->do_export($header_row,$row_data);
        }
        
        if ($enable_ftp_ie) {
            $file = apply_filters('wt_product_export_prepared_data', $file);
            $redirect_url = self::handle_ftp($settings,$file);
            unlink($file);
            wp_redirect($redirect_url);
        }

        fclose($fp);
        exit;
    }
    
    public static function handle_ftp($settings, $file) {

        $ftp_server = isset($settings['pro_ftp_server']) ? $settings['pro_ftp_server'] : '';
        $ftp_user = isset($settings['pro_ftp_user']) ? $settings['pro_ftp_user'] : '';
        $ftp_password = isset($settings['pro_ftp_password']) ? $settings['pro_ftp_password'] : '';
        $ftp_port = isset($settings['pro_ftp_port']) ? $settings['pro_ftp_port'] : 21;
        $use_ftps = isset($settings['pro_use_ftps']) ? $settings['pro_use_ftps'] : '';
        $remote_path = isset($settings['pro_auto_export_ftp_path']) ? $settings['pro_auto_export_ftp_path'] : null;

        // Upload ftp path with filename
        $remote_file = ( substr($remote_path, -1) != '/' ) ? ( $remote_path . "/" . basename($file) ) : ( $remote_path . basename($file) );
        //if have SFTP Add-on for Import Export for WooCommerce 
        if (class_exists('class_wf_sftp_import_export')) {
            $sftp_export = new class_wf_sftp_import_export();
            if (!$sftp_export->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
                $wf_product_ie_msg = 2;
                $url = admin_url('/admin.php?page=wf_woocommerce_csv_im_ex&wf_product_ie_msg=' . $wf_product_ie_msg);
                return $url;
            }
            if ($sftp_export->put_contents($remote_file, file_get_contents($file))) {
                $wf_product_ie_msg = 1;
            } else {
                $wf_product_ie_msg = 2;
            }
            $url = admin_url('/admin.php?page=wf_woocommerce_csv_im_ex&wf_product_ie_msg=' . $wf_product_ie_msg);
            return $url;
            
        }

        if ($use_ftps) {
            $ftp_conn = @ftp_ssl_connect($ftp_server, $ftp_port) or die("Could not connect to $ftp_server:$ftp_port");
        } else {
            $ftp_conn = @ftp_connect($ftp_server, $ftp_port) or die("Could not connect to $ftp_server:$ftp_port");
        }
        $login = ftp_login($ftp_conn, $ftp_user, $ftp_password);

        ftp_pasv($ftp_conn, TRUE);

        // upload file	
        if (ftp_put($ftp_conn, $remote_file, $file, FTP_ASCII)) {
            $wf_product_ie_msg = 1;
        } else {
            $wf_product_ie_msg = 2;
        }
        
        // close connection
        ftp_close($ftp_conn);
        $url = admin_url('/admin.php?page=wf_woocommerce_csv_im_ex&wf_product_ie_msg=' . $wf_product_ie_msg);
        return $url;        
    }

    /**
     * Format the data if required
     * function gets childs of products and psuh to products array
     * @param type $products
     */
    public static function get_childs_of_selected_parents($products) {
        global $wpdb;
        $var_ar_ids = array();
        foreach ($products as $product) {
            $get_type = 'product_variation';
            $child_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_parent = $product->ID AND post_type = '$get_type'");
            if (!empty($child_ids)) {
                $var_ar_ids = array_merge($var_ar_ids, $child_ids);
            }
        }
        if (!empty($var_ar_ids)) {
            $product_args = array(
                'numberposts' => -1,
                'post_type' => array('product_variation'),
                'post__in' => $var_ar_ids
            );
            $product_variations = get_posts($product_args);
            $products = array_merge($products, $product_variations);
        }
        return $products;
    }
       
    /**
     * Format the data if required
     * @param  string $meta_value
     * @param  string $meta name of meta key
     * @return string
     */
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

    public static function format_data($data) {
        if (!is_array($data));
        $data = (string) urldecode($data);
        $enc = mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true);
        $data = ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);
        return $data;
    }

    /**
     * Wrap a column in quotes for the CSV
     * @param  string data to wrap
     * @return string wrapped data
     */
    public static function wrap_column($data) {
        return '"' . str_replace('"', '""', $data) . '"';
    }

    /**
     * Get a list of all the meta keys for a post type. This includes all public, private,
     * used, no-longer used etc. They will be sorted once fetched.
     */
    public static function get_all_metakeys($post_type = 'product') {
        global $wpdb;

        $meta = $wpdb->get_col($wpdb->prepare(
                        "SELECT DISTINCT pm.meta_key
            FROM {$wpdb->postmeta} AS pm
            LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status IN ( 'publish', 'private', 'draft', 'pending', 'future' )", $post_type
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
            AND p.post_status IN ( 'publish', 'private', 'draft', 'pending', 'future' )
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

}
