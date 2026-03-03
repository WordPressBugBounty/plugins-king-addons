<?php
/**
 * Template: Taxonomy Archive (category & tag pages)
 *
 * Apple Liquid-Glass design – category header, subcategories, articles list, pagination.
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$ext     = \King_Addons\Docs_KB::instance();
$options = $ext->get_options();
$mode    = $options['dark_mode'] ?? 'auto';
$primary = $options['primary_color'] ?? '#0071e3';

$term      = get_queried_object();
$is_tag    = is_tax('kng_doc_tag');
$icon_name = get_term_meta($term->term_id, 'kng_doc_cat_icon', true) ?: 'folder';

/* ── Subcategories ── */
$children = [];
if (!$is_tag) {
    $children = get_terms([
        'taxonomy'   => 'kng_doc_category',
        'parent'     => $term->term_id,
        'hide_empty' => true,
    ]);
    if (is_wp_error($children)) $children = [];
}

get_header();
?>

<div class="kng-docs-page"
     data-kng-theme-mode="<?php echo esc_attr($mode); ?>"
     style="--kng-docs-primary:<?php echo esc_attr($primary); ?>; --kng-docs-icon-color:<?php echo esc_attr($primary); ?>;">

    <div class="kng-docs-container">

        <!-- ══════════════════════════════════════
             BREADCRUMBS
             ══════════════════════════════════════ -->
        <nav class="kng-docs-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'king-addons'); ?>">
            <a href="<?php echo esc_url(get_post_type_archive_link('kng_doc')); ?>" class="kng-docs-breadcrumb-item">
                <?php echo $ext->get_icon_svg('home'); ?>
                <?php esc_html_e('Docs', 'king-addons'); ?>
            </a>

            <?php
            if (!$is_tag && $term->parent) {
                $ancestors = get_ancestors($term->term_id, 'kng_doc_category', 'taxonomy');
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $anc_id) {
                    $anc = get_term($anc_id, 'kng_doc_category');
                    if ($anc && !is_wp_error($anc)) {
                        echo '<span class="kng-docs-breadcrumb-sep">/</span>';
                        echo '<a href="' . esc_url(get_term_link($anc)) . '" class="kng-docs-breadcrumb-item">' . esc_html($anc->name) . '</a>';
                    }
                }
            }
            ?>

            <span class="kng-docs-breadcrumb-sep">/</span>
            <span class="kng-docs-breadcrumb-current"><?php echo esc_html($term->name); ?></span>
        </nav>

        <!-- ══════════════════════════════════════
             CATEGORY / TAG HEADER
             ══════════════════════════════════════ -->
        <div class="kng-docs-category-header-section kng-docs-reveal">
            <div class="kng-docs-category-icon-large">
                <?php echo $ext->get_icon_svg($icon_name); ?>
            </div>
            <h1 class="kng-docs-category-page-title"><?php echo esc_html($term->name); ?></h1>
            <?php if ($term->description) : ?>
                <p class="kng-docs-category-page-desc"><?php echo esc_html($term->description); ?></p>
            <?php endif; ?>
            <p class="kng-docs-category-page-count">
                <?php printf(
                    esc_html(_n('%d article', '%d articles', $term->count, 'king-addons')),
                    $term->count
                ); ?>
            </p>
        </div>

        <!-- ══════════════════════════════════════
             SUBCATEGORIES
             ══════════════════════════════════════ -->
        <?php if (!empty($children)) : ?>
            <div class="kng-docs-subcategories kng-docs-reveal">
                <h2 class="kng-docs-subcategories-title"><?php esc_html_e('Subcategories', 'king-addons'); ?></h2>
                <div class="kng-docs-subcategories-grid">
                    <?php foreach ($children as $child) :
                        $child_icon = get_term_meta($child->term_id, 'kng_doc_cat_icon', true) ?: 'folder';
                        ?>
                        <a href="<?php echo esc_url(get_term_link($child)); ?>" class="kng-docs-subcategory-card">
                            <span class="kng-docs-subcategory-icon">
                                <?php echo $ext->get_icon_svg($child_icon); ?>
                            </span>
                            <span class="kng-docs-subcategory-content">
                                <span class="kng-docs-subcategory-name"><?php echo esc_html($child->name); ?></span>
                                <span class="kng-docs-subcategory-count">
                                    <?php printf(
                                        esc_html(_n('%d article', '%d articles', $child->count, 'king-addons')),
                                        $child->count
                                    ); ?>
                                </span>
                            </span>
                            <svg class="kng-docs-subcategory-arrow" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════
             ARTICLES LIST
             ══════════════════════════════════════ -->
        <?php if (have_posts()) : ?>
            <div class="kng-docs-reveal">
                <h2 class="kng-docs-articles-title"><?php esc_html_e('Articles', 'king-addons'); ?></h2>
                <ul class="kng-docs-articles">
                    <?php while (have_posts()) : the_post(); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>" class="kng-docs-article-link">
                                <svg class="kng-docs-article-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                    <path d="M14 2v6h6"/>
                                    <path d="M16 13H8"/>
                                    <path d="M16 17H8"/>
                                    <path d="M10 9H8"/>
                                </svg>
                                <span class="kng-docs-article-info">
                                    <span class="kng-docs-article-item-title"><?php the_title(); ?></span>
                                    <?php if (has_excerpt()) : ?>
                                        <span class="kng-docs-article-excerpt"><?php echo esc_html(get_the_excerpt()); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="kng-docs-article-date">
                                    <?php echo get_the_modified_date(); ?>
                                </span>
                                <svg class="kng-docs-article-arrow" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Pagination -->
            <div class="kng-docs-pagination">
                <?php
                the_posts_pagination([
                    'mid_size'  => 2,
                    'prev_text' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>',
                    'next_text' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>',
                ]);
                ?>
            </div>
        <?php else : ?>
            <div class="kng-docs-empty kng-docs-reveal">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3><?php esc_html_e('No articles yet', 'king-addons'); ?></h3>
                <p><?php esc_html_e('There are no articles in this category yet.', 'king-addons'); ?></p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
