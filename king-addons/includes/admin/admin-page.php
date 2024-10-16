<?php

/** @noinspection SpellCheckingInspection */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This page can only be accessed by admins, so nonce verification is not required. Check Admin.php, showAdminPage function.
if (isset($_GET['settings-updated'])) {
    add_settings_error('king_addons_messages', 'king_addons_message', esc_html__('Settings Saved', 'king-addons'), 'updated');
}

// show error/update messages
settings_errors('king_addons_messages');

$options = get_option('king_addons_options');
?>
<div class="king-addons-admin">
    <div class="kng-intro">
        <div class="kng-intro-wrap">
            <div class="kng-intro-wrap-1">
                <h1 class="kng-intro-title"><?php echo esc_html(get_admin_page_title()); ?></h1>
                <h2 class="kng-intro-subtitle"><?php echo esc_html__('Free, lightweight, super-fast Elementor addons that do not affect website performance', 'king-addons'); ?></h2>
            </div>
            <div class="kng-intro-wrap-2">
                <div class="kng-navigation">
                    <div class="kng-nav-item">
                        <a href="../wp-admin/admin.php?page=king-addons-templates">
                            <img src="<?php echo esc_url(KING_ADDONS_URL) . 'includes/admin/img/icon-for-templates.svg'; ?>"
                                 alt="<?php echo esc_html__('Premium Templates', 'king-addons'); ?>">
                            <div class="kng-nav-item-txt"><?php echo esc_html__('Premium Templates', 'king-addons'); ?></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--suppress HtmlUnknownTarget -->
    <form action="options.php" method="post">
        <?php

        // output security fields for the registered setting "king_addons"
        settings_fields('king_addons');

        // output setting sections and their fields
        // (sections are registered for "king-addons", each field is registered to a specific section)

        $page = 'king-addons';

        global $wp_settings_sections, $wp_settings_fields;

        foreach ((array)$wp_settings_sections[$page] as $section) {

            $section_current = $section['id'];

            if (!isset($wp_settings_fields[$page][$section_current])) {
                continue;
            }

            if ($section['callback']) {
                call_user_func($section['callback'], $section);
            }

            echo '<table class="kng-form-table"><tbody class="kng-tbody">';

            foreach ((array)$wp_settings_fields[$page][$section_current] as $field) {

                $args = $field['args'];
                $class = '';

                if (!empty($field['args']['class'])) {
                    $class = $field['args']['class'];
                }
                ?>
                <tr class="<?php echo esc_attr($class); ?>">
                    <td>
                        <div class="kng-td-wrap">
                            <div class="kng-td">
                                <div class="kng-td-icon">
                                    <img alt="<?php echo esc_attr($args['label_for']); ?>"
                                         src="<?php echo esc_attr(KING_ADDONS_URL) . 'includes/admin/img/' . esc_attr($args['label_for']); ?>.svg?v=<?php echo esc_attr(KING_ADDONS_VERSION); ?>"
                                         class="kng-item-icon"
                                         width="80px"/>
                                </div>
                                <div class="kng-td-content">
                                    <h3><?php echo esc_attr($field['title']); ?></h3>
                                    <p class="kng-td-description">
                                        <?php echo esc_attr($args['description']); ?>
                                    </p>
                                    <div class="kng-settings-switch-box">
                                        <input type="hidden"
                                               name="king_addons_options[<?php echo esc_attr($args['label_for']); ?>]"
                                               value="disabled"/>
                                        <input type="checkbox"
                                               class="kng-settings-switch"
                                               id="<?php echo esc_attr($args['label_for']); ?>"
                                               name="king_addons_options[<?php echo esc_attr($args['label_for']); ?>]"
                                               value="enabled"
                                            <?php checked(isset($options[$args['label_for']]) && $options[$args['label_for']] === 'enabled'); ?>
                                        />
                                        <label for="<?php echo esc_attr($args['label_for']); ?>"
                                               class="kng-settings-switch-label"></label>
                                    </div>
                                    <?php
                                    $demo_link = $args['demo_link'];
                                    if (!empty($demo_link)) {
                                        echo '<a class="kng-td-link" href="' . esc_url($demo_link) . '"target="_blank">' . esc_html__('View Demo', 'king-addons') . '</a>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php

            }

            echo '</tbody></table>';

        }
        ?>
        <div class="kng-btn-submit-wrap">
            <button type="submit" name="submit" id="submit" class="kng-btn-submit"
                    value="submit"><?php echo esc_html__('SAVE SETTINGS', 'king-addons'); ?></button>
        </div>
    </form>
</div>