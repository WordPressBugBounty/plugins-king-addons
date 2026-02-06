<?php
/**
 * Wishlist Icon (Pro placeholder).
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
 * Placeholder widget promoting Pro wishlist icon.
 */
class Wishlist_Icon extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-wishlist-icon';
    }

    /**
     * Widget title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Wishlist Icon', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string Icon class.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-wishlist-icon';
    }

    /**
     * Widget categories.
     *
     * @return array<int, string> Categories.
     */
    public function get_categories(): array
    {
        return ['king-addons', 'king-addons-woo-builder'];
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
    protected function register_controls(): void
    {
        Core::renderProFeaturesSection(
            $this,
            Controls_Manager::TAB_CONTENT,
            Controls_Manager::RAW_HTML,
            'wishlist-icon',
            [
                esc_html__('Floating wishlist icon with badge', 'king-addons'),
                esc_html__('Mini dropdown with recent items', 'king-addons'),
                esc_html__('Hover or click triggers', 'king-addons'),
                esc_html__('Device and position rules', 'king-addons'),
            ]
        );
    }

    /**
     * Render placeholder output.
     *
     * @return void
     */
    protected function render(): void
    {
        echo '<div class="king-addons-pro-placeholder">' . esc_html__('Available in King Addons Pro.', 'king-addons') . '</div>';
    }
}



