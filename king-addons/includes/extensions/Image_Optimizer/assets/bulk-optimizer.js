/**
 * King Addons - Bulk Image Optimizer
 * 
 * Handles bulk optimization workflow, progress tracking,
 * and UI interactions.
 *
 * @package King_Addons
 */

(function($) {
    'use strict';

    function updateQuotaUI(quota) {
        if (!quota || typeof quota !== 'object') {
            return;
        }

        // Keep localized state in sync so other checks use fresh values.
        if (typeof kingImageOptimizer !== 'undefined') {
            kingImageOptimizer.quota = quota;
        }

        const $remaining = $('#ka-img-opt-quota-remaining');
        const $limit = $('#ka-img-opt-quota-limit');

        if ($remaining.length && typeof quota.remaining !== 'undefined') {
            $remaining.text(String(quota.remaining));
        }
        if ($limit.length && typeof quota.limit !== 'undefined') {
            $limit.text(String(quota.limit));
        }
    }

    function showProLimitModal(context = {}) {
        const $modal = $('#ka-img-opt-pro-modal');
        if (!$modal.length) {
            const msg = (kingImageOptimizer && kingImageOptimizer.strings && kingImageOptimizer.strings.quotaExceeded)
                ? kingImageOptimizer.strings.quotaExceeded
                : 'Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.';
            alert(msg);
            return;
        }

        const q = (context && context.quota) ? context.quota : (kingImageOptimizer ? kingImageOptimizer.quota : null);
        if (q) {
            $('#ka-img-opt-modal-remaining').text(String(typeof q.remaining !== 'undefined' ? q.remaining : 0));
            $('#ka-img-opt-modal-limit').text(String(typeof q.limit !== 'undefined' ? q.limit : 200));
        }

        if (context && context.subtitle) {
            $('#ka-img-opt-pro-modal-sub').text(String(context.subtitle));
        }

        // Upgrade URL can be overridden by server.
        const upgradeUrl = (context && context.upgradeUrl)
            ? context.upgradeUrl
            : (kingImageOptimizer && kingImageOptimizer.upgradeUrl ? kingImageOptimizer.upgradeUrl : 'https://kingaddons.com/pricing/');
        $('#ka-img-opt-modal-upgrade').attr('href', upgradeUrl);

        $modal.attr('aria-hidden', 'false').show();
    }

    function hideProLimitModal() {
        const $modal = $('#ka-img-opt-pro-modal');
        if (!$modal.length) {
            return;
        }
        $modal.attr('aria-hidden', 'true').hide();
    }

    function getQuotaRemaining() {
        if (typeof kingImageOptimizer === 'undefined' || kingImageOptimizer.isPro) {
            return Infinity;
        }
        const q = kingImageOptimizer.quota || {};
        const remaining = parseInt(q.remaining, 10);
        return Number.isFinite(remaining) ? remaining : 0;
    }

    // Prevent accidental tab close/navigation during long-running processes
    let unloadGuardEnabled = false;
    let unloadGuardMessage = '';

    function setUnloadGuard(enabled, message) {
        unloadGuardEnabled = !!enabled;
        unloadGuardMessage = message || unloadGuardMessage || 'A process is running. Are you sure you want to leave this page?';
    }

    // Note: Most modern browsers ignore custom text and show a standard prompt.
    window.addEventListener('beforeunload', function(e) {
        if (!unloadGuardEnabled) {
            return;
        }

        e.preventDefault();
        // Chrome requires returnValue to be set.
        e.returnValue = unloadGuardMessage;
        return unloadGuardMessage;
    });

    // State management
    let state = {
        isProcessing: false,
        isPaused: false,
        shouldStop: false,
        stopReason: null,
        imageQueue: [],
        imageIndex: {},
        currentIndex: 0,
        totalImages: 0,
        successCount: 0,
        skippedCount: 0,
        errorCount: 0,
        failedBase: null,
        totalSavedBytes: 0,
        startTime: 0,
        results: []
    };

    // Live list UI state
    const liveList = {
        view: 'processed', // processed | remaining
        filter: 'all', // all | success | skipped | error
        page: 1,
        perPage: 10
    };

    // Settings (canvas-only WebP conversion)
    let settings = {
        quality: 82,
        skipSmall: false,
        minSize: 10240,
        autoReplaceUrls: true,
        resizeEnabled: true,
        maxWidth: 2048
    };

    function qualityToFillColor(quality) {
        const q = Math.max(1, Math.min(100, parseInt(quality, 10) || 0));
        // Map 1..100 => hue 10..120 (red -> green)
        const t = (q - 1) / 99;
        const hue = 10 + (110 * t);
        return `hsl(${hue}, 85%, 45%)`;
    }

    function applySliderFill($slider, quality) {
        if (!$slider || !$slider.length) {
            return;
        }

        const q = Math.max(1, Math.min(100, parseInt(quality, 10) || 0));
        const pct = ((q - 1) / 99) * 100;
        const fillColor = qualityToFillColor(q);

        const $wrap = $slider.closest('.ka-img-opt-slider-wrap');
        if ($wrap.length) {
            $wrap.css('--ka-slider-pct', pct.toFixed(2) + '%');
            $wrap.css('--ka-slider-fill-color', fillColor);
        }
    }

    function initSliderFills() {
        $('.ka-img-opt-slider').each(function() {
            const $slider = $(this);
            applySliderFill($slider, $slider.val());
        });
    }

    function setProgressTitleState(stateName) {
        const $spinner = $('#ka-opt-progress-spinner');
        const $check = $('#ka-opt-progress-check');

        if (!$spinner.length || !$check.length) {
            return;
        }

        if (stateName === 'running') {
            $check.hide();
            $spinner.removeClass('is-paused');
            $spinner.addClass('is-active').show();
            return;
        }

        if (stateName === 'paused') {
            $check.hide();
            $spinner.addClass('is-active is-paused').show();
            return;
        }

        if (stateName === 'complete') {
            $spinner.removeClass('is-active').hide();
            $spinner.removeClass('is-paused');
            $check.show();
            return;
        }

        // idle / reset
        $spinner.removeClass('is-active').hide();
        $spinner.removeClass('is-paused');
        $check.hide();
    }

    function parseIntFromText(text) {
        const cleaned = String(text || '').replace(/[^0-9]/g, '');
        const n = parseInt(cleaned, 10);
        return Number.isFinite(n) ? n : 0;
    }

    function fetchGlobalStats() {
        return new Promise((resolve) => {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_get_stats',
                    nonce: kingImageOptimizer.nonce
                },
                success: function(response) {
                    if (response && response.success && response.data) {
                        resolve(response.data);
                        return;
                    }
                    resolve(null);
                },
                error: function() {
                    resolve(null);
                }
            });
        });
    }

    function initLiveListControls() {
        // Tabs
        $(document).on('click', '.ka-img-opt-live-tab', function() {
            const view = $(this).data('view');
            if (!view) {
                return;
            }

            liveList.view = view;
            liveList.page = 1;

            $('.ka-img-opt-live-tab').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');

            if (liveList.view === 'remaining') {
                $('.ka-img-opt-live-filters').css('opacity', '0.55');
                $('.ka-img-opt-live-filter').prop('disabled', true);
            } else {
                $('.ka-img-opt-live-filters').css('opacity', '1');
                $('.ka-img-opt-live-filter').prop('disabled', false);
            }

            renderLiveList();
        });

        // Filters
        $(document).on('click', '.ka-img-opt-live-filter', function() {
            const filter = $(this).data('filter');
            if (!filter) {
                return;
            }

            liveList.filter = filter;
            liveList.page = 1;

            $('.ka-img-opt-live-filter').removeClass('active');
            $(this).addClass('active');
            renderLiveList();
        });

        // Pagination
        $('#ka-live-prev').on('click', function() {
            liveList.page = Math.max(1, liveList.page - 1);
            renderLiveList();
        });

        $('#ka-live-next').on('click', function() {
            liveList.page = liveList.page + 1;
            renderLiveList();
        });
    }

    function buildImageIndex(images) {
        state.imageIndex = {};
        (images || []).forEach((img) => {
            if (img && img.id) {
                state.imageIndex[img.id] = img;
            }
        });
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getProcessedItems() {
        const items = (state.results || []).slice().reverse();
        return items
            .filter((r) => {
                if (liveList.filter === 'all') {
                    return true;
                }
                return r.status === liveList.filter;
            })
            .map((r) => {
                const img = (state.imageIndex && r.id) ? state.imageIndex[r.id] : null;
                return {
                    id: r.id,
                    title: (img && img.title) ? img.title : (r.filename || ''),
                    filename: r.filename || '',
                    thumb_url: (img && img.thumb_url) ? img.thumb_url : '',
                    status: r.status,
                    error: r.error,
                    savedBytes: r.savedBytes || 0,
                    originalBytes: r.originalBytes || 0,
                    optimizedBytes: r.optimizedBytes || 0,
                    savingsPercent: typeof r.savingsPercent !== 'undefined' ? r.savingsPercent : null,
                    mediaLink: r.id ? ('upload.php?item=' + r.id) : ''
                };
            });
    }

    function getRemainingItems() {
        const remaining = (state.imageQueue || []).slice(state.currentIndex);
        return remaining.map((img, idx) => ({
            id: img.id,
            title: img.title || img.filename,
            filename: img.filename || '',
            thumb_url: img.thumb_url || '',
            status: (idx === 0 && state.isProcessing && !state.isPaused) ? 'current' : 'pending'
        }));
    }

    function renderLiveList() {
        const $list = $('#ka-live-list');
        if (!$list.length) {
            return;
        }

        const processedCount = (state.results || []).length;
        const remainingCount = Math.max(0, (state.totalImages || 0) - (state.currentIndex || 0));
        $('#ka-live-processed-count').text(processedCount);
        $('#ka-live-remaining-count').text(remainingCount);

        const items = (liveList.view === 'remaining') ? getRemainingItems() : getProcessedItems();
        const total = items.length;
        const perPage = liveList.perPage;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        liveList.page = Math.min(Math.max(1, liveList.page), totalPages);

        const startIdx = (liveList.page - 1) * perPage;
        const pageItems = items.slice(startIdx, startIdx + perPage);

        $('#ka-live-page').text(liveList.page + ' / ' + totalPages);
        $('#ka-live-prev').prop('disabled', liveList.page <= 1);
        $('#ka-live-next').prop('disabled', liveList.page >= totalPages);

        if (!pageItems.length) {
            $list.html('<div class="ka-img-opt-live-item"><div class="ka-img-opt-live-meta"><div class="ka-img-opt-live-title">No items</div><div class="ka-img-opt-live-sub">Nothing to show yet.</div></div></div>');
            return;
        }

        const html = pageItems.map((it) => {
            const thumb = it.thumb_url
                ? `<img src="${escapeHtml(it.thumb_url)}" alt="" loading="lazy" />`
                : '';

            const status = it.status || 'pending';
            const pillClass = status;
            const pillText = (status === 'success')
                ? 'Optimized'
                : (status === 'skipped')
                    ? 'Skipped'
                    : (status === 'error')
                        ? 'Failed'
                        : (status === 'current')
                            ? (state.isPaused ? 'Paused' : 'Current')
                            : 'Queued';

            const sub = `#${it.id} • ${escapeHtml(it.filename || '')}`;

            const hasSizeInfo = (status === 'success')
                && Number.isFinite(it.originalBytes)
                && it.originalBytes > 0
                && Number.isFinite(it.optimizedBytes)
                && it.optimizedBytes > 0;

            const savingsPct = (hasSizeInfo)
                ? (
                    (typeof it.savingsPercent === 'number' && Number.isFinite(it.savingsPercent))
                        ? it.savingsPercent
                        : Math.round(((it.originalBytes - it.optimizedBytes) / it.originalBytes) * 100)
                )
                : null;

            const metrics = (hasSizeInfo)
                ? `<div class="ka-img-opt-live-metrics" title="Original → New • Saved %">
                        <span class="ka-img-opt-live-size-old">${escapeHtml(KingImageOptimizer.formatBytes(it.originalBytes))}</span>
                        <span class="ka-img-opt-live-arrow">→</span>
                        <span class="ka-img-opt-live-size-new">${escapeHtml(KingImageOptimizer.formatBytes(it.optimizedBytes))}</span>
                        <span class="ka-img-opt-live-dot">•</span>
                        <span class="ka-img-opt-live-pct">${escapeHtml(String(savingsPct))}%</span>
                    </div>`
                : '';

            const mediaHref = it.mediaLink ? escapeHtml(it.mediaLink) : '';
            const titleText = escapeHtml(it.filename || it.title || ('Attachment #' + it.id));
            const title = mediaHref
                ? `<a class="ka-img-opt-live-title-link" href="${mediaHref}" target="_blank" rel="noopener noreferrer">${titleText}</a>`
                : titleText;

            const errorHint = (status === 'error' && it.error)
                ? ` title="${escapeHtml(it.error)}"`
                : '';

            return `
                <div class="ka-img-opt-live-item"${errorHint}>
                    <div class="ka-img-opt-live-thumb">${thumb}</div>
                    <div class="ka-img-opt-live-meta">
                        <div class="ka-img-opt-live-title">${title}</div>
                        <div class="ka-img-opt-live-sub">${sub}</div>
                    </div>
                    <div class="ka-img-opt-live-right">
                        ${metrics}
                        <span class="ka-img-opt-pill ${pillClass}">${pillText}</span>
                    </div>
                </div>
            `;
        }).join('');

        $list.html(html);
    }

    /**
     * Initialize bulk optimizer
     */
    function init() {
        // Sync settings from server
        if (typeof kingImageOptimizer !== 'undefined' && kingImageOptimizer.settings) {
            settings.quality = kingImageOptimizer.settings.quality || settings.quality;
            if (typeof kingImageOptimizer.settings.skip_small !== 'undefined') {
                settings.skipSmall = !!parseInt(kingImageOptimizer.settings.skip_small, 10);
            }
            settings.minSize = parseInt(kingImageOptimizer.settings.min_size || settings.minSize, 10) || settings.minSize;
            settings.autoReplaceUrls = kingImageOptimizer.settings.auto_replace_urls !== false;
            settings.resizeEnabled = kingImageOptimizer.settings.resize_enabled || false;
            settings.maxWidth = kingImageOptimizer.settings.max_width || 2048;
        }
        
        // Sync UI elements with settings
        $('#auto-replace-urls').prop('checked', settings.autoReplaceUrls);
        $('#skip-small').prop('checked', settings.skipSmall);
        $('#resize-enabled').prop('checked', settings.resizeEnabled);
        $('#max-width').val(settings.maxWidth);

        // Settings tab mirrors (if present)
        $('#settings-resize-enabled').prop('checked', settings.resizeEnabled);
        $('#settings-max-width').val(settings.maxWidth);
        $('.ka-img-opt-resize-options').toggle(!!settings.resizeEnabled);
        
        bindEvents();

        // Modal close handlers
        $(document).on('click', '[data-ka-modal-close="1"]', function(e) {
            e.preventDefault();
            hideProLimitModal();
        });

        checkForSavedState();
        initQualitySlider();
        initSliderFills();
        initLiveListControls();
        renderLiveList();
    }

    /**
     * Bind UI events
     */
    function bindEvents() {

        // Quality presets
        $('.ka-img-opt-preset-btn').on('click', function() {
            $('.ka-img-opt-preset-btn').removeClass('active');
            $(this).addClass('active');
            
            const quality = parseInt($(this).data('quality'));
            settings.quality = quality;
            $('#quality-slider').val(quality);
            $('#quality-output').text(quality + '%');
            applySliderFill($('#quality-slider'), quality);
        });

        // Quality slider
        $('#quality-slider').on('input', function() {
            settings.quality = parseInt($(this).val());
            $('#quality-output').text(settings.quality + '%');
            updatePresetSelection(settings.quality);
            applySliderFill($(this), settings.quality);
        });

        // Advanced settings toggle
        $('#advanced-toggle').on('click', function() {
            $('#advanced-content').slideToggle(300);
            $(this).toggleClass('open');
        });

        // Skip small toggle
        $('#skip-small').on('change', function() {
            settings.skipSmall = $(this).is(':checked');
        });

        // Auto replace URLs toggle
        $('#auto-replace-urls').on('change', function() {
            settings.autoReplaceUrls = $(this).is(':checked');
        });

        // Resize toggle
        $('#resize-enabled').on('change', function() {
            settings.resizeEnabled = $(this).is(':checked');
            $('.ka-img-opt-resize-options').toggle(settings.resizeEnabled);

            // Mirror to settings tab
            $('#settings-resize-enabled').prop('checked', settings.resizeEnabled);
        });

        // Settings tab resize toggle
        $('#settings-resize-enabled').on('change', function() {
            settings.resizeEnabled = $(this).is(':checked');
            $('.ka-img-opt-resize-options').toggle(settings.resizeEnabled);

            // Mirror to bulk advanced
            $('#resize-enabled').prop('checked', settings.resizeEnabled);
        });

        // Max width input
        $('#max-width').on('change', function() {
            settings.maxWidth = parseInt($(this).val()) || 2048;

            // Mirror to settings tab
            $('#settings-max-width').val(settings.maxWidth);
        });

        // Settings tab max width
        $('#settings-max-width').on('change', function() {
            settings.maxWidth = parseInt($(this).val()) || 2048;

            // Mirror to bulk advanced
            $('#max-width').val(settings.maxWidth);
        });


        // Start optimization
        $('#start-optimization').on('click', startOptimization);

        // Pause button
        $('#pause-btn').on('click', togglePause);

        // Stop button
        $('#stop-btn').on('click', stopOptimization);

        // Resume button
        $('#resume-btn').on('click', resumeOptimization);

        // Discard session button
        $('#discard-btn').on('click', discardSession);

        // Resume close button
        $('#resume-close').on('click', function() {
            $('#resume-banner').slideUp(300);
        });

        // Optimize more button
        $('#optimize-more').on('click', function() {
            resetState();
            $('#results-section').hide();
            $('#optimization-options').show();
        });

        // Settings tab handlers
        $('#settings-quality').on('input', function() {
            const val = $(this).val();
            $('#settings-quality-output').text(val + '%');
            applySliderFill($(this), val);
        });

        $('#save-settings').on('click', saveSettings);
        $('#restore-all').on('click', restoreAllImages);
        $('#sync-media-library').on('click', syncMediaLibrary);
        $('#sync-media-library-stop').on('click', stopSyncMediaLibrary);
    }

    // --- Media Library Sync ---
    let syncQueue = [];
    let syncTotal = 0;
    let syncProcessed = 0;
    let syncSynced = 0;
    let syncSkipped = 0;
    let syncErrors = 0;
    let isSyncing = false;

    function syncMediaLibrary() {
        const $btn = $('#sync-media-library');
        const $stop = $('#sync-media-library-stop');
        const $progress = $('#sync-media-library-progress');

        $btn.prop('disabled', true).html('<span class="ka-btn-spinner"></span> Restoring...');
        $stop.hide();
        $progress.hide();

        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_get_sync_ids',
                nonce: kingImageOptimizer.nonce
            },
            success: function(response) {
                if (!response.success || !response.data || !response.data.ids) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Sync Media Library');
                    showNotification('Failed to load sync list.', 'error');
                    return;
                }

                syncQueue = response.data.ids;
                syncTotal = response.data.total || syncQueue.length;
                syncProcessed = 0;
                syncSynced = 0;
                syncSkipped = 0;
                syncErrors = 0;
                isSyncing = true;

                setUnloadGuard(true, (typeof kingImageOptimizer !== 'undefined' && kingImageOptimizer.strings && kingImageOptimizer.strings.leaveWarning)
                    ? kingImageOptimizer.strings.leaveWarning
                    : 'A process is running. Are you sure you want to leave this page?');

                if (syncTotal === 0) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Sync Media Library');
                    setUnloadGuard(false);
                    showNotification('No optimized images found to sync.', 'info');
                    return;
                }

                $btn.html('<span class="ka-btn-spinner"></span> Syncing...');
                $stop.show();
                $progress.show();
                $('#sync-current-filename').text('Starting...');
                updateSyncProgress();
                processNextSyncBatch();
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Sync Media Library');
                setUnloadGuard(false);
                showNotification('Failed to load sync list.', 'error');
            }
        });
    }

    function stopSyncMediaLibrary() {
        isSyncing = false;
        setUnloadGuard(false);
        $('#sync-media-library-stop').hide();
        $('#sync-current-filename').text('Stopping...');
    }

    function updateSyncProgress() {
        const percent = syncTotal > 0 ? Math.round((syncProcessed / syncTotal) * 100) : 0;
        $('#sync-progress-fill').css('width', percent + '%');
        $('#sync-progress-percent').text(percent + '%');
        $('#sync-progress-count').text(syncProcessed + ' / ' + syncTotal);
    }

    function finishSyncMediaLibrary() {
        const $btn = $('#sync-media-library');
        const $stop = $('#sync-media-library-stop');

        isSyncing = false;
        setUnloadGuard(false);
        $stop.hide();
        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Sync Media Library');
        $('#sync-current-filename').text('Done');

        showNotification(
            `Sync complete! Synced: ${syncSynced}, skipped: ${syncSkipped}, errors: ${syncErrors}.`,
            syncErrors > 0 ? 'warning' : 'success'
        );

        // Refresh top stats
        refreshStats();

        // Refresh breakdown
        refreshBreakdown();
    }

    function processNextSyncBatch() {
        if (!isSyncing) {
            finishSyncMediaLibrary();
            return;
        }

        if (syncQueue.length === 0) {
            finishSyncMediaLibrary();
            return;
        }

        // Batch size (keep small to avoid timeouts)
        const batchSize = 10;
        const batch = syncQueue.splice(0, batchSize);

        $('#sync-current-filename').text(`Syncing ${Math.min(syncProcessed + batch.length, syncTotal)} of ${syncTotal}...`);

        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_sync_batch',
                nonce: kingImageOptimizer.nonce,
                ids: JSON.stringify(batch)
            },
            success: function(response) {
                if (response.success && response.data) {
                    syncSynced += response.data.synced || 0;
                    syncSkipped += response.data.skipped || 0;
                    syncErrors += response.data.errors || 0;
                } else {
                    syncErrors += batch.length;
                }

                syncProcessed += batch.length;
                updateSyncProgress();

                // Continue (avoid background tab throttling issues a bit)
                const delay = (document.hidden || document.visibilityState === 'hidden') ? 500 : 0;
                setTimeout(processNextSyncBatch, delay);
            },
            error: function() {
                syncErrors += batch.length;
                syncProcessed += batch.length;
                updateSyncProgress();
                const delay = (document.hidden || document.visibilityState === 'hidden') ? 500 : 0;
                setTimeout(processNextSyncBatch, delay);
            }
        });
    }

    /**
     * Initialize quality slider
     */
    function initQualitySlider() {
        const quality = kingImageOptimizer.settings.quality || 82;
        settings.quality = quality;
        $('#quality-slider').val(quality);
        $('#quality-output').text(quality + '%');
        updatePresetSelection(quality);
        applySliderFill($('#quality-slider'), quality);
    }

    /**
     * Update preset button selection based on quality
     */
    function updatePresetSelection(quality) {
        $('.ka-img-opt-preset-btn').removeClass('active');
        
        // Find closest preset
        let closest = null;
        let minDiff = Infinity;
        
        $('.ka-img-opt-preset-btn').each(function() {
            const preset = parseInt($(this).data('quality'));
            const diff = Math.abs(preset - quality);
            if (diff < minDiff) {
                minDiff = diff;
                closest = $(this);
            }
        });
        
        if (closest && minDiff <= 10) {
            closest.addClass('active');
        }
    }

    /**
     * Check for saved optimization state
     */
    function checkForSavedState() {
        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_get_state',
                nonce: kingImageOptimizer.nonce
            },
            success: function(response) {
                if (response.success && response.data.has_state) {
                    showResumeBanner(response.data.state);
                }
            }
        });
    }

    /**
     * Show resume banner
     */
    function showResumeBanner(savedState) {
        // Check if optimization is already complete
        if (savedState.currentIndex >= savedState.totalImages && savedState.totalImages > 0) {
            // Already finished, clear the stale state silently
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_clear_state',
                    nonce: kingImageOptimizer.nonce
                }
            });
            return;
        }
        
        $('#resume-count').text(savedState.currentIndex + ' of ' + savedState.totalImages);
        
        // Show saved bytes if available
        if (savedState.totalSavedBytes) {
            $('#stat-saved').text(KingImageOptimizer.formatBytes(savedState.totalSavedBytes));
        }
        
        $('#resume-banner').slideDown(300);
        
        // Store state for resume
        state.savedState = savedState;
    }

    /**
     * Resume optimization
     */
    async function resumeOptimization() {
        if (!state.savedState) return;
        
        // Restore state
        state.imageQueue = state.savedState.imageQueue || [];
        buildImageIndex(state.imageQueue);
        state.currentIndex = state.savedState.currentIndex || 0;
        state.totalImages = state.savedState.totalImages || 0;
        state.successCount = state.savedState.successCount || 0;
        state.errorCount = state.savedState.errorCount || 0;
        state.totalSavedBytes = state.savedState.totalSavedBytes || 0;
        settings = { ...settings, ...state.savedState.settings };

        // Refresh baseline failed count so live updates don't double count
        try {
            const stats = await fetchGlobalStats();
            const serverFailed = stats && typeof stats.failed_images !== 'undefined' ? parseInt(stats.failed_images, 10) || 0 : parseIntFromText($('#stat-failed').text());
            state.failedBase = Math.max(0, serverFailed - (state.errorCount || 0));
        } catch (e) {
            state.failedBase = parseIntFromText($('#stat-failed').text());
        }
        
        // Check if already complete
        if (state.currentIndex >= state.totalImages) {
            // Already finished, clear state and show completion
            discardSessionSilent();
            $('#resume-banner').slideUp(300);
            return;
        }
        
        // Hide resume banner and options
        $('#resume-banner').slideUp(300);
        $('#optimization-options').slideUp(300);
        
        // Show progress
        $('#progress-section').slideDown(300);

        // Title spinner while running
        setProgressTitleState('running');
        
        // Update progress display with saved values
        updateProgress();
        renderLiveList();
        
        // Start processing
        state.isProcessing = true;
        state.isPaused = false;
        state.shouldStop = false;
        state.startTime = Date.now();
        
        processNextImage();
    }

    /**
     * Discard session
     */
    function discardSession() {
        if (!confirm(kingImageOptimizer.strings.confirmBulkRestore || 'Are you sure you want to discard the saved session?')) {
            return;
        }
        discardSessionSilent();
    }
    
    /**
     * Discard session silently (no confirmation)
     */
    function discardSessionSilent() {
        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_clear_state',
                nonce: kingImageOptimizer.nonce
            },
            success: function() {
                $('#resume-banner').slideUp(300);
                state.savedState = null;
            }
        });
    }

    /**
     * Start optimization
     */
    async function startOptimization() {
        const remaining = getQuotaRemaining();
        if (remaining <= 0) {
            showProLimitModal({
                quota: (kingImageOptimizer ? kingImageOptimizer.quota : null),
                subtitle: (kingImageOptimizer && kingImageOptimizer.strings && kingImageOptimizer.strings.quotaExceeded)
                    ? kingImageOptimizer.strings.quotaExceeded
                    : 'Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.',
            });
            return;
        }

        setUnloadGuard(true, (typeof kingImageOptimizer !== 'undefined' && kingImageOptimizer.strings && kingImageOptimizer.strings.leaveWarning)
            ? kingImageOptimizer.strings.leaveWarning
            : 'Optimization is running. Are you sure you want to leave this page?');

        resetState();

        // Baseline failed count for live updates
        try {
            const stats = await fetchGlobalStats();
            const serverFailed = stats && typeof stats.failed_images !== 'undefined' ? parseInt(stats.failed_images, 10) || 0 : parseIntFromText($('#stat-failed').text());
            state.failedBase = Math.max(0, serverFailed - state.errorCount);
        } catch (e) {
            state.failedBase = parseIntFromText($('#stat-failed').text());
        }
        
        // Show progress section
        $('#optimization-options').slideUp(300);
        $('#progress-section').slideDown(300);

        // Title spinner while running
        setProgressTitleState('running');
        
        // Fetch images to optimize
        try {
            const images = await fetchImagesToOptimize();
            
            if (images.length === 0) {
                setUnloadGuard(false);
                showError('No images found to optimize.');
                return;
            }

            state.imageQueue = images;
            buildImageIndex(images);
            state.totalImages = images.length;
            state.isProcessing = true;
            state.startTime = Date.now();
            
            updateProgress();
            renderLiveList();
            processNextImage();
            
        } catch (error) {
            setUnloadGuard(false);
            showError('Failed to fetch images: ' + error.message);
        }
    }

    /**
     * Fetch images to optimize
     */
    function fetchImagesToOptimize() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_get_bulk_images',
                    nonce: kingImageOptimizer.nonce,
                    filter: 'pending',
                    per_page: 1000
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data.images);
                    } else {
                        reject(new Error(response.data.message || 'Failed to fetch images'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(error));
                }
            });
        });
    }

    /**
     * Process next image in queue
     */
    async function processNextImage() {
        // Check stop condition
        if (state.shouldStop || state.currentIndex >= state.totalImages) {
            finishOptimization();
            return;
        }

        // Check pause
        if (state.isPaused) {
            return;
        }

        const image = state.imageQueue[state.currentIndex];
        
        // Update UI
        $('#current-filename').text(image.filename);
        
        try {
            // Get image data
            const imageData = await getImageData(image.id);

            let imageSavedBytes = 0;
            let fullOriginalBytes = 0;
            let fullOptimizedBytes = 0;

            let didSaveAny = false;
            let hadAnyEligibleSize = false;
            
            // Process each size
            for (const [sizeName, sizeData] of Object.entries(imageData.images)) {
                // Skip small files if enabled
                if (settings.skipSmall && sizeData.filesize < settings.minSize) {
                    continue;
                }

                hadAnyEligibleSize = true;

                // Optimize
                const result = await window.kingOptimizer.optimize({
                    url: sizeData.url,
                    filesize: sizeData.filesize,
                    mime_type: sizeData.mime_type,
                    width: sizeData.width,
                    height: sizeData.height
                }, {
                    quality: settings.quality,
                    resize: settings.resizeEnabled,
                    maxWidth: settings.maxWidth
                });

                if (result.success) {
                    // Save optimized image
                    const saved = await saveOptimizedImage(image.id, sizeName, result);

                    const savedBytes = (saved && typeof saved.saved_bytes !== 'undefined')
                        ? (parseInt(saved.saved_bytes, 10) || 0)
                        : (result.savedBytes || 0);

                    state.totalSavedBytes += savedBytes;
                    imageSavedBytes += savedBytes;

                    if (sizeName === 'full') {
                        fullOriginalBytes = (saved && typeof saved.original_size !== 'undefined')
                            ? (parseInt(saved.original_size, 10) || 0)
                            : (result.originalSize || sizeData.filesize || 0);
                        fullOptimizedBytes = (saved && typeof saved.optimized_size !== 'undefined')
                            ? (parseInt(saved.optimized_size, 10) || 0)
                            : (result.optimizedSize || 0);
                    }

                    didSaveAny = true;
                }
            }

            if (didSaveAny) {
                // Apply WebP URLs if enabled
                if (settings.autoReplaceUrls) {
                    await applyWebpUrls(image.id);
                }

                state.successCount++;
                state.results.push({
                    id: image.id,
                    filename: image.filename,
                    status: 'success',
                    savedBytes: imageSavedBytes,
                    originalBytes: fullOriginalBytes,
                    optimizedBytes: fullOptimizedBytes,
                    savingsPercent: fullOriginalBytes > 0 ? Math.round(((fullOriginalBytes - fullOptimizedBytes) / fullOriginalBytes) * 100) : 0
                });
            } else {
                // Nothing saved (usually everything skipped by "Skip Small")
                state.skippedCount++;

                // Persist skipped images so they don't keep reappearing as Pending
                if (!hadAnyEligibleSize && settings.skipSmall) {
                    await markSkipped(image.id, 'skip_small');
                }

                state.results.push({
                    id: image.id,
                    filename: image.filename,
                    status: 'skipped'
                });
            }

            // If the free quota just ran out, stop before moving to next image.
            const remainingAfter = getQuotaRemaining();
            if (!kingImageOptimizer.isPro && remainingAfter <= 0 && (state.currentIndex + 1) < state.totalImages) {
                state.stopReason = 'quota';
                state.shouldStop = true;
            }

        } catch (error) {
            console.error('Error optimizing image:', error);

            if (error && error.code === 'quota_exceeded') {
                state.stopReason = 'quota';
                state.shouldStop = true;
                state.isProcessing = false;
                state.isPaused = false;
                setUnloadGuard(false);

                // Keep server resume state so user can upgrade and continue later.
                setTimeout(() => {
                    showProLimitModal({
                        quota: (kingImageOptimizer ? kingImageOptimizer.quota : null),
                        subtitle: (kingImageOptimizer && kingImageOptimizer.strings && kingImageOptimizer.strings.quotaExceeded)
                            ? kingImageOptimizer.strings.quotaExceeded
                            : 'Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.',
                        upgradeUrl: error.upgradeUrl || (kingImageOptimizer ? kingImageOptimizer.upgradeUrl : null),
                    });
                }, 250);

                finishOptimization();
                return;
            }

            state.errorCount++;

            // Persist failed images so they don't keep reappearing as Pending
            try {
                await markFailed(image.id, error && error.message ? error.message : 'Optimization error');
            } catch (e) {
                // best-effort only
            }

            state.results.push({
                id: image.id,
                filename: image.filename,
                status: 'error',
                error: error.message
            });
        }

        // Move to next
        state.currentIndex++;
        updateProgress();
        renderLiveList();
        saveOptimizationState();

        if (state.stopReason === 'quota') {
            finishOptimization();
            setTimeout(() => {
                showProLimitModal({
                    quota: (kingImageOptimizer ? kingImageOptimizer.quota : null),
                    subtitle: 'Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.',
                });
            }, 350);
            return;
        }

        if (state.shouldStop) {
            finishOptimization();
            return;
        }

        // Continue with next image
        // NOTE: requestAnimationFrame is heavily throttled/paused in background tabs.
        // Use a timer so bulk optimization can continue (though browsers may still throttle).
        const delay = (document.hidden || document.visibilityState === 'hidden') ? 1000 : 0;
        setTimeout(() => processNextImage(), delay);
    }

    /**
     * Get image data from server
     */
    function getImageData(attachmentId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_get_image_data',
                    nonce: kingImageOptimizer.nonce,
                    attachment_id: attachmentId,
                    sizes: 'all'
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data.message || 'Failed to get image data'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(error));
                }
            });
        });
    }

    /**
     * Save optimized image to server
     */
    function saveOptimizedImage(attachmentId, size, result) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_save_optimized',
                    nonce: kingImageOptimizer.nonce,
                    attachment_id: attachmentId,
                    size: size,
                    format: result.format,
                    image_data: result.data,
                    original_size: result.originalSize,
                    optimized_size: result.optimizedSize,
                    method: result.method
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data && response.data.quota) {
                            updateQuotaUI(response.data.quota);
                        }
                        resolve(response.data);
                    } else {
                        if (response.data && response.data.quota) {
                            updateQuotaUI(response.data.quota);
                        }

                        const err = new Error((response.data && response.data.message) ? response.data.message : 'Failed to save image');
                        if (response.data && response.data.code) {
                            err.code = response.data.code;
                        }
                        reject(err);
                    }
                },
                error: function(xhr, status, error) {
                    const json = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                    if (json && json.success === false && json.data) {
                        if (json.data.quota) {
                            updateQuotaUI(json.data.quota);
                        }

                        const err = new Error(json.data.message || error || 'Request failed');
                        if (json.data.code) {
                            err.code = json.data.code;
                        }
                        if (json.data.upgrade_url) {
                            err.upgradeUrl = json.data.upgrade_url;
                        }
                        reject(err);
                        return;
                    }

                    reject(new Error(error));
                }
            });
        });
    }

    /**
     * Apply WebP URLs to database
     */
    function applyWebpUrls(attachmentId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_apply_webp_urls',
                    nonce: kingImageOptimizer.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    resolve(response.success);
                },
                error: function() {
                    resolve(false); // Don't fail optimization for URL replacement errors
                }
            });
        });
    }
    
    /**
     * Mark an attachment as skipped in DB so it doesn't reappear as pending.
     */
    function markSkipped(attachmentId, reason) {
        return new Promise((resolve) => {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_mark_skipped',
                    nonce: kingImageOptimizer.nonce,
                    attachment_id: attachmentId,
                    reason: reason || 'skipped'
                },
                success: function() {
                    resolve(true);
                },
                error: function() {
                    resolve(false);
                }
            });
        });
    }

    /**
     * Mark an attachment as failed in DB so it doesn't reappear as pending.
     */
    function markFailed(attachmentId, reason) {
        return new Promise((resolve) => {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_mark_failed',
                    nonce: kingImageOptimizer.nonce,
                    attachment_id: attachmentId,
                    reason: reason || 'Optimization failed'
                },
                success: function() {
                    resolve(true);
                },
                error: function() {
                    resolve(false);
                }
            });
        });
    }

    /**
     * Update progress UI
     */
    function updateProgress() {
        const percent = state.totalImages > 0 
            ? Math.round((state.currentIndex / state.totalImages) * 100) 
            : 0;

        $('#progress-fill').css('width', percent + '%');
        $('#progress-percent').text(percent + '%');
        $('#progress-count').text(state.currentIndex + ' / ' + state.totalImages);

        $('#live-success').text(state.successCount);
        $('#live-skipped').text(state.skippedCount);
        $('#live-errors').text(state.errorCount);
        $('#live-saved').text(KingImageOptimizer.formatBytes(state.totalSavedBytes));

        // Update stats
        $('#stat-optimized').text(state.successCount);
        $('#stat-saved').text(KingImageOptimizer.formatBytes(state.totalSavedBytes));

        // Update failed stat live during processing
        if ($('#stat-failed').length) {
            const base = (typeof state.failedBase === 'number' && Number.isFinite(state.failedBase))
                ? state.failedBase
                : parseIntFromText($('#stat-failed').text());
            const displayFailed = Math.max(0, base + (state.errorCount || 0));
            $('#stat-failed').text(displayFailed.toLocaleString());
        }
    }

    /**
     * Toggle pause
     */
    function togglePause() {
        state.isPaused = !state.isPaused;
        
        if (state.isPaused) {
            $('#pause-btn').html('<span class="dashicons dashicons-controls-play"></span> ' + 
                (kingImageOptimizer.strings.resume || 'Resume'));
            saveOptimizationState();

            // Stop the title spinner animation while paused
            setProgressTitleState('paused');

            refreshStats();
            refreshBreakdown();
            renderLiveList();
        } else {
            $('#pause-btn').html('<span class="dashicons dashicons-controls-pause"></span> ' + 
                (kingImageOptimizer.strings.pause || 'Pause'));

            // Resume the title spinner animation
            setProgressTitleState('running');
            renderLiveList();
            processNextImage();
        }
    }

    /**
     * Stop optimization
     */
    function stopOptimization() {
        state.stopReason = 'user';
        state.shouldStop = true;

        // If paused, resume the loop just to allow it to exit cleanly.
        if (state.isPaused) {
            state.isPaused = false;
            $('#pause-btn').html('<span class="dashicons dashicons-controls-pause"></span> ' +
                (kingImageOptimizer.strings.pause || 'Pause'));

            setProgressTitleState('running');
            processNextImage();
            return;
        }

        // For in-flight work, Stop completes after current image finishes.
        renderLiveList();
    }

    /**
     * Save optimization state for resume
     */
    function saveOptimizationState() {
        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_save_state',
                nonce: kingImageOptimizer.nonce,
                currentIndex: state.currentIndex,
                totalImages: state.totalImages,
                successCount: state.successCount,
                errorCount: state.errorCount,
                totalSavedBytes: state.totalSavedBytes,
                imageQueue: JSON.stringify(state.imageQueue),
                settings: JSON.stringify(settings)
            }
        });
    }
    /**
     * Finish optimization
     */
    function finishOptimization() {
        state.isProcessing = false;
        setUnloadGuard(false);
        if (state.stopReason !== 'quota') {
            state.savedState = null; // Clear local saved state
        }

        // Title check when finished
        setProgressTitleState('complete');
        
        // Clear saved state on server only when fully complete.
        if (state.stopReason !== 'quota') {
            $.ajax({
                url: kingImageOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_img_clear_state',
                    nonce: kingImageOptimizer.nonce
                }
            });
        }
        
        // Hide resume banner if visible (keep it for quota stop so user can resume after upgrade)
        if (state.stopReason !== 'quota') {
            $('#resume-banner').slideUp(300);
        }

        // Update results
        $('#result-success').text(state.successCount);
        $('#result-saved').text(KingImageOptimizer.formatBytes(state.totalSavedBytes));
        
        const avgPercent = state.successCount > 0 
            ? Math.round((state.totalSavedBytes / (state.successCount * 500000)) * 100) // Rough estimate
            : 0;
        $('#result-percent').text(Math.min(avgPercent, 100) + '%');

        // Show results
        $('#progress-section').slideUp(300);
        $('#results-section').slideDown(300);

        // Refresh stats
        refreshStats();

        // Refresh breakdown
        refreshBreakdown();
    }

    /**
     * Reset state
     */
    function resetState() {
        state = {
            isProcessing: false,
            isPaused: false,
            shouldStop: false,
            stopReason: null,
            imageQueue: [],
            imageIndex: {},
            currentIndex: 0,
            totalImages: 0,
            successCount: 0,
            skippedCount: 0,
            errorCount: 0,
            failedBase: null,
            totalSavedBytes: 0,
            startTime: 0,
            results: []
        };
    }

    /**
     * Show error message
     */
    function showError(message) {
        setUnloadGuard(false);
        alert(message); // Simple alert for now, can be enhanced with custom modal
        $('#progress-section').hide();
        $('#optimization-options').show();
    }

    /**
     * Refresh stats from server
     */
    function refreshStats() {
        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_get_stats',
                nonce: kingImageOptimizer.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#stat-total').text(response.data.total_images.toLocaleString());
                    $('#stat-optimized').text(response.data.optimized_images.toLocaleString());
                    const skipped = response.data.skipped_images ? response.data.skipped_images : 0;
                    const failed = response.data.failed_images ? response.data.failed_images : 0;

                    // Keep the baseline in sync so live display doesn't double count after refreshStats()
                    state.failedBase = Math.max(0, (parseInt(failed, 10) || 0) - (state.errorCount || 0));

                    if ($('#stat-failed').length) {
                        $('#stat-failed').text(failed.toLocaleString());
                    }
                    const pending = (typeof response.data.pending_images !== 'undefined')
                        ? response.data.pending_images
                        : (response.data.total_images - response.data.optimized_images - skipped - failed);
                    $('#stat-pending').text(Math.max(0, pending).toLocaleString());
                    $('#stat-saved').text(KingImageOptimizer.formatBytes(response.data.total_saved_bytes));
                }
            }
        });
    }

    /**
     * Refresh the "Image Library Breakdown" block dynamically.
     */
    function refreshBreakdown() {
        const $list = $('#ka-img-opt-format-list');
        if ($list.length === 0) {
            return;
        }

        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_get_breakdown',
                nonce: kingImageOptimizer.nonce
            },
            success: function(response) {
                if (!response.success || !response.data) {
                    return;
                }

                const total = parseInt(response.data.total_images || 0, 10) || 0;
                const formats = response.data.formats || {};

                const keys = Object.keys(formats);
                if (keys.length === 0) {
                    $list.html('');
                    return;
                }

                let html = '';
                keys.forEach(function(format) {
                    const count = parseInt(formats[format] || 0, 10) || 0;
                    const width = total > 0 ? Math.min(100, (count / total) * 100) : 0;

                    html += '<div class="ka-img-opt-format-item">'
                        + '<div class="ka-img-opt-format-info">'
                        + '<span class="ka-img-opt-format-name">' + String(format).toUpperCase() + '</span>'
                        + '<span class="ka-img-opt-format-count">' + count.toLocaleString() + '</span>'
                        + '</div>'
                        + '<div class="ka-img-opt-format-bar">'
                        + '<div class="ka-img-opt-format-bar-fill" style="width: ' + width + '%;"></div>'
                        + '</div>'
                        + '</div>';
                });

                $list.html(html);
            }
        });
    }

    /**
     * Save settings
     */
    function saveSettings() {
        const resizeEnabled = $('#settings-resize-enabled').length
            ? $('#settings-resize-enabled').is(':checked')
            : $('#resize-enabled').is(':checked');

        const maxWidth = $('#settings-max-width').length
            ? $('#settings-max-width').val()
            : $('#max-width').val();

        const settingsData = {
            action: 'king_img_save_settings',
            nonce: kingImageOptimizer.nonce,
            quality: $('#settings-quality').val(),
            skip_small: $('#settings-skip-small').is(':checked') ? 1 : 0,
            auto_replace_urls: $('#settings-auto-replace').is(':checked') ? 1 : 0,
            auto_optimize_uploads: $('#settings-auto-optimize-uploads').is(':checked') ? 1 : 0,
            resize_enabled: resizeEnabled ? 1 : 0,
            max_width: maxWidth
        };

        $('#save-settings').prop('disabled', true).text('Saving...');

        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: settingsData,
            success: function(response) {
                $('#save-settings').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Settings');
                
                if (response.success) {
                    // Show success feedback
                    $('#save-settings').addClass('saved').text('✓ Saved!');
                    setTimeout(function() {
                        $('#save-settings').removeClass('saved').html('<span class="dashicons dashicons-saved"></span> Save Settings');
                    }, 2000);
                }
            },
            error: function() {
                $('#save-settings').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Settings');
                alert('Failed to save settings');
            }
        });
    }

    /**
     * Restore all images
     */
    let restoreQueue = [];
    let restoreTotal = 0;
    let restoreSuccess = 0;
    let restoreErrors = 0;
    let isRestoring = false;
    let restoreLastId = 0;

    function restoreAllImages() {
        if (!confirm(kingImageOptimizer.strings.confirmBulkRestore || 'Are you sure you want to restore ALL optimized images to originals? This will delete all optimized files.')) {
            return;
        }

        const $btn = $('#restore-all');
        $btn.prop('disabled', true).html('<span class="ka-btn-spinner"></span> Loading...');

        // First get all optimized image IDs
        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_get_optimized_ids',
                nonce: kingImageOptimizer.nonce,
            },
            success: function(response) {
                if (response.success && response.data.ids.length > 0) {
                    restoreQueue = response.data.ids;
                    restoreTotal = response.data.total;
                    restoreSuccess = 0;
                    restoreErrors = 0;
                    isRestoring = true;
                    restoreLastId = 0;

                    // Show spinner for the entire restore progress
                    $btn.prop('disabled', true).html('<span class="ka-btn-spinner"></span> Restoring...');

                    setUnloadGuard(true, (typeof kingImageOptimizer !== 'undefined' && kingImageOptimizer.strings && kingImageOptimizer.strings.leaveWarning)
                        ? kingImageOptimizer.strings.leaveWarning
                        : 'Restore is running. Are you sure you want to leave this page?');

                    // Show restore progress UI
                    showRestoreProgress();
                    processNextRestore();
                } else {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-undo"></span> Restore All Originals');
                    setUnloadGuard(false);
                    if (response.data.total === 0) {
                        showNotification('No optimized images found to restore.', 'info');
                    }
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-undo"></span> Restore All Originals');
                setUnloadGuard(false);
                showNotification('Failed to get optimized images list.', 'error');
            }
        });
    }

    function showRestoreProgress() {
        $('#restore-all-progress').show();
        $('#restore-current-filename').text('Preparing...');
        updateRestoreProgress();
    }

    function updateRestoreProgress() {
        const processed = restoreSuccess + restoreErrors;
        const percent = restoreTotal > 0 ? Math.round((processed / restoreTotal) * 100) : 0;

        $('#restore-progress-fill').css('width', percent + '%');
        $('#restore-progress-percent').text(percent + '%');
        $('#restore-progress-count').text(processed + ' / ' + restoreTotal);

        if (restoreLastId) {
            $('#restore-current-filename').text('Restoring media id ' + restoreLastId + '...');
        } else {
            $('#restore-current-filename').text('Restoring...');
        }
    }

    function processNextRestore() {
        if (!isRestoring || restoreQueue.length === 0) {
            finishBulkRestore();
            return;
        }

        const attachmentId = restoreQueue.shift();
        restoreLastId = attachmentId;
        updateRestoreProgress();

        $.ajax({
            url: kingImageOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_img_bulk_restore_single',
                nonce: kingImageOptimizer.nonce,
                attachment_id: attachmentId,
            },
            success: function(response) {
                if (response.success) {
                    restoreSuccess++;
                } else {
                    restoreErrors++;
                }
                updateRestoreProgress();
                processNextRestore();
            },
            error: function() {
                restoreErrors++;
                updateRestoreProgress();
                processNextRestore();
            }
        });
    }

    function finishBulkRestore() {
        isRestoring = false;
        restoreLastId = 0;
        setUnloadGuard(false);
        updateRestoreProgress();

        // Show a clear completion state
        $('#restore-current-filename').text('Done');
        
        const $btn = $('#restore-all');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-yes-alt"></span> Restore Complete');
        
        // Show completion notification
        showNotification(
            `Restore complete! ${restoreSuccess} images restored, ${restoreErrors} errors.`,
            restoreErrors > 0 ? 'warning' : 'success'
        );
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        init();
    });

})(jQuery);
