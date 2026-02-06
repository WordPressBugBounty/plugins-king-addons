<?php
/**
 * Smart Links add/edit view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$link_id = isset($_GET['link_id']) ? absint($_GET['link_id']) : 0;
$link = $link_id ? $service->get_link($link_id) : null;
$is_edit = (bool) $link;

$slug = $is_edit ? (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_SLUG, true) : '';
$destination = $is_edit ? (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_DESTINATION, true) : '';
$status = $is_edit ? (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_STATUS, true) : 'active';
$tags = $is_edit ? (array) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_TAGS, true) : [];
$notes = $is_edit ? (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_NOTES, true) : '';
$utm = $is_edit ? (array) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_UTM, true) : [];

$redirect_type = $is_edit ? (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_REDIRECT, true) : '';
if (!in_array($redirect_type, ['301', '302'], true)) {
    $redirect_type = (string) $settings['default_redirect_type'];
}

$allow_query = $is_edit ? (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_ALLOW_QUERY, true) : '';
$allow_query = $allow_query === '' ? !empty($settings['pass_query_params']) : (bool) $allow_query;

$utm_enabled = !empty($utm['enabled']);
$short_url = $slug ? $service->build_short_url($slug) : $service->build_short_url('your-slug');
$tags_value = !empty($tags) ? implode(', ', $tags) : '';
$slug_mode = $is_edit && !empty($settings['allow_manual_slug']) ? 'manual' : 'auto';
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="kng_smart_links_save">
    <input type="hidden" name="link_id" value="<?php echo esc_attr($link_id); ?>">
    <?php wp_nonce_field('kng_smart_links_save'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-links green"></span>
            <h2><?php echo $is_edit ? esc_html__('Edit Link', 'king-addons') : esc_html__('Create New Link', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Title', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="text" name="title" value="<?php echo esc_attr($is_edit ? $link->post_title : ''); ?>" placeholder="<?php esc_attr_e('Campaign name', 'king-addons'); ?>">
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Destination URL', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="url" id="kng-destination" name="destination_url" value="<?php echo esc_attr($destination); ?>" placeholder="https://example.com/product">
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Slug Mode', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <div class="ka-radio-group">
                        <label class="ka-radio-item">
                            <input type="radio" name="slug_mode" value="auto" <?php checked($slug_mode, 'auto'); ?> />
                            <?php esc_html_e('Auto generate', 'king-addons'); ?>
                        </label>
                        <label class="ka-radio-item">
                            <input type="radio" name="slug_mode" value="manual" <?php checked($slug_mode, 'manual'); ?> <?php disabled(empty($settings['allow_manual_slug'])); ?> />
                            <?php esc_html_e('Manual slug', 'king-addons'); ?>
                        </label>
                        <?php if (empty($settings['allow_manual_slug'])) : ?>
                            <span class="ka-pro-badge">PRO</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="ka-row kng-slug-manual" style="display:none;">
                <div class="ka-row-label"><?php esc_html_e('Custom Slug', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="text" id="kng-slug-input" name="slug" value="<?php echo esc_attr($slug); ?>" placeholder="summer-sale">
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Short URL', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <div class="ka-link-preview">
                        <code id="kng-short-preview" data-copy="<?php echo esc_attr($short_url); ?>"><?php echo esc_html($short_url); ?></code>
                        <button type="button" id="kng-copy-short" class="ka-copy-btn" data-copy="<?php echo esc_attr($short_url); ?>">
                            <span class="dashicons dashicons-admin-page"></span>
                            <span><?php esc_html_e('Copy', 'king-addons'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Status', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="status" value="disabled" <?php checked($status === 'disabled'); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Disable this link', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-site green"></span>
            <h2><?php esc_html_e('UTM Builder', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Enable UTM', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" id="kng-utm-enabled" name="utm_enabled" value="1" <?php checked($utm_enabled); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Append UTM parameters', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
            <div class="ka-inline-fields">
                <div class="ka-field">
                    <label><?php esc_html_e('utm_source', 'king-addons'); ?></label>
                    <input type="text" id="kng-utm_source" name="utm_source" value="<?php echo esc_attr($utm['utm_source'] ?? ''); ?>" placeholder="newsletter">
                </div>
                <div class="ka-field">
                    <label><?php esc_html_e('utm_medium', 'king-addons'); ?></label>
                    <input type="text" id="kng-utm_medium" name="utm_medium" value="<?php echo esc_attr($utm['utm_medium'] ?? ''); ?>" placeholder="email">
                </div>
                <div class="ka-field">
                    <label><?php esc_html_e('utm_campaign', 'king-addons'); ?></label>
                    <input type="text" id="kng-utm_campaign" name="utm_campaign" value="<?php echo esc_attr($utm['utm_campaign'] ?? ''); ?>" placeholder="launch">
                </div>
            </div>
            <div class="ka-inline-fields" style="margin-top:16px;">
                <div class="ka-field">
                    <label><?php esc_html_e('utm_term', 'king-addons'); ?></label>
                    <input type="text" id="kng-utm_term" name="utm_term" value="<?php echo esc_attr($utm['utm_term'] ?? ''); ?>">
                </div>
                <div class="ka-field">
                    <label><?php esc_html_e('utm_content', 'king-addons'); ?></label>
                    <input type="text" id="kng-utm_content" name="utm_content" value="<?php echo esc_attr($utm['utm_content'] ?? ''); ?>">
                </div>
            </div>
            <div class="ka-row" style="margin-top:16px;">
                <div class="ka-row-label"><?php esc_html_e('Preview', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <div class="ka-utm-preview" id="kng-utm-preview"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-tag green"></span>
            <h2><?php esc_html_e('Tags & Notes', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Tags', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="text" name="tags" value="<?php echo esc_attr($tags_value); ?>" placeholder="summer, launch">
                    <p class="ka-row-desc"><?php esc_html_e('Separate tags with commas.', 'king-addons'); ?></p>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Notes', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <textarea name="notes" rows="3"><?php echo esc_textarea($notes); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-settings green"></span>
            <h2><?php esc_html_e('Advanced', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Redirect Type', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="redirect_type">
                        <option value="301" <?php selected($redirect_type, '301'); ?>><?php esc_html_e('301 Permanent', 'king-addons'); ?></option>
                        <option value="302" <?php selected($redirect_type, '302'); ?>><?php esc_html_e('302 Temporary', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Query Passthrough', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="allow_query" value="1" <?php checked($allow_query); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Forward query parameters', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card ka-smart-links-pro-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-lock pink"></span>
            <h2><?php esc_html_e('Pro Features', 'king-addons'); ?></h2>
            <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </div>
        <div class="ka-card-body">
            <p><?php esc_html_e('Unlock A/B rotation, rules engine, expiration, and password protection.', 'king-addons'); ?></p>
            <ul>
                <li><?php esc_html_e('A/B testing with multiple destinations', 'king-addons'); ?></li>
                <li><?php esc_html_e('Geo, device, and referrer rules', 'king-addons'); ?></li>
                <li><?php esc_html_e('Password-protected and expiring links', 'king-addons'); ?></li>
            </ul>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-submit">
            <button type="submit" class="ka-btn ka-btn-primary">
                <?php echo $is_edit ? esc_html__('Save Changes', 'king-addons') : esc_html__('Create Link', 'king-addons'); ?>
            </button>
        </div>
    </div>
</form>
