(function ($) {
	'use strict';

	function setActiveTab(tab) {
		$('.ka-ai-seo-tools-wrap .ka-tab').removeClass('active');
		$('.ka-ai-seo-tools-wrap .ka-tab[data-tab="' + tab + '"]').addClass('active');
		$('.ka-ai-seo-tools-wrap .ka-tab-content').removeClass('active');
		$('.ka-ai-seo-tools-wrap .ka-tab-content[data-tab="' + tab + '"]').addClass('active');
	}

	function buildDonutSvg(goodPct, goodColor, badColor) {
		const R = 44;
		const C = 2 * Math.PI * R;
		const goodLen = (goodPct / 100) * C;
		const badLen  = C - goodLen;
		const sz = (R + 14) * 2;
		const cx = sz / 2;
		return '<svg width="' + sz + '" height="' + sz + '" viewBox="0 0 ' + sz + ' ' + sz + '" class="ka-stats-donut-svg">' +
			'<circle cx="' + cx + '" cy="' + cx + '" r="' + R + '" fill="none" stroke="' + badColor + '" stroke-width="10" stroke-dasharray="' + C + '" />' +
			'<circle cx="' + cx + '" cy="' + cx + '" r="' + R + '" fill="none" stroke="' + goodColor + '" stroke-width="10"' +
				' stroke-dasharray="' + goodLen.toFixed(2) + ' ' + badLen.toFixed(2) + '"' +
				' stroke-linecap="round" transform="rotate(-90 ' + cx + ' ' + cx + ')" />' +
			'</svg>';
	}

	function buildStatsHtml(opts) {
		const goodPct  = opts.total ? parseFloat(((opts.good / opts.total) * 100).toFixed(1)) : 0;
		const badPct   = opts.total ? parseFloat(((opts.bad  / opts.total) * 100).toFixed(1)) : 0;
		const donut    = buildDonutSvg(goodPct, opts.goodColor, opts.badColor);

		return '<div class="ka-stats-infographic">' +
			'<div class="ka-stats-donut-wrap">' +
				donut +
				'<div class="ka-stats-donut-center"><span class="ka-stats-donut-num">' + goodPct.toFixed(0) + '</span><span class="ka-stats-donut-unit">%</span><span class="ka-stats-donut-sub">' + opts.goodLabel + '</span></div>' +
			'</div>' +
			'<div class="ka-stats-pills">' +
				'<div class="ka-stats-pill">' +
					'<span class="ka-stats-pill-dot" style="background:#a0a0a0"></span>' +
					'<span class="ka-stats-pill-val">' + opts.total + '</span>' +
					'<span class="ka-stats-pill-lbl">' + opts.totalLabel + '</span>' +
				'</div>' +
				'<div class="ka-stats-pill">' +
					'<span class="ka-stats-pill-dot" style="background:' + opts.goodColor + '"></span>' +
					'<span class="ka-stats-pill-val">' + opts.good + '</span>' +
					'<span class="ka-stats-pill-lbl">' + opts.goodLabel + '</span>' +
					'<span class="ka-stats-pill-pct">' + goodPct.toFixed(1) + '%</span>' +
				'</div>' +
				'<div class="ka-stats-pill">' +
					'<span class="ka-stats-pill-dot" style="background:' + opts.badColor + '"></span>' +
					'<span class="ka-stats-pill-val">' + opts.bad + '</span>' +
					'<span class="ka-stats-pill-lbl">' + opts.badLabel + '</span>' +
					'<span class="ka-stats-pill-pct">' + badPct.toFixed(1) + '%</span>' +
				'</div>' +
			'</div>' +
		'</div>';
	}

	function renderAltStats() {
		const $stats = $('#ka-ai-seo-alt-stats');
		if (!$stats.length) {
			return;
		}

		const total      = parseInt($stats.data('total') || 0, 10);
		const withAlt    = parseInt($stats.data('with-alt') || 0, 10);
		const withoutAlt = parseInt($stats.data('without-alt') || 0, 10);

		$stats.html(buildStatsHtml({
			total:      total,
			good:       withAlt,
			bad:        withoutAlt,
			totalLabel: 'Total Images',
			goodLabel:  'With Alt Text',
			badLabel:   'Without Alt Text',
			goodColor:  '#30d158',
			badColor:   '#ff453a',
		}));
	}

	function renderTagStats() {
		const $stats = $('#ka-ai-seo-tags-stats');
		if (!$stats.length) {
			return;
		}

		const total       = parseInt($stats.data('total') || 0, 10);
		const withTags    = parseInt($stats.data('with-tags') || 0, 10);
		const withoutTags = parseInt($stats.data('without-tags') || 0, 10);

		$stats.html(buildStatsHtml({
			total:      total,
			good:       withTags,
			bad:        withoutTags,
			totalLabel: 'Total Published Posts',
			goodLabel:  'With Tags',
			badLabel:   'Without Tags',
			goodColor:  '#0071e3',
			badColor:   '#ff9f0a',
		}));
	}

	function escHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function getApiErrorHints(msg) {
		if (!msg) {
			return '';
		}
		const lower = msg.toLowerCase();
		const settingsUrl = (kingAddonsAiSeoTools && kingAddonsAiSeoTools.settingsUrl) ? kingAddonsAiSeoTools.settingsUrl : '#';
		if (lower.includes('quota') || lower.includes('insufficient') || lower.includes('billing') || lower.includes('exceed') || lower.includes('credit')) {
			return '<br><span class="ka-api-hint">💡 Your OpenAI account may have run out of credits. <a href="https://platform.openai.com/usage" target="_blank">Check usage →</a></span>';
		}
		if (lower.includes('invalid api key') || lower.includes('incorrect api key') || lower.includes('no api key') || lower.includes('authentication') || lower.includes('unauthorized')) {
			return '<br><span class="ka-api-hint">💡 Check that your API key is correct. <a href="' + settingsUrl + '">Open AI Settings →</a></span>';
		}
		if (lower.includes('rate limit') || lower.includes('too many request') || lower.includes('rate_limit')) {
			return '<br><span class="ka-api-hint">💡 Rate limit exceeded — wait a moment and try again.</span>';
		}
		if (lower.includes('timeout') || lower.includes('curl error') || lower.includes('could not resolve')) {
			return '<br><span class="ka-api-hint">💡 Connection error — check your server\'s internet access.</span>';
		}
		return '';
	}

	function runBulk(cfg) {
		let timer = null;

		const $start = $(cfg.start);
		const $stop = $(cfg.stop);
		const $limit = $(cfg.limit);
		const $progress = $(cfg.progress);
		const $bar = $(cfg.bar);
		const $text = $(cfg.text);
		const $spinner = $(cfg.spinner);
		const $details = cfg.details ? $(cfg.details) : $();
		const $errorBox = $('<div class="ka-bulk-error-box" hidden></div>').insertAfter($spinner);

		function renderDetails(status, currentItem, lastSuccess, lastError) {
			if (!$details.length) {
				return;
			}

			const parts = [];

			if (status === 'running' && currentItem && currentItem.id) {
				parts.push(
					'<div class="ka-bulk-details-row">' +
						(currentItem.thumb_url ? '<img src="' + escHtml(currentItem.thumb_url) + '" alt="" class="ka-bulk-thumb">' : '') +
						'<div><strong>' + escHtml(cfg.currentLabel || 'Now') + ':</strong> #' + escHtml(currentItem.id) +
						(currentItem.filename ? ' · ' + escHtml(currentItem.filename) : '') +
						(currentItem.title ? '<br>' + escHtml(currentItem.title) : '') +
						'</div>' +
					'</div>'
				);
			}

			if (lastSuccess && lastSuccess.id) {
				const resultText = lastSuccess.result_text || lastSuccess.alt_text || '';
				const editUrl = lastSuccess.edit_url ? String(lastSuccess.edit_url).replace(/[<>"]/g, '') : '';
				const editLink = editUrl ? ' <a href="' + editUrl + '" target="_blank" class="ka-postgen-edit-link">Edit →</a>' : '';
				parts.push(
					'<div class="ka-bulk-details-row">' +
						(lastSuccess.thumb_url ? '<img src="' + escHtml(lastSuccess.thumb_url) + '" alt="" class="ka-bulk-thumb">' : '') +
						'<div><strong>' + escHtml(cfg.lastSuccessLabel || 'Last added') + ':</strong> #' + escHtml(lastSuccess.id) +
						(lastSuccess.filename ? ' · ' + escHtml(lastSuccess.filename) : '') +
						(lastSuccess.title ? '<br>' + escHtml(lastSuccess.title) : '') +
						(resultText ? '<br>' + escHtml(resultText) : '') +
						editLink +
						'</div>' +
					'</div>'
				);
			}

			if (lastError && lastError.id) {
				const errorHints = lastError.message ? getApiErrorHints(lastError.message) : '';
				parts.push(
					'<div class="ka-bulk-details-row">' +
						'<div><strong>Last error:</strong> #' + escHtml(lastError.id) +
						(lastError.message ? '<br>' + escHtml(lastError.message) : '') +
						errorHints +
						'</div>' +
					'</div>'
				);
			}

			if (parts.length) {
				$details.html(parts.join('')).prop('hidden', false);
			} else {
				$details.empty().prop('hidden', true);
			}
		}

		function setRunningState(isRunning) {
			if (isRunning) {
				$start.prop('hidden', true).prop('disabled', true);
				$stop.prop('hidden', false).prop('disabled', false);
				$spinner.prop('hidden', false).addClass('is-active');
			} else {
				$start.prop('hidden', false).prop('disabled', false);
				$stop.prop('hidden', true).prop('disabled', false);
				$spinner.removeClass('is-active').prop('hidden', true);
			}
		}

		function startPolling() {
			if (timer) {
				return;
			}
			timer = setInterval(fetchStatus, 3000);
		}

		function stopPolling() {
			if (!timer) {
				return;
			}
			clearInterval(timer);
			timer = null;
		}

		function progressText(status, done, total, currentId, errorsCount) {
			if (status === 'running') {
				let message = (kingAddonsAiSeoTools.i18n.processed || 'Processed %1$d of %2$d')
					.replace('%1$d', done)
					.replace('%2$d', total);

				if (currentId) {
					message += ' • now processing ID #' + currentId;
				}
				if (errorsCount > 0) {
					message += ' • errors: ' + errorsCount;
				}

				return message;
			}

			if (status === 'complete') {
				return kingAddonsAiSeoTools.i18n.completed || 'Completed';
			}
			if (status === 'stopped') {
				return kingAddonsAiSeoTools.i18n.stopped || 'Stopped';
			}
			if (status === 'error') {
				return kingAddonsAiSeoTools.i18n.error || 'Error';
			}
			return '';
		}

		function applyStatus(payload) {
			const status = String(payload && payload.status ? payload.status : 'idle');
			const total = parseInt(payload && payload.total ? payload.total : 0, 10);
			const done = parseInt(payload && payload.processed ? payload.processed : 0, 10);
			const currentId = payload && payload.current_id ? parseInt(payload.current_id, 10) : null;
			const currentItem = payload && payload.current_item && typeof payload.current_item === 'object' ? payload.current_item : null;
			const lastSuccess = payload && payload.last_success && typeof payload.last_success === 'object' ? payload.last_success : null;
			const errors = (payload && payload.errors && typeof payload.errors === 'object') ? payload.errors : {};
			const errorsCount = Object.keys(errors).length;
			const errorKeys = Object.keys(errors);
			const lastError = errorKeys.length ? {
				id: errorKeys[errorKeys.length - 1],
				message: errors[errorKeys[errorKeys.length - 1]],
			} : null;

			let percent = total > 0 ? Math.max(0, Math.min(100, Math.round((done / total) * 100))) : 0;
			if (total > 0 && done > 0 && percent === 0) {
				percent = 1;
			}
			const hasProgress = total > 0 || done > 0 || status === 'running' || status === 'complete' || status === 'stopped';

			if (hasProgress) {
				$progress.prop('hidden', false);
			} else {
				$progress.prop('hidden', true);
			}

			$bar.css('width', percent + '%').text(percent + '%');
			$text.text(progressText(status, done, total, currentId, errorsCount));
			renderDetails(status, currentItem, lastSuccess, lastError);

			if (typeof cfg.renderExtra === 'function') {
				cfg.renderExtra(payload);
			}

			if (status === 'running') {
				setRunningState(true);
				startPolling();
				if (typeof cfg.onStateChange === 'function') {
					cfg.onStateChange(true, cfg.processKey || cfg.startAction);
				}
				return;
			}

			setRunningState(false);
			stopPolling();
			if (typeof cfg.onStateChange === 'function') {
				cfg.onStateChange(false, cfg.processKey || cfg.startAction);
			}
		}

		function fetchStatus() {
			$.post(
				kingAddonsAiSeoTools.ajaxUrl,
				{
					action: cfg.statusAction,
					nonce: cfg.statusNonce,
				}
			).done(function (response) {
				if (response && response.success) {
					applyStatus(response.data || {});
				}
			});
		}

		$start.on('click', function () {
			const limit = parseInt($limit.val() || 0, 10);

			$start.prop('disabled', true);
			$errorBox.prop('hidden', true).html('');

			const extraData = typeof cfg.getStartExtra === 'function' ? cfg.getStartExtra() : {};

			$.post(
				kingAddonsAiSeoTools.ajaxUrl,
				Object.assign({
					action: cfg.startAction,
					nonce: cfg.startNonce,
					limit: limit,
				}, extraData)
			).done(function (response) {
				if (response && response.success) {
					applyStatus(response.data || {});
				} else {
					const errData = (response && response.data) || {};
					const errMsg  = errData.message || 'An error occurred.';
					const isNoKey = errData.code === 'no_api_key';
					const settingsUrl = (kingAddonsAiSeoTools && kingAddonsAiSeoTools.settingsUrl) ? kingAddonsAiSeoTools.settingsUrl : '';
					let html = (isNoKey ? '⚠️ ' : '❌ ') + escHtml(errMsg);
					if (settingsUrl) {
						html += ' &nbsp;<a href="' + settingsUrl + '" class="ka-bulk-error-link">Open AI Settings →</a>';
					}
					if (!isNoKey) {
						html += getApiErrorHints(errMsg);
					}
					$errorBox
						.toggleClass('ka-bulk-error-box--warn', isNoKey)
						.html(html)
						.prop('hidden', false);
					setRunningState(false);
				}
			}).fail(function () {
				$errorBox.removeClass('ka-bulk-error-box--warn').html('❌ Request failed. Please check your connection.').prop('hidden', false);
				setRunningState(false);
			});
		});

		$stop.on('click', function () {
			$stop.prop('disabled', true);

			$.post(
				kingAddonsAiSeoTools.ajaxUrl,
				{
					action: cfg.stopAction,
					nonce: cfg.stopNonce,
				}
			).done(function (response) {
				if (response && response.success) {
					applyStatus(response.data || {});
				} else {
					$stop.prop('disabled', false);
				}
			}).fail(function () {
				$stop.prop('disabled', false);
			});
		});

		$stop.prop('hidden', true);
		fetchStatus();
	}

	$(function () {
		const runningMap = {};
		const $bgNotice = $('#ka-ai-seo-background-notice');

		// ── Theme segmented control ──────────────────────────────────────────
		(function () {
			var $segment = $('#ka-v3-theme-segment');
			if (!$segment.length) { return; }
			var $buttons = $segment.find('.ka-v3-segmented-btn');
			var themeMql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
			var themeMode = ($segment.attr('data-active') || 'dark').toString();
			var themeMqlHandler = null;

			function applyThemeClass(isDark) {
				$('body').toggleClass('ka-v3-dark', isDark);
				document.documentElement.classList.toggle('ka-v3-dark', isDark);
			}

			function updateSegment(mode) {
				$segment.attr('data-active', mode);
				$buttons.each(function () {
					$(this).attr('aria-pressed', $(this).data('theme') === mode ? 'true' : 'false');
				});
			}

			function setThemeMode(mode, save) {
				themeMode = mode;
				updateSegment(mode);

				if (themeMqlHandler && themeMql) {
					if (themeMql.removeEventListener) {
						themeMql.removeEventListener('change', themeMqlHandler);
					} else if (themeMql.removeListener) {
						themeMql.removeListener(themeMqlHandler);
					}
					themeMqlHandler = null;
				}

				if (mode === 'auto') {
					applyThemeClass(!!(themeMql && themeMql.matches));
					themeMqlHandler = function (e) {
						if (themeMode !== 'auto') { return; }
						applyThemeClass(!!e.matches);
					};
					if (themeMql) {
						if (themeMql.addEventListener) {
							themeMql.addEventListener('change', themeMqlHandler);
						} else if (themeMql.addListener) {
							themeMql.addListener(themeMqlHandler);
						}
					}
				} else {
					applyThemeClass(mode === 'dark');
				}

				if (save && kingAddonsAiSeoTools && kingAddonsAiSeoTools.dashboardUiNonce) {
					$.post(kingAddonsAiSeoTools.ajaxUrl, {
						action: 'king_addons_save_dashboard_ui',
						nonce: kingAddonsAiSeoTools.dashboardUiNonce,
						key: 'theme_mode',
						value: mode,
					});
				}
			}

			$segment.on('click', '.ka-v3-segmented-btn', function (e) {
				e.preventDefault();
				var mode = ($(this).data('theme') || 'dark').toString();
				setThemeMode(mode, true);
			});

			setThemeMode(themeMode, false);
		}());
		// ─────────────────────────────────────────────────────────────────────

		function updateBackgroundNotice() {
			const activeCount = Object.keys(runningMap).filter(function (key) {
				return runningMap[key] === true;
			}).length;

			if (!$bgNotice.length) {
				return;
			}

			$bgNotice.prop('hidden', activeCount === 0);
		}

		function setProcessRunning(isRunning, processKey) {
			runningMap[processKey] = !!isRunning;
			updateBackgroundNotice();
		}

		$('.ka-ai-seo-tools-wrap .ka-tab').on('click', function () {
			setActiveTab($(this).data('tab'));
		});

		// Settings tab — save auto tagging settings.
		$('#ka-seo-settings-save').on('click', function () {
			const $btn = $(this);
			const $fb  = $('#ka-seo-settings-feedback');
			$btn.prop('disabled', true);
			$fb.prop('hidden', true).removeClass('ka-seo-settings-feedback--ok ka-seo-settings-feedback--err');

			$.post(kingAddonsAiSeoTools.ajaxUrl, {
				action: 'king_addons_ai_seo_save_settings',
				nonce: kingAddonsAiSeoTools.nonces.settingsSave,
				auto_tagging_max_tags: $('#ka-seo-max-tags').val(),
				auto_tagging_confidence_threshold: $('#ka-seo-confidence').val(),
				auto_tagging_stop_words: $('#ka-seo-stop-words').val(),
			}).done(function (response) {
				if (response && response.success) {
					$fb.addClass('ka-seo-settings-feedback--ok').text('✓ ' + (response.data.message || 'Saved.')).prop('hidden', false);
				} else {
					$fb.addClass('ka-seo-settings-feedback--err').text('✗ ' + ((response.data && response.data.message) || 'Error saving.')).prop('hidden', false);
				}
			}).fail(function () {
				$fb.addClass('ka-seo-settings-feedback--err').text('✗ Request failed.').prop('hidden', false);
			}).always(function () {
				$btn.prop('disabled', false);
				setTimeout(function () { $fb.prop('hidden', true); }, 4000);
			});
		});

		renderAltStats();
		renderTagStats();

		runBulk({
			start: '#ka-ai-seo-alt-start',
			stop: '#ka-ai-seo-alt-stop',
			spinner: '#ka-ai-seo-alt-spinner',
			details: '#ka-ai-seo-alt-details',
			currentLabel: 'Now processing image',
			lastSuccessLabel: 'Last added alt text',
			processKey: 'alt',
			onStateChange: setProcessRunning,
			limit: '#ka-ai-seo-alt-limit',
			progress: '#ka-ai-seo-alt-progress',
			bar: '#ka-ai-seo-alt-progress-bar',
			text: '#ka-ai-seo-alt-progress-text',
			startAction: 'king_addons_ai_seo_start_bulk_alt',
			statusAction: 'king_addons_ai_seo_get_bulk_alt_status',
			stopAction: 'king_addons_ai_seo_stop_bulk_alt',
			startNonce: kingAddonsAiSeoTools.nonces.altStart,
			statusNonce: kingAddonsAiSeoTools.nonces.altStatus,
			stopNonce: kingAddonsAiSeoTools.nonces.altStop,
		});

		runBulk({
			start: '#ka-ai-seo-tags-start',
			stop: '#ka-ai-seo-tags-stop',
			spinner: '#ka-ai-seo-tags-spinner',
			details: '#ka-ai-seo-tags-details',
			currentLabel: 'Now processing post',
			lastSuccessLabel: 'Last updated tags',
			processKey: 'tags',
			onStateChange: setProcessRunning,
			limit: '#ka-ai-seo-tags-limit',
			progress: '#ka-ai-seo-tags-progress',
			bar: '#ka-ai-seo-tags-progress-bar',
			text: '#ka-ai-seo-tags-progress-text',
			startAction: 'king_addons_ai_seo_start_bulk_tags',
			statusAction: 'king_addons_ai_seo_get_bulk_tags_status',
			stopAction: 'king_addons_ai_seo_stop_bulk_tags',
			startNonce: kingAddonsAiSeoTools.nonces.tagsStart,
			statusNonce: kingAddonsAiSeoTools.nonces.tagsStatus,
			stopNonce: kingAddonsAiSeoTools.nonces.tagsStop,
		});

		runBulk({
			start: '#ka-ai-seo-append-start',
			stop: '#ka-ai-seo-append-stop',
			spinner: '#ka-ai-seo-append-spinner',
			details: '#ka-ai-seo-append-details',
			currentLabel: 'Now processing post',
			lastSuccessLabel: 'Last appended tags',
			processKey: 'append',
			onStateChange: setProcessRunning,
			limit: '#ka-ai-seo-append-limit',
			progress: '#ka-ai-seo-append-progress',
			bar: '#ka-ai-seo-append-progress-bar',
			text: '#ka-ai-seo-append-progress-text',
			startAction: 'king_addons_ai_seo_start_bulk_append_tags',
			statusAction: 'king_addons_ai_seo_get_bulk_append_tags_status',
			stopAction: 'king_addons_ai_seo_stop_bulk_append_tags',
			startNonce: kingAddonsAiSeoTools.nonces.appendStart,
			statusNonce: kingAddonsAiSeoTools.nonces.appendStatus,
			stopNonce: kingAddonsAiSeoTools.nonces.appendStop,
		});

		runBulk({
			start: '#ka-ai-seo-regen-start',
			stop: '#ka-ai-seo-regen-stop',
			spinner: '#ka-ai-seo-regen-spinner',
			details: '#ka-ai-seo-regen-details',
			currentLabel: 'Now processing post',
			lastSuccessLabel: 'Last regenerated tags',
			processKey: 'regen',
			onStateChange: setProcessRunning,
			limit: '#ka-ai-seo-regen-limit',
			progress: '#ka-ai-seo-regen-progress',
			bar: '#ka-ai-seo-regen-progress-bar',
			text: '#ka-ai-seo-regen-progress-text',
			startAction: 'king_addons_ai_seo_start_bulk_regenerate_tags',
			statusAction: 'king_addons_ai_seo_get_bulk_regenerate_tags_status',
			stopAction: 'king_addons_ai_seo_stop_bulk_regenerate_tags',
			startNonce: kingAddonsAiSeoTools.nonces.regenStart,
			statusNonce: kingAddonsAiSeoTools.nonces.regenStatus,
			stopNonce: kingAddonsAiSeoTools.nonces.regenStop,
		});

		// Post Generator — length toggle.
		$(document).on('click', '.ka-postgen-length-btn', function () {
			$('.ka-postgen-length-btn').removeClass('active');
			$(this).addClass('active');
			$('#ka-postgen-length').val($(this).data('value'));
		});

		// Post Generator — image settings toggles.
		$('#ka-postgen-gen-image').on('change', function () {
			$('#ka-postgen-image-settings').prop('hidden', !this.checked);
		});

		$('#ka-postgen-image-model').on('change', function () {
			const isGpt = $(this).val() === 'gpt-image-1';
			$('#ka-postgen-dalle3-opts').prop('hidden', isGpt);
			$('#ka-postgen-gptimg-opts').prop('hidden', !isGpt);
		});

		runBulk({
			start: '#ka-postgen-start',
			stop: '#ka-postgen-stop',
			spinner: '#ka-postgen-spinner',
			details: '#ka-postgen-details',
			currentLabel: 'Generating post',
			lastSuccessLabel: 'Last created post',
			processKey: 'postgen',
			onStateChange: setProcessRunning,
			progress: '#ka-postgen-progress',
			bar: '#ka-postgen-progress-bar',
			text: '#ka-postgen-progress-text',			renderExtra: function (payload) {
				const $info = $('#ka-postgen-run-info');
				if (!$info.length) {
					return;
				}
				const settings = payload && payload.settings && typeof payload.settings === 'object' ? payload.settings : null;
				const runStatus = String(payload && payload.status ? payload.status : 'idle');
				const currentItem = payload && payload.current_item && typeof payload.current_item === 'object' ? payload.current_item : null;

				if ((runStatus === 'running' || runStatus === 'complete' || runStatus === 'stopped') && settings) {
					const lengthMap = { short: 'Short (~300 words)', medium: 'Medium (~600 words)', long: 'Long (~1200 words)' };
					const statusLabelMap = { draft: 'Draft', publish: 'Published' };

					let catLabel = '✨ Auto-generate';
					if (settings.category_id === '0') {
						catLabel = 'None';
					} else if (settings.category_id && settings.category_id !== 'auto') {
						const $opt = $('#ka-postgen-category option[value="' + escHtml(settings.category_id) + '"]');
						catLabel = $opt.length ? $opt.text() : '#' + settings.category_id;
					}

					let html = '<div class="ka-postgen-run-settings">' +
						'<div class="ka-postgen-run-settings-title">⚙️ Run Settings</div>' +
						'<div class="ka-postgen-run-settings-rows">' +
						'<div class="ka-postgen-run-setting-row"><span class="ka-postgen-run-setting-key">Topic</span><span class="ka-postgen-run-setting-val">' + escHtml(settings.description || '') + '</span></div>' +
						'<div class="ka-postgen-run-setting-row"><span class="ka-postgen-run-setting-key">Length</span><span class="ka-postgen-run-setting-val">' + escHtml(lengthMap[settings.length] || settings.length) + '</span></div>' +
						'<div class="ka-postgen-run-setting-row"><span class="ka-postgen-run-setting-key">Posts</span><span class="ka-postgen-run-setting-val">' + escHtml(String(payload.total || '')) + '</span></div>' +
						'<div class="ka-postgen-run-setting-row"><span class="ka-postgen-run-setting-key">Category</span><span class="ka-postgen-run-setting-val">' + escHtml(catLabel) + '</span></div>' +
						'<div class="ka-postgen-run-setting-row"><span class="ka-postgen-run-setting-key">Post Status</span><span class="ka-postgen-run-setting-val">' + escHtml(statusLabelMap[settings.post_status] || settings.post_status) + '</span></div>';

					if (settings.generate_image) {
						html += '<div class="ka-postgen-run-setting-row"><span class="ka-postgen-run-setting-key">Image Model</span><span class="ka-postgen-run-setting-val">' + escHtml(settings.image_model + ' · ' + settings.image_quality + ' · ' + settings.image_size) + '</span></div>';
					}

					html += '</div>';

					const prompt = currentItem && currentItem.prompt ? currentItem.prompt : '';
					if (prompt) {
						html += '<details class="ka-postgen-prompt-details" open>' +
							'<summary>🤖 OpenAI Prompt</summary>' +
							'<pre class="ka-postgen-prompt-pre">' + escHtml(prompt) + '</pre>' +
							'</details>';
					}

					html += '</div>';
					$info.html(html).prop('hidden', false);
				} else {
					$info.empty().prop('hidden', true);
				}
			},			startAction: 'king_addons_ai_seo_start_post_gen',
			statusAction: 'king_addons_ai_seo_get_post_gen_status',
			stopAction: 'king_addons_ai_seo_stop_post_gen',
			startNonce: kingAddonsAiSeoTools.nonces.postgenStart,
			statusNonce: kingAddonsAiSeoTools.nonces.postgenStatus,
			stopNonce: kingAddonsAiSeoTools.nonces.postgenStop,
			getStartExtra: function () {
				const imageModel = $('#ka-postgen-image-model').val();
				const genImage = $('#ka-postgen-gen-image').is(':checked');
				const quality = imageModel === 'gpt-image-1'
					? $('#ka-postgen-gptimg-quality').val()
					: $('#ka-postgen-dalle3-quality').val();
				const size = imageModel === 'gpt-image-1'
					? $('#ka-postgen-gptimg-size').val()
					: $('#ka-postgen-dalle3-size').val();
				return {
					description:    $('#ka-postgen-description').val(),
					count:          parseInt($('#ka-postgen-count').val() || 1, 10),
					length:         $('#ka-postgen-length').val(),
					category_id:    $('#ka-postgen-category').val(),
					post_status:    $('#ka-postgen-status').val(),
					generate_image: genImage ? '1' : '',
					image_model:    imageModel,
					image_quality:  quality,
					image_size:     size,
				};
			},
		});
	});
})(jQuery);