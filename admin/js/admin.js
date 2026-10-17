jQuery(function ($) {
    var searchTimer;
    var activeJobId = null;

    // Detect whether we're already on a job detail page (URL has ?job_id=N)
    function getUrlJobId() {
        var m = window.location.search.match(/[?&]job_id=(\d+)/);
        return m ? parseInt(m[1], 10) : null;
    }

    function isOnJobDetailPage(jobId) {
        return getUrlJobId() === jobId;
    }

    // Restore job id from URL query (e.g. after refresh)
    var urlJobId = getUrlJobId();
    if (urlJobId) activeJobId = urlJobId;
    if (activeJobId) $('#cp-kr-progress').data('job-id', activeJobId);

    $('#cp-kr-product-search').on('input', function () {
        clearTimeout(searchTimer);
        var q = $(this).val();
        if (q.length < 2) { $('#cp-kr-product-results').removeClass('active').empty(); return; }
        searchTimer = setTimeout(function () {
            $.post(cpKr.ajax, { action: 'cp_kr_search_products', nonce: cpKr.nonce, q: q }, function (res) {
                if (!res.success) return;
                var $c = $('#cp-kr-product-results').empty().addClass('active');
                if (!res.data.length) { $c.append('<div class="cp-kr-prod">No results</div>'); return; }
                res.data.forEach(function (p) {
                    $c.append($('<div class="cp-kr-prod"></div>')
                        .text(p.title + ' (#' + p.id + ' — ' + p.type + ')')
                        .on('click', function () {
                            $('#cp-kr-product-id').val(p.id);
                            $('#cp-kr-product-search').val(p.title);
                            $c.removeClass('active').empty();
                        }));
                });
            });
        }, 300);
    });

    $('#cp-kr-start').on('click', function () {
        var pid = $('#cp-kr-product-id').val();
        if (!pid) { alert('Select a product first.'); return; }
        var data = { action: 'cp_kr_start_job', nonce: cpKr.nonce, product_id: pid };
        for (var i = 1; i <= 3; i++) {
            var u = $('#cp-kr-url-' + i).val();
            if (u) data['url_' + i] = u;
        }
        var $msg = $('#cp-kr-start-msg').html('<span class="cp-kr-ok">Starting… (this may take up to 30 seconds)</span>');
        $('#cp-kr-start').prop('disabled', true);
        $.post(cpKr.ajax, data, function (res) {
            $('#cp-kr-start').prop('disabled', false);
            if (!res || !res.success) {
                var m = (res && res.data) ? res.data : 'Empty response from server.';
                $msg.html('<span class="cp-kr-err">' + m + '</span>');
                return;
            }
            activeJobId = res.data.job_id;
            $msg.html('<span class="cp-kr-ok">Job #' + activeJobId + ' started.</span>');
            $('#cp-kr-progress').data('job-id', activeJobId).show();
            updateProgressFromResponse(res.data.progress);
            continueJob();
        }).fail(function (xhr) {
            $('#cp-kr-start').prop('disabled', false);
            $msg.html('<span class="cp-kr-err">HTTP ' + xhr.status + ': ' + (xhr.responseText || '').substring(0, 300) + '</span>');
        });
    });

    function updateProgressFromResponse(p) {
        if (!p) return;
        $('#cp-kr-stage').text(p.stage || '—');
        $('#cp-kr-status').text(p.status || '—');
        $('#cp-kr-api').text(p.api_requests || 0);
        if (p.error) $('#cp-kr-error').html('<div class="cp-kr-error">' + p.error + '</div>');
    }

    function continueJob() {
        if (!activeJobId) return;
        $.post(cpKr.ajax, { action: 'cp_kr_kick_job', nonce: cpKr.nonce, job_id: activeJobId }, function (res) {
            if (!res || !res.success) return;
            var p = res.data;
            updateProgressFromResponse(p);
            refreshCounts();
            if (p.status === 'completed') {
                // Only redirect if we're NOT already on this job's detail page.
                if (!isOnJobDetailPage(activeJobId)) {
                    window.location.href = 'admin.php?page=cp-kr&job_id=' + activeJobId;
                }
                return;
            }
            if (p.status === 'failed') return;
            setTimeout(continueJob, 500);
        }).fail(function () {
            setTimeout(continueJob, 2000);
        });
    }

    function refreshCounts() {
        if (!activeJobId) return;
        $.post(cpKr.ajax, { action: 'cp_kr_job_status', nonce: cpKr.nonce, job_id: activeJobId }, function (res) {
            if (!res || !res.success) return;
            $('#cp-kr-cands').text(res.data.candidates);
            $('#cp-kr-rejs').text(res.data.rejected);
        });
    }

    // Manual kick button in progress panel — reads job id from DOM
    $('#cp-kr-kick').on('click', function () {
        var jid = parseInt($('#cp-kr-progress').data('job-id'), 10) || activeJobId;
        if (!jid) { alert('No job to continue. Click View on a job in the table below.'); return; }
        activeJobId = jid;
        continueJob();
    });

    // Complete button — force-completes a stuck job
    $(document).on('click', '#cp-kr-complete', function () {
        var jid = parseInt($('#cp-kr-progress').data('job-id'), 10) || activeJobId;
        if (!jid) { alert('No active job.'); return; }
        if (!confirm('Mark job #' + jid + ' as completed? Use only if stuck at stage 7/8.')) return;
        $.post(cpKr.ajax, { action: 'cp_kr_complete_job', nonce: cpKr.nonce, job_id: jid }, function (r) {
            if (r.success && !isOnJobDetailPage(jid)) {
                window.location.href = 'admin.php?page=cp-kr&job_id=' + jid;
            } else if (r.success) {
                location.reload();
            } else {
                alert('Failed: ' + (r.data || 'unknown'));
            }
        });
    });

    // Kick button in recent jobs table
    $(document).on('click', '.cp-kr-kick-row', function (e) {
        e.preventDefault();
        var id = parseInt($(this).data('id'), 10);
        if (!id) return;
        activeJobId = id;
        $('#cp-kr-progress').data('job-id', id).show();
        continueJob();
    });

    // Auto-resume ONLY when the job is NOT already completed or failed.
    // This prevents the refresh loop on detail pages for completed jobs.
    if (activeJobId) {
        $('#cp-kr-progress').show();
        $.post(cpKr.ajax, { action: 'cp_kr_job_status', nonce: cpKr.nonce, job_id: activeJobId }, function (res) {
            if (!res || !res.success) return;
            updateProgressFromResponse(res.data);
            if (res.data.status !== 'completed' && res.data.status !== 'failed') {
                continueJob();
            }
        });
    }

    $('#cp-kr-test').on('click', function () {
        var $out = $('#cp-kr-test-result');
        $out.html('<em>Testing credentials…</em>');
        $.ajax({
            url: cpKr.ajax,
            type: 'POST',
            dataType: 'json',
            data: { action: 'cp_kr_test_credentials', nonce: cpKr.nonce }
        }).done(function (res) {
            if (res && res.success) {
                var data = res.data || {};
                var msg = 'Credentials valid.';
                if (data.customer_id) msg += ' Customer: ' + data.customer_id + '.';
                if (data.api_version) msg += ' API: ' + data.api_version + '.';
                $out.html('<span class="cp-kr-ok">' + msg + '</span>');
            } else {
                var err = (res && res.data) ? res.data : 'Unknown error — no data returned.';
                $out.html('<span class="cp-kr-err">' + err + '</span>');
            }
        }).fail(function (xhr) {
            var body = xhr.responseText ? xhr.responseText.substring(0, 400) : '(empty response)';
            $out.html('<span class="cp-kr-err">HTTP ' + xhr.status + ': ' + body + '</span>');
        });
    });

    $(document).on('click', '.cp-kr-accept, .cp-kr-reject', function () {
        var $b = $(this), id = $b.data('id');
        var status = $b.hasClass('cp-kr-accept') ? 'accepted' : 'rejected';
        var reason = status === 'rejected' ? (prompt('Rejection reason?') || 'manual') : '';
        $.post(cpKr.ajax, { action: 'cp_kr_update_candidate', nonce: cpKr.nonce, id: id, status: status, reason: reason }, function () {
            location.reload();
        });
    });

    $(document).on('click', '.cp-kr-delete-job', function () {
        var $button = $(this);
        var jobId = parseInt($button.data('id'), 10);
        if (!jobId || !confirm('Permanently delete job #' + jobId + ' and all its results, candidates, rejected keywords, sources, and variation data? This cannot be undone.')) return;

        $button.prop('disabled', true);
        $.post(cpKr.ajax, { action: 'cp_kr_delete_job', nonce: cpKr.nonce, job_id: jobId }, function (res) {
            if (res && res.success) {
                if (activeJobId === jobId) {
                    activeJobId = null;
                }
                window.location.href = 'admin.php?page=cp-kr';
                return;
            }
            $button.prop('disabled', false);
            alert('Could not delete job: ' + (res && res.data ? res.data : 'Unknown error'));
        }).fail(function (xhr) {
            $button.prop('disabled', false);
            alert('Could not delete job (HTTP ' + xhr.status + '). Please try again.');
        });
    });

    $(document).on('click', '[data-cp-kr-export]', function (e) {
        e.preventDefault();
        var jobId = $(this).data('cp-kr-export');
        var type = $(this).data('type');
        var $form = $('<form method="post" action="' + cpKr.ajax + '" target="_blank"></form>');
        $form.append('<input name="action" value="cp_kr_export">');
        $form.append('<input name="nonce" value="' + cpKr.nonce + '">');
        $form.append('<input name="job_id" value="' + jobId + '">');
        $form.append('<input name="type" value="' + type + '">');
        $('body').append($form);
        $form.submit().remove();
    });
});