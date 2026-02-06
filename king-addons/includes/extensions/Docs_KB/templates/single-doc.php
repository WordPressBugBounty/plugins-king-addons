<?php
/**
 * Single Doc Template
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = \King_Addons\Docs_KB::instance()->get_options();
$docs_kb = \King_Addons\Docs_KB::instance();

// Get current doc
$doc_id = get_the_ID();

// Track view (Pro)
if (!empty($options['analytics_enabled'])) {
    $docs_kb->track_view($doc_id);
}

// Get category
$categories = get_the_terms($doc_id, \King_Addons\Docs_KB::TAXONOMY);
$current_category = !empty($categories) ? $categories[0] : null;

// Get navigation
$navigation = !empty($options['navigation_enabled']) ? $docs_kb->get_article_navigation($doc_id) : null;

// Get related docs (Pro)
$related_docs = !empty($options['related_enabled']) ? $docs_kb->get_related_docs($doc_id, $options['related_count'] ?? 3) : [];

// Settings
$toc_enabled = !empty($options['toc_enabled']);
$sidebar_enabled = !empty($options['sidebar_enabled']);
$print_button = !empty($options['print_button']);
$feedback_enabled = !empty($options['feedback_enabled']);
$primary_color = $options['primary_color'] ?? '#0066ff';

get_header();
?>

<div class="kng-docs-page kng-docs-single" style="--kng-docs-primary: <?php echo esc_attr($primary_color); ?>;">
    <div class="kng-docs-container">
        
        <!-- Breadcrumbs -->
        <nav class="kng-docs-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'king-addons'); ?>">
            <a href="<?php echo esc_url(get_post_type_archive_link(\King_Addons\Docs_KB::POST_TYPE)); ?>" class="kng-docs-breadcrumb-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                <?php esc_html_e('Docs', 'king-addons'); ?>
            </a>
            <?php if ($current_category): ?>
            <span class="kng-docs-breadcrumb-sep">/</span>
            <a href="<?php echo esc_url(get_term_link($current_category)); ?>" class="kng-docs-breadcrumb-item">
                <?php echo esc_html($current_category->name); ?>
            </a>
            <?php endif; ?>
            <span class="kng-docs-breadcrumb-sep">/</span>
            <span class="kng-docs-breadcrumb-current"><?php the_title(); ?></span>
        </nav>

        <div class="kng-docs-single-layout <?php echo $sidebar_enabled ? 'has-sidebar' : ''; ?>">
            
            <?php if ($sidebar_enabled): ?>
            <!-- Sidebar -->
            <aside class="kng-docs-sidebar <?php echo !empty($options['toc_sticky']) ? 'is-sticky' : ''; ?>">
                <div class="kng-docs-sidebar-inner">
                    <?php if ($current_category): ?>
                    <h4 class="kng-docs-sidebar-title">
                        <?php echo esc_html($current_category->name); ?>
                    </h4>
                    <?php
                    $category_docs = get_posts([
                        'post_type' => \King_Addons\Docs_KB::POST_TYPE,
                        'posts_per_page' => -1,
                        'tax_query' => [
                            [
                                'taxonomy' => \King_Addons\Docs_KB::TAXONOMY,
                                'field' => 'term_id',
                                'terms' => $current_category->term_id,
                            ],
                        ],
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                    ]);
                    ?>
                    <ul class="kng-docs-sidebar-list">
                        <?php foreach ($category_docs as $cat_doc): ?>
                        <li class="<?php echo $cat_doc->ID === $doc_id ? 'is-current' : ''; ?>">
                            <a href="<?php echo esc_url(get_permalink($cat_doc)); ?>">
                                <?php echo esc_html($cat_doc->post_title); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <!-- No category - show all categories -->
                    <h4 class="kng-docs-sidebar-title">
                        <?php esc_html_e('Categories', 'king-addons'); ?>
                    </h4>
                    <?php
                    $all_categories = get_terms([
                        'taxonomy' => \King_Addons\Docs_KB::TAXONOMY,
                        'hide_empty' => true,
                        'parent' => 0,
                    ]);
                    ?>
                    <?php if (!empty($all_categories) && !is_wp_error($all_categories)): ?>
                    <ul class="kng-docs-sidebar-list">
                        <?php foreach ($all_categories as $cat): ?>
                        <li>
                            <a href="<?php echo esc_url(get_term_link($cat)); ?>">
                                <?php echo esc_html($cat->name); ?>
                                <span class="kng-docs-sidebar-count"><?php echo esc_html($cat->count); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </aside>
            <?php endif; ?>

            <!-- Main Content -->
            <article class="kng-docs-article">
                <?php if ($print_button): ?>
                <div class="kng-docs-article-actions">
                    <button type="button" class="kng-docs-print-btn" onclick="window.print();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 9V2h12v7"/>
                            <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        <?php esc_html_e('Print', 'king-addons'); ?>
                    </button>
                </div>
                <?php endif; ?>

                <header class="kng-docs-article-header">
                    <h1 class="kng-docs-article-title"><?php the_title(); ?></h1>
                    <div class="kng-docs-article-meta">
                        <span class="kng-docs-article-date">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <?php echo get_the_modified_date(); ?>
                        </span>
                        <?php 
                        $reading_time = ceil(str_word_count(strip_tags(get_the_content())) / 200);
                        ?>
                        <span class="kng-docs-article-reading">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <?php printf(esc_html(_n('%d min read', '%d min read', $reading_time, 'king-addons')), $reading_time); ?>
                        </span>
                    </div>
                </header>

                <?php if ($toc_enabled): ?>
                <!-- Table of Contents (generated by JS) -->
                <nav class="kng-docs-toc" id="kng-docs-toc" style="display: none;">
                    <h4 class="kng-docs-toc-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                        <?php esc_html_e('On this page', 'king-addons'); ?>
                    </h4>
                    <ul class="kng-docs-toc-list"></ul>
                </nav>
                <?php endif; ?>

                <div class="kng-docs-article-content" data-toc-headings="<?php echo esc_attr($options['toc_headings'] ?? 'h2,h3'); ?>">
                    <?php the_content(); ?>
                </div>

                <?php if ($feedback_enabled): ?>
                <!-- Feedback Section -->
                <div class="kng-docs-feedback" data-doc-id="<?php echo esc_attr($doc_id); ?>">
                    <p class="kng-docs-feedback-question">
                        <?php echo esc_html($options['feedback_question'] ?? __('Was this article helpful?', 'king-addons')); ?>
                    </p>
                    <div class="kng-docs-feedback-buttons">
                        <button type="button" class="kng-docs-feedback-btn kng-docs-feedback-yes" data-value="helpful">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/>
                            </svg>
                            <?php esc_html_e('Yes', 'king-addons'); ?>
                        </button>
                        <button type="button" class="kng-docs-feedback-btn kng-docs-feedback-no" data-value="not_helpful">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3zm7-13h2.67A2.31 2.31 0 0122 4v7a2.31 2.31 0 01-2.33 2H17"/>
                            </svg>
                            <?php esc_html_e('No', 'king-addons'); ?>
                        </button>
                    </div>
                    <div class="kng-docs-feedback-thanks" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <?php esc_html_e('Thank you for your feedback!', 'king-addons'); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($related_docs)): ?>
                <!-- Related Articles -->
                <div class="kng-docs-related">
                    <h3 class="kng-docs-related-title"><?php esc_html_e('Related Articles', 'king-addons'); ?></h3>
                    <div class="kng-docs-related-grid">
                        <?php foreach ($related_docs as $related): ?>
                        <a href="<?php echo esc_url(get_permalink($related)); ?>" class="kng-docs-related-item">
                            <span class="kng-docs-related-item-title"><?php echo esc_html($related->post_title); ?></span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($navigation)): ?>
                <!-- Article Navigation -->
                <nav class="kng-docs-navigation">
                    <?php if (!empty($navigation['prev'])): ?>
                    <a href="<?php echo esc_url(get_permalink($navigation['prev'])); ?>" class="kng-docs-nav-item kng-docs-nav-prev">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                        <span>
                            <span class="kng-docs-nav-label"><?php esc_html_e('Previous', 'king-addons'); ?></span>
                            <span class="kng-docs-nav-title"><?php echo esc_html($navigation['prev']->post_title); ?></span>
                        </span>
                    </a>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($navigation['next'])): ?>
                    <a href="<?php echo esc_url(get_permalink($navigation['next'])); ?>" class="kng-docs-nav-item kng-docs-nav-next">
                        <span>
                            <span class="kng-docs-nav-label"><?php esc_html_e('Next', 'king-addons'); ?></span>
                            <span class="kng-docs-nav-title"><?php echo esc_html($navigation['next']->post_title); ?></span>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </article>

            <?php if ($toc_enabled && !$sidebar_enabled && !empty($options['toc_sticky'])): ?>
            <!-- Floating TOC (when no sidebar) -->
            <aside class="kng-docs-toc-floating is-sticky">
                <div class="kng-docs-toc-floating-inner">
                    <h4 class="kng-docs-toc-title"><?php esc_html_e('On this page', 'king-addons'); ?></h4>
                    <ul class="kng-docs-toc-list kng-docs-toc-floating-list"></ul>
                </div>
            </aside>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    // TOC generation
    var content = document.querySelector('.kng-docs-article-content');
    var toc = document.getElementById('kng-docs-toc');
    var floatingToc = document.querySelector('.kng-docs-toc-floating-list');
    
    if (content && (toc || floatingToc)) {
        var headings = content.dataset.tocHeadings || 'h2,h3';
        var elements = content.querySelectorAll(headings);
        
        if (elements.length > 0) {
            var list = '';
            elements.forEach(function(el, index) {
                var id = 'toc-' + index;
                el.id = id;
                var level = el.tagName.toLowerCase();
                list += '<li class="kng-docs-toc-item kng-docs-toc-' + level + '">';
                list += '<a href="#' + id + '">' + el.textContent + '</a>';
                list += '</li>';
            });
            
            if (toc) {
                toc.querySelector('.kng-docs-toc-list').innerHTML = list;
                toc.style.display = '';
            }
            if (floatingToc) {
                floatingToc.innerHTML = list;
            }
        }
    }
    
    // Smooth scroll
    document.querySelectorAll('.kng-docs-toc-list a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // Feedback
    var feedback = document.querySelector('.kng-docs-feedback');
    if (feedback) {
        feedback.querySelectorAll('.kng-docs-feedback-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var docId = feedback.dataset.docId;
                var value = this.dataset.value;
                
                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=king_addons_docs_feedback&doc_id=' + docId + '&feedback=' + value + '&nonce=<?php echo wp_create_nonce('king_addons_docs_feedback'); ?>'
                });
                
                feedback.querySelector('.kng-docs-feedback-buttons').style.display = 'none';
                feedback.querySelector('.kng-docs-feedback-question').style.display = 'none';
                feedback.querySelector('.kng-docs-feedback-thanks').style.display = '';
            });
        });
    }
})();
</script>

<?php get_footer(); ?>
