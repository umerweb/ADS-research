<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Api_Client {

    private static $last_request_time = 0;

    public static function get_customer_id() {
        if ( ! defined( 'CP_GOOGLE_ADS_CUSTOMER_ID' ) ) {
            return new WP_Error( 'cp_kr_missing_customer', 'CP_GOOGLE_ADS_CUSTOMER_ID is not defined.' );
        }
        return preg_replace( '/\D/', '', CP_GOOGLE_ADS_CUSTOMER_ID );
    }

    private static function throttle() {
        $min_interval = 1 / CP_KR_KEYWORD_PLANNING_QPS;
        $elapsed = microtime( true ) - self::$last_request_time;
        if ( $elapsed < $min_interval ) {
            usleep( (int) ( ( $min_interval - $elapsed ) * 1000000 ) );
        }
        self::$last_request_time = microtime( true );
    }

    public static function request( $endpoint, $body, $retry_count = 0 ) {
        $token = CP_KR_OAuth::get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $customer_id = self::get_customer_id();
        if ( is_wp_error( $customer_id ) ) {
            return $customer_id;
        }

        self::throttle();

        $url = 'https://googleads.googleapis.com/' . CP_KR_API_VERSION . '/customers/' . $customer_id . ':' . $endpoint;

        $headers = array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        );

        if ( defined( 'CP_GOOGLE_ADS_LOGIN_CUSTOMER_ID' ) && CP_GOOGLE_ADS_LOGIN_CUSTOMER_ID ) {
            $login_id = preg_replace( '/\D/', '', CP_GOOGLE_ADS_LOGIN_CUSTOMER_ID );
            if ( $login_id ) {
                $headers['login-customer-id'] = $login_id;
            }
        }

        $response = wp_remote_post( $url, array(
            'timeout' => 60,
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
        ) );

        if ( is_wp_error( $response ) ) {
            if ( $retry_count < 2 ) {
                sleep( 2 ** $retry_count );
                return self::request( $endpoint, $body, $retry_count + 1 );
            }
            return new WP_Error( 'cp_kr_api_transport', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 429 === $code && $retry_count < 3 ) {
            sleep( 5 * ( $retry_count + 1 ) );
            return self::request( $endpoint, $body, $retry_count + 1 );
        }

        if ( 5 === (int) ( $code / 100 ) && $retry_count < 2 ) {
            sleep( 2 ** $retry_count );
            return self::request( $endpoint, $body, $retry_count + 1 );
        }

        if ( 200 !== $code ) {
            $error_msg = 'HTTP ' . $code;
            if ( isset( $response_body['error']['message'] ) ) {
                $error_msg .= ': ' . $response_body['error']['message'];
            }
            if ( isset( $response_body['error']['details'][0]['errors'][0]['message'] ) ) {
                $error_msg .= ' — ' . $response_body['error']['details'][0]['errors'][0]['message'];
            }
            $request_id = isset( $response_body['error']['details'][0]['requestId'] ) ? $response_body['error']['details'][0]['requestId'] : '';
            if ( $request_id ) {
                $error_msg .= ' (request ID: ' . $request_id . ')';
            }
            CP_KR_Logger::log( 'API error: ' . $error_msg );
            return new WP_Error( 'cp_kr_api_error', $error_msg );
        }

        return $response_body;
    }

    public static function generate_keyword_ideas( $seed_type, $seed_data, $options = array() ) {
        if ( in_array( $seed_type, array( 'keyword', 'keyword_url' ), true ) && count( $seed_data['keywords'] ?? array() ) > 10 ) {
            return new WP_Error( 'cp_kr_seed_limit', 'Keyword Planner seed batches may contain no more than 10 keywords.' );
        }

        $body = array(
            'language'             => $options['language'] ?? CP_KR_DEFAULT_LANGUAGE,
            'geoTargetConstants'   => $options['geoTargets'] ?? array( CP_KR_DEFAULT_GEO_TARGET ),
            'keywordPlanNetwork'   => $options['network'] ?? CP_KR_DEFAULT_NETWORK,
            'includeAdultKeywords' => $options['includeAdult'] ?? false,
            'pageSize'             => $options['pageSize'] ?? 1000,
        );

        if ( 'url' === $seed_type ) {
            $body['urlSeed'] = array( 'url' => $seed_data['url'] );
        } elseif ( 'keyword' === $seed_type ) {
            $body['keywordSeed'] = array( 'keywords' => $seed_data['keywords'] );
        } elseif ( 'keyword_url' === $seed_type ) {
            $body['keywordAndUrlSeed'] = array(
                'url'      => $seed_data['url'],
                'keywords' => $seed_data['keywords'],
            );
        }

        return self::request( 'generateKeywordIdeas', $body );
    }

    public static function generate_historical_metrics( $keywords, $options = array() ) {
        $body = array(
            'keywords'             => array_values( $keywords ),
            'language'             => $options['language'] ?? CP_KR_DEFAULT_LANGUAGE,
            'geoTargetConstants'   => $options['geoTargets'] ?? array( CP_KR_DEFAULT_GEO_TARGET ),
            'keywordPlanNetwork'   => $options['network'] ?? CP_KR_DEFAULT_NETWORK,
            'includeAdultKeywords' => $options['includeAdult'] ?? false,
            'includeAverageCpc'    => $options['includeAverageCpc'] ?? false,
        );

        return self::request( 'generateKeywordHistoricalMetrics', $body );
    }
}