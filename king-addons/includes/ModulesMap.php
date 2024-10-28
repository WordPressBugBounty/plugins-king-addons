<?php /** @noinspection SpellCheckingInspection */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class ModulesMap
{
    public static function getModulesMapArray(): array
    {
        return [
            'widgets' => [
                'styled-text-builder' => [
                    'title' => esc_html__('Styled Text Builder', 'king-addons'),
                    'description' => esc_html__('Enables the creation of uniquely styled and animated text elements, offering extensive customization options for impactful and visually captivating web typography. Allows combining different styles in one text paragraph.', 'king-addons'),
                    'php-class' => 'Styled_Text_Builder',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/styled-text-builder/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'image-hotspots' => [
                    'title' => esc_html__('Image Hotspots', 'king-addons'),
                    'description' => esc_html__('Module for Elementor to create interactive, clickable areas on images, ideal for displaying information or links in a dynamic, visually engaging way. Uses only CSS, not any JS.', 'king-addons'),
                    'php-class' => 'Image_Hotspots',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/image-hotspots/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'one-page-navigation' => [
                    'title' => esc_html__('One Page Navigation', 'king-addons'),
                    'description' => esc_html__('Facilitates seamless navigation within a single page, using anchor links to smoothly scroll to different sections, enhancing user experience and site organization. Uses only CSS, not any JS.', 'king-addons'),
                    'php-class' => 'One_Page_Navigation',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/one-page-navigation/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'off-canvas-content' => [
                    'title' => esc_html__('Off-Canvas Content ', 'king-addons'),
                    'description' => esc_html__('Allows the creation of hidden (offcanvas), slide-in panels on websites, ideal for menus, widgets, or additional content, accessible with a simple user interaction.', 'king-addons'),
                    'php-class' => 'Off_Canvas_Content',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/off-canvas-content/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'image-comparison' => [
                    'title' => esc_html__('Image Comparison', 'king-addons'),
                    'description' => esc_html__('Also known as Before & After, the widget offers an interactive tool to visually contrast two images side-by-side with a draggable slider, perfect for before-and-after views, product comparisons, or design showcases.', 'king-addons'),
                    'php-class' => 'Image_Comparison',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/image-comparison/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'vertical-scrollable-image' => [
                    'title' => esc_html__('Vertical Scrollable Image', 'king-addons'),
                    'description' => esc_html__('Widget allows seamless vertical scrolling on mouse hover over through long images, such as infographics or timelines, on a webpage, enhancing user engagement and storytelling without resizing. Uses only CSS, not any JS.', 'king-addons'),
                    'php-class' => 'Vertical_Scrollable_Image',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/vertical-scrollable-image/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'global-section-container' => [
                    'title' => esc_html__('Global Section & Container', 'king-addons'),
                    'description' => esc_html__('Allows designers to create and manage reusable website sections and containers, streamlining design consistency and efficiency across multiple pages. Useful for building Header, Footer, and CTA sections. Does not affect website performance because it uses Elementor built-in functionality. Does not use any CSS or JS.', 'king-addons'),
                    'php-class' => 'Global_Section_Container',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/global-section-container/',
                    'css' => [],
                    'js' => [],
                ],
                'card-carousel' => [
                    'title' => esc_html__('Card Carousel', 'king-addons'),
                    'description' => esc_html__('Create a card carousel, also known as horizontal slider/scroller. Each card has an image, title, subtitle, description and button. Ideal for showcasing content, highlighting features, or presenting services, perfect for team members and testimonials sections. It is fully responsive, and supports both mobile and mouse dragging.', 'king-addons'),
                    'php-class' => 'Card_Carousel',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/card-carousel/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'auto-scrolling-text' => [
                    'title' => esc_html__('Auto-Scrolling Text', 'king-addons'),
                    'description' => esc_html__('An engaging text marquee for dynamic, continuously scrolling text sections. Ideal for announcements, news tickers, or any content you want in constant motion. Customize the style with various effects, and add images to enhance your design. Uses only CSS for animation.', 'king-addons'),
                    'php-class' => 'Auto_Scrolling_Text',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/auto-scrolling-text/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'popup' => [
                    'title' => esc_html__('Popup & Lightbox Modal', 'king-addons'),
                    'description' => esc_html__('A versatile tool for creating engaging popups and lightbox modals. Perfect for displaying important messages, promotions, or multimedia content. Customize the appearance and behavior with various effects, and easily add images, videos, and other elements to capture your audience attention.', 'king-addons'),
                    'php-class' => 'Popup',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/popup-lightbox-modal/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'video-popup' => [
                    'title' => esc_html__('Video Popup', 'king-addons'),
                    'description' => esc_html__('A powerful tool for creating engaging video popups. Ideal for showcasing promotional videos, tutorials, or any multimedia content. Customize the appearance and behavior with various effects, and easily integrate videos to capture your audience attention.', 'king-addons'),
                    'php-class' => 'Video_Popup',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/video-popup/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'blog-posts' => [
                    'title' => esc_html__('Blog Posts Carousel', 'king-addons'),
                    'description' => esc_html__('A dynamic tool for showcasing blog posts in an engaging carousel format. Perfect for highlighting featured articles, news, and updates. Customize the layout, style, and transition effects to create an eye-catching display that enhances your website design and attracts readers.', 'king-addons'),
                    'php-class' => 'Blog_Posts',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/blog-posts-carousel/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'image-grid' => [
                    'title' => esc_html__('Image Grid', 'king-addons'),
                    'description' => esc_html__('Create stunning image galleries with masonry and fit rows grid layouts. Perfectly adaptable for both mobile and desktop views. Customize the design to match your website style and showcase your images beautifully.', 'king-addons'),
                    'php-class' => 'Image_Grid',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/image-grid/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'business-hours' => [
                    'title' => esc_html__('Business Hours', 'king-addons'),
                    'description' => esc_html__('Display your business hours, also known as opening hours or working hours, clearly and attractively. Customize the layout and style to match your website design, ensuring your customers always know when you are open. Perfect for restaurants, shops, and any business that wants to communicate their hours effectively.', 'king-addons'),
                    'php-class' => 'Business_Hours',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/business-hours/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'price-list' => [
                    'title' => esc_html__('Price List & Menu', 'king-addons'),
                    'description' => esc_html__('Showcase your price list and menu items clearly and stylishly. Perfect for restaurants, cafes, and any business offering a range of products or services. Customize the layout and style to fit your brand, ensuring your offerings are presented in an appealing and organized manner.', 'king-addons'),
                    'php-class' => 'Price_List',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/price-list-menu/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'image-hover-box' => [
                    'title' => esc_html__('Image Hover Box', 'king-addons'),
                    'description' => esc_html__('Create interactive image elements with engaging hover effects. Ideal for showcasing services, products, or portfolio items. Customize the hover effects, layout, and style to enhance your website visual appeal and provide an interactive user experience.', 'king-addons'),
                    'php-class' => 'Image_Hover_Box',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/image-hover-box/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'reading-progress-bar' => [
                    'title' => esc_html__('Reading Progress Bar', 'king-addons'),
                    'description' => esc_html__('Enhance user engagement with a dynamic reading progress bar. Ideal for blogs, articles, and long-form content. Customize the style, position, and behavior to match your website design and provide readers with a visual cue of their progress.', 'king-addons'),
                    'php-class' => 'Reading_Progress_Bar',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/reading-progress-bar/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'pulsing-button' => [
                    'title' => esc_html__('Pulsing Button', 'king-addons'),
                    'description' => esc_html__('Boost interactivity with an engaging call-to-action (CTA) animated button. Ideal for driving clicks, sign-ups, or purchases. Customize animation effects, colors, and styles to match your brand and catch visitors attention. Perfect for increasing conversions.', 'king-addons'),
                    'php-class' => 'Pulsing_Button',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/pulsing-button/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'creative-button' => [
                    'title' => esc_html__('Creative Button', 'king-addons'),
                    'description' => esc_html__('Add a touch of creativity to your website with the Creative Button widget. Perfect for unique and eye-catching call-to-actions. Customize shapes, colors, and effects to align with your brand and capture visitor attention. Ideal for enhancing user engagement and driving interactions.', 'king-addons'),
                    'php-class' => 'Creative_Button',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/creative-button/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'hovering-image-stack' => [
                    'title' => esc_html__('Hovering Image Stack', 'king-addons'),
                    'description' => esc_html__('Create visually appealing image stacks to showcase multiple images in a layered format. Perfect for portfolios, product displays, or galleries. Customize the stack style, spacing, and effects to match your website design and provide a unique visual experience for your visitors.', 'king-addons'),
                    'php-class' => 'Hovering_Image_Stack',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/hovering-image-stack/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'content-flip-box' => [
                    'title' => esc_html__('Content Hover & Flip Box', 'king-addons'),
                    'description' => esc_html__('Showcase interactive content with the Content Hover Box widget. This versatile tool includes various hover effects and also has the Flip Box effect for enhanced engagement. Perfect for highlighting features, services, or promotions in a visually appealing way. Customize the layout, colors, and effects to match your website design.', 'king-addons'),
                    'php-class' => 'Content_Flip_Box',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/content-hover-flip-box/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'flip-countdown' => [
                    'title' => esc_html__('Flip Countdown & Timer', 'king-addons'),
                    'description' => esc_html__('Add dynamic urgency to your events or promotions with the Flip Countdown widget. This tool features a unique flipping animation, perfect for time-limited offers, upcoming launches, or important dates. Customize the design, format, and style to match your website, ensuring it captures visitors attention and encourages action.', 'king-addons'),
                    'php-class' => 'Flip_Countdown',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/flip-countdown-timer/',
                    'css' => [],
                    'js' => [],
                ],
                'smooth-counter' => [
                    'title' => esc_html__('Smooth Counter', 'king-addons'),
                    'description' => esc_html__('Showcase your statistics or milestones with the Smooth Counter widget. Featuring seamless animations, this tool is perfect for displaying numbers, achievements, or progress in an engaging way. Customize the design, animation speed, and style to match your website, ensuring it draws visitors attention and enhances your content.', 'king-addons'),
                    'php-class' => 'Smooth_Counter',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/smooth-counter/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'testimonial' => [
                    'title' => esc_html__('Testimonial & Review', 'king-addons'),
                    'description' => esc_html__('Build trust and credibility with the Testimonial widget. Perfect for showcasing customer reviews, feedback, or client success stories. Customize the layout, design, and style to fit your brand, ensuring that positive experiences stand out and resonate with your audience.', 'king-addons'),
                    'php-class' => 'Testimonial',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/testimonial-review/',
                    'css' => ['style'],
                    'js' => [],
                ],
                'testimonial-carousel' => [
                    'title' => esc_html__('Testimonial & Review Carousel', 'king-addons'),
                    'description' => esc_html__('Display customer feedback and reviews dynamically with the Testimonial & Review Carousel widget. Ideal for showcasing client testimonials, ratings, or success stories in a sliding format. Customize the layout, transition effects, and style to match your brand, making positive experiences stand out and engage your audience effectively.', 'king-addons'),
                    'php-class' => 'Testimonial_Carousel',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/testimonial-review-carousel/',
                    'css' => ['style'],
                    'js' => [],
                ],
//                'test' => [
//                    'title' => esc_html__('test', 'king-addons'),
//                    'description' => esc_html__('', 'king-addons'),
//                    'php-class' => 'Test',
//                    'docs-link' => '',
////                    'demo-link' => 'https://kingaddons.com/elementor/test/',
//                    'demo-link' => '',
//                    'css' => ['style'],
//                    'js' => [],
//                ],
            ],
            'features' => [
                'particles-background' => [
                    'title' => esc_html__('Particles Background', 'king-addons'),
                    'description' => esc_html__('Brings website to life by adding a dynamic, animated particle effect as background, creating an engaging and modern visual experience.', 'king-addons'),
                    'php-class' => 'Particles_Background',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/particles-background/',
                    'css' => [],
                    'js' => ['preview-handler']
                ],
                'parallax-background' => [
                    'title' => esc_html__('Parallax Background', 'king-addons'),
                    'description' => esc_html__('Adds a dynamic, multi-layered visual effect to website backgrounds, moving at different speeds during scrolling for an immersive, 3D-like experience for sections and containers. The feature uses only lightweight JS code (11 kilobytes).', 'king-addons'),
                    'php-class' => 'Parallax_Background',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/parallax-background/',
                    'css' => [],
                    'js' => ['preview-handler']
                ],
                'matte-glass-background' => [
                    'title' => esc_html__('Matte Glass Background', 'king-addons'),
                    'description' => esc_html__('Adds matte glass background feature also known as frosted glass effect. The feature uses only CSS, not any JavaScript at all.', 'king-addons'),
                    'php-class' => 'Matte_Glass_Background',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/matte-glass-background/',
                    'css' => [],
                    'js' => []
                ],
                'advanced-border-radius' => [
                    'title' => esc_html__('Advanced Border Radius', 'king-addons'),
                    'description' => esc_html__('Perfect for creating unique, rounded corners on elements. Customize the border radius for each corner, add different styles, and create visually appealing shapes to match your brand. Ideal for adding a modern and stylish touch to your site.', 'king-addons'),
                    'php-class' => 'Advanced_Border_Radius',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/advanced-border-radius/',
                    'css' => [],
                    'js' => []
                ],
                'floating-animation' => [
                    'title' => esc_html__('Floating Animation', 'king-addons'),
                    'description' => esc_html__('Adds floating animation setting. The feature does not use JavaScript, only lightweight CSS animation.', 'king-addons'),
                    'php-class' => 'Floating_Animation',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/floating-animation/',
                    'css' => [],
                    'js' => ['preview-handler']
                ],
                'custom-css' => [
                    'title' => esc_html__('Custom CSS', 'king-addons'),
                    'description' => esc_html__('Provides an easy-to-use interface for adding personalized CSS styles, allowing precise control over the appearance and layout of web elements on your page.', 'king-addons'),
                    'php-class' => 'Custom_CSS',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/custom-css/',
                    'css' => [],
                    'js' => ['preview-handler']
                ],
                'rotating-animation' => [
                    'title' => esc_html__('Rotating Animation', 'king-addons'),
                    'description' => esc_html__('Adds rotating animation setting. The feature does not use JS, only lightweight CSS animation.', 'king-addons'),
                    'php-class' => 'Rotating_Animation',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/rotating-animation/',
                    'css' => [],
                    'js' => ['preview-handler']
                ],
                'wrapper-link' => [
                    'title' => esc_html__('Wrapper Link', 'king-addons'),
                    'description' => esc_html__('Wrap everything in link - section, container, column and common elements', 'king-addons'),
                    'php-class' => 'Wrapper_Link',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/wrapper-link/',
                    'css' => [],
                    'js' => []
                ],
                'duplicator' => [
                    'title' => esc_html__('Duplicator', 'king-addons'),
                    'description' => esc_html__('Adds feature to duplicate posts, pages, Elementor templates and etc.', 'king-addons'),
                    'php-class' => 'Duplicator',
                    'docs-link' => '',
                    'demo-link' => 'https://kingaddons.com/elementor/duplicator/',
                    'css' => [],
                    'js' => []
                ],
            ],
            'libraries' => [
                'swiper' => [
                    'css' => ['swiper'],
                    'js' => ['swiper']
                ],
                'flipclock' => [
                    'css' => ['flipclock'],
                    'js' => ['flipclock']
                ],
                'jarallax' => [
                    'css' => [],
                    'js' => ['jarallax']
                ],
                'jquery' => [
                    'css' => [],
                    'js' => ['jquery']
                ],
                'odometer' => [
                    'css' => ['minimal'],
                    'js' => ['odometer']
                ],
                'particles' => [
                    'css' => [],
                    'js' => ['particles']
                ],
                'isotope' => [
                    'css' => [],
                    'js' => ['isotope']
                ],
                'imagesloaded' => [
                    'css' => [],
                    'js' => ['imagesloaded']
                ],
            ]
        ];
    }
}