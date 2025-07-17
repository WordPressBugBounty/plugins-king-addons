<?php
/**
 * Handles automatic alt text generation for images using AI.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Alt_Text_Generator
 */
class Alt_Text_Generator {

    // Constants for batch processing
    private const BATCH_OPTION_PENDING = 'king_addons_alt_text_pending_queue';
    private const BATCH_OPTION_PROCESSING = 'king_addons_alt_text_processing';
    private const CRON_HOOK = 'king_addons_process_alt_text_queue';
    private const BATCH_SIZE = 1; // Process 1 image at a time to respect rate limits
    private const RETRY_LIMIT = 3; // Maximum retry attempts
    private const RATE_LIMIT_DELAY = 5; // Delay between batches in seconds

    /**
     * Constructor.
     * Hooks into WordPress actions.
     */
    public function __construct() {
        // Check if AI features are enabled
        $options = get_option('king_addons_ai_options', []);
        
        // Check if any alt text feature is enabled (with proper defaults)
        $button_enabled = isset($options['enable_ai_alt_text_button']) ? (bool) $options['enable_ai_alt_text_button'] : true; // Default to true
        $auto_enabled = isset($options['enable_ai_alt_text_auto_generation']) ? (bool) $options['enable_ai_alt_text_auto_generation'] : false; // Default to false
        
        if (!$button_enabled && !$auto_enabled) {
            return; // Don't initialize if both features are disabled
        }

        $has_api_key = !empty($options['openai_api_key']);

        // Hook for new attachments (only if auto generation is enabled and API key exists).
        if ($auto_enabled && $has_api_key) {
            if(king_addons_freemius()->can_use_premium_code()){
            add_action('add_attachment', array($this, 'add_to_processing_queue'));
            add_action(self::CRON_HOOK, array($this, 'process_alt_text_queue'));
            
            // Add custom cron interval
            add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
            
            // Schedule recurring cron job if not already scheduled
            if (!wp_next_scheduled(self::CRON_HOOK)) {
                wp_schedule_event(time(), 'king_addons_alt_text_interval', self::CRON_HOOK);
            }
        }
        }

        // Hooks for Media Library integration (always enable if button is enabled, regardless of API key).
        if ($button_enabled) {
            add_filter('manage_media_columns', array($this, 'add_alt_text_column'));
            add_action('manage_media_custom_column', array($this, 'display_alt_text_column'), 10, 2);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));

            // AJAX handler for manual generation (only if API key exists).
            if ($has_api_key) {
                add_action('wp_ajax_king_addons_generate_single_alt', array($this, 'handle_ajax_generate_single_alt'));
            }
            
