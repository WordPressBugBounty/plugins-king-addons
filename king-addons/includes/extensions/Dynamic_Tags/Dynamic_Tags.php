<?php
/**
 * Dynamic Tags for Elementor.
 *
 * Registers the King Addons dynamic tag group and its tags, so any Elementor
 * control that accepts dynamic content can pull post, author, site, custom
 * field, ACF and WooCommerce data. Without these the Theme Builder and Woo
 * Builder widgets can only show their own native data.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers dynamic tags with Elementor.
 */
class Dynamic_Tags
{
    /**
     * Group the tags are listed under in the editor.
     */
    public const GROUP = 'king-addons';

    /**
     * Tags that need a Pro licence. They stay in the picker so a free user
     * can see what they would get; render() returns empty and Elementor's
     * Fallback fills the gap.
     *
     * @var array<int,string>
     */
    private const PRO_TAGS = [
        'Custom_Field',
        'Request_Parameter',
        'Shortcode_Tag',
        'ACF_Field',
        'ACF_Image',
        'Product_Field',
    ];

    /**
     * Tags that are always registered: class short name.
     *
     * @var array<int,string>
     */
    private const CORE_TAGS = [
        'Post_Title',
        'Post_Excerpt',
        'Post_Date',
        'Post_Terms',
        'Archive_Title',
        'Author_Name',
        'Site_Title',
        'Site_Tagline',
        'Custom_Field',
        'Request_Parameter',
        'Shortcode_Tag',
        'Current_Date_Time',
        'Post_URL',
        'Site_URL',
        'Author_URL',
        'Featured_Image',
        'Author_Avatar',
        'Site_Logo',
    ];

    /**
     * Tags that need Advanced Custom Fields.
     *
     * @var array<int,string>
     */
    private const ACF_TAGS = [
        'ACF_Field',
        'ACF_Image',
    ];

    /**
     * Tags that need WooCommerce.
     *
     * @var array<int,string>
     */
    private const WOO_TAGS = [
        'Product_Field',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('elementor/dynamic_tags/register', [$this, 'register']);
    }

    /**
     * Whether Advanced Custom Fields is available.
     *
     * @return bool
     */
    public static function has_acf(): bool
    {
        return function_exists('get_field') && function_exists('acf_get_field_groups');
    }

