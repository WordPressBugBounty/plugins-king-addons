<?php
/**
 * Custom Code Manager - Import/Export Page Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

use King_Addons\Custom_Code_Manager;

/** @var bool $has_pro */
?>
<div class="kng-cc-admin">
    <!-- Header -->
    <header class="kng-cc-header">
        <div class="kng-cc-header-left">
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code')); ?>" class="kng-cc-back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <?php esc_html_e('Back to snippets', 'king-addons'); ?>
            </a>
            <h1 class="kng-cc-title"><?php esc_html_e('Import / Export', 'king-addons'); ?></h1>
            <p class="kng-cc-subtitle"><?php esc_html_e('Backup, restore, or migrate your custom code snippets', 'king-addons'); ?></p>
        </div>
        <div class="kng-cc-header-right">
            <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr(esc_html__('Theme', 'king-addons')); ?>" data-active="<?php echo esc_attr($theme_mode); ?>">
                <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
                <button type="button" class="ka-v3-segmented-btn" data-theme="light" aria-pressed="<?php echo $theme_mode === 'light' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">☀︎</span>
                    <?php esc_html_e('Light', 'king-addons'); ?>
                </button>
                <button type="button" class="ka-v3-segmented-btn" data-theme="dark" aria-pressed="<?php echo $theme_mode === 'dark' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">☾</span>
                    <?php esc_html_e('Dark', 'king-addons'); ?>
                </button>
                <button type="button" class="ka-v3-segmented-btn" data-theme="auto" aria-pressed="<?php echo $theme_mode === 'auto' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">◐</span>
                    <?php esc_html_e('Auto', 'king-addons'); ?>
                </button>
            </div>
        </div>
    </header>

    <div class="kng-cc-import-export-content">
        <div class="kng-cc-import-export-grid">
            <!-- Export Section -->
            <div class="kng-cc-ie-card">
                <div class="kng-cc-ie-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </div>
                <h3><?php esc_html_e('Export Snippets', 'king-addons'); ?></h3>
                <p><?php esc_html_e('Download all your snippets as a JSON file. You can use this to backup your snippets or import them to another site.', 'king-addons'); ?></p>
                
                <div class="kng-cc-ie-options">
                    <label class="kng-v3-toggle-wrap kng-v3-toggle-row">
                        <input type="checkbox" id="kng-cc-export-settings" checked />
                        <span class="kng-v3-toggle-slider"></span>
                        <span class="kng-v3-toggle-text"><?php esc_html_e('Include settings', 'king-addons'); ?></span>
                    </label>
                </div>

                <button type="button" class="kng-v3-btn kng-v3-btn--primary" id="kng-cc-export-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <?php esc_html_e('Export All Snippets', 'king-addons'); ?>
                </button>
            </div>

            <!-- Import Section -->
            <div class="kng-cc-ie-card">
                <div class="kng-cc-ie-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
                <h3><?php esc_html_e('Import Snippets', 'king-addons'); ?></h3>
                <p><?php esc_html_e('Upload a JSON file with snippets from another site or backup. Choose how to handle existing snippets.', 'king-addons'); ?></p>

                <div class="kng-cc-ie-options">
                    <div class="kng-v3-field">
                        <label class="kng-v3-label"><?php esc_html_e('Import Mode', 'king-addons'); ?></label>
                        <select id="kng-cc-import-mode" class="kng-v3-select">
                            <option value="merge"><?php esc_html_e('Merge - Add to existing snippets', 'king-addons'); ?></option>
                            <option value="skip"><?php esc_html_e('Skip duplicates - Only import new snippets', 'king-addons'); ?></option>
                            <?php if ($has_pro): ?>
                            <option value="replace"><?php esc_html_e('Replace all - Delete existing and import', 'king-addons'); ?></option>
                            <?php else: ?>
                            <option value="replace" disabled><?php esc_html_e('Replace all (Pro)', 'king-addons'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="kng-cc-import-dropzone" id="kng-cc-import-dropzone">
                    <input type="file" id="kng-cc-import-file" accept=".json" style="display: none;" />
                    <div class="kng-cc-dropzone-content">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span><?php esc_html_e('Drop JSON file here or click to browse', 'king-addons'); ?></span>
                    </div>
                    <div class="kng-cc-dropzone-file" style="display: none;">
                        <span class="kng-cc-file-name"></span>
                        <button type="button" class="kng-cc-file-remove">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="button" class="kng-v3-btn kng-v3-btn--primary" id="kng-cc-import-btn" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <?php esc_html_e('Import Snippets', 'king-addons'); ?>
                </button>
            </div>
        </div>

        <!-- Import Results -->
        <div class="kng-cc-import-results" id="kng-cc-import-results" style="display: none;">
            <div class="kng-cc-import-results-content">
                <h4><?php esc_html_e('Import Results', 'king-addons'); ?></h4>
                <div class="kng-cc-import-stats">
                    <div class="kng-cc-import-stat">
                        <span class="kng-cc-import-stat-value" id="kng-cc-imported-count">0</span>
                        <span class="kng-cc-import-stat-label"><?php esc_html_e('Imported', 'king-addons'); ?></span>
                    </div>
                    <div class="kng-cc-import-stat">
                        <span class="kng-cc-import-stat-value" id="kng-cc-skipped-count">0</span>
                        <span class="kng-cc-import-stat-label"><?php esc_html_e('Skipped', 'king-addons'); ?></span>
                    </div>
                    <div class="kng-cc-import-stat">
                        <span class="kng-cc-import-stat-value" id="kng-cc-errors-count">0</span>
                        <span class="kng-cc-import-stat-label"><?php esc_html_e('Errors', 'king-addons'); ?></span>
                    </div>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code')); ?>" class="kng-v3-btn kng-v3-btn--ghost">
                    <?php esc_html_e('View Snippets', 'king-addons'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
