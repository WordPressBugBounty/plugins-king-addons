<?php
/**
 * My Account Endpoints screen.
 *
 * @package King_Addons
 *
 * @var array<string,array<string,mixed>> $endpoints Endpoints with their settings.
 * @var bool                              $is_pro    Whether the paid tier is active.
 * @var array<int,string>                 $templates Elementor templates to pick from.
 * @var array<string,string>              $roles     Roles an endpoint can be limited to.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once KING_ADDONS_PATH . 'includes/admin/shared/page-shell.php';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ka_notice = isset($_GET['ka-notice']) ? sanitize_key(wp_unslash($_GET['ka-notice'])) : '';

$ka_notices = [];

if ('added' === $ka_notice) {
    $ka_notices[] = ['type' => 'success', 'text' => esc_html__('Endpoint added. Give it a template below so it has something to show.', 'king-addons')];
} elseif ('removed' === $ka_notice) {
    $ka_notices[] = ['type' => 'success', 'text' => esc_html__('Endpoint removed.', 'king-addons')];
} elseif ('exists' === $ka_notice) {
    $ka_notices[] = ['type' => 'error', 'text' => esc_html__('An endpoint with that address already exists.', 'king-addons')];
} elseif ('pro' === $ka_notice) {
    $ka_notices[] = ['type' => 'warning', 'text' => esc_html__('Custom endpoints are part of King Addons Pro. Your other changes were saved.', 'king-addons')];
}

if (!class_exists('WooCommerce')) {
    $ka_notices[] = [
        'type' => 'warning',
        'text' => esc_html__('WooCommerce is not active, so there is no My Account page to arrange.', 'king-addons'),
    ];
}

uasort($endpoints, static function ($a, $b) {
    return ((int) ($a['position'] ?? 10)) <=> ((int) ($b['position'] ?? 10));
});

ka_admin_page_open([
    'title' => esc_html__('My Account Endpoints', 'king-addons'),
    'subtitle' => esc_html__('Rename, reorder, hide or replace the pages inside the WooCommerce account area.', 'king-addons'),
    'icon' => 'dashicons-admin-users',
    'icon_color' => 'purple',
    'saved' => 'saved' === $ka_notice,
    'notices' => $ka_notices,
]);
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="king_addons_myaccount_endpoints_save">
    <input type="hidden" name="ka_remove_endpoint" id="ka-remove-endpoint" value="">
    <?php wp_nonce_field('king_addons_myaccount_endpoints_save'); ?>

    <?php foreach ($endpoints as $ka_slug => $ka_data) : ?>
        <?php
        $ka_field = 'ka_endpoint[' . esc_attr($ka_slug) . ']';
        $ka_id = 'ka-ep-' . sanitize_html_class($ka_slug);
        $ka_custom = !empty($ka_data['is_custom']);
        $ka_enabled = !empty($ka_data['enabled']);
        ?>
        <div class="ka-card ka-endpoint-card<?php echo $ka_enabled ? '' : ' is-off'; ?>">
            <div class="ka-card-header">
                <span class="dashicons <?php echo $ka_custom ? 'dashicons-admin-links' : 'dashicons-admin-page'; ?>"></span>
                <h2><?php echo esc_html($ka_data['label'] ?? $ka_slug); ?></h2>
                <code class="ka-endpoint-slug">/<?php echo esc_html($ka_slug); ?></code>
                <?php if ($ka_custom) : ?>
                    <span class="ka-badge ka-badge-neutral"><?php esc_html_e('Custom', 'king-addons'); ?></span>
                <?php endif; ?>
                <label class="ka-check ka-endpoint-toggle">
                    <input type="checkbox" name="<?php echo $ka_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[enabled]"
                        value="1" <?php checked($ka_enabled); ?>>
                    <span><?php esc_html_e('Visible', 'king-addons'); ?></span>
                </label>
            </div>
            <div class="ka-card-body">
                <?php ka_admin_row_open(esc_html__('Menu label', 'king-addons'), $ka_id . '-label'); ?>
                    <input type="text" id="<?php echo esc_attr($ka_id); ?>-label"
                        name="<?php echo $ka_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[label]"
                        value="<?php echo esc_attr((string) ($ka_data['label'] ?? '')); ?>">
                <?php ka_admin_row_close(); ?>

                <?php ka_admin_row_open(esc_html__('Order', 'king-addons'), $ka_id . '-position'); ?>
                    <input type="number" min="0" max="9999" step="5" id="<?php echo esc_attr($ka_id); ?>-position"
                        name="<?php echo $ka_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[position]"
                        value="<?php echo esc_attr((string) ($ka_data['position'] ?? 10)); ?>" style="max-width:110px">
                <?php ka_admin_row_close(esc_html__('Lower numbers come first in the account menu.', 'king-addons')); ?>

                <?php ka_admin_row_open(esc_html__('Content', 'king-addons'), $ka_id . '-template'); ?>
                    <select id="<?php echo esc_attr($ka_id); ?>-template"
                        name="<?php echo $ka_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[template_id]"
                        <?php disabled(!$is_pro); ?>>
                        <option value="0"><?php esc_html_e('WooCommerce default', 'king-addons'); ?></option>
                        <?php foreach ($templates as $ka_tpl_id => $ka_tpl_title) : ?>
                            <option value="<?php echo esc_attr((string) $ka_tpl_id); ?>"
                                <?php selected((int) ($ka_data['template_id'] ?? 0), (int) $ka_tpl_id); ?>>
                                <?php echo esc_html($ka_tpl_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$is_pro) : ?>
                        <input type="hidden" name="<?php echo $ka_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[template_id]"
                            value="<?php echo esc_attr((string) ($ka_data['template_id'] ?? 0)); ?>">
                    <?php endif; ?>
                <?php ka_admin_row_close(
                    $is_pro
                        ? esc_html__('Replace what this endpoint shows with an Elementor template.', 'king-addons')
                        : esc_html__('Replacing an endpoint with an Elementor template is part of King Addons Pro.', 'king-addons')
                ); ?>

                <?php ka_admin_row_open(esc_html__('Who can see it', 'king-addons')); ?>
                    <div class="ka-check-group ka-check-group--inline">
                        <?php foreach ($roles as $ka_role => $ka_role_label) : ?>
                            <label class="ka-check">
                                <input type="checkbox"
                                    name="<?php echo $ka_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[roles][]"
                                    value="<?php echo esc_attr($ka_role); ?>"
                                    <?php checked(in_array($ka_role, (array) ($ka_data['roles'] ?? []), true)); ?>>
                                <span><?php echo esc_html($ka_role_label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php ka_admin_row_close(esc_html__('Leave everything unticked to show it to all logged-in customers.', 'king-addons')); ?>

                <?php if ('customer-logout' === $ka_slug) : ?>
                    <?php ka_admin_row_open(esc_html__('Logging out', 'king-addons')); ?>
                        <label class="ka-check">
                            <input type="checkbox"
                                name="<?php echo $ka_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[confirm]"
                                value="1" <?php checked(!empty($ka_data['confirm'])); ?>>
                            <span><?php esc_html_e('Ask for confirmation first', 'king-addons'); ?></span>
                        </label>
                    <?php ka_admin_row_close(); ?>
                <?php endif; ?>

                <?php if ($ka_custom) : ?>
                    <div class="ka-row ka-row--danger">
                        <div class="ka-row-label"><?php esc_html_e('Remove', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <button type="submit" class="ka-btn ka-btn-secondary ka-endpoint-remove"
                                data-slug="<?php echo esc_attr($ka_slug); ?>"
                                data-confirm="<?php echo esc_attr(sprintf(
                                    /* translators: %s: endpoint address. */
                                    esc_html__('Remove the "%s" endpoint? Anything it linked to will 404.', 'king-addons'),
                                    $ka_slug
                                )); ?>">
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Delete this endpoint', 'king-addons'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php ka_admin_card_open(esc_html__('Add your own endpoint', 'king-addons'), 'dashicons-plus-alt'); ?>
        <?php if (!$is_pro) : ?>
            <p class="ka-pro-notice">
                <?php esc_html_e('Custom endpoints are part of King Addons Pro.', 'king-addons'); ?>
                <a href="https://kingaddons.com/pricing/?utm_source=kng-myaccount-endpoints&utm_medium=plugin&utm_campaign=kng" target="_blank" rel="noopener">
                    <?php esc_html_e('See plans', 'king-addons'); ?>
                </a>
            </p>
        <?php endif; ?>

        <?php ka_admin_row_open(esc_html__('Address', 'king-addons'), 'ka-new-endpoint-slug'); ?>
            <input type="text" id="ka-new-endpoint-slug" name="ka_new_endpoint_slug"
                placeholder="my-downloads" <?php disabled(!$is_pro); ?>>
        <?php ka_admin_row_close(esc_html__('Letters, numbers and dashes. It becomes /my-account/your-address/.', 'king-addons')); ?>

        <?php ka_admin_row_open(esc_html__('Menu label', 'king-addons'), 'ka-new-endpoint-label'); ?>
            <input type="text" id="ka-new-endpoint-label" name="ka_new_endpoint_label"
                placeholder="<?php esc_attr_e('My downloads', 'king-addons'); ?>" <?php disabled(!$is_pro); ?>>
        <?php ka_admin_row_close(); ?>
    <?php ka_admin_card_close(); ?>

    <p class="ka-submit">
        <button type="submit" class="ka-btn ka-btn-primary">
            <span class="dashicons dashicons-yes"></span>
            <?php esc_html_e('Save Changes', 'king-addons'); ?>
        </button>
    </p>
</form>

<script>
(function () {
    document.querySelectorAll('.ka-endpoint-remove').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (!window.confirm(button.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            document.getElementById('ka-remove-endpoint').value = button.dataset.slug;
        });
    });

    // Dim a card the moment its visibility is switched off, so the state is
    // obvious before saving.
    document.querySelectorAll('.ka-endpoint-toggle input').forEach(function (input) {
        input.addEventListener('change', function () {
            input.closest('.ka-endpoint-card').classList.toggle('is-off', !input.checked);
        });
    });
})();
</script>

<?php ka_admin_page_close(); ?>