    /**
     * Whether WooCommerce is available.
     *
     * @return bool
     */
    public static function has_woo(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    /**
     * ACF fields of the given types, as Elementor control options.
     *
     * Keyed by field name, because that is what get_field() takes and what stays
     * valid if the field is later moved to another group.
     *
     * @param array<int,string> $types Field types to include.
     *
     * @return array<string,string>
     */
    public static function acf_field_options(array $types): array
    {
        if (!self::has_acf() || !function_exists('acf_get_fields')) {
            return [];
        }

        $options = [];

        foreach (acf_get_field_groups() as $group) {
            if (empty($group['key'])) {
                continue;
            }

            $fields = acf_get_fields($group['key']);
            if (!is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (empty($field['name']) || empty($field['type'])) {
                    continue;
                }

                if (!in_array($field['type'], $types, true)) {
                    continue;
                }

                $label = !empty($field['label']) ? $field['label'] : $field['name'];
                $group_title = !empty($group['title']) ? $group['title'] : '';

                $options[$field['name']] = '' !== $group_title
                    ? $group_title . ' - ' . $label
                    : $label;
            }
        }

        return $options;
    }

    /**
     * The object id ACF should read from on the current request.
     *
     * ACF addresses terms and users with prefixed ids rather than plain ones.
     *
     * @param int $post_id Post id resolved by the tag.
     *
     * @return int|string
     */
    public static function acf_object_id(int $post_id)
    {
        $queried = get_queried_object();

        if ($queried instanceof \WP_Term) {
            return 'term_' . $queried->term_id;
        }

        if ($queried instanceof \WP_User) {
            return 'user_' . $queried->ID;
        }

        return $post_id;
    }

    /**
     * The value of an ACF field, with choice keys turned into their labels.
     *
     * Select, radio, checkbox and button group store the key ("b") and keep the
     * label ("Второй") in the field definition, so get_field() alone shows the
     * key. get_field_object() carries both.
     *
     * @param string     $key    Field name or key.
     * @param int|string $object ACF object id.
     *
     * @return mixed
     */
    public static function acf_value(string $key, $object)
    {
        if (!function_exists('get_field_object')) {
            return function_exists('get_field') ? get_field($key, $object) : null;
        }

        $field = get_field_object($key, $object);

        if (!is_array($field) || !array_key_exists('value', $field)) {
            return function_exists('get_field') ? get_field($key, $object) : null;
        }

        $value = $field['value'];
        $choices = isset($field['choices']) && is_array($field['choices']) ? $field['choices'] : [];

        if (empty($choices)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(static function ($item) use ($choices) {
                $item = is_scalar($item) ? (string) $item : '';

                return $choices[$item] ?? $item;
            }, $value);
        }

        if (is_scalar($value)) {
            $key_string = (string) $value;

            return $choices[$key_string] ?? $value;
        }

        return $value;
    }

    /**
     * Flatten whatever ACF returned into something printable.
     *
     * @param mixed  $value Field value.
     * @param string $glue  Separator for multi-value fields.
     *
     * @return string
     */
    public static function acf_stringify($value, string $glue = ', '): string
    {
        if ('' === $glue) {
            $glue = ', ';
        }

        if (is_bool($value)) {
            return $value ? esc_html__('Yes', 'king-addons') : '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof \WP_Post) {
            return get_the_title($value);
        }

        if ($value instanceof \WP_Term) {
            return $value->name;
        }

        if (is_array($value)) {
            // A link field, an image array, or a select with labels.
            if (isset($value['url']) && is_scalar($value['url'])) {
                return (string) (isset($value['title']) && '' !== $value['title'] ? $value['title'] : $value['url']);
            }

            if (isset($value['label']) && is_scalar($value['label'])) {
                return (string) $value['label'];
            }

            $parts = [];
            foreach ($value as $item) {
                $part = self::acf_stringify($item, $glue);
                if ('' !== $part) {
                    $parts[] = $part;
                }
            }

            return implode($glue, $parts);
        }

        return '';
    }

    /**
     * Whether a tag class is Pro-only.
     *
     * @param string $class_short_name Tag class short name.
     *
     * @return bool
     */
    public static function is_pro_tag(string $class_short_name): bool
    {
        return in_array($class_short_name, self::PRO_TAGS, true);
    }

    /**
     * Register the group and every available tag.
     *
     * @param \Elementor\Core\DynamicTags\Manager $manager Elementor's tag manager.
     *
     * @return void
     */
    public function register($manager): void
    {
        if (!is_object($manager) || !method_exists($manager, 'register')) {
            return;
        }

        $manager->register_group(
            self::GROUP,
            [
                'title' => esc_html__('King Addons', 'king-addons'),
            ]
        );

        // All tags stay registered. Pro ones render empty without a licence
        // so Elementor's Fallback still works on a site that loses Pro.
        require_once KING_ADDONS_PATH . 'includes/extensions/Dynamic_Tags/tags/Base.php';

        $tags = self::CORE_TAGS;

        // ACF and WooCommerce tags would only ever return nothing without their
        // plugin, so they are not offered at all in that case.
        if (self::has_acf()) {
            $tags = array_merge($tags, self::ACF_TAGS);
        }

        if (self::has_woo()) {
            $tags = array_merge($tags, self::WOO_TAGS);
        }

        foreach ($tags as $tag) {
            $file = KING_ADDONS_PATH . 'includes/extensions/Dynamic_Tags/tags/' . $tag . '.php';
            if (!file_exists($file)) {
                continue;
            }

            require_once $file;

            $class = 'King_Addons\\Dynamic_Tags\\' . $tag;
            if (!class_exists($class)) {
                continue;
            }

            // Pro tags are still registered so they appear in the picker.
            $manager->register(new $class());
        }
    }
}
