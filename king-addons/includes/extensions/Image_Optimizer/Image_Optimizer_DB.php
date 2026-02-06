<?php
/**
 * Image Optimizer database/metadata operations.
 *
 * @package King_Addons
 */

namespace King_Addons\Image_Optimizer;

if (!defined('ABSPATH')) {
    exit;
}

class Image_Optimizer_DB
{
    private const META_KEY = '_king_img_optimized';

    /**
     * Sync the WordPress attachment to point to the optimized files.
     * This updates `_wp_attached_file` and attachment metadata, while keeping originals via backup meta keys.
     */
    public static function sync_attachment_to_optimized(int $attachment_id, array $opt_meta): void
    {
        self::update_attachment_metadata_to_webp($attachment_id, $opt_meta);
    }

    /**
     * Get optimization metadata for an attachment.
     */
    public static function get_optimization_meta(int $attachment_id): ?array
    {
        $meta = get_post_meta($attachment_id, self::META_KEY, true);
        return is_array($meta) ? $meta : null;
    }

    /**
     * Set optimization metadata for an attachment.
     */
    public static function set_optimization_meta(int $attachment_id, array $meta): bool
    {
        return (bool) update_post_meta($attachment_id, self::META_KEY, $meta);
    }

    /**
     * Delete optimization metadata for an attachment.
     */
    public static function delete_optimization_meta(int $attachment_id): bool
    {
        return delete_post_meta($attachment_id, self::META_KEY);
    }

    /**
     * Apply WebP URLs in database - replace original URLs with optimized versions.
     */
    public static function apply_webp_urls(int $attachment_id, array $meta): int
    {
        global $wpdb;

        if (empty($meta['sizes'])) {
            return 0;
        }

        $replacements = [];
        $upload_dir = wp_upload_dir();

        foreach ($meta['sizes'] as $size => $data) {
            $original_path = $data['original_path'] ?? '';
            $optimized_path = $data['optimized_path'] ?? $data['webp_path'] ?? '';
            
            if (empty($original_path) || empty($optimized_path)) {
                continue;
            }
            
            // Skip if paths are the same (shouldn't happen but safety check)
            if ($original_path === $optimized_path) {
                continue;
            }

            // Build URLs from paths
            $original_url = str_replace(
                $upload_dir['basedir'],
                $upload_dir['baseurl'],
                $original_path
            );
            $optimized_url = str_replace(
                $upload_dir['basedir'],
                $upload_dir['baseurl'],
                $optimized_path
            );

            $replacements[$original_url] = $optimized_url;
        }

        if (empty($replacements)) {
            return 0;
        }

        $updated = 0;

        // Update wp_posts.post_content
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

        // Update wp_postmeta
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_value LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

        // Update wp_options (widgets, theme mods, etc.)
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, %s, %s) WHERE option_value LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

        // Update wp_termmeta
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->termmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_value LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

        // Handle Elementor data (JSON in postmeta)
        $updated += self::replace_in_elementor_data($replacements);

        // Update attachment metadata
        self::update_attachment_metadata_to_webp($attachment_id, $meta);

        // Mark URLs as replaced in meta
        $meta['urls_replaced'] = true;
        $meta['urls_replaced_at'] = current_time('mysql');
        $meta['url_replacements'] = $replacements;
        self::set_optimization_meta($attachment_id, $meta);

