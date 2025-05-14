<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

class Upload_Email_File
{
    public function __construct()
    {
        add_action('wp_ajax_king_addons_upload_file', [$this, 'handle_file_upload']);
        add_action('wp_ajax_nopriv_king_addons_upload_file', [$this, 'handle_file_upload']);
    }

    public function handle_file_upload()
    {
        if (!isset($_POST['king_addons_fb_nonce']) || !wp_verify_nonce($_POST['king_addons_fb_nonce'], 'king-addons-js')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'king-addons'),
            ));
        }

        $max_file_size = isset($_POST['max_file_size']) ? floatval(sanitize_text_field($_POST['max_file_size'])) : 0;
        if ($max_file_size <= 0) {
            $max_file_size = wp_max_upload_size() / pow(1024, 2);
        }

        if (isset($_FILES['uploaded_file'])) {
            $file = $_FILES['uploaded_file'];

            if ($file['size'] > $max_file_size * 1024 * 1024) {
                wp_send_json_error(array(
                    'cause' => 'filesize',
                    'sizes' => [
                        $max_file_size * 1024 * 1024,
                        $file['size']
                    ],
                    'message' => 'File size exceeds the allowed limit.'
                ));
            }

            if (!$this->file_validity($file)) {
                wp_send_json_error(array(
                    'cause' => 'filetype',
                    'message' => esc_html__('File type is not valid.', 'king-addons')
                ));
            }

            if ('click' == $_POST['triggering_event']) {
                $upload_dir = wp_upload_dir();
                $upload_path = $upload_dir['basedir'] . '/king-addons/forms';

                wp_mkdir_p($upload_path);

                $filename = wp_unique_filename($upload_path, $file['name']);

                if (move_uploaded_file($file['tmp_name'], $upload_path . '/' . $filename)) {
                    wp_send_json_success(array(
                        'url' => $upload_dir['baseurl'] . '/king-addons/forms/' . $filename
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => esc_html__('Failed to upload the file.', 'king-addons')
                    ));
                }
            } else {
                wp_send_json_success(array(
                    'message' => esc_html__('File validation passed', 'king-addons')
                ));
            }
        }

        if ('click' == $_POST['triggering_event']) {

            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['basedir'] . '/king-addons/forms';

            wp_mkdir_p($upload_path);

            wp_send_json_error(array(
                'message' => esc_html__('No file was uploaded.', 'king-addons'),
                'files' => $_FILES['uploaded_file']
            ));
        }
    }

    private function file_validity($file)
    {
        $whitelist = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'odt', 'avi', 'ogg', 'm4a', 'mov', 'mp3', 'mp4', 'mpg', 'wav', 'wmv', 'txt'];

        if (empty($_POST['allowed_file_types'])) {
            $allowed_file_types = 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,odt,avi,ogg,m4a,mov,mp3,mp4,mpg,wav,wmv,txt';
        } else {
            $allowed_file_types = $_POST['allowed_file_types'];
        }

        if (!wp_check_filetype($file['name'])['ext']) {
            return false;
        }

        $f_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $f_extension = strtolower($f_extension);

        $allowed_file_types = explode(',', $allowed_file_types);
        $allowed_file_types = array_map('trim', $allowed_file_types);
        $allowed_file_types = array_map('strtolower', $allowed_file_types);

        return (in_array($f_extension, $allowed_file_types) && in_array($f_extension, $whitelist) && !in_array($f_extension, $this->get_exclusion_list()));
    }

    private function get_exclusion_list()
    {
        static $exclusionlist = false;
        if (!$exclusionlist) {
            $exclusionlist = [
                'php',
                'php3',
                'php4',
                'php5',
                'php6',
                'phps',
                'php7',
                'phtml',
                'shtml',
                'pht',
                'swf',
                'html',
                'asp',
                'aspx',
                'cmd',
                'csh',
                'bat',
                'htm',
                'hta',
                'jar',
                'exe',
                'com',
                'js',
                'lnk',
                'htaccess',
                'htpasswd',
                'phtml',
                'ps1',
                'ps2',
                'py',
                'rb',
                'tmp',
                'cgi',
                'svg',
                'svgz'
            ];
        }

        return $exclusionlist;
    }
}

new Upload_Email_File();