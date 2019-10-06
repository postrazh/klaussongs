<div class="tool-box bg-white p-20p ">
    <h3 class="title aw-title"><?php _e('Import Products in CSV Format', 'wf_csv_import_export'); ?></h3>
    <p><?php _e('Import products in CSV format ( works for simple, grouped, external and variable products)  from different sources (  from your computer OR from another server via FTP )', 'wf_csv_import_export'); ?></p>
    <p class="submit">
        <?php
        $merge_url = admin_url('admin.php?import=woocommerce_csv&merge=1');
        $merge_n_skip_url = admin_url('admin.php?import=woocommerce_csv&merge=1&skip_new=1');
        $skip_url = admin_url('admin.php?import=woocommerce_csv&skip_new=1');
        $import_url = admin_url('admin.php?import=woocommerce_csv');
        ?>
        <a class="button button-primary" id="mylink" href="<?php echo admin_url('admin.php?import=woocommerce_csv'); ?>"><?php _e('Go to Import', 'wf_csv_import_export'); ?></a>
        &nbsp;
        <input type="checkbox" id="merge" value="0"><?php _e('Update Existing Products ', 'wf_csv_import_export'); ?>
        &nbsp;
        <input type="checkbox" id="skip_new" value="0"><?php _e('Skip New Products', 'wf_csv_import_export'); ?> <br>
    </p>
</div>
<script type="text/javascript">

    var $merge = jQuery("#merge");
    var $skip_new = jQuery("#skip_new");
    var $mylink = jQuery("#mylink");

    jQuery('#merge,#skip_new').click(function () {

        if ($merge.is(":checked") && !$skip_new.is(":checked")) {
            $mylink.attr("href", '<?php echo $merge_url ?>');
        } else if (!$merge.is(":checked") && $skip_new.is(":checked")) {
            $mylink.attr("href", '<?php echo $skip_url ?>');
        } else if ($merge.is(":checked") && $skip_new.is(":checked")) {
            $mylink.attr("href", '<?php echo $merge_n_skip_url ?>');
        } else
            $mylink.attr("href", '<?php echo $import_url ?>');
    });

</script>