<?php

if (!defined('WPINC'))
    exit; // Exit if accessed directly

class WF_PrRevImpExpCsv_ImportCron {

    public $settings;
    public $file_url;
    public $error_message;

    public function __construct() {
        add_filter('cron_schedules', array($this, 'wf_auto_import_schedule'));
        add_action('init', array($this, 'wf_new_scheduled_pr_rev_import'));
        add_action('wf_pr_rev_csv_im_ex_auto_import_products', array($this, 'wf_scheduled_import_products'));
        $this->settings = get_option('woocommerce_' . WF_PROD_IMP_EXP_ID . '_settings', null);
        $this->settings_ftp_import = get_option('wf_review_import_ftp', null);
        $this->imports_enabled = FALSE;

        if (isset($this->settings['rev_auto_import']) && $this->settings['rev_auto_import'] === 'Enabled' && isset($this->settings['rev_enable_ftp_ie']) && $this->settings['rev_enable_ftp_ie'] === TRUE)
            $this->imports_enabled = TRUE;
    }

    public function wf_auto_import_schedule($schedules) {
        if ($this->imports_enabled) {
            $import_interval = $this->settings['rev_auto_import_interval'];
            if ($import_interval) {
                $schedules['rev_import_interval'] = array(
                    'interval' => (int) $import_interval * 60,
                    'display' => sprintf(__('Every %d minutes', 'wf_csv_import_export'), (int) $import_interval)
                );
            }
        }
        return $schedules;
    }

