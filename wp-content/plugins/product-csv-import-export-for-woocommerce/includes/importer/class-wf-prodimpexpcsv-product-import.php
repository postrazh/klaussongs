<?php
/**
 * WordPress Importer class for managing the import process of a CSV file
 *
 * @package WordPress
 * @subpackage Importer
 */
/**
 * @var array, array of product id to be deleted
 */
$product_to_be_deleted = array();

if (!class_exists('WP_Importer'))
    return;

class WF_ProdImpExpCsv_Product_Import extends WP_Importer {

    var $id;
    var $file_url;
    var $delimiter;
    var $profile;
    var $merge;
    var $skip_new;
    var $merge_empty_cells;

    /**
     *
     * @var boolean, If true, delete the products from product page which are not available in CSV
     */
    var $delete_products;

    /**
     * To import / Merge upsell, crosssells using sku
     * @var boolean, If true, import or merge the Upsells and  Crosssells using sku
     */
    var $use_sku_upsell_crosssell;

    /**
     * To proceed with thumbnail regeneration or not
     * @var boolean, If true stop the Thumbnail Regeneration. 
     */
    var $pro_stop_thumbnail_regen;

    /**
     * Set the product status
     * @var string, Set the new product status to the given status. <br />like draft / pending / publish regardless of product status given in CSV
     */
    var $new_prod_status;

    /**
     * To import linked products of grouped products, True if SKU is given for children column in CSV.
     * @var boolean Use SKU to import children of grouped products if true, use ID if false
     */
    var $prod_use_chidren_sku;
    // mappings from old information to new
    var $processed_terms = array();
    var $processed_posts = array();
    var $post_orphans = array();
    var $attachments = array();
    var $upsell_skus = array();
    var $crosssell_skus = array();
    // Results
    var $import_results = array();

    /**
     * Constructor
     */
    public function __construct() {
                
        if (WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
            $this->log = new WC_Logger();
        } else {
            $this->log = wc_get_logger();
        }
        //require_once(dirname(__DIR__) . '/class-wf-piep-helper.php');
        $this->import_page = 'woocommerce_csv';
        $this->file_url_import_enabled = apply_filters('woocommerce_csv_product_file_url_import_enabled', true);
        $this->decentFgetcsv = (version_compare(PHP_VERSION, '5.3.0') >= 0);
    }

    public function hf_log_data_change($content = 'csv-import', $data = '') {
        if (WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
            $this->log->add($content, $data);
        } else {
            $context = array('source' => $content);
            $this->log->log("debug", $data, $context);
        }
    }

