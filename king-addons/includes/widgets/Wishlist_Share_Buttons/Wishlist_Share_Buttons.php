<?php
/**
 * Wishlist Share Buttons (Pro placeholder).
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
 * Placeholder for Pro wishlist share buttons widget.
 */
class Wishlist_Share_Buttons extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-wishlist-share-buttons';
    }

    /**
     * Widget title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Wishlist Share Buttons', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string Icon class.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-wishlist-share-buttons';
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

    /**
     * Register controls.
     *
     * @return void
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls(): void
    {
        Core::renderProFeaturesSection(
            $this,
            Controls_Manager::TAB_CONTENT,
            Controls_Manager::RAW_HTML,
            'wishlist-share-buttons',
            [
                esc_html__('Share wishlist via Telegram, WhatsApp, email', 'king-addons'),
                esc_html__('Copy link with visibility rules', 'king-addons'),
                esc_html__('Custom label, icons, and alignment', 'king-addons'),
                esc_html__('Button skins and hover states', 'king-addons'),
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



