<ul class="subsubsub" style="margin-left: 15px;">
    <li><a href="<?php echo admin_url('admin.php?page=wf_pr_rev_csv_im_ex&tab=review') ?>" class="current"><?php _e('Export', 'wf_csv_import_export'); ?></a> | </li>
    <li><a href="<?php echo admin_url('admin.php?import=product_reviews_csv') ?>" class=""><?php _e('Import', 'wf_csv_import_export'); ?></a> </li>
</ul>
<br/>
<div class="tool-box bg-white p-20p">
    <h3 class="title aw-title"><?php _e('Export Product Reviews in CSV Format:', 'wf_csv_import_export'); ?></h3>
    <p><?php _e('Export and download your product reviews in CSV format. This file can be used to import product reviews back into your WooCommerce store.', 'wf_csv_import_export'); ?></p>
    <form action="<?php echo admin_url('admin.php?page=wf_pr_rev_csv_im_ex&action=export'); ?>" method="post">

        <table class="form-table">
            <!-- <tr>
                <th>
                    <label for="v_offset"><?php _e('Offset', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="offset" id="v_offset" placeholder="<?php _e('0', 'wf_csv_import_export'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('The number of product reviews to skip before returning', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>  -->           
            <tr>
                <th>
                    <label for="v_limit"><?php _e('Limit', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="limit" id="v_limit" placeholder="<?php _e('Unlimited', 'wf_csv_import_export'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('The number of product reviews you wish to export', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_date"><?php _e('Date', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="date" name="pr_rev_date_from" id="datepicker1" placeholder="<?php _e('From date', 'wf_csv_import_export'); ?>" class="input-text" /> -
                    <input type="date" name="pr_rev_date_to" id="datepicker2" placeholder="<?php _e('To date', 'wf_csv_import_export'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('The date interval of which the reviews are to be exported', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_prods"><?php _e('Products', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="v_prods" name="products[]" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'wf_csv_import_export'); ?>"></select>

                    <p style="font-size: 12px"><?php _e('Export reviews for the selected products', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_ratings"><?php _e('Stars', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <select id="v_ratings" name="stars[]" data-placeholder="<?php _e('Any', 'wf_csv_import_export'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<option value="' . $i . '">' . $i . ' Star</option>';
                        }
                        ?>
                    </select>

                    <p style="font-size: 12px"><?php _e('Export reviews for the selected star ratings', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_owner"><?php _e('Customer/Guest Review', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <select id="v_owner" name="owner" data-placeholder="<?php _e('All', 'wf_csv_import_export'); ?>" class="wc-enhanced-select">
                        <option value="">--All Reviews--</option>
                        <option value="verified">Customer </option>
                        <option value="non-verified">Guest</option>
                    </select>

                    <p style="font-size: 12px"><?php _e('Select to export reviews from verified customers', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_replycolumn"><?php _e('Review with replies', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="v_replycolumn" id="v_replycolumn" class="input-text" />
                    <p style="font-size: 12px"><?php _e('Check to export reviews with replies', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>             <tr>
                <th>
                    <label for="v_delimiter"><?php _e('Delimiter', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="delimiter" id="v_delimiter" placeholder="<?php _e(',', 'wf_csv_import_export'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('Separate the columns in the CSV file. Takes ‘,’ by default', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_columns"><?php _e('Column Mapping', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <table id="datagrid">
                        <th style="text-align: left;">
                            <label for="v_columns"><?php _e('Column', 'wf_csv_import_export'); ?></label>
                        </th>
                        <th style="text-align: left;">
                            <label for="v_columns_name"><?php _e('Column Name', 'wf_csv_import_export'); ?></label>
                        </th>

                        <?php
                        foreach ($post_columns as $pkey => $pcolumn) {
                            $ena = ($pkey == 'comment_alter_id') ? 'style="display:none;"' : '';
                            ?>
                            <tr <?php echo $ena; ?>>
                                <td>
                                    <input name= "columns[<?php echo $pkey; ?>]" type="checkbox" value="<?php echo $pkey; ?>" checked>
                                    <label for="columns[<?php echo $pkey; ?>]"><?php _e($pcolumn, 'wf_csv_import_export'); ?></label>
                                </td>
                                <td>
                                    <?php
                                    $tmpkey = $pkey;
                                    if (strpos($pkey, 'yoast') === false) {
                                        $tmpkey = ltrim($pkey, '_');
                                    }
                                    ?>
                                    <input type="text" name="columns_name[<?php echo $pkey; ?>]"  value="<?php echo $tmpkey; ?>" class="input-text" />
                                </td>
                            </tr>
                        <?php } ?>

                    </table>
                </td>
            </tr>


        </table>
        <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Export Product Reviews', 'wf_csv_import_export'); ?>" /></p>
    </form>
</div>