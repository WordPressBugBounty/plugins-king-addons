<?php
/**
 * Live Chat & Support Builder extension.
 *
 * Provides real-time chat support widget with admin inbox.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Live Chat class.
 *
 * Handles admin inbox, frontend widget, REST API, and email notifications.
 */
final class Live_Chat
{
    /**
     * Option name for settings.
     */
    private const OPTION_NAME = 'king_addons_live_chat_options';

    /**
     * Cookie name for visitor identification.
     */
    public const VISITOR_COOKIE = 'king_support_vid';

    /**
     * Conversations table name (without prefix).
     */
    public const TABLE_CONVERSATIONS = 'king_support_conversations';

    /**
     * Messages table name (without prefix).
     */
    public const TABLE_MESSAGES = 'king_support_messages';

    /**
     * REST API namespace.
     */
    public const API_NAMESPACE = 'king-addons/v1';

    /**
     * Default polling interval in milliseconds.
     */
    private const DEFAULT_POLL_INTERVAL = 4000;

    /**
     * Rate limit: minimum seconds between messages.
     */
    private const RATE_LIMIT_SECONDS = 2;

    /**
     * Rate limit: max messages per window.
     */
    private const RATE_LIMIT_MAX_MESSAGES = 20;

    /**
     * Rate limit window in seconds.
     */
    private const RATE_LIMIT_WINDOW = 600;

    /**
     * Singleton instance.
     *
     * @var Live_Chat|null
     */
    private static ?Live_Chat $instance = null;

    /**
     * Cached options array.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * Gets singleton instance.
     *
     * @return Live_Chat
     */
    public static function instance(): Live_Chat
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Registers hooks.
     */
    public function __construct()
    {
        $this->options = $this->get_options();

        // Activation hook
        register_activation_hook(KING_ADDONS_PATH . 'king-addons.php', [$this, 'handle_activation']);

        // Admin hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_king_addons_live_chat_save', [$this, 'handle_save_settings']);

        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_footer', [$this, 'render_frontend_widget']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Admin AJAX for inbox
        add_action('wp_ajax_king_live_chat_get_conversations', [$this, 'ajax_get_conversations']);
        add_action('wp_ajax_king_live_chat_get_conversation', [$this, 'ajax_get_conversation']);
        add_action('wp_ajax_king_live_chat_send_reply', [$this, 'ajax_send_reply']);
        add_action('wp_ajax_king_live_chat_update_status', [$this, 'ajax_update_status']);
        add_action('wp_ajax_king_live_chat_delete_conversation', [$this, 'ajax_delete_conversation']);
    }

    /**
     * Handles plugin activation.
     *
     * Creates database tables and default options.
     *
     * @return void
     */
    public function handle_activation(): void
    {
        if (!get_option(self::OPTION_NAME)) {
            add_option(self::OPTION_NAME, $this->get_default_options());
        }

        $this->create_tables();
    }

