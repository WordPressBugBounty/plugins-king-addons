/* global jQuery, kingImageOptimizerAttachment */
(function ($) {
  'use strict';
  // Prevent accidental tab close/navigation during upload-auto optimization
  let unloadGuardEnabled = false;
  function setUnloadGuard(enabled) {
    unloadGuardEnabled = !!enabled;
  }
  
  window.addEventListener('beforeunload', function (e) {
    if (!unloadGuardEnabled) return;
    e.preventDefault();
    e.returnValue = 'A process is running. Are you sure you want to leave this page?';
    return e.returnValue;
  });
  
  // Toast UI (simple)
  let toastHideTimer = null;
  function ensureToast() {
    let $toast = $('#king-img-upload-toast');
    if ($toast.length) return $toast;
  
    $toast = $(
      '<div id="king-img-upload-toast" class="king-img-toast" style="display:none;">' +
        '<div class="king-img-toast-head">' +
          '<span class="dashicons king-img-toast-icon dashicons-update" aria-hidden="true"></span>' +
          '<div class="king-img-toast-title">Optimizing uploads</div>' +
          '<button type="button" class="king-img-toast-close" aria-label="Close">&times;</button>' +
        '</div>' +
        '<div class="king-img-toast-body">' +
          '<div class="king-img-toast-line" id="king-img-toast-line">Preparing...</div>' +
          '<div class="king-img-toast-bar"><div class="king-img-toast-bar-fill" id="king-img-toast-bar-fill" style="width:0%"></div></div>' +
          '<div class="king-img-toast-meta" id="king-img-toast-meta">0 / 0</div>' +
        '</div>' +
      '</div>'
    );
  
    $(document.body).append($toast);
    $toast.on('click', '.king-img-toast-close', function () {
      $toast.fadeOut(200);
    });
  
    return $toast;
  }
  
  function toastUpdate({ line, current, total, percent, status }) {
    const $toast = ensureToast();
    $toast.show();

    if (toastHideTimer) {
      window.clearTimeout(toastHideTimer);
      toastHideTimer = null;
    }
  
    if (typeof line !== 'undefined') {
      $('#king-img-toast-line').text(String(line));
    }
    if (typeof current !== 'undefined' && typeof total !== 'undefined') {
      $('#king-img-toast-meta').text(String(current) + ' / ' + String(total));
    }
    if (typeof percent !== 'undefined') {
      const p = Math.max(0, Math.min(100, Number(percent) || 0));
      $('#king-img-toast-bar-fill').css('width', p + '%');
    }
  
    const $icon = $toast.find('.king-img-toast-icon');
    $toast.removeClass('is-success is-error is-warning');
    if (status === 'success') {
      $toast.addClass('is-success');
      $icon.removeClass().addClass('dashicons king-img-toast-icon dashicons-yes-alt');
      toastHideTimer = window.setTimeout(function () { $toast.fadeOut(200); }, 5000);
    } else if (status === 'error') {
      $toast.addClass('is-error');
      $icon.removeClass().addClass('dashicons king-img-toast-icon dashicons-dismiss');
      toastHideTimer = window.setTimeout(function () { $toast.fadeOut(200); }, 5000);
    } else if (status === 'warning') {
      $toast.addClass('is-warning');
      $icon.removeClass().addClass('dashicons king-img-toast-icon dashicons-warning');
      toastHideTimer = window.setTimeout(function () { $toast.fadeOut(200); }, 5000);
    } else {
      $icon.removeClass().addClass('dashicons king-img-toast-icon dashicons-update');
    }
  }

  function isMediaModalOpen() {
    try {
      return $('.media-modal:visible').length > 0;
    } catch (e) {
      return false;
    }
  }

  async function refreshAttachmentOptimizerCard(attachmentId) {
    const id = Number(attachmentId || 0);
    if (!id) {
      return;
    }

    try {
      const res = await postAjax({
        action: 'king_img_get_attachment_card_html',
        attachment_id: id,
      });

      if (!res || !res.success || !res.data || !res.data.html) {
        return;
      }

      const $cards = $('.king-img-attach-card[data-king-img-attachment-id="' + String(id) + '"]');
      if ($cards.length) {
        $cards.each(function () {
          $(this).replaceWith(res.data.html);
        });
      }
    } catch (e) {
      // ignore
    }
  }

  function getAllBlocksDeep(blocks) {
    const out = [];
    const walk = function (arr) {
      (arr || []).forEach(function (b) {
        if (!b) return;
        out.push(b);
        if (b.innerBlocks && b.innerBlocks.length) {
          walk(b.innerBlocks);
        }
      });
    };
    walk(blocks || []);
    return out;
  }

  async function refreshGutenbergImageBlocksForAttachment(attachmentId) {
    const id = Number(attachmentId || 0);
    if (!id) return;

    if (!(window.wp && wp.data)) {
      return;
    }

    // Only relevant in the block editor.
    const blockEditor = wp.data.select('core/block-editor');
    const blockEditorDispatch = wp.data.dispatch('core/block-editor');
    if (!blockEditor || !blockEditorDispatch) {
      return;
    }

    let optimizedUrl = '';

    // Prefer REST (same source Gutenberg uses for entities).
    try {
      if (wp.apiFetch) {
        const media = await wp.apiFetch({ path: '/wp/v2/media/' + String(id) });
        if (media && media.source_url) {
          optimizedUrl = String(media.source_url);
        }
      }
    } catch (e) {
      // ignore
    }

    // Fallback to wp.media model.
    if (!optimizedUrl) {
      try {
        if (wp.media && wp.media.attachment) {
          const model = wp.media.attachment(id);
          // Ensure freshest data.
          if (model && model.fetch) {
            await new Promise((resolve) => {
              model.fetch({
                success: function () { resolve(); },
                error: function () { resolve(); },
              });
            });
          }
          optimizedUrl = String((model && model.get && (model.get('url') || model.get('source_url'))) || '');
        }
      } catch (e) {
        // ignore
      }
    }

    if (!optimizedUrl) {
      return;
    }

    // Update any core/image blocks using this attachment ID.
    try {
      const blocks = getAllBlocksDeep(blockEditor.getBlocks());
      blocks.forEach(function (b) {
        if (!b || !b.clientId) return;
        if (b.name !== 'core/image') return;
        const bid = Number((b.attributes && b.attributes.id) || 0);
        if (bid !== id) return;

        blockEditorDispatch.updateBlockAttributes(b.clientId, {
          url: optimizedUrl,
        });
      });
    } catch (e) {
      // ignore
    }
  }

  function postAjax(data) {
    return $.ajax({
      url: kingImageOptimizerAttachment.ajaxUrl,
      method: 'POST',
      dataType: 'json',
      data: Object.assign({ nonce: kingImageOptimizerAttachment.nonce }, data),
    });
  }

  async function refreshSettingsFromServer() {
    try {
      const res = await postAjax({
        action: 'king_img_get_settings',
      });
      if (res && res.success && res.data && res.data.settings) {
        kingImageOptimizerAttachment.settings = res.data.settings;
      }
    } catch (e) {
      // ignore
    }
  }

  function setBusy($card, busy) {
    $card.toggleClass('is-busy', !!busy);
    $card.find('.king-img-attachment-action').prop('disabled', !!busy);
  }

  function setMsg($card, msg) {
    $card.find('.king-img-attach-msg').text(msg || '');
  }

  function dataUrlFromBlob(blob) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('Failed to read blob'));
      reader.readAsDataURL(blob);
    });
  }

  function loadImage(url) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error('Failed to load image'));
      img.crossOrigin = 'anonymous';
      img.src = url;
    });
  }

  async function convertOneSizeToWebp(imageInfo, quality, resizeEnabled, maxWidth) {
    const img = await loadImage(imageInfo.url);

    const srcW = img.naturalWidth || imageInfo.width || 0;
    const srcH = img.naturalHeight || imageInfo.height || 0;

    let outW = srcW;
    let outH = srcH;

    const mw = Number(maxWidth || 0);
    if (resizeEnabled && mw > 0 && srcW > mw) {
      const scale = mw / srcW;
      outW = mw;
      outH = Math.max(1, Math.round(srcH * scale));
    }

    const canvas = document.createElement('canvas');
    canvas.width = outW;
    canvas.height = outH;

    const ctx = canvas.getContext('2d', { alpha: true });
    ctx.drawImage(img, 0, 0, outW, outH);

    const blob = await new Promise((resolve, reject) => {
      canvas.toBlob(
        (b) => (b ? resolve(b) : reject(new Error('toBlob returned null'))),
        'image/webp',
        quality
      );
    });

    const dataUrl = await dataUrlFromBlob(blob);

    return {
      dataUrl,
      optimizedSize: blob.size,
      originalSize: Number(imageInfo.filesize || 0),
    };
  }

  async function handleConvert($card) {
    const attachmentId = Number($card.data('king-img-attachment-id'));
    await refreshSettingsFromServer();
    const settings = kingImageOptimizerAttachment.settings || {};

    const qualityPct = Number(settings.quality || 82);
    const quality = Math.max(0.1, Math.min(1, qualityPct / 100));

    const skipSmall = !!settings.skip_small;
    const minSize = Number(settings.min_size || 10240);

    const resizeEnabled = !!settings.resize_enabled;
    const maxWidth = Number(settings.max_width || 2048);

    setBusy($card, true);
    setMsg($card, kingImageOptimizerAttachment.strings.fetching || 'Fetching image data...');

    const res = await postAjax({
      action: 'king_img_get_image_data',
      attachment_id: attachmentId,
      sizes: 'all',
    });

    if (!res || !res.success) {
      throw new Error((res && res.data && res.data.message) || 'Failed to fetch image data');
    }

    const images = (res.data && res.data.images) || {};
    const sizeNames = Object.keys(images);
    if (!sizeNames.length) {
      throw new Error('No image sizes found');
    }

    let converted = 0;
    let skipped = 0;

    for (let i = 0; i < sizeNames.length; i++) {
      const size = sizeNames[i];
      const info = images[size];

      if (skipSmall && Number(info.filesize || 0) > 0 && Number(info.filesize || 0) < minSize) {
        skipped++;
        continue;
      }

      setMsg(
        $card,
        (kingImageOptimizerAttachment.strings.converting || 'Converting...') +
          ' ' +
          size +
          ' (' +
          String(i + 1) +
          '/' +
          String(sizeNames.length) +
          ')'
      );

      const out = await convertOneSizeToWebp(info, quality, resizeEnabled, maxWidth);

      const saveRes = await postAjax({
        action: 'king_img_save_optimized',
        attachment_id: attachmentId,
        size: size,
        format: 'webp',
        method: 'canvas',
        image_data: out.dataUrl,
        original_size: out.originalSize,
        optimized_size: out.optimizedSize,
      });

      if (!saveRes || !saveRes.success) {
        if (saveRes && saveRes.data && saveRes.data.code === 'quota_exceeded') {
          setMsg(
            $card,
            kingImageOptimizerAttachment.strings.quotaExceeded ||
              'Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.'
          );
          setBusy($card, false);
          return;
        }
        throw new Error((saveRes && saveRes.data && saveRes.data.message) || 'Failed to save optimized image');
      }

      converted++;
    }

    if (converted === 0 && skipped > 0) {
      // Optional: persist skipped state so it doesn't show as pending elsewhere.
      await postAjax({
        action: 'king_img_mark_skipped',
        attachment_id: attachmentId,
        reason: 'too_small',
      }).catch(() => {});

      setMsg($card, kingImageOptimizerAttachment.strings.skipped || 'Skipped (too small)');
      setBusy($card, false);
      return;
    }

    if (isMediaModalOpen()) {
      setMsg($card, kingImageOptimizerAttachment.strings.optimized || 'Optimized');
      await refreshAttachmentOptimizerCard(attachmentId);
      await refreshGutenbergImageBlocksForAttachment(attachmentId);
      try {
        if (window.wp && wp.media && wp.media.attachment) {
          wp.media.attachment(attachmentId).fetch();
        }
      } catch (e) {
        // ignore
      }
      setBusy($card, false);
      return;
    }

    setMsg($card, kingImageOptimizerAttachment.strings.doneReload || 'Done. Reloading...');
    window.location.reload();
  }

  async function handleRestore($card) {
    const attachmentId = Number($card.data('king-img-attachment-id'));

    const ok = window.confirm(
      kingImageOptimizerAttachment.strings.confirmRestore || 'Restore original and delete optimized files?'
    );
    if (!ok) return;

    setBusy($card, true);
    setMsg($card, kingImageOptimizerAttachment.strings.restoring || 'Restoring...');

    const res = await postAjax({
      action: 'king_img_full_restore',
      attachment_id: attachmentId,
    });

    if (!res || !res.success) {
      throw new Error((res && res.data && res.data.message) || 'Restore failed');
    }

    if (isMediaModalOpen()) {
      await refreshAttachmentOptimizerCard(attachmentId);
      await refreshGutenbergImageBlocksForAttachment(attachmentId);
      try {
        if (window.wp && wp.media && wp.media.attachment) {
          wp.media.attachment(attachmentId).fetch();
        }
      } catch (e) {
        // ignore
      }
      setBusy($card, false);
      setMsg($card, kingImageOptimizerAttachment.strings.restored || 'Restored');
      return;
    }

    setMsg($card, kingImageOptimizerAttachment.strings.doneReload || 'Done. Reloading...');
    window.location.reload();
  }

  $(document).on('click', '.king-img-attachment-action', async function () {
    const $btn = $(this);
    const $card = $btn.closest('.king-img-attach-card');
    const action = String($btn.data('king-img-action') || '');

    try {
      if (action === 'restore') {
        await handleRestore($card);
      } else {
        await handleConvert($card);
      }
    } catch (e) {
      setBusy($card, false);
      setMsg($card, (kingImageOptimizerAttachment.strings.errorPrefix || 'Error: ') + (e && e.message ? e.message : String(e)));
    }
  });
  
  // --- Auto optimize new uploads (browser) ---
  const uploadAuto = {
    enabled: !!(kingImageOptimizerAttachment && kingImageOptimizerAttachment.settings && kingImageOptimizerAttachment.settings.auto_optimize_uploads),
    queue: [],
    queuedIds: new Set(),
    processedIds: new Set(),
    bound: false,
    running: false,
    total: 0,
    done: 0,
    errors: 0,
  };
  
  async function convertAttachmentId(attachmentId, label) {
    const settings = kingImageOptimizerAttachment.settings || {};
  
    const qualityPct = Number(settings.quality || 82);
    const quality = Math.max(0.1, Math.min(1, qualityPct / 100));
  
    const skipSmall = !!settings.skip_small;
    const minSize = Number(settings.min_size || 10240);

    const resizeEnabled = !!settings.resize_enabled;
    const maxWidth = Number(settings.max_width || 2048);
  
    const autoReplaceUrls = settings.auto_replace_urls !== false;
  
    const res = await postAjax({
      action: 'king_img_get_image_data',
      attachment_id: attachmentId,
      sizes: 'all',
    });
  
    if (!res || !res.success) {
      throw new Error((res && res.data && res.data.message) || 'Failed to fetch image data');
    }
  
    const images = (res.data && res.data.images) || {};
    const sizeNames = Object.keys(images);
    if (!sizeNames.length) {
      throw new Error('No image sizes found');
    }
  
    let converted = 0;
    let skipped = 0;
  
    for (let i = 0; i < sizeNames.length; i++) {
      const size = sizeNames[i];
      const info = images[size];
  
      if (skipSmall && Number(info.filesize || 0) > 0 && Number(info.filesize || 0) < minSize) {
        skipped++;
        continue;
      }
  
      toastUpdate({
        line: `Converting ${label} • ${size} (${i + 1}/${sizeNames.length})`,
      });
  
      const out = await convertOneSizeToWebp(info, quality, resizeEnabled, maxWidth);
  
      const saveRes = await postAjax({
        action: 'king_img_save_optimized',
        attachment_id: attachmentId,
        size: size,
        format: 'webp',
        method: 'canvas',
        image_data: out.dataUrl,
        original_size: out.originalSize,
        optimized_size: out.optimizedSize,
      });
  
      if (!saveRes || !saveRes.success) {
        if (saveRes && saveRes.data && saveRes.data.code === 'quota_exceeded') {
          setMsg(
            $card,
            kingImageOptimizerAttachment.strings.quotaExceeded ||
              'Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.'
          );
          setBusy($card, false);
          return;
        }
        throw new Error((saveRes && saveRes.data && saveRes.data.message) || 'Failed to save optimized image');
      }
  
      converted++;
    }
  
    if (converted === 0 && skipped > 0) {
      // Persist skipped state so it doesn't remain pending elsewhere.
      await postAjax({
        action: 'king_img_mark_skipped',
        attachment_id: attachmentId,
        reason: 'too_small',
      }).catch(() => {});
      return { status: 'skipped' };
    }
  
    // Optionally apply URL replacements if enabled
    if (autoReplaceUrls) {
      await postAjax({
        action: 'king_img_apply_webp_urls',
        attachment_id: attachmentId,
      }).catch(() => {});
    }
  
    // Refresh the attachment model in media library if available
    try {
      if (window.wp && wp.media && wp.media.attachment) {
        wp.media.attachment(attachmentId).fetch();
      }
    } catch (e) {
      // ignore
    }

    // Refresh the card in the modal/details UI if present
    await refreshAttachmentOptimizerCard(attachmentId);

    // Refresh Gutenberg block previews that reference this attachment
    await refreshGutenbergImageBlocksForAttachment(attachmentId);
  
    return { status: 'optimized' };
  }
  
  function enqueueUploadedImage(attachmentId, label) {
    const id = Number(attachmentId || 0);
    if (!id) return;
    if (uploadAuto.processedIds.has(id) || uploadAuto.queuedIds.has(id)) return;
  
    uploadAuto.queue.push({ id, label: String(label || ('Attachment #' + id)) });
    uploadAuto.queuedIds.add(id);
    uploadAuto.total++;
  
    if (!uploadAuto.running) {
      processUploadQueue();
    } else {
      toastUpdate({ line: 'Added to queue: ' + String(label || id), current: uploadAuto.done, total: uploadAuto.total });
    }
  }
  
  async function processUploadQueue() {
    if (uploadAuto.running) return;
    if (!uploadAuto.enabled) return;
    if (!uploadAuto.queue.length) return;
  
    uploadAuto.running = true;
    setUnloadGuard(true);

    // Ensure we use the latest saved settings (Default Quality, skip-small, etc)
    await refreshSettingsFromServer();
  
    toastUpdate({
      line: 'Starting…',
      current: uploadAuto.done,
      total: uploadAuto.total,
      percent: 0,
      status: 'running',
    });
  
    while (uploadAuto.queue.length) {
      const item = uploadAuto.queue.shift();
      const id = item.id;
  
      const currentIndex = uploadAuto.done + 1;
      const total = Math.max(uploadAuto.total, currentIndex);
      const percent = Math.round(((currentIndex - 1) / total) * 100);
      toastUpdate({ line: `Optimizing ${item.label}`, current: currentIndex - 1, total, percent });
  
      try {
        await convertAttachmentId(id, item.label);
        uploadAuto.processedIds.add(id);
      } catch (e) {
        uploadAuto.errors++;
      }
  
      uploadAuto.done++;
      const p2 = Math.round((uploadAuto.done / uploadAuto.total) * 100);
      toastUpdate({ current: uploadAuto.done, total: uploadAuto.total, percent: p2 });
    }
  
    // Done
    setUnloadGuard(false);
    uploadAuto.running = false;
  
    if (uploadAuto.errors > 0) {
      toastUpdate({
        line: `Done with ${uploadAuto.errors} error(s).`,
        current: uploadAuto.done,
        total: uploadAuto.total,
        percent: 100,
        status: 'warning',
      });
    } else {
      toastUpdate({
        line: 'Done. All uploads optimized.',
        current: uploadAuto.done,
        total: uploadAuto.total,
        percent: 100,
        status: 'success',
      });
    }
  }
  
  function initAutoOptimizeUploads() {
    if (!uploadAuto.enabled) return false;
    if (uploadAuto.bound) return true;
  
    // Hook into WP media attachments collection: new uploads get added here.
    try {
      if (window.wp && wp.media && wp.media.model && wp.media.model.Attachments && wp.media.model.Attachments.all) {
        const all = wp.media.model.Attachments.all;

        // Snapshot existing IDs so we don't auto-optimize the whole library on initial population.
        const knownIds = new Set();
        try {
          const existingModels = all.models || [];
          for (let i = 0; i < existingModels.length; i++) {
            const m = existingModels[i];
            if (m && m.get) {
              const id0 = Number(m.get('id') || 0);
              if (id0) knownIds.add(id0);
            }
          }
        } catch (e) {
          // ignore
        }

        all.on('add', function (model) {
          try {
            if (!model || !model.get) return;
            const type = model.get('type');
            if (type !== 'image') return;

            const maybeEnqueue = function () {
              const id = Number(model.get('id') || 0);
              if (!id) return;
              if (knownIds.has(id)) return;
              knownIds.add(id);
              const filename = model.get('filename') || model.get('title') || ('Attachment #' + id);
              enqueueUploadedImage(id, filename);
            };

            // Some upload models receive their attachment ID after upload completes.
            maybeEnqueue();
            if (!Number(model.get('id') || 0)) {
              if (model.once) {
                model.once('change:id', maybeEnqueue);
                model.once('sync', maybeEnqueue);
              }
            }
          } catch (e) {
            // ignore
          }
        });

        uploadAuto.bound = true;
        return true;
      }
    } catch (e) {
      // ignore
    }

    return false;
  }

  function initApiFetchMediaUploadHook() {
    if (!uploadAuto.enabled) return;
    if (!(window.wp && wp.apiFetch && typeof wp.apiFetch.use === 'function')) return;
    if (wp.apiFetch.__kingImgMediaUploadHooked) return;

    wp.apiFetch.__kingImgMediaUploadHooked = true;

    // Gutenberg/Image block direct uploads use wp.apiFetch POST /wp/v2/media
    wp.apiFetch.use(function (options, next) {
      const opts = options || {};
      const method = String(opts.method || 'GET').toUpperCase();
      const path = String(opts.path || opts.url || '');
      const isMediaUpload = method === 'POST' && path.indexOf('/wp/v2/media') !== -1;

      let labelFromFile = '';
      if (isMediaUpload) {
        try {
          const body = opts.body;
          if (body && typeof body.get === 'function') {
            const file = body.get('file');
            if (file && file.name) {
              labelFromFile = String(file.name);
            }
          }
        } catch (e) {
          // ignore
        }
      }

      return next(options).then(function (result) {
        try {
          if (!isMediaUpload) {
            return result;
          }

          const id = Number(result && result.id ? result.id : 0);
          if (!id) {
            return result;
          }

          const mediaType = String((result && result.media_type) || '');
          const mimeType = String((result && result.mime_type) || '');
          const isImage = mediaType === 'image' || mimeType.indexOf('image/') === 0;
          if (!isImage) {
            return result;
          }

          const titleObj = result && result.title ? result.title : null;
          const title = titleObj && (titleObj.raw || titleObj.rendered) ? (titleObj.raw || titleObj.rendered) : '';
          enqueueUploadedImage(id, labelFromFile || title || ('Attachment #' + id));
        } catch (e) {
          // ignore
        }

        return result;
      });
    });
  }

  function scheduleAutoOptimizeUploadsInit() {
    if (!uploadAuto.enabled) return;
    if (uploadAuto.bound) return;

    // In the "Select or Upload Media" modal, wp.media collections are created lazily.
    // Retry a few times until wp.media.model.Attachments.all exists.
    let tries = 0;
    const timer = window.setInterval(function () {
      tries++;
      const ok = initAutoOptimizeUploads();
      if (ok || tries >= 120) {
        window.clearInterval(timer);
      }
    }, 500);
  }
  
  $(document).ready(function () {
    initAutoOptimizeUploads();
    scheduleAutoOptimizeUploadsInit();
    initApiFetchMediaUploadHook();
  });
})(jQuery);
