<?php

namespace King_Addons;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Maintenance_Page extends Widget_Base
{
    public function get_name()
    {
        return 'king-addons-maintenance-page';
    }

    public function get_title()
    {
        return esc_html__('KNG Maintenance Page', 'king-addons');
    }

    public function get_icon()
    {
        return 'king-addons-icon king-addons-maintenance-mode';
    }

    public function get_categories()
    {
        return ['king-addons'];
    }

    public function get_keywords()
    {
        return ['maintenance', 'coming soon', 'access', 'gate', 'maintenance page'];
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
                'label' => esc_html__('Maintenance Page', 'king-addons'),
            ]
        );

        $this->add_control(
            'source_type',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'built_in' => esc_html__('Built-in Templates', 'king-addons'),
                    'page' => esc_html__('WordPress Page', 'king-addons'),
                    'elementor' => esc_html__('Elementor Template', 'king-addons'),
                ],
                'default' => 'built_in',
            ]
        );

        $this->add_control(
            'template_id',
            [
                'label' => esc_html__('Built-in Template', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_template_options(),
                'default' => 'minimal',
                'condition' => [
                    'source_type' => 'built_in',
                ],
            ]
        );

        $this->add_control(
            'page_id',
            [
                'label' => esc_html__('Select Page', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_pages_options(),
                'default' => '',
                'condition' => [
                    'source_type' => 'page',
                ],
            ]
        );

        $this->add_control(
            'elementor_id',
            [
                'label' => esc_html__('Elementor Template', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_elementor_templates(),
                'default' => '',
                'condition' => [
                    'source_type' => 'elementor',
                ],
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
            'full_height',
            [
                'label' => esc_html__('Full Height', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->end_controls_section();

        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'maintenance-page', [
            'Private site password protection',
            'Rule-based access control',
            'Multiple schedules and recurring windows',
            'Analytics and integrations'
        ]);
    }

    private function get_template_options(): array
    {
        return [
            'minimal' => esc_html__('Minimal', 'king-addons'),
            'dark' => esc_html__('Dark', 'king-addons'),
            'gradient' => esc_html__('Gradient', 'king-addons'),
            'aurora' => esc_html__('Aurora Glow', 'king-addons'),
            'neon' => esc_html__('Neon Tech', 'king-addons'),
            'paper' => esc_html__('Paper Light', 'king-addons'),
            'grid' => esc_html__('Tech Grid', 'king-addons'),
            'mono' => esc_html__('Mono Minimal', 'king-addons'),
            'spotlight' => esc_html__('Spotlight', 'king-addons'),
            'poster' => esc_html__('Poster', 'king-addons'),
            'ribbon' => esc_html__('Ribbon', 'king-addons'),
            'countdown' => esc_html__('Coming Soon Countdown', 'king-addons'),
            'progress' => esc_html__('Maintenance Progress', 'king-addons'),
            'subscribe' => esc_html__('Simple Subscribe', 'king-addons'),
            'product-launch' => esc_html__('Product Launch', 'king-addons'),
            'construction' => esc_html__('Under Construction', 'king-addons'),
            'split' => esc_html__('Split Layout', 'king-addons'),
            'logo' => esc_html__('Centered Logo', 'king-addons'),
        ];
    }

    private function get_pages_options(): array
    {
        $pages = get_pages([
            'sort_column' => 'post_title',
            'sort_order' => 'ASC',
            'post_status' => 'publish',
        ]);

        $options = [
            '' => esc_html__('Select a page', 'king-addons'),
        ];

        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title;
        }

        return $options;
    }

    private function get_elementor_templates(): array
    {
        if (!class_exists('\\Elementor\\Plugin')) {
            return [
                '' => esc_html__('Elementor not active', 'king-addons'),
            ];
        }

        $templates = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $options = [
            '' => esc_html__('Select a template', 'king-addons'),
        ];

        foreach ($templates as $template) {
            $options[$template->ID] = $template->post_title;
        }

        return $options;
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $source = $settings['source_type'] ?? 'built_in';

        $shortcode = '[kng_maintenance_page';

        if ($source === 'page' && !empty($settings['page_id'])) {
            $shortcode .= ' id="' . esc_attr($settings['page_id']) . '" type="page"';
        } elseif ($source === 'elementor' && !empty($settings['elementor_id'])) {
            $shortcode .= ' id="' . esc_attr($settings['elementor_id']) . '" type="elementor"';
        } else {
            $shortcode .= ' id="' . esc_attr($settings['template_id'] ?? 'minimal') . '"';
        }

        if (!empty($settings['theme_override'])) {
            $shortcode .= ' theme="' . esc_attr($settings['theme_override']) . '"';
        }

        if (!empty($settings['full_height'])) {
            $shortcode .= ' full_height="true"';
        }

        $shortcode .= ']';

        echo do_shortcode($shortcode);
    }
}
