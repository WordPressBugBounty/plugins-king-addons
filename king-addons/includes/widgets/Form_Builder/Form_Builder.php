<?php

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Form_Builder extends Widget_Base
{
    


    public function get_name()
    {
        return 'king-addons-form-builder';
    }

    public function get_title()
    {
        return esc_html__('Form Builder', 'king-addons');
    }

    public function get_icon()
    {
        return 'king-addons-icon king-addons-form-builder';
    }

    public function get_categories()
    {
        return ['king-addons'];
    }

    public function get_keywords()
    {
        return ['king addons', 'king', 'addons', 'kingaddons', 'king-addons', 'cf7', 'contact form 7', 'builder', 'ninja', 'caldera', 'contact form',
            'caldera forms', 'ninja forms', 'wpforms', 'wp forms', 'email', 'mail', 'email', 'form', 'forms', 'email', 'contact'];
    }

    public function get_script_depends()
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-animation-loading',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-form-builder-script'
        ];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-form-builder-style'];
    }

    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function get_control_id($control_id)
    {
        return $control_id;
    }

    public function get_label()
    {
        return esc_html__('Email', 'king-addons');
    }

    public static function get_site_domain()
    {
        return str_ireplace('www.', '', parse_url(home_url(), PHP_URL_HOST));
    }

    public function submit_action_args()
    {
        return [
            'email' => 'Email',
            'redirect' => 'Redirect',
            'pro-sb' => 'Submission (Pro)',
            'pro-mch' => 'Mailchimp (Pro)',
            'pro-wh' => 'Webhook (Pro)'
        ];
    }

    public function register_settings_section_submissions($widget)
    {
        $widget->start_controls_section(
            $this->get_control_id('section_submissions'),
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Submissions', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'submit_actions' => 'submissions',
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id('submissions_action_message'),
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    __(
                        'View Submissions in King Addons > <a href="%s" target="_blank">Form Submissions</a>',
                        'king-addons'
                    ),
                    self_admin_url('edit.php?post_type=king-addons-fb-sub')
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $widget->end_controls_section();
    }

    public function register_settings_section_webhook($widget)
    {
        $widget->start_controls_section(
            $this->get_control_id('section_webhook'),
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Webhook', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'submit_actions' => 'webhook',
                ],
            ]
        );

        $widget->add_control(
            'webhook_url',
            [
                'label' => esc_html__('Webhook URL', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('your-webhook-url.com', 'king-addons'),
                'ai' => [
                    'active' => false,
                ],
                'dynamic' => [
                    'active' => true,
                ],
                'label_block' => true,
                'separator' => 'before',
                'description' => esc_html__('Enter the webhook URL (e.g. Zapier) that will receive the submitted data.', 'king-addons'),
                'render_type' => 'none',
            ]
        );

        $widget->end_controls_section();
    }

    public function register_settings_section_email($widget)
    {
        $widget->start_controls_section(
            $this->get_control_id('section_email'),
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . $this->get_label(),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'submit_actions' => 'email',
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id('email_to'),
            [
                'label' => esc_html__('To', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => get_option('admin_email'),
                'label_block' => true,
                'title' => esc_html__('Separate emails with commas', 'king-addons'),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );


        $default_message = sprintf(esc_html__('New message from %s', 'king-addons'), get_option('blogname'));

        $widget->add_control(
            $this->get_control_id('email_subject'),
            [
                'label' => esc_html__('Subject', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => $default_message,
                'placeholder' => $default_message,
                'label_block' => true,
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id('email_content'),
            [
                'label' => esc_html__('Message', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '[all-fields]',
                'placeholder' => '[all-fields]',
                'description' => sprintf(
                    esc_html__('By default, the form submits all fields. To modify this behavior, copy the shortcode of the fields you wish to include and paste it in place of %s.', 'king-addons'),
                    '<code>[all-fields]</code>'
                ),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $site_domain = $this->get_site_domain();

        $widget->add_control(
            $this->get_control_id('email_from'),
            [
                'label' => esc_html__('From Email', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Shortcodes such as [id="email"] can be inserted based on the ID of the associated mail field.', 'king-addons'),
                'default' => 'email@' . $site_domain,
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id('email_from_name'),
            [
                'label' => esc_html__('From Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => get_bloginfo('name'),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id('email_reply_to'),
            [
                'label' => esc_html__('Reply To', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => 'email@' . $site_domain,
                'render_type' => 'none'
            ]
        );

        $widget->add_control(
            $this->get_control_id('email_to_cc'),
            [
                'label' => esc_html__('Cc', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => esc_html__('Separate emails with commas', 'king-addons'),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id('email_to_bcc'),
            [
                'label' => esc_html__('Bcc', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => esc_html__('Separate emails with commas', 'king-addons'),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id('form_metadata'),
            [
                'label' => esc_html__('Meta Data', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'separator' => 'before',
                'default' => [
                    'date',
                    'time',
                    'credit'
                ],
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'time' => esc_html__('Time', 'king-addons'),
                    'page_url' => esc_html__('Page URL', 'king-addons'),
                    'page_title' => esc_html__('Page Title', 'king-addons'),
                    'user_agent' => esc_html__('User Agent', 'king-addons'),
                    'remote_ip' => esc_html__('Remote IP', 'king-addons'),
                    'credit' => esc_html__('Credit', 'king-addons'),
                ],
                'render_type' => 'none',
            ]
        );

        $widget->add_control(
            $this->get_control_id('email_content_type'),
            [
                'label' => esc_html__('Send As', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'html',
                'render_type' => 'none',
                'options' => [
                    'html' => esc_html__('HTML', 'king-addons'),
                    'plain' => esc_html__('Plain', 'king-addons'),
                ],
            ]
        );

        $widget->end_controls_section();
    }

    public function register_settings_section_redirect($widget)
    {
        $widget->start_controls_section(
            'section_redirect',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Redirect', 'king-addons'),
                'condition' => [
                    'submit_actions' => 'redirect',
                ],
            ]
        );

        $widget->add_control(
            'redirect_to',
            [
                'label' => esc_html__('Redirect To', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true
                ],
                'placeholder' => esc_html__('https://example.com', 'king-addons'),
                'label_block' => true
            ]
        );

        $widget->end_controls_section();
    }

    public function register_settings_section_mailchimp()
    {
        $this->start_controls_section(
            'section_mailchimp',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Mailchimp', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'submit_actions' => 'mailchimp'
                ]
            ]
        );

        $this->add_control(
            'mailchimp_audience',
            [
                'label' => esc_html__('Select Audience', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'def',

                'options' => Core::getMailchimpLists(),
            ]
        );


        $this->add_control(
            'mailchimp_groups',
            [
                'label' => esc_html__('Groups', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => Core::getMailchimpGroups(),

                'label_block' => true,
            ]
        );

        if ('' == get_option('king_addons_mailchimp_api_key')) {
            $this->add_control(
                'mailchimp_key_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(__('Navigate to <strong><a href="%s" target="_blank">Dashboard > %s > Integrations</a></strong> to set up <strong>MailChimp API Key</strong>.', 'king-addons'), admin_url('admin.php?page=king-addons-settings'), Core::getPluginName()),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                ]
            );
        }

        $this->add_control(
            'mailchimp_fields',
            [
                'label' => esc_html__('Fields', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'email_field',
            [
                'label' => esc_html__('Email', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'first_name_field',
            [
                'label' => esc_html__('First Name', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'last_name_field',
            [
                'label' => esc_html__('Last Name', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'phone_field',
            [
                'label' => esc_html__('Phone', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'birthday_field',
            [
                'label' => esc_html__('Birthday', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'address_field',
            [
                'label' => esc_html__('Address', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'country_field',
            [
                'label' => esc_html__('Country', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'city_field',
            [
                'label' => esc_html__('City', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'state_field',
            [
                'label' => esc_html__('State', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        $this->add_control(
            'zip_field',
            [
                'label' => esc_html__('Zip', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => []
            ]
        );

        
        

$this->end_controls_section();

    
        
    }

    public $last_prev_btn_text;

    protected function register_controls()
    {

        $this->start_controls_section(
            'section_form_fields',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Fields', 'king-addons'),
            ]
        );


        $repeater = new Repeater();

        $field_types = [
            'text' => esc_html__('Text', 'king-addons'),
            'textarea' => esc_html__('Textarea', 'king-addons'),
            'email' => esc_html__('Email', 'king-addons'),
            'url' => esc_html__('URL (Link)', 'king-addons'),
            'number' => esc_html__('Number', 'king-addons'),
            'tel' => esc_html__('Tel (Phone Number)', 'king-addons'),
            'radio' => esc_html__('Radio', 'king-addons'),
            'select' => esc_html__('Select', 'king-addons'),
            'checkbox' => esc_html__('Checkbox', 'king-addons'),
            'date' => esc_html__('Date', 'king-addons'),
            'time' => esc_html__('Time', 'king-addons'),
            'upload' => esc_html__('File Upload', 'king-addons'),
            'password' => esc_html__('Password', 'king-addons'),
            'html' => esc_html__('HTML', 'king-addons'),
            'recaptcha-v3' => esc_html__('reCAPTCHA V3', 'king-addons'),
            'hidden' => esc_html__('Hidden Field', 'king-addons'),
            'king-addons-fb-step' => esc_html__('Step', 'king-addons'),
        ];

        $repeater->add_control(
            'field_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $field_types,
                'default' => 'text',
            ]
        );

        $repeater->add_control(
            'field_step_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Step should be a First element of fields group. Ex: Step 1 followed by Field 1, Field 2. Step 2 followed by Field 3, Field 4.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert',
                'condition' => [
                    'field_type' => 'king-addons-fb-step'
                ]
            ]
        );

        if ('' == get_option('king_addons_recaptcha_v3_site_key')) {
            $repeater->add_control(
                'recaptcha_key_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(__('Navigate to <strong><a href="%s" target="_blank">Dashboard > %s > Integrations</a></strong> to set up <strong>reCaptcha Site Key</strong>.', 'king-addons'), admin_url('admin.php?page=king-addons-settings'), Core::getPluginName()),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    'condition' => [
                        'field_type' => 'recaptcha-v3'
                    ]
                ]
            );
        }

        $repeater->add_control(
            'field_label',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'field_sub_label',
            [
                'label' => esc_html__('Sub Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
                'condition' => [
                    'field_type' => 'king-addons-fb-step'
                ]
            ]
        );

        $repeater->add_control(
            'previous_button_text',
            [
                'label' => esc_html__('Previous Button', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => 'Previous',
                'dynamic' => [
                    'active' => true,
                ],
                'condition' => [
                    'field_type' => 'king-addons-fb-step'
                ]
            ]
        );

        $repeater->add_control(
            'next_button_text',
            [
                'label' => esc_html__('Next Button', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => 'Next',
                'dynamic' => [
                    'active' => true,
                ],
                'condition' => [
                    'field_type' => 'king-addons-fb-step'
                ]
            ]
        );

        $repeater->add_control(
            'step_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'skin' => 'inline',
                'label_block' => false,
                'separator' => 'before',
                'default' => [
                    'value' => 'far fa-edit',
                    'library' => 'regular'
                ],
                'condition' => [
                    'field_type' => 'king-addons-fb-step'
                ]
            ]
        );

        $repeater->add_control(
            'placeholder',
            [
                'label' => esc_html__('Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => [
                                'tel',
                                'text',
                                'email',
                                'textarea',
                                'number',
                                'url',
                                'password',
                            ],
                        ],
                    ],
                ],
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'field_value',
            [
                'label' => esc_html__('Default Value', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => [
                                'text',
                                'email',
                                'textarea',
                                'url',
                                'tel',
                                'radio',
                                'select',
                                'number',
                                'date',
                                'time',
                                'hidden',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'field_id',
            [
                'label' => esc_html__('ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Element ID should be unique and not used elsewhere in this widget.', 'king-addons'),
                'default' => '',
                'render_type' => 'none',
                'required' => true,
                'dynamic' => [
                    'active' => true,
                ]
            ]
        );

        $shortcode_value = '{{ view.container.settings.get( \'field_id\' ) }}';

        $repeater->add_control(
            'shortcode',
            [
                'label' => esc_html__('Shortcode', 'king-addons'),
                'type' => Controls_Manager::RAW_HTML,
                'classes' => 'forms-field-shortcode',
                'raw' => '<input class="king-addons-form-field-shortcode" value=\'[id="' . $shortcode_value . '"]\' readonly />'
            ]
        );

        $repeater->add_control(
            'required',
            [
                'label' => esc_html__('Required', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'true',
                'default' => '',
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => '!in',
                            'value' => [
                                'recaptcha',
                                'recaptcha-v3',
                                'hidden',
                                'html',
                                'king-addons-fb-step',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'allow_multiple_upload',
            [
                'label' => esc_html__('Multiple', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'condition' => [
                    'field_type' => 'upload'
                ],
            ]
        );

        $max_file_size = wp_max_upload_size() / pow(1024, 2);

        $repeater->add_control(
            'file_size',
            [
                'label' => esc_html__('File Size (MB)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => $max_file_size,
                'king-addons-fb-step' => 1,
                'description' => esc_html__('Max upload size allowed is ' . $max_file_size . 'MB. Please contact your hosting to increase it.', 'king-addons'),
                'condition' => [
                    'field_type' => 'upload'
                ]
            ]
        );

        $repeater->add_control(
            'file_types',
            [
                'label' => esc_html__('File Types', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Enter the comma separated file types to allow.', 'king-addons'),
                'condition' => [
                    'field_type' => 'upload',
                ]
            ]
        );

        $repeater->add_control(
            'field_options',
            [
                'label' => esc_html__('Options', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'description' => esc_html__('Insert each option on a separate line. To set a different label and value for an option, separate them with a pipe ("|"). Example: First Option|f_option', 'king-addons'),
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => [
                                'select',
                                'checkbox',
                                'radio',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'allow_multiple',
            [
                'label' => esc_html__('Multiple Selection', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'true',
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'value' => 'select',
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'select_size',
            [
                'label' => esc_html__('Rows', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 2,
                'king-addons-fb-step' => 1,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'value' => 'select',
                        ],
                        [
                            'name' => 'allow_multiple',
                            'value' => 'true',
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'inline_list',
            [
                'label' => esc_html__('Inline List', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'king-addons-inline-sub-group',
                'default' => '',
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => [
                                'checkbox',
                                'radio',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'field_html',
            [
                'label' => esc_html__('HTML', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'dynamic' => [
                    'active' => true,
                ],
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'value' => 'html',
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_responsive_control(
            'width',
            [
                'label' => esc_html__('Column Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} {{CURRENT_ITEM}}' => 'width: {{SIZE}}%;',
                ],
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => '!in',
                            'value' => [
                                'hidden',
                                'recaptcha',
                                'recaptcha-v3',
                                'king-addons-fb-step',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'rows',
            [
                'label' => esc_html__('Rows', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 7,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'value' => 'textarea',
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'recaptcha_size', [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'normal',
                'options' => [
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'compact' => esc_html__('Compact', 'king-addons'),
                ],
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'value' => 'recaptcha',
                        ],
                    ],
                ],
            ]
        );

        $repeater->add_control(
            'recaptcha_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'light',
                'options' => [
                    'light' => esc_html__('Light', 'king-addons'),
                    'dark' => esc_html__('Dark', 'king-addons'),
                ],
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'value' => 'recaptcha',
                        ],
                    ],
                ],
            ]
        );


        $repeater->add_control(
            'css_classes',
            [
                'label' => esc_html__('CSS Classes', 'king-addons'),
                'type' => Controls_Manager::HIDDEN,
                'default' => '',
                'title' => esc_html__('Add your custom class without the dot, e.g: "your-class"', 'king-addons'),
            ]
        );

        $this->add_control(
            'form_fields',
            [

                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'field_id' => 'name',
                        'field_type' => 'text',
                        'field_label' => esc_html__('Name', 'king-addons'),
                        'placeholder' => esc_html__('Name', 'king-addons'),
                        'width' => '100',
                        'dynamic' => [
                            'active' => true,
                        ],
                    ],
                    [
                        'field_id' => 'email',
                        'field_type' => 'email',
                        'required' => 'true',
                        'field_label' => esc_html__('Email', 'king-addons'),
                        'placeholder' => esc_html__('Email', 'king-addons'),
                        'width' => '100',
                    ],
                    [
                        'field_id' => 'message',
                        'field_type' => 'textarea',
                        'field_label' => esc_html__('Message', 'king-addons'),
                        'placeholder' => esc_html__('Message', 'king-addons'),
                        'width' => '100',
                    ],
                ],
                'title_field' => '{{{ field_label }}}',
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $this->add_control(
                'fields_to_show_pro_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => __('More than 3 Fields (Excluding Steps) are available in the <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-form-builder-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>'),
                    'content_classes' => 'king-addons-pro-notice'
                ]
            );
        }

        $this->end_controls_section();

        $this->start_controls_section(
            'section_buttons',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Buttons', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'button_width',
            [
                'label' => esc_html__('Column Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group.king-addons-form-field-type-submit' => 'width: {{SIZE}}%;',
                    '{{WRAPPER}} .king-addons-step-buttons-wrap' => 'width: {{SIZE}}%;'
                ],
                'frontend_available' => true,
            ]
        );

        $this->add_responsive_control(
            'buttons_width',
            [
                'label' => esc_html__('Step Buttons Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ]
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 150,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step-next' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step-tab .king-addons-button' => 'width: {{SIZE}}{{UNIT}};'
                ],
            ]
        );

        $this->add_responsive_control(
            'button_align',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors_dictionary' => [
                    'left' => 'margin-left: 0; margin-right: auto;',
                    'center' => 'margin-left: auto; margin-right: auto;',
                    'right' => 'margin-left: auto; margin-right: 0;'
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step-buttons-wrap' => '{{VALUE}}',
                    '{{WRAPPER}} .king-addons-fb-step-tab:first-of-type .king-addons-fb-step-next' => '{{VALUE}}',
                ],
            ]
        );


        $this->add_control(
            'heading_submit_button',
            [
                'label' => esc_html__('Submit Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Submit', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Send', 'king-addons'),
                'placeholder' => esc_html__('Send', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'selected_button_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'skin' => 'inline',
                'label_block' => false,
            ]
        );

        $this->add_control(
            'button_icon_align',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Before', 'king-addons'),
                    'right' => esc_html__('After', 'king-addons'),
                ],
                'condition' => [
                    'selected_button_icon[value]!' => '',
                ],
            ]
        );

        $this->add_control(
            'button_icon_indent',
            [
                'label' => esc_html__('Icon Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 50,
                    ],
                ],
                'condition' => [
                    'selected_button_icon[value]!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-button .king-addons-align-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-button .king-addons-align-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_css_id',
            [
                'label' => esc_html__('Button ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => esc_html__('Add your custom id WITHOUT the Pound key. e.g: my-id', 'king-addons'),
                'description' => esc_html__('Element ID should be unique and not used elsewhere in this widget', 'king-addons'),
                'separator' => 'before',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->end_controls_section();


        $this->start_controls_section(
            'section_form_settings',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT
            ]
        );

        $this->add_control(
            'form_name',
            [
                'label' => esc_html__('Form Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('New Form', 'king-addons'),
                'placeholder' => esc_html__('Form Name', 'king-addons'),
            ]
        );

        $this->add_control(
            'form_id',
            [
                'label' => esc_html__('Form ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'form_id',
                'description' => esc_html__('Form ID should be unique and shouldn\'t contain spaces', 'king-addons'),
                'separator' => 'after',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'success_message',
            [
                'label' => esc_html__('Success Message', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Submission successful', 'king-addons'),
                'placeholder' => esc_html__('Submission successful', 'king-addons'),
                'label_block' => true,
                'frontend_available' => true,


                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'error_message',
            [
                'label' => esc_html__('Error Message', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Submission failed', 'king-addons'),
                'placeholder' => esc_html__('Submission failed', 'king-addons'),
                'label_block' => true,
                'frontend_available' => true,


                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'show_labels',
            [
                'label' => esc_html__('Show Field Labels', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
                'return_value' => 'true',
                'default' => 'true',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'show_placeholders',
            [
                'label' => esc_html__('Show Placeholders', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
                'return_value' => 'true',
                'default' => 'true'
            ]
        );

        $this->add_control(
            'label_position',
            [
                'label' => esc_html__('Label Position', 'king-addons'),
                'type' => Controls_Manager::HIDDEN,
                'options' => [
                    'above' => esc_html__('Above', 'king-addons'),
                    'inline' => esc_html__('Inline', 'king-addons'),
                ],
                'default' => 'above',
                'condition' => [
                    'show_labels!' => '',
                ],
            ]
        );

        $this->add_control(
            'mark_required',
            [
                'label' => esc_html__('Show Required Mark', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
                'default' => '',
                'condition' => [
                    'show_labels!' => '',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_integration',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Actions', 'king-addons'),
            ]
        );


        $default_submit_actions = ['email'];

        $this->add_control(
            'submit_actions',
            [
                'label' => esc_html__('Add Action', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->submit_action_args(),
                'render_type' => 'none',
                'label_block' => true,
                'default' => $default_submit_actions,
                'description' => esc_html__('Select the actions to perform after a user submits the form (e.g., sending an email notification). Once an action is selected, its corresponding settings will be displayed below.', 'king-addons'),
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $this->add_control(
                'submit_actions_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => __('<strong>Submission</strong> and <strong>Mailchimp</strong> actions are only available <br> in the <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-form-builder-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>'),
                    'content_classes' => 'king-addons-pro-notice'
                ]
            );
        }

        $this->end_controls_section();

        $this->register_settings_section_submissions($this);

        $this->register_settings_section_email($this);

        $this->register_settings_section_webhook($this);

        $this->register_settings_section_redirect($this);

        $this->register_settings_section_mailchimp();

        $this->start_controls_section(
            'section_form_step_settings',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Steps', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT
            ]
        );

        $this->add_control(
            'step_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'frontend_available' => true,
                'render_type' => 'template',
                'options' => [
                    'none' => 'None',
                    'text' => 'Label',
                    'icon' => 'Icon',
                    'number' => 'Number',
                    'progress_bar' => 'Progress Bar',
                    'number_text' => 'Number & Label',
                    'icon_text' => 'Icon & Label',
                ],
                'prefix_class' => 'king-addons-fb-step-type-',
                'default' => 'number_text'
            ]
        );

        $this->add_control(
            'step_content_layout',
            [
                'label' => esc_html__('Content Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'frontend_available' => true,
                'options' => [
                    'horizontal' => 'Horizontal',
                    'vertical' => 'Vertical',
                ],
                'default' => 'vertical',
                'prefix_class' => 'king-addons-fb-step-content-layout-',
                'condition' => [
                    'step_type!' => ['progress_bar', 'none']
                ]
            ]
        );

        $this->add_control(
            'show_separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => [
                    'step_type!' => 'progress_bar'
                ]
            ]
        );

        $this->add_responsive_control(
            'step_box_align',
            [
                'label' => esc_html__('Box Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}}.king-addons-fb-step-content-layout-vertical .king-addons-fb-step' => 'align-items: {{VALUE}}',
                    '{{WRAPPER}}.king-addons-fb-step-content-layout-horizontal .king-addons-fb-step' => 'justify-content: {{VALUE}}'
                ],
                'condition' => [
                    'step_type!' => ['progress_bar', 'none']
                ]
            ]
        );

        $this->add_responsive_control(
            'step_align',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step' => 'text-align: {{VALUE}}'
                ],
                'condition' => [
                    'step_type!' => ['progress_bar', 'none']
                ]
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_form_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Form', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group > label, {{WRAPPER}} .king-addons-field-sub-group label' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'mark_required_color',
            [
                'label' => esc_html__('Mark Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#CB3030',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-required-mark .king-addons-form-field-label:after' => 'color: {{COLOR}};',
                ],
                'condition' => [
                    'mark_required' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .king-addons-field-group > label'
            ]
        );

        $this->add_control(
            'label_spacing',
            [
                'label' => esc_html__('Distance', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 0,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'selectors' => [
                    'body.rtl {{WRAPPER}} .king-addons-labels-inline .king-addons-field-group > label' => 'padding-left: {{SIZE}}{{UNIT}};',

                    'body:not(.rtl) {{WRAPPER}} .king-addons-labels-inline .king-addons-field-group > label' => 'padding-right: {{SIZE}}{{UNIT}};',

                    'body {{WRAPPER}} .king-addons-labels-above .king-addons-field-group > label' => 'padding-bottom: {{SIZE}}{{UNIT}};',

                ],
            ]
        );

        $this->add_control(
            'heading_label',
            [
                'label' => esc_html__('Inputs', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'column_gap',
            [
                'label' => esc_html__('Horizontal Distance', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 10,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group' => 'padding-right: calc( {{SIZE}}{{UNIT}}/2 ); padding-left: calc( {{SIZE}}{{UNIT}}/2 );',
                    '{{WRAPPER}} .king-addons-fb-step-wrap' => 'padding-left: calc( -{{SIZE}}{{UNIT}}/2 ); padding-right: calc( -{{SIZE}}{{UNIT}}/2 );',
                    '{{WRAPPER}} .king-addons-step-buttons-wrap' => 'padding-right: calc( {{SIZE}}{{UNIT}}/2 ); padding-left: calc( {{SIZE}}{{UNIT}}/2 );',
                    '{{WRAPPER}} .king-addons-form-fields-wrap' => 'margin-left: calc( -{{SIZE}}{{UNIT}}/2 ); margin-right: calc( -{{SIZE}}{{UNIT}}/2 );',
                ],
            ]
        );

        $this->add_control(
            'row_gap',
            [
                'label' => esc_html__('Vertical Distance', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 10,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-step-buttons-wrap)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-field-group.recaptcha-v3-bottomleft, {{WRAPPER}} .king-addons-field-group.recaptcha-v3-bottomright' => 'margin-bottom: 0;',
                ],
            ]
        );

        $this->add_responsive_control(
            'labels_align',
            [
                'label' => esc_html__('Align Labels', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-step-buttons-wrap)' => 'justify-content: {{VALUE}}'
                ],
                'separator' => 'before'
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_field_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Field', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('tabs_fields_style');

        $this->start_controls_tab(
            'tab_fields_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'field_text_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group .king-addons-form-field' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-form-field svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="radio"] + label' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="checkbox"] + label' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'field_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap)' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'field_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap)' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap::before' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'field_typography',
                'selector' => '{{WRAPPER}} .king-addons-field-group .king-addons-form-field, {{WRAPPER}} .king-addons-field-sub-group label'
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_fields_focus',
            [
                'label' => esc_html__('Focus', 'king-addons'),
            ]
        );

        $this->add_control(
            'field_text_color_focus',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group .king-addons-form-field:focus' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="radio"]:focus' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="checkbox"]:focus' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'field_background_color_focus',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap):focus' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select:focus' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'field_border_color_focus',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap):focus' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select:focus' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap:focus-within::before' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_fields_error',
            [
                'label' => esc_html__('Error', 'king-addons'),
            ]
        );

        $this->add_control(
            'field_text_color_error',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#CB3030',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group .king-addons-form-field.king-addons-form-error' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="radio"].king-addons-form-error' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="checkbox"].king-addons-form-error' => 'color: {{VALUE}};',

                ]
            ]
        );

        $this->add_control(
            'field_background_color_error',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap).king-addons-form-error' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select.king-addons-form-error' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'field_border_color_error',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#CB3030',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap).king-addons-form-error' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select.king-addons-form-error' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap.king-addons-form-error-wrap::before' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'field_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'placeholder' => '1',
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap)' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before'
            ]
        );

        $this->add_control(
            'field_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 3,
                    'right' => 3,
                    'bottom' => 3,
                    'left' => 3,
                    'unit' => 'px'
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'field_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 6,
                    'right' => 5,
                    'bottom' => 7,
                    'left' => 10,
                    'unit' => 'px'
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-field-group:not(.king-addons-form-field-type-upload) .king-addons-form-field:not(.king-addons-select-wrap)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-field-group .king-addons-select-wrap select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="date"]::before' => 'right: {{RIGHT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-field-group input[type="time"]::before' => 'right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'radio_and_checkbox_distance',
            [
                'label' => esc_html__('Radio & Checkbox', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'radion_&_checkbox_padding',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px'
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field-option' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'radion_&_checkbox_gutter',
            [
                'label' => esc_html__('Inner Gutter', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 5,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field-option label' => 'margin-left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.king-addons-custom-styles-yes .king-addons-form-field-option label:before' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before'
            ]
        );

        $this->end_controls_section();


        $this->start_controls_section(
            'section_style_checkbox_radio',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Checkbox & Radio', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            ]
        );

        $this->add_control(
            'checkbox_radio_custom',
            [
                'label' => esc_html__('Use Custom Styles', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'prefix_class' => 'king-addons-custom-styles-'
            ]
        );

        $this->add_control(
            'checkbox_radio_static_color',
            [
                'label' => esc_html__('Static Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field-type-checkbox .king-addons-form-field-option label:before' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .king-addons-form-field-type-radio .king-addons-form-field-option label:before' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'checkbox_radio_custom' => 'yes'
                ]
            ]
        );

        $this->add_control(
            'checkbox_radio_active_color',
            [
                'label' => esc_html__('Active Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field-type-checkbox .king-addons-form-field-option label:before' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .king-addons-form-field-type-radio .king-addons-form-field-option label:before' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'checkbox_radio_custom' => 'yes'
                ]
            ]
        );

        $this->add_control(
            'checkbox_radio_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#E8E8E8',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field-type-checkbox .king-addons-form-field-option label:before' => 'border-color: {{VALUE}}',
                    '{{WRAPPER}} .king-addons-form-field-type-radio .king-addons-form-field-option label:before' => 'border-color: {{VALUE}}',
                ],
                'condition' => [
                    'checkbox_radio_custom' => 'yes'
                ]
            ]
        );

        $this->add_control(
            'checkbox_radio_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field-type-checkbox .king-addons-form-field-option label:before' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} / 1.3);',
                    '{{WRAPPER}} .king-addons-form-field-type-radio .king-addons-form-field-option label:before' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} / 1.3);',
                    '{{WRAPPER}} .king-addons-form-field-type-checkbox input' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-form-field-type-radio input' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
                ],
                'separator' => 'before',
                'condition' => [
                    'checkbox_radio_custom' => 'yes'
                ]
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_button_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Buttons', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'heading_next_submit_button',
            [
                'label' => esc_html__('Submit Button, Next Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-next' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-button[type="submit"]' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-next' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-button[type="submit"]' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-double-bounce .king-addons-child' => 'background-color: {{VALUE}} !important;',
                    '{{WRAPPER}} .king-addons-button[type="submit"] svg *' => 'fill: {{VALUE}};'
                ],
            ]
        );

        $this->add_control(
            'button_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-next' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-button[type="submit"]' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'button_border_border!' => '',
                ],
            ]
        );

        $this->add_control(
            'heading_previous_button',
            [
                'label' => esc_html__('Previous Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'previous_button_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'previous_button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'previous_button_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'button_border_border!' => '',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .king-addons-button, {{WRAPPER}} .king-addons-fb-step-prev, {{WRAPPER}} .king-addons-fb-step-next',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'heading_next_submit_button_hover',
            [
                'label' => esc_html__('Next & Submit Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-next:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-button[type="submit"]:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-next:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-button[type="submit"]:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-button[type="submit"]:hover svg *' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-next:hover' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-button[type="submit"]:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'button_border_border!' => '',
                ],
            ]
        );

        $this->add_control(
            'heading_previous_button_hover',
            [
                'label' => esc_html__('Previous Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'previous_button_background_color_hover',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'previous_button_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'previous_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-prev:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'button_border_border!' => '',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(), [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .king-addons-button, {{WRAPPER}} .king-addons-fb-step-prev, {{WRAPPER}} .king-addons-fb-step-next',
                'exclude' => [
                    'color',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step-prev' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step-next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ],
            ]
        );

        $this->add_control(
            'button_text_padding',
            [
                'label' => esc_html__('Text Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step-prev' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step-next' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_step_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Step', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('tabs_step_style');

        $this->start_controls_tab(
            'tab_step_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'main_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-main-label' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'sub_label_color',
            [
                'label' => esc_html__('Sub Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-sub-label' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step' => 'background-color: {{VALUE}};'
                ],
            ]
        );

        $this->add_control(
            'step_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step' => 'border-color: {{VALUE}};'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_step_active',
            [
                'label' => esc_html__('Active', 'king-addons'),
            ]
        );

        $this->add_control(
            'main_label_color_active',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-active .king-addons-fb-step-main-label' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'sub_label_color_active',
            [
                'label' => esc_html__('Sub Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-active .king-addons-fb-step-sub-label' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_bg_color_active',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-active' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_border_color_active',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-active' => 'border-color: {{VALUE}};'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_step_finished',
            [
                'label' => esc_html__('Finished', 'king-addons'),
            ]
        );

        $this->add_control(
            'main_label_color_finish',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-finish .king-addons-fb-step-main-label' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'sub_label_color_finish',
            [
                'label' => esc_html__('Sub Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7a7a7a',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-finish .king-addons-fb-step-sub-label' => 'color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_bg_color_finish',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-finish' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_border_color_finish',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-finish' => 'border-color: {{VALUE}};'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'step_wrap_distance',
            [
                'label' => esc_html__('Distance', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before'
            ]
        );

        $this->add_responsive_control(
            'step_wrap_gutter',
            [
                'label' => esc_html__('Gutter', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 25
                    ]
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 5
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-sep' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}}',
                    '{{WRAPPER}} .king-addons-separator-off .king-addons-fb-step:not(:last-of-type)' => 'margin-right: {{SIZE}}{{UNIT}}'
                ],
                'separator' => 'before'
            ]
        );

        $this->add_control(
            'step_border_type',
            [
                'label' => esc_html__('Border Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'double' => esc_html__('Double', 'king-addons'),
                    'dotted' => esc_html__('Dotted', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'groove' => esc_html__('Groove', 'king-addons'),
                ],
                'default' => 'solid',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step' => 'border-style: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'step_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'default' => [
                    'top' => 1,
                    'right' => 1,
                    'bottom' => 1,
                    'left' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'step_border_type!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'step_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'step_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'unit' => 'px',
                    'top' => 10,
                    'right' => 10,
                    'bottom' => 10,
                    'left' => 10,
                ],
                'selectors' => [

                    '{{WRAPPER}} .king-addons-fb-step' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}}' => '--king-addons-fb-steps-padding: {{TOP}}{{UNIT}};'
                ],
            ]
        );

        $this->add_control(
            'step_inner_styles',
            [
                'label' => esc_html__('Step Indicator', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs('tabs_step_inner_style');

        $this->start_controls_tab(
            'tab_step_inner_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'step_inner_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-content i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-content svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-content' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'step_inner_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-content' => 'background-color: {{VALUE}};'
                ],
            ]
        );

        $this->add_control(
            'step_inner_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-content' => 'border-color: {{VALUE}};'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_step_inner_active',
            [
                'label' => esc_html__('Active', 'king-addons'),
            ]
        );

        $this->add_control(
            'step_inner_color_active',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-active .king-addons-fb-step-content i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-active .king-addons-fb-step-content svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-active .king-addons-fb-step-content' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'step_inner_bg_color_active',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-active .king-addons-fb-step-content' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_inner_border_color_active',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-active .king-addons-fb-step-content' => 'border-color: {{VALUE}};'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_step_inner_finish',
            [
                'label' => esc_html__('Finish', 'king-addons'),
            ]
        );

        $this->add_control(
            'step_inner_color_finish',
            [
                'label' => esc_html__('Color (Labels, Icon, Number)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-finish .king-addons-fb-step-content i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-finish .king-addons-fb-step-content svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-finish .king-addons-fb-step-content' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_control(
            'step_inner_bg_color_finish',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-finish .king-addons-fb-step-content' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_inner_border_color_finish',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step.king-addons-fb-step-finish .king-addons-fb-step-content' => 'border-color: {{VALUE}};'
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'step_inner_border_type',
            [
                'label' => esc_html__('Border Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'double' => esc_html__('Double', 'king-addons'),
                    'dotted' => esc_html__('Dotted', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'groove' => esc_html__('Groove', 'king-addons'),
                ],
                'default' => 'solid',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-content' => 'border-style: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'step_inner_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'default' => [
                    'top' => 1,
                    'right' => 1,
                    'bottom' => 1,
                    'left' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-content' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'step_inner_border_type!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'step_inner_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );


        $this->add_responsive_control(
            'step_inner_padding',
            [
                'label' => esc_html__('Box Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--king-addons-fb-steps-indicator-padding: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before'
            ]
        );

        $this->add_responsive_control(
            'step_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 25,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 12,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'step_type' => ['icon', 'icon_text']
                ]
            ]
        );

        $this->add_responsive_control(
            'step_label_distance',
            [
                'label' => esc_html__('Distance', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 5,
                ],
                'selectors' => [
                    '{{WRAPPER}}.king-addons-fb-step-content-layout-horizontal .king-addons-fb-step-label' => 'margin-left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.king-addons-fb-step-content-layout-vertical .king-addons-fb-step-label' => 'margin-top: {{SIZE}}{{UNIT}};'
                ],
                'separator' => 'before',
                'condition' => [
                    'step_type' => ['number_text', 'icon_text']
                ]
            ]
        );

        $this->add_control(
            'step_divider',
            [
                'label' => esc_html__('Divider', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'step_type!' => 'progress_bar'
                ]
            ]
        );

        $this->add_control(
            'step_progressbar',
            [
                'label' => esc_html__('Progressbar', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'step_type' => 'progress_bar'
                ]
            ]
        );

        $this->add_control(
            'step_divider_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-sep' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-fb-step-progress' => 'background-color: {{VALUE}};',
                ]
            ]
        );

        $this->add_control(
            'step_progress_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-progress-fill' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'step_type' => 'progress_bar'
                ]
            ]
        );

        $this->add_control(
            'step_progress_fill_color',
            [
                'label' => esc_html__('Fill Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-progress-fill' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'step_type' => 'progress_bar'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'step_percent_typo',
                'selector' => '{{WRAPPER}} .king-addons-fb-step-progress-fill',
                'condition' => [
                    'step_type' => 'progress_bar'
                ]
            ]
        );

        $this->add_responsive_control(
            'step_divider_height',
            [
                'label' => esc_html__('Divider Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 2,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--king-addons-fb-steps-divider-width: {{SIZE}}{{UNIT}};'
                ],
                'condition' => [
                    'step_type!' => 'progress_bar'
                ]
            ]
        );

        $this->add_responsive_control(
            'step_progress_text_distance',
            [
                'label' => esc_html__('Text Distance', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-progress-fill' => 'padding-right: {{SIZE}}{{UNIT}};'
                ],
                'condition' => [
                    'step_type' => 'progress_bar'
                ]
            ]
        );

        $this->add_responsive_control(
            'step_progress_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-fb-step-progress' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-fb-step-progress-fill' => 'border-radius: {{SIZE}}{{UNIT}};'
                ],
                'condition' => [
                    'step_type' => 'progress_bar'
                ]
            ]
        );

        $this->add_control(
            'step_main_label',
            [
                'label' => esc_html__('Main Label', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'main_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-fb-step-main-label',
                'fields_options' => [
                    'typography' => [
                        'default' => 'custom',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => '12',
                            'unit' => 'px',
                        ],
                    ]
                ]
            ]
        );

        $this->add_control(
            'step_sub_label',
            [
                'label' => esc_html__('Sub Label', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'sub_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-fb-step-sub-label',
                'fields_options' => [
                    'typography' => [
                        'default' => 'custom',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => '12',
                            'unit' => 'px',
                        ],
                    ]
                ]
            ]
        );

        $this->add_control(
            'step_number_heading',
            [
                'label' => esc_html__('Number', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'step_type' => ['number', 'number_text']
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'step_number',
                'selector' => '{{WRAPPER}} .king-addons-fb-step-number',
                'fields_options' => [
                    'typography' => [
                        'default' => 'custom',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => '12',
                            'unit' => 'px',
                        ],
                    ]
                ],
                'condition' => [
                    'step_type' => ['number', 'number_text']
                ]
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_results_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Results', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'results_typography',
                'selector' => '{{WRAPPER}} .king-addons-submit-success, {{WRAPPER}} .king-addons-submit-error',
                'fields_options' => [
                    'typography' => [
                        'default' => 'custom',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => '12',
                            'unit' => 'px',
                        ],
                    ]
                ]
            ]
        );

        $this->add_control(
            'success_result_color',
            [
                'label' => esc_html__('Success Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#30CBCB',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-submit-success' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'error_message_color',
            [
                'label' => esc_html__('Error Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#CB3030',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-submit-error' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'finish_message_align',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-submit-success' => 'text-align: {{VALUE}}',
                    '{{WRAPPER}} .king-addons-submit-error' => 'text-align: {{VALUE}}'
                ],
            ]
        );

        $this->end_controls_section();


        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'king-addons-form-builder', [
            'Unlimited number of fields',
            'Submission action',
            'Mailchimp action',
            'Webhook action'
        ]);
    }

    private function add_required_attribute($element)
    {
        $this->add_render_attribute($element, 'required', 'required');
        $this->add_render_attribute($element, 'aria-required', 'true');
    }

    public function get_attribute_name($item)
    {
        return "form_fields[{$item['field_id']}]";
    }

    public function get_attribute_id($item)
    {

        $id_suffix = !empty($item['field_id']) ? $item['field_id'] : $item['_id'];
        return 'form-field-' . $id_suffix;
    }

    protected function make_textarea_field($item, $item_index)
    {
        $this->add_render_attribute('textarea' . $item_index, [
            'class' => [
                'king-addons-form-field-textual',
                'king-addons-form-field',
                esc_attr($item['css_classes'])
            ],
            'name' => $this->get_attribute_name($item),
            'id' => $this->get_attribute_id($item),
            'rows' => $item['rows'],
        ]);

        if ('true' == $this->get_settings_for_display()['show_placeholders'] && $item['placeholder']) {
            $this->add_render_attribute('textarea' . $item_index, 'placeholder', $item['placeholder']);
        }

        if ($item['required']) {
            $this->add_required_attribute('textarea' . $item_index);
        }

        $value = empty($item['field_value']) ? '' : $item['field_value'];

        return '<textarea ' . $this->get_render_attribute_string('textarea' . $item_index) . '>' . $value . '</textarea>';
    }

    protected function make_select_field($item, $i)
    {
        $this->add_render_attribute(
            [
                'select-wrapper' . $i => [
                    'class' => [
                        'king-addons-form-field',
                        'king-addons-select-wrap',
                        'king-addons-fi-svg-' . (Plugin::$instance->experiments->is_feature_active('e_font_icon_svg') ? 'yes' : 'no'),
                        'remove-before',
                        esc_attr($item['css_classes']),
                    ],
                ],
                'select' . $i => [
                    'name' => $this->get_attribute_name($item) . (!empty($item['allow_multiple']) ? '[]' : ''),
                    'id' => $this->get_attribute_id($item),
                    'class' => [
                        'king-addons-form-field-textual'
                    ],
                ],
            ]
        );

        if ($item['required']) {
            $this->add_required_attribute('select' . $i);
        }

        if ($item['allow_multiple']) {
            $this->add_render_attribute('select' . $i, 'multiple');
            if (!empty($item['select_size'])) {
                $this->add_render_attribute('select' . $i, 'size', $item['select_size']);
            }
        }

        $options = preg_split("/\\r\\n|\\r|\\n/", $item['field_options']);

        if (!$options) {
            return '';
        }

        ob_start();
        ?>
        <div <?php $this->print_render_attribute_string('select-wrapper' . $i); ?>>

            <?php if (Plugin::$instance->experiments->is_feature_active('e_font_icon_svg')) { ?>
                <svg class="e-font-icon-svg e-eicon-caret-up" viewBox="0 0 1000 500">
                    <path d="M763 279c8-8 8-12 8-25 0-8-4-16-8-25-9-8-13-8-25-8h-459c-8 0-16 4-25 8 0 9-4 17-4 25 0 9 4 17 8 25l230 229c8 5 16 9 25 9 8 0 16-4 25-9l225-229z"/>
                </svg>
            <?php } ?>
            <!--suppress HtmlFormInputWithoutLabel -->
            <select <?php $this->print_render_attribute_string('select' . $i); ?>>
                <?php
                foreach ($options as $key => $option) :
                    $option_id = $item['field_id'] . $key;
                    $option_value = esc_attr($option);
                    $option_label = esc_html($option);

                    if (false !== strpos($option, '|')) {
                        list($label, $value) = explode('|', $option);
                        $option_value = esc_attr($value);
                        $option_label = esc_html($label);
                    }

                    $this->add_render_attribute($option_id, 'value', $option_value);


                    if (!empty($item['field_value']) && in_array($option_value, explode(',', $item['field_value']))) {
                        $this->add_render_attribute($option_id, 'selected', 'selected');
                    } ?>
                    <option <?php $this->print_render_attribute_string($option_id); ?>>
                        <?php

                        echo $option_label;
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php

        $select = ob_get_clean();
        return $select;
    }

    protected function make_radio_checkbox_field($item, $item_index, $type)
    {
        $options = preg_split("/\\r\\n|\\r|\\n/", $item['field_options']);
        $html = '';
        if ($options) {
            $html .= '<div class="king-addons-field-sub-group ' . esc_attr($item['css_classes']) . ' ' . esc_attr($item['inline_list']) . '">';
            foreach ($options as $key => $option) {
                $element_id = ($item['field_id'] ? esc_attr($item['field_id']) : $item['field_type']) . $key;
                $html_id = $this->get_attribute_id($item) . '-' . $key;
                $option_label = $option;
                $option_value = $option;

                if (false !== strpos($option, '|')) {
                    list($option_label, $option_value) = explode('|', $option);
                }

                $this->add_render_attribute(
                    $element_id,
                    [
                        'type' => $type,
                        'value' => $option_value,
                        'id' => $html_id,
                        'name' => $this->get_attribute_name($item) . (('checkbox' === $type && count($options) > 1) ? '[]' : ''),
                    ]
                );

                if (!empty($item['field_value']) && $option_value === $item['field_value']) {
                    $this->add_render_attribute($element_id, 'checked', 'checked');
                }

                if ($item['required'] && ('radio' === $type || 'checkbox' === $type)) {
                    $this->add_required_attribute($element_id);
                }

                $html .= '<span class="king-addons-form-field-option" data-key="form-field-' . esc_attr($item['field_id']) . '"><input ' . $this->get_render_attribute_string($element_id) . '> <label for="' . esc_attr($html_id) . '">' . $option_label . '</label></span>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    protected function form_fields_render_attributes($i, $instance, $item)
    {
        $this->add_render_attribute(
            [
                'field-group' . $i => [
                    'class' => [
                        'king-addons-form-field-type-' . $item['field_type'],
                        'king-addons-field-group',
                        'king-addons-column',
                        'king-addons-field-group-' . esc_attr($item['field_id']),
                    ],
                ],
                'input' . $i => [
                    'type' => ('acceptance' === $item['field_type']) ? 'checkbox' : (('upload' === $item['field_type']) ? 'file' : $item['field_type']),
                    'name' => $this->get_attribute_name($item),
                    'id' => $this->get_attribute_id($item),
                    'class' => [
                        'king-addons-form-field',
                        empty($item['css_classes']) ? '' : esc_attr($item['css_classes']),
                    ],
                ],
                'label' . $i => [
                    'for' => $this->get_attribute_id($item),
                    'class' => 'king-addons-form-field-label',
                ],
            ]
        );

        if (empty($item['width'])) {
            $item['width'] = '100';
        }


        if ($item['allow_multiple']) {
            $this->add_render_attribute('field-group' . $i, 'class', 'king-addons-form-field-type-' . $item['field_type'] . '-multiple');
        }


        $this->add_render_attribute('field-group' . $i, 'class', 'elementor-repeater-item-' . esc_attr($item['_id']));


        if ('true' == $instance['show_placeholders'] && !Utils::is_empty($item['placeholder'])) {
            $this->add_render_attribute('input' . $i, 'placeholder', $item['placeholder']);
        }

        if (!empty($item['field_value'])) {
            $this->add_render_attribute('input' . $i, 'value', $item['field_value']);
        }

        if (!$instance['show_labels']) {
            $this->add_render_attribute('label' . $i, 'class', 'king-addons-hidden-element');
        }

        if (!empty($item['required'])) {
            $class = 'king-addons-form-field-required';
            if (!empty($instance['mark_required'])) {
                $class .= ' king-addons-required-mark';
            }
            $this->add_render_attribute('field-group' . $i, 'class', $class);
            $this->add_required_attribute('input' . $i);
        }
    }

    private function render_form_icon($settings)
    { ?>
        <span <?php echo $this->get_render_attribute_string('icon-align'); ?>>
			<?php Icons_Manager::render_icon($settings['selected_button_icon'], ['aria-hidden' => 'true']); ?>
            <?php if (empty($instance['button_text'])) : ?>
                <span class="king-addons-hidden-element"><?php echo esc_html__('Submit', 'king-addons'); ?></span>
            <?php endif; ?>
		</span>
    <?php }

    public function render_submit_button($instance)
    {
        ?>
        <button type="submit" <?php echo $this->get_render_attribute_string('button'); ?>>
				<span <?php echo $this->get_render_attribute_string('content-wrapper'); ?>>
					<?php if (!empty($instance['selected_button_icon']) && 'left' === $instance['button_icon_align']) : ?>
                        <?php $this->render_form_icon($instance); ?>
                    <?php endif; ?>
                    <?php if (!empty($instance['button_text'])) : ?>
                        <span><?php $this->print_unescaped_setting('button_text'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($instance['selected_button_icon']) && 'right' === $instance['button_icon_align']) : ?>
                        <?php $this->render_form_icon($instance); ?>
                    <?php endif; ?>
				</span>
            <div class="king-addons-double-bounce king-addons-loader-hidden">
                <div class="king-addons-child king-addons-double-bounce1"></div>
                <div class="king-addons-child king-addons-double-bounce2"></div>
            </div>
        </button>
        <?php
    }

    protected function render()
    {
        global $post;
        $instance = $this->get_settings_for_display();

        $form_fields_length = sizeof($instance['form_fields']);
        $thisId = $this->get_id();

        update_option('king_addons_email_content_type_' . $this->get_id(), $instance['email_content_type']);
        update_option('king_addons_email_to_' . $this->get_id(), $instance['email_to']);
        update_option('king_addons_email_subject_' . $this->get_id(), $instance['email_subject']);
        update_option('king_addons_email_fields_' . $this->get_id(), $instance['email_content']);
        update_option('king_addons_cc_header_' . $this->get_id(), $instance['email_to_cc']);
        update_option('king_addons_bcc_header_' . $this->get_id(), $instance['email_to_bcc']);
        update_option('king_addons_email_from_' . $this->get_id(), $instance['email_from']);
        update_option('king_addons_email_from_name_' . $this->get_id(), $instance['email_from_name']);
        update_option('king_addons_reply_to_' . $this->get_id(), $instance['email_reply_to']);
        update_option('king_addons_meta_keys_' . $this->get_id(), $instance['form_metadata']);
        update_option('king_addons_referrer_' . $this->get_id(), home_url($_SERVER['REQUEST_URI']));
        update_option('king_addons_referrer_title_' . $this->get_id(), get_the_title($post->ID));
        update_option('king_addons_webhook_url_' . $this->get_id(), $instance['webhook_url']);

        $emailField = isset($instance['email_field']) ? $instance['email_field'] : '';
        $firstNameField = isset($instance['first_name_field']) ? $instance['first_name_field'] : '';
        $lastNameField = isset($instance['last_name_field']) ? $instance['last_name_field'] : '';
        $addressField = isset($instance['address_field']) ? $instance['address_field'] : '';
        $phoneField = isset($instance['phone_field']) ? $instance['phone_field'] : '';
        $birthdayField = isset($instance['birthday_field']) ? $instance['birthday_field'] : '';
        $groupId = isset($instance['mailchimp_groups']) ? $instance['mailchimp_groups'] : '';

        $fieldsArray = [
            'email_field' => $emailField,
            'first_name_field' => $firstNameField,
            'last_name_field' => $lastNameField,
            'address_field' => $addressField,
            'phone_field' => $phoneField,
            'birthday_field' => $birthdayField,
            'group_id' => $groupId
        ];

        $submit_actions = array_filter($instance['submit_actions'], function ($value) {
            return $value !== 'pro-sb' && $value !== 'pro-mch' && $value !== 'pro-wh';
        });
        $submit_actions = array_values($submit_actions);

        $this->add_render_attribute(
            [
                'wrapper' => [
                    'class' => [
                        'king-addons-form-fields-wrap',
                        'king-addons-labels-' . $instance['label_position'],
                    ],
                ],
                'submit-group' => [
                    'class' => [
                        'king-addons-field-group',
                        'king-addons-step-buttons-wrap',
                        'king-addons-column',
                        'king-addons-form-field-type-submit',
                    ],
                    'data-actions' => [
                        json_encode($submit_actions)
                    ],
                    'data-redirect-url' => [
                        in_array('redirect', $submit_actions) ? esc_url($instance['redirect_to']) : ''
                    ],
                    'data-mailchimp-fields' => [
                        json_encode($fieldsArray)
                    ],
                    'data-list-id' => [
                        isset($instance['mailchimp_audience']) ? esc_attr($instance['mailchimp_audience']) : ''
                    ]
                ],
                'button' => [
                    'class' => 'king-addons-button',
                ],
                'icon-align' => [
                    'class' => [
                        empty($instance['button_icon_align']) ? '' :
                            'king-addons-align-icon-' . $instance['button_icon_align'],
                        'elementor-button-icon',
                    ],
                ],
            ]
        );

        if (!empty($instance['form_id'])) {
            $this->add_render_attribute('form', 'id', $instance['form_id']);
        }

        if (!empty($instance['form_name'])) {
            $this->add_render_attribute('form', 'name', $instance['form_name']);
        }

        $this->add_render_attribute('form', 'page', get_post()->post_title);
        $this->add_render_attribute('form', 'page_id', get_post()->ID);

        if (!empty($instance['button_css_id'])) {
            $this->add_render_attribute('button', 'id', $instance['button_css_id']);
        }

        $referer_title = trim(wp_title('', false));

        if (!$referer_title && is_home()) {
            $referer_title = get_option('blogname');
        }

        ?>
        <form class="king-addons-form" method="post" <?php echo $this->get_render_attribute_string('form'); ?>
              novalidate>
            <input type="hidden" name="post_id" value="<?php
            echo get_the_ID();
            ?>"/>
            <input type="hidden" name="form_id" value="<?php echo esc_attr($this->get_id()); ?>"/>
            <input type="hidden" name="referer_title" value="<?php echo esc_attr($referer_title); ?>"/>

            <?php if (is_singular()) {

                ?>
                <input type="hidden" name="queried_id" value="<?php echo get_the_ID(); ?>"/>
            <?php }

            $step_count1 = 0;
            $step_exists = '';
            $step_icon = [];
            $step_label = [];
            $step_sub_label = [];
            $whitelist = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'odt', 'avi', 'ogg', 'm4a', 'mov', 'mp3', 'mp4', 'mpg', 'wav', 'wmv', 'txt'];

            foreach ($instance['form_fields'] as $key => $value) {
                if ('king-addons-fb-step' === $value['field_type']) {
                    $step_exists = 'exists';
                    $step_count1++;

                    ob_start();
                    Icons_Manager::render_icon($value['step_icon'], ['aria-hidden' => 'true']);
                    $step_icon[] = ob_get_clean();

                    $step_label[] = '<span class="king-addons-fb-step-main-label">' . $value['field_label'] . '</span>';

                    $step_sub_label[] = '<span class="king-addons-fb-step-sub-label">' . $value['field_sub_label'] . '</span>';
                }
            }


            $step_wrap_class = 'yes' !== $instance['show_separator'] ? 'king-addons-fb-step-wrap king-addons-separator-off' : 'king-addons-fb-step-wrap';

            echo '<div class="' . $step_wrap_class . '">';
            if ('progress_bar' == $instance['step_type']) {
                echo '<div class="king-addons-fb-step-progress">';
                echo '<div class="king-addons-fb-step-progress-fill"></div>';
                echo '</div>';
            } else {
                $i = 0;

                while ($i < $step_count1) :

                    if ('none' == $instance['step_type']) {
                        $step_html = '<span class="king-addons-fb-step"></span>';
                    } else if ('text' == $instance['step_type']) {
                        $step_html = '<span class="king-addons-fb-step">' . $step_label[$i] . $step_sub_label[$i] . '</span>';
                    } else if ('icon' == $instance['step_type']) {
                        $step_html = '<span class="king-addons-fb-step"><span class="king-addons-fb-step-content">' . $step_icon[$i] . '</span></span>';
                    } else if ('number' == $instance['step_type']) {
                        $step_html = '<span class="king-addons-fb-step"><span class="king-addons-fb-step-content"><span class="king-addons-fb-step-number">' . ($i + 1) . '</span></span></span>';
                    } else if ('number_text' == $instance['step_type']) {
                        $step_html = '<span class="king-addons-fb-step"><span class="king-addons-fb-step-content"><span class="king-addons-fb-step-number">' . ($i + 1) . '</span></span><span class="king-addons-fb-step-label">' . $step_label[$i] . $step_sub_label[$i] . '</span></span>';
                    } else if ('icon_text' == $instance['step_type']) {
                        $step_html = '<span class="king-addons-fb-step"><span class="king-addons-fb-step-content">' . $step_icon[$i] . '</span><span class="king-addons-fb-step-label">' . $step_label[$i] . $step_sub_label[$i] . '</span></span>';
                    }

                    echo $step_html;


                    if ('yes' == $instance['show_separator']) {
                        echo '<span class="king-addons-fb-step-sep"></span>';
                    }

                    $i++;
                endwhile;
            }
            echo '</div>';
            ?>

            <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
                <?php

                $step_count = 0;
                $field_count = 0;

                foreach ($instance['form_fields'] as $item_index => $item) :
                    if ('king-addons-fb-step' !== $item['field_type']) {
                        $field_count++;
                        if (!king_addons_freemius()->can_use_premium_code__premium_only() && 3 < $field_count) {
                            continue;
                        }
                    }

                    $this->form_fields_render_attributes($item_index, $instance, $item);

                    $print_label = !in_array($item['field_type'], ['hidden', 'html', 'king-addons-fb-step'], true);

                    if ('king-addons-fb-step' === $item['field_type']) {
                        if (isset($item['previous_button_text'])) {
                            $this->last_prev_btn_text = $item['previous_button_text'];
                        }

                        if (0 === $step_count) {
                            echo '<div class="king-addons-fb-step-tab king-addons-fb-step-tab-hidden">';
                        } else {
                            echo '<div class="king-addons-step-buttons-wrap">';
                            echo '<button type="button" class="king-addons-fb-step-prev">' . $item['previous_button_text'] . '</button>';
                            echo '<button type="button" class="king-addons-fb-step-next">' . $item['next_button_text'] . '</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '<div class="king-addons-fb-step-tab king-addons-fb-step-tab-hidden">';
                        }
                        $step_count++;
                    }

                    ?>
                    <div <?php $this->print_render_attribute_string('field-group' . $item_index); ?>>
                        <?php
                        if ($print_label && $item['field_label']) {
                            ?>
                            <label <?php echo $this->get_render_attribute_string('label' . $item_index); ?>>
                                <?php
                                echo $item['field_label']; ?>
                            </label>
                            <?php
                        }

                        switch ($item['field_type']) :
                            case 'html':
                                echo do_shortcode($item['field_html']);
                                break;
                            case 'textarea':

                                echo $this->make_textarea_field($item, $item_index);
                                break;

                            case 'select':

                                echo $this->make_select_field($item, $item_index);
                                break;

                            case 'radio':
                            case 'checkbox':

                                echo $this->make_radio_checkbox_field($item, $item_index, $item['field_type']);
                                break;
                            case 'recaptcha-v3':
                                echo '<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" data-site-key="' . get_option('king_addons_recaptcha_v3_site_key') . '" />';
                            case 'text':
                            case 'email':
                            case 'url':
                            case 'tel':
                            case 'password':
                            case 'hidden':
                            case 'search':
                            case 'number':
                            case 'date':
                            case 'time':
                                $this->add_render_attribute('input' . $item_index, 'class', 'king-addons-form-field-textual');
                                echo '<input size="1 "' . $this->get_render_attribute_string('input' . $item_index) . '>';
                                break;
                            case 'upload':
                                if ('yes' === $item['allow_multiple_upload']) {
                                    $this->add_render_attribute('input' . $item_index, 'multiple', 'multiple');
                                }

                                if (!empty($item['file_size'])) {
                                    $this->add_render_attribute(
                                        'input' . $item_index,
                                        [
                                            'data-maxfs' => $item['file_size'],
                                            'data-maxfs-notice' => esc_html__('File size is more than allowed.', 'king-addons'),
                                        ]
                                    );
                                }

                                if (!empty($item['file_types'])) {


                                    $file_types = explode(',', $item['file_types']);


                                    $non_whitelisted = array_diff($file_types, $whitelist);

                                    if (!empty($non_whitelisted)) {
                                        $item['file_types'] = 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,odt,avi,ogg,m4a,mov,mp3,mp4,mpg,wav,wmv,txt';
                                        if (is_admin()) {
                                            echo '<br>';
                                            echo '<ul class="king-addons-file-type-error">';
                                            echo esc_html__('Please remove unsupported file type(s):', 'king-addons');
                                            foreach ($non_whitelisted as $type) {
                                                if (!empty($type)) {
                                                    echo '<li>' . $type . ' <li/>';
                                                }
                                            }
                                            echo '</ul>';
                                        }
                                    }

                                    $this->add_render_attribute(
                                        'input' . $item_index,
                                        [
                                            'data-allft' => $item['file_types']
                                        ]
                                    );
                                }

                                echo '<input size="1 "' . $this->get_render_attribute_string('input' . $item_index) . '>';
                                break;
                            case 'king-addons-fb-step':
                                echo '<input type="hidden" class="king-addons-fb-step-input" id=form-field-' . esc_attr($item['field_id']) . ' value=' . $item['field_label'] . '>';
                                break;
                            default:
                                $field_type = $item['field_type'];
                        endswitch;
                        ?>
                    </div>
                <?php
                endforeach;

                echo '<div ' . $this->get_render_attribute_string('submit-group') . '>';
                if ('exists' === $step_exists) {
                    if (2 <= $step_count) {
                        echo '<button type="button" class="king-addons-fb-step-prev">' . $this->last_prev_btn_text . '</button>';
                    }

                    $this->render_submit_button($instance);

                    echo '</div>';
                } else {

                    $this->render_submit_button($instance);

                }
                echo '</div>'; ?>

            </div>
        </form>
        <?php
    }
}