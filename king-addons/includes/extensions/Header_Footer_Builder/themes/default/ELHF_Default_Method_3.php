<?php

namespace King_Addons;

/** @noinspection SpellCheckingInspection */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Universal Compatibility Method
 * Combines multiple approaches for maximum theme compatibility:
 * 1. Standard get_header/get_footer hooks
 * 2. wp_body_open for modern themes  
 * 3. Output buffering for themes without proper hooks
 * 4. JavaScript fallback for stubborn themes
 * 5. Auto-detection of common header/footer selectors
 */
final class ELHF_Default_Method_3
{
    private static ?ELHF_Default_Method_3 $instance = null;
    private bool $header_rendered = false;
    private bool $footer_rendered = false;

    public static function instance(): ?ELHF_Default_Method_3
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('wp', [$this, 'doHooks'], 5);
        
        // FSE (Block Theme) support
        add_filter('render_block', [$this, 'filterFseBlocks'], 10, 2);
    }
    
    /**
     * Filter FSE (Full Site Editing / Block Theme) template parts
     * Replaces core/template-part blocks for header/footer with our custom content
     */
    public function filterFseBlocks(string $block_content, array $block): string
    {
        // Only process template-part blocks
        if ($block['blockName'] !== 'core/template-part') {
            return $block_content;
        }
        
        $slug = $block['attrs']['slug'] ?? '';
        $tag_name = $block['attrs']['tagName'] ?? '';
        
        // Check if this is a header template part
        if (Header_Footer_Builder::isHeaderEnabled()) {
            if ($slug === 'header' || $tag_name === 'header' || strpos($slug, 'header') !== false) {
                if (!$this->header_rendered) {
                    $this->header_rendered = true;
                    return $this->getHeaderHtml();
                }
                return ''; // Already rendered, return empty
            }
        }
        
        // Check if this is a footer template part
        if (Header_Footer_Builder::isFooterEnabled()) {
            if ($slug === 'footer' || $tag_name === 'footer' || strpos($slug, 'footer') !== false) {
                if (!$this->footer_rendered) {
                    $this->footer_rendered = true;
                    return $this->getFooterHtml();
                }
                return ''; // Already rendered, return empty
            }
        }
        
        return $block_content;
    }

    public function doHooks(): void
    {
        // Don't run in admin or customizer preview
        if (is_admin() || is_customize_preview()) {
            return;
        }

        if (Header_Footer_Builder::isHeaderEnabled()) {
            // Primary: get_header hook (works on most themes)
            add_action('get_header', [$this, 'captureHeader'], 5);
            
            // Secondary: wp_body_open (modern themes support this)
            add_action('wp_body_open', [$this, 'renderHeaderOnBodyOpen'], 1);
            
            // Tertiary: template_redirect with output buffering
            add_action('template_redirect', [$this, 'startOutputBuffering'], 1);
            
            // Add CSS to hide theme's native headers
            add_action('wp_head', [$this, 'hideNativeHeaders'], 999);
        }

        if (Header_Footer_Builder::isFooterEnabled()) {
            // Primary: get_footer hook
            add_action('get_footer', [$this, 'captureFooter'], 5);
            
            // Secondary: wp_footer hook (near the end)
            add_action('wp_footer', [$this, 'renderFooterOnWpFooter'], 1);
            
            // Add CSS to hide theme's native footers
            add_action('wp_head', [$this, 'hideNativeFooters'], 999);
        }

        // JavaScript fallback for DOM manipulation
        add_action('wp_footer', [$this, 'addJavaScriptFallback'], 999);
    }

    /**
     * Start output buffering to capture and modify the full page output
     */
    public function startOutputBuffering(): void
    {
        ob_start([$this, 'processOutputBuffer']);
    }

    /**
     * Process output buffer to inject header/footer if not already rendered
     */
    public function processOutputBuffer(string $buffer): string
    {
        // If header not rendered yet, inject after <body> tag
        if (Header_Footer_Builder::isHeaderEnabled() && !$this->header_rendered) {
            $header_html = $this->getHeaderHtml();
            // Find <body...> tag and inject after it
            $buffer = preg_replace(
                '/(<body[^>]*>)/i',
                '$1' . $header_html,
                $buffer,
                1
            );
            $this->header_rendered = true;
        }

        return $buffer;
    }

    /**
     * CSS to hide common theme header selectors
     */
    public function hideNativeHeaders(): void
    {
        // Common header selectors across popular themes
        $selectors = [
            'header#masthead:not(.king-addons-el-hf-header)',
            'header.site-header:not(.king-addons-el-hf-header)',
            'header.header:not(.king-addons-el-hf-header)',
            '.site-header:not(.king-addons-el-hf-header)',
            '#site-header:not(.king-addons-el-hf-header)',
            '#masthead:not(.king-addons-el-hf-header)',
            '.header-main:not(.king-addons-el-hf-header)',
            '.main-header:not(.king-addons-el-hf-header)',
            'header[role="banner"]:not(.king-addons-el-hf-header)',
            '.ast-header-overlay', // Astra
            '.storefront-primary-navigation', // Storefront
            '.site-branding', // Many themes
            '#theme-header',
            '.theme-header',
            '.elementor-location-header', // Elementor native
        ];

        echo '<style id="king-addons-hf-hide-native-header">';
        echo implode(',', $selectors);
        echo '{display:none !important; visibility:hidden !important; height:0 !important; overflow:hidden !important; opacity:0 !important; pointer-events:none !important;}';
        echo '</style>';
    }

    /**
     * CSS to hide common theme footer selectors
     */
    public function hideNativeFooters(): void
    {
        // Common footer selectors across popular themes
        $selectors = [
            'footer#colophon:not(.king-addons-el-hf-footer)',
            'footer.site-footer:not(.king-addons-el-hf-footer)',
            'footer.footer:not(.king-addons-el-hf-footer)',
            '.site-footer:not(.king-addons-el-hf-footer)',
            '#site-footer:not(.king-addons-el-hf-footer)',
            '#colophon:not(.king-addons-el-hf-footer)',
            '.footer-main:not(.king-addons-el-hf-footer)',
            '.main-footer:not(.king-addons-el-hf-footer)',
            'footer[role="contentinfo"]:not(.king-addons-el-hf-footer)',
            '.ast-footer-overlay', // Astra
            '#theme-footer',
            '.theme-footer',
            '.elementor-location-footer', // Elementor native
        ];

        echo '<style id="king-addons-hf-hide-native-footer">';
        echo implode(',', $selectors);
        echo '{display:none !important; visibility:hidden !important; height:0 !important; overflow:hidden !important; opacity:0 !important; pointer-events:none !important;}';
        echo '</style>';
    }

    /**
     * Get header HTML content
     */
    private function getHeaderHtml(): string
    {
        ob_start();
        Header_Footer_Builder::renderHeader();
        return ob_get_clean();
    }

    /**
     * Get footer HTML content
     */
    private function getFooterHtml(): string
    {
        ob_start();
        Header_Footer_Builder::renderFooter();
        return ob_get_clean();
    }

    /**
     * Capture and replace header via get_header hook
     */
    public function captureHeader(): void
    {
        if ($this->header_rendered) {
            return;
        }

        // Load our custom header template instead
        require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/default/ELHF_Default_Header.php');
        
        $templates = ['header.php'];
        
        // Suppress the default header output
        ob_start();
        locate_template($templates, true);
        ob_end_clean();
        
        $this->header_rendered = true;
    }

    /**
     * Render header on wp_body_open (for themes that don't use get_header properly)
     */
    public function renderHeaderOnBodyOpen(): void
    {
        if ($this->header_rendered) {
            return;
        }

        Header_Footer_Builder::renderHeader();
        $this->header_rendered = true;
    }

    /**
     * Capture and replace footer via get_footer hook
     */
    public function captureFooter(): void
    {
        if ($this->footer_rendered) {
            return;
        }

        // Load our custom footer template
        require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/default/ELHF_Default_Footer.php');
        
        $templates = ['footer.php'];
        
        // Suppress the default footer output
        ob_start();
        locate_template($templates, true);
        ob_end_clean();
        
        $this->footer_rendered = true;
    }

    /**
     * Render footer on wp_footer (fallback for themes without get_footer)
     */
    public function renderFooterOnWpFooter(): void
    {
        // Всегда выводим футер, если он не был выведен ранее
        if (!$this->footer_rendered) {
            Header_Footer_Builder::renderFooter();
            $this->footer_rendered = true;
        }
    }

    /**
     * JavaScript fallback for DOM manipulation
     * This runs as a last resort to ensure headers/footers are properly displayed
     */
    public function addJavaScriptFallback(): void
    {
        $header_enabled = Header_Footer_Builder::isHeaderEnabled();
        $footer_enabled = Header_Footer_Builder::isFooterEnabled();

        if (!$header_enabled && !$footer_enabled) {
            return;
        }

        ?>
        <script id="king-addons-hf-js-fallback">
        (function() {
            'use strict';
            
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($header_enabled): ?>
                // Ensure our header is visible and positioned correctly
                var kaHeader = document.getElementById('masthead');
                if (kaHeader && kaHeader.querySelector('.elementor')) {
                    kaHeader.style.display = 'block';
                    kaHeader.style.visibility = 'visible';
                    kaHeader.style.opacity = '1';
                    
                    // Move our header to the top of body if needed
                    var body = document.body;
                    if (body.firstElementChild !== kaHeader) {
                        body.insertBefore(kaHeader, body.firstElementChild);
                    }
                }
                
                // Hide any duplicate headers
                var allHeaders = document.querySelectorAll('header');
                var foundOurHeader = false;
                allHeaders.forEach(function(header) {
                    if (header.querySelector('.elementor') && header.id === 'masthead') {
                        foundOurHeader = true;
                        return;
                    }
                    if (foundOurHeader || header.querySelector('.elementor')) {
                        return;
                    }
                    header.style.display = 'none';
                });
                <?php endif; ?>
                
                <?php if ($footer_enabled): ?>
                // Ensure our footer is visible
                var kaFooter = document.getElementById('colophon');
                if (kaFooter && kaFooter.querySelector('.elementor')) {
                    kaFooter.style.display = 'block';
                    kaFooter.style.visibility = 'visible';
                    kaFooter.style.opacity = '1';
                    
                    // Move our footer to the end of body if needed
                    var body = document.body;
                    body.appendChild(kaFooter);
                }
                
                // Hide any duplicate footers
                var allFooters = document.querySelectorAll('footer');
                allFooters.forEach(function(footer) {
                    if (footer.querySelector('.elementor') && footer.id === 'colophon') {
                        return;
                    }
                    footer.style.display = 'none';
                });
                <?php endif; ?>
            });
        })();
        </script>
        <?php
    }
}

new ELHF_Default_Method_3();
