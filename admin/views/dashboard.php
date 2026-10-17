<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap cp-kr-wrap">
    <h1>Cloud Packaging — Keyword Research</h1>

    <div class="cp-kr-panel">
        <h2>Start a New Research Job</h2>
        <table class="form-table">
            <tr>
                <th><label for="cp-kr-product-search">Product</label></th>
                <td>
                    <input type="text" id="cp-kr-product-search" class="regular-text" placeholder="Search products…">
                    <input type="hidden" id="cp-kr-product-id">
                    <div id="cp-kr-product-results"></div>
                    <p class="description">Search WooCommerce products by name or SKU.</p>
                </td>
            </tr>
            <tr>
                <th><label>Competitor URLs (max 3)</label></th>
                <td>
                    <input type="url" id="cp-kr-url-1" class="regular-text" placeholder="https://competitor1.com/product">
                    <input type="url" id="cp-kr-url-2" class="regular-text" placeholder="https://competitor2.com/product" style="margin-top:6px">
                    <input type="url" id="cp-kr-url-3" class="regular-text" placeholder="https://competitor3.com/product" style="margin-top:6px">
                </td>
            </tr>
        </table>
        <p>
            <button type="button" class="button button-primary" id="cp-kr-start">Start Research Job</button>
            <button type="button" class="button" id="cp-kr-test">Test Credentials</button>
        </p>
        <div id="cp-kr-start-msg"></div>
        <div id="cp-kr-test-result"></div>
    </div>

    <div class="cp-kr-panel" id="cp-kr-progress" style="display:none">
        <h2>Job Progress</h2>
        <p><strong>Stage:</strong> <span id="cp-kr-stage">—</span></p>
        <p><strong>Status:</strong> <span id="cp-kr-status">—</span></p>
        <p><strong>Candidates:</strong> <span id="cp-kr-cands">0</span> |
           <strong>Rejected:</strong> <span id="cp-kr-rejs">0</span> |
           <strong>API Calls:</strong> <span id="cp-kr-api">0</span></p>
        <p><button type="button" class="button" id="cp-kr-kick">Run Next Stage Now</button></p>
        <div id="cp-kr-error"></div>
    </div>

    <div class="cp-kr-panel">
        <h2>Recent Jobs</h2>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Product ID</th><th>Status</th><th>Stage</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach (CP_KR_DB::get_jobs(25) as $j): ?>
                <tr>
                    <td><?php echo (int)$j->id; ?></td>
                    <td><?php echo (int)$j->product_id; ?></td>
                    <td><?php echo esc_html($j->status); ?></td>
                    <td><?php echo esc_html($j->current_stage); ?></td>
                    <td><?php echo esc_html($j->created_at); ?></td>
                    <td>
                        <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=cp-kr&job_id=' . $j->id)); ?>">View</a>
                        <a class="button button-small cp-kr-kick-row" data-id="<?php echo (int)$j->id; ?>" href="#">Run Stage</a>
                        <a class="button button-small" href="#" data-cp-kr-export="<?php echo (int)$j->id; ?>" data-type="approved">CSV Approved</a>
                        <a class="button button-small" href="#" data-cp-kr-export="<?php echo (int)$j->id; ?>" data-type="rejected">CSV Rejected</a>
                        <a class="button button-small" href="#" data-cp-kr-export="<?php echo (int)$j->id; ?>" data-type="complete">CSV Everything</a>
                        <button type="button" class="button button-small cp-kr-delete-job" data-id="<?php echo (int)$j->id; ?>">Delete Job &amp; Data</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>