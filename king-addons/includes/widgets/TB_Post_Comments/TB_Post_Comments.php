<?php
/**
 * Theme Builder Post Comments widget (Free).
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
 * Renders comments list and form for the current post.
 */
class TB_Post_Comments extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-post-comments';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Post Comments', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-comments';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-comments-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-comments-script'];
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
        return ['comments', 'discussion', 'form', 'theme builder', 'king-addons'];
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
            'kng_show',
            [
                'label' => esc_html__('Show', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'both',
                'options' => [
                    'list' => esc_html__('Comments List', 'king-addons'),
                    'form' => esc_html__('Comment Form', 'king-addons'),
                    'both' => esc_html__('Both', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_hide_when_closed',
            [
                'label' => esc_html__('Hide When Closed', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_no_comments_text',
            [
                'label' => $is_pro ?
                    esc_html__('No Comments Text', 'king-addons') :
                    sprintf(__('No Comments Text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('No comments yet.', 'king-addons'),
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_order',
            [
                'label' => $is_pro ?
                    esc_html__('Order', 'king-addons') :
                    sprintf(__('Order %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => [
                    'ASC' => esc_html__('Oldest First', 'king-addons'),
                    'DESC' => esc_html__('Newest First', 'king-addons'),
                ],
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
                'name' => 'kng_heading_typography',
                'label' => esc_html__('Heading Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-post-comments__heading',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_body_typography',
                'label' => esc_html__('Text Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-post-comments, {{WRAPPER}} .king-addons-tb-post-comments a',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-comments' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-post-comments a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_link_hover_color',
            [
                'label' => esc_html__('Link Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-comments a:hover' => 'color: {{VALUE}};',
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
            'tb-post-comments',
            [
                'Custom empty text and ordering',
                'Extended styling for lists and form',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Whether Pro features are enabled.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }

        if (!comments_open($post) && ('yes' === ($settings['kng_hide_when_closed'] ?? ''))) {
            return;
        }

        $show = $settings['kng_show'] ?? 'both';
        $show_list = in_array($show, ['list', 'both'], true);
        $show_form = in_array($show, ['form', 'both'], true);

        echo '<div class="king-addons-tb-post-comments">';

        if ($show_list) {
            $comments = get_comments(
                [
                    'post_id' => $post->ID,
                    'status' => 'approve',
                    'order' => $is_pro ? ($settings['kng_order'] ?? 'ASC') : 'ASC',
                ]
            );

            if (!empty($comments)) {
                echo '<div class="king-addons-tb-post-comments__heading">' . esc_html__('Comments', 'king-addons') . '</div>';
                echo '<ol class="king-addons-tb-post-comments__list">';
                foreach ($comments as $comment) {
                    echo '<li class="king-addons-tb-post-comments__item">';
                    echo '<div class="king-addons-tb-post-comments__meta">' . esc_html(get_comment_author($comment)) . ' · ' . esc_html(get_comment_date('', $comment)) . '</div>';
                    echo '<div class="king-addons-tb-post-comments__content">' . wp_kses_post(get_comment_text($comment)) . '</div>';
                    echo '</li>';
                }
                echo '</ol>';
            } else {
                $empty_text = $is_pro ? ($settings['kng_no_comments_text'] ?? '') : '';
                if ($empty_text) {
                    echo '<div class="king-addons-tb-post-comments__empty">' . esc_html($empty_text) . '</div>';
                }
            }
        }

        if ($show_form && comments_open($post)) {
            echo '<div class="king-addons-tb-post-comments__form">';
            comment_form(
                [
                    'title_reply' => esc_html__('Leave a comment', 'king-addons'),
                    'label_submit' => esc_html__('Post Comment', 'king-addons'),
                    'comment_notes_after' => '',
                ],
                $post->ID
            );
            echo '</div>';
        }

        echo '</div>';
    }
}
