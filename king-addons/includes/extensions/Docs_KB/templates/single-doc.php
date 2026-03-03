<?php
/**
 * Template: Single Doc Article
 *
 * Apple Liquid-Glass design – breadcrumbs, sidebar, sticky TOC, content,
 * reactions, related articles, prev/next navigation.
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$ext     = \King_Addons\Docs_KB::instance();
$options = $ext->get_options();
$mode    = $options['dark_mode'] ?? 'auto';
$primary = $options['primary_color'] ?? '#0071e3';

$show_toc      = !empty($options['toc_enabled']);
$show_reading  = !empty($options['reading_time']);
$show_share    = !empty($options['social_share']);
$show_reactions = !empty($options['reactions_enabled']);

/* ── Current article ── */
$post_id = get_the_ID();
$terms   = get_the_terms($post_id, 'kng_doc_category');
$cat     = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
$tags    = get_the_terms($post_id, 'kng_doc_tag');

/* ── Sidebar: articles in same category ── */
$sidebar_posts = [];
if ($cat) {
    $sidebar_posts = get_posts([
        'post_type'      => 'kng_doc',
        'posts_per_page' => 40,
        'tax_query'      => [[
            'taxonomy' => 'kng_doc_category',
            'terms'    => $cat->term_id,
        ]],
        'orderby' => 'menu_order',
        'order'   => 'ASC',
    ]);
}

/* ── Meta ── */
$views        = (int) get_post_meta($post_id, '_kng_doc_views', true);
$reading_time = $ext::estimate_reading_time(get_the_content());

/* ── Related ── */
$related = $ext->get_related_docs($post_id, 3);

