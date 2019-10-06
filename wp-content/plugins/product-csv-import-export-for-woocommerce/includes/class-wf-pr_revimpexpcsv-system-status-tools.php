<?php
if ( ! defined( 'WPINC' ) ) {
	exit;
}

class WF_ProdReviewImpExpCsv_System_Status_Tools {

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
		$tools['delete_reviews'] = array(
			'name'		=> __( 'Delete Product Reviews','wf_csv_import_export'),
			'button'	=> __( 'Delete ALL Product Reviews','wf_csv_import_export' ),
			'desc'		=> __( 'This tool will delete all product reviews allowing you to start fresh.', 'wf_csv_import_export' ),
			'callback'  => array( $this, 'delete_reviews' )
		);
                $tools['delete_pro_reviews'] = array(
                        'name'	=> __( 'Delete Pro Review Transient Count','wf_csv_import_export'),
                        'button'	=> __( 'Delete ALL Product Reviews Transient Count','wf_csv_import_export' ),
                        'desc'	=> __( 'This tool will delete all product reviews transient count allowing you to start fresh.', 'wf_csv_import_export' ),
                        'callback'  => array( $this, 'delete_pro_reviews' )
                );
		return $tools;
	}

	/**
	 * Delete reviews
	 */
	public function delete_reviews() {
		global $wpdb;

                $wpdb->query( "DELETE wpc FROM {$wpdb->comments} wpc LEFT JOIN {$wpdb->commentmeta} wpcm ON wpcm.comment_id = wpc.comment_ID WHERE wpcm.meta_key = 'rating'");
                $wpdb->query( "DELETE wpcm FROM {$wpdb->commentmeta} wpcm LEFT JOIN {$wpdb->comments} wpc ON wpc.comment_ID = wpcm.comment_id WHERE wpc.comment_ID IS NULL");
		echo '<div class="updated"><p>' . sprintf( __( 'All Reviews Deleted', 'wf_csv_import_export' ) ) . '</p></div>';
	}
        
        /**
	 * Delete pro review counts
	 */
        public function delete_pro_reviews() {   
                global $wpdb;

                $wpdb->query( "DELETE  FROM {$wpdb->options}  WHERE option_name LIKE '%_transient_wc_product_reviews_pro_review_count_%'");
                echo '<div class="updated"><p>' . sprintf( __( 'All Review Trans Deleted', 'wf_csv_import_export' ) ) . '</p></div>';
        }
}

new WF_ProdReviewImpExpCsv_System_Status_Tools();