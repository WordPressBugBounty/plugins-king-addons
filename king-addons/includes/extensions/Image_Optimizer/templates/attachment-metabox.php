<?php
/**
 * Attachment metabox template for Image Optimizer.
 *
 * @package King_Addons
 * @var WP_Post $post
 * @var array|null $meta
 * @var int $file_size
 */

if (!defined('ABSPATH')) {
    exit;
}

use King_Addons\Image_Optimizer\Image_Optimizer;

$status = $meta['status'] ?? 'pending';
$is_optimized = $status === 'optimized';
$optimizer = Image_Optimizer::instance();
$settings = $optimizer->get_settings();

$full_size_meta = is_array($meta) && !empty($meta['sizes']['full']) ? $meta['sizes']['full'] : null;
$full_original_path = is_array($full_size_meta) ? ($full_size_meta['original_path'] ?? '') : '';
$full_optimized_path = is_array($full_size_meta) ? ($full_size_meta['optimized_path'] ?? ($full_size_meta['webp_path'] ?? '')) : '';

$full_original_bytes = 0;
if (!empty($full_original_path) && is_string($full_original_path) && file_exists($full_original_path)) {
    $full_original_bytes = (int) filesize($full_original_path);
} elseif (is_array($full_size_meta)) {
    $full_original_bytes = (int) ($full_size_meta['original_size'] ?? 0);
}

$full_optimized_bytes = 0;
if (!empty($full_optimized_path) && is_string($full_optimized_path) && file_exists($full_optimized_path)) {
    $full_optimized_bytes = (int) filesize($full_optimized_path);
} elseif (is_array($full_size_meta)) {
    $full_optimized_bytes = (int) ($full_size_meta['optimized_size'] ?? 0);
}

$full_saved_bytes = max(0, $full_original_bytes - $full_optimized_bytes);
$full_saved_percent = $full_original_bytes > 0 ? round(($full_saved_bytes / $full_original_bytes) * 100, 1) : 0;
$full_ratio_percent = $full_original_bytes > 0 ? round(($full_optimized_bytes / $full_original_bytes) * 100, 1) : 0;
?>

