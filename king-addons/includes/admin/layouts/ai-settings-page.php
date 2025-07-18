<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap king-addons-ai-settings king-addons-admin">
    <!-- Header Section -->
    <div class="king-addons-settings-header">
        <div class="king-addons-settings-header-content">
            <div class="king-addons-settings-header-icon">
                <img width="32px" height="32px" src="<?php echo esc_url(KING_ADDONS_URL . 'includes/admin/img/ai.svg'); ?>" alt="AI" />
            </div>
            <div class="king-addons-settings-header-text">
                <h1 class="king-addons-settings-title"><?php esc_html_e('AI Settings', 'king-addons'); ?></h1>
                <p class="king-addons-settings-subtitle"><?php esc_html_e('Configure OpenAI integration and AI features for Elementor editor', 'king-addons'); ?></p>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="king-addons-settings-container">
        <form method="post" action="options.php" class="king-addons-settings-form">
            <?php
            // Output settings fields for the registered option group
            settings_fields('king_addons_ai');
            // Output all sections and fields for this page
            do_settings_sections('king-addons-ai-settings');
            ?>
            
            <!-- Custom Save Button -->
            <div class="king-addons-settings-footer">
                <button type="submit" class="king-addons-save-button">
                    <?php esc_html_e('Save Settings', 'king-addons'); ?>
                </button>
            </div>
        </form>
    </div>
</div> 