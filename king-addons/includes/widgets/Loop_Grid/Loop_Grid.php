<?php
/**
 * Loop Grid widget.
 *
 * Repeats a Loop Builder item template for every entry a query returns.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Plugin as Elementor_Plugin;
use Elementor\Widget_Base;
use King_Addons\Loop_Builder\Renderer as Loop_Renderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A card holding a Loop Grid that points back at its own template would
 * recurse until the request runs out of memory — Renderer guards that.
 */
class Loop_Grid extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-loop-grid';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Loop Grid', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-loop-grid';
    }

    /**
     * Categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int,string>
     */
    public function get_keywords(): array
    {
        return ['loop', 'grid', 'query', 'posts', 'masonry', 'carousel', 'filter', 'king-addons'];
    }

    /**
     * Help URL.
     *
     * @return string
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    /**
     * Style dependencies.
     *
     * @return array<int,string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-loop-grid-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-imagesloaded-imagesloaded',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-isotope-kng',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-loop-grid-script',
        ];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->register_layout_controls();
        $this->register_query_controls();
        $this->register_filter_controls();
        $this->register_carousel_controls();
        $this->register_pagination_controls();
        $this->register_style_controls();
    }

    /**
     * Layout / template.
     *
     * @return void
     */
    private function register_layout_controls(): void
    {
        $this->start_controls_section(
            'section_layout',
            ['label' => esc_html__('Layout', 'king-addons')]
        );

        $templates = ['' => esc_html__('Select a loop item', 'king-addons')];
        foreach (Loop_Renderer::get_templates() as $id => $title) {
            $templates[(string) $id] = $title;
        }

        $this->add_control(
            'template_id',
            [
                'label' => esc_html__('Loop item', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $templates,
                'default' => '',
                'description' => esc_html__('The card that is repeated for every entry.', 'king-addons'),
            ]
        );

        $is_pro = self::can_use_pro();

        $this->add_control(
            'layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'grid' => esc_html__('Grid', 'king-addons'),
                    'masonry' => $is_pro
                        ? esc_html__('Masonry', 'king-addons')
                        : sprintf(__('Masonry %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'carousel' => $is_pro
                        ? esc_html__('Carousel', 'king-addons')
                        : sprintf(__('Carousel %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'grid',
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        if (!$is_pro) {
            Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'loop-grid', 'layout', ['masonry', 'carousel']);
        }

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'condition' => ['layout!' => 'carousel'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid--grid .king-addons-loop-grid__items' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                    '{{WRAPPER}} .king-addons-loop-grid' => '--ka-loop-cols: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => ['px' => ['min' => 0, 'max' => 120]],
                'default' => ['unit' => 'px', 'size' => 24],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__items' => 'gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-loop-grid' => '--ka-loop-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'equal_height',
            [
                'label' => esc_html__('Equal height cards', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => ['layout' => 'grid'],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Query.
     *
     * @return void
     */
    private function register_query_controls(): void
    {
        $this->start_controls_section(
            'section_query',
            ['label' => esc_html__('Query', 'king-addons')]
        );

        $this->add_control(
            'source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'custom' => esc_html__('Custom query', 'king-addons'),
                    'current' => esc_html__('Current query', 'king-addons'),
                ],
                'default' => 'custom',
            ]
        );

        $this->add_control(
            'post_type',
            [
                'label' => esc_html__('Post type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_post_type_options(),
                'default' => 'post',
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => esc_html__('Posts per page', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'default' => 6,
            ]
        );

        $this->add_control(
            'offset',
            [
                'label' => esc_html__('Offset', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'default' => 0,
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => esc_html__('Order by', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'title' => esc_html__('Title', 'king-addons'),
                    'modified' => esc_html__('Last modified', 'king-addons'),
                    'menu_order' => esc_html__('Menu order', 'king-addons'),
                    'comment_count' => esc_html__('Comment count', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                ],
                'default' => 'date',
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'DESC' => esc_html__('Descending', 'king-addons'),
                    'ASC' => esc_html__('Ascending', 'king-addons'),
                ],
                'default' => 'DESC',
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'taxonomy',
            [
                'label' => esc_html__('Taxonomy', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_taxonomy_options(),
                'default' => '',
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'terms',
            [
                'label' => esc_html__('Terms', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'news, updates',
                'description' => esc_html__('Slugs or IDs, comma-separated.', 'king-addons'),
                'condition' => [
                    'source' => 'custom',
                    'taxonomy!' => '',
                ],
            ]
        );

        $this->add_control(
            'authors',
            [
                'label' => esc_html__('Authors', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '1, 2',
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'include_ids',
            [
                'label' => esc_html__('Include IDs', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'exclude_ids',
            [
                'label' => esc_html__('Exclude IDs', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => ['source' => 'custom'],
            ]
        );

        $this->add_control(
            'exclude_current',
            [
                'label' => esc_html__('Exclude current post', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'query_id',
            [
                'label' => esc_html__('Query ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Optional id for the elementor/query/{id} hook.', 'king-addons'),
            ]
        );

        $this->add_control(
            'nothing_found_text',
            [
                'label' => esc_html__('Nothing found text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Nothing found.', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Taxonomy filter bar (Pro).
     *
     * @return void
     */
    private function register_filter_controls(): void
    {
        $is_pro = self::can_use_pro();

        $this->start_controls_section(
            'section_filter',
            ['label' => esc_html__('Filters', 'king-addons')]
        );

        $this->add_control(
            'filter_enable',
            [
                'label' => $is_pro
                    ? esc_html__('Filter bar', 'king-addons')
                    : sprintf(__('Filter bar %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        if (!$is_pro) {
            Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'loop-grid', 'filter_enable', ['yes']);
        }

        $this->add_control(
            'filter_taxonomy',
            [
                'label' => esc_html__('Taxonomy', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_taxonomy_options(),
                'default' => '',
                'description' => esc_html__('Leave empty to use the taxonomy from the Query section.', 'king-addons'),
                'condition' => ['filter_enable' => 'yes'],
            ]
        );

        $this->add_control(
            'filter_terms_mode',
            [
                'label' => esc_html__('Terms', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'all' => esc_html__('All terms', 'king-addons'),
                    'include' => esc_html__('Include', 'king-addons'),
                    'exclude' => esc_html__('Exclude', 'king-addons'),
                ],
                'default' => 'all',
                'condition' => ['filter_enable' => 'yes'],
            ]
        );

        $this->add_control(
            'filter_terms',
            [
                'label' => esc_html__('Term slugs or IDs', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'news, updates',
                'condition' => [
                    'filter_enable' => 'yes',
                    'filter_terms_mode!' => 'all',
                ],
            ]
        );

        $this->add_control(
            'filter_all_label',
            [
                'label' => esc_html__('“All” label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('All', 'king-addons'),
                'condition' => ['filter_enable' => 'yes'],
            ]
        );

        $this->add_control(
            'filter_deeplink',
            [
                'label' => esc_html__('Remember filter in the URL', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => ['filter_enable' => 'yes'],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Carousel controls (Pro).
     *
     * @return void
     */
    private function register_carousel_controls(): void
    {
        $this->start_controls_section(
            'section_carousel',
            [
                'label' => esc_html__('Carousel', 'king-addons'),
                'condition' => ['layout' => 'carousel'],
            ]
        );

        $this->add_responsive_control(
            'carousel_slides',
            [
                'label' => esc_html__('Slides per view', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 8,
                'default' => 3,
                'tablet_default' => 2,
                'mobile_default' => 1,
            ]
        );

        $this->add_control(
            'carousel_autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'carousel_delay',
            [
                'label' => esc_html__('Autoplay delay (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 500,
                'default' => 4000,
                'condition' => ['carousel_autoplay' => 'yes'],
            ]
        );

        $this->add_control(
            'carousel_loop',
            [
                'label' => esc_html__('Loop', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'carousel_arrows',
            [
                'label' => esc_html__('Arrows', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'carousel_dots',
            [
                'label' => esc_html__('Dots', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'carousel_speed',
            [
                'label' => esc_html__('Speed (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 100,
                'default' => 400,
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pagination.
     *
     * @return void
     */
    private function register_pagination_controls(): void
    {
        $this->start_controls_section(
            'section_pagination',
            [
                'label' => esc_html__('Pagination', 'king-addons'),
                'condition' => ['layout!' => 'carousel'],
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label' => esc_html__('Pagination', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'numbers' => esc_html__('Numbers', 'king-addons'),
                    'load_more' => esc_html__('Load more', 'king-addons'),
                ],
                'default' => 'none',
                'condition' => ['layout!' => 'carousel'],
            ]
        );

        $this->add_control(
            'load_more_text',
            [
                'label' => esc_html__('Load more text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Load more', 'king-addons'),
                'condition' => ['pagination_type' => 'load_more'],
            ]
        );

        $this->add_control(
            'all_loaded_text',
            [
                'label' => esc_html__('All loaded text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('All items loaded', 'king-addons'),
                'condition' => ['pagination_type' => 'load_more'],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @return void
     */
    private function register_style_controls(): void
    {
        $this->start_controls_section(
            'section_style_items',
            [
                'label' => esc_html__('Items', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_radius',
            [
                'label' => esc_html__('Border radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_pagination',
            [
                'label' => esc_html__('Pagination', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'pagination_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => ['px' => ['min' => 0, 'max' => 120]],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__footer' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__pagination' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-loop-grid__load-more' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-loop-grid__all-loaded' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_button_background',
            [
                'label' => esc_html__('Button background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__pagination .page-numbers' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-loop-grid__load-more' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'empty_color',
            [
                'label' => esc_html__('Empty text color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-loop-grid__empty' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render the grid.
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $template_id = absint($settings['template_id'] ?? 0);

        if (!Loop_Renderer::is_template($template_id)) {
            $this->print_notice(esc_html__('Pick a loop item in the Layout section.', 'king-addons'));
            return;
        }

        $page = self::resolve_page_number($this->get_id());
        $filter = self::resolve_filter_term($this->get_id(), $settings);
        $query = self::build_query($settings, $page, self::current_context(), $filter);

        echo self::render_grid($settings, $query, $template_id, $this->get_id(), $page, self::current_document_id(), $filter); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Editor / front notice.
     *
     * @param string $message Message.
     *
     * @return void
     */
    private function print_notice(string $message): void
    {
        echo '<div class="king-addons-loop-grid__notice">' . esc_html($message) . '</div>';
    }

    /**
     * @param array<string,mixed> $settings    Widget settings.
     * @param \WP_Query           $query       Query.
     * @param int                 $template_id Loop item template.
     * @param string              $element_id  Element id.
     * @param int                 $page        Page number.
     * @param int                 $document_id Document the widget belongs to.
     * @param string              $filter      Active filter term slug or ID.
     *
     * @return string
     */
    private static function render_grid(array $settings, \WP_Query $query, int $template_id, string $element_id, int $page, int $document_id, string $filter = ''): string
    {
        $layout = self::resolve_layout($settings);
        $pagination = (string) ($settings['pagination_type'] ?? 'none');
        if ('carousel' === $layout || !in_array($pagination, ['none', 'numbers', 'load_more'], true)) {
            $pagination = 'none';
        }

        ob_start();

        $classes = ['king-addons-loop-grid', 'king-addons-loop-grid--' . $layout];
        if ('yes' === ($settings['equal_height'] ?? '') && 'grid' === $layout) {
            $classes[] = 'king-addons-loop-grid--equal';
        }

        $context = self::current_context();
        $swiper = self::carousel_config($settings);
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>"
             data-ka-loop-grid="1"
             data-layout="<?php echo esc_attr($layout); ?>"
             data-element-id="<?php echo esc_attr($element_id); ?>"
             data-post-id="<?php echo esc_attr((string) $document_id); ?>"
             data-page="<?php echo esc_attr((string) $page); ?>"
             data-max-pages="<?php echo esc_attr((string) max(1, (int) $query->max_num_pages)); ?>"
             data-context="<?php echo esc_attr((string) wp_json_encode($context)); ?>"
             data-filter="<?php echo esc_attr($filter); ?>"
             data-swiper="<?php echo esc_attr((string) wp_json_encode($swiper)); ?>"
             data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
             data-nonce="<?php echo esc_attr(wp_create_nonce('ka_loop_grid')); ?>">
            <?php echo self::render_filter_bar($settings, $element_id, $filter); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php if (!$query->have_posts()) : ?>
                <div class="king-addons-loop-grid__empty"><?php echo esc_html((string) ($settings['nothing_found_text'] ?? '')); ?></div>
            <?php else : ?>
                <?php if ('carousel' === $layout) : ?>
                    <div class="swiper king-addons-loop-grid__swiper">
                        <div class="swiper-wrapper king-addons-loop-grid__items">
                            <?php echo self::render_items($query, $template_id, $layout); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <?php if (!empty($swiper['arrows'])) : ?>
                            <button type="button" class="swiper-button-prev" aria-label="<?php echo esc_attr__('Previous', 'king-addons'); ?>"></button>
                            <button type="button" class="swiper-button-next" aria-label="<?php echo esc_attr__('Next', 'king-addons'); ?>"></button>
                        <?php endif; ?>
                        <?php if (!empty($swiper['dots'])) : ?>
                            <div class="swiper-pagination"></div>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="king-addons-loop-grid__items">
                        <?php if ('masonry' === $layout) : ?>
                            <div class="king-addons-loop-grid__sizer"></div>
                        <?php endif; ?>
                        <?php echo self::render_items($query, $template_id, $layout); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>

                <?php if ('none' !== $pagination && (int) $query->max_num_pages > 1) : ?>
                    <div class="king-addons-loop-grid__footer">
                        <?php echo self::render_pagination($settings, $query, $element_id, $page, $pagination); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param \WP_Query $query       Query to walk.
     * @param int       $template_id Loop item template.
     * @param string    $layout      grid|masonry|carousel.
     *
     * @return string
     */
    private static function render_items(\WP_Query $query, int $template_id, string $layout = 'grid'): string
    {
        $html = '';
        $item_class = 'carousel' === $layout
            ? 'swiper-slide king-addons-loop-grid__item'
            : 'king-addons-loop-grid__item';

        foreach ($query->posts as $post) {
            $post_id = $post instanceof \WP_Post ? (int) $post->ID : (int) $post;

            $html .= '<div class="' . esc_attr($item_class) . '" data-post-id="' . esc_attr((string) $post_id) . '">';
            $html .= Loop_Renderer::render($template_id, $post_id);
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Numbers or load-more markup.
     *
     * @param array<string,mixed> $settings    Widget settings.
     * @param \WP_Query           $query       Query.
     * @param string              $element_id  Element id.
     * @param int                 $page        Current page.
     * @param string              $pagination  numbers|load_more.
     *
     * @return string
     */
    private static function render_pagination(array $settings, \WP_Query $query, string $element_id, int $page, string $pagination): string
    {
        $max = max(1, (int) $query->max_num_pages);

        if ('load_more' === $pagination) {
            $loaded = $page >= $max;
            $html = '';
            if (!$loaded) {
                $label = (string) ($settings['load_more_text'] ?? __('Load more', 'king-addons'));
                $html .= '<button type="button" class="king-addons-loop-grid__load-more">' . esc_html($label) . '</button>';
            }
            $done = (string) ($settings['all_loaded_text'] ?? __('All items loaded', 'king-addons'));
            $html .= '<div class="king-addons-loop-grid__all-loaded"' . ($loaded ? '' : ' hidden') . '>' . esc_html($done) . '</div>';

            return $html;
        }

        $arg = 'ka-loop-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $element_id);
        $links = paginate_links([
            'base' => esc_url_raw(add_query_arg($arg, '%#%', false) ?: ''),
            'format' => '',
            'current' => max(1, $page),
            'total' => $max,
            'type' => 'plain',
            'prev_next' => true,
        ]);

        if (!is_string($links) || '' === $links) {
            return '';
        }

        return '<nav class="king-addons-loop-grid__pagination">' . $links . '</nav>';
    }

    /**
     * @param array<string,mixed> $settings Widget settings.
     * @param int                 $page     Page number.
     * @param array<string,mixed> $context  Archive context.
     * @param string              $filter   Active filter term.
     *
     * @return \WP_Query
     */
    private static function build_query(array $settings, int $page, array $context, string $filter = ''): \WP_Query
    {
        $per_page = max(1, (int) ($settings['posts_per_page'] ?? 6));
        $source = (string) ($settings['source'] ?? 'custom');

        if ('current' === $source) {
            global $wp_query;
            $main = [];

            if (!wp_doing_ajax() && $wp_query instanceof \WP_Query) {
                $main = $wp_query->query_vars;
                unset($main['pagename'], $main['page_id'], $main['name'], $main['p'], $main['error']);
            } else {
                $main = self::context_to_query_vars($context);
            }

            return new \WP_Query(self::apply_filter_args(array_merge($main, [
                'posts_per_page' => $per_page,
                'paged' => max(1, $page),
                'ignore_sticky_posts' => true,
                'no_found_rows' => false,
            ]), $settings, $filter));
        }

        $post_type = sanitize_key((string) ($settings['post_type'] ?? 'post'));
        if ('' === $post_type || !post_type_exists($post_type)) {
            $post_type = 'post';
        }

        $orderby = (string) ($settings['orderby'] ?? 'date');
        if (!in_array($orderby, ['date', 'title', 'modified', 'menu_order', 'comment_count', 'rand'], true)) {
            $orderby = 'date';
        }

        $order = strtoupper((string) ($settings['order'] ?? 'DESC'));
        $order = 'ASC' === $order ? 'ASC' : 'DESC';

        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => max(1, $page),
            'orderby' => $orderby,
            'order' => $order,
            'ignore_sticky_posts' => true,
            'no_found_rows' => false,
        ];

        $offset = absint($settings['offset'] ?? 0);
        if ($offset > 0) {
            $args['offset'] = $offset + ((max(1, $page) - 1) * $per_page);
            unset($args['paged']);
        }

        $include = self::id_list((string) ($settings['include_ids'] ?? ''));
        if (!empty($include)) {
            $args['post__in'] = $include;
            $args['posts_per_page'] = max($per_page, count($include));
        }

        $exclude = self::id_list((string) ($settings['exclude_ids'] ?? ''));
        if ('yes' === ($settings['exclude_current'] ?? '')) {
            $current = (int) get_the_ID();
            if ($current > 0) {
                $exclude[] = $current;
            }
        }
        if (!empty($exclude)) {
            $args['post__not_in'] = array_values(array_unique($exclude));
        }

        $authors = self::id_list((string) ($settings['authors'] ?? ''));
        if (!empty($authors)) {
            $args['author__in'] = $authors;
        }

        $taxonomy = sanitize_key((string) ($settings['taxonomy'] ?? ''));
        $terms = self::split_list((string) ($settings['terms'] ?? ''));
        if ('' !== $taxonomy && taxonomy_exists($taxonomy) && !empty($terms)) {
            $numeric = array_filter($terms, 'is_numeric');
            $args['tax_query'] = [
                [
                    'taxonomy' => $taxonomy,
                    'field' => count($numeric) === count($terms) ? 'term_id' : 'slug',
                    'terms' => count($numeric) === count($terms) ? array_map('intval', $terms) : $terms,
                ],
            ];
        }

        $query_id = sanitize_key((string) ($settings['query_id'] ?? ''));
        if ('' !== $query_id) {
            $args = apply_filters('elementor/query/' . $query_id, $args, $settings);
            if (!is_array($args)) {
                $args = [];
            }
        }

        return new \WP_Query(self::apply_filter_args($args, $settings, $filter));
    }

    /**
     * Load-more / paging for the Loop Grid widget.
     *
     * @return void
     */
    public static function handle_ajax(): void
    {
        check_ajax_referer('ka_loop_grid', 'nonce');

        $document_id = absint($_POST['post_id'] ?? 0);
        $element_id = sanitize_text_field(wp_unslash($_POST['element_id'] ?? ''));
        $page = absint($_POST['page'] ?? 1);
        $context_raw = wp_unslash($_POST['context'] ?? '{}');
        $context = json_decode(is_string($context_raw) ? $context_raw : '{}', true);
        if (!is_array($context)) {
            $context = [];
        }

        if ($document_id < 1 || '' === $element_id || !class_exists('Elementor\\Plugin')) {
            wp_send_json_error(['message' => 'invalid'], 400);
        }

        $document = Elementor_Plugin::$instance->documents->get($document_id);
        if (!$document) {
            wp_send_json_error(['message' => 'missing'], 404);
        }

        $settings = self::find_widget_settings($document, $element_id);
        if (empty($settings)) {
            wp_send_json_error(['message' => 'missing'], 404);
        }

        $template_id = absint($settings['template_id'] ?? 0);
        if (!Loop_Renderer::is_template($template_id)) {
            wp_send_json_error(['message' => 'template'], 400);
        }

        $filter = isset($_POST['filter']) ? sanitize_text_field(wp_unslash($_POST['filter'])) : '';
        if (!self::filters_enabled($settings)) {
            $filter = '';
        }

        $layout = self::resolve_layout($settings);
        $query = self::build_query($settings, max(1, $page), $context, $filter);

        // Each response is a fresh document as far as the browser is concerned.
        Loop_Renderer::reset_css_state();

        $pagination = (string) ($settings['pagination_type'] ?? 'none');
        if ('carousel' === $layout) {
            $pagination = 'none';
        }

        wp_send_json_success([
            'html' => self::render_items($query, $template_id, $layout),
            'page' => max(1, $page),
            'max_pages' => max(1, (int) $query->max_num_pages),
            'empty' => !$query->have_posts(),
            'empty_text' => (string) ($settings['nothing_found_text'] ?? ''),
            'pagination' => ('none' !== $pagination && $query->have_posts())
                ? self::render_pagination($settings, $query, $element_id, max(1, $page), $pagination)
                : '',
        ]);
    }

    /**
     * Settings for one widget inside a document.
     *
     * @param \Elementor\Core\Base\Document $document   Document.
     * @param string                        $element_id Element id.
     *
     * @return array<string,mixed>
     */
    private static function find_widget_settings($document, string $element_id): array
    {
        $found = [];

        $walk = static function ($els) use (&$walk, &$found, $element_id) {
            foreach ((array) $els as $el) {
                if (($el['id'] ?? '') === $element_id) {
                    $found = is_array($el['settings'] ?? null) ? $el['settings'] : [];
                    return;
                }
                if (!empty($el['elements'])) {
                    $walk($el['elements']);
                }
            }
        };

        $walk((array) $document->get_elements_data());

        return $found;
    }

    /**
     * Whether the current site can use Loop Grid Pro layouts.
     *
     * @return bool
     */
    private static function can_use_pro(): bool
    {
        return function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
    }

    /**
     * Layout that will actually render.
     *
     * @param array<string,mixed> $settings Widget settings.
     *
     * @return string
     */
    private static function resolve_layout(array $settings): string
    {
        $layout = (string) ($settings['layout'] ?? 'grid');
        if (!in_array($layout, ['grid', 'masonry', 'carousel'], true)) {
            $layout = 'grid';
        }

        if ('grid' !== $layout && !self::can_use_pro()) {
            return 'grid';
        }

        return $layout;
    }

    /**
     * Whether the filter bar is on and allowed.
     *
     * @param array<string,mixed> $settings Widget settings.
     *
     * @return bool
     */
    private static function filters_enabled(array $settings): bool
    {
        return self::can_use_pro() && 'yes' === ($settings['filter_enable'] ?? '');
    }

    /**
     * Taxonomy used by the filter bar.
     *
     * @param array<string,mixed> $settings Widget settings.
     *
     * @return string
     */
    private static function filter_taxonomy(array $settings): string
    {
        $taxonomy = sanitize_key((string) ($settings['filter_taxonomy'] ?? ''));
        if ('' === $taxonomy) {
            $taxonomy = sanitize_key((string) ($settings['taxonomy'] ?? ''));
        }

        return taxonomy_exists($taxonomy) ? $taxonomy : '';
    }

    /**
     * Active filter from the URL, if deeplinks are on.
     *
     * @param string              $element_id Element id.
     * @param array<string,mixed> $settings   Widget settings.
     *
     * @return string
     */
    private static function resolve_filter_term(string $element_id, array $settings): string
    {
        if (!self::filters_enabled($settings) || 'yes' !== ($settings['filter_deeplink'] ?? '')) {
            return '';
        }

        $arg = 'ka-lf-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $element_id);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET[$arg])) {
            return '';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return sanitize_text_field(wp_unslash($_GET[$arg]));
    }

    /**
     * Restrict a query to one filter term.
     *
     * @param array<string,mixed> $args     WP_Query args.
     * @param array<string,mixed> $settings Widget settings.
     * @param string              $filter   Term slug or ID.
     *
     * @return array<string,mixed>
     */
    private static function apply_filter_args(array $args, array $settings, string $filter): array
    {
        $filter = trim($filter);
        if ('' === $filter || !self::filters_enabled($settings)) {
            return $args;
        }

        $taxonomy = self::filter_taxonomy($settings);
        if ('' === $taxonomy) {
            return $args;
        }

        $field = is_numeric($filter) ? 'term_id' : 'slug';
        $term = is_numeric($filter) ? (int) $filter : $filter;
        $clause = [
            'taxonomy' => $taxonomy,
            'field' => $field,
            'terms' => [$term],
        ];

        if (empty($args['tax_query']) || !is_array($args['tax_query'])) {
            $args['tax_query'] = [$clause];
            return $args;
        }

        $args['tax_query']['relation'] = 'AND';
        $args['tax_query'][] = $clause;

        return $args;
    }

    /**
     * Swiper config written onto the grid for the front-end script.
     *
     * @param array<string,mixed> $settings Widget settings.
     *
     * @return array<string,mixed>
     */
    private static function carousel_config(array $settings): array
    {
        $slides = $settings['carousel_slides'] ?? 3;
        if (is_array($slides)) {
            $slides = $slides['size'] ?? 3;
        }

        $tablet = $settings['carousel_slides_tablet'] ?? 2;
        if (is_array($tablet)) {
            $tablet = $tablet['size'] ?? 2;
        }

        $mobile = $settings['carousel_slides_mobile'] ?? 1;
        if (is_array($mobile)) {
            $mobile = $mobile['size'] ?? 1;
        }

        $gap = $settings['gap'] ?? ['size' => 24];
        $gap_size = is_array($gap) ? (float) ($gap['size'] ?? 24) : (float) $gap;

        return [
            'slides' => max(1, (int) $slides),
            'slidesTablet' => max(1, (int) $tablet),
            'slidesMobile' => max(1, (int) $mobile),
            'gap' => $gap_size,
            'autoplay' => 'yes' === ($settings['carousel_autoplay'] ?? ''),
            'delay' => max(500, (int) ($settings['carousel_delay'] ?? 4000)),
            'loop' => 'yes' === ($settings['carousel_loop'] ?? 'yes'),
            'arrows' => 'yes' === ($settings['carousel_arrows'] ?? 'yes'),
            'dots' => 'yes' === ($settings['carousel_dots'] ?? 'yes'),
            'speed' => max(100, (int) ($settings['carousel_speed'] ?? 400)),
        ];
    }

    /**
     * Filter bar markup.
     *
     * @param array<string,mixed> $settings   Widget settings.
     * @param string              $element_id Element id.
     * @param string              $active     Active term.
     *
     * @return string
     */
    private static function render_filter_bar(array $settings, string $element_id, string $active): string
    {
        if (!self::filters_enabled($settings)) {
            return '';
        }

        $taxonomy = self::filter_taxonomy($settings);
        if ('' === $taxonomy) {
            return '';
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        $mode = (string) ($settings['filter_terms_mode'] ?? 'all');
        $list = self::split_list((string) ($settings['filter_terms'] ?? ''));
        if ('include' === $mode && !empty($list)) {
            $terms = array_values(array_filter($terms, static function ($term) use ($list) {
                return in_array((string) $term->term_id, $list, true) || in_array($term->slug, $list, true);
            }));
        } elseif ('exclude' === $mode && !empty($list)) {
            $terms = array_values(array_filter($terms, static function ($term) use ($list) {
                return !in_array((string) $term->term_id, $list, true) && !in_array($term->slug, $list, true);
            }));
        }

        if (empty($terms)) {
            return '';
        }

        $all_label = (string) ($settings['filter_all_label'] ?? __('All', 'king-addons'));
        $deeplink = 'yes' === ($settings['filter_deeplink'] ?? '') ? '1' : '';
        $html = '<div class="king-addons-loop-grid__filters" role="toolbar" data-deeplink="' . esc_attr($deeplink) . '">';
        $html .= self::filter_button($all_label, '', '' === $active);
        foreach ($terms as $term) {
            $html .= self::filter_button($term->name, $term->slug, $active === $term->slug || $active === (string) $term->term_id);
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * One filter button.
     *
     * @param string $label  Label.
     * @param string $value  Term slug, empty for All.
     * @param bool   $active Whether this button is pressed.
     *
     * @return string
     */
    private static function filter_button(string $label, string $value, bool $active): string
    {
        return sprintf(
            '<button type="button" class="king-addons-loop-grid__filter%1$s" data-filter="%2$s" aria-pressed="%3$s">%4$s</button>',
            $active ? ' is-active' : '',
            esc_attr($value),
            $active ? 'true' : 'false',
            esc_html($label)
        );
    }

    /**
     * Page from the URL, if this grid paginates with numbers.
     *
     * @param string $element_id Element id.
     *
     * @return int
     */
    private static function resolve_page_number(string $element_id): int
    {
        $arg = 'ka-loop-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $element_id);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET[$arg])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return max(1, absint(wp_unslash($_GET[$arg])));
        }

        return 1;
    }

    /**
     * Archive / search flags written onto the grid for AJAX.
     *
     * @return array<string,mixed>
     */
    private static function current_context(): array
    {
        $object = get_queried_object();
        $post_type = get_query_var('post_type');
        if (is_array($post_type)) {
            $post_type = implode(',', array_map('sanitize_key', $post_type));
        }

        $context = [
            'is_search' => is_search(),
            's' => is_search() ? (string) get_query_var('s') : '',
            'is_home' => is_home(),
            'is_post_type_archive' => is_post_type_archive(),
            'post_type' => is_string($post_type) ? $post_type : '',
            'taxonomy' => '',
            'term' => '',
            'author' => is_author() ? (int) get_query_var('author') : 0,
        ];

        if ($object instanceof \WP_Term) {
            $context['taxonomy'] = $object->taxonomy;
            $context['term'] = $object->slug;
        }

        return $context;
    }

    /**
     * Rebuild query vars from a stored archive context.
     *
     * @param array<string,mixed> $context Context.
     *
     * @return array<string,mixed>
     */
    private static function context_to_query_vars(array $context): array
    {
        $args = [
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
        ];

        if (!empty($context['is_search'])) {
            $args['s'] = (string) ($context['s'] ?? '');
            $args['post_type'] = 'any';
            return $args;
        }

        if (!empty($context['taxonomy']) && !empty($context['term'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => sanitize_key((string) $context['taxonomy']),
                    'field' => 'slug',
                    'terms' => [(string) $context['term']],
                ],
            ];
            $args['post_type'] = 'any';
            return $args;
        }

        if (!empty($context['author'])) {
            $args['author'] = absint($context['author']);
        }

        $post_type = sanitize_key((string) ($context['post_type'] ?? ''));
        if ('' !== $post_type && post_type_exists($post_type)) {
            $args['post_type'] = $post_type;
        }

        return $args;
    }

    /**
     * Document the widget is rendering in.
     *
     * @return int
     */
    private static function current_document_id(): int
    {
        if (class_exists('Elementor\\Plugin')) {
            $document = Elementor_Plugin::$instance->documents->get_current();
            if ($document) {
                return (int) $document->get_main_id();
            }
        }

        return (int) get_the_ID();
    }

    /**
     * Post types a custom query can target.
     *
     * @return array<string,string>
     */
    private function get_post_type_options(): array
    {
        $skip = [
            'attachment',
            'elementor_library',
            'e-floating-buttons',
            'king-addons-el-hf',
            'king_addons_ext_pb',
            'king-addons-fb-sub',
        ];

        $options = [];
        foreach (get_post_types(['public' => true], 'objects') as $slug => $type) {
            if (in_array($slug, $skip, true)) {
                continue;
            }
            $options[$slug] = $type->labels->name ?? $slug;
        }

        return $options;
    }

    /**
     * Public taxonomies plus an empty option.
     *
     * @return array<string,string>
     */
    private function get_taxonomy_options(): array
    {
        $options = ['' => esc_html__('— Select —', 'king-addons')];

        foreach (get_taxonomies(['public' => true], 'objects') as $slug => $taxonomy) {
            $options[$slug] = $taxonomy->labels->name ?? $slug;
        }

        return $options;
    }

    /**
     * Comma-separated slugs or IDs.
     *
     * @param string $value Raw value.
     *
     * @return array<int,string>
     */
    private static function split_list(string $value): array
    {
        $parts = preg_split('/[\s,]+/', $value) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ('' !== $part) {
                $out[] = $part;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Comma-separated positive IDs.
     *
     * @param string $value Raw value.
     *
     * @return array<int,int>
     */
    private static function id_list(string $value): array
    {
        $ids = [];
        foreach (self::split_list($value) as $part) {
            $id = absint($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
