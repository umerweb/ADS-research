<?php
if (!defined('ABSPATH')) exit;
$s = get_option('cp_kr_settings', []);
$brands = get_option('cp_kr_brand_list', []);
$product_synonyms = get_option('cp_kr_product_synonyms', []);
$product_synonym_lines = [];
foreach ((array)$product_synonyms as $product_id => $terms) {
    $product_synonym_lines[] = (int)$product_id . ' | ' . implode(', ', (array)$terms);
}
?>
<div class="wrap cp-kr-wrap">
    <h1>Keyword Research Settings</h1>
    <form method="post">
        <?php wp_nonce_field('cp_kr_save_settings'); ?>
        <table class="form-table">
            <tr><th>Minimum avg monthly searches</th>
                <td><input type="number" name="min_volume" value="<?php echo (int)($s['min_volume'] ?? 30); ?>" min="0"></td></tr>
            <tr><th>Max high-range bid (GBP)</th>
                <td><input type="number" step="0.01" name="max_bid_gbp" value="<?php echo number_format(($s['max_bid_micros'] ?? 3000000) / 1000000, 2); ?>"></td></tr>
            <tr><th>Geo target</th>
                <td><input type="text" name="geo_target" class="regular-text" value="<?php echo esc_attr($s['geo_target'] ?? 'geoTargetConstants/2826'); ?>"></td></tr>
            <tr><th>Language</th>
                <td><input type="text" name="language" class="regular-text" value="<?php echo esc_attr($s['language'] ?? 'languageConstants/1000'); ?>"></td></tr>
            <tr><th>Network</th>
                <td>
                    <select name="network">
                        <option value="GOOGLE_SEARCH" <?php selected($s['network'] ?? '', 'GOOGLE_SEARCH'); ?>>Google Search</option>
                        <option value="GOOGLE_SEARCH_AND_PARTNERS" <?php selected($s['network'] ?? '', 'GOOGLE_SEARCH_AND_PARTNERS'); ?>>Search + Partners</option>
                    </select>
                </td></tr>
            <tr><th>Include adult keywords</th>
                <td><label><input type="checkbox" name="include_adult" <?php checked(!empty($s['include_adult'])); ?>> Enable</label></td></tr>
            <tr><th>Max seeds per batch</th>
                <td><input type="number" name="max_seeds_per_batch" value="<?php echo (int)($s['max_seeds_per_batch'] ?? 10); ?>"></td></tr>
            <tr><th>Max API requests per job</th>
                <td><input type="number" name="max_requests_per_job" value="<?php echo (int)($s['max_requests_per_job'] ?? 200); ?>"></td></tr>
            <tr><th>Brand exclusion list (one per line)</th>
                <td><textarea name="brands" rows="10" class="large-text"><?php echo esc_textarea(implode("\n", (array)$brands)); ?></textarea></td></tr>
            <tr><th>Approved product synonyms</th>
                <td>
                    <textarea name="product_synonyms" rows="8" class="large-text" placeholder="123 | bubble mailers, padded envelopes"><?php echo esc_textarea(implode("\n", $product_synonym_lines)); ?></textarea>
                    <p class="description">One product per line: WooCommerce product ID, a vertical bar, then comma-separated confirmed equivalent names. These terms are used as seeds and to validate keyword relevance. Do not enter guessed features or materials.</p>
                </td></tr>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>

    <div class="cp-kr-panel">
        <h2>Credential Test</h2>
        <p><button type="button" class="button" id="cp-kr-test">Test Google Ads Credentials</button></p>
        <div id="cp-kr-test-result"></div>
    </div>

    <div class="cp-kr-panel">
        <h2>Configuration Reference</h2>
        <p>Required constants in <code>wp-config.php</code>:</p>
        <pre>define('CP_GOOGLE_ADS_CLIENT_ID', '...');
define('CP_GOOGLE_ADS_CLIENT_SECRET', '...');
define('CP_GOOGLE_ADS_REFRESH_TOKEN', '...');
define('CP_GOOGLE_ADS_CUSTOMER_ID', '...');</pre>
        <p>Optional:</p>
        <pre>define('CP_GOOGLE_ADS_LOGIN_CUSTOMER_ID', '...'); // only for managed accounts
define('CP_KR_API_VERSION', 'v25');</pre>
    </div>
</div>