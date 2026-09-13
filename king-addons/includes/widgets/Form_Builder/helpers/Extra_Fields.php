<?php
/**
 * Extra Form Builder field types.
 *
 * Range, rating, signature and acceptance live here rather than in
 * Form_Builder.php so the widget file stays readable.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controls and markup for the field types added on top of the original set.
 */
class Form_Extra_Fields
{
    /**
     * Field types this class renders.
     *
     * @return array<string,string>
     */
    public static function field_types(): array
    {
        return [
            'range' => esc_html__('Range Slider', 'king-addons'),
            'rating' => esc_html__('Rating', 'king-addons'),
            'signature' => esc_html__('Signature', 'king-addons'),
            'acceptance' => esc_html__('Acceptance', 'king-addons'),
            'calculation' => esc_html__('Calculation', 'king-addons'),
        ];
    }

    /**
     * Types that carry their own markup instead of a plain input.
     *
     * @return array<int,string>
     */
    public static function custom_markup_types(): array
    {
        return ['rating', 'signature', 'acceptance', 'calculation'];
    }

    /**
     * Add the per-field controls to the fields repeater.
     *
     * @param Repeater $repeater Fields repeater.
     *
     * @return void
     */
    public static function register_controls(Repeater $repeater): void
    {
        $repeater->add_control(
            'range_min',
            [
                'label' => esc_html__('Minimum', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => ['field_type' => 'range'],
            ]
        );

        $repeater->add_control(
            'range_max',
            [
                'label' => esc_html__('Maximum', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 100,
                'condition' => ['field_type' => 'range'],
            ]
        );

        $repeater->add_control(
            'range_step',
            [
                'label' => esc_html__('Step', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'default' => 1,
                'condition' => ['field_type' => 'range'],
            ]
        );

        $repeater->add_control(
            'range_show_value',
            [
                'label' => esc_html__('Show the value', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => ['field_type' => 'range'],
            ]
        );

        $repeater->add_control(
            'rating_max',
            [
                'label' => esc_html__('How many icons', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 2,
                'max' => 10,
                'default' => 5,
                'condition' => ['field_type' => 'rating'],
            ]
        );

        $repeater->add_control(
            'rating_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'star' => esc_html__('Star', 'king-addons'),
                    'heart' => esc_html__('Heart', 'king-addons'),
                    'circle' => esc_html__('Circle', 'king-addons'),
                ],
                'default' => 'star',
                'condition' => ['field_type' => 'rating'],
            ]
        );

        $repeater->add_control(
            'signature_height',
            [
                'label' => esc_html__('Height (px)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 80,
                'max' => 600,
                'default' => 160,
                'condition' => ['field_type' => 'signature'],
            ]
        );

        $repeater->add_control(
            'signature_clear_text',
            [
                'label' => esc_html__('Clear button', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Clear', 'king-addons'),
                'dynamic' => ['active' => true],
                'condition' => ['field_type' => 'signature'],
            ]
        );

        $repeater->add_control(
            'acceptance_text',
            [
                'label' => esc_html__('Consent text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('I agree to the terms.', 'king-addons'),
                'dynamic' => ['active' => true],
                'description' => esc_html__('Links and basic formatting are allowed.', 'king-addons'),
                'condition' => ['field_type' => 'acceptance'],
            ]
        );

        $repeater->add_control(
            'acceptance_checked',
            [
                'label' => esc_html__('Checked by default', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => ['field_type' => 'acceptance'],
            ]
        );

        $repeater->add_control(
            'calc_formula',
            [
                'label' => esc_html__('Formula', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '[seats] * 25 + [extras]',
                'description' => esc_html__('Reference other fields by their ID in square brackets. Supported: + - * / ( ) and numbers.', 'king-addons'),
                'condition' => ['field_type' => 'calculation'],
            ]
        );

        $repeater->add_control(
            'calc_decimals',
            [
                'label' => esc_html__('Decimals', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 6,
                'default' => 2,
                'condition' => ['field_type' => 'calculation'],
            ]
        );

        $repeater->add_control(
            'calc_prefix',
            [
                'label' => esc_html__('Prefix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
                'condition' => ['field_type' => 'calculation'],
            ]
        );

        $repeater->add_control(
            'calc_suffix',
            [
                'label' => esc_html__('Suffix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
                'condition' => ['field_type' => 'calculation'],
            ]
        );
    }

    /**
     * Markup for one of the added field types.
     *
     * @param array<string,mixed> $item Repeater item.
     * @param string              $name Input name attribute.
     * @param string              $id   Input id attribute.
     *
     * @return string
     */
    public static function render(array $item, string $name, string $id): string
    {
        switch ($item['field_type']) {
            case 'rating':
                return self::render_rating($item, $name, $id);
            case 'signature':
                return self::render_signature($item, $name, $id);
            case 'acceptance':
                return self::render_acceptance($item, $name, $id);
            case 'calculation':
                return self::render_calculation($item, $name, $id);
        }

        return '';
    }

    /**
     * Star (or heart, or circle) rating.
     *
     * Built from radio inputs so it works without JavaScript and reads
     * correctly to a screen reader; the hidden input carries the value the
     * form collector picks up.
     *
     * @param array<string,mixed> $item Repeater item.
     * @param string              $name Input name.
     * @param string              $id   Input id.
     *
     * @return string
     */
    private static function render_rating(array $item, string $name, string $id): string
    {
        $max = isset($item['rating_max']) ? (int) $item['rating_max'] : 5;
        if ($max < 2 || $max > 10) {
            $max = 5;
        }

        $icons = ['star' => '★', 'heart' => '♥', 'circle' => '●'];
        $icon_key = isset($item['rating_icon']) ? (string) $item['rating_icon'] : 'star';
        $icon = $icons[$icon_key] ?? $icons['star'];

        $required = self::is_required($item) ? ' data-ka-required="1"' : '';

        $html = '<div class="king-addons-form-rating" data-ka-rating="1" data-max="' . esc_attr((string) $max) . '"' . $required . '>';

        for ($i = 1; $i <= $max; $i++) {
            $html .= '<button type="button" class="king-addons-form-rating__item" data-value="' . esc_attr((string) $i) . '"'
                . ' aria-label="' . esc_attr(sprintf(
                    /* translators: 1: chosen value, 2: maximum. */
                    esc_html__('%1$d out of %2$d', 'king-addons'),
                    $i,
                    $max
                )) . '">'
                . '<span aria-hidden="true">' . esc_html($icon) . '</span></button>';
        }

        $html .= '<input type="hidden" class="king-addons-form-field" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" value="">';
        $html .= '</div>';

        return $html;
    }

    /**
     * Signature pad.
     *
     * @param array<string,mixed> $item Repeater item.
     * @param string              $name Input name.
     * @param string              $id   Input id.
     *
     * @return string
     */
    private static function render_signature(array $item, string $name, string $id): string
    {
        $height = isset($item['signature_height']) ? (int) $item['signature_height'] : 160;
        if ($height < 80 || $height > 600) {
            $height = 160;
        }

        $clear = isset($item['signature_clear_text']) && '' !== $item['signature_clear_text']
            ? (string) $item['signature_clear_text']
            : esc_html__('Clear', 'king-addons');

        $required = self::is_required($item) ? ' data-ka-required="1"' : '';

        return '<div class="king-addons-form-signature" data-ka-signature="1"' . $required . '>'
            . '<canvas class="king-addons-form-signature__pad" height="' . esc_attr((string) $height) . '"'
            . ' style="height:' . esc_attr((string) $height) . 'px"></canvas>'
            . '<button type="button" class="king-addons-form-signature__clear">' . esc_html($clear) . '</button>'
            . '<input type="hidden" class="king-addons-form-field" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" value="">'
            . '</div>';
    }

    /**
     * Consent checkbox with its own inline text.
     *
     * @param array<string,mixed> $item Repeater item.
     * @param string              $name Input name.
     * @param string              $id   Input id.
     *
     * @return string
     */
    private static function render_acceptance(array $item, string $name, string $id): string
    {
        $text = isset($item['acceptance_text']) ? (string) $item['acceptance_text'] : '';
        $checked = 'yes' === ($item['acceptance_checked'] ?? '') ? ' checked' : '';
        $required = self::is_required($item) ? ' required="required"' : '';

        return '<div class="king-addons-form-acceptance">'
            . '<input type="checkbox" class="king-addons-form-field" name="' . esc_attr($name) . '"'
            . ' id="' . esc_attr($id) . '" value="1"' . $checked . $required . '>'
            . '<label class="king-addons-form-acceptance__text" for="' . esc_attr($id) . '">'
            . wp_kses_post($text)
            . '</label>'
            . '</div>';
    }

    /**
     * A read-only field whose value is worked out from the others.
     *
     * The formula is evaluated in the browser by a small parser - never by
     * eval() - and the result lands in a read-only input so it travels with the
     * submission like any other value.
     *
     * @param array<string,mixed> $item Repeater item.
     * @param string              $name Input name.
     * @param string              $id   Input id.
     *
     * @return string
     */
    private static function render_calculation(array $item, string $name, string $id): string
    {
        $formula = isset($item['calc_formula']) ? (string) $item['calc_formula'] : '';
        $decimals = isset($item['calc_decimals']) && '' !== $item['calc_decimals'] ? (int) $item['calc_decimals'] : 2;
        if ($decimals < 0 || $decimals > 6) {
            $decimals = 2;
        }

        return '<div class="king-addons-form-calculation" data-ka-calc="1"'
            . ' data-formula="' . esc_attr($formula) . '"'
            . ' data-decimals="' . esc_attr((string) $decimals) . '"'
            . ' data-prefix="' . esc_attr((string) ($item['calc_prefix'] ?? '')) . '"'
            . ' data-suffix="' . esc_attr((string) ($item['calc_suffix'] ?? '')) . '">'
            . '<input type="text" class="king-addons-form-field king-addons-form-field-textual" readonly'
            . ' name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" value="">'
            . '</div>';
    }

    /**
     * Whether the author marked the field required.
     *
     * @param array<string,mixed> $item Repeater item.
     *
     * @return bool
     */
    private static function is_required(array $item): bool
    {
        return !empty($item['required']) && 'true' === (string) $item['required'];
    }
}
