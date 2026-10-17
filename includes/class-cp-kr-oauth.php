<?php
if (!defined('ABSPATH')) exit;

class CP_KR_OAuth {
    const TRANSIENT = 'cp_kr_access_token';

    public static function get_access_token() {
        $cached = get_transient(self::TRANSIENT);
        if ($cached) return $cached;

        if (!defined('CP_GOOGLE_ADS_CLIENT_ID') || !defined('CP_GOOGLE_ADS_CLIENT_SECRET') || !defined('CP_GOOGLE_ADS_REFRESH_TOKEN')) {
            return new WP_Error('cp_kr_missing_creds', 'Google Ads OAuth constants missing from wp-config.php.');
        }

        $resp = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 20,
            'body' => [
                'client_id'     => CP_GOOGLE_ADS_CLIENT_ID,
                'client_secret' => CP_GOOGLE_ADS_CLIENT_SECRET,
                'refresh_token' => CP_GOOGLE_ADS_REFRESH_TOKEN,
                'grant_type'    => 'refresh_token',
            ],
        ]);

        if (is_wp_error($resp)) {
            CP_KR_Logger::log('OAuth transport: ' . $resp->get_error_message());
            return new WP_Error('cp_kr_oauth_failed', 'OAuth request failed.');
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);

        if (200 !== $code || empty($body['access_token'])) {
            $desc = $body['error_description'] ?? 'Unknown';
            CP_KR_Logger::log('OAuth error: ' . $desc);
            return new WP_Error('cp_kr_oauth_failed', 'OAuth token could not be obtained: ' . $desc);
        }

        $token   = $body['access_token'];
        $expires = max(60, (int)($body['expires_in'] ?? 3600) - 300);
        set_transient(self::TRANSIENT, $token, $expires);
        return $token;
    }

    /**
     * Validate the OAuth token by calling customers:listAccessibleCustomers.
     * Returns the decoded response body on success or WP_Error on failure.
     */
    public static function validate() {
        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }

        $resp = wp_remote_get(
            'https://googleads.googleapis.com/' . CP_KR_API_VERSION . '/customers:listAccessibleCustomers',
            [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]
        );

        if (is_wp_error($resp)) {
            return new WP_Error('cp_kr_validate_failed', $resp->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);

        if (200 !== $code) {
            $msg = 'OAuth token lacks Google Ads API access. HTTP ' . $code;
            if (isset($body['error']['message'])) {
                $msg .= ': ' . $body['error']['message'];
            }
            return new WP_Error('cp_kr_scope_invalid', $msg);
        }

        return $body;
    }
}