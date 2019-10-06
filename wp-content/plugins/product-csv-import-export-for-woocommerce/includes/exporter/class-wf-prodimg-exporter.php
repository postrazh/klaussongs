<?php
if (!defined('WPINC')) {
    exit;
}
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-wf-prodimg-exporter
 *
 * @author Fasil
 */
class WF_ProdImg_Exporter {

    public static function do_export($post_type = 'product', $prod_ids = array()) {
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', -1);
        $upload_path = wp_upload_dir();
        $wf_export_images_path = $upload_path['basedir'] . '/wf_export_images/'; // wp content path - fixed WP violation
        if (!file_exists($wf_export_images_path)) {
            mkdir($wf_export_images_path, 0777, true);
        }
        $destination = $wf_export_images_path . "images.zip";
        $wf_export_images = '';
        //$wf_export_images = WF_ProdImg_Exporter::recursive_file_search($upload_path['basedir']);
        //$wf_export_images = WF_ProdImg_Exporter::get_all_products_images();  this workflow have issue when site url comes http and https both 
        $wf_export_images = self::get_all_products_images_new();

        $wf_export_images = array_unique($wf_export_images); // Avoid dublication.

        if ($wf_export_images) {
            if (!strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {


                require_once plugin_dir_path(__FILE__) . "../../src/zip.php";

                $zip = new Zip();

                $zip1 = $zip->zip_start($destination);

                $zip2 = $zip->zip_add($wf_export_images);

                $zip_res = $zip->zip_end();
            } else {

                $zip_res = self::Zippp($wf_export_images, $destination);
            }

            //Then download the zipped file.
//             header('Content-Type: application/zip');
//             header('Content-disposition: attachment; filename=images.zip');
//             header('Content-Length: ' . filesize($destination));
//             readfile($destination);

            if ($zip_res !== FALSE) {
                $external_link = $upload_path['baseurl'] . '/wf_export_images/images.zip';
                echo "<script>  window.open('" . $external_link . "', '_blank'); </script>";
                die;
            }
        }
        wp_redirect(admin_url('/admin.php?page=wf_woocommerce_csv_im_ex&wf_product_ie_msg=3'));
        die;
    }

    public static function Zippp($source_array, $destination) {
        if (!extension_loaded('zip') || !is_array($source_array)) {
            return false;
        }
        $zip = new ZipArchive;
        if (!$zip->open($destination, ZipArchive::OVERWRITE | ZIPARCHIVE::CREATE)) {
            return false;
        }

        foreach ($source_array as $file) {
//            $new_filename = substr($file, strrpos($file, '/') + 1);
            $new_filename = basename($file);

            $zip->addFile($file, $new_filename);
        }
        $zip->close();
    }
    
    
    public static function get_all_products_images_new() {
        $product_args = apply_filters('woocommerce_csv_product_image_export_args', array(
            'numberposts' => -1,
            'post_status' => array('publish', 'pending', 'private', 'draft'),
            'post_type' => array('product', 'product_variation'),
            'order' => 'ASC',
        ));
        $products = get_posts($product_args);


        $image_array = $image_array_with_path = array();

        if ($products || !is_wp_error($products)) {
            foreach ($products as $key => $product) {
                $image_array[] = self::getProductImages_new($product);
            }
        }

        if (!empty($image_array)) {
            foreach ($image_array as $value) {
                if (empty($value))
                    continue;
                foreach ($value as $val) {
                    $image_array_with_path[] = $val;
                }
            }
        }
        return $image_array_with_path;
    }

    public static function getProductImages_new($product) {
        $image_file_names = array();
        $meta_data = get_post_custom($product->ID);
        
        // Featured image
        if (( $featured_image_id = get_post_thumbnail_id($product->ID))) {
            
           $attached_file_path= get_attached_file($featured_image_id);
            
            if (!empty($attached_file_path)) {
                $image_file_names[] = $attached_file_path;
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
                $attached_file_path = get_attached_file($image_id);

                if (!empty($attached_file_path)) {
                    $image_file_names[] = $attached_file_path;
                }
            }
        }
        
        /* compatible with WooCommerce Additional Variation Images Gallery plugin */
        $woo_variation_gallery_images = isset($meta_data['woo_variation_gallery_images'][0]) ? maybe_unserialize($meta_data['woo_variation_gallery_images'][0]) : FALSE;
        if($woo_variation_gallery_images){
            foreach ($woo_variation_gallery_images as $image_id) {
                if ($featured_image_id == $image_id) {
                    continue;
                }
                $attached_file_path = get_attached_file($image_id);

                if (!empty($attached_file_path)) {
                    $image_file_names[] = $attached_file_path;
                }
            }
        }
        
        return $image_file_names;
        
    }
    
    public static function get_all_products_images() {

        $upload_path = wp_upload_dir();
        $product_args = apply_filters('woocommerce_csv_product_image_export_args', array(
            'numberposts' => -1,
            'post_status' => array('publish', 'pending', 'private', 'draft'),
            'post_type' => array('product', 'product_variation'),
            'order' => 'ASC',
        ));
        $products = get_posts($product_args);
        $image_array = array();

        if ($products || !is_wp_error($products)) {
            foreach ($products as $key => $product) {
                $attachments = self::getProductImages($product);
                if (!empty($attachments)) {

                    foreach ($attachments as $att_id => $attachment) {
                        if (strstr(basename($attachment), '.')) {
                            $image_array[] = str_replace($upload_path['baseurl'], $upload_path['basedir'], $attachment);
                        }
                    }
                }
            }
        }
        return $image_array;
    }

    public static function getProductImages($product) {
        $image_file_names = array();
        $meta_data = get_post_custom($product->ID);

        // Featured image
        if (( $featured_image_id = get_post_thumbnail_id($product->ID))) {
            $image_object = get_post($featured_image_id);

            if ($image_object && $image_object->guid) {
                $temp_images_export_to_csv = $image_object->guid;
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

                $gallery_image_object = get_post($image_id);

                if ($gallery_image_object && $gallery_image_object->guid) {
                    $temp_gallery_images_export_to_csv = $gallery_image_object->guid;
                }
                if (!empty($temp_gallery_images_export_to_csv)) {
                    $image_file_names[] = $temp_gallery_images_export_to_csv;
                }
            }
        }
        return $image_file_names;
    }

    public static function recursive_file_search($directory, $display = Array('.jpeg', '.jpg')) { // not using now ,let it be for future reference and improvments
        $files = array();
        $it = new RecursiveDirectoryIterator($directory);
        foreach (new RecursiveIteratorIterator($it) as $file) {
            $file = str_replace('\\', '/', $file);
            if (in_array(strrchr($file, '.'), $display)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    public static function get_all_products_images_old() { // not using now ,let it be for future reference and improvments
        $upload_path = wp_upload_dir();
        $product_args = apply_filters('woocommerce_csv_product_image_export_args', array(
            'numberposts' => -1,
            'post_status' => array('publish', 'pending', 'private', 'draft'),
            'post_type' => array('product', 'product_variation'),
            'order' => 'ASC',
        ));
        $products = get_posts($product_args);
        $image_array = array();

        if ($products || !is_wp_error($products)) {
            foreach ($products as $key => $product) {
                $attachments = get_children(array('post_parent' => $product->ID,
                    'post_status' => 'inherit',
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image',
                    'order' => 'ASC',
                    'orderby' => 'menu_order ID'));

                foreach ($attachments as $att_id => $attachment) {
                    $image_array[] = str_replace($upload_path['baseurl'], $upload_path['basedir'], $attachment->guid);
                }
            }
        }
        return $image_array;
    }

    function GetImageUrlsByProductId($productId) { // not using now ,let it be for future reference and improvments
        $product = new WC_product($productId);
        $attachmentIds = $product->get_gallery_attachment_ids();
        $imgUrls = array();
        foreach ($attachmentIds as $attachmentId) {
            $imgUrls[] = wp_get_attachment_url($attachmentId);
        }

        return $imgUrls;
    }

}
