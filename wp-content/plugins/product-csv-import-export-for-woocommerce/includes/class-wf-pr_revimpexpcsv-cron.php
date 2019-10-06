<?php
if (!defined('WPINC'))
    exit; // Exit if accessed directly

class WF_PrRevImpExpCsv_Cron {

    public $settings;

    public function __construct() {
        add_filter('cron_schedules', array($this, 'wf_auto_export_schedule'));
        add_action('init', array($this, 'wf_new_scheduled_pr_rev_export'));
        add_action('wf_pr_rev_csv_im_ex_auto_export_products', array($this, 'wf_scheduled_export_products'));
        $this->settings = get_option('woocommerce_' . WF_PROD_IMP_EXP_ID . '_settings', null);
        $this->exports_enabled = FALSE;
        if (isset($this->settings['rev_auto_export']) && ($this->settings['rev_auto_export'] === 'Enabled') && isset($this->settings['rev_enable_ftp_ie']) && $this->settings['rev_enable_ftp_ie'] === TRUE)
            $this->exports_enabled = TRUE;
    }

    public function wf_auto_export_schedule($schedules) {
        if ($this->exports_enabled) {
            $export_interval = $this->settings['rev_auto_export_interval'];
            if ($export_interval) {
                $schedules['rev_export_interval'] = array(
                    'interval' => (int) $export_interval * 60,
                    'display' => sprintf(__('Every %d minutes', 'wf_csv_import_export'), (int) $export_interval)
                );
            }
        }
        return $schedules;
    }

    public function wf_new_scheduled_pr_rev_export() {
        if ($this->exports_enabled) {
            if (!wp_next_scheduled('wf_pr_rev_csv_im_ex_auto_export_products')) {
                $start_time = $this->settings['rev_auto_export_start_time'];
                $current_time = current_time('timestamp');
                if ($start_time) {
                    if ($current_time > strtotime('today ' . $start_time, $current_time)) {
                        $start_timestamp = strtotime('tomorrow ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    } else {
                        $start_timestamp = strtotime('today ' . $start_time, $current_time) - ( get_option('gmt_offset') * HOUR_IN_SECONDS );
                    }
                } else {
                    $export_interval = $this->settings['rev_auto_export_interval'];
                    $start_timestamp = strtotime("now +{$export_interval} minutes");
                }
                wp_schedule_event($start_timestamp, 'rev_export_interval', 'wf_pr_rev_csv_im_ex_auto_export_products');
            }
        }
    }

    public function wf_scheduled_export_products() {
        include_once( 'exporter/class-wf-pr_revimpexpcsv-exporter.php' );
        WF_PrRevImpExpCsv_Exporter::do_export();
    }

    public function clear_wf_scheduled_pr_rev_export() {
        wp_clear_scheduled_hook('wf_pr_rev_csv_im_ex_auto_export_products');
    }

}