<?php
namespace King_Addons;

use Elementor\Controls_Manager as Controls;
use Elementor\Element_Base;

if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/Display_Rules.php';
require_once __DIR__ . '/Display_Access_Plan.php';

/** One editor entry point; legacy setting names and runtime remain compatible. */
class Display_Access
{
    const TAB = 'king-display-access';
    private static bool $registered = false;
    private static array $features = [];

    public static function register(string $feature): void
    {
        self::$features[$feature] = true;
        if (self::$registered) { return; }
        self::$registered = true;
        $tab = static function () { Controls::add_tab(self::TAB, __('Display', 'king-addons')); };
        if (did_action('elementor/init')) { $tab(); } else { add_action('elementor/init', $tab); }
        add_action('elementor/editor/after_enqueue_scripts', static function () {
            wp_enqueue_style('king-display-access', KING_ADDONS_URL . 'includes/helpers/display-access.css', [], self::asset_version('includes/helpers/display-access.css'));
            wp_enqueue_script('king-display-access-editor', KING_ADDONS_URL . 'includes/helpers/display-access-editor.js', ['jquery'], self::asset_version('includes/helpers/display-access-editor.js'), true);
            wp_localize_script('king-display-access-editor', 'kingDisplayEditor', ['ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('king_display_editor'), 'hasPro' => Display_Access_Plan::has_pro(), 'upgradeUrl' => Display_Access_Plan::upgrade_url(), 'upgradeLabel' => __('Get King Addons Pro', 'king-addons')]);
        });
        add_action('elementor/frontend/after_enqueue_styles', static function () { wp_enqueue_style('king-display-access', KING_ADDONS_URL . 'includes/helpers/display-access.css', [], self::asset_version('includes/helpers/display-access.css')); });
        add_action('wp_ajax_king_addons_display_preview', [self::class, 'preview']);
    }

    public static function asset_version(string $path): string
    {
        return KING_ADDONS_VERSION . '.' . filemtime(KING_ADDONS_PATH . $path);
    }

    public static function intro(Element_Base $element): void
    {
        if ($element->get_controls('ka_display_start')) { return; }
        $element->start_controls_section('ka_display_start', ['label' => __('Quick setup', 'king-addons'), 'tab' => self::TAB]);
        $element->add_control('ka_display_help', ['type' => Controls::RAW_HTML, 'raw' => '<p class="ka-display-intro">' . esc_html__('Choose how this element appears or opens.', 'king-addons') . '</p>']);
        $presets = [
            'rules' => ['label' => __('Visibility rules', 'king-addons'), 'hint' => __('Choose who sees it.', 'king-addons'), 'icon' => 'eicon-eye'],
            'password' => ['label' => __('Password access', 'king-addons'), 'hint' => __('Password or selected roles.', 'king-addons'), 'icon' => 'eicon-lock'],
            'locker' => ['label' => __('Unlock after a form', 'king-addons'), 'hint' => __('Unlock on form submission.', 'king-addons'), 'icon' => 'eicon-form-horizontal'],
        ];
        foreach ($presets as $key => $preset) {
            if (!isset(self::$features[$key === 'rules' ? 'cd' : 'pc'])) { continue; }
            $pro = $key !== 'rules';
            $text = '<i class="ka-display-action-icon ' . esc_attr($preset['icon']) . '"></i>'
                . '<span class="ka-display-action-copy"><span class="ka-display-action-title">' . esc_html($preset['label'])
                . ($pro ? '<span class="ka-display-action-pro">Pro</span>' : '') . '</span>'
                . '<span class="ka-display-action-hint">' . esc_html($preset['hint']) . '</span></span>'
                . '<i class="ka-display-action-arrow eicon-chevron-right"></i>';
            $element->add_control('ka_display_preset_' . $key, ['type' => Controls::BUTTON, 'text' => $text, 'event' => 'king:display:' . $key, 'label_block' => true]);
        }
        $element->add_control('ka_display_next', ['type' => Controls::RAW_HTML, 'raw' => '<p class="ka-display-next">' . esc_html__('Choose an option to open its settings below.', 'king-addons') . '</p>']);
        $element->end_controls_section();
    }

    public static function rule_setup_control(Element_Base $element): void
    {
        $element->add_control('cd_rules_mode', ['label' => __('Rule setup', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'legacy', 'options' => ['legacy' => __('Classic conditions', 'king-addons'), 'rules' => Display_Access_Plan::pro_label(__('Advanced rule builder', 'king-addons'))], 'condition' => ['cd_enable' => 'yes'], 'description' => __('Switching setup keeps your saved conditions.', 'king-addons')]);
        self::upgrade_control($element, 'cd', ['cd_enable' => 'yes', 'cd_rules_mode' => 'rules']);
    }

    public static function upgrade_control(Element_Base $element, string $feature, array $condition = [], string $suffix = ''): void
    {
        if (Display_Access_Plan::has_pro()) { return; }
        $title = $feature === 'cd' ? __('Unlock advanced display rules', 'king-addons') : __('Unlock password and form access', 'king-addons');
        $description = $feature === 'cd'
            ? __('Get ACF and post fields, AND/OR groups, scheduling, advanced targeting, rule explanations and interactive display controls.', 'king-addons')
            : __('Add passwords, role-or-password access, form unlocks, scheduling, URL and WooCommerce conditions.', 'king-addons');
        $raw = '<div class="ka-display-upgrade"><span class="ka-display-action-pro">Pro</span><strong>' . esc_html($title) . '</strong><p>' . esc_html($description) . '</p><a href="' . esc_url(Display_Access_Plan::upgrade_url()) . '" target="_blank" rel="noopener">' . esc_html__('Get King Addons Pro', 'king-addons') . '</a><small>' . esc_html__('Saved Pro settings are kept. Elements using Pro access conditions stay hidden until Pro is restored or you explicitly change their protection settings.', 'king-addons') . '</small></div>';
        $element->add_control('ka_display_upgrade_' . $feature . $suffix, ['type' => Controls::RAW_HTML, 'raw' => $raw, 'condition' => $condition]);
    }

    public static function plan_controls(Element_Base $element, string $section): void
    {
        $has_pro = Display_Access_Plan::has_pro();
        $badges = ['cd_cond_browser_enable', 'cd_cond_date_enable', 'cd_cond_time_enable', 'cd_cond_url_enable', 'cd_cond_ref_enable', 'cd_cond_woo_enable', 'cd_cond_cookie_enable', 'cd_cond_lang_enable', 'cd_reveal', 'cd_hide_empty'];
        foreach ($element->get_controls() as $key => $control) {
            if (($control['section'] ?? '') !== $section) { continue; }
            $updates = [];
            if (in_array($key, $badges, true) && isset($control['label'])) { $updates['label'] = Display_Access_Plan::pro_label($control['label']); }
            if (!$has_pro && Display_Access_Plan::pro_control($key)) { $updates['classes'] = trim(($control['classes'] ?? '') . ' ka-display-pro-only'); }
            if ($updates) { $element->update_control($key, $updates); }
        }
    }

    public static function editor_field_layout(Element_Base $element, string $section): void
    {
        foreach ($element->get_controls() as $key => $control) {
            if (($control['section'] ?? '') === $section && in_array($control['type'] ?? '', [Controls::SELECT, Controls::SELECT2, Controls::TEXT, Controls::DATE_TIME], true)) {
                $layout = ['label_block' => true];
                if (($control['type'] ?? '') === Controls::TEXT && !in_array($key, ['cd_reveal_label', 'protected_content_password_button'], true)) {
                    $layout['ai'] = ['active' => false];
                    $layout['classes'] = trim(($control['classes'] ?? '') . ' ka-display-technical-field');
                }
                $element->update_control($key, $layout);
            }
        }
    }

    public static function rules_controls(Element_Base $element): void
    {
        $rule = new \Elementor\Repeater();
        $rule->add_control('label', ['label' => __('Rule name (optional)', 'king-addons'), 'type' => Controls::TEXT]);
        $rule->add_control('join', ['label' => __('Combine with previous rules', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'and', 'options' => ['and' => __('AND — also require this', 'king-addons'), 'or' => __('OR — start another group', 'king-addons')], 'description' => __('Ignored for the first rule. AND stays in the same group; OR starts another way to show the block.', 'king-addons')]);
        $rule->add_control('type', ['label' => __('What to check', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'login', 'options' => Display_Rules::type_options()]);
        $rule->add_control('source', ['label' => __('Read field from', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'item', 'options' => ['item' => __('Current item (including Loop)', 'king-addons'), 'page' => __('Current page', 'king-addons')], 'condition' => ['type' => ['meta', 'acf']]]);
        $rule->add_control('key', ['label' => __('Field / parameter / cookie name', 'king-addons'), 'type' => Controls::TEXT, 'placeholder' => 'campaign', 'condition' => ['type' => ['query', 'meta', 'cookie']]]);
        $acf = [];
        if (function_exists('acf_get_field_groups')) {
            foreach (acf_get_field_groups() as $group) { foreach ((array) acf_get_fields($group) as $field) { if (!empty($field['key'])) { $acf[$field['key']] = $group['title'] . ' / ' . $field['label']; } } }
        }
        $rule->add_control('acf_field', ['label' => __('Choose an ACF field', 'king-addons'), 'type' => Controls::SELECT2, 'label_block' => true, 'options' => $acf, 'condition' => ['type' => 'acf'], 'description' => __('Fields come from ACF field groups. ACF must be active.', 'king-addons')]);
        $rule->add_control('operator', ['label' => __('Comparison', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'equals', 'options' => ['empty' => __('Is empty / missing', 'king-addons'), 'not_empty' => __('Is not empty / exists', 'king-addons'), 'equals' => __('Equals', 'king-addons'), 'not_equals' => __('Does not equal', 'king-addons'), 'contains' => __('Contains', 'king-addons')], 'condition' => ['type' => ['query', 'meta', 'acf', 'cookie', 'referrer']]]);
        $rule->add_control('value', ['label' => __('Compare with', 'king-addons'), 'type' => Controls::TEXT, 'condition' => ['type' => ['query', 'meta', 'acf', 'cookie', 'referrer'], 'operator' => ['equals', 'not_equals', 'contains']]]);
        $rule->add_control('browser', ['label' => __('Browser', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'chrome', 'options' => ['chrome'=>'Chrome', 'firefox'=>'Firefox', 'safari'=>'Safari', 'edge'=>'Edge', 'opera'=>'Opera', 'ie'=>'Internet Explorer'], 'condition' => ['type'=>'browser']]);
        $rule->add_control('language', ['label' => __('Site language', 'king-addons'), 'type' => Controls::SELECT2, 'options' => Display_Rules::language_options(), 'condition' => ['type'=>'language'], 'description' => __('Uses the active WPML / Polylang language, or the WordPress locale.', 'king-addons')]);
        $rule->add_control('woo_check', ['label' => __('WooCommerce condition', 'king-addons'), 'type' => Controls::SELECT, 'default'=>'cart_total', 'options'=>['cart_total'=>__('Minimum cart total', 'king-addons'), 'in_cart'=>__('Products in cart', 'king-addons'), 'purchased'=>__('Previously purchased products', 'king-addons')], 'condition'=>['type'=>'woo']]);
        $rule->add_control('min_total', ['label'=>__('Minimum amount', 'king-addons'), 'type'=>Controls::NUMBER, 'min'=>0, 'default'=>0, 'condition'=>['type'=>'woo','woo_check'=>'cart_total']]);
        $rule->add_control('product_ids', ['label'=>__('Product IDs', 'king-addons'), 'type'=>Controls::TEXT, 'placeholder'=>'123, 456', 'condition'=>['type'=>'woo','woo_check'=>['in_cart','purchased']], 'description'=>__('Comma-separated product IDs.', 'king-addons')]);
        $rule->add_control('product_match', ['label'=>__('Match products', 'king-addons'), 'type'=>Controls::SELECT, 'default'=>'any', 'options'=>['any'=>__('Any listed product', 'king-addons'), 'all'=>__('All listed products', 'king-addons')], 'condition'=>['type'=>'woo','woo_check'=>['in_cart','purchased']]]);
        $rule->add_control('login_mode', ['label' => __('Visitor', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'logged_in', 'options' => ['logged_in' => __('Logged in', 'king-addons'), 'guest' => __('Guest', 'king-addons')], 'condition' => ['type' => 'login']]);
        $rule->add_control('role', ['label' => __('Allowed role', 'king-addons'), 'type' => Controls::SELECT, 'options' => wp_roles()->get_names(), 'condition' => ['type' => 'role']]);
        $rule->add_control('device', ['label' => __('Device', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'desktop', 'options' => ['desktop' => __('Desktop', 'king-addons'), 'tablet' => __('Tablet', 'king-addons'), 'mobile' => __('Mobile', 'king-addons')], 'condition' => ['type' => 'device']]);
        foreach (['from' => __('From date', 'king-addons'), 'to' => __('Until date', 'king-addons')] as $key => $label) { $rule->add_control($key, ['label' => $label, 'type' => Controls::DATE_TIME, 'condition' => ['type' => 'date']]); }
        foreach (['time_from' => __('From time', 'king-addons'), 'time_to' => __('Until time', 'king-addons')] as $key => $label) { $rule->add_control($key, ['label' => $label, 'type' => Controls::TEXT, 'placeholder' => '09:00', 'condition' => ['type' => 'time']]); }
        foreach (['annual_from' => __('From (MM-DD)', 'king-addons'), 'annual_to' => __('Until (MM-DD)', 'king-addons')] as $key => $label) { $rule->add_control($key, ['label' => $label, 'type' => Controls::TEXT, 'placeholder' => '12-20', 'condition' => ['type' => 'annual']]); }
        foreach ($rule->get_controls() as $key => $control) {
            if (in_array($control['type'] ?? '', [Controls::SELECT, Controls::SELECT2, Controls::TEXT, Controls::DATE_TIME], true)) {
                $layout = ['label_block' => true];
                if (($control['type'] ?? '') === Controls::TEXT) {
                    $layout['ai'] = ['active' => false];
                    $layout['classes'] = trim(($control['classes'] ?? '') . ' ka-display-technical-field');
                }
                $rule->update_control($key, $layout);
            }
        }
        $types = $rule->get_controls('type')['options'] ?? [];
        $element->add_control('cd_rules', ['label' => __('Show this block when…', 'king-addons'), 'type' => Controls::REPEATER, 'fields' => $rule->get_controls(), 'title_field' => '{{{ label || (' . wp_json_encode($types) . ')[type] || type }}}', 'condition' => ['cd_enable' => 'yes', 'cd_rules_mode' => 'rules'], 'description' => __('No rules: always show. Times use the WordPress site timezone. Yearly and daily windows may cross New Year or midnight.', 'king-addons')]);
        $element->add_control('cd_reveal', ['label' => __('Reveal after a click', 'king-addons'), 'type' => Controls::SWITCHER, 'condition' => ['cd_enable' => 'yes'], 'description' => __('Rules must match first. Then the visitor clicks a button to expand this block.', 'king-addons')]);
        $element->add_control('cd_reveal_label', ['label' => __('Reveal button text', 'king-addons'), 'type' => Controls::TEXT, 'default' => __('Show content', 'king-addons'), 'condition' => ['cd_enable' => 'yes', 'cd_reveal' => 'yes']]);
        if (in_array($element->get_type(), ['container', 'section', 'column'], true)) {
            $element->add_control('cd_hide_empty', ['label' => __('Hide empty container', 'king-addons'), 'type' => Controls::SWITCHER, 'description' => __('Removes background and spacing when all children are hidden by rules. Messages and forms count as visible content.', 'king-addons')]);
        }
        $element->add_control('ka_display_visitor', ['condition' => ['cd_enable' => 'yes'], 'label' => __('Check as', 'king-addons'), 'type' => Controls::SELECT, 'default' => 'current', 'options' => ['current' => __('Current visitor', 'king-addons'), 'guest' => __('Guest (guided rules only)', 'king-addons')]]);
        $element->add_control('ka_display_check_button', ['condition' => ['cd_enable' => 'yes'], 'type' => Controls::BUTTON, 'text' => __('Check rules', 'king-addons'), 'event' => 'king:display:check']);
        $element->add_control('ka_display_check', ['condition' => ['cd_enable' => 'yes'], 'type' => Controls::RAW_HTML, 'raw' => '<div class="ka-display-report" data-state="idle"><strong>' . esc_html__('Rule preview', 'king-addons') . '</strong><p>' . esc_html__('Check which rules match before publishing.', 'king-addons') . '</p></div>']);
    }

    public static function preview(): void
    {
        check_ajax_referer('king_display_editor', 'nonce');
        $id = absint($_POST['post_id'] ?? 0);
        if (!$id || !current_user_can('edit_post', $id)) { wp_send_json_error(['message' => __('Open an editable Elementor document first.', 'king-addons')]); }
        $settings = json_decode(wp_unslash($_POST['settings'] ?? '{}'), true);
        if (!is_array($settings)) { wp_send_json_error(['message' => __('Unable to read these rules.', 'king-addons')]); }
        if (!Display_Access_Plan::has_pro()) {
            wp_send_json_success(['requires_pro' => true, 'message' => __('This setup requires King Addons Pro. Your saved settings are kept; elements using Pro access conditions remain hidden while Pro is unavailable.', 'king-addons'), 'upgrade_url' => Display_Access_Plan::upgrade_url()]);
        }
        if (($_POST['kind'] ?? '') === 'form') {
            $template = absint($settings['protected_content_locker_template'] ?? 0);
            if (!$template) {
                $shortcode = (string) ($settings['protected_content_locker_form'] ?? '');
                wp_send_json_success(['message' => $shortcode !== '' ? __('A shortcode is configured. Verify its provider is active and complete a frontend submission before publishing.', 'king-addons') : __('Choose a form template, or enter a form shortcode under More options.', 'king-addons')]);
            }
            $data = json_decode((string)get_post_meta($template, '_elementor_data', true), true);
            $forms = [];
            $visit = static function ($elements) use (&$visit, &$forms) {
                foreach ((array)$elements as $element) {
                    if (!is_array($element)) { continue; }
                    if (($element['widgetType'] ?? '') === 'king-addons-form-builder') { $forms[] = in_array('submissions', (array)($element['settings']['submit_actions'] ?? ['email']), true); }
                    $visit($element['elements'] ?? []);
                }
            };
            $visit($data);
            $ready = $forms && !in_array(false, $forms, true);
            wp_send_json_success(['template_id' => $template, 'ready' => (bool)$ready, 'message' => !$forms ? __('This template has no King Addons form. Choose a template containing Form Builder.', 'king-addons') : ($ready ? __('Form setup is ready: Submissions is enabled. Test the form on the frontend before publishing.', 'king-addons') : __('Open this form template and enable Submissions in Actions After Submit. An Email-only form does not open the locker.', 'king-addons'))]);
        }
        if (($settings['cd_rules_mode'] ?? 'legacy') !== 'rules') { wp_send_json_success(['legacy' => true, 'message' => __('Original conditions are still active. Their saved values remain in the section below. Choose Guided rule builder to use per-rule explanations.', 'king-addons')]); }
        $context = ['post_id' => $id, 'page_id' => $id];
        if (($_POST['visitor'] ?? '') === 'guest') { $context['logged_in'] = false; $context['roles'] = []; }
        $query = []; parse_str(substr(wp_unslash($_POST['query'] ?? ''), 0, 2048), $query); $context['query'] = $query;
        $result = Display_Rules::evaluate($settings['cd_rules'] ?? [], $context);
        if (($settings['cd_invert'] ?? '') === 'yes') { $result['pass'] = !$result['pass']; }
        wp_send_json_success($result);
    }
}
