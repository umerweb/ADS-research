<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
global $wpdb;

$tables = ['cp_kr_jobs', 'cp_kr_results', 'cp_kr_candidates', 'cp_kr_rejected', 'cp_kr_sources', 'cp_kr_variation_seeds'];
foreach ($tables as $t) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$t}");
}

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cp_kr_%' OR option_name LIKE '_transient_timeout_cp_kr_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'cp_kr_%'");