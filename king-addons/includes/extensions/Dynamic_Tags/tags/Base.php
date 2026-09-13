<?php
/**
 * Shared pieces for King Addons dynamic tags.
 *
 * @package King_Addons
 */

namespace King_Addons\Dynamic_Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use King_Addons\Dynamic_Tags;

if (!defined('ABSPATH')) {
    exit;
}

if (!trait_exists('King_Addons\\Dynamic_Tags\\Tag_Basics')) {
    /**
     * Group and post resolution shared by every tag.
     */
    trait Tag_Basics
    {
        /**
         * Group shown in the editor's dynamic tag list.
         *
         * @return string
         */
        public function get_group()
        {
            return Dynamic_Tags::GROUP;
        }

        /**
         * The post the tag should read from.
         *
         * Falls back to the queried object so the tag still resolves inside a
         * Theme Builder or Woo Builder template, where the global post is the
         * template itself rather than the content being displayed.
         *
         * @return \WP_Post|null
         */
        protected function ka_get_post(): ?\WP_Post
        {
            $post = get_post();
            if ($post instanceof \WP_Post && 'elementor_library' !== $post->post_type) {
                return $post;
            }

            $queried = get_queried_object();
            if ($queried instanceof \WP_Post) {
                return $queried;
            }

            return $post instanceof \WP_Post ? $post : null;
        }

        /**
         * The post id the tag should read from, or 0.
         *
         * @return int
         */
        protected function ka_get_post_id(): int
        {
            $post = $this->ka_get_post();

            return $post ? (int) $post->ID : 0;
        }

        /**
         * Title with a Pro suffix when the site cannot use premium code.
         *
         * @param string $title Tag title.
         *
         * @return string
         */
        protected function ka_title_with_pro(string $title): string
        {
            if (function_exists('king_addons_can_use_pro') && king_addons_can_use_pro()) {
                return $title;
            }

            return sprintf(
                /* translators: %s: dynamic tag name. */
                esc_html__('%s (Pro)', 'king-addons'),
                $title
            );
        }

        /**
         * Editor notice shown on Pro-only tags while the site is on the free plan.
         *
         * @return void
         */
        protected function ka_register_pro_notice(): void
        {
            if (function_exists('king_addons_can_use_pro') && king_addons_can_use_pro()) {
                return;
            }

            $this->add_control(
                'ka_pro_only_notice',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        '<div class="elementor-panel-alert elementor-panel-alert-info">%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></div>',
                        esc_html__('Available in King Addons Pro.', 'king-addons'),
                        esc_url('https://kingaddons.com/pricing/?utm_source=kng-dynamic-tag-upgrade-pro&utm_medium=plugin&utm_campaign=kng'),
                        esc_html__('Upgrade', 'king-addons')
                    ),
                ]
            );
        }

        /**
         * Whether this Pro tag should stay silent on the front end.
         *
         * @return bool
         */
        protected function ka_pro_locked(): bool
        {
            return !function_exists('king_addons_can_use_pro') || !king_addons_can_use_pro();
        }

        /**
         * Print a resolved value.
         *
         * Nothing is printed when the value is empty: Elementor's
         * Tag::get_content() then substitutes the Fallback the user set in the
         * tag's Advanced section, so the fallback is handled in one place.
         *
         * @param string $value Resolved value.
         *
         * @return void
         */
        protected function ka_echo(string $value): void
        {
            if ('' === trim($value)) {
                return;
            }

            echo wp_kses_post($value);
        }
    }
}

if (!class_exists('King_Addons\\Dynamic_Tags\\Text_Tag')) {
    /**
     * Base for tags that output text.
     */
    abstract class Text_Tag extends Tag
    {
        use Tag_Basics;

        /**
         * Categories the tag can be used in.
         *
         * @return array<int,string>
         */
        public function get_categories()
        {
            return [Module::TEXT_CATEGORY];
        }
    }
}

if (!class_exists('King_Addons\\Dynamic_Tags\\Url_Tag')) {
    /**
     * Base for tags that output a URL.
     */
    abstract class Url_Tag extends Data_Tag
    {
        use Tag_Basics;

        /**
         * Categories the tag can be used in.
         *
         * @return array<int,string>
         */
        public function get_categories()
        {
            return [Module::URL_CATEGORY];
        }
    }
}

if (!class_exists('King_Addons\\Dynamic_Tags\\Image_Tag')) {
    /**
     * Base for tags that output an image.
     */
    abstract class Image_Tag extends Data_Tag
    {
        use Tag_Basics;

        /**
         * Categories the tag can be used in.
         *
         * @return array<int,string>
         */
        public function get_categories()
        {
            return [Module::IMAGE_CATEGORY];
        }

        /**
         * Elementor expects an image control value: id plus url.
         *
         * @param int    $attachment_id Attachment id, if any.
         * @param string $url           Direct URL, if any.
         *
         * @return array{id:int,url:string}
         */
        protected function ka_image_value(int $attachment_id, string $url = ''): array
        {
            if ($attachment_id > 0) {
                $resolved = wp_get_attachment_image_url($attachment_id, 'full');
                if ($resolved) {
                    return ['id' => $attachment_id, 'url' => $resolved];
                }
            }

            return ['id' => 0, 'url' => $url];
        }
    }
}
