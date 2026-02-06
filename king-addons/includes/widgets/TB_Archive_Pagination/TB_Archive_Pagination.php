<?php
/**
 * Theme Builder Archive Pagination widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Outputs pagination for the current archive query.
 */
class TB_Archive_Pagination extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-archive-pagination';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Archive Pagination', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-pagination';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-archive-pagination-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-archive-pagination-script'];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['pagination', 'archive', 'navigation', 'king-addons'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     *
     * @return void
     */
    public function register_controls(): void
    {
        $this->register_content_controls(false);
        $this->register_style_controls(false);
        $this->register_pro_notice_controls();
    }

    /**
     * Render output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings, false);
    }

    /**
     * Content controls.
     *
     * @param bool $is_pro Whether Pro controls are enabled.
     *
     * @return void
     */
    protected function register_content_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'numbers',
                'options' => [
                    'numbers' => esc_html__('Numbers', 'king-addons'),
                    'prev_next' => esc_html__('Previous/Next', 'king-addons'),
                    'numbers_prev_next' => esc_html__('Numbers + Prev/Next', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_prev_label',
            [
                'label' => esc_html__('Previous Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Previous', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_next_label',
            [
                'label' => esc_html__('Next Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Next', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_first_last',
            [
                'label' => $is_pro ?
                    esc_html__('Show First/Last', 'king-addons') :
                    sprintf(__('Show First/Last %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @param bool $is_pro Whether Pro controls are enabled.
     *
     * @return void
     */
    protected function register_style_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_typography',
                'selector' => '{{WRAPPER}} .king-addons-tb-archive-pagination',
            ]
        );

        $this->add_control(
            'kng_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-pagination a, {{WRAPPER}} .king-addons-tb-archive-pagination span' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_active_color',
            [
                'label' => esc_html__('Active Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-pagination .current' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-pagination' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pro upsell.
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }

        Core::renderProFeaturesSection(
            $this,
            '',
            Controls_Manager::RAW_HTML,
            'tb-archive-pagination',
            [
                'First/last page links',
                'Additional skins and hover states',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Pro flag.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        global $wp_query;
        if (!$wp_query instanceof \WP_Query || $wp_query->max_num_pages <= 1) {
            return;
        }

        $type = $settings['kng_type'] ?? 'numbers';
        $links = paginate_links(
            [
                'type' => 'array',
                'mid_size' => 1,
                'prev_next' => in_array($type, ['prev_next', 'numbers_prev_next'], true),
                'prev_text' => esc_html($settings['kng_prev_label'] ?? ''),
                'next_text' => esc_html($settings['kng_next_label'] ?? ''),
            ]
        );

        if (empty($links)) {
            return;
        }

        $show_first_last = $is_pro && ('yes' === ($settings['kng_show_first_last'] ?? ''));
        $current = max(1, get_query_var('paged'));
        $max = (int) $wp_query->max_num_pages;

        if ($show_first_last && $current > 1) {
            array_unshift($links, '<a class="page-numbers" href="' . esc_url(get_pagenum_link(1)) . '">&laquo;</a>');
        }
        if ($show_first_last && $current < $max) {
            $links[] = '<a class="page-numbers" href="' . esc_url(get_pagenum_link($max)) . '">&raquo;</a>';
        }

        echo '<div class="king-addons-tb-archive-pagination">';
        foreach ($links as $link) {
            echo wp_kses_post($link);
        }
        echo '</div>';
    }
}