    /**
     * Creates database tables.
     *
     * @return void
     */
    public function create_tables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $conversations_table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        $sql_conversations = "CREATE TABLE IF NOT EXISTS $conversations_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            visitor_id varchar(64) NOT NULL,
            visitor_name varchar(100) DEFAULT '',
            visitor_email varchar(100) DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'open',
            last_page_url text,
            referrer text,
            user_agent text,
            ip_address varchar(45),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_message_at datetime DEFAULT NULL,
            unread_admin int(11) NOT NULL DEFAULT 0,
            unread_visitor int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY visitor_id (visitor_id),
            KEY status (status),
            KEY last_message_at (last_message_at)
        ) $charset_collate;";

        $sql_messages = "CREATE TABLE IF NOT EXISTS $messages_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL,
            author_type varchar(20) NOT NULL,
            author_user_id bigint(20) unsigned DEFAULT NULL,
            message_text text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY author_type (author_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_conversations);
        dbDelta($sql_messages);
    }

    /**
     * Gets default options.
     *
     * @return array<string, mixed>
     */
    private function get_default_options(): array
    {
        return [
            // General
            'enabled' => false,
            'widget_mode' => 'live_chat', // live_chat or contact_form
            'position' => 'right',
            'offset_bottom' => 20,
            'offset_side' => 20,
            'z_index' => 9999,

            // Appearance
            'button_size' => 60,
            'button_color' => '#0066ff',
            'button_icon' => 'chat',
            'header_bg' => '#0066ff',
            'header_text_color' => '#ffffff',
            'chat_bg' => '#ffffff',
            'chat_width' => 380,
            'chat_height' => 520,

            // Messages colors
            'visitor_msg_bg' => '#e8f4fd',
            'visitor_msg_text' => '#1d1d1f',
            'admin_msg_bg' => '#0066ff',
            'admin_msg_text' => '#ffffff',

            // Texts
            'header_title' => __('Chat with us', 'king-addons'),
            'header_subtitle' => __('We typically reply within minutes', 'king-addons'),
            'placeholder' => __('Type your message...', 'king-addons'),
            'offline_message' => __('We\'re currently offline. Leave a message and we\'ll get back to you.', 'king-addons'),
            'welcome_message' => __('Hi! How can we help you today?', 'king-addons'),

            // Contact Form Mode texts
            'subject_label' => __('Subject', 'king-addons'),
            'message_label' => __('Your message', 'king-addons'),
            'submit_button' => __('Send Message', 'king-addons'),
            'success_message' => __('Thank you! Your message has been sent. We\'ll get back to you soon.', 'king-addons'),

            // Pre-chat form
            'require_name' => true,
            'require_email' => true,
            'name_label' => __('Your name', 'king-addons'),
            'email_label' => __('Your email', 'king-addons'),
            'start_chat_button' => __('Start Chat', 'king-addons'),

            // Schedule (Pro)
            'schedule_enabled' => false,
            'schedule' => [],

            // Email notifications
            'admin_email' => get_option('admin_email'),
            'notify_new_conversation' => true,
            'notify_new_message' => false,
            'email_subject_admin' => __('New support message from {visitor_name}', 'king-addons'),
            'email_subject_visitor' => __('Reply from {site_name} support', 'king-addons'),

            // Polling
            'poll_interval' => self::DEFAULT_POLL_INTERVAL,
        ];
    }

    /**
     * Gets current options merged with defaults.
     *
     * @return array<string, mixed>
     */
    public function get_options(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args($saved, $this->get_default_options());
    }

    /**
     * Checks if premium features are available.
     *
     * @return bool
     */
    public function is_premium(): bool
    {
        return function_exists('king_addons_freemius')
            && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Renders the admin settings/inbox page.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->options = $this->get_options();
        $is_premium = $this->is_premium();
        $options = $this->options;

        // Ensure tables exist
        $this->create_tables();

        include __DIR__ . '/templates/admin-page.php';
    }

    /**
     * Enqueues admin assets.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-live-chat') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style(
            'king-addons-v3-styles',
            KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_style(
            'king-addons-live-chat-admin',
            KING_ADDONS_URL . 'includes/extensions/Live_Chat/assets/admin.css',
            ['king-addons-v3-styles'],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-live-chat-admin',
            KING_ADDONS_URL . 'includes/extensions/Live_Chat/assets/admin.js',
            ['jquery', 'wp-color-picker'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-live-chat-admin', 'kingLiveChatAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king_live_chat_admin'),
            'strings' => [
                'confirmDelete' => __('Are you sure you want to delete this conversation?', 'king-addons'),
                'sending' => __('Sending...', 'king-addons'),
                'send' => __('Send Reply', 'king-addons'),
                'noMessages' => __('No messages yet', 'king-addons'),
                'error' => __('An error occurred. Please try again.', 'king-addons'),
            ],
        ]);
    }

    /**
     * Enqueues frontend assets.
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void
    {
        if (is_admin() || !$this->options['enabled']) {
            return;
        }

        wp_enqueue_style(
            'king-addons-live-chat',
            KING_ADDONS_URL . 'includes/extensions/Live_Chat/assets/frontend.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-live-chat',
            KING_ADDONS_URL . 'includes/extensions/Live_Chat/assets/frontend.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-live-chat', 'kingLiveChat', [
            'restUrl' => rest_url(self::API_NAMESPACE . '/support'),
            'nonce' => wp_create_nonce('wp_rest'),
            'visitorId' => $this->get_or_create_visitor_id(),
            'pollInterval' => intval($this->options['poll_interval']),
            'isOnline' => $this->is_online(),
            'widgetMode' => $this->options['widget_mode'] ?? 'live_chat',
            'options' => [
                'position' => $this->options['position'],
                'requireName' => $this->options['require_name'],
                'requireEmail' => $this->options['require_email'],
            ],
            'strings' => [
                'headerTitle' => $this->options['header_title'],
                'headerSubtitle' => $this->options['header_subtitle'],
                'placeholder' => $this->options['placeholder'],
                'offlineMessage' => $this->options['offline_message'],
                'welcomeMessage' => $this->options['welcome_message'],
                'nameLabel' => $this->options['name_label'],
                'emailLabel' => $this->options['email_label'],
                'startChat' => $this->options['start_chat_button'],
                'send' => __('Send', 'king-addons'),
                'typing' => __('typing...', 'king-addons'),
                'justNow' => __('Just now', 'king-addons'),
                'errorNetwork' => __('Network error. Please try again.', 'king-addons'),
                'errorRateLimit' => __('Please wait a moment before sending another message.', 'king-addons'),
                // Contact Form Mode strings
                'subjectLabel' => $this->options['subject_label'] ?? __('Subject', 'king-addons'),
                'messageLabel' => $this->options['message_label'] ?? __('Your message', 'king-addons'),
                'submitButton' => $this->options['submit_button'] ?? __('Send Message', 'king-addons'),
                'successMessage' => $this->options['success_message'] ?? __('Thank you! Your message has been sent.', 'king-addons'),
            ],
        ]);
    }

    /**
     * Renders the frontend chat widget markup.
     *
     * @return void
     */
    public function render_frontend_widget(): void
    {
        if (is_admin() || !$this->options['enabled']) {
            return;
        }

        $options = $this->options;
        $position = $options['position'];
        $is_online = $this->is_online();

        // Generate inline styles
        $button_styles = sprintf(
            '--ka-chat-btn-size: %dpx; --ka-chat-btn-color: %s;',
            intval($options['button_size']),
            esc_attr($options['button_color'])
        );

        $panel_styles = sprintf(
            '--ka-chat-width: %dpx; --ka-chat-height: %dpx; --ka-chat-header-bg: %s; --ka-chat-header-text: %s; --ka-chat-bg: %s; --ka-chat-visitor-bg: %s; --ka-chat-visitor-text: %s; --ka-chat-admin-bg: %s; --ka-chat-admin-text: %s;',
            intval($options['chat_width']),
            intval($options['chat_height']),
            esc_attr($options['header_bg']),
            esc_attr($options['header_text_color']),
            esc_attr($options['chat_bg']),
            esc_attr($options['visitor_msg_bg']),
            esc_attr($options['visitor_msg_text']),
            esc_attr($options['admin_msg_bg']),
            esc_attr($options['admin_msg_text'])
        );

        $position_styles = sprintf(
            'bottom: %dpx; %s: %dpx; z-index: %d;',
            intval($options['offset_bottom']),
            $position === 'left' ? 'left' : 'right',
            intval($options['offset_side']),
            intval($options['z_index'])
        );

        $widget_mode = $options['widget_mode'] ?? 'live_chat';
        ?>
        <div id="ka-live-chat" 
             class="ka-live-chat ka-live-chat--<?php echo esc_attr($position); ?> ka-live-chat--mode-<?php echo esc_attr($widget_mode); ?>" 
             style="<?php echo esc_attr($position_styles); ?>"
             data-online="<?php echo $is_online ? 'true' : 'false'; ?>"
             data-mode="<?php echo esc_attr($widget_mode); ?>">
            
            <!-- Floating Button -->
            <button type="button" 
                    class="ka-live-chat__button" 
                    style="<?php echo esc_attr($button_styles); ?>"
                    aria-label="<?php esc_attr_e('Open chat', 'king-addons'); ?>">
                <?php if ($widget_mode === 'contact_form'): ?>
                <svg class="ka-live-chat__icon ka-live-chat__icon--chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <?php else: ?>
                <svg class="ka-live-chat__icon ka-live-chat__icon--chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
                <?php endif; ?>
                <svg class="ka-live-chat__icon ka-live-chat__icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                <span class="ka-live-chat__badge" style="display: none;">0</span>
            </button>

            <!-- Chat Panel -->
            <div class="ka-live-chat__panel" style="<?php echo esc_attr($panel_styles); ?>">
                <!-- Header -->
                <div class="ka-live-chat__header">
                    <div class="ka-live-chat__header-info">
                        <div class="ka-live-chat__header-title"><?php echo esc_html($options['header_title']); ?></div>
                        <div class="ka-live-chat__header-subtitle">
                            <?php if ($widget_mode === 'live_chat'): ?>
                            <span class="ka-live-chat__status-dot <?php echo $is_online ? 'ka-live-chat__status-dot--online' : ''; ?>"></span>
                            <?php endif; ?>
                            <?php echo esc_html($options['header_subtitle']); ?>
                        </div>
                    </div>
                    <button type="button" class="ka-live-chat__close" aria-label="<?php esc_attr_e('Close chat', 'king-addons'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <?php if ($widget_mode === 'contact_form'): ?>
                <!-- Contact Form Mode -->
                <div class="ka-live-chat__contact-form">
                    <?php if ($options['require_name']): ?>
                    <div class="ka-live-chat__field">
                        <label for="ka-chat-name"><?php echo esc_html($options['name_label']); ?></label>
                        <input type="text" id="ka-chat-name" name="name" required>
                    </div>
                    <?php endif; ?>
                    <?php if ($options['require_email']): ?>
                    <div class="ka-live-chat__field">
                        <label for="ka-chat-email"><?php echo esc_html($options['email_label']); ?></label>
                        <input type="email" id="ka-chat-email" name="email" required>
                    </div>
                    <?php endif; ?>
                    <div class="ka-live-chat__field">
                        <label for="ka-chat-subject"><?php echo esc_html($options['subject_label'] ?? __('Subject', 'king-addons')); ?></label>
                        <input type="text" id="ka-chat-subject" name="subject">
                    </div>
                    <div class="ka-live-chat__field">
                        <label for="ka-chat-message"><?php echo esc_html($options['message_label'] ?? __('Your message', 'king-addons')); ?></label>
                        <textarea id="ka-chat-message" name="message" rows="4" required></textarea>
                    </div>
                    <!-- Honeypot -->
                    <div class="ka-live-chat__hp" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <button type="button" class="ka-live-chat__submit">
                        <?php echo esc_html($options['submit_button'] ?? __('Send Message', 'king-addons')); ?>
                    </button>
                </div>
                
                <!-- Success Message -->
                <div class="ka-live-chat__success" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <p><?php echo esc_html($options['success_message'] ?? __('Thank you! Your message has been sent.', 'king-addons')); ?></p>
                    <button type="button" class="ka-live-chat__new-message"><?php esc_html_e('Send another message', 'king-addons'); ?></button>
                </div>

                <?php else: ?>
                <!-- Live Chat Mode -->
                <!-- Pre-chat Form -->
                <div class="ka-live-chat__prechat">
                    <?php if ($options['require_name']): ?>
                    <div class="ka-live-chat__field">
                        <label for="ka-chat-name"><?php echo esc_html($options['name_label']); ?></label>
                        <input type="text" id="ka-chat-name" name="name" required>
                    </div>
                    <?php endif; ?>
                    <?php if ($options['require_email']): ?>
                    <div class="ka-live-chat__field">
                        <label for="ka-chat-email"><?php echo esc_html($options['email_label']); ?></label>
                        <input type="email" id="ka-chat-email" name="email" required>
                    </div>
                    <?php endif; ?>
                    <!-- Honeypot -->
                    <div class="ka-live-chat__hp" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <button type="button" class="ka-live-chat__start">
                        <?php echo esc_html($options['start_chat_button']); ?>
                    </button>
                </div>

                <!-- Messages Area -->
                <div class="ka-live-chat__messages">
                    <div class="ka-live-chat__messages-list"></div>
                </div>

                <!-- Input Area -->
                <div class="ka-live-chat__input">
                    <textarea placeholder="<?php echo esc_attr($options['placeholder']); ?>" rows="1"></textarea>
                    <button type="button" class="ka-live-chat__send" aria-label="<?php esc_attr_e('Send message', 'king-addons'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Offline Message -->
                <?php if (!$is_online): ?>
                <div class="ka-live-chat__offline">
                    <?php echo esc_html($options['offline_message']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Checks if support is currently online.
     *
     * @return bool
     */
    private function is_online(): bool
    {
        if (!$this->options['schedule_enabled'] || !$this->is_premium()) {
            return true;
        }

        // Pro: check schedule
        // TODO: implement schedule check in Pro version
        return true;
    }

    /**
     * Gets or creates visitor ID from cookie.
     *
     * @return string
     */
    private function get_or_create_visitor_id(): string
    {
        if (isset($_COOKIE[self::VISITOR_COOKIE])) {
            return sanitize_text_field($_COOKIE[self::VISITOR_COOKIE]);
        }

        $visitor_id = wp_generate_uuid4();

        // Cookie will be set via JavaScript for proper handling
        return $visitor_id;
    }

    /**
     * Registers REST API routes.
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        // Initialize or restore conversation
        register_rest_route(self::API_NAMESPACE, '/support/conversation/init', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_init_conversation'],
            'permission_callback' => '__return_true',
        ]);

        // Send message
        register_rest_route(self::API_NAMESPACE, '/support/message/send', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_send_message'],
            'permission_callback' => '__return_true',
        ]);

        // Poll for new messages
        register_rest_route(self::API_NAMESPACE, '/support/messages/poll', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_poll_messages'],
            'permission_callback' => '__return_true',
        ]);

        // Mark messages as read
        register_rest_route(self::API_NAMESPACE, '/support/messages/read', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_mark_read'],
            'permission_callback' => '__return_true',
        ]);

        // Contact Form submission
        register_rest_route(self::API_NAMESPACE, '/support/contact', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_submit_contact_form'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST: Submit contact form (Contact Form mode).
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_submit_contact_form(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $visitor_id = sanitize_text_field($request->get_param('visitor_id') ?? '');
        $name = sanitize_text_field($request->get_param('name') ?? '');
        $email = sanitize_email($request->get_param('email') ?? '');
        $subject = sanitize_text_field($request->get_param('subject') ?? '');
        $message = sanitize_textarea_field($request->get_param('message') ?? '');
        $page_url = esc_url_raw($request->get_param('page_url') ?? '');
        $referrer = esc_url_raw($request->get_param('referrer') ?? '');

        if (empty($visitor_id) || empty($message)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Rate limiting
        if (!$this->check_rate_limit($visitor_id)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Too many messages. Please wait a moment.', 'king-addons'),
            ], 429);
        }

        $conversations_table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        // Create conversation
        $wpdb->insert($conversations_table, [
            'visitor_id' => $visitor_id,
            'visitor_name' => $name,
            'visitor_email' => $email,
            'status' => 'open',
            'last_page_url' => $page_url,
            'referrer' => $referrer,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'ip_address' => $this->get_client_ip(),
            'created_at' => current_time('mysql'),
            'last_message_at' => current_time('mysql'),
            'unread_admin' => 1,
        ]);

        $conversation_id = $wpdb->insert_id;

        if (!$conversation_id) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Failed to create conversation'], 500);
        }

        // Combine subject and message
        $full_message = $message;
        if (!empty($subject)) {
            $full_message = "[{$subject}]\n\n{$message}";
        }

        // Insert message
        $wpdb->insert($messages_table, [
            'conversation_id' => $conversation_id,
            'author_type' => 'visitor',
            'message_text' => $full_message,
            'created_at' => current_time('mysql'),
        ]);

        // Close conversation immediately for contact form mode
        $wpdb->update(
            $conversations_table,
            ['status' => 'closed'],
            ['id' => $conversation_id]
        );

        // Send email notification
        $this->send_admin_notification($conversation_id, $name, $email, $full_message);

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Message sent successfully', 'king-addons'),
        ]);
    }

    /**
     * REST: Initialize or restore conversation.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_init_conversation(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $visitor_id = sanitize_text_field($request->get_param('visitor_id') ?? '');
        $name = sanitize_text_field($request->get_param('name') ?? '');
        $email = sanitize_email($request->get_param('email') ?? '');
        $page_url = esc_url_raw($request->get_param('page_url') ?? '');
        $referrer = esc_url_raw($request->get_param('referrer') ?? '');

        if (empty($visitor_id)) {
            return new \WP_REST_Response(['error' => 'Invalid visitor ID'], 400);
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        // Check for existing open conversation
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE visitor_id = %s AND status = 'open' ORDER BY created_at DESC LIMIT 1",
            $visitor_id
        ));

        if ($conversation) {
            // Update visitor info if provided
            if (!empty($name) || !empty($email)) {
                $wpdb->update(
                    $table,
                    array_filter([
                        'visitor_name' => $name ?: null,
                        'visitor_email' => $email ?: null,
                    ]),
                    ['id' => $conversation->id]
                );
            }

            // Get messages
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY created_at ASC",
                $conversation->id
            ));

            return new \WP_REST_Response([
                'conversation_id' => $conversation->id,
                'messages' => $this->format_messages($messages),
                'unread' => intval($conversation->unread_visitor),
            ]);
        }

        // No existing conversation - will be created on first message
        return new \WP_REST_Response([
            'conversation_id' => null,
            'messages' => [],
            'unread' => 0,
        ]);
    }

    /**
     * REST: Send message from visitor.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_send_message(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        // Honeypot check
        $honeypot = $request->get_param('website');
        if (!empty($honeypot)) {
            return new \WP_REST_Response(['error' => 'Spam detected'], 403);
        }

        $visitor_id = sanitize_text_field($request->get_param('visitor_id') ?? '');
        $conversation_id = intval($request->get_param('conversation_id') ?? 0);
        $message = sanitize_textarea_field($request->get_param('message') ?? '');
        $name = sanitize_text_field($request->get_param('name') ?? '');
        $email = sanitize_email($request->get_param('email') ?? '');
        $page_url = esc_url_raw($request->get_param('page_url') ?? '');
        $referrer = esc_url_raw($request->get_param('referrer') ?? '');

        if (empty($visitor_id) || empty($message)) {
            return new \WP_REST_Response(['error' => 'Missing required fields'], 400);
        }

        // Rate limiting
        if (!$this->check_rate_limit($visitor_id)) {
            return new \WP_REST_Response(['error' => 'rate_limit'], 429);
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        // Create conversation if needed
        if (!$conversation_id) {
            $wpdb->insert($table, [
                'visitor_id' => $visitor_id,
                'visitor_name' => $name,
                'visitor_email' => $email,
                'status' => 'open',
                'last_page_url' => $page_url,
                'referrer' => $referrer,
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql'),
                'last_message_at' => current_time('mysql'),
                'unread_admin' => 1,
            ]);
            $conversation_id = $wpdb->insert_id;
            $is_new = true;
        } else {
            // Verify conversation belongs to visitor
            $conv = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table WHERE id = %d AND visitor_id = %s",
                $conversation_id,
                $visitor_id
            ));

            if (!$conv) {
                return new \WP_REST_Response(['error' => 'Invalid conversation'], 403);
            }

            // Update conversation
            $wpdb->update($table, [
                'last_message_at' => current_time('mysql'),
                'unread_admin' => $wpdb->get_var($wpdb->prepare(
                    "SELECT unread_admin FROM $table WHERE id = %d",
                    $conversation_id
                )) + 1,
            ], ['id' => $conversation_id]);
            $is_new = false;
        }

        // Insert message
        $wpdb->insert($messages_table, [
            'conversation_id' => $conversation_id,
            'author_type' => 'visitor',
            'message_text' => $message,
            'created_at' => current_time('mysql'),
        ]);
        $message_id = $wpdb->insert_id;

        // Send email notification to admin
        if ($is_new && $this->options['notify_new_conversation']) {
            $this->send_admin_notification($conversation_id, $message, $name, $email);
        } elseif (!$is_new && $this->options['notify_new_message']) {
            $this->send_admin_notification($conversation_id, $message, $name, $email, false);
        }

        // Update rate limit
        $this->update_rate_limit($visitor_id);

        return new \WP_REST_Response([
            'success' => true,
            'conversation_id' => $conversation_id,
            'message_id' => $message_id,
            'created_at' => current_time('mysql'),
        ]);
    }

    /**
     * REST: Poll for new messages.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_poll_messages(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $visitor_id = sanitize_text_field($request->get_param('visitor_id') ?? '');
        $conversation_id = intval($request->get_param('conversation_id') ?? 0);
        $after_id = intval($request->get_param('after_id') ?? 0);

        if (empty($visitor_id) || !$conversation_id) {
            return new \WP_REST_Response(['messages' => [], 'unread' => 0]);
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        // Verify conversation
        $conv = $wpdb->get_row($wpdb->prepare(
            "SELECT id, unread_visitor FROM $table WHERE id = %d AND visitor_id = %s",
            $conversation_id,
            $visitor_id
        ));

        if (!$conv) {
            return new \WP_REST_Response(['messages' => [], 'unread' => 0]);
        }

        // Get new messages
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE conversation_id = %d AND id > %d ORDER BY created_at ASC",
            $conversation_id,
            $after_id
        ));

        return new \WP_REST_Response([
            'messages' => $this->format_messages($messages),
            'unread' => intval($conv->unread_visitor),
        ]);
    }

    /**
     * REST: Mark messages as read.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_mark_read(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $visitor_id = sanitize_text_field($request->get_param('visitor_id') ?? '');
        $conversation_id = intval($request->get_param('conversation_id') ?? 0);

        if (empty($visitor_id) || !$conversation_id) {
            return new \WP_REST_Response(['success' => false]);
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        // Verify and update
        $updated = $wpdb->update(
            $table,
            ['unread_visitor' => 0],
            ['id' => $conversation_id, 'visitor_id' => $visitor_id]
        );

        // Mark admin messages as read
        $wpdb->query($wpdb->prepare(
            "UPDATE $messages_table SET is_read = 1 WHERE conversation_id = %d AND author_type = 'admin'",
            $conversation_id
        ));

        return new \WP_REST_Response(['success' => $updated !== false]);
    }

    /**
     * Formats messages for JSON response.
     *
     * @param array $messages Database rows.
     * @return array
     */
    private function format_messages(array $messages): array
    {
        $formatted = [];
        foreach ($messages as $msg) {
            $formatted[] = [
                'id' => intval($msg->id),
                'type' => $msg->author_type,
                'text' => $msg->message_text,
                'time' => $msg->created_at,
                'is_read' => (bool) $msg->is_read,
            ];
        }
        return $formatted;
    }

    /**
     * Checks rate limit for visitor.
     *
     * @param string $visitor_id Visitor ID.
     * @return bool
     */
    private function check_rate_limit(string $visitor_id): bool
    {
        $transient_key = 'ka_chat_rl_' . md5($visitor_id);
        $data = get_transient($transient_key);

        if (!$data) {
            return true;
        }

        // Check minimum time between messages
        if (time() - $data['last'] < self::RATE_LIMIT_SECONDS) {
            return false;
        }

        // Check max messages in window
        if ($data['count'] >= self::RATE_LIMIT_MAX_MESSAGES) {
            return false;
        }

        return true;
    }

    /**
     * Updates rate limit counter.
     *
     * @param string $visitor_id Visitor ID.
     * @return void
     */
    private function update_rate_limit(string $visitor_id): void
    {
        $transient_key = 'ka_chat_rl_' . md5($visitor_id);
        $data = get_transient($transient_key);

        if (!$data) {
            $data = ['count' => 0, 'last' => 0];
        }

        $data['count']++;
        $data['last'] = time();

        set_transient($transient_key, $data, self::RATE_LIMIT_WINDOW);
    }

    /**
     * Gets client IP address.
     *
     * @return string
     */
    private function get_client_ip(): string
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }

    /**
     * Sends email notification to admin.
     *
     * @param int $conversation_id Conversation ID.
     * @param string $message Message text.
     * @param string $name Visitor name.
     * @param string $email Visitor email.
     * @param bool $is_new Whether this is a new conversation.
     * @return void
     */
    private function send_admin_notification(int $conversation_id, string $message, string $name, string $email, bool $is_new = true): void
    {
        $admin_email = $this->options['admin_email'];
        if (empty($admin_email)) {
            return;
        }

        $subject = str_replace(
            ['{visitor_name}', '{site_name}'],
            [$name ?: __('Visitor', 'king-addons'), get_bloginfo('name')],
            $this->options['email_subject_admin']
        );

        $inbox_url = admin_url('admin.php?page=king-addons-live-chat&conversation=' . $conversation_id);

        $body = sprintf(
            "%s\n\n%s: %s\n%s: %s\n\n%s:\n%s\n\n%s:\n%s",
            $is_new ? __('New support conversation started', 'king-addons') : __('New message in support conversation', 'king-addons'),
            __('Name', 'king-addons'),
            $name ?: __('Not provided', 'king-addons'),
            __('Email', 'king-addons'),
            $email ?: __('Not provided', 'king-addons'),
            __('Message', 'king-addons'),
            $message,
            __('View conversation', 'king-addons'),
            $inbox_url
        );

        wp_mail($admin_email, $subject, $body);
    }

    /**
     * Sends email to visitor with admin reply.
     *
     * @param string $email Visitor email.
     * @param string $name Visitor name.
     * @param string $message Reply text.
     * @return bool
     */
    public function send_visitor_notification(string $email, string $name, string $message): bool
    {
        if (empty($email) || !is_email($email)) {
            return false;
        }

        $subject = str_replace(
            ['{visitor_name}', '{site_name}'],
            [$name ?: __('there', 'king-addons'), get_bloginfo('name')],
            $this->options['email_subject_visitor']
        );

        $body = sprintf(
            "%s %s,\n\n%s\n\n--\n%s",
            __('Hi', 'king-addons'),
            $name ?: '',
            $message,
            get_bloginfo('name')
        );

        return wp_mail($email, $subject, $body);
    }

    /**
     * AJAX: Get conversations list.
     *
     * @return void
     */
    public function ajax_get_conversations(): void
    {
        check_ajax_referer('king_live_chat_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;

        $status = sanitize_text_field($_POST['status'] ?? 'all');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $where = "1=1";
        $params = [];

        if ($status !== 'all') {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        if (!empty($search)) {
            $where .= " AND (visitor_name LIKE %s OR visitor_email LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_param;
            $params[] = $search_param;
        }

        $total = $wpdb->get_var(
            empty($params) 
                ? "SELECT COUNT(*) FROM $table WHERE $where"
                : $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where", ...$params)
        );

        $query = "SELECT * FROM $table WHERE $where ORDER BY last_message_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $conversations = $wpdb->get_results($wpdb->prepare($query, ...$params));

        $formatted = [];
        foreach ($conversations as $conv) {
            $formatted[] = [
                'id' => intval($conv->id),
                'name' => $conv->visitor_name ?: __('Anonymous', 'king-addons'),
                'email' => $conv->visitor_email,
                'status' => $conv->status,
                'unread' => intval($conv->unread_admin),
                'last_message' => $conv->last_message_at,
                'created' => $conv->created_at,
            ];
        }

        wp_send_json_success([
            'conversations' => $formatted,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
        ]);
    }

    /**
     * AJAX: Get single conversation with messages.
     *
     * @return void
     */
    public function ajax_get_conversation(): void
    {
        check_ajax_referer('king_live_chat_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $conversation_id = intval($_POST['conversation_id'] ?? 0);

        if (!$conversation_id) {
            wp_send_json_error('Invalid conversation');
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            wp_send_json_error('Conversation not found');
        }

        // Mark as read
        $wpdb->update($table, ['unread_admin' => 0], ['id' => $conversation_id]);
        $wpdb->query($wpdb->prepare(
            "UPDATE $messages_table SET is_read = 1 WHERE conversation_id = %d AND author_type = 'visitor'",
            $conversation_id
        ));

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as admin_name 
             FROM $messages_table m 
             LEFT JOIN {$wpdb->users} u ON m.author_user_id = u.ID
             WHERE m.conversation_id = %d 
             ORDER BY m.created_at ASC",
            $conversation_id
        ));

        $formatted_messages = [];
        foreach ($messages as $msg) {
            $formatted_messages[] = [
                'id' => intval($msg->id),
                'type' => $msg->author_type,
                'text' => $msg->message_text,
                'time' => $msg->created_at,
                'admin_name' => $msg->admin_name ?: null,
            ];
        }

        wp_send_json_success([
            'conversation' => [
                'id' => intval($conversation->id),
                'name' => $conversation->visitor_name,
                'email' => $conversation->visitor_email,
                'status' => $conversation->status,
                'page_url' => $conversation->last_page_url,
                'referrer' => $conversation->referrer,
                'user_agent' => $conversation->user_agent,
                'ip' => $conversation->ip_address,
                'created' => $conversation->created_at,
            ],
            'messages' => $formatted_messages,
        ]);
    }

    /**
     * AJAX: Send admin reply.
     *
     * @return void
     */
    public function ajax_send_reply(): void
    {
        check_ajax_referer('king_live_chat_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;

        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$conversation_id || empty($message)) {
            wp_send_json_error('Missing required fields');
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        // Get conversation
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            wp_send_json_error('Conversation not found');
        }

        // Insert message
        $wpdb->insert($messages_table, [
            'conversation_id' => $conversation_id,
            'author_type' => 'admin',
            'author_user_id' => get_current_user_id(),
            'message_text' => $message,
            'created_at' => current_time('mysql'),
        ]);
        $message_id = $wpdb->insert_id;

        // Update conversation
        $wpdb->update($table, [
            'last_message_at' => current_time('mysql'),
            'unread_visitor' => $conversation->unread_visitor + 1,
        ], ['id' => $conversation_id]);

        // Send email to visitor
        if (!empty($conversation->visitor_email)) {
            $this->send_visitor_notification(
                $conversation->visitor_email,
                $conversation->visitor_name,
                $message
            );
        }

        $user = wp_get_current_user();

        wp_send_json_success([
            'message' => [
                'id' => $message_id,
                'type' => 'admin',
                'text' => $message,
                'time' => current_time('mysql'),
                'admin_name' => $user->display_name,
            ],
        ]);
    }

    /**
     * AJAX: Update conversation status.
     *
     * @return void
     */
    public function ajax_update_status(): void
    {
        check_ajax_referer('king_live_chat_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;

        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        if (!$conversation_id || !in_array($status, ['open', 'closed'], true)) {
            wp_send_json_error('Invalid parameters');
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;

        $updated = $wpdb->update(
            $table,
            ['status' => $status],
            ['id' => $conversation_id]
        );

        wp_send_json_success(['updated' => $updated !== false]);
    }

    /**
     * AJAX: Delete conversation.
     *
     * @return void
     */
    public function ajax_delete_conversation(): void
    {
        check_ajax_referer('king_live_chat_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;

        $conversation_id = intval($_POST['conversation_id'] ?? 0);

        if (!$conversation_id) {
            wp_send_json_error('Invalid conversation');
        }

        $table = $wpdb->prefix . self::TABLE_CONVERSATIONS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        // Delete messages first
        $wpdb->delete($messages_table, ['conversation_id' => $conversation_id]);

        // Delete conversation
        $deleted = $wpdb->delete($table, ['id' => $conversation_id]);

        wp_send_json_success(['deleted' => $deleted !== false]);
    }

    /**
     * Handles settings save.
     *
     * @return void
     */
    public function handle_save_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('king_addons_live_chat_save', 'king_live_chat_nonce');

        $options = [];

        // General
        $options['enabled'] = !empty($_POST['enabled']);
        $options['widget_mode'] = in_array($_POST['widget_mode'] ?? 'live_chat', ['live_chat', 'contact_form']) 
            ? sanitize_text_field($_POST['widget_mode']) 
            : 'live_chat';
        $options['position'] = sanitize_text_field($_POST['position'] ?? 'right');
        $options['offset_bottom'] = intval($_POST['offset_bottom'] ?? 20);
        $options['offset_side'] = intval($_POST['offset_side'] ?? 20);
        $options['z_index'] = intval($_POST['z_index'] ?? 9999);

        // Appearance
        $options['button_size'] = intval($_POST['button_size'] ?? 60);
        $options['button_color'] = sanitize_hex_color($_POST['button_color'] ?? '#0066ff');
        $options['header_bg'] = sanitize_hex_color($_POST['header_bg'] ?? '#0066ff');
        $options['header_text_color'] = sanitize_hex_color($_POST['header_text_color'] ?? '#ffffff');
        $options['chat_bg'] = sanitize_hex_color($_POST['chat_bg'] ?? '#ffffff');
        $options['chat_width'] = intval($_POST['chat_width'] ?? 380);
        $options['chat_height'] = intval($_POST['chat_height'] ?? 520);
        $options['visitor_msg_bg'] = sanitize_hex_color($_POST['visitor_msg_bg'] ?? '#e8f4fd');
        $options['visitor_msg_text'] = sanitize_hex_color($_POST['visitor_msg_text'] ?? '#1d1d1f');
        $options['admin_msg_bg'] = sanitize_hex_color($_POST['admin_msg_bg'] ?? '#0066ff');
        $options['admin_msg_text'] = sanitize_hex_color($_POST['admin_msg_text'] ?? '#ffffff');

        // Texts
        $options['header_title'] = sanitize_text_field($_POST['header_title'] ?? '');
        $options['header_subtitle'] = sanitize_text_field($_POST['header_subtitle'] ?? '');
        $options['placeholder'] = sanitize_text_field($_POST['placeholder'] ?? '');
        $options['offline_message'] = sanitize_textarea_field($_POST['offline_message'] ?? '');
        $options['welcome_message'] = sanitize_textarea_field($_POST['welcome_message'] ?? '');

        // Contact Form mode texts
        $options['subject_label'] = sanitize_text_field($_POST['subject_label'] ?? __('Subject', 'king-addons'));
        $options['message_label'] = sanitize_text_field($_POST['message_label'] ?? __('Your message', 'king-addons'));
        $options['submit_button'] = sanitize_text_field($_POST['submit_button'] ?? __('Send Message', 'king-addons'));
        $options['success_message'] = sanitize_textarea_field($_POST['success_message'] ?? __('Thank you! Your message has been sent.', 'king-addons'));

        // Pre-chat form
        $options['require_name'] = !empty($_POST['require_name']);
        $options['require_email'] = !empty($_POST['require_email']);
        $options['name_label'] = sanitize_text_field($_POST['name_label'] ?? '');
        $options['email_label'] = sanitize_text_field($_POST['email_label'] ?? '');
        $options['start_chat_button'] = sanitize_text_field($_POST['start_chat_button'] ?? '');

        // Email
        $options['admin_email'] = sanitize_email($_POST['admin_email'] ?? '');
        $options['notify_new_conversation'] = !empty($_POST['notify_new_conversation']);
        $options['notify_new_message'] = !empty($_POST['notify_new_message']);
        $options['email_subject_admin'] = sanitize_text_field($_POST['email_subject_admin'] ?? '');
        $options['email_subject_visitor'] = sanitize_text_field($_POST['email_subject_visitor'] ?? '');

        // Polling
        $options['poll_interval'] = max(2000, intval($_POST['poll_interval'] ?? self::DEFAULT_POLL_INTERVAL));

        update_option(self::OPTION_NAME, $options);

        wp_redirect(admin_url('admin.php?page=king-addons-live-chat&tab=settings&saved=1'));
        exit;
    }
}
