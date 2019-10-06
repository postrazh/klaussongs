<?php
if ( ! defined( 'WPINC' ) ) {
	exit;
}

class WF_ProdImpExpCsv_System_Status_Tools {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'tools' ) );
	}

	/**
	 * Tools we add to WC
	 * @param  array $tools
	 * @return array
	 */
	public function tools( $tools ) {
		$tools['delete_products'] = array(
			'name'		=> __( 'Delete Products','wf_csv_import_export'),
			'button'	=> __( 'Delete ALL products','wf_csv_import_export' ),
			'desc'		=> __( 'This tool will delete all products allowing you to start fresh.', 'wf_csv_import_export' ),
			'callback'  => array( $this, 'delete_products' )
		);
		$tools['delete_variations'] = array(
			'name'		=> __( 'Delete Variations','wf_csv_import_export'),
			'button'	=> __( 'Delete ALL variations','wf_csv_import_export' ),
			'desc'		=> __( 'This tool will delete all variations allowing you to start fresh.', 'wf_csv_import_export' ),
			'callback'  => array( $this, 'delete_variations' )
		);
		$tools['delete_orphaned_variations'] = array(
			'name'		=> __( 'Delete Orphans','wf_csv_import_export'),
			'button'	=> __( 'Delete orphaned variations','wf_csv_import_export' ),
			'desc'		=> __( 'This tool will delete variations which have no parent.', 'wf_csv_import_export' ),
			'callback'  => array( $this, 'delete_orphaned_variations' )
		);
                $tools['delete_existing_products'] = array(
                    'name' => __('Delete Existing Products', 'wf_csv_import_export'),
                    'button' => __('Delete Existing products', 'wf_csv_import_export'),
                    'desc' => __('This tool will delete all products that are not present in the last imported CSV.', 'wf_csv_import_export'),
                    'callback' => array( $this,'wt_delete_existing_product')
                );
                $tools['remove_all_product_categories'] = array(
                    'name' => __('Remove All Product Categories', 'wf_csv_import_export'),
                    'button' => __('Delete Categories', 'wf_csv_import_export'),
                    'desc' => __( 'This tool will forcefully delete all product categories.', 'wf_csv_import_export' ),
                    'callback' => array( $this,'wt_remove_all_product_categories')
                );
                $tools['remove_all_product_tags'] = array(
                    'name' => __('Remove All Product Tags', 'wf_csv_import_export'),
                    'button' => __('Delete  Tags', 'wf_csv_import_export'),
                    'desc' => __( 'This tool will forcefully delete all product tags.', 'wf_csv_import_export' ),
                    'callback' => array( $this,'wt_remove_all_product_tags')
                );
//                $tools['remove_all_product_attributes'] = array(
//                    'name' => __('Remove All Product Attributes', 'wf_csv_import_export'),
//                    'button' => __('Delete  Attributes', 'wf_csv_import_export'),
//                    'desc' => __( 'This tool will forcefully delete all product attributes.', 'wf_csv_import_export' ),
//                    'callback' => array( $this,'wt_remove_all_product_attributes')
//                );
                
                return $tools;
	}

	/**
	 * Delete products
	 */
	public function delete_products() {
		global $wpdb;

		// Delete products
		$result  = absint( $wpdb->delete( $wpdb->posts, array( 'post_type' => 'product' ) ) );
		$result2 = absint( $wpdb->delete( $wpdb->posts, array( 'post_type' => 'product_variation' ) ) );

		// Delete meta and term relationships with no post
		$wpdb->query( "DELETE pm
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id
			WHERE wp.ID IS NULL" );
		$wpdb->query( "DELETE tr
			FROM {$wpdb->term_relationships} tr
			LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
			WHERE wp.ID IS NULL" );

		echo '<div class="updated"><p>' . sprintf( __( '%d Products Deleted', 'wf_csv_import_export' ), ( $result + $result2 ) ) . '</p></div>';
	}

	/**
	 * Delete variations
	 */
	public function delete_variations() {
		global $wpdb;

		// Delete products
		$result = absint( $wpdb->delete( $wpdb->posts, array( 'post_type' => 'product_variation' ) ) );

		// Delete meta and term relationships with no post
		$wpdb->query( "DELETE pm
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id
			WHERE wp.ID IS NULL" );
		$wpdb->query( "DELETE tr
			FROM {$wpdb->term_relationships} tr
			LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
			WHERE wp.ID IS NULL" );

		echo '<div class="updated"><p>' . sprintf( __( '%d Variations Deleted', 'wf_csv_import_export' ), $result ) . '</p></div>';
	}

	/**
	 * Delete orphans
	 */
	public function delete_orphaned_variations() {
		global $wpdb;

		// Delete meta and term relationships with no post
		$result = absint( $wpdb->query( "DELETE products
			FROM {$wpdb->posts} products
			LEFT JOIN {$wpdb->posts} wp ON wp.ID = products.post_parent
			WHERE wp.ID IS NULL AND products.post_type = 'product_variation';" ) );

		echo '<div class="updated"><p>' . sprintf( __( '%d Variations Deleted', 'wf_csv_import_export' ), $result ) . '</p></div>';
	}
        
        
        
        function wt_remove_all_product_categories() {
            global $wpdb;
    
            $wpdb->query("DELETE a,c FROM wp_terms AS a
                      LEFT JOIN wp_term_taxonomy AS c ON a.term_id = c.term_id
                      LEFT JOIN wp_term_relationships AS b ON b.term_taxonomy_id = c.term_taxonomy_id
                      WHERE c.taxonomy = 'product_cat'");

//            $args = array(
//                "taxonomy" => 'product_cat',
//                "hide_empty" => 0,
//                "type" => "post",
//                "orderby" => "name",
//                "order" => "ASC"
//            );
//            $types = get_categories($args);
//
//            foreach ($types as $type) {
//                $res = wp_delete_category($type->cat_ID);
//            }

            echo '<div class="updated"><p>' . __('Product Categories Deleted', 'wf_csv_import_export') . '</p></div>';
        }


        function wt_remove_all_product_tags() {
            global $wpdb;

            $wpdb->query("DELETE a,c FROM wp_terms AS a
                LEFT JOIN wp_term_taxonomy AS c ON a.term_id = c.term_id
                LEFT JOIN wp_term_relationships AS b ON b.term_taxonomy_id = c.term_taxonomy_id
                WHERE c.taxonomy = 'product_tag'");
            
            echo '<div class="updated"><p>' . __('Product Tags Deleted', 'wf_csv_import_export') . '</p></div>';
        }
        
        function wt_remove_all_product_attributes() {
            global $wpdb;            
            $wpdb->query("DELETE FROM wp_terms WHERE term_id IN 
                (SELECT term_id FROM wp_term_taxonomy WHERE taxonomy LIKE 'pa_%');
                DELETE FROM wp_term_taxonomy WHERE taxonomy LIKE 'pa_%';
                DELETE FROM wp_term_relationships WHERE term_taxonomy_id not IN 
                (SELECT term_taxonomy_id FROM wp_term_taxonomy)");
            
            echo '<div class="updated"><p>' . __('Product Attributes Deleted', 'wf_csv_import_export') . '</p></div>';
        }
        
        
        

}

new WF_ProdImpExpCsv_System_Status_Tools();