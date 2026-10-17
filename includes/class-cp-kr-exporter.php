<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Exporter {

    public static function csv($rows, $filename, $headers = null) {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
        if ($headers) fputcsv($out, $headers);
        foreach ($rows as $row) {
            $safe = array_map([__CLASS__, 'safe'], (array)$row);
            fputcsv($out, $safe);
        }
        fclose($out);
        exit;
    }

    public static function safe($v) {
        $v = (string)$v;
        if (preg_match('/^[=+\-@\t\r]/', $v)) {
            $v = "'" . $v;
        }
        return $v;
    }

    public static function approved($job_id) {
        $job = CP_KR_DB::get_job($job_id);
        $rows = CP_KR_DB::get_candidates($job_id, ['review_status' => 'accepted', 'limit' => 10000]);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r->keyword_text,
                $job ? $job->product_id : '',
                implode(',', json_decode($r->variation_ids, true) ?: []),
                $r->avg_monthly_searches,
                self::micros_to_gbp($r->low_top_of_page_bid_micros),
                self::micros_to_gbp($r->high_top_of_page_bid_micros),
                $r->competition,
                $r->competition_index,
                self::source_summary($r->sources),
                implode(',', json_decode($r->stages, true) ?: []),
                $r->relevance_rating,
                $r->relevance_notes,
                $r->commercial_intent,
                $r->ranking_score,
                $r->review_status,
                $r->notes,
            ];
        }
        return $out;
    }

    public static function all_candidates($job_id) {
        $rows = CP_KR_DB::get_candidates($job_id, ['limit' => 10000]);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r->keyword_text,
                $r->avg_monthly_searches,
                self::micros_to_gbp($r->low_top_of_page_bid_micros),
                self::micros_to_gbp($r->high_top_of_page_bid_micros),
                $r->competition,
                $r->relevance_rating,
                $r->relevance_notes,
                $r->commercial_intent,
                $r->ranking_score,
                $r->review_status,
                self::source_summary($r->sources),
                implode(',', json_decode($r->stages, true) ?: []),
            ];
        }
        return $out;
    }

    public static function rejected($job_id) {
        $rows = CP_KR_DB::get_rejected($job_id);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r->keyword_text,
                $r->rejection_code,
                $r->rejection_reason,
                $r->detection_method,
                $r->suspected_brand,
                $r->source_stage,
            ];
        }
        return $out;
    }

    public static function sources($job_id) {
        $rows = CP_KR_DB::get_sources($job_id);
        $vs = CP_KR_DB::get_variation_seeds($job_id);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r->source_type, $r->source_value, $r->fetch_status, $r->fetch_error];
        }
        foreach ($vs as $v) {
            $out[] = ['variation_seed', 'Variation ' . $v->variation_id, $v->status, ''];
        }
        return $out;
    }

    public static function complete_job($job_id) {
        $job = CP_KR_DB::get_job($job_id);
        if (!$job) return [];

        $records = CP_KR_DB::get_job_export_records($job_id);
        $headers = [
            'Record Type', 'Job ID', 'Record ID', 'Product ID', 'Keyword', 'Normalized Keyword',
            'Status', 'Stage', 'Source Type', 'Source Value', 'Avg Monthly Searches',
            'Low Bid Micros', 'High Bid Micros', 'Rejection Code', 'Reason', 'Detection Method',
            'Relevance', 'Intent', 'Ranking Score', 'Error', 'Created At', 'Full Record JSON',
        ];
        $rows = [];
        $rows[] = [
            'job', $job->id, $job->id, $job->product_id, '', '', $job->status,
            $job->current_stage, '', '', '', '', '', '', '', '', '', '', '',
            $job->error_log, $job->created_at, wp_json_encode($job),
        ];

        foreach ($records as $record_type => $items) {
            foreach ($items as $item) {
                $rows[] = [
                    $record_type,
                    $item->job_id ?? $job->id,
                    $item->id ?? '',
                    $job->product_id,
                    $item->keyword_text ?? '',
                    $item->normalized_text ?? '',
                    $item->review_status ?? $item->fetch_status ?? $item->status ?? '',
                    $item->stage ?? $item->source_stage ?? '',
                    $item->source_type ?? '',
                    $item->source_value ?? '',
                    $item->avg_monthly_searches ?? '',
                    $item->low_top_of_page_bid_micros ?? '',
                    $item->high_top_of_page_bid_micros ?? '',
                    $item->rejection_code ?? '',
                    $item->rejection_reason ?? $item->relevance_notes ?? $item->fetch_error ?? '',
                    $item->detection_method ?? '',
                    $item->relevance_rating ?? '',
                    $item->commercial_intent ?? '',
                    $item->ranking_score ?? '',
                    $item->error_log ?? $item->fetch_error ?? '',
                    $item->created_at ?? '',
                    wp_json_encode($item),
                ];
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private static function micros_to_gbp($micros) {
        if ($micros === null) return '';
        return number_format(((int)$micros) / 1000000, 2, '.', '');
    }

    private static function source_summary($json) {
        $arr = json_decode($json, true) ?: [];
        return implode('; ', array_map(
            fn($s) => ($s['type'] ?? '') . ':' . ($s['value'] ?? ''),
            $arr
        ));
    }
}