            // AJAX handler for queue status (debugging)
            add_action('wp_ajax_king_addons_alt_text_queue_status', array($this, 'handle_ajax_queue_status'));
        }

        // Hook for cleanup on plugin deactivation
        register_deactivation_hook(KING_ADDONS_PATH . 'king-addons.php', array($this, 'cleanup_cron_jobs'));
        
        // Hook to update cron schedule when settings change
        add_action('update_option_king_addons_ai_options', array($this, 'update_cron_schedule'), 10, 3);
    }

    /**
     * Enqueues scripts needed for the media library screen.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_media_scripts(string $hook): void {
        // Load on the upload.php screen (Media Library List view) and post editing screens (for media modal)
        if (!in_array($hook, ['upload.php', 'post.php', 'post-new.php', 'page.php', 'page-new.php'])) {
            return;
        }

        wp_enqueue_style(
            'king-addons-media-alt-text-styles',
            KING_ADDONS_URL . 'includes/extensions/alt-text-generator/alt-text-styles.css',
            array(),
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-media-alt-text',
            KING_ADDONS_URL . 'includes/extensions/alt-text-generator/alt-text-media.js',
            array('jquery'),
            KING_ADDONS_VERSION,
            true
        );

        // Check if API key exists
        $options = get_option('king_addons_ai_options', []);
        $has_api_key = !empty($options['openai_api_key']);

        // Pass data to JavaScript.
        wp_localize_script('king-addons-media-alt-text', 'kingAddonsMediaAltText', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('king_addons_generate_alt_nonce'),
            'generating_text' => esc_html__('Generate with AI', 'king-addons'),
            'error_text' => esc_html__('Error', 'king-addons'),
            'has_api_key' => $has_api_key,
            'settings_url' => admin_url('admin.php?page=king-addons-ai-settings'),
        ));
    }

    /**
     * Adds a custom column to the Media Library list view.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_alt_text_column(array $columns): array {
        // Add column before 'Date'.
        $new_columns = array();
        foreach ($columns as $key => $title) {
            if ('date' === $key) {
                $new_columns['king_addons_alt_text'] = esc_html__('AI Alt Text', 'king-addons');
            }
            $new_columns[$key] = $title;
        }
        // If 'date' column wasn't found, add it at the end.
        if (!isset($new_columns['king_addons_alt_text'])) {
            $new_columns['king_addons_alt_text'] = esc_html__('AI Alt Text', 'king-addons');
        }
        return $new_columns;
    }

    /**
     * Displays content for the custom alt text column.
     *
     * @param string $column_name The name of the column being displayed.
     * @param int    $attachment_id The ID of the current attachment.
     */
    public function display_alt_text_column(string $column_name, int $attachment_id): void {
        if ('king_addons_alt_text' !== $column_name) {
            return;
        }

        // Only show for images.
        if (!wp_attachment_is_image($attachment_id)) {
            echo '—'; // Not an image
            return;
        }

        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        $options = get_option('king_addons_ai_options', []);
        $has_api_key = !empty($options['openai_api_key']);

        echo '<div class="king-addons-alt-text-status" data-attachment-id="' . esc_attr($attachment_id) . '">';
        if (!empty($alt_text)) {
            echo '<span>' . esc_html($alt_text) . '</span>';
        } else {
            if ($has_api_key) {
                // Button to generate alt text when API key exists.
                printf(
                    '<button type="button" class="button button-secondary button-small king-addons-generate-alt-button">%s</button>',
                    esc_html__('Generate', 'king-addons')
                );
                echo '<span class="king-addons-alt-text-result king-addons-status-inline"></span>'; // For displaying results/errors
                echo '<span class="spinner king-addons-spinner-inline"></span>';
            } else {
                // Link to settings when API key is missing.
                printf(
                    '<a href="%s" class="button button-secondary button-small" target="_blank">%s</a>',
                    esc_url(admin_url('admin.php?page=king-addons-ai-settings')),
                    esc_html__('Set API Key', 'king-addons')
                );
                echo '<span class="king-addons-alt-text-result king-addons-status-inline"></span>';
            }
        }
        echo '</div>';
    }

    /**
     * Handles the AJAX request to generate alt text for a single image.
     */
    public function handle_ajax_generate_single_alt(): void {
        check_ajax_referer('king_addons_generate_alt_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => esc_html__('Permission denied.', 'king-addons')), 403);
        }

        // Check if API key exists
        $options = get_option('king_addons_ai_options', []);
        if (empty($options['openai_api_key'])) {
            wp_send_json_error(array(
                'message' => esc_html__('OpenAI API key is not configured. Please set it in the AI settings.', 'king-addons'),
                'needs_setup' => true
            ), 400);
        }

        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(array('message' => esc_html__('Invalid attachment ID.', 'king-addons')), 400);
        }

        // Call the existing generation logic, but slightly refactored to return the result.
        $result = $this->generate_alt_text_for_image($attachment_id, true); // Pass true to indicate AJAX context.

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 500);
        } elseif ($result === false) {
            // Handle cases where generation failed silently within the function (e.g., API key missing)
            wp_send_json_error(array('message' => esc_html__('Alt text generation failed. Check logs or API key.', 'king-addons')), 500);
        } elseif (is_string($result)) {
            // Success! Return the generated alt text.
            wp_send_json_success(array('alt_text' => $result));
        } else {
            // Unexpected result.
            wp_send_json_error(array('message' => esc_html__('An unexpected error occurred.', 'king-addons')), 500);
        }
    }

    /**
     * Handles the AJAX request to get queue status.
     */
    public function handle_ajax_queue_status(): void {
        check_ajax_referer('king_addons_generate_alt_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Permission denied.', 'king-addons')), 403);
        }

        $status = $this->get_queue_status();
        wp_send_json_success($status);
    }

    /**
     * Adds custom cron intervals.
     * 
     * Note: Recommended interval is 60+ seconds to avoid OpenAI API rate limits.
     * Lower intervals may cause API errors during high usage periods.
     *
     * @param array $schedules Existing cron schedules.
     * @return array Modified schedules.
     */
    public function add_custom_cron_intervals(array $schedules): array {
        // Get the interval from settings, default to 60 seconds
        $options = get_option('king_addons_ai_options', []);
        $interval = isset($options['ai_alt_text_generation_interval']) ? (int) $options['ai_alt_text_generation_interval'] : 60;
        
        // Ensure interval is within acceptable range
        $interval = max(10, min(3600, $interval));
        
        $schedules['king_addons_alt_text_interval'] = array(
            'interval' => $interval,
            'display'  => sprintf(esc_html__('Every %d Seconds (King Addons Alt Text)', 'king-addons'), $interval)
        );
        return $schedules;
    }

    /**
     * Adds a new attachment to the processing queue instead of immediate processing.
     *
     * @param int $attachment_id The ID of the attachment just added.
     */
    public function add_to_processing_queue(int $attachment_id): void {
        // Check if the attachment is an image.
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }

        // Check if alt text already exists
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return; // Skip if alt text already exists
        }

        // Get current queue
        $queue = get_option(self::BATCH_OPTION_PENDING, []);
        
        // Add to queue if not already present
        if (!in_array($attachment_id, $queue)) {
            $queue[] = $attachment_id;
            update_option(self::BATCH_OPTION_PENDING, $queue, false);
        }
    }

    /**
     * Processes the alt text generation queue in batches.
     */
    public function process_alt_text_queue(): void {
        // Check if already processing to avoid conflicts
        $processing = get_option(self::BATCH_OPTION_PROCESSING, false);
        if ($processing && (time() - $processing) < 300) { // 5 minute timeout
            return; // Another process is already running
        }

        // Set processing flag
        update_option(self::BATCH_OPTION_PROCESSING, time(), false);

        try {
            // Get pending queue
            $queue = get_option(self::BATCH_OPTION_PENDING, []);
            
            if (empty($queue)) {
                delete_option(self::BATCH_OPTION_PROCESSING);
                return; // Nothing to process
            }

            // Process batch
            $batch = array_splice($queue, 0, self::BATCH_SIZE);
            
            foreach ($batch as $attachment_id) {
                $this->process_single_attachment_with_retry($attachment_id);
                
                // Add delay between requests to respect rate limits
                if (count($batch) > 1) {
                    sleep(2); // 2 second delay between individual requests
                }
            }

            // Update queue
            update_option(self::BATCH_OPTION_PENDING, $queue, false);

            // Schedule next batch if queue is not empty
            if (!empty($queue)) {
                wp_schedule_single_event(time() + self::RATE_LIMIT_DELAY, self::CRON_HOOK);
            }

        } finally {
            // Always clear processing flag
            delete_option(self::BATCH_OPTION_PROCESSING);
        }
    }

    /**
     * Processes a single attachment with retry logic.
     *
     * @param int $attachment_id The attachment ID to process.
     */
    private function process_single_attachment_with_retry(int $attachment_id): void {
        $retry_count = get_post_meta($attachment_id, '_king_addons_alt_retry_count', true);
        $retry_count = $retry_count ? (int) $retry_count : 0;

        if ($retry_count >= self::RETRY_LIMIT) {
            // Max retries reached, skip this attachment
            delete_post_meta($attachment_id, '_king_addons_alt_retry_count');
            return;
        }

        $result = $this->generate_alt_text_for_image($attachment_id, false);
        
        if ($result !== true) {
            // Generation failed, increment retry count
            update_post_meta($attachment_id, '_king_addons_alt_retry_count', $retry_count + 1);
            
            // Add back to queue for retry (at the end)
            $queue = get_option(self::BATCH_OPTION_PENDING, []);
            if (!in_array($attachment_id, $queue)) {
                $queue[] = $attachment_id;
                update_option(self::BATCH_OPTION_PENDING, $queue, false);
            }
        } else {
            // Success, clean up retry count
            delete_post_meta($attachment_id, '_king_addons_alt_retry_count');
        }
    }

    /**
     * Generates alt text for a given image attachment using an AI service.
     *
     * @param int  $attachment_id The ID of the attachment.
     * @param bool $is_ajax Optional. Indicates if called via AJAX context. If true, returns result/error instead of void.
     * @return bool|string|WP_Error Returns true on success (non-AJAX), generated alt text (string) on success (AJAX), specific error message (string) on failure (non-AJAX), or WP_Error on failure (AJAX).
     */
    public function generate_alt_text_for_image(int $attachment_id, bool $is_ajax = false) {
        // Verify it's an image.
        if (!wp_attachment_is_image($attachment_id)) {
            $error_msg = esc_html__('Not an image.', 'king-addons');
            return $is_ajax ? new \WP_Error('invalid_attachment', $error_msg) : $error_msg;
        }

        // Check if alt text already exists (only if not forced, future enhancement).
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            // If called via AJAX, maybe return existing text or an indication it wasn't generated.
            // For now, we assume the button won't be shown if alt exists, so this path is mainly for the cron job.
            return $is_ajax ? $existing_alt : true;
        }

        // Retrieve OpenAI API key and settings from King Addons AI options.
        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? '';
        // Use the text model for vision analysis, not the image generation model
        $model = $options['openai_model'] ?? 'gpt-4o';
        // Get image detail level from settings (default to 'low')
        $image_detail_level = $options['ai_alt_text_image_detail_level'] ?? 'low';

        if (empty($api_key)) {
            $error_msg = esc_html__('OpenAI API key is missing.', 'king-addons');
            return $is_ajax ? new \WP_Error('missing_api_key', $error_msg) : $error_msg;
        }

        // Get image path instead of URL.
        $image_path = get_attached_file($attachment_id);
        if (!$image_path || !file_exists($image_path)) {
            $error_msg = esc_html__('Could not retrieve image file path.', 'king-addons');
            return $is_ajax ? new \WP_Error('no_image_path', $error_msg) : $error_msg;
        }

        // Read image data.
        $image_data = file_get_contents($image_path);
        if (false === $image_data) {
            $error_msg = esc_html__('Could not read image file.', 'king-addons');
            return $is_ajax ? new \WP_Error('read_image_failed', $error_msg) : $error_msg;
        }

        // Get MIME type.
        $file_info = wp_check_filetype(basename($image_path));
        if (!$file_info || empty($file_info['type'])) {
            $error_msg = esc_html__('Could not determine image type.', 'king-addons');
            return $is_ajax ? new \WP_Error('mime_type_failed', $error_msg) : $error_msg;
        }
        $mime_type = $file_info['type'];

        // Encode image data in Base64.
        $base64_image = base64_encode($image_data);

        // Create the data URI.
        $image_data_uri = "data:{$mime_type};base64,{$base64_image}";

        // --- OpenAI API Call --- //
        $api_endpoint = 'https://api.openai.com/v1/chat/completions';

        $prompt_text = 'Generate a concise, descriptive alt text for this image, suitable for SEO and accessibility. Focus on the main subject and action. Maximum 125 characters.';

        $payload = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => $prompt_text
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => $image_data_uri,
                                'detail' => $image_detail_level
                            )
                        )
                    )
                )
            ),
            'max_tokens' => 50 // Limit the response length
        );

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode($payload),
            'timeout' => 60, // Increased timeout
            'method'  => 'POST',
            'data_format' => 'body',
        );

        $response = wp_remote_post($api_endpoint, $args);

        // Handle the response.
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            /* translators: %s: Error message returned from the network request. */
            return $is_ajax ? $response : sprintf(esc_html__('Network error: %s', 'king-addons'), $error_message);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_body  = json_decode($response_body, true);

        if ($response_code !== 200 || !isset($decoded_body['choices'][0]['message']['content'])) {
            $api_error_message = isset($decoded_body['error']['message']) ? $decoded_body['error']['message'] : esc_html__('API request failed or returned unexpected data.', 'king-addons');
            /* translators: 1: HTTP response code, 2: API error message. */
            return $is_ajax ? new \WP_Error('api_error', $api_error_message, array('status' => $response_code)) : sprintf(esc_html__('API error (%1$d): %2$s', 'king-addons'), $response_code, $api_error_message);
        }

        $generated_alt_text = $decoded_body['choices'][0]['message']['content'];
        // --- End API Call --- //

        // If the response starts with "I'm sorry" (case-insensitive, allow whitespace before), treat as error and do not save
        if (preg_match('/^\s*I\'m sorry/i', $generated_alt_text)) {
            $generated_alt_text = '';
        }

        // Sanitize and potentially trim the generated alt text.
        $sanitized_alt_text = sanitize_text_field(trim($generated_alt_text));
        $sanitized_alt_text = trim($sanitized_alt_text, '"'); // Remove surrounding quotes
        $sanitized_alt_text = mb_substr($sanitized_alt_text, 0, 125); // Enforce length limit

        // Update the image alt text meta data.
        if (!empty($sanitized_alt_text)) {
            if (update_post_meta($attachment_id, '_wp_attachment_image_alt', $sanitized_alt_text)) {
                // Log success for debugging
                error_log("King Addons Alt Text: Successfully generated alt text for attachment {$attachment_id}: {$sanitized_alt_text}");
                return $is_ajax ? $sanitized_alt_text : true;
            } else {
                $error_msg = esc_html__('Failed to save the generated alt text.', 'king-addons');
                error_log("King Addons Alt Text: Failed to save alt text for attachment {$attachment_id}");
                return $is_ajax ? new \WP_Error('update_failed', $error_msg) : $error_msg;
            }
        } else {
            $error_msg = esc_html__('AI returned empty or invalid text.', 'king-addons');
            error_log("King Addons Alt Text: AI returned empty text for attachment {$attachment_id}");
            return $is_ajax ? new \WP_Error('empty_alt_text', $error_msg) : $error_msg;
        }

        // Should not be reached in normal flow due to checks above
        $error_msg = esc_html__('An unknown error occurred during generation.', 'king-addons');
        return $is_ajax ? new \WP_Error('unknown_error', $error_msg) : $error_msg;
    }

    /**
     * Cleans up cron jobs and options on plugin deactivation.
     */
    public function cleanup_cron_jobs(): void {
        // Clear scheduled cron jobs
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }

        // Clear options
        delete_option(self::BATCH_OPTION_PENDING);
        delete_option(self::BATCH_OPTION_PROCESSING);

        // Clean up retry count meta for all attachments
        global $wpdb;
        $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => '_king_addons_alt_retry_count'),
            array('%s')
        );
    }

    /**
     * Updates the cron schedule if the relevant settings change.
     *
     * @param mixed $old_value The old value.
     * @param mixed $new_value The new value.
     * @param string $option The option name.
     */
    public function update_cron_schedule($old_value, $new_value, string $option = 'king_addons_ai_options'): void {

        // Check if interval setting changed
        $old_interval = isset($old_value['ai_alt_text_generation_interval']) ? (int) $old_value['ai_alt_text_generation_interval'] : 60;
        $new_interval = isset($new_value['ai_alt_text_generation_interval']) ? (int) $new_value['ai_alt_text_generation_interval'] : 60;
        
        // Check if auto generation setting changed
        $old_auto = isset($old_value['enable_ai_alt_text_auto_generation']) ? (bool) $old_value['enable_ai_alt_text_auto_generation'] : false;
        $new_auto = isset($new_value['enable_ai_alt_text_auto_generation']) ? (bool) $new_value['enable_ai_alt_text_auto_generation'] : false;
        
        if ($old_interval !== $new_interval || $old_auto !== $new_auto) {
            // Clear existing cron job
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
            if ($timestamp) {
                wp_unschedule_event($timestamp, self::CRON_HOOK);
            }
            
            // Reschedule if auto generation is enabled
            if ($new_auto) {
                wp_schedule_event(time(), 'king_addons_alt_text_interval', self::CRON_HOOK);
            }
        }
    }

    /**
     * Gets queue status for debugging purposes.
     *
     * @return array Queue status information.
     */
    public function get_queue_status(): array {
        $queue = get_option(self::BATCH_OPTION_PENDING, []);
        $processing = get_option(self::BATCH_OPTION_PROCESSING, false);
        $next_scheduled = wp_next_scheduled(self::CRON_HOOK);

        return array(
            'pending_count' => count($queue),
            'pending_ids' => $queue,
            'is_processing' => $processing ? true : false,
            'processing_since' => $processing ? date('Y-m-d H:i:s', $processing) : null,
            'next_run' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : null,
        );
    }
}
