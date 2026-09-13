<?php
/**
 * King Addons dynamic tag.
 *
 * @package King_Addons
 */

namespace King_Addons\Dynamic_Tags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;
use King_Addons\Dynamic_Tags;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Value of an Advanced Custom Fields field.
 *
 * Only registered while ACF is active - see King_Addons\Dynamic_Tags::has_acf().
 */
class ACF_Field extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-acf-field';
    }

    public function get_title()
    {
        return $this->ka_title_with_pro(esc_html__('ACF Field', 'king-addons'));
    }

    public function get_categories()
    {
        return [Module::TEXT_CATEGORY, Module::POST_META_CATEGORY, Module::NUMBER_CATEGORY];
    }

    /**
     * Every text-ish field ACF knows about, as control options.
     *
     * @return array<string,string>
     */
    public static function ka_field_options(): array
    {
        return Dynamic_Tags::acf_field_options([
            'text', 'textarea', 'number', 'range', 'email', 'url', 'password',
            'select', 'checkbox', 'radio', 'button_group', 'true_false',
            'date_picker', 'date_time_picker', 'time_picker', 'color_picker',
            'wysiwyg', 'oembed', 'link', 'post_object', 'page_link', 'taxonomy', 'user',
        ]);
    }

    protected function register_controls()
    {
        $this->ka_register_pro_notice();

        $options = self::ka_field_options();

        $this->add_control(
            'field_key',
            [
                'label' => esc_html__('Field', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => ['' => esc_html__('- Select -', 'king-addons')] + $options,
                'default' => '',
            ]
        );

        $this->add_control(
            'manual_key',
            [
                'label' => esc_html__('Or field name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Use this for fields ACF only exposes at runtime, such as fields inside options pages.', 'king-addons'),
            ]
        );

        $this->add_control(
            'separator_glue',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => ', ',
                'description' => esc_html__('Joins multi-value fields.', 'king-addons'),
            ]
        );
    }

    public function render()
    {
        if ($this->ka_pro_locked()) {
            return;
        }

        $key = (string) $this->get_settings('manual_key');
        if ('' === trim($key)) {
            $key = (string) $this->get_settings('field_key');
        }

        $key = trim($key);
        if ('' === $key || !function_exists('get_field')) {
            $this->ka_echo('');
            return;
        }

        $value = Dynamic_Tags::acf_value($key, Dynamic_Tags::acf_object_id($this->ka_get_post_id()));
        $glue = (string) $this->get_settings('separator_glue');

        $this->ka_echo(Dynamic_Tags::acf_stringify($value, $glue));
    }
}
