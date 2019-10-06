<?php

if (!defined('ABSPATH')) {
    exit;
}

class WT_ProductImpExpXML_Exporter extends XMLWriter {

    private $ids;

    public function __construct($ids) {

        $this->ids = $ids;
        $this->openMemory();
        $this->setIndent(TRUE);
        $xml_version = '1.0';
        $xml_encoding = 'UTF-8';
        //$xml_standalone = 'no';
        $this->startDocument($xml_version, $xml_encoding /*, $xml_standalone*/);
    }

    public function do_xml_export($filename, $xml) {
        global $wpdb;
        $wpdb->hide_errors();
        @set_time_limit(0);
        if (function_exists('apache_setenv'))
            @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 0);
        @ob_end_clean();



            $charset = get_option('blog_charset');
            header(apply_filters('hf_order_import_export_xml_content_type', "Content-Type: application/xml; charset={$charset}"));
            header(sprintf('Content-Disposition: attachment; filename="%s"', $filename.".xml"));
            header('Pragma: no-cache');
            header('Expires: 0');
            if (version_compare(PHP_VERSION, '5.6', '<')) {
                iconv_set_encoding('output_encoding', $charset);
            } else {
                ini_set('default_charset', 'UTF-8');
            }

            echo $xml;
            exit;
    }

    public function prepare_xml_data_from_data_array($data_array, $xmlns = NULL) {
        $xmlnsurl = $xmlns;
        $keys = array_keys($data_array);
        $root_tag = reset($keys);
        WT_ProductImpExpXML_Exporter::array_to_xml($this, $root_tag, $data_array[$root_tag], $xmlnsurl);
        return $this->output_xml();
    }

    public static function array_to_xml($xml_writer, $element_key, $element_value = array(), $xmlnsurl = NULL) {
        if (!empty($xmlnsurl)) {
            $my_root_tag = $element_key;
            $xml_writer->startElementNS(null, $element_key, $xmlnsurl);
        } else {
            $my_root_tag = '';
        }

        if (is_array($element_value)) {
            //handle attributes
            if ('@attributes' === $element_key) {
                foreach ($element_value as $attribute_key => $attribute_value) {

                    $xml_writer->startAttribute($attribute_key);
                    $xml_writer->text($attribute_value);
                    $xml_writer->endAttribute();
                }
                return;
            }

            //handle order elements
            if (is_numeric(key($element_value))) {

                foreach ($element_value as $child_element_key => $child_element_value) {

                    if ($element_key !== $my_root_tag)
                        $xml_writer->startElement($element_key);
                    foreach ($child_element_value as $sibling_element_key => $sibling_element_value) {
                        self::array_to_xml($xml_writer, $sibling_element_key, $sibling_element_value);
                    }

                    $xml_writer->endElement();
                }
            } else {

                if ($element_key !== $my_root_tag)
                    $xml_writer->startElement($element_key);

                foreach ($element_value as $child_element_key => $child_element_value) {
                    self::array_to_xml($xml_writer, $child_element_key, $child_element_value);
                }

                $xml_writer->endElement();
            }
        } else {

            //handle single elements
            if ('@value' == $element_key) {

                $xml_writer->text($element_value);
            } else {

                //wrap element in CDATA tag if it contain illegal characters
                if (false !== strpos($element_value, '<') || false !== strpos($element_value, '>')) {                    
                    $arr = explode(':',$element_key); 
                    if(isset($arr[1])){
                        $xml_writer->startElementNS($arr[0],$arr[1],$arr[0]);
                    }else{
                        $xml_writer->startElement($element_key);
                    }                    
                    $xml_writer->writeCdata($element_value);
                    $xml_writer->endElement();
                    
                } else {
                    // Write full namespaced element tag using xmlns
                    $arr = explode(':',$element_key); 
                    if(count($arr)>1){
                      $xml_writer->writeElementNS($arr[0],$arr[1],$arr[0], $element_value);  
                    }else{
                        $xml_writer->writeElement($element_key, $element_value);                        
                    }
                    
                    
//                    if($ns = strstr($element_key, ':',TRUE)) {
//                        $element_key = strstr($element_key, ':');
//                        $element_key = str_replace(':', '', $element_key);
//                        $xml_writer->writeElementNS($ns,$element_key,'product-'.$ns, $element_value);
//                    }else{
//                        $xml_writer->writeElement($element_key, $element_value);                        
//                    }
                }
            }

            return;
        }
    }

    private function output_xml() {
        $this->endDocument();
        return $this->outputMemory();
    }


}