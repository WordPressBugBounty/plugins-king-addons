<?php
/**
 * Template: Docs Archive (main landing page)
 *
 * Apple Liquid-Glass design – hero, search capsule, category grid.
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$ext        = \King_Addons\Docs_KB::instance();
$options    = $ext->get_options();
$layout     = $options['layout'] ?? 'glass-card';
$cats       = $ext->get_categories_with_docs(5);
$mode       = $options['dark_mode'] ?? 'auto';
$primary    = $options['primary_color'] ?? '#0071e3';
$icon_color = $options['category_icon_color'] ?? $primary;
$columns    = (int)($options['columns'] ?? 3);

get_header();
?>

<div class="kng-docs-page kng-docs-layout--<?php echo esc_attr($layout); ?>"
     data-kng-theme-mode="<?php echo esc_attr($mode); ?>"
     style="--kng-docs-primary:<?php echo esc_attr($primary); ?>; --kng-docs-icon-color:<?php echo esc_attr($icon_color); ?>; --kng-docs-columns:<?php echo esc_attr($columns); ?>;">

    <!-- ══════════════════════════════════════
         HERO
         ══════════════════════════════════════ -->
    <div class="kng-docs-hero kng-docs-reveal">
        <span class="kng-docs-hero-eyebrow">
            <?php echo $ext->get_icon_svg('book-open'); ?>
            <?php esc_html_e('Knowledge Base', 'king-addons'); ?>
        </span>

        <h1 class="kng-docs-hero-title">
            <?php echo esc_html($options['archive_title'] ?? __('How can we help?', 'king-addons')); ?>
        </h1>

        <p class="kng-docs-hero-subtitle">
            <?php echo esc_html($options['archive_subtitle'] ?? __('Find guides, tutorials and answers to your questions.', 'king-addons')); ?>
        </p>

        <!-- Search capsule -->
        <div class="kng-docs-search">
            <div class="kng-docs-search-inner">
                <svg class="kng-docs-search-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>

                <input type="search"
                       class="kng-docs-search-input"
                      placeholder="<?php echo esc_attr($options['search_placeholder'] ?? __('Search documentation…', 'king-addons')); ?>"
                       autocomplete="off"
                       aria-label="<?php esc_attr_e('Search articles', 'king-addons'); ?>">

                <span class="kng-docs-search-shortcut">
                    <kbd>⌘</kbd><kbd>K</kbd>
                </span>

                <span class="kng-docs-search-loader">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                </span>

                <div class="kng-docs-search-results" role="listbox"></div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         CATEGORIES GRID
         ══════════════════════════════════════ -->
    <div class="kng-docs-container">
        <?php if (!empty($cats)) : ?>
            <div class="kng-docs-categories kng-docs-reveal-stagger">
                <?php foreach ($cats as $cat) :
                    $icon_name = $cat['icon'] ?? 'file-text';
                    $cat_link  = $cat['url'];
                    $cat_desc  = $cat['description'];
                    $count     = $cat['count'];
                    ?>

                    <?php if ($layout === 'glass-card') : ?>
                        <a href="<?php echo esc_url($cat_link); ?>"
                           class="kng-docs-category--glass-card">
                            <span class="kng-docs-category__icon">
                                <?php echo $ext->get_icon_svg($icon_name); ?>
                            </span>
                            <h3 class="kng-docs-category__title"><?php echo esc_html($cat['name']); ?></h3>
                            <?php if ($cat_desc) : ?>
                                <p class="kng-docs-category__desc"><?php echo esc_html($cat_desc); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($cat['docs'])) : ?>
                                <ul class="kng-docs-category__articles" aria-label="<?php esc_attr_e('Articles', 'king-addons'); ?>">
                                    <?php foreach (array_slice($cat['docs'], 0, 3) as $d) : ?>
                                        <li>
                                            <span class="kng-docs-category__article-link">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                                                <?php echo esc_html($d['title']); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <span class="kng-docs-category__count">
                                <?php printf(
                                    esc_html(_n('%d article', '%d articles', $count, 'king-addons')),
                                    $count
                                ); ?>
                            </span>
                            <svg class="kng-docs-category__arrow" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>

                    <?php elseif ($layout === 'glass-list') : ?>
                        <a href="<?php echo esc_url($cat_link); ?>"
                           class="kng-docs-category--glass-list">
                            <span class="kng-docs-category__icon">
                                <?php echo $ext->get_icon_svg($icon_name); ?>
                            </span>
                            <span class="kng-docs-category__content">
                                <h3 class="kng-docs-category__title"><?php echo esc_html($cat['name']); ?></h3>
                                <?php if ($cat_desc) : ?>
                                    <p class="kng-docs-category__desc"><?php echo esc_html($cat_desc); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($cat['docs'])) : ?>
                                    <span class="kng-docs-category__desc" style="margin-top:10px;">
                                        <?php
                                        $titles = array_map(static fn($d) => $d['title'], array_slice($cat['docs'], 0, 3));
                                        echo esc_html(implode(' • ', $titles));
                                        ?>
                                    </span>
                                <?php endif; ?>

                                <span class="kng-docs-category__count">
                                    <?php printf(
                                        esc_html(_n('%d article', '%d articles', $count, 'king-addons')),
                                        $count
                                    ); ?>
                                </span>
                            </span>
                            <svg class="kng-docs-category__arrow-list" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>

                    <?php elseif ($layout === 'glass-grid') : ?>
                        <?php
                        $articles = get_posts([
                            'post_type'      => 'kng_doc',
                            'posts_per_page' => 5,
                            'tax_query'      => [[
                                'taxonomy' => 'kng_doc_category',
                                'terms'    => $cat['id'],
                            ]],
                            'orderby' => 'menu_order',
                            'order'   => 'ASC',
                        ]);
                        ?>
                        <div class="kng-docs-category--glass-grid">
                            <div class="kng-docs-category__header">
                                <span class="kng-docs-category__icon">
                                    <?php echo $ext->get_icon_svg($icon_name); ?>
                                </span>
                                <div class="kng-docs-category__header-text">
                                    <h3 class="kng-docs-category__title"><?php echo esc_html($cat['name']); ?></h3>
                                </div>
                                <span class="kng-docs-category__count-badge">
                                    <?php printf(
                                        esc_html(_n('%d article', '%d articles', $count, 'king-addons')),
                                        $count
                                    ); ?>
                                </span>
                            </div>

                            <?php if ($articles) : ?>
                                <ul class="kng-docs-category__articles">
                                    <?php foreach ($articles as $art) : ?>
                                        <li>
                                            <a href="<?php echo get_permalink($art); ?>" class="kng-docs-category__article-link">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                                                <?php echo esc_html($art->post_title); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; wp_reset_postdata(); ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($count > 5) : ?>
                                <a href="<?php echo esc_url($cat_link); ?>" class="kng-docs-category__more">
                                    <?php esc_html_e('View all', 'king-addons'); ?>
                                    <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <?php
            $latest = get_posts([
                'post_type'      => \King_Addons\Docs_KB::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ]);
            ?>

            <?php if (!empty($latest)) : ?>
                <div class="kng-docs-reveal">
                    <h2 class="kng-docs-articles-title"><?php esc_html_e('Articles', 'king-addons'); ?></h2>
                    <ul class="kng-docs-articles">
                        <?php foreach ($latest as $p) : ?>
                            <li>
                                <a href="<?php echo esc_url(get_permalink($p)); ?>" class="kng-docs-article-link">
                                    <svg class="kng-docs-article-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                        <path d="M14 2v6h6"/>
                                        <path d="M16 13H8"/>
                                        <path d="M16 17H8"/>
                                        <path d="M10 9H8"/>
                                    </svg>
                                    <span class="kng-docs-article-info">
                                        <span class="kng-docs-article-item-title"><?php echo esc_html(get_the_title($p)); ?></span>
                                    </span>
                                    <span class="kng-docs-article-date"><?php echo esc_html(get_the_modified_date('', $p)); ?></span>
                                    <svg class="kng-docs-article-arrow" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </li>
                        <?php endforeach; wp_reset_postdata(); ?>
                    </ul>
                </div>
            <?php else : ?>
                <div class="kng-docs-empty kng-docs-reveal">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h3><?php esc_html_e('No articles yet', 'king-addons'); ?></h3>
                <p><?php esc_html_e('Start by creating categories and articles in the WordPress admin.', 'king-addons'); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
