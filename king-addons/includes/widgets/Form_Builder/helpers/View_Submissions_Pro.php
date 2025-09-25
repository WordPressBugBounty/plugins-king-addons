<?php

namespace King_Addons;

use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

class View_Submissions_Pro
{
    public function __construct()
    {


        add_action('admin_enqueue_scripts', [$this, 'enqueue_submissions_script']);
        add_action('init', [$this, 'register_post_type_king_addons_submissions']);
        add_action('in_admin_header', [$this, 'renderAdminCustomHeader']);

        add_filter('manage_king-addons-fb-sub_posts_columns', [$this, 'king_addons_submissions_custom_columns']);
        add_action('manage_king-addons-fb-sub_posts_custom_column', [$this, 'king_addons_submissions_custom_column_content'], 10, 2);
        add_filter('manage_edit-king-addons-fb-sub_sortable_columns', [$this, 'king_addons_submissions_sortable_columns']);

        add_action('wp_ajax_king_addons_submissions_update_read_status', [$this, 'king_addons_submissions_update_read_status']);
        add_action('current_screen', [$this, 'king_addons_submissions_mark_as_read']);
        add_filter('post_row_actions', [$this, 'king_addons_submissions_row_actions'], 10, 2);
        add_action('current_screen', [$this, 'king_addons_submissions_remove_bulk_edit_filter']);

        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));


    }

    public static function enqueueScripts(): void
    {
        $screen = get_current_screen();
        if ($screen->id === 'edit-king-addons-fb-sub') {
            wp_enqueue_style('king-addons-fb-sub-style', KING_ADDONS_URL . 'includes/widgets/Form_Builder/admin.css', '', KING_ADDONS_VERSION);
        }
    }

    public function renderAdminCustomHeader()
    {
        $current_screen = get_current_screen()->id;
        if ($current_screen !== 'edit-king-addons-fb-sub') {
            return;
        }

        ?>
        <div class="king-addons-pb-settings-page-header">
            <h1><?php esc_html_e('Form Builder Submissions', 'king-addons'); ?></h1>
            <p>
                <?php esc_html_e('Submitted data from the Form Builder element will be displayed on this page', 'king-addons'); ?>
            </p>
            <?php if (!king_addons_freemius()->can_use_premium_code__premium_only()): ?>
                <div class="king-addons-pb-preview-buttons">
                    <div class="kng-promo-btn-wrap">
                        <a href="https://kingaddons.com/pricing/?rel=king-addons-fb-submissions-page" target="_blank">
                            <div class="kng-promo-btn-txt">
                                <?php esc_html_e('Unlock Premium Features & 650+ Templates Today!', 'king-addons'); ?>
                            </div>
                            <img width="16px"
                                 src="<?php echo esc_url(KING_ADDONS_URL) . 'includes/admin/img/share-v2.svg'; ?>"
                                 alt="<?php echo esc_html__('Open link in the new tab', 'king-addons'); ?>">
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function enqueue_submissions_script($hook)
    {

        $post_type = 'king-addons-fb-sub';


        if ($hook == 'post.php' || $hook == 'post-new.php' || $hook == 'edit.php') {
            global $post;


            if (isset($post) && $post->post_type === $post_type) {
                
                // Check if Pro constants are defined before using them
                if (defined('KING_ADDONS_PRO_URL') && defined('KING_ADDONS_PRO_VERSION')) {
                    wp_enqueue_style('king-addons-form-builder-submissions-css', KING_ADDONS_PRO_URL . 'assets/css/king-addons-submissions.css', [], KING_ADDONS_VERSION);
                    wp_enqueue_script('king-addons-form-builder-submissions-js', KING_ADDONS_PRO_URL . 'assets/js/king-addons-submissions.js', ['jquery'], KING_ADDONS_VERSION);
                } else {
                    wp_enqueue_style('king-addons-form-builder-submissions-css', KING_ADDONS_URL . 'includes/widgets/Form_Builder/assets/css/king-addons-submissions.css', [], KING_ADDONS_VERSION);
                    wp_enqueue_script('king-addons-form-builder-submissions-js', KING_ADDONS_URL . 'includes/widgets/Form_Builder/assets/js/king-addons-submissions.js', ['jquery'], KING_ADDONS_VERSION);
                }

                wp_localize_script(
                    'king-addons-form-builder-submissions-js',
                    'KingAddonsSubmissions',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'resturl' => get_rest_url() . 'king-addons/v1',
                        'nonce' => wp_create_nonce('king-addons-submissions-js'),
                        'form_name' => get_post_meta($post->ID, 'king_addons_form_name', true),
                        'form_id' => get_post_meta($post->ID, 'king_addons_form_id', true),
                        'form_page' => get_post_meta($post->ID, 'king_addons_form_page', true),
                        'form_page_id' => get_post_meta($post->ID, 'king_addons_form_page_id', true),
                        'form_page_url' => get_permalink(get_post_meta($post->ID, 'king_addons_form_page_id', true)),
                        'form_page_editor' => admin_url('post.php?post=' . get_post_meta($post->ID, 'king_addons_form_page_id', true) . '&action=elementor'),
                        'form_agent' => get_post_meta($post->ID, 'king_addons_user_agent', true),
                        'agent_ip' => get_post_meta($post->ID, 'king_addons_user_ip', true),
                        'post_created' => date('F j, Y g:i a', strtotime($post->post_date)),
                        'post_updated' => date('F j, Y g:i a', strtotime($post->post_modified)),
                    ]
                );
            }
        }
    }


    public function king_addons_submissions_meta_box()
    {
        $args = new WP_Query([
            'post_type' => 'king-addons-fb-sub'
        ]);
        foreach ($args as $arg) {

            add_meta_box(
                'king_addons_submission_fields',
                'King Addons Submissions',
                [$this, 'king_addons_meta_box_callback'],
                'king-addons-fb-sub',
                'normal',
                'default'
            );
        }
    }


    public function king_addons_meta_box_callback($post, $metabox)
    {
        echo '<button class="king-addons-edit-submissions button button-primary">' . esc_html__('Edit', 'king-addons') . '</button>';
        foreach (get_post_meta($post->ID) as $key => $value) {

            $exclude = ['king_addons_form_id', 'king_addons_form_name', 'king_addons_form_page', 'king_addons_form_page_id', 'king_addons_user_agent', 'king_addons_user_ip', 'king_addons_submission_read_status', '_edit_lock', '_edit_last'];

            if (in_array($key, $exclude)) {
                continue;
            }

            echo '<div class="king-addons-submissions-wrap">';
            if (is_serialized($value[0])) {


                if ($value[0]) {
                    // Security fix: Use safe unserialize with validation
                    $unserialized = @unserialize($value[0]);
                    if ($unserialized !== false && is_array($unserialized)) {
                        $value = $unserialized;
                    } else {
                        $value = $value[0]; // Fallback to original string if unserialize fails
                    }
                }

                $prefix = "form_field-";
                $key_title = !empty($value[2]) ? $value[2] : ucfirst(str_replace($prefix, "", $key));

                if (str_contains($key, '_action_')) {
                    $prefix = '_action_king_addons_form_builder_';
                    $label = ucfirst(substr($key, strpos($key, $prefix) + strlen($prefix)));
                    echo '<label>' . $label . '</label>';
                    echo '<p class="notice notice-' . $value['status'] . '">' . ucfirst($value['message']) . '</p>';
                } elseif ('file' == $value[0]) {
                    echo '<label for="' . $key . '">' . $key_title . ' </label>';
                    if (is_array($value[1])) {
                        foreach ($value[1] as $index => $file) {
                            echo '<a  id="' . $key . '_' . $index . '" target="_blank" href="' . $file . '">' . $file . '</a>';
                        }
                    }
                } elseif ('textarea' == $value[0]) {
                    echo '<label for="' . $key . '">' . $key_title . ' </label>';
                    echo '<textarea   id="' . $key . '">' . $value[1] . '</textarea>';
                } else {
                    if ($value[0] === 'radio' || $value[0] === 'checkbox') {
                        echo '<label for="' . $key . '" class="' . $key . '">' . $key_title . ' </label>';
                        foreach ($value[1] as $index => $item) {
                            $checked = $item[1] == 'true' ? 'checked' : '';
                            echo '<input class="king-addons-inline"  type="' . $value[0] . '" name="' . $item[2] . '" id="' . $item[3] . '" value="' . $item[0] . '" ' . $checked . '>';
                            echo '<label class="king-addons-inline" for="' . $item[2] . '">' . $item[0] . ' </label>';
                        }
                    } else {
                        if ($value[0] == 'select') {
                            if (is_array($value[1])) {
                                $value[1] = implode(",", $value[1]);
                            }

                            echo '<label for="' . $key . '">' . $key_title . ' </label>';
                            echo '<input  type="text" id="' . $key . '" value="' . $value[1] . '">';

                        } else {
                            echo '<label for="' . $key . '">' . $key_title . ' </label>';
                            // Use 'text' if type is empty, and sanitize attributes
                            $type_attr = ! empty( $value[0] ) ? $value[0] : 'text';
                            echo '<input type="' . esc_attr( $type_attr ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value[1] ) . '">';
                        }
                    }
                }
            } else {
                $prefix = "form_field-";
                $key_title = !empty($value[2]) ? $value[2] : ucfirst(str_replace($prefix, "", $key));

                echo '<label for="' . $key . '">' . $key_title . ' </label>';
                echo '<input  type="text" id="' . $key . '" value="' . $value[0] . '">';
            }
            echo '</div>';
        }


    }

    public function register_post_type_king_addons_submissions()
    {
        $labels = [
            'name' => esc_html__('Form Submissions', 'king-addons'),
            'singular_name' => esc_html__('Submission', 'king-addons'),
            'edit_item' => esc_html__('Edit Submission', 'king-addons'),
            'view_item' => esc_html__('View Submission', 'king-addons'),
            'all_items' => esc_html__('Form Submissions', 'king-addons'),
            'search_items' => esc_html__('Search Submissions', 'king-addons'),
            'parent_item_colon' => esc_html__('Parent Submissions:', 'king-addons'),
            'not_found' => esc_html__('No Submissions found.', 'king-addons'),
            'not_found_in_trash' => esc_html__('No Submissions found in Trash.', 'king-addons'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
//            'show_in_menu' => 'king-addons',
            'show_in_menu' => false,
            'rewrite' => ['slug' => 'king-addons-fb-sub'],
            'supports' => [],
            'register_meta_box_cb' => [$this, 'king_addons_submissions_meta_box']
        ];
        register_post_type('king-addons-fb-sub', $args);
        // Remove Title and Editor fields for submissions
        remove_post_type_support('king-addons-fb-sub', 'title');
        remove_post_type_support('king-addons-fb-sub', 'editor');
    }

    public function king_addons_submissions_custom_columns($columns)
    {

        unset($columns['title']);
        unset($columns['author']);
        unset($columns['categories']);
        unset($columns['date']);


        $columns['main'] = __('Main', 'king-addons');
        $columns['action_status'] = __('Action Status', 'king-addons');
        $columns['form_id'] = __('Form', 'king-addons');
        $columns['page'] = __('Page', 'king-addons');
        $columns['post_id'] = __('ID', 'king-addons');
        $columns['read_status'] = __('Read Status');
        $columns['date'] = __('Date', 'king-addons');

        return $columns;
    }

    public function king_addons_submissions_sortable_columns($columns)
    {
        $columns['read_status'] = 'read_status';
        return $columns;
    }

    public function king_addons_submissions_custom_column_content($column, $post_id)
    {
        $submission = get_post($post_id);
        $submission_meta = get_post_meta($post_id);
        $action_status = 'success';

        foreach ($submission_meta as $key => $value) {
            if (str_contains($key, 'form_field-email')) {
                $main_key = $key;
            }

            if (str_contains($key, '_action_')) {
                if (str_contains($value[0], 'error')) {
                    $action_status = 'error';
                }
            }
        }

        switch ($column) {
            case 'main':

                echo sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')),
                    __('View', 'king-addons'),
                    __(get_post_meta($post_id, $main_key, true)[1], 'king-addons')
                );
                break;

            case 'action_status':

                echo $action_status;
                break;

            case 'form_id':
                echo '<a href="' . admin_url('post.php?post=' . get_post_meta($post_id, 'king_addons_form_page_id', true) . '&action=elementor') . '" target="_blank">';
                echo get_post_meta($post_id, 'king_addons_form_name', true);
                echo '</a>';
                break;

            case 'page':
                echo '<a href="' . get_permalink(get_post_meta($post_id, 'king_addons_form_page_id', true)) . '" target="_blank">';
                echo get_post_meta($post_id, 'king_addons_form_page', true);
                echo '</a>';
                break;

            case 'post_id':

                echo $submission->ID;
                break;

            case 'read_status':
                $read_status = get_post_meta($post_id, 'king_addons_submission_read_status', true);

                if ($read_status == '1') {
                    echo '<span class="king-addons-button king-addons-submission-read">' . __('Read') . '</span>';
                } else {
                    echo '<span class="king-addons-button king-addons-submission-unread">' . __('Unread') . '</span>';
                }
                break;

            case 'custom_date':

                echo get_post_meta($post_id, 'custom_date_key', true);
                break;
        }
    }

    public function king_addons_submissions_update_read_status()
    {
        if (!isset($_POST['post_id']) || !isset($_POST['read_status']) || !wp_verify_nonce($_POST['nonce'], 'king-addons-submissions-js')) {
            wp_send_json_error('Invalid request');
        }

        $post_id = intval($_POST['post_id']);
        $read_status = $_POST['read_status'] === '1' ? '1' : '0';

        update_post_meta($post_id, 'king_addons_submission_read_status', $read_status);

        wp_send_json_success();
    }

    public function king_addons_submissions_mark_as_read($screen)
    {
        if (is_admin()) {
            $screen = get_current_screen();


            if ($screen && $screen->base == 'post' && $screen->post_type == 'king-addons-fb-sub') {
                if (isset($_GET['post']) && !empty($_GET['post'])) {
                    $post_id = intval($_GET['post']);
                    $post = get_post($post_id);


                    update_post_meta($post_id, 'king_addons_submission_read_status', '1');
                }
            }
        }
    }

    public function king_addons_submissions_row_actions($actions, $post)
    {

        if ($post->post_type === 'king-addons-fb-sub') {

            unset($actions['edit']);
            unset($actions['inline hide-if-no-js']);


            $actions['view'] = sprintf(
                '<a href="%s" title="%s">%s</a>',
                esc_url(admin_url('post.php?post=' . $post->ID . '&action=edit')),
                __('View', 'king-addons'),
                __('View', 'king-addons')
            );
        }

        return $actions;
    }

    public function king_addons_submissions_remove_bulk_edit($actions)
    {

        unset($actions['edit']);
        return $actions;
    }

    public function king_addons_submissions_remove_bulk_edit_filter()
    {
        $screen = get_current_screen();


        if ($screen->id === 'edit-king-addons-fb-sub') {

            add_filter('bulk_actions-' . $screen->id, [$this, 'king_addons_submissions_remove_bulk_edit']);
        }
    }

}

new View_Submissions_Pro();