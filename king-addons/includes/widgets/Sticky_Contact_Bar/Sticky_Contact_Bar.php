<?php
/**
 * Sticky Contact Bar Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a sticky contact bar with quick-action channels.
 */
class Sticky_Contact_Bar extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-sticky-contact-bar';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Sticky Contact Bar', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-sticky-contact-bar';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-contact-bar-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-contact-bar-script',
        ];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['contact', 'sticky', 'whatsapp', 'telegram', 'messenger'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     *
     * @return void
     */
    public function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_style_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $channels = $this->prepare_channels($settings['kng_channels'] ?? []);

        if (empty($channels)) {
            return;
        }

        $attrs = $this->get_wrapper_attributes($settings);
        ?>
        <div class="king-addons-sticky-contact-bar" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php if (($settings['kng_show_status'] ?? 'yes') === 'yes') : ?>
                <div class="king-addons-sticky-contact-bar__status" role="status" aria-live="polite">
                    <span class="king-addons-sticky-contact-bar__dot" aria-hidden="true"></span>
                    <span class="king-addons-sticky-contact-bar__status-text">
                        <?php echo esc_html($settings['kng_status_online_label'] ?? esc_html__('We are online', 'king-addons')); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (!empty($settings['kng_title']) && ($settings['kng_show_labels'] ?? 'yes') === 'yes') : ?>
                <div class="king-addons-sticky-contact-bar__title">
                    <?php echo esc_html($settings['kng_title']); ?>
                </div>
            <?php endif; ?>

            <div class="king-addons-sticky-contact-bar__list">
                <?php foreach ($channels as $channel) : ?>
                    <?php
                    $rel = $channel['is_external'] ? 'noopener noreferrer' : 'nofollow';
                    ?>
                    <a
                        class="king-addons-sticky-contact-bar__item"
                        data-availability="<?php echo esc_attr($channel['availability']); ?>"
                        data-channel="<?php echo esc_attr($channel['type']); ?>"
                        data-device="<?php echo esc_attr($channel['device']); ?>"
                        href="<?php echo esc_url($channel['url']); ?>"
                        <?php echo $channel['is_external'] ? 'target="_blank"' : ''; ?>
                        rel="<?php echo esc_attr($rel); ?>"
                    >
                        <?php if (!empty($channel['icon'])) : ?>
                            <span class="king-addons-sticky-contact-bar__icon">
                                <?php Icons_Manager::render_icon($channel['icon'], ['aria-hidden' => 'true']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (($settings['kng_show_labels'] ?? 'yes') === 'yes') : ?>
                            <span class="king-addons-sticky-contact-bar__label">
                                <?php echo esc_html($channel['label']); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Content controls.
     *
     * @param bool $is_pro Whether pro controls are needed.
     *
     * @return void
     */
    public function register_content_controls(bool $is_pro = false): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_title',
            [
                'label' => esc_html__('Helper Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Need quick help?', 'king-addons'),
                'description' => esc_html__('Shown above channels when labels are enabled.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_status_default',
            [
                'label' => esc_html__('Default Status', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'online',
                'options' => [
                    'online' => esc_html__('Online', 'king-addons'),
                    'offline' => esc_html__('Offline', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_status_online_label',
            [
                'label' => esc_html__('Online Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('We are online', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_status_offline_label',
            [
                'label' => esc_html__('Offline Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('We respond soon', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_status',
            [
                'label' => esc_html__('Show Status', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_labels',
            [
                'label' => esc_html__('Show Channel Labels', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_channel_label',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('WhatsApp', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_channel_type',
            [
                'label' => esc_html__('Channel', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'whatsapp',
                'options' => [
                    'whatsapp' => esc_html__('WhatsApp', 'king-addons'),
                    'telegram' => esc_html__('Telegram', 'king-addons'),
                    'messenger' => esc_html__('Facebook Messenger', 'king-addons'),
                    'phone' => esc_html__('Phone', 'king-addons'),
                    'email' => esc_html__('Email', 'king-addons'),
                    'link' => esc_html__('Custom Link', 'king-addons'),
                ],
            ]
        );

        $repeater->add_control(
            'kng_channel_value',
            [
                'label' => esc_html__('Destination', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Phone, @username, or email', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_channel_message',
            [
                'label' => esc_html__('Prefill Message', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Used for WhatsApp/Telegram', 'king-addons'),
                'condition' => [
                    'kng_channel_type' => ['whatsapp', 'telegram'],
                ],
            ]
        );

        $repeater->add_control(
            'kng_channel_url',
            [
                'label' => esc_html__('Custom URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://', 'king-addons'),
                'condition' => [
                    'kng_channel_type' => 'link',
                ],
            ]
        );

        $repeater->add_control(
            'kng_channel_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
            ]
        );

        $availability_options = [
            'always' => esc_html__('Always show', 'king-addons'),
            'online' => esc_html__('Show when online', 'king-addons'),
        ];

        if ($is_pro) {
            $availability_options['offline'] = esc_html__('Show when offline', 'king-addons');
        }

        $repeater->add_control(
            'kng_channel_availability',
            [
                'label' => esc_html__('Visibility', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'always',
                'options' => $availability_options,
            ]
        );

        if ($is_pro) {
            $repeater->add_control(
                'kng_channel_device',
                [
                    'label' => esc_html__('Device', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'all',
                    'options' => [
                        'all' => esc_html__('All devices', 'king-addons'),
                        'desktop' => esc_html__('Desktop only', 'king-addons'),
                        'mobile' => esc_html__('Mobile only', 'king-addons'),
                    ],
                ]
            );
        }

        $repeater->add_control(
            'kng_channel_new_tab',
            [
                'label' => esc_html__('Open in new tab', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_channels',
            [
                'label' => esc_html__('Channels', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'kng_channel_label' => esc_html__('WhatsApp', 'king-addons'),
                        'kng_channel_type' => 'whatsapp',
                        'kng_channel_value' => '+1 800 555 1234',
                        'kng_channel_message' => esc_html__('Hi! I need assistance.', 'king-addons'),
                        'kng_channel_new_tab' => 'yes',
                        'kng_channel_availability' => 'always',
                    ],
                    [
                        'kng_channel_label' => esc_html__('Call us', 'king-addons'),
                        'kng_channel_type' => 'phone',
                        'kng_channel_value' => '+1 800 555 9876',
                        'kng_channel_new_tab' => 'yes',
                        'kng_channel_availability' => 'always',
                    ],
                    [
                        'kng_channel_label' => esc_html__('Telegram', 'king-addons'),
                        'kng_channel_type' => 'telegram',
                        'kng_channel_value' => 'kingaddons',
                        'kng_channel_message' => esc_html__('Hello! I have a question.', 'king-addons'),
                        'kng_channel_new_tab' => 'yes',
                        'kng_channel_availability' => 'online',
                    ],
                ],
                'title_field' => '{{{ kng_channel_label }}}',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_edge',
            [
                'label' => esc_html__('Edge', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'right',
                'options' => [
                    'right' => esc_html__('Right', 'king-addons'),
                    'left' => esc_html__('Left', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_vertical_position',
            [
                'label' => esc_html__('Vertical Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'middle',
                'options' => [
                    'top' => esc_html__('Top', 'king-addons'),
                    'middle' => esc_html__('Middle', 'king-addons'),
                    'bottom' => esc_html__('Bottom', 'king-addons'),
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_offset',
            [
                'label' => esc_html__('Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 400,
                    ],
                ],
                'default' => [
                    'size' => 120,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Items Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @return void
     */
    public function register_style_controls(): void
    {
        $this->start_controls_section(
            'kng_style_box_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Box', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_box_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_box_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_box_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                    '%' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_box_border',
                'selector' => '{{WRAPPER}} .king-addons-sticky-contact-bar',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-sticky-contact-bar',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_status_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Status', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_status_typography',
                'selector' => '{{WRAPPER}} .king-addons-sticky-contact-bar__status-text, {{WRAPPER}} .king-addons-sticky-contact-bar__title',
            ]
        );

        $this->add_control(
            'kng_status_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__status-text' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_status_dot_online',
            [
                'label' => esc_html__('Dot Color (Online)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__dot' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_status_dot_offline',
            [
                'label' => esc_html__('Dot Color (Offline)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar.is-offline .king-addons-sticky-contact-bar__dot' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_items_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Channels', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_item_typography',
                'selector' => '{{WRAPPER}} .king-addons-sticky-contact-bar__label',
            ]
        );

        $this->add_control(
            'kng_item_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_item_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_item_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__item:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_item_radius',
            [
                'label' => esc_html__('Item Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-contact-bar__item' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_item_border',
                'selector' => '{{WRAPPER}} .king-addons-sticky-contact-bar__item',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render pro upsell section.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'sticky-contact-bar', [
                'Schedule-based online/offline states',
                'UTM-aware availability (campaign-specific)',
                'Per-channel visibility rules and offline presets',
                'Device-aware multi-channel bar with status indicator',
            ]);
        }
    }

    /**
     * Build wrapper attributes.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    public function get_wrapper_attributes(array $settings): string
    {
        $attrs = [
            'data-position' => $settings['kng_edge'] ?? 'right',
            'data-align' => $settings['kng_vertical_position'] ?? 'middle',
            'data-offset' => isset($settings['kng_offset']['size']) ? (string) $settings['kng_offset']['size'] : '120',
            'data-status-default' => $settings['kng_status_default'] ?? 'online',
            'data-online-label' => $settings['kng_status_online_label'] ?? esc_html__('We are online', 'king-addons'),
            'data-offline-label' => $settings['kng_status_offline_label'] ?? esc_html__('We respond soon', 'king-addons'),
            'data-show-labels' => (($settings['kng_show_labels'] ?? 'yes') === 'yes') ? 'yes' : 'no',
            'data-schedule-active' => 'no',
            'data-schedule-start' => '',
            'data-schedule-end' => '',
            'data-timezone-offset' => '0',
            'data-utm-key' => '',
            'data-utm-value' => '',
            'data-utm-state' => '',
        ];

        return $this->compile_attributes($attrs);
    }

    /**
     * Compile attribute array to string.
     *
     * @param array<string, string> $attrs Attributes.
     *
     * @return string
     */
    public function compile_attributes(array $attrs): string
    {
        $compiled = [];
        foreach ($attrs as $key => $value) {
            $compiled[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $compiled);
    }

    /**
     * Prepare channels data.
     *
     * @param array<int, array<string, mixed>> $channels Channels.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prepare_channels(array $channels): array
    {
        $prepared = [];

        foreach ($channels as $channel) {
            $label = trim($channel['kng_channel_label'] ?? '');
            $type = $channel['kng_channel_type'] ?? '';
            if ($label === '' || $type === '') {
                continue;
            }

            $url = $this->build_channel_url($channel);
            if ($url === '') {
                continue;
            }

            $prepared[] = [
                'label' => $label,
                'type' => $type,
                'url' => $url,
                'icon' => $channel['kng_channel_icon'] ?? [],
                'availability' => $channel['kng_channel_availability'] ?? 'always',
                'device' => $channel['kng_channel_device'] ?? 'all',
                'is_external' => ($channel['kng_channel_new_tab'] ?? '') === 'yes',
            ];
        }

        return $prepared;
    }

    /**
     * Build URL for a channel.
     *
     * @param array<string, mixed> $channel Channel data.
     *
     * @return string
     */
    public function build_channel_url(array $channel): string
    {
        $type = $channel['kng_channel_type'] ?? '';
        $value = trim($channel['kng_channel_value'] ?? '');
        $message = trim($channel['kng_channel_message'] ?? '');

        if ($type === 'link') {
            return $channel['kng_channel_url']['url'] ?? '';
        }

        if ($value === '') {
            return '';
        }

        switch ($type) {
            case 'whatsapp':
                $number = preg_replace('/[^0-9]/', '', $value);
                $url = 'https://wa.me/' . rawurlencode($number);
                if ($message !== '') {
                    $url .= '?text=' . rawurlencode($message);
                }
                return $url;
            case 'telegram':
                $username = ltrim($value, '@');
                $url = 'https://t.me/' . rawurlencode($username);
                if ($message !== '') {
                    $url .= '?text=' . rawurlencode($message);
                }
                return $url;
            case 'messenger':
                $page = ltrim($value, '/');
                return 'https://m.me/' . rawurlencode($page);
            case 'phone':
                return 'tel:' . rawurlencode(preg_replace('/\s+/', '', $value));
            case 'email':
                return 'mailto:' . rawurlencode($value);
            default:
                return '';
        }
    }
}




