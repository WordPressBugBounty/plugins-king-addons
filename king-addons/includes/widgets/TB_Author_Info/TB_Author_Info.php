<?php
/**
 * Theme Builder Author Info widget (Free).
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
 * Shows the author whose archive is being viewed.
 *
 * TB - Author Box describes the author of a single post; this one reads the
 * author from the archive query, so it belongs on an Author template.
 */
class TB_Author_Info extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-author-info';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Author Info', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-tb-author-info';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-author-info-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-author-info-script'];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons-theme-builder'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['author', 'archive', 'profile', 'theme builder', 'king-addons'];
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
            'kng_show_avatar',
            [
                'label' => esc_html__('Avatar', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'kng_avatar_size',
            [
                'label' => esc_html__('Avatar Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => ['px' => ['min' => 32, 'max' => 200]],
                'default' => ['unit' => 'px', 'size' => 96],
                'condition' => ['kng_show_avatar' => 'yes'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-info__avatar img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_show_bio',
            [
                'label' => esc_html__('Biography', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_post_count',
            [
                'label' => esc_html__('Post Count', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_post_count_format',
            [
                'label' => esc_html__('Post Count Format', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('{count} posts', 'king-addons'),
                'condition' => ['kng_show_post_count' => 'yes'],
            ]
        );

        $this->add_control(
            'kng_show_website',
            [
                'label' => esc_html__('Website Link', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_social_links',
            [
                'label' => $is_pro ?
                    esc_html__('Social Links', 'king-addons') :
                    sprintf(__('Social Links %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
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
                'name' => 'kng_name_typography',
                'selector' => '{{WRAPPER}} .king-addons-tb-author-info__name',
            ]
        );

        $this->add_control(
            'kng_name_color',
            [
                'label' => esc_html__('Name Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-info__name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_bio_color',
            [
                'label' => esc_html__('Biography Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-info__bio' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_count_color',
            [
                'label' => esc_html__('Post Count Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-info__count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-info' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors_dictionary' => [
                    'flex-start' => 'align-items: flex-start; text-align: left;',
                    'center' => 'align-items: center; text-align: center;',
                    'flex-end' => 'align-items: flex-end; text-align: right;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-author-info' => '{{VALUE}}',
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
            'tb-author-info',
            [
                'Social profile links',
                'Custom avatar source',
                'Follow button',
            ]
        );
    }

    /**
     * Resolve the author for the current view.
     *
     * @return \WP_User|null
     */
    protected function resolve_author(): ?\WP_User
    {
        $author = null;

        if (is_author()) {
            $queried = get_queried_object();
            if ($queried instanceof \WP_User) {
                $author = $queried;
            }
        }

        if (!$author instanceof \WP_User && is_singular()) {
            $post_author = (int) get_post_field('post_author', (int) get_the_ID());
            if ($post_author) {
                $found = get_user_by('id', $post_author);
                if ($found instanceof \WP_User) {
                    $author = $found;
                }
            }
        }

        if (!$author instanceof \WP_User && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $users = get_users(['number' => 1, 'orderby' => 'ID']);
            if (!empty($users[0]) && $users[0] instanceof \WP_User) {
                $author = $users[0];
            }
        }

        return $author instanceof \WP_User ? $author : null;
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
        $author = $this->resolve_author();

        if (!$author instanceof \WP_User) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                Core::renderEditorHint(
                    esc_html__('This widget reads the author of the current archive. Preview the template from an author URL to see real data.', 'king-addons')
                );
            }
            return;
        }

        $show_avatar = 'yes' === ($settings['kng_show_avatar'] ?? 'yes');
        $show_bio = 'yes' === ($settings['kng_show_bio'] ?? 'yes');
        $show_count = 'yes' === ($settings['kng_show_post_count'] ?? 'yes');
        $show_site = 'yes' === ($settings['kng_show_website'] ?? '');

        echo '<div class="king-addons-tb-author-info">';

        if ($show_avatar) {
            $avatar = get_avatar($author->ID, 192);
            if ($avatar) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<div class="king-addons-tb-author-info__avatar">' . $avatar . '</div>';
            }
        }

        echo '<div class="king-addons-tb-author-info__name">' . esc_html($author->display_name) . '</div>';

        if ($show_count) {
            $count = (int) count_user_posts($author->ID, 'post', true);
            $format = (string) ($settings['kng_post_count_format'] ?? '{count} posts');
            $text = strtr($format, ['{count}' => number_format_i18n($count)]);
            echo '<div class="king-addons-tb-author-info__count">' . esc_html($text) . '</div>';
        }

        if ($show_bio) {
            $bio = (string) get_the_author_meta('description', $author->ID);
            if ('' !== $bio) {
                echo '<div class="king-addons-tb-author-info__bio">' . esc_html($bio) . '</div>';
            }
        }

        if ($show_site) {
            $url = (string) $author->user_url;
            if ('' !== $url) {
                echo '<a class="king-addons-tb-author-info__site" href="' . esc_url($url) . '" rel="nofollow">'
                    . esc_html($url) . '</a>';
            }
        }

        if ($is_pro && 'yes' === ($settings['kng_social_links'] ?? '')) {
            $links = [];
            $networks = [
                'facebook' => esc_html__('Facebook', 'king-addons'),
                'twitter' => esc_html__('Twitter', 'king-addons'),
                'instagram' => esc_html__('Instagram', 'king-addons'),
                'linkedin' => esc_html__('LinkedIn', 'king-addons'),
                'youtube' => esc_html__('YouTube', 'king-addons'),
            ];
            foreach ($networks as $meta_key => $label) {
                $url = (string) get_user_meta($author->ID, $meta_key, true);
                if ('' === $url) {
                    $url = (string) get_the_author_meta($meta_key, $author->ID);
                }
                if ('' === $url || !preg_match('#^https?://#i', $url)) {
                    continue;
                }
                $links[] = '<a class="king-addons-tb-author-info__social-link" href="' . esc_url($url) . '" rel="nofollow">'
                    . esc_html($label) . '</a>';
            }
            if ($links) {
                echo '<div class="king-addons-tb-author-info__social">' . implode('', $links) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
            }
        }

        echo '</div>';
    }
}
