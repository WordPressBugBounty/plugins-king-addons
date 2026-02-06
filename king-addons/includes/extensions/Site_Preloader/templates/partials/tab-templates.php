<?php
/**
 * Site Preloader Templates Tab.
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

$current_template = $settings['template'] ?? 'spinner-circle';
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="ka-preloader-templates-form">
    <?php wp_nonce_field('king_addons_site_preloader_save'); ?>
    <input type="hidden" name="action" value="king_addons_site_preloader_save" />
    <input type="hidden" name="current_tab" value="templates" />
    <input type="hidden" name="template" id="ka-selected-template" value="<?php echo esc_attr($current_template); ?>" />
    
    <?php
    // Preserve other settings
    foreach ($settings as $key => $value) {
        if ($key === 'template') continue;
        if (is_array($value)) {
            foreach ($value as $arr_val) {
                echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($arr_val) . '" />';
            }
        } else {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }
    }
    ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-layout"></span>
            <h2><?php esc_html_e('Animation Templates', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <p class="ka-preloader-templates-intro">
                <?php esc_html_e('Select a preloader animation template. Click on any template to preview and select it.', 'king-addons'); ?>
            </p>

            <div class="ka-preloader-templates-grid">
                <?php foreach ($presets as $preset_id => $preset): ?>
                <div class="ka-preloader-template-card <?php echo $current_template === $preset_id ? 'active' : ''; ?> <?php echo !empty($preset['pro']) && !$is_pro ? 'pro-only' : ''; ?>" 
                     data-template="<?php echo esc_attr($preset_id); ?>"
                     onclick="kaPreloaderSelectTemplate('<?php echo esc_attr($preset_id); ?>')">
                    
                    <div class="ka-preloader-template-card__preview">
                        <div class="ka-preloader-template-card__animation" style="--kng-preloader-accent: <?php echo esc_attr($settings['accent_color']); ?>; --kng-preloader-size: 32px;">
                            <?php $this->render_preset_html($preset_id, array_merge($settings, ['logo_enabled' => false, 'text_enabled' => false])); ?>
                        </div>
                    </div>
                    
                    <div class="ka-preloader-template-card__info">
                        <h4 class="ka-preloader-template-card__title">
                            <?php echo esc_html($preset['title']); ?>
                            <?php if (!empty($preset['pro']) && !$is_pro): ?>
                            <span class="ka-pro-badge">PRO</span>
                            <?php endif; ?>
                        </h4>
                        <p class="ka-preloader-template-card__desc"><?php echo esc_html($preset['description']); ?></p>
                    </div>
                    
                    <?php if ($current_template === $preset_id): ?>
                    <div class="ka-preloader-template-card__check">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Pro Templates Promo -->
    <?php if (!$is_pro): ?>
    <div class="ka-upgrade-card">
        <h3><?php esc_html_e('Custom Preloader Builder', 'king-addons'); ?></h3>
        <p><?php esc_html_e('Create fully custom preloaders with Pro features', 'king-addons'); ?></p>
        <ul>
            <li><?php esc_html_e('Layer-based visual builder', 'king-addons'); ?></li>
            <li><?php esc_html_e('Lottie JSON animations', 'king-addons'); ?></li>
            <li><?php esc_html_e('SVG and GIF support', 'king-addons'); ?></li>
            <li><?php esc_html_e('Progress bar with real percentage', 'king-addons'); ?></li>
            <li><?php esc_html_e('Custom HTML snippets', 'king-addons'); ?></li>
        </ul>
        <a href="https://kingaddons.com/pricing/?utm_source=kng-preloader-templates-upgrade&utm_medium=plugin&utm_campaign=kng" target="_blank" class="ka-btn ka-btn-pink">
            <span class="dashicons dashicons-star-filled"></span>
            <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Save Button -->
    <div class="ka-submit ka-preloader-submit">
        <button type="submit" class="ka-btn ka-btn-primary">
            <span class="dashicons dashicons-saved"></span>
            <?php esc_html_e('Save Template', 'king-addons'); ?>
        </button>
    </div>
</form>

<script>
function kaPreloaderSelectTemplate(templateId) {
    // Check if it's a pro template and user doesn't have pro
    const card = document.querySelector('[data-template="' + templateId + '"]');
    if (card && card.classList.contains('pro-only')) {
        alert('<?php echo esc_js(__('This template requires Pro version.', 'king-addons')); ?>');
        return;
    }

    // Update hidden input
    document.getElementById('ka-selected-template').value = templateId;

    // Update visual selection
    document.querySelectorAll('.ka-preloader-template-card').forEach(card => {
        card.classList.remove('active');
        const check = card.querySelector('.ka-preloader-template-card__check');
        if (check) check.remove();
    });

    card.classList.add('active');
    
    // Add checkmark
    const checkDiv = document.createElement('div');
    checkDiv.className = 'ka-preloader-template-card__check';
    checkDiv.innerHTML = '<span class="dashicons dashicons-yes-alt"></span>';
    card.appendChild(checkDiv);
}
</script>
