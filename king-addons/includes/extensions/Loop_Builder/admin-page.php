<?php
/**
 * Loop Builder admin screen.
 *
 * Included from King_Addons\Loop_Builder::render_admin_page(), which has
 * already checked the current user's capability.
 *
 * @package King_Addons
 */

use King_Addons\Loop_Builder;
use King_Addons\Loop_Builder\Document as Loop_Document;
use King_Addons\Loop_Builder\Renderer as Loop_Renderer;

if (!defined('ABSPATH')) {
    exit;
}

require_once KING_ADDONS_PATH . 'includes/admin/shared/page-shell.php';

$ka_loop_templates = Loop_Renderer::get_templates();
$ka_loop_sources = Loop_Builder::get_source_post_types();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ka_loop_error = isset($_GET['ka_error']) ? sanitize_key(wp_unslash($_GET['ka_error'])) : '';

$ka_notices = [];

if ('create' === $ka_loop_error) {
    $ka_notices[] = ['type' => 'error', 'text' => esc_html__('The template could not be created.', 'king-addons')];
}

ka_admin_page_open([
    'title' => esc_html__('Loop Builder', 'king-addons'),
    'subtitle' => esc_html__('Design one card in Elementor, then repeat it for every entry a query returns with the Loop Grid widget.', 'king-addons'),
    'icon' => 'dashicons-screenoptions',
    'icon_color' => 'pink',
    'notices' => $ka_notices,
]);
?>

<?php ka_admin_card_open(esc_html__('New loop item', 'king-addons'), 'dashicons-plus-alt'); ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="king_addons_create_loop_template">
        <?php wp_nonce_field('king_addons_create_loop_template'); ?>

        <?php ka_admin_row_open(esc_html__('Name', 'king-addons'), 'ka-loop-title'); ?>
            <input type="text" id="ka-loop-title" name="loop_title"
                placeholder="<?php esc_attr_e('Post card', 'king-addons'); ?>">
        <?php ka_admin_row_close(); ?>

        <?php ka_admin_row_open(esc_html__('Designed for', 'king-addons'), 'ka-loop-source'); ?>
            <select name="loop_source" id="ka-loop-source">
                <?php foreach ($ka_loop_sources as $ka_slug => $ka_label) : ?>
                    <option value="<?php echo esc_attr($ka_slug); ?>"<?php selected('post', $ka_slug); ?>>
                        <?php echo esc_html($ka_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php ka_admin_row_close(esc_html__('Only decides which entry the editor previews the card with.', 'king-addons')); ?>

        <p class="ka-submit">
            <button type="submit" class="ka-btn ka-btn-primary">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e('Create and edit', 'king-addons'); ?>
            </button>
        </p>
    </form>
<?php ka_admin_card_close(); ?>

<?php ka_admin_card_open(esc_html__('Loop items', 'king-addons'), 'dashicons-screenoptions'); ?>
    <?php if (empty($ka_loop_templates)) : ?>
        <div class="ka-empty">
            <p><?php esc_html_e('No loop items yet. Create one above, design the card in Elementor, then drop a Loop Grid widget on any page and pick it.', 'king-addons'); ?></p>
        </div>
    <?php else : ?>
        <table class="ka-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'king-addons'); ?></th>
                    <th><?php esc_html_e('Designed for', 'king-addons'); ?></th>
                    <th><?php esc_html_e('ID', 'king-addons'); ?></th>
                    <th class="ka-table-actions"><?php esc_html_e('Actions', 'king-addons'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ka_loop_templates as $ka_id => $ka_title) : ?>
                    <?php
                    $ka_source = (string) get_post_meta($ka_id, Loop_Document::META_SOURCE, true);
                    $ka_source_label = $ka_loop_sources[$ka_source] ?? $ka_source;
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($ka_title); ?></strong></td>
                        <td><?php echo esc_html('' !== $ka_source_label ? $ka_source_label : '—'); ?></td>
                        <td><code><?php echo (int) $ka_id; ?></code></td>
                        <td class="ka-table-actions">
                            <a class="ka-btn ka-btn-secondary ka-btn-sm" href="<?php echo esc_url(add_query_arg(['post' => $ka_id, 'action' => 'elementor'], admin_url('post.php'))); ?>">
                                <span class="dashicons dashicons-edit"></span>
                                <?php esc_html_e('Edit', 'king-addons'); ?>
                            </a>
                            <a class="ka-btn ka-btn-secondary ka-btn-sm" href="<?php echo esc_url(get_delete_post_link($ka_id)); ?>"
                                onclick="return window.confirm(<?php echo esc_attr(wp_json_encode(esc_html__('Move this loop item to the trash? Any grid using it will show nothing.', 'king-addons'))); ?>);">
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Trash', 'king-addons'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php ka_admin_card_close(); ?>

<?php ka_admin_page_close(); ?>
