<?php
/**
 * Wishlist admin settings page - V3 Premium style inspired design.
 *
 * @package King_Addons
 */

use King_Addons\Wishlist\Wishlist_Settings;

if (!defined('ABSPATH')) {
    exit;
}

// Theme mode is per-user
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}

$settings = Wishlist_Settings::get_settings();
$pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
$columns = Wishlist_Settings::get_column_options();
$is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();

// Enqueue the shared CSS
$css_url = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
$css_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
$css_version = file_exists($css_path) ? filemtime($css_path) : KING_ADDONS_VERSION;
?>

<link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?v=<?php echo esc_attr($css_version); ?>">

<script>
document.body.classList.add('ka-admin-v3');
(function() {
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
    document.body.classList.toggle('ka-v3-dark', isDark);
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<div class="ka-admin-wrap">
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon pink">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Wishlist', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Configure WooCommerce wishlist functionality.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <span class="ka-status <?php echo !empty($settings['enabled']) ? 'ka-status-enabled' : 'ka-status-disabled'; ?>">
                <span class="ka-status-dot"></span>
                <?php echo !empty($settings['enabled']) ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
            </span>
            <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr(esc_html__('Theme', 'king-addons')); ?>" data-active="<?php echo esc_attr($theme_mode); ?>">
                <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
                <button type="button" class="ka-v3-segmented-btn" data-theme="light" aria-pressed="<?php echo $theme_mode === 'light' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">☀︎</span>
                    <?php esc_html_e('Light', 'king-addons'); ?>
                </button>
                <button type="button" class="ka-v3-segmented-btn" data-theme="dark" aria-pressed="<?php echo $theme_mode === 'dark' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">☾</span>
                    <?php esc_html_e('Dark', 'king-addons'); ?>
                </button>
                <button type="button" class="ka-v3-segmented-btn" data-theme="auto" aria-pressed="<?php echo $theme_mode === 'auto' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">◐</span>
                    <?php esc_html_e('Auto', 'king-addons'); ?>
                </button>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-wishlist-analytics')); ?>" class="ka-btn ka-btn-pink">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Analytics', 'king-addons'); ?>
            </a>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="ka-tabs">
        <button type="button" class="ka-tab active" data-tab="general"><?php esc_html_e('General', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="buttons"><?php esc_html_e('Buttons & Icons', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="page"><?php esc_html_e('Wishlist Page', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="performance"><?php esc_html_e('Performance', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="pro"><?php esc_html_e('Pro Features', 'king-addons'); ?><?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?></button>
    </div>

    <form method="post" action="options.php" id="king-addons-wishlist-form">
        <?php settings_fields('king_addons_wishlist'); ?>

        <!-- General Tab -->
        <div class="ka-tab-content active" data-tab="general">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-settings pink"></span>
                    <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Wishlist', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_wishlist_settings[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Turn on wishlist functionality', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Wishlist Page', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_wishlist_settings[wishlist_page_id]">
                                <option value="0"><?php esc_html_e('— Select page —', 'king-addons'); ?></option>
                                <?php foreach ($pages as $page) : ?>
                                    <option value="<?php echo esc_attr($page->ID); ?>" <?php selected(intval($settings['wishlist_page_id']), $page->ID); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('Choose the page that displays the wishlist or use the shortcode [ka_wishlist_page].', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Allow Guests', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_wishlist_settings[allow_guests]" value="1" <?php checked(!empty($settings['allow_guests'])); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Guests can use wishlist with session', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Guest Notice', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <textarea name="king_addons_wishlist_settings[guest_block_text]" rows="2" style="max-width:100%"><?php echo esc_textarea($settings['guest_block_text']); ?></textarea>
                            <p class="ka-row-desc"><?php esc_html_e('Message shown to guests when wishlist is disabled for them.', 'king-addons'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons Tab -->
        <div class="ka-tab-content" data-tab="buttons">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-button pink"></span>
                    <h2><?php esc_html_e('Button Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Default Text', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_wishlist_settings[button_add_text]" value="<?php echo esc_attr($settings['button_add_text']); ?>" />
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Added Text', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_wishlist_settings[button_added_text]" value="<?php echo esc_attr($settings['button_added_text']); ?>" />
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Display Mode', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-radio-group">
                                <label class="ka-radio-item">
                                    <input type="radio" name="king_addons_wishlist_settings[button_display_mode]" value="icon_text" <?php checked($settings['button_display_mode'], 'icon_text'); ?> />
                                    <?php esc_html_e('Icon with text', 'king-addons'); ?>
                                </label>
                                <label class="ka-radio-item">
                                    <input type="radio" name="king_addons_wishlist_settings[button_display_mode]" value="icon" <?php checked($settings['button_display_mode'], 'icon'); ?> />
                                    <?php esc_html_e('Icon only', 'king-addons'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Icon Class', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_wishlist_settings[icon_choice]" value="<?php echo esc_attr($settings['icon_choice']); ?>" />
                            <p class="ka-row-desc"><?php esc_html_e('Elementor or FontAwesome class, e.g. eicon-heart', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Position', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_wishlist_settings[button_position]">
                                <option value="before_add_to_cart" <?php selected($settings['button_position'], 'before_add_to_cart'); ?>><?php esc_html_e('Before Add to Cart', 'king-addons'); ?></option>
                                <option value="after_add_to_cart" <?php selected($settings['button_position'], 'after_add_to_cart'); ?>><?php esc_html_e('After Add to Cart', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Show in Archives', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_wishlist_settings[show_in_archives]" value="1" <?php checked(!empty($settings['show_in_archives'])); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Display in product loops', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Tab -->
        <div class="ka-tab-content" data-tab="page">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-editor-table pink"></span>
                    <h2><?php esc_html_e('Table Columns', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Columns', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <p class="ka-row-desc" style="margin-top:0;margin-bottom:12px"><?php esc_html_e('Drag to reorder. Check/uncheck to show/hide.', 'king-addons'); ?></p>
                            <ul id="ka-wishlist-columns" class="ka-sortable-list">
                                <?php foreach ($settings['wishlist_columns'] as $saved_key) : if (isset($columns[$saved_key])) : ?>
                                    <li class="ka-sortable-item" data-column="<?php echo esc_attr($saved_key); ?>">
                                        <span class="dashicons dashicons-menu ka-sortable-handle"></span>
                                        <label>
                                            <input type="checkbox" name="king_addons_wishlist_settings[wishlist_columns][]" value="<?php echo esc_attr($saved_key); ?>" checked />
                                            <?php echo esc_html($columns[$saved_key]); ?>
                                        </label>
                                    </li>
                                <?php endif; endforeach; ?>
                                <?php foreach ($columns as $column_key => $label) : if (!in_array($column_key, $settings['wishlist_columns'], true)) : ?>
                                    <li class="ka-sortable-item" data-column="<?php echo esc_attr($column_key); ?>">
                                        <span class="dashicons dashicons-menu ka-sortable-handle"></span>
                                        <label>
                                            <input type="checkbox" name="king_addons_wishlist_settings[wishlist_columns][]" value="<?php echo esc_attr($column_key); ?>" />
                                            <?php echo esc_html($label); ?>
                                        </label>
                                    </li>
                                <?php endif; endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Tab -->
        <div class="ka-tab-content" data-tab="performance">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-performance pink"></span>
                    <h2><?php esc_html_e('Performance', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Frontend Cache', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_wishlist_settings[cache_enabled]" value="1" <?php checked(!empty($settings['cache_enabled'])); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Cache wishlist fragments', 'king-addons'); ?></span>
                            </label>
                            <p class="ka-row-desc"><?php esc_html_e('Basic HTML cache. Disable if counts appear stale.', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Cache Lifetime', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" min="0" step="30" name="king_addons_wishlist_settings[cache_ttl]" value="<?php echo esc_attr(intval($settings['cache_ttl'])); ?>" style="max-width:120px" />
                            <span style="color:#86868b;margin-left:8px"><?php esc_html_e('seconds', 'king-addons'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pro Tab -->
        <div class="ka-tab-content" data-tab="pro">
            <?php if (!$is_pro) : ?>
                <div class="ka-upgrade-card">
                    <h3><?php esc_html_e('Upgrade to Pro', 'king-addons'); ?></h3>
                    <p><?php esc_html_e('Unlock powerful wishlist features for your WooCommerce store.', 'king-addons'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Multiple wishlists with visibility control', 'king-addons'); ?></li>
                        <li><?php esc_html_e('Shareable links and social buttons', 'king-addons'); ?></li>
                        <li><?php esc_html_e('Email triggers for price drops and restocks', 'king-addons'); ?></li>
                        <li><?php esc_html_e('Wishlist analytics and CSV export', 'king-addons'); ?></li>
                        <li><?php esc_html_e('Product notes and advanced design controls', 'king-addons'); ?></li>
                    </ul>
                    <a href="https://kingaddons.com/pricing/?utm_source=kng-wishlist-admin&utm_medium=plugin&utm_campaign=kng" target="_blank" class="ka-btn ka-btn-pink">
                        <?php esc_html_e('View Pro Plans', 'king-addons'); ?>
                    </a>
                </div>
            <?php else :
                $email_settings = class_exists('King_Addons\Wishlist\Wishlist_Email_Notifications')
                    ? \King_Addons\Wishlist\Wishlist_Email_Notifications::get_settings()
                    : [];
            ?>
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-email-alt pink"></span>
                        <h2><?php esc_html_e('Email Notifications', 'king-addons'); ?></h2>
                    </div>
                    <div class="ka-card-body">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Price Drops', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="king_addons_wishlist_email_settings[enable_price_drop]" value="1" <?php checked(!empty($email_settings['enable_price_drop'])); ?> />
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Notify on price reduction', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Back in Stock', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="king_addons_wishlist_email_settings[enable_back_in_stock]" value="1" <?php checked(!empty($email_settings['enable_back_in_stock'])); ?> />
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Notify when product returns', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Weekly Digest', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="king_addons_wishlist_email_settings[enable_digest]" value="1" <?php checked(!empty($email_settings['enable_digest'])); ?> />
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Send weekly wishlist summary', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Price Threshold', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" min="1" max="50" name="king_addons_wishlist_email_settings[price_threshold]" value="<?php echo esc_attr($email_settings['price_threshold'] ?? 5); ?>" style="max-width:80px" />
                                <span style="color:#86868b;margin-left:8px">%</span>
                                <p class="ka-row-desc"><?php esc_html_e('Minimum % drop to trigger notification', 'king-addons'); ?></p>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Daily Limit', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" min="1" max="10" name="king_addons_wishlist_email_settings[daily_email_limit]" value="<?php echo esc_attr($email_settings['daily_email_limit'] ?? 3); ?>" style="max-width:80px" />
                                <span style="color:#86868b;margin-left:8px"><?php esc_html_e('emails/user/day', 'king-addons'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="ka-card">
            <div class="ka-submit">
                <button type="submit" class="ka-btn ka-btn-pink">
                    <?php esc_html_e('Save Settings', 'king-addons'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';

        // Theme segmented control (dashboard-style)
        const $themeSegment = $('#ka-v3-theme-segment');
        const $themeSegmentButtons = $themeSegment.find('.ka-v3-segmented-btn');
        const themeMql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        let themeMode = ($themeSegment.attr('data-active') || 'dark').toString();
        let themeMqlHandler = null;

        function saveUISetting(key, value) {
            $.post(ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: nonce,
                key: key,
                value: value
            });
        }

        function updateSegment(mode) {
            $themeSegment.attr('data-active', mode);
            $themeSegmentButtons.each(function() {
                const theme = $(this).data('theme');
                $(this).attr('aria-pressed', theme === mode ? 'true' : 'false');
            });
        }

        function applyThemeClass(isDark) {
            $('body').toggleClass('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function setThemeMode(mode, save) {
            themeMode = mode;
            updateSegment(mode);

            if (themeMqlHandler && themeMql) {
                if (themeMql.removeEventListener) {
                    themeMql.removeEventListener('change', themeMqlHandler);
                } else if (themeMql.removeListener) {
                    themeMql.removeListener(themeMqlHandler);
                }
                themeMqlHandler = null;
            }

            if (mode === 'auto') {
                applyThemeClass(!!(themeMql && themeMql.matches));
                themeMqlHandler = function(e) {
                    if (themeMode !== 'auto') {
                        return;
                    }
                    applyThemeClass(!!e.matches);
                };
                if (themeMql) {
                    if (themeMql.addEventListener) {
                        themeMql.addEventListener('change', themeMqlHandler);
                    } else if (themeMql.addListener) {
                        themeMql.addListener(themeMqlHandler);
                    }
                }
            } else {
                applyThemeClass(mode === 'dark');
            }

            if (save) {
                saveUISetting('theme_mode', mode);
            }
        }

        $themeSegment.on('click', '.ka-v3-segmented-btn', function(e) {
            e.preventDefault();
            const mode = ($(this).data('theme') || 'dark').toString();
            setThemeMode(mode, true);
        });

        // Ensure mode applies for Auto (listener + system state)
        setThemeMode(themeMode, false);

        // Tab navigation
        $('.ka-tab').on('click', function() {
            const target = $(this).data('tab');
            $('.ka-tab').removeClass('active');
            $(this).addClass('active');
            $('.ka-tab-content').removeClass('active');
            $(`.ka-tab-content[data-tab="${target}"]`).addClass('active');
        });

        // Sortable columns
        if ($.fn.sortable) {
            $('#ka-wishlist-columns').sortable({
                handle: '.ka-sortable-handle',
                placeholder: 'ui-sortable-placeholder',
                axis: 'y',
                cursor: 'move',
                tolerance: 'pointer'
            });
        }
    });
})(jQuery);
</script>
<?php wp_enqueue_script('jquery-ui-sortable'); ?>
