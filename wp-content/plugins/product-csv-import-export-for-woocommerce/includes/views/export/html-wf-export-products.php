<ul class="subsubsub" style="margin-left: 15px;">
    <li><a href="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex') ?>" class="current"><?php _e('Export', 'wf_csv_import_export'); ?></a> | </li>
    <li><a href="<?php echo admin_url('admin.php?import=woocommerce_csv') ?>" class=""><?php _e('Import', 'wf_csv_import_export'); ?></a> </li>
</ul>
<br/>
<div class="tool-box bg-white p-20p">
    <h3 class="title aw-title"><?php _e('Export Product in CSV/XML Format', 'wf_csv_import_export'); ?></h3>
    <p><?php _e('Export and download your products in CSV/XML format. This file can be used to import products back into your WooCommerce store.', 'wf_csv_import_export'); ?></p>
    <form action="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex&action=export'); ?>" method="post" id="wf_woocommerce_csv_im_ex_export">
        <table class="form-table">
            <tr>
                <th>
                    <label for="v_offset"><?php _e('Offset', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="offset" id="v_offset" placeholder="<?php _e('0', 'wf_csv_import_export'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('The number of products you wish to skip before exporting', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>            
            <tr>
                <th>
                    <label for="v_limit"><?php _e('Limit', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="limit" id="v_limit" placeholder="<?php _e('Unlimited', 'wf_csv_import_export'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('The number of products you wish to export', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="v_prod_types"><?php _e('Product Types', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <select id="v_prod_types" name="prod_types[]" data-placeholder="<?php _e('All Types', 'wf_csv_import_export'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                        foreach ($export_types as $type_slug => $type_name) {
                            echo '<option value="' . $type_slug . '">' . $type_name . '</option>';
                        }
                        ?>
                    </select>

                    <p style="font-size: 12px"><?php _e('Filter the products to be exported by type', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="v_prod_categories"><?php _e('Product Categories', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <!--<select class="wc-category-search" multiple="multiple" style="width: 40%;"  name="prod_categories[]" data-placeholder="<?php //esc_attr_e('Search for a category&hellip;', 'wf_csv_import_export');  ?>"></select>-->

                    <select id="v_prod_categories" name="prod_categories[]" data-placeholder="<?php _e('Any Category', 'wf_csv_import_export'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                        //$product_categories = get_terms('product_cat', array('fields' => 'id=>name'));
                        $product_categories = get_terms('product_cat');
                        foreach ($product_categories as $category) {
                            echo '<option value="' . $category->term_id . '">' . ( ( get_bloginfo('version') < '4.8') ? $category->name : get_term_parents_list($category->term_id, 'product_cat', array('separator' => ' -> ')) ) . '</option>';
                        }
                        ?>
                    </select>

                    <p style="font-size: 12px"><?php _e('Filter the products to be exported by categories', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="v_prod_tags"><?php _e('Product Tags', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <select id="v_prod_tags" name="prod_tags[]" data-placeholder="<?php _e('Any Tag', 'wf_csv_import_export'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                        $product_tags = get_terms('product_tag');
                        foreach ($product_tags as $tag) {
                            echo '<option value="' . $tag->term_id . '">' . $tag->name . '</option>';
                        }
                        ?>
                    </select>
                    <p style="font-size: 12px"><?php _e('Filter the products to be exported by tags', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>

            

            <tr>
                <th>
                    <label for="v_prod_status"><?php _e('Product Status', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <?php $prod_status = array('publish', 'private', 'draft', 'pending', 'future') ?>
                    <select id="v_prod_types" multiple name="prod_status[]" data-placeholder="<?php _e('All Status', 'wf_csv_import_export'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                        foreach ($prod_status as $type_name) {
                            echo '<option value="' . $type_name . '">' . ucwords($type_name) . '</option>';
                        }
                        ?>
                    </select>

                    <p style="font-size: 12px"><?php _e('Filter the products to be exported by Post status', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="v_sortcolumn"><?php _e('Sort Columns', 'wf_csv_import_export'); ?></label>
                </th>
                <td>

                    <?php $sortcolumn = array('post_parent', 'ID', 'post_author', 'post_date', 'post_title', 'post_name', 'post_modified', 'menu_order', 'post_modified_gmt', 'rand', 'comment_count') ?>
                    <select id="v_prod_types" name="sortcolumn[]" data-placeholder="<?php _e('post_parent , ID', 'wf_csv_import_export'); ?>" class="wc-enhanced-select" multiple="multiple">
                        <?php
                        foreach ($sortcolumn as $type_name) {
                            echo '<option value="' . $type_name . '">' . ucwords($type_name) . '</option>';
                        }
                        ?>
                    </select>
                    <!--<input type="text" name="sortcolumn" id="v_sortcolumn" placeholder="<?php _e('post_parent , ID', 'wf_csv_import_export'); ?>" class="input-text" />-->
                    <p style="font-size: 12px"><?php _e('Sort by: post_parent, ID, post_author , post_date , post_title, post_name, post_modified, menu_order, post_modified_gmt , rand , comment_count', 'wf_csv_import_export'); ?> </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_delimiter"><?php _e('Delimiter', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="delimiter" id="v_delimiter" placeholder="<?php _e(',', 'wf_csv_import_export'); ?>" class="input-text" />
                    <p style="font-size: 12px"><?php _e('Separate the columns in the CSV file. Takes ‘,’ by default', 'wf_csv_import_export'); ?></p>
                </td>
            </tr>



            <?php
            $export_mapping_from_db = get_option('xa_prod_csv_export_mapping');
            if (!empty($export_mapping_from_db)) {
                ?>
                <tr>
                    <th>
                        <label for="export_profile"><?php _e('Mapping Profile', 'wf_csv_import_export'); ?></label>
                    </th>
                    <td>
                        <select name="export_profile">
                            <option value="">--Select--</option>
                            <?php foreach ($export_mapping_from_db as $key => $value) { ?>
                                <option value="<?php echo $key; ?>"><?php echo $key; ?></option>

                            <?php } ?>
                        </select> <input type="button" name="delete_export_mapping" id="v_delete_export_mapping"  class="button button-primary" value="<?php _e('Delete Mapping Profile', 'wf_csv_import_export'); ?>" >
                        <span style="float: none" class ="delete spinner " ></span>
                        <span id="prod_delete_mapping_notice"></span>
                        <p style="font-size: 12px"><?php _e('Select the previously saved mapping profile', 'wf_csv_import_export'); ?></p>
                    </td>
                </tr>
            <?php } ?>


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
                        <!-- select all boxes -->
                        <tr>
                            <td style="padding: 10px;">
                                <a href="#" id="pselectall" onclick="return false;" >Select all</a> &nbsp;/&nbsp;
                                <a href="#" id="punselectall" onclick="return false;">Unselect all</a>
                            </td>
                        </tr>

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
//                                    else {
//                                        $tmpkey = 'meta' . $pkey;
//                                    }
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
                    <label for="v_new_profile"><?php _e('Save the export mapping', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="text" name="new_profile" id="v_new_profile" class="input-text" /> <input type="button" name="save_export_mapping" id="v_save_export_mapping"  class="button button-primary" value="<?php _e('Save Export Mapping', 'wf_csv_import_export'); ?>" >
                    <span style="float: none" class ="spinner " ></span>
                    <span id="prod_save_mapping_notice"></span>
                    <p style="font-size: 12px"><?php _e('Save the above mapping for reuse in later exports', 'wf_csv_import_export'); ?></p> 

                </td>

            </tr>
            <tr>
                <th>
                    <label for="v_include_hidden_meta"><?php _e('Include hidden meta data', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="include_hidden_meta" id="v_include_hidden_meta" class="checkbox" />
                    <p style="font-size: 12px"><?php _e('Check if you want to include hidden metadata also in the exported CSV', 'wf_csv_import_export'); ?></p> 
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_export_children_sku"><?php _e('Export children SKU of grouped products', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="v_export_children_sku" id="v_export_children_sku" class="checkbox" />
                    <p style="font-size: 12px"><?php _e('Check this to connect parent and child products with SKU', 'wf_csv_import_export'); ?></p> 
                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_export_do_shortcode"><?php _e('Convert shortcodes to HTML', 'wf_csv_import_export'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="v_export_do_shortcode" id="v_export_do_shortcode" class="checkbox" />
                    <p style="font-size: 12px"><?php _e('Check this to convert the shortcode to HTML in the exported CSV', 'wf_csv_import_export'); ?></p> 

                </td>
            </tr>
            <tr>
                <th>
                    <label for="v_export_images_zip"><?php _e('Export images as zip file', 'wf_csv_import_export'); ?></label>
                </th>
                <td> <select name="v_export_images_zip" id="v_export_images_zip" class="">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select> 
                    <p class="export_images_zip_carry_msg_no" style="font-size: 12px"><?php _e('The exported CSV will contain the URL path of the images. <a href="https://www.webtoffee.com/exporting-importing-woocommerce-products-images-with-zip-file/" target="_blank" > Learn More</a>.', 'wf_csv_import_export'); ?></p>                             

                    <div class="tool-box export_images_zip_carry">  
                        <p class="export_images_zip_carry_msg_yes" style="font-size: 12px"><?php _e('This option downloads the product images in a separate zip file. The exported CSV will contain the name of the images instead of their URL path. Use this option if you have a large number of products to import or if you experience slowness during the import process. <a href="https://www.webtoffee.com/exporting-importing-woocommerce-products-images-with-zip-file/" target="_blank" > Learn More</a>.', 'wf_csv_import_export'); ?></p>                             
                        <p class="submit "><input id="export_images_zip_button" type="button" class="button button-primary" value="<?php _e('Export Product\'s Images', 'wf_csv_import_export'); ?>" /></p>        
                    </div>
                </td>
            </tr>
        </table>
        <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Export Products (CSV)', 'wf_csv_import_export'); ?>" />
            <input type="submit" class="button button-primary" value="<?php _e('Export Product (XML)', 'wf_csv_import_export'); ?>" formaction="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex&action=export&xml=1'); ?>"/></p>
    </form>
</div>
