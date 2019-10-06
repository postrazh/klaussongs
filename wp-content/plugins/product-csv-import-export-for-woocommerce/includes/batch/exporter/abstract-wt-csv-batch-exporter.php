<?php

/*
 * https://pippinsplugins.com/batch-processing-for-big-data/
 * 
 */

if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('WT_pipe_Exporter', false)) {
    require_once 'abstract-wt-csv-exporter.php';
}

abstract class WT_pipe_Batch_Exporter extends WT_pipe_Exporter {

    protected $page = 1;

    public function __construct() { 
        $this->column_names = $this->get_default_column_names();
    }

    protected function get_file_path() {

        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['basedir']) . $this->get_filename();
    }

    public function get_file() {

        $file = '';
        if (@file_exists($this->get_file_path())) {
            $file = @file_get_contents($this->get_file_path());
        } else {
            @file_put_contents($this->get_file_path(), '');
            @chmod($this->get_file_path(), 0664);
        }
        return $file;
    }

    public function export() {

        $this->send_headers();
        $this->send_content($this->get_file());
        @unlink($this->get_file_path());
        die();
    }

    public function generate_file() {

        if (1 === $this->get_page()) {
            @unlink($this->get_file_path());
        }
        $this->prepare_data_to_export(); // class-wt-product-csv-exporter.php WT_Batch_CSV_Exporter::prepare_data_to_export
        $this->write_csv_data($this->get_csv_data());
    }

    protected function write_csv_data($data) {

        $file = $this->get_file();

        // add BOM ( byte order mark )
        if (100 === $this->get_percent_complete()) {
            $file = chr(239) . chr(187) . chr(191) . $this->export_column_headers() . $file;
        }

        $file .= $data;
        @file_put_contents($this->get_file_path(), $file);
    }

    public function get_page() {
        return $this->page;
    }

    public function set_page($page) {
        $this->page = absint($page);
    }

    public function get_total_exported() {
        return ( ( $this->get_page() - 1 ) * $this->get_limit() ) + $this->exported_row_count;
    }

    public function get_percent_complete() {
        return $this->total_rows ? floor(( $this->get_total_exported() / $this->total_rows ) * 100) : 100;
    }

}