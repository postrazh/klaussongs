    <?php // include_once WT_PIPE_BASE_PATH.'includes/views/html-wt-common-header.php';  ?>
<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex') ?>" class="nav-tab <?php echo ($tab == 'import') ? 'nav-tab-active' : ''; ?>"><?php _e('Product', 'wf_csv_import_export'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wf_pr_rev_csv_im_ex&tab=review') ?>" class="nav-tab <?php echo ($tab == 'review') ? 'nav-tab-active' : ''; ?>"><?php _e('Product Reviews ', 'wf_csv_import_export'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex&tab=batchexport') ?>" class="nav-tab <?php echo ($tab == 'batchexport') ? 'nav-tab-active' : ''; ?>"><?php _e('Batch Export', 'wf_csv_import_export'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex&tab=settings') ?>" class="nav-tab <?php echo ($tab == 'settings') ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'wf_csv_import_export'); ?></a>
        <?php
        $plugin_name = 'productimportexport';
        $status = get_option($plugin_name . '_activation_status'); ?>
        <a href="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex&tab=licence') ?>" class="nav-tab licence-tab <?php echo ($tab == 'licence') ? 'nav-tab-active' : ''; ?>"><?php _e('Licence', 'wf_csv_import_export') . ($status ? _e('<span class="actived">Activated</span>', 'wf_csv_import_export') : _e('<span class="deactived">Deactivated</span>', 'wf_csv_import_export')); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wf_woocommerce_csv_im_ex&tab=help') ?>" class="nav-tab <?php echo ($tab == 'help') ? 'nav-tab-active' : ''; ?>"><?php _e('Help Guide', 'wf_csv_import_export'); ?></a> 
    </h2>