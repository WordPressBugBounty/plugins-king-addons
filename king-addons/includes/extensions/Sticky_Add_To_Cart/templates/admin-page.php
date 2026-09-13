<?php
/**
 * Sticky Add To Cart settings screen.
 *
 * @package King_Addons
 *
 * @var array<string, mixed> $s      Current settings.
 * @var bool                 $is_pro Whether the paid tier is active.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once KING_ADDONS_PATH . 'includes/admin/shared/page-shell.php';

$ka_notices = [];

if (!$is_pro) {
    $ka_notices[] = [
        'type' => 'info',
        'text' => sprintf(
            '<strong>%1$s</strong> %2$s <a href="%3$s" target="_blank" rel="noopener">%4$s</a>',
            esc_html__('This module is part of King Addons Pro.', 'king-addons'),
            esc_html__('You can set it up now; the bar starts showing on your product pages once Pro is active.', 'king-addons'),
            esc_url('https://kingaddons.com/pricing/?utm_source=kng-sticky-add-to-cart&utm_medium=plugin&utm_campaign=kng'),
            esc_html__('See plans', 'king-addons')
        ),
    ];
}

if (!class_exists('WooCommerce')) {
    $ka_notices[] = [
        'type' => 'warning',
        'text' => esc_html__('WooCommerce is not active, so there are no product pages to add this bar to.', 'king-addons'),
    ];
}

$ka_position = $s['position'] ?? 'bottom';

ka_admin_page_open([
    'title' => esc_html__('Sticky Add To Cart', 'king-addons'),
    'subtitle' => esc_html__('Keeps the price and buy button in reach once the visitor scrolls past the product form.', 'king-addons'),
    'icon' => 'dashicons-cart',
    'icon_color' => 'orange',
    'saved' => isset($_GET['ka-saved']), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    'notices' => $ka_notices,
]);
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="king_addons_satc_save">
    <?php wp_nonce_field('king_addons_satc_save'); ?>

    <?php ka_admin_card_open(esc_html__('Behaviour', 'king-addons'), 'dashicons-visibility'); ?>

        <?php ka_admin_row_open(esc_html__('Enable', 'king-addons')); ?>
            <label class="ka-check">
                <input type="checkbox" name="ka_satc[enabled]" value="1" <?php checked(!empty($s['enabled'])); ?>>
                <span><?php esc_html_e('Show the sticky bar on single product pages', 'king-addons'); ?></span>
            </label>
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Position', 'king-addons')); ?>
            <div class="ka-radio-group">
                <label class="ka-radio-item">
                    <input type="radio" name="ka_satc[position]" value="bottom" <?php checked($ka_position, 'bottom'); ?>>
                    <span><?php esc_html_e('Bottom of the screen', 'king-addons'); ?></span>
                </label>
                <label class="ka-radio-item">
                    <input type="radio" name="ka_satc[position]" value="top" <?php checked($ka_position, 'top'); ?>>
                    <span><?php esc_html_e('Top of the screen', 'king-addons'); ?></span>
                </label>
            </div>
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('What to show', 'king-addons')); ?>
            <div class="ka-check-group">
                <label class="ka-check">
                    <input type="checkbox" name="ka_satc[show_thumbnail]" value="1" <?php checked(!empty($s['show_thumbnail'])); ?>>
                    <span><?php esc_html_e('Product thumbnail', 'king-addons'); ?></span>
                </label>
                <label class="ka-check">
                    <input type="checkbox" name="ka_satc[show_price]" value="1" <?php checked(!empty($s['show_price'])); ?>>
                    <span><?php esc_html_e('Price', 'king-addons'); ?></span>
                </label>
                <label class="ka-check">
                    <input type="checkbox" name="ka_satc[show_on_mobile]" value="1" <?php checked(!empty($s['show_on_mobile'])); ?>>
                    <span><?php esc_html_e('Show on mobile devices', 'king-addons'); ?></span>
                </label>
            </div>
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Button label', 'king-addons'), 'ka-satc-label'); ?>
            <input type="text" id="ka-satc-label" name="ka_satc[button_text]"
                value="<?php echo esc_attr((string) $s['button_text']); ?>">
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Fallback trigger', 'king-addons'), 'ka-satc-offset'); ?>
            <p class="ka-inline-field">
                <input type="number" min="0" max="5000" id="ka-satc-offset" name="ka_satc[trigger_offset]"
                    value="<?php echo esc_attr((string) $s['trigger_offset']); ?>" style="max-width:110px">
                <span><?php esc_html_e('px', 'king-addons'); ?></span>
            </p>
        <?php ka_admin_row_close(esc_html__('The bar normally appears when the real add-to-cart form scrolls out of view. This offset is used only on themes where that form cannot be found.', 'king-addons')); ?>

    <?php ka_admin_card_close(); ?>

    <?php ka_admin_card_open(esc_html__('Appearance', 'king-addons'), 'dashicons-art'); ?>

        <?php ka_admin_row_open(esc_html__('Bar', 'king-addons')); ?>
            <div class="ka-color-row">
                <label class="ka-color-wrap">
                    <span><?php esc_html_e('Background', 'king-addons'); ?></span>
                    <input type="color" name="ka_satc[bg_color]" value="<?php echo esc_attr((string) $s['bg_color']); ?>">
                </label>
                <label class="ka-color-wrap">
                    <span><?php esc_html_e('Text', 'king-addons'); ?></span>
                    <input type="color" name="ka_satc[text_color]" value="<?php echo esc_attr((string) $s['text_color']); ?>">
                </label>
            </div>
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Button', 'king-addons')); ?>
            <div class="ka-color-row">
                <label class="ka-color-wrap">
                    <span><?php esc_html_e('Background', 'king-addons'); ?></span>
                    <input type="color" name="ka_satc[button_bg_color]" value="<?php echo esc_attr((string) $s['button_bg_color']); ?>">
                </label>
                <label class="ka-color-wrap">
                    <span><?php esc_html_e('Text', 'king-addons'); ?></span>
                    <input type="color" name="ka_satc[button_text_color]" value="<?php echo esc_attr((string) $s['button_text_color']); ?>">
                </label>
            </div>
        <?php ka_admin_row_close(); ?>

    <?php ka_admin_card_close(); ?>

    <p class="ka-submit">
        <button type="submit" class="ka-btn ka-btn-primary">
            <span class="dashicons dashicons-yes"></span>
            <?php esc_html_e('Save Settings', 'king-addons'); ?>
        </button>
    </p>
</form>

<?php ka_admin_page_close(); ?>
