<?php
if (!defined('ABSPATH')) exit;

class CP_KR_DB {

    public static function insert_job($d) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'cp_kr_jobs', $d);
        return $wpdb->insert_id;
    }

    public static function update_job($id, $d) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'cp_kr_jobs', $d, ['id' => $id]);
    }

    public static function get_job($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cp_kr_jobs WHERE id = %d", $id
        ));
    }

    public static function get_jobs($limit = 50) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cp_kr_jobs ORDER BY id DESC LIMIT %d",
            (int)$limit
        ));
    }

    public static function delete_job($job_id) {
        global $wpdb;

        $job_id = (int)$job_id;
        if ($job_id <= 0 || !self::get_job($job_id)) {
            return new WP_Error('cp_kr_job_not_found', 'Research job not found.');
        }

        $wpdb->query('START TRANSACTION');
        $related_tables = [
            'cp_kr_results',
            'cp_kr_candidates',
            'cp_kr_rejected',
            'cp_kr_sources',
            'cp_kr_variation_seeds',
        ];

        foreach ($related_tables as $table) {
            $deleted = $wpdb->delete($wpdb->prefix . $table, ['job_id' => $job_id], ['%d']);
            if (false === $deleted) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('cp_kr_job_delete_failed', 'Could not delete all research job data.');
            }
        }

        $deleted = $wpdb->delete($wpdb->prefix . 'cp_kr_jobs', ['id' => $job_id], ['%d']);
        if (false === $deleted) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('cp_kr_job_delete_failed', 'Could not delete the research job.');
        }

        $wpdb->query('COMMIT');
        return true;
    }

    public static function insert_result($d) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'cp_kr_results', $d);
        return $wpdb->insert_id;
    }

    public static function get_results($job_id, $stage = null) {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cp_kr_results WHERE job_id = %d", $job_id
        );
        if ($stage) {
            $sql .= $wpdb->prepare(" AND stage = %s", $stage);
        }
        return $wpdb->get_results($sql);
    }

    public static function insert_candidate($d) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'cp_kr_candidates', $d);
        return $wpdb->insert_id;
    }

    public static function get_reviewed_keyword_keys($job_id) {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT normalized_text FROM {$wpdb->prefix}cp_kr_candidates WHERE job_id = %d
             UNION SELECT normalized_text FROM {$wpdb->prefix}cp_kr_rejected WHERE job_id = %d",
            (int)$job_id,
            (int)$job_id
        );
        return array_fill_keys((array)$wpdb->get_col($sql), true);
    }

    public static function update_candidate($id, $d) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'cp_kr_candidates', $d, ['id' => (int)$id]);
    }

    public static function get_candidates($job_id, $args = []) {
        global $wpdb;
        $where = $wpdb->prepare("job_id = %d", $job_id);
        if (!empty($args['review_status'])) {
            $where .= $wpdb->prepare(" AND review_status = %s", $args['review_status']);
        }
        if (!empty($args['relevance_rating'])) {
            $where .= $wpdb->prepare(" AND relevance_rating = %s", $args['relevance_rating']);
        }

        $ob = $args['orderby'] ?? 'ranking_score';
        $allowed = ['ranking_score','avg_monthly_searches','high_top_of_page_bid_micros','relevance_rating','keyword_text','id'];
        if (!in_array($ob, $allowed, true)) $ob = 'ranking_score';

        $ord = strtoupper($args['order'] ?? 'DESC');
        if (!in_array($ord, ['ASC','DESC'], true)) $ord = 'DESC';

        $lim = (int)($args['limit'] ?? 100);
        $off = (int)($args['offset'] ?? 0);

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}cp_kr_candidates WHERE {$where} ORDER BY {$ob} {$ord} LIMIT {$lim} OFFSET {$off}"
        );
    }

    public static function count_candidates($job_id, $args = []) {
        global $wpdb;
        $w = $wpdb->prepare("job_id = %d", $job_id);
        if (!empty($args['review_status'])) {
            $w .= $wpdb->prepare(" AND review_status = %s", $args['review_status']);
        }
        return (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cp_kr_candidates WHERE {$w}"
        );
    }

    public static function insert_rejected($d) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'cp_kr_rejected', $d);
        return $wpdb->insert_id;
    }

    public static function get_rejected($job_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cp_kr_rejected WHERE job_id = %d ORDER BY id ASC",
            $job_id
        ));
    }

    public static function insert_source($d) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'cp_kr_sources', $d);
        return $wpdb->insert_id;
    }

    public static function get_sources($job_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cp_kr_sources WHERE job_id = %d", $job_id
        ));
    }

    public static function insert_variation_seed($d) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'cp_kr_variation_seeds', $d);
        return $wpdb->insert_id;
    }

    public static function update_variation_seed($id, $d) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'cp_kr_variation_seeds', $d, ['id' => (int)$id]);
    }

    public static function get_variation_seeds($job_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cp_kr_variation_seeds WHERE job_id = %d", $job_id
        ));
    }
}