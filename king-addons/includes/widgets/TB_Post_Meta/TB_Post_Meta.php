<?php
/**
 * Theme Builder Post Meta widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays post meta items such as date, author, categories, and comments.
 */
class TB_Post_Meta extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-post-meta';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Post Meta', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-meta-data';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-meta-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-meta-script'];
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
        return ['meta', 'author', 'date', 'categories', 'tags', 'king-addons'];
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
     * Render widget output.
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
        $repeater = new Repeater();
        $repeater->add_control(
            'kng_meta_type',
            [
                'label' => esc_html__('Meta Item', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'author' => esc_html__('Author', 'king-addons'),
                    'categories' => esc_html__('Categories', 'king-addons'),
                    'tags' => esc_html__('Tags', 'king-addons'),
                    'comments' => esc_html__('Comments Count', 'king-addons'),
                    'reading_time' => $is_pro ?
                        esc_html__('Reading Time', 'king-addons') :
                        sprintf(__('Reading Time %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'date',
            ]
        );

        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'inline',
                'options' => [
                    'inline' => esc_html__('Inline', 'king-addons'),
                    'stacked' => esc_html__('Stacked', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '•',
                'condition' => [
                    'kng_layout' => 'inline',
                ],
            ]
        );

        $this->add_control(
            'kng_date_format',
            [
                'label' => esc_html__('Date Format', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'default' => esc_html__('Default', 'king-addons'),
                    'medium' => esc_html__('Medium', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'default' => 'default',
            ]
        );

        $this->add_control(
            'kng_date_format_custom',
            [
                'label' => esc_html__('Custom Date Format', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('F j, Y', 'king-addons'),
                'condition' => [
                    'kng_date_format' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_author_link',
            [
                'label' => esc_html__('Link Author to Archive', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_terms_link',
            [
                'label' => esc_html__('Link Categories/Tags', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_meta_items',
            [
                'label' => esc_html__('Meta Items', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ kng_meta_type }}}',
                'default' => [
                    ['kng_meta_type' => 'date'],
                    ['kng_meta_type' => 'author'],
                    ['kng_meta_type' => 'categories'],
                    ['kng_meta_type' => 'tags'],
                    ['kng_meta_type' => 'comments'],
                ],
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
                'selector' => '{{WRAPPER}} .king-addons-tb-post-meta, {{WRAPPER}} .king-addons-tb-post-meta a',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-meta' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-post-meta a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_text_color_hover',
            [
                'label' => esc_html__('Link Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-meta a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'default' => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-meta' => '--kng-meta-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pro upsell section.
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
            'tb-post-meta',
            [
                'Reading time meta item',
                'Icons and badge styles for taxonomies',
                'Separate typography for labels and values',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether Pro is enabled.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }

        $items = $settings['kng_meta_items'] ?? [];
        if (empty($items)) {
            $items = [
                ['kng_meta_type' => 'date'],
                ['kng_meta_type' => 'author'],
                ['kng_meta_type' => 'categories'],
                ['kng_meta_type' => 'tags'],
                ['kng_meta_type' => 'comments'],
            ];
        }

        $output = [];
        $separator = isset($settings['kng_separator']) ? (string) $settings['kng_separator'] : '•';
        $terms_link = 'yes' === ($settings['kng_terms_link'] ?? 'yes');
        $author_link = 'yes' === ($settings['kng_author_link'] ?? 'yes');

        foreach ($items as $item) {
            $type = $item['kng_meta_type'] ?? '';
            switch ($type) {
                case 'date':
                    $output[] = esc_html($this->get_formatted_date($settings, $post));
                    break;
                case 'author':
                    $author = get_the_author_meta('display_name', $post->post_author);
                    if ($author) {
                        if ($author_link) {
                            $url = get_author_posts_url($post->post_author);
                            $output[] = sprintf(
                                '<a href="%1$s">%2$s</a>',
                                esc_url($url),
                                esc_html($author)
                            );
                        } else {
                            $output[] = esc_html($author);
                        }
                    }
                    break;
                case 'categories':
                    $cats = get_the_category($post->ID);
                    if (!empty($cats)) {
                        $cat_parts = [];
                        foreach ($cats as $cat) {
                            $name = esc_html($cat->name);
                            if ($terms_link) {
                                $cat_parts[] = '<a href="' . esc_url(get_category_link($cat)) . '">' . $name . '</a>';
                            } else {
                                $cat_parts[] = $name;
                            }
                        }
                        $output[] = implode(', ', $cat_parts);
                    }
                    break;
                case 'tags':
                    $tags = get_the_tags($post->ID);
                    if (!empty($tags)) {
                        $tag_parts = [];
                        foreach ($tags as $tag) {
                            $name = esc_html($tag->name);
                            if ($terms_link) {
                                $tag_parts[] = '<a href="' . esc_url(get_tag_link($tag)) . '">' . $name . '</a>';
                            } else {
                                $tag_parts[] = $name;
                            }
                        }
                        $output[] = implode(', ', $tag_parts);
                    }
                    break;
                case 'comments':
                    $count = get_comments_number($post);
                    $output[] = sprintf(
                        _n('%s comment', '%s comments', $count, 'king-addons'),
                        number_format_i18n($count)
                    );
                    break;
                case 'reading_time':
                    if ($is_pro) {
                        $output[] = esc_html($this->calculate_reading_time($post));
                    }
                    break;
                default:
                    break;
            }
        }

        $output = array_filter($output);
        if (empty($output)) {
            return;
        }

        $layout = $settings['kng_layout'] ?? 'inline';
        $wrapper_classes = ['king-addons-tb-post-meta', 'king-addons-tb-post-meta--' . $layout];

        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '">';

        if ('stacked' === $layout) {
            foreach ($output as $part) {
                echo '<div class="king-addons-tb-post-meta__item">' . wp_kses_post($part) . '</div>';
            }
        } else {
            $last_index = count($output) - 1;
            foreach ($output as $index => $part) {
                echo '<span class="king-addons-tb-post-meta__item">' . wp_kses_post($part) . '</span>';
                if ($index !== $last_index) {
                    echo '<span class="king-addons-tb-post-meta__separator">' . esc_html($separator) . '</span>';
                }
            }
        }

        echo '</div>';
    }

    /**
     * Get formatted date.
     *
     * @param array<string, mixed> $settings Settings.
     * @param \WP_Post             $post     Post instance.
     *
     * @return string
     */
    protected function get_formatted_date(array $settings, \WP_Post $post): string
    {
        $format = $settings['kng_date_format'] ?? 'default';
        if ('custom' === $format && !empty($settings['kng_date_format_custom'])) {
            return get_the_date(sanitize_text_field($settings['kng_date_format_custom']), $post);
        }

        if ('medium' === $format) {
            return get_the_date('M j, Y', $post);
        }

        return get_the_date('', $post);
    }

    /**
     * Calculate reading time.
     *
     * @param \WP_Post $post Post instance.
     *
     * @return string
     */
    protected function calculate_reading_time(\WP_Post $post): string
    {
        $content = wp_strip_all_tags((string) $post->post_content);
        $words = str_word_count($content);
        $minutes = max(1, (int) ceil($words / 200));

        return sprintf(
            _n('%s min read', '%s mins read', $minutes, 'king-addons'),
            number_format_i18n($minutes)
        );
    }
}
