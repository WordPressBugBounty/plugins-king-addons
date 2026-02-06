<?php

/** @noinspection SpellCheckingInspection, DuplicatedCode */

namespace King_Addons;

use Exception;
use King_Addons\SectionsMap;

if (!defined('ABSPATH')) {
    exit;
}

final class Templates
{
    private static ?Templates $instance = null;

    public static function instance(): ?Templates
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_template_catalog_page(): void
    {
        $templates = TemplatesMap::getTemplatesMapArray();
        $collections = CollectionsMap::getCollectionsMapArray();

        // Load Sections for counting
        if (!class_exists('King_Addons\\SectionsMap')) {
            require_once KING_ADDONS_PATH . 'includes/SectionsMap.php';
        }
        $sections_map = SectionsMap::getSectionsMapArray();
        $sections = $sections_map['sections'] ?? [];

        uasort($collections, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        $is_premium_active = king_addons_freemius()->can_use_premium_code();

        // TODO: TEST: For UI testing, it doesn't enable the real premium
        //        $is_premium_active = false;

        // Arrays for categories and tags
        $categories = [];
        $tags = [];
        $category_counts = [];
        $collection_counts = [];
        $collection_images = [];
        $template_collection_map = [];

        // Getting unique categories and tags
        foreach ($templates['templates'] as $key => $template) {
            if (!in_array($template['category'], $categories)) {
                $categories[] = $template['category'];
            }

            foreach ($template['tags'] as $tag) {
                if (!in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }

            $category = $template['category'];
            $category_counts[$category] = isset($category_counts[$category]) ? $category_counts[$category] + 1 : 1;

            $collection_id = $template['collection'] ?? '';
            if ($collection_id !== '') {
                $collection_counts[$collection_id] = isset($collection_counts[$collection_id]) ? $collection_counts[$collection_id] + 1 : 1;
                if (!isset($collection_images[$collection_id])) {
                    $collection_images[$collection_id] = $key;
                }
                $template_collection_map[$key] = $collection_id;
            }
        }

        // Count sections per collection
        $collection_sections_counts = [];
        foreach ($sections as $section) {
            $parent = $section['parent_template'] ?? '';
            if ($parent && isset($template_collection_map[$parent])) {
                $col_id = $template_collection_map[$parent];
                $collection_sections_counts[$col_id] = isset($collection_sections_counts[$col_id]) ? $collection_sections_counts[$col_id] + 1 : 1;
            }
        }

        sort($categories);

        // Get filters from query
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        $selected_collection = isset($_GET['collection']) ? sanitize_text_field($_GET['collection']) : '';
        $selected_tags = isset($_GET['tags']) ? array_filter(explode(',', sanitize_text_field($_GET['tags']))) : [];
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        // Use the common function to get filtered templates and pagination
        $result = $this->get_filtered_templates($templates, $search_query, $selected_category, $selected_tags, $selected_collection, $current_page);

        if (isset($_GET['ajax']) && $_GET['ajax']) {
            wp_send_json_success(['grid_html' => $result['grid_html'], 'pagination_html' => $result['pagination_html']]);
        }
        if ($is_premium_active) {
?>
            <script type="text/javascript">
                (function() {
                    window.kingAddons = window.kingAddons || {};
                    window.kingAddons.installId = <?php
                                                    echo json_encode(king_addons_freemius()->get_site()->id);
                                                    ?>;
                })();
            </script>
        <?php
        }
        ?>
        <div id="king-addons-templates-top"></div>
        <div id="king-addons-templates" class="king-addons-templates">
            <div class="kng-intro">
                <div class="kng-intro-wrap">
                    <div class="kng-intro-wrap-1">
                        <h1 class="kng-intro-title"><?php echo esc_html__('King Addons for Elementor', 'king-addons'); ?></h1>
                        <?php if ($is_premium_active): ?>
                            <span class="premium-active-txt"><?php echo esc_html__('PREMIUM', 'king-addons'); ?></h1></span>
                        <?php endif; ?>
                        <h2 class="kng-intro-subtitle"><?php echo esc_html__('Discover professionally designed, attention-grabbing, and SEO-optimized templates perfect for any site', 'king-addons'); ?></h2>
                    </div>
                    <div class="kng-intro-wrap-2">
                        <div class="kng-navigation">
                            <div class="kng-nav-item kng-nav-item-current">
                                <a href="<?php echo admin_url('admin.php?page=king-addons'); ?>">
                                    <div class="kng-nav-item-txt"><?php echo esc_html__('Free Widgets & Features', 'king-addons'); ?></div>
                                </a>
                            </div>
                            <?php if (KING_ADDONS_EXT_HEADER_FOOTER_BUILDER): ?>
                                <div class="kng-nav-item kng-nav-item-current">
                                    <a href="<?php echo admin_url('edit.php?post_type=king-addons-el-hf'); ?>">
                                        <div class="kng-nav-item-txt"><?php echo esc_html__('Free Header & Footer Builder', 'king-addons'); ?></div>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (KING_ADDONS_EXT_POPUP_BUILDER): ?>
                                <div class="kng-nav-item kng-nav-item-current">
                                    <a href="<?php echo admin_url('admin.php?page=king-addons-popup-builder'); ?>">
                                        <div class="kng-nav-item-txt"><?php echo esc_html__('Free Popup Builder', 'king-addons'); ?></div>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="kng-nav-item kng-nav-item-current">
                                    <a href="https://www.youtube.com/@kingaddons" target="_blank">
                                        <div class="kng-nav-item-txt"><?php echo esc_html__('YouTube Guides', 'king-addons'); ?></div>
                                    </a>
                            </div>
                            <?php if (!king_addons_freemius()->can_use_premium_code()): ?>
                                <div class="kng-nav-item kng-nav-item-current kng-nav-activate-license">
                                    <a id="activate-license-btn">
                                        <img src="<?php echo esc_url(KING_ADDONS_URL) . 'includes/admin/img/up.svg'; ?>"
                                            alt="<?php echo esc_html__('Activate License', 'king-addons'); ?>">
                                        <div class="kng-nav-item-txt"><?php echo esc_html__('Activate License', 'king-addons'); ?></div>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (!king_addons_freemius()->can_use_premium_code()): ?>
                                <div class="kng-promo-btn-wrap">
                        <a href="https://kingaddons.com/pricing/?utm_source=king-addons-templates-catalog" target="_blank">
                            <div class="kng-promo-btn-txt">
                                <?php esc_html_e('Unlock Premium Features & 650+ Templates Today!', 'king-addons'); ?>
                            </div>
                            <img width="16px"
                                 src="<?php echo esc_url(KING_ADDONS_URL) . 'includes/admin/img/share-v2.svg'; ?>"
                                 alt="<?php echo esc_html__('Open link in the new tab', 'king-addons'); ?>">
                        </a>
                    </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Catalog Tabs -->
            <div class="king-addons-catalog-tabs">
                <button class="king-addons-tab-button active" data-tab="templates">
                    <i class="eicon-document-file"></i>
                    <?php esc_html_e('Templates', 'king-addons'); ?>
                    <span class="tab-count"><?php echo count($templates['templates']); ?></span>
                </button>
                <button class="king-addons-tab-button" data-tab="sections">
                    <i class="eicon-section"></i>
                    <?php esc_html_e('Sections', 'king-addons'); ?>
                    <span class="tab-count" id="sections-count">0</span>
                </button>
                <button class="king-addons-tab-button" data-tab="collections">
                    <i class="eicon-folder"></i>
                    <?php esc_html_e('Collections', 'king-addons'); ?>
                    <span class="tab-count"><?php echo count($collections); ?></span>
                </button>
            </div>

            <!-- Templates Tab Content -->
            <div id="templates-catalog" class="king-addons-tab-content active">
                <div class="filters-wrapper">
                    <div class="filters">
                        <select id="template-category">
                            <option value=""><?php esc_html_e('All Categories', 'king-addons'); ?>
                                (<?php echo count($templates['templates']); ?>)
                            </option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>" <?php selected($selected_category, $category); ?>>
                                    <?php echo esc_html(ucwords(str_replace('-', ' ', $category))); ?>
                                    (<?php echo $category_counts[$category]; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="template-collection">
                            <option value=""><?php esc_html_e('All Collections', 'king-addons'); ?>
                                (<?php echo count($collections); ?>)
                            </option>
                            <?php foreach ($collections as $id => $name): ?>
                                <option value="<?php echo esc_attr($id); ?>" <?php selected($selected_collection, $id); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="template-tags">
                            <?php
                            shuffle($tags); ?>
                            <div class="tags-header">Tags</div>
                            <?php foreach ($tags as $tag): ?>
                                <label>
                                    <input type="checkbox"
                                        value="<?php echo esc_attr($tag); ?>" <?php echo in_array($tag, $selected_tags) ? 'checked' : ''; ?>> <?php echo esc_html(ucwords(str_replace('-', ' ', $tag))); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button id="reset-filters"><?php esc_html_e('Reset Search & Filters', 'king-addons'); ?></button>
                        <?php if (!$is_premium_active): ?>
                            <div class="promo-wrapper">
                                <div class="promo-txt"><?php
                                                        esc_html_e('Unlock Premium Templates', 'king-addons');
                                                        echo '<ul><li>$4.99/month</li>';
                                                        echo '<li>Unlimited Downloads</li>';
                                                        echo '<li>Keep all templates forever</li></ul>';
                                                        ?></div>
                                <a class="purchase-btn"
                                    href="https://kingaddons.com/pricing/?utm_source=kng-templates-banner-side&utm_medium=plugin&utm_campaign=kng"
                                    target="_blank">
                                    <button class="promo-btn purchase-btn"
                                        style="display: flex;align-items: center;">
                                        <img src="<?php echo esc_url(KING_ADDONS_URL) . 'includes/admin/img/icon-for-admin.svg'; ?>"
                                            style="margin-right: 5px;width: 14px;height: 14px;"
                                            alt="<?php echo esc_html__('Upgrade Now', 'king-addons'); ?>"><?php esc_html_e('Upgrade Now', 'king-addons'); ?>
                                    </button>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="templates-grid-wrapper">
                    <div class="search-wrapper">
                        <input type="text" id="template-search" value="<?php echo esc_attr($search_query); ?>"
                            placeholder="<?php esc_attr_e('Search templates...', 'king-addons'); ?>">
                    </div>
                    <div class="templates-grid">
                        <?php echo $result['grid_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>
                    </div>
                    <div class="pagination">
                        <?php echo $result['pagination_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>
                    </div>
                </div>
            </div>

            <!-- Sections Tab Content -->
            <div id="sections-catalog" class="king-addons-tab-content">
                <div class="filters-wrapper">
                    <div class="filters">
                        <select id="sections-category">
                            <option value=""><?php esc_html_e('All Categories', 'king-addons'); ?></option>
                        </select>
                        <select id="sections-type">
                            <option value=""><?php esc_html_e('All Types', 'king-addons'); ?></option>
                        </select>
                        <select id="sections-plan">
                            <option value=""><?php esc_html_e('All Plans', 'king-addons'); ?></option>
                            <option value="free"><?php esc_html_e('Free', 'king-addons'); ?></option>
                            <?php if ($is_premium_active): ?>
                            <option value="premium"><?php esc_html_e('Premium', 'king-addons'); ?></option>
                            <?php endif; ?>
                        </select>
                        <button id="sections-reset-filters"><?php esc_html_e('Reset Search & Filters', 'king-addons'); ?></button>
                    </div>
                </div>
                <div class="sections-grid-wrapper">
                    <div class="search-wrapper">
                        <input type="text" id="sections-search" placeholder="<?php esc_attr_e('Search sections...', 'king-addons'); ?>">
                    </div>
                    <div class="sections-grid">
                        <div class="sections-loading">
                            <?php esc_html_e('Loading sections...', 'king-addons'); ?>
                        </div>
                    </div>
                    <div class="sections-pagination"></div>
                </div>
            </div>

            <!-- Collections Tab Content -->
            <div id="collections-catalog" class="king-addons-tab-content">
                <div class="collections-toolbar">
                    <div class="collections-search">
                        <input type="text" id="collections-search" placeholder="<?php esc_attr_e('Search collections...', 'king-addons'); ?>">
                    </div>
                </div>

                <div class="collections-rows-wrapper">
                    <div class="collections-rows-container">
                        <div class="collections-loading">
                            <?php esc_html_e('Loading collections...', 'king-addons'); ?>
                        </div>
                    </div>
                    <div class="collections-rows-pagination"></div>
                </div>
                
                <!-- Collection Details View -->
                <div id="collection-details-view" style="display: none;">
                    <div class="collection-details-header">
                        <button type="button" class="ka-back-button">
                            <i class="eicon-arrow-left"></i> <?php esc_html_e('Back to Collections', 'king-addons'); ?>
                        </button>
                        <div class="collection-details-search">
                            <input type="text" id="collection-search" placeholder="<?php esc_attr_e('Search in collection...', 'king-addons'); ?>">
                        </div>
                    </div>
                    <div class="collection-details-content">
                        <div class="collection-loading">
                            <?php esc_html_e('Loading collection...', 'king-addons'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="template-preview-popup" style="display:none;">
                <div class="popup-content">
                    <div class="popup-content-nav">
                        <button data-plan-active="<?php echo ($is_premium_active) ? 'premium' : 'free'; ?>"
                            id="install-template">
                            <?php esc_html_e('Import Template', 'king-addons'); ?>
                        </button>
                        <a href="#" id="template-preview-link" target="_blank">
                            <?php esc_html_e('Live Preview', 'king-addons'); ?>
                        </a>
                        <div class="preview-mode-switcher">
                            <button data-mode="desktop" class="active" id="preview-desktop">
                                <span class="dashicons dashicons-desktop"></span>
                            </button>
                            <button data-mode="tablet" id="preview-tablet">
                                <span class="dashicons dashicons-tablet"></span>
                            </button>
                            <button data-mode="mobile" id="preview-mobile">
                                <span class="dashicons dashicons-smartphone"></span>
                            </button>
                        </div>
                        <button id="close-popup">
                            <?php esc_html_e('Close Preview X', 'king-addons'); ?>
                        </button>
                    </div>
                    <iframe id="template-preview-iframe" src="" frameborder="0"></iframe>
                </div>
            </div>

            <!-- Section Import Popup -->
            <div id="section-import-popup" class="ka-popup-overlay" style="display:none;">
                <div class="ka-popup-content">
                    <button type="button" class="ka-popup-close" id="close-section-popup">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <div class="ka-popup-body">
                        <div class="ka-popup-icon">
                            <i class="eicon-file-download"></i>
                        </div>
                        <h3 class="ka-popup-title"><?php esc_html_e('Import Section', 'king-addons'); ?></h3>
                        <p class="ka-popup-message">
                            <?php esc_html_e('Are you sure you want to import this section? It will be added to your current page.', 'king-addons'); ?>
                        </p>
                        <div class="ka-popup-details">
                            <div class="ka-popup-detail-row">
                                <span class="ka-label"><?php esc_html_e('Section:', 'king-addons'); ?></span>
                                <span class="ka-value" id="popup-section-name"></span>
                            </div>
                            <div class="ka-popup-detail-row">
                                <span class="ka-label"><?php esc_html_e('Plan:', 'king-addons'); ?></span>
                                <span class="ka-value" id="popup-section-plan"></span>
                            </div>
                        </div>
                        <div class="ka-popup-actions">
                            <button type="button" class="ka-btn ka-btn-secondary" id="cancel-section-import">
                                <?php esc_html_e('Cancel', 'king-addons'); ?>
                            </button>
                            <button type="button" class="ka-btn ka-btn-primary" id="confirm-section-import">
                                <?php esc_html_e('Import Section', 'king-addons'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="template-installing-popup" style="display:none;">
                <div class="popup-content">
                    <div id="progress-container">
                        <div id="progress-bar">
                            0%
                        </div>
                    </div>
                    <div id="progress"></div>
                    <div id="image-list"></div>
                    <div id="final_response"></div>
                    <button id="close-installing-popup"
                        style="display:none;"><?php esc_html_e('Close', 'king-addons'); ?></button>
                    <a href="#" id="go-to-imported-page"
                        style="display:none;"><?php esc_html_e('Go to imported page', 'king-addons'); ?></a>
                </div>
            </div>
            <div id="license-activating-popup" style="display:none;">
                <div class="license-activating-popup-content">
                    <div class="license-activating-popup-txt"><?php esc_html_e('1. Download and install the premium version of the plugin - King Addons Pro. You can find the link in the email received after the license purchase.', 'king-addons'); ?></div>
                    <div class="license-activating-popup-txt"><?php esc_html_e('2. Go to the Plugins page.', 'king-addons'); ?></div>
                    <div class="license-activating-popup-txt"><?php esc_html_e('3. Find the King Addons Pro plugin.', 'king-addons'); ?></div>
                    <div class="license-activating-popup-txt"><?php esc_html_e('4. Click on Activate License link.', 'king-addons'); ?></div>
                    <div class="license-activating-popup-txt"><?php esc_html_e('5. Enter the License Key provided in the email. Done!', 'king-addons'); ?></div>
                    <button id="close-license-activating-popup"><?php esc_html_e('Close', 'king-addons'); ?></button>
                </div>
            </div>
            <div id="premium-promo-popup" style="display:none;">
                <div class="premium-promo-popup-content">
                    <div class="premium-promo-popup-wrapper">
                        <div class="premium-promo-popup-txt"><?php

                                                                echo '<span class="pr-popup-title">Want This Premium Template?</span>';
                                                                echo '<br><span class="pr-popup-desc">';
                                                                echo 'Get <span class="pr-popup-desc-b">unlimited downloads</span> for just';
                                                                echo ' <span class="pr-popup-desc-b">$4.99/month';
                                                                echo '</span> — keep them <span class="pr-popup-desc-b">even after</span> your subscription ends!';
                                                                echo '</span><span class="pr-popup-desc" style="font-size: 16px;opacity: 0.6;">Trusted by 20,000+ users</span>';
                                                                ?></div>
                        <a class="purchase-btn"
                            href="https://kingaddons.com/pricing/?utm_source=kng-templates-banner-pro&utm_medium=plugin&utm_campaign=kng"
                            target="_blank">
                            <button class="premium-promo-popup-purchase-btn purchase-btn">
                                <img src="<?php echo esc_url(KING_ADDONS_URL) . 'includes/admin/img/icon-for-admin.svg'; ?>"
                                    style="margin-right: 7px;width: 16px;height: 16px;"
                                    alt="<?php echo esc_html__('Unlock All Templates', 'king-addons'); ?>"><?php esc_html_e('Unlock All Templates', 'king-addons'); ?>
                            </button>
                        </a>
                        <button id="close-premium-promo-popup"><?php esc_html_e('Close', 'king-addons'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_filtered_templates($templates, $search_query, $selected_category, $selected_tags, $selected_collection, $current_page): array
    {
        $search_terms = array_filter(explode(' ', $search_query));
        $has_search = !empty($search_terms);
        // Custom: direct key search
        $search_by_key = false;
        $found_by_key = [];
        if (!empty($search_query) && isset($templates['templates'][$search_query])) {
            $found_by_key[] = $templates['templates'][$search_query];
            $found_by_key[0]['template_key'] = $search_query;
            $search_by_key = true;
        }

        // Filter templates based on search and selected filters
        if ($search_by_key) {
            $filtered_templates = $found_by_key;
        } elseif (!$has_search) {
            $filtered_templates = array_filter($templates['templates'], function ($template) use ($search_terms, $selected_category, $selected_tags, $selected_collection) {

                foreach ($search_terms as $term) {
                    $found_in_title = stripos($template['title'], $term) !== false;
                    $found_in_tags = false;

                    foreach ($template['tags'] as $tag) {
                        if (stripos($tag, $term) !== false) {
                            $found_in_tags = true;
                            break;
                        }
                    }

                    if (!$found_in_title && !$found_in_tags) {
                        return false;
                    }
                }

                if ($selected_category && $template['category'] !== $selected_category) {
                    return false;
                }

                if ($selected_tags) {
                    $template_tags = $template['tags'];
                    foreach ($selected_tags as $tag) {
                        if (!in_array($tag, $template_tags)) {
                            return false;
                        }
                    }
                }

                if ($selected_collection && $template['collection'] != $selected_collection) {
                    return false;
                }

                return true;
            });

            // Shuffle templates
            // shuffle($filtered_templates);

        } else {

            $filtered_templates = $templates['templates'];

            if (!empty($search_terms) || !empty($selected_category) || !empty($selected_tags) || !empty($selected_collection)) {
                $matched_by_title = [];
                $matched_by_tags = [];

                foreach ($filtered_templates as $key => $template) {

                    $found_in_title = false;
                    $found_in_tags = false;

                    foreach ($search_terms as $term) {
                        if ($term && stripos($template['title'], $term) !== false) {
                            $found_in_title = true;
                        }

                        foreach ($template['tags'] as $tag) {
                            if (stripos($tag, $term) !== false) {
                                $found_in_tags = true;
                                break;
                            }
                        }
                        //                        if (!$found_in_title && !$found_in_tags) {
                        //                            continue;
                        //                        }
                    }

                    if ($selected_category && $template['category'] !== $selected_category) {
                        continue;
                    }

                    if ($selected_tags) {
                        $template_tags = $template['tags'];
                        foreach ($selected_tags as $tag) {
                            if (!in_array($tag, $template_tags)) {
                                continue 2;
                            }
                        }
                    }

                    if ($selected_collection && $template['collection'] != $selected_collection) {
                        continue;
                    }

                    // Attach the template key for later use
                    $template['template_key'] = $key;

                    if ($found_in_title) {
                        $matched_by_title[] = $template;
                    } elseif ($found_in_tags) {
                        $matched_by_tags[] = $template;
                    }
                }

                // Combine the arrays, so templates matching by title come first
                $filtered_templates = array_merge($matched_by_title, $matched_by_tags);
            }
        }

        // Pagination setup
        $items_per_page = 20;
        $total_templates = count($filtered_templates);
        $offset = ($current_page - 1) * $items_per_page;
        $paged_templates = array_slice($filtered_templates, $offset, $items_per_page);

        ob_start();
        if (empty($paged_templates)) {
            echo '<p class="templates-not-found">' . esc_html__('No templates found.', 'king-addons') . '</p>';
        } else {
            foreach ($paged_templates as $key => $template) {
                $attr_key = ($has_search) ? $template['template_key'] : $key;
        ?>
                <div class="template-item"
                    data-category="<?php echo esc_attr($template['category']); ?>"
                    data-tags="<?php echo esc_attr(implode(',', $template['tags'])); ?>"
                    data-template-key="<?php echo esc_attr($attr_key); ?>"
                    data-template-plan="<?php echo esc_attr($template['plan']); ?>">
                    <img class="kng-addons-template-thumbnail" loading="lazy"
                        src="<?php echo esc_url("https://thumbnails.kingaddons.com/$attr_key.png?v=4"); ?>"
                        alt="<?php echo esc_attr($template['title']); ?>">
                    <h3><?php echo esc_html($template['title']); ?></h3>
                    <div class="template-plan template-plan-<?php echo esc_html($template['plan']); ?>"><?php echo esc_html($template['plan']); ?></div>
                </div>
<?php
            }
        }
        $grid_html = ob_get_clean();

        ob_start();

        $pages = paginate_links(array(
            'base' => add_query_arg(array(
                'paged' => '%#%',
                's' => $search_query,
                'category' => $selected_category,
                'collection' => $selected_collection,
                'tags' => implode(',', $selected_tags),
            )),
            'format' => '?paged=%#%',
            'current' => $current_page,
            'total' => ceil($total_templates / $items_per_page),
            'prev_text' => __('&larr; Previous', 'king-addons'),
            'next_text' => __('Next &rarr;', 'king-addons'),
            'end_size' => 9,
            'mid_size' => 3,
        ));
        if ($pages) {
            echo '<div id="king-addons-pagination-inner-wrap" class="pagination-inner-wrap"><div class="pagination-inner">';
            echo $pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div></div>';
        }

        $pagination_html = ob_get_clean();

        return ['grid_html' => $grid_html, 'pagination_html' => $pagination_html];
    }

    public function king_addons_enqueue_scripts(): void
    {
        $screen = get_current_screen();
        if ($screen->id === 'toplevel_page_king-addons-templates') {
            wp_enqueue_style('king-addons-templates-style', KING_ADDONS_URL . 'includes/admin/css/templates.css', '', KING_ADDONS_VERSION);

            if (!wp_script_is(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . 'jquery' . '-' . 'jquery')) {
                wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . 'jquery' . '-' . 'jquery', '', '', KING_ADDONS_VERSION);
            }

            wp_enqueue_script('king-addons-templates-script', KING_ADDONS_URL . 'includes/admin/js/templates.js', '', KING_ADDONS_VERSION, true);

            wp_localize_script('king-addons-templates-script', 'kingAddonsData', array(
                'adminUrl' => admin_url('admin-post.php'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kingAddonsNonce'),
            ));
        }
        // if ($screen->id === 'king-addons_page_king-addons-account') {
        //     wp_enqueue_style('king-addons-account-style', KING_ADDONS_URL . 'includes/admin/css/account.css', '', KING_ADDONS_VERSION);
        // }
    }

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'king_addons_enqueue_scripts'));

        // Get the performance setting
        $improve_import = get_option('king_addons_improve_import_performance', '1');

        // Conditionally add time limit adjustments based on setting
        if ($improve_import === '1') {
            add_action('wp_ajax_import_elementor_page_with_images', function () {
                @set_time_limit(300);
            }, 0);

            add_action('wp_ajax_process_import_images', function () {
                @set_time_limit(300);
            }, 0);
        }

        add_action(
            'wp_ajax_import_elementor_page_with_images',
            [$this, 'import_elementor_page_with_images']
        );

        add_action(
            'wp_ajax_process_import_images',
            [$this, 'process_import_images']
        );

        add_action('wp_ajax_filter_templates', array($this, 'handle_filter_templates'));
        add_action('wp_ajax_king_addons_get_collection_details', array($this, 'handle_get_collection_details'));
        add_action('wp_ajax_king_addons_get_collections_rows', array($this, 'handle_get_collections_rows'));
        
        // Sections catalog endpoints
        add_action('wp_ajax_king_addons_get_sections_catalog', array($this, 'handle_get_sections_catalog'));
        add_action('wp_ajax_king_addons_import_section_admin', array($this, 'handle_import_section_to_page'));
        
        add_action('http_api_curl', array($this, 'set_custom_curl_options'), 10, 3);
    }

    public function set_custom_curl_options($handle, $r, $url)
    {
        if (strpos($url, 'kingaddons.com') !== false) {
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($handle, CURLOPT_DNS_CACHE_TIMEOUT, 300);
            curl_setopt($handle, CURLOPT_TIMEOUT, 300);
        }
    }

    function handle_filter_templates(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['action']) || $_POST['action'] !== 'filter_templates') {
            wp_send_json_error('Invalid request');
            return;
        }

        $search_query = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
        $selected_category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $selected_collection = isset($_POST['collection']) ? sanitize_text_field($_POST['collection']) : '';
        $selected_tags = isset($_POST['tags']) ? array_filter(explode(',', sanitize_text_field($_POST['tags']))) : [];
        $current_page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;

        $templates = TemplatesMap::getTemplatesMapArray();

        // Use the common function to get filtered templates and pagination
        $result = $this->get_filtered_templates($templates, $search_query, $selected_category, $selected_tags, $selected_collection, $current_page);

        wp_send_json_success(['grid_html' => $result['grid_html'], 'pagination_html' => $result['pagination_html']]);
    }

    public function import_elementor_page_with_images(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Support both the legacy templates admin nonce and the Elementor editor
        // Template Catalog button nonce.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $valid_nonce = $nonce && (
            wp_verify_nonce($nonce, 'kingAddonsNonce') ||
            wp_verify_nonce($nonce, 'king_addons_template_catalog')
        );

        if (!$valid_nonce) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $import_data = json_decode(stripslashes($_POST['data']), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('JSON decode error: ' . json_last_error_msg());
            return;
        }

        if (isset($import_data['content']) && isset($import_data['images']) && isset($import_data['title'])) {
            $content = $import_data['content'];
            $image_data = $import_data['images'];

            $page_title = sanitize_text_field($import_data['title']);
            $elementor_version = sanitize_text_field($import_data['elementor_version']);
            
            // Check if this is for existing page (from popup import)
            $existing_page_id = isset($import_data['existing_page_id']) ? intval($import_data['existing_page_id']) : 0;
            $create_new_page = isset($import_data['create_new_page']) ? (bool)$import_data['create_new_page'] : true;

            delete_transient('elementor_import_content');
            delete_transient('elementor_import_images');
            delete_transient('elementor_import_total_images');
            delete_transient('elementor_import_images_processed');
            delete_transient('elementor_import_image_retry_count');
            delete_transient('elementor_import_page_title');
            delete_transient('elementor_import_elementor_version');
            delete_transient('elementor_import_existing_page_id');
            delete_transient('elementor_import_create_new_page');

            set_transient('elementor_import_content', $content, 60 * 60);
            set_transient('elementor_import_images', $image_data, 60 * 60);
            set_transient('elementor_import_total_images', count($image_data), 60 * 60);
            set_transient('elementor_import_images_processed', 0, 60 * 60);
            set_transient('elementor_import_image_retry_count', [], 60 * 60);
            set_transient('elementor_import_page_title', $page_title, 60 * 60);
            set_transient('elementor_import_elementor_version', $elementor_version, 60 * 60);
            set_transient('elementor_import_existing_page_id', $existing_page_id, 60 * 60);
            set_transient('elementor_import_create_new_page', $create_new_page, 60 * 60);

            error_log('King Addons Import: Initialized ' . ($create_new_page ? 'new page' : 'existing page') . ' import. Existing page ID: ' . $existing_page_id);

            wp_send_json_success([
                'message' => 'Import initialized.',
                'images' => $image_data,
                'existing_page_id' => $existing_page_id,
                'create_new_page' => $create_new_page
            ]);
        } else {
            wp_send_json_error('Invalid import data.');
        }
    }

    private function replace_image_data(array &$array, $old_url, $new_url, $old_id, $new_id)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                // Standard structure with both 'url' and 'id'
                if (
                    isset($value['url'], $value['id']) &&
                    $value['url'] === $old_url &&
                    $value['id'] === $old_id
                ) {
                    $value['url'] = $new_url;
                    $value['id'] = $new_id;
                }
                
                // Background image structure (common in Elementor)
                if (
                    isset($value['url']) &&
                    $value['url'] === $old_url &&
                    (!isset($value['id']) || $value['id'] === $old_id)
                ) {
                    $value['url'] = $new_url;
                    if (isset($value['id'])) {
                        $value['id'] = $new_id;
                    }
                }

                // Recursively check deeper nested arrays
                $this->replace_image_data($value, $old_url, $new_url, $old_id, $new_id);
            } elseif (is_string($value)) {
                // Direct URL string replacement
                if ($value === $old_url) {
                    $array[$key] = $new_url;
                }
                
                // CSS background-image style replacement
                if (strpos($value, 'background-image:') !== false && strpos($value, $old_url) !== false) {
                    $array[$key] = str_replace($old_url, $new_url, $value);
                }
                
                // URL() function replacement in CSS
                if (strpos($value, 'url(') !== false && strpos($value, $old_url) !== false) {
                    $array[$key] = str_replace($old_url, $new_url, $value);
                }
                
                // JSON string containing URLs (for widget settings)
                if (strpos($value, '{') === 0 && strpos($value, $old_url) !== false) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        $this->replace_image_data($decoded, $old_url, $new_url, $old_id, $new_id);
                        $array[$key] = json_encode($decoded);
                    }
                }
            }
        }
    }

    public function process_import_images(): void
    {
        // Conditionally increase execution time limit based on setting
        $improve_import = get_option('king_addons_improve_import_performance', '1');
        if ($improve_import === '1') {
            @set_time_limit(300);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('The current user can not manage options and create pages. Please change it in the WordPress settings.');
            return;
        }

        // Support both the legacy templates admin nonce and the Elementor editor
        // Template Catalog button nonce.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $valid_nonce = $nonce && (
            wp_verify_nonce($nonce, 'kingAddonsNonce') ||
            wp_verify_nonce($nonce, 'king_addons_template_catalog')
        );

        if (!$valid_nonce) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $start_time = time();
        $timeout = 30;

        $content = get_transient('elementor_import_content');
        $image_data = get_transient('elementor_import_images');
        $total_images = get_transient('elementor_import_total_images');
        $images_processed = get_transient('elementor_import_images_processed');
        $image_retry_count = get_transient('elementor_import_image_retry_count');
        $page_title = get_transient('elementor_import_page_title');
        $elementor_version = get_transient('elementor_import_elementor_version');
        $existing_page_id = get_transient('elementor_import_existing_page_id');
        $create_new_page = get_transient('elementor_import_create_new_page');

        if (!is_array($image_retry_count)) {
            $image_retry_count = [];
        }

        if ($images_processed < $total_images) {
            $current_image = $image_data[$images_processed];
            $url = $current_image['url'];
            if (!isset($image_retry_count[$url])) {
                $image_retry_count[$url] = 0;
            }

            $new_image_id = $this->download_image_to_media_gallery($url, $image_retry_count[$url]);
            if ($new_image_id === false) {
                $image_retry_count[$url]++;
                set_transient('elementor_import_image_retry_count', $image_retry_count, 60 * 60);

                if ($image_retry_count[$url] > 3) {
                    $images_processed++;
                    set_transient('elementor_import_images_processed', $images_processed, 60 * 60);
                    $progress = round(($images_processed / $total_images) * 90) + 10;

                    wp_send_json_success([
                        'progress' => $progress,
                        'message' => "Processed $images_processed of $total_images images.",
                        'image_url' => $url,
                        'images_processed' => $images_processed,
                        'new_image_id' => 'SKIPPED'
                    ]);
                } else {
                    wp_send_json_error([
                        'retry' => true,
                        'image_url' => $url
                    ]);
                }
            } else {
                $new_url = wp_get_attachment_url($new_image_id);
                $images_processed++;
                set_transient('elementor_import_images_processed', $images_processed, 60 * 60);
                $progress = round(($images_processed / $total_images) * 90) + 10;

                $this->replace_image_data($content, $url, $new_url, $current_image['id'], $new_image_id);

                set_transient('elementor_import_content', $content, 60 * 60);

                wp_send_json_success([
                    'progress' => $progress,
                    'message' => "Processed $images_processed of $total_images images.",
                    'image_url' => $url,
                    'new_image_url' => $new_url
                ]);
            }
        } else {
            // All images processed
            if ($create_new_page) {
                // Original behavior - create new page
                $new_post_id = wp_insert_post([
                    'post_title' => $page_title,
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                ]);

                if ($new_post_id) {
                    update_post_meta($new_post_id, '_elementor_data', wp_slash(json_encode($content)));
                    update_post_meta($new_post_id, '_elementor_edit_mode', 'builder');
                    update_post_meta($new_post_id, '_elementor_template_type', 'page');
                    update_post_meta($new_post_id, '_elementor_version', $elementor_version);

                    update_post_meta($new_post_id, '_wp_page_template', 'elementor_canvas');

                    update_post_meta($new_post_id, '_wp_gutenberg_disable', '1');
                    update_post_meta($new_post_id, '_wp_gutenberg_enabled', '0');

                    delete_transient('elementor_import_content');
                    delete_transient('elementor_import_images');
                    delete_transient('elementor_import_total_images');
                    delete_transient('elementor_import_images_processed');
                    delete_transient('elementor_import_image_retry_count');
                    delete_transient('elementor_import_page_title');
                    delete_transient('elementor_import_elementor_version');
                    delete_transient('elementor_import_existing_page_id');
                    delete_transient('elementor_import_create_new_page');

                    wp_send_json_success([
                        'message' => "Page imported successfully!",
                        'page_url' => get_permalink($new_post_id),
                    ]);
                } else {
                    wp_send_json_error('Failed to import page.');
                }
            } else {
                // New behavior - for existing page, just signal completion
                // Content and images are processed and stored in transients
                // Will be merged by king_addons_merge_with_existing_page endpoint
                
                error_log('King Addons Import: Image processing completed for existing page. Content ready for merge.');
                
                wp_send_json_success([
                    'message' => "Image processing completed! Content ready for merge.",
                    'images_processed' => $images_processed,
                    'existing_page_id' => $existing_page_id,
                    'processing_complete' => true
                ]);
            }
        }

        if (time() >= $start_time + $timeout) {
            wp_send_json_error('Process timeout, please resume the import.');
        }
    }

    public function download_image_to_media_gallery($image_url, $image_retry_count)
    {
        try {
            if (!$this->is_safe_remote_url($image_url)) {
                throw new Exception('Blocked potentially unsafe image URL: ' . $image_url);
            }

            $response = wp_remote_get($image_url, ['timeout' => 30]);

            if (is_wp_error($response)) {
                throw new Exception('HTTP request error: ' . $response->get_error_message());
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                throw new Exception(
                    'HTTP status code ' . $status_code . ' when trying to download: ' . $image_url
                );
            }

            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                throw new Exception('Failed to retrieve image data from URL: ' . $image_url);
            }

            $image_name = pathinfo(basename($image_url), PATHINFO_FILENAME);
            $image_extension = pathinfo(basename($image_url), PATHINFO_EXTENSION);
            $unique_image_name = $image_name . '-' . time() . '.' . $image_extension;

            $upload_dir = wp_upload_dir();
            if (! file_exists($upload_dir['path'])) {
                wp_mkdir_p($upload_dir['path']);
            }
            $image_file = $upload_dir['path'] . '/' . $unique_image_name;

            if (file_put_contents($image_file, $image_data) === false) {
                throw new Exception('Failed to write image data to file: ' . $image_file);
            }

            $wp_filetype = wp_check_filetype($unique_image_name);
            $attachment = [
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($unique_image_name),
                'post_content' => '',
                'post_status' => 'inherit',
            ];

            $attach_id = wp_insert_attachment($attachment, $image_file);

            // Conditionally disable intermediate image sizes based on setting
            $improve_import = get_option('king_addons_improve_import_performance', '1');
            if ($improve_import === '1') {
                // Disable ALL extra sizes and big-image scaling
                add_filter('intermediate_image_sizes', '__return_empty_array', 999);
                add_filter('big_image_size_threshold', '__return_false', 999);

                /* Save only the original file info — no thumbnails, no EXIF */
                // Get image dimensions
                $image_size = getimagesize($image_file);
                $width = $image_size ? $image_size[0] : 0;
                $height = $image_size ? $image_size[1] : 0;
 
                $meta = [
                    'file'       => wp_basename($image_file),
                    'filesize'   => filesize($image_file),
                    'width'      => $width,
                    'height'     => $height,
                    'sizes'      => [],
                    'image_meta' => [],
                ];
                wp_update_attachment_metadata($attach_id, $meta);

                // Clean up filters
                remove_filter('intermediate_image_sizes', '__return_empty_array', 999);
                remove_filter('big_image_size_threshold', '__return_false', 999);
            } else {
                if ($image_retry_count > 1) {
                    add_filter('intermediate_image_sizes', '__return_empty_array', 999);
                }

                // Generate default metadata if optimization is off
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $image_file);
                wp_update_attachment_metadata($attach_id, $attach_data);

                if ($image_retry_count > 1) {
                    remove_filter('intermediate_image_sizes', '__return_empty_array', 999);
                }
            }

            return $attach_id;
        } catch (Exception $e) {
            error_log('[KING_ADDONS_ERROR] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Basic SSRF guard for remote URLs.
     *
     * @param string $url URL to validate.
     *
     * @return bool
     */
    private function is_safe_remote_url(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        if (!in_array($parsed['scheme'], ['http', 'https'], true)) {
            return false;
        }

        $host = $parsed['host'];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        // Allow domain names; block obvious localhost patterns.
        $lower = strtolower($host);
        if ($lower === 'localhost' || str_ends_with($lower, '.local')) {
            return false;
        }

        return true;
    }

    /**
     * AJAX handler for getting sections catalog data
     */
    public function handle_get_sections_catalog(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Check nonce - handle both admin area and popup
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        $valid_nonce = $nonce && (wp_verify_nonce($nonce, 'kingAddonsNonce') ||
            wp_verify_nonce($nonce, 'king_addons_template_catalog'));

        if (!$valid_nonce) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!class_exists('King_Addons\\SectionsMap')) {
            require_once KING_ADDONS_PATH . 'includes/SectionsMap.php';
        }

        $sections_map_class = '\\King_Addons\\SectionsMap';
        $sections_map = is_callable([$sections_map_class, 'getSectionsMapArray'])
            ? call_user_func([$sections_map_class, 'getSectionsMapArray'])
            : [];
        $sections = $sections_map['sections'] ?? [];
        
        $is_premium_active = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();
        
        // Get filters from request
        $search_query = sanitize_text_field($_POST['search'] ?? '');
        $selected_category = sanitize_text_field($_POST['category'] ?? '');
        $selected_type = sanitize_text_field($_POST['section_type'] ?? '');
        $selected_plan = sanitize_text_field($_POST['plan'] ?? '');
        $current_page = max(1, intval($_POST['page'] ?? 1));

        // Get categories and section types for filters
        $categories = [];
        $section_types = [];

        foreach ($sections as $section_key => $section) {
            if (!in_array($section['category'], $categories)) {
                $categories[] = $section['category'];
            }
            if (!in_array($section['section_type'], $section_types)) {
                $section_types[] = $section['section_type'];
            }
        }

        sort($categories);
        sort($section_types);

        // Filter sections
        $filtered_sections = [];

        foreach ($sections as $section_key => $section) {
            // Add section key for frontend
            $section['section_key'] = $section_key;
            
            // Note: Premium sections are shown but restricted for import if no premium license
            
            // Apply search filter
            if (!empty($search_query)) {
                $found_in_title = stripos($section['title'], $search_query) !== false;
                $found_in_tags = false;
                
                foreach ($section['tags'] ?? [] as $tag) {
                    if (stripos($tag, $search_query) !== false) {
                        $found_in_tags = true;
                        break;
                    }
                }
                
                if (!$found_in_title && !$found_in_tags) {
                    continue;
                }
            }
            
            // Apply category filter
            if (!empty($selected_category) && $section['category'] !== $selected_category) {
                continue;
            }
            
            // Apply section type filter
            if (!empty($selected_type) && $section['section_type'] !== $selected_type) {
                continue;
            }
            
            // Apply plan filter
            if (!empty($selected_plan) && $section['plan'] !== $selected_plan) {
                continue;
            }
            
            $filtered_sections[] = $section;
        }

        // Pagination
        $items_per_page = 20;
        $total_sections = count($filtered_sections);
        $total_pages = ceil($total_sections / $items_per_page);
        $offset = ($current_page - 1) * $items_per_page;
        $paged_sections = array_slice($filtered_sections, $offset, $items_per_page);

        wp_send_json_success([
            'sections' => $paged_sections,
            'categories' => $categories,
            'section_types' => $section_types,
            'pagination' => [
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'total_sections' => $total_sections,
                'items_per_page' => $items_per_page
            ],
            'is_premium_active' => $is_premium_active
        ]);
    }

    /**
     * AJAX handler for importing section to current page (from main admin catalog)
     */
    public function handle_import_section_to_page(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'kingAddonsNonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $section_key = sanitize_text_field($_POST['section_key']);
        $section_plan = sanitize_text_field($_POST['section_plan']);
        $is_premium_active = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();

        // Determine API URL and install ID (same logic as templates)
        if ($is_premium_active && $section_plan === 'premium') {
            $api_url = 'https://api.kingaddons.com/get-section.php';
            
            // Use the same method as original templates catalog
            if (function_exists('king_addons_freemius')) {
                $freemius_site = king_addons_freemius()->get_site();
                $install_id = $freemius_site ? $freemius_site->id : 0;
            } else {
                $install_id = 0;
            }
            
            error_log('King Addons Premium Section: Using install_id: ' . $install_id . ' for premium section: ' . $section_key);
        } elseif ($section_plan === 'free') {
            $api_url = 'https://api.kingaddons.com/get-section-free.php';
            $install_id = 0;
            error_log('King Addons Free Section: Fetching free section: ' . $section_key);
        } else {
            error_log('King Addons Section Error: Premium section requires premium license. Section: ' . $section_key . ', Plan: ' . $section_plan . ', Premium Active: ' . ($is_premium_active ? 'Yes' : 'No'));
            wp_send_json_error('Premium section requires premium license');
            return;
        }

        // Get section data from API (same as templates)
        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'key' => $section_key,
                'install' => $install_id,
            ]),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch section: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        error_log('King Addons Section API Response: ' . substr($body, 0, 500) . (strlen($body) > 500 ? '...' : ''));
        
        if (!$data) {
            error_log('King Addons Section Error: Failed to decode JSON response');
            wp_send_json_error('Invalid JSON response from section API');
            return;
        }

        if (!isset($data['success']) || !$data['success']) {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown API error';
            error_log('King Addons Section Error: API returned error: ' . $error_message);
            wp_send_json_error('Section API error: ' . $error_message);
            return;
        }

        // Return section data for frontend processing (adjust format from your API)
        wp_send_json_success([
            'section_data' => $data['section'],  // Your API returns 'section' not 'landing'
            'message' => 'Section data retrieved successfully'
        ]);
    }

    public function handle_get_collection_details()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kingAddonsNonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $collection_id = isset($_POST['collection_id']) ? sanitize_text_field($_POST['collection_id']) : '';
        if (empty($collection_id)) {
            wp_send_json_error('Collection ID is required');
        }

        $templates = TemplatesMap::getTemplatesMapArray();
        
        if (!class_exists('King_Addons\\SectionsMap')) {
            require_once KING_ADDONS_PATH . 'includes/SectionsMap.php';
        }
        $sections_map = SectionsMap::getSectionsMapArray();
        $sections = $sections_map['sections'] ?? [];

        // Filter Templates
        $collection_templates = [];
        $template_keys = [];
        foreach ($templates['templates'] as $key => $template) {
            if (isset($template['collection']) && (string)$template['collection'] === (string)$collection_id) {
                $template['template_key'] = $key;
                $collection_templates[] = $template;
                $template_keys[] = $key;
            }
        }

        // Filter Sections
        $collection_sections = [];
        foreach ($sections as $key => $section) {
            $parent = $section['parent_template'] ?? '';
            if ($parent && in_array($parent, $template_keys)) {
                $section['section_key'] = $key;
                $collection_sections[] = $section;
            }
        }

        // Render Templates HTML
        ob_start();
        if (empty($collection_templates)) {
            echo '<p class="templates-not-found">' . esc_html__('No templates found in this collection.', 'king-addons') . '</p>';
        } else {
            foreach ($collection_templates as $template) {
                $attr_key = $template['template_key'];
                ?>
                <div class="template-item"
                    data-category="<?php echo esc_attr($template['category']); ?>"
                    data-tags="<?php echo esc_attr(implode(',', $template['tags'])); ?>"
                    data-template-key="<?php echo esc_attr($attr_key); ?>"
                    data-template-plan="<?php echo esc_attr($template['plan']); ?>">
                    <img class="kng-addons-template-thumbnail" loading="lazy"
                        src="<?php echo esc_url("https://thumbnails.kingaddons.com/$attr_key.png?v=4"); ?>"
                        alt="<?php echo esc_attr($template['title']); ?>">
                    <h3><?php echo esc_html($template['title']); ?></h3>
                    <div class="template-plan template-plan-<?php echo esc_html($template['plan']); ?>"><?php echo esc_html($template['plan']); ?></div>
                </div>
                <?php
            }
        }
        $templates_html = ob_get_clean();

        // Render Sections HTML
        ob_start();
        if (empty($collection_sections)) {
            echo '<p class="sections-not-found">' . esc_html__('No sections found in this collection.', 'king-addons') . '</p>';
        } else {
            foreach ($collection_sections as $section) {
                $section_key = $section['section_key'];
                $plan = $section['plan'];
                $title = $section['title'];
                $thumb_url = "https://thumbnails.kingaddons.com/sections/$plan/$section_key.png";
                ?>
                <div class="section-item"
                     data-section-key="<?php echo esc_attr($section_key); ?>"
                     data-section-plan="<?php echo esc_attr($plan); ?>">
                    <div class="section-preview">
                        <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                        <div class="section-actions">
                            <button class="import-section-btn" data-section-key="<?php echo esc_attr($section_key); ?>" data-section-plan="<?php echo esc_attr($plan); ?>">
                                <i class="eicon-file-download"></i> <?php esc_html_e('Import', 'king-addons'); ?>
                            </button>
                            <a href="<?php echo esc_url('https://sections.kingaddons.com/' . $section_key); ?>" class="live-preview-btn" target="_blank">
                                <i class="eicon-preview-medium"></i> <?php esc_html_e('Live Preview', 'king-addons'); ?>
                            </a>
                        </div>
                        <?php if ($plan === 'premium'): ?>
                            <span class="section-badge-pro">PRO</span>
                        <?php endif; ?>
                    </div>
                    <div class="section-info">
                        <h4><?php echo esc_html($title); ?></h4>
                    </div>
                </div>
                <?php
            }
        }
        $sections_html = ob_get_clean();

        wp_send_json_success([
            'templates_html' => $templates_html,
            'sections_html' => $sections_html,
            'templates_count' => count($collection_templates),
            'sections_count' => count($collection_sections)
        ]);
    }

    public function handle_get_collections_rows()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kingAddonsNonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $per_page = 10; // Number of collections per page

        $templates = TemplatesMap::getTemplatesMapArray();
        $collections_map = CollectionsMap::getCollectionsMapArray();

        // Load Sections to compute section counts per collection
        if (!class_exists('King_Addons\\SectionsMap')) {
            require_once KING_ADDONS_PATH . 'includes/SectionsMap.php';
        }
        $sections_map = SectionsMap::getSectionsMapArray();
        $sections = $sections_map['sections'] ?? [];

        // Build templates grouped by collection + counts
        // Search behavior mirrors template search: title + tags (and direct key).
        $search_terms = array_values(array_filter(preg_split('/\s+/', (string) $search)));
        $has_search = !empty($search_terms);

        $templates_by_collection = [];
        $templates_by_collection_title = [];
        $templates_by_collection_tags = [];

        $template_counts = [];
        $template_key_to_collection = [];
        $template_matches_search = [];
        $collections_matching_by_name = [];

        $contains_ci = static function ($haystack, $needle): bool {
            if ($needle === '') {
                return true;
            }
            if (function_exists('mb_stripos')) {
                return mb_stripos((string) $haystack, (string) $needle) !== false;
            }
            return stripos((string) $haystack, (string) $needle) !== false;
        };

        if ($has_search) {
            foreach ($collections_map as $collection_id => $collection_name) {
                foreach ($search_terms as $term) {
                    if ($term !== '' && $contains_ci($collection_name, $term)) {
                        $collections_matching_by_name[$collection_id] = true;
                        break;
                    }
                }
            }
        }

        foreach (($templates['templates'] ?? []) as $key => $template) {
            $collection_id = $template['collection'] ?? '';
            if ($collection_id === '' || !isset($collections_map[$collection_id])) {
                continue;
            }

            $template_key_to_collection[$key] = $collection_id;

            if (!isset($template_counts[$collection_id])) {
                $template_counts[$collection_id] = 0;
            }
            $template_counts[$collection_id]++;

            $title = (string) ($template['title'] ?? '');
            $tags = is_array($template['tags'] ?? null) ? $template['tags'] : [];
            $matches_by_title = false;
            $matches_by_tags = false;
            $matches_by_key = ($search !== '' && $key === $search);

            if ($has_search) {
                foreach ($search_terms as $term) {
                    if ($term === '') {
                        continue;
                    }

                    if ($contains_ci($title, $term)) {
                        $matches_by_title = true;
                        continue;
                    }

                    if ($contains_ci($key, $term)) {
                        $matches_by_key = true;
                        continue;
                    }

                    foreach ($tags as $tag) {
                        if ($contains_ci((string) $tag, $term)) {
                            $matches_by_tags = true;
                            break;
                        }
                    }
                }
            }

            $collection_name_match = $has_search && isset($collections_matching_by_name[$collection_id]);
            $template_is_relevant = !$has_search || $collection_name_match || $matches_by_key || $matches_by_title || $matches_by_tags;
            $template_matches_search[$key] = (bool) ($matches_by_key || $matches_by_title || $matches_by_tags);

            if (!$template_is_relevant) {
                continue;
            }

            $item = [
                'key' => $key,
                'title' => $title,
                'plan' => $template['plan'],
                'category' => $template['category'],
            ];

            if (!isset($templates_by_collection_title[$collection_id])) {
                $templates_by_collection_title[$collection_id] = [];
            }
            if (!isset($templates_by_collection_tags[$collection_id])) {
                $templates_by_collection_tags[$collection_id] = [];
            }

            // No search: keep natural order (default Collections landing should not be empty).
            if (!$has_search) {
                if (!isset($templates_by_collection[$collection_id])) {
                    $templates_by_collection[$collection_id] = [];
                }
                $templates_by_collection[$collection_id][] = $item;
                continue;
            }

            // If the collection name matches, keep natural order; otherwise prioritize title matches.
            if ($collection_name_match) {
                if (!isset($templates_by_collection[$collection_id])) {
                    $templates_by_collection[$collection_id] = [];
                }
                $templates_by_collection[$collection_id][] = $item;
            } else {
                if ($matches_by_key || $matches_by_title) {
                    $templates_by_collection_title[$collection_id][] = $item;
                } elseif ($matches_by_tags) {
                    $templates_by_collection_tags[$collection_id][] = $item;
                }
            }
        }

        // Merge per-collection buckets and apply soft limit
        $collection_ids_all = array_keys($template_counts);
        foreach ($collection_ids_all as $collection_id) {
            if (!isset($templates_by_collection[$collection_id])) {
                $templates_by_collection[$collection_id] = [];
            }

            if ($has_search && !isset($collections_matching_by_name[$collection_id])) {
                $merged = array_merge(
                    $templates_by_collection_title[$collection_id] ?? [],
                    $templates_by_collection_tags[$collection_id] ?? []
                );
                $templates_by_collection[$collection_id] = $merged;
            }

            if (count($templates_by_collection[$collection_id]) > 20) {
                $templates_by_collection[$collection_id] = array_slice($templates_by_collection[$collection_id], 0, 20);
            }
        }

        // Compute sections count per collection using parent_template -> template -> collection mapping.
        // When searching, count only sections whose parent template matches search (unless collection name matches).
        $section_counts = [];
        foreach ($sections as $section) {
            $parent = $section['parent_template'] ?? '';
            if (!$parent || !isset($template_key_to_collection[$parent])) {
                continue;
            }

            $collection_id = $template_key_to_collection[$parent];
            $collection_name_match = $has_search && isset($collections_matching_by_name[$collection_id]);
            if ($has_search && !$collection_name_match) {
                if (!isset($template_matches_search[$parent]) || !$template_matches_search[$parent]) {
                    continue;
                }
            }

            if (!isset($section_counts[$collection_id])) {
                $section_counts[$collection_id] = 0;
            }
            $section_counts[$collection_id]++;
        }

        // Only collections that actually have templates in the (possibly filtered) result
        $collection_ids = array_keys(array_filter($templates_by_collection, static function ($items) {
            return !empty($items);
        }));

        // Sort collections by name to match the main Collections view
        usort($collection_ids, function ($a, $b) use ($collections_map) {
            return strcasecmp((string) ($collections_map[$a] ?? ''), (string) ($collections_map[$b] ?? ''));
        });

        $total_collections = count($collection_ids);
        $total_pages = (int) ceil($total_collections / $per_page);
        $page = min($page, max(1, $total_pages));
        $offset = ($page - 1) * $per_page;
        $paged_collection_ids = array_slice($collection_ids, $offset, $per_page);

        $result_collections = [];
        foreach ($paged_collection_ids as $id) {
            $result_collections[] = [
                'id' => $id,
                'name' => $collections_map[$id],
                'templates' => $templates_by_collection[$id],
                'templates_count' => (int) ($template_counts[$id] ?? 0),
                'sections_count' => (int) ($section_counts[$id] ?? 0)
            ];
        }

        wp_send_json_success([
            'collections' => $result_collections,
            'pagination' => [
                'total' => $total_collections,
                'pages' => $total_pages,
                'current' => $page
            ]
        ]);
    }
}

Templates::instance();
