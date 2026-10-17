<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Scoring {
    public static function rank($candidates, $profile, $settings) {
        $weights = $settings['weights'] ?? [
            'relevance' => 0.35,
            'volume' => 0.20,
            'bid' => 0.15,
            'intent' => 0.20,
            'specificity' => 0.10,
        ];
        $scored = [];
        foreach ($candidates as $c) {
            $factors = [];

            $rel_score = ['high'=>1.0,'medium'=>0.6,'review'=>0.3,'irrelevant'=>0.0][$c->relevance_rating] ?? 0.3;
            $factors['relevance'] = $rel_score;

            $vol = $c->avg_monthly_searches ?? null;
            $factors['volume'] = $vol === null ? 0.0 : min(1.0, log10(max(1, (int)$vol)) / 4);

            $bid = $c->high_top_of_page_bid_micros ?? null;
            $factors['bid'] = $bid === null ? 0.0 : max(0, 1.0 - ((int)$bid / 3000000));

            $intent_map = ['commercial'=>1.0,'transactional'=>0.9,'informational'=>0.3];
            $factors['intent'] = $intent_map[$c->commercial_intent ?? 'unknown'] ?? 0.3;

            $kw_tokens = preg_split('/\s+/', $c->keyword_text);
            $factors['specificity'] = min(1.0, count($kw_tokens) / 6);

            $total = 0;
            foreach ($weights as $k => $w) $total += ($factors[$k] ?? 0) * (float)$w;

            $scored[] = [
                'candidate' => $c,
                'score' => round($total * 100, 4),
                'factors' => $factors,
            ];
        }
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        return $scored;
    }
}
