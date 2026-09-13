<?php
/**
 * Conditional logic for Form Builder fields.
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
 * Shows or hides a field depending on what was entered in another one.
 */
class Form_Conditional_Logic
{
    /**
     * Comparisons a rule can use.
     *
     * @return array<string,string>
     */
    public static function operators(): array
    {
        return [
            'is' => esc_html__('is', 'king-addons'),
            'is_not' => esc_html__('is not', 'king-addons'),
            'contains' => esc_html__('contains', 'king-addons'),
            'not_contains' => esc_html__('does not contain', 'king-addons'),
            'is_empty' => esc_html__('is empty', 'king-addons'),
            'is_not_empty' => esc_html__('is not empty', 'king-addons'),
            'gt' => esc_html__('is greater than', 'king-addons'),
            'lt' => esc_html__('is less than', 'king-addons'),
        ];
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
            'conditional_enable',
            [
                'label' => esc_html__('Conditional logic', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'conditional_action',
            [
                'label' => esc_html__('Then', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'show' => esc_html__('Show this field', 'king-addons'),
                    'hide' => esc_html__('Hide this field', 'king-addons'),
                ],
                'default' => 'show',
                'condition' => ['conditional_enable' => 'yes'],
            ]
        );

        $repeater->add_control(
            'conditional_relation',
            [
                'label' => esc_html__('When', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'all' => esc_html__('All rules match', 'king-addons'),
                    'any' => esc_html__('Any rule matches', 'king-addons'),
                ],
                'default' => 'all',
                'condition' => ['conditional_enable' => 'yes'],
            ]
        );

        foreach ([1, 2] as $index) {
            $suffix = 1 === $index ? '' : '_2';

            $repeater->add_control(
                'conditional_field' . $suffix,
                [
                    'label' => 1 === $index
                        ? esc_html__('Field ID', 'king-addons')
                        : esc_html__('Second field ID', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'description' => 1 === $index
                        ? esc_html__('The ID of the field to watch, as set in its own "Field ID" box.', 'king-addons')
                        : esc_html__('Leave empty to use one rule only.', 'king-addons'),
                    'condition' => ['conditional_enable' => 'yes'],
                ]
            );

            $repeater->add_control(
                'conditional_operator' . $suffix,
                [
                    'label' => esc_html__('Comparison', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'options' => self::operators(),
                    'default' => 'is',
                    'condition' => ['conditional_enable' => 'yes'],
                ]
            );

            $repeater->add_control(
                'conditional_value' . $suffix,
                [
                    'label' => esc_html__('Value', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'dynamic' => ['active' => true],
                    'condition' => [
                        'conditional_enable' => 'yes',
                        'conditional_operator' . $suffix . '!' => ['is_empty', 'is_not_empty'],
                    ],
                ]
            );
        }
    }

    /**
     * The rule set for one field, ready to be printed as a data attribute.
     *
     * @param array<string,mixed> $item Repeater item.
     *
     * @return array<string,mixed>|null
     */
    public static function build($item): ?array
    {
        if (!is_array($item) || 'yes' !== ($item['conditional_enable'] ?? '')) {
            return null;
        }

        $operators = array_keys(self::operators());
        $rules = [];

        foreach (['', '_2'] as $suffix) {
            $field = isset($item['conditional_field' . $suffix])
                ? trim((string) $item['conditional_field' . $suffix])
                : '';

            if ('' === $field) {
                continue;
            }

            $operator = (string) ($item['conditional_operator' . $suffix] ?? 'is');
            if (!in_array($operator, $operators, true)) {
                $operator = 'is';
            }

            $rules[] = [
                'field' => $field,
                'op' => $operator,
                'value' => (string) ($item['conditional_value' . $suffix] ?? ''),
            ];
        }

        if (empty($rules)) {
            return null;
        }

        return [
            'action' => 'hide' === ($item['conditional_action'] ?? 'show') ? 'hide' : 'show',
            'relation' => 'any' === ($item['conditional_relation'] ?? 'all') ? 'any' : 'all',
            'rules' => $rules,
        ];
    }
}
