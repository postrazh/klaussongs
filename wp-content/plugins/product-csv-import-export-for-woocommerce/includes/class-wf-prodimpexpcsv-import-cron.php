<?php

if (!defined('WPINC'))
    exit; // Exit if accessed directly

class WF_ProdImpExpCsv_ImportCron {

    public $settings;
    public $file_url;
    public $error_message;

    public function __construct() {
        add_filter('cron_schedules', array($this, 'wf_auto_import_schedule'), 11);
        add_action('init', array($this, 'wf_new_scheduled_import'));
        add_action('wf_woocommerce_csv_im_ex_auto_import_products', array($this, 'wf_scheduled_import_products'));
        $this->settings = get_option('woocommerce_' . WF_PROD_IMP_EXP_ID . '_settings', null);
        $this->settings_ftp_import = get_option('wf_product_import_ftp', null);
        $this->imports_enabled = FALSE;
        if (isset($this->settings['pro_auto_import']) && $this->settings['pro_auto_import'] === 'Enabled' && isset($this->settings['pro_enable_ftp_ie']) && $this->settings['pro_enable_ftp_ie'] === TRUE)
            $this->imports_enabled = TRUE;
    }

    public function wf_auto_import_schedule($schedules) {
        if ($this->imports_enabled) {
            $import_interval = $this->settings['pro_auto_import_interval'];
            if ($import_interval) {
                $schedules['pro_import_interval'] = array(
                    'interval' => (int) $import_interval * 60,
                    'display' => sprintf(__('Every %d minutes', 'wf_csv_import_export'), (int) $import_interval)
                );
            }
        }
        return $schedules;
    }

