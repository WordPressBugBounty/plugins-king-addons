<?php
/**
 * Site Preloader Import/Export Tab.
 *
 * @package King_Addons
 * @since 1.0.0
 *
 * @var array $settings Current settings.
 * @var bool  $is_pro   Whether Pro version is active.
 * @var array $presets  Available presets.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ka-preloader-import-export-grid">
    <!-- Export -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-upload"></span>
            <h2>
                <?php esc_html_e('Export Settings', 'king-addons'); ?>
                <?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
            </h2>
        </div>
        <div class="ka-card-body">
            <p><?php esc_html_e('Export your preloader settings, rules, and templates to a JSON file. You can use this file to import the settings on another site or as a backup.', 'king-addons'); ?></p>
            
            <?php if ($is_pro): ?>
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <?php wp_nonce_field('king_addons_site_preloader_export'); ?>
                <input type="hidden" name="action" value="king_addons_site_preloader_export" />
                <button type="submit" class="ka-btn ka-btn-primary">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export JSON', 'king-addons'); ?>
                </button>
            </form>
            <?php else: ?>
            <div class="ka-pro-notice" style="padding: 20px; margin-top: 15px;">
                <p><?php esc_html_e('Export functionality is available in the Pro version.', 'king-addons'); ?></p>
                <a href="https://kingaddons.com/pricing/?utm_source=kng-preloader-export-upgrade&utm_medium=plugin&utm_campaign=kng" target="_blank" class="ka-btn ka-btn-primary ka-btn-sm">
                    <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Import -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-download"></span>
            <h2>
                <?php esc_html_e('Import Settings', 'king-addons'); ?>
                <?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
            </h2>
        </div>
        <div class="ka-card-body">
            <p><?php esc_html_e('Import preloader settings from a previously exported JSON file. This will overwrite your current settings.', 'king-addons'); ?></p>
            
            <?php if ($is_pro): ?>
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('king_addons_site_preloader_import'); ?>
                <input type="hidden" name="action" value="king_addons_site_preloader_import" />
                
                <div class="ka-file-upload">
                    <input type="file" name="import_file" accept=".json" id="ka-import-file" />
                    <label for="ka-import-file" class="ka-file-upload__label">
                        <span class="dashicons dashicons-upload"></span>
                        <span class="ka-file-upload__text"><?php esc_html_e('Choose JSON file...', 'king-addons'); ?></span>
                    </label>
                    <span class="ka-file-upload__filename" id="ka-import-filename"></span>
                </div>
                
                <button type="submit" class="ka-btn ka-btn-primary" style="margin-top: 15px;">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Import Settings', 'king-addons'); ?>
                </button>
            </form>
            <?php else: ?>
            <div class="ka-pro-notice" style="padding: 20px; margin-top: 15px;">
                <p><?php esc_html_e('Import functionality is available in the Pro version.', 'king-addons'); ?></p>
                <a href="https://kingaddons.com/pricing/?utm_source=kng-preloader-import-upgrade&utm_medium=plugin&utm_campaign=kng" target="_blank" class="ka-btn ka-btn-primary ka-btn-sm">
                    <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reset to Defaults -->
<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-image-rotate" style="color: #ff3b30;"></span>
        <h2><?php esc_html_e('Reset to Defaults', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('Reset all preloader settings, rules, and templates to their default values. This action cannot be undone.', 'king-addons'); ?></p>
        
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="ka-reset-form">
            <?php wp_nonce_field('king_addons_site_preloader_reset'); ?>
            <input type="hidden" name="action" value="king_addons_site_preloader_reset" />
            <button type="submit" class="ka-btn ka-btn-danger" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset all settings? This cannot be undone.', 'king-addons')); ?>')">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Reset All Settings', 'king-addons'); ?>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('ka-import-file');
    const filenameSpan = document.getElementById('ka-import-filename');
    
    if (fileInput && filenameSpan) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                filenameSpan.textContent = this.files[0].name;
            } else {
                filenameSpan.textContent = '';
            }
        });
    }
});
</script>
