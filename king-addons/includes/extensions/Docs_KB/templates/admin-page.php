<?php
/**
 * Template: Admin Settings Page – Docs & Knowledge Base
 *
 * Uses the shared ka-admin-v3 styling framework.
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$ext     = \King_Addons\Docs_KB::instance();
$options = $ext->get_options();
$notices = [];

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $notices[] = __('Settings saved.', 'king-addons');
}

/* Admin theme mode */
$theme_mode = 'auto';
if (isset($_COOKIE['ka_admin_theme'])) {
    $theme_mode = sanitize_text_field($_COOKIE['ka_admin_theme']);
}
?>

<script>
(function() {
    var stored = document.cookie.match('(^|;)\\s*ka_admin_theme=([^;]*)');
    var mode = stored ? stored[2] : 'auto';
    var isDark = mode === 'dark' || (mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
    document.body && document.body.classList.add('ka-admin-v3');
    document.body && document.body.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<div class="ka-admin-wrap">
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon blue">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Docs & Knowledge Base', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Apple-inspired documentation center for your site', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <a href="<?php echo esc_url(get_post_type_archive_link('kng_doc')); ?>"
               target="_blank"
               class="ka-btn ka-btn-secondary"
               style="text-decoration:none;">
                <span class="dashicons dashicons-external" style="margin-top:3px;"></span>
                <?php esc_html_e('View Docs', 'king-addons'); ?>
            </a>
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

    <!-- Notices -->
    <?php foreach ($notices as $notice): ?>
        <div class="ka-card" style="background: rgba(52, 199, 89, 0.1); border: 1px solid rgba(52, 199, 89, 0.3); margin-bottom: 20px;">
            <div class="ka-card-body" style="padding: 16px 24px; display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-yes-alt" style="color: #34c759;"></span>
                <span style="color: #34c759; font-weight: 500;"><?php echo esc_html($notice); ?></span>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Tabs -->
    <div class="ka-tabs">
        <button type="button" class="ka-tab active" data-tab="general"><?php esc_html_e('General', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="content"><?php esc_html_e('Content', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="design"><?php esc_html_e('Design', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="features"><?php esc_html_e('Features', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="manage"><?php esc_html_e('Manage', 'king-addons'); ?></button>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('king_addons_docs_kb_save', 'king_docs_kb_nonce'); ?>
        <input type="hidden" name="action" value="king_addons_docs_kb_save">

        <!-- ══════════════════════════════════════
             TAB: General
             ══════════════════════════════════════ -->
        <div class="ka-tab-content active" data-tab="general">

            <!-- Enable Extension -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <h2><?php esc_html_e('Extension Status', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Docs & KB', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($options['enabled'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Enable the documentation system on the frontend', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archive Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-home"></span>
                    <h2><?php esc_html_e('Archive Page', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Title', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text"
                                   name="archive_title"
                                   class="ka-input"
                                   value="<?php echo esc_attr($options['archive_title'] ?? __('How can we help?', 'king-addons')); ?>"
                                   placeholder="<?php esc_attr_e('How can we help?', 'king-addons'); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Subtitle', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text"
                                   name="archive_subtitle"
                                   class="ka-input"
                                   value="<?php echo esc_attr($options['archive_subtitle'] ?? __('Find guides, tutorials and answers to your questions.', 'king-addons')); ?>"
                                   placeholder="<?php esc_attr_e('Find guides, tutorials...', 'king-addons'); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('URL Slug', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text"
                                   name="docs_slug"
                                   class="ka-input"
                                   value="<?php echo esc_attr($options['docs_slug'] ?? 'docs'); ?>"
                                   placeholder="docs">
                            <p class="ka-field-hint"><?php esc_html_e('After changing, go to Settings → Permalinks and click Save to flush rewrite rules.', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Articles per page', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number"
                                   name="docs_per_page"
                                   class="ka-input"
                                   min="1"
                                   max="100"
                                   value="<?php echo esc_attr($options['docs_per_page'] ?? 12); ?>"
                                   style="width:100px;">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════
             TAB: Content
             ══════════════════════════════════════ -->
        <div class="ka-tab-content" data-tab="content">

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-edit"></span>
                    <h2><?php esc_html_e('Content Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Feedback question', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text"
                                   name="feedback_question"
                                   class="ka-input"
                                   value="<?php echo esc_attr($options['feedback_question'] ?? __('Was this article helpful?', 'king-addons')); ?>"
                                   placeholder="<?php esc_attr_e('Was this article helpful?', 'king-addons'); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Thank you message', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text"
                                   name="feedback_thanks"
                                   class="ka-input"
                                   value="<?php echo esc_attr($options['feedback_thanks'] ?? __('Thank you for your feedback!', 'king-addons')); ?>"
                                   placeholder="<?php esc_attr_e('Thank you for your feedback!', 'king-addons'); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Empty state text', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text"
                                   name="empty_text"
                                   class="ka-input"
                                   value="<?php echo esc_attr($options['empty_text'] ?? __('No articles yet', 'king-addons')); ?>">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════
             TAB: Design
             ══════════════════════════════════════ -->
        <div class="ka-tab-content" data-tab="design">

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-art"></span>
                    <h2><?php esc_html_e('Appearance', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">

                    <!-- Layout -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Layout', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <?php
                            $layouts = [
                                'glass-card' => __('Glass Card', 'king-addons'),
                                'glass-list' => __('Glass List', 'king-addons'),
                                'glass-grid' => __('Glass Grid', 'king-addons'),
                            ];
                            $current_layout = $options['layout'] ?? 'glass-card';
                            ?>
                            <div class="ka-v3-segmented" id="ka-docs-layout-segment" data-active="<?php echo esc_attr($current_layout); ?>" style="max-width:400px;" role="radiogroup" aria-label="Layout">
                                <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
                                <?php foreach ($layouts as $key => $label) : ?>
                                    <button type="button"
                                            class="ka-v3-segmented-btn <?php echo $current_layout === $key ? 'active' : ''; ?>"
                                            data-layout="<?php echo esc_attr($key); ?>"
                                            aria-pressed="<?php echo $current_layout === $key ? 'true' : 'false'; ?>">
                                        <?php echo esc_html($label); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="layout" id="ka-docs-layout" value="<?php echo esc_attr($current_layout); ?>">
                        </div>
                    </div>

                    <!-- Primary Color -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Primary Color', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <input type="color"
                                       name="primary_color"
                                       value="<?php echo esc_attr($options['primary_color'] ?? '#0071e3'); ?>"
                                       style="width:48px; height:36px; border:1px solid rgba(0,0,0,0.1); border-radius:8px; cursor:pointer;">
                                <input type="text"
                                       class="ka-input"
                                       value="<?php echo esc_attr($options['primary_color'] ?? '#0071e3'); ?>"
                                       style="width:100px; font-family:monospace;"
                                       readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Dark Mode -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Color Scheme', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="dark_mode" class="ka-select">
                                <option value="light" <?php selected($options['dark_mode'] ?? 'auto', 'light'); ?>><?php esc_html_e('Light', 'king-addons'); ?></option>
                                <option value="dark" <?php selected($options['dark_mode'] ?? 'auto', 'dark'); ?>><?php esc_html_e('Dark', 'king-addons'); ?></option>
                                <option value="auto" <?php selected($options['dark_mode'] ?? 'auto', 'auto'); ?>><?php esc_html_e('Auto (system preference)', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Columns -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Columns', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="columns" class="ka-select" style="width:100px;">
                                <option value="2" <?php selected($options['columns'] ?? 3, 2); ?>>2</option>
                                <option value="3" <?php selected($options['columns'] ?? 3, 3); ?>>3</option>
                                <option value="4" <?php selected($options['columns'] ?? 3, 4); ?>>4</option>
                            </select>
                            <p class="ka-field-hint"><?php esc_html_e('Number of columns on desktop.', 'king-addons'); ?></p>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════
             TAB: Features
             ══════════════════════════════════════ -->
        <div class="ka-tab-content" data-tab="features">

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <h2><?php esc_html_e('Article Features', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">

                    <!-- Table of Contents -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Table of Contents', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="toc_enabled" value="1" <?php checked(!empty($options['toc_enabled'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Auto-generate TOC from headings', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Reading Time -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Reading Time', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="reading_time" value="1" <?php checked(!empty($options['reading_time'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show estimated reading time', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Reactions -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Reactions', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="reactions_enabled" value="1" <?php checked(!empty($options['reactions_enabled'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Emoji reactions (😊 😐 😞)', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Social Share -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Social Share', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="social_share" value="1" <?php checked(!empty($options['social_share'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show share buttons (Copy, Twitter, Facebook, LinkedIn)', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════
             TAB: Manage
             ══════════════════════════════════════ -->
        <div class="ka-tab-content" data-tab="manage">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-welcome-write-blog"></span>
                    <h2><?php esc_html_e('Create & Manage Content', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <p style="margin:0 0 12px; color:rgba(0,0,0,.65);">
                        <?php esc_html_e('All Docs KB content is managed from here. Use these shortcuts to create articles and organize them with categories/tags.', 'king-addons'); ?>
                    </p>

                    <div style="display:flex; flex-wrap:wrap; gap:12px;">
                        <a class="ka-btn ka-btn-primary" style="text-decoration:none;" href="<?php echo esc_url(admin_url('edit.php?post_type=kng_doc')); ?>">
                            <?php esc_html_e('All Docs', 'king-addons'); ?>
                        </a>
                        <a class="ka-btn ka-btn-secondary" style="text-decoration:none;" href="<?php echo esc_url(admin_url('post-new.php?post_type=kng_doc')); ?>">
                            <?php esc_html_e('Add New Doc', 'king-addons'); ?>
                        </a>
                        <a class="ka-btn ka-btn-secondary" style="text-decoration:none;" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=kng_doc_category&post_type=kng_doc')); ?>">
                            <?php esc_html_e('Doc Categories', 'king-addons'); ?>
                        </a>
                        <a class="ka-btn ka-btn-secondary" style="text-decoration:none;" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=kng_doc_tag&post_type=kng_doc')); ?>">
                            <?php esc_html_e('Doc Tags', 'king-addons'); ?>
                        </a>
                    </div>

                    <div style="margin-top:14px; color:rgba(0,0,0,.65);">
                        <strong><?php esc_html_e('Tip:', 'king-addons'); ?></strong>
                        <?php esc_html_e('Create a category first, then assign it to your docs so they appear on the Docs page.', 'king-addons'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save -->
        <div class="ka-save-bar">
            <button type="submit" class="ka-btn ka-btn-primary">
                <span class="dashicons dashicons-saved" style="margin-top:3px;"></span>
                <?php esc_html_e('Save Settings', 'king-addons'); ?>
            </button>
        </div>

    </form>
</div>

<style>
#ka-docs-layout-segment[data-active="glass-list"] .ka-v3-segmented-indicator { transform: translateX(100%); }
#ka-docs-layout-segment[data-active="glass-grid"] .ka-v3-segmented-indicator { transform: translateX(200%); }
</style>
<script>
(function () {
    /* Tabs */
    document.querySelectorAll('.ka-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = this.dataset.tab;
            document.querySelectorAll('.ka-tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.ka-tab-content').forEach(function (c) { c.classList.remove('active'); });
            this.classList.add('active');
            document.querySelector('.ka-tab-content[data-tab="' + target + '"]').classList.add('active');
        });
    });

    /* Layout selector */
    document.querySelectorAll('[data-layout]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-layout]').forEach(function (b) {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-pressed', 'true');
            document.getElementById('ka-docs-layout').value = this.dataset.layout;
            var seg = document.getElementById('ka-docs-layout-segment');
            if (seg) seg.dataset.active = this.dataset.layout;
        });
    });

    /* Color picker sync */
    var colorInput = document.querySelector('input[name="primary_color"]');
    if (colorInput) {
        var textDisplay = colorInput.nextElementSibling;
        if (!textDisplay) {
            textDisplay = colorInput.parentElement.querySelector('input[type="text"]');
        }
        colorInput.addEventListener('input', function () {
            if (textDisplay) textDisplay.value = this.value;
        });
    }

    /* Admin theme toggle */
    var seg = document.getElementById('ka-v3-theme-segment');
    if (seg) {
        seg.querySelectorAll('.ka-v3-segmented-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var mode = this.dataset.theme;
                seg.dataset.active = mode;
                seg.querySelectorAll('.ka-v3-segmented-btn').forEach(function(b) {
                    b.setAttribute('aria-pressed', 'false');
                });
                this.setAttribute('aria-pressed', 'true');
                var isDark = mode === 'dark' || (mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('ka-v3-dark', isDark);
                document.body.classList.toggle('ka-v3-dark', isDark);
                document.cookie = 'ka_admin_theme=' + mode + ';path=/;max-age=31536000;SameSite=Lax';
            });
        });
    }
})();
</script>
