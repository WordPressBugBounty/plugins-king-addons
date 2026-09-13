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
 * Image stored in an Advanced Custom Fields field.
 *
 * Only registered while ACF is active - see King_Addons\Dynamic_Tags::has_acf().
 */
class ACF_Image extends Image_Tag
{
    public function get_name()
    {
        return 'king-addons-acf-image';
    }

    public function get_title()
    {
        return $this->ka_title_with_pro(esc_html__('ACF Image', 'king-addons'));
    }

    protected function register_controls()
    {
        $this->ka_register_pro_notice();

        $options = Dynamic_Tags::acf_field_options(['image', 'file']);

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
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
            ]
        );
    }

    public function get_value(array $options = [])
    {
        if ($this->ka_pro_locked()) {
            return ['id' => 0, 'url' => ''];
        }

        $key = (string) $this->get_settings('manual_key');
        if ('' === trim($key)) {
            $key = (string) $this->get_settings('field_key');
        }

        $key = trim($key);
        $value = ('' !== $key && function_exists('get_field'))
            ? Dynamic_Tags::acf_value($key, Dynamic_Tags::acf_object_id($this->ka_get_post_id()))
            : null;

        // ACF returns an id, a URL or the whole attachment array depending on
        // the field's return format.
        if (is_array($value)) {
            $id = isset($value['ID']) ? (int) $value['ID'] : (isset($value['id']) ? (int) $value['id'] : 0);
            $url = isset($value['url']) ? (string) $value['url'] : '';
            if ($id > 0 || '' !== $url) {
                return $this->ka_image_value($id, $url);
            }
        } elseif (is_numeric($value)) {
            return $this->ka_image_value((int) $value);
        } elseif (is_string($value) && '' !== $value) {
            return ['id' => attachment_url_to_postid($value), 'url' => $value];
        }

        $fallback = $this->get_settings('fallback');
        if (is_array($fallback) && !empty($fallback['url'])) {
            return [
                'id' => isset($fallback['id']) ? (int) $fallback['id'] : 0,
                'url' => (string) $fallback['url'],
            ];
        }

        return ['id' => 0, 'url' => ''];
    }
}
