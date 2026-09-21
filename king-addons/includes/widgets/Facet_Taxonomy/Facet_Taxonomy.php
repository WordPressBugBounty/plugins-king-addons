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
        return esc_html__('Taxonomy Filter', 'king-addons');
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['shop', 'product', 'filter', 'filters', 'ajax', 'woocommerce', 'category', 'attribute', 'faceted', 'smart filters'];
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-facet-taxonomy';
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
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Taxonomy Filter', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_filters_query_id',
            [
                'label' => esc_html__('Shop Filters ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('shop_grid_1', 'king-addons'),
                'description' => esc_html__('Must match the Shop Filters ID on the product grid.', 'king-addons'),
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
                'options' => $this->get_editor_term_options(),
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
            'kng_hierarchy',
            [
                'label' => sprintf(__('Show child terms %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Indent child categories under their parent. Applies to hierarchical taxonomies such as product categories.', 'king-addons'),
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'facet-taxonomy', 'kng_hierarchy', 'yes');

        $this->add_control(
            'kng_display_mode',
            [
                'label' => esc_html__('Display Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'text' => esc_html__('Text', 'king-addons'),
                    'swatch' => sprintf(__('Swatch %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'text',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'facet-taxonomy', 'kng_display_mode', 'swatch');

        $this->add_control(
            'kng_swatch_type',
            [
                'label' => sprintf(__('Swatch Type %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
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
                'label' => sprintf(__('Swatch Map %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => "red:#ff0000\nblue:#0000ff\npattern:https://example.com/pattern.jpg",
                'description' => esc_html__('Optional. A line here overrides the color or image saved on the WooCommerce attribute.', 'king-addons'),
                'condition' => [
                    'kng_display_mode' => 'swatch',
                ],
            ]
        );

        $this->end_controls_section();

        require_once KING_ADDONS_PATH . 'includes/helpers/Faceted/Style_Controls.php';
        Facet_Style_Controls::list($this);
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
        if ('' === $query_id || '' === $taxonomy) {
            return;
        }

        $terms = $this->resolve_terms($taxonomy, $settings);
        if (empty($terms)) {
            return;
        }

        $can_pro = function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
        $hierarchical = $can_pro
            && ($settings['kng_hierarchy'] ?? '') === 'yes'
            && is_taxonomy_hierarchical($taxonomy);
        if ($hierarchical) {
            $terms = $this->with_ancestors($terms, $taxonomy);
        }

        $can_swatch = $can_pro;
        $swatch_map = $can_swatch ? $this->parse_swatch_map($settings['kng_swatch_map'] ?? '') : [];
        $is_swatch = $can_swatch && ($settings['kng_display_mode'] ?? 'text') === 'swatch';
        $swatch_type = $settings['kng_swatch_type'] ?? 'color';
        $taxonomy_obj = get_taxonomy($taxonomy);
        $taxonomy_label = ($taxonomy_obj && !empty($taxonomy_obj->labels->singular_name))
            ? (string) $taxonomy_obj->labels->singular_name
            : $taxonomy;

        echo '<div class="king-addons-facet king-addons-facet--taxonomy">';
        $this->render_term_branch(
            $terms,
            $hierarchical ? 0 : -1,
            $hierarchical,
            $query_id,
            $taxonomy,
            $taxonomy_label,
            $settings,
            $is_swatch,
            $swatch_type,
            $swatch_map
        );
        echo '</div>';
    }

    /**
     * Terms chosen for this filter.
     *
     * @param string               $taxonomy Taxonomy name.
     * @param array<string, mixed> $settings Widget settings.
     * @return array<int, \WP_Term>
     */
    private function resolve_terms(string $taxonomy, array $settings): array
    {
        if (($settings['kng_terms_mode'] ?? 'auto') === 'manual') {
            $terms_raw = $settings['kng_terms'] ?? '';
            $slugs = array_filter(array_map('sanitize_title', preg_split("/\r\n|\n|\r/", (string) $terms_raw)));
            $terms = [];
            foreach ($slugs as $slug) {
                $term = get_term_by('slug', $slug, $taxonomy);
                if ($term instanceof \WP_Term) {
                    $terms[] = $term;
                }
            }
            return $terms;
        }

        $selected = $settings['kng_terms_select'] ?? [];
        $args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 50,
            'orderby' => 'name',
            'order' => 'ASC',
        ];
        if (!empty($selected) && is_array($selected)) {
            $args['slug'] = array_map('sanitize_title', $selected);
            unset($args['number']);
        }

        $terms = get_terms($args);
        return is_array($terms) ? $terms : [];
    }

    /**
     * Include missing parents so a child is not rendered as a root term.
     *
     * @param array<int, \WP_Term> $terms Terms already selected.
     * @param string               $taxonomy Taxonomy name.
     * @return array<int, \WP_Term>
     */
    private function with_ancestors(array $terms, string $taxonomy): array
    {
        $by_id = [];
        foreach ($terms as $term) {
            $by_id[$term->term_id] = $term;
            $parent_id = (int) $term->parent;
            while ($parent_id > 0 && !isset($by_id[$parent_id])) {
                $parent = get_term($parent_id, $taxonomy);
                if (!$parent instanceof \WP_Term) {
                    break;
                }
                $by_id[$parent->term_id] = $parent;
                $parent_id = (int) $parent->parent;
            }
        }

        return array_values($by_id);
    }

    /**
     * Render one level of the term list. Parent -1 means a flat list.
     *
     * @param array<int, \WP_Term> $terms All terms.
     * @param int                  $parent Parent term id, or -1 for a flat list.
     * @param bool                 $hierarchical Whether to nest children.
     * @param string               $query_id Shop Filters ID.
     * @param string               $taxonomy Taxonomy name.
     * @param string               $taxonomy_label Singular taxonomy label.
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_swatch Swatch display.
     * @param string               $swatch_type color|image|text.
     * @param array<string, string> $swatch_map Optional overrides.
     * @return void
     */
    private function render_term_branch(
        array $terms,
        int $parent,
        bool $hierarchical,
        string $query_id,
        string $taxonomy,
        string $taxonomy_label,
        array $settings,
        bool $is_swatch,
        string $swatch_type,
        array $swatch_map
    ): void {
        $level = [];
        foreach ($terms as $term) {
            if ($parent === -1 || (int) $term->parent === $parent) {
                $level[] = $term;
            }
        }
        if (empty($level)) {
            return;
        }

        $list_class = 'king-addons-facet__list';
        if ($parent > 0) {
            $list_class .= ' king-addons-facet__list--child';
        }

        echo '<ul class="' . esc_attr($list_class) . '" data-ka-filters-query-id="' . esc_attr($query_id) . '">';
        foreach ($level as $term) {
            $this->render_term_item(
                $term,
                $query_id,
                $taxonomy,
                $taxonomy_label,
                $settings,
                $is_swatch,
                $swatch_type,
                $swatch_map
            );
            if ($hierarchical) {
                $this->render_term_branch(
                    $terms,
                    (int) $term->term_id,
                    true,
                    $query_id,
                    $taxonomy,
                    $taxonomy_label,
                    $settings,
                    $is_swatch,
                    $swatch_type,
                    $swatch_map
                );
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * One checkbox or swatch row.
     *
     * @param \WP_Term              $term Term.
     * @param string                $query_id Shop Filters ID.
     * @param string                $taxonomy Taxonomy name.
     * @param string                $taxonomy_label Singular label.
     * @param array<string, mixed>  $settings Widget settings.
     * @param bool                  $is_swatch Swatch display.
     * @param string                $swatch_type color|image|text.
     * @param array<string, string> $swatch_map Optional overrides.
     * @return void
     */
    private function render_term_item(
        \WP_Term $term,
        string $query_id,
        string $taxonomy,
        string $taxonomy_label,
        array $settings,
        bool $is_swatch,
        string $swatch_type,
        array $swatch_map
    ): void {
        $slug = $term->slug;
        $term_label = $term->name;
        $swatch = $is_swatch ? $this->resolve_swatch($term, $swatch_type, $swatch_map) : ['type' => '', 'value' => ''];
        ?>
        <li class="king-addons-facet__item<?php echo (int) $term->parent > 0 ? ' king-addons-facet__item--child' : ''; ?>">
            <label class="king-addons-facet__label">
                <input
                    type="checkbox"
                    class="king-addons-facet__input"
                    data-ka-filter-type="taxonomy"
                    data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>"
                    data-ka-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                    data-ka-taxonomy-label="<?php echo esc_attr($taxonomy_label); ?>"
                    data-ka-term="<?php echo esc_attr($slug); ?>"
                    data-ka-term-label="<?php echo esc_attr($term_label); ?>"
                    data-ka-show-counts="<?php echo esc_attr(($settings['kng_show_counts'] ?? '') === 'yes' ? '1' : '0'); ?>"
                    data-ka-swatch="<?php echo $is_swatch ? '1' : '0'; ?>"
                    data-ka-count-key="<?php echo esc_attr($taxonomy . ':' . $slug); ?>"
                />
                <?php if ($is_swatch) : ?>
                    <?php
                    $class = 'king-addons-facet__swatch';
                    $style_attr = '';
                    if ('image' === $swatch['type'] && $swatch['value'] !== '') {
                        $style_attr = ' style="background-image:url(' . esc_url($swatch['value']) . ');"';
                        $class .= ' king-addons-facet__swatch--image';
                    } elseif ('color' === $swatch['type'] && $swatch['value'] !== '') {
                        $style_attr = ' style="background-color:' . esc_attr($swatch['value']) . ';"';
                    } else {
                        $class .= ' king-addons-facet__swatch--letter';
                    }
                    ?>
                    <span class="<?php echo esc_attr($class); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo 'letter' === $swatch['type'] || $swatch['value'] === '' ? esc_html(mb_substr($term_label, 0, 1)) : ''; ?></span>
                    <span class="king-addons-facet__text"><?php echo esc_html($term_label); ?></span>
                <?php else : ?>
                    <span class="king-addons-facet__box" aria-hidden="true"></span>
                    <span class="king-addons-facet__text"><?php echo esc_html($term_label); ?></span>
                <?php endif; ?>
                <?php if (($settings['kng_show_counts'] ?? '') === 'yes') : ?>
                    <span
                        class="king-addons-facet__count"
                        data-ka-count="<?php echo esc_attr($taxonomy . ':' . $slug); ?>"
                        data-ka-hide-zero="<?php echo esc_attr(($settings['kng_hide_zero'] ?? '') === 'yes' ? '1' : '0'); ?>"
                    ></span>
                <?php endif; ?>
            </label>
        <?php
    }

    /**
     * Color or image for a swatch. The textarea overrides attribute data.
     *
     * @param \WP_Term              $term Term.
     * @param string                $type color|image|text.
     * @param array<string, string> $map Optional slug overrides.
     * @return array{type: string, value: string}
     */
    private function resolve_swatch(\WP_Term $term, string $type, array $map): array
    {
        $override = $map[$term->slug] ?? '';
        if ($override !== '') {
            $is_image = (bool) preg_match('#^https?://#i', $override);
            return [
                'type' => $is_image ? 'image' : 'color',
                'value' => $is_image ? $override : (sanitize_hex_color($override) ?: $override),
            ];
        }

        if ('image' === $type) {
            $thumb_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
            if ($thumb_id > 0) {
                $url = wp_get_attachment_image_url($thumb_id, 'thumbnail');
                if (is_string($url) && $url !== '') {
                    return ['type' => 'image', 'value' => $url];
                }
            }
        }

        $color_raw = get_term_meta($term->term_id, 'color', true);
        if (is_string($color_raw) && $color_raw !== '') {
            $hex = sanitize_hex_color($color_raw);
            if ($hex) {
                return ['type' => 'color', 'value' => $hex];
            }
        }

        return ['type' => 'letter', 'value' => ''];
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
     * Term options for the editor select (product taxonomies + attributes).
     *
     * @return array<string,string>
     */
    private function get_editor_term_options(): array
    {
        $options = [];
        $taxonomies = ['product_cat', 'product_tag'];

        if (function_exists('wc_get_attribute_taxonomies')) {
            foreach (wc_get_attribute_taxonomies() as $attribute) {
                if (empty($attribute->attribute_name) || !function_exists('wc_attribute_taxonomy_name')) {
                    continue;
                }
                $taxonomies[] = wc_attribute_taxonomy_name($attribute->attribute_name);
            }
        }

        foreach (array_unique($taxonomies) as $taxonomy) {
            foreach ($this->get_terms_options($taxonomy) as $slug => $name) {
                if (!isset($options[$slug])) {
                    $options[$slug] = $name;
                }
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






