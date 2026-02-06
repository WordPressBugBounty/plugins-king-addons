<?php
/**
 * Pricing Table Extension Widget for Elementor.
 *
 * Displays pricing tables created with the Pricing Table Builder.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pricing Table Extension Widget.
 */
class Pricing_Table_Ext extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-pricing-table-ext';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Pricing Table', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-pricing-table-ext';
    }

    /**
     * Get widget categories.
     *
     * @return array
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array
     */
    public function get_keywords(): array
    {
        return ['pricing', 'table', 'plans', 'price', 'comparison', 'subscription', 'billing'];
    }

    /**
     * Get style dependencies.
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return ['king-addons-pt-frontend'];
    }

    /**
     * Get script dependencies.
     *
     * @return array
     */
    public function get_script_depends(): array
    {
        return ['king-addons-pt-frontend'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register widget controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        // Content Section - Source
        $this->start_controls_section(
            'section_source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'table_id',
            [
                'label' => esc_html__('Select Pricing Table', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_pricing_tables(),
                'default' => '',
                'label_block' => true,
                'description' => sprintf(
                    '%s <a href="%s" target="_blank">%s</a>',
                    esc_html__('Select a pricing table or', 'king-addons'),
                    admin_url('admin.php?page=king-addons-pricing-tables&action=new'),
                    esc_html__('create a new one', 'king-addons')
                ),
            ]
        );

        $this->end_controls_section();

        // Content Section - Display Options
        $this->start_controls_section(
            'section_display',
            [
                'label' => esc_html__('Display', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'hide_toggle',
            [
                'label' => esc_html__('Hide Billing Toggle', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'description' => esc_html__('Hide the monthly/annual toggle', 'king-addons'),
            ]
        );

        $this->add_control(
            'force_period',
            [
                'label' => esc_html__('Force Period', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('Default', 'king-addons'),
                    'monthly' => esc_html__('Monthly', 'king-addons'),
                    'annual' => esc_html__('Annual', 'king-addons'),
                ],
                'default' => '',
                'description' => esc_html__('Force a specific billing period', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        // Style Section - Layout
        $this->start_controls_section(
            'section_style_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('Default', 'king-addons'),
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'default' => '',
                'tablet_default' => '',
                'mobile_default' => '',
            ]
        );

        $this->add_control(
            'max_width',
            [
                'label' => esc_html__('Max Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 600,
                        'max' => 2000,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'alignment',
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
                'default' => '',
            ]
        );

        $this->end_controls_section();

        // Style Section - Colors (Pro)
        $this->start_controls_section(
            'section_style_colors',
            [
                'label' => esc_html__('Colors', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $is_premium = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();

        if ($is_premium) {
            $this->add_control(
                'color_accent',
                [
                    'label' => esc_html__('Accent Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .kng-pt-wrapper' => '--kng-pt-accent: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'color_card_bg',
                [
                    'label' => esc_html__('Card Background', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .kng-pt-wrapper' => '--kng-pt-card-bg: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'color_text',
                [
                    'label' => esc_html__('Text Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .kng-pt-wrapper' => '--kng-pt-text: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'color_muted',
                [
                    'label' => esc_html__('Muted Text Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .kng-pt-wrapper' => '--kng-pt-muted: {{VALUE}};',
                    ],
                ]
            );
        } else {
            $this->add_control(
                'colors_pro_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        '<div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                            <strong>%s</strong><br>%s <a href="%s" target="_blank">%s</a>
                        </div>',
                        esc_html__('Pro Feature', 'king-addons'),
                        esc_html__('Color overrides are available in the Pro version.', 'king-addons'),
                        'https://kingaddons.com/pricing/',
                        esc_html__('Upgrade Now', 'king-addons')
                    ),
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Get pricing tables for select control.
     *
     * @return array
     */
    private function get_pricing_tables(): array
    {
        $tables = get_posts([
            'post_type' => Pricing_Table_Builder::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $options = ['' => esc_html__('Select a table', 'king-addons')];

        foreach ($tables as $table) {
            $options[$table->ID] = $table->post_title;
        }

        return $options;
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $table_id = (int)($settings['table_id'] ?? 0);

        if (!$table_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div style="padding: 40px; text-align: center; background: #f5f5f7; border-radius: 12px;">';
                echo '<p style="color: #86868b; margin: 0;">' . esc_html__('Please select a pricing table from the widget settings.', 'king-addons') . '</p>';
                echo '</div>';
            }
            return;
        }

        $builder = Pricing_Table_Builder::instance();

        $overrides = [];

        if (!empty($settings['hide_toggle'])) {
            $overrides['hide_toggle'] = true;
        }

        if (!empty($settings['force_period'])) {
            $overrides['force_period'] = $settings['force_period'];
        }

        // Responsive columns
        if (!empty($settings['columns'])) {
            $overrides['columns_desktop'] = $settings['columns'];
        }
        if (!empty($settings['columns_tablet'])) {
            $overrides['columns_tablet'] = $settings['columns_tablet'];
        }
        if (!empty($settings['columns_mobile'])) {
            $overrides['columns_mobile'] = $settings['columns_mobile'];
        }

        if (!empty($settings['max_width']['size'])) {
            $overrides['max_width'] = $settings['max_width']['size'];
        }

        if (!empty($settings['alignment'])) {
            $overrides['alignment'] = $settings['alignment'];
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $builder->render_for_elementor($table_id, $overrides);
    }

    /**
     * Render widget output in the editor.
     *
     * @return void
     */
    protected function content_template(): void
    {
        ?>
        <#
        if (!settings.table_id) {
            #>
            <div style="padding: 40px; text-align: center; background: #f5f5f7; border-radius: 12px;">
                <p style="color: #86868b; margin: 0;"><?php echo esc_html__('Please select a pricing table from the widget settings.', 'king-addons'); ?></p>
            </div>
            <#
        }
        #>
        <?php
    }
}
