<?php

/**
 * Admin class do all things for admin menu
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class Admin
{
    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addAdminMenu']);
            add_action('admin_init', [$this, 'createSettings']);
            add_action('admin_init', [$this, 'createAiSettings']);
        }
    }

    function addAdminMenu(): void
    {
        add_menu_page(
            'King Addons for Elementor',
            'King Addons',
            'manage_options',
            'king-addons',
            [$this, 'showAdminPage'],
            KING_ADDONS_URL . 'includes/admin/img/icon-for-admin.svg',
            58.7
        );

        add_submenu_page(
            'king-addons',
            'King Addons Settings',
            'Settings',
            'manage_options',
            'king-addons-settings',
            [$this, 'showSettingsPage']
        );

        if (KING_ADDONS_WGT_FORM_BUILDER) {
            add_submenu_page(
                'king-addons',
                esc_html__('Form Submissions', 'king-addons'),
                esc_html__('Form Submissions', 'king-addons'),
                'edit_posts',
                'edit.php?post_type=king-addons-fb-sub',
            );
        }

        if (KING_ADDONS_EXT_TEMPLATES_CATALOG) {
            add_menu_page(
                'King Addons for Elementor',
                (!king_addons_freemius()->can_use_premium_code() ? esc_html__('Free Templates', 'king-addons') : esc_html__('Templates Pro', 'king-addons')),
                'manage_options',
                'king-addons-templates',
                [Templates::instance(), 'render_template_catalog_page'],
                KING_ADDONS_URL . 'includes/admin/img/icon-for-menu-templates.svg',
                58.71
            );
        }

        if (KING_ADDONS_EXT_HEADER_FOOTER_BUILDER) {
            self::showHeaderFooterBuilder();
        }

        if (KING_ADDONS_EXT_POPUP_BUILDER) {
            self::showPopupBuilder();
        }

        // Add AI Settings submenu under King Addons
        add_submenu_page(
            'king-addons',
            esc_html__('AI Settings', 'king-addons'),
            esc_html__('AI Settings', 'king-addons'),
            'manage_options',
            'king-addons-ai-settings',
            [$this, 'showAiSettingsPage']
        );
    }

    function showPopupBuilder(): void
    {
        add_menu_page(
            'Popup Builder',
            'Popup Builder',
            'manage_options',
            'king-addons-popup-builder',
            [Popup_Builder::instance(), 'renderPopupBuilder'],
            KING_ADDONS_URL . 'includes/admin/img/icon-for-popup-builder.svg',
            58.73
        );
    }

    function showHeaderFooterBuilder(): void
    {
        $post_type = 'king-addons-el-hf';
        $menu_slug = 'edit.php?post_type=' . $post_type;

        // Add Main Menu
        add_menu_page(
            esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            esc_html__('Header & Footer', 'king-addons'),
            'manage_options',
            $menu_slug, // Menu slug points to the custom post type edit screen
            '', // No callback function needed
            KING_ADDONS_URL . 'includes/admin/img/icon-for-header-footer-builder.svg',
            58.72
        );

        // Add 'All Templates' Submenu - this will be the first submenu item
        add_submenu_page(
            $menu_slug, // Parent slug matches the main menu slug
            esc_html__('All Templates', 'king-addons'),
            esc_html__('All Templates', 'king-addons'),
            'edit_posts',
            $menu_slug
        );
    }

    function showAdminPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        self::enqueueAdminAssets();

        require_once(KING_ADDONS_PATH . 'includes/admin/layouts/admin-page.php');
    }

    function showSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        require_once(KING_ADDONS_PATH . 'includes/admin/layouts/settings-page.php');

        self::enqueueSettingsAssets();
    }

    function createSettings(): void
    {
        // Register a new setting for "king-addons" page.
        register_setting('king_addons', 'king_addons_options');

        // Register a new section in the "king-addons" page.
        add_settings_section(
            'king_addons_section_widgets',
            '',
            [$this, 'king_addons_section_widgets_callback'],
            'king-addons'
        );

        // Register a new section in the "king-addons" page.
        add_settings_section(
            'king_addons_section_features',
            '',
            [$this, 'king_addons_section_features_callback'],
            'king-addons'
        );

        foreach (ModulesMap::getModulesMapArray()['widgets'] as $widget_id => $widget_array) {
            add_settings_field(
                $widget_id,
                $widget_array['title'],
                '',
                'king-addons',
                'king_addons_section_widgets',
                array(
                    'label_for' => $widget_id,
                    'description' => $widget_array['description'],
                    'docs_link' => $widget_array['docs-link'],
                    'demo_link' => $widget_array['demo-link'],
                    'class' => 'kng-tr kng-tr-' . $widget_id . (!empty($widget_array['has-pro']) ? ' kng-tr-freemium' : '')
                )
            );
        }

        foreach (ModulesMap::getModulesMapArray()['features'] as $feature_id => $feature_array) {
            add_settings_field(
                $feature_id,
                $feature_array['title'],
                '',
                'king-addons',
                'king_addons_section_features',
                array(
                    'label_for' => $feature_id,
                    'description' => $feature_array['description'],
                    'docs_link' => $feature_array['docs-link'],
                    'demo_link' => $feature_array['demo-link'],
                    'class' => 'kng-tr kng-tr-' . $feature_id
                )
            );
        }
    }

    function king_addons_section_widgets_callback($args): void
    {
?>
        <h2 id="<?php echo esc_attr($args['id']); ?>"
            class="kng-section-title"><?php esc_html_e('Elements', 'king-addons'); ?></h2>
    <?php
    }

    function king_addons_section_features_callback($args): void
    {
    ?>
        <div class="kng-section-separator"></div>
        <h2 id="<?php echo esc_attr($args['id']); ?>"
            class="kng-section-title"><?php esc_html_e('Features', 'king-addons'); ?></h2>
<?php
    }

    function enqueueAdminAssets(): void
    {
        wp_enqueue_style('king-addons-admin', KING_ADDONS_URL . 'includes/admin/css/admin.css', '', KING_ADDONS_VERSION);
    }

    function enqueueSettingsAssets(): void
    {
        wp_enqueue_style('king-addons-settings', KING_ADDONS_URL . 'includes/admin/css/settings.css', '', KING_ADDONS_VERSION);
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-wpcolorpicker-wpcolorpicker');
        wp_enqueue_script('king-addons-settings', KING_ADDONS_URL . 'includes/admin/js/settings.js', '', KING_ADDONS_VERSION);
    }

    /**
     * Registers AI Settings using the WordPress Settings API.
     *
     * @return void
     */
    public function createAiSettings(): void
    {
        register_setting(
            'king_addons_ai',
            'king_addons_ai_options',
            [$this, 'sanitizeAiSettings']
        );

        add_settings_section(
            'king_addons_ai_openai_section',
            esc_html__('OpenAI API Settings', 'king-addons'),
            [$this, 'renderAiOpenaiSection'],
            'king-addons-ai-settings'
        );

        add_settings_field(
            'openai_api_key',
            esc_html__('OpenAI API Key', 'king-addons'),
            [$this, 'renderAiApiKeyField'],
            'king-addons-ai-settings',
            'king_addons_ai_openai_section'
        );

        add_settings_field(
            'openai_model',
            esc_html__('OpenAI Model', 'king-addons'),
            [$this, 'renderAiModelField'],
            'king-addons-ai-settings',
            'king_addons_ai_openai_section'
        );

        // Add Editor Integration section and field
        add_settings_section(
            'king_addons_ai_editor_section',
            esc_html__('Editor Integration', 'king-addons'),
            [$this, 'renderAiEditorSection'],
            'king-addons-ai-settings'
        );
        add_settings_field(
            'enable_ai_buttons',
            esc_html__('AI Text Editing Buttons', 'king-addons'),
            [$this, 'renderAiEnableButtonsField'],
            'king-addons-ai-settings',
            'king_addons_ai_editor_section'
        );

        // Add Usage Quota Settings section and field
        add_settings_section(
            'king_addons_ai_quota_section',
            esc_html__('Usage Quota Settings', 'king-addons'),
            [$this, 'renderAiQuotaSection'],
            'king-addons-ai-settings'
        );

        add_settings_field(
            'daily_token_limit',
            esc_html__('Daily Token Limit', 'king-addons'),
            [$this, 'renderAiDailyLimitField'],
            'king-addons-ai-settings',
            'king_addons_ai_quota_section'
        );

        // Add Usage Statistics section (read-only)
        add_settings_section(
            'king_addons_ai_stats_section',
            esc_html__('Usage Statistics', 'king-addons'),
            [$this, 'renderAiStatsSection'],
            'king-addons-ai-settings'
        );

        // Clear models cache when options updated.
        add_action('update_option_king_addons_ai_options', [$this, 'clearAiModelsCache']);

        // AJAX handler for refreshing models.
        add_action('wp_ajax_king_addons_ai_refresh_models', [$this, 'handleAiRefreshModels']);

        // AJAX handler for generating text via AI
        add_action('wp_ajax_king_addons_ai_generate_text', [$this, 'handleAiGenerateText']);

        // AJAX handler to change text using AI based on user prompt and original text.
        add_action('wp_ajax_king_addons_ai_change_text', [$this, 'handleAiChangeText']);

        // AJAX handler to check token usage limits
        add_action('wp_ajax_king_addons_ai_check_tokens', [$this, 'handleAiCheckTokens']);
    }

    /**
     * Sanitizes AI Settings options.
     *
     * @param array $input Raw input array.
     * @return array Sanitized input.
     */
    public function sanitizeAiSettings(array $input): array
    {
        $sanitized = [];
        $sanitized['openai_api_key'] = isset($input['openai_api_key'])
            ? sanitize_text_field($input['openai_api_key'])
            : '';
        $sanitized['openai_model'] = isset($input['openai_model'])
            ? sanitize_text_field($input['openai_model'])
            : '';

        // Sanitize Daily Token Limit.
        if (isset($input['daily_token_limit'])) {
            $daily_limit = absint($input['daily_token_limit']);
            $sanitized['daily_token_limit'] = max(0, $daily_limit); // Ensure non-negative
        } else {
            $sanitized['daily_token_limit'] = 1000000; // Default to 1 million tokens if not set
        }

        // Sanitize Enable AI Buttons option.
        $sanitized['enable_ai_buttons'] = ! empty($input['enable_ai_buttons']);

        return $sanitized;
    }

    /**
     * Clears cached AI models list.
     *
     * @return void
     */
    public function clearAiModelsCache(): void
    {
        delete_transient('king_addons_ai_models_cache');
    }

    /**
     * Renders the AI Settings page content.
     *
     * @return void
     */
    public function showAiSettingsPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        require_once KING_ADDONS_PATH . 'includes/admin/layouts/ai-settings-page.php';
        $this->enqueueAiSettingsAssets();
    }

    /**
     * Enqueues scripts and styles for the AI Settings page.
     *
     * @return void
     */
    public function enqueueAiSettingsAssets(): void
    {
        wp_enqueue_style(
            'king-addons-ai-settings',
            KING_ADDONS_URL . 'includes/admin/css/ai-settings.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-ai-settings',
            KING_ADDONS_URL . 'includes/admin/js/ai-settings.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script(
            'king-addons-ai-settings',
            'KingAddonsAiSettings',
            [
                'ajax_url'        => admin_url('admin-ajax.php'),
                'nonce'           => wp_create_nonce('king_addons_ai_refresh_models_nonce'),
                'refreshing_text' => esc_html__('Refreshing...', 'king-addons'),
                'refreshed_text'  => esc_html__('List updated.', 'king-addons'),
                'error_text'      => esc_html__('Error updating list.', 'king-addons'),
            ]
        );
    }

    /**
     * Renders description for OpenAI API Settings section.
     *
     * @return void
     */
    public function renderAiOpenaiSection(): void
    {
        echo '<p>' . esc_html__('Enter your OpenAI API key and select the model for AI features.', 'king-addons') . '</p>';
    }

    /**
     * Renders the OpenAI API Key input field.
     *
     * @return void
     */
    public function renderAiApiKeyField(): void
    {
        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? '';
        printf(
            '<input type="password" name="king_addons_ai_options[openai_api_key]" value="%s" class="regular-text" autocomplete="off" />',
            esc_attr($api_key)
        );
        echo '<p class="description">';
        printf(
            esc_html__('Get your API key from the %1$sOpenAI Platform%2$s. Saving the key will attempt to fetch the available models.', 'king-addons'),
            '<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">',
            '</a>'
        );
        echo '</p>';
        echo '<div style="background: #ffebe8; color: #a00; border: 1px solid #a00; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>' . esc_html__('Important:', 'king-addons') . '</strong> ';
        echo esc_html__('You must top up your OpenAI account balance by at least $5 for the API to work. Free accounts are not supported.', 'king-addons');
        echo '</div>';
        echo '<div style="background: #e7f3fe; color: #084d7a; border: 1px solid #b6e0fe; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '<span style="font-weight: bold; color: #084d7a;">' . esc_html__('Info:', 'king-addons') . '</span> ';
        echo esc_html__('With GPT-4o-mini, a $5 balance is enough for roughly 130,000–150,000 text generations.', 'king-addons');
        echo '</div>';
        echo '<div style="background: #e7f3fe; color: #084d7a; border: 1px solid #b6e0fe; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '<span style="font-weight: bold; color: #084d7a; display: block; margin-bottom: 4px;">' . esc_html__('Useful OpenAI Links:', 'king-addons') . '</span>';
        echo '<ul style="margin: 0 0 0 18px; padding: 0; list-style: disc;">';
        $links = [
            'API Pricing'         => 'https://openai.com/api/pricing/',
            // 'API Documentation'        => 'https://platform.openai.com/docs',
            'API Keys'                => 'https://platform.openai.com/api-keys',
            'Usage Dashboard'         => 'https://platform.openai.com/account/usage',
            'Billing Overview'             => 'https://platform.openai.com/account/billing/overview',
            'Rate Limits'         => 'https://platform.openai.com/account/billing/limits',
        ];
        foreach ($links as $label => $url) {
            printf(
                '<li><a href="%s" target="_blank" rel="noopener noreferrer" style="color: #084d7a; text-decoration: underline;">%s</a></li>',
                esc_url($url),
                esc_html($label)
            );
        }
        echo '</ul></div>';
    }

    /**
     * Renders the model selection dropdown field with refresh button.
     *
     * @return void
     */
    public function renderAiModelField(): void
    {
        $options = get_option('king_addons_ai_options', []);
        $selected = $options['openai_model'] ?? '';
        $models = $this->getAiAvailableModels();
        printf(
            '<select name="king_addons_ai_options[openai_model]" %s>',
            empty($models) ? 'disabled' : ''
        );
        if (!empty($models)) {
            foreach ($models as $id => $label) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($id),
                    selected($selected, $id, false),
                    esc_html($label)
                );
            }
        } else {
            echo '<option value="">' . esc_html__('Could not fetch models. Check API key?', 'king-addons') . '</option>';
        }
        echo '</select>';
        echo '<button type="button" id="king-addons-ai-refresh-models-button" class="button button-secondary" style="margin-left:10px; vertical-align:middle;">' . esc_html__('Refresh List', 'king-addons') . '</button>';
        echo '<span class="spinner" id="king-addons-ai-refresh-models-spinner" style="float:none; vertical-align:middle;"></span>';
        echo '<span id="king-addons-ai-refresh-models-status" style="margin-left:5px; vertical-align:middle;"></span>';
        echo '<p class="description">' . esc_html__('Select an available OpenAI model capable of processing text. We recommend GPT-4o-mini or GPT-4.1-nano for best results. The list of models is cached indefinitely until manually refreshed.', 'king-addons') . '</p>';
    }

    /**
     * Fetches the list of OpenAI models via API.
     *
     * @param string|null $api_key API key to use.
     * @return array|\WP_Error Model list or error.
     */
    private function fetchAiOpenaiModels(?string $api_key)
    {
        if (empty($api_key)) {
            return new \WP_Error('missing_key', esc_html__('API key is required to fetch models.', 'king-addons'));
        }
        $endpoint = 'https://api.openai.com/v1/models';
        $response = wp_remote_get($endpoint, [
            'headers' => ['Authorization' => 'Bearer ' . $api_key],
            'timeout' => 20,
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($code !== 200 || empty($data['data']) || !is_array($data['data'])) {
            $message = $data['error']['message'] ?? esc_html__('Invalid response from API.', 'king-addons');
            return new \WP_Error('api_error', $message, ['status' => $code]);
        }
        $list = [];
        foreach ($data['data'] as $model) {
            if (isset($model['id'])) {
                $list[$model['id']] = $model['id'];
            }
        }
        ksort($list);
        if (empty($list)) {
            return new \WP_Error('no_models', esc_html__('No models found via API.', 'king-addons'));
        }
        return $list;
    }

    /**
     * Retrieves available models, using cache if possible.
     *
     * @return array Model list.
     */
    private function getAiAvailableModels(): array
    {
        $cached = get_transient('king_addons_ai_models_cache');
        if (false !== $cached && is_array($cached)) {
            return $cached;
        }
        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? null;
        $fetched = $this->fetchAiOpenaiModels($api_key);
        if (!is_wp_error($fetched)) {
            set_transient('king_addons_ai_models_cache', $fetched, 0);
            return $fetched;
        }
        return ['gpt-4o-mini' => 'GPT-4o-mini', 'gpt-4.1-nano' => 'GPT-4.1-nano'];
    }

    /**
     * Handles AJAX request to refresh model list.
     *
     * @return void
     */
    public function handleAiRefreshModels(): void
    {
        check_ajax_referer('king_addons_ai_refresh_models_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }
        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? null;
        if (empty($api_key)) {
            wp_send_json_error(['message' => esc_html__('API key is not set.', 'king-addons')], 400);
        }
        $this->clearAiModelsCache();
        $models = $this->fetchAiOpenaiModels($api_key);
        if (is_wp_error($models)) {
            wp_send_json_error(['message' => $models->get_error_message()], 500);
        }
        if (empty($models)) {
            wp_send_json_error(['message' => esc_html__('No models returned by API.', 'king-addons')], 500);
        }
        set_transient('king_addons_ai_models_cache', $models, 0);
        wp_send_json_success(['models' => $models]);
    }

    /**
     * AJAX handler to generate text using OpenAI.
     *
     * @return void
     */
    public function handleAiGenerateText(): void
    {
        check_ajax_referer('king_addons_ai_generate_nonce', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        // Accept 'prompt' parameter (new) but fall back to 'value' parameter (old) for backwards compatibility
        $prompt = isset($_POST['prompt'])
            ? sanitize_textarea_field($_POST['prompt'])
            : sanitize_textarea_field($_POST['value'] ?? '');

        // Get editor type if provided
        $editor_type = sanitize_text_field($_POST['editor_type'] ?? 'text');

        $options    = get_option('king_addons_ai_options', []);
        $api_key    = $options['openai_api_key'] ?? '';
        $model      = $options['openai_model']      ?? '';

        if (empty($api_key) || empty($model)) {
            wp_send_json_error(['message' => esc_html__('API key or model not set.', 'king-addons')], 400);
        }

        if (empty($prompt)) {
            wp_send_json_error(['message' => esc_html__('Please provide a prompt.', 'king-addons')], 400);
        }

        // Check daily token limit
        $daily_limit = isset($options['daily_token_limit']) ? intval($options['daily_token_limit']) : self::DEFAULT_DAILY_TOKEN_LIMIT;
        $current_usage = $this->getAiDailyUsage();

        if ($daily_limit > 0 && $current_usage >= $daily_limit) {
            wp_send_json_error([
                'message' => esc_html__('Daily token limit reached. Please try again tomorrow or increase the limit in AI Settings.', 'king-addons')
            ], 429);
        }

        // System instruction based on editor type
        $system_instruction = 'You are a helpful content assistant. Provide concise, well-written content based on the user\'s request.';

        // Enhanced instruction for WYSIWYG editor
        if ($editor_type === 'wysiwyg') {
            $system_instruction = 'You are a helpful content assistant for a rich text editor. Provide content with proper HTML formatting. Use <p> tags for paragraphs with appropriate spacing between them. If relevant, use other HTML formatting like <strong>, <em>, <ul>, <ol>, etc. for better readability and structure. IMPORTANT: Do NOT wrap your HTML in code fences (``` or ```html). Respond ONLY with the actual HTML content.';
        }

        // Prepare request to OpenAI Chat Completions
        $messages = [
            ['role' => 'system', 'content' => $system_instruction],
            ['role' => 'user', 'content' => $prompt]
        ];

        // Add format instruction for WYSIWYG
        if ($editor_type === 'wysiwyg') {
            $messages[1]['content'] .= "\n\nOutput should be properly formatted HTML with <p> tags for paragraphs, maintaining good spacing and readability. Do NOT use code fences (``` or ```html) in your response - provide just the clean HTML.";
        }

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode([
                    'model'    => $model,
                    'messages' => $messages,
                    'max_tokens' => 500,
                    'temperature' => 0.7, // Slight creativity for better content
                ]),
                'timeout' => 30,
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()], 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($data['choices'][0]['message']['content'])) {
            $error_msg = $data['error']['message'] ?? esc_html__('AI API error.', 'king-addons');
            wp_send_json_error(['message' => $error_msg], 500);
        }

        $generated = trim($data['choices'][0]['message']['content']);

        // Clean up any code fence markers for WYSIWYG editor
        if ($editor_type === 'wysiwyg') {
            // Remove code fence markers (```html and ```) that might be returned by AI
            $generated = preg_replace('/^```(?:html|HTML)?\s*/', '', $generated);
            $generated = preg_replace('/```\s*$/', '', $generated);
        }

        // Update token usage statistics if present in the response
        if (isset($data['usage']['total_tokens'])) {
            $this->incrementAiDailyUsage(intval($data['usage']['total_tokens']));
        }

        wp_send_json_success([
            'text' => $generated,
            'usage' => [
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'daily_used' => $this->getAiDailyUsage(),
                'daily_limit' => $daily_limit,
            ]
        ]);
    }

    /**
     * AJAX handler to change text using AI based on user prompt and original text.
     *
     * @return void
     */
    public function handleAiChangeText(): void
    {
        check_ajax_referer('king_addons_ai_change_nonce', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }
        $field_name = isset($_POST['field_name']) ? sanitize_text_field(wp_unslash($_POST['field_name'])) : '';
        $prompt     = isset($_POST['prompt'])     ? sanitize_text_field(wp_unslash($_POST['prompt']))     : '';
        $original   = isset($_POST['original'])   ? wp_kses_post(wp_unslash($_POST['original'])) : '';
        $instruction_context = isset($_POST['instruction_context']) ? sanitize_textarea_field(wp_unslash($_POST['instruction_context'])) : '';

        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? '';
        $model   = $options['openai_model']     ?? '';

        if (empty($api_key) || empty($model) || empty($prompt) || empty($original)) {
            wp_send_json_error(['message' => esc_html__('Missing data for AI change.', 'king-addons')], 400);
        }

        // Check daily token limit
        $daily_limit = isset($options['daily_token_limit']) ? intval($options['daily_token_limit']) : self::DEFAULT_DAILY_TOKEN_LIMIT;
        $current_usage = $this->getAiDailyUsage();

        if ($daily_limit > 0 && $current_usage >= $daily_limit) {
            wp_send_json_error([
                'message' => esc_html__('Daily token limit reached. Please try again tomorrow or increase the limit in AI Settings.', 'king-addons')
            ], 429);
        }

        // Default instruction if none provided
        if (empty($instruction_context)) {
            $instruction_context = 'You are an assistant that modifies text per user instruction. If the instruction mentions adding paragraphs or texts, make sure to KEEP the original text and ADD to it. If the instruction is about changing style, maintain the same information but change the tone. Return the complete modified text.';

            // Add specific formatting instructions for WYSIWYG editor
            $editor_type = isset($_POST['editor_type']) ? sanitize_text_field(wp_unslash($_POST['editor_type'])) : 'text';
            if ($editor_type === 'wysiwyg') {
                $instruction_context = 'You are an assistant that modifies HTML content for a rich text editor. IMPORTANT: If asked to add a specific number of paragraphs (like "add 2 paragraphs" or "add 3 sections"), you MUST add EXACTLY that number of distinct paragraphs or sections - no more, no less. If asked to add "some" or "several" paragraphs, add at minimum 2-3 paragraphs. Always keep the original content intact and add the new paragraphs after the original content. Use proper HTML formatting with <p> tags for each paragraph. Ensure there is appropriate spacing between paragraphs. Preserve any existing HTML formatting (like <strong>, <em>, <a>, etc). DO NOT wrap your output in code fences (``` or ```html) - respond only with the actual HTML. Return the complete modified content with proper HTML structure.';
            }
        }

        // Analyze if the prompt is likely requesting to add content rather than replace
        $add_content_keywords = [
            // English
            'add',
            'insert',
            'extend',
            'append',
            'more',
            'additional',
            'expand',
            // Russian
            'добавь',
            'вставь',
            'расширь',
            // Spanish
            'añadir',
            'agregar',
            'insertar',
            'adjuntar',
            'extender',
            // French
            'ajouter',
            'insérer',
            'étendre',
            'annexer',
            'joindre',
            // German
            'hinzufügen',
            'einfügen',
            'erweitern',
            'anhängen',
            'ergänzen',
            // Italian
            'aggiungere',
            'inserire',
            'allegare',
            'estendere',
            'appendere',
            // Portuguese
            'adicionar',
            'inserir',
            'acrescentar',
            'anexar',
            'estender',
            // Polish
            'dodać',
            'wstawić',
            'dołączyć',
            'rozszerzyć',
            'załączyć'
        ];

        // Also look for numeric patterns like "add 2 paragraphs" or "добавь 3 абзаца"
        // Enhanced pattern to find numeric paragraph requests in different languages
        $numeric_pattern = '/(?:' .
            // English verbs
            'add|append|insert|create|write|' .
            // Russian verbs
            'добавь|вставь|создай|напиши|' .
            // Spanish verbs
            'añadir|agregar|insertar|crear|escribir|' .
            // French verbs
            'ajouter|insérer|créer|écrire|' .
            // German verbs
            'hinzufügen|einfügen|erstellen|schreiben|' .
            // Italian verbs
            'aggiungere|inserire|creare|scrivere|' .
            // Portuguese verbs
            'adicionar|inserir|criar|escrever|' .
            // Polish verbs
            'dodać|wstawić|utworzyć|napisać' .
            ')\s+(\d+|' .
            // English quantifiers
            'several|few|couple|some|' .
            // Russian quantifiers
            'несколько|пару|еще|ещё|' .
            // Spanish quantifiers
            'varios|algunos|un par|unos|' .
            // French quantifiers
            'plusieurs|quelques|une paire|certains|' .
            // German quantifiers
            'mehrere|einige|ein paar|manche|' .
            // Italian quantifiers
            'diversi|alcuni|un paio|qualche|' .
            // Portuguese quantifiers
            'vários|alguns|um par|' .
            // Polish quantifiers
            'kilka|parę|pare|niektóre' .
            ')\s+(?:' .
            // English nouns
            'paragraph|paragraphs|section|sections|content|text|' .
            // Russian nouns
            'абзац|абзаца|абзацев|раздел|разделы|текст|контент|параграф|параграфа|параграфов|' .
            // Spanish nouns
            'párrafo|párrafos|sección|secciones|contenido|texto|' .
            // French nouns
            'paragraphe|paragraphes|section|sections|contenu|texte|' .
            // German nouns
            'absatz|absätze|abschnitt|abschnitte|inhalt|text|' .
            // Italian nouns
            'paragrafo|paragrafi|sezione|sezioni|contenuto|testo|' .
            // Portuguese nouns
            'parágrafo|parágrafos|seção|seções|conteúdo|texto|' .
            // Polish nouns
            'akapit|akapity|sekcja|sekcje|treść|tekst' .
            ')/i';
        $contains_add_keyword = false;
        $numeric_match = [];
        $requested_paragraphs = 0;

        // First check for specific numeric requests
        if (preg_match($numeric_pattern, $prompt, $numeric_match)) {
            $contains_add_keyword = true;
            $number_text = $numeric_match[1] ?? '';

            // Convert text numbers to digits
            if (is_numeric($number_text)) {
                $requested_paragraphs = (int)$number_text;
            } else {
                // For words like "several", "few", "couple", etc.
                switch (strtolower($number_text)) {
                    // Words meaning approximately "2"
                    case 'couple':
                    case 'пару': // Russian
                    case 'пара': // Russian
                    case 'un par':  // Spanish
                    case 'une paire': // French
                    case 'ein paar': // German
                    case 'un paio': // Italian
                    case 'um par': // Portuguese
                    case 'parę': // Polish
                    case 'pare': // Polish
                        $requested_paragraphs = 2;
                        break;

                    // Words meaning approximately "3-4" (several/few)
                    case 'few':
                    case 'several':
                    case 'some':
                    case 'несколько': // Russian
                    case 'еще': // Russian
                    case 'ещё': // Russian
                    case 'varios': // Spanish
                    case 'algunos': // Spanish
                    case 'unos': // Spanish
                    case 'plusieurs': // French
                    case 'quelques': // French
                    case 'certains': // French
                    case 'mehrere': // German
                    case 'einige': // German
                    case 'manche': // German
                    case 'diversi': // Italian
                    case 'alcuni': // Italian
                    case 'qualche': // Italian
                    case 'vários': // Portuguese
                    case 'alguns': // Portuguese
                    case 'kilka': // Polish
                    case 'niektóre': // Polish
                    default:
                        $requested_paragraphs = 3; // Default "several" = 3
                        break;
                }
            }
        } else {
            // Then check for general add keywords
            foreach ($add_content_keywords as $keyword) {
                if (stripos($prompt, $keyword) !== false) {
                    $contains_add_keyword = true;
                    $requested_paragraphs = 2; // Default to 2 paragraphs if just "add paragraphs"
                    break;
                }
            }
        }

        // Build the system message dynamically based on the prompt analysis
        $system_message = $instruction_context;
        if ($contains_add_keyword) {
            if ($requested_paragraphs > 0) {
                // Request only new paragraphs, without modifying original content
                $system_message = 'You are an assistant that generates NEW content only, without modifying the original text. DO NOT repeat or return the original text in your response.';
                $system_message .= sprintf(
                    ' IMPORTANT: You must generate EXACTLY %d NEW distinct paragraphs. Return ONLY these new paragraphs, properly formatted with HTML <p> tags around each paragraph. The generated paragraphs should be a logical continuation or addition to the original content.',
                    $requested_paragraphs
                );
            } else {
                $system_message .= ' IMPORTANT: The user is asking you to ADD content, not replace it. Make sure to preserve all the original text and add to it with at least 2-3 new paragraphs or sections.';
            }
        }

        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => $system_message,
                ],
                [
                    'role'    => 'user',
                    'content' => ($contains_add_keyword && $requested_paragraphs > 0)
                        ? sprintf(
                            "Original Text for context: %s\n\nInstruction: Generate %d new paragraphs to add to this text, following the same style and continuing the topic. Return ONLY the new paragraphs.",
                            $original,
                            $requested_paragraphs
                        )
                        : sprintf(
                            /* translators: %1$s: User's instruction prompt, %2$s: Original text to modify. */
                            esc_html__("Instruction: %1\$s\nOriginal Text: %2\$s\n\nReturn the complete modified text that incorporates both the original content and your changes, unless explicitly asked to replace content.", 'king-addons'),
                            $prompt,
                            $original
                        ),
                ],
            ],
            'max_tokens' => 10000, // Increased to allow for more content
            'temperature' => 0.7, // Slightly more creative
        ];

        // Set append_mode flag for paragraph additions
        $append_mode = ($contains_add_keyword && $requested_paragraphs > 0);

        // Modify request for WYSIWYG editor
        $editor_type = isset($_POST['editor_type']) ? sanitize_text_field(wp_unslash($_POST['editor_type'])) : 'text';
        if ($editor_type === 'wysiwyg') {
            // Add a specific instruction for formatting
            $body['messages'][0]['content'] .= ' Format the response as proper HTML with <p> tags for paragraphs and appropriate spacing. IMPORTANT: Do NOT use code fences (``` or ```html) in your response.';

            if (!$append_mode) {
                // Only add this for non-append mode
                $body['messages'][1]['content'] .= "\n\nOutput should be properly formatted HTML with <p> tags for paragraphs, maintaining good spacing and readability. Do NOT use code fences (```html or ```) - provide just the clean HTML.";
            }

            // Increase temperature for WYSIWYG to be more creative when creating paragraphs
            $body['temperature'] = 0.8;

            // Increase max_tokens for longer responses with multiple paragraphs
            $body['max_tokens'] = 15000;
        }

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode($body),
                'timeout' => 30,
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()], 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($data['choices'][0]['message']['content'])) {
            $error_msg = $data['error']['message'] ?? esc_html__('AI change error.', 'king-addons');
            wp_send_json_error(['message' => $error_msg], 500);
        }

        $changed = trim($data['choices'][0]['message']['content']);

        // Clean up any code fence markers for WYSIWYG editor
        if ($editor_type === 'wysiwyg') {
            // Remove code fence markers (```html and ```) that might be returned by AI
            $changed = preg_replace('/^```(?:html|HTML)?\s*/', '', $changed);
            $changed = preg_replace('/```\s*$/', '', $changed);
        }

        // Update token usage statistics if present in the response
        if (isset($data['usage']['total_tokens'])) {
            $this->incrementAiDailyUsage(intval($data['usage']['total_tokens']));
        }

        // Send response with append mode flag
        wp_send_json_success([
            'text' => $changed,
            'append_mode' => $append_mode,
            'original' => $append_mode ? $original : '',
            'usage' => [
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'daily_used' => $this->getAiDailyUsage(),
                'daily_limit' => $daily_limit,
            ]
        ]);
    }

    /**
     * Renders Usage Quota Settings section.
     *
     * @return void
     */
    public function renderAiQuotaSection(): void
    {
        echo '<p>' . esc_html__('Set the daily token limit for AI features.', 'king-addons') . '</p>';
    }

    /**
     * Renders the Daily Token Limit input field.
     *
     * @return void
     */
    public function renderAiDailyLimitField(): void
    {
        $options = get_option('king_addons_ai_options', []);
        $daily_token_limit = $options['daily_token_limit'] ?? self::DEFAULT_DAILY_TOKEN_LIMIT;

        echo '<div class="daily-token-limit-wrap">';
        printf(
            '<input type="number" name="king_addons_ai_options[daily_token_limit]" value="%s" class="regular-text" min="0" step="1000" />',
            esc_attr($daily_token_limit)
        );
        echo '<span>' . esc_html__('tokens', 'king-addons') . '</span>';
        echo '</div>';

        echo '<p class="description">' . esc_html__('Set the maximum number of tokens allowed per day for AI features. Set to 0 for unlimited.', 'king-addons') . '</p>';

        echo '<div class="king-addons-info-box">';
        echo '<p><strong>' . esc_html__('About tokens:', 'king-addons') . '</strong> ' .
            esc_html__('Tokens are the basic unit of text that the AI processes. As a rough guide:', 'king-addons') . '</p>';
        echo '<p>• ' . esc_html__('1 token ≈ 4 characters or 0.75 words in English', 'king-addons') . '</p>';
        echo '<p>• ' . esc_html__('A typical paragraph might use around 50-100 tokens', 'king-addons') . '</p>';
        echo '<p>• ' . esc_html__('A full page of text (500 words) is approximately 750 tokens', 'king-addons') . '</p>';
        echo '<p>• ' . esc_html__('Recommended daily limit: 10,000 - 50,000 tokens for moderate use', 'king-addons') . '</p>';
        echo '</div>';
    }

    /**
     * Default daily token limit if not explicitly set.
     *
     * @var int
     */
    private const DEFAULT_DAILY_TOKEN_LIMIT = 1000000;

    /**
     * Gets the current daily token usage.
     *
     * @return int Number of tokens used today.
     */
    private function getAiDailyUsage(): int
    {
        $usage_data = get_option('king_addons_ai_daily_usage', ['date' => '', 'count' => 0]);
        $today = current_time('Y-m-d');
        if (!isset($usage_data['date']) || $usage_data['date'] !== $today) {
            return 0;
        }
        return intval($usage_data['count']);
    }

    /**
     * Increments the daily token usage count.
     *
     * @param int $tokens Number of tokens to add.
     * @return void
     */
    public function incrementAiDailyUsage(int $tokens): void
    {
        $today = current_time('Y-m-d');
        $usage_data = get_option('king_addons_ai_daily_usage', ['date' => '', 'count' => 0]);
        if (!isset($usage_data['date']) || $usage_data['date'] !== $today) {
            $usage_data = [
                'date' => $today,
                'count' => 0,
            ];
        }
        $usage_data['count'] = intval($usage_data['count']) + $tokens;
        update_option('king_addons_ai_daily_usage', $usage_data, false);
    }

    /**
     * Renders Usage Statistics section.
     *
     * @return void
     */
    public function renderAiStatsSection(): void
    {
        $usage_data = get_option('king_addons_ai_daily_usage', ['date' => '', 'count' => 0]);
        $today = current_time('Y-m-d');
        $used = (isset($usage_data['date']) && $usage_data['date'] === $today) ? intval($usage_data['count']) : 0;

        $options = get_option('king_addons_ai_options', []);
        $limit = isset($options['daily_token_limit']) ? intval($options['daily_token_limit']) : self::DEFAULT_DAILY_TOKEN_LIMIT;

        if ($limit > 0) {
            $limit_display = number_format_i18n($limit);
            $remaining = max(0, $limit - $used);
            $remaining_display = number_format_i18n($remaining);

            $usage_percentage = ($limit > 0) ? min(100, round(($used / $limit) * 100)) : 0;

            echo '<div class="king-addons-ai-usage-stats">';
            echo '<table class="form-table">';
            echo '<tr>';
            echo '<th>' . esc_html__('Tokens Used Today', 'king-addons') . '</th>';
            echo '<td><strong>' . esc_html(number_format_i18n($used)) . '</strong></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>' . esc_html__('Daily Limit', 'king-addons') . '</th>';
            echo '<td>' . esc_html($limit_display) . '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>' . esc_html__('Remaining', 'king-addons') . '</th>';
            echo '<td>' . esc_html($remaining_display) . '</td>';
            echo '</tr>';
            echo '</table>';

            // Add progress bar
            echo '<div class="king-addons-ai-usage-bar-container" style="background-color: #f0f0f0; height: 20px; border-radius: 10px; margin: 15px 0; overflow: hidden;">';
            echo '<div class="king-addons-ai-usage-bar" style="width: ' . esc_attr($usage_percentage) . '%; background-color: ' . esc_attr($usage_percentage > 80 ? '#ff5a5a' : ($usage_percentage > 60 ? '#ffa500' : '#4CAF50')) . '; height: 100%;"></div>';
            echo '</div>';
            echo '<p class="description">' . esc_html(sprintf(__('Usage: %d%%', 'king-addons'), $usage_percentage)) . '</p>';
            echo '</div>';
        } else {
            echo '<p>' . esc_html__('No daily token limit is set. All requests will be processed.', 'king-addons') . '</p>';
            echo '<p><strong>' . esc_html__('Tokens used today:', 'king-addons') . ' ' . esc_html(number_format_i18n($used)) . '</strong></p>';
        }
    }

    /**
     * AJAX handler to check token usage limits.
     * 
     * @return void
     */
    public function handleAiCheckTokens(): void
    {
        check_ajax_referer('king_addons_ai_generate_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $options = get_option('king_addons_ai_options', []);
        $daily_limit = isset($options['daily_token_limit']) ? intval($options['daily_token_limit']) : self::DEFAULT_DAILY_TOKEN_LIMIT;
        $daily_used = $this->getAiDailyUsage();
        // Check if API key and model are set
        $api_key = $options['openai_api_key'] ?? '';
        $model   = $options['openai_model']    ?? '';
        $api_key_valid = !empty($api_key) && !empty($model);

        wp_send_json_success([
            'daily_used'    => $daily_used,
            'daily_limit'   => $daily_limit,
            'limit_reached' => ($daily_limit > 0 && $daily_used >= $daily_limit),
            'api_key_valid' => $api_key_valid,
        ]);
    }

    /**
     * Renders the Editor Integration section description.
     *
     * @return void
     */
    public function renderAiEditorSection(): void
    {
        echo '<p>' . esc_html__('Control the integration of AI features in the Elementor editor.', 'king-addons') . '</p>';
    }

    /**
     * Renders the Enable AI Buttons checkbox field.
     *
     * @return void
     */
    public function renderAiEnableButtonsField(): void
    {
        $options = get_option('king_addons_ai_options', []);
        $enabled = isset($options['enable_ai_buttons']) ? (bool) $options['enable_ai_buttons'] : true;
        printf(
            '<label><input type="checkbox" name="king_addons_ai_options[enable_ai_buttons]" value="1" %s /> %s</label>',
            checked($enabled, true, false),
            esc_html__('Enable AI Text Editing Buttons in Elementor Editor', 'king-addons')
        );
    }
}
