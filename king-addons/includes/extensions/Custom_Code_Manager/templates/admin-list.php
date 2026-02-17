<?php
/**
 * Custom Code Manager - List Page Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

use King_Addons\Custom_Code_Manager;

/** @var array $snippets */
/** @var int $snippet_count */
/** @var bool $has_pro */
/** @var bool $at_limit */
?>
<div class="kng-cc-admin">
    <!-- Header -->
    <header class="kng-cc-header">
        <div class="kng-cc-header-left">
            <div class="kng-cc-header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="32" height="32"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </div>
            <div>
                <h1 class="kng-cc-title">
                    <?php esc_html_e('Custom Code', 'king-addons'); ?>
                </h1>
                <p class="kng-cc-subtitle"><?php esc_html_e('Add custom CSS, JavaScript, and HTML snippets anywhere on your site.', 'king-addons'); ?></p>
                <p class="kng-cc-meta">
                    <?php if (!$has_pro): ?>
                        <?php printf(
                            /* translators: %1$d: current count, %2$d: max limit */
                            esc_html__('%1$d of %2$d snippets (Free)', 'king-addons'),
                            $snippet_count,
                            Custom_Code_Manager::FREE_LIMIT
                        ); ?>
                    <?php else: ?>
                        <?php printf(
                            /* translators: %d: total count */
                            esc_html(_n('%d snippet', '%d snippets', $snippet_count, 'king-addons')),
                            $snippet_count
                        ); ?>
                    <?php endif; ?>
                </p>
            </div>
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
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code&view=import-export')); ?>" class="kng-v3-btn kng-v3-btn--ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <?php esc_html_e('Import / Export', 'king-addons'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code&view=settings')); ?>" class="kng-v3-btn kng-v3-btn--ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                <?php esc_html_e('Settings', 'king-addons'); ?>
            </a>
            <?php if ($at_limit): ?>
            <button type="button" class="kng-v3-btn kng-v3-btn--primary" id="kng-cc-add-new-limit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e('Add New', 'king-addons'); ?>
            </button>
            <?php else: ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code&view=new')); ?>" class="kng-v3-btn kng-v3-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e('Add New', 'king-addons'); ?>
            </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Filters & Bulk Actions -->
    <div class="kng-cc-toolbar">
        <div class="kng-cc-toolbar-left">
            <div class="kng-cc-bulk-actions">
                <label class="kng-cc-checkbox-wrap kng-cc-select-all-wrap">
                    <input type="checkbox" class="kng-cc-select-all" />
                    <span class="kng-cc-checkbox-custom"></span>
                </label>
                <select class="kng-cc-bulk-select kng-v3-select" id="kng-cc-bulk-action">
                    <option value=""><?php esc_html_e('Bulk Actions', 'king-addons'); ?></option>
                    <option value="enable"><?php esc_html_e('Enable', 'king-addons'); ?></option>
                    <option value="disable"><?php esc_html_e('Disable', 'king-addons'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'king-addons'); ?></option>
                </select>
                <button type="button" class="kng-v3-btn kng-v3-btn--secondary kng-v3-btn--sm" id="kng-cc-bulk-apply">
                    <?php esc_html_e('Apply', 'king-addons'); ?>
                </button>
            </div>
        </div>
        <div class="kng-cc-toolbar-right">
            <div class="kng-cc-filters">
                <select class="kng-v3-select kng-cc-filter" data-filter="type">
                    <option value=""><?php esc_html_e('All Types', 'king-addons'); ?></option>
                    <option value="css">CSS</option>
                    <option value="js">JavaScript</option>
                    <?php if ($has_pro): ?>
                    <option value="html">HTML</option>
                    <?php endif; ?>
                </select>
                <select class="kng-v3-select kng-cc-filter" data-filter="status">
                    <option value=""><?php esc_html_e('All Status', 'king-addons'); ?></option>
                    <option value="enabled"><?php esc_html_e('Enabled', 'king-addons'); ?></option>
                    <option value="disabled"><?php esc_html_e('Disabled', 'king-addons'); ?></option>
                </select>
                <select class="kng-v3-select kng-cc-filter" data-filter="location">
                    <option value=""><?php esc_html_e('All Locations', 'king-addons'); ?></option>
                    <option value="head"><?php esc_html_e('Head', 'king-addons'); ?></option>
                    <option value="footer"><?php esc_html_e('Footer', 'king-addons'); ?></option>
                    <?php if ($has_pro): ?>
                    <option value="body_open"><?php esc_html_e('Body Open', 'king-addons'); ?></option>
                    <option value="custom_hook"><?php esc_html_e('Custom Hook', 'king-addons'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="kng-cc-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" class="kng-v3-input" placeholder="<?php esc_attr_e('Search snippets...', 'king-addons'); ?>" id="kng-cc-search" />
            </div>
        </div>
    </div>

    <!-- Snippets List -->
    <div class="kng-cc-list">
        <?php if (empty($snippets)): ?>
        <div class="kng-cc-empty">
            <div class="kng-cc-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40">
                    <polyline points="16 18 22 12 16 6"/>
                    <polyline points="8 6 2 12 8 18"/>
                </svg>
            </div>
            <h3><?php esc_html_e('No snippets yet', 'king-addons'); ?></h3>
            <p><?php esc_html_e('Create your first custom code snippet to add CSS, JavaScript, or HTML to your site.', 'king-addons'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code&view=new')); ?>" class="kng-v3-btn kng-v3-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e('Create First Snippet', 'king-addons'); ?>
            </a>
        </div>
        <?php else: ?>
        <div class="kng-cc-table-wrap">
            <table class="kng-cc-table">
                <thead>
                    <tr>
                        <th class="kng-cc-col-check"></th>
                        <th class="kng-cc-col-name"><?php esc_html_e('Name', 'king-addons'); ?></th>
                        <th class="kng-cc-col-type"><?php esc_html_e('Type', 'king-addons'); ?></th>
                        <th class="kng-cc-col-location"><?php esc_html_e('Location', 'king-addons'); ?></th>
                        <th class="kng-cc-col-scope"><?php esc_html_e('Scope', 'king-addons'); ?></th>
                        <th class="kng-cc-col-priority"><?php esc_html_e('Priority', 'king-addons'); ?></th>
                        <th class="kng-cc-col-status"><?php esc_html_e('Status', 'king-addons'); ?></th>
                        <th class="kng-cc-col-modified"><?php esc_html_e('Modified', 'king-addons'); ?></th>
                        <th class="kng-cc-col-actions"><?php esc_html_e('Actions', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody id="kng-cc-snippets-list">
                    <?php foreach ($snippets as $snippet): ?>
                    <tr class="kng-cc-row" 
                        data-id="<?php echo esc_attr($snippet['id']); ?>"
                        data-type="<?php echo esc_attr($snippet['type']); ?>"
                        data-status="<?php echo esc_attr($snippet['status']); ?>"
                        data-location="<?php echo esc_attr($snippet['location']); ?>">
                        <td class="kng-cc-col-check">
                            <label class="kng-cc-checkbox-wrap">
                                <input type="checkbox" class="kng-cc-row-check" value="<?php echo esc_attr($snippet['id']); ?>" />
                                <span class="kng-cc-checkbox-custom"></span>
                            </label>
                        </td>
                        <td class="kng-cc-col-name">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code&view=edit&id=' . $snippet['id'])); ?>" class="kng-cc-name-link">
                                <?php echo esc_html($snippet['title'] ?: __('(no title)', 'king-addons')); ?>
                            </a>
                            <?php if (!empty($snippet['notes'])): ?>
                            <span class="kng-cc-has-notes" title="<?php echo esc_attr($snippet['notes']); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="kng-cc-col-type">
                            <span class="kng-cc-type-badge kng-cc-type-<?php echo esc_attr($snippet['type']); ?>">
                                <?php echo esc_html(strtoupper($snippet['type'])); ?>
                            </span>
                        </td>
                        <td class="kng-cc-col-location">
                            <span class="kng-cc-location-badge">
                                <?php
                                $locations = [
                                    'head' => __('Head', 'king-addons'),
                                    'footer' => __('Footer', 'king-addons'),
                                    'body_open' => __('Body Open', 'king-addons'),
                                    'custom_hook' => $snippet['custom_hook'] ?: __('Custom Hook', 'king-addons'),
                                ];
                                echo esc_html($locations[$snippet['location']] ?? $snippet['location']);
                                ?>
                            </span>
                        </td>
                        <td class="kng-cc-col-scope">
                            <span class="kng-cc-scope-badge kng-cc-scope-<?php echo esc_attr($snippet['scope_mode']); ?>">
                                <?php
                                $scopes = [
                                    'global' => __('Global', 'king-addons'),
                                    'include' => __('Include', 'king-addons'),
                                    'exclude' => __('Exclude', 'king-addons'),
                                ];
                                echo esc_html($scopes[$snippet['scope_mode']] ?? $snippet['scope_mode']);
                                ?>
                            </span>
                            <?php if (!empty($snippet['rules'])): ?>
                            <span class="kng-cc-rules-count"><?php echo count($snippet['rules']); ?> <?php esc_html_e('rules', 'king-addons'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="kng-cc-col-priority">
                            <?php echo esc_html($snippet['priority']); ?>
                        </td>
                        <td class="kng-cc-col-status">
                            <label class="kng-v3-toggle-wrap">
                                <input type="checkbox" 
                                       class="kng-cc-status-toggle" 
                                       data-id="<?php echo esc_attr($snippet['id']); ?>"
                                       <?php checked($snippet['status'], 'enabled'); ?> />
                                <span class="kng-v3-toggle-slider"></span>
                            </label>
                        </td>
                        <td class="kng-cc-col-modified">
                            <span class="kng-cc-date" title="<?php echo esc_attr($snippet['modified']); ?>">
                                <?php echo esc_html(human_time_diff(strtotime($snippet['modified'])) . ' ' . __('ago', 'king-addons')); ?>
                            </span>
                        </td>
                        <td class="kng-cc-col-actions">
                            <div class="kng-cc-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code&view=edit&id=' . $snippet['id'])); ?>" 
                                   class="kng-cc-action-btn kng-cc-action-btn--edit" 
                                   title="<?php esc_attr_e('Edit', 'king-addons'); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    <?php esc_html_e('Edit', 'king-addons'); ?>
                                </a>
                                <button type="button" 
                                        class="kng-cc-action-btn kng-cc-duplicate-btn" 
                                        data-id="<?php echo esc_attr($snippet['id']); ?>"
                                        title="<?php esc_attr_e('Duplicate', 'king-addons'); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                        <rect x="9" y="9" width="13" height="13" rx="2"/>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                </button>
                                <button type="button" 
                                        class="kng-cc-action-btn kng-cc-export-btn" 
                                        data-id="<?php echo esc_attr($snippet['id']); ?>"
                                        title="<?php esc_attr_e('Export', 'king-addons'); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7 10 12 15 17 10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                </button>
                                <button type="button" 
                                        class="kng-cc-action-btn kng-cc-action-btn--danger kng-cc-delete-btn" 
                                        data-id="<?php echo esc_attr($snippet['id']); ?>"
                                        title="<?php esc_attr_e('Delete', 'king-addons'); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$has_pro): ?>
    <!-- Pro Upsell -->
    <div class="kng-cc-pro-upsell">
        <div class="kng-cc-pro-upsell-content">
            <div class="kng-cc-pro-upsell-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </div>
            <div class="kng-cc-pro-upsell-text">
                <h4><?php esc_html_e('Unlock Pro Features', 'king-addons'); ?></h4>
                <p><?php esc_html_e('Get HTML snippets, unlimited code blocks, body open injection, custom hooks, advanced rules, and more.', 'king-addons'); ?></p>
            </div>
            <a href="https://kingaddons.com/pricing/?utm_source=kng-custom-code&utm_medium=wp-admin&utm_campaign=kng" target="_blank" rel="noopener" class="kng-v3-btn kng-v3-btn--accent">
                <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$has_pro): ?>
<!-- Pro Upgrade Popup -->
<div class="kng-cc-pro-popup-overlay" id="kng-cc-pro-popup" style="display:none;">
    <div class="kng-cc-pro-popup">
        <button type="button" class="kng-cc-pro-popup-close" id="kng-cc-pro-popup-close">&times;</button>
        <div class="kng-cc-pro-popup-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        </div>
        <h3><?php esc_html_e('Snippet Limit Reached', 'king-addons'); ?></h3>
        <p><?php
            printf(
                /* translators: %d: free limit number */
                esc_html__('You\'ve reached the free limit of %d snippets. Upgrade to Pro for unlimited code snippets and advanced features.', 'king-addons'),
                Custom_Code_Manager::FREE_LIMIT
            );
        ?></p>
        <div class="kng-cc-pro-popup-features">
            <span>✓ <?php esc_html_e('Unlimited snippets', 'king-addons'); ?></span>
            <span>✓ <?php esc_html_e('HTML support', 'king-addons'); ?></span>
            <span>✓ <?php esc_html_e('Advanced targeting', 'king-addons'); ?></span>
            <span>✓ <?php esc_html_e('Custom hooks', 'king-addons'); ?></span>
        </div>
        <div class="kng-cc-pro-popup-actions">
            <a href="https://kingaddons.com/pricing/?utm_source=kng-custom-code&utm_medium=wp-admin&utm_campaign=kng" target="_blank" rel="noopener" class="kng-v3-btn kng-v3-btn--accent">
                <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
            </a>
            <button type="button" class="kng-v3-btn kng-v3-btn--ghost kng-cc-pro-popup-dismiss">
                <?php esc_html_e('Maybe Later', 'king-addons'); ?>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
