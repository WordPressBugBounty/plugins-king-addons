<?php
/**
 * Site Preloader Advanced Tab.
 *
 * @package King_Addons
 * @since 1.0.0
 *
 * @var array $settings Current settings.
 * @var bool  $is_pro   Whether Pro version is active.
 * @var array $presets  Available presets.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="ka-preloader-advanced-form">
    <?php wp_nonce_field('king_addons_site_preloader_save'); ?>
    <input type="hidden" name="action" value="king_addons_site_preloader_save" />
    <input type="hidden" name="current_tab" value="advanced" />
    
    <?php
    // Preserve all settings
    foreach ($settings as $key => $value) {
        if (in_array($key, ['show_animation', 'hide_animation', 'transition_duration', 'easing', 'z_index', 'custom_css'])) continue;
        if (is_array($value)) {
            foreach ($value as $arr_val) {
                echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($arr_val) . '" />';
            }
        } else {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }
    }
    ?>

    <!-- Transitions -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-controls-play"></span>
            <h2><?php esc_html_e('Transitions & Timing', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Show Animation', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="show_animation">
                        <option value="none" <?php selected($settings['show_animation'], 'none'); ?>><?php esc_html_e('None', 'king-addons'); ?></option>
                        <option value="fade" <?php selected($settings['show_animation'], 'fade'); ?>><?php esc_html_e('Fade In', 'king-addons'); ?></option>
                        <option value="scale" <?php selected($settings['show_animation'], 'scale'); ?>><?php esc_html_e('Scale Up', 'king-addons'); ?></option>
                    </select>
                    <p class="ka-row-desc"><?php esc_html_e('Animation when preloader appears.', 'king-addons'); ?></p>
                </div>
            </div>

            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Hide Animation', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="hide_animation">
                        <option value="none" <?php selected($settings['hide_animation'], 'none'); ?>><?php esc_html_e('None', 'king-addons'); ?></option>
                        <option value="fade" <?php selected($settings['hide_animation'], 'fade'); ?>><?php esc_html_e('Fade Out', 'king-addons'); ?></option>
                        <option value="slide-up" <?php selected($settings['hide_animation'], 'slide-up'); ?>><?php esc_html_e('Slide Up', 'king-addons'); ?></option>
                        <option value="blur" <?php selected($settings['hide_animation'], 'blur'); ?>><?php esc_html_e('Blur Out', 'king-addons'); ?></option>
                        <option value="scale" <?php selected($settings['hide_animation'], 'scale'); ?>><?php esc_html_e('Scale Down', 'king-addons'); ?></option>
                    </select>
                    <p class="ka-row-desc"><?php esc_html_e('Animation when preloader disappears.', 'king-addons'); ?></p>
                </div>
            </div>

            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Transition Duration', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="number" name="transition_duration" value="<?php echo esc_attr($settings['transition_duration']); ?>" min="0" max="2000" step="50" /> <span class="ka-unit">ms</span>
                    <p class="ka-row-desc"><?php esc_html_e('Duration of show/hide animations.', 'king-addons'); ?></p>
                </div>
            </div>

            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Easing Function', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="easing">
                        <option value="linear" <?php selected($settings['easing'], 'linear'); ?>><?php esc_html_e('Linear', 'king-addons'); ?></option>
                        <option value="ease" <?php selected($settings['easing'], 'ease'); ?>><?php esc_html_e('Ease', 'king-addons'); ?></option>
                        <option value="ease-in" <?php selected($settings['easing'], 'ease-in'); ?>><?php esc_html_e('Ease In', 'king-addons'); ?></option>
                        <option value="ease-out" <?php selected($settings['easing'], 'ease-out'); ?>><?php esc_html_e('Ease Out', 'king-addons'); ?></option>
                        <option value="ease-in-out" <?php selected($settings['easing'], 'ease-in-out'); ?>><?php esc_html_e('Ease In Out', 'king-addons'); ?></option>
                        <option value="cubic-bezier(0.25, 1, 0.5, 1)" <?php selected($settings['easing'], 'cubic-bezier(0.25, 1, 0.5, 1)'); ?>><?php esc_html_e('Smooth', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Settings -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-tools"></span>
            <h2><?php esc_html_e('Technical Settings', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Z-Index', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="number" name="z_index" value="<?php echo esc_attr($settings['z_index']); ?>" min="1" max="2147483647" />
                    <p class="ka-row-desc"><?php esc_html_e('Stack order of the preloader. Higher values appear on top.', 'king-addons'); ?></p>
                </div>
            </div>

            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Auto-Exclude', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <div class="ka-info-box">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php esc_html_e('The preloader is automatically hidden on:', 'king-addons'); ?></p>
                        <ul>
                            <li><?php esc_html_e('WordPress Admin pages', 'king-addons'); ?></li>
                            <li><?php esc_html_e('Login page (wp-login.php)', 'king-addons'); ?></li>
                            <li><?php esc_html_e('Elementor Editor & Preview modes', 'king-addons'); ?></li>
                            <li><?php esc_html_e('AJAX requests', 'king-addons'); ?></li>
                            <li><?php esc_html_e('REST API calls', 'king-addons'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-editor-code"></span>
            <h2>
                <?php esc_html_e('Custom CSS', 'king-addons'); ?>
                <?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
            </h2>
        </div>
        <div class="ka-card-body">
            <?php if ($is_pro): ?>
            <div class="ka-row">
                <div class="ka-row-field" style="width: 100%;">
                    <textarea name="custom_css" rows="12" class="ka-code-textarea" placeholder="/* Your custom CSS here */"><?php echo esc_textarea($settings['custom_css'] ?? ''); ?></textarea>
                    <p class="ka-row-desc">
                        <?php esc_html_e('Add custom CSS to style the preloader. Use', 'king-addons'); ?>
                        <code>.kng-site-preloader</code>
                        <?php esc_html_e('as the main selector.', 'king-addons'); ?>
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="ka-pro-notice" style="padding: 20px; margin: 0;">
                <p><?php esc_html_e('Custom CSS is available in the Pro version.', 'king-addons'); ?></p>
                <a href="https://kingaddons.com/pricing/?utm_source=kng-preloader-css-upgrade&utm_medium=plugin&utm_campaign=kng" target="_blank" class="ka-btn ka-btn-primary ka-btn-sm">
                    <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pro: JavaScript Hooks -->
    <?php if ($is_pro): ?>
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-editor-code"></span>
            <h2><?php esc_html_e('JavaScript API', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-field" style="width: 100%;">
                    <div class="ka-info-box">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php esc_html_e('Use the JavaScript API for manual control and AJAX navigation integration:', 'king-addons'); ?></p>
                        <pre><code>// Show preloader
window.KngPreloader.show();

// Hide preloader
window.KngPreloader.hide();

// Check if visible
window.KngPreloader.isVisible();

// Listen for events
document.addEventListener('kngPreloaderShown', function() {
    console.log('Preloader shown');
});
document.addEventListener('kngPreloaderHidden', function() {
    console.log('Preloader hidden');
});</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Save Button -->
    <div class="ka-submit ka-preloader-submit">
        <button type="submit" class="ka-btn ka-btn-primary">
            <span class="dashicons dashicons-saved"></span>
            <?php esc_html_e('Save Settings', 'king-addons'); ?>
        </button>
    </div>
</form>
