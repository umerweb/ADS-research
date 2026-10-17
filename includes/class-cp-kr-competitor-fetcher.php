<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Competitor_Fetcher {

    public static function fetch( $url ) {
        $url = esc_url_raw( $url );

        if ( ! self::is_safe_url( $url ) ) {
            return new WP_Error( 'cp_kr_unsafe_url', 'The URL is not allowed: ' . $url );
        }

        $cache_key = 'cp_kr_page_' . md5( $url );
        $cached = get_transient( $cache_key );
        if ( $cached ) {
            return $cached;
        }

        $response = wp_remote_get( $url, array(
            'timeout'     => 20,
            'redirection' => 3,
            'user-agent'  => 'CloudPackagingKeywordResearch/1.0',
            'headers'     => array(
                'Accept' => 'text/html,application/xhtml+xml',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'cp_kr_fetch_failed', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error( 'cp_kr_fetch_http', 'HTTP ' . $code . ' for ' . $url );
        }

        $body = wp_remote_retrieve_body( $response );
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        if ( $content_type && false === strpos( $content_type, 'html' ) ) {
            return new WP_Error( 'cp_kr_fetch_type', 'Unsupported content type: ' . $content_type );
        }

        $extracted = self::extract_content( $body );

        set_transient( $cache_key, $extracted, DAY_IN_SECONDS );

        return $extracted;
    }

    public static function is_safe_url( $url ) {
        $parsed = wp_parse_url( $url );
        if ( empty( $parsed['scheme'] ) || ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
            return false;
        }

        $host = $parsed['host'] ?? '';
        if ( '' === $host ) {
            return false;
        }

        if ( 'localhost' === strtolower( $host ) || str_ends_with( $host, '.local' ) ) {
            return false;
        }

        if ( preg_match( '/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.|169\.254\.|0\.)/', $host ) ) {
            return false;
        }

        $ip = gethostbyname( $host );
        if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
            return false;
        }

        return true;
    }

    private static function extract_content( $html ) {
        $doc = new DOMDocument();
        libxml_use_internal_errors( true );
        $doc->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
        libxml_clear_errors();

        $xpath = new DOMXPath( $doc );

        $title = '';
        $title_nodes = $xpath->query( '//title' );
        if ( $title_nodes->length > 0 ) {
            $title = trim( $title_nodes->item( 0 )->textContent );
        }

        $meta_description = '';
        $meta_nodes = $xpath->query( '//meta[@name="description"]/@content' );
        if ( $meta_nodes->length > 0 ) {
            $meta_description = trim( $meta_nodes->item( 0 )->textContent );
        }

        $headings = array();
        foreach ( $xpath->query( '//h1|//h2|//h3' ) as $node ) {
            $text = trim( $node->textContent );
            if ( '' !== $text ) {
                $headings[] = $text;
            }
        }

        $body_text = '';
        $body_nodes = $xpath->query( '//body' );
        if ( $body_nodes->length > 0 ) {
            $body_text = trim( preg_replace( '/\s+/', ' ', $body_nodes->item( 0 )->textContent ) );
        }

        return array(
            'url'              => '',
            'title'            => $title,
            'meta_description' => $meta_description,
            'headings'         => $headings,
            'body_text'        => $body_text,
            'extracted_terms'  => self::extract_terms( $title . ' ' . $meta_description . ' ' . implode( ' ', $headings ) . ' ' . $body_text ),
        );
    }

    private static function extract_terms( $text ) {
        $text = mb_strtolower( $text );
        $text = preg_replace( '/[^a-z0-9\s\-\.]/', ' ', $text );
        $words = preg_split( '/\s+/', $text );
        $stopwords = array( 'the', 'and', 'for', 'with', 'from', 'this', 'that', 'are', 'was', 'were', 'have', 'has', 'had', 'not', 'but', 'what', 'all', 'can', 'will', 'just', 'about', 'into', 'over', 'after', 'before', 'between', 'under', 'above', 'through', 'during', 'without', 'within', 'along', 'across', 'behind', 'beyond', 'plus', 'except', 'until', 'upon', 'among', 'around', 'because', 'been', 'being', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'than', 'too', 'very', 'own', 'same', 'then', 'now', 'here', 'there', 'when', 'where', 'why', 'how' );
        $terms = array();
        foreach ( $words as $word ) {
            $word = trim( $word, '.-' );
            if ( strlen( $word ) < 3 ) {
                continue;
            }
            if ( in_array( $word, $stopwords, true ) ) {
                continue;
            }
            $terms[ $word ] = ( $terms[ $word ] ?? 0 ) + 1;
        }
        arsort( $terms );
        return array_slice( array_keys( $terms ), 0, 200 );
    }
}