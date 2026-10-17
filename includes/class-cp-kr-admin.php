<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
    }

    public static function menu() {
        add_menu_page(
            'Cloud Packaging', 'Cloud Packaging', 'manage_options',
            'cp-kr', [__CLASS__, 'page_dashboard'], 'dashicons-chart-line', 56
        );
        add_submenu_page('cp-kr', 'Keyword Research', 'Keyword Research', 'manage_options', 'cp-kr', [__CLASS__, 'page_dashboard']);
        add_submenu_page('cp-kr', 'Settings', 'Settings', 'manage_options', 'cp-kr-settings', [__CLASS__, 'page_settings']);
    }

    public static function assets($hook) {
        if (false === strpos($hook, 'cp-kr')) return;
        wp_enqueue_style('cp-kr-admin', CP_KR_PLUGIN_URL . 'admin/css/admin.css', [], CP_KR_VERSION);
        wp_enqueue_script('cp-kr-admin', CP_KR_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], filemtime(CP_KR_PLUGIN_DIR . 'admin/js/admin.js'), true);
        wp_localize_script('cp-kr-admin', 'cpKr', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cp_kr_ajax'),
        ]);
    }

    public static function page_dashboard() {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        if ($job_id) {
            include CP_KR_PLUGIN_DIR . 'admin/views/job-detail.php';
        } else {
            include CP_KR_PLUGIN_DIR . 'admin/views/dashboard.php';
        }
    }

    public static function page_settings() {
        if (!current_user_can('manage_options')) wp_die('Forbidden');

        if (!empty($_POST) && check_admin_referer('cp_kr_save_settings')) {
            $s = get_option('cp_kr_settings', []);
            $s['min_volume'] = (int)($_POST['min_volume'] ?? 30);
            $s['max_bid_micros'] = (int)round(((float)($_POST['max_bid_gbp'] ?? 3.0)) * 1000000);
            $s['geo_target'] = sanitize_text_field($_POST['geo_target'] ?? 'geoTargetConstants/2826');
            $s['language'] = sanitize_text_field($_POST['language'] ?? 'languageConstants/1000');
            $s['network'] = sanitize_text_field($_POST['network'] ?? 'GOOGLE_SEARCH');
            $s['include_adult'] = !empty($_POST['include_adult']);
            $s['max_seeds_per_batch'] = (int)($_POST['max_seeds_per_batch'] ?? 10);
            $s['max_requests_per_job'] = (int)($_POST['max_requests_per_job'] ?? 200);
            update_option('cp_kr_settings', $s);

            if (isset($_POST['brands'])) {
                $brands = array_filter(array_map('trim', explode("\n", wp_unslash($_POST['brands']))));
                update_option('cp_kr_brand_list', array_values($brands));
            }
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        include CP_KR_PLUGIN_DIR . 'admin/views/settings.php';
    }
}