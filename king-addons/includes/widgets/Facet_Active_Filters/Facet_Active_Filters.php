<?php
/**
 * Facet Active Filters widget.
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
 * Displays active filters list.
 */
class Facet_Active_Filters extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-facet-active-filters';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Facet Active Filters', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-filter';
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-active-filters-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-active-filters-script',
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
            'kng_facet_active_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Facet Active Filters', 'king-addons'),
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
            'kng_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Active filters', 'king-addons'),
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
        $title = $settings['kng_title'] ?? '';

        if ('' === $query_id) {
            return;
        }

        ?>
        <div class="king-addons-active-filters" data-ka-filter-type="active-filters" data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>">
            <?php if (!empty($title)) : ?>
                <div class="king-addons-active-filters__title"><?php echo esc_html($title); ?></div>
            <?php endif; ?>
            <div class="king-addons-active-filters__body">
                <ul class="king-addons-active-filters__list"></ul>
            </div>
        </div>
        <?php
    }
}






