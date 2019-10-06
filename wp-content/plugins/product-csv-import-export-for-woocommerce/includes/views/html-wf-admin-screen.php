<div class="woocommerce">
    <div class="icon32" id="icon-woocommerce-importer"><br></div>
    <?php include_once WT_PIPE_BASE_PATH . 'includes/views/html-wt-common-header.php'; ?>

    <?php
    switch ($tab) {
        case "export" :
            $this->admin_export_page();
            break;
        case "settings" :
            $this->admin_settings_page();
            break;
        case "review" :
            $this->admin_review_page();
            break;
        case "licence" :
            $this->admin_licence_page($plugin_name);
            break;
        case "help" :
            $this->admin_help_page();
            break;
        case "batchexport" :
            $this->admin_batchexport_page();
            break;
        default :
            $this->admin_import_page();
            break;
    }
    ?>
</div>