<?php /** @noinspection PhpUnused, DuplicatedCode, SpellCheckingInspection */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}



class Global_Section_Container extends Widget_Base
{
    

    public function get_name(): string
    {
        return 'king-addons-global-section-container';
    }

    public function get_title(): string
    {
        return esc_html__('Global Section & Container', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-global-section-container';
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['content', 'template', 'templates', 'container', 'section', 'column', 'widget', 'module', 'connect',
            'global widget', 'global', 'reusable', 'across', 'multiple', 'multi', 'page', 'pages',
            'king', 'addons', 'kingaddons', 'king-addons', 'off canvas', 'embed'];
    }

    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'kng_global_section_container_general',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('General', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_global_section_container_template',
            [
                'label' => esc_html__('Select Template', 'king-addons'),
                'type' => 'king-addons-ajax-select2',
                'options' => 'ajaxselect2/getElementorTemplates',
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_global_section_container_alert_info',
            [
                'type' => Controls_Manager::ALERT,
                'alert_type' => 'info',
                'heading' => esc_html__('Note for Entrance Animations in Editor mode', 'king-addons'),
                'content' => esc_html__('If any content within the global template has Entrance Animations configured in the Advanced -> Motion Effects tab, it may not be displayed after selecting the template in Editor mode (Elementor editor). This occurs because the Entrance Animations are triggered immediately after the page loads. To address this issue, try saving your changes and reloading this editor page; following this, the global template should fully appear in most cases. The problem does not affect the appearance of the live website and is only present in the editor.', 'king-addons'),
            ]
        );

        
        

$this->end_controls_section();
    
        
    }

    public function getOffCanvasTemplate($template_id): ?string
    {
        if (empty($template_id)) {
            return null;
        }

        // Always include CSS for proper rendering in both editor and frontend
        $has_css = true;

        return Plugin::instance()->frontend->get_builder_content_for_display($template_id, $has_css);
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        if (!empty($settings['kng_global_section_container_template'])) {
            $template_id = intval($settings['kng_global_section_container_template']);
            
            // Verify template exists and is an Elementor template
            $template_post = get_post($template_id);
            if (!$template_post || 'elementor_library' !== $template_post->post_type) {
                echo '<p>' . esc_html__('Invalid template selected', 'king-addons') . '</p>';
                return;
            }
            
            // Enqueue template styles in editor mode
            $is_editor = Plugin::$instance->editor->is_edit_mode() || Plugin::$instance->preview->is_preview_mode();
            if ($is_editor) {
                if (class_exists('\Elementor\Core\Files\CSS\Post')) {
                    $css_file = new \Elementor\Core\Files\CSS\Post($template_id);
                    $css_file->enqueue();
                }
            }
            
            $html = $this->getOffCanvasTemplate($template_id);
            
            if (empty($html)) {
                echo '<p>' . esc_html__('Template is empty or not found', 'king-addons') . '</p>';
                return;
            }
            
            // Wrapper for editor animation reset
            $widget_id = $this->get_id();
            echo '<div class="king-addons-global-section-wrapper" data-widget-id="' . esc_attr($widget_id) . '">';
            
            // Elementor content is already sanitized by Elementor itself
            // Using wp_kses strips inline styles with background-image url() which breaks the layout
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $html;
            
            echo '</div>';
            
            // In editor mode, reset entrance animations so content is visible immediately
            if ($is_editor) {
                ?>
                <script>
                (function() {
                    var wrapper = document.querySelector('[data-widget-id="<?php echo esc_js($widget_id); ?>"]');
                    if (wrapper) {
                        // Remove animation classes and make elements visible
                        var animatedElements = wrapper.querySelectorAll('.elementor-invisible, [class*="animated"]');
                        animatedElements.forEach(function(el) {
                            el.classList.remove('elementor-invisible');
                            el.style.opacity = '1';
                            el.style.visibility = 'visible';
                        });
                    }
                })();
                </script>
                <?php
            }
        } else {
            echo '<p>' . esc_html__('Please select a template', 'king-addons') . '</p>';
        }
    }
}