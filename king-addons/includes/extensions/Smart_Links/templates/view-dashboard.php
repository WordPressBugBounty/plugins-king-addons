<?php
/**
 * Smart Links dashboard view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_links = 0;
$count = wp_count_posts(\King_Addons\Smart_Links\Smart_Links::POST_TYPE);
if ($count && isset($count->publish)) {
    $total_links = (int) $count->publish;
}

$totals = $service->get_totals(7);
$top_link = $service->get_top_link(7);
$recent_links = get_posts([
    'post_type' => \King_Addons\Smart_Links\Smart_Links::POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="ka-stats-grid">
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Total Links', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($total_links)); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Clicks (7 days)', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($totals['clicks'])); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Unique (7 days)', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($totals['unique_clicks'])); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Top Link', 'king-addons'); ?></div>
        <?php if ($top_link && !empty($top_link['link'])) :
            $slug = (string) get_post_meta($top_link['link']->ID, \King_Addons\Smart_Links\Smart_Links::META_SLUG, true);
            $short_url = $service->build_short_url($slug);
            ?>
            <div class="ka-stat-value" style="font-size:16px;line-height:1.3">
                <div><?php echo esc_html($top_link['link']->post_title); ?></div>
                <small><?php echo esc_html($short_url); ?> · <?php echo esc_html(number_format_i18n($top_link['clicks'])); ?></small>
            </div>
        <?php else : ?>
            <div class="ka-stat-value" style="font-size:16px;line-height:1.3">
                <?php esc_html_e('No data yet', 'king-addons'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-clock green"></span>
        <h2><?php esc_html_e('Latest Links', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body" style="padding:0;">
        <?php if (empty($recent_links)) : ?>
            <div class="ka-empty">
                <span class="dashicons dashicons-admin-links"></span>
                <p><?php esc_html_e('Create your first smart link to start tracking clicks.', 'king-addons'); ?></p>
            </div>
        <?php else : ?>
            <table class="ka-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Link', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Short URL', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Clicks', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Status', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_links as $link) :
                        $slug = (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_SLUG, true);
                        $short_url = $service->build_short_url($slug);
                        $status = (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_STATUS, true);
                        $clicks = (int) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_CLICKS, true);
                        ?>
                        <tr>
                            <td class="ka-link-list-title">
                                <strong><?php echo esc_html($link->post_title); ?></strong>
                                <small><?php echo esc_html($slug); ?></small>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($short_url); ?>" class="ka-short-url-text" target="_blank" rel="noopener">
                                    <?php echo esc_html($short_url); ?>
                                </a>
                            </td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($clicks)); ?></td>
                            <td>
                                <span class="ka-status <?php echo $status === 'disabled' ? 'ka-status-disabled' : 'ka-status-enabled'; ?>">
                                    <span class="ka-status-dot"></span>
                                    <?php echo $status === 'disabled' ? esc_html__('Disabled', 'king-addons') : esc_html__('Active', 'king-addons'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="ka-upgrade-card">
    <h2><?php esc_html_e('Unlock Pro Analytics', 'king-addons'); ?></h2>
    <p><?php esc_html_e('Get geo insights, device breakdowns, referrer domains, A/B rotation, and automation rules.', 'king-addons'); ?></p>
    <ul>
        <li><?php esc_html_e('Country, device, and browser analytics', 'king-addons'); ?></li>
        <li><?php esc_html_e('A/B destination testing and rules engine', 'king-addons'); ?></li>
        <li><?php esc_html_e('Expiration, password protection, and deep links', 'king-addons'); ?></li>
    </ul>
    <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
        <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
    </a>
</div>
