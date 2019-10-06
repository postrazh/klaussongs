<?php
/**
 * WordPress Importer class for managing the import process of a CSV file
 *
 * @package WordPress
 * @subpackage Importer
 */
if (!class_exists('WP_Importer'))
    return;

class WF_PrRevImpExpCsv_Import extends WP_Importer {

    var $id;
    var $file_url;
    var $delimiter;
    var $use_sku;
    var $profile;
    var $merge;
    // mappings from old information to new
    var $processed_terms = array();
    var $processed_posts = array();
    var $post_orphans = array();
    var $attachments = array();
    var $upsell_skus = array();
    var $crosssell_skus = array();
    var $cmd_type = '';
    var $new_id = array();
    var $parent_data = '';
    var $csv_last_start = '';
    // Results
    var $import_results = array();

    /**
     * Constructor
     */
    public function __construct() {

        if (WC()->version < '2.7.0') {
            $this->log = new WC_Logger();
        } else {
            $this->log = wc_get_logger();
        }
        $this->import_page = 'product_reviews_csv';
        $this->file_url_import_enabled = apply_filters('product_reviews_csv_product_file_url_import_enabled', true);
    }

    public function hf_rev_im_ex_StartSession() {
        if (!session_id()) {
            session_start();
        }
    }

    public function hf_log_data_change($content = 'review-csv-import', $data = '') {
        if (WC()->version < '2.7.0') {
            $this->log->add($content, $data);
        } else {
            $context = array('source' => $content);
            $this->log->log("debug", $data, $context);
        }
    }

    public function hf_rev_im_ex_myEndSession() {
        session_destroy();
    }

