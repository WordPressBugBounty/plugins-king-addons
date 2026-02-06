<?php
/**
 * WooCommerce Builder feature.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Plugin as Elementor_Plugin;
use Elementor\Repeater;
use King_Addons\Woo_Builder\Context as Woo_Context;
use King_Addons\Woo_Builder\Document as Woo_Document;
use King_Addons\Woo_Builder\My_Account_Manager;
use WP_Post;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers and renders Elementor-based WooCommerce templates.
 */
class Woo_Builder
{
    /**
     * Current template IDs keyed by context.
     *
     * @var array<string,int>
     */
    private array $current_templates = [];

    /**
     * Active context for body classes.
     *
     * @var string|null
     */
    private ?string $active_context = null;

    /**
     * Flag to ensure default blocks hook only once.
     *
     * @var bool
     */
    private bool $default_blocks_registered = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!class_exists('WooCommerce') || !did_action('elementor/loaded')) {
            return;
        }

        require_once KING_ADDONS_PATH . 'includes/helpers/Woo_Builder/Context.php';

        add_action('init', [$this, 'register_template_types']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_filter('template_include', [$this, 'override_wc_template'], 99);
        add_filter('king_addons/woo_builder/current_template_id', [$this, 'filter_current_template_id'], 10, 2);
        add_filter('body_class', [$this, 'filter_body_class']);

        // My Account endpoint manager (Pro routing).
        add_action(
            'plugins_loaded',
            static function () {
                if (!class_exists(My_Account_Manager::class)) {
                    require_once KING_ADDONS_PATH . 'includes/helpers/Woo_Builder/My_Account_Manager.php';
                }
                new My_Account_Manager();
            }
        );
        add_filter('default_post_metadata', [$this, 'prefill_template_meta'], 10, 5);

        // Save template type meta when post is created via URL parameter
        add_action('save_post_elementor_library', [$this, 'save_template_type_from_url'], 10, 3);
        
        // Also hook into wp_insert_post for auto-draft creation
        add_action('wp_insert_post', [$this, 'save_template_type_on_insert'], 10, 3);
        
        // Hook into admin to set meta when creating new template from URL
        add_action('admin_init', [$this, 'maybe_set_woo_template_meta_on_new_post']);

        // All conditions are managed in Elementor document settings (no WP metabox UI).

        add_action('elementor/documents/register_controls', [$this, 'register_document_controls']);
        add_action('elementor/document/after_save', [$this, 'handle_document_after_save'], 10, 2);
    }

    /**
     * Register assets for builder.
     *
     * @return void
     */
    public function register_assets(): void
    {
        $deps = is_admin() ? ['jquery', 'select2', 'wp-api'] : [];

        wp_register_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-style',
                KING_ADDONS_URL . 'includes/extensions/Woo_Builder/style.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_register_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-script',
                KING_ADDONS_URL . 'includes/extensions/Woo_Builder/script.js',
            $deps,
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Register Elementor document type for Woo Builder.
     *
     * @return void
     */
    public function register_template_types(): void
    {
        if (!class_exists('Elementor\\Plugin')) {
            return;
        }

        if (!class_exists('King_Addons\\Woo_Builder\\Document')) {
            require_once KING_ADDONS_PATH . 'includes/helpers/Woo_Builder/Document.php';
        }

        if (class_exists('King_Addons\\Woo_Builder\\Document')) {
            Elementor_Plugin::$instance->documents->register_document_type(
                Woo_Document::get_type(),
                Woo_Document::class
            );
        }
    }

    /**
     * Register meta box for Elementor templates.
     *
     * @return void
     */
    public function register_meta_boxes(): void
    {
        add_meta_box(
            'king-addons-woo-builder',
            esc_html__('Woo Builder Conditions', 'king-addons'),
            [$this, 'render_meta_box'],
            'elementor_library',
            'side',
            'default'
        );
    }

    /**
     * Render meta box UI.
     *
     * @param WP_Post $post Post object.
     *
     * @return void
     */
    public function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('king_addons_woo_builder_meta', 'king_addons_woo_builder_meta_nonce');

        $template_type = get_post_meta($post->ID, 'ka_woo_template_type', true);
        $conditions = get_post_meta($post->ID, 'ka_woo_conditions', true);
        $enabled = !empty($conditions['enabled']);
        $priority = isset($conditions['priority']) ? (int) $conditions['priority'] : 10;
        $rules = [];
        if (!empty($conditions['rules']) && is_array($conditions['rules'])) {
            $rules = $conditions['rules'];
        }
        foreach ($rules as $idx => $rule) {
            $rules[$idx]['type'] = $this->normalize_rule_type($rule['type'] ?? '');
        }
        if (empty($rules)) {
            $rules[] = ['type' => 'always', 'values' => []];
        }

        $types = [
            '' => esc_html__('Select type', 'king-addons'),
            'single_product' => esc_html__('Single Product', 'king-addons'),
            'product_archive' => esc_html__('Product Archive', 'king-addons'),
            'cart' => esc_html__('Cart', 'king-addons'),
            'checkout' => esc_html__('Checkout', 'king-addons'),
            'my_account' => esc_html__('My Account', 'king-addons'),
        ];

        $rule_types = [
            'always' => esc_html__('Always', 'king-addons'),
            'all_products' => esc_html__('All products', 'king-addons'),
            'product_in' => esc_html__('Specific products', 'king-addons'),
            'product_cat_in' => esc_html__('Product categories', 'king-addons'),
            'product_tag_in' => esc_html__('Product tags', 'king-addons'),
            'product_type_in' => esc_html__('Product types', 'king-addons'),
            'is_shop' => esc_html__('Shop page', 'king-addons'),
            'product_cat_archive_in' => esc_html__('Product category archives', 'king-addons'),
            'product_tag_archive_in' => esc_html__('Product tag archives', 'king-addons'),
            'cart' => esc_html__('Cart page', 'king-addons'),
            'checkout' => esc_html__('Checkout page', 'king-addons'),
            'my_account' => esc_html__('My Account page', 'king-addons'),
            // Legacy aliases kept for backward compatibility.
            'products' => esc_html__('Specific products (IDs)', 'king-addons') . ' (legacy)',
            'product_categories' => esc_html__('Product categories (IDs)', 'king-addons') . ' (legacy)',
            'product_tags' => esc_html__('Product tags (IDs)', 'king-addons') . ' (legacy)',
            'product_types' => esc_html__('Product types (slugs)', 'king-addons') . ' (legacy)',
            'shop' => esc_html__('Shop page', 'king-addons') . ' (legacy)',
            'product_cat_archives' => esc_html__('Product category archives (IDs)', 'king-addons') . ' (legacy)',
        ];

        // Admin assets for select2 UI and REST.
        wp_enqueue_style('select2');
        wp_enqueue_script('select2');
        wp_enqueue_script('wp-api');
        wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-script');
        wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-style');
        wp_localize_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-script',
            'KingAddonsWooBuilder',
            [
                'labels' => [
                    'valuePlaceholder' => esc_html__('Search or select…', 'king-addons'),
                ],
            ]
        );
        ?>
        <p>
            <label>
                <input type="checkbox" name="ka_woo_enabled" value="1" <?php checked($enabled); ?> />
                <?php esc_html_e('Enable template', 'king-addons'); ?>
            </label>
        </p>
        <p>
            <label for="ka_woo_template_type"><strong><?php esc_html_e('Template Type', 'king-addons'); ?></strong></label><br />
            <select name="ka_woo_template_type" id="ka_woo_template_type" style="width:100%;">
                <?php foreach ($types as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($template_type, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php if (!$this->can_use_pro()) : ?>
            <p class="king-addons-woo-builder-pro-lock">
                <?php esc_html_e('Cart, Checkout, and My Account templates require the Pro version.', 'king-addons'); ?>
            </p>
        <?php endif; ?>
        <p>
            <label for="ka_woo_priority"><strong><?php esc_html_e('Priority', 'king-addons'); ?></strong></label><br />
            <input type="number" min="0" name="ka_woo_priority" id="ka_woo_priority" value="<?php echo esc_attr($priority); ?>" style="width:100%;" />
        </p>
        <div id="ka-woo-rules">
            <strong><?php esc_html_e('Rules (AND)', 'king-addons'); ?></strong>
            <?php foreach ($rules as $index => $rule) : ?>
                <div class="ka-woo-rule" style="border:1px solid #e0e0e0;padding:8px;margin-top:8px;border-radius:4px;">
                    <?php
                    $rule_type_value = $rule['type'] ?? '';
                    if ($rule_type_value && !isset($rule_types[$rule_type_value])) {
                        $rule_types[$rule_type_value] = sprintf(
                            /* translators: %s: rule type slug */
                            esc_html__('Unknown rule (%s)', 'king-addons'),
                            esc_html($rule_type_value)
                        );
                    }
                    ?>
                    <select class="ka-woo-rule-type" name="ka_woo_rule_type[<?php echo esc_attr($index); ?>]" style="width:100%;margin-bottom:6px;">
                        <?php foreach ($rule_types as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($rule['type'] ?? '', $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    $rule_values = isset($rule['values']) && is_array($rule['values']) ? $rule['values'] : [];
                    $value_options = $this->get_rule_value_options($rule['type'] ?? '', $rule_values);
                    ?>
                    <select
                        class="ka-woo-rule-values"
                        name="ka_woo_rule_values[<?php echo esc_attr($index); ?>][]"
                        multiple
                        data-rule-type="<?php echo esc_attr($rule['type'] ?? ''); ?>"
                        data-selected-ids="<?php echo esc_attr(implode(',', array_map('strval', $rule_values))); ?>"
                        style="width:100%;"
                    >
                        <?php foreach ($value_options as $option_value => $option_label) : ?>
                            <option value="<?php echo esc_attr($option_value); ?>" <?php selected(true, in_array((string) $option_value, array_map('strval', $rule_values), true)); ?>>
                                <?php echo esc_html($option_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button ka-woo-remove-rule" style="margin-top:6px;"><?php esc_html_e('Remove', 'king-addons'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <p><button type="button" class="button" id="ka-woo-add-rule"><?php esc_html_e('Add rule', 'king-addons'); ?></button></p>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post.
     *
     * @return void
     */
    public function save_template_meta(int $post_id, WP_Post $post): void
    {
        if ('elementor_library' !== $post->post_type) {
            return;
        }

        if (!isset($_POST['king_addons_woo_builder_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['king_addons_woo_builder_meta_nonce'])), 'king_addons_woo_builder_meta')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $template_type = sanitize_text_field($_POST['ka_woo_template_type'] ?? '');
        update_post_meta($post_id, 'ka_woo_template_type', $template_type);
        if (in_array($template_type, ['single_product', 'product_archive', 'cart', 'checkout', 'my_account'], true)) {
            $document_type = class_exists(Woo_Document::class) ? Woo_Document::get_type() : 'king-addons-woo-builder';
            update_post_meta($post_id, '_elementor_template_type', $document_type);
        }

        $enabled = !empty($_POST['ka_woo_enabled']);
        $priority = isset($_POST['ka_woo_priority']) ? (int) $_POST['ka_woo_priority'] : 10;

        $types = $_POST['ka_woo_rule_type'] ?? [];
        $values = $_POST['ka_woo_rule_values'] ?? [];

        $rules = [];
        if (is_array($types) && is_array($values)) {
            foreach ($types as $idx => $type) {
                $type = sanitize_text_field($type);
                if ('' === $type) {
                    continue;
                }
                $val_raw = $values[$idx] ?? '';
                if (is_array($val_raw)) {
                    $vals = array_filter(array_map('trim', array_map('strval', $val_raw)));
                } else {
                    $vals = array_filter(array_map('trim', explode(',', (string) $val_raw)));
                }
                $parsed_vals = [];
                foreach ($vals as $v) {
                    if (is_numeric($v)) {
                        $parsed_vals[] = (int) $v;
                    } else {
                        $parsed_vals[] = sanitize_text_field($v);
                    }
                }
                $rules[] = [
                    'type' => $this->normalize_rule_type($type),
                    'values' => $parsed_vals,
                ];
            }
        }

        $conditions = [
            'enabled' => $enabled,
            'priority' => $priority,
            'rules' => $rules,
        ];

        update_post_meta($post_id, 'ka_woo_conditions', $conditions);
    }

    /**
     * Register Elementor document controls for Woo Builder templates.
     *
     * @param mixed $document Elementor document.
     *
     * @return void
     */
    public function register_document_controls($document): void
    {
        if (!is_object($document) || !method_exists($document, 'get_main_id')) {
            return;
        }

        $post_id = (int) $document->get_main_id();
        if ('elementor_library' !== get_post_type($post_id)) {
            return;
        }

        $template_type = (string) get_post_meta($post_id, 'ka_woo_template_type', true);
        $doc_type = class_exists(Woo_Document::class) ? Woo_Document::get_type() : 'king-addons-woo-builder';
        $elementor_type = (string) get_post_meta($post_id, '_elementor_template_type', true);
        if ('' === $template_type && $elementor_type !== $doc_type) {
            return;
        }

        $conditions = get_post_meta($post_id, 'ka_woo_conditions', true);
        $enabled = !empty($conditions['enabled']);
        $priority = isset($conditions['priority']) ? (int) $conditions['priority'] : 10;
        $rules = is_array($conditions['rules'] ?? null) ? $conditions['rules'] : [];
        if (empty($rules)) {
            $rules = [
                [
                    'type' => 'always',
                    'values' => [],
                ],
            ];
        }

        $types = [
            '' => esc_html__('Select type', 'king-addons'),
            'single_product' => esc_html__('Single Product', 'king-addons'),
            'product_archive' => esc_html__('Product Archive', 'king-addons'),
            'cart' => esc_html__('Cart', 'king-addons'),
            'checkout' => esc_html__('Checkout', 'king-addons'),
            'my_account' => esc_html__('My Account', 'king-addons'),
        ];

        $rule_types = [
            'always' => esc_html__('Always', 'king-addons'),
            'all_products' => esc_html__('All products', 'king-addons'),
            'product_in' => esc_html__('Specific products', 'king-addons'),
            'product_cat_in' => esc_html__('Product categories', 'king-addons'),
            'product_tag_in' => esc_html__('Product tags', 'king-addons'),
            'product_type_in' => esc_html__('Product types', 'king-addons'),
            'is_shop' => esc_html__('Shop page', 'king-addons'),
            'product_cat_archive_in' => esc_html__('Product category archives', 'king-addons'),
            'product_tag_archive_in' => esc_html__('Product tag archives', 'king-addons'),
            'cart' => esc_html__('Cart page', 'king-addons'),
            'checkout' => esc_html__('Checkout page', 'king-addons'),
            'my_account' => esc_html__('My Account page', 'king-addons'),
        ];

        $product_types = function_exists('wc_get_product_types')
            ? wc_get_product_types()
            : [
                'simple' => esc_html__('Simple', 'king-addons'),
                'variable' => esc_html__('Variable', 'king-addons'),
                'grouped' => esc_html__('Grouped', 'king-addons'),
                'external' => esc_html__('External/Affiliate', 'king-addons'),
            ];

        $repeater = new Repeater();
        $repeater->add_control(
            'rule_type',
            [
                'label' => esc_html__('Rule Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $rule_types,
                'default' => 'always',
            ]
        );
        $repeater->add_control(
            'values_products',
            [
                'label' => esc_html__('Products', 'king-addons'),
                'type' => 'king-addons-ajax-select2',
                'options' => 'ajaxselect2/getPostsByPostType',
                'query_slug' => 'product',
                'multiple' => true,
                'condition' => [
                    'rule_type' => ['product_in', 'products'],
                ],
            ]
        );
        $repeater->add_control(
            'values_product_cats',
            [
                'label' => esc_html__('Product categories', 'king-addons'),
                'type' => 'king-addons-ajax-select2',
                'options' => 'ajaxselect2/getTaxonomies',
                'query_slug' => 'product_cat',
                'multiple' => true,
                'condition' => [
                    'rule_type' => ['product_cat_in', 'product_categories', 'product_cat_archive_in', 'product_cat_archives'],
                ],
            ]
        );
        $repeater->add_control(
            'values_product_tags',
            [
                'label' => esc_html__('Product tags', 'king-addons'),
                'type' => 'king-addons-ajax-select2',
                'options' => 'ajaxselect2/getTaxonomies',
                'query_slug' => 'product_tag',
                'multiple' => true,
                'condition' => [
                    'rule_type' => ['product_tag_in', 'product_tags', 'product_tag_archive_in', 'product_tag_archives'],
                ],
            ]
        );
        $repeater->add_control(
            'values_product_types',
            [
                'label' => esc_html__('Product types', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'options' => $product_types,
                'multiple' => true,
                'condition' => [
                    'rule_type' => ['product_type_in', 'product_types'],
                ],
            ]
        );

        $default_rules = [];
        foreach ($rules as $rule) {
            $type = $this->normalize_rule_type($rule['type'] ?? '');
            $values = is_array($rule['values'] ?? null) ? $rule['values'] : [];
            $item = [
                'rule_type' => $type ?: 'always',
            ];
            switch ($type) {
                case 'product_in':
                case 'products':
                    $item['values_products'] = array_map('strval', $values);
                    break;
                case 'product_cat_in':
                case 'product_categories':
                case 'product_cat_archive_in':
                case 'product_cat_archives':
                    $item['values_product_cats'] = array_map('strval', $values);
                    break;
                case 'product_tag_in':
                case 'product_tags':
                case 'product_tag_archive_in':
                case 'product_tag_archives':
                    $item['values_product_tags'] = array_map('strval', $values);
                    break;
                case 'product_type_in':
                case 'product_types':
                    $item['values_product_types'] = array_map('strval', $values);
                    break;
            }
            $default_rules[] = $item;
        }

        if (empty($default_rules)) {
            $default_rules[] = ['rule_type' => 'always'];
        }

        $document->start_controls_section(
            'ka_woo_builder_settings',
            [
                'label' => esc_html__('Woo Builder', 'king-addons'),
                'tab' => Controls_Manager::TAB_SETTINGS,
            ]
        );

        $document->add_control(
            'ka_woo_template_type',
            [
                'label' => esc_html__('Template Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $types,
                'default' => $template_type ?: '',
            ]
        );

        if (!$this->can_use_pro()) {
            $document->add_control(
                'ka_woo_pro_notice',
                [
                    'label' => esc_html__('Pro', 'king-addons'),
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('Cart, Checkout, and My Account templates require Pro.', 'king-addons'),
                    'content_classes' => 'ka-theme-builder-readonly',
                ]
            );
        }

        $document->add_control(
            'ka_woo_enabled',
            [
                'label' => esc_html__('Enable template', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => $enabled ? 'yes' : 'no',
            ]
        );

        $document->add_control(
            'ka_woo_priority',
            [
                'label' => esc_html__('Priority', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => $priority,
                'min' => 0,
            ]
        );

        $document->add_control(
            'ka_woo_rules',
            [
                'label' => esc_html__('Rules (AND)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $default_rules,
                'title_field' => '{{{ rule_type }}}',
            ]
        );

        $document->end_controls_section();
    }

    /**
     * Persist Woo Builder meta from Elementor document settings.
     *
     * @param mixed $document Elementor document.
     * @param array $data     Saved data.
     *
     * @return void
     */
    public function handle_document_after_save($document, array $data): void
    {
        if (!is_object($document) || !method_exists($document, 'get_main_id')) {
            return;
        }

        $post_id = (int) $document->get_main_id();
        if ('elementor_library' !== get_post_type($post_id)) {
            return;
        }

        $valid_types = ['single_product', 'product_archive', 'cart', 'checkout', 'my_account'];
        $template_type = sanitize_text_field((string) $document->get_settings('ka_woo_template_type'));
        if (!in_array($template_type, $valid_types, true)) {
            $template_type = '';
        }

        // If template_type not in settings, check existing meta or URL parameter
        if ('' === $template_type) {
            // First check if meta already exists
            $existing_type = get_post_meta($post_id, 'ka_woo_template_type', true);
            if (!empty($existing_type) && in_array($existing_type, $valid_types, true)) {
                $template_type = $existing_type;
            } else {
                // Check URL parameter from referrer
                $template_type = $this->get_template_type_from_request();
            }
        }

        if ('' === $template_type && !get_post_meta($post_id, 'ka_woo_template_type', true)) {
            return;
        }

        if ('' === $template_type) {
            delete_post_meta($post_id, 'ka_woo_template_type');
        } else {
            update_post_meta($post_id, 'ka_woo_template_type', $template_type);
            $document_type = class_exists(Woo_Document::class) ? Woo_Document::get_type() : 'king-addons-woo-builder';
            update_post_meta($post_id, '_elementor_template_type', $document_type);
        }

        $enabled_setting = $document->get_settings('ka_woo_enabled');
        $enabled = ('yes' === $enabled_setting || true === $enabled_setting);
        $priority_setting = $document->get_settings('ka_woo_priority');
        $priority = is_numeric($priority_setting) ? (int) $priority_setting : 10;

        $raw_rules = $document->get_settings('ka_woo_rules');
        $rules = [];

        if (is_array($raw_rules)) {
            foreach ($raw_rules as $rule) {
                $type = $this->normalize_rule_type(sanitize_text_field($rule['rule_type'] ?? ''));
                if ('' === $type) {
                    continue;
                }
                $values = [];
                switch ($type) {
                    case 'product_in':
                    case 'products':
                        $values = $this->sanitize_rule_values($rule['values_products'] ?? [], true);
                        break;
                    case 'product_cat_in':
                    case 'product_categories':
                    case 'product_cat_archive_in':
                    case 'product_cat_archives':
                        $values = $this->sanitize_rule_values($rule['values_product_cats'] ?? [], true);
                        break;
                    case 'product_tag_in':
                    case 'product_tags':
                    case 'product_tag_archive_in':
                    case 'product_tag_archives':
                        $values = $this->sanitize_rule_values($rule['values_product_tags'] ?? [], true);
                        break;
                    case 'product_type_in':
                    case 'product_types':
                        $values = $this->sanitize_rule_values($rule['values_product_types'] ?? [], false);
                        break;
                    default:
                        $values = [];
                        break;
                }
                $rules[] = [
                    'type' => $type,
                    'values' => $values,
                ];
            }
        }

        if (empty($rules)) {
            $rules[] = [
                'type' => 'always',
                'values' => [],
            ];
        }

        $conditions = [
            'enabled' => $enabled,
            'priority' => $priority,
            'rules' => $rules,
        ];

        update_post_meta($post_id, 'ka_woo_conditions', $conditions);
    }

    /**
     * Template include override.
     *
     * @param string $template Default template.
     *
     * @return string
     */
    public function override_wc_template(string $template): string
    {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $template;
        }

        $context = Woo_Context::detect_context();
        if (null === $context) {
            return $template;
        }

        if (!$this->can_use_pro() && in_array($context, ['cart', 'checkout', 'my_account'], true)) {
            return $template;
        }

        $template_id = $this->get_active_template_for_context($context);
        $this->current_templates[$context] = $template_id ?: 0;
        $this->active_context = $template_id ? $context : null;

        if (!$template_id) {
            return $template;
        }

        $this->maybe_register_default_blocks();

        $wrapper = KING_ADDONS_PATH . 'includes/extensions/Woo_Builder/templates/' . $context . '.php';
        if (file_exists($wrapper)) {
            return $wrapper;
        }

        return $template;
    }

    /**
     * Get active template ID for context.
     *
     * @param string $context Context.
     *
     * @return int|null
     */
    public function get_active_template_for_context(string $context): ?int
    {
        $query = new WP_Query([
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
            'post_status' => 'publish', // Only published templates should be applied on frontend
            'meta_query' => [
                [
                    'key' => 'ka_woo_template_type',
                    'value' => $context,
                ],
            ],
        ]);

        if (!$query->have_posts()) {
            return null;
        }

        $candidates = [];

        foreach ($query->posts as $post) {
            $conditions = get_post_meta($post->ID, 'ka_woo_conditions', true);
            if (!is_array($conditions) || empty($conditions['enabled'])) {
                continue;
            }

            if (!$this->can_use_pro() && !in_array($context, ['single_product', 'product_archive'], true)) {
                continue;
            }

            if (!Woo_Context::match_conditions($context, $conditions)) {
                continue;
            }

            $priority = isset($conditions['priority']) ? (int) $conditions['priority'] : 10;
            $candidates[] = [
                'id' => (int) $post->ID,
                'priority' => $priority,
                'specificity' => $this->get_conditions_specificity_score($context, $conditions),
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        usort(
            $candidates,
            static function ($a, $b) {
                $spec = ($b['specificity'] ?? 0) <=> ($a['specificity'] ?? 0);
                if (0 !== $spec) {
                    return $spec;
                }

                $prio = ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10);
                if (0 !== $prio) {
                    return $prio;
                }

                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            }
        );

        // Free: limit one template per context by picking top; Pro may have multiple but we still pick top priority.
        return (int) $candidates[0]['id'];
    }

    /**
     * Compute how specific a condition set is for the given context.
     * Higher score = more specific.
     *
     * @param string              $context
     * @param array<string,mixed> $conditions
     *
     * @return int
     */
    private function get_conditions_specificity_score(string $context, array $conditions): int
    {
        if (empty($conditions['rules']) || !is_array($conditions['rules'])) {
            return 0;
        }

        $score = 0;

        foreach ($conditions['rules'] as $rule) {
            $type = (string) ($rule['type'] ?? '');
            $values = is_array($rule['values'] ?? null) ? $rule['values'] : [];
            $has_values = !empty(array_filter($values, static fn($v) => '' !== (string) $v));

            if ('single_product' === $context) {
                if (in_array($type, ['specific_product', 'product_in', 'products'], true) && $has_values) {
                    $score += 300;
                } elseif (in_array($type, ['product_cat', 'product_cat_in', 'product_categories'], true) && $has_values) {
                    $score += 200;
                } elseif (in_array($type, ['product_tag', 'product_tag_in', 'product_tags'], true) && $has_values) {
                    $score += 200;
                } elseif (in_array($type, ['all', 'all_products', 'always'], true)) {
                    $score += 100;
                }
            } elseif ('product_archive' === $context) {
                if (in_array($type, ['shop', 'is_shop'], true)) {
                    $score += 300;
                } elseif (in_array($type, ['product_cat', 'product_cat_archive_in', 'product_cat_archives', 'product_cat_in', 'product_categories'], true) && $has_values) {
                    $score += 200;
                } elseif (in_array($type, ['product_tag', 'product_tag_archive_in', 'product_tag_archives', 'product_tag_in', 'product_tags'], true) && $has_values) {
                    $score += 200;
                } elseif (in_array($type, ['all', 'always'], true)) {
                    $score += 100;
                }
            } else {
                if (in_array($type, ['cart', 'checkout', 'my_account'], true)) {
                    $score += 200;
                } elseif (in_array($type, ['all', 'always'], true)) {
                    $score += 100;
                }
            }
        }

        return $score;
    }

    /**
     * Filter to expose current template id.
     *
     * @param int         $template_id Template ID.
     * @param string|null $context     Context.
     *
     * @return int
     */
    public function filter_current_template_id(int $template_id, ?string $context = null): int
    {
        if ($context && !empty($this->current_templates[$context])) {
            return (int) $this->current_templates[$context];
        }
        return $template_id;
    }

    /**
     * Add body classes for active Woo Builder context.
     *
     * @param array<int,string> $classes Existing classes.
     *
     * @return array<int,string>
     */
    public function filter_body_class(array $classes): array
    {
        if (null !== $this->active_context) {
            $classes[] = 'king-addons-woo-builder';
            $classes[] = 'king-addons-woo-builder--' . $this->active_context;
            if ($this->can_use_pro()) {
                $classes[] = 'king-addons-woo-builder--pro';
            }
        }

        return $classes;
    }

    /**
     * Register default Pro blocks (upsells, quick links) once.
     *
     * @return void
     */
    private function maybe_register_default_blocks(): void
    {
        if ($this->default_blocks_registered || !$this->can_use_pro()) {
            return;
        }

        add_action('king_addons/woo_builder/after_render', [$this, 'render_default_block'], 10, 2);
        $this->default_blocks_registered = true;
    }

    /**
     * Render default Pro blocks for supported contexts.
     *
     * @param string $context     Context.
     * @param int    $template_id Template id.
     *
     * @return void
     */
    public function render_default_block(string $context, int $template_id): void
    {
        if (!$this->can_use_pro()) {
            return;
        }

        $disable_meta = get_post_meta($template_id, 'ka_woo_disable_autoblocks', true);
        if (!empty($disable_meta)) {
            return;
        }

        if ('checkout' === $context) {
            $this->render_checkout_upsells();
        } elseif ('cart' === $context) {
            $this->render_cart_cross_sells();
        } elseif ('my_account' === $context) {
            $this->render_account_quick_links();
        }
    }

    /**
     * Render checkout upsells block.
     *
     * @return void
     */
    private function render_checkout_upsells(): void
    {
        if (!function_exists('WC') || !WC()->cart || apply_filters('king_addons/woo_builder/disable_checkout_upsells', false)) {
            return;
        }

        $cart_items = WC()->cart->get_cart();
        $upsell_ids = [];
        foreach ($cart_items as $item) {
            $product = $item['data'] ?? null;
            if ($product && is_object($product) && method_exists($product, 'get_upsell_ids')) {
                $upsell_ids = array_merge($upsell_ids, $product->get_upsell_ids());
            }
        }
        $upsell_ids = array_values(array_unique(array_filter(array_map('absint', $upsell_ids))));

        /**
         * Allow custom upsell IDs (e.g., personalization/A/B).
         *
         * @param array<int> $upsell_ids Upsell product IDs.
         * @param array      $cart_items Cart contents.
         */
        $upsell_ids = apply_filters('king_addons/woo_builder/checkout_upsell_ids', $upsell_ids, $cart_items);

        if (empty($upsell_ids)) {
            // Fallback: same categories as cart items.
            $cat_ids = [];
            $tag_ids = [];
            foreach ($cart_items as $item) {
                $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                if ($product_id) {
                    $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
                    $cat_ids = array_merge($cat_ids, $terms);
                    $tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']);
                    $tag_ids = array_merge($tag_ids, $tags);
                }
            }
            $cat_ids = array_values(array_unique(array_filter(array_map('absint', $cat_ids))));
            $tag_ids = array_values(array_unique(array_filter(array_map('absint', $tag_ids))));
            if (!empty($cat_ids)) {
                $upsell_ids = get_posts(
                    [
                        'post_type' => 'product',
                        'fields' => 'ids',
                        'numberposts' => (int) apply_filters('king_addons/woo_builder/checkout_upsells_fallback_limit', 6),
                        'tax_query' => [
                            [
                                'taxonomy' => 'product_cat',
                                'field' => 'term_id',
                                'terms' => $cat_ids,
                            ],
                        ],
                    ]
                );
            } elseif (!empty($tag_ids)) {
                $upsell_ids = get_posts(
                    [
                        'post_type' => 'product',
                        'fields' => 'ids',
                        'numberposts' => (int) apply_filters('king_addons/woo_builder/checkout_upsells_tag_limit', 6),
                        'tax_query' => [
                            [
                                'taxonomy' => 'product_tag',
                                'field' => 'term_id',
                                'terms' => $tag_ids,
                            ],
                        ],
                    ]
                );
            }
        }

        if (empty($upsell_ids)) {
            return;
        }

        $limit = (int) apply_filters('king_addons/woo_builder/checkout_upsells_limit', 4);
        $query = new WP_Query(
            apply_filters(
                'king_addons/woo_builder/checkout_upsells_query_args',
                [
                'post_type' => 'product',
                'post__in' => $upsell_ids,
                'posts_per_page' => $limit,
                'orderby' => 'post__in',
                ]
            )
        );

        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $title = apply_filters('king_addons/woo_builder/checkout_upsells_title', esc_html__('You may also like', 'king-addons'));

        echo '<section class="ka-woo-checkout-upsells" aria-label="' . esc_attr($title) . '">';
        if ($title) {
            echo '<h3 class="ka-woo-checkout-upsells__title">' . esc_html($title) . '</h3>';
        }
        echo '<div class="ka-woo-checkout-upsells__grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            if (!$product) {
                continue;
            }
            $this->render_simple_product_card($product);
        }
        echo '</div>';
        echo '</section>';
        wp_reset_postdata();
    }

    /**
     * Render cart cross-sells fallback.
     *
     * @return void
     */
    private function render_cart_cross_sells(): void
    {
        if (!function_exists('woocommerce_cross_sell_display') || apply_filters('king_addons/woo_builder/disable_cart_cross_sells', false)) {
            return;
        }
        $cart_items = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart() : [];
        $suggested_ids = [];
        foreach ($cart_items as $item) {
            $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            if ($product_id) {
                $tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']);
                $suggested_ids = array_merge($suggested_ids, $tags);
            }
        }
        $suggested_ids = array_values(array_unique(array_filter(array_map('absint', $suggested_ids))));
        $suggested_args = [];
        if (!empty($suggested_ids)) {
            $suggested_args = [
                'post_type' => 'product',
                'posts_per_page' => (int) apply_filters('king_addons/woo_builder/cart_cross_sells_suggested_limit', 4),
                'tax_query' => [
                    [
                        'taxonomy' => 'product_tag',
                        'field' => 'term_id',
                        'terms' => $suggested_ids,
                    ],
                ],
            ];
        }

        /**
         * Allow overriding cross-sell query (e.g., custom tags/meta).
         *
         * @param array<string,mixed> $args Query args, empty for Woo default.
         * @param array               $cart_items Cart items.
         */
        $suggested_args = apply_filters('king_addons/woo_builder/cart_cross_sells_query_args', $suggested_args, $cart_items);

        $title = apply_filters('king_addons/woo_builder/cart_cross_sells_title', esc_html__('You may also like', 'king-addons'));
        echo '<section class="ka-woo-cart-cross-sells" aria-label="' . esc_attr($title) . '">';
        if ($title) {
            echo '<h3 class="ka-woo-cart-cross-sells__title">' . esc_html($title) . '</h3>';
        }
        if (!empty($suggested_args)) {
            $loop = new WP_Query($suggested_args);
            if ($loop->have_posts()) {
                echo '<div class="ka-woo-checkout-upsells__grid">';
                while ($loop->have_posts()) {
                    $loop->the_post();
                    $product = wc_get_product(get_the_ID());
                    if ($product) {
                        $this->render_simple_product_card($product);
                    }
                }
                echo '</div>';
            }
            wp_reset_postdata();
        } else {
            woocommerce_cross_sell_display(
                (int) apply_filters('king_addons/woo_builder/cart_cross_sells_limit', 4),
                (int) apply_filters('king_addons/woo_builder/cart_cross_sells_columns', 4)
            );
        }
        echo '</section>';
    }

    /**
     * Render My Account quick links.
     *
     * @return void
     */
    private function render_account_quick_links(): void
    {
        if (!function_exists('wc_get_account_endpoint_url') || apply_filters('king_addons/woo_builder/disable_account_quick_links', false)) {
            return;
        }

        $links = apply_filters(
            'king_addons/woo_builder/account_quick_links',
            [
            'orders' => esc_html__('Orders', 'king-addons'),
            'downloads' => esc_html__('Downloads', 'king-addons'),
            'edit-address' => esc_html__('Addresses', 'king-addons'),
            'payment-methods' => esc_html__('Payment Methods', 'king-addons'),
            'edit-account' => esc_html__('Account Details', 'king-addons'),
            ]
        );

        echo '<nav class="ka-woo-account-quick-links" aria-label="' . esc_attr__('Account quick links', 'king-addons') . '">';
        echo '<ul class="ka-woo-account-quick-links__list">';
        foreach ($links as $endpoint => $label) {
            $url = wc_get_account_endpoint_url($endpoint);
            if (!$url) {
                continue;
            }
            echo '<li class="ka-woo-account-quick-links__item">';
            echo '<a class="ka-woo-account-quick-links__link" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</nav>';

        /**
         * Hook to output additional custom blocks in My Account (Pro).
         *
         * @param array<string,string> $links Current quick links.
         */
        do_action('king_addons/woo_builder/account_after_quick_links', $links);
    }

    /**
     * Render a simple product card for upsells.
     *
     * @param \WC_Product $product Product instance.
     *
     * @return void
     */
    private function render_simple_product_card(\WC_Product $product): void
    {
        $link = get_permalink($product->get_id());
        $image = $product->get_image('woocommerce_thumbnail');
        $title = $product->get_name();
        $price = $product->get_price_html();

        echo '<article class="ka-woo-checkout-upsells__item">';
        echo '<a class="ka-woo-checkout-upsells__thumb" href="' . esc_url($link) . '">' . wp_kses_post($image) . '</a>';
        echo '<h4 class="ka-woo-checkout-upsells__title"><a href="' . esc_url($link) . '">' . esc_html($title) . '</a></h4>';
        if (!empty($price)) {
            echo '<div class="ka-woo-checkout-upsells__price">' . wp_kses_post($price) . '</div>';
        }
        echo $this->render_add_to_cart_button($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</article>';
    }

    /**
     * Render a controlled Add to Cart button without relying on Woo shortcode.
     *
     * @param \WC_Product $product Product instance.
     *
     * @return string
     */
    private function render_add_to_cart_button(\WC_Product $product): string
    {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return '<span class="ka-woo-checkout-upsells__add-to-cart button is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
        }

        if (function_exists('wp_enqueue_script') && function_exists('wp_script_is') && wp_script_is('wc-add-to-cart', 'registered')) {
            wp_enqueue_script('wc-add-to-cart');
        }

        $classes = [
            'ka-woo-checkout-upsells__add-to-cart',
            'button',
            'product_type_' . $product->get_type(),
        ];

        if ($product->supports('ajax_add_to_cart')) {
            $classes[] = 'ajax_add_to_cart';
            $classes[] = 'add_to_cart_button';
        }

        $attributes = [
            'href' => $product->add_to_cart_url(),
            'data-quantity' => 1,
            'data-product_id' => $product->get_id(),
            'data-product_sku' => $product->get_sku(),
            'rel' => 'nofollow',
            'class' => implode(' ', array_filter($classes)),
            'aria-label' => wp_strip_all_tags($product->add_to_cart_description()),
        ];

        return '<a ' . wc_implode_html_attributes($attributes) . '><span class="ka-woo-checkout-upsells__add-to-cart-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
    }

    /**
     * Prefill meta values when creating a new Elementor template via custom links.
     *
     * @param mixed       $value     Default meta value.
     * @param int         $object_id Object ID.
     * @param string      $meta_key  Meta key.
     * @param bool        $single    Whether meta is single.
     * @param string|null $meta_type Meta type.
     *
     * @return mixed
     */
    public function prefill_template_meta($value, int $object_id, string $meta_key, bool $single, ?string $meta_type)
    {
        if ('post' !== $meta_type) {
            return $value;
        }

        $requested_type = isset($_GET['ka_woo_template_type'])
            ? sanitize_text_field(wp_unslash($_GET['ka_woo_template_type']))
            : '';

        if (empty($requested_type)) {
            return $value;
        }

        $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';

        if ('elementor_library' !== $post_type) {
            return $value;
        }

        if ('ka_woo_template_type' === $meta_key) {
            return $requested_type;
        }

        if ('ka_woo_conditions' === $meta_key) {
            return [
                'enabled' => true,
                'priority' => 10,
                'rules' => [
                    [
                        'type' => 'always',
                        'values' => [],
                    ],
                ],
            ];
        }
        if ('_elementor_template_type' === $meta_key) {
            return class_exists(Woo_Document::class) ? Woo_Document::get_type() : 'king-addons-woo-builder';
        }

        return $value;
    }

    /**
     * Save template type meta when any post is inserted (catches auto-draft creation).
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an update.
     *
     * @return void
     */
    public function save_template_type_on_insert(int $post_id, \WP_Post $post, bool $update): void
    {
        // Only for elementor_library posts
        if ('elementor_library' !== $post->post_type) {
            return;
        }
        
        // Skip if meta already exists
        if (get_post_meta($post_id, 'ka_woo_template_type', true)) {
            return;
        }
        
        // Get template type from request or transient
        $template_type = $this->get_template_type_from_request();
        
        // If not found in request, check transient
        if (empty($template_type)) {
            $user_id = get_current_user_id();
            $transient_key = 'ka_woo_pending_template_type_' . $user_id;
            $template_type = get_transient($transient_key);
            if ($template_type) {
                delete_transient($transient_key);
            }
        }
        
        if (empty($template_type)) {
            return;
        }
        
        // Save the template type meta
        update_post_meta($post_id, 'ka_woo_template_type', $template_type);
        
        // Set Elementor template type
        $document_type = class_exists(Woo_Document::class) ? Woo_Document::get_type() : 'king-addons-woo-builder';
        update_post_meta($post_id, '_elementor_template_type', $document_type);
        
        // Set default conditions (enabled by default)
        $default_conditions = [
            'enabled' => true,
            'priority' => 10,
            'rules' => [
                [
                    'type' => 'always',
                    'values' => [],
                ],
            ],
        ];
        update_post_meta($post_id, 'ka_woo_conditions', $default_conditions);
    }

    /**
     * Set WooCommerce template meta when creating a new post from URL parameter.
     * This runs on admin_init to catch post creation before Elementor takes over.
     *
     * @return void
     */
    public function maybe_set_woo_template_meta_on_new_post(): void
    {
        global $pagenow;
        
        // Only on post-new.php for elementor_library
        if ('post-new.php' !== $pagenow) {
            return;
        }
        
        $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';
        if ('elementor_library' !== $post_type) {
            return;
        }
        
        // Get template type from URL
        $template_type = $this->get_template_type_from_request();
        if (empty($template_type)) {
            return;
        }
        
        // Store in session/transient for later use when the post is actually created
        $user_id = get_current_user_id();
        set_transient('ka_woo_pending_template_type_' . $user_id, $template_type, 300);
    }

    /**
     * Save template type from URL parameter when post is created.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an update.
     *
     * @return void
     */
    public function save_template_type_from_url(int $post_id, \WP_Post $post, bool $update): void
    {
        // Skip if meta already exists
        if (get_post_meta($post_id, 'ka_woo_template_type', true)) {
            return;
        }

        // Get template type from request or transient
        $template_type = $this->get_template_type_from_request();
        
        // If not found in request, check transient
        if (empty($template_type)) {
            $user_id = get_current_user_id();
            $transient_key = 'ka_woo_pending_template_type_' . $user_id;
            $template_type = get_transient($transient_key);
            if ($template_type) {
                delete_transient($transient_key);
            }
        }
        
        if (empty($template_type)) {
            return;
        }

        // Save the template type meta
        update_post_meta($post_id, 'ka_woo_template_type', $template_type);
        
        // Set Elementor template type
        $document_type = class_exists(Woo_Document::class) ? Woo_Document::get_type() : 'king-addons-woo-builder';
        update_post_meta($post_id, '_elementor_template_type', $document_type);
        
        // Set default conditions (enabled by default)
        $conditions = [
            'enabled' => true,
            'priority' => 10,
            'rules' => [
                [
                    'type' => 'always',
                    'values' => [],
                ],
            ],
        ];
        update_post_meta($post_id, 'ka_woo_conditions', $conditions);
    }

    /**
     * Get template type from current request or referrer URL.
     *
     * @return string Template type or empty string.
     */
    private function get_template_type_from_request(): string
    {
        $valid_types = ['single_product', 'product_archive', 'cart', 'checkout', 'my_account'];
        $template_type = '';
        
        // Check current request
        if (isset($_GET['ka_woo_template_type'])) {
            $template_type = sanitize_text_field(wp_unslash($_GET['ka_woo_template_type']));
        }
        
        // Check POST data
        if (empty($template_type) && isset($_POST['ka_woo_template_type'])) {
            $template_type = sanitize_text_field(wp_unslash($_POST['ka_woo_template_type']));
        }
        
        // Check referrer URL
        if (empty($template_type) && isset($_SERVER['HTTP_REFERER'])) {
            $referrer = wp_unslash($_SERVER['HTTP_REFERER']);
            $parsed = wp_parse_url($referrer);
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query_args);
                if (!empty($query_args['ka_woo_template_type'])) {
                    $template_type = sanitize_text_field($query_args['ka_woo_template_type']);
                }
            }
        }
        
        // Validate
        if (!in_array($template_type, $valid_types, true)) {
            return '';
        }
        
        // Check Pro restriction
        if (!$this->can_use_pro() && in_array($template_type, ['cart', 'checkout', 'my_account'], true)) {
            return '';
        }
        
        return $template_type;
    }

    /**
     * Check Pro availability.
     *
     * @return bool
     */
    private function can_use_pro(): bool
    {
        if (!function_exists('king_addons_freemius')) {
            return false;
        }

        $fs = king_addons_freemius();
        if (!is_object($fs) || !method_exists($fs, 'can_use_premium_code')) {
            return false;
        }

        return (bool) $fs->can_use_premium_code();
    }

    /**
     * Map rule values to human-readable labels for select pre-fill.
     *
     * @param string $type   Rule type.
     * @param array  $values Raw values.
     *
     * @return array<string,string>
     */
    private function get_rule_value_options(string $type, array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $options = [];
        $string_values = array_map('strval', $values);

        switch ($type) {
            case 'product_in':
            case 'products':
                $products = get_posts([
                    'post_type' => 'product',
                    'post__in' => array_map('intval', $values),
                    'numberposts' => -1,
                ]);
                foreach ($products as $product) {
                    $options[(string) $product->ID] = $product->post_title;
                }
                break;
            case 'product_cat_in':
            case 'product_cat_archive_in':
            case 'product_categories':
            case 'product_cat_archives':
                $terms = get_terms([
                    'taxonomy' => 'product_cat',
                    'include' => array_map('intval', $values),
                    'hide_empty' => false,
                ]);
                foreach ($terms as $term) {
                    $options[(string) $term->term_id] = $term->name;
                }
                break;
            case 'product_tag_in':
            case 'product_tag_archive_in':
            case 'product_tag_archives':
            case 'product_tags':
                $terms = get_terms([
                    'taxonomy' => 'product_tag',
                    'include' => array_map('intval', $values),
                    'hide_empty' => false,
                ]);
                foreach ($terms as $term) {
                    $options[(string) $term->term_id] = $term->name;
                }
                break;
            case 'product_type_in':
            case 'product_types':
                $types = [
                    'simple' => esc_html__('Simple', 'king-addons'),
                    'variable' => esc_html__('Variable', 'king-addons'),
                    'grouped' => esc_html__('Grouped', 'king-addons'),
                    'external' => esc_html__('External/Affiliate', 'king-addons'),
                ];
                foreach ($string_values as $v) {
                    if (isset($types[$v])) {
                        $options[$v] = $types[$v];
                    }
                }
                break;
            default:
                foreach ($string_values as $v) {
                    $options[$v] = $v;
                }
        }

        return $options;
    }

    /**
     * Sanitize rule values from Elementor document settings.
     *
     * @param mixed $values  Raw values.
     * @param bool  $numeric Whether values should be numeric IDs.
     *
     * @return array<int|string>
     */
    private function sanitize_rule_values($values, bool $numeric): array
    {
        if (is_string($values)) {
            $values = explode(',', $values);
        }

        if (!is_array($values)) {
            return [];
        }

        $values = array_filter(array_map('strval', $values), 'strlen');

        if ($numeric) {
            $values = array_values(array_unique(array_filter(array_map('absint', $values))));
        } else {
            $values = array_values(array_unique(array_filter(array_map('sanitize_text_field', $values), 'strlen')));
        }

        return $values;
    }

    /**
     * Normalize legacy rule type slugs to current ones.
     *
     * @param string $type Rule type.
     *
     * @return string
     */
    private function normalize_rule_type(string $type): string
    {
        $map = [
            'products' => 'product_in',
            'product_categories' => 'product_cat_in',
            'product_tags' => 'product_tag_in',
            'product_types' => 'product_type_in',
            'shop' => 'is_shop',
            'product_cat_archives' => 'product_cat_archive_in',
            'product_tag_archives' => 'product_tag_archive_in',
        ];

        return $map[$type] ?? $type;
    }
}
