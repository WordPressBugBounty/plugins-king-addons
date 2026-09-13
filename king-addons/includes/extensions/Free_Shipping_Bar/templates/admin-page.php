<?php
/**
 * Free Shipping Bar settings screen.
 *
 * @package King_Addons
 *
 * @var array<string, mixed> $s        Current settings.
 * @var float|null           $detected Threshold detected from WooCommerce.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once KING_ADDONS_PATH . 'includes/admin/shared/page-shell.php';

$ka_notices = [];

if (!class_exists('WooCommerce')) {
    $ka_notices[] = [
        'type' => 'warning',
        'text' => esc_html__('WooCommerce is not active, so this bar has nothing to measure. Activate WooCommerce to use it.', 'king-addons'),
    ];
}

$ka_source = $s['threshold_source'] ?? 'auto';

ka_admin_page_open([
    'title' => esc_html__('Free Shipping Bar', 'king-addons'),
    'subtitle' => esc_html__('Show customers how close they are to free shipping.', 'king-addons'),
    'icon' => 'dashicons-cart',
    'icon_color' => 'green',
    'saved' => isset($_GET['ka-saved']), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    'notices' => $ka_notices,
]);
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="king_addons_fsb_save">
    <?php wp_nonce_field('king_addons_fsb_save'); ?>

    <?php ka_admin_card_open(esc_html__('Behaviour', 'king-addons'), 'dashicons-visibility'); ?>

        <?php ka_admin_row_open(esc_html__('Enable', 'king-addons')); ?>
            <label class="ka-check">
                <input type="checkbox" name="ka_fsb[enabled]" value="1" <?php checked(!empty($s['enabled'])); ?>>
                <span><?php esc_html_e('Show the free shipping bar on the storefront', 'king-addons'); ?></span>
            </label>
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Threshold', 'king-addons')); ?>
            <div class="ka-radio-group">
                <label class="ka-radio-item">
                    <input type="radio" name="ka_fsb[threshold_source]" value="auto" <?php checked($ka_source, 'auto'); ?>>
                    <span>
                        <strong><?php esc_html_e('Read from WooCommerce shipping zones', 'king-addons'); ?></strong>
                        <em>
                            <?php
                            if (null !== $detected && function_exists('wc_price')) {
                                printf(
                                    /* translators: %s: formatted amount. */
                                    esc_html__('Detected right now: %s', 'king-addons'),
                                    wp_kses_post(wc_price($detected))
                                );
                            } else {
                                esc_html_e('No amount-based free shipping method in the matching zone yet. Add one in WooCommerce, or use a fixed amount below.', 'king-addons');
                            }
                            ?>
                        </em>
                    </span>
                </label>

                <label class="ka-radio-item">
                    <input type="radio" name="ka_fsb[threshold_source]" value="manual" <?php checked($ka_source, 'manual'); ?>>
                    <span>
                        <strong><?php esc_html_e('Use a fixed amount', 'king-addons'); ?></strong>
                        <em><?php esc_html_e('Ignores the shipping zones and always aims at this number.', 'king-addons'); ?></em>
                    </span>
                </label>
            </div>

            <p class="ka-inline-field">
                <label for="ka-fsb-manual"><?php esc_html_e('Fixed amount', 'king-addons'); ?></label>
                <input type="number" step="0.01" min="0" id="ka-fsb-manual" name="ka_fsb[manual_amount]"
                    value="<?php echo esc_attr((string) ($s['manual_amount'] ?? '')); ?>" style="max-width:140px">
            </p>
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Where to show', 'king-addons')); ?>
            <div class="ka-check-group">
                <label class="ka-check">
                    <input type="checkbox" name="ka_fsb[show_on_cart]" value="1" <?php checked(!empty($s['show_on_cart'])); ?>>
                    <span><?php esc_html_e('Cart page', 'king-addons'); ?></span>
                </label>
                <label class="ka-check">
                    <input type="checkbox" name="ka_fsb[show_on_checkout]" value="1" <?php checked(!empty($s['show_on_checkout'])); ?>>
                    <span><?php esc_html_e('Checkout page', 'king-addons'); ?></span>
                </label>
                <label class="ka-check">
                    <input type="checkbox" name="ka_fsb[show_in_mini_cart]" value="1" <?php checked(!empty($s['show_in_mini_cart'])); ?>>
                    <span><?php esc_html_e('Mini cart', 'king-addons'); ?></span>
                </label>
            </div>
        <?php ka_admin_row_close(); ?>

    <?php ka_admin_card_close(); ?>

    <?php ka_admin_card_open(esc_html__('Wording', 'king-addons'), 'dashicons-editor-textcolor'); ?>

        <?php ka_admin_row_open(esc_html__('Before the goal', 'king-addons'), 'ka-fsb-progress'); ?>
            <input type="text" id="ka-fsb-progress" name="ka_fsb[text_progress]"
                value="<?php echo esc_attr((string) $s['text_progress']); ?>">
        <?php ka_admin_row_close(esc_html__('Placeholders: {remaining}, {total}, {threshold}', 'king-addons')); ?>

        <?php ka_admin_row_open(esc_html__('Once reached', 'king-addons'), 'ka-fsb-success'); ?>
            <input type="text" id="ka-fsb-success" name="ka_fsb[text_success]"
                value="<?php echo esc_attr((string) $s['text_success']); ?>">
            <label class="ka-check" style="margin-top:10px">
                <input type="checkbox" name="ka_fsb[hide_when_reached]" value="1" <?php checked(!empty($s['hide_when_reached'])); ?>>
                <span><?php esc_html_e('Hide the bar entirely once free shipping is reached', 'king-addons'); ?></span>
            </label>
        <?php ka_admin_row_close(); ?>

    <?php ka_admin_card_close(); ?>

    <?php ka_admin_card_open(esc_html__('Appearance', 'king-addons'), 'dashicons-art'); ?>

        <?php ka_admin_row_open(esc_html__('Bar height', 'king-addons'), 'ka-fsb-height'); ?>
            <p class="ka-inline-field">
                <input type="number" min="2" max="40" id="ka-fsb-height" name="ka_fsb[bar_height]"
                    value="<?php echo esc_attr((string) $s['bar_height']); ?>" style="max-width:100px">
                <span><?php esc_html_e('px', 'king-addons'); ?></span>
            </p>
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Colours', 'king-addons')); ?>
            <div class="ka-color-row">
                <label class="ka-color-wrap">
                    <span><?php esc_html_e('Bar', 'king-addons'); ?></span>
                    <input type="color" name="ka_fsb[bar_color]" value="<?php echo esc_attr((string) $s['bar_color']); ?>">
                </label>
                <label class="ka-color-wrap">
                    <span><?php esc_html_e('Track', 'king-addons'); ?></span>
                    <input type="color" name="ka_fsb[bar_track_color]" value="<?php echo esc_attr((string) $s['bar_track_color']); ?>">
                </label>
                <label class="ka-color-wrap">
                    <span><?php esc_html_e('Text', 'king-addons'); ?></span>
                    <input type="color" name="ka_fsb[text_color]" value="<?php echo esc_attr((string) $s['text_color']); ?>">
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
