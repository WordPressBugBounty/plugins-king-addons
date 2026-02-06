<?php

namespace King_Addons;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Table_Builder extends Widget_Base
{
    public function get_name()
    {
        return 'king-addons-table-builder';
    }

    public function get_title()
    {
        return esc_html__('KNG Table', 'king-addons');
    }

    public function get_icon()
    {
        return 'king-addons-icon king-addons-data-table';
    }

    public function get_categories()
    {
        return ['king-addons'];
    }

    public function get_keywords()
    {
        return ['table', 'data table', 'builder', 'kng table', 'interactive table'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Table', 'king-addons'),
            ]
        );

        $this->add_control(
            'table_id',
            [
                'label' => esc_html__('Select Table', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_table_options(),
                'default' => '',
            ]
        );

        $this->add_control(
            'theme_override',
            [
                'label' => esc_html__('Theme Override', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('Default', 'king-addons'),
                    'dark' => esc_html__('Dark', 'king-addons'),
                    'light' => esc_html__('Light', 'king-addons'),
                ],
                'default' => '',
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label' => esc_html__('Show Search', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_pagination',
            [
                'label' => esc_html__('Show Pagination', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'enable_sort',
            [
                'label' => esc_html__('Enable Sorting', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'table-builder', [
            'Advanced filters and column types',
            'Conditional formatting and badges',
            'Inline charts and expandable rows',
            'Google Sheets sync and JSON/PDF export',
            'Analytics and role-based access'
        ]);
    }

    private function get_table_options(): array
    {
        $post_type = class_exists('King_Addons\Data_Table_Builder') ? Data_Table_Builder::POST_TYPE : 'kng_table';
        $tables = get_posts([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $options = [
            '' => esc_html__('Select a table', 'king-addons'),
        ];

        foreach ($tables as $table) {
            $options[$table->ID] = $table->post_title;
        }

        return $options;
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $table_id = isset($settings['table_id']) ? absint($settings['table_id']) : 0;

        if ($table_id === 0) {
            $link = admin_url('admin.php?page=king-addons-table-builder&view=add');
            echo '<div class="kng-table-empty">';
            echo esc_html__('Select a table in the widget settings or create one in Table Builder.', 'king-addons');
            echo ' <a href="' . esc_url($link) . '" target="_blank" rel="noopener">' . esc_html__('Create Table', 'king-addons') . '</a>';
            echo '</div>';
            return;
        }

        $shortcode = '[kng_table id="' . $table_id . '"';

        if (!empty($settings['theme_override'])) {
            $shortcode .= ' theme="' . esc_attr($settings['theme_override']) . '"';
        }

        if ($settings['show_search'] !== 'yes') {
            $shortcode .= ' search="false"';
        }

        if ($settings['show_pagination'] !== 'yes') {
            $shortcode .= ' pagination="false"';
        }

        if ($settings['enable_sort'] !== 'yes') {
            $shortcode .= ' sort="false"';
        }

        $shortcode .= ']';

        echo do_shortcode($shortcode);
    }
}
