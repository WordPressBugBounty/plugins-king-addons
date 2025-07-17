<?php

namespace King_Addons\Widgets\Login_Register_Form;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * User Profile Fields Handler for Login Register Form widget
 */
class User_Profile_Fields
{
    /**
     * Initialize hooks
     */
    public static function init()
    {
        add_action('show_user_profile', [__CLASS__, 'show_extra_profile_fields']);
        add_action('edit_user_profile', [__CLASS__, 'show_extra_profile_fields']);
        add_action('personal_options_update', [__CLASS__, 'save_extra_profile_fields']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_extra_profile_fields']);
    }

    /**
     * Show extra profile fields
     */
    public static function show_extra_profile_fields($user)
    {
        ?>
        <h3><?php esc_html_e('Additional Information', 'king-addons'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="phone"><?php esc_html_e('Phone', 'king-addons'); ?></label></th>
                <td>
                    <input type="text" name="phone" id="phone" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone', true)); ?>" class="regular-text" />
                    <br />
                    <span class="description"><?php esc_html_e('Please enter your phone number.', 'king-addons'); ?></span>
                </td>
            </tr>
            <?php
            // Show social login provider info
            $social_provider = get_user_meta($user->ID, 'king_addons_social_provider', true);
            if (!empty($social_provider)) {
                $provider_id = get_user_meta($user->ID, 'king_addons_social_provider_id', true);
                $social_picture = get_user_meta($user->ID, 'king_addons_social_picture', true);
                ?>
                <tr>
                    <th><?php esc_html_e('Social Login', 'king-addons'); ?></th>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if (!empty($social_picture)): ?>
                                <img src="<?php echo esc_url($social_picture); ?>" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%;" />
                            <?php endif; ?>
                            <div>
                                <strong><?php echo esc_html(ucfirst($social_provider)); ?></strong><br />
                                <small><?php printf(esc_html__('Connected via %s', 'king-addons'), esc_html(ucfirst($social_provider))); ?></small>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
            }
            
            // Show custom fields from King Addons
            $custom_fields = self::get_king_addons_custom_fields($user->ID);
            if (!empty($custom_fields)) {
                foreach ($custom_fields as $meta_key => $field_data) {
                    $field_label = $field_data['label'];
                    $field_value = $field_data['value'];
                    ?>
                    <tr>
                        <th><label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($field_label); ?></label></th>
                        <td>
                            <?php if (is_array($field_value)): ?>
                                <textarea name="<?php echo esc_attr($meta_key); ?>" id="<?php echo esc_attr($meta_key); ?>" class="regular-text" rows="3"><?php echo esc_textarea(implode(', ', $field_value)); ?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?php echo esc_attr($meta_key); ?>" id="<?php echo esc_attr($meta_key); ?>" value="<?php echo esc_attr($field_value); ?>" class="regular-text" />
                            <?php endif; ?>
                            <br />
                            <span class="description"><?php printf(esc_html__('Custom field: %s (added by King Addons)', 'king-addons'), esc_html($field_label)); ?></span>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
        </table>
        <?php
    }

    /**
     * Save extra profile fields
     */
    public static function save_extra_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (isset($_POST['phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
        }

        // Save King Addons custom fields
        $custom_fields = self::get_king_addons_custom_fields($user_id);
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $meta_key => $field_data) {
                if (isset($_POST[$meta_key])) {
                    $sanitized_value = sanitize_textarea_field($_POST[$meta_key]);
                    update_user_meta($user_id, $meta_key, $sanitized_value);
                }
            }
        }
    }

    /**
     * Get King Addons custom fields for a user
     */
    private static function get_king_addons_custom_fields($user_id)
    {
        $custom_fields = [];
        $all_meta = get_user_meta($user_id);
        
        foreach ($all_meta as $meta_key => $meta_value) {
            // Check if it's a King Addons custom field (but not a label)
            if (strpos($meta_key, 'king_addons_custom_field_') === 0 && strpos($meta_key, '_label') === false) {
                $field_value = is_array($meta_value) && count($meta_value) === 1 ? $meta_value[0] : $meta_value;
                
                // Try to get the field label
                $label_key = $meta_key . '_label';
                $field_label = get_user_meta($user_id, $label_key, true);
                
                $custom_fields[$meta_key] = [
                    'value' => $field_value,
                    'label' => !empty($field_label) ? $field_label : ucwords(str_replace(['king_addons_custom_field_', '_'], ['', ' '], $meta_key))
                ];
            }
        }
        
        return $custom_fields;
    }
} 