<?php
/**
 * Docs Category (Taxonomy) Template
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = \King_Addons\Docs_KB::instance()->get_options();
$term = get_queried_object();
$search_enabled = !empty($options['search_enabled']);
$primary_color = $options['primary_color'] ?? '#0066ff';

// Get category meta
$icon = get_term_meta($term->term_id, '_kng_doc_cat_icon', true);

// Get docs in this category
$docs_query = new WP_Query([
    'post_type' => \King_Addons\Docs_KB::POST_TYPE,
    'posts_per_page' => $options['docs_per_page'] ?? 10,
    'paged' => get_query_var('paged') ?: 1,
    'tax_query' => [
        [
            'taxonomy' => \King_Addons\Docs_KB::TAXONOMY,
            'field' => 'term_id',
            'terms' => $term->term_id,
        ],
    ],
    'orderby' => 'menu_order',
    'order' => 'ASC',
]);

// Get subcategories
$subcategories = get_terms([
    'taxonomy' => \King_Addons\Docs_KB::TAXONOMY,
    'parent' => $term->term_id,
    'hide_empty' => false,
    'orderby' => 'meta_value_num',
    'meta_key' => '_kng_doc_cat_order',
]);

get_header();
?>

<div class="kng-docs-page kng-docs-category-page" style="--kng-docs-primary: <?php echo esc_attr($primary_color); ?>;">
    <div class="kng-docs-container">
        
        <!-- Breadcrumbs -->
        <nav class="kng-docs-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'king-addons'); ?>">
            <a href="<?php echo esc_url(get_post_type_archive_link(\King_Addons\Docs_KB::POST_TYPE)); ?>" class="kng-docs-breadcrumb-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                <?php esc_html_e('Docs', 'king-addons'); ?>
            </a>
            <?php
            // Show parent categories if any
            $ancestors = get_ancestors($term->term_id, \King_Addons\Docs_KB::TAXONOMY, 'taxonomy');
            if (!empty($ancestors)) {
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor_id) {
                    $ancestor = get_term($ancestor_id);
                    ?>
                    <span class="kng-docs-breadcrumb-sep">/</span>
                    <a href="<?php echo esc_url(get_term_link($ancestor)); ?>" class="kng-docs-breadcrumb-item">
                        <?php echo esc_html($ancestor->name); ?>
                    </a>
                    <?php
                }
            }
            ?>
            <span class="kng-docs-breadcrumb-sep">/</span>
            <span class="kng-docs-breadcrumb-current"><?php echo esc_html($term->name); ?></span>
        </nav>

        <!-- Category Header -->
        <div class="kng-docs-category-header-section">
            <?php if (!empty($icon)): ?>
            <div class="kng-docs-category-icon-large" style="--icon-color: <?php echo esc_attr($options['category_icon_color'] ?? $primary_color); ?>">
                <?php echo \King_Addons\Docs_KB::get_icon_svg($icon); ?>
            </div>
            <?php endif; ?>
            <h1 class="kng-docs-category-page-title"><?php echo esc_html($term->name); ?></h1>
            <?php if (!empty($term->description)): ?>
            <p class="kng-docs-category-page-desc"><?php echo esc_html($term->description); ?></p>
            <?php endif; ?>
            <span class="kng-docs-category-page-count">
                <?php printf(esc_html(_n('%d article', '%d articles', $term->count, 'king-addons')), $term->count); ?>
            </span>
        </div>

        <?php if ($search_enabled): ?>
        <!-- Search -->
        <div class="kng-docs-search kng-docs-search-category">
            <div class="kng-docs-search-inner">
                <svg class="kng-docs-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" 
                       class="kng-docs-search-input" 
                       placeholder="<?php printf(esc_attr__('Search in %s...', 'king-addons'), $term->name); ?>"
                       data-min-chars="<?php echo esc_attr($options['search_min_chars'] ?? 2); ?>"
                       data-category="<?php echo esc_attr($term->term_id); ?>"
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

        <?php if (!empty($subcategories)): ?>
        <!-- Subcategories -->
        <div class="kng-docs-subcategories">
            <h3 class="kng-docs-subcategories-title"><?php esc_html_e('Subcategories', 'king-addons'); ?></h3>
            <div class="kng-docs-subcategories-grid">
                <?php foreach ($subcategories as $subcat): 
                    $sub_icon = get_term_meta($subcat->term_id, '_kng_doc_cat_icon', true);
                ?>
                <a href="<?php echo esc_url(get_term_link($subcat)); ?>" class="kng-docs-subcategory-card">
                    <?php if (!empty($sub_icon)): ?>
                    <div class="kng-docs-subcategory-icon">
                        <?php echo \King_Addons\Docs_KB::get_icon_svg($sub_icon); ?>
                    </div>
                    <?php endif; ?>
                    <div class="kng-docs-subcategory-content">
                        <span class="kng-docs-subcategory-name"><?php echo esc_html($subcat->name); ?></span>
                        <span class="kng-docs-subcategory-count">
                            <?php printf(esc_html(_n('%d article', '%d articles', $subcat->count, 'king-addons')), $subcat->count); ?>
                        </span>
                    </div>
                    <svg class="kng-docs-subcategory-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($docs_query->have_posts()): ?>
        <!-- Articles List -->
        <div class="kng-docs-articles-list">
            <h3 class="kng-docs-articles-title"><?php esc_html_e('Articles', 'king-addons'); ?></h3>
            <ul class="kng-docs-articles">
                <?php while ($docs_query->have_posts()): $docs_query->the_post(); ?>
                <li class="kng-docs-article-item">
                    <a href="<?php the_permalink(); ?>" class="kng-docs-article-link">
                        <svg class="kng-docs-article-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <div class="kng-docs-article-info">
                            <span class="kng-docs-article-item-title"><?php the_title(); ?></span>
                            <?php if (has_excerpt()): ?>
                            <span class="kng-docs-article-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="kng-docs-article-date"><?php echo get_the_modified_date(); ?></span>
                        <svg class="kng-docs-article-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>

            <?php 
            // Pagination
            $total_pages = $docs_query->max_num_pages;
            if ($total_pages > 1):
                $current_page = max(1, get_query_var('paged'));
            ?>
            <nav class="kng-docs-pagination">
                <?php
                echo paginate_links([
                    'total' => $total_pages,
                    'current' => $current_page,
                    'prev_text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>',
                    'next_text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>',
                    'type' => 'list',
                ]);
                ?>
            </nav>
            <?php endif; ?>
        </div>
        <?php wp_reset_postdata(); ?>
        
        <?php else: ?>
        <div class="kng-docs-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
            <h3><?php esc_html_e('No articles found', 'king-addons'); ?></h3>
            <p><?php esc_html_e('This category doesn\'t have any articles yet.', 'king-addons'); ?></p>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