    /**
     * Registered callback function for the WordPress Importer
     *
     * Manages the three separate stages of the CSV import process
     */
    public function dispatch() {
        global $woocommerce, $wpdb;

        $this->merge = (( ! empty( $_POST['merge'] ) || ! empty( $_GET['merge'] ) ) ? 1 : 0 );
        $this->skip_new = (( ! empty( $_POST['skip_new'] ) || ! empty( $_GET['skip_new'] ) ) ? 1 : 0 );

        if (!empty($_POST['delimiter'])) {
            $this->delimiter = stripslashes(trim($_POST['delimiter']));
        } else if (!empty($_GET['delimiter'])) {
            $this->delimiter = stripslashes(trim($_GET['delimiter']));
        }

        if (!$this->delimiter)
            $this->delimiter = ',';

        if (!empty($_POST['profile'])) {
            $this->profile = stripslashes(trim($_POST['profile']));
        } else if (!empty($_GET['profile'])) {
            $this->profile = stripslashes(trim($_GET['profile']));
        }
        if (!$this->profile)
            $this->profile = '';
               
        if(!$this->merge_empty_cells){
            $this->merge_empty_cells = (!empty($_POST['merge_empty_cells']) || !empty($_GET['merge_empty_cells']) ) ? 1 : 0;
        }
        
        $this->delete_products = (!empty($_POST['delete_products']) || !empty($_GET['delete_products']) ) ? 1 : 0;
        
        if(!$this->use_sku_upsell_crosssell ){
            $this->use_sku_upsell_crosssell = !empty($_POST['use_sku_upsell_crosssell']) ? 1 : 0;
        }

        if(!$this->pro_stop_thumbnail_regen){
            $this->pro_stop_thumbnail_regen = !empty($_POST['pro_stop_thumbnail_regen']) ? 1 : 0;
        }
        if (!$this->new_prod_status) {
            $this->new_prod_status = !empty($_POST['new_prod_status']) ? $_POST['new_prod_status'] : null;
        }
        if (!$this->prod_use_chidren_sku){
            $this->prod_use_chidren_sku = !empty($_POST['prod_use_chidren_sku']) ? true : false;
        }
        
        $step = empty($_GET['step']) ? 0 : (int) $_GET['step'];
        switch ($step) {
            case 0 :
                $this->header();
                $this->greet();
                break;
            case 1 :
                $this->header();
                check_admin_referer('import-upload');
                if (!empty($_GET['file_url']))
                    $this->file_url = esc_attr($_GET['file_url']);
                if (!empty($_GET['file_id']))
                    $this->id = $_GET['file_id'];

                if (!empty($_GET['clearmapping']) || $this->handle_upload())
                    $this->import_options();
                else
                    _e('Error with handle_upload!', 'wf_csv_import_export');
                break;
            case 2 :
                $this->header();

                check_admin_referer('import-woocommerce');
                
                $this->id = (int) $_POST['import_id'];
                            
                if ($this->file_url_import_enabled)
                    $this->file_url = esc_attr($_POST['import_url']);

                if ($this->id)
                    $file = get_attached_file($this->id);
                else if ($this->file_url_import_enabled)
                    $file = ABSPATH . $this->file_url;
                
                if ($this->hf_mime_content_type($file) === 'application/xml' || $this->hf_mime_content_type($file) === 'text/xml') // introduced XML import
                    $file = $this->xml_import($file);

                $file = str_replace("\\", "/", $file);
                if ($file) {
                    
                    if (WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
                        $memory = size_format(woocommerce_let_to_num(ini_get('memory_limit')));
                        $wp_memory = size_format(woocommerce_let_to_num(WP_MEMORY_LIMIT));
                    } else {
                        $memory = size_format(wc_let_to_num(ini_get('memory_limit')));
                        $wp_memory = size_format(wc_let_to_num(WP_MEMORY_LIMIT));
                    }
                    $this->hf_log_data_change('csv-import', '---[ New Import start at '.date('Y-m-d H:i:s').' ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory);
                                                                 
                    ?>
                    <table id="import-progress" class="widefat_importer widefat">
                        <thead>
                            <tr>             <th class="status">&nbsp;</th>
                                <th class="row"><?php _e('Row', 'wf_csv_import_export'); ?></th>
                                <th><?php  _e('SKU', 'wf_csv_import_export'); ?></th>             <th><?php _e('Product', 'wf_csv_import_export'); ?></th>
                                <th class="reason"><?php _e('Status Msg', 'wf_csv_import_export'); ?></th>          </tr> 
                        </thead> 
                        <tfoot>         <tr class="importer-loading">             <td colspan="5"></td>         </tr>               </tfoot>      
                        <tbody></tbody> 
                    </table>                    
                    <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            if (! window.console) {
                                window.console = function(){};
                            }
                            var processed_terms = [];
                            var processed_posts = [];
                            var post_orphans = [];
                            var attachments = [];
                            var upsell_skus = [];
                            var crosssell_skus = [];
                            var i = 1;
                            var delete_products = 0;
                            var done_count = 0;
                            var file_id=0;
                            function import_rows(start_pos, end_pos) {
                                var data = {  
                                                action                  : 'woocommerce_csv_import_request',
                                                file                    : '<?php echo addslashes($file); ?>',
                                                mapping                 : '<?php echo json_encode($_POST['map_from'],JSON_HEX_APOS); ?>',
                                                profile                 : '<?php echo $this->profile; ?>',
                                                merge                   : '<?php echo $this->merge; ?>',
                                                skip_new                : '<?php echo $this->skip_new; ?>',
                                                eval_field              : '<?php echo stripslashes(json_encode(($_POST['eval_field']), JSON_HEX_APOS)) ?>',
                                                delimiter               : '<?php echo $this->delimiter; ?>',
                                                delete_products         : '<?php echo $this->delete_products; ?>',
                                                merge_empty_cells       : '<?php echo $this->merge_empty_cells; ?>',
                                                use_sku_upsell_crosssell: '<?php echo $this->use_sku_upsell_crosssell; ?>',
                                                new_prod_status         : '<?php echo $this->new_prod_status; ?>',
                                                prod_use_chidren_sku    : '<?php echo $this->prod_use_chidren_sku; ?>',
                                                start_pos               : start_pos,
                                                end_pos                 : end_pos,
                                                file_id                 : '<?php echo $this->id; ?>',
                                };
                                data.eval_field = $.parseJSON(data.eval_field);
                                return $.ajax({
                                    url:        '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '3', 'merge' => !empty($_GET['merge']) ? '1' : '0', 'skip_new' => !empty($_GET['skip_new']) ? '1' : '0'), admin_url('admin-ajax.php')); ?>',
                                    data:       data,
                                    type:       'POST',
                                    success:    function(response) {
                                                if (response) {

                                                    try {                     // Get the valid JSON only from the returned string
                                                        if (response.indexOf("<!--WC_START-->") >= 0)
                                                                response = response.split("<!--WC_START-->")[1]; // Strip off before after WC_START 
                                                        if (response.indexOf("<!--WC_END-->") >= 0)
                                                                response = response.split("<!--WC_END-->")[0]; // Strip off anything after WC_END

                                                        // Parse
                                                        var results = $.parseJSON(response);
                                                        if (results.error) {

                                                            $('#import-progress tbody').append('<tr id="row-' + i + '" class="error"><td class="status" colspan="5">' + results.error + '</td></tr>');
                                                                i++;
                                                            } else if (results.import_results && $(results.import_results).size() > 0) {

                                                                            $.each(results.processed_terms, function(index, value) {
                                                                            processed_terms.push(value);
                                                                            });
                                                                            $.each(results.processed_posts, function(index, value) {
                                                                            processed_posts.push(value);
                                                                            });
                                                                            $.each(results.post_orphans, function(index, value) {
                                                                            post_orphans.push(value);
                                                                            });
                                                                            $.each(results.attachments, function(index, value) {
                                                                            attachments.push(value);
                                                                            });
                                                                            upsell_skus = jQuery.extend({}, upsell_skus, results.upsell_skus);
                                                                            crosssell_skus = jQuery.extend({}, crosssell_skus, results.crosssell_skus);
                                                                            delete_products = results.delete_products;
                                                                            file_id=results.file_id;
                                                                            $(results.import_results).each(function(index, row) {
                                                                            $('#import-progress tbody').append('<tr id="row-' + i + '" class="' + row['status'] + '"><td><mark class="result" title="' + row['status'] + '">' + row['status'] + '</mark></td><td class="row">' + i + '</td><td>' + row['sku'] + '</td><td>' + row['post_id'] + ' - ' + row['post_title'] + '</td><td class="reason">' + row['reason'] + '</td></tr>');
                                                                                i++;
                                                                            });
                                                                        }

                                                        } catch (err) {}

                                                } else {
                                                            $('#import-progress tbody').append('<tr class="error"><td class="status" colspan="5">' + '<?php _e('AJAX Error', 'wf_csv_import_export'); ?>' + '</td></tr>');
                                                        }

                                                        var w = $(window);
                                                        var row = $("#row-" + (i - 1));
                                                        if (row.length) {
                                                            w.scrollTop(row.offset().top - (w.height() / 2));
                                                        }

                                                        done_count++;
                                                        $('body').trigger('woocommerce_csv_import_request_complete');
                                                },
                                    error:  function (jqXHR, httpStatusMessage, customErrorMessage) {
                                                import_rows(start_pos, end_pos);
                                            }           
                                    });
                                }

                                var rows = [];
                    <?php
                    $limit = apply_filters('woocommerce_csv_import_limit_per_request', 10);                                        
                    $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
                    if ($enc)
                        setlocale(LC_ALL, 'en_US.' . $enc);
                    @ini_set('auto_detect_line_endings', true);

                    $count = 0;
                    $previous_position = 0;
                    $position = 0;
                    $import_count = 0;

// Get CSV positions
                    if (( $handle = @fopen($file, "r") ) !== FALSE) {
                        $csv_delimiter =($this->delimiter=='tab'?"\t":$this->delimiter);

                        while (( $postmeta = ($this->decentFgetcsv) ? fgetcsv($handle, 0, $csv_delimiter, '"', '"') : fgetcsv($handle, 0, $csv_delimiter, '"') ) !== FALSE) {
                            $count++;

                            if ($count >= $limit) {
                                $previous_position = $position;
                                $position = ftell($handle);
                                $count = 0;
                                $import_count ++;

// Import rows between $previous_position $position
                                ?>rows.push([ <?php echo $previous_position; ?>, <?php echo $position; ?> ]); <?php
                            }
                        }

// Remainder
                        if ($count > 0) {
                            ?>rows.push([ <?php echo $position; ?>, '' ]); <?php
                            $import_count ++;

                        }

                        fclose($handle);
                    }
                    ?>

                                var data = rows.shift();
                                var regen_count = 0;
                                var failed_regen_count = 0;
                                import_rows(data[0], data[1]);
                                $('body').on('woocommerce_csv_import_request_complete', function() {
                                        if (done_count == <?php echo $import_count; ?>) {

                                                if (attachments.length && ! (<?php echo $this->pro_stop_thumbnail_regen; ?>)) {

                                                        $('#import-progress tbody').append('<tr class="regenerating"><td colspan="5"><div class="progress"></div></td></tr>');
                                                        index = 0;
                                                        $.each(attachments, function(i, value) {
                                                                regenerate_thumbnail(value);
                                                                index ++;
                                                                if (index == attachments.length) {
                                                                        import_done();
                                                                }
                                                        });
                                                } else {
                                                        import_done();
                                                }
                                        } else {
                                                 // Call next request
                                                data = rows.shift();
                                                import_rows(data[0], data[1]);
                                        }
                                });
                                                                                                                            // Regenerate a specified image via AJAX
                                function regenerate_thumbnail(id) {
                                $.ajax({
                                        type: 'POST',
                                        url: ajaxurl,
                                        data: { action: "woocommerce_csv_import_regenerate_thumbnail", id: id },
                                        success: function(response) {
                                                if (response !== Object(response) || (typeof response.success === "undefined" && typeof response.error === "undefined")) {
                                                        response = new Object;
                                                        response.success = false;
                                                        response.error = "<?php printf(esc_js(__('The resize request was abnormally terminated (ID %s). This is likely due to the image exceeding available memory or some other type of fatal error.', 'wf_csv_import_export')), '" + id + "'); ?>";
                                                }
                                                if (! response.error) {
                                                        regen_count ++;
                                                }
                                                if (! response.success) {
                                                        failed_regen_count++;
                                                }
                                                all_regen_count = failed_regen_count + regen_count;
                                                $('#import-progress tbody .regenerating .progress').css( 'width', ( ( all_regen_count / attachments.length ) * 100 ) + '%' ).html( regen_count + ' / ' + attachments.length + ' <?php echo esc_js(__('thumbnails regenerated.', 'wf_csv_import_export')); ?>' );
                                        },
                                        error: function( response ) {
                                                failed_regen_count++;
                                                all_regen_count = failed_regen_count + regen_count;
                                                $('#import-progress tbody .regenerating .progress').css( 'width', ( ( all_regen_count / attachments.length ) * 100 ) + '%' ).html( regen_count + ' / ' + attachments.length + ' <?php echo esc_js(__('thumbnails regenerated.', 'wf_csv_import_export')); ?>' );
                                        }
                                });
                    }

                    function import_done() {
                            var data = {
                                    action: 'woocommerce_csv_import_request',
                                    file: '<?php echo $file; ?>',
                                    processed_terms: processed_terms,
                                    processed_posts: processed_posts,
                                    post_orphans: post_orphans,
                                    upsell_skus: upsell_skus,
                                    crosssell_skus: crosssell_skus,
                                    delete_products:delete_products,
                                    file_id:file_id,
                            };

                            $.ajax({
                                    url: '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '4', 'merge' => !empty($_GET['merge']) ? 1 : 0, 'skip_new' => !empty($_GET['skip_new']) ? 1 : 0), admin_url('admin-ajax.php')); ?>',
                                    data:       data,
                                    type:       'POST',
                                    success:    function( response ) {

                                            console.log( response );
                                            $('#import-progress tbody').append( '<tr class="complete"><td colspan="5">' + response + '</td></tr>' );
                                            $('.importer-loading').hide();
                                    }
                            });
                    }
        });
</script>
            <?php
                } else {
                    echo '<p class="error">' . __('Error finding uploaded file!', 'wf_csv_import_export') . '</p>';
                }
                break;
            case 3 :                
                // Check access - cannot use nonce here as it will expire after multiple requests
                if (!current_user_can('manage_woocommerce'))
                    die();

                add_filter('http_request_timeout', array($this, 'bump_request_timeout'));

                if (function_exists('gc_enable'))
                    gc_enable();

                @set_time_limit(0);
                @ob_flush();
                @flush();
                $wpdb->hide_errors();

                $file = stripslashes($_POST['file']);
                $mapping = json_decode(stripslashes($_POST['mapping']), true);
                $profile = isset($_POST['profile']) ? $_POST['profile'] : '';
                $eval_field = $_POST['eval_field'];
                $start_pos = isset($_POST['start_pos']) ? absint($_POST['start_pos']) : 0;
                $end_pos = isset($_POST['end_pos']) ? absint($_POST['end_pos']) : '';
                
                $position = $this->import_start($file, $mapping, $start_pos, $end_pos, $eval_field);
                $this->import();
                $this->import_end();
                
                $results = array();
                $results['import_results'] = $this->import_results;
                $results['processed_terms'] = $this->processed_terms;
                $results['processed_posts'] = $this->processed_posts;
                $results['post_orphans'] = $this->post_orphans;
                $results['attachments'] = $this->attachments;
                $results['upsell_skus'] = $this->upsell_skus;
                $results['crosssell_skus'] = $this->crosssell_skus;
                $results['delete_products'] = $this->delete_products;
                $results['file_id'] = isset($_POST['file_id']) ? absint($_POST['file_id']) : 0;
                
                echo "<!--WC_START-->";
                echo json_encode($results);
                echo "<!--WC_END-->";
                exit;
                break;
            case 4 :
                                
                // Check access - cannot use nonce here as it will expire after multiple requests
                if (!current_user_can('manage_woocommerce'))
                    die();

                add_filter('http_request_timeout', array($this, 'bump_request_timeout'));

                if (function_exists('gc_enable'))
                    gc_enable();

                @set_time_limit(0);
                @ob_flush();
                @flush();
                $wpdb->hide_errors();

                $this->processed_terms = isset($_POST['processed_terms']) ? $_POST['processed_terms'] : array();
                $this->processed_posts = isset($_POST['processed_posts']) ? $_POST['processed_posts'] : array();
                $this->post_orphans = isset($_POST['post_orphans']) ? $_POST['post_orphans'] : array();
                $this->crosssell_skus = isset($_POST['crosssell_skus']) ? array_filter((array) $_POST['crosssell_skus']) : array();
                $this->upsell_skus = isset($_POST['upsell_skus']) ? array_filter((array) $_POST['upsell_skus']) : array();
                $this->delete_products = isset($_POST['delete_products']) ? $_POST['delete_products'] : 0;
            
                $file = isset($_POST['file']) ? stripslashes($_POST['file']) : ''; 
                $this->id=isset($_POST['file_id']) ? $_POST['file_id'] : 0;
                
                _e('Step 1...', 'wf_csv_import_export') . ' ';

                wp_defer_term_counting(true);
                wp_defer_comment_counting(true);

                _e('Step 2...', 'wf_csv_import_export') . ' ';

                echo 'Step 3...' . ' '; // Easter egg
                // reset transients for products
                if (function_exists('wc_delete_product_transients')) {
                    wc_delete_product_transients();
                } else {
                    $woocommerce->clear_product_transients();
                }

                delete_transient('wc_attribute_taxonomies');

                $wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_product_type_%')");

                _e('Finalizing...', 'wf_csv_import_export') . ' ';

                $this->backfill_parents();

                if (!empty($this->upsell_skus)) {

                    _e('Linking upsells...', 'wf_csv_import_export') . ' ';

                    foreach ($this->upsell_skus as $post_id => $skus) {
                        $this->link_product_skus('upsell', $post_id, $skus);
                    }
                }

                if (!empty($this->crosssell_skus)) {

                    _e('Linking crosssells...', 'wf_csv_import_export') . ' ';

                    foreach ($this->crosssell_skus as $post_id => $skus) {
                        $this->link_product_skus('crosssell', $post_id, $skus);
                    }
                }

                // SUCCESS
                _e('Finished. Import complete.', 'wf_csv_import_export');


                if ($this->delete_products == 1) {
                    $this->delete_products_not_in_csv();
                }
                
                               
                if(apply_filters('hf_import_processed_id_save_flag',false)){ 
                   update_option('wf_prod_csv_imp_exp_processed_product_ids', array());
                }

                if(!empty($this->id) && $this->id >0){ // deleting temparary file from meadia library if have attachment id
                    wp_delete_attachment( $this->id );
                }else{
                    unlink($file); // deleting temparary file from meadia library by path
                }
               
                $this->import_end();                
                $this->hf_log_data_change('csv-import', '---[ Import end at '.date('Y-m-d H:i:s').']---');
                exit;
                break;
        }