    /**
     * Registered callback function for the WordPress Importer
     *
     * Manages the three separate stages of the CSV import process
     */
    public function dispatch() {
        global $woocommerce, $wpdb;

        add_action('init', array($this, 'hf_rev_im_ex_StartSession'), 1);
        
        $this->merge = (( ! empty( $_POST['merge'] ) || ! empty( $_GET['merge'] ) ) ? 1 : 0 );        
        
        if (!empty($_POST['delimiter'])) {
            $this->delimiter = stripslashes(trim($_POST['delimiter']));
        } else if (!empty($_GET['delimiter'])) {
            $this->delimiter = stripslashes(trim($_GET['delimiter']));
        }

        if (!$this->delimiter)
            $this->delimiter = ',';
        if (!empty($_POST['use_sku']))
            $this->use_sku = 1;
        if (!empty($_POST['profile'])) {
            $this->profile = stripslashes(trim($_POST['profile']));
        } else if (!empty($_GET['profile'])) {
            $this->profile = stripslashes(trim($_GET['profile']));
        }
        if (!$this->profile)
            $this->profile = '';

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
                //_e( 'Error with handle_upload!', 'wf_csv_import_export' );
                    wp_redirect(wp_get_referer() . '&wf_product_review_ie_msg=3');
                exit;
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

                $file = str_replace("\\", "/", $file);

                if ($file) {
                    ?>
                    <table id="import-progress" class="widefat_importer widefat">
                        <thead>
                            <tr>
                                <th class="status">&nbsp;</th>
                                <th class="row"><?php _e('Row', 'wf_csv_import_export'); ?></th>
                                <th><?php _e('ID', 'wf_csv_import_export'); ?></th>
                                <th><?php _e('Product Review ID', 'wf_csv_import_export'); ?></th>
                                <th class="reason"><?php _e('Status Msg', 'wf_csv_import_export'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="importer-loading">
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                        <tbody ></tbody>
                    </table>
                    <script type="text/javascript">
                                jQuery(document).ready(function($) {

                                if (! window.console) { window.console = function(){}; }

                                var processed_terms = [];
                                var processed_posts = []; var post_orphans = [];
                                var attachments = [];
                                var upsell_skus = [];
                                var crosssell_skus = [];
                                var i = 1;
                                var done_count = 0;
                                var file_id=0;
                                function import_rows(start_pos, end_pos) {

                        var data = {
                                        action: 	'product_reviews_csv_import_request',
                                        file:       '<?php echo addslashes($file); ?>',
                                        mapping:    '<?php echo json_encode($_POST['map_from']); ?>',
                                        profile:    '<?php echo $this->profile; ?>',
                                        merge:      '<?php echo $this->merge; ?>',
                                        eval_field: '<?php echo stripslashes(json_encode(($_POST['eval_field']), JSON_HEX_APOS)) ?>',
                                        delimiter:  '<?php echo $this->delimiter; ?>',
                                        use_sku:    '<?php echo $this->use_sku; ?>',
                                        start_pos:  start_pos,
                                        end_pos:    end_pos,
                                        file_id:    '<?php echo $this->id; ?>',
                                };
                                data.eval_field = $.parseJSON(data.eval_field);
                        return $.ajax({
                                        url:        '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '3', 'merge' => !empty($_GET['merge']) ? '1' : '0'), admin_url('admin-ajax.php')); ?>',
                                        data:       data,
                                type:       'POST',
                                        success:    function(response) {
                                        if (response) {

                                        try {
                                        // Get the valid JSON only from the returned string
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
                                                            file_id=results.file_id;
                                                            $(results.import_results).each(function(index, row) {

                                                            $('#import-progress tbody').append('<tr id="row-' + i + '" class="' + row['status'] + '"><td><mark class="result" title="' + row['status'] + '">' + row['status'] + '</mark></td><td class="row">' + i + '</td><td>' + row['post_id'] + '</td><td>' + row['post_id'] + '</td><td class="reason">' + row['reason'] + '</td></tr>');
                                                                                i++;
                                                                                });
                                                                                }

                                                                                } catch (err) {}

                                                                                } else {
                                                                                $('#import-progress tbody').append('<tr class="error"><td class="status" colspan="5">' +     '<?php _e('AJAX Error', 'wf_csv_import_export'); ?>' + '</td></tr>');
                                                                                                    }

                                                                                                    var w = $(window);
                                                                                                    var row = $("#row-" + (i - 1));
                                                                                                    if (row.length) {
                                                                                                    w.scrollTop(row.offset().top - (w.height() / 2));
                                                                                                    }

                                                                                                    done_count++;
                                                                                                    $('body').trigger('product_reviews_csv_import_request_complete');
                                                                                                    }
                                                                                            });
                                                                                            }

                                                                                            var rows = [];
                    <?php
                    $limit = apply_filters('product_reviews_csv_import_limit_per_request', 10);
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

                        while (( $postmeta = fgetcsv($handle, 0, $this->delimiter, '"', '"') ) !== FALSE) {
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
                                            import_rows(data[0], data[1]);
                                            $('body').on('product_reviews_csv_import_request_complete', function() {
                                            if (done_count == <?php echo $import_count; ?>) {

                                            if (attachments.length) {

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
                                                    data: { action: "product_reviews_csv_import_regenerate_thumbnail", id: id },
                                                    success: function(response) {
                                                    if (response !== Object(response) || (typeof response.success === "undefined" && typeof response.error === "undefined")) {
                                                            response = new Object;
                                                            response.success = false;
                                                            response.error = "<?php printf(esc_js(__('The resize request was abnormally terminated (ID %s). This is likely due to the image exceeding available memory or some other type of fatal error.', 'wf_csv_import_export')), '" + id + "'); ?>";
                                                            }

                                                            regen_count ++;
                                                            $('#import-progress tbody .regenerating .progress').css('width', ((regen_count / attachments.length) * 100) + '%').html(regen_count + ' / ' + attachments.length + ' <?php echo esc_js(__('thumbnails regenerated', 'wf_csv_import_export')); ?>');
                                        if (! response.success) {
                    $('#import-progress tbody').append( '<tr><td colspan="5">' + response.error + '</td></tr>' );
                                        }
                                },
                                error: function( response ) {
                                        $('#import-progress tbody').append( '<tr><td colspan="5">' + response.error + '</td></tr>' );
                                }
                        });
                    }

                    function import_done() {
                        var data = {
                                action: 'product_reviews_csv_import_request',
                                file: '<?php echo $file; ?>',
                                processed_terms: processed_terms,
                                processed_posts: processed_posts,
                                post_orphans: post_orphans,
                                upsell_skus: upsell_skus,
                                crosssell_skus: crosssell_skus,
                                file_id:file_id,
                        };

                        $.ajax({
                                url: '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '4', 'merge' => !empty($_GET['merge']) ? 1 : 0), admin_url('admin-ajax.php')); ?>',
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

                if ($profile !== '') {
                    $profile_array = get_option('wf_prod_review_csv_imp_exp_mapping');
                    $profile_array[$profile] = array($mapping, $eval_field);
                    update_option('wf_prod_review_csv_imp_exp_mapping', $profile_array);
                }

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
                if(class_exists('WC_Product_Reviews_Pro')){
                    $wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('%_transient_wc_product_reviews_pro_review_count_%')");
                }

                _e('Finalizing...', 'wf_csv_import_export') . ' ';


                // SUCCESS
                _e('Finished. Import complete.', 'wf_csv_import_export');

                if(!empty($this->id) && $this->id >0){ // deleting temparary file from meadia library if have attachment id
                    wp_delete_attachment( $this->id );
                }else{
                    unlink($file); // deleting temparary file from meadia library by path
                }
                
                $this->import_end();
                exit;
                break;
        }

        $this->footer();
    }

    /**
     * format_data_from_csv
     */
    public function format_data_from_csv($data, $enc) {
        return ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);
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

        // Set locale
        $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
        if ($enc)
            setlocale(LC_ALL, 'en_US.' . $enc);
        @ini_set('auto_detect_line_endings', true);

        // Get headers
        if (( $handle = @fopen($file, "r") ) !== FALSE) {

            $row = $raw_headers = array();

            $header = fgetcsv($handle, 0, $this->delimiter, '"', '"');

            while (( $postmeta = fgetcsv($handle, 0, $this->delimiter, '"', '"') ) !== FALSE) {
                foreach ($header as $key => $heading) {
                    if (!$heading)
                        continue;
                    $s_heading = strtolower($heading);
                    $row[$s_heading] = ( isset($postmeta[$key]) ) ? $this->format_data_from_csv($postmeta[$key], $enc) : '';
                    $raw_headers[$s_heading] = $heading;
                }
                break;
            }
            fclose($handle);
        }

        $mapping_from_db = get_option('wf_prod_review_csv_imp_exp_mapping');

        if ($this->profile !== '' && !empty($_GET['clearmapping'])) {
            unset($mapping_from_db[$this->profile]);
            update_option('wf_prod_review_csv_imp_exp_mapping', $mapping_from_db);
            $this->profile = '';
        }
        if ($this->profile !== '')
            $mapping_from_db = $mapping_from_db[$this->profile];

        $saved_mapping = null;
        $saved_evaluation = null;
        if ($mapping_from_db && is_array($mapping_from_db) && count($mapping_from_db) == 2 && empty($_GET['clearmapping'])) {
            //if(count(array_intersect_key ( $mapping_from_db[0] , $row)) ==  count($mapping_from_db[0])){	
            $reset_action = 'admin.php?clearmapping=1&amp;profile=' . $this->profile . '&amp;import=' . $this->import_page . '&amp;step=1&amp;merge=' . $this->merge. '&amp;file_url=' . $this->file_url . '&amp;delimiter=' . $this->delimiter . '&amp;file_id=' . $this->id . '';
            $reset_action = esc_attr(wp_nonce_url($reset_action, 'import-upload'));
            echo '<h3>' . __('Columns are pre-selected using the Mapping file: "<b style="color:gray">' . $this->profile . '</b>".  <a href="' . $reset_action . '"> Delete</a> this mapping file.', 'wf_csv_import_export') . '</h3>';
            $saved_mapping = !empty($mapping_from_db[0]) ? $mapping_from_db[0] : '';
            $saved_evaluation = !empty($mapping_from_db[1]) ? $mapping_from_db[1] : '';
            //}	
        }

        $merge = (!empty($_GET['merge']) && $_GET['merge']) ? 1 : 0;

        include( 'views/html-wf-import-options-review.php' );
    }

    /**
     * The main controller for the actual import stage.
     */
    public function import() {
        global $woocommerce, $wpdb;

        wp_suspend_cache_invalidation(true);
        $this->hf_log_data_change('review-csv-import', '---');
        $this->hf_log_data_change('review-csv-import', __('Processing product reviews.', 'wf_csv_import_export'));
        foreach ($this->parsed_data as $key => &$item) {
            $product_review = $this->parser->parse_product_review($item, $this->use_sku);
            if (!is_wp_error($product_review))
                $this->process_product_reviews($product_review);
            else
                $this->add_import_result('failed', $product_review->get_error_message(), 'Not parsed', json_encode($item), '-');

            unset($item, $product_review);
        }
        $this->hf_log_data_change('review-csv-import', __('Finished processing product reviews.', 'wf_csv_import_export'));
        wp_suspend_cache_invalidation(false);
    }

    /**
     * Parses the CSV file and prepares us for the task of processing parsed data
     *
     * @param string $file Path to the CSV file for importing
     */
    public function import_start($file, $mapping, $start_pos, $end_pos, $eval_field) {
        if (WC()->version < '2.7.0') {
            $memory = size_format(woocommerce_let_to_num(ini_get('memory_limit')));
            $wp_memory = size_format(woocommerce_let_to_num(WP_MEMORY_LIMIT));
        } else {
            $memory = size_format(wc_let_to_num(ini_get('memory_limit')));
            $wp_memory = size_format(wc_let_to_num(WP_MEMORY_LIMIT));
        }

        $this->hf_log_data_change('review-csv-import', '---[ New Import ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory);
        $this->hf_log_data_change('review-csv-import', __('Parsing product reviews CSV.', 'wf_csv_import_export'));

        $this->parser = new WF_CSV_Parser_Review('product');

        list( $this->parsed_data, $this->raw_headers, $position ) = $this->parser->parse_data($file, $this->delimiter, $mapping, $start_pos, $end_pos, $eval_field);
        $this->hf_log_data_change('review-csv-import', __('Finished parsing product reviews CSV.', 'wf_csv_import_export'));

        unset($import_data);

        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);

        return $position;
    }

    /**
     * Performs post-import cleanup of files and the cache
     */
    public function import_end() {

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
        if (empty($_POST['file_url'])) {

            $file = wp_import_handle_upload();

            if (isset($file['error'])) {
                echo '<p><strong>' . __('Sorry, there has been an error.', 'wf_csv_import_export') . '</strong><br />';
                echo esc_html($file['error']) . '</p>';
                return false;
            }

            $this->id = (int) $file['id'];
            return true;
        } else {

            if (file_exists(ABSPATH . $_POST['file_url'])) {

                $this->file_url = esc_attr($_POST['file_url']);
                return true;
            } else {

                echo '<p><strong>' . __('Sorry, there has been an error.', 'wf_csv_import_export') . '</strong></p>';
                return false;
            }
        }

        return false;
    }

    public function product_review_exists($id) {
        global $wpdb;
        $posts_that_exist = $wpdb->get_col($wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_ID = %d AND comment_approved != 'trash'", $id));
        if ($posts_that_exist) {
            foreach ($posts_that_exist as $post_exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create new posts based on import information
     */
    public function process_product_reviews($post) {        
        $processing_product_id = absint($post['comment_ID']);
        $merging = !empty($post['merging']);
        
        $processing_product_title = (!empty($post['post_title']) ? $post['post_title'] : '');

        if (!empty($processing_product_id) && isset($this->processed_posts[$processing_product_id])) {
            $this->add_import_result('skipped', __('Product review already processed', 'wf_csv_import_export'), $processing_product_id);
            $this->hf_log_data_change('review-csv-import', __('> Post ID already processed. Skipping.', 'wf_csv_import_export'), true);
            unset($post);
            return;
        }

        if (!empty($post['post_status']) && $post['post_status'] == 'auto-draft') {
            $this->add_import_result('skipped', __('Skipping auto-draft', 'wf_csv_import_export'), $processing_product_id);
            $this->hf_log_data_change('review-csv-import', __('> Skipping auto-draft.', 'wf_csv_import_export'), true);
            unset($post);
            return;
        }
        // Check if post exists when importing
        $is_post_exist_in_db = $this->product_review_exists($processing_product_id);
        if (!$merging) {
            if ($is_post_exist_in_db) {
                $usr_msg = 'Product review skipped.';
                $this->add_import_result('skipped', __($usr_msg, 'wf_csv_import_export'), $processing_product_id);
                $this->hf_log_data_change('review-csv-import', sprintf(__('> &#8220;%s&#8221;' . $usr_msg, 'wf_csv_import_export'), esc_html($processing_product_title)), true);
                unset($post);
                return;
            }
        }
        
        if ($post['comment_parent'] === '0') {
                    $this->cmd_type = 'review';
                } else {
                    $this->cmd_type = '';
                }

        if ($merging && !empty($is_post_exist_in_db)) {

            // Only merge fields which are set
            $post_id = $processing_product_id;

            $this->hf_log_data_change('review-csv-import', sprintf(__('> Merging post ID %s.', 'wf_csv_import_export'), $post_id), true);

            if (!empty($post['comment_post_ID'])) {
                $postdata['comment_post_ID'] = $post['comment_post_ID'];
            }

            if (!empty($post['comment_author'])) {
                $postdata['comment_author'] = $post['comment_author'];
            }
            if (!empty($post['comment_author_url'])) {
                $postdata['comment_author_url'] = $post['comment_author_url'];
            }
            if (!empty($post['comment_date'])) {
                $postdata['comment_date'] = date("Y-m-d H:i:s", strtotime($post['comment_date']));
            }
            if (!empty($post['comment_date_gmt'])) {
                $postdata['comment_date_gmt'] = date("Y-m-d H:i:s", strtotime($post['comment_date_gmt']));
            }
            if (!empty($post['comment_author_email'])) {
                $postdata['comment_author_email'] = $post['comment_author_email'];
            }
            if (!empty($post['comment_author_IP'])) {
                $postdata['comment_author_IP'] = $post['comment_author_IP'];
            }
            if (!empty($post['comment_content'])) {
                $postdata['comment_content'] = $post['comment_content'];
            }
            if (!empty($post['comment_approved'])) {
                $postdata['comment_approved'] = $post['comment_approved'];
            }
            $postdata['comment_type'] = 'review';

            if (!empty($post['comment_parent'])) {
                $postdata['comment_parent'] = $post['comment_parent'];
            }
            if (!empty($post['user_id'])) {
                $postdata['user_id'] = $post['user_id'];
            }

            // Update product review
            if (sizeof($postdata) > 1) {
                global $wpdb;
                $result = $wpdb->update('wp_comments', $postdata, array('comment_ID' => $post_id));
                if (!empty($post['rating']))
                    update_comment_meta($post_id, 'rating', $post['rating']);
            }
        } else {
            $merging = FALSE;

            if ($this->csv_last_start === '') {
                $last_cmt_id = $this->get_last_comment_id();
                update_option('xa_rev_im_ex_last_import_csv_start_col', $last_cmt_id);
                $this->csv_last_start = $last_cmt_id;
            }

            // Insert product review
            $this->hf_log_data_change('review-csv-import', sprintf(__('> Inserting %s', 'wf_csv_import_export'), esc_html($processing_product_id)), true);

            
            
            if(isset($post['comment_parent']) && $post['comment_parent']!='' ){
                if ($post['comment_parent'] == '0') {
                    $this->parent_data = $post['comment_parent'];
                    $_SESSION['new_id'][$post['comment_alter_id']] = $this->get_last_comment_id();
                } else {
                    if (!empty($_SESSION['new_id'][$post['comment_parent']])) {
                        $this->parent_data = $_SESSION['new_id'][$post['comment_parent']];
                    } else {
                        $this->parent_data = $post['comment_parent'];
                    }                    
                    $_SESSION['new_id'][$post['comment_alter_id']] = $this->get_last_comment_id();

                }

//                if ($post['comment_parent'] === '0') {
//                    $this->cmd_type = 'review';
//                } else {
//                    $this->cmd_type = '';
//                }
            } else {
                $this->parent_data = $post['comment_parent'];
                $this->cmd_type = 'review';
            }
            $postdata = array(
                'comment_ID' => $processing_product_id,
                'comment_post_ID' => $post['comment_post_ID'],
                'comment_date' => ( $post['comment_date'] ) ? date('Y-m-d H:i:s', strtotime($post['comment_date'])) : current_time( 'mysql' ),
                'comment_date_gmt' => ( $post['comment_date_gmt'] ) ? date('Y-m-d H:i:s', strtotime($post['comment_date_gmt'])) : get_gmt_from_date( current_time( 'mysql' ) ),
                'comment_author' => $post['comment_author'],
                'comment_author_url' => $post['comment_author_url'],
                'comment_author_email' => $post['comment_author_email'],
                'comment_author_IP' => $post['comment_author_IP'],
                'comment_content' => ( $post['comment_content'] ) ? $post['comment_content'] : sanitize_title($comment_content),
                'comment_approved' => ( $post['comment_approved'] ) ? $post['comment_approved'] : 0,
                'comment_type' => $this->cmd_type,
                'comment_parent' => $this->parent_data,
                'user_id' => $post['user_id'],
            );            
            $post_id = wp_insert_comment($postdata, true);

            if (is_wp_error($post_id)) {

                $this->add_import_result('failed', __('Failed to import product review', 'wf_csv_import_export'), $processing_product_id);
                $this->hf_log_data_change('review-csv-import', sprintf(__('Failed to import product review &#8220;%s&#8221;', 'wf_csv_import_export'), esc_html($processing_product_title)));
                unset($post);
                return;
            } else {

                $this->hf_log_data_change('review-csv-import', sprintf(__('> Inserted - post ID is %s.', 'wf_csv_import_export'), $post_id));
            }
        }
        unset($postdata);
        // map pre-import ID to local ID
        if (empty($processing_product_id)) {
            $processing_product_id = (int) $post_id;
        }
        $this->processed_posts[intval($processing_product_id)] = (int) $post_id;

        if (!empty($post['postmeta']) && is_array($post['postmeta'])) {
            if ($this->cmd_type === '') {
                update_comment_meta($post_id, 'verified', $post['postmeta'][1]['value']);
            } else {
//                update_comment_meta($post_id, 'verified', $post['postmeta'][1]['value']);
//                update_comment_meta($post_id, 'rating', $post['postmeta'][0]['value']);
//                update_comment_meta($post_id, 'title', $post['postmeta'][2]['value']);
                foreach ($post['postmeta'] as $meta) {
                    update_comment_meta($post_id, $meta['key'], $meta['value']);
                }
            }
        }

        update_option('xa_rev_im_ex_last_import_csv_end_col', $post_id);

        if ($merging) {
            $this->add_import_result('merged', 'Merge successful', $post_id);
            $this->hf_log_data_change('review-csv-import', sprintf(__('> Finished merging post ID %s.', 'wf_csv_import_export'), $post_id));
        } else {
            $this->add_import_result('imported', 'Import successful', $post_id);
            $this->hf_log_data_change('review-csv-import', sprintf(__('> Finished importing post ID %s.', 'wf_csv_import_export'), $post_id));
        }
        unset($post);
    }

    /**
     * Log a row's import status
     */
    protected function add_import_result($status, $reason, $post_id = '') {
        $this->import_results[] = array(
            'post_id' => $post_id,
            'status' => $status,
            'reason' => $reason,
        );
    }

    public function get_last_comment_id() {
        global $wpdb;
//        $query = "SELECT MAX(comment_ID) FROM $wpdb->comments";
//        $results = $wpdb->get_var($query);
//        return $results + 1;
        
        $get_id = $wpdb->get_row("SHOW TABLE STATUS LIKE '".$wpdb->prefix."comments'"); 
        $last_id = $get_id->Auto_increment;
        return $last_id;
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

    private function handle_ftp() {
        $enable_ftp_ie = !empty($_POST['rev_enable_ftp_ie']) ? true : false;
        if ($enable_ftp_ie == false) {
            $settings_in_db = get_option('wf_review_import_ftp', null);
            $settings_in_db['rev_enable_ftp_ie'] = false;
            update_option('wf_review_import_ftp', $settings_in_db);
            return false;
        }

        $ftp_server = !empty($_POST['rev_ftp_server']) ? $_POST['rev_ftp_server'] : '';
        $ftp_server_path = !empty($_POST['rev_ftp_server_path']) ? $_POST['rev_ftp_server_path'] : '';
        $ftp_user = !empty($_POST['rev_ftp_user']) ? $_POST['rev_ftp_user'] : '';
        $ftp_password = !empty($_POST['rev_ftp_password']) ? $_POST['rev_ftp_password'] : '';
        $ftp_port = !empty($_POST['rev_ftp_port']) ? $_POST['rev_ftp_port'] : 21;
        $use_ftps = !empty($_POST['rev_use_ftps']) ? true : false;
        $use_pasv = !empty($_POST['rev_use_pasv']) ? true : false;


        $settings = array();
        $settings['rev_ftp_server'] = $ftp_server;
        $settings['rev_ftp_user'] = $ftp_user;
        $settings['rev_ftp_password'] = $ftp_password;
        $settings['rev_ftp_port'] = $ftp_port;
        $settings['rev_use_ftps'] = $use_ftps;
        $settings['rev_use_pasv'] = $use_pasv;
        $settings['rev_enable_ftp_ie'] = $enable_ftp_ie;
        $settings['rev_ftp_server_path'] = $ftp_server_path;


        $local_file = 'wp-content/plugins/product-csv-import-export-for-woocommerce/temp-import-review.csv';
        $server_file = $ftp_server_path;

        update_option('wf_review_import_ftp', $settings);

        
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
                if ($use_pasv) {
                    ftp_pasv($ftp_conn, TRUE);
                }
                if (@ftp_get($ftp_conn, ABSPATH . $local_file, $server_file, FTP_BINARY)) {
                    $error_message = "";
                    $success = true;
                } else {
                    $error_message = "Failed to Download Specified file in FTP Server File Path.<br/><br/><b>Possible Reasons</b><br/><b>1.</b> File path may be invalid.<br/><b>2.</b> Maybe File / Folder Permission missing for specified file or folder in path.<br/><b>3.</b> Write permission may be missing for file <b>plugins/product-csv-import-export-for-woocommerce/temp-import-review.csv</b>.\n";
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
        echo '<h2>' . ( empty($_GET['merge']) ? __('Import', 'wf_csv_import_export') : __('Merge Product Reviews', 'wf_csv_import_export') ) . '</h2>';
    }

    // Close div.wrap
    public function footer() {
        echo '</div>';
        add_action('wp_logout', array($this, 'hf_rev_im_ex_myEndSession'));
        add_action('wp_login', array($this, 'hf_rev_im_ex_myEndSession'));
    }

    /**
     * Display introductory text and file upload form
     */
    public function greet() {
        $action = 'admin.php?import=product_reviews_csv&amp;step=1';
        $bytes = apply_filters('import_upload_size_limit', wp_max_upload_size());
        $size = size_format($bytes);
        $upload_dir = wp_upload_dir();
        $ftp_settings = get_option('wf_review_import_ftp');
        include( 'views/html-wf-import-greeting-review.php' );
    }

    /**
     * Added to http_request_timeout filter to force timeout at 60 seconds during import
     * @return int 60
     */
    public function bump_request_timeout($val) {
        return 60;
    }

}