    public function wf_new_scheduled_import() {
        if ($this->imports_enabled) {
            if (!wp_next_scheduled('wf_woocommerce_csv_im_ex_auto_import_products')) {
                $start_time = $this->settings['pro_auto_import_start_time'];
                $current_time = current_time('timestamp');
                if ($start_time) {
                    if ($current_time > strtotime('today ' . $start_time, $current_time)) {
                        $start_timestamp = strtotime('tomorrow ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    } else {
                        $start_timestamp = strtotime('today ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    }
                } else {
                    $import_interval = $this->settings['pro_auto_import_interval'];
                    $start_timestamp = strtotime("now +{$import_interval} minutes");
                }
                wp_schedule_event($start_timestamp, 'pro_import_interval', 'wf_woocommerce_csv_im_ex_auto_import_products');
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
        //error_log("test run by wp-cron" , 3 , ABSPATH . '/wp-content/uploads/wc-logs/my-cron-log.txt');        
        define('WP_LOAD_IMPORTERS', true);
        if (!class_exists('WooCommerce')) :
            require ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';
        endif;
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        ignore_user_abort(true);
        $arg['delimiter'] = (!empty($this->settings['pro_auto_import_delimiter']) ) ? $this->settings['pro_auto_import_delimiter'] : ',';
        $arg['new_prod_status'] = (!empty($this->settings['pro_auto_new_prod_status']) ) ? $this->settings['pro_auto_new_prod_status'] : '';
        $arg['prod_use_chidren_sku'] = (!empty($this->settings['prod_auto_use_chidren_sku']) ) ? true : false;
        $arg['use_sku_upsell_crosssell'] = (!empty($this->settings['pro_auto_use_sku_upsell_crosssell']) ) ? true : false;
        $arg['merge_empty_cells']= (!empty($this->settings['pro_auto_merge_empty_cells']) ) ? true : false;
                
        $multi_csv_import_enabled = apply_filters('hf_multi_csv_import_enabled', FALSE);

        self::product_importer($arg);  

        $GLOBALS['WF_CSV_Product_Import']->hf_log_data_change('csv-import', "---------------".__('Start: Cron Import started at ', 'wf_csv_import_export').date('Y-m-d H:i:s')."---------------");

        if ($this->handle_ftp_for_autoimport($multi_csv_import_enabled)) {
            $mapping = '';
            $eval_field = '';
            $start_pos = 0;
            $end_pos = '';
            if ($this->settings['pro_auto_import_profile'] !== '') {
                $profile_array = get_option('wf_prod_csv_imp_exp_mapping');
                $mapping = $profile_array[$this->settings['pro_auto_import_profile']][0];
                $eval_field = $profile_array[$this->settings['pro_auto_import_profile']][1];                
            } 

            $_GET['merge'] = (($this->settings['pro_auto_import_merge']) ? 1 : 0 );

            $_GET['skip_new'] = (($this->settings['pro_auto_import_skip']) ? 1 : 0);

            $_GET['delete_products'] = (($this->settings['pro_auto_delete_products']) ? 1 : 0);
            
            $_GET['merge_empty_cells'] = (($this->settings['pro_auto_merge_empty_cells']) ? 1 : 0);

            //echo wp_next_scheduled('wf_woocommerce_csv_im_ex_auto_import_products').'<br/>';
            //echo date('Y-m-d H:i:s' , wp_next_scheduled('wf_woocommerce_csv_im_ex_auto_import_products'));
            //echo $_GET['merge'];exit;

            
            if (WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
                $memory = size_format(woocommerce_let_to_num(ini_get('memory_limit')));
                $wp_memory = size_format(woocommerce_let_to_num(WP_MEMORY_LIMIT));
            } else {
                $memory = size_format(wc_let_to_num(ini_get('memory_limit')));
                $wp_memory = size_format(wc_let_to_num(WP_MEMORY_LIMIT));
            }
            $GLOBALS['WF_CSV_Product_Import']->hf_log_data_change('csv-import', '---[ New Import ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory);
            
            $delete_server_file = apply_filters('hf_delete_remote_csv_after_product_import', FALSE); // To delete the CSV file from server after importing the CSV.
            
            if ($multi_csv_import_enabled && is_array($this->file_url)) {

                foreach ($this->file_url as $key => $file_url) {
                    $GLOBALS['WF_CSV_Product_Import']->import_start($file_url, $mapping, $start_pos, $end_pos, $eval_field);
                    $GLOBALS['WF_CSV_Product_Import']->import();
                    $GLOBALS['WF_CSV_Product_Import']->import_end();

                    if ($_GET['delete_products'] == 1) {
                        $GLOBALS['WF_CSV_Product_Import']->delete_products_not_in_csv();
                    }

                    unlink($file_url);
                }
                if ($delete_server_file) {
                    $this->delete_file_from_server($multi_csv_import_enabled);
                }
            } else {
                $GLOBALS['WF_CSV_Product_Import']->import_start($this->file_url, $mapping, $start_pos, $end_pos, $eval_field);
                $GLOBALS['WF_CSV_Product_Import']->import();
                $GLOBALS['WF_CSV_Product_Import']->import_end();


                if ($_GET['delete_products'] == 1) {
                    $GLOBALS['WF_CSV_Product_Import']->delete_products_not_in_csv();
                }
                if($delete_server_file){
                    $this->delete_file_from_server();
                }
            }

            //do_action('wf_new_scheduled_import');
            //wp_clear_scheduled_hook('wf_woocommerce_csv_im_ex_auto_import_products');
            //do_action('wf_new_scheduled_import');
            
            do_action('wt_pipe_cron_ended');
            
            /* Regenerate all products shop thumbnail using woocommerce functionality which introduced in WC3.3.0 */
            if(!$this->settings['pro_auto_stop_thumbnail_regen'] &&  WC()->version >= '3.3'){
                WC_Regenerate_Images::queue_image_regeneration();                
            }
            
            $GLOBALS['WF_CSV_Product_Import']->hf_log_data_change('csv-import', '---------------'.__('End: Cron Import ended at ', 'wf_csv_import_export').date('Y-m-d H:i:s')."--------------- \n");
            die();
        } else {
            $GLOBALS['WF_CSV_Product_Import']->hf_log_data_change('csv-import', __('Fetching file failed. Reason:' . $this->error_message, 'wf_csv_import_export'));
            $GLOBALS['WF_CSV_Product_Import']->hf_log_data_change('csv-import', '---------------'.__('End: Cron Import ended with errors at ', 'wf_csv_import_export').date('Y-m-d H:i:s')."--------------- \n");

        }
    }

    public function clear_wf_scheduled_import() {
        wp_clear_scheduled_hook('wf_woocommerce_csv_im_ex_auto_import_products');
    }

    private function handle_ftp_for_autoimport($multi_csv_import_enabled = false) {
        $enable_ftp_ie = $this->settings['pro_enable_ftp_ie'];
        $this->error_message = "";
        $success = false;
        if (!$enable_ftp_ie){
            $this->error_message = __("Please enable auto import.");;
            return false;
        }
        $ftp_server = $this->settings['pro_ftp_server'];
        $ftp_user = $this->settings['pro_ftp_user'];
        $ftp_password = $this->settings['pro_ftp_password'];
        $ftp_port = !empty($this->settings['pro_ftp_port']) ? $this->settings['pro_ftp_port'] : 21;
        $use_ftps = $this->settings['pro_use_ftps'];
        $use_pasv = $this->settings['pro_use_pasv'];
        $ftp_server_path = isset($this->settings['pro_auto_import_file']) ? $this->settings['pro_auto_import_file'] : null;


        $local_file = 'wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import.csv';
        $server_file = $ftp_server_path;
        
        // if have SFTP Add-on for Import Export for WooCommerce 
        if (class_exists('class_wf_sftp_import_export')) {
            $sftp_import = new class_wf_sftp_import_export();
            if (!$sftp_import->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
                $this->error_message = __("Not able to connect to the server please check <b>FTP Server Host / IP</b> and <b>Port number</b>.");
                return false;
            }

            if (empty($server_file)) {
                $this->error_message = __("Please Complete fill the FTP Details.");
                return false;
            } else {
                $file_contents = $sftp_import->get_contents($server_file);
                if (!empty($file_contents)) {
                    file_put_contents(ABSPATH . $local_file, $file_contents);
                    $this->error_message = "";
                    $success = true;
                } else {
                    $this->error_message = __("Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b>.");
                    return false;
                }
            }
        } else {

            $ftp_conn = $use_ftps ? @ftp_ssl_connect($ftp_server, $ftp_port) : ftp_connect($ftp_server, $ftp_port);

            if ($ftp_conn == false) {
                $this->error_message = __("Could not connect to the host. Server Host Name / IP or Port may be wrong.");
                return false;
            }

            if (empty($this->error_message)) {
                if (@ftp_login($ftp_conn, $ftp_user, $ftp_password) == false) {
                    $this->error_message = __("Connected to host but could not login. Server UserID or Password may be wrong or Try with / without FTPS.");
                    return false;
                }
            }
            if (empty($this->error_message)) {
                if ($use_pasv) {
                    ftp_pasv($ftp_conn, TRUE);
                }
                
                if ($multi_csv_import_enabled) {

                    $server_csv_files = ftp_nlist($ftp_conn, $ftp_server_path."/*.csv");
                    if ($server_csv_files) {
                        foreach ($server_csv_files as $key => $server_file1) {
                            if (@ftp_get($ftp_conn, ABSPATH . "wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import_$key.csv", $ftp_server_path.'/'.$server_file1, FTP_BINARY)) {
                                $this->error_message = "";
                                $success = true;
                            } else {
                                ftp_pasv($ftp_conn, TRUE); // Enabling passive mode to retry
                                if (@ftp_get($ftp_conn, ABSPATH . "wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import_$key.csv", $ftp_server_path.'/'.$server_file1, FTP_BINARY)) { //Retrying after enable passive mode 
                                    $this->error_message = "";
                                    $success = true;
                                } else {
                                    $this->error_message = __("Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b>.");
                                    return false;
                                }   
                            }
                        }
                    }

                    if (!$success) {
                        return FALSE;
                        die($this->error_message);
                    }
                } else {
                    if (@ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) {
                        $this->error_message = "";
                        $success = true;
                    } else {
                         ftp_pasv($ftp_conn, TRUE); // Enabling passive mode to retry
                         if (@ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) { //Retrying after enable passive mode 
                            $this->error_message = "";
                            $success = true;
                         }else{
                            $this->error_message = __("Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b>.");
                            return false;
                         }
                    }
                }
            }
            if ($ftp_conn) {
                ftp_close($ftp_conn);
            }
        }

        if ($success) {

            if ($multi_csv_import_enabled) {
                if ($server_csv_files) {
                    foreach ($server_csv_files as $key => $server_file) {
                        $file = ABSPATH . "wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import_$key.csv";
                        if (file_exists($file)) {
                            if ($GLOBALS['WF_CSV_Product_Import']->hf_mime_content_type($file) === 'application/xml' || $GLOBALS['WF_CSV_Product_Import']->hf_mime_content_type($file) === 'text/xml') // introduced XML import
                                $file = $GLOBALS['WF_CSV_Product_Import']->xml_import($file);
                            $this->file_url[] = $file;
                        }
                    }
                }
            } else {

                $file = ABSPATH . $local_file;                
                if ($GLOBALS['WF_CSV_Product_Import']->hf_mime_content_type($file) === 'application/xml' || $GLOBALS['WF_CSV_Product_Import']->hf_mime_content_type($file) === 'text/xml') // introduced XML import
                    $file = $GLOBALS['WF_CSV_Product_Import']->xml_import($file);                
                $this->file_url = $file;
            }
        } else {
            return FALSE;
            die($this->error_message);
        }

        return true;
    }

    public static function product_importer($arg=array()) {
        if (!defined('WP_LOAD_IMPORTERS')) {
            return;
        }

        self::load_wp_importer();

        // includes
        require_once 'importer/class-wf-prodimpexpcsv-product-import.php';
        require_once 'importer/class-wf-csv-parser.php';

        if (!class_exists('WC_Logger')) {
            $class_wc_logger = ABSPATH . 'wp-content/plugins/woocommerce/includes/class-wc-logger.php';
            if (file_exists($class_wc_logger)) {
                require $class_wc_logger;
            }
        }

        $class_wc_logger = ABSPATH . 'wp-includes/pluggable.php';
        require_once($class_wc_logger);
        wp_set_current_user(1); // escape user access check while running cron

        $GLOBALS['WF_CSV_Product_Import'] = new WF_ProdImpExpCsv_Product_Import();
        $GLOBALS['WF_CSV_Product_Import']->import_page = 'woocommerce_csv_cron';
        $GLOBALS['WF_CSV_Product_Import']->delimiter = $arg['delimiter'];
        $GLOBALS['WF_CSV_Product_Import']->new_prod_status = $arg['new_prod_status'];
        $GLOBALS['WF_CSV_Product_Import']->prod_use_chidren_sku = $arg['prod_use_chidren_sku'];
        $GLOBALS['WF_CSV_Product_Import']->use_sku_upsell_crosssell = $arg['use_sku_upsell_crosssell'];
        $GLOBALS['WF_CSV_Product_Import']->merge_empty_cells = $arg['merge_empty_cells'];
    }
    
    
    public function delete_file_from_server($multi_csv_import_enabled = false) {

        $enable_ftp_ie = $this->settings['pro_enable_ftp_ie'];
        if (!$enable_ftp_ie)
            return false;

        $ftp_server = $this->settings['pro_ftp_server'];
        $ftp_user = $this->settings['pro_ftp_user'];
        $ftp_password = $this->settings['pro_ftp_password'];
        $ftp_port = !empty($this->settings['pro_ftp_port']) ? $this->settings['pro_ftp_port'] : 21;
        $use_ftps = $this->settings['pro_use_ftps'];
        $use_pasv = $this->settings['pro_use_pasv'];
        $ftp_server_path = isset($this->settings['pro_auto_import_file']) ? $this->settings['pro_auto_import_file'] : null;

        $server_file = $ftp_server_path;

        $this->error_message = "";
        $success = false;

        // if have SFTP Add-on for Import Export for WooCommerce 
        if (class_exists('class_wf_sftp_import_export')) {
            $sftp_import = new class_wf_sftp_import_export();
            if (!$sftp_import->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
                $this->error_message = __("Not able to connect to the server please check <b>sFTP Server Host / IP</b> and <b>Port number</b>. \n");
            }
            if (empty($server_file)) {
                $this->error_message = __("Please Complete fill the sFTP Details. \n");
            } else {
                if (!$sftp_import->delete_file($server_file)) {
                    $this->error_message = __("Failed to Delete Specified file in sFTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b> .\n");
                }
            }
        } else {

            $ftp_conn = $use_ftps ? @ftp_ssl_connect($ftp_server, $ftp_port) : ftp_connect($ftp_server, $ftp_port);

            if ($ftp_conn == false) {
                $this->error_message = __("Could not connect to the host. Server Host Name / IP or Port may be wrong.\n");
            }

            if (empty($this->error_message)) {
                if (@ftp_login($ftp_conn, $ftp_user, $ftp_password) == false) {
                    $this->error_message = __("Connected to host but could not login. Server UserID or Password may be wrong or Try with / without FTPS .\n");
                }
            }
            if (empty($this->error_message)) {
                if ($use_pasv) {
                    ftp_pasv($ftp_conn, TRUE);
                }

                if ($multi_csv_import_enabled == true) {
                    $server_csv_files = ftp_nlist($ftp_conn, $ftp_server_path . "/*.csv");
                    if ($server_csv_files) {
                        $s_count = $f_count = 0;
                        foreach ($server_csv_files as $key => $server_file1) {
                            if (@ftp_delete($ftp_conn, $ftp_server_path.'/'.$server_file1)) {
                                $s_count++;
                            } else {
                                $f_count++;
                            }
                        }

                        if ($s_count > 0) {
                            $success = true;
                        }
                        if ($f_count > 0) {
                            $this->error_message = __("Failed to Delete Specified file in FTP Server File Path.");
                        }
                    }
                } else {
                    if (@ftp_delete($ftp_conn, $server_file)) {
                        $success = true;
                    } else {
                        $this->error_message = __("Could not delete $server_file");
                    }
                }
            }

            if ($ftp_conn) {
                ftp_close($ftp_conn);
            }
        }

        if ($success) {
            $GLOBALS['WF_CSV_Product_Import']->hf_log_data_change('csv-import', __('Delete Server File After Import: Successful', 'wf_csv_import_export'));
        } else {
            $GLOBALS['WF_CSV_Product_Import']->hf_log_data_change('csv-import', __('Delete Server File After Import: Failed. Possible Reasons:- ', 'wf_csv_import_export') . $this->error_message);
        }
    }

}