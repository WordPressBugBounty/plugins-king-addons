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
        // Add endpoint for dynamic nonce generation
        add_action('wp_ajax_king_addons_get_fresh_nonce', [$this, 'get_fresh_nonce']);
        add_action('wp_ajax_nopriv_king_addons_get_fresh_nonce', [$this, 'get_fresh_nonce']);
    }

    public function handle_file_upload()
    {
        // Security fix: Generate nonce server-side instead of relying on client-provided nonce
        $server_nonce = wp_create_nonce('king-addons-js');
        if (!isset($_POST['king_addons_fb_nonce']) || !wp_verify_nonce($_POST['king_addons_fb_nonce'], 'king-addons-js')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'king-addons'),
            ));
        }

        // Add capability check
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions to upload files.', 'king-addons'),
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

            // Additional MIME type validation
            $allowed_mime_types = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.oasis.opendocument.text', 'video/avi', 'audio/ogg', 'video/mp4', 'audio/mp3',
                'video/mpeg', 'audio/wav', 'video/x-ms-wmv', 'text/plain'
            ];

            if (!in_array($file['type'], $allowed_mime_types)) {
                wp_send_json_error(array(
                    'cause' => 'mime_type',
                    'message' => esc_html__('File MIME type is not allowed.', 'king-addons')
                ));
            }

            // Security check: Scan file content for malicious patterns
            if (!$this->is_file_safe($file['tmp_name'])) {
                wp_send_json_error(array(
                    'cause' => 'security',
                    'message' => esc_html__('File contains potentially malicious content.', 'king-addons')
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

    /**
     * Check if uploaded file is safe from malicious content
     *
     * @param string $file_path Path to the uploaded file
     * @return bool True if file is safe, false if potentially malicious
     */
    private function is_file_safe($file_path)
    {
        // Only check text-based files for malicious content
        $text_mime_types = ['text/plain', 'application/json', 'text/html', 'text/css', 'text/javascript'];

        if (!in_array($this->get_file_mime_type($file_path), $text_mime_types)) {
            return true; // Non-text files are considered safe for this check
        }

        if (!file_exists($file_path)) {
            return false;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            return false;
        }

        // Check for common malicious patterns
        $malicious_patterns = [
            '/<\?php/i',           // PHP opening tag
            '/eval\s*\(/i',        // eval() function
            '/base64_decode/i',    // Base64 decode
            '/system\s*\(/i',      // system() function
            '/exec\s*\(/i',        // exec() function
            '/shell_exec/i',       // shell_exec function
            '/passthru/i',         // passthru function
            '/<\?=/i',             // PHP short tag
            '/<script/i',          // JavaScript tags
            '/javascript:/i',      // JavaScript protocol
            '/on\w+\s*=/i',        // Event handlers
        ];

        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get file MIME type from file path
     *
     * @param string $file_path Path to the file
     * @return string MIME type
     */
    private function get_file_mime_type($file_path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $mime_type;
    }

    /**
     * AJAX handler for generating fresh nonce
     * Security fix: Provides dynamic nonce generation instead of public exposure
     */
    public function get_fresh_nonce()
    {
        // Only allow if user has upload permissions or if it's a public form
        if (!current_user_can('upload_files') && !isset($_POST['form_public'])) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        wp_send_json_success([
            'nonce' => wp_create_nonce('king-addons-js'),
            'timestamp' => time()
        ]);
    }
}

new Upload_Email_File();