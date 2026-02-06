<?php
/**
 * Theme Builder admin list table.
 *
 * @package King_Addons
 */

namespace King_Addons\Theme_Builder;

use WP_List_Table;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Renders Theme Builder templates list in admin.
 */
class Admin_List_Table extends WP_List_Table
{
    /**
     * Templates data.
     *
     * @var array<int,array<string,mixed>>
     */
    private array $items_data;

    /**
     * Base page URL.
     *
     * @var string
     */
    private string $base_url;

    /**
     * Constructor.
     *
     * @param array<int,array<string,mixed>> $items    Templates.
     * @param string                         $base_url Base admin page URL.
     */
    public function __construct(array $items, string $base_url)
    {
        parent::__construct([
            'plural' => 'templates',
            'singular' => 'template',
            'ajax' => false,
        ]);

        $this->items_data = $items;
        $this->base_url = $base_url;
    }

    /**
     * Define table columns.
     *
     * @return array<string,string>
     */
    public function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'title' => esc_html__('Title', 'king-addons'),
            'type' => esc_html__('Type', 'king-addons'),
            'location' => esc_html__('Location', 'king-addons'),
            'conditions' => esc_html__('Conditions', 'king-addons'),
            'priority' => esc_html__('Priority', 'king-addons'),
            'status' => esc_html__('Status', 'king-addons'),
            'pro' => esc_html__('Pro', 'king-addons'),
        ];
    }

    /**
     * Sortable columns.
     *
     * @return array<string,array<int,string>>
     */
    protected function get_sortable_columns(): array
    {
        return [
            'title' => ['title', false],
            'priority' => ['priority', false],
        ];
    }

    /**
     * Prepare items with filters and pagination.
     *
     * @return void
     */
    public function prepare_items(): void
    {
        // Set column headers - required for WP_List_Table to display properly
        $this->_column_headers = [
            $this->get_columns(),
            [], // hidden columns
            $this->get_sortable_columns(),
        ];

        $data = $this->filter_items($this->items_data);

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $this->items = array_slice($data, ($current_page - 1) * $per_page, $per_page);
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);
    }

    /**
     * Render checkbox column.
     *
     * @param array<string,mixed> $item Row item.
     *
     * @return string
     */
    protected function column_cb($item): string
    {
        return '<input type="checkbox" name="template_ids[]" value="' . esc_attr((string) $item['id']) . '" />';
    }

    /**
     * Render title column with actions.
     *
     * @param array<string,mixed> $item Row item.
     *
     * @return string
     */
    public function column_title($item): string
    {
        $title = !empty($item['title']) ? esc_html($item['title']) : esc_html__('(no title)', 'king-addons');
        $edit_link = esc_url(admin_url('post.php?post=' . $item['id'] . '&action=elementor'));
        $quick_link = '#';
        $toggle_action = !empty($item['enabled']) ? 'disable_template' : 'enable_template';
        $toggle_link = esc_url(wp_nonce_url(add_query_arg(['action' => $toggle_action, 'template_id' => $item['id']], $this->base_url), 'ka_theme_builder_toggle_' . $item['id']));
        $delete_link = esc_url(wp_nonce_url(add_query_arg(['action' => 'delete_template', 'template_id' => $item['id']], $this->base_url), 'ka_theme_builder_delete_' . $item['id']));

        $title_html = '<strong><a href="' . $edit_link . '">' . $title . '</a></strong>';

        $actions = [
            'edit' => '<a href="' . $edit_link . '">' . esc_html__('Edit in Elementor', 'king-addons') . '</a>',
            'quick_edit' => '<a href="' . $quick_link . '" class="ka-tb-quick-edit" data-template-id="' . esc_attr((string) $item['id']) . '" data-priority="' . esc_attr((string) ($item['priority'] ?? '')) . '" data-enabled="' . (!empty($item['enabled']) ? '1' : '0') . '">' . esc_html__('Quick Edit', 'king-addons') . '</a>',
            'toggle' => '<a href="' . $toggle_link . '">' . (!empty($item['enabled']) ? esc_html__('Disable', 'king-addons') : esc_html__('Enable', 'king-addons')) . '</a>',
            'delete' => '<a href="' . $delete_link . '" style="color: #ff3b30;">' . esc_html__('Delete', 'king-addons') . '</a>',
        ];

        if (function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only()) {
            $duplicate_link = wp_nonce_url(
                admin_url('admin-post.php?action=ka_theme_builder_duplicate&template_id=' . $item['id']),
                'ka_theme_builder_duplicate_' . $item['id']
            );
            $actions['duplicate'] = '<a href="' . esc_url($duplicate_link) . '">' . esc_html__('Duplicate', 'king-addons') . '</a>';
        }

        return $title_html . $this->row_actions($actions);
    }

    /**
     * Render default column output.
     *
     * @param array<string,mixed> $item        Row item.
     * @param string              $column_name Column name.
     *
     * @return string
     */
    public function column_default($item, $column_name): string
    {
        switch ($column_name) {
            case 'type':
                return esc_html($item['type'] ?? '');
            case 'location':
                return esc_html($item['sub_location'] ?? '');
            case 'priority':
                return esc_html((string) ($item['priority'] ?? ''));
            case 'status':
                return !empty($item['enabled']) ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons');
            case 'conditions':
                return esc_html($this->summarize_conditions($item['conditions'] ?? []));
            case 'pro':
                return !empty($item['is_pro_only']) ? esc_html__('Pro', 'king-addons') : esc_html__('Free', 'king-addons');
            default:
                return '';
        }
    }

    /**
     * Views for filters.
     *
     * @return array<string,string>
     */
    protected function get_views(): array
    {
        $current = $_GET['status'] ?? 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $base = remove_query_arg(['status', 'paged'], $this->base_url);

        $views = [
            'all' => '<a href="' . esc_url($base) . '"' . ('all' === $current ? ' class="current"' : '') . '>' . esc_html__('All', 'king-addons') . '</a>',
            'enabled' => '<a href="' . esc_url(add_query_arg('status', 'enabled', $base)) . '"' . ('enabled' === $current ? ' class="current"' : '') . '>' . esc_html__('Enabled', 'king-addons') . '</a>',
            'disabled' => '<a href="' . esc_url(add_query_arg('status', 'disabled', $base)) . '"' . ('disabled' === $current ? ' class="current"' : '') . '>' . esc_html__('Disabled', 'king-addons') . '</a>',
        ];

        return $views;
    }

    /**
     * Filter items by query args.
     *
     * @param array<int,array<string,mixed>> $items Items.
     *
     * @return array<int,array<string,mixed>>
     */
    private function filter_items(array $items): array
    {
        $status_filter = $_GET['status'] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $type_filter = $_GET['type'] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $location_filter = $_GET['location'] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $filtered = array_filter(
            $items,
            static function ($item) use ($status_filter, $type_filter, $location_filter) {
                if ('enabled' === $status_filter && empty($item['enabled'])) {
                    return false;
                }
                if ('disabled' === $status_filter && !empty($item['enabled'])) {
                    return false;
                }
                if (!empty($type_filter) && ($item['type'] ?? '') !== $type_filter) {
                    return false;
                }
                if (!empty($location_filter) && ($item['sub_location'] ?? '') !== $location_filter) {
                    return false;
                }
                return true;
            }
        );

        return array_values($filtered);
    }

    /**
     * Summarize conditions to human-friendly string.
     *
     * @param array<string,mixed> $conditions Conditions payload.
     *
     * @return string
     */
    private function summarize_conditions(array $conditions): string
    {
        $groups = $conditions['groups'] ?? [];
        if (empty($groups)) {
            return esc_html__('All', 'king-addons');
        }

        $summary = [];
        foreach ($groups as $group) {
            $rules = $group['rules'] ?? [];
            $summary[] = sprintf(
                /* translators: %d: number of rules */
                esc_html__('%d rules', 'king-addons'),
                count($rules)
            );
        }

        return implode(' / ', $summary);
    }
}




