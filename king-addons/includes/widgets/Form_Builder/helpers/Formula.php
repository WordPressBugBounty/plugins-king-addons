<?php
/**
 * Arithmetic evaluator shared by the calculation field and payments.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evaluates "1 + 2 * (3 - 4)" without eval().
 *
 * The browser has the same rules in script.js; this copy exists so a price can
 * be worked out again on the server rather than taken from the request.
 */
class Form_Formula
{
    /**
     * Work out a formula, substituting [field_id] with submitted values.
     *
     * @param string                $formula Formula as the author wrote it.
     * @param array<string,string>  $values  Field id => submitted value.
     *
     * @return float|null Result, or null when the formula is not valid.
     */
    public static function evaluate(string $formula, array $values): ?float
    {
        $resolved = preg_replace_callback(
            '/\[([^\]]+)\]/',
            static function ($matches) use ($values) {
                $key = trim($matches[1]);
                // Submitted names use resolve_field_key(); older payloads
                // prefixed the same id with form_field-.
                $raw = $values[$key] ?? $values['form_field-' . $key] ?? '0';
                $number = (float) str_replace(',', '.', (string) $raw);

                return '(' . $number . ')';
            },
            $formula
        );

        return self::compute((string) $resolved);
    }

    /**
     * Evaluate an expression of numbers, + - * / and parentheses.
     *
     * @param string $expression Expression.
     *
     * @return float|null
     */
    public static function compute(string $expression): ?float
    {
        preg_match_all('/\d+\.?\d*|[-+*\/()]/', $expression, $matches);
        $tokens = $matches[0];

        if (empty($tokens)) {
            return null;
        }

        // Anything the tokenizer skipped means the expression held something
        // else - a letter, a symbol - and the whole thing is rejected.
        if (implode('', $tokens) !== preg_replace('/\s+/', '', $expression)) {
            return null;
        }

        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];
        $output = [];
        $operators = [];
        $previous = null;

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = (float) $token;
            } elseif ('(' === $token) {
                $operators[] = $token;
            } elseif (')' === $token) {
                while (!empty($operators) && '(' !== end($operators)) {
                    $output[] = array_pop($operators);
                }

                if (empty($operators)) {
                    return null;
                }

                array_pop($operators);
            } else {
                // A leading minus is a sign, not an operator.
                if ('-' === $token && (null === $previous || '(' === $previous || isset($precedence[$previous]))) {
                    $output[] = 0.0;
                }

                while (
                    !empty($operators)
                    && '(' !== end($operators)
                    && $precedence[end($operators)] >= $precedence[$token]
                ) {
                    $output[] = array_pop($operators);
                }

                $operators[] = $token;
            }

            $previous = $token;
        }

        while (!empty($operators)) {
            $last = array_pop($operators);
            if ('(' === $last) {
                return null;
            }
            $output[] = $last;
        }

        $stack = [];

        foreach ($output as $item) {
            if (is_float($item)) {
                $stack[] = $item;
                continue;
            }

            if (count($stack) < 2) {
                return null;
            }

            $right = array_pop($stack);
            $left = array_pop($stack);

            switch ($item) {
                case '+':
                    $stack[] = $left + $right;
                    break;
                case '-':
                    $stack[] = $left - $right;
                    break;
                case '*':
                    $stack[] = $left * $right;
                    break;
                case '/':
                    if (0.0 === $right) {
                        return null;
                    }
                    $stack[] = $left / $right;
                    break;
                default:
                    return null;
            }
        }

        if (1 !== count($stack) || !is_finite($stack[0])) {
            return null;
        }

        return (float) $stack[0];
    }
}
