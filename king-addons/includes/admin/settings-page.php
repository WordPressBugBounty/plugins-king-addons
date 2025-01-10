<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Handle form submission
if (isset($_POST['king_addons_settings_submit_settings'])) {
    // Verify nonce for security
    if (!isset($_POST['king_addons_settings_nonce_field']) ||
        !wp_verify_nonce($_POST['king_addons_settings_nonce_field'], 'king_addons_settings_save_settings')) {
        wp_die('Security check failed.');
    }

    // Sanitize and save the submitted data
    update_option('king_addons_google_map_api_key', sanitize_text_field($_POST['king_addons_google_map_api_key']));
    update_option('king_addons_mailchimp_api_key', sanitize_text_field($_POST['king_addons_mailchimp_api_key']));
    update_option('king_addons_recaptcha_v3_site_key', sanitize_text_field($_POST['king_addons_recaptcha_v3_site_key']));
    update_option('king_addons_recaptcha_v3_secret_key', sanitize_text_field($_POST['king_addons_recaptcha_v3_secret_key']));
    update_option('king_addons_recaptcha_v3_score_threshold', floatval($_POST['king_addons_recaptcha_v3_score_threshold']));

    // Show a success message
    add_settings_error('king_addons_messages', 'king_addons_message', esc_html__('Settings Saved', 'king-addons'), 'updated');
    settings_errors('king_addons_messages');
}

// Get existing values from the database
$google_map_key = get_option('king_addons_google_map_api_key', '');
$mailchimp_key = get_option('king_addons_mailchimp_api_key', '');
$recaptcha_site_key = get_option('king_addons_recaptcha_v3_site_key', '');
$recaptcha_secret_key = get_option('king_addons_recaptcha_v3_secret_key', '');
$recaptcha_score_threshold = get_option('king_addons_recaptcha_v3_score_threshold', 0.5);

// Render the settings form
?>
    <style>
        #wpwrap,
        #wpcontent,
        .king-addons-settings {
            display: none;
        }

        #wpcontent {
            min-height: 100vh;
            overflow-x: hidden;
        }

        #wpwrap,
        html,
        body,
        #wpcontent {
            background: #101112;
        }

        .wrap {
            margin: 0;
        }

        .king-addons-settings {
            max-width: 1200px;
            margin: 10px 20px 0 0;
            padding: 20px;
            border-radius: 30px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        h1.title {
            font-size: 25px;
            font-weight: 600;
            line-height: 1.5;
            display: inline-block;
            margin-top: 0;
            margin-bottom: 20px;
            padding: 0;
            background: linear-gradient(45deg, #E1CBFF, #9B62FF 50%, #5B03FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .king-addons-settings-table-wrap {
            padding: 30px 40px;
            border-radius: 30px;
            background: #1a1b1b;
        }

        th label {
            color: white;
            font-size: 15px;
        }

        .form-table td p, .form-table td p span {
            font-size: 15px;
            line-height: 1.4705882353;
            font-weight: 500;
            letter-spacing: normal;
            margin-top: 13px;
            color: #646970;
        }

        input[type=text], input[type=number] {
            background: #262829;
            font-size: 16px;
            color: white;
            border: 1px solid #484c4e;
            padding: 3px 14px;
        }
    </style>
    <div class="wrap">
        <div class="king-addons-settings">
            <h1 class="title"><?php echo esc_html__('Settings', 'king-addons'); ?></h1>
            <form method="post" action="">
                <?php
                // Nonce field for security
                wp_nonce_field('king_addons_settings_save_settings', 'king_addons_settings_nonce_field');
                ?>
                <div class="king-addons-settings-table-wrap">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="king_addons_google_map_api_key"><?php echo esc_html__('Google Map API Key', 'king-addons'); ?></label>
                            </th>
                            <td>
                                <input
                                        type="text"
                                        name="king_addons_google_map_api_key"
                                        id="king_addons_google_map_api_key"
                                        value="<?php echo esc_attr($google_map_key); ?>"
                                        class="regular-text"
                                >
                                <p class="description">
                                    <span><?php echo esc_html__('Enter your Google Map API key. You can obtain it from the Google Cloud Platform.', 'king-addons'); ?></span>
                                    <br>
                                    <a href="https://www.youtube.com/watch?v=O5cUoVpVUjU"
                                       target="_blank"><?php esc_html_e('How to get Google Map API Key?', 'king-addons'); ?></a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="king_addons_mailchimp_api_key"><?php echo esc_html__('MailChimp API Key', 'king-addons'); ?></label>
                            </th>
                            <td>
                                <input
                                        type="text"
                                        name="king_addons_mailchimp_api_key"
                                        id="king_addons_mailchimp_api_key"
                                        value="<?php echo esc_attr($mailchimp_key); ?>"
                                        class="regular-text"
                                >
                                <p class="description">
                                    <span><?php echo esc_html__('Insert your MailChimp API key here to integrate mailing features.', 'king-addons'); ?></span>
                                    <br>
                                    <a href="https://mailchimp.com/help/about-api-keys/"
                                       target="_blank"><?php esc_html_e('How to get MailChimp API Key?', 'king-addons'); ?></a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="king_addons_recaptcha_v3_site_key"><?php echo esc_html__('reCAPTCHA - Site Key', 'king-addons'); ?></label>
                            </th>
                            <td>
                                <input
                                        type="text"
                                        name="king_addons_recaptcha_v3_site_key"
                                        id="king_addons_recaptcha_v3_site_key"
                                        value="<?php echo esc_attr($recaptcha_site_key); ?>"
                                        class="regular-text"
                                >
                                <p class="description">
                                    <span><?php echo esc_html__('Enter your reCAPTCHA Site Key from the Google reCAPTCHA admin console. Add a reCAPTCHA element to the Form Builder fields to make it work.', 'king-addons'); ?></span>
                                    <br>
                                    <a href="https://www.google.com/recaptcha/about/"
                                       target="_blank"><?php esc_html_e('How to get reCAPTCHA Site Key?', 'king-addons'); ?></a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="king_addons_recaptcha_v3_secret_key"><?php echo esc_html__('reCAPTCHA - Secret Key', 'king-addons'); ?></label>
                            </th>
                            <td>
                                <input
                                        type="text"
                                        name="king_addons_recaptcha_v3_secret_key"
                                        id="king_addons_recaptcha_v3_secret_key"
                                        value="<?php echo esc_attr($recaptcha_secret_key); ?>"
                                        class="regular-text"
                                >
                                <p class="description">
                                    <span><?php echo esc_html__('Your reCAPTCHA Secret Key. Make sure to keep this secure.', 'king-addons'); ?></span>
                                    <br>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="king_addons_recaptcha_v3_score_threshold"><?php echo esc_html__('reCAPTCHA - Score Threshold', 'king-addons'); ?></label>
                            </th>
                            <td>
                                <input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="1"
                                        placeholder="0.5"
                                        name="king_addons_recaptcha_v3_score_threshold"
                                        id="king_addons_recaptcha_v3_score_threshold"
                                        value="<?php echo esc_attr($recaptcha_score_threshold); ?>"
                                        class="regular-text"
                                >
                                <p class="description">
                                    <?php echo esc_html__('Set a score threshold (0.0 to 1.0) for reCAPTCHA.', 'king-addons'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="kng-btn-submit-wrap">
                    <button type="submit" name="king_addons_settings_submit_settings" id="submit" class="kng-btn-submit"
                            value="submit"><?php echo esc_html__('SAVE SETTINGS', 'king-addons'); ?></button>
                </div>
            </form>
        </div>
    </div>
<?php