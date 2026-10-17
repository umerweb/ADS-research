<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Logger {

    public static function log( $message, $level = 'info' ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }
        $safe_message = self::redact_secrets( $message );
        error_log( '[CP Keyword Research] ' . $safe_message );
    }

    public static function redact_secrets( $message ) {
        $patterns = array(
            '/Bearer\s+[A-Za-z0-9\-_\.]+/i',
            '/client_secret=[^&\s]+/i',
            '/refresh_token=[^&\s]+/i',
            '/access_token=[^&\s]+/i',
            '/developer-token:\s*[A-Za-z0-9\-_]+/i',
        );
        foreach ( $patterns as $pattern ) {
            $message = preg_replace( $pattern, '[REDACTED]', $message );
        }
        return $message;
    }
}