<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Relevance {
    public static function evaluate($keyword, $profile) {
        $kw = mb_strtolower($keyword);
        $kw_tokens = preg_split('/\s+/', preg_replace('/[^a-z0-9\s]/', ' ', $kw));
        $kw_tokens = array_values(array_filter($kw_tokens, fn($t) => strlen($t) >= 3));

        $p_tokens = self::profile_tokens($profile);
        $matched = array_intersect($kw_tokens, $p_tokens);
        $match_ratio = count($kw_tokens) > 0 ? count($matched) / count($kw_tokens) : 0;

        $notes = [];
        $rating = 'review';

        if ($match_ratio >= 0.6) {
            $rating = 'high';
            $notes[] = 'Strong semantic match to product profile.';
        } elseif ($match_ratio >= 0.3) {
            $rating = 'medium';
            $notes[] = 'Partial match; contextual assessment recommended.';
        } elseif (self::has_category_match($kw, $profile)) {
            $rating = 'medium';
            $notes[] = 'Category-level match only.';
        } else {
            $rating = 'review';
            $notes[] = 'Weak or no overlap with product data.';
        }

        if (self::size_conflict($kw, $profile)) {
            $rating = 'irrelevant';
            $notes[] = 'Size or dimension conflicts with confirmed product specs.';
        }

        return [
            'rating' => $rating,
            'notes' => implode(' ', $notes),
            'commercial_intent' => self::intent($kw),
            'match_ratio' => $match_ratio,
        ];
    }

    private static function profile_tokens($p) {
        $parts = [
            $p['title'] ?? '',
            $p['short_desc'] ?? '',
            $p['full_desc'] ?? '',
            implode(' ', $p['categories'] ?? []),
            implode(' ', $p['tags'] ?? []),
        ];
        if (!empty($p['attributes']) && is_array($p['attributes'])) {
            foreach ($p['attributes'] as $v) {
                $parts[] = is_array($v) ? implode(' ', $v) : (string)$v;
            }
        }
        $text = strtolower(implode(' ', array_filter($parts)));
        $t = preg_split('/\s+/', preg_replace('/[^a-z0-9\s]/', ' ', $text));
        return array_unique(array_filter($t, fn($x) => strlen($x) >= 3));
    }

    private static function has_category_match($kw, $p) {
        foreach (($p['categories'] ?? []) as $c) {
            $parts = preg_split('/\s+/', strtolower($c));
            foreach ($parts as $part) {
                if (strlen($part) >= 4 && false !== strpos($kw, $part)) return true;
            }
        }
        return false;
    }

    private static function size_conflict($kw, $p) {
        if (!preg_match('/(\d+)\s*(mm|cm|inch|in)/i', $kw, $m)) return false;
        $val = (int)$m[1];
        $unit = strtolower($m[2]);
        $profile_text = strtolower(
            ($p['title'] ?? '') . ' ' . ($p['short_desc'] ?? '') . ' ' . ($p['full_desc'] ?? '')
        );
        if (preg_match_all('/(\d+)\s*' . preg_quote($unit, '/') . '\b/i', $profile_text, $matches)) {
            foreach ($matches[1] as $psize) {
                if ((int)$psize === $val) return false;
            }
        }
        return false;
    }

    private static function intent($kw) {
        $commercial = ['buy','price','cheap','wholesale','bulk','order','supplier','uk','discount','sale','custom','printed','personalised'];
        $transactional = ['near me','same day','next day','fast','delivery'];
        foreach ($commercial as $c) if (false !== strpos($kw, $c)) return 'commercial';
        foreach ($transactional as $t) if (false !== strpos($kw, $t)) return 'transactional';
        return 'informational';
    }
}