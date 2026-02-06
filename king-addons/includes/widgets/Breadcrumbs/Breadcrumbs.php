<?php
/**
 * Breadcrumbs Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Breadcrumbs widget.
 */
class Breadcrumbs extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-breadcrumbs';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Breadcrumbs', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-breadcrumbs';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-breadcrumbs-style',
        ];
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
        return ['breadcrumb', 'navigation', 'trail', 'path', 'king-addons'];
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
        $this->register_content_controls();
        $this->register_style_controls();
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
        $items = $this->build_breadcrumbs($settings);

        if (empty($items)) {
            return;
        }

        $this->render_output($settings, $items);
    }

    /**
     * Content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Breadcrumbs', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_home',
            [
                'label' => esc_html__('Show Home', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_home_label',
            [
                'label' => esc_html__('Home Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Home', 'king-addons'),
                'condition' => [
                    'kng_show_home' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '/',
                'placeholder' => '/',
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'default' => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-breadcrumbs' => '--kng-breadcrumbs-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_show_current',
            [
                'label' => esc_html__('Show Current Item', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_hide_page_parents',
            [
                'label' => esc_html__('Hide Parent Pages', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_use_custom_path!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_hide_post_type_archive',
            [
                'label' => esc_html__('Hide Post Type Archive', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_use_custom_path!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_hide_taxonomy_segments',
            [
                'label' => esc_html__('Hide Taxonomy Segments', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_use_custom_path!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_enable_schema',
            [
                'label' => esc_html__('JSON-LD Schema', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_use_custom_path',
            [
                'label' => esc_html__('Use Custom Path', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Manually define breadcrumb items. Disable "Show Home" if you add Home manually.', 'king-addons'),
                'separator' => 'before',
            ]
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'kng_custom_label',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_custom_url',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://',
            ]
        );

        $repeater->add_control(
            'kng_custom_is_current',
            [
                'label' => esc_html__('Mark as Current', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_custom_path',
            [
                'label' => esc_html__('Custom Items', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ kng_custom_label }}}',
                'condition' => [
                    'kng_use_custom_path' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_custom_append_current',
            [
                'label' => esc_html__('Append Current Page', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Adds the current page if no item is marked as current.', 'king-addons'),
                'condition' => [
                    'kng_use_custom_path' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @return void
     */
    protected function register_style_controls(): void
    {
        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Styles', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'toggle' => false,
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-breadcrumbs__list' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_typography',
                'selector' => '{{WRAPPER}} .king-addons-breadcrumbs__link, {{WRAPPER}} .king-addons-breadcrumbs__text, {{WRAPPER}} .king-addons-breadcrumbs__separator',
            ]
        );

        $this->add_control(
            'kng_link_color',
            [
                'label' => esc_html__('Link Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-breadcrumbs__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_link_color_hover',
            [
                'label' => esc_html__('Link Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-breadcrumbs__link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_current_color',
            [
                'label' => esc_html__('Current Item Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-breadcrumbs__current' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_separator_color',
            [
                'label' => esc_html__('Separator Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-breadcrumbs__separator' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_container_border',
                'selector' => '{{WRAPPER}} .king-addons-breadcrumbs',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build breadcrumbs array.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function build_breadcrumbs(array $settings): array
    {
        $items = [];
        $show_home = ($settings['kng_show_home'] ?? 'yes') === 'yes';
        $show_current = ($settings['kng_show_current'] ?? 'yes') === 'yes';
        $use_custom_path = ($settings['kng_use_custom_path'] ?? '') === 'yes';
        $home_label = $settings['kng_home_label'] ?? esc_html__('Home', 'king-addons');
        $hide_page_parents = ($settings['kng_hide_page_parents'] ?? '') === 'yes';
        $hide_post_type_archive = ($settings['kng_hide_post_type_archive'] ?? '') === 'yes';
        $hide_taxonomy_segments = ($settings['kng_hide_taxonomy_segments'] ?? '') === 'yes';

        if ($show_home) {
            $items[] = $this->build_home_item($home_label);
        }

        if ($use_custom_path) {
            $custom_items = $this->build_custom_path($settings, $show_current);
            if (!empty($items) && !empty($custom_items) && $this->is_home_item($custom_items[0])) {
                array_shift($custom_items);
            }

            return array_merge($items, $custom_items);
        }

        if (is_front_page()) {
            return $items;
        }

        if (is_home()) {
            $items[] = [
                'label' => get_the_title(get_option('page_for_posts')) ?: esc_html__('Blog', 'king-addons'),
                'url' => '',
                'current' => true,
            ];

            return $items;
        }

        if (is_singular()) {
            $post = get_post();
            if (!$post) {
                return $items;
            }

            $post_type = get_post_type_object(get_post_type($post));
            if ($post_type && !in_array($post_type->name, ['post', 'page'], true) && !empty($post_type->has_archive) && !$hide_post_type_archive) {
                $items[] = [
                    'label' => $post_type->labels->name,
                    'url' => get_post_type_archive_link($post_type->name),
                    'current' => false,
                ];
            }

            if (is_page($post) && !$hide_page_parents) {
                $ancestors = array_reverse(get_post_ancestors($post));
                foreach ($ancestors as $ancestor_id) {
                    $items[] = [
                        'label' => get_the_title($ancestor_id),
                        'url' => get_permalink($ancestor_id),
                        'current' => false,
                    ];
                }
            } elseif ('post' === get_post_type($post) && !$hide_taxonomy_segments) {
                $categories = get_the_category($post->ID);
                if (!empty($categories)) {
                    $primary = $categories[0];
                    $cat_ancestors = array_reverse(get_ancestors($primary->term_id, 'category'));
                    foreach ($cat_ancestors as $cat_id) {
                        $items[] = [
                            'label' => get_cat_name($cat_id),
                            'url' => get_category_link($cat_id),
                            'current' => false,
                        ];
                    }

                    $items[] = [
                        'label' => $primary->name,
                        'url' => get_category_link($primary),
                        'current' => false,
                    ];
                }
            }

            if ($show_current) {
                $items[] = [
                    'label' => get_the_title($post),
                    'url' => '',
                    'current' => true,
                ];
            }

            return $items;
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && !is_wp_error($term)) {
                if (!$hide_taxonomy_segments) {
                    $ancestors = array_reverse(get_ancestors($term->term_id, $term->taxonomy));
                    foreach ($ancestors as $ancestor_id) {
                        $items[] = [
                            'label' => get_term_field('name', $ancestor_id, $term->taxonomy),
                            'url' => get_term_link((int) $ancestor_id, $term->taxonomy),
                            'current' => false,
                        ];
                    }
                }

                $items[] = [
                    'label' => $term->name,
                    'url' => '',
                    'current' => true,
                ];
            }

            return $items;
        }

        if (is_search()) {
            $items[] = [
                'label' => sprintf(esc_html__('Search: %s', 'king-addons'), get_search_query()),
                'url' => '',
                'current' => true,
            ];

            return $items;
        }

        if (is_404()) {
            $items[] = [
                'label' => esc_html__('404 Not Found', 'king-addons'),
                'url' => '',
                'current' => true,
            ];

            return $items;
        }

        if (is_author()) {
            $items[] = [
                'label' => esc_html__('Author', 'king-addons'),
                'url' => '',
                'current' => true,
            ];
        }

        if (is_date()) {
            if (is_year()) {
                $items[] = [
                    'label' => get_the_date(_x('Y', 'yearly archives date format', 'king-addons')),
                    'url' => '',
                    'current' => true,
                ];
            } elseif (is_month()) {
                $items[] = [
                    'label' => get_the_date(_x('F Y', 'monthly archives date format', 'king-addons')),
                    'url' => '',
                    'current' => true,
                ];
            } elseif (is_day()) {
                $items[] = [
                    'label' => get_the_date(),
                    'url' => '',
                    'current' => true,
                ];
            }
        }

        return $items;
    }

    /**
     * Build home item.
     *
     * @param string $label Home label.
     *
     * @return array<string, mixed>
     */
    protected function build_home_item(string $label): array
    {
        return [
            'label' => $label,
            'url' => home_url('/'),
            'current' => false,
        ];
    }

    /**
     * Check whether an item is the home item.
     *
     * @param array<string, mixed> $item Breadcrumb item.
     *
     * @return bool
     */
    protected function is_home_item(array $item): bool
    {
        if (empty($item['url'])) {
            return false;
        }

        $home_url = trailingslashit(home_url('/'));
        $item_url = trailingslashit((string) $item['url']);

        return $home_url === $item_url;
    }

    /**
     * Build custom breadcrumb path from settings.
     *
     * @param array<string, mixed> $settings    Widget settings.
     * @param bool                 $show_current Whether to append current item.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function build_custom_path(array $settings, bool $show_current): array
    {
        $items = [];
        $custom_items = $settings['kng_custom_path'] ?? [];

        foreach ($custom_items as $custom_item) {
            $label = trim((string) ($custom_item['kng_custom_label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $raw_url = $custom_item['kng_custom_url'] ?? '';
            if (is_array($raw_url)) {
                $raw_url = $raw_url['url'] ?? '';
            }

            $items[] = [
                'label' => $label,
                'url' => $raw_url,
                'current' => ($custom_item['kng_custom_is_current'] ?? '') === 'yes',
            ];
        }

        $has_current = array_filter(
            $items,
            static function (array $item): bool {
                return !empty($item['current']);
            }
        );

        if (empty($has_current) && $show_current && ($settings['kng_custom_append_current'] ?? 'yes') === 'yes') {
            $items[] = $this->build_current_item();
        }

        return $items;
    }

    /**
     * Build current breadcrumb item for fallback cases.
     *
     * @return array<string, mixed>
     */
    protected function build_current_item(): array
    {
        return [
            'label' => $this->get_current_label(),
            'url' => $this->get_current_url(),
            'current' => true,
        ];
    }

    /**
     * Resolve label for current page.
     *
     * @return string
     */
    protected function get_current_label(): string
    {
        if (is_search()) {
            return sprintf(esc_html__('Search: %s', 'king-addons'), get_search_query());
        }

        if (is_404()) {
            return esc_html__('404 Not Found', 'king-addons');
        }

        if (is_home()) {
            $posts_page = get_option('page_for_posts');
            if ($posts_page) {
                return get_the_title($posts_page) ?: esc_html__('Blog', 'king-addons');
            }
        }

        if (is_singular()) {
            $post = get_post();
            if ($post) {
                return get_the_title($post);
            }
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && !is_wp_error($term) && isset($term->name)) {
                return (string) $term->name;
            }
        }

        if (is_author()) {
            $author = get_queried_object();
            if ($author && !is_wp_error($author) && isset($author->display_name)) {
                return (string) $author->display_name;
            }

            return esc_html__('Author', 'king-addons');
        }

        if (is_year()) {
            return get_the_date(_x('Y', 'yearly archives date format', 'king-addons'));
        }

        if (is_month()) {
            return get_the_date(_x('F Y', 'monthly archives date format', 'king-addons'));
        }

        if (is_day()) {
            return get_the_date();
        }

        $title = wp_get_document_title();

        return is_string($title) ? wp_strip_all_tags($title) : '';
    }

    /**
     * Resolve current page URL.
     *
     * @return string
     */
    protected function get_current_url(): string
    {
        if (is_home()) {
            $posts_page = get_option('page_for_posts');
            if ($posts_page) {
                return get_permalink($posts_page);
            }

            return home_url('/');
        }

        if (is_singular()) {
            $post = get_post();
            if ($post) {
                return get_permalink($post);
            }
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && !is_wp_error($term)) {
                $link = get_term_link((int) $term->term_id, $term->taxonomy);
                if (!is_wp_error($link)) {
                    return $link;
                }
            }
        }

        if (is_author()) {
            $author = get_queried_object();
            if ($author && !is_wp_error($author) && isset($author->ID)) {
                return get_author_posts_url((int) $author->ID);
            }
        }

        if (is_day()) {
            return get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
        }

        if (is_month()) {
            return get_month_link(get_query_var('year'), get_query_var('monthnum'));
        }

        if (is_year()) {
            return get_year_link(get_query_var('year'));
        }

        if (is_search()) {
            return get_search_link();
        }

        if (is_post_type_archive()) {
            $post_type = get_query_var('post_type');
            if (!empty($post_type)) {
                $archive_link = get_post_type_archive_link($post_type);
                if ($archive_link) {
                    return $archive_link;
                }
            }
        }

        global $wp;
        $request = isset($wp->request) ? $wp->request : '';

        return home_url($request ? '/' . ltrim($request, '/') : '/');
    }

    /**
     * Maybe render schema markup.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param array<int, array>    $items    Items.
     *
     * @return void
     */
    protected function maybe_render_schema_markup(array $settings, array $items): void
    {
        if (!$this->is_schema_enabled($settings)) {
            return;
        }

        $this->render_schema_markup($items);
    }

    /**
     * Determine whether schema is enabled.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return bool
     */
    protected function is_schema_enabled(array $settings): bool
    {
        if (!array_key_exists('kng_enable_schema', $settings) && !array_key_exists('kng_schema_markup', $settings)) {
            return true;
        }

        if (($settings['kng_enable_schema'] ?? '') === 'yes') {
            return true;
        }

        return ($settings['kng_schema_markup'] ?? '') === 'yes';
    }

    /**
     * Render JSON-LD schema for breadcrumbs.
     *
     * @param array<int, array<string, mixed>> $items Breadcrumb items.
     *
     * @return void
     */
    protected function render_schema_markup(array $items): void
    {
        $position = 1;
        $elements = [];
        foreach ($items as $item) {
            $url = $item['url'] ?? '';
            if (empty($url) && !empty($item['current'])) {
                $url = $this->get_current_url();
            }

            if (empty($url)) {
                continue;
            }

            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => wp_strip_all_tags((string) ($item['label'] ?? '')),
                'item' => esc_url_raw($url),
            ];
            $position++;
        }

        if (empty($elements)) {
            return;
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($data) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Render breadcrumb markup.
     *
     * @param array<string, mixed>   $settings Settings.
     * @param array<int, array>      $items    Items.
     *
     * @return void
     */
    protected function render_output(array $settings, array $items): void
    {
        $separator = $settings['kng_separator'] ?? '/';
        $schema_enabled = $this->is_schema_enabled($settings);
        $nav_schema_attr = $schema_enabled ? ' itemscope itemtype="https://schema.org/BreadcrumbList"' : '';
        ?>
        <nav class="king-addons-breadcrumbs" aria-label="<?php echo esc_attr__('Breadcrumbs', 'king-addons'); ?>"<?php echo $nav_schema_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <ol class="king-addons-breadcrumbs__list">
                <?php foreach ($items as $index => $item) : ?>
                    <?php
                    $is_current = !empty($item['current']);
                    $raw_url = $item['url'] ?? '';
                    $resolved_url = $is_current && empty($raw_url) ? $this->get_current_url() : $raw_url;
                    $label = $item['label'] ?? '';
                    $position = $index + 1;
                    $item_schema_attr = $schema_enabled ? ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"' : '';
                    ?>
                    <li class="king-addons-breadcrumbs__item"<?php echo $item_schema_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                        <?php if (!empty($resolved_url) && !$is_current) : ?>
                            <a class="king-addons-breadcrumbs__link" href="<?php echo esc_url($resolved_url); ?>"<?php echo $schema_enabled ? ' itemprop="item"' : ''; ?>>
                                <span class="king-addons-breadcrumbs__text"<?php echo $schema_enabled ? ' itemprop="name"' : ''; ?>>
                                    <?php echo esc_html($label); ?>
                                </span>
                            </a>
                        <?php else : ?>
                            <span class="king-addons-breadcrumbs__text <?php echo $is_current ? 'king-addons-breadcrumbs__current' : ''; ?>"<?php echo $schema_enabled ? ' itemprop="name"' : ''; ?><?php echo $is_current ? ' aria-current="page"' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($schema_enabled) : ?>
                            <meta itemprop="position" content="<?php echo esc_attr((string) $position); ?>"/>
                            <?php if (!empty($resolved_url)) : ?>
                                <meta itemprop="item" content="<?php echo esc_url($resolved_url); ?>"/>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($index !== array_key_last($items)) : ?>
                            <span class="king-addons-breadcrumbs__separator"><?php echo esc_html($separator); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php
        $this->maybe_render_schema_markup($settings, $items);
    }

    /**
     * Pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'breadcrumbs', [
                'Icon separators and home icon',
                'Prefix label and max depth',
                'Vertical & multi-row layouts',
                'Separator icon sizing controls',
            ]);
        }
    }
}