<div class="king-img-opt-metabox">
    <?php if ($is_optimized): ?>
        <div class="king-img-opt-status optimized">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e('Optimized', 'king-addons'); ?>
        </div>

        <div class="king-img-opt-stats">
            <?php if ($full_original_bytes > 0 && $full_optimized_bytes > 0): ?>
                <div class="king-img-opt-stat-row">
                    <span class="label"><?php esc_html_e('Full (original):', 'king-addons'); ?></span>
                    <span class="value"><?php echo esc_html(Image_Optimizer::format_bytes($full_original_bytes)); ?></span>
                </div>
                <div class="king-img-opt-stat-row">
                    <span class="label"><?php esc_html_e('Full (optimized):', 'king-addons'); ?></span>
                    <span class="value"><?php echo esc_html(Image_Optimizer::format_bytes($full_optimized_bytes)); ?></span>
                </div>
                <div class="king-img-opt-stat-row highlight">
                    <span class="label"><?php esc_html_e('Full saved:', 'king-addons'); ?></span>
                    <span class="value"><?php echo esc_html(Image_Optimizer::format_bytes($full_saved_bytes)); ?> (<?php echo esc_html($full_saved_percent); ?>%)</span>
                </div>
                <div class="king-img-opt-stat-row">
                    <span class="label"><?php esc_html_e('Compression ratio:', 'king-addons'); ?></span>
                    <span class="value"><?php echo esc_html($full_ratio_percent); ?>%</span>
                </div>
                <hr class="king-img-opt-divider" />
            <?php endif; ?>

            <div class="king-img-opt-stat-row">
                <span class="label"><?php esc_html_e('Original:', 'king-addons'); ?></span>
                <span class="value"><?php echo esc_html(Image_Optimizer::format_bytes($meta['total_original_bytes'] ?? 0)); ?></span>
            </div>
            <div class="king-img-opt-stat-row">
                <span class="label"><?php esc_html_e('Optimized:', 'king-addons'); ?></span>
                <span class="value"><?php echo esc_html(Image_Optimizer::format_bytes($meta['total_optimized_bytes'] ?? 0)); ?></span>
            </div>
            <div class="king-img-opt-stat-row highlight">
                <span class="label"><?php esc_html_e('Saved:', 'king-addons'); ?></span>
                <span class="value"><?php echo esc_html(Image_Optimizer::format_bytes($meta['total_saved_bytes'] ?? 0)); ?> (<?php echo esc_html($meta['savings_percent'] ?? 0); ?>%)</span>
            </div>
            <div class="king-img-opt-stat-row">
                <span class="label"><?php esc_html_e('Format:', 'king-addons'); ?></span>
                <span class="value"><?php echo esc_html(strtoupper($meta['format'] ?? 'webp')); ?></span>
            </div>
        </div>

        <?php if (!empty($meta['sizes'])): ?>
        <details class="king-img-opt-sizes">
            <summary><?php printf(esc_html__('%d sizes optimized', 'king-addons'), count($meta['sizes'])); ?></summary>
            <ul>
                <?php foreach ($meta['sizes'] as $size_name => $size_data): ?>
                    <?php
                        $o = (int) ($size_data['original_size'] ?? 0);
                        $n = (int) ($size_data['optimized_size'] ?? 0);
                        $sb = max(0, $o - $n);
                        $sp = $o > 0 ? round(($sb / $o) * 100, 1) : 0;
                    ?>
                    <li>
                        <strong><?php echo esc_html($size_name); ?></strong>: 
                        <?php echo esc_html(Image_Optimizer::format_bytes($size_data['original_size'] ?? 0)); ?> → 
                        <?php echo esc_html(Image_Optimizer::format_bytes($size_data['optimized_size'] ?? 0)); ?>
                        <?php if ($o > 0 && $n > 0): ?>
                            (<?php echo esc_html(Image_Optimizer::format_bytes($sb)); ?>, <?php echo esc_html($sp); ?>%)
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>

        <div class="king-img-opt-actions">
            <button type="button" class="button king-img-reoptimize" data-id="<?php echo esc_attr($post->ID); ?>">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Re-optimize', 'king-addons'); ?>
            </button>
            <button type="button" class="button king-img-restore" data-id="<?php echo esc_attr($post->ID); ?>">
                <span class="dashicons dashicons-undo"></span>
                <?php esc_html_e('Restore Original', 'king-addons'); ?>
            </button>
        </div>

    <?php else: ?>
        <div class="king-img-opt-status pending">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e('Not Optimized', 'king-addons'); ?>
        </div>

        <div class="king-img-opt-info">
            <p><strong><?php esc_html_e('File size:', 'king-addons'); ?></strong> <?php echo esc_html(Image_Optimizer::format_bytes($file_size)); ?></p>
        </div>

        <div class="king-img-opt-actions">
            <button type="button" class="button button-primary king-img-optimize" data-id="<?php echo esc_attr($post->ID); ?>">
                <span class="dashicons dashicons-performance"></span>
                <?php esc_html_e('Optimize Now', 'king-addons'); ?>
            </button>
        </div>
    <?php endif; ?>

    <div class="king-img-opt-spinner" style="display: none;">
        <span class="spinner is-active"></span>
        <span class="message"><?php esc_html_e('Processing...', 'king-addons'); ?></span>
    </div>
</div>

<style>
.king-img-opt-metabox {
    padding: 12px 0;
}

.king-img-opt-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-weight: 500;
    margin-bottom: 12px;
}

.king-img-opt-status.optimized {
    background: rgba(52, 199, 89, 0.1);
    color: #34c759;
}

.king-img-opt-status.pending {
    background: rgba(255, 149, 0, 0.1);
    color: #ff9500;
}

.king-img-opt-stats {
    background: #f5f5f7;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
}

.king-img-opt-stat-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 13px;
}

.king-img-opt-stat-row.highlight {
    color: #34c759;
    font-weight: 600;
}

.king-img-opt-sizes {
    margin-bottom: 12px;
}

.king-img-opt-sizes summary {
    cursor: pointer;
    color: #0071e3;
    font-size: 13px;
}

.king-img-opt-sizes ul {
    margin: 8px 0 0 16px;
    font-size: 12px;
}

.king-img-opt-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.king-img-opt-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.king-img-opt-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.king-img-opt-spinner {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding: 8px;
    background: #f5f5f7;
    border-radius: 8px;
}

.king-img-opt-spinner .spinner {
    float: none;
    margin: 0;
}

.king-img-opt-divider {
    border: 0;
    border-top: 1px solid rgba(0,0,0,0.06);
    margin: 10px 0;
}

body.ka-v3-dark .king-img-opt-divider {
    border-top-color: rgba(255,255,255,0.12);
}

/* Dark mode */
body.ka-v3-dark .king-img-opt-stats {
    background: #1c1c1e;
    color: #f5f5f7;
}
</style>

