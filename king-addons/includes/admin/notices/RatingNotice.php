<?php
/**
 * Rating Notice for King Addons
 * Shows an interactive star rating notice in the WordPress admin
 */

namespace King_Addons\Admin\Notices;

if (!defined('ABSPATH')) {
    exit;
}

class RatingNotice {
    
    private const OPTION_DISMISS = 'king_addons_rating_dismiss';
    private const OPTION_RATED = 'king_addons_rating_rated';
    private const OPTION_LATER_TIME = 'king_addons_rating_later_time';
    private const OPTION_ACTIVATION_TIME = 'king_addons_optionActivationTime';
    private const DAYS_BEFORE_SHOW = 14;
    private const DAYS_REMIND_LATER = 7;
    
    /**
     * Email for receiving low rating feedback
     * Change this to your actual feedback email
     */
    private const FEEDBACK_EMAIL = 'starter@developer5.dev';
    
    /**
     * Singleton instance
     */
    private static ?RatingNotice $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function instance(): RatingNotice {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        if (!current_user_can('administrator')) {
            return;
        }
        
        if (!empty(get_option(self::OPTION_DISMISS)) || !empty(get_option(self::OPTION_RATED))) {
            return;
        }
        
        add_action('admin_init', [$this, 'check_show_notice']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_king_addons_rating_dismiss', [$this, 'ajax_dismiss']);
        add_action('wp_ajax_king_addons_rating_later', [$this, 'ajax_later']);
        add_action('wp_ajax_king_addons_rating_rated', [$this, 'ajax_rated']);
        add_action('wp_ajax_king_addons_rating_feedback', [$this, 'ajax_feedback']);
    }
    
    /**
     * Check if we should show the notice based on timing
     * 
     * Logic:
     * 1. If user clicked "Maybe Later" - check 7 days from that time
     * 2. If user is an active free user (dismissed upgrade notice) - can show immediately
     * 3. Otherwise - check 14 days from activation time
     * 
     * This ensures:
     * - New users see it after 14 days
     * - Active free users (who dismissed upgrade notice) see it sooner
     * - Premium users see it after 14 days (they don't have upgrade notice)
     */
    public function check_show_notice(): void {
        if ($this->should_show_notice()) {
            add_action('admin_notices', [$this, 'render_notice']);
        }
    }
    
    /**
     * Enqueue CSS and JS assets
     */
    public function enqueue_assets(): void {
        if (!$this->should_show_notice()) {
            return;
        }
        
        wp_enqueue_style(
            'king-addons-rating-notice',
            KING_ADDONS_URL . 'includes/admin/css/rating-notice.css',
            [],
            KING_ADDONS_VERSION
        );
        
        wp_enqueue_script(
            'king-addons-rating-notice',
            KING_ADDONS_URL . 'includes/admin/js/rating-notice.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );
        
        wp_localize_script('king-addons-rating-notice', 'KingAddonsRating', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king-addons-rating-nonce'),
            'wpOrgUrl' => 'https://wordpress.org/support/plugin/king-addons/reviews/?filter=5#new-post',
        ]);
    }
    
    /**
     * Check if notice should be displayed
     * 
     * Priority logic:
     * 1. Never show if already dismissed or rated
     * 2. If "Maybe Later" was clicked - wait 7 days from that time
     * 3. Premium users - show immediately (they paid, they're happy!)
     * 4. If user dismissed upgrade notice (active free user) - can show after 3 days
     * 5. Otherwise - wait 14 days from plugin activation
     */
    private function should_show_notice(): bool {
        // Already dismissed or rated - never show
        if (!empty(get_option(self::OPTION_DISMISS)) || !empty(get_option(self::OPTION_RATED))) {
            return false;
        }
        
        $later_time = get_option(self::OPTION_LATER_TIME);
        $activation_time = get_option(self::OPTION_ACTIVATION_TIME);
        $user_id = get_current_user_id();
        
        // Case 1: User clicked "Maybe Later" - check 7 days from that time
        if ($later_time) {
            $past_date = strtotime("-" . self::DAYS_REMIND_LATER . " days");
            return $past_date >= $later_time;
        }
        
        // Case 2: Premium users - show immediately
        // They already paid, they're clearly happy with the plugin!
        if (function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only()) {
            return true;
        }
        
        // Case 3: Check if user previously dismissed the upgrade notice (active free user)
        // This means they've been using the plugin actively
        $upgrade_notice_dismissed_time = get_user_meta($user_id, 'king_addons_premium_notice_dismissed_time', true);
        if ($upgrade_notice_dismissed_time) {
            // Active user who dismissed upgrade notice - can show rating notice
            // But let's give them at least 3 days after dismissing upgrade notice
            $grace_period = strtotime("-3 days");
            return $grace_period >= $upgrade_notice_dismissed_time;
        }
        
        // Case 4: Standard check - 14 days from activation
        if (false === $activation_time) {
            return false;
        }
        
        $past_date = strtotime("-" . self::DAYS_BEFORE_SHOW . " days");
        return $past_date >= $activation_time;
    }
    
    /**
     * Render the rating notice HTML
     */
    public function render_notice(): void {
        $logo_url = KING_ADDONS_URL . 'includes/admin/img/icon-for-admin.svg';
        ?>
        <div class="notice king-addons-rating-notice is-dismissible">
            <div class="king-addons-rating-notice-inner">
                <div class="king-addons-rating-notice-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="King Addons">
                </div>
                <div class="king-addons-rating-notice-content">
                    <h3><?php esc_html_e('Enjoying King Addons for Elementor?', 'king-addons'); ?> 🎉</h3>
                    <p><?php esc_html_e('We hope you love using King Addons! Could you please take a moment to rate us? Your feedback helps us grow and improve!', 'king-addons'); ?></p>
                    
                    <div class="king-addons-rating-stars" data-rating="0">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="king-addons-star" data-star="<?php echo $i; ?>">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        <?php endfor; ?>
                        <span class="king-addons-rating-text"></span>
                    </div>
                    
                    <!-- Feedback form for low ratings -->
                    <div class="king-addons-feedback-form" style="display: none;">
                        <p><?php esc_html_e('We\'re sorry to hear that! Please tell us how we can improve:', 'king-addons'); ?></p>
                        <textarea class="king-addons-feedback-text" rows="3" placeholder="<?php esc_attr_e('Your feedback helps us make King Addons better...', 'king-addons'); ?>"></textarea>
                        <div class="king-addons-feedback-actions">
                            <button type="button" class="button button-primary king-addons-submit-feedback">
                                <?php esc_html_e('Send Feedback', 'king-addons'); ?>
                            </button>
                            <button type="button" class="button king-addons-skip-feedback">
                                <?php esc_html_e('Skip', 'king-addons'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="king-addons-rating-actions">
                        <a href="#" class="king-addons-maybe-later">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Maybe Later', 'king-addons'); ?>
                        </a>
                        <a href="#" class="king-addons-already-rated">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('I Already Did', 'king-addons'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <button type="button" class="notice-dismiss king-addons-notice-dismiss-btn">
                <span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'king-addons'); ?></span>
            </button>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for dismissing the notice
     */
    public function ajax_dismiss(): void {
        check_ajax_referer('king-addons-rating-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        update_option(self::OPTION_DISMISS, true);
        wp_send_json_success();
    }
    
    /**
     * AJAX handler for "Maybe Later"
     */
    public function ajax_later(): void {
        check_ajax_referer('king-addons-rating-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        update_option(self::OPTION_LATER_TIME, time());
        wp_send_json_success();
    }
    
    /**
     * AJAX handler for "Already Rated"
     */
    public function ajax_rated(): void {
        check_ajax_referer('king-addons-rating-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        update_option(self::OPTION_RATED, true);
        wp_send_json_success();
    }
    
    /**
     * AJAX handler for submitting feedback
     */
    public function ajax_feedback(): void {
        check_ajax_referer('king-addons-rating-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $rating = isset($_POST['rating']) ? absint($_POST['rating']) : 0;
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field(wp_unslash($_POST['feedback'])) : '';
        
        // Send email with feedback
        $site_url = get_site_url();
        $admin_email = get_option('admin_email');
        $wp_version = get_bloginfo('version');
        $plugin_version = defined('KING_ADDONS_VERSION') ? KING_ADDONS_VERSION : 'Unknown';
        
        $subject = sprintf('[King Addons Feedback] Rating: %d star%s', $rating, $rating === 1 ? '' : 's');
        
        $message = sprintf(
            "New feedback received from King Addons user:\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
            "Rating: %d star%s\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
            "Site Details:\n" .
            "• Site URL: %s\n" .
            "• Admin Email: %s\n" .
            "• WordPress Version: %s\n" .
            "• Plugin Version: %s\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
            "Feedback:\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
            "%s\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n",
            $rating,
            $rating === 1 ? '' : 's',
            $site_url,
            $admin_email,
            $wp_version,
            $plugin_version,
            $feedback ?: '(No feedback provided)'
        );
        
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $admin_email
        ];
        
        wp_mail(self::FEEDBACK_EMAIL, $subject, $message, $headers);
        
        update_option(self::OPTION_RATED, true);
        wp_send_json_success();
    }
}
