<?php
if (!defined('ABSPATH')) exit;
$job = CP_KR_DB::get_job($job_id);
if (!$job) { echo '<div class="wrap"><p>Job not found.</p></div>'; return; }
$candidates = CP_KR_DB::get_candidates($job_id, ['limit' => 500]);
$rejected = CP_KR_DB::get_rejected($job_id);
$sources = CP_KR_DB::get_sources($job_id);
?>
<div class="wrap cp-kr-wrap">
    <h1>Job #<?php echo (int)$job->id; ?> — Product #<?php echo (int)$job->product_id; ?></h1>
    <p><a href="<?php echo esc_url(admin_url('admin.php?page=cp-kr')); ?>">← Back to jobs</a></p>

    <div class="cp-kr-panel">
        <p>
            <strong>Status:</strong> <?php echo esc_html($job->status); ?> |
            <strong>Stage:</strong> <?php echo esc_html($job->current_stage); ?> |
            <strong>API calls:</strong> <?php echo (int)$job->api_requests; ?>
        </p>
        <?php if ($job->error_log): ?>
            <p class="cp-kr-error"><?php echo esc_html($job->error_log); ?></p>
        <?php endif; ?>
        <p>
            <a class="button" href="#" data-cp-kr-export="<?php echo (int)$job->id; ?>" data-type="approved">Export Approved</a>
            <a class="button" href="#" data-cp-kr-export="<?php echo (int)$job->id; ?>" data-type="all">Export All Candidates</a>
            <a class="button" href="#" data-cp-kr-export="<?php echo (int)$job->id; ?>" data-type="rejected">Export Rejected</a>
            <a class="button" href="#" data-cp-kr-export="<?php echo (int)$job->id; ?>" data-type="sources">Export Sources</a>
            <button type="button" class="button button-secondary cp-kr-delete-job" data-id="<?php echo (int)$job->id; ?>">Delete Job &amp; All Data</button>
        </p>
    </div>

    <div class="cp-kr-panel">
        <h2>Candidates (<?php echo count($candidates); ?>)</h2>
        <table class="widefat striped cp-kr-table">
            <thead><tr>
                <th>Keyword</th><th>Vol</th><th>Low £</th><th>High £</th><th>Comp</th>
                <th>Rel</th><th>Intent</th><th>Score</th><th>Status / Metric Flags</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($candidates as $c):
                $low = $c->low_top_of_page_bid_micros !== null ? number_format($c->low_top_of_page_bid_micros / 1000000, 2) : '—';
                $high = $c->high_top_of_page_bid_micros !== null ? number_format($c->high_top_of_page_bid_micros / 1000000, 2) : '—';
            ?>
                <tr data-id="<?php echo (int)$c->id; ?>">
                    <td><?php echo esc_html($c->keyword_text); ?></td>
                    <td><?php echo $c->avg_monthly_searches !== null ? (int)$c->avg_monthly_searches : '—'; ?></td>
                    <td><?php echo esc_html($low); ?></td>
                    <td><?php echo esc_html($high); ?></td>
                    <td><?php echo esc_html($c->competition ?? '—'); ?></td>
                    <td><?php echo esc_html($c->relevance_rating); ?></td>
                    <td><?php echo esc_html($c->commercial_intent); ?></td>
                    <td><?php echo esc_html(number_format((float)$c->ranking_score, 2)); ?></td>
                    <td>
                        <?php echo esc_html($c->review_status); ?>
                        <?php if (!empty($c->relevance_notes)): ?>
                            <br><small><?php echo esc_html($c->relevance_notes); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small cp-kr-accept" data-id="<?php echo (int)$c->id; ?>">Accept</button>
                        <button class="button button-small cp-kr-reject" data-id="<?php echo (int)$c->id; ?>">Reject</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="cp-kr-panel">
        <h2>Rejected (<?php echo count($rejected); ?>)</h2>
        <table class="widefat striped">
            <thead><tr><th>Keyword</th><th>Code</th><th>Reason</th><th>Method</th></tr></thead>
            <tbody>
            <?php foreach ($rejected as $r): ?>
                <tr>
                    <td><?php echo esc_html($r->keyword_text); ?></td>
                    <td><?php echo esc_html($r->rejection_code); ?></td>
                    <td><?php echo esc_html($r->rejection_reason); ?></td>
                    <td><?php echo esc_html($r->detection_method); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="cp-kr-panel">
        <h2>Sources (<?php echo count($sources); ?>)</h2>
        <table class="widefat striped">
            <thead><tr><th>Type</th><th>Value</th><th>Status</th><th>Error</th></tr></thead>
            <tbody>
            <?php foreach ($sources as $s): ?>
                <tr>
                    <td><?php echo esc_html($s->source_type); ?></td>
                    <td><?php echo esc_html($s->source_value); ?></td>
                    <td><?php echo esc_html($s->fetch_status); ?></td>
                    <td><?php echo esc_html($s->fetch_error); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>