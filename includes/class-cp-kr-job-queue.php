<?php
if (!defined('ABSPATH')) exit;

class CP_KR_Job_Queue {
    /**
     * Kept for backwards compatibility; stages now run inline via AJAX.
     */
    public static function schedule($job_id) {
        // No-op. See CP_KR_Ajax::kick_job() and CP_KR_Ajax::start_job().
    }

    public static function init() {
        // nothing
    }
}
add_action('init', ['CP_KR_Job_Queue', 'init']);