<?php
/**
 * Theme Builder Author Box widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays the post author information.
 */
class TB_Author_Box extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-author-box';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Author Box', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-person';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-author-box-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-author-box-script'];
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
        return ['author', 'bio', 'profile', 'theme builder', 'king-addons'];
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
                'default' => 'side',
                'options' => [
                    'side' => esc_html__('Avatar Left', 'king-addons'),
                    'stacked' => esc_html__('Avatar Top', 'king-addons'),
                    'center' => esc_html__('Centered', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_show_avatar',
            [
                'label' => esc_html__('Show Avatar', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_avatar_size',
            [
                'label' => esc_html__('Avatar Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 30, 'max' => 200],
                ],
                'default' => [
                    'size' => 72,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_show_avatar' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_link_type',
            [
                'label' => esc_html__('Name Link', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'archive',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'archive' => esc_html__('Author Archive', 'king-addons'),
                    'custom' => $is_pro ?
                        esc_html__('Custom URL', 'king-addons') :
                        sprintf(__('Custom URL %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_control(
            'kng_custom_link',
            [
                'label' => esc_html__('Custom URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://example.com',
                'condition' => [
                    'kng_link_type' => 'custom',
                ],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_show_website',
            [
                'label' => esc_html__('Show Website', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_bio',
            [
                'label' => esc_html__('Show Bio', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        if ($is_pro) {
            $repeater = new Repeater();
            $repeater->add_control(
                'kng_social_label',
                [
                    'label' => esc_html__('Label', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__('Social', 'king-addons'),
                ]
            );
            $repeater->add_control(
                'kng_social_url',
                [
                    'label' => esc_html__('URL', 'king-addons'),
                    'type' => Controls_Manager::URL,
                    'placeholder' => 'https://example.com',
                ]
            );

            $this->add_control(
                'kng_social_links',
                [
                    'label' => esc_html__('Social Links', 'king-addons'),
                    'type' => Controls_Manager::REPEATER,
                    'fields' => $repeater->get_controls(),
                    'title_field' => '{{{ kng_social_label }}}',
                ]
            );
        }

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
                'label' => esc_html__('Box', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_box_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-box' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_box_border',
                'selector' => '{{WRAPPER}} .king-addons-tb-author-box',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-tb-author-box',
            ]
        );

        $this->add_responsive_control(
            'kng_box_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_content_style',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_name_typography',
                'label' => esc_html__('Name Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-author-box__name',
            ]
        );

        $this->add_control(
            'kng_name_color',
            [
                'label' => esc_html__('Name Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-box__name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-author-box__name a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_bio_typography',
                'label' => esc_html__('Bio Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-author-box__bio, {{WRAPPER}} .king-addons-tb-author-box__website',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-box__bio' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-author-box__website' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-author-box__website a' => 'color: {{VALUE}};',
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
            'tb-author-box',
            [
                'Custom link target for author name',
                'Social icons list with custom URLs',
                'Additional layout and badge styles',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Settings.
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

        $author_id = (int) $post->post_author;
        $name = get_the_author_meta('display_name', $author_id);
        $bio = get_the_author_meta('description', $author_id);
        $website = get_the_author_meta('user_url', $author_id);

        $layout = $settings['kng_layout'] ?? 'side';
        $classes = ['king-addons-tb-author-box', 'king-addons-tb-author-box--' . $layout];

        echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';

        if ('yes' === ($settings['kng_show_avatar'] ?? 'yes')) {
            $size = isset($settings['kng_avatar_size']['size']) ? (int) $settings['kng_avatar_size']['size'] : 72;
            echo '<div class="king-addons-tb-author-box__avatar">' . get_avatar($author_id, $size) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        echo '<div class="king-addons-tb-author-box__content">';

        if ($name) {
            $name_html = esc_html($name);
            $link_type = $settings['kng_link_type'] ?? 'archive';
            if ('archive' === $link_type) {
                $url = get_author_posts_url($author_id);
                $name_html = '<a href="' . esc_url($url) . '">' . $name_html . '</a>';
            } elseif ('custom' === $link_type && $is_pro && !empty($settings['kng_custom_link']['url'])) {
                $url = $settings['kng_custom_link']['url'];
                $name_html = '<a href="' . esc_url($url) . '">' . $name_html . '</a>';
            }

            echo '<div class="king-addons-tb-author-box__name">' . $name_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        if ('yes' === ($settings['kng_show_bio'] ?? 'yes') && $bio) {
            echo '<div class="king-addons-tb-author-box__bio">' . esc_html($bio) . '</div>';
        }

        if ('yes' === ($settings['kng_show_website'] ?? 'yes') && $website) {
            echo '<div class="king-addons-tb-author-box__website"><a href="' . esc_url($website) . '">' . esc_html($website) . '</a></div>';
        }

        if ($is_pro && !empty($settings['kng_social_links'])) {
            echo '<div class="king-addons-tb-author-box__social">';
            foreach ($settings['kng_social_links'] as $social) {
                if (empty($social['kng_social_url']['url'])) {
                    continue;
                }
                $label = $social['kng_social_label'] ?? esc_html__('Social', 'king-addons');
                echo '<a class="king-addons-tb-author-box__social-link" href="' . esc_url($social['kng_social_url']['url']) . '">' . esc_html($label) . '</a>';
            }
            echo '</div>';
        }

        echo '</div></div>';
    }
}
