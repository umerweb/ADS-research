<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Brand_Exclusion {
    public static function check($keyword) {
        $kw = mb_strtolower($keyword);
        $brands = get_option('cp_kr_brand_list', []);
        foreach ((array)$brands as $brand) {
            $brand = strtolower(trim($brand));
            if ('' === $brand) continue;
            if (preg_match('/\b' . preg_quote($brand, '/') . '\b/i', $kw)) {
                return [
                    'excluded' => true,
                    'brand' => $brand,
                    'method' => 'list_match',
                    'reason' => 'Matches brand exclusion list: ' . $brand,
                ];
            }
        }
        return ['excluded' => false, 'brand' => '', 'method' => '', 'reason' => ''];
    }
}