        return $updated;
    }

    /**
     * Replace URLs in Elementor data.
     */
    private static function replace_in_elementor_data(array $replacements): int
    {
        global $wpdb;

        $updated = 0;

        // Get all Elementor data
        $results = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data'"
        );

        foreach ($results as $row) {
            $data = $row->meta_value;
            $modified = false;

            foreach ($replacements as $old_url => $new_url) {
                if (strpos($data, $old_url) !== false) {
                    $data = str_replace($old_url, $new_url, $data);
                    $modified = true;
                }
            }

            if ($modified) {
                $result = $wpdb->update(
                    $wpdb->postmeta,
                    ['meta_value' => $data],
                    [
                        'post_id' => $row->post_id,
                        'meta_key' => '_elementor_data',
                    ]
                );
                if ($result !== false) {
                    $updated += $result;
                }
            }
        }

        return $updated;
    }

    /**
     * Update attachment metadata to use optimized version.
     */
    private static function update_attachment_metadata_to_webp(int $attachment_id, array $opt_meta): void
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (!is_array($metadata)) {
            return;
        }

        // Store original metadata for potential rollback (if not already stored)
        if (!get_post_meta($attachment_id, '_king_img_original_metadata', true)) {
            update_post_meta($attachment_id, '_king_img_original_metadata', $metadata);
        }

        $format = $opt_meta['format'] ?? 'webp';

        $upload_dir = wp_upload_dir();

        // Backup original attached file (relative) for rollback
        if (!get_post_meta($attachment_id, '_king_img_original_attached_file', true)) {
            $original_attached = get_post_meta($attachment_id, '_wp_attached_file', true);
            if (!empty($original_attached)) {
                update_post_meta($attachment_id, '_king_img_original_attached_file', $original_attached);
            }
        }

        // Update main file: set metadata['file'] and _wp_attached_file to the optimized version
        $full_optimized_path = $opt_meta['sizes']['full']['optimized_path'] ?? $opt_meta['sizes']['full']['webp_path'] ?? '';
        if (!empty($full_optimized_path) && is_string($full_optimized_path) && strpos($full_optimized_path, $upload_dir['basedir']) === 0) {
            $relative = ltrim(str_replace($upload_dir['basedir'], '', $full_optimized_path), '/');
            if (!empty($relative)) {
                $metadata['file'] = $relative;

                // Update stored filesize so Media Library shows correct value
                if (file_exists($full_optimized_path)) {
                    $metadata['filesize'] = filesize($full_optimized_path);
                }

                // Use WP helper when available
                if (function_exists('update_attached_file')) {
                    update_attached_file($attachment_id, $full_optimized_path);
                } else {
                    update_post_meta($attachment_id, '_wp_attached_file', $relative);
                }

                // Update dimensions so Media Library "Dimensions" stays correct after browser resize
                if (function_exists('getimagesize') && file_exists($full_optimized_path)) {
                    $dims = @getimagesize($full_optimized_path);
                    if (is_array($dims) && !empty($dims[0]) && !empty($dims[1])) {
                        $metadata['width'] = (int) $dims[0];
                        $metadata['height'] = (int) $dims[1];
                    }
                }
            }
        }

        // Update sizes' filenames + filesizes + dimensions if we have optimized versions
        if (!empty($metadata['sizes']) && is_array($metadata['sizes']) && !empty($opt_meta['sizes'])) {
            foreach ($metadata['sizes'] as $size_name => &$size_data) {
                $size_optimized_path = $opt_meta['sizes'][$size_name]['optimized_path'] ?? $opt_meta['sizes'][$size_name]['webp_path'] ?? '';
                if (!empty($size_optimized_path) && is_string($size_optimized_path) && strpos($size_optimized_path, $upload_dir['basedir']) === 0) {
                    $size_data['file'] = basename($size_optimized_path);
                    if (file_exists($size_optimized_path)) {
                        $size_data['filesize'] = filesize($size_optimized_path);

                        if (function_exists('getimagesize')) {
                            $dims = @getimagesize($size_optimized_path);
                            if (is_array($dims) && !empty($dims[0]) && !empty($dims[1])) {
                                $size_data['width'] = (int) $dims[0];
                                $size_data['height'] = (int) $dims[1];
                            }
                        }
                    }
                    $size_format = $opt_meta['sizes'][$size_name]['format'] ?? $format;
                    $size_data['mime-type'] = ($size_format === 'jpg') ? 'image/jpeg' : ('image/' . $size_format);
                }
            }
            unset($size_data);
        }

        // Update sizes
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_name => &$size_data) {
                // Determine which path to use
                $size_optimized_path = $opt_meta['sizes'][$size_name]['optimized_path'] ?? $opt_meta['sizes'][$size_name]['webp_path'] ?? '';
                
                if (!empty($size_optimized_path)) {
                    $size_data['file'] = basename($size_optimized_path);
                    
                    // Update mime type based on actual format
                    $size_format = $opt_meta['sizes'][$size_name]['format'] ?? $format;
                    if ($size_format === 'jpg') {
                        $size_data['mime-type'] = 'image/jpeg';
                    } else {
                        $size_data['mime-type'] = 'image/' . $size_format;
                    }
                }
            }
        }

        wp_update_attachment_metadata($attachment_id, $metadata);
        
        // Update post mime type
        $new_mime = ($format === 'jpg') ? 'image/jpeg' : 'image/' . $format;
        wp_update_post([
            'ID' => $attachment_id,
            'post_mime_type' => $new_mime,
        ]);
    }

    /**
     * Revert to original URLs.
     */
    public static function revert_to_original_urls(int $attachment_id): int
    {
        $meta = self::get_optimization_meta($attachment_id);

        $updated = 0;

        if (!empty($meta['url_replacements']) && is_array($meta['url_replacements'])) {
            global $wpdb;

            // Reverse the replacements
            $replacements = array_flip($meta['url_replacements']);

        // Update wp_posts.post_content
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

        // Update wp_postmeta
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_value LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

        // Update wp_options
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, %s, %s) WHERE option_value LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

        // Update wp_termmeta
        foreach ($replacements as $old_url => $new_url) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->termmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_value LIKE %s",
                    $old_url,
                    $new_url,
                    '%' . $wpdb->esc_like($old_url) . '%'
                )
            );
            $updated += $result !== false ? $result : 0;
        }

            // Elementor data
            $updated += self::replace_in_elementor_data($replacements);
        }

        // Restore original attachment metadata
        $original_metadata = get_post_meta($attachment_id, '_king_img_original_metadata', true);
        if ($original_metadata) {
            wp_update_attachment_metadata($attachment_id, $original_metadata);
            delete_post_meta($attachment_id, '_king_img_original_metadata');
        }

        // Restore original attached file (so Media Library points back to the original)
        $original_attached = get_post_meta($attachment_id, '_king_img_original_attached_file', true);
        if (!empty($original_attached)) {
            update_post_meta($attachment_id, '_wp_attached_file', $original_attached);
            delete_post_meta($attachment_id, '_king_img_original_attached_file');

            // Also restore mime type based on the restored file
            $original_file_abs = get_attached_file($attachment_id);
            if ($original_file_abs && file_exists($original_file_abs)) {
                $file_type = wp_check_filetype($original_file_abs);
                if (!empty($file_type['type'])) {
                    wp_update_post([
                        'ID' => $attachment_id,
                        'post_mime_type' => $file_type['type'],
                    ]);
                }
            }
        }

        // Update meta (if it exists)
        if (is_array($meta)) {
            $meta['urls_replaced'] = false;
            $meta['urls_replaced_at'] = null;
            unset($meta['url_replacements']);
            self::set_optimization_meta($attachment_id, $meta);
        }

        return $updated;
    }

    /**
     * Get images pending optimization.
     */
    public static function get_pending_images(int $limit = 50, int $offset = 0): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_mime_type 
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                WHERE p.post_type = 'attachment' 
                AND p.post_mime_type LIKE 'image/%%'
                AND (pm.meta_value IS NULL OR pm.meta_value LIKE '%%\"status\";s:7:\"pending\"%%')
                ORDER BY p.ID DESC
                LIMIT %d OFFSET %d",
                self::META_KEY,
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Get optimized images.
     */
    public static function get_optimized_images(int $limit = 50, int $offset = 0): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_mime_type, pm.meta_value as optimization_meta
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                WHERE p.post_type = 'attachment' 
                AND p.post_mime_type LIKE 'image/%%'
                AND pm.meta_value LIKE '%%\"status\";s:9:\"optimized\"%%'
                ORDER BY p.ID DESC
                LIMIT %d OFFSET %d",
                self::META_KEY,
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Count images by status.
     */
    public static function count_images_by_status(): array
    {
        global $wpdb;

        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"
        );

        $optimized = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'attachment' 
                AND p.post_mime_type LIKE 'image/%%'
                AND pm.meta_key = %s
                AND pm.meta_value LIKE '%%\"status\";s:9:\"optimized\"%%'",
                self::META_KEY
            )
        );

        return [
            'total' => $total,
            'optimized' => $optimized,
            'pending' => $total - $optimized,
        ];
    }

    /**
     * Get images by format.
     */
    public static function count_images_by_format(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT post_mime_type, COUNT(*) as count 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'
            GROUP BY post_mime_type",
            ARRAY_A
        );

        $formats = [];
        foreach ($results as $row) {
            $format = str_replace('image/', '', $row['post_mime_type']);
            $formats[$format] = (int) $row['count'];
        }

        return $formats;
    }

    /**
     * Get total bytes saved.
     */
    public static function get_total_savings(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.meta_value FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s 
                AND p.post_type = 'attachment'
                AND pm.meta_value LIKE '%%\"status\";s:9:\"optimized\"%%'",
                self::META_KEY
            )
        );

        $total_original = 0;
        $total_optimized = 0;
        $total_saved = 0;

        foreach ($results as $row) {
            $meta = maybe_unserialize($row->meta_value);
            if (is_array($meta)) {
                $total_original += $meta['total_original_bytes'] ?? 0;
                $total_optimized += $meta['total_optimized_bytes'] ?? 0;
                $total_saved += $meta['total_saved_bytes'] ?? 0;
            }
        }

        return [
            'total_original' => $total_original,
            'total_optimized' => $total_optimized,
            'total_saved' => $total_saved,
            'savings_percent' => $total_original > 0 ? round(($total_saved / $total_original) * 100, 1) : 0,
        ];
    }
}
