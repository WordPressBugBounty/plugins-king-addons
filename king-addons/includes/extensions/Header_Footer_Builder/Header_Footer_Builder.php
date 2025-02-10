<?php /** @noinspection PhpMissingFieldTypeInspection, DuplicatedCode */

namespace King_Addons;

use Elementor;

if (!defined('ABSPATH')) {
    exit;
}

final class Header_Footer_Builder
{
    private static ?Header_Footer_Builder $instance = null;
    private static ?string $current_page_type = null;
    private static array $current_page_data = array();
    private static $location_selection;
    private static $elementor_instance;

    public static function instance(): ?Header_Footer_Builder
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('init', [$this, 'addPostType']);
        add_action('add_meta_boxes', [$this, 'registerMetabox']);
        add_action('save_post', [$this, 'saveMetaboxData']);
        add_action('template_redirect', [$this, 'checkUserCanEdit']);
        add_filter('single_template', [$this, 'loadElementorCanvasTemplate']);
        self::setCompatibility();
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));

        if (is_admin()) {
            add_action('manage_king-addons-el-hf_posts_custom_column', [$this, 'columnContent'], 10, 2);
            add_filter('manage_king-addons-el-hf_posts_columns', [$this, 'columnHeadings']);
        }
    }

    function addPostType(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $labels = [
            'name' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'singular_name' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'menu_name' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'name_admin_bar' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'add_new' => esc_html__('Add New', 'king-addons'),
            'add_new_item' => esc_html__('Add New', 'king-addons'),
            'new_item' => esc_html__('New Template', 'king-addons'),
            'edit_item' => esc_html__('Edit Template', 'king-addons'),
            'view_item' => esc_html__('View Template', 'king-addons'),
            'all_items' => esc_html__('All Templates', 'king-addons'),
            'search_items' => esc_html__('Search Templates', 'king-addons'),
            'parent_item_colon' => esc_html__('Parent Templates:', 'king-addons'),
            'not_found' => esc_html__('No Templates found.', 'king-addons'),
            'not_found_in_trash' => esc_html__('No Templates found in Trash.', 'king-addons'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'exclude_from_search' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_icon' => 'dashicons-editor-kitchensink',
            'supports' => ['title', 'thumbnail', 'elementor'],
            'show_in_rest' => true,
        ];

        register_post_type('king-addons-el-hf', $args);

        if (false === get_option('king_addons_HFB_flushed_rewrite_rules')) {
            add_option('king_addons_HFB_flushed_rewrite_rules', true);
            flush_rewrite_rules();
        }
    }

    function registerMetabox()
    {
        add_meta_box(
            'king-addons-el-hf-meta-box',
            esc_html__('Elementor Header & Footer Builder Options', 'king-addons'),
            [$this, 'renderMetabox'],
            'king-addons-el-hf',
            'normal',
            'high'
        );
    }

    // todo
    function renderMetabox($post)
    {
        $values = get_post_custom($post->ID);
        $template_type = isset($values['king_addons_el_hf_template_type']) ? esc_attr(sanitize_text_field($values['king_addons_el_hf_template_type'][0])) : '';

        wp_nonce_field('king_addons_el_hf_meta_nounce', 'king_addons_el_hf_meta_nounce');
        ?>
        <table class="king-addons-el-hf-options-table widefat">
            <tbody>
            <tr class="king-addons-el-hf-options-row type-of-template">
                <td class="king-addons-el-hf-options-row-heading">
                    <label for="king_addons_el_hf_template_type"><?php esc_html_e('Type of Template', 'king-addons'); ?></label>
                </td>
                <td class="king-addons-el-hf-options-row-content">
                    <select name="king_addons_el_hf_template_type" id="king_addons_el_hf_template_type">
                        <option value="king_addons_el_hf_not_selected" <?php selected($template_type, ''); ?>><?php esc_html_e('Select Option', 'king-addons'); ?></option>
                        <option value="king_addons_el_hf_type_header" <?php selected($template_type, 'king_addons_el_hf_type_header'); ?>><?php esc_html_e('Header', 'king-addons'); ?></option>
                        <option value="king_addons_el_hf_type_footer" <?php selected($template_type, 'king_addons_el_hf_type_footer'); ?>><?php esc_html_e('Footer', 'king-addons'); ?></option>
                    </select>
                </td>
            </tr>

            <?php

            // todo
//            $this->display_rules_tab();

            ?>

            </tbody>
        </table>
        <?php
    }

    // todo
    function saveMetaboxData($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['king_addons_el_hf_meta_nounce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['king_addons_el_hf_meta_nounce'])), 'king_addons_el_hf_meta_nounce')) {
            return;
        }

        if (!current_user_can('edit_posts')) {
            return;
        }

        // todo
        $target_locations = array(
            "rule" => array(
                0 => "basic-global"
            ),
            "specific" => array()
        );

        update_post_meta($post_id, 'king_addons_el_hf_target_include_locations', $target_locations);

        if (isset($_POST['king_addons_el_hf_template_type'])) {
            update_post_meta($post_id, 'king_addons_el_hf_template_type', sanitize_text_field(wp_unslash($_POST['king_addons_el_hf_template_type'])));
        }
    }

    function setCompatibility()
    {
        $template = get_template();
        $is_elementor_callable = defined('ELEMENTOR_VERSION') && is_callable('Elementor\Plugin::instance');

        if ($is_elementor_callable) {
            self::$elementor_instance = Elementor\Plugin::instance();

            // TODO: Add popular themes
            switch ($template) {
                case 'hello-elementor':
                    require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/hello-elementor/ELHF_Hello_Elementor.php');
                    break;
                default:
                    add_action('init', [$this, 'setupSettingsPage']);
                    add_filter('king_addons_el_hf_settings_tabs', [$this, 'setupUnsupportedTheme']);
                    add_action('init', [$this, 'setupFallbackSupport']);
                    break;
            }
        }
    }

    public function setupUnsupportedTheme($settings_tabs = [])
    {
        if (!current_theme_supports('king-addons-elementor-header-footer')) {
            $settings_tabs['king_addons_el_hf_settings'] = [
                'name' => esc_html__('Display Settings', 'king-addons'),
                'url' => admin_url('edit.php?post_type=king-addons-el-hf&page=king-addons-el-hf-settings'),
            ];
        }
        return $settings_tabs;
    }

    public function setupFallbackSupport()
    {
        if (!current_theme_supports('king-addons-elementor-header-footer')) {
            $compatibility_option = get_option('king_addons_el_hf_compatibility_option', '1');

            if ('1' === $compatibility_option) {
                if (!class_exists('ELHF_Default_Method_1')) {
                    require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/default/ELHF_Default_Method_1.php');
                }
            } elseif ('2' === $compatibility_option) {
                if (!class_exists('ELHF_Default_Method_2')) {
                    require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/default/ELHF_Default_Method_2.php');
                }
            }
        }
    }

    function setupSettingsPage()
    {
        require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/ELHF_Settings_Page.php');
    }

    public static function renderHeader()
    {
        /** @noinspection SpellCheckingInspection */
        echo '<header id="masthead" itemscope="itemscope" itemtype="https://schema.org/WPHeader">';
        ?><p class="main-title" style="display: none;" itemprop="headline"><a
                href="<?php echo esc_url(get_bloginfo('url')); ?>"
                title="<?php echo esc_attr(get_bloginfo('name', 'display')); ?>"
                rel="home"><?php echo esc_html(get_bloginfo('name')); ?></a></p><?php
        self::getHeaderContent();
        echo '</header>';
    }

    public static function renderFooter()
    {
        /** @noinspection SpellCheckingInspection */
        echo '<footer id="colophon" itemscope="itemscope" itemtype="https://schema.org/WPFooter" role="contentinfo">';
        self::getFooterContent();
        echo '</footer>';
    }

    public static function getHeaderContent()
    {
        echo self::$elementor_instance->frontend->get_builder_content_for_display(self::getHeaderID()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public static function getFooterContent()
    {
        echo '<div style="width: 100%;">';
        echo self::$elementor_instance->frontend->get_builder_content_for_display(self::getFooterID()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }

    public static function getHeaderID()
    {
        $header_id = self::getSettings('king_addons_el_hf_type_header');

        if ('' === $header_id) {
            $header_id = false;
        }

        return apply_filters('king_addons_el_hf_get_header_id', $header_id);
    }

    public static function isHeaderEnabled()
    {
        $header_id = self::getSettings('king_addons_el_hf_type_header');
        $status = false;

        if ('' !== $header_id) {
            $status = true;
        }

        return apply_filters('king_addons_el_hf_header_enabled', $status);
    }

    public static function isFooterEnabled()
    {
        $footer_id = self::getSettings('king_addons_el_hf_type_footer');
        $status = false;

        if ('' !== $footer_id) {
            $status = true;
        }

        return apply_filters('king_addons_el_hf_footer_enabled', $status);
    }

    public static function getFooterID()
    {
        $footer_id = self::getSettings('king_addons_el_hf_type_footer');

        if ('' === $footer_id) {
            $footer_id = false;
        }

        return apply_filters('king_addons_el_hf_get_footer_id', $footer_id);
    }


    public static function getSettings($setting = '')
    {
        if ('king_addons_el_hf_type_header' == $setting || 'king_addons_el_hf_type_footer' == $setting) {
            $templates = self::getTemplateID($setting);
            $template = !is_array($templates) ? $templates : $templates[0];
            return apply_filters("king_addons_el_hf_get_settings_$setting", $template);
        }

        return null;
    }

    public static function getTemplateID($type)
    {
        // TODO
        $option = [
            'location' => 'king_addons_el_hf_target_include_locations',
            'exclusion' => 'king_addons_el_hf_target_exclude_locations',
            'users' => 'king_addons_el_hf_target_user_roles',
        ];

        $templates = self::getPostsByConditions('king-addons-el-hf', $option);

        foreach ($templates as $template) {
            if (get_post_meta(absint($template['id']), 'king_addons_el_hf_template_type', true) === $type) {
                // Polylang check - https://polylang.pro/doc/function-reference/
                if (function_exists('pll_current_language')) {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    if (pll_current_language('slug') == pll_get_post_language($template['id'], 'slug')) {
                        return $template['id'];
                    }
                } else {
                    return $template['id'];
                }
            }
        }

        return '';
    }

    public static function getPostsByConditions($post_type, $option)
    {
        global $wpdb;
        global $post;

        $post_type = $post_type ? esc_sql($post_type) : esc_sql($post->post_type);

        if (is_array(self::$current_page_data) && isset(self::$current_page_data[$post_type])) {
            return apply_filters('king_addons_el_hf_get_display_posts_by_conditions', self::$current_page_data[$post_type], $post_type);
        }

        $current_page_type = self::getCurrentPageType();

        self::$current_page_data[$post_type] = array();

        $option['current_post_id'] = self::$current_page_data['ID'];
        $meta_header = self::getMetaOptionPost($post_type, $option);

        if (false === $meta_header) {
            $current_post_type = esc_sql(get_post_type());
            $current_post_id = false;
            $q_obj = get_queried_object();

            $current_id = esc_sql(get_the_id());

            // Check if WPML is active. Find WPML Object ID for current page.
            /** @noinspection SpellCheckingInspection */
            if (defined('ICL_SITEPRESS_VERSION')) {
                $default_lang = apply_filters('wpml_default_language', '');
                $current_lang = apply_filters('wpml_current_language', '');

                if ($default_lang !== $current_lang) {
                    $current_post_type = get_post_type($current_id);
                    $current_id = apply_filters('wpml_object_id', $current_id, $current_post_type, true, $default_lang);
                }
            }

            $location = isset($option['location']) ? esc_sql($option['location']) : '';

            $query = "SELECT p.ID, pm.meta_value FROM $wpdb->postmeta as pm
						INNER JOIN $wpdb->posts as p ON pm.post_id = p.ID
						WHERE pm.meta_key = '$location'
						AND p.post_type = '$post_type'
						AND p.post_status = 'publish'";

            $orderby = ' ORDER BY p.post_date DESC';

            $meta_args = "pm.meta_value LIKE '%\"basic-global\"%'";

            switch ($current_page_type) {
                case 'is_404':
                    $meta_args .= " OR pm.meta_value LIKE '%\"special-404\"%'";
                    break;
                case 'is_search':
                    $meta_args .= " OR pm.meta_value LIKE '%\"special-search\"%'";
                    break;
                case 'is_archive':
                case 'is_tax':
                case 'is_date':
                case 'is_author':
                    $meta_args .= " OR pm.meta_value LIKE '%\"basic-archives\"%'";
                    $meta_args .= " OR pm.meta_value LIKE '%\"$current_post_type|all|archive\"%'";
                    if ('is_tax' == $current_page_type && (is_category() || is_tag() || is_tax())) {
                        if (is_object($q_obj)) {
                            $meta_args .= " OR pm.meta_value LIKE '%\"$current_post_type|all|taxarchive|$q_obj->taxonomy\"%'";
                            $meta_args .= " OR pm.meta_value LIKE '%\"tax-$q_obj->term_id\"%'";
                        }
                    } elseif ('is_date' == $current_page_type) {
                        $meta_args .= " OR pm.meta_value LIKE '%\"special-date\"%'";
                    } elseif ('is_author' == $current_page_type) {
                        $meta_args .= " OR pm.meta_value LIKE '%\"special-author\"%'";
                    }
                    break;
                case 'is_home':
                    $meta_args .= " OR pm.meta_value LIKE '%\"special-blog\"%'";
                    break;
                case 'is_front_page':
                    $current_post_id = $current_id;
                    $meta_args .= " OR pm.meta_value LIKE '%\"special-front\"%'";
                    $meta_args .= " OR pm.meta_value LIKE '%\"$current_post_type|all\"%'";
                    $meta_args .= " OR pm.meta_value LIKE '%\"post-$current_id\"%'";
                    break;
                case 'is_singular':
                    $current_post_id = $current_id;
                    $meta_args .= " OR pm.meta_value LIKE '%\"basic-singulars\"%'";
                    $meta_args .= " OR pm.meta_value LIKE '%\"$current_post_type|all\"%'";
                    $meta_args .= " OR pm.meta_value LIKE '%\"post-$current_id\"%'";
                    $taxonomies = get_object_taxonomies($q_obj->post_type);
                    $terms = wp_get_post_terms($q_obj->ID, $taxonomies);
                    foreach ($terms as $term) {
                        $meta_args .= " OR pm.meta_value LIKE '%\"tax-$term->term_id-single-$term->taxonomy\"%'";
                    }
                    break;
                case 'is_woo_shop_page':
                    $meta_args .= " OR pm.meta_value LIKE '%\"special-woocommerce-shop\"%'";
                    break;
                case '':
                    $current_post_id = $current_id;
                    break;
            }

            // @codingStandardsIgnoreStart
            $posts = $wpdb->get_results($query . ' AND (' . $meta_args . ')' . $orderby);
            // @codingStandardsIgnoreEnd

            foreach ($posts as $local_post) {
                self::$current_page_data[$post_type][$local_post->ID] = array(
                    'id' => $local_post->ID,
                    'location' => unserialize($local_post->meta_value),
                );
            }

            $option['current_post_id'] = $current_post_id;

            self::removeExclusionRulePosts($post_type, $option);
            self::removeUserRulePosts($post_type, $option);
        }

        return apply_filters('king_addons_el_hf_get_display_posts_by_conditions', self::$current_page_data[$post_type], $post_type);
    }

    public static function getCurrentPageType(): ?string
    {
        if (null === self::$current_page_type) {
            $page_type = '';
            $current_id = false;

            if (is_404()) {
                $page_type = 'is_404';
            } elseif (is_search()) {
                $page_type = 'is_search';
            } elseif (is_archive()) {
                $page_type = 'is_archive';
                if (is_category() || is_tag() || is_tax()) {
                    $page_type = 'is_tax';
                } elseif (is_date()) {
                    $page_type = 'is_date';
                } elseif (is_author()) {
                    $page_type = 'is_author';
                } elseif (function_exists('is_shop')) {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    if (is_shop()) {
                        $page_type = 'is_woo_shop_page';
                    }
                }
            } elseif (is_home()) {
                $page_type = 'is_home';
            } elseif (is_front_page()) {
                $page_type = 'is_front_page';
                $current_id = get_the_id();
            } elseif (is_singular()) {
                $page_type = 'is_singular';
                $current_id = get_the_id();
            } else {
                $current_id = get_the_id();
            }

            self::$current_page_data['ID'] = $current_id;
            self::$current_page_type = $page_type;
        }

        return self::$current_page_type;
    }

    public static function getMetaOptionPost($post_type, $option)
    {
        $page_meta = (isset($option['page_meta']) && '' != $option['page_meta']) ? $option['page_meta'] : false;

        if (false !== $page_meta) {
            $current_post_id = $option['current_post_id'] ?? false;
            $meta_id = get_post_meta($current_post_id, $option['page_meta'], true);

            if (false !== $meta_id && '' != $meta_id) {
                self::$current_page_data[$post_type][$meta_id] = array(
                    'id' => $meta_id,
                    'location' => '',
                );

                return self::$current_page_data[$post_type];
            }
        }

        return false;
    }

    public static function removeExclusionRulePosts($post_type, $option)
    {
        $exclusion = $option['exclusion'] ?? '';
        $current_post_id = $option['current_post_id'] ?? false;
        foreach (self::$current_page_data[$post_type] as $c_post_id => $c_data) {
            $exclusion_rules = get_post_meta($c_post_id, $exclusion, true);
            $is_exclude = self::parseLayoutDisplayCondition($current_post_id, $exclusion_rules);
            if ($is_exclude) {
                unset(self::$current_page_data[$post_type][$c_post_id]);
            }
        }
    }

    public static function removeUserRulePosts($post_type, $option)
    {
        $users = $option['users'] ?? '';

        foreach (self::$current_page_data[$post_type] as $c_post_id => $c_data) {
            $user_rules = get_post_meta($c_post_id, $users, true);
            $is_user = self::parseUserRoleCondition($user_rules);

            if (!$is_user) {
                unset(self::$current_page_data[$post_type][$c_post_id]);
            }
        }
    }

    public static function parseLayoutDisplayCondition($post_id, $rules): bool
    {
        $display = false;

        /** @noinspection PhpConditionCheckedByNextConditionInspection */
        if (isset($rules['rule']) && is_array($rules['rule']) && !empty($rules['rule'])) {
            foreach ($rules['rule'] as $rule) {
                if (strrpos($rule, 'all') !== false) {
                    $rule_case = 'all';
                } else {
                    $rule_case = $rule;
                }

                switch ($rule_case) {
                    case 'basic-global':
                        $display = true;
                        break;

                    case 'basic-singulars':
                        if (is_singular()) {
                            $display = true;
                        }
                        break;

                    case 'basic-archives':
                        if (is_archive()) {
                            $display = true;
                        }
                        break;

                    case 'special-404':
                        if (is_404()) {
                            $display = true;
                        }
                        break;

                    case 'special-search':
                        if (is_search()) {
                            $display = true;
                        }
                        break;

                    case 'special-blog':
                        if (is_home()) {
                            $display = true;
                        }
                        break;

                    case 'special-front':
                        if (is_front_page()) {
                            $display = true;
                        }
                        break;

                    case 'special-date':
                        if (is_date()) {
                            $display = true;
                        }
                        break;

                    case 'special-author':
                        if (is_author()) {
                            $display = true;
                        }
                        break;

                    case 'special-woocommerce-shop':
                        if (function_exists('is_shop')) {
                            /** @noinspection PhpUndefinedFunctionInspection */
                            if (is_shop()) {
                                $display = true;
                            }
                        }
                        break;

                    case 'all':
                        $rule_data = explode('|', $rule);

                        $post_type = $rule_data[0] ?? false;
                        $archive_type = $rule_data[2] ?? false;
                        $taxonomy = $rule_data[3] ?? false;
                        if (false === $archive_type) {
                            $current_post_type = get_post_type($post_id);
                            if (false !== $post_id && $current_post_type == $post_type) {
                                $display = true;
                            }
                        } else {
                            if (is_archive()) {
                                $current_post_type = get_post_type();
                                if ($current_post_type == $post_type) {
                                    if ('archive' == $archive_type) {
                                        $display = true;
                                    } elseif ('taxarchive' == $archive_type) {
                                        $obj = get_queried_object();
                                        $current_taxonomy = '';
                                        if ('' !== $obj && null !== $obj) {
                                            $current_taxonomy = $obj->taxonomy;
                                        }

                                        if ($current_taxonomy == $taxonomy) {
                                            $display = true;
                                        }
                                    }
                                }
                            }
                        }
                        break;

                    case 'specifics':
                        if (isset($rules['specific']) && is_array($rules['specific'])) {
                            foreach ($rules['specific'] as $specific_page) {
                                $specific_data = explode('-', $specific_page);
                                $specific_post_type = $specific_data[0] ?? false;
                                $specific_post_id = $specific_data[1] ?? false;
                                if ('post' == $specific_post_type) {
                                    if ($specific_post_id == $post_id) {
                                        $display = true;
                                    }
                                } elseif (isset($specific_data[2]) && ('single' == $specific_data[2]) && 'tax' == $specific_post_type) {
                                    if (is_singular()) {
                                        $term_details = get_term($specific_post_id);

                                        if (isset($term_details->taxonomy)) {
                                            $has_term = has_term((int)$specific_post_id, $term_details->taxonomy, $post_id);

                                            if ($has_term) {
                                                $display = true;
                                            }
                                        }
                                    }
                                } elseif ('tax' == $specific_post_type) {
                                    $tax_id = get_queried_object_id();
                                    if ($specific_post_id == $tax_id) {
                                        $display = true;
                                    }
                                }
                            }
                        }
                        break;

                    default:
                        break;
                }

                if ($display) {
                    break;
                }
            }
        }

        return $display;
    }

    public static function parseUserRoleCondition($rules): bool
    {
        $show_popup = true;

        if (is_array($rules) && !empty($rules)) {
            $show_popup = false;

            foreach ($rules as $rule) {
                switch ($rule) {
                    case '':
                    case 'all':
                        $show_popup = true;
                        break;

                    case 'logged-in':
                        if (is_user_logged_in()) {
                            $show_popup = true;
                        }
                        break;

                    case 'logged-out':
                        if (!is_user_logged_in()) {
                            $show_popup = true;
                        }
                        break;

                    default:
                        if (is_user_logged_in()) {
                            $current_user = wp_get_current_user();

                            if (isset($current_user->roles)
                                && is_array($current_user->roles)
                                && in_array($rule, $current_user->roles)
                            ) {
                                $show_popup = true;
                            }
                        }
                        break;
                }

                if ($show_popup) {
                    break;
                }
            }
        }

        return $show_popup;
    }

    public static function checkUserCanEdit()
    {
        if (is_singular('king-addons-el-hf') && !current_user_can('edit_posts')) {
            wp_redirect(site_url(), 301);
            die;
        }
    }

    public static function loadElementorCanvasTemplate($single_template)
    {
        global $post;

        if ('king-addons-el-hf' == $post->post_type) {
            if (defined('ELEMENTOR_VERSION')) {
                $elementor_2_0_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';

                if (file_exists($elementor_2_0_canvas)) {
                    return $elementor_2_0_canvas;
                } else {
                    return ELEMENTOR_PATH . '/includes/page-templates/canvas.php';
                }
            }
        }

        return $single_template;
    }

    public static function enqueueScripts(): void
    {
        $screen = get_current_screen();
        if ($screen->id === 'edit-king-addons-el-hf') {
            // todo - styles
//            wp_enqueue_style('king-addons-el-hf-style', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/header-footer-builder.css', '', KING_ADDONS_VERSION);
            wp_enqueue_style('king-addons-el-hf-style', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/admin.css', '', KING_ADDONS_VERSION);
        }
    }

    public static function columnHeadings($columns)
    {
        unset($columns['date']);
        $columns['king_addons_el_hf_edit_template'] = esc_html__('Edit Template', 'king-addons');
        $columns['king_addons_el_hf_type_of_template'] = esc_html__('Type of Template', 'king-addons');
        $columns['king_addons_el_hf_display_rules'] = esc_html__('Display Rules', 'king-addons');
        $columns['date'] = esc_html__('Date', 'king-addons');
        return $columns;
    }

    public static function columnContent($column, $post_id)
    {
        // Edit Template
        if ('king_addons_el_hf_edit_template' === $column) {
            echo '<a class="king-addons-el-hf-edit-template-btn" href="';
            echo './post.php?post=' . esc_attr($post_id) . '&action=edit';
            echo '">' . esc_html__('Edit Template', 'king-addons') . '</a>';
        }

        // Display Rules
        if ('king_addons_el_hf_display_rules' === $column) {
            $locations = get_post_meta($post_id, 'king_addons_el_hf_target_include_locations', true);
            if (!empty($locations)) {
                echo '<div style="margin-bottom: 5px;">';
                echo '<strong>';
                echo esc_html__('Display: ', 'king-addons');
                echo '</strong>';
                self::columnDisplayLocation($locations);
                echo '</div>';
            }
        }

        // Type of Template
        if ('king_addons_el_hf_type_of_template' === $column) {
            $template_type = get_post_meta($post_id, 'king_addons_el_hf_template_type', true);
            if (!empty($template_type)) {
                echo '<div style="margin-bottom: 5px;">';
                echo '<strong>';
                switch ($template_type) {
                    case 'king_addons_el_hf_type_header':
                        echo esc_html__('Header', 'king-addons');
                        break;
                    case 'king_addons_el_hf_type_footer':
                        echo esc_html__('Footer', 'king-addons');
                        break;
                    default:
                        echo esc_html__('Not selected', 'king-addons');
                        break;
                }
                echo '</strong>';
                echo '</div>';
            }
        }
    }

    public static function columnDisplayLocation($locations)
    {
        $location_label = [];
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (is_array($locations) && is_array($locations['rule']) && isset($locations['rule'])) {
            /** @noinspection PhpArraySearchInBooleanContextInspection */
            $index = array_search('specifics', $locations['rule']);
            /** @noinspection PhpConditionCheckedByNextConditionInspection */
            if (false !== $index && !empty($index)) {
                unset($locations['rule'][$index]);
            }
        }

        if (isset($locations['rule']) && is_array($locations['rule'])) {
            foreach ($locations['rule'] as $location) {
                $location_label[] = self::getLocation($location);
            }
        }

        if (isset($locations['specific']) && is_array($locations['specific'])) {
            foreach ($locations['specific'] as $location) {
                $location_label[] = self::getLocation($location);
            }
        }

        echo esc_html(join(', ', $location_label));
    }

    public static function getLocation($key)
    {
        if (!isset(self::$location_selection) || empty(self::$location_selection)) {
            self::$location_selection = self::getLocationSelections();
        }

        $location_selection = self::$location_selection;

        foreach ($location_selection as $location_grp) {
            if (isset($location_grp['value'][$key])) {
                return $location_grp['value'][$key];
            }
        }

        if (strpos($key, 'post-') !== false) {
            $post_id = (int)str_replace('post-', '', $key);
            return get_the_title($post_id);
        }

        if (strpos($key, 'tax-') !== false) {
            $tax_id = (int)str_replace('tax-', '', $key);
            $term = get_term($tax_id);

            if (!is_wp_error($term)) {
                $term_taxonomy = ucfirst(str_replace('_', ' ', $term->taxonomy));
                return $term->name . ' - ' . $term_taxonomy;
            } else {
                return '';
            }
        }

        return $key;
    }

    public static function getLocationSelections()
    {
        $args = array(
            'public' => true,
            '_builtin' => true,
        );

        $post_types = get_post_types($args, 'objects');
        unset($post_types['attachment']);

        $args['_builtin'] = false;
        $custom_post_type = get_post_types($args, 'objects');

        $post_types = apply_filters('king_addons_el_hf_location_rule_post_types', array_merge($post_types, $custom_post_type));

        $special_pages = array(
            'special-404' => esc_html__('404 Page', 'king-addons'),
            'special-search' => esc_html__('Search Page', 'king-addons'),
            'special-blog' => esc_html__('Blog / Posts Page', 'king-addons'),
            'special-front' => esc_html__('Front Page', 'king-addons'),
            'special-date' => esc_html__('Date Archive', 'king-addons'),
            'special-author' => esc_html__('Author Archive', 'king-addons'),
        );

        if (class_exists('WooCommerce')) {
            $special_pages['special-woocommerce-shop'] = esc_html__('WooCommerce Shop Page', 'king-addons');
        }

        $selection_options = array(
            'basic' => array(
                'label' => esc_html__('Basic', 'king-addons'),
                'value' => array(
                    'basic-global' => esc_html__('Entire Website', 'king-addons'),
                    'basic-singulars' => esc_html__('All Singulars', 'king-addons'),
                    'basic-archives' => esc_html__('All Archives', 'king-addons'),
                ),
            ),

            'special-pages' => array(
                'label' => esc_html__('Special Pages', 'king-addons'),
                'value' => $special_pages,
            ),
        );

        $args = array(
            'public' => true,
        );

        $taxonomies = get_taxonomies($args, 'objects');

        if (!empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {

                if ('post_format' == $taxonomy->name) {
                    continue;
                }

                foreach ($post_types as $post_type) {
                    $post_opt = self::getPostTargetRuleOptions($post_type, $taxonomy);

                    if (isset($selection_options[$post_opt['post_key']])) {
                        if (!empty($post_opt['value']) && is_array($post_opt['value'])) {
                            foreach ($post_opt['value'] as $key => $value) {
                                if (!in_array($value, $selection_options[$post_opt['post_key']]['value'])) {
                                    $selection_options[$post_opt['post_key']]['value'][$key] = $value;
                                }
                            }
                        }
                    } else {
                        $selection_options[$post_opt['post_key']] = array(
                            'label' => $post_opt['label'],
                            'value' => $post_opt['value'],
                        );
                    }
                }
            }
        }

        $selection_options['specific-target'] = array(
            'label' => esc_html__('Specific Target', 'king-addons'),
            'value' => array(
                'specifics' => esc_html__('Specific Pages / Posts / Taxonomies, etc.', 'king-addons'),
            ),
        );

        return apply_filters('king_addons_el_hf_display_on_list', $selection_options);
    }

    public static function getPostTargetRuleOptions($post_type, $taxonomy): array
    {
        $post_key = str_replace(' ', '-', strtolower($post_type->label));
        $post_label = ucwords($post_type->label);
        $post_name = $post_type->name;
        $post_option = array();

        /* translators: %s is post label */
        $all_posts = sprintf(esc_html__('All %s', 'king-addons'), $post_label);
        $post_option[$post_name . '|all'] = $all_posts;

        if ('pages' != $post_key) {
            /* translators: %s is post label */
            $all_archive = sprintf(esc_html__('All %s Archive', 'king-addons'), $post_label);
            $post_option[$post_name . '|all|archive'] = $all_archive;
        }

        if (in_array($post_type->name, $taxonomy->object_type)) {
            $tax_label = ucwords($taxonomy->label);
            $tax_name = $taxonomy->name;

            /* translators: %s is taxonomy label */
            $tax_archive = sprintf(esc_html__('All %s Archive', 'king-addons'), $tax_label);

            $post_option[$post_name . '|all|taxarchive|' . $tax_name] = $tax_archive;
        }

        $post_output['post_key'] = $post_key;
        $post_output['label'] = $post_label;
        $post_output['value'] = $post_option;

        return $post_output;
    }

    public static function getFormatRuleValue($save_data, $key): array
    {
        $meta_value = array();

        if (isset($save_data[$key]['rule'])) {
            $save_data[$key]['rule'] = array_unique($save_data[$key]['rule']);
            if (isset($save_data[$key]['specific'])) {
                $save_data[$key]['specific'] = array_unique($save_data[$key]['specific']);
            }

            $index = array_search('', $save_data[$key]['rule']);
            if (false !== $index) {
                unset($save_data[$key]['rule'][$index]);
            }
            $index = array_search('specifics', $save_data[$key]['rule']);
            if (false !== $index) {
                unset($save_data[$key]['rule'][$index]);

                if (isset($save_data[$key]['specific']) && is_array($save_data[$key]['specific'])) {
//                    array_push( $save_data[ $key ]['rule'], 'specifics' );
                    $save_data[$key]['rule'][] = 'specifics';
                }
            }

            foreach ($save_data[$key] as $meta_key => $value) {
                if (!empty($value)) {
                    $meta_value[$meta_key] = array_map('esc_attr', $value);
                }
            }
            if (!isset($meta_value['rule']) || !in_array('specifics', $meta_value['rule'])) {
                $meta_value['specific'] = array();
            }

            if (empty($meta_value['rule'])) {
                $meta_value = array();
            }
        }

        return $meta_value;
    }
}