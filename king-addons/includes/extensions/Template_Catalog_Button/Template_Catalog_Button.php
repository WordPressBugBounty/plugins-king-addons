<?php

/**
 * Template Catalog Button Extension
 * 
 * Adds a button to Elementor editor panel that opens King Addons template catalog in popup
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Template_Catalog_Button
{
    /**
     * Instance
     *
     * @var Template_Catalog_Button|null The single instance of the class.
     */
    private static ?Template_Catalog_Button $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return Template_Catalog_Button An instance of the class.
     */
    public static function instance(): Template_Catalog_Button
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        // Only load if templates catalog is enabled
        if (!KING_ADDONS_EXT_TEMPLATES_CATALOG) {
            return;
        }

        // Check if template catalog button is disabled by premium user
        if ($this->is_template_catalog_disabled()) {
            return;
        }

        // Hook into Elementor editor
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'enqueue_editor_scripts'], 10);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_editor_styles'], 10);
        
        // AJAX endpoints for template catalog in editor
        add_action('wp_ajax_king_addons_get_template_catalog', [$this, 'get_template_catalog']);
        add_action('wp_ajax_king_addons_import_template_to_page', [$this, 'import_template_to_page']);
        add_action('wp_ajax_king_addons_import_template_content', [$this, 'import_template_content']);
        
        // New endpoint for merging with existing page
        add_action('wp_ajax_king_addons_merge_with_existing_page', [$this, 'merge_with_existing_page']);
        
        // Sections catalog endpoint
        add_action('wp_ajax_king_addons_get_sections_catalog', [$this, 'get_sections_catalog']);
        
        // Section import endpoints
        add_action('wp_ajax_king_addons_import_section_to_page', [$this, 'import_section_to_page']);
    }

    /**
     * Enqueue scripts for Elementor editor
     */
    public function enqueue_editor_scripts(): void
    {
        wp_enqueue_script(
            'king-addons-template-catalog-button',
            KING_ADDONS_URL . 'includes/extensions/Template_Catalog_Button/assets/template-catalog-button.js',
            ['jquery', 'elementor-editor'],
            KING_ADDONS_VERSION,
            true
        );

        // Get current post ID if available
        $current_post_id = 0;
        if (isset($_GET['post'])) {
            $current_post_id = intval($_GET['post']);
        } elseif (isset($_GET['post_id'])) {
            $current_post_id = intval($_GET['post_id']);
        }

        // Localize script with template catalog data
        wp_localize_script(
            'king-addons-template-catalog-button',
            'kingAddonsTemplateCatalog',
            [
                'templateCatalogUrl' => admin_url('admin.php?page=king-addons-templates'),
                'templatesEnabled' => KING_ADDONS_EXT_TEMPLATES_CATALOG,
                'buttonEnabled' => !$this->is_template_catalog_disabled(), // If script loaded, button is enabled
                'buttonText' => $this->get_button_text(),
                'nonce' => wp_create_nonce('king_addons_template_catalog'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'isPremium' => function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code(),
                'currentPostId' => $current_post_id,
                'adminUrl' => admin_url(),
                'pluginUrl' => KING_ADDONS_URL,
            ]
        );
    }

    /**
     * Enqueue styles for Elementor editor
     */
    public function enqueue_editor_styles(): void
    {
        wp_enqueue_style(
            'king-addons-template-catalog-popup',
            KING_ADDONS_URL . 'includes/extensions/Template_Catalog_Button/assets/template-catalog-popup.css',
            [],
            KING_ADDONS_VERSION
        );
    }

    /**
     * Check if template catalog button is disabled by premium user
     */
    private function is_template_catalog_disabled(): bool
    {
        // Only premium users can disable the template catalog button
        if (!function_exists('king_addons_freemius') || !king_addons_freemius()->can_use_premium_code()) {
            return false;
        }

        // Check if setting exists and is enabled (1 = disabled)
        $disabled = get_option('king_addons_disable_template_catalog_button', '0');
        return $disabled === '1';
    }

    /**
     * Get button text based on user's subscription level
     */
    private function get_button_text(): string
    {
        if (function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code()) {
            return esc_html__('Templates Pro', 'king-addons');
        }
        
        return esc_html__('Free Templates', 'king-addons');
    }

    /**
     * AJAX handler for getting template catalog data
     */
    public function get_template_catalog(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_template_catalog')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $templates = \King_Addons\TemplatesMap::getTemplatesMapArray();
        $collections = \King_Addons\CollectionsMap::getCollectionsMapArray();
        
        $is_premium_active = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();
        
        // Get filters from request
        $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $selected_category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $selected_collection = isset($_POST['collection']) ? sanitize_text_field($_POST['collection']) : '';
        $current_page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

        // Get categories and tags
        $categories = [];
        $tags = [];
        $category_counts = [];

        foreach ($templates['templates'] as $template) {
            if (!in_array($template['category'], $categories)) {
                $categories[] = $template['category'];
            }

            foreach ($template['tags'] as $tag) {
                if (!in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }

            $category = $template['category'];
            $category_counts[$category] = isset($category_counts[$category]) ? $category_counts[$category] + 1 : 1;
        }

        sort($categories);

        // Filter templates
        $filtered_templates = $templates['templates'];

        // Apply filters
        if (!empty($search_query)) {
            $matched_by_title = [];
            $matched_by_tags = [];

            foreach ($filtered_templates as $template_key => $template) {
                $found_in_title = stripos($template['title'], $search_query) !== false;
                $found_in_tags = false;

                foreach ($template['tags'] as $tag) {
                    if (stripos($tag, $search_query) !== false) {
                        $found_in_tags = true;
                        break;
                    }
                }

                if ($found_in_title) {
                    $template['template_key'] = $template_key;
                    $matched_by_title[] = $template;
                } elseif ($found_in_tags) {
                    $template['template_key'] = $template_key;
                    $matched_by_tags[] = $template;
                }
            }

            $filtered_templates = array_merge($matched_by_title, $matched_by_tags);
        } else {
            // Add template keys for non-search results
            $temp_templates = [];
            foreach ($filtered_templates as $key => $template) {
                $template['template_key'] = $key;
                $temp_templates[] = $template;
            }
            $filtered_templates = $temp_templates;
        }

        if (!empty($selected_category)) {
            $filtered_templates = array_filter($filtered_templates, function($template) use ($selected_category) {
                return $template['category'] === $selected_category;
            });
        }

        if (!empty($selected_collection)) {
            $filtered_templates = array_filter($filtered_templates, function($template) use ($selected_collection) {
                return $template['collection'] == $selected_collection;
            });
        }

        // Pagination
        $items_per_page = 20;
        $total_templates = count($filtered_templates);
        $total_pages = ceil($total_templates / $items_per_page);
        $offset = ($current_page - 1) * $items_per_page;
        $paged_templates = array_slice($filtered_templates, $offset, $items_per_page);

        wp_send_json_success([
            'templates' => $paged_templates,
            'categories' => $categories,
            'collections' => $collections,
            'category_counts' => $category_counts,
            'pagination' => [
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'total_templates' => $total_templates,
                'items_per_page' => $items_per_page
            ],
            'is_premium_active' => $is_premium_active
        ]);
    }

    /**
     * AJAX handler for importing template to current page
     */
    public function import_template_to_page(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_template_catalog')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $template_key = sanitize_text_field($_POST['template_key']);
        $template_plan = sanitize_text_field($_POST['template_plan']);
        $is_premium_active = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();

        // Determine API URL and install ID
        if ($is_premium_active && $template_plan === 'premium') {
            $api_url = 'https://api.kingaddons.com/get-template.php';
            
            // Use the same method as original templates catalog
            if (function_exists('king_addons_freemius')) {
                $freemius_site = king_addons_freemius()->get_site();
                $install_id = $freemius_site ? $freemius_site->id : 0;
            } else {
                $install_id = 0;
            }
            
            error_log('King Addons Premium Template: Using install_id: ' . $install_id . ' for premium template: ' . $template_key);
        } elseif ($template_plan === 'free') {
            $api_url = 'https://api.kingaddons.com/get-template-free.php';
            $install_id = 0;
            error_log('King Addons Free Template: Fetching free template: ' . $template_key);
        } else {
            error_log('King Addons Template Error: Premium template requires premium license. Template: ' . $template_key . ', Plan: ' . $template_plan . ', Premium Active: ' . ($is_premium_active ? 'Yes' : 'No'));
            wp_send_json_error('Premium template requires premium license');
            return;
        }

        // Get template data from API
        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'key' => $template_key,
                'install' => $install_id,
            ]),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch template: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        error_log('King Addons API Response: ' . substr($body, 0, 500) . (strlen($body) > 500 ? '...' : ''));
        
        if (!$data) {
            error_log('King Addons Template Error: Failed to decode JSON response');
            wp_send_json_error('Invalid JSON response from template API');
            return;
        }

        if (!isset($data['success']) || !$data['success']) {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown API error';
            error_log('King Addons Template Error: API returned error: ' . $error_message);
            wp_send_json_error('Template API error: ' . $error_message);
            return;
        }

        // Return template data for frontend processing
        wp_send_json_success([
            'template_data' => $data['landing'],
            'message' => 'Template data retrieved successfully'
        ]);
    }

    /**
     * AJAX handler for importing template content directly into current page
     */
    public function import_template_content(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_template_catalog')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Security fix: Sanitize template data input
        $raw_template_data = sanitize_textarea_field(stripslashes($_POST['template_data'] ?? ''));
        $template_data = json_decode($raw_template_data, true);
        $page_id = intval($_POST['page_id'] ?? 0);

        error_log('King Addons Template Import: Starting import for page ID: ' . $page_id);
        error_log('King Addons Template Import: Template data keys: ' . json_encode(array_keys($template_data ?: [])));

        if (!$template_data || !$page_id) {
            error_log('King Addons Template Import: Invalid data - template_data: ' . (!empty($template_data) ? 'valid' : 'invalid') . ', page_id: ' . $page_id);
            wp_send_json_error('Invalid template data or page ID');
            return;
        }

        // Get current page Elementor data
        $current_data = get_post_meta($page_id, '_elementor_data', true);
        $current_elements = json_decode($current_data, true);
        
        if (!is_array($current_elements)) {
            $current_elements = [];
        }

        // Parse template content
        $template_content = isset($template_data['content']) ? $template_data['content'] : null;
        if (!$template_content) {
            wp_send_json_error('No template content found');
            return;
        }

        // If template_content is a string, decode it
        if (is_string($template_content)) {
            $template_content = json_decode($template_content, true);
        }

        if (!is_array($template_content)) {
            wp_send_json_error('Invalid template content format');
            return;
        }

        // Process images in template content
        $image_map = [];
        $images_processed = 0;
        $images_failed = 0;
        
        if (isset($template_data['images']) && is_array($template_data['images'])) {
            error_log('King Addons Template Import: Processing ' . count($template_data['images']) . ' images');
            
            foreach ($template_data['images'] as $image) {
                // Download and import image
                $new_image_id = $this->download_and_import_image($image['url']);
                if ($new_image_id) {
                    $image_map[$image['id']] = $new_image_id;
                    $images_processed++;
                    error_log('King Addons Template Import: Successfully imported image ' . $image['url'] . ' as ID ' . $new_image_id);
                } else {
                    $images_failed++;
                    error_log('King Addons Template Import: Failed to import image ' . $image['url']);
                }
            }
            
            error_log('King Addons Template Import: Images summary - processed: ' . $images_processed . ', failed: ' . $images_failed);
        } else {
            error_log('King Addons Template Import: No images to process');
        }

        // Replace image IDs in template content
        $template_content = $this->replace_image_ids($template_content, $image_map);

        // Merge template content with current page content
        $current_count = count($current_elements);
        $new_count = count($template_content);
        $merged_elements = array_merge($current_elements, $template_content);
        $total_count = count($merged_elements);

        error_log('King Addons Template Import: Merging content - current: ' . $current_count . ', new: ' . $new_count . ', total: ' . $total_count);

        // Update page meta
        $update_result = update_post_meta($page_id, '_elementor_data', wp_slash(json_encode($merged_elements)));
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        
        error_log('King Addons Template Import: Page meta updated - result: ' . ($update_result ? 'success' : 'failed'));
        
        // Clear Elementor cache
        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
            error_log('King Addons Template Import: Elementor cache cleared');
        } else {
            error_log('King Addons Template Import: Elementor Plugin class not found, cache not cleared');
        }

        error_log('King Addons Template Import: Import completed successfully');

        wp_send_json_success([
            'message' => 'Template imported successfully',
            'imported_elements' => $new_count,
            'images_processed' => $images_processed,
            'images_failed' => $images_failed,
            'page_id' => $page_id,
            'current_elements_before' => $current_count,
            'total_elements_after' => $total_count
        ]);
    }

    /**
     * Download and import image to WordPress media library
     */
    private function download_and_import_image($image_url): ?int
    {
        try {
            // Security fix: Validate URL to prevent SSRF attacks
            if (!$this->is_safe_image_url($image_url)) {
                error_log('King Addons Security: Blocked unsafe image URL: ' . $image_url);
                return null;
            }
            
            $response = wp_remote_get($image_url, [
                'timeout' => 30,
                'user-agent' => 'King Addons Template Import/1.0',
                'redirection' => 2 // Limit redirects
            ]);

            if (is_wp_error($response)) {
                return null;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                return null;
            }

            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                return null;
            }

            // Security fix: Sanitize filename components
            $image_name = sanitize_file_name(pathinfo(basename($image_url), PATHINFO_FILENAME));
            $image_extension = sanitize_file_name(pathinfo(basename($image_url), PATHINFO_EXTENSION));
            
            // Validate file extension
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($image_extension), $allowed_extensions, true)) {
                error_log('King Addons Security: Invalid image extension: ' . $image_extension);
                return null;
            }
            
            $unique_image_name = $image_name . '-' . time() . '.' . $image_extension;

            $upload_dir = wp_upload_dir();
            if (!file_exists($upload_dir['path'])) {
                wp_mkdir_p($upload_dir['path']);
            }
            $image_file = $upload_dir['path'] . '/' . $unique_image_name;

            if (file_put_contents($image_file, $image_data) === false) {
                return null;
            }

            $wp_filetype = wp_check_filetype($unique_image_name);
            $attachment = [
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($unique_image_name),
                'post_content' => '',
                'post_status' => 'inherit',
            ];

            $attach_id = wp_insert_attachment($attachment, $image_file);

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $image_file);
            wp_update_attachment_metadata($attach_id, $attach_data);

            return $attach_id;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Replace image IDs in template content
     */
    private function replace_image_ids($content, $image_map): array
    {
        if (!is_array($content)) {
            return $content;
        }

        foreach ($content as &$element) {
            if (isset($element['settings'])) {
                $element['settings'] = $this->replace_image_ids_in_settings($element['settings'], $image_map);
            }
            
            if (isset($element['elements']) && is_array($element['elements'])) {
                $element['elements'] = $this->replace_image_ids($element['elements'], $image_map);
            }
        }

        return $content;
    }

    /**
     * Generate new unique IDs for all elements to avoid conflicts on repeated imports
     */
    private function regenerate_element_ids($content): array
    {
        if (!is_array($content)) {
            return $content;
        }

        foreach ($content as &$element) {
            // Generate new unique ID for this element
            if (isset($element['id'])) {
                $element['id'] = $this->generate_unique_elementor_id();
            }

            // Process nested elements recursively
            if (isset($element['elements']) && is_array($element['elements'])) {
                $element['elements'] = $this->regenerate_element_ids($element['elements']);
            }
        }

        return $content;
    }

    /**
     * Generate a unique Elementor-style ID
     */
    private function generate_unique_elementor_id(): string
    {
        // Elementor uses 7-character alphanumeric IDs
        $chars = '0123456789abcdef';
        $id = '';
        for ($i = 0; $i < 7; $i++) {
            $id .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $id;
    }

    /**
     * Replace image IDs in element settings
     */
    private function replace_image_ids_in_settings($settings, $image_map): array
    {
        if (!is_array($settings)) {
            return $settings;
        }

        foreach ($settings as $key => &$value) {
            if (is_array($value)) {
                $value = $this->replace_image_ids_in_settings($value, $image_map);
            } elseif (isset($image_map[$value])) {
                // Replace image ID
                $value = $image_map[$value];
            }
        }

        return $settings;
    }

    /**
     * Merge processed template content with existing page
     */
    public function merge_with_existing_page(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_template_catalog')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $page_id = intval($_POST['page_id']);
        
        if (!$page_id) {
            wp_send_json_error('Invalid page ID');
            return;
        }

        // Get processed content from the original import system
        $content = get_transient('elementor_import_content');
        $page_title = get_transient('elementor_import_page_title');
        
        if (!$content) {
            wp_send_json_error('No processed content found. Import may have expired.');
            return;
        }

        error_log('King Addons Import: Merging processed content with existing page ' . $page_id);

        // Get current page Elementor data
        $current_data = get_post_meta($page_id, '_elementor_data', true);
        $current_elements = json_decode($current_data, true);
        
        if (!is_array($current_elements)) {
            $current_elements = [];
        }

        // The content is already processed by the original system (images replaced)
        $template_content = $content;

        if (!is_array($template_content)) {
            wp_send_json_error('Invalid processed content format');
            return;
        }

        // Generate new unique IDs for all elements to avoid conflicts
        $template_content = $this->regenerate_element_ids($template_content);

        // Merge template content with current page content
        $current_count = count($current_elements);
        $new_count = count($template_content);
        $merged_elements = array_merge($current_elements, $template_content);
        $total_count = count($merged_elements);

        error_log('King Addons Import: Merging content - current: ' . $current_count . ', new: ' . $new_count . ', total: ' . $total_count);

        // Update page meta with merged content
        $update_result = update_post_meta($page_id, '_elementor_data', wp_slash(json_encode($merged_elements)));
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        
        // Force update _elementor_version to trigger cache clear
        if (defined('ELEMENTOR_VERSION')) {
            update_post_meta($page_id, '_elementor_version', ELEMENTOR_VERSION);
        }
        
        // Update page modification time to force Elementor refresh
        wp_update_post(['ID' => $page_id, 'post_modified' => current_time('mysql'), 'post_modified_gmt' => current_time('mysql', 1)]);
        
        error_log('King Addons Import: Page meta updated - result: ' . ($update_result ? 'success' : 'failed'));
        
        // Clear all Elementor caches
        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
            error_log('King Addons Import: Elementor cache cleared');
        }

        // Clean up transients
        delete_transient('elementor_import_content');
        delete_transient('elementor_import_images');
        delete_transient('elementor_import_total_images');
        delete_transient('elementor_import_images_processed');
        delete_transient('elementor_import_image_retry_count');
        delete_transient('elementor_import_page_title');
        delete_transient('elementor_import_elementor_version');
        delete_transient('elementor_import_existing_page_id');
        delete_transient('elementor_import_create_new_page');

        error_log('King Addons Import: Merge completed successfully');

        wp_send_json_success([
            'message' => 'Template merged successfully',
            'imported_elements' => $new_count,
            'page_id' => $page_id,
            'current_elements_before' => $current_count,
            'total_elements_after' => $total_count
        ]);
    }

    /**
     * AJAX handler for getting sections catalog data for popup
     */
    public function get_sections_catalog(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_template_catalog')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!class_exists('King_Addons\\SectionsMap')) {
            require_once KING_ADDONS_PATH . 'includes/SectionsMap.php';
        }

        $sections_map = SectionsMap::getSectionsMapArray();
        $sections = $sections_map['sections'] ?? [];
        
        $is_premium_active = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();
        
        // Get filters from request
        $search_query = sanitize_text_field($_POST['search'] ?? '');
        $selected_category = sanitize_text_field($_POST['category'] ?? '');
        $selected_type = sanitize_text_field($_POST['section_type'] ?? '');
        $selected_plan = sanitize_text_field($_POST['plan'] ?? '');
        $current_page = max(1, intval($_POST['page'] ?? 1));

        // Get categories and section types for filters
        $categories = [];
        $section_types = [];

        foreach ($sections as $section_key => $section) {
            if (!in_array($section['category'], $categories)) {
                $categories[] = $section['category'];
            }
            if (!in_array($section['section_type'], $section_types)) {
                $section_types[] = $section['section_type'];
            }
        }

        sort($categories);
        sort($section_types);

        // Filter sections
        $filtered_sections = [];

        foreach ($sections as $section_key => $section) {
            // Add section key for frontend
            $section['section_key'] = $section_key;
            
            // Skip premium sections if user doesn't have premium license
            if ($section['plan'] === 'premium' && !$is_premium_active) {
                continue;
            }
            
            // Apply search filter
            if (!empty($search_query)) {
                $found_in_title = stripos($section['title'], $search_query) !== false;
                $found_in_tags = false;
                
                foreach ($section['tags'] ?? [] as $tag) {
                    if (stripos($tag, $search_query) !== false) {
                        $found_in_tags = true;
                        break;
                    }
                }
                
                if (!$found_in_title && !$found_in_tags) {
                    continue;
                }
            }
            
            // Apply category filter
            if (!empty($selected_category) && $section['category'] !== $selected_category) {
                continue;
            }
            
            // Apply section type filter
            if (!empty($selected_type) && $section['section_type'] !== $selected_type) {
                continue;
            }
            
            // Apply plan filter
            if (!empty($selected_plan) && $section['plan'] !== $selected_plan) {
                continue;
            }
            
            $filtered_sections[] = $section;
        }

        // Pagination
        $items_per_page = 20;
        $total_sections = count($filtered_sections);
        $total_pages = ceil($total_sections / $items_per_page);
        $offset = ($current_page - 1) * $items_per_page;
        $paged_sections = array_slice($filtered_sections, $offset, $items_per_page);

        wp_send_json_success([
            'sections' => $paged_sections,
            'categories' => $categories,
            'section_types' => $section_types,
            'pagination' => [
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'total_sections' => $total_sections,
                'items_per_page' => $items_per_page
            ],
            'is_premium_active' => $is_premium_active
        ]);
    }

    /**
     * AJAX handler for importing section to current page
     */
    public function import_section_to_page(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_template_catalog')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $section_key = sanitize_text_field($_POST['section_key']);
        $section_plan = sanitize_text_field($_POST['section_plan']);
        $is_premium_active = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();

        // Determine API URL and install ID (same logic as templates)
        if ($is_premium_active && $section_plan === 'premium') {
            $api_url = 'https://api.kingaddons.com/get-section.php';
            
            // Use the same method as original templates catalog
            if (function_exists('king_addons_freemius')) {
                $freemius_site = king_addons_freemius()->get_site();
                $install_id = $freemius_site ? $freemius_site->id : 0;
            } else {
                $install_id = 0;
            }
            
            error_log('King Addons Premium Section: Using install_id: ' . $install_id . ' for premium section: ' . $section_key);
        } elseif ($section_plan === 'free') {
            $api_url = 'https://api.kingaddons.com/get-section-free.php';
            $install_id = 0;
            error_log('King Addons Free Section: Fetching free section: ' . $section_key);
        } else {
            error_log('King Addons Section Error: Premium section requires premium license. Section: ' . $section_key . ', Plan: ' . $section_plan . ', Premium Active: ' . ($is_premium_active ? 'Yes' : 'No'));
            wp_send_json_error('Premium section requires premium license');
            return;
        }

        // Get section data from API (same as templates)
        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'key' => $section_key,
                'install' => $install_id,
            ]),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch section: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        error_log('King Addons Section API Response: ' . substr($body, 0, 500) . (strlen($body) > 500 ? '...' : ''));
        
        if (!$data) {
            error_log('King Addons Section Error: Failed to decode JSON response');
            wp_send_json_error('Invalid JSON response from section API');
            return;
        }

        if (!isset($data['success']) || !$data['success']) {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown API error';
            error_log('King Addons Section Error: API returned error: ' . $error_message);
            wp_send_json_error('Section API error: ' . $error_message);
            return;
        }

        // Return section data for frontend processing (adjust format from your API)
        wp_send_json_success([
            'section_data' => $data['section'],  // Your API returns 'section' not 'landing'
            'message' => 'Section data retrieved successfully'
        ]);
    }

    /**
     * Validate image URL for security (prevent SSRF attacks)
     * @param string $url The URL to validate
     * @return bool True if URL is safe, false otherwise
     */
    private function is_safe_image_url(string $url): bool
    {
        // Parse URL
        $parsed_url = parse_url($url);
        if (!$parsed_url || !isset($parsed_url['scheme']) || !isset($parsed_url['host'])) {
            return false;
        }

        // Only allow HTTP/HTTPS
        if (!in_array($parsed_url['scheme'], ['http', 'https'], true)) {
            return false;
        }

        // Block local/private IP addresses to prevent SSRF
        $host = $parsed_url['host'];
        
        // Check if it's an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Block private/reserved IP ranges
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        // Block localhost and common local domains
        $blocked_hosts = [
            'localhost',
            '127.0.0.1',
            '::1',
            'metadata.google.internal',
            '169.254.169.254', // AWS metadata
        ];
        
        if (in_array(strtolower($host), $blocked_hosts, true)) {
            return false;
        }

        // Only allow images from trusted domains (King Addons CDN)
        $allowed_domains = [
            'api.kingaddons.com',
            'cdn.kingaddons.com',
            'templates.kingaddons.com',
            'images.kingaddons.com'
        ];

        $is_allowed_domain = false;
        foreach ($allowed_domains as $allowed_domain) {
            if (strtolower($host) === strtolower($allowed_domain) || 
                str_ends_with(strtolower($host), '.' . strtolower($allowed_domain))) {
                $is_allowed_domain = true;
                break;
            }
        }

        return $is_allowed_domain;
    }
}
