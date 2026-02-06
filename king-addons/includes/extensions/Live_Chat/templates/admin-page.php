<?php
/**
 * Live Chat admin page template.
 *
 * @package King_Addons
 * @var array $options Current options
 * @var bool $is_premium Whether premium is active
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue shared V3 styles
wp_enqueue_style(
    'king-addons-admin-v3',
    KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
    [],
    KING_ADDONS_VERSION
);

// Theme mode is per-user
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'inbox';
$conversation_id = isset($_GET['conversation']) ? intval($_GET['conversation']) : 0;
$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
?>
<style>
/* Page specific styles */
.ka-live-chat-page .ka-admin-wrap {
    --ka-accent: #0066ff;
    --ka-accent-hover: #0052cc;
}

/* Widget Mode Selector */
.ka-widget-mode-selector {
    display: grid;
    gap: 12px;
}

.ka-mode-option {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 20px;
    background: #f8f9fa;
    border: 2px solid #e5e5ea;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ka-mode-option:hover {
    border-color: #c5c5ca;
    background: #f5f5f7;
}

.ka-mode-option.active,
.ka-mode-option:has(input:checked) {
    border-color: var(--ka-accent);
    background: #f0f7ff;
}

.ka-mode-option input {
    display: none;
}

.ka-mode-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--ka-accent) 0%, #5ac8fa 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ka-mode-icon .dashicons {
    color: #fff;
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.ka-mode-content {
    flex: 1;
}

.ka-mode-title {
    display: block;
    font-size: 15px;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 4px;
}

.ka-mode-desc {
    display: block;
    font-size: 13px;
    color: #6e6e73;
    line-height: 1.4;
}

.ka-v3-dark .ka-mode-option {
    background: #1e1e1e;
    border-color: #3a3a3a;
}

.ka-v3-dark .ka-mode-option:hover {
    background: #2a2a2a;
    border-color: #4a4a4a;
}

.ka-v3-dark .ka-mode-option.active,
.ka-v3-dark .ka-mode-option:has(input:checked) {
    border-color: var(--ka-accent);
    background: #0d1f33;
}

.ka-v3-dark .ka-mode-title {
    color: #f5f5f7;
}

.ka-v3-dark .ka-mode-desc {
    color: #8e8e93;
}

/* Inbox Layout */
.ka-inbox-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 24px;
    min-height: 600px;
}

