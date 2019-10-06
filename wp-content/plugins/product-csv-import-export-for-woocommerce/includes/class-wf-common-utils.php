<?php
if (!defined('WPINC')) {
    die();
}

class WF_ProdImpExpCsv_Common_Utils {

    public static function is_woocommerce_prior_to($version) {

        $woocommerce_is_pre_version = (!defined('WC_VERSION') || version_compare(WC_VERSION, $version, '<')) ? true : false;
        return $woocommerce_is_pre_version;
    }

}
