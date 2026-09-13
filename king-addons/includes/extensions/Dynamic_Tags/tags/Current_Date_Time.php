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
 * The current date and time, in the site's timezone.
 */
class Current_Date_Time extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-current-date-time';
    }

    public function get_title()
    {
        // No ampersand on purpose: Elementor prints tag titles with jQuery's
        // .text() in the tag list but as raw HTML in the control cover, so an
        // escaped entity would show up literally in one of the two places.
        return esc_html__('Current Date and Time', 'king-addons');
    }

    public function get_categories()
    {
        return [Module::TEXT_CATEGORY, Module::DATETIME_CATEGORY];
    }

    protected function register_controls()
    {
        $this->add_control(
            'format',
            [
                'label' => esc_html__('Format', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'default' => esc_html__('Site default', 'king-addons'),
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
        $format = 'custom' === $this->get_settings('format')
            ? (string) $this->get_settings('custom_format')
            : get_option('date_format') . ' ' . get_option('time_format');

        if ('' === trim($format)) {
            $format = get_option('date_format');
        }

        // wp_date() honours the site timezone; date_i18n() would drift on sites
        // whose timezone is not UTC.
        echo esc_html((string) wp_date($format));
    }
}
