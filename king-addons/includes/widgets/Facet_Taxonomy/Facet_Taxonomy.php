<?php
/**
 * Facet Taxonomy widget.
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
 * Checkbox taxonomy filter widget.
 */
class Facet_Taxonomy extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-facet-taxonomy';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Facet Taxonomy', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-checkbox';
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-taxonomy-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-taxonomy-script',
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
            'kng_facet_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Facet Taxonomy', 'king-addons'),
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
            'kng_taxonomy',
            [
                'label' => esc_html__('Taxonomy', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_taxonomy_options(),
                'default' => 'product_cat',
            ]
        );

        $this->add_control(
            'kng_terms_mode',
            [
                'label' => esc_html__('Terms Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'auto' => esc_html__('Auto (all terms)', 'king-addons'),
                    'manual' => esc_html__('Manual list', 'king-addons'),
                ],
                'default' => 'auto',
            ]
        );

        $this->add_control(
            'kng_terms_select',
            [
                'label' => esc_html__('Select Terms', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_terms_options('product_cat'),
                'condition' => [
                    'kng_terms_mode' => 'auto',
                ],
            ]
        );

        $this->add_control(
            'kng_terms',
            [
                'label' => esc_html__('Terms (slugs, one per line)', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => "shoes\nboots\nsneakers",
                'condition' => [
                    'kng_terms_mode' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'kng_show_counts',
            [
                'label' => esc_html__('Show Counts', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_hide_zero',
            [
                'label' => esc_html__('Hide Zero Count', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_show_counts' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_display_mode',
            [
                'label' => esc_html__('Display Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'text' => esc_html__('Text', 'king-addons'),
                    'swatch' => esc_html__('Swatch', 'king-addons'),
                ],
                'default' => 'text',
            ]
        );

        $this->add_control(
            'kng_swatch_type',
            [
                'label' => esc_html__('Swatch Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'color' => esc_html__('Color', 'king-addons'),
                    'image' => esc_html__('Image URL', 'king-addons'),
                    'text' => esc_html__('Text (fallback)', 'king-addons'),
                ],
                'default' => 'color',
                'condition' => [
                    'kng_display_mode' => 'swatch',
                ],
            ]
        );

        $this->add_control(
            'kng_swatch_map',
            [
                'label' => esc_html__('Swatch Map', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => "red:#ff0000\nblue:#0000ff\npattern:https://example.com/pattern.jpg",
                'description' => esc_html__('Key-value per line: slug:value (hex color or image URL).', 'king-addons'),
                'condition' => [
                    'kng_display_mode' => 'swatch',
                ],
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
        $taxonomy = sanitize_key($settings['kng_taxonomy'] ?? '');
        $terms = [];

        if (($settings['kng_terms_mode'] ?? 'auto') === 'auto') {
            $selected = $settings['kng_terms_select'] ?? [];
            if (!empty($selected) && is_array($selected)) {
                $terms = array_map('sanitize_title', $selected);
            } else {
                $terms = $this->get_term_slugs_for_tax($taxonomy);
            }
        } else {
            $terms_raw = $settings['kng_terms'] ?? '';
            $terms = array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string) $terms_raw)));
        }

        if (empty($terms)) {
            return;
        }

        $swatch_map = $this->parse_swatch_map($settings['kng_swatch_map'] ?? '');
        $is_swatch = ($settings['kng_display_mode'] ?? 'text') === 'swatch';
        $swatch_type = $settings['kng_swatch_type'] ?? 'color';

        ?>
        <div class="king-addons-facet king-addons-facet--taxonomy">
            <ul class="king-addons-facet__list" data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>">
                <?php foreach ($terms as $term) : ?>
                    <li class="king-addons-facet__item">
                        <label class="king-addons-facet__label">
                            <input
                                type="checkbox"
                                class="king-addons-facet__input"
                                data-ka-filter-type="taxonomy"
                                data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>"
                                data-ka-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                                data-ka-term="<?php echo esc_attr($term); ?>"
                                data-ka-show-counts="<?php echo esc_attr(($settings['kng_show_counts'] ?? '') === 'yes' ? '1' : '0'); ?>"
                                data-ka-swatch="<?php echo $is_swatch ? '1' : '0'; ?>"
                                data-ka-count-key="<?php echo esc_attr($taxonomy . ':' . $term); ?>"
                            />
                            <?php if ($is_swatch) : ?>
                                <?php
                                $value = $swatch_map[$term] ?? '';
                                $style_attr = '';
                                $class = 'king-addons-facet__swatch';
                                if ('color' === $swatch_type && $value) {
                                    $style_attr = ' style="background-color:' . esc_attr($value) . ';"';
                                } elseif ('image' === $swatch_type && $value) {
                                    $style_attr = ' style="background-image:url(' . esc_url($value) . ');"';
                                    $class .= ' king-addons-facet__swatch--image';
                                }
                                ?>
                                <span class="<?php echo esc_attr($class); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></span>
                                <span class="king-addons-facet__text"><?php echo esc_html($term); ?></span>
                            <?php else : ?>
                                <span class="king-addons-facet__text"><?php echo esc_html($term); ?></span>
                            <?php endif; ?>
                            <?php if (($settings['kng_show_counts'] ?? '') === 'yes') : ?>
                                <span
                                    class="king-addons-facet__count"
                                    data-ka-count="<?php echo esc_attr($taxonomy . ':' . $term); ?>"
                                    data-ka-hide-zero="<?php echo esc_attr(($settings['kng_hide_zero'] ?? '') === 'yes' ? '1' : '0'); ?>"
                                ></span>
                            <?php endif; ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Get available taxonomies list.
     *
     * @return array<string,string>
     */
    private function get_taxonomy_options(): array
    {
        $options = [];
        $taxes = get_taxonomies(['public' => true], 'objects');
        foreach ($taxes as $tax) {
            $options[$tax->name] = esc_html($tax->labels->singular_name ?? $tax->label ?? $tax->name);
        }
        return $options;
    }

    /**
     * Get term options for a taxonomy.
     *
     * @param string $taxonomy Taxonomy.
     *
     * @return array<string,string>
     */
    private function get_terms_options(string $taxonomy): array
    {
        $options = [];
        $terms = get_terms(
            [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'number' => 50,
            ]
        );

        if (is_array($terms)) {
            foreach ($terms as $term) {
                $options[$term->slug] = esc_html($term->name);
            }
        }

        return $options;
    }

    /**
     * Get term slugs for taxonomy (fallback).
     *
     * @param string $taxonomy Taxonomy.
     *
     * @return array<int,string>
     */
    private function get_term_slugs_for_tax(string $taxonomy): array
    {
        $options = $this->get_terms_options($taxonomy);
        return array_keys($options);
    }

    /**
     * Parse swatch map textarea (slug:value).
     *
     * @param string $raw Raw input.
     *
     * @return array<string,string>
     */
    private function parse_swatch_map(string $raw): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $raw);
        $map = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$slug, $val] = array_map('trim', explode(':', $line, 2));
            if ($slug && $val) {
                $map[sanitize_title($slug)] = $val;
            }
        }
        return $map;
    }
}






