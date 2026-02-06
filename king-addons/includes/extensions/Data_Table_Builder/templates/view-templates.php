<?php
/**
 * Data Table Builder templates view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$presets = [
    'Minimal',
    'Glass',
    'Contrast',
    'Soft Gray',
    'Modern Lines',
    'Card Table',
    'Dark Mode',
    'Highlighted Header',
    'Pricing Comparison',
    'Feature Matrix',
];
?>

<div class="kng-template-grid">
    <?php foreach ($presets as $preset) : ?>
        <div class="kng-template-card">
            <div class="kng-template-preview">
                <div class="kng-template-line"></div>
                <div class="kng-template-line"></div>
                <div class="kng-template-line"></div>
            </div>
            <div class="kng-template-info">
                <h3><?php echo esc_html($preset); ?></h3>
                <p><?php esc_html_e('Premium style inspired preset for modern data tables.', 'king-addons'); ?></p>
                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm">
                    <?php esc_html_e('Use Preset', 'king-addons'); ?>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="ka-pro-notice">
    <h2><?php esc_html_e('Pro Templates', 'king-addons'); ?></h2>
    <p><?php esc_html_e('Unlock premium template packs, advanced typography, and custom CSS per table.', 'king-addons'); ?></p>
    <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
        <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
    </a>
</div>
