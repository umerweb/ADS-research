<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Ajax {
    const STAGE_BUDGET_SECONDS = 25;

    public static function init() {
        add_action('wp_ajax_cp_kr_search_products',  [__CLASS__, 'search_products']);
        add_action('wp_ajax_cp_kr_start_job',        [__CLASS__, 'start_job']);
        add_action('wp_ajax_cp_kr_job_status',       [__CLASS__, 'job_status']);
        add_action('wp_ajax_cp_kr_test_credentials', [__CLASS__, 'test_credentials']);
        add_action('wp_ajax_cp_kr_update_candidate', [__CLASS__, 'update_candidate']);
        add_action('wp_ajax_cp_kr_export',           [__CLASS__, 'export']);
        add_action('wp_ajax_cp_kr_kick_job',         [__CLASS__, 'kick_job']);
        add_action('wp_ajax_cp_kr_complete_job',     [__CLASS__, 'complete_job']);
        add_action('wp_ajax_cp_kr_delete_job',       [__CLASS__, 'delete_job']);
    }

    private static function check() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Forbidden: administrator access required.', 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'cp_kr_ajax')) {
            wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        }
    }

    private static function run_until_budget($job_id, $budget_seconds = self::STAGE_BUDGET_SECONDS) {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $start = microtime(true);
        $stages_run = [];

        while ((microtime(true) - $start) < $budget_seconds) {
            $job = CP_KR_DB::get_job($job_id);
            if (!$job) break;
            if (in_array($job->status, ['completed', 'failed'], true)) break;

            $before_stage = $job->current_stage;
            $result = CP_KR_Research_Engine::run_stage($job_id);
            $job = CP_KR_DB::get_job($job_id);
            if (!$job) break;

            $stages_run[] = $before_stage . ' -> ' . $job->current_stage;

            if (in_array($job->status, ['completed', 'failed'], true)) break;
            if (!$result) break;
            if ($job->current_stage === $before_stage) break;
        }

        $job = CP_KR_DB::get_job($job_id);
        return [
            'status' => $job ? $job->status : 'unknown',
            'stage' => $job ? $job->current_stage : '',
            'stages_run' => $stages_run,
            'api_requests' => $job ? (int)$job->api_requests : 0,
            'error' => $job ? $job->error_log : '',
        ];
    }

    public static function search_products() {
        self::check();
        $q = sanitize_text_field($_POST['q'] ?? '');
        $args = ['post_type' => 'product', 'posts_per_page' => 20, 'post_status' => 'publish'];
        if ($q) $args['s'] = $q;
        $posts = get_posts($args);
        $out = [];
        foreach ($posts as $p) {
            $prod = wc_get_product($p->ID);
            $out[] = ['id' => $p->ID, 'title' => $p->post_title, 'type' => $prod ? $prod->get_type() : 'simple'];
        }
        wp_send_json_success($out);
    }

    public static function start_job() {
        self::check();
        $pid = (int)($_POST['product_id'] ?? 0);
        $urls = [];
        for ($i = 1; $i <= 3; $i++) {
            $u = isset($_POST['url_' . $i]) ? esc_url_raw($_POST['url_' . $i]) : '';
            if ($u) $urls[] = $u;
        }
        $job_id = CP_KR_Research_Engine::create_job($pid, $urls);
        if (is_wp_error($job_id)) wp_send_json_error($job_id->get_error_message());

        $progress = self::run_until_budget($job_id);

        wp_send_json_success([
            'job_id' => $job_id,
            'progress' => $progress,
        ]);
    }

    public static function kick_job() {
        self::check();
        $job_id = (int)($_POST['job_id'] ?? 0);
        if (!$job_id) wp_send_json_error('Invalid job');

        $progress = self::run_until_budget($job_id);
        wp_send_json_success($progress);
    }

    public static function complete_job() {
        self::check();
        $job_id = (int)($_POST['job_id'] ?? 0);
        if (!$job_id) wp_send_json_error('Invalid job');
        CP_KR_DB::update_job($job_id, [
            'status' => 'completed',
            'current_stage' => 'completed',
        ]);
        wp_send_json_success(['job_id' => $job_id]);
    }

    public static function delete_job() {
        self::check();
        $job_id = (int)($_POST['job_id'] ?? 0);
        if (!$job_id) wp_send_json_error('Invalid job');

        $result = CP_KR_DB::delete_job($job_id);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['job_id' => $job_id]);
    }

    public static function job_status() {
        self::check();
        $job_id = (int)($_POST['job_id'] ?? 0);
        $job = CP_KR_DB::get_job($job_id);
        if (!$job) wp_send_json_error('Job not found');
        wp_send_json_success([
            'status' => $job->status,
            'stage' => $job->current_stage,
            'api_requests' => (int)$job->api_requests,
            'candidates' => CP_KR_DB::count_candidates($job_id),
            'rejected' => count(CP_KR_DB::get_rejected($job_id)),
            'error' => $job->error_log,
        ]);
    }

    public static function test_credentials() {
        self::check();

        if (!defined('CP_GOOGLE_ADS_CLIENT_ID') || !defined('CP_GOOGLE_ADS_CLIENT_SECRET') || !defined('CP_GOOGLE_ADS_REFRESH_TOKEN') || !defined('CP_GOOGLE_ADS_CUSTOMER_ID')) {
            wp_send_json_error('Missing one or more Google Ads constants in wp-config.php.');
        }

        $result = CP_KR_OAuth::validate();

        if (is_wp_error($result)) {
            wp_send_json_error('OAuth/API error: ' . $result->get_error_message());
        }

        wp_send_json_success([
            'customers' => $result,
            'customer_id' => preg_replace('/\D/', '', CP_GOOGLE_ADS_CUSTOMER_ID),
            'api_version' => CP_KR_API_VERSION,
        ]);
    }

    public static function update_candidate() {
        self::check();
        $id = (int)($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        if (!in_array($status, ['accepted', 'rejected', 'pending_review', 'needs_metrics', 'needs_relevance_review'], true)) {
            wp_send_json_error('Invalid status');
        }
        CP_KR_DB::update_candidate($id, ['review_status' => $status, 'rejection_reason' => $reason]);
        wp_send_json_success();
    }

    public static function export() {
        self::check();
        $job_id = (int)($_POST['job_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? 'approved');

        switch ($type) {
            case 'approved':
                $rows = CP_KR_Exporter::approved($job_id);
                $headers = ['Keyword','Product ID','Variations','Avg Monthly Searches','Low Bid (GBP)','High Bid (GBP)','Competition','Competition Index','Sources','Stages','Relevance','Intent','Score','Status','Notes'];
                break;
            case 'all':
                $rows = CP_KR_Exporter::all_candidates($job_id);
                $headers = ['Keyword','Avg Monthly Searches','Low Bid','High Bid','Competition','Relevance','Intent','Score','Status','Sources','Stages'];
                break;
            case 'rejected':
                $rows = CP_KR_Exporter::rejected($job_id);
                $headers = ['Keyword','Rejection Code','Reason','Detection Method','Suspected Brand','Stage'];
                break;
            case 'sources':
                $rows = CP_KR_Exporter::sources($job_id);
                $headers = ['Type','Value','Status','Error'];
                break;
            default:
                wp_send_json_error('Invalid export type');
        }
        CP_KR_Exporter::csv($rows, "cp-kr-{$type}-job{$job_id}.csv", $headers);
    }
}