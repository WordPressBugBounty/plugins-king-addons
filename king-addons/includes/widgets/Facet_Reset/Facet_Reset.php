<?php
/**
 * Facet Reset widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reset filters widget.
 */
class Facet_Reset extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-facet-reset';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Facet Reset', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-close';
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
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-reset-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-reset-script',
        ];
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
        $this->start_controls_section(
            'kng_facet_reset_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Facet Reset', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_filters_query_id',
            [
                'label' => esc_html__('Filter Query ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('shop_grid_1', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_label',
            [
                'label' => esc_html__('Button Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Reset filters', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $query_id = sanitize_title($settings['kng_filters_query_id'] ?? '');
        $label = $settings['kng_label'] ?? esc_html__('Reset filters', 'king-addons');

        if ('' === $query_id) {
            return;
        }

        ?>
        <div class="king-addons-facet king-addons-facet--reset">
            <button
                type="button"
                class="king-addons-facet__button"
                data-ka-filter-type="reset"
                data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>"
            >
                <?php echo esc_html($label); ?>
            </button>
        </div>
        <?php
    }
}






