<?php
/**
 * Theme Builder template resolver.
 *
 * @package King_Addons
 */

namespace King_Addons\Theme_Builder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolves which template should render current request.
 */
class Resolver
{
    /**
     * Repository instance.
     *
     * @var Repository
     */
    private Repository $repository;

    /**
     * Constructor.
     *
     * @param Repository $repository Template repository.
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Find best matching template ID for context.
     *
     * @param array<string,mixed> $context Request context.
     *
     * @return int|null
     */
    public function resolve(array $context): ?int
    {
        $candidates = $this->repository->get_location_candidates($context);
        if (empty($candidates)) {
            return null;
        }

        $eligible = [];
        foreach ($candidates as $candidate) {
            if (!empty($candidate['is_pro_only']) && !$this->can_use_pro()) {
                continue;
            }

            if (!Conditions::evaluate($candidate['conditions'] ?? [], $context)) {
                continue;
            }

            $candidate['specificity'] = $this->count_specificity($candidate['conditions'] ?? []);
            $eligible[] = $candidate;
        }

        if (empty($eligible)) {
            return null;
        }

        usort(
            $eligible,
            static function (array $a, array $b): int {
                if ($a['priority'] !== $b['priority']) {
                    return ($a['priority'] < $b['priority']) ? -1 : 1;
                }

                if ($a['specificity'] !== $b['specificity']) {
                    return ($a['specificity'] > $b['specificity']) ? -1 : 1;
                }

                return ($a['id'] < $b['id']) ? -1 : 1;
            }
        );

        if (!$this->can_use_pro()) {
            // Free tier: only one template per primary type.
            $type = $context['type'] ?? '';
            foreach ($eligible as $template) {
                if ($this->matches_primary_type($template, $type)) {
                    return (int) $template['id'];
                }
            }

            return null;
        }

        return (int) $eligible[0]['id'];
    }

    /**
     * Count how specific a condition set is.
     *
     * @param array<string,mixed> $conditions Conditions data.
     *
     * @return int
     */
    private function count_specificity(array $conditions): int
    {
        $groups = $conditions['groups'] ?? [];
        $count = 0;

        foreach ($groups as $group) {
            $rules = $group['rules'] ?? [];
            $count += count($rules);
        }

        return $count;
    }

    /**
     * Check premium availability.
     *
     * @return bool
     */
    private function can_use_pro(): bool
    {
        return function_exists('king_addons_freemius')
            && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Ensure a template belongs to the requested primary type.
     *
     * @param array<string,mixed> $template Template data.
     * @param string              $type     Request primary type.
     *
     * @return bool
     */
    private function matches_primary_type(array $template, string $type): bool
    {
        $location = $template['location'] ?? '';

        if ('not_found' === $type) {
            return 'not_found' === $template['sub_location'] || 'not_found' === $location;
        }

        if ('search' === $type) {
            return in_array($template['sub_location'], ['search_results', 'search'], true) || 'search' === $location;
        }

        if ('author' === $type) {
            return 'author_all' === ($template['sub_location'] ?? '') || 'author_specific' === ($template['sub_location'] ?? '');
        }

        if ('single' === $type) {
            return 'single' === $location;
        }

        if ('archive' === $type) {
            return 'archive' === $location;
        }

        return false;
    }
}




