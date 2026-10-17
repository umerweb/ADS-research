<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Relevance {
    private const BUYING_MODIFIERS = [
        'buy', 'buying', 'price', 'prices', 'cheap', 'wholesale', 'bulk', 'order', 'orders',
        'supplier', 'suppliers', 'uk', 'online', 'sale', 'sales', 'discount', 'discounts',
        'delivery', 'custom', 'printed', 'personalised', 'personalized', 'near', 'me',
    ];

    public static function evaluate($keyword, $profile) {
        $keyword_tokens = self::tokens($keyword);
        $content_tokens = array_values(array_diff($keyword_tokens, self::BUYING_MODIFIERS));
        $phrases = self::identity_phrases($profile);
        $best_coverage = 0.0;
        $best_precision = 0.0;
        $best_matches = 0;
        $best_required = 1;

        foreach ($phrases as $phrase) {
            $phrase_tokens = self::tokens($phrase);
            if (!$phrase_tokens) continue;

            $matches = count(array_intersect($phrase_tokens, $content_tokens));
            $coverage = $matches / count($phrase_tokens);
            $precision = count($content_tokens) > 0 ? $matches / count($content_tokens) : 0.0;

            if (min($coverage, $precision) > min($best_coverage, $best_precision)) {
                $best_coverage = $coverage;
                $best_precision = $precision;
                $best_matches = $matches;
                $best_required = count($phrase_tokens) > 1 ? 2 : 1;
            }
        }

        $relevant = $best_matches >= $best_required
            && $best_coverage >= 0.5
            && $best_precision >= 0.6;

        if (!$relevant) {
            $rating = 'irrelevant';
            $notes = 'Keyword does not sufficiently match the product name or a variation name.';
        } elseif ($best_coverage >= 0.75 && $best_precision >= 0.75) {
            $rating = 'high';
            $notes = 'Strong match to the product name or a variation name.';
        } else {
            $rating = 'medium';
            $notes = 'Partial product-name match; verify the extra terms are relevant.';
        }

        return [
            'rating' => $rating,
            'notes' => $notes,
            'commercial_intent' => self::intent(mb_strtolower($keyword)),
            'match_ratio' => min($best_coverage, $best_precision),
        ];
    }

    private static function identity_phrases($profile) {
        $phrases = array_merge(
            [trim((string)($profile['title'] ?? ''))],
            array_map('trim', (array)($profile['approved_synonyms'] ?? [])),
            array_map('trim', (array)($profile['fixed_seed_phrases'] ?? []))
        );
        foreach (($profile['variations'] ?? []) as $variation) {
            if (!empty($variation['name'])) {
                $name = trim((string)$variation['name']);
                $title = trim((string)($profile['title'] ?? ''));
                $phrases[] = '' !== $title && false === stripos($name, $title)
                    ? trim($title . ' ' . $name)
                    : $name;
            }
        }

        return array_values(array_unique(array_filter($phrases)));
    }

    private static function tokens($text) {
        $text = mb_strtolower((string)$text);
        $tokens = preg_split('/\s+/', preg_replace('/[^a-z0-9\s]/', ' ', $text));
        $tokens = array_filter($tokens, static function ($token) {
            return strlen($token) >= 3;
        });

        $tokens = array_map(static function ($token) {
            if (strlen($token) > 4 && str_ends_with($token, 'ies')) {
                return substr($token, 0, -3) . 'y';
            }
            if (strlen($token) > 4 && preg_match('/(x|z|ch|sh|ss)es$/', $token)) {
                return substr($token, 0, -2);
            }
            if (strlen($token) > 4 && str_ends_with($token, 's') && !str_ends_with($token, 'ss')) {
                return substr($token, 0, -1);
            }
            return $token;
        }, $tokens);

        return array_values(array_unique($tokens));
    }

    private static function intent($keyword) {
        $commercial = ['buy', 'price', 'cheap', 'wholesale', 'bulk', 'order', 'supplier', 'uk', 'discount', 'sale', 'custom', 'printed', 'personalised', 'personalized'];
        $transactional = ['near me', 'same day', 'next day', 'fast', 'delivery'];
        foreach ($commercial as $term) {
            if (false !== strpos($keyword, $term)) return 'commercial';
        }
        foreach ($transactional as $term) {
            if (false !== strpos($keyword, $term)) return 'transactional';
        }
        return 'informational';
    }
}