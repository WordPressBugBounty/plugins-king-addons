<?php
/**
 * Partial entries for Form Builder.
 *
 * Saves what a visitor has typed before they submit, so an abandoned form still
 * leaves something behind. Entries land in the same list as completed
 * submissions, as drafts.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stores and clears in-progress submissions.
 */
class Form_Partial_Entries
{
    /**
     * Post type shared with completed submissions.
     */
    public const POST_TYPE = 'king-addons-fb-sub';

    /**
     * Meta flag marking a row as still in progress.
     */
    public const META_PARTIAL = 'king_addons_partial';

    /**
     * Meta holding the browser's key for one visit.
     */
    public const META_KEY = 'king_addons_partial_key';

    /**
     * Option prefix for the per-form switch.
     */
    private const OPTION_PREFIX = 'king_addons_partial_entries_';

    /**
     * Cron hook that clears old drafts.
     */
    private const CRON_HOOK = 'king_addons_form_partial_cleanup';

    /**
     * How long an unfinished entry is kept, in days.
     */
    private const RETENTION_DAYS = 30;

    /**
     * Register the endpoint and the cleanup schedule.
     */
    public function __construct()
    {
        add_action('wp_ajax_king_addons_form_builder_partial', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_partial', [self::class, 'handle']);

        add_action(self::CRON_HOOK, [self::class, 'cleanup']);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Remember whether a form saves partial entries.
     *
     * @param string $form_id Form element id.
     * @param bool   $enabled Whether it is switched on.
     *
     * @return void
     */
    public static function save_setting(string $form_id, bool $enabled): void
    {
        update_option(self::OPTION_PREFIX . $form_id, $enabled ? '1' : '0', false);
    }

    /**
     * Whether a form saves partial entries.
     *
     * @param string $form_id Form element id.
     *
     * @return bool
     */
    public static function is_enabled(string $form_id): bool
    {
        return '1' === (string) get_option(self::OPTION_PREFIX . $form_id, '0');
    }

    /**
     * Store, update or clear one in-progress entry.
     *
     * @return void
     */
    public static function handle(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            wp_send_json_error(['message' => 'invalid-nonce']);
        }

        $form_id = isset($_POST['king_addons_form_id'])
            ? sanitize_text_field(wp_unslash($_POST['king_addons_form_id']))
            : '';

        if ('' === $form_id || !self::is_enabled($form_id)) {
            wp_send_json_error(['message' => 'not-enabled']);
        }

        $page_id = isset($_POST['form_page_id']) ? absint(wp_unslash($_POST['form_page_id'])) : 0;
        if (!Form_Builder_Security::is_valid_submission_page($page_id)) {
            wp_send_json_error(['message' => 'bad-page']);
        }

        $key = isset($_POST['entry_key']) ? sanitize_key(wp_unslash($_POST['entry_key'])) : '';
        if ('' === $key || strlen($key) < 8 || strlen($key) > 64) {
            wp_send_json_error(['message' => 'bad-key']);
        }

        $existing = self::find($form_id, $key);

        // The visitor got to the end: the completed submission is the record now.
        if (!empty($_POST['finalise'])) {
            if ($existing) {
                wp_delete_post($existing, true);
            }

            wp_send_json_success(['message' => 'cleared']);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked above.
        $raw = isset($_POST['form_content']) && is_array($_POST['form_content']) ? wp_unslash($_POST['form_content']) : [];
        if (empty($raw)) {
            wp_send_json_error(['message' => 'empty']);
        }

        $post_id = $existing;

        if (!$post_id) {
            // One row per visit; a burst of new keys from one address would
            // otherwise be an easy way to fill the table.
            if (self::count_recent() > 200) {
                wp_send_json_error(['message' => 'too-many']);
            }

            $post_id = wp_insert_post([
                'post_type' => self::POST_TYPE,
                'post_status' => 'draft',
                'post_title' => sprintf(
                    /* translators: %s: date and time. */
                    esc_html__('In progress - %s', 'king-addons'),
                    current_time('mysql')
                ),
            ]);

            if (is_wp_error($post_id) || !$post_id) {
                wp_send_json_error(['message' => 'insert-failed']);
            }

            update_post_meta($post_id, self::META_PARTIAL, '1');
            update_post_meta($post_id, self::META_KEY, $key);
            update_post_meta($post_id, 'king_addons_form_id', $form_id);
            update_post_meta($post_id, 'king_addons_form_page_id', $page_id);
            update_post_meta($post_id, 'king_addons_user_ip', class_exists('King_Addons\\Core') ? Core::getClientIP() : '');
        }

        self::store_fields((int) $post_id, $raw);

        wp_send_json_success(['message' => 'saved', 'entry' => (int) $post_id]);
    }

    /**
     * Write the submitted values onto the draft.
     *
     * @param int          $post_id Draft id.
     * @param array<mixed> $raw     Form content from the browser.
     *
     * @return void
     */
    private static function store_fields(int $post_id, array $raw): void
    {
        $stored = [];

        foreach ($raw as $key => $value) {
            if (!is_array($value) || count($value) < 2) {
                continue;
            }

            $meta_key = sanitize_key((string) $key);
            if ('' === $meta_key) {
                continue;
            }

            $stored[] = $meta_key;

            update_post_meta($post_id, $meta_key, [
                sanitize_text_field((string) $value[0]),
                is_array($value[1]) ? array_map('sanitize_text_field', $value[1]) : sanitize_text_field((string) $value[1]),
                isset($value[2]) ? sanitize_text_field((string) $value[2]) : '',
            ]);
        }

        // A field the visitor cleared, or one hidden by conditional logic, must
        // not linger from an earlier save.
        $previous = (array) get_post_meta($post_id, 'king_addons_partial_fields', true);
        foreach (array_diff($previous, $stored) as $gone) {
            delete_post_meta($post_id, (string) $gone);
        }

        update_post_meta($post_id, 'king_addons_partial_fields', $stored);
        update_post_meta($post_id, 'king_addons_partial_updated', current_time('mysql'));
    }

    /**
     * The draft belonging to one visit, if there is one.
     *
     * @param string $form_id Form element id.
     * @param string $key     Entry key.
     *
     * @return int Post id, or 0.
     */
    private static function find(string $form_id, string $key): int
    {
        $found = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'draft',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                ['key' => self::META_KEY, 'value' => $key],
                ['key' => 'king_addons_form_id', 'value' => $form_id],
            ],
        ]);

        return $found ? (int) $found[0] : 0;
    }

    /**
     * How many drafts were started in the last hour.
     *
     * @return int
     */
    private static function count_recent(): int
    {
        $recent = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'draft',
            'posts_per_page' => 201,
            'fields' => 'ids',
            'no_found_rows' => true,
            'date_query' => [['after' => '1 hour ago']],
            'meta_query' => [['key' => self::META_PARTIAL, 'value' => '1']],
        ]);

        return count($recent);
    }

    /**
     * Drop drafts nobody came back to finish.
     *
     * @return void
     */
    public static function cleanup(): void
    {
        $old = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'draft',
            'posts_per_page' => 200,
            'fields' => 'ids',
            'no_found_rows' => true,
            'date_query' => [['before' => self::RETENTION_DAYS . ' days ago']],
            'meta_query' => [['key' => self::META_PARTIAL, 'value' => '1']],
        ]);

        foreach ($old as $post_id) {
            wp_delete_post((int) $post_id, true);
        }
    }
}
