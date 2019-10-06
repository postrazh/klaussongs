<?php

if (!defined('ABSPATH')) {
    exit;
}

class ProductExpXMLBase_Exporter {

    public function do_export($header_row = array(), $row_data = array()) {

        $product_array = array();
        foreach ($row_data as $key => $value) {
            foreach ($value as $data_key => $data) {
                $header_key = $header_row[$data_key];
                if (strpos($header_key, ' ') !== false) {
                    $header_key = str_replace(' ', '-', $header_key);
                }
                $product_array[$key][str_replace('"', '', $header_key)] = $data;
            }
        }
        include_once( 'class-wt-product-exp-xml.php' );
        $export = new WT_ProductImpExpXML_Exporter(array());
        $filename = 'wt_product_xml';
        $data_array = array('Products' => array('Product' => $product_array));
        $export->do_xml_export($filename, $export->prepare_xml_data_from_data_array($data_array));
        die();
    }

}
