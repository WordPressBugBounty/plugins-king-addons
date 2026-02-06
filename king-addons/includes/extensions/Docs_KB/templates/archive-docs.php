<?php
/**
 * Docs Archive Template
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$docs_kb = \King_Addons\Docs_KB::instance();
$options = $docs_kb->get_options();
$layout = $options['layout'] ?? 'card';
$columns = $options['columns'] ?? 3;
$search_enabled = !empty($options['search_enabled']);
$primary_color = $options['primary_color'] ?? '#0066ff';

// Get categories with docs
$categories = $docs_kb->get_categories_with_docs();

get_header();
?>

<div class="kng-docs-page kng-docs-archive" data-layout="<?php echo esc_attr($layout); ?>">
    <div class="kng-docs-container">
        
        <!-- Hero Section -->
        <div class="kng-docs-hero">
            <h1 class="kng-docs-hero-title"><?php echo esc_html($options['archive_title'] ?? __('Documentation', 'king-addons')); ?></h1>
            <p class="kng-docs-hero-subtitle"><?php echo esc_html($options['archive_subtitle'] ?? __('Find answers to your questions', 'king-addons')); ?></p>
            
            <?php if ($search_enabled): ?>
            <div class="kng-docs-search">
                <div class="kng-docs-search-inner">
                    <svg class="kng-docs-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" 
                           class="kng-docs-search-input" 
                           placeholder="<?php echo esc_attr($options['search_placeholder'] ?? __('Search documentation...', 'king-addons')); ?>"
                           data-min-chars="<?php echo esc_attr($options['search_min_chars'] ?? 2); ?>"
                           autocomplete="off">
                    <div class="kng-docs-search-loader" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
                <div class="kng-docs-search-results" style="display: none;"></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($categories)): ?>
        <div class="kng-docs-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
            <h3><?php esc_html_e('No documentation yet', 'king-addons'); ?></h3>
            <p><?php esc_html_e('Start by creating categories and adding articles.', 'king-addons'); ?></p>
        </div>
        <?php else: ?>

        <!-- Categories Grid -->
        <div class="kng-docs-categories kng-docs-layout-<?php echo esc_attr($layout); ?>" style="--kng-docs-columns: <?php echo esc_attr($columns); ?>; --kng-docs-primary: <?php echo esc_attr($primary_color); ?>;">
            <?php foreach ($categories as $category): ?>
                <?php if ($layout === 'box'): ?>
                <!-- Box Layout -->
                <div class="kng-docs-category kng-docs-category-box">
                    <div class="kng-docs-category-header">
                        <?php if (!empty($options['show_category_icon']) && !empty($category['icon'])): ?>
                        <div class="kng-docs-category-icon" style="--icon-color: <?php echo esc_attr($options['category_icon_color'] ?? $primary_color); ?>">
                            <?php echo \King_Addons\Docs_KB::get_icon_svg($category['icon']); ?>
                        </div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($category['url']); ?>" class="kng-docs-category-title">
                            <?php echo esc_html($category['name']); ?>
                        </a>
                        <?php if (!empty($options['show_article_count'])): ?>
                        <span class="kng-docs-category-count"><?php echo esc_html($category['count']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($category['description'])): ?>
                    <p class="kng-docs-category-desc"><?php echo esc_html($category['description']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($category['docs'])): ?>
                    <ul class="kng-docs-category-list">
                        <?php foreach (array_slice($category['docs'], 0, 5) as $doc): ?>
                        <li>
                            <a href="<?php echo esc_url($doc['url']); ?>">
                                <?php echo esc_html($doc['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($category['docs']) > 5): ?>
                    <a href="<?php echo esc_url($category['url']); ?>" class="kng-docs-category-more">
                        <?php printf(esc_html__('View all %d articles', 'king-addons'), $category['count']); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php elseif ($layout === 'card'): ?>
                <!-- Card Layout -->
                <a href="<?php echo esc_url($category['url']); ?>" class="kng-docs-category kng-docs-category-card">
                    <?php if (!empty($options['show_category_icon']) && !empty($category['icon'])): ?>
                    <div class="kng-docs-category-icon" style="--icon-color: <?php echo esc_attr($options['category_icon_color'] ?? $primary_color); ?>">
                        <?php echo \King_Addons\Docs_KB::get_icon_svg($category['icon']); ?>
                    </div>
                    <?php endif; ?>
                    <h3 class="kng-docs-category-title"><?php echo esc_html($category['name']); ?></h3>
                    <?php if (!empty($category['description'])): ?>
                    <p class="kng-docs-category-desc"><?php echo esc_html($category['description']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($options['show_article_count'])): ?>
                    <span class="kng-docs-category-count">
                        <?php printf(esc_html(_n('%d article', '%d articles', $category['count'], 'king-addons')), $category['count']); ?>
                    </span>
                    <?php endif; ?>
                </a>

                <?php else: ?>
                <!-- Modern Layout -->
                <a href="<?php echo esc_url($category['url']); ?>" class="kng-docs-category kng-docs-category-modern">
                    <?php if (!empty($options['show_category_icon']) && !empty($category['icon'])): ?>
                    <div class="kng-docs-category-icon" style="--icon-color: <?php echo esc_attr($options['category_icon_color'] ?? $primary_color); ?>">
                        <?php echo \King_Addons\Docs_KB::get_icon_svg($category['icon']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="kng-docs-category-content">
                        <h3 class="kng-docs-category-title"><?php echo esc_html($category['name']); ?></h3>
                        <?php if (!empty($category['description'])): ?>
                        <p class="kng-docs-category-desc"><?php echo esc_html($category['description']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($options['show_article_count'])): ?>
                        <span class="kng-docs-category-count">
                            <?php printf(esc_html(_n('%d article', '%d articles', $category['count'], 'king-addons')), $category['count']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <svg class="kng-docs-category-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
