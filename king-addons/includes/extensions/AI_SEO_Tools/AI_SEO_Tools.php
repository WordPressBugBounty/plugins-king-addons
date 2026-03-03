<?php
/**
 * AI SEO Tools extension bootstrap.
 *
 * @package King_Addons
 */

namespace King_Addons\AI_SEO_Tools;

use King_Addons\Alt_Text_Generator;

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Tools
{
    private static ?AI_SEO_Tools $instance = null;

    private Alt_Text_Generator $alt_text_generator;

    private Bulk_Alt_Text_Module $bulk_alt_module;

    private Auto_Tagging_Module $auto_tagging_module;

    private Post_Generator_Module $post_gen_module;

    public static function instance(): AI_SEO_Tools
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        require_once KING_ADDONS_PATH . 'includes/extensions/alt-text-generator/Alt_Text_Generator.php';
        require_once __DIR__ . '/Bulk_Alt_Text_Module.php';
        require_once __DIR__ . '/Auto_Tagging_Module.php';
        require_once __DIR__ . '/Post_Generator_Module.php';

        $this->alt_text_generator = new Alt_Text_Generator();
        $this->bulk_alt_module = new Bulk_Alt_Text_Module($this->alt_text_generator);
        $this->auto_tagging_module = new Auto_Tagging_Module();
        $this->post_gen_module = new Post_Generator_Module();

        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_ajax_king_addons_ai_seo_save_settings', [$this, 'handle_ajax_save_settings']);
    }

    public function handle_ajax_save_settings(): void
    {
        check_ajax_referer('king_addons_ai_seo_save_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $existing = get_option('king_addons_ai_options', []);

        $existing['auto_tagging_max_tags'] = max(1, min(20, absint($_POST['auto_tagging_max_tags'] ?? 5)));
        $existing['auto_tagging_confidence_threshold'] = max(0.0, min(1.0, (float) ($_POST['auto_tagging_confidence_threshold'] ?? 0.75)));
        $existing['auto_tagging_stop_words'] = sanitize_text_field(wp_unslash($_POST['auto_tagging_stop_words'] ?? ''));

        update_option('king_addons_ai_options', $existing);

        wp_send_json_success(['message' => esc_html__('Settings saved.', 'king-addons')]);
    }

    public function registerAdminMenu(): void
    {
        add_submenu_page(
            'king-addons',
            esc_html__('AI SEO Tools', 'king-addons'),
            esc_html__('AI SEO Tools', 'king-addons'),
            'manage_options',
            'king-addons-ai-seo-tools',
            [$this, 'renderAdminPage']
        );
    }

    public function enqueueAdminAssets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-ai-seo-tools') {
            return;
        }

        wp_enqueue_style(
            'king-addons-admin-v3-shared',
            KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_style(
            'king-addons-ai-seo-tools',
            KING_ADDONS_URL . 'includes/extensions/AI_SEO_Tools/assets/ai-seo-tools.css',
            ['king-addons-admin-v3-shared'],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-ai-seo-tools',
            KING_ADDONS_URL . 'includes/extensions/AI_SEO_Tools/assets/ai-seo-tools.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );

        $ka_ai_options_for_js = get_option('king_addons_ai_options', []);
        wp_localize_script('king-addons-ai-seo-tools', 'kingAddonsAiSeoTools', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'settingsUrl' => admin_url('admin.php?page=king-addons-ai-settings'),
            'hasApiKey'   => !empty($ka_ai_options_for_js['openai_api_key']) ? '1' : '',
            'nonces' => [
                'altStart' => wp_create_nonce('king_addons_ai_seo_bulk_alt_start_nonce'),
                'altStatus' => wp_create_nonce('king_addons_ai_seo_bulk_alt_status_nonce'),
                'altStop' => wp_create_nonce('king_addons_ai_seo_bulk_alt_stop_nonce'),
                'altStats' => wp_create_nonce('king_addons_ai_seo_get_alt_stats_nonce'),

                'tagsStart' => wp_create_nonce('king_addons_ai_seo_bulk_tags_start_nonce'),
                'tagsStatus' => wp_create_nonce('king_addons_ai_seo_bulk_tags_status_nonce'),
                'tagsStop' => wp_create_nonce('king_addons_ai_seo_bulk_tags_stop_nonce'),

                'appendStart' => wp_create_nonce('king_addons_ai_seo_bulk_append_tags_start_nonce'),
                'appendStatus' => wp_create_nonce('king_addons_ai_seo_bulk_append_tags_status_nonce'),
                'appendStop' => wp_create_nonce('king_addons_ai_seo_bulk_append_tags_stop_nonce'),

                'regenStart' => wp_create_nonce('king_addons_ai_seo_bulk_regenerate_tags_start_nonce'),
                'regenStatus' => wp_create_nonce('king_addons_ai_seo_bulk_regenerate_tags_status_nonce'),
                'regenStop' => wp_create_nonce('king_addons_ai_seo_bulk_regenerate_tags_stop_nonce'),

                'postgenStart' => wp_create_nonce('king_addons_ai_seo_post_gen_start_nonce'),
                'postgenStatus' => wp_create_nonce('king_addons_ai_seo_post_gen_status_nonce'),
                'postgenStop' => wp_create_nonce('king_addons_ai_seo_post_gen_stop_nonce'),

                'settingsSave' => wp_create_nonce('king_addons_ai_seo_save_settings_nonce'),
            ],
            'i18n' => [
                'processed' => esc_html__('Processed %1$d of %2$d', 'king-addons'),
                'completed' => esc_html__('Completed', 'king-addons'),
                'stopped' => esc_html__('Stopped', 'king-addons'),
                'error' => esc_html__('Error', 'king-addons'),
                'backgroundNotice' => esc_html__('AI SEO Tools is processing in the background. You can close this tab and continue working.', 'king-addons'),
            ],
        ]);
    }

    public function renderAdminPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
        if (!in_array($theme_mode, ['dark', 'light', 'auto'], true)) {
            $theme_mode = 'dark';
        }

        $alt_stats = Alt_Text_Generator::get_alt_text_stats();
        $tag_stats = Auto_Tagging_Module::get_tagging_stats();
        $ka_ai_options = get_option('king_addons_ai_options', []);
        ?>
        <script>
            (function() {
                document.body.classList.add('ka-admin-v3');
                const mode = '<?php echo esc_js($theme_mode); ?>';
                const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
                const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
                document.documentElement.classList.toggle('ka-v3-dark', isDark);
                document.body.classList.toggle('ka-v3-dark', isDark);
            })();
        </script>

        <div class="ka-admin-wrap ka-ai-seo-tools-wrap">
            <div class="ka-admin-header">
                <div class="ka-admin-header-left">
                    <div class="ka-admin-header-icon purple"><span class="dashicons dashicons-chart-area"></span></div>
                    <div>
                        <h1 class="ka-admin-title"><?php esc_html_e('AI SEO Tools', 'king-addons'); ?></h1>
                        <p class="ka-admin-subtitle"><?php esc_html_e('Alt Text Generator and Auto Tagging for SEO workflows.', 'king-addons'); ?></p>
                    </div>
                </div>
                <?php if (!king_addons_freemius()->can_use_premium_code()): ?>
                <div class="ka-admin-header-right">
                    <a href="https://kingaddons.com/pricing/?utm_source=kng-ai-seo-tools&utm_medium=wp-admin&utm_campaign=kng" target="_blank" rel="noopener" class="ka-btn ka-btn-primary"><?php esc_html_e('Upgrade to PRO', 'king-addons'); ?></a>
                </div>
                <?php endif; ?>
            </div>

            <div id="ka-ai-seo-background-notice" class="notice notice-info ka-ai-seo-notice" hidden>
                <p>
                    <strong><?php esc_html_e('Background process is running.', 'king-addons'); ?></strong>
                    <?php esc_html_e('AI SEO Tools is processing in the background. You can close this tab and continue working.', 'king-addons'); ?>
                </p>
            </div>

            <?php if (empty($ka_ai_options['openai_api_key'])) : ?>
            <div class="ka-ai-seo-nokey-banner">
                ⚠️ <strong><?php esc_html_e('OpenAI API key is missing.', 'king-addons'); ?></strong>
                <?php esc_html_e('AI features are disabled until you', 'king-addons'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-ai-settings')); ?>"><?php esc_html_e('add your API key in AI Settings', 'king-addons'); ?></a>.
            </div>
            <?php endif; ?>

            <div class="ka-tabs">
                <button type="button" class="ka-tab active" data-tab="alt"><?php esc_html_e('Alt Text Generator', 'king-addons'); ?> <span class="ka-tab-badge ka-tab-badge--blue"><?php esc_html_e('Media', 'king-addons'); ?></span></button>
                <button type="button" class="ka-tab" data-tab="tagging"><?php esc_html_e('Auto Tagging', 'king-addons'); ?> <span class="ka-tab-badge ka-tab-badge--green"><?php esc_html_e('Blog Post', 'king-addons'); ?></span></button>
                <button type="button" class="ka-tab" data-tab="postgen"><?php esc_html_e('Post Generator', 'king-addons'); ?> <span class="ka-tab-badge ka-tab-badge--purple"><?php esc_html_e('Blog Post', 'king-addons'); ?></span></button>
                <button type="button" class="ka-tab" data-tab="settings"><?php esc_html_e('Settings', 'king-addons'); ?></button>
            </div>

            <div class="ka-tab-content active" data-tab="alt">
                <div class="ka-seo-intro-callout">
                    <div class="ka-seo-intro-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2a7 7 0 0 1 4.5 12.37V16a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1v-1.63A7 7 0 0 1 12 2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9.5 21h5M10.5 17v4M13.5 17v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <div class="ka-seo-intro-label"><?php esc_html_e('What SEO problem does this solve?', 'king-addons'); ?></div>
                        <p class="ka-seo-intro-text"><?php esc_html_e('Many site owners upload images without providing descriptive alt text or optimizing filenames, which hurts both accessibility and SEO. Over half of website homepages have images with missing alternative text, leaving visually impaired users in the dark and missing an SEO opportunity (search engines use alt text to understand images). Writing alt text for every image is tedious, and many people simply forget or don\'t know how to write a good description. This is a pain point for bloggers, e-commerce (lots of product images), and anyone mindful of SEO and accessibility compliance.', 'king-addons'); ?></p>
                    </div>
                </div>

                <div class="ka-card"><div class="ka-card-body">
                    <h3><?php esc_html_e('Post Alt Text Statistics & Generation', 'king-addons'); ?></h3>
                    <div id="ka-ai-seo-alt-stats"
                        data-total="<?php echo esc_attr((string) $alt_stats['total']); ?>"
                        data-with-alt="<?php echo esc_attr((string) $alt_stats['with_alt']); ?>"
                        data-without-alt="<?php echo esc_attr((string) $alt_stats['without_alt']); ?>"></div>
                </div></div>

                <div class="ka-card"><div class="ka-card-body">
                    <h3><?php esc_html_e('Bulk Generation', 'king-addons'); ?></h3>
                    <label for="ka-ai-seo-alt-limit"><?php esc_html_e('Process maximum:', 'king-addons'); ?></label>
                    <input id="ka-ai-seo-alt-limit" type="number" min="1" max="<?php echo esc_attr((string) max(1, (int) $alt_stats['without_alt'])); ?>" value="<?php echo esc_attr((string) max(1, (int) $alt_stats['without_alt'])); ?>">
                    <button type="button" id="ka-ai-seo-alt-start" class="ka-btn ka-btn-primary"><?php esc_html_e('Start Bulk Generation', 'king-addons'); ?></button>
                    <button type="button" id="ka-ai-seo-alt-stop" class="ka-btn ka-btn-secondary" hidden><?php esc_html_e('Stop Generation', 'king-addons'); ?></button>
                    <span id="ka-ai-seo-alt-spinner" class="spinner ka-bulk-spinner" hidden></span>
                    <div id="ka-ai-seo-alt-progress" class="ka-progress" hidden><div id="ka-ai-seo-alt-progress-bar" class="ka-progress-bar">0%</div></div>
                    <p id="ka-ai-seo-alt-progress-text"></p>
                    <div id="ka-ai-seo-alt-details" class="ka-bulk-details" hidden></div>
                </div></div>
            </div>

            <div class="ka-tab-content" data-tab="tagging">
                <div class="ka-seo-intro-callout ka-seo-intro-callout--tags">
                    <div class="ka-seo-intro-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.5 3H4a1 1 0 0 0-1 1v5.5a1 1 0 0 0 .29.71l9.5 9.5a1 1 0 0 0 1.42 0l5.5-5.5a1 1 0 0 0 0-1.42l-9.5-9.5A1 1 0 0 0 9.5 3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="6.5" cy="6.5" r="1" fill="currentColor"/></svg>
                    </div>
                    <div>
                        <div class="ka-seo-intro-label"><?php esc_html_e('What SEO problem does this solve?', 'king-addons'); ?></div>
                        <p class="ka-seo-intro-text"><?php esc_html_e('Automated tagging enriches metadata, improves internal linking, and reduces manual effort for editors. Generate semantically relevant tags based on post content.', 'king-addons'); ?></p>
                    </div>
                </div>

                <div class="ka-card"><div class="ka-card-body">
                    <h3><?php esc_html_e('Post Tagging Statistics & Generation', 'king-addons'); ?></h3>
                    <div id="ka-ai-seo-tags-stats"
                        data-total="<?php echo esc_attr((string) $tag_stats['total']); ?>"
                        data-with-tags="<?php echo esc_attr((string) $tag_stats['with_tags']); ?>"
                        data-without-tags="<?php echo esc_attr((string) $tag_stats['without_tags']); ?>"></div>
                </div></div>

                <div class="ka-card"><div class="ka-card-body">
                    <h3><?php esc_html_e('Bulk Tagging', 'king-addons'); ?></h3>
                    <input id="ka-ai-seo-tags-limit" type="number" min="1" max="<?php echo esc_attr((string) max(1, (int) $tag_stats['without_tags'])); ?>" value="<?php echo esc_attr((string) max(1, (int) $tag_stats['without_tags'])); ?>">
                    <button type="button" id="ka-ai-seo-tags-start" class="ka-btn ka-btn-primary"><?php esc_html_e('Start Bulk Tagging', 'king-addons'); ?></button>
                    <button type="button" id="ka-ai-seo-tags-stop" class="ka-btn ka-btn-secondary" hidden><?php esc_html_e('Stop Tagging', 'king-addons'); ?></button>
                    <span id="ka-ai-seo-tags-spinner" class="spinner ka-bulk-spinner" hidden></span>
                    <div id="ka-ai-seo-tags-progress" class="ka-progress" hidden><div id="ka-ai-seo-tags-progress-bar" class="ka-progress-bar">0%</div></div>
                    <p id="ka-ai-seo-tags-progress-text"></p>
                    <div id="ka-ai-seo-tags-details" class="ka-bulk-details" hidden></div>
                </div></div>

                <div class="ka-card"><div class="ka-card-body">
                    <h3><?php esc_html_e('Bulk Append Tags', 'king-addons'); ?></h3>
                    <input id="ka-ai-seo-append-limit" type="number" min="1" max="<?php echo esc_attr((string) max(1, (int) $tag_stats['with_tags'])); ?>" value="<?php echo esc_attr((string) max(1, (int) $tag_stats['with_tags'])); ?>">
                    <button type="button" id="ka-ai-seo-append-start" class="ka-btn ka-btn-primary"><?php esc_html_e('Start Bulk Append', 'king-addons'); ?></button>
                    <button type="button" id="ka-ai-seo-append-stop" class="ka-btn ka-btn-secondary" hidden><?php esc_html_e('Stop Append', 'king-addons'); ?></button>
                    <span id="ka-ai-seo-append-spinner" class="spinner ka-bulk-spinner" hidden></span>
                    <div id="ka-ai-seo-append-progress" class="ka-progress" hidden><div id="ka-ai-seo-append-progress-bar" class="ka-progress-bar">0%</div></div>
                    <p id="ka-ai-seo-append-progress-text"></p>
                    <div id="ka-ai-seo-append-details" class="ka-bulk-details" hidden></div>
                </div></div>

                <div class="ka-card"><div class="ka-card-body">
                    <h3><?php esc_html_e('Bulk Regenerate Tags', 'king-addons'); ?></h3>
                    <input id="ka-ai-seo-regen-limit" type="number" min="1" max="<?php echo esc_attr((string) max(1, (int) $tag_stats['with_tags'])); ?>" value="<?php echo esc_attr((string) max(1, (int) $tag_stats['with_tags'])); ?>">
                    <button type="button" id="ka-ai-seo-regen-start" class="ka-btn ka-btn-primary"><?php esc_html_e('Start Bulk Regenerate', 'king-addons'); ?></button>
                    <button type="button" id="ka-ai-seo-regen-stop" class="ka-btn ka-btn-secondary" hidden><?php esc_html_e('Stop Regenerate', 'king-addons'); ?></button>
                    <span id="ka-ai-seo-regen-spinner" class="spinner ka-bulk-spinner" hidden></span>
                    <div id="ka-ai-seo-regen-progress" class="ka-progress" hidden><div id="ka-ai-seo-regen-progress-bar" class="ka-progress-bar">0%</div></div>
                    <p id="ka-ai-seo-regen-progress-text"></p>
                    <div id="ka-ai-seo-regen-details" class="ka-bulk-details" hidden></div>
                </div></div>
            </div>

            <div class="ka-tab-content" data-tab="postgen">
                <div class="ka-seo-intro-callout ka-seo-intro-callout--postgen">
                    <div class="ka-seo-intro-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="ka-seo-intro-label"><?php esc_html_e('AI Blog Post Generator', 'king-addons'); ?></div>
                        <p class="ka-seo-intro-text"><?php esc_html_e('Automatically generate complete, unique blog posts using AI — title, full HTML content, excerpt, and tags. Optionally generate a matching featured image for each post using DALL·E 3 or GPT Image 1. Posts are saved as drafts by default so you can review them before publishing.', 'king-addons'); ?></p>
                    </div>
                </div>

                <div class="ka-card"><div class="ka-card-body">
                    <h3><?php esc_html_e('Generation Settings', 'king-addons'); ?></h3>
                    <div class="ka-postgen-form">
                        <div>
                            <label for="ka-postgen-description"><?php esc_html_e('Describe what the posts should be about:', 'king-addons'); ?></label>
                            <textarea id="ka-postgen-description" rows="3" placeholder="<?php esc_attr_e('e.g. healthy breakfast recipes for busy professionals', 'king-addons'); ?>"></textarea>
                        </div>
                        <div class="ka-postgen-row">
                            <label for="ka-postgen-count"><?php esc_html_e('Number of posts to generate:', 'king-addons'); ?></label>
                            <input id="ka-postgen-count" type="number" min="1" max="50" value="3">
                        </div>
                        <div class="ka-postgen-row">
                            <label for="ka-postgen-length"><?php esc_html_e('Post length:', 'king-addons'); ?></label>
                            <div class="ka-postgen-length-toggle">
                                <button type="button" class="ka-postgen-length-btn" data-value="short"><?php esc_html_e('Short', 'king-addons'); ?><span class="ka-postgen-length-hint">~300 <?php esc_html_e('words', 'king-addons'); ?></span></button>
                                <button type="button" class="ka-postgen-length-btn active" data-value="medium"><?php esc_html_e('Medium', 'king-addons'); ?><span class="ka-postgen-length-hint">~600 <?php esc_html_e('words', 'king-addons'); ?></span></button>
                                <button type="button" class="ka-postgen-length-btn" data-value="long"><?php esc_html_e('Long', 'king-addons'); ?><span class="ka-postgen-length-hint">~1200 <?php esc_html_e('words', 'king-addons'); ?></span></button>
                            </div>
                            <input type="hidden" id="ka-postgen-length" value="medium">
                        </div>
                        <div class="ka-postgen-row">
                            <label for="ka-postgen-category"><?php esc_html_e('Category:', 'king-addons'); ?></label>
                            <select id="ka-postgen-category">
                                <option value="auto"><?php esc_html_e('✨ Auto-generate category', 'king-addons'); ?></option>
                                <option value="0"><?php esc_html_e('— None / Uncategorized —', 'king-addons'); ?></option>
                                <?php
                                $cats = get_categories(['hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
                                foreach ($cats as $cat) {
                                    echo '<option value="' . esc_attr((string) $cat->term_id) . '">' . esc_html($cat->name) . ' (' . esc_html((string) $cat->count) . ')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="ka-postgen-row">
                            <label for="ka-postgen-status"><?php esc_html_e('Save posts as:', 'king-addons'); ?></label>
                            <select id="ka-postgen-status">
                                <option value="draft" selected><?php esc_html_e('Draft (recommended)', 'king-addons'); ?></option>
                                <option value="publish"><?php esc_html_e('Published', 'king-addons'); ?></option>
                            </select>
                        </div>
                        <?php $is_image_pro = !king_addons_freemius()->can_use_premium_code(); ?>
                        <div class="ka-postgen-row">
                            <label class="ka-postgen-checkbox-label<?php echo $is_image_pro ? ' ka-postgen-checkbox-label--disabled ka-postgen-gen-label-pro' : ''; ?>">
                                <input type="checkbox" id="ka-postgen-gen-image"<?php echo $is_image_pro ? ' disabled' : ''; ?>>
                                <?php esc_html_e('Generate featured image for each post', 'king-addons'); ?>
                                <?php if ($is_image_pro): ?>
                                    <span class="ka-tab-badge ka-tab-badge--purple" style="margin-left:6px;"><?php esc_html_e('PRO', 'king-addons'); ?></span>
                                    <a href="https://kingaddons.com/pricing/?utm_source=kng-ai-seo-tools&utm_medium=wp-admin&utm_campaign=kng" target="_blank" rel="noopener" class="ka-postgen-pro-link"><?php esc_html_e('Upgrade →', 'king-addons'); ?></a>
                                <?php endif; ?>
                            </label>
                        </div>
                        <div id="ka-postgen-image-settings" class="ka-postgen-image-settings" hidden>
                            <div class="ka-postgen-row">
                                <label for="ka-postgen-image-model"><?php esc_html_e('Image model:', 'king-addons'); ?></label>
                                <select id="ka-postgen-image-model">
                                    <option value="dall-e-3" selected>DALL·E 3</option>
                                    <option value="gpt-image-1">GPT Image 1</option>
                                </select>
                            </div>
                            <div id="ka-postgen-dalle3-opts">
                                <div class="ka-postgen-row" style="margin-bottom: 8px;">
                                    <label for="ka-postgen-dalle3-quality"><?php esc_html_e('Quality:', 'king-addons'); ?></label>
                                    <select id="ka-postgen-dalle3-quality">
                                        <option value="standard" selected><?php esc_html_e('Standard', 'king-addons'); ?></option>
                                        <option value="hd">HD</option>
                                    </select>
                                </div>
                                <div class="ka-postgen-row">
                                    <label for="ka-postgen-dalle3-size"><?php esc_html_e('Size:', 'king-addons'); ?></label>
                                    <select id="ka-postgen-dalle3-size">
                                        <option value="1024x1024" selected>1024 × 1024</option>
                                        <option value="1792x1024">1792 × 1024 <?php esc_html_e('(landscape)', 'king-addons'); ?></option>
                                        <option value="1024x1792">1024 × 1792 <?php esc_html_e('(portrait)', 'king-addons'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div id="ka-postgen-gptimg-opts" hidden>
                                <div class="ka-postgen-row" style="margin-bottom: 8px;">
                                    <label for="ka-postgen-gptimg-quality"><?php esc_html_e('Quality:', 'king-addons'); ?></label>
                                    <select id="ka-postgen-gptimg-quality">
                                        <option value="auto" selected>Auto</option>
                                        <option value="low"><?php esc_html_e('Low', 'king-addons'); ?></option>
                                        <option value="medium"><?php esc_html_e('Medium', 'king-addons'); ?></option>
                                        <option value="high"><?php esc_html_e('High', 'king-addons'); ?></option>
                                    </select>
                                </div>
                                <div class="ka-postgen-row">
                                    <label for="ka-postgen-gptimg-size"><?php esc_html_e('Size:', 'king-addons'); ?></label>
                                    <select id="ka-postgen-gptimg-size">
                                        <option value="1024x1024" selected>1024 × 1024</option>
                                        <option value="1536x1024">1536 × 1024 <?php esc_html_e('(landscape)', 'king-addons'); ?></option>
                                        <option value="1024x1536">1024 × 1536 <?php esc_html_e('(portrait)', 'king-addons'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="ka-postgen-actions">
                            <button type="button" id="ka-postgen-start" class="ka-btn ka-btn-primary"><?php esc_html_e('Generate Posts', 'king-addons'); ?></button>
                            <button type="button" id="ka-postgen-stop" class="ka-btn ka-btn-secondary" hidden><?php esc_html_e('Stop Generation', 'king-addons'); ?></button>
                            <span id="ka-postgen-spinner" class="spinner ka-bulk-spinner" hidden></span>
                        </div>
                        <div id="ka-postgen-progress" class="ka-progress" hidden><div id="ka-postgen-progress-bar" class="ka-progress-bar">0%</div></div>
                        <p id="ka-postgen-progress-text"></p>
                        <div id="ka-postgen-details" class="ka-bulk-details" hidden></div>
                        <div id="ka-postgen-run-info" class="ka-postgen-run-info" hidden></div>
                    </div>
                </div></div>
            </div>

            <div class="ka-tab-content" data-tab="settings">
                <?php
                $ka_seo_opts = get_option('king_addons_ai_options', []);
                $ka_s_max_tags   = isset($ka_seo_opts['auto_tagging_max_tags']) ? (int) $ka_seo_opts['auto_tagging_max_tags'] : 5;
                $ka_s_confidence = isset($ka_seo_opts['auto_tagging_confidence_threshold']) ? (float) $ka_seo_opts['auto_tagging_confidence_threshold'] : 0.75;
                $ka_s_stop_words = $ka_seo_opts['auto_tagging_stop_words'] ?? '';
                ?>
                <div class="ka-card">
                    <div class="ka-card-body">
                        <h2 class="ka-seo-settings-section-title"><?php esc_html_e('Auto Tagging Settings', 'king-addons'); ?></h2>
                        <p class="ka-seo-settings-section-desc"><?php esc_html_e('Configure default settings for the Auto Tagging module.', 'king-addons'); ?></p>
                        <table class="ka-seo-settings-table form-table">
                            <tr>
                                <th scope="row"><label for="ka-seo-max-tags"><?php esc_html_e('Max Tags per Post', 'king-addons'); ?></label></th>
                                <td>
                                    <input type="number" id="ka-seo-max-tags" name="auto_tagging_max_tags" value="<?php echo esc_attr($ka_s_max_tags); ?>" min="1" max="20" class="ka-seo-settings-input-sm" />
                                    <p class="description"><?php esc_html_e('Maximum number of tags to generate per post (1-20).', 'king-addons'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ka-seo-confidence"><?php esc_html_e('Confidence Threshold', 'king-addons'); ?></label></th>
                                <td>
                                    <input type="number" id="ka-seo-confidence" name="auto_tagging_confidence_threshold" value="<?php echo esc_attr(number_format($ka_s_confidence, 2)); ?>" min="0.00" max="1.00" step="0.01" class="ka-seo-settings-input-sm" />
                                    <p class="description"><?php esc_html_e('Confidence threshold for tag relevance (0.00-1.00).', 'king-addons'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ka-seo-stop-words"><?php esc_html_e('Stop-Words List', 'king-addons'); ?></label></th>
                                <td>
                                    <input type="text" id="ka-seo-stop-words" name="auto_tagging_stop_words" value="<?php echo esc_attr($ka_s_stop_words); ?>" class="ka-seo-settings-input-lg" />
                                    <p class="description"><?php esc_html_e('Comma-separated list of words to exclude from generated tags.', 'king-addons'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <div class="ka-seo-settings-actions">
                            <button type="button" id="ka-seo-settings-save" class="ka-btn ka-btn-primary"><?php esc_html_e('Save Settings', 'king-addons'); ?></button>
                            <span id="ka-seo-settings-feedback" class="ka-seo-settings-feedback" hidden></span>
                        </div>
                    </div>
                </div>
                <div class="ka-card">
                    <div class="ka-card-body">
                        <h2 class="ka-seo-settings-section-title"><?php esc_html_e('AI Keys &amp; Models', 'king-addons'); ?></h2>
                        <p><?php esc_html_e('OpenAI API key, model selection and other general AI settings are managed in AI Settings.', 'king-addons'); ?></p>
                        <a class="ka-btn ka-btn-primary" href="<?php echo esc_url(admin_url('admin.php?page=king-addons-ai-settings')); ?>"><?php esc_html_e('Open AI Settings', 'king-addons'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