.ka-inbox-list {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.ka-inbox-header {
    padding: 20px;
    border-bottom: 1px solid #f1f1f4;
}

.ka-inbox-filters {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.ka-inbox-filter {
    padding: 6px 12px;
    border: none;
    background: #f5f5f7;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.ka-inbox-filter:hover,
.ka-inbox-filter.active {
    background: var(--ka-accent);
    color: #fff;
}

.ka-inbox-search {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e5ea;
    border-radius: 10px;
    font-size: 14px;
}

.ka-inbox-search:focus {
    outline: none;
    border-color: var(--ka-accent);
}

.ka-inbox-conversations {
    flex: 1;
    overflow-y: auto;
}

.ka-inbox-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f1f4;
    cursor: pointer;
    transition: background 0.2s;
}

.ka-inbox-item:hover,
.ka-inbox-item.active {
    background: #f8f9fa;
}

.ka-inbox-item.unread {
    background: #f0f7ff;
}

.ka-inbox-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--ka-accent);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.ka-inbox-item-content {
    flex: 1;
    min-width: 0;
}

.ka-inbox-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.ka-inbox-item-name {
    font-weight: 600;
    font-size: 14px;
    color: #1d1d1f;
}

.ka-inbox-item-time {
    font-size: 12px;
    color: #86868b;
}

.ka-inbox-item-preview {
    font-size: 13px;
    color: #6e6e73;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ka-inbox-badge {
    background: var(--ka-accent);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.ka-inbox-empty {
    padding: 40px 20px;
    text-align: center;
    color: #86868b;
}

/* Conversation View */
.ka-conversation-view {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.ka-conversation-header {
    padding: 20px;
    border-bottom: 1px solid #f1f1f4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ka-conversation-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ka-conversation-details h3 {
    margin: 0 0 4px;
    font-size: 16px;
    font-weight: 600;
}

.ka-conversation-email {
    font-size: 13px;
    color: #6e6e73;
}

.ka-conversation-actions {
    display: flex;
    gap: 8px;
}

.ka-conversation-btn {
    padding: 8px 16px;
    border: 1px solid #e5e5ea;
    background: #fff;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ka-conversation-btn:hover {
    border-color: var(--ka-accent);
    color: var(--ka-accent);
}

.ka-conversation-btn--danger:hover {
    border-color: #ff3b30;
    color: #ff3b30;
}

.ka-conversation-btn--primary {
    background: var(--ka-accent);
    color: #fff;
    border-color: var(--ka-accent);
}

.ka-conversation-btn--primary:hover {
    background: var(--ka-accent-hover);
    color: #fff;
}

/* Messages */
.ka-conversation-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 400px;
}

.ka-message {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.5;
}

.ka-message--visitor {
    background: #f5f5f7;
    color: #1d1d1f;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}

.ka-message--admin {
    background: var(--ka-accent);
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}

.ka-message-meta {
    font-size: 11px;
    margin-top: 6px;
    opacity: 0.7;
}

/* Reply Form */
.ka-conversation-reply {
    padding: 20px;
    border-top: 1px solid #f1f1f4;
}

.ka-reply-form {
    display: flex;
    gap: 12px;
}

.ka-reply-textarea {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #e5e5ea;
    border-radius: 12px;
    font-size: 14px;
    resize: none;
    min-height: 80px;
}

.ka-reply-textarea:focus {
    outline: none;
    border-color: var(--ka-accent);
}

.ka-reply-send {
    padding: 12px 24px;
    background: var(--ka-accent);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    align-self: flex-end;
}

.ka-reply-send:hover {
    background: var(--ka-accent-hover);
}

.ka-reply-send:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Visitor Info Sidebar */
.ka-visitor-info {
    padding: 20px;
    border-top: 1px solid #f1f1f4;
    background: #fafafa;
}

.ka-visitor-info h4 {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: #86868b;
    text-transform: uppercase;
}

.ka-visitor-info-item {
    display: flex;
    gap: 8px;
    font-size: 13px;
    margin-bottom: 8px;
    word-break: break-all;
}

.ka-visitor-info-label {
    color: #86868b;
    flex-shrink: 0;
    width: 80px;
}

.ka-visitor-info-value {
    color: #1d1d1f;
}

/* Empty State */
.ka-conversation-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #86868b;
}

.ka-conversation-empty svg {
    width: 80px;
    height: 80px;
    margin-bottom: 16px;
    opacity: 0.3;
}

/* Status Badge */
.ka-status-open {
    color: #34c759;
}

.ka-status-closed {
    color: #86868b;
}

/* Loading */
.ka-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.ka-loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #f1f1f4;
    border-top-color: var(--ka-accent);
    border-radius: 50%;
    animation: ka-spin 0.8s linear infinite;
}

@keyframes ka-spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1024px) {
    .ka-inbox-layout {
        grid-template-columns: 1fr;
    }
    
    .ka-inbox-list {
        max-height: 400px;
    }
}

/* Dark Mode - Full Dark Theme */
.ka-v3-dark .ka-live-chat-page {
    background: #0a0a0a;
}

.ka-v3-dark .ka-admin-wrap {
    background: #0a0a0a;
}

