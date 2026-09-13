<?php
/**
 * King Addons dynamic tag.
 *
 * @package King_Addons
 */

namespace King_Addons\Dynamic_Tags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Any post meta value, by key.
 */
class Custom_Field extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-custom-field';
    }

    public function get_title()
    {
        return $this->ka_title_with_pro(esc_html__('Custom Field', 'king-addons'));
    }

    public function get_categories()
    {
        return [Module::TEXT_CATEGORY, Module::POST_META_CATEGORY, Module::NUMBER_CATEGORY];
    }

    protected function register_controls()
    {
        $this->ka_register_pro_notice();

        $this->add_control(
            'meta_key',
            [
                'label' => esc_html__('Meta key', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '_my_field',
            ]
        );

        $this->add_control(
            'source',
            [
                'label' => esc_html__('Read from', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'post' => esc_html__('Current post', 'king-addons'),
                    'term' => esc_html__('Current term', 'king-addons'),
                    'user' => esc_html__('Post author', 'king-addons'),
                ],
                'default' => 'post',
            ]
        );
    }

    public function render()
    {
        if ($this->ka_pro_locked()) {
            return;
        }

        $key = (string) $this->get_settings('meta_key');
        if ('' === trim($key)) {
            $this->ka_echo('');
            return;
        }

        // A meta key is author input that reaches the database, so keep it to
        // the characters WordPress itself allows.
        $key = preg_replace('/[^A-Za-z0-9_\-]/', '', $key);
        if ('' === $key) {
            $this->ka_echo('');
            return;
        }

        $source = (string) $this->get_settings('source');
        $value = '';

        if ('term' === $source) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $value = get_term_meta($term->term_id, $key, true);
            }
        } elseif ('user' === $source) {
            $post = $this->ka_get_post();
            if ($post && $post->post_author) {
                $value = get_user_meta((int) $post->post_author, $key, true);
            }
        } else {
            $id = $this->ka_get_post_id();
            if ($id) {
                $value = get_post_meta($id, $key, true);
            }
        }

        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map(static function ($v) {
                return is_scalar($v) ? (string) $v : '';
            }, $value)));
        }

        $this->ka_echo(is_scalar($value) ? (string) $value : '');
    }
}