<script>
jQuery(document).ready(function($) {
    var attachmentId = <?php echo (int) $post->ID; ?>;
    var $metabox = $('.king-img-opt-metabox');
    var $spinner = $metabox.find('.king-img-opt-spinner');
    
    // Optimize button
    $metabox.on('click', '.king-img-optimize', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $spinner.show();
        $spinner.find('.message').text('<?php echo esc_js(__('Optimizing...', 'king-addons')); ?>');
        
        optimizeSingleImage(attachmentId).then(function(result) {
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || '<?php echo esc_js(__('Optimization failed', 'king-addons')); ?>');
                $btn.prop('disabled', false);
                $spinner.hide();
            }
        }).catch(function(err) {
            alert(err.message || '<?php echo esc_js(__('Optimization failed', 'king-addons')); ?>');
            $btn.prop('disabled', false);
            $spinner.hide();
        });
    });
    
    // Re-optimize button
    $metabox.on('click', '.king-img-reoptimize', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $spinner.show();
        $spinner.find('.message').text('<?php echo esc_js(__('Re-optimizing...', 'king-addons')); ?>');
        
        optimizeSingleImage(attachmentId).then(function(result) {
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || '<?php echo esc_js(__('Optimization failed', 'king-addons')); ?>');
                $btn.prop('disabled', false);
                $spinner.hide();
            }
        }).catch(function(err) {
            alert(err.message || '<?php echo esc_js(__('Optimization failed', 'king-addons')); ?>');
            $btn.prop('disabled', false);
            $spinner.hide();
        });
    });
    
    // Restore button
    $metabox.on('click', '.king-img-restore', function() {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to restore the original image? This will delete all WebP files.', 'king-addons')); ?>')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true);
        $spinner.show();
        $spinner.find('.message').text('<?php echo esc_js(__('Restoring...', 'king-addons')); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'king_img_full_restore',
                nonce: '<?php echo wp_create_nonce('king_img_optimizer_nonce'); ?>',
                attachment_id: attachmentId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Restore failed', 'king-addons')); ?>');
                    $btn.prop('disabled', false);
                    $spinner.hide();
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Restore failed', 'king-addons')); ?>');
                $btn.prop('disabled', false);
                $spinner.hide();
            }
        });
    });
    
    // Optimize single image function
    async function optimizeSingleImage(id) {
        // Get image data
        var imageData = await $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'king_img_get_image_data',
                nonce: '<?php echo wp_create_nonce('king_img_optimizer_nonce'); ?>',
                attachment_id: id,
                sizes: 'all'
            }
        });
        
        if (!imageData.success) {
            throw new Error(imageData.data.message || 'Failed to get image data');
        }
        
        var results = [];
        var totalSaved = 0;
        
        // Process each size
        for (var sizeName in imageData.data.images) {
            var sizeData = imageData.data.images[sizeName];
            
            try {
                // Create image element
                var img = new Image();
                img.crossOrigin = 'anonymous';
                
                var loaded = await new Promise(function(resolve, reject) {
                    img.onload = function() { resolve(true); };
                    img.onerror = function() { reject(new Error('Failed to load')); };
                    img.src = sizeData.url;
                });
                
                // Create canvas
                var canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                
                // Convert to WebP
                var quality = <?php echo (int) ($settings['quality'] ?? 82); ?> / 100;
                var dataUrl = canvas.toDataURL('image/webp', quality);
                var base64Data = dataUrl.split(',')[1];
                var optimizedSize = Math.round(base64Data.length * 0.75);
                var savedBytes = Math.max(0, sizeData.filesize - optimizedSize);
                
                // Save to server
                var saveResult = await $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'king_img_save_optimized',
                        nonce: '<?php echo wp_create_nonce('king_img_optimizer_nonce'); ?>',
                        attachment_id: id,
                        size: sizeName,
                        format: 'webp',
                        image_data: dataUrl,
                        original_size: sizeData.filesize,
                        optimized_size: optimizedSize,
                        method: 'canvas'
                    }
                });
                
                if (saveResult.success) {
                    results.push({ size: sizeName, saved: savedBytes });
                    totalSaved += savedBytes;
                }
                
            } catch (e) {
                console.error('Error optimizing size ' + sizeName, e);
            }
        }
        
        // Apply WebP URLs
        <?php if (!empty($settings['auto_replace_urls'])): ?>
        await $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'king_img_apply_webp_urls',
                nonce: '<?php echo wp_create_nonce('king_img_optimizer_nonce'); ?>',
                attachment_id: id
            }
        });
        <?php endif; ?>
        
        return { success: true, results: results, totalSaved: totalSaved };
    }
});
</script>