    public function wf_new_scheduled_pr_rev_import() {
        if ($this->imports_enabled) {
            if (!wp_next_scheduled('wf_pr_rev_csv_im_ex_auto_import_products')) {
                $start_time = $this->settings['rev_auto_import_start_time'];
                $current_time = current_time('timestamp');
                if ($start_time) {
                    if ($current_time > strtotime('today ' . $start_time, $current_time)) {
                        $start_timestamp = strtotime('tomorrow ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    } else {
                        $start_timestamp = strtotime('today ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    }
                } else {
                    $import_interval = $this->settings['rev_auto_import_interval'];
                    $start_timestamp = strtotime("now +{$import_interval} minutes");
                }
                wp_schedule_event($start_timestamp, 'rev_import_interval', 'wf_pr_rev_csv_im_ex_auto_import_products');
            }
        }
    }

    public static function load_wp_importer() {
        // Load Importer API
        require_once ABSPATH . 'wp-admin/includes/import.php';

        if (!class_exists('WP_Importer')) {
            $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
            if (file_exists($class_wp_importer)) {
                require $class_wp_importer;
            }
        }
    }

    public function wf_scheduled_import_products() {

        if (!defined('WP_LOAD_IMPORTERS'))
            define('WP_LOAD_IMPORTERS', true);

        if (!class_exists('WooCommerce')) :
            require ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';
        endif;

        WF_PrRevImpExpCsv_ImportCron::product_importer();
        if ($this->handle_ftp_for_autoimport()) {
            $mapping = '';
            $eval_field = '';
            $start_pos = 0;
            $end_pos = '';
            if ($this->settings['rev_auto_import_profile'] !== '') {
                $profile_array = get_option('wf_prod_review_csv_imp_exp_mapping');
                $mapping = $profile_array[$this->settings['rev_auto_import_profile']][0];
                $eval_field = $profile_array[$this->settings['rev_auto_import_profile']][1];
                $start_pos = 0;
                $end_pos = '';
            } 
            if ($this->settings['rev_auto_import_merge']) {
                $_GET['merge'] = 1;
            } else {
                $_GET['merge'] = 0;
            }


            $GLOBALS['WF_CSV_Product_Review_Import']->import_start($this->file_url, $mapping, $start_pos, $end_pos, $eval_field);
            $GLOBALS['WF_CSV_Product_Review_Import']->import();
            $GLOBALS['WF_CSV_Product_Review_Import']->import_end();

            //do_action('wf_new_scheduled_pr_rev_import');
            //wp_clear_scheduled_hook('wf_pr_rev_csv_im_ex_auto_import_products');
            //do_action('wf_new_scheduled_pr_rev_import');

            die();
        } else {
            $GLOBALS['WF_CSV_Product_Review_Import']->hf_log_data_change('review-csv-import', __('Fetching file failed. Reason:' . $this->error_message, 'wf_csv_import_export'));
        }
    }

    public function clear_wf_scheduled_pr_rev_import() {
        wp_clear_scheduled_hook('wf_pr_rev_csv_im_ex_auto_import_products');
    }

    private function handle_ftp_for_autoimport() {


        $enable_ftp_ie = $this->settings['rev_enable_ftp_ie'];
        if (!$enable_ftp_ie)
            return false;

        $ftp_server = $this->settings['rev_ftp_server'];
        $ftp_user = $this->settings['rev_ftp_user'];
        $ftp_password = $this->settings['rev_ftp_password'];
        $use_ftps = $this->settings['rev_use_ftps'];
        $use_pasv = $this->settings['rev_use_pasv'];
        $ftp_server_path = $this->settings['rev_auto_import_file'];


        $local_file = 'wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import-review.csv';
        $server_file = $ftp_server_path;

        $this->error_message = "";
        $success = false;

        // if have SFTP Add-on for Import Export for WooCommerce 
        if (class_exists('class_wf_sftp_import_export')) {
            $sftp_import = new class_wf_sftp_import_export();
            if (!$sftp_import->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
                $this->error_message = "Not able to connect to the server please check <b>FTP Server Host / IP</b> and <b>Port number</b>. \n";
            }

            if (empty($server_file)) {
                $this->error_message = "Please Complete fill the FTP Details. \n";
            } else {
                $file_contents = $sftp_import->get_contents($server_file);
                if (!empty($file_contents)) {
                    file_put_contents(ABSPATH . $local_file, $file_contents);
                    $this->error_message = "";
                    $success = true;
                } else {
                    $this->error_message = "Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b> .\n";
                }
            }
        } else {

            $ftp_conn = $use_ftps ? ftp_ssl_connect($ftp_server) : ftp_connect($ftp_server);

            if ($ftp_conn == false) {
                $this->error_message = "There is connection problem\n";
            }

            if (empty($this->error_message)) {
                if (ftp_login($ftp_conn, $ftp_user, $ftp_password) == false) {
                    $this->error_message = "Not able to login \n";
                }
            }
            if (empty($this->error_message)) {
                if ($use_pasv) {
                    ftp_pasv($ftp_conn, TRUE);
                }
                if (ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) {
                    $this->error_message = "";
                    $success = true;
                } else {
                    $this->error_message = "There was a problem while downloading the specified File <b>" . $server_file . "</b> .\n";
                }
            }
            if ($ftp_conn) {
                ftp_close($ftp_conn);
            }
        }
        if ($success) {
            $this->file_url = ABSPATH . $local_file;
        } else {
            die($this->error_message);
        }
        return true;
    }

    public static function product_importer() {
        if (!defined('WP_LOAD_IMPORTERS')) {
            return;
        }

        self::load_wp_importer();

        // includes
        require_once 'importer/class-wf-pr_revimpexpcsv-import.php';
        require_once 'importer/class-wf-csv-parser-review.php';

        if (!class_exists('WC_Logger')) {
            $class_wc_logger = ABSPATH . 'wp-content/plugins/woocommerce/includes/class-wc-logger.php';
            if (file_exists($class_wc_logger)) {
                require $class_wc_logger;
            }
        }

        $class_wc_logger = ABSPATH . 'wp-includes/pluggable.php';
        require_once($class_wc_logger);
        wp_set_current_user(1); // escape user access check while running cron

        $GLOBALS['WF_CSV_Product_Review_Import'] = new WF_PrRevImpExpCsv_Import();
        $GLOBALS['WF_CSV_Product_Review_Import']->import_page = 'product_reviews_csv_cron';
        $GLOBALS['WF_CSV_Product_Review_Import']->delimiter = ','; // need to give option in settingn , if some queries are coming
    }

}
