<?php
/**
 * 
 *
 * @package King_Addons
 */

namespace King_Addons\Dynamic_Tags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The excerpt of the post being displayed.
 */
class Post_Excerpt extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-post-excerpt';
    }

    public function get_title()
    {
        return esc_html__('Post Excerpt', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'word_limit',
            [
                'label' => esc_html__('Word limit', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'description' => esc_html__('0 keeps the full excerpt.', 'king-addons'),
            ]
        );
    }

    public function render()
    {
        $post = $this->ka_get_post();
        if (!$post) {
            $this->ka_echo('');
            return;
        }

        // get_the_excerpt() falls back to a trimmed version of the content when
        // no manual excerpt is set, which is what an author expects here.
        $excerpt = (string) get_the_excerpt($post);

        $limit = (int) $this->get_settings('word_limit');
        if ($limit > 0 && '' !== $excerpt) {
            $excerpt = wp_trim_words($excerpt, $limit, '…');
        }

        $this->ka_echo($excerpt);
    }
}
