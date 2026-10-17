<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Cache {

    const PREFIX = 'cp_kr_api_';
    const EXPIRY = 6 * HOUR_IN_SECONDS;

    public static function get( $key ) {
        return get_transient( self::PREFIX . md5( $key ) );
    }

    public static function set( $key, $value, $expiry = null ) {
        set_transient( self::PREFIX . md5( $key ), $value, $expiry ?? self::EXPIRY );
    }

    public static function delete( $key ) {
        delete_transient( self::PREFIX . md5( $key ) );
    }

    public static function flush() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cp_kr_api_%' OR option_name LIKE '_transient_timeout_cp_kr_api_%'" );
    }
}