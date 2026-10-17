<?php
/**
 * Plugin Name: Cloud Packaging Keyword Research
 * Plugin URI:  https://cloudpackaging.co.uk/
 * Description: Automates Google Ads Keyword Planner research for WooCommerce products. Read-only: never modifies live campaigns, ad groups, ads, bids, or keywords.
 * Version:     1.0.0.6
 * Author:      Cloud Packaging
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 * Text Domain: cp-keyword-research
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CP_KR_VERSION', '1.0.0' );
define( 'CP_KR_PLUGIN_FILE', __FILE__ );
define( 'CP_KR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CP_KR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CP_KR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'CP_KR_API_VERSION' ) ) {
    define( 'CP_KR_API_VERSION', 'v25' );
}

define( 'CP_KR_DEFAULT_GEO_TARGET', 'geoTargetConstants/2826' );
define( 'CP_KR_DEFAULT_LANGUAGE', 'languageConstants/1000' );
define( 'CP_KR_DEFAULT_NETWORK', 'GOOGLE_SEARCH' );
define( 'CP_KR_DEFAULT_MIN_VOLUME', 30 );
define( 'CP_KR_DEFAULT_MAX_BID_MICROS', 3000000 );
define( 'CP_KR_DEFAULT_MAX_SEEDS_PER_BATCH', 10 );
define( 'CP_KR_DEFAULT_MAX_REQUESTS_PER_JOB', 200 );
define( 'CP_KR_KEYWORD_PLANNING_QPS', 1 );

require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-activator.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-deactivator.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-db.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-logger.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-oauth.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-api-client.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-product-profile.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-competitor-fetcher.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-relevance.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-brand-exclusion.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-scoring.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-cache.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-job-queue.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-research-engine.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-exporter.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-admin.php';
require_once CP_KR_PLUGIN_DIR . 'includes/class-cp-kr-ajax.php';

register_activation_hook( __FILE__, array( 'CP_KR_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CP_KR_Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', 'cp_kr_init_plugin' );
function cp_kr_init_plugin() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>Cloud Packaging Keyword Research requires WooCommerce to be active.</p></div>';
        } );
        return;
    }

    CP_KR_Admin::init();
    CP_KR_Ajax::init();
}