        $this->footer();
    }

    /**
     * format_data_from_csv
     */
    public function format_data_from_csv($data, $enc) {
        return ( $enc == 'UTF-8' ) ? trim($data) : utf8_encode(trim($data));
    }
    
    public function createCsv1($xml, $f) {

        foreach ($xml->children() as $item) {
            $row_data = array_values((array) $item);
            fputcsv($f, $row_data, ',', '"');
        }
    }
    
    public function xml_import1($file) {

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        if ($xml) {
            $header = array_keys((array) $xml->children()->children());
            $fp = fopen($file, 'w');
            fputcsv($fp, $header, ',', '"');
            $this->createCsv($xml, $fp);
            fclose($fp);
        } else {
            echo '<div class="error notice"><p>This XML File Is Not Valid</p></div>';
        }

        return $file;
    }
    
    public function createCsv($xml, $f, $ns) {
        foreach ($xml->children() as $item) {
            $row = array();
            $row_data1 = array();
            $row_data = array_values((array) $item);
            foreach ($ns as $key => $data) {
                $row_data1[] = array_values((array) $item->children($data));
            }

            foreach ($row_data1 as $data1) {
                $row = array_merge($row, $data1);
            }

            $row_data = array_merge($row_data, $row);
            fputcsv($f, $row_data, ',', '"');
            unset($row_data);
        }
    }

    public function xml_import($file) {

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        if ($xml) {
            $nsdata = array();
            $hddata = array();
            $ns = $xml->getNamespaces(true);
            foreach ($ns as $data) {
                $nsdata[$data] = array_keys((array) $xml->children()->children($data));
            }

            foreach ($nsdata as $key => $value) {
                foreach ($value as $data1) {
                    $hddata[] = $key . ":" . $data1;
                }
            }

            $header = array_keys((array) $xml->children()->children());
            $header = array_merge($header, $hddata);
            $fp = fopen($file, 'w');
            fputcsv($fp, $header, ',', '"');
            $this->createCsv($xml, $fp, $ns);
            fclose($fp);
        } else {
            echo '<div class="error notice"><p>This XML File Is Not Valid</p></div>';
        }
        return $file;
    }

    public function hf_mime_content_type($filename) {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/msword',
            'xlsx' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
        $value = explode('.', $filename);
        $ext = strtolower(array_pop($value));
        if (function_exists('mime_content_type')) {
            $mimetype = mime_content_type($filename);
            return $mimetype;
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } elseif (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
    }

    /**
     * Display pre-import options
     */
    public function import_options() {
        $j = 0;
        if ($this->id)
            $file = get_attached_file($this->id);
        else if ($this->file_url_import_enabled)
            $file = ABSPATH . $this->file_url;
        else
            return;
        
        if ($this->hf_mime_content_type($file) === 'application/xml' || $this->hf_mime_content_type($file) === 'text/xml')  // introduced XML import          
            $file = $this->xml_import($file);
       
        // Set locale
        $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
        if ($enc)
            setlocale(LC_ALL, 'en_US.' . $enc);
        @ini_set('auto_detect_line_endings', true);

        // Get headers
        if (( $handle = @fopen($file, "r") ) !== FALSE) {

            $row = $raw_headers = array();

            $csv_delimiter =($this->delimiter=='tab'?"\t":$this->delimiter);
            
            $header = ($this->decentFgetcsv) ? fgetcsv($handle, 0, $csv_delimiter, '"', '"') : fgetcsv($handle, 0, $csv_delimiter, '"');

            while (( $postmeta = ($this->decentFgetcsv) ? fgetcsv($handle, 0, $csv_delimiter, '"', '"') : fgetcsv($handle, 0, $csv_delimiter, '"') ) !== FALSE) {
                foreach ($header as $key => $heading) {
                    if (!$heading)
                        continue;
                    //$s_heading = strtolower($heading);
                    $s_heading = ($heading);
                    $row[$s_heading] = ( isset($postmeta[$key]) ) ? $this->format_data_from_csv($postmeta[$key], $enc) : '';
                    $raw_headers[$s_heading] = $heading;
                }
                break;
            }
            fclose($handle);
        }
        
        $saved_mapping = null;
        $saved_evaluation = null;

        $mapping_from_db = get_option('wf_prod_csv_imp_exp_mapping');

        if ($this->profile !== '' && !empty($_GET['clearmapping'])) {
            unset($mapping_from_db[$this->profile]);
            update_option('wf_prod_csv_imp_exp_mapping', $mapping_from_db);
            $this->profile = '';
        }
        if ($this->profile !== ''){
            $mapping_from_db = $mapping_from_db[$this->profile];

            if ($mapping_from_db && is_array($mapping_from_db) && count($mapping_from_db) == 2 && empty($_GET['clearmapping'])) {
                //if(count(array_intersect_key ( $mapping_from_db[0] , $row)) ==  count($mapping_from_db[0])){	
                $reset_action = 'admin.php?clearmapping=1&amp;profile=' . $this->profile . '&amp;import=' . $this->import_page . '&amp;step=1&amp;merge=' . $this->merge . '&amp;skip_new=' . $this->skip_new. '&amp;file_url=' . $this->file_url . '&amp;delimiter=' . $this->delimiter . '&amp;merge_empty_cells=' . $this->merge_empty_cells . '&amp;file_id=' . $this->id . '';
                $reset_action = esc_attr(wp_nonce_url($reset_action, 'import-upload'));
                echo '<h3>' . __('Columns are pre-selected using the Mapping file: "<b style="color:gray">' . $this->profile . '</b>".  <a href="' . $reset_action . '"> Delete</a> this mapping file.', 'wf_csv_import_export') . '</h3>';
                $saved_mapping = $mapping_from_db[0];
                $saved_evaluation = $mapping_from_db[1];
                //}	
            }
        
        }

        $merge = (!empty($_GET['merge']) && $_GET['merge']) ? 1 : 0;
        $skip_new = (!empty($_GET['skip_new']) && $_GET['skip_new']) ? 1 : 0;

        $attrs = self::get_all_product_attributes();
        $attr_keys = array_values($attrs);

        $attributes = array();
        if (!empty($attr_keys) && !empty($attrs))
            $attributes = array_combine($attr_keys, $attrs);

        $product_ptaxonomies = get_object_taxonomies('product', 'name');
        $product_vtaxonomies = get_object_taxonomies('product_variation', 'name');
        $product_taxonomies = array_merge($product_ptaxonomies, $product_vtaxonomies);
        $taxonomies = array_keys($product_taxonomies);
        $new_keys = array_values($taxonomies);
        $taxonomies = array_combine($new_keys, $taxonomies);
        $wt_has_large_number_of_columns_in_csv = apply_filters('wt_has_large_number_of_columns_in_csv', FALSE); 
        if($wt_has_large_number_of_columns_in_csv){
            set_time_limit(0);            
        }
        include( 'views/html-wf-import-options.php' );
    }

    /**
     * The main controller for the actual import stage.
     */
    public function import() {
        global $woocommerce, $wpdb;
        if (!defined('XA_INVENTORY_STOCK_STATUS')) {
            define('XA_INVENTORY_STOCK_STATUS', get_option('woocommerce_manage_stock'));
        }
        if (!defined('XA_INVENTORY_STOCK_THRESHOLD')) {
            define('XA_INVENTORY_STOCK_THRESHOLD', get_option('woocommerce_notify_no_stock_amount'));
        }
        wp_suspend_cache_invalidation(true);

        $this->hf_log_data_change('csv-import', __('Processing products.', 'wf_csv_import_export'));
        foreach ($this->parsed_data as $key => &$item) {

            $product = $this->parser->parse_product($item, $this->merge_empty_cells, $this->use_sku_upsell_crosssell);  
            if (!is_wp_error($product)){
                try{
                    $this->process_product($product, $this->new_prod_status);
                } catch (Exception $ex) {
                    $this->add_import_result('failed', $ex->getMessage(), 'Exception Error', json_encode($item), '-');
                }
                
            }else{
                $this->add_import_result('failed', $product->get_error_message(), 'Not parsed', json_encode($item), '-');
            }    
            unset($item, $product);
        }


        if ($this->delete_products == 1) {
            $this->save_product_id_from_csv();
        }
        $this->hf_log_data_change('csv-import', __('Finished processing products.', 'wf_csv_import_export'));
        wp_suspend_cache_invalidation(false);
    }

    /**
     * Function to save the products ids which are available in CSV
     */
    public function save_product_id_from_csv() {
        global $product_to_be_deleted;        

            $product_to_be_deleted = array_unique($product_to_be_deleted);
            $this->product_to_be_deleted_array = get_option('wf_prod_csv_imp_exp_product_to_be_deleted'); // get saved product ids 
            if (!is_array($this->product_to_be_deleted_array) && empty($this->product_to_be_deleted_array)) {
                $this->product_to_be_deleted_array = array();
            }
            $product_to_be_deleted_new_array = array_merge($this->product_to_be_deleted_array, $product_to_be_deleted);
            unset($this->product_to_be_deleted_array);            
            update_option('wf_prod_csv_imp_exp_product_to_be_deleted', $product_to_be_deleted_new_array); // append product ids to existign or new delete que           
            unset($product_to_be_deleted_new_array);
    }

    /**
     * Function to delete the products (Move to trash) which are not available in CSV
     */
    public function delete_products_not_in_csv() {
        global $wpdb;


        if (!defined('EMPTY_TRASH_DAYS') || ( EMPTY_TRASH_DAYS == 0 || EMPTY_TRASH_DAYS == '' || EMPTY_TRASH_DAYS == null )) {
            $this->hf_log_data_change('csv-import', __('> --------No product Deleted since trash is not active or EMPTY_TRASH_DAYS is set to 0.----------------', 'wf_csv_import_export'));
        } else {
            ini_set('max_input_vars','2000' );
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 0);
            $product_to_be_deleted_new_array = get_option('wf_prod_csv_imp_exp_product_to_be_deleted'); // get saved product ids 
            $product_to_be_deleted_new_array = array_unique($product_to_be_deleted_new_array);
            if(!empty($product_to_be_deleted_new_array)){
                $query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND post_status != 'trash'";
                $all_product_id = $wpdb->get_col($query);
                $product_to_be_deleted_final = array_diff($all_product_id, $product_to_be_deleted_new_array); //Product to be deleted 
                unset($all_product_id);
                if (!empty($product_to_be_deleted_final)) {
                    $this->hf_log_data_change('csv-import', __('> ==================================================================', 'wf_csv_import_export'));
                    foreach ($product_to_be_deleted_final as $temp_product_key => $temp_product_id) {
                        $status = wp_trash_post($temp_product_id);
                        if ($status)
                            $this->hf_log_data_change('csv-import', __('> Deleted Product ID: ', 'wf_csv_import_export') . print_r($temp_product_id, true));
                        else
                            $this->hf_log_data_change('csv-import', __('> Product could not be deleted: ', 'wf_csv_import_export') . print_r($temp_product_id, true));                                              
                    }
                    $this->hf_log_data_change('csv-import', __('> ==================================================================', 'wf_csv_import_export'));
                    unset($product_to_be_deleted_final);
                }
                else {
                    $this->hf_log_data_change('csv-import', __('> -----------No product got deleted.-----------------', 'wf_csv_import_export'));
                }
                update_option('wf_prod_csv_imp_exp_product_to_be_deleted', array());
            }
        }
        
    }

    /**
     * Parses the CSV file and prepares us for the task of processing parsed data
     *
     * @param string $file Path to the CSV file for importing
     */
    public function import_start($file, $mapping, $start_pos, $end_pos, $eval_field) {
//        if (WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
//            $memory = size_format(woocommerce_let_to_num(ini_get('memory_limit')));
//            $wp_memory = size_format(woocommerce_let_to_num(WP_MEMORY_LIMIT));
//        } else {
//            $memory = size_format(wc_let_to_num(ini_get('memory_limit')));
//            $wp_memory = size_format(wc_let_to_num(WP_MEMORY_LIMIT));
//        }  
                    
        $this->hf_log_data_change('csv-import', 'Import Start Pos: ' . $start_pos . ', Import End Pos: ' . $end_pos);
                
        $this->hf_log_data_change('csv-import', __('Parsing products CSV.', 'wf_csv_import_export'));

        $this->parser = new WF_CSV_Parser('product');

        list( $this->parsed_data, $this->raw_headers, $position ) = $this->parser->parse_data($file, $this->delimiter, $mapping, $start_pos, $end_pos, $eval_field);
        $this->hf_log_data_change('csv-import', __('Finished parsing products CSV.', 'wf_csv_import_export'));

//        unset($import_data);

        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);

        return $position;
    }

    /**
     * Performs post-import cleanup of files and the cache
     */
    public function import_end() {

        //wp_cache_flush(); Stops output in some hosting environments
        foreach (get_taxonomies() as $tax) {
            delete_option("{$tax}_children");
            _get_term_hierarchy($tax);
        }

        wp_defer_term_counting(false);
        wp_defer_comment_counting(false);

        do_action('import_end');
    }

    /**
     * Handles the CSV upload and initial parsing of the file to prepare for
     * displaying author import options
     *
     * @return bool False if error uploading or invalid file, true otherwise
     */
    public function handle_upload() {
        if ($this->handle_ftp()) {
            return true;
        }
        if (empty($_POST['file_url']) && (!empty($_FILES['import']['name']) )) {
            $file = wp_import_handle_upload();

            if (isset($file['error'])) {
                echo '<p><strong>' . __('Sorry, there has been an error.', 'wf_csv_import_export') . '</strong><br />';
                echo esc_html($file['error']) . '</p>';
                return false;
            }

            $this->id = (int) $file['id'];
            return true;
        } else if (isset($_POST['file_url'])) {
            $this->file_url = esc_attr($_POST['file_url']);
            return true;
        } elseif (!empty($_POST['import_from_url'])) {
            

            if (filter_var($_POST['import_from_url'], FILTER_VALIDATE_URL)) {
                $this->file_url = $this->get_data_from_url($_POST['import_from_url']);
                if(!$this->file_url){                    
                    return false;
                }
                return true;
            } else {
                echo '<p><strong>' . __('Sorry, The entered URL is not valid.', 'wf_csv_import_export') . '</strong></p>';
                return false;
            }
        } else {
            echo '<p><strong>' . __('Sorry, there has been an error.', 'wf_csv_import_export') . '</strong></p>';
            return false;
        }


        return false;
    }
    
    public function get_data_from_url($url) {
        
        $wp_upload_dir = wp_upload_dir();
        $wp_upload_path = $wp_upload_dir['path'];
        
        $local_file = $wp_upload_path . '/woocommerce-product-import-from-url.csv.txt';
                
        if (strpos(substr($url, 0, 7), 'ftp://') !== false) { // the given url is an ftp url
            
            return $this->get_data_from_ftp_url($url);

        }        
        
        if($file_path = $this->get_data_from_url_method_1($url,$local_file)){
            //return $file_path;
        }elseif($file_path = $this->get_data_from_url_method_2($url,$local_file)){
            //return $file_path;
        }elseif($file_path = $this->get_data_from_url_method_3($url,$local_file)){
            //return $file_path;
        }else{
            return FALSE;
        }
        
        if($file_path){            
/*            $url_args = explode("/",$url);
            if (strpos($url_args[count($url_args) - 1], '.xml') !== false) {
                $file_contents='<?xml version="1.0" encoding="UTF-8"?>';
                $file_contents .= file_get_contents($local_file);
                file_put_contents($local_file, $file_contents); 
            }    */         
            return esc_attr(str_replace(ABSPATH, "", $local_file));
        }
        
    }
    
    public function get_data_from_ftp_url($url) {

        function get_password_and_host_from_url($url) {
            $vsar = explode('@', $url);

            list($host) = explode('/', end($vsar));  // get host name, here list holds the first element of an array 

            $path = substr(end($vsar), strlen($host));

            $port = (substr($url, 0, 4) == 'sftp' ? 22 : 21);

            array_pop($vsar); // removes last element of array

            $v2 = implode('@', $vsar);

            $v3 = explode(':', $v2);

            array_shift($v3);

            array_shift($v3);

            $password = $v3[0];

            return array($password, $host, $path, $port);
        }

        function get_string_between($string, $start, $end) {
            $string = ' ' . $string;
            $ini = strpos($string, $start);
            if ($ini == 0)
                return '';
            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;
            return substr($string, $ini, $len);
        }

        $username = get_string_between($url, '://', ':');

        list($passsword, $host, $path, $port) = get_password_and_host_from_url($url);

        return $this->handle_ftp_for_url($username, $passsword, $host, $path, $port);
    }

    public function get_data_from_url_method_1($url,$local_file){
        set_time_limit(0); // avoiding time out issue.
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
                
        if (ini_get('allow_url_fopen')) {

            $file_contents = @file_get_contents($url, false, stream_context_create($arrContextOptions));
        } else {
            echo '<p><strong>' . __('Sorry, allow_url_fopen not activated. Please setup in php.ini', 'wf_csv_import_export') . '</strong></p>';
            return false;
        }
        
        if (empty($file_contents)) {
            echo '<p><strong>' . __('Sorry, there has been an error.', 'wf_csv_import_export') . '</strong></p>';
            return false;
        }
        
        file_put_contents($local_file, $file_contents);
//        return esc_attr(str_replace(ABSPATH, "", $local_file));
        return $local_file;
    }
    
    public function get_data_from_url_method_2($filePath , $local_file) {

        $file = @fopen($filePath, "rb");

        if (is_resource($file)) {
            $fp = @fopen($local_file, 'w');
            while (!@feof($file)) {
                $chunk = @fread($file, 1024);
                @fwrite($fp, $chunk);
            }
            @fclose($file);
            @fclose($fp);
        }
        if(file_exists($local_file)){
//            return esc_attr(str_replace(ABSPATH, "", $local_file));
            return $local_file;
        }
        return FALSE;
        
    }
        
    public function get_data_from_url_method_3( $url, $local_file, $cookiesIn = '' ){
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => true,     //return headers in addition to content
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_SSL_VERIFYPEER => true,     // Validate SSL Cert
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_COOKIE         => $cookiesIn
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $rough_content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header_content = substr($rough_content, 0, $header['header_size']);
        $body_content = trim(str_replace($header_content, '', $rough_content));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
        preg_match_all($pattern, $header_content, $matches); 
        $cookiesOut = implode("; ", $matches['cookie']);

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['headers']  = $header_content;
        $header['content'] = $body_content;
        $header['cookies'] = $cookiesOut;

        $fp = @fopen($local_file, 'w');
        fwrite($fp, print_r($header['content'], TRUE));
        fclose($fp);
        if(file_exists($local_file)){
//            return esc_attr(str_replace(ABSPATH, "", $local_file));
            return $local_file;
        }
        return FALSE;
        
    }
    
    private function handle_ftp_for_url($username_via_url='',$passsword_via_url='',$host_via_url='',$path_via_url='',$port_via_url=21) {

    if(empty($username_via_url) && empty($passsword_via_url) && empty($host_via_url) && empty($path_via_url)){
        return false;
    }

    $ftp_server = !empty($host_via_url) ? $host_via_url : '';
    $ftp_server_path = !empty($path_via_url) ? $path_via_url : '';
    $ftp_user = !empty($username_via_url) ? $username_via_url : '';
    $ftp_password = !empty($passsword_via_url) ? $passsword_via_url : '';
    $ftp_port =  $port_via_url;
    $use_ftps = TRUE;

    $local_file = 'wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import.csv';
    $server_file = $ftp_server_path;

    $error_message = "";
    $success = false;

    // if have SFTP Add-on for Import Export for WooCommerce 
    if (class_exists('class_wf_sftp_import_export')) {
        $sftp_import = new class_wf_sftp_import_export();
        if (!$sftp_import->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
            $error_message = "Not able to connect to the server please check <b>FTP Server Host / IP</b> and <b>Port number</b>. \n";
        }

        if (empty($server_file)) {
            $error_message = "Please Complete fill the FTP Details. \n";
        } else {
            $file_contents = $sftp_import->get_contents($server_file);
            if (!empty($file_contents)) {
                file_put_contents(ABSPATH . $local_file, $file_contents);
                $error_message = "";
                $success = true;
            } else {
                $error_message = "Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b> .\n";
            }
        }
    } else {

        $ftp_conn = $use_ftps ? @ftp_ssl_connect($ftp_server, $ftp_port) : @ftp_connect($ftp_server, $ftp_port);

        if ($ftp_conn == false) {
            $error_message = "Not able to connect to the server please check <b>FTP Server Host / IP</b> and <b>Port number</b>. \n";
        } else {
            if (!@ftp_login($ftp_conn, $ftp_user, $ftp_password)) {
                $error_message = "Connected to FTP Server.<br/>But, not able to login please check <b>FTP User Name</b> and <b>Password.</b>\n";
            }
        }

        if (empty($error_message)) {
                ftp_pasv($ftp_conn, TRUE);
            if (@ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) {
                $error_message = "";
                $success = true;
            } else {
                $error_message = "Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b> .\n";
            }
        }

        if ($ftp_conn != false) {
            ftp_close($ftp_conn);
        }
    }


    if ($success) {
        return $local_file;
    } else {
        die($error_message);
    }
    return true;
}

    public function product_exists($title, $sku = '', $post_name = '') {
        global $wpdb;

        // Post Title Check
        $post_title = stripslashes(sanitize_post_field('post_title', $title, 0, 'db'));

        $query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND post_status IN ( 'publish', 'private', 'draft', 'pending', 'future' )";
        $args = array();

        /*
          if ( ! empty ( $title ) ) {
          $query .= ' AND post_title = %s';
          $args[] = $post_title;
          }


          if ( ! empty ( $post_name ) ) {
          $query .= ' AND post_name = %s';
          $args[] = $post_name;
          }
         */
        if (!empty($args)) {
            $posts_that_exist = $wpdb->get_col($wpdb->prepare($query, $args));           
            if ($posts_that_exist) {

                foreach ($posts_that_exist as $post_exists) {

                    // Check unique SKU
                    $post_exists_sku = get_post_meta($post_exists, '_sku', true);

                    if ($sku == $post_exists_sku) {
                        return true;
                    }
                }
            }
        }

        // Sku Check
        if ($sku) {

            $post_exists_sku = $wpdb->get_var($wpdb->prepare("
	    		SELECT $wpdb->posts.ID
	    		FROM $wpdb->posts
	    		LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
	    		WHERE $wpdb->posts.post_status IN ( 'publish', 'private', 'draft', 'pending', 'future' )
	    		AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
	    		", $sku));   
            if ($post_exists_sku) {
                return $post_exists_sku;
            }
        }

        return false;
    }

    /**
     * Create new posts based on import information
     */
    public function process_product($post, $new_product_status = null) {
        global $product_to_be_deleted;
        $post = $this->arrange_product_images($post);
        $post = apply_filters('hf_alter_product_import_data', $post);
        $processing_product_id = absint($post['post_id']);  
        $processing_product = get_post($processing_product_id); 
        $processing_product_title = $processing_product ? $processing_product->post_title : '';
        $processing_product_sku = $processing_product ? $processing_product->_sku : '';
        $merging = !empty($post['merging']);
        $skip_new = !empty($post['skip_new']);  

        if(!empty($post['delete_products'])){
            $this->delete_products = 1;
        }
       
        if ($this->delete_products == 1) {
            $product_to_be_deleted[] =$processing_product_id;
        } 
        if (!empty($post['post_title'])) {
            $processing_product_title = $post['post_title'];
        }
        $post['post_type'] = 'product';

        if (isset($post['parent_sku']) && $post['parent_sku'] !== '' && $post['parent_sku'] !== null) {
            $prod_id = (isset($post['parent_sku'])&& !empty($post['parent_sku']) ? wf_piep_helper::wt_get_product_id_by_sku($post['parent_sku']) : '');
            if ($this->delete_products == 1) {
                $product_to_be_deleted[] =$prod_id;
            }            
            $prod = wc_get_product($prod_id);
            if (WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
                $temp_product_type = ($prod) ? $prod->product_type : '';
            } else {
                $temp_product_type = ($prod) ? $prod->get_type() : '';
            }
            if ( 'grouped' === $temp_product_type) {
                $post['post_type'] = 'product';
            } else {
                $post['post_type'] = 'product_variation';
            }
        }
        if (isset($post['post_parent']) && $post['post_parent'] !== '' && $post['post_parent'] !== null) {
            if ($this->delete_products == 1) {
                $product_to_be_deleted[] = $post['post_parent'];
            }
            $product = wc_get_product($post['post_parent']);
            if (WF_ProdImpExpCsv_Common_Utils::is_woocommerce_prior_to('2.7')) {
                $temp_product_type1 = ($product) ? $product->product_type : '';                
            } else {
                $temp_product_type1 = ($product) ? $product->get_type() : '';               
            }
            
            if ( 'grouped' === $temp_product_type1) {
                $post['post_type'] = 'product';
            } else {
                $post['post_type'] = 'product_variation';
            }
        }
        if (!empty($post['sku'])) {
            $processing_product_sku = $post['sku']; 
        }

        if(apply_filters('hf_import_processed_id_save_flag',false)){           
            $this->processed_posts = $this->already_processed_ids_in_db();   
        }  

        if (!empty($processing_product_id) && isset($this->processed_posts[$processing_product_id])) {                       
            $this->add_import_result('skipped', __('Product already processed', 'wf_csv_import_export'), $processing_product_id, $processing_product_title, $processing_product_sku);
            $this->hf_log_data_change('csv-import', __('> Post ID already processed. Skipping.', 'wf_csv_import_export'), true);
            unset($post);
            return;
        }

        if (!empty($post['post_status']) && 'auto-draft' == trim($post['post_status'])) {
            
            $this->add_import_result('skipped', __('Skipping auto-draft', 'wf_csv_import_export'), $processing_product_id, $processing_product_title, $processing_product_sku);
            $this->hf_log_data_change('csv-import', __('> Skipping auto-draft.', 'wf_csv_import_export'), true);
            unset($post);
            return;
        }
        
        
        /*
         * WPML         
         */
        if (isset($post['wpml']) && !empty($post['wpml'])) {
            foreach ($post['wpml'] as $meta) {
                $key = apply_filters('import_post_meta_key', $meta['key']);
                if ($key == 'original_product_id' || $key == 'original_product_sku' || $key == 'language_code') {
                    $translation_post_details[$key] = $meta['value'];
                }
            }
        }
        // Check if post exists when importing
        if (!$merging) {
            
            
            $is_post_type_product = get_post_type($processing_product_id);
            if (!empty($processing_product_id) && (in_array($is_post_type_product, array('product','product_variation')))) {
                $usr_msg = 'Product with same ID already exists.';
                $this->add_import_result('skipped', __($usr_msg, 'wf_csv_import_export'), $processing_product_id, $processing_product_title, $processing_product_sku);
                $this->hf_log_data_change('csv-import', sprintf(__('> &#8220;%s&#8221;' . $usr_msg, 'wf_csv_import_export'), esc_html($processing_product_title)), true);
                unset($post);
                return;
            }


//            $existing_product = (isset($processing_product_sku) && !empty($processing_product_sku) ? wc_get_product_id_by_sku($processing_product_sku) : ''); //$this->product_exists($processing_product_title, $processing_product_sku, $post['post_name']);
            
            $existing_product = '';
            if (isset($processing_product_sku) && !empty($processing_product_sku)) {
                $existing_product = wf_piep_helper::wt_get_product_id_by_sku($processing_product_sku);  
                /*
                 * WPML
                 * Finding product ID by sku
                 */
                 
                if (apply_filters('wpml_setting', false, 'setup_complete') && ((!empty($translation_post_details['original_product_id']) || !empty($translation_post_details['original_product_sku'])) && !empty($translation_post_details['language_code']))) {
                    global $wpdb;
                    $db_query = $wpdb->prepare("
						SELECT $wpdb->posts.ID
						FROM $wpdb->posts
						LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
						WHERE $wpdb->posts.post_status IN ( 'publish', 'private', 'draft', 'pending', 'future' )
						AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
						", $processing_product_sku);
                    $found_product_ids = $wpdb->get_results($db_query);
                    
                    /*
                    * Finding product ID by sku (each translation may have the same sku if the translation created by duplicating original product)
                    */
                    $found_product_id='';
                    foreach ($found_product_ids as $value) {
                        $original_post_language_info = wf_piep_helper::wt_get_wpml_original_post_language_info($value->ID);
                        if ($original_post_language_info->language_code == $translation_post_details['language_code']) {
                            $found_product_id = $value->ID;
                        }
                    } 
                    $existing_product = ($found_product_id ? $found_product_id : '');
                }
            }
            if ($existing_product) {
                if ($this->delete_products == 1) {
                    $product_to_be_deleted[] =$existing_product;
                }
                if (!$processing_product_id && empty($processing_product_sku)) {
                    // if no sku , no id and no merge and has same title in DB -> just give message
                    $usr_msg = 'Product with same title already exists.';
                } else {
                    $usr_msg = 'Product with same SKU already exists.';
                }
                $this->add_import_result('skipped', __($usr_msg, 'wf_csv_import_export'), $existing_product, $processing_product_title, $processing_product_sku);
                $this->hf_log_data_change('csv-import', sprintf(__('> &#8220;%s&#8221;' . $usr_msg, 'wf_csv_import_export'), esc_html($processing_product_title)).' with post ID:'.$existing_product, true);
                unset($post);
                return;
            }

            if ($processing_product_id && is_string(get_post_status($processing_product_id))) {
                $this->add_import_result('skipped', __('Importing product(ID) conflicts with an existing post.', 'wf_csv_import_export'), $processing_product_id, get_the_title($processing_product_id), '');
                $this->hf_log_data_change('csv-import', sprintf(__('> &#8220;%s&#8221; ID already exists.', 'wf_csv_import_export'), esc_html($processing_product_id)), true);
                unset($post);
                return;
            }
        }
        
        /*
         * To handle ,when want to update/insert product variations without parant product 
         */
        if (!empty($post['tax:product_type']) && $post['tax:product_type'] == 'product_variation') {
            $post['post_type'] = 'product_variation';
        }

        // Check post type to avoid conflicts with IDs
        $is_post_type_product = get_post_type($processing_product_id);
        if ($merging && $processing_product_id && !empty($is_post_type_product) && ($is_post_type_product !== $post['post_type'] )) {
            $this->add_import_result('skipped', __('Importing product(ID) conflicts with an existing post which is not a product.', 'wf_csv_import_export'), $processing_product_id, $processing_product_title, $processing_product_sku);
            $this->hf_log_data_change('csv-import', sprintf(__('> &#8220;%s&#8221; is not a product.', 'wf_csv_import_export'), esc_html($processing_product_id)), true);
            unset($post);
            return;
        }

        if ($merging && !empty($is_post_type_product)) {

            // Only merge fields which are set
            $post_id = $processing_product_id;
            $this->hf_log_data_change('csv-import', sprintf(__('> Merging post ID %s.', 'wf_csv_import_export'), $post_id), true);

            $postdata = array(
                    'ID' => $post_id
            );

            if ($this->merge_empty_cells) {
                if (isset($post['post_content'])) {
                    $postdata['post_content'] = $post['post_content'];
                }
                if (isset($post['post_excerpt'])) {
                    $postdata['post_excerpt'] = $post['post_excerpt'];
                }
                if (isset($post['post_password'])) {
                    $postdata['post_password'] = $post['post_password'];
                }
                if (isset($post['post_parent'])) {
                    $postdata['post_parent'] = $post['post_parent'];
                }
            } else {
                if (!empty($post['post_content'])) {
                    $postdata['post_content'] = $post['post_content'];
                }
                if (!empty($post['post_excerpt'])) {
                    $postdata['post_excerpt'] = $post['post_excerpt'];
                }
                if (!empty($post['post_password'])) {
                    $postdata['post_password'] = $post['post_password'];
                }
                if (isset($post['post_parent']) && $post['post_parent'] !== '') {
                    $postdata['post_parent'] = $post['post_parent'];
                }
            }

            if (!empty($post['post_title'])) {
                $postdata['post_title'] = $post['post_title'];
            }

            if (!empty($post['post_author'])) {
                $postdata['post_author'] = absint($post['post_author']);
            }
            if (!empty($post['post_date'])) {
                $postdata['post_date'] = date("Y-m-d H:i:s", strtotime($post['post_date']));
            }
            if (!empty($post['post_date_gmt'])) {
                $postdata['post_date_gmt'] = date("Y-m-d H:i:s", strtotime($post['post_date_gmt']));
            }
            if (!empty($post['post_name'])) {
                $postdata['post_name'] = $post['post_name'];
            }
            if (!empty($post['post_status'])) {
                $postdata['post_status'] = trim($post['post_status']);
            }
            if (!empty($post['menu_order'])) {
                $postdata['menu_order'] = $post['menu_order'];
            }
            if (!empty($post['comment_status'])) {
                $postdata['comment_status'] = $post['comment_status'];
            }

            //if(!empty($postdata['post_content']))  $postdata['post_content'] = utf8_encode ($postdata['post_content']);

            if (sizeof($postdata) > 1) {
                $result = wp_update_post($postdata);

                if (!$result) {
                    $this->add_import_result('failed', __('Failed to update product', 'wf_csv_import_export'), $post_id, $processing_product_title, $processing_product_sku);
                    $this->hf_log_data_change('csv-import', sprintf(__('> Failed to update product %s', 'wf_csv_import_export'), $post_id), true);
                    unset($post);
                    return;
                } else {
                    $this->hf_log_data_change('csv-import', __('> Merged post data: ', 'wf_csv_import_export') . print_r($postdata, true));
                    $translation_post_details['current_post_id'] = $post_id;
                }
            }
        } else {
            $merging = FALSE;
            // Get parent
            $post_parent = (!empty($post['post_parent']) ? $post['post_parent'] : 0);
            
            if ($this->delete_products == 1) {
                $product_to_be_deleted[] =$post['post_parent'];
            }
            
            if ($post_parent !== "") {
                $post_parent = absint($post_parent);

                if ($post_parent > 0) {
                    // if we already know the parent, map it to the new local ID
                    if (isset($this->processed_posts[$post_parent])) {
                        $post_parent = $this->processed_posts[$post_parent];

                        // otherwise record the parent for later
                    } else {

                        $this->post_orphans[intval($processing_product_id)] = $post_parent;
                        //$post_parent = 0;
                    }
                }
            }

            //set product status 
            if ($post['post_type'] == 'product_variation') { // setting product status for variatoin product when new_product_status option enabled 
                $product_status = 'publish';
            } elseif (!empty($new_product_status) && in_array(strtolower(trim($new_product_status)), array('draft', 'publish', 'pending'))) {
                $product_status = strtolower(trim($new_product_status));
            } elseif ($post['post_status']) {
                $product_status = trim($post['post_status']);
            } else {
                $product_status = 'publish';
            }

            // Insert product
            $this->hf_log_data_change('csv-import', sprintf(__('> Inserting %s', 'wf_csv_import_export'), esc_html($processing_product_title)), true);
            $postdata = apply_filters('xa_piep_alter_new_products_data', array(
                    'import_id' => $processing_product_id,
                    'post_author' => !empty($post['post_author']) ? absint($post['post_author']) : get_current_user_id(),
                    'post_date' => !empty( $post['post_date'] ) ? date("Y-m-d H:i:s", strtotime($post['post_date'])) : '',
                    'post_date_gmt' => ( !empty($post['post_date_gmt']) && $post['post_date_gmt'] ) ? date('Y-m-d H:i:s', strtotime($post['post_date_gmt'])) : '',
                    'post_content' => !empty($post['post_content'])?$post['post_content']:'',
                    'post_excerpt' => !empty($post['post_excerpt'])?$post['post_excerpt']:'',
                    'post_title' => $processing_product_title,
                    'post_name' => !empty( $post['post_name'] ) ? $post['post_name'] : sanitize_title($processing_product_title),
                    'post_status' => $product_status,//(!empty($new_product_status) && in_array(strtolower(trim($new_product_status)), array('draft', 'publish', 'pending')) ) ? strtolower(trim($new_product_status)) : ( ( $post['post_status'] ) ? trim($post['post_status']) : 'publish'),
                    'post_parent' => $post_parent,
                    'menu_order' => !empty($post['menu_order'])?$post['menu_order']:0,
                    'post_type' => !empty($post['post_type'])?$post['post_type']:'',
                    'post_password' => !empty($post['post_password'])?$post['post_password']:'',
                    'comment_status' => !empty($post['comment_status'])?$post['comment_status']:'',
            ));
            if ($skip_new) {
                $this->add_import_result('skipped', __('Skipped New Product', 'wf_csv_import_export'), $processing_product_id, $processing_product_title, $processing_product_sku);
                unset($post);
                return;
            }
            $post_id = wp_insert_post($postdata, true);   
            if (is_wp_error($post_id)) {
                $this->add_import_result('failed', __('Failed to import product', 'wf_csv_import_export'), $processing_product_id, $processing_product_title, $processing_product_sku);
                $this->hf_log_data_change('csv-import', sprintf(__($post_id->get_error_message().'&#8220;%s&#8221;', 'wf_csv_import_export'), esc_html($processing_product_title)));
                unset($post);
                return;
            } else {

                $this->hf_log_data_change('csv-import', sprintf(__('> Inserted - post ID is %s.', 'wf_csv_import_export'), $post_id));
                $translation_post_details['current_post_id'] = $post_id;
            }
            if ($this->delete_products == 1) {
                $product_to_be_deleted[] =$post_id;
            }
        }

        unset($postdata); 
         
        // map pre-import ID to local ID
        if (empty($processing_product_id)) {
            $processing_product_id = (int) $post_id;
        }

        $this->processed_posts[intval($processing_product_id)] = (int) $post_id;
        if(apply_filters('hf_import_processed_id_save_flag',false)){           
            $this->save_processed_product_id_in_db(intval($processing_product_id),(int)$post_id);            
        }

        // add categories, tags and other terms
        if (!empty($post['terms']) && is_array($post['terms'])) {
            
            $terms_to_set = array();

            foreach ($post['terms'] as $term_group) {

                $taxonomy = $term_group['taxonomy'];
                $terms = $term_group['terms'];

                if (!$taxonomy || !taxonomy_exists($taxonomy)) {
                    continue;
                }

                if (!is_array($terms)) {
                    $terms = array($terms);
                }

                $terms_to_set[$taxonomy] = array();

                foreach ($terms as $term_id) {

                    if (!$term_id && !$this->merge_empty_cells)
                        continue;

                    $terms_to_set[$taxonomy][] = intval($term_id);
                }
            }
            
            foreach ($terms_to_set as $tax => $ids) {
                $tt_ids = wp_set_post_terms($post_id, $ids, $tax, false);
            }

            unset($post['terms'], $terms_to_set);
        }


        $post['postmeta'] = apply_filters('hf_insert_post_extra_data', $post['postmeta'], $post, $post_id); //process extra table datas from outside that contains in CSV. especially for third party plugins.
        // add/update post meta
        $processing_product_object = wc_get_product($post_id);
        if (!empty($post['postmeta']) && is_array($post['postmeta'])) {

            $product_type = ( WC()->version < '3.0' ) ? $processing_product_object->type : $processing_product_object->get_type();
            foreach ($post['postmeta'] as $meta) {
                $key = apply_filters('import_post_meta_key', $meta['key']);                

                //Only grouped product used to have _children metadata
                if ('grouped' != $product_type && '_children' == $key) {
                    continue;
                }

                if ('_visibility' == $key && WC()->version > '2.7') {
                    if ($processing_product_object) {
                        if (!empty($meta['value'])) {
                            $available_visiblity_options = explode('|', $meta['value']);
                            foreach ($available_visiblity_options as $opt_value) {
                                if ($opt_value) {
                                    wp_set_object_terms($post_id, $opt_value, 'product_visibility', TRUE);
                                }
                            }
                        }
                        continue;
                    }
                }

                // Get product id from product sku of children products of grouped products to link
                if ($this->prod_use_chidren_sku && '_children' == $key && 'grouped' == $product_type && !empty($meta['value'][0])) {
                    $children_id = array();
                    foreach ($meta['value'] as $children_sku_key => $children_sku) {
                        if (isset($children_sku) && !empty($children_sku)) 
                            $children_id[] = wf_piep_helper::wt_get_product_id_by_sku($children_sku);                        
                    }
                    $meta['value'] = $children_id;
                }
                if ($key) {
                    $escape_serialize_keys = apply_filters('wt_escape_serialize_keys', array(
                        '_regular_currency_prices', // For aelia multicurrency plugin
                        '_sale_currency_prices', // For aelia multicurrency plugin
                        'variable_regular_currency_prices', // For aelia multicurrency plugin
                        'variable_sale_currency_prices', // For aelia multicurrency plugin
                            )
                    );
                    update_post_meta($post_id, ( ( stristr($key, 'attribute') != false ) ? strtolower($key) : $key), (in_array($key, $escape_serialize_keys) ? json_encode($meta['value']) : maybe_unserialize($meta['value'])));
                }

                if ('_file_paths' == $key) {
                    do_action('woocommerce_process_product_file_download_paths', $post_id, 0, maybe_unserialize($meta['value']));
                }
            }

            unset($post['postmeta']);
        }

        if((!empty($translation_post_details['original_product_id']) || !empty($translation_post_details['original_product_sku'])) && !empty($translation_post_details['language_code'])) {
            $this->element_connect_on_insert($translation_post_details);
        }
        
        // Import images and add to post
        if (!empty($post['images']) && is_array($post['images'])) {
            $featured = true;
            $gallery_ids = array();

            if ($merging) {
                // Get basenames
                $image_basenames = array();
                
                foreach ($post['images'] as $image) {
                    foreach ($image as $imagekey => $imagevalue) {
                        if ($imagekey == 'url')
                            $image_basenames[] = basename($imagevalue);
                    }
                }
                
                // Loop attachments already attached to the product
                //$attachments = get_posts('post_parent=' . $post_id . '&post_type=attachment&fields=ids&post_mime_type=image&numberposts=-1');
                                                
                $attachments = $processing_product_object->get_gallery_image_ids();
                $post_thumbnail_id = get_post_thumbnail_id($post_id);
                if(isset($post_thumbnail_id)&& !empty($post_thumbnail_id)){
                    $attachments[]=$post_thumbnail_id;
                }
                
                foreach ($attachments as $attachment_key => $attachment) {

                    $attachment_url = wp_get_attachment_url($attachment);
                    $attachment_basename = basename($attachment_url);
                    // Don't import existing images
                    if (in_array($attachment_url, $post['images']) || in_array($attachment_basename, $image_basenames)) {
                        foreach ($post['images'] as $key => $image) {

                            if ($image['url'] == $attachment_url || basename($image['url']) == $attachment_basename) {                               
                                
                                $attachment_object = get_post($attachment);
                                $temp_image_alt_update = isset($image['alt']) ? update_post_meta($attachment, '_wp_attachment_image_alt', $image['alt']) : '';
                                if ($temp_image_alt_update)
                                    $this->hf_log_data_change('csv-import', sprintf(__('> > Image %d alt updated to %s', 'wf_csv_import_export'), $attachment, $image['alt']));
                                if (isset($image['title']) || isset($image['caption']) || isset($image['desc'])) {
                                    if (!empty($attachment_object)) {
                                        $temp_image_metadata_update = wp_update_post(array(
                                                'ID' => $attachment,
                                                'post_title' => isset($image['title']) ? $image['title'] : $attachment_object->post_title,
                                                'post_excerpt' => isset($image['caption']) ? $image['caption'] : $attachment_object->post_excerpt,
                                                'post_content' => isset($image['desc']) ? $image['desc'] : $attachment_object->post_content,
                                        ));
                                        if ($temp_image_metadata_update) {
                                            $this->hf_log_data_change('csv-import', sprintf(__('> > Image %d metadata updated successfully.', 'wf_csv_import_export'), $attachment));
                                        } else {
                                            $this->hf_log_data_change('csv-import', sprintf(__('> > Image %d metadata could not be updated.', 'wf_csv_import_export'), $attachment));
                                        }
                                    } else {
                                        $this->hf_log_data_change('csv-import', sprintf(__('> > Image %d metadata could not be updated, because could not access old metadata.', 'wf_csv_import_export'), $attachment));
                                    }
                                }
                                $this->hf_log_data_change('csv-import', sprintf(__('> > Image exists - skipping %s', 'wf_csv_import_export'), basename($image['url'])));

                                if ($key == 0) {
                                    update_post_meta($post_id, '_thumbnail_id', $attachment); 
                                    $featured = false;
                                } else {
                                    $gallery_ids[$key] = $attachment;
                                }
                                unset($post['images'][$key]);
                            }
                        }
                    } else {
                        // Detach image which is not being merged
                        $attachment_post = array();
                        $attachment_post['ID'] = $attachment;
                        $attachment_post['post_parent'] = '';
                        wp_update_post($attachment_post);
                        unset($attachment_post);
                    }
                }

                unset($attachments);
            }
            
            if ($post['images'])
                foreach ($post['images'] as $image_key => $image) {

                    $this->hf_log_data_change('csv-import', sprintf(__('> > Importing image "%s"', 'wf_csv_import_export'), $image['url']));

                    $filename = basename($image['url']);

                    $attachment = array(
                            'post_title' => isset($image['title']) ? $image['title'] : preg_replace('/\.[^.]+$/', '', $processing_product_title . ' ' . ( $image_key + 1 )),
                            'post_content' => isset($image['desc']) ? $image['desc'] : '',
                            'post_excerpt' => isset($image['caption']) ? $image['caption'] : '',
                            'post_status' => 'inherit',
                            'post_parent' => $post_id
                    );

                    $attachment_id = $this->process_attachment($attachment, $image['url'], $post_id);

                    if (!is_wp_error($attachment_id) && $attachment_id) {

                        $this->hf_log_data_change('csv-import', sprintf(__('> > Imported image "%s"', 'wf_csv_import_export'), $image['url']));

                        // Set alt
                        update_post_meta($attachment_id, '_wp_attachment_image_alt', ( isset($image['alt']) ? $image['alt'] : $processing_product_title));

                        if ($featured) {
                            update_post_meta($post_id, '_thumbnail_id', $attachment_id);
                        } else {
                            $gallery_ids[$image_key] = $attachment_id;
                        }

                        update_post_meta($attachment_id, '_woocommerce_exclude_image', 0);

                        $featured = false;
                    } else {
                        $this->hf_log_data_change('csv-import', sprintf(__('> > Error importing image "%s"', 'wf_csv_import_export'), $image['url']));
                        $this->hf_log_data_change('csv-import', '> > ' . $attachment_id->get_error_message());
                    }

                    unset($attachment, $attachment_id);
                }

            $this->hf_log_data_change('csv-import', __('> > Images set', 'wf_csv_import_export'));

            ksort($gallery_ids);

            update_post_meta($post_id, '_product_image_gallery', implode(',', $gallery_ids));

            unset($post['images'], $featured, $gallery_ids);
        }

        // Import attributes
        if (!empty($post['attributes']) && is_array($post['attributes'])) {
            if ($merging) {
                $attributes = array_filter((array) maybe_unserialize(get_post_meta($post_id, '_product_attributes', true)));
                $attributes = array_merge($attributes, $post['attributes']);
            } else {
                $attributes = $post['attributes'];
            }

            // Sort attribute positions
            if (!function_exists('attributes_cmp')) {

                function attributes_cmp($a, $b) {
                    if ($a['position'] == $b['position'])
                        return 0;
                    return ( $a['position'] < $b['position'] ) ? -1 : 1;
                }

            }
            uasort($attributes, 'attributes_cmp');

            update_post_meta($post_id, '_product_attributes', $attributes);

            unset($post['attributes'], $attributes);
        }

//        // Import GPF
//        if (!empty($post['gpf_data']) && is_array($post['gpf_data'])) {
//
//            update_post_meta($post_id, '_woocommerce_gpf_data', $post['gpf_data']);
//
//            unset($post['gpf_data']);
//        }

        if (!empty($post['upsell_skus']) && is_array($post['upsell_skus'])) {
            $this->upsell_skus[$post_id] = $post['upsell_skus'];
        }

        if (!empty($post['crosssell_skus']) && is_array($post['crosssell_skus'])) {
            $this->crosssell_skus[$post_id] = $post['crosssell_skus'];
        }

        //add_post_meta( $post_id, 'total_sales', 0 );

        if ($merging) {
            $this->add_import_result('merged', 'Product updated successfully.', $post_id, $processing_product_title, $processing_product_sku);
            $this->hf_log_data_change('csv-import', sprintf(__('> Finished merging post ID %s.', 'wf_csv_import_export'), $post_id));
        } else {
            $this->add_import_result('imported', 'Import successful', $post_id, $processing_product_title, $processing_product_sku);
            $this->hf_log_data_change('csv-import', sprintf(__('> Finished importing post ID %s.', 'wf_csv_import_export'), $post_id));
        }
        wc_delete_product_transients($post_id);
        do_action('wf_refresh_after_product_import',$processing_product_object); // hook for forcefully refresh product
        unset($post);
    }

    /**
     * Arrange product images metadata
     */
    public function arrange_product_images($post) {
        if (!empty($post['images'])) {
            foreach ($post['images'] as $temp_images) {
                $image_details[] = explode('!', $temp_images);
            }

            foreach ($image_details as $image_detail) {
                $i = isset($images) ? count($images) : 0;
                $j = 0;
                foreach ($image_detail as $current_image_detail) {
                    if ($j == 0) {
                        $images[$i]['url'] = trim($current_image_detail);
                        $j++;
                        continue;
                    }
                    @list($image['key'], $image['data']) = explode(':', $current_image_detail);
                    $images[$i][trim(strtolower($image['key']))] = trim($image['data']);
                }
            }
            $post['images'] = $images;
            unset($temp_images, $image_details, $image_detail, $current_image_detail, $image, $images, $i, $j);
        }
        return $post;
    }

    /**
     * Log a row's import status
     */
    protected function add_import_result($status, $reason, $post_id = '', $post_title = '', $sku = '') {
        $this->import_results[] = array(
                'post_title' => $post_title,
                'post_id' => $post_id,
                'sku' => $sku,
                'status' => $status,
                'reason' => $reason
        );
    }

    /**
     * If fetching attachments is enabled then attempt to create a new attachment
     *
     * @param array $post Attachment post details from WXR
     * @param string $url URL to fetch attachment from
     * @return int|WP_Error Post ID on success, WP_Error otherwise
     */
    public function process_attachment($post, $url, $post_id) {
        $attachment_id = '';
        $attachment_url = '';
        $attachment_file = '';
        $upload_dir = wp_upload_dir();

        // If same server, make it a path and move to upload directory
        /* if ( strstr( $url, $upload_dir['baseurl'] ) ) {

          $url = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );

          } else */
        if (strstr($url, site_url())) {
            
            $image_id = $this->wt_get_image_id_by_url($url);
            if($image_id){
                $attachment_id = $image_id;
                
                $this->hf_log_data_change('csv-import', sprintf(__('> > (Image already in the site)Inserted image attachment "%s"', 'wf_csv_import_export'), $url));

                $this->attachments[] = $attachment_id;
                
                return $attachment_id;
            }
            
            $abs_url = str_replace(trailingslashit(site_url()), trailingslashit(ABSPATH), urldecode($url));
            $new_name = wp_unique_filename($upload_dir['path'], basename(urldecode($url)));
            $new_url = trailingslashit($upload_dir['path']) . $new_name;

            if (copy($abs_url, $new_url)) {
                $url = basename($new_url);
            }
        }

        if (!strstr($url, 'http')) { // if not a url 
            // Local file. 
            // We have the path, check it exists, check in /wp-content/uploads/product_images/
            $attachment_file = trailingslashit($upload_dir['basedir']) . 'product_images/' . $url;
            
            // We have the path, check it exists, check in current month dir
            if (!file_exists($attachment_file))
                $attachment_file = trailingslashit($upload_dir['path']) . $url;
            
            // We have the path, check it exists, check in /wp-content/uploads/ and its sub folders(Recursive)
             if (!file_exists($attachment_file)){   
                $attachment_file = $this->recursive_file_search($upload_dir['basedir'],$url); 
             }            

            // We have the path, check it exists
            if (file_exists($attachment_file)) {

                $attachment_url = str_replace(trailingslashit(ABSPATH), trailingslashit(site_url()), $attachment_file);

                if ($info = wp_check_filetype($attachment_file))
                    $post['post_mime_type'] = $info['type'];
                else
                    return new WP_Error('attachment_processing_error', __('Invalid file type', 'wordpress-importer'));
                
                
                $image_id = $this->wt_get_image_id_by_url($attachment_url);
                if($image_id){
                    $attachment_id = $image_id;
                    $this->hf_log_data_change('csv-import', sprintf(__('> > (Image already in the site)Inserted image attachment "%s"', 'wf_csv_import_export'), $url));
                    $this->attachments[] = $attachment_id;
                    return $attachment_id;
                }

                $post['guid'] = $attachment_url;
 
                $attachment_id = wp_insert_attachment($post, $attachment_file, $post_id);
                
            } else  {                                               
                return new WP_Error('attachment_processing_error', __('Local image did not exist!', 'wordpress-importer'));
            }
        } else {

            // if the URL is absolute, but does not contain address, then upload it assuming base_site_url
            if (preg_match('|^/[\w\W]+$|', $url))
                $url = rtrim(site_url(), '/') . $url;

            $upload = $this->fetch_remote_file($url, $post); 
            if (is_wp_error($upload))
                return $upload;

            if ($info = wp_check_filetype($upload['file']))
                $post['post_mime_type'] = $info['type'];
            else
                return new WP_Error('attachment_processing_error', __('Invalid file type', 'wordpress-importer'));

            $post['guid'] = $upload['url'];
            $attachment_file = $upload['file'];
            $attachment_url = $upload['url'];

            // as per wp-admin/includes/upload.php
            $attachment_id = wp_insert_attachment($post, $upload['file'], $post_id);
            
            unset($upload);
        }

        if (!is_wp_error($attachment_id) && $attachment_id > 0) {
            $this->hf_log_data_change('csv-import', sprintf(__('> > Inserted image attachment "%s"', 'wf_csv_import_export'), $url));

            $this->attachments[] = $attachment_id;
        }
        return $attachment_id;
    }
    
    function wt_get_image_id_by_url($image_url) {
        global $wpdb;
        $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url));
        return isset($attachment[0])&& $attachment[0]>0 ? $attachment[0]:'';
    }

    public function recursive_file_search($directory,$file_name){
        $it = new RecursiveDirectoryIterator($directory);
                //$display = Array ( 'jpeg', 'jpg' );
                foreach (new RecursiveIteratorIterator($it) as $file) {
                    //if (in_array(strtolower(array_pop(explode('.', $file))), $display)) {
                        $file = str_replace('\\', '/', $file);
                        if (substr(strrchr($file, '/'), 1) == $file_name) {
                            return $file;
                        }
                    //}
                }
    }

    /**
     * Attempt to download a remote file attachment
     */
    public function fetch_remote_file($url, $post) {

        // extract the file name and extension from the url
        $file_name = basename(current(explode('?', $url)));
        $wp_filetype = wp_check_filetype($file_name, null);
        $parsed_url = @parse_url($url);

        // Check parsed URL
        if (!$parsed_url || !is_array($parsed_url))
            return new WP_Error('import_file_error', 'Invalid URL');

        // Ensure url is valid
        $url = str_replace(" ", '%20', $url);
        // Get the file
        $response = wp_remote_get($url, array(
                'timeout' => 10,
                "user-agent" => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:56.0) Gecko/20100101 Firefox/56.0",
                'sslverify' => FALSE
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200)
            return new WP_Error('import_file_error', 'Error getting remote image');

        // Ensure we have a file name and type
        if (!$wp_filetype['type']) {

            $headers = wp_remote_retrieve_headers($response);

            if (isset($headers['content-disposition']) && strstr($headers['content-disposition'], 'filename=')) {

                $disposition = end(explode('filename=', $headers['content-disposition']));
                $disposition = sanitize_file_name($disposition);
                $file_name = $disposition;
                if (isset($headers['content-type']) && strstr($headers['content-type'], 'image/')) {
                    $supported_image = array(
                        'gif',
                        'jpg',
                        'jpeg',
                        'png'
                    );
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION)); // Using strtolower to overcome case sensitive
                    if (!in_array($ext, $supported_image)) {
                        $file_name = $file_name . '.' . str_replace('image/', '', $headers['content-type']);
                    }
                }
            } elseif (isset($headers['content-type']) && strstr($headers['content-type'], 'image/')) {

                $file_name = 'image.' . str_replace('image/', '', $headers['content-type']);
            }

            unset($headers);
        }

        // Upload the file
        $upload = wp_upload_bits($file_name, '', wp_remote_retrieve_body($response));

        if ($upload['error'])
            return new WP_Error('upload_dir_error', $upload['error']);

        // Get filesize
        $filesize = filesize($upload['file']);

        if (0 == $filesize) {
            @unlink($upload['file']);
            unset($upload);
            return new WP_Error('import_file_error', __('Zero size file downloaded', 'wf_csv_import_export'));
        }

        unset($response);

        return $upload;
    }

    /**
     * Decide what the maximum file size for downloaded attachments is.
     * Default is 0 (unlimited), can be filtered via import_attachment_size_limit
     *
     * @return int Maximum attachment file size to import
     */
    public function max_attachment_size() {
        return apply_filters('import_attachment_size_limit', 0);
    }

    /**
     * Attempt to associate posts and menu items with previously missing parents
     */
    public function backfill_parents() {
        global $wpdb;

        // find parents for post orphans
        if (!empty($this->post_orphans) && is_array($this->post_orphans))
            foreach ($this->post_orphans as $child_id => $parent_id) {
                $local_child_id = $local_parent_id = false;
                if (isset($this->processed_posts[$child_id]))
                    $local_child_id = $this->processed_posts[$child_id];
                if (isset($this->processed_posts[$parent_id]))
                    $local_parent_id = $this->processed_posts[$parent_id];

                if ($local_child_id && $local_parent_id)
                    $wpdb->update($wpdb->posts, array('post_parent' => $local_parent_id), array('ID' => $local_child_id), '%d', '%d');
            }
    }

    /**
     * Attempt to associate posts and menu items with previously missing parents
     */
    public function link_product_skus($type, $product_id, $skus) {
        global $wpdb;

        $ids = array();

        foreach ($skus as $sku) {
            $ids[] = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s;", $sku));
        }

        $ids = array_filter($ids);

        update_post_meta($product_id, "_{$type}_ids", $ids);
    }

    private function handle_ftp() {        
        $enable_ftp_ie = !empty($_POST['pro_enable_ftp_ie']) ? true : false;    
        
        if ($enable_ftp_ie == false) {
           
            $settings_in_db = get_option('wf_product_import_ftp', null);
            $settings_in_db['pro_enable_ftp_ie'] = false;
            update_option('wf_product_import_ftp', $settings_in_db);
            return false;
        }

        $ftp_server = !empty($_POST['pro_ftp_server']) ? $_POST['pro_ftp_server'] : '';
        $ftp_server_path = !empty($_POST['pro_ftp_server_path']) ? $_POST['pro_ftp_server_path'] : '';
        $ftp_user = !empty($_POST['pro_ftp_user']) ? $_POST['pro_ftp_user'] : '';
        $ftp_password = !empty($_POST['pro_ftp_password']) ? $_POST['pro_ftp_password'] : '';
        $ftp_port = !empty($_POST['pro_ftp_port']) ? $_POST['pro_ftp_port'] : 21;
        $use_ftps = !empty($_POST['pro_use_ftps']) ? true : false;
        $use_pasv = !empty($_POST['pro_use_pasv']) ? true : false;

        $settings = array();
        $settings['pro_ftp_server'] = $ftp_server;
        $settings['pro_ftp_user'] = $ftp_user;
        $settings['pro_ftp_password'] = $ftp_password;
        $settings['pro_ftp_port'] = $ftp_port;
        $settings['pro_use_ftps'] = $use_ftps;
        $settings['pro_enable_ftp_ie'] = $enable_ftp_ie;
        $settings['pro_ftp_server_path'] = $ftp_server_path;        
        $settings['pro_use_pasv'] = $use_pasv;

        $local_file = 'wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import.csv';
        $server_file = $ftp_server_path;

        update_option('wf_product_import_ftp', $settings);

        $error_message = "";
        $success = false;

        // if have SFTP Add-on for Import Export for WooCommerce 
        if (class_exists('class_wf_sftp_import_export')) {
            $sftp_import = new class_wf_sftp_import_export();
            if (!$sftp_import->connect($ftp_server, $ftp_user, $ftp_password, $ftp_port)) {
                $error_message = "Not able to connect to the server please check <b>FTP Server Host / IP</b> and <b>Port number</b>. \n";
            }

            if (empty($server_file)) {
                $error_message = "Please Complete fill the FTP Details. \n";
            } else {
                $file_contents = $sftp_import->get_contents($server_file);
                if (!empty($file_contents)) {
                    file_put_contents(ABSPATH . $local_file, $file_contents);
                    $error_message = "";
                    $success = true;
                } else {
                    $getErrors = $sftp_import->getErrors();
                    if(is_array($getErrors)){
                        $error_message .= implode(',', $getErrors);
                    }
                    //$error_message = "Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b> .\n";
                }
            }
        } else {

            $ftp_conn = $use_ftps ? @ftp_ssl_connect($ftp_server, $ftp_port) : @ftp_connect($ftp_server, $ftp_port);

            if ($ftp_conn == false) {
                $error_message = "Not able to connect to the server please check <b>FTP Server Host / IP</b> and <b>Port number</b>. \n";
            } else {
                if (!@ftp_login($ftp_conn, $ftp_user, $ftp_password)) {
                    $error_message = "Connected to FTP Server.<br/>But, not able to login please check <b>FTP User Name</b> and <b>Password.</b>\n";
                }
            }

            if (empty($error_message)) {
                if ($use_pasv) {
                    ftp_pasv($ftp_conn, TRUE);
                }
                if (@ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) {
                    $error_message = "";
                    $success = true;
                } else {
                     ftp_pasv($ftp_conn, TRUE); // Enabling passive mode to retry
                     if (@ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) { //Retrying after enable passive mode 
                        $error_message = "";
                        $success = true;
                     }else{
                        $error_message = "Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import.csv</b> .\n";
                     }
                    
                }
            }

            if ($ftp_conn != false) {
                ftp_close($ftp_conn);
            }
        }


        if ($success) {
            $this->file_url = $local_file;
        } else {
            die($error_message);
        }
        return true;
    }

    // Display import page title
    public function header() {
        echo '<div class="wrap"><div class="icon32" id="icon-woocommerce-importer"><br></div>';
        echo '<h2>' . ( empty($_GET['merge']) ? __('Import', 'wf_csv_import_export') : __('Merge Products', 'wf_csv_import_export') ) . '</h2>';
    }

    // Close div.wrap
    public function footer() {
        echo '</div>';
    }

    /**
     * Display introductory text and file upload form
     */
    public function greet() {
        $action = 'admin.php?import=woocommerce_csv&amp;step=1';
        $bytes = apply_filters('import_upload_size_limit', wp_max_upload_size());
        $size = size_format($bytes);
        $upload_dir = wp_upload_dir();
        $ftp_settings = get_option('wf_product_import_ftp');        
        include( 'views/html-wf-import-greeting.php' );
    }

    /**
     * Added to http_request_timeout filter to force timeout at 60 seconds during import
     * @return int 60
     */
    public function bump_request_timeout($val) {
        return 60;
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
    
    
    /**
     * Function to save the products ids which are already processed.
     */
    public function save_processed_product_id_in_db($processing_product_id=0,$post_id=0) {

            $processed_ids_in_db = get_option('wf_prod_csv_imp_exp_processed_product_ids'); // get saved product ids 
            if (!is_array($processed_ids_in_db) && empty($processed_ids_in_db)) {
                $processed_ids_in_db = array();
            }
            $processed_ids_in_db[$processing_product_id]=$post_id;
            $processed_ids_in_db = array_unique($processed_ids_in_db);
            update_option('wf_prod_csv_imp_exp_processed_product_ids', $processed_ids_in_db); // append product ids to existign or new delete que           
            unset($processed_ids_in_db);
    }
    
    
    /**
     * Function to fetch the product ids which already processed
     */
    public function already_processed_ids_in_db() {

        return get_option('wf_prod_csv_imp_exp_processed_product_ids'); // get saved product ids       
    }

    /**
     * WPML compatibility
     */
    public function element_connect_on_insert($translation_post_details) {
        if ($translation_post_details) {
            // https://wpml.org/wpml-hook/wpml_element_type/
            $wpml_element_type = apply_filters('wpml_element_type', 'post_product');

            // get the language info of the original post
            // https://wpml.org/wpml-hook/wpml_element_language_details/

            if (isset($translation_post_details['original_product_id']) && !empty($translation_post_details['original_product_id']))
                $original_product = wc_get_product($translation_post_details['original_product_id']);
            $original_product_id = '';
            if (!empty($original_product) && is_object($original_product)) {
                $original_product_id = $translation_post_details['original_product_id'];
            }
            if (!$original_product_id && isset($translation_post_details['original_product_sku']) && !empty($translation_post_details['original_product_sku'])) {
                $original_product_id = wf_piep_helper::wt_get_product_id_by_sku($translation_post_details['original_product_sku']);
            }
            if ($original_product_id) {

                $original_post_language_info =wf_piep_helper::wt_get_wpml_original_post_language_info($original_product_id);

                $set_language_args = array(
                    'element_id' => $translation_post_details['current_post_id'],
                    'element_type' => $wpml_element_type,
                    'trid' => $original_post_language_info->trid,
                    'language_code' => $translation_post_details['language_code'],
                    'source_language_code' => $original_post_language_info->language_code
                );

                do_action('wpml_set_element_language_details', $set_language_args);
            }
        }
    }

}
