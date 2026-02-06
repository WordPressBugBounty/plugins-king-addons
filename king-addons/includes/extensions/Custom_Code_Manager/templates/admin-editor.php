<?php
/**
 * Custom Code Manager - Editor Page Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

use King_Addons\Custom_Code_Manager;

/** @var array|null $snippet */
/** @var bool $is_new */
/** @var bool $has_pro */
/** @var array $defaults */
/** @var array $config */

$type_icons = [
    'css' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M4 4h16v16H4z"/><path d="M9 9h6v6H9z"/></svg>',
    'js' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M12 2v20M2 12h20"/></svg>',
    'html' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
];
?>
<script>
window.kngCCInitialConfig = <?php echo wp_json_encode($config); ?>;
</script>

<div class="kng-cc-admin kng-cc-editor-page">
    <form id="kng-cc-editor-form" class="kng-cc-editor-form" autocomplete="off">
        <!-- Header -->
        <header class="kng-cc-header kng-cc-header--sticky">
            <div class="kng-cc-header-left">
                <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code')); ?>" class="kng-cc-back-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <?php esc_html_e('Back to snippets', 'king-addons'); ?>
                </a>
                <h1 class="kng-cc-title">
                    <?php echo $is_new ? esc_html__('New Snippet', 'king-addons') : esc_html__('Edit Snippet', 'king-addons'); ?>
                </h1>
            </div>
            <div class="kng-cc-header-right">
                <div class="kng-cc-status-wrap">
                    <label class="kng-v3-toggle-wrap">
                        <input type="checkbox" name="status" id="kng-cc-status" <?php checked($config['status'], 'enabled'); ?> />
                        <span class="kng-v3-toggle-slider"></span>
                    </label>
                    <span class="kng-cc-status-label" id="kng-cc-status-label">
                        <?php echo $config['status'] === 'enabled' ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
                    </span>
                </div>
                <button type="submit" class="kng-v3-btn kng-v3-btn--primary" id="kng-cc-save-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" class="kng-cc-save-icon">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" class="kng-cc-spinner" style="display: none;">
                        <circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="32">
                            <animate attributeName="stroke-dashoffset" values="32;0;32" dur="1s" repeatCount="indefinite"/>
                        </circle>
                    </svg>
                    <span class="kng-cc-save-text"><?php esc_html_e('Save Snippet', 'king-addons'); ?></span>
                </button>
            </div>
        </header>

        <input type="hidden" name="id" id="kng-cc-id" value="<?php echo esc_attr($config['id']); ?>" />

        <div class="kng-cc-editor-layout">
            <!-- Main Content -->
            <div class="kng-cc-editor-main">
                <!-- Title -->
                <div class="kng-cc-editor-section kng-cc-title-section">
                    <input type="text" 
                           name="title" 
                           id="kng-cc-title" 
                           class="kng-cc-title-input" 
                           placeholder="<?php esc_attr_e('Snippet name...', 'king-addons'); ?>"
                           value="<?php echo esc_attr($config['title']); ?>"
                           required />
                </div>

                <!-- Code Editor -->
                <div class="kng-cc-editor-section kng-cc-code-section">
                    <div class="kng-cc-code-header">
                        <div class="kng-cc-type-tabs" role="tablist">
                            <button type="button" 
                                    class="kng-cc-type-tab <?php echo $config['type'] === 'css' ? 'is-active' : ''; ?>" 
                                    data-type="css"
                                    role="tab"
                                    aria-selected="<?php echo $config['type'] === 'css' ? 'true' : 'false'; ?>">
                                <?php echo $type_icons['css']; // phpcs:ignore ?>
                                <span>CSS</span>
                            </button>
                            <button type="button" 
                                    class="kng-cc-type-tab <?php echo $config['type'] === 'js' ? 'is-active' : ''; ?>" 
                                    data-type="js"
                                    role="tab"
                                    aria-selected="<?php echo $config['type'] === 'js' ? 'true' : 'false'; ?>">
                                <?php echo $type_icons['js']; // phpcs:ignore ?>
                                <span>JavaScript</span>
                            </button>
                            <button type="button" 
                                    class="kng-cc-type-tab <?php echo $config['type'] === 'html' ? 'is-active' : ''; ?> <?php echo !$has_pro ? 'is-pro' : ''; ?>" 
                                    data-type="html"
                                    role="tab"
                                    aria-selected="<?php echo $config['type'] === 'html' ? 'true' : 'false'; ?>"
                                    <?php echo !$has_pro ? 'disabled' : ''; ?>>
                                <?php echo $type_icons['html']; // phpcs:ignore ?>
                                <span>HTML</span>
                                <?php if (!$has_pro): ?>
                                <span class="kng-cc-pro-badge">PRO</span>
                                <?php endif; ?>
                            </button>
                        </div>
                        <div class="kng-cc-code-actions">
                            <button type="button" class="kng-cc-code-action" id="kng-cc-fullscreen" title="<?php esc_attr_e('Fullscreen', 'king-addons'); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="type" id="kng-cc-type" value="<?php echo esc_attr($config['type']); ?>" />
                    <div class="kng-cc-code-editor-wrap" id="kng-cc-code-wrap">
                        <textarea name="code" id="kng-cc-code" class="kng-cc-code-textarea"><?php echo esc_textarea($config['code']); ?></textarea>
                    </div>
                </div>

                <!-- Notes -->
                <div class="kng-cc-editor-section kng-cc-notes-section">
                    <div class="kng-cc-section-header" data-collapsible="notes">
                        <h3 class="kng-cc-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            <?php esc_html_e('Notes', 'king-addons'); ?>
                        </h3>
                        <svg class="kng-cc-collapse-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="kng-cc-section-content" id="kng-cc-notes-content">
                        <textarea name="notes" id="kng-cc-notes" class="kng-v3-textarea" rows="3" placeholder="<?php esc_attr_e('Add notes about this snippet...', 'king-addons'); ?>"><?php echo esc_textarea($config['notes']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="kng-cc-editor-sidebar">
                <!-- Injection Settings -->
                <div class="kng-cc-sidebar-section">
                    <h3 class="kng-cc-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <?php esc_html_e('Injection', 'king-addons'); ?>
                    </h3>
                    <div class="kng-cc-sidebar-content">
                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Location', 'king-addons'); ?></label>
                            <select name="location" id="kng-cc-location" class="kng-v3-select">
                                <option value="head" <?php selected($config['location'], 'head'); ?>><?php esc_html_e('Head (wp_head)', 'king-addons'); ?></option>
                                <option value="footer" <?php selected($config['location'], 'footer'); ?>><?php esc_html_e('Footer (wp_footer)', 'king-addons'); ?></option>
                                <?php if ($has_pro): ?>
                                <option value="body_open" <?php selected($config['location'], 'body_open'); ?>><?php esc_html_e('Body Open (wp_body_open)', 'king-addons'); ?></option>
                                <option value="custom_hook" <?php selected($config['location'], 'custom_hook'); ?>><?php esc_html_e('Custom Hook', 'king-addons'); ?></option>
                                <?php else: ?>
                                <option value="body_open" disabled><?php esc_html_e('Body Open', 'king-addons'); ?> (PRO)</option>
                                <option value="custom_hook" disabled><?php esc_html_e('Custom Hook', 'king-addons'); ?> (PRO)</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="kng-v3-field kng-cc-custom-hook-field" id="kng-cc-custom-hook-wrap" style="<?php echo $config['location'] !== 'custom_hook' ? 'display: none;' : ''; ?>">
                            <label class="kng-v3-label"><?php esc_html_e('Hook Name', 'king-addons'); ?></label>
                            <input type="text" name="custom_hook" id="kng-cc-custom-hook" class="kng-v3-input" placeholder="my_custom_hook" value="<?php echo esc_attr($config['custom_hook']); ?>" />
                        </div>

                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Priority', 'king-addons'); ?></label>
                            <input type="number" name="priority" id="kng-cc-priority" class="kng-v3-input" min="1" max="9999" value="<?php echo esc_attr($config['priority']); ?>" />
                            <p class="kng-v3-field-help"><?php esc_html_e('Lower numbers run first (default: 10)', 'king-addons'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- JS Options (only for JS type) -->
                <div class="kng-cc-sidebar-section kng-cc-js-options" id="kng-cc-js-options" style="<?php echo $config['type'] !== 'js' ? 'display: none;' : ''; ?>">
                    <h3 class="kng-cc-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <rect x="2" y="7" width="20" height="15" rx="2" ry="2"/>
                            <polyline points="17 2 12 7 7 2"/>
                        </svg>
                        <?php esc_html_e('JavaScript Options', 'king-addons'); ?>
                    </h3>
                    <div class="kng-cc-sidebar-content">
                        <div class="kng-v3-field">
                            <label class="kng-v3-toggle-wrap kng-v3-toggle-row">
                                <input type="checkbox" name="js_dom_ready" id="kng-cc-js-dom-ready" <?php checked($config['js_dom_ready']); ?> />
                                <span class="kng-v3-toggle-slider"></span>
                                <span class="kng-v3-toggle-text"><?php esc_html_e('Wrap in DOMContentLoaded', 'king-addons'); ?></span>
                            </label>
                        </div>

                        <div class="kng-v3-field <?php echo !$has_pro ? 'is-pro-field' : ''; ?>">
                            <label class="kng-v3-toggle-wrap kng-v3-toggle-row">
                                <input type="checkbox" name="js_defer" id="kng-cc-js-defer" <?php checked($config['js_defer']); ?> <?php echo !$has_pro ? 'disabled' : ''; ?> />
                                <span class="kng-v3-toggle-slider"></span>
                                <span class="kng-v3-toggle-text">
                                    <?php esc_html_e('Defer', 'king-addons'); ?>
                                    <?php if (!$has_pro): ?><span class="kng-cc-pro-badge-sm">PRO</span><?php endif; ?>
                                </span>
                            </label>
                        </div>

                        <div class="kng-v3-field <?php echo !$has_pro ? 'is-pro-field' : ''; ?>">
                            <label class="kng-v3-toggle-wrap kng-v3-toggle-row">
                                <input type="checkbox" name="js_async" id="kng-cc-js-async" <?php checked($config['js_async']); ?> <?php echo !$has_pro ? 'disabled' : ''; ?> />
                                <span class="kng-v3-toggle-slider"></span>
                                <span class="kng-v3-toggle-text">
                                    <?php esc_html_e('Async', 'king-addons'); ?>
                                    <?php if (!$has_pro): ?><span class="kng-cc-pro-badge-sm">PRO</span><?php endif; ?>
                                </span>
                            </label>
                        </div>

                        <div class="kng-v3-field <?php echo !$has_pro ? 'is-pro-field' : ''; ?>">
                            <label class="kng-v3-toggle-wrap kng-v3-toggle-row">
                                <input type="checkbox" name="js_module" id="kng-cc-js-module" <?php checked($config['js_module']); ?> <?php echo !$has_pro ? 'disabled' : ''; ?> />
                                <span class="kng-v3-toggle-slider"></span>
                                <span class="kng-v3-toggle-text">
                                    <?php esc_html_e('ES Module', 'king-addons'); ?>
                                    <?php if (!$has_pro): ?><span class="kng-cc-pro-badge-sm">PRO</span><?php endif; ?>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Targeting Rules -->
                <div class="kng-cc-sidebar-section kng-cc-rules-section">
                    <h3 class="kng-cc-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <circle cx="12" cy="12" r="10"/>
                            <circle cx="12" cy="12" r="6"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                        <?php esc_html_e('Targeting Rules', 'king-addons'); ?>
                    </h3>
                    <div class="kng-cc-sidebar-content">
                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Scope', 'king-addons'); ?></label>
                            <div class="kng-cc-scope-options">
                                <label class="kng-cc-radio-card <?php echo $config['scope_mode'] === 'global' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="scope_mode" value="global" <?php checked($config['scope_mode'], 'global'); ?> />
                                    <span class="kng-cc-radio-card-content">
                                        <span class="kng-cc-radio-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                        </span>
                                        <span class="kng-cc-radio-text"><?php esc_html_e('Global', 'king-addons'); ?></span>
                                    </span>
                                </label>
                                <label class="kng-cc-radio-card <?php echo $config['scope_mode'] === 'include' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="scope_mode" value="include" <?php checked($config['scope_mode'], 'include'); ?> />
                                    <span class="kng-cc-radio-card-content">
                                        <span class="kng-cc-radio-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                <polyline points="22 4 12 14.01 9 11.01"/>
                                            </svg>
                                        </span>
                                        <span class="kng-cc-radio-text"><?php esc_html_e('Include', 'king-addons'); ?></span>
                                    </span>
                                </label>
                                <label class="kng-cc-radio-card <?php echo $config['scope_mode'] === 'exclude' ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="scope_mode" value="exclude" <?php checked($config['scope_mode'], 'exclude'); ?> />
                                    <span class="kng-cc-radio-card-content">
                                        <span class="kng-cc-radio-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                            </svg>
                                        </span>
                                        <span class="kng-cc-radio-text"><?php esc_html_e('Exclude', 'king-addons'); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Rules Builder -->
                        <div class="kng-cc-rules-builder" id="kng-cc-rules-builder" style="<?php echo $config['scope_mode'] === 'global' ? 'display: none;' : ''; ?>">
                            <?php if ($has_pro): ?>
                            <div class="kng-v3-field">
                                <label class="kng-v3-label"><?php esc_html_e('Match Mode', 'king-addons'); ?></label>
                                <select name="match_mode" id="kng-cc-match-mode" class="kng-v3-select">
                                    <option value="any" <?php selected($config['match_mode'], 'any'); ?>><?php esc_html_e('Match ANY rule', 'king-addons'); ?></option>
                                    <option value="all" <?php selected($config['match_mode'], 'all'); ?>><?php esc_html_e('Match ALL rules', 'king-addons'); ?></option>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="kng-cc-rules-list" id="kng-cc-rules-list">
                                <!-- Rules will be rendered by JS -->
                            </div>

                            <button type="button" class="kng-v3-btn kng-v3-btn--ghost kng-v3-btn--sm kng-cc-add-rule-btn" id="kng-cc-add-rule">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                <?php esc_html_e('Add Rule', 'king-addons'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!$has_pro): ?>
                <!-- Pro Features Upsell -->
                <div class="kng-cc-sidebar-section kng-cc-pro-section">
                    <div class="kng-cc-pro-box">
                        <div class="kng-cc-pro-box-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </div>
                        <h4><?php esc_html_e('Pro Features', 'king-addons'); ?></h4>
                        <ul class="kng-cc-pro-features-list">
                            <li><?php esc_html_e('HTML snippets', 'king-addons'); ?></li>
                            <li><?php esc_html_e('Unlimited snippets', 'king-addons'); ?></li>
                            <li><?php esc_html_e('Body Open & Custom Hooks', 'king-addons'); ?></li>
                            <li><?php esc_html_e('Advanced JS attributes', 'king-addons'); ?></li>
                            <li><?php esc_html_e('User role rules', 'king-addons'); ?></li>
                            <li><?php esc_html_e('Device targeting', 'king-addons'); ?></li>
                            <li><?php esc_html_e('URL regex matching', 'king-addons'); ?></li>
                        </ul>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-upgrade')); ?>" class="kng-v3-btn kng-v3-btn--accent kng-v3-btn--sm">
                            <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Rule Template -->
<script type="text/html" id="kng-cc-rule-template">
<div class="kng-cc-rule" data-index="{{index}}">
    <div class="kng-cc-rule-header">
        <select class="kng-v3-select kng-cc-rule-type" name="rules[{{index}}][type]">
            <optgroup label="<?php esc_attr_e('Content', 'king-addons'); ?>">
                <option value="page"><?php esc_html_e('Specific Page', 'king-addons'); ?></option>
                <option value="post"><?php esc_html_e('Specific Post', 'king-addons'); ?></option>
                <option value="post_type"><?php esc_html_e('Post Type', 'king-addons'); ?></option>
                <?php if ($has_pro): ?>
                <option value="taxonomy"><?php esc_html_e('Taxonomy', 'king-addons'); ?></option>
                <?php endif; ?>
            </optgroup>
            <optgroup label="<?php esc_attr_e('Special Pages', 'king-addons'); ?>">
                <option value="front_page"><?php esc_html_e('Front Page', 'king-addons'); ?></option>
                <option value="blog_page"><?php esc_html_e('Blog Page', 'king-addons'); ?></option>
                <option value="archive"><?php esc_html_e('Archive Pages', 'king-addons'); ?></option>
                <option value="search"><?php esc_html_e('Search Results', 'king-addons'); ?></option>
                <option value="404"><?php esc_html_e('404 Page', 'king-addons'); ?></option>
            </optgroup>
            <optgroup label="<?php esc_attr_e('URL', 'king-addons'); ?>">
                <option value="url_contains"><?php esc_html_e('URL Contains', 'king-addons'); ?></option>
                <?php if ($has_pro): ?>
                <option value="url_starts"><?php esc_html_e('URL Starts With', 'king-addons'); ?></option>
                <option value="url_ends"><?php esc_html_e('URL Ends With', 'king-addons'); ?></option>
                <option value="url_regex"><?php esc_html_e('URL Regex', 'king-addons'); ?></option>
                <?php endif; ?>
            </optgroup>
            <?php if ($has_pro): ?>
            <optgroup label="<?php esc_attr_e('User', 'king-addons'); ?>">
                <option value="user_logged_in"><?php esc_html_e('User Logged In', 'king-addons'); ?></option>
                <option value="user_role"><?php esc_html_e('User Role', 'king-addons'); ?></option>
            </optgroup>
            <optgroup label="<?php esc_attr_e('Device', 'king-addons'); ?>">
                <option value="device"><?php esc_html_e('Device Type', 'king-addons'); ?></option>
            </optgroup>
            <?php endif; ?>
        </select>
        <button type="button" class="kng-cc-rule-remove" title="<?php esc_attr_e('Remove', 'king-addons'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    <div class="kng-cc-rule-value">
        <!-- Value field rendered based on type -->
    </div>
</div>
</script>
