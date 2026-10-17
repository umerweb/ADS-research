<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Research_Engine {

    public static function create_job($product_id, $competitor_urls = []) {
        $product_id = (int)$product_id;
        if ($product_id <= 0) return new WP_Error('cp_kr_invalid', 'Invalid product.');

        $urls = array_slice(array_filter(array_map('esc_url_raw', (array)$competitor_urls)), 0, 3);

        return CP_KR_DB::insert_job([
            'status' => 'pending',
            'current_stage' => 'stage_1_product_url',
            'stage_data' => wp_json_encode([]),
            'product_id' => $product_id,
            'competitor_urls' => wp_json_encode($urls),
            'created_at' => current_time('mysql'),
        ]);
    }

    public static function run_stage($job_id) {
        $job = CP_KR_DB::get_job($job_id);
        if (!$job) return false;

        if ($job->status === 'completed') return false;

        if ($job->status === 'failed') {
            CP_KR_DB::update_job($job_id, ['status' => 'pending', 'error_log' => '']);
        }

        $settings = get_option('cp_kr_settings', []);
        $stage    = $job->current_stage;
        $profile  = CP_KR_Product_Profile::build($job->product_id);

        if (is_wp_error($profile)) {
            CP_KR_DB::update_job($job_id, ['status' => 'failed', 'error_log' => $profile->get_error_message()]);
            return false;
        }

        try {
            switch ($stage) {
                case 'stage_1_product_url':
                    $next = self::stage_1($job_id, $profile, $settings);
                    break;
                case 'stage_2_competitors':
                    $next = self::stage_2($job_id, $profile, $settings);
                    break;
                case 'stage_3_filter':
                    $next = self::stage_3($job_id, $profile, $settings);
                    break;
                case 'stage_4_top_seeds':
                    $next = self::stage_4($job_id, $profile, $settings);
                    break;
                case 'stage_5_expansion':
                    $next = self::stage_5($job_id, $profile, $settings);
                    break;
                case 'stage_6_variations':
                    $next = self::stage_6($job_id, $profile, $settings);
                    break;
                case 'stage_7_consolidate':
                    $next = self::stage_7($job_id, $profile, $settings);
                    break;
                case 'stage_8_brand':
                case 'completed':
                default:
                    // Terminal: complete immediately, no further transitions.
                    CP_KR_DB::update_job($job_id, [
                        'status' => 'completed',
                        'current_stage' => 'completed',
                    ]);
                    return false;
            }
        } catch (Throwable $e) {
            CP_KR_DB::update_job($job_id, ['status' => 'failed', 'error_log' => $e->getMessage()]);
            return false;
        }

        if (is_wp_error($next)) {
            CP_KR_DB::update_job($job_id, ['status' => 'failed', 'error_log' => $next->get_error_message()]);
            return false;
        }

        if ($next === null) {
            CP_KR_DB::update_job($job_id, ['status' => 'completed', 'current_stage' => 'completed']);
            return false;
        }

        CP_KR_DB::update_job($job_id, [
            'status'         => 'running',
            'current_stage'  => $next,
            'api_requests'   => (int)$job->api_requests + 1,
        ]);
        return true;
    }

    private static function store_ideas($job_id, $data, $source_type, $source_value, $stage, $variation_id = 0) {
        if (empty($data['results'])) return 0;
        $count = 0;
        foreach ($data['results'] as $r) {
            $metrics = $r['keywordIdeaMetrics'] ?? [];
            CP_KR_DB::insert_result([
                'job_id' => $job_id,
                'keyword_text' => $r['text'] ?? '',
                'normalized_text' => mb_strtolower(trim($r['text'] ?? '')),
                'source_type' => $source_type,
                'source_value' => mb_substr($source_value, 0, 500),
                'stage' => $stage,
                'variation_id' => $variation_id,
                'avg_monthly_searches' => isset($metrics['avgMonthlySearches']) ? (int)$metrics['avgMonthlySearches'] : null,
                'competition' => $metrics['competition'] ?? null,
                'competition_index' => isset($metrics['competitionIndex']) ? (int)$metrics['competitionIndex'] : null,
                'low_top_of_page_bid_micros' => isset($metrics['lowTopOfPageBidMicros']) ? (int)$metrics['lowTopOfPageBidMicros'] : null,
                'high_top_of_page_bid_micros' => isset($metrics['highTopOfPageBidMicros']) ? (int)$metrics['highTopOfPageBidMicros'] : null,
                'close_variants' => wp_json_encode($r['closeVariants'] ?? []),
                'monthly_search_volumes' => wp_json_encode($metrics['monthlySearchVolumes'] ?? []),
                'api_version' => CP_KR_API_VERSION,
                'created_at' => current_time('mysql'),
            ]);
            $count++;
        }
        return $count;
    }

    private static function stage_1($job_id, $profile, $settings) {
        $opts = self::api_opts($settings);
        $job = CP_KR_DB::get_job($job_id);
        $stage_data = json_decode($job->stage_data, true) ?: [];

        if (!isset($stage_data['product_seed_phrases'])) {
            $stage_data['product_seed_phrases'] = self::build_product_seed_phrases($profile);
            $stage_data['product_seed_offset'] = 0;
            $stage_data['product_url_scan_done'] = false;
            CP_KR_DB::update_job($job_id, ['stage_data' => wp_json_encode($stage_data)]);
        }

        if (empty($stage_data['product_url_scan_done'])) {
            $url_ideas = CP_KR_Api_Client::generate_keyword_ideas('url', ['url' => $profile['canonical_url']], $opts);
            if (is_wp_error($url_ideas)) return $url_ideas;
            self::store_ideas($job_id, $url_ideas, 'product_url_scan', $profile['canonical_url'], 'stage_1_product_url');
            $stage_data['product_url_scan_done'] = true;
            CP_KR_DB::update_job($job_id, ['stage_data' => wp_json_encode($stage_data)]);
        }

        $seeds = (array)$stage_data['product_seed_phrases'];
        $offset = (int)($stage_data['product_seed_offset'] ?? 0);
        if ($offset >= count($seeds)) return 'stage_2_competitors';

        $batch_size = max(1, min(10, (int)($settings['max_seeds_per_batch'] ?? 10)));
        $batch = array_slice($seeds, $offset, $batch_size);
        $data = CP_KR_Api_Client::generate_keyword_ideas('keyword_url', [
            'url' => $profile['canonical_url'],
            'keywords' => $batch,
        ], $opts);
        if (is_wp_error($data)) return $data;

        self::store_ideas($job_id, $data, 'product_fixed_seed', implode(' | ', $batch), 'stage_1_product_url');
        $stage_data['product_seed_offset'] = $offset + count($batch);
        CP_KR_DB::update_job($job_id, ['stage_data' => wp_json_encode($stage_data)]);

        return $stage_data['product_seed_offset'] >= count($seeds)
            ? 'stage_2_competitors'
            : 'stage_1_product_url';
    }

    private static function build_product_seed_phrases($profile) {
        $seeds = [trim((string)($profile['title'] ?? ''))];
        $seeds = array_merge($seeds, (array)($profile['approved_synonyms'] ?? []));
        $seeds = array_merge($seeds, (array)($profile['fixed_seed_phrases'] ?? []));
        foreach (($profile['variations'] ?? []) as $variation) {
            if (!empty($variation['seed_phrase'])) {
                $seeds[] = trim((string)$variation['seed_phrase']);
            }
            $name = trim((string)($variation['name'] ?? ''));
            if ('' !== $name && 0 !== strcasecmp($name, (string)($profile['title'] ?? ''))) {
                $title = trim((string)($profile['title'] ?? ''));
                $seeds[] = false !== stripos($name, $title) ? $name : trim($title . ' ' . $name);
            }
        }

        return array_values(array_unique(array_filter($seeds)));
    }

    private static function stage_2($job_id, $profile, $settings) {
        $job = CP_KR_DB::get_job($job_id);
        $urls = json_decode($job->competitor_urls, true) ?: [];
        $opts = self::api_opts($settings);

        foreach ($urls as $url) {
            if (!CP_KR_Competitor_Fetcher::is_safe_url($url)) {
                CP_KR_DB::insert_source([
                    'job_id' => $job_id, 'source_type' => 'competitor_url', 'source_value' => $url,
                    'fetch_status' => 'rejected', 'fetch_error' => 'URL failed SSRF validation',
                    'extracted_terms' => wp_json_encode([]),
                ]);
                continue;
            }
            $page = CP_KR_Competitor_Fetcher::fetch($url);
            if (is_wp_error($page)) {
                CP_KR_DB::insert_source([
                    'job_id' => $job_id, 'source_type' => 'competitor_url', 'source_value' => $url,
                    'fetch_status' => 'failed', 'fetch_error' => $page->get_error_message(),
                    'extracted_terms' => wp_json_encode([]),
                ]);
                continue;
            }
            CP_KR_DB::insert_source([
                'job_id' => $job_id, 'source_type' => 'competitor_url', 'source_value' => $url,
                'fetch_status' => 'ok', 'fetch_error' => '',
                'extracted_terms' => wp_json_encode($page['extracted_terms'] ?? []),
            ]);
            $ideas = CP_KR_Api_Client::generate_keyword_ideas('url', ['url' => $url], $opts);
            if (!is_wp_error($ideas)) {
                self::store_ideas($job_id, $ideas, 'competitor_url', $url, 'stage_2_competitors');
            }
        }
        return 'stage_3_filter';
    }

    private static function stage_3($job_id, $profile, $settings, $source_stages = null) {
        $results = CP_KR_DB::get_results($job_id);
        if (null !== $source_stages) {
            $results = array_filter($results, static function ($result) use ($source_stages) {
                return in_array($result->stage, $source_stages, true);
            });
        }
        $existing_keywords = null !== $source_stages ? CP_KR_DB::get_reviewed_keyword_keys($job_id) : [];
        $grouped = [];

        foreach ($results as $r) {
            $key = $r->normalized_text;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'keyword_text' => $r->keyword_text,
                    'normalized_text' => $key,
                    'sources' => [], 'stages' => [], 'variation_ids' => [],
                    'avg' => null, 'comp' => null, 'comp_idx' => null,
                    'low' => null, 'high' => null,
                ];
            }
            $grouped[$key]['sources'][] = ['type'=>$r->source_type,'value'=>$r->source_value,'stage'=>$r->stage];
            if (!in_array($r->stage, $grouped[$key]['stages'], true)) $grouped[$key]['stages'][] = $r->stage;
            if ($r->variation_id) $grouped[$key]['variation_ids'][] = (int)$r->variation_id;

            $new_vol = $r->avg_monthly_searches !== null ? (int)$r->avg_monthly_searches : null;
            if ($new_vol !== null && ($grouped[$key]['avg'] === null || $new_vol > $grouped[$key]['avg'])) {
                $grouped[$key]['avg'] = $new_vol;
                $grouped[$key]['comp'] = $r->competition;
                $grouped[$key]['comp_idx'] = $r->competition_index !== null ? (int)$r->competition_index : null;
                $grouped[$key]['low'] = $r->low_top_of_page_bid_micros !== null ? (int)$r->low_top_of_page_bid_micros : null;
                $grouped[$key]['high'] = $r->high_top_of_page_bid_micros !== null ? (int)$r->high_top_of_page_bid_micros : null;
            }
        }

        $min_vol = (int)($settings['min_volume'] ?? 30);
        $max_bid = (int)($settings['max_bid_micros'] ?? 3000000);

        foreach ($grouped as $g) {
            if (isset($existing_keywords[$g['normalized_text']])) {
                continue;
            }

            $brand = CP_KR_Brand_Exclusion::check($g['keyword_text']);
            if ($brand['excluded']) {
                CP_KR_DB::insert_rejected([
                    'job_id'=>$job_id,'keyword_text'=>$g['keyword_text'],'normalized_text'=>$g['normalized_text'],
                    'rejection_code'=>'brand_exclusion','rejection_reason'=>$brand['reason'],
                    'detection_method'=>$brand['method'],'suspected_brand'=>$brand['brand'],
                    'source_stage'=>implode(',', $g['stages']),
                ]);
                continue;
            }

            $rel = CP_KR_Relevance::evaluate($g['keyword_text'], $profile);
            if ('irrelevant' === $rel['rating']) {
                CP_KR_DB::insert_rejected([
                    'job_id'=>$job_id,'keyword_text'=>$g['keyword_text'],'normalized_text'=>$g['normalized_text'],
                    'rejection_code'=>'product_name_mismatch','rejection_reason'=>$rel['notes'],
                    'detection_method'=>'product_name_match','source_stage'=>implode(',', $g['stages']),
                ]);
                continue;
            }

            $metric_flags = [];
            if ($g['avg'] === null) {
                $metric_flags[] = 'No search-volume data returned';
            } elseif ($g['avg'] < $min_vol) {
                $metric_flags[] = 'Low volume: ' . $g['avg'] . ' searches/month (configured minimum: ' . $min_vol . ')';
            }
            if ($g['high'] === null) {
                $metric_flags[] = 'No high-range bid data returned';
            } elseif ($g['high'] > $max_bid) {
                $metric_flags[] = 'High bid exceeds configured maximum of £' . number_format($max_bid / 1000000, 2);
            }

            CP_KR_DB::insert_candidate([
                'job_id' => $job_id,
                'keyword_text' => $g['keyword_text'],
                'normalized_text' => $g['normalized_text'],
                'sources' => wp_json_encode($g['sources']),
                'stages' => wp_json_encode($g['stages']),
                'variation_ids' => wp_json_encode(array_values(array_unique($g['variation_ids']))),
                'avg_monthly_searches' => $g['avg'],
                'competition' => $g['comp'],
                'competition_index' => $g['comp_idx'],
                'low_top_of_page_bid_micros' => $g['low'],
                'high_top_of_page_bid_micros' => $g['high'],
                'relevance_rating' => $rel['rating'],
                'relevance_notes' => $rel['notes'] . (empty($metric_flags) ? '' : ' Metrics: ' . implode('; ', $metric_flags) . '.'),
                'commercial_intent' => $rel['commercial_intent'],
                'review_status' => empty($metric_flags) ? 'pending_review' : 'needs_metrics',
            ]);
        }
        return 'stage_4_top_seeds';
    }

    private static function stage_4($job_id, $profile, $settings) {
        $candidates = CP_KR_DB::get_candidates($job_id, ['review_status' => 'pending_review', 'limit' => 5000]);
        $scored = CP_KR_Scoring::rank($candidates, $profile, $settings);
        $top = array_slice($scored, 0, 10);

        foreach ($scored as $s) {
            CP_KR_DB::update_candidate($s['candidate']->id, [
                'ranking_score' => $s['score'],
                'score_factors' => wp_json_encode($s['factors']),
            ]);
        }

        $seeds = array_map(fn($s) => $s['candidate']->keyword_text, $top);
        CP_KR_DB::update_job($job_id, ['stage_data' => wp_json_encode(['top_seeds' => $seeds])]);
        return 'stage_5_expansion';
    }

    private static function stage_5($job_id, $profile, $settings) {
        $job = CP_KR_DB::get_job($job_id);
        $data = json_decode($job->stage_data, true) ?: [];
        $seeds = $data['top_seeds'] ?? [];
        if (empty($seeds)) return 'stage_6_variations';

        $opts = self::api_opts($settings);
        $ideas = CP_KR_Api_Client::generate_keyword_ideas('keyword', ['keywords' => $seeds], $opts);
        if (!is_wp_error($ideas)) {
            self::store_ideas($job_id, $ideas, 'keyword_seed', implode(',', $seeds), 'stage_5_expansion');
        }
        return 'stage_6_variations';
    }

    private static function stage_6($job_id, $profile, $settings) {
        $opts = self::api_opts($settings);
        $batch_size = max(1, min(10, (int)($settings['max_seeds_per_batch'] ?? 10)));
        $product_name = $profile['title'];

        $variation_seeds = [];
        foreach ($profile['variations'] as $variation) {
            $seeds = self::build_variation_seeds($product_name, $variation);
            if (empty($seeds)) continue;
            $variation_seeds = array_merge($variation_seeds, $seeds);

            CP_KR_DB::insert_variation_seed([
                'job_id' => $job_id,
                'variation_id' => $variation['id'],
                'variation_attributes' => wp_json_encode($variation['attributes']),
                'seed_phrases' => wp_json_encode($seeds),
                'status' => 'completed',
            ]);
        }

        $all_seeds = array_values(array_unique(array_merge($variation_seeds, self::build_size_seeds($profile))));
        foreach (array_chunk($all_seeds, $batch_size) as $batch) {
            $ideas = CP_KR_Api_Client::generate_keyword_ideas('keyword', ['keywords' => $batch], $opts);
            if (!is_wp_error($ideas)) {
                self::store_ideas($job_id, $ideas, 'variation_seed', implode('|', $batch), 'stage_6_variations');
            }
        }
        return 'stage_7_consolidate';
    }

    private static function build_size_seeds($profile) {
        $anchors = array_merge(
            [trim((string)($profile['title'] ?? ''))],
            (array)($profile['approved_synonyms'] ?? [])
        );
        $seeds = [];
        foreach ((array)($profile['size_terms'] ?? []) as $size) {
            foreach ($anchors as $anchor) {
                $anchor = trim((string)$anchor);
                $size = trim((string)$size);
                if ('' !== $anchor && '' !== $size) $seeds[] = $anchor . ' ' . $size;
            }
        }

        return array_slice(array_values(array_unique($seeds)), 0, 100);
    }

    private static function stage_7($job_id, $profile, $settings) {
        self::stage_3($job_id, $profile, $settings, ['stage_5_expansion', 'stage_6_variations']);

        $candidates = CP_KR_DB::get_candidates($job_id, ['limit' => 5000]);
        $scored = CP_KR_Scoring::rank($candidates, $profile, $settings);
        foreach ($scored as $item) {
            CP_KR_DB::update_candidate($item['candidate']->id, [
                'ranking_score' => $item['score'],
                'score_factors' => wp_json_encode($item['factors']),
            ]);
        }

        return null;
    }

    private static function build_variation_seeds($product_name, $variation) {
        if (!empty($variation['seed_phrase'])) {
            return [trim((string)$variation['seed_phrase'])];
        }

        $name = trim((string)($variation['name'] ?? ''));
        if ('' !== $name && 0 !== strcasecmp($name, $product_name)) {
            return [false !== stripos($name, $product_name) ? $name : trim($product_name . ' ' . $name)];
        }

        $values = [];
        foreach ((array)($variation['attributes'] ?? []) as $key => $value) {
            if (self::is_size_attribute($key)) {
                $values[] = trim((string)$value);
            }
        }
        $values = array_values(array_filter($values));
        if (empty($values)) return [];

        return [trim($product_name . ' ' . implode(' ', $values))];
    }

    private static function is_size_attribute($name) {
        return (bool)preg_match('/size|dimension|width|length|height|depth|diameter|gauge|micron|thickness|quantity|pack/i', (string)$name);
    }

    private static function api_opts($settings) {
        return [
            'language' => $settings['language'] ?? CP_KR_DEFAULT_LANGUAGE,
            'geoTargets' => [$settings['geo_target'] ?? CP_KR_DEFAULT_GEO_TARGET],
            'network' => $settings['network'] ?? CP_KR_DEFAULT_NETWORK,
            'includeAdult' => (bool)($settings['include_adult'] ?? false),
        ];
    }
}