<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap king-addons-ai-settings">
    <h1 class="title"><?php esc_html_e('AI Settings', 'king-addons'); ?></h1>
    <form method="post" action="options.php">
        <?php
        // Output settings fields for the registered option group
        settings_fields('king_addons_ai');
        // Output all sections and fields for this page
        do_settings_sections('king-addons-ai-settings');
        // Output save button
        submit_button( esc_html__('Save Settings', 'king-addons') );
        ?>
    </form>
</div> 