/* ── Prev / Next ── */
$nav = $ext->get_article_navigation($post_id);

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

            <?php if ($cat) : ?>
                <span class="kng-docs-breadcrumb-sep">/</span>
                <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="kng-docs-breadcrumb-item">
                    <?php echo esc_html($cat->name); ?>
                </a>
            <?php endif; ?>

            <span class="kng-docs-breadcrumb-sep">/</span>
            <span class="kng-docs-breadcrumb-current"><?php the_title(); ?></span>
        </nav>

        <!-- ══════════════════════════════════════
             3-COLUMN LAYOUT
             ══════════════════════════════════════ -->
        <div class="kng-docs-single-layout <?php echo !empty($sidebar_posts) ? 'has-sidebar' : ''; ?> <?php echo $show_toc ? 'has-toc-aside' : ''; ?>">

            <!-- ── Sidebar ── -->
            <?php if (!empty($sidebar_posts)) : ?>
                <aside class="kng-docs-sidebar is-sticky">
                    <div class="kng-docs-sidebar-inner">
                        <?php if ($cat) : ?>
                            <h4 class="kng-docs-sidebar-title"><?php echo esc_html($cat->name); ?></h4>
                        <?php endif; ?>

                        <ul class="kng-docs-sidebar-list">
                            <?php foreach ($sidebar_posts as $sp) : ?>
                                <li class="<?php echo $sp->ID === $post_id ? 'is-current' : ''; ?>">
                                    <a href="<?php echo get_permalink($sp); ?>">
                                        <?php echo esc_html($sp->post_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; wp_reset_postdata(); ?>
                        </ul>
                    </div>
                </aside>
            <?php endif; ?>

            <!-- ── Main Article ── -->
            <article class="kng-docs-article">

                <!-- Actions bar -->
                <div class="kng-docs-article-actions">
                    <button class="kng-docs-action-btn" onclick="window.print()" title="<?php esc_attr_e('Print', 'king-addons'); ?>">
                        <?php echo $ext->get_icon_svg('printer'); ?>
                    </button>
                </div>

                <!-- Header -->
                <header class="kng-docs-article-header kng-docs-reveal">
                    <h1 class="kng-docs-article-title"><?php the_title(); ?></h1>

                    <div class="kng-docs-article-meta">
                        <span>
                            <?php echo $ext->get_icon_svg('calendar'); ?>
                            <?php echo get_the_modified_date(); ?>
                        </span>

                        <?php if ($show_reading && $reading_time) : ?>
                            <span>
                                <?php echo $ext->get_icon_svg('clock'); ?>
                                <?php echo esc_html($reading_time); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($views) : ?>
                            <span>
                                <?php echo $ext->get_icon_svg('eye'); ?>
                                <?php printf(
                                    esc_html(_n('%d view', '%d views', $views, 'king-addons')),
                                    $views
                                ); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($tags && !is_wp_error($tags)) : ?>
                        <div class="kng-docs-article-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="kng-docs-tag">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </header>

                <!-- TOC (inline, above content) -->
                <?php if ($show_toc) : ?>
                    <div class="kng-docs-toc kng-docs-reveal">
                        <h4 class="kng-docs-toc-title">
                            <?php echo $ext->get_icon_svg('list'); ?>
                            <?php esc_html_e('Table of Contents', 'king-addons'); ?>
                        </h4>
                        <ul class="kng-docs-toc-list">
                            <!-- populated by JS -->
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="kng-docs-article-content kng-docs-reveal">
                    <?php the_content(); ?>
                </div>

                <!-- Social Share -->
                <?php if ($show_share) : ?>
                    <div class="kng-docs-share">
                        <span class="kng-docs-share-label"><?php esc_html_e('Share', 'king-addons'); ?></span>

                        <button class="kng-docs-share-btn" data-share="copy" title="<?php esc_attr_e('Copy link', 'king-addons'); ?>">
                            <?php echo $ext->get_icon_svg('link'); ?>
                        </button>
                        <button class="kng-docs-share-btn" data-share="twitter" title="Twitter">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </button>
                        <button class="kng-docs-share-btn" data-share="facebook" title="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </button>
                        <button class="kng-docs-share-btn" data-share="linkedin" title="LinkedIn">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Reactions -->
                <?php if ($show_reactions) : ?>
                    <div class="kng-docs-reactions" data-post-id="<?php echo esc_attr($post_id); ?>">
                        <p class="kng-docs-reactions-question">
                            <?php esc_html_e('Was this article helpful?', 'king-addons'); ?>
                        </p>
                        <div class="kng-docs-reactions-buttons">
                            <button class="kng-docs-reaction-btn" data-reaction="happy">
                                <span class="kng-docs-reaction-emoji">😊</span>
                                <?php esc_html_e('Helpful', 'king-addons'); ?>
                            </button>
                            <button class="kng-docs-reaction-btn" data-reaction="neutral">
                                <span class="kng-docs-reaction-emoji">😐</span>
                                <?php esc_html_e('Somewhat', 'king-addons'); ?>
                            </button>
                            <button class="kng-docs-reaction-btn" data-reaction="sad">
                                <span class="kng-docs-reaction-emoji">😞</span>
                                <?php esc_html_e('Not helpful', 'king-addons'); ?>
                            </button>
                        </div>
                        <span class="kng-docs-reactions-thanks">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?php esc_html_e('Thank you for your feedback!', 'king-addons'); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Related articles -->
                <?php if (!empty($related)) : ?>
                    <div class="kng-docs-related kng-docs-reveal">
                        <h3 class="kng-docs-related-title"><?php esc_html_e('Related Articles', 'king-addons'); ?></h3>
                        <div class="kng-docs-related-grid">
                            <?php foreach ($related as $rel) : ?>
                                <a href="<?php echo esc_url($rel['url']); ?>" class="kng-docs-related-item">
                                    <?php echo esc_html($rel['title']); ?>
                                    <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Prev / Next -->
                <?php if ($nav['prev'] || $nav['next']) : ?>
                    <nav class="kng-docs-navigation kng-docs-reveal">
                        <?php if ($nav['prev']) : ?>
                            <a href="<?php echo esc_url($nav['prev']['url']); ?>" class="kng-docs-nav-item kng-docs-nav-prev">
                                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
                                <span>
                                    <span class="kng-docs-nav-label"><?php esc_html_e('Previous', 'king-addons'); ?></span>
                                    <span class="kng-docs-nav-title"><?php echo esc_html($nav['prev']['title']); ?></span>
                                </span>
                            </a>
                        <?php else : ?>
                            <span></span>
                        <?php endif; ?>

                        <?php if ($nav['next']) : ?>
                            <a href="<?php echo esc_url($nav['next']['url']); ?>" class="kng-docs-nav-item kng-docs-nav-next">
                                <span>
                                    <span class="kng-docs-nav-label"><?php esc_html_e('Next', 'king-addons'); ?></span>
                                    <span class="kng-docs-nav-title"><?php echo esc_html($nav['next']['title']); ?></span>
                                </span>
                                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            </article>

            <!-- ── Floating TOC aside (right column) ── -->
            <?php if ($show_toc) : ?>
                <aside class="kng-docs-toc-floating is-sticky">
                    <div class="kng-docs-toc-floating-inner">
                        <h4 class="kng-docs-toc-title">
                            <?php esc_html_e('On this page', 'king-addons'); ?>
                        </h4>
                        <ul class="kng-docs-toc-list kng-docs-toc-floating-list">
                            <!-- populated by JS -->
                        </ul>
                    </div>
                </aside>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php get_footer(); ?>
