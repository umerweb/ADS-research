<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Activator {

    public static function activate() {
        self::create_tables();
        add_option( 'cp_kr_db_version', CP_KR_VERSION );
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $jobs_table = $wpdb->prefix . 'cp_kr_jobs';
        $sql = "CREATE TABLE {$jobs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            status varchar(20) NOT NULL DEFAULT 'pending',
            current_stage varchar(50) NOT NULL DEFAULT '',
            stage_data longtext NOT NULL,
            product_id bigint(20) unsigned NOT NULL DEFAULT 0,
            competitor_urls text NOT NULL,
            seeds_processed int(11) NOT NULL DEFAULT 0,
            total_seeds int(11) NOT NULL DEFAULT 0,
            api_requests int(11) NOT NULL DEFAULT 0,
            error_log longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY product_id (product_id)
        ) {$charset_collate};";
        dbDelta( $sql );

        $results_table = $wpdb->prefix . 'cp_kr_results';
        $sql = "CREATE TABLE {$results_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            keyword_text varchar(500) NOT NULL,
            normalized_text varchar(500) NOT NULL,
            source_type varchar(50) NOT NULL,
            source_value varchar(500) NOT NULL,
            stage varchar(50) NOT NULL,
            variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
            avg_monthly_searches bigint(20) DEFAULT NULL,
            competition varchar(20) DEFAULT NULL,
            competition_index int(11) DEFAULT NULL,
            low_top_of_page_bid_micros bigint(20) DEFAULT NULL,
            high_top_of_page_bid_micros bigint(20) DEFAULT NULL,
            close_variants longtext NOT NULL,
            monthly_search_volumes longtext NOT NULL,
            api_version varchar(10) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY normalized_text (normalized_text),
            KEY variation_id (variation_id)
        ) {$charset_collate};";
        dbDelta( $sql );

        $candidates_table = $wpdb->prefix . 'cp_kr_candidates';
        $sql = "CREATE TABLE {$candidates_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            keyword_text varchar(500) NOT NULL,
            normalized_text varchar(500) NOT NULL,
            sources longtext NOT NULL,
            stages longtext NOT NULL,
            variation_ids longtext NOT NULL,
            avg_monthly_searches bigint(20) DEFAULT NULL,
            competition varchar(20) DEFAULT NULL,
            competition_index int(11) DEFAULT NULL,
            low_top_of_page_bid_micros bigint(20) DEFAULT NULL,
            high_top_of_page_bid_micros bigint(20) DEFAULT NULL,
            relevance_rating varchar(20) NOT NULL DEFAULT 'review',
            relevance_notes text NOT NULL,
            commercial_intent varchar(20) NOT NULL DEFAULT 'unknown',
            ranking_score decimal(10,4) DEFAULT NULL,
            score_factors longtext NOT NULL,
            review_status varchar(30) NOT NULL DEFAULT 'pending_review',
            rejection_reason varchar(100) NOT NULL DEFAULT '',
            notes text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY normalized_text (normalized_text),
            KEY review_status (review_status)
        ) {$charset_collate};";
        dbDelta( $sql );

        $rejected_table = $wpdb->prefix . 'cp_kr_rejected';
        $sql = "CREATE TABLE {$rejected_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            keyword_text varchar(500) NOT NULL,
            normalized_text varchar(500) NOT NULL,
            rejection_code varchar(50) NOT NULL,
            rejection_reason text NOT NULL,
            detection_method varchar(50) NOT NULL,
            suspected_brand varchar(200) NOT NULL DEFAULT '',
            source_stage varchar(50) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY rejection_code (rejection_code)
        ) {$charset_collate};";
        dbDelta( $sql );

        $sources_table = $wpdb->prefix . 'cp_kr_sources';
        $sql = "CREATE TABLE {$sources_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            source_type varchar(30) NOT NULL,
            source_value varchar(1000) NOT NULL,
            extracted_terms longtext NOT NULL,
            fetch_status varchar(20) NOT NULL DEFAULT 'pending',
            fetch_error text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id)
        ) {$charset_collate};";
        dbDelta( $sql );

        $variation_seeds_table = $wpdb->prefix . 'cp_kr_variation_seeds';
        $sql = "CREATE TABLE {$variation_seeds_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            variation_id bigint(20) unsigned NOT NULL,
            variation_attributes longtext NOT NULL,
            seed_phrases longtext NOT NULL,
            batch_number int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY variation_id (variation_id)
        ) {$charset_collate};";
        dbDelta( $sql );
    }
}