<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CP_KR_Deactivator {
    public static function deactivate() {
        wp_clear_scheduled_hook( 'cp_kr_process_job' );
    }
}