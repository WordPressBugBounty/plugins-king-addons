<?php
/**
 * Site Preloader Settings Tab.
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

// Get all pages for selection
$pages = get_pages(['post_status' => 'publish', 'number' => 100]);
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="ka-preloader-settings-form">
    <?php wp_nonce_field('king_addons_site_preloader_save'); ?>
    <input type="hidden" name="action" value="king_addons_site_preloader_save" />
    <input type="hidden" name="current_tab" value="settings" />

    <div class="ka-preloader-settings-grid">
        <!-- Left Column: Settings -->
        <div class="ka-preloader-settings-main">
            <!-- General Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Preloader', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show preloader on page load', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Show On', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="show_on" id="ka-preloader-show-on">
                                <option value="all" <?php selected($settings['show_on'], 'all'); ?>><?php esc_html_e('All Pages', 'king-addons'); ?></option>
                                <option value="selected" <?php selected($settings['show_on'], 'selected'); ?>><?php esc_html_e('Selected Pages Only', 'king-addons'); ?></option>
                                <option value="exclude" <?php selected($settings['show_on'], 'exclude'); ?>><?php esc_html_e('Exclude Specific Pages', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="ka-row ka-row-pages" id="ka-selected-pages-row" style="<?php echo $settings['show_on'] === 'selected' ? '' : 'display:none'; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Select Pages', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="selected_pages[]" multiple class="ka-multiselect">
                                <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected(in_array($page->ID, $settings['selected_pages'] ?? [])); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('Hold Ctrl/Cmd to select multiple pages.', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <div class="ka-row ka-row-pages" id="ka-excluded-pages-row" style="<?php echo $settings['show_on'] === 'exclude' ? '' : 'display:none'; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Exclude Pages', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="excluded_pages[]" multiple class="ka-multiselect">
                                <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected(in_array($page->ID, $settings['excluded_pages'] ?? [])); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('Preloader will not show on these pages.', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Show For', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="show_for">
                                <option value="everyone" <?php selected($settings['show_for'], 'everyone'); ?>><?php esc_html_e('Everyone', 'king-addons'); ?></option>
                                <option value="guests" <?php selected($settings['show_for'], 'guests'); ?>><?php esc_html_e('Guests Only', 'king-addons'); ?></option>
                                <option value="logged_in" <?php selected($settings['show_for'], 'logged_in'); ?>><?php esc_html_e('Logged In Users Only', 'king-addons'); ?></option>
                                <?php if ($is_pro): ?>
                                <option value="roles" <?php selected($settings['show_for'], 'roles'); ?>><?php esc_html_e('Specific Roles (Pro)', 'king-addons'); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Device Visibility', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-checkbox-group">
                                <label class="ka-checkbox-item">
                                    <input type="checkbox" name="device_desktop" value="1" <?php checked(!empty($settings['device_desktop'])); ?> />
                                    <span class="dashicons dashicons-desktop"></span>
                                    <?php esc_html_e('Desktop', 'king-addons'); ?>
                                </label>
                                <label class="ka-checkbox-item">
                                    <input type="checkbox" name="device_tablet" value="1" <?php checked(!empty($settings['device_tablet'])); ?> />
                                    <span class="dashicons dashicons-tablet"></span>
                                    <?php esc_html_e('Tablet', 'king-addons'); ?>
                                </label>
                                <label class="ka-checkbox-item">
                                    <input type="checkbox" name="device_mobile" value="1" <?php checked(!empty($settings['device_mobile'])); ?> />
                                    <span class="dashicons dashicons-smartphone"></span>
                                    <?php esc_html_e('Mobile', 'king-addons'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-art"></span>
                    <h2><?php esc_html_e('Appearance', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <h4 class="ka-settings-subheading"><?php esc_html_e('Background', 'king-addons'); ?></h4>
                    
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Background Type', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="bg_type" id="ka-preloader-bg-type">
                                <option value="solid" <?php selected($settings['bg_type'], 'solid'); ?>><?php esc_html_e('Solid Color', 'king-addons'); ?></option>
                                <option value="gradient" <?php selected($settings['bg_type'], 'gradient'); ?>><?php esc_html_e('Gradient', 'king-addons'); ?></option>
                                <option value="image" <?php selected($settings['bg_type'], 'image'); ?> <?php disabled(!$is_pro); ?>><?php esc_html_e('Image', 'king-addons'); ?><?php echo !$is_pro ? ' (Pro)' : ''; ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="ka-row ka-bg-solid-row" style="<?php echo $settings['bg_type'] !== 'solid' ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Background Color', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="bg_color" value="<?php echo esc_attr($settings['bg_color']); ?>" class="ka-color-picker" data-default-color="rgba(0,0,0,0)" data-alpha-enabled="true" />
                        </div>
                    </div>

                    <div class="ka-row ka-bg-gradient-row" style="<?php echo $settings['bg_type'] !== 'gradient' ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Gradient Start', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="bg_gradient_start" value="<?php echo esc_attr($settings['bg_gradient_start']); ?>" class="ka-color-picker" data-default-color="#ffffff" data-alpha-enabled="true" />
                        </div>
                    </div>

                    <div class="ka-row ka-bg-gradient-row" style="<?php echo $settings['bg_type'] !== 'gradient' ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Gradient End', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="bg_gradient_end" value="<?php echo esc_attr($settings['bg_gradient_end']); ?>" class="ka-color-picker" data-default-color="#f5f5f7" data-alpha-enabled="true" />
                        </div>
                    </div>

                    <?php if ($is_pro): ?>
                    <div class="ka-row ka-bg-image-row" style="<?php echo $settings['bg_type'] !== 'image' ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Background Image', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-image-upload">
                                <input type="hidden" name="bg_image" id="ka-bg-image" value="<?php echo esc_attr($settings['bg_image']); ?>" />
                                <div class="ka-image-preview" id="ka-bg-image-preview">
                                    <?php if (!empty($settings['bg_image'])): ?>
                                    <img src="<?php echo esc_url($settings['bg_image']); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" id="ka-bg-image-upload-btn">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e('Upload Image', 'king-addons'); ?>
                                </button>
                                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" id="ka-bg-image-remove-btn" style="<?php echo empty($settings['bg_image']) ? 'display:none' : ''; ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Overlay Opacity', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-range-wrap">
                                <input type="range" name="overlay_opacity" min="0" max="1" step="0.05" value="<?php echo esc_attr($settings['overlay_opacity']); ?>" id="ka-overlay-opacity" />
                                <span class="ka-range-value"><?php echo esc_html($settings['overlay_opacity']); ?></span>
                            </div>
                        </div>
                    </div>

                    <h4 class="ka-settings-subheading"><?php esc_html_e('Logo', 'king-addons'); ?></h4>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Show Logo', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="logo_enabled" value="1" <?php checked(!empty($settings['logo_enabled'])); ?> id="ka-logo-enabled" />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Display logo above the animation', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="ka-row ka-logo-row" style="<?php echo empty($settings['logo_enabled']) ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Logo Image', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-image-upload">
                                <input type="hidden" name="logo_url" id="ka-logo-url" value="<?php echo esc_attr($settings['logo_url']); ?>" />
                                <div class="ka-image-preview" id="ka-logo-preview">
                                    <?php if (!empty($settings['logo_url'])): ?>
                                    <img src="<?php echo esc_url($settings['logo_url']); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" id="ka-logo-upload-btn">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e('Upload Logo', 'king-addons'); ?>
                                </button>
                                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" id="ka-logo-remove-btn" style="<?php echo empty($settings['logo_url']) ? 'display:none' : ''; ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="ka-row ka-logo-row" style="<?php echo empty($settings['logo_enabled']) ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Logo Max Width', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="logo_max_width" value="<?php echo esc_attr($settings['logo_max_width']); ?>" min="20" max="500" /> <span class="ka-unit">px</span>
                        </div>
                    </div>

                    <h4 class="ka-settings-subheading"><?php esc_html_e('Text', 'king-addons'); ?></h4>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Show Text', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="text_enabled" value="1" <?php checked(!empty($settings['text_enabled'])); ?> id="ka-text-enabled" />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Display text below the animation', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="ka-row ka-text-row" style="<?php echo empty($settings['text_enabled']) ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Text Content', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="text_content" value="<?php echo esc_attr($settings['text_content']); ?>" placeholder="<?php esc_attr_e('Loading...', 'king-addons'); ?>" />
                        </div>
                    </div>

                    <div class="ka-row ka-text-row" style="<?php echo empty($settings['text_enabled']) ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Text Size', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="text_size" value="<?php echo esc_attr($settings['text_size']); ?>" min="10" max="72" /> <span class="ka-unit">px</span>
                        </div>
                    </div>

                    <div class="ka-row ka-text-row" style="<?php echo empty($settings['text_enabled']) ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Text Weight', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="text_weight">
                                <option value="300" <?php selected($settings['text_weight'], '300'); ?>><?php esc_html_e('Light', 'king-addons'); ?></option>
                                <option value="400" <?php selected($settings['text_weight'], '400'); ?>><?php esc_html_e('Normal', 'king-addons'); ?></option>
                                <option value="500" <?php selected($settings['text_weight'], '500'); ?>><?php esc_html_e('Medium', 'king-addons'); ?></option>
                                <option value="600" <?php selected($settings['text_weight'], '600'); ?>><?php esc_html_e('Semi Bold', 'king-addons'); ?></option>
                                <option value="700" <?php selected($settings['text_weight'], '700'); ?>><?php esc_html_e('Bold', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="ka-row ka-text-row" style="<?php echo empty($settings['text_enabled']) ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Text Color', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="text_color" value="<?php echo esc_attr($settings['text_color']); ?>" class="ka-color-picker" data-default-color="#1d1d1f" data-alpha-enabled="true" />
                        </div>
                    </div>

                    <h4 class="ka-settings-subheading"><?php esc_html_e('Animation', 'king-addons'); ?></h4>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Accent Color', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="accent_color" value="<?php echo esc_attr($settings['accent_color']); ?>" class="ka-color-picker" data-default-color="#0071e3" data-alpha-enabled="true" />
                            <p class="ka-row-desc"><?php esc_html_e('Main color for the animation.', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Animation Size', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="spinner_size" value="<?php echo esc_attr($settings['spinner_size']); ?>" min="16" max="200" /> <span class="ka-unit">px</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Behavior Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-performance"></span>
                    <h2><?php esc_html_e('Behavior', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Trigger Type', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="trigger_type">
                                <option value="always" <?php selected($settings['trigger_type'], 'always'); ?>><?php esc_html_e('Every Page Load', 'king-addons'); ?></option>
                                <option value="first_visit" <?php selected($settings['trigger_type'], 'first_visit'); ?>><?php esc_html_e('First Visit Only', 'king-addons'); ?></option>
                                <option value="once_per_session" <?php selected($settings['trigger_type'], 'once_per_session'); ?>><?php esc_html_e('Once Per Session', 'king-addons'); ?></option>
                                <option value="once_per_day" <?php selected($settings['trigger_type'], 'once_per_day'); ?>><?php esc_html_e('Once Per Day', 'king-addons'); ?></option>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('When to show the preloader to visitors.', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Hide Strategy', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="hide_strategy">
                                <option value="window_load" <?php selected($settings['hide_strategy'], 'window_load'); ?>><?php esc_html_e('On Window Load', 'king-addons'); ?></option>
                                <option value="dom_ready" <?php selected($settings['hide_strategy'], 'dom_ready'); ?>><?php esc_html_e('On DOM Ready + Delay', 'king-addons'); ?></option>
                                <option value="timeout" <?php selected($settings['hide_strategy'], 'timeout'); ?>><?php esc_html_e('After Timeout', 'king-addons'); ?></option>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('When to hide the preloader.', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Minimum Display Time', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="min_display_time" value="<?php echo esc_attr($settings['min_display_time']); ?>" min="0" max="10000" /> <span class="ka-unit">ms</span>
                            <p class="ka-row-desc"><?php esc_html_e('Minimum time to show preloader (prevents flashing).', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Maximum Display Time', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="max_display_time" value="<?php echo esc_attr($settings['max_display_time']); ?>" min="1000" max="30000" /> <span class="ka-unit">ms</span>
                            <p class="ka-row-desc"><?php esc_html_e('Force hide after this time (prevents stuck overlay).', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Allow Skip', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="allow_skip" value="1" <?php checked(!empty($settings['allow_skip'])); ?> id="ka-allow-skip" />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Allow users to close the preloader early', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="ka-row ka-skip-row" style="<?php echo empty($settings['allow_skip']) ? 'display:none' : ''; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Skip Method', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-checkbox-group">
                                <label class="ka-checkbox-item">
                                    <input type="radio" name="skip_method" value="click" <?php checked($settings['skip_method'], 'click'); ?> />
                                    <?php esc_html_e('Click to Close', 'king-addons'); ?>
                                </label>
                                <label class="ka-checkbox-item">
                                    <input type="radio" name="skip_method" value="escape" <?php checked($settings['skip_method'], 'escape'); ?> />
                                    <?php esc_html_e('Press ESC', 'king-addons'); ?>
                                </label>
                                <label class="ka-checkbox-item">
                                    <input type="radio" name="skip_method" value="both" <?php checked($settings['skip_method'], 'both'); ?> />
                                    <?php esc_html_e('Both', 'king-addons'); ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Lock Scroll', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="lock_scroll" value="1" <?php checked(!empty($settings['lock_scroll'])); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Prevent scrolling while preloader is visible', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Preview -->
        <div class="ka-preloader-settings-sidebar">
            <div class="ka-card ka-preloader-live-preview" id="ka-settings-live-preview">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-visibility"></span>
                    <h2><?php esc_html_e('Live Preview', 'king-addons'); ?></h2>
                </div>
                <div class="ka-preloader-preview-frame">
                    <div class="ka-preloader-preview-wrapper" id="ka-live-preview-wrapper">
                        <?php $this->render_preset_html($settings['template'], $settings); ?>
                    </div>
                </div>
                <div class="ka-card-footer">
                    <button type="button" class="ka-btn ka-btn-secondary" onclick="kaPreloaderOpenPreview()">
                        <span class="dashicons dashicons-fullscreen-alt"></span>
                        <?php esc_html_e('Full Preview', 'king-addons'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden fields for template (to preserve it) -->
    <input type="hidden" name="template" value="<?php echo esc_attr($settings['template']); ?>" />

    <!-- Save Button -->
    <div class="ka-submit ka-preloader-submit">
        <button type="submit" class="ka-btn ka-btn-primary">
            <span class="dashicons dashicons-saved"></span>
            <?php esc_html_e('Save Settings', 'king-addons'); ?>
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide pages selection
    const showOnSelect = document.getElementById('ka-preloader-show-on');
    if (showOnSelect) {
        showOnSelect.addEventListener('change', function() {
            document.getElementById('ka-selected-pages-row').style.display = this.value === 'selected' ? '' : 'none';
            document.getElementById('ka-excluded-pages-row').style.display = this.value === 'exclude' ? '' : 'none';
        });
    }

    // Show/hide background type options
    const bgTypeSelect = document.getElementById('ka-preloader-bg-type');
    if (bgTypeSelect) {
        bgTypeSelect.addEventListener('change', function() {
            document.querySelectorAll('.ka-bg-solid-row').forEach(el => el.style.display = this.value === 'solid' ? '' : 'none');
            document.querySelectorAll('.ka-bg-gradient-row').forEach(el => el.style.display = this.value === 'gradient' ? '' : 'none');
            document.querySelectorAll('.ka-bg-image-row').forEach(el => el.style.display = this.value === 'image' ? '' : 'none');
        });
    }

    // Show/hide logo options
    const logoEnabled = document.getElementById('ka-logo-enabled');
    if (logoEnabled) {
        logoEnabled.addEventListener('change', function() {
            document.querySelectorAll('.ka-logo-row').forEach(el => el.style.display = this.checked ? '' : 'none');
        });
    }

    // Show/hide text options
    const textEnabled = document.getElementById('ka-text-enabled');
    if (textEnabled) {
        textEnabled.addEventListener('change', function() {
            document.querySelectorAll('.ka-text-row').forEach(el => el.style.display = this.checked ? '' : 'none');
        });
    }

    // Show/hide skip method
    const allowSkip = document.getElementById('ka-allow-skip');
    if (allowSkip) {
        allowSkip.addEventListener('change', function() {
            document.querySelectorAll('.ka-skip-row').forEach(el => el.style.display = this.checked ? '' : 'none');
        });
    }

    // Range value display
    const opacityRange = document.getElementById('ka-overlay-opacity');
    if (opacityRange) {
        opacityRange.addEventListener('input', function() {
            this.nextElementSibling.textContent = this.value;
        });
    }
});
</script>