.ka-v3-dark .ka-inbox-list,
.ka-v3-dark .ka-conversation-view,
.ka-v3-dark .ka-card {
    background: #141414;
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-inbox-header {
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-inbox-item {
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-inbox-item:hover,
.ka-v3-dark .ka-inbox-item.active {
    background: #1e1e1e;
}

.ka-v3-dark .ka-inbox-item.unread {
    background: #0d1f33;
}

.ka-v3-dark .ka-inbox-item-name,
.ka-v3-dark .ka-conversation-details h3,
.ka-v3-dark .ka-card-header h2 {
    color: #f5f5f7;
}

.ka-v3-dark .ka-inbox-item-preview,
.ka-v3-dark .ka-inbox-item-time {
    color: #8e8e93;
}

.ka-v3-dark .ka-inbox-search,
.ka-v3-dark .ka-reply-textarea,
.ka-v3-dark .ka-row-field input,
.ka-v3-dark .ka-row-field select,
.ka-v3-dark .ka-row-field textarea {
    background: #1e1e1e;
    border-color: #3a3a3a;
    color: #f5f5f7;
}

.ka-v3-dark .ka-inbox-search:focus,
.ka-v3-dark .ka-row-field input:focus,
.ka-v3-dark .ka-row-field select:focus,
.ka-v3-dark .ka-row-field textarea:focus {
    border-color: var(--ka-accent);
    background: #1e1e1e;
}

.ka-v3-dark .ka-inbox-filter {
    background: #1e1e1e;
    color: #f5f5f7;
}

.ka-v3-dark .ka-inbox-filter.active,
.ka-v3-dark .ka-inbox-filter:hover {
    background: var(--ka-accent);
    color: #fff;
}

.ka-v3-dark .ka-message--visitor {
    background: #1e1e1e;
    color: #f5f5f7;
}

.ka-v3-dark .ka-message--admin {
    background: var(--ka-accent);
    color: #fff;
}

.ka-v3-dark .ka-visitor-info {
    background: #0f0f0f;
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-visitor-info dt {
    color: #8e8e93;
}

.ka-v3-dark .ka-visitor-info dd {
    color: #f5f5f7;
}

.ka-v3-dark .ka-conversation-btn {
    background: #1e1e1e;
    border-color: #3a3a3a;
    color: #f5f5f7;
}

.ka-v3-dark .ka-conversation-btn:hover {
    background: #2a2a2a;
    border-color: var(--ka-accent);
    color: var(--ka-accent);
}

.ka-v3-dark .ka-conversation-header,
.ka-v3-dark .ka-card-header {
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-conversation-empty {
    color: #6e6e73;
}

.ka-v3-dark .ka-conversation-empty svg {
    stroke: #4a4a4a;
}

.ka-v3-dark .ka-row-label {
    color: #c7c7cc;
}

.ka-v3-dark .ka-row-desc {
    color: #6e6e73;
}

.ka-v3-dark .ka-tabs {
    background: #141414;
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-tab {
    color: #8e8e93;
}

.ka-v3-dark .ka-tab:hover,
.ka-v3-dark .ka-tab.active {
    color: var(--ka-accent);
}

.ka-v3-dark .ka-admin-header {
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-admin-title {
    color: #f5f5f7;
}

.ka-v3-dark .ka-admin-subtitle {
    color: #8e8e93;
}

/* Save Button Styles */
.ka-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(0, 102, 255, 0.25);
}

.ka-save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 102, 255, 0.35);
}

.ka-save-btn:active {
    transform: translateY(0);
}

.ka-save-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.ka-card-footer {
    background: transparent;
    box-shadow: none;
    padding: 24px 0;
}

.ka-v3-dark .ka-save-btn {
    background: linear-gradient(135deg, #0077ff 0%, #0055cc 100%);
    box-shadow: 0 4px 15px rgba(0, 102, 255, 0.3);
}

.ka-v3-dark .ka-save-btn:hover {
    box-shadow: 0 6px 20px rgba(0, 102, 255, 0.4);
}
</style>

<div class="wrap ka-live-chat-page">
    <script>
    (function() {
        const mode = '<?php echo esc_js($theme_mode); ?>';
        const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
        document.documentElement.classList.toggle('ka-v3-dark', isDark);
        document.body.classList.toggle('ka-v3-dark', isDark);
        document.body.classList.add('ka-admin-v3');
    })();
    </script>

    <div class="ka-admin-wrap">
        <!-- Header -->
        <div class="ka-admin-header">
            <div class="ka-admin-header-left">
                <div class="ka-admin-header-icon" style="background: linear-gradient(135deg, #0066ff 0%, #5ac8fa 100%);">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <div>
                    <h1 class="ka-admin-title"><?php esc_html_e('Live Chat', 'king-addons'); ?></h1>
                    <p class="ka-admin-subtitle"><?php esc_html_e('Real-time customer support', 'king-addons'); ?></p>
                </div>
            </div>
            <div class="ka-admin-header-actions">
                <?php if (!empty($options['enabled'])): ?>
                <span class="ka-status-badge enabled">
                    <span class="ka-status-badge-dot"></span>
                    <?php esc_html_e('Online', 'king-addons'); ?>
                </span>
                <?php else: ?>
                <span class="ka-status-badge disabled">
                    <span class="ka-status-badge-dot"></span>
                    <?php esc_html_e('Offline', 'king-addons'); ?>
                </span>
                <?php endif; ?>
                <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="Theme" data-active="<?php echo esc_attr($theme_mode); ?>">
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
        </div>

        <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible" style="margin: 20px 0;">
            <p><?php esc_html_e('Settings saved successfully.', 'king-addons'); ?></p>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="ka-tabs">
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-live-chat&tab=inbox')); ?>" 
               class="ka-tab <?php echo $current_tab === 'inbox' ? 'active' : ''; ?>">
                <?php esc_html_e('Inbox', 'king-addons'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-live-chat&tab=settings')); ?>" 
               class="ka-tab <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                <?php esc_html_e('Settings', 'king-addons'); ?>
            </a>
        </div>

        <?php if ($current_tab === 'inbox'): ?>
        <!-- Inbox View -->
        <div class="ka-inbox-layout">
            <!-- Conversations List -->
            <div class="ka-inbox-list">
                <div class="ka-inbox-header">
                    <div class="ka-inbox-filters">
                        <button type="button" class="ka-inbox-filter active" data-status="all">
                            <?php esc_html_e('All', 'king-addons'); ?>
                        </button>
                        <button type="button" class="ka-inbox-filter" data-status="open">
                            <?php esc_html_e('Open', 'king-addons'); ?>
                        </button>
                        <button type="button" class="ka-inbox-filter" data-status="closed">
                            <?php esc_html_e('Closed', 'king-addons'); ?>
                        </button>
                    </div>
                    <input type="text" class="ka-inbox-search" placeholder="<?php esc_attr_e('Search conversations...', 'king-addons'); ?>">
                </div>
                <div class="ka-inbox-conversations" id="ka-inbox-conversations">
                    <div class="ka-loading">
                        <div class="ka-loading-spinner"></div>
                    </div>
                </div>
            </div>

            <!-- Conversation View -->
            <div class="ka-conversation-view" id="ka-conversation-view">
                <div class="ka-conversation-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                    </svg>
                    <p><?php esc_html_e('Select a conversation to view messages', 'king-addons'); ?></p>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Settings View -->
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="king_addons_live_chat_save">
            <?php wp_nonce_field('king_addons_live_chat_save', 'king_live_chat_nonce'); ?>

            <!-- General Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Chat Widget', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($options['enabled'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show chat widget on frontend', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Widget Mode -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Widget Mode', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-widget-mode-selector">
                                <label class="ka-mode-option <?php echo ($options['widget_mode'] ?? 'live_chat') === 'live_chat' ? 'active' : ''; ?>">
                                    <input type="radio" name="widget_mode" value="live_chat" <?php checked(($options['widget_mode'] ?? 'live_chat'), 'live_chat'); ?>>
                                    <span class="ka-mode-icon">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </span>
                                    <span class="ka-mode-content">
                                        <span class="ka-mode-title"><?php esc_html_e('Live Chat', 'king-addons'); ?></span>
                                        <span class="ka-mode-desc"><?php esc_html_e('Real-time messaging with polling. Visitors can have ongoing conversations.', 'king-addons'); ?></span>
                                    </span>
                                </label>
                                <label class="ka-mode-option <?php echo ($options['widget_mode'] ?? 'live_chat') === 'contact_form' ? 'active' : ''; ?>">
                                    <input type="radio" name="widget_mode" value="contact_form" <?php checked(($options['widget_mode'] ?? 'live_chat'), 'contact_form'); ?>>
                                    <span class="ka-mode-icon">
                                        <span class="dashicons dashicons-email-alt"></span>
                                    </span>
                                    <span class="ka-mode-content">
                                        <span class="ka-mode-title"><?php esc_html_e('Contact Form', 'king-addons'); ?></span>
                                        <span class="ka-mode-desc"><?php esc_html_e('Simple contact form. Messages are sent via email, no real-time chat.', 'king-addons'); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Position', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <select name="position">
                                    <option value="right" <?php selected($options['position'], 'right'); ?>><?php esc_html_e('Right', 'king-addons'); ?></option>
                                    <option value="left" <?php selected($options['position'], 'left'); ?>><?php esc_html_e('Left', 'king-addons'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Z-Index', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="z_index" value="<?php echo esc_attr($options['z_index']); ?>" min="1">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Bottom Offset', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="offset_bottom" value="<?php echo esc_attr($options['offset_bottom']); ?>" min="0">
                                <span style="color:#64748b;margin-left:6px">px</span>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Side Offset', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="offset_side" value="<?php echo esc_attr($options['offset_side']); ?>" min="0">
                                <span style="color:#64748b;margin-left:6px">px</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-art"></span>
                    <h2><?php esc_html_e('Appearance', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Button Size', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="button_size" value="<?php echo esc_attr($options['button_size']); ?>" min="40" max="80">
                                <span style="color:#64748b;margin-left:6px">px</span>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Button Color', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="button_color" value="<?php echo esc_attr($options['button_color']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Chat Width', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="chat_width" value="<?php echo esc_attr($options['chat_width']); ?>" min="300" max="500">
                                <span style="color:#64748b;margin-left:6px">px</span>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Chat Height', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="chat_height" value="<?php echo esc_attr($options['chat_height']); ?>" min="400" max="700">
                                <span style="color:#64748b;margin-left:6px">px</span>
                            </div>
                        </div>
                    </div>

                    <h4 style="margin: 24px 0 16px; color: #6e6e73; font-size: 13px; text-transform: uppercase;"><?php esc_html_e('Colors', 'king-addons'); ?></h4>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Header Background', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="header_bg" value="<?php echo esc_attr($options['header_bg']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Header Text', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="header_text_color" value="<?php echo esc_attr($options['header_text_color']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Chat Background', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="chat_bg" value="<?php echo esc_attr($options['chat_bg']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                    </div>

                    <h4 style="margin: 24px 0 16px; color: #6e6e73; font-size: 13px; text-transform: uppercase;"><?php esc_html_e('Message Bubbles', 'king-addons'); ?></h4>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Visitor Message BG', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="visitor_msg_bg" value="<?php echo esc_attr($options['visitor_msg_bg']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Visitor Message Text', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="visitor_msg_text" value="<?php echo esc_attr($options['visitor_msg_text']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Admin Message BG', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="admin_msg_bg" value="<?php echo esc_attr($options['admin_msg_bg']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Admin Message Text', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="admin_msg_text" value="<?php echo esc_attr($options['admin_msg_text']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Texts -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-editor-textcolor"></span>
                    <h2><?php esc_html_e('Texts', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Header Title', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="header_title" value="<?php echo esc_attr($options['header_title']); ?>">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Header Subtitle', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="header_subtitle" value="<?php echo esc_attr($options['header_subtitle']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Input Placeholder', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="placeholder" value="<?php echo esc_attr($options['placeholder']); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Welcome Message', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <textarea name="welcome_message" rows="2"><?php echo esc_textarea($options['welcome_message']); ?></textarea>
                            <p class="ka-row-desc"><?php esc_html_e('Shown as first message when chat is opened', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Offline Message', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <textarea name="offline_message" rows="2"><?php echo esc_textarea($options['offline_message']); ?></textarea>
                        </div>
                    </div>

                    <!-- Contact Form Mode specific -->
                    <div class="ka-contact-form-fields" style="display: <?php echo ($options['widget_mode'] ?? 'live_chat') === 'contact_form' ? 'block' : 'none'; ?>;">
                        <h4 style="margin: 24px 0 16px; color: #6e6e73; font-size: 13px; text-transform: uppercase;"><?php esc_html_e('Contact Form Mode', 'king-addons'); ?></h4>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Subject Label', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="subject_label" value="<?php echo esc_attr($options['subject_label'] ?? __('Subject', 'king-addons')); ?>">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Message Label', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="message_label" value="<?php echo esc_attr($options['message_label'] ?? __('Your message', 'king-addons')); ?>">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Submit Button', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="submit_button" value="<?php echo esc_attr($options['submit_button'] ?? __('Send Message', 'king-addons')); ?>">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Success Message', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <textarea name="success_message" rows="2"><?php echo esc_textarea($options['success_message'] ?? __('Thank you! Your message has been sent. We\'ll get back to you soon.', 'king-addons')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pre-chat Form -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-id-alt"></span>
                    <h2><?php esc_html_e('Pre-chat Form', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Require Name', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="require_name" value="1" <?php checked(!empty($options['require_name'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Require Email', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="require_email" value="1" <?php checked(!empty($options['require_email'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Name Label', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="name_label" value="<?php echo esc_attr($options['name_label']); ?>">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Email Label', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="email_label" value="<?php echo esc_attr($options['email_label']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Start Chat Button', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="start_chat_button" value="<?php echo esc_attr($options['start_chat_button']); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Notifications -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-email"></span>
                    <h2><?php esc_html_e('Email Notifications', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Admin Email', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="email" name="admin_email" value="<?php echo esc_attr($options['admin_email']); ?>">
                            <p class="ka-row-desc"><?php esc_html_e('Where to send new conversation notifications', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('New Conversation', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="notify_new_conversation" value="1" <?php checked(!empty($options['notify_new_conversation'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Email on new conversation', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('New Messages', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="notify_new_message" value="1" <?php checked(!empty($options['notify_new_message'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Email on each new message', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Admin Email Subject', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="email_subject_admin" value="<?php echo esc_attr($options['email_subject_admin']); ?>">
                                <p class="ka-row-desc"><?php esc_html_e('Use {visitor_name}, {site_name}', 'king-addons'); ?></p>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Visitor Email Subject', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="email_subject_visitor" value="<?php echo esc_attr($options['email_subject_visitor']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <h2><?php esc_html_e('Advanced', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Polling Interval', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="poll_interval" value="<?php echo esc_attr($options['poll_interval']); ?>" min="2000" max="10000" step="500">
                            <span style="color:#64748b;margin-left:6px">ms</span>
                            <p class="ka-row-desc"><?php esc_html_e('How often to check for new messages (lower = faster but more server load)', 'king-addons'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="ka-card ka-card-footer">
                <button type="submit" class="ka-save-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Settings', 'king-addons'); ?>
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';
        const segment = document.getElementById('ka-v3-theme-segment');
        const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        let mode = (segment && segment.getAttribute('data-active') ? segment.getAttribute('data-active') : 'dark').toString();
        let mqlHandler = null;

        function saveUISetting(key, value) {
            const body = new URLSearchParams();
            body.set('action', 'king_addons_save_dashboard_ui');
            body.set('nonce', nonce);
            body.set('key', key);
            body.set('value', value);
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            });
        }

        function updateSegment(activeMode) {
            if (!segment) {
                return;
            }
            segment.setAttribute('data-active', activeMode);
            segment.querySelectorAll('.ka-v3-segmented-btn').forEach((btn) => {
                const theme = btn.getAttribute('data-theme');
                btn.setAttribute('aria-pressed', theme === activeMode ? 'true' : 'false');
            });
        }

        function applyTheme(isDark) {
            document.body.classList.toggle('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function setThemeMode(nextMode, save) {
            mode = nextMode;
            updateSegment(nextMode);

            if (mqlHandler && mql) {
                if (mql.removeEventListener) {
                    mql.removeEventListener('change', mqlHandler);
                } else if (mql.removeListener) {
                    mql.removeListener(mqlHandler);
                }
                mqlHandler = null;
            }

            if (nextMode === 'auto') {
                applyTheme(!!(mql && mql.matches));
                mqlHandler = (e) => {
                    if (mode !== 'auto') {
                        return;
                    }
                    applyTheme(!!e.matches);
                };
                if (mql) {
                    if (mql.addEventListener) {
                        mql.addEventListener('change', mqlHandler);
                    } else if (mql.addListener) {
                        mql.addListener(mqlHandler);
                    }
                }
            } else {
                applyTheme(nextMode === 'dark');
            }

            if (save) {
                saveUISetting('theme_mode', nextMode);
            }
        }

        window.kaV3ToggleDark = function() {
            const isDark = document.body.classList.contains('ka-v3-dark');
            setThemeMode(isDark ? 'light' : 'dark', true);
        };

        if (segment) {
            segment.addEventListener('click', function(e) {
                const btn = e.target.closest('.ka-v3-segmented-btn');
                if (!btn) return;
                e.preventDefault();
                setThemeMode((btn.getAttribute('data-theme') || 'dark').toString(), true);
            });
        }

        if (segment) {
            setThemeMode(mode, false);
        }
    })();

    // Widget mode toggle
    document.querySelectorAll('input[name="widget_mode"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const contactFormFields = document.querySelector('.ka-contact-form-fields');
            const modeOptions = document.querySelectorAll('.ka-mode-option');
            
            modeOptions.forEach(function(opt) {
                opt.classList.remove('active');
            });
            
            this.closest('.ka-mode-option').classList.add('active');
            
            if (contactFormFields) {
                contactFormFields.style.display = this.value === 'contact_form' ? 'block' : 'none';
            }
        });
    });
    </script>
</div>
