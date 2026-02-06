<?php
/**
 * Theme Builder conditions evaluator.
 *
 * @package King_Addons
 */

namespace King_Addons\Theme_Builder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evaluates stored Theme Builder display conditions.
 */
class Conditions
{
    /**
     * Decode and normalize saved conditions data.
     *
     * @param string|array<mixed> $raw Raw meta value.
     *
     * @return array<string,mixed>
     */
    public static function normalize($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && !empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['groups' => []];
    }

    /**
     * Check whether a conditions set requires Pro.
     *
     * @param array<string,mixed> $conditions Conditions payload.
     *
     * @return bool
     */
    public static function is_pro_only(array $conditions): bool
    {
        $groups = $conditions['groups'] ?? [];
        if (empty($groups)) {
            return false;
        }

        foreach ($groups as $group) {
            $rules = $group['rules'] ?? [];
            foreach ($rules as $rule) {
                $target = $rule['target'] ?? '';
                $values = is_array($rule['value'] ?? null) ? ($rule['value'] ?? []) : [];

                if (!in_array($target, self::get_free_targets(), true)) {
                    return true;
                }

                if ('post_type' === $target && array_diff($values, ['post', 'page'])) {
                    return true;
                }

                if (in_array($target, ['post_id', 'term', 'author', 'front_page', 'blog_page'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Evaluate conditions against current context.
     *
     * @param array<string,mixed> $conditions Conditions payload.
     * @param array<string,mixed> $context    Request context.
     *
     * @return bool
     */
    public static function evaluate(array $conditions, array $context): bool
    {
        $groups = $conditions['groups'] ?? [];
        if (empty($groups)) {
            return true;
        }

        foreach ($groups as $group) {
            if (self::evaluate_group($group, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get free-condition targets.
     *
     * @return array<int,string>
     */
    private static function get_free_targets(): array
    {
        return ['post_type', 'search', '404'];
    }

    /**
     * Evaluate a single group with relation.
     *
     * @param array<string,mixed> $group    Conditions group.
     * @param array<string,mixed> $context  Request context.
     *
     * @return bool
     */
    private static function evaluate_group(array $group, array $context): bool
    {
        $relation = strtoupper($group['relation'] ?? 'AND');
        $rules = $group['rules'] ?? [];

        if (empty($rules)) {
            return true;
        }

        $results = [];

        foreach ($rules as $rule) {
            $match = self::match_rule($rule, $context);
            $type = $rule['type'] ?? 'include';

            if ('exclude' === $type) {
                $results[] = !$match;
            } else {
                $results[] = $match;
            }
        }

        if ('OR' === $relation) {
            return in_array(true, $results, true);
        }

        return !in_array(false, $results, true);
    }

    /**
     * Evaluate a single rule.
     *
     * @param array<string,mixed> $rule    Rule data.
     * @param array<string,mixed> $context Request context.
     *
     * @return bool
     */
    private static function match_rule(array $rule, array $context): bool
    {
        $target = $rule['target'] ?? '';
        $values = $rule['value'] ?? [];
        $operator = $rule['operator'] ?? 'in';

        if (!is_array($values)) {
            $values = [$values];
        }

        switch ($target) {
            case 'post_type':
                return self::match_in((array) ($context['post_type'] ?? null), $values, $operator);
            case 'post_id':
                return self::match_in([(int) ($context['post_id'] ?? 0)], array_map('intval', $values), $operator);
            case 'term':
                $term_id = (int) ($context['term_id'] ?? 0);
                $taxonomy = $context['taxonomy'] ?? '';
                $filtered = array_filter($values, static function ($item) use ($taxonomy) {
                    if (is_array($item) && isset($item['taxonomy'], $item['id'])) {
                        return $taxonomy === ($item['taxonomy'] ?? '');
                    }
                    return true;
                });
                $term_values = array_map(static function ($item) {
                    if (is_array($item) && isset($item['id'])) {
                        return (int) $item['id'];
                    }
                    return (int) $item;
                }, $filtered);
                return self::match_in([$term_id], $term_values, $operator);
            case 'author':
                return self::match_in([(int) ($context['author_id'] ?? 0)], array_map('intval', $values), $operator);
            case 'front_page':
                return !empty($context['is_front_page']);
            case 'blog_page':
                return !empty($context['is_blog_page']);
            case 'search':
                return 'search' === ($context['type'] ?? '');
            case '404':
                return 'not_found' === ($context['type'] ?? '');
            default:
                return false;
        }
    }

    /**
     * Match values with operator.
     *
     * @param array<int|string> $current  Current context values.
     * @param array<int|string> $allowed  Allowed values.
     * @param string            $operator Operator string.
     *
     * @return bool
     */
    private static function match_in(array $current, array $allowed, string $operator): bool
    {
        $operator = strtolower($operator ?: 'in');
        $current = array_filter($current, static fn($value) => null !== $value && '' !== $value);
        $allowed = array_filter($allowed, static fn($value) => null !== $value && '' !== $value);

        if (empty($allowed)) {
            return false;
        }

        $intersection = array_intersect($current, $allowed);

        if ('not_in' === $operator) {
            return empty($intersection);
        }

        return !empty($intersection);
    }
}




