<?php
/**
 * One-shot repair of Cookie Consent and Age Gate texts stored with HTML entities.
 *
 * Defaults used to be wrapped in esc_html__(), so quotes and apostrophes were
 * written into the option as &quot; / &#039;. The banners insert those strings
 * with textContent, which does not decode entities.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Walks the two option arrays and undoes the three entities that leak on screen.
 */
final class Text_Entities_Migration
{
    public const FLAG = 'king_addons_migration_text_entities';

    /**
     * Hook a cheap flag check on admin and front so ZIP/FTP updates still run it.
     *
     * @return void
     */
    public static function boot(): void
    {
        add_action('init', [self::class, 'maybe_run'], 1);
        add_action('admin_init', [self::class, 'maybe_run'], 1);
    }

    /**
     * Decode stored texts once per site, then set the flag.
     *
     * @return void
     */
    public static function maybe_run(): void
    {
        if ('1' === (string) get_option(self::FLAG, '')) {
            return;
        }

        self::migrate_option(
            'king_addons_cookie_consent_options',
            [
                ['content', 'title'],
                ['content', 'message'],
                ['content', 'privacy_label'],
                ['content', 'cookie_label'],
                ['buttons', 'accept'],
                ['buttons', 'reject'],
                ['buttons', 'settings'],
                ['buttons', 'save'],
            ],
            true
        );

        self::migrate_option(
            'king_addons_age_gate_options',
            [
                ['design', 'title'],
                ['design', 'subtitle'],
                ['design', 'button_yes'],
                ['design', 'button_no'],
                ['behaviour', 'block_message'],
                ['dob', 'error_invalid'],
                ['dob', 'error_denied'],
            ],
            false
        );

        update_option(self::FLAG, '1', true);
    }

    /**
     * Decode listed string keys in one option. Categories are walked separately
     * for Cookie Consent.
     *
     * @param string             $option_name Option name.
     * @param array<int,array<int,string>> $paths Nested keys to decode.
     * @param bool               $with_categories Also decode category labels.
     *
     * @return void
     */
    private static function migrate_option(string $option_name, array $paths, bool $with_categories): void
    {
        $stored = get_option($option_name, null);
        if (!is_array($stored)) {
            return;
        }

        $changed = false;

        foreach ($paths as $path) {
            if (self::decode_path($stored, $path)) {
                $changed = true;
            }
        }

        if ($with_categories && isset($stored['categories']) && is_array($stored['categories'])) {
            foreach ($stored['categories'] as $index => $category) {
                if (!is_array($category)) {
                    continue;
                }
                foreach (['label', 'description'] as $key) {
                    if (self::decode_path($stored, ['categories', (string) $index, $key])) {
                        $changed = true;
                    }
                }
            }
        }

        if ($changed) {
            update_option($option_name, $stored, false);
        }
    }

    /**
     * Decode one nested string in place.
     *
     * @param array<string,mixed> $data Option array (by reference).
     * @param array<int,string>   $path Keys from the root.
     *
     * @return bool True when the stored string changed.
     */
    private static function decode_path(array &$data, array $path): bool
    {
        $ref = &$data;
        $last = array_pop($path);

        foreach ($path as $key) {
            if (!is_array($ref) || !array_key_exists($key, $ref)) {
                return false;
            }
            $ref = &$ref[$key];
        }

        if (!is_array($ref) || !isset($ref[$last]) || !is_string($ref[$last])) {
            return false;
        }

        $decoded = self::decode_text($ref[$last]);
        if ($decoded === $ref[$last]) {
            return false;
        }

        $ref[$last] = $decoded;

        return true;
    }

    /**
     * Undo the entities esc_html__() writes. Leaves &lt; / &gt; alone.
     *
     * @param string $value Stored string.
     *
     * @return string
     */
    public static function decode_text(string $value): string
    {
        return str_replace(
            ['&quot;', '&#039;', '&#39;', '&amp;'],
            ['"', "'", "'", '&'],
            $value
        );
    }
}
