<?php
/**
 * 
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
 * The published or modified date of the post being displayed.
 */
class Post_Date extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-post-date';
    }

    public function get_title()
    {
        return esc_html__('Post Date', 'king-addons');
    }

    public function get_categories()
    {
        return [Module::TEXT_CATEGORY, Module::DATETIME_CATEGORY];
    }

    protected function register_controls()
    {
        $this->add_control(
            'date_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'published' => esc_html__('Published', 'king-addons'),
                    'modified' => esc_html__('Modified', 'king-addons'),
                ],
                'default' => 'published',
            ]
        );

        $this->add_control(
            'format',
            [
                'label' => esc_html__('Format', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'default' => esc_html__('Site default', 'king-addons'),
                    'human' => esc_html__('Time ago', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'default' => 'default',
            ]
        );

        $this->add_control(
            'custom_format',
            [
                'label' => esc_html__('Custom format', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => 'F j, Y',
                'condition' => ['format' => 'custom'],
            ]
        );
    }

    public function render()
    {
        $post = $this->ka_get_post();
        if (!$post) {
            $this->ka_echo('');
            return;
        }

        $modified = 'modified' === $this->get_settings('date_type');
        $format = (string) $this->get_settings('format');

        if ('human' === $format) {
            $timestamp = $modified ? get_post_modified_time('U', true, $post) : get_post_time('U', true, $post);
            $value = $timestamp
                /* translators: %s: human-readable time difference. */
                ? sprintf(esc_html__('%s ago', 'king-addons'), human_time_diff((int) $timestamp, time()))
                : '';
            $this->ka_echo($value);
            return;
        }

        $date_format = '';
        if ('custom' === $format) {
            $date_format = (string) $this->get_settings('custom_format');
        }

        $value = $modified
            ? get_the_modified_date($date_format, $post)
            : get_the_date($date_format, $post);

        $this->ka_echo((string) $value);
    }
}
