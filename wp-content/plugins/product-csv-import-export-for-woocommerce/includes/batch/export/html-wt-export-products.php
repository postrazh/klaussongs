<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="woocommerce tool-box bg-white p-20p">


    <div class="wt-pipe-batch-exporter-wrapper">
        <form class="wt-batch-exporter" id="wt-batch-exporter">

            <header>
                <span class="spinner"></span> 
                <h3 class="title aw-title"><?php esc_html_e('Export products to a CSV file', 'wf_csv_import_export'); ?></h2>
            </header>
            <p><?php _e('Use batch export option to process the records in batches. Its advisable to use this option to overcome memory limitations or low timeout interval.', 'wf_csv_import_export'); ?></p>


            <section>
                <table class="form-table wt-batch-exporter-options">


                    <tr>
                        <th>
                            <label for="v_columns"><?php _e('Columns', 'wf_csv_import_export'); ?></label>
                        </th>
                        <td>
                            <table id="datagrid">
                                <th style="text-align: left;">
                                    <label for="v_columns"><?php _e('Column', 'wf_csv_import_export'); ?></label>
                                </th>
                                <th style="text-align: left;">
                                    <label for="v_columns_name"><?php _e('Column Name', 'wf_csv_import_export'); ?></label>
                                </th>
                                <!-- select all boxes -->
                                <tr>
                                    <td style="padding: 10px;">
                                        <a href="#" id="pselectall" onclick="return false;" >Select all</a> &nbsp;/&nbsp;
                                        <a href="#" id="punselectall" onclick="return false;">Unselect all</a>
                                    </td>
                                </tr>
                                <?php
//                                    $post_columns['images'] = 'Images (featured and gallery)';
//                                    $post_columns['file_paths'] = 'Downloadable file paths';
//                                    $post_columns['taxonomies'] = 'Taxonomies (cat/tags/shipping-class)';
//                                    $post_columns['attributes'] = 'Attributes';
//                                    $post_columns['meta'] = 'Meta (custom fields)';
//                                    $post_columns['product_page_url'] = 'Product Page URL';
//                                    $post_columns['parent_sku']= 'Parent SKU';
//                                    if (function_exists('woocommerce_gpf_install'))
//                                        $post_columns['gpf'] = 'Google Product Feed fields';
                                ?>
                                <?php
                                foreach ($post_columns as $pkey => $pcolumn) {
                                    $readonly = (in_array($pkey, array('taxonomies', 'meta', 'attributes')) ? 'readonly' : '');
                                    ?>
                                    <tr>
                                        <td>
                                            <input name= "columns[<?php echo $pkey; ?>]" id='columns[<?php echo $pkey; ?>]' type="checkbox" value="<?php echo $pkey; ?>" checked><?php _e('', 'wf_csv_import_export'); ?>
                                            <label for="columns[<?php echo $pkey; ?>]"><?php _e($pcolumn, 'wf_csv_import_export'); ?></label>
                                        </td>                                
                                        <td>
                                            <?php
                                            $tmpkey = $pkey;
                                            if (strpos($pkey, 'yoast') === false) {
                                                $tmpkey = ltrim($pkey, '_');
                                            }
//                                            else {
//                                                $tmpkey = 'meta' . $pkey;
//                                            }
                                            ?>
                                            <input type="text" name="columns_name[<?php echo $pkey; ?>]"  value="<?php echo $tmpkey; ?>" class="input-text" <?php echo $readonly ?>/>
                                        </td>
                                    </tr>
                                <?php } ?>

                            </table>
                        </td>

                    </tr>

                    <tr>
                        <th>
                            <label for="v_include_hidden_meta"><?php _e('Include hidden meta data', 'wf_csv_import_export'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="include_hidden_meta" id="v_include_hidden_meta" class="checkbox" />
                            <p style="font-size: 12px"><?php _e('Check if you also want to include hidden metadata in the exported CSV', 'wf_csv_import_export'); ?></p> 
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="v_batch_count"><?php _e('Batch count', 'wf_csv_import_export'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="batch_count" id="v_batch_count" value="100" class="input-text" />
                            <!--<p style="font-size: 12px"><?php _e('', 'wf_csv_import_export'); ?></p>--> 
                            <p style="font-size: 12px"><?php _e('The number of records that the server can process for every iteration within the configured timeout interval. If the export fails you can lower this number accordingly and try again.', 'wf_csv_import_export'); ?></p>

                        </td>
                    </tr>
                    <tr></tr>

                </table>
                <progress class="wt-batch-exporter-progress" max="100" value="0"></progress>
            </section>
            <div class="wc-actions">
                <button type="submit" class="wt-batch-exporter-button button button-primary" value="<?php esc_attr_e('Start Export', 'wf_csv_import_export'); ?>"><?php esc_html_e('Start Export', 'wf_csv_import_export'); ?></button>
            </div>
        </form>
    </div>
</div>