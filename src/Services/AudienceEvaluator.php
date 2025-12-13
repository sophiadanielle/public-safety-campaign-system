<?php

declare(strict_types=1);

namespace App\Services;

class AudienceEvaluator
{
    private array $criteria;

    public function __construct(array $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Filter members array based on simple criteria.
     * Supported keys: channel (string/array), name_contains, contact_contains, created_after, created_before.
     */
    public function filter(array $members): array
    {
        $filtered = [];
        foreach ($members as $member) {
            if (!$this->matches($member)) {
                continue;
            }
            $filtered[] = $member;
        }
        return $filtered;
    }

    private function matches(array $member): bool
    {
        // channel criteria
        if (isset($this->criteria['channel'])) {
            $allowed = (array) $this->criteria['channel'];
            if (!in_array($member['channel'] ?? 'other', $allowed, true)) {
                return false;
            }
        }

        // name contains
        if (!empty($this->criteria['name_contains'])) {
            $needle = strtolower((string) $this->criteria['name_contains']);
            $hay = strtolower((string) ($member['full_name'] ?? ''));
            if (strpos($hay, $needle) === false) {
                return false;
            }
        }

        // contact contains
        if (!empty($this->criteria['contact_contains'])) {
            $needle = strtolower((string) $this->criteria['contact_contains']);
            $hay = strtolower((string) ($member['contact'] ?? ''));
            if (strpos($hay, $needle) === false) {
                return false;
            }
        }

        // date boundaries (expects Y-m-d)
        if (!empty($this->criteria['created_after']) && !empty($member['created_at'])) {
            if (strtotime($member['created_at']) <= strtotime($this->criteria['created_after'])) {
                return false;
            }
        }
        if (!empty($this->criteria['created_before']) && !empty($member['created_at'])) {
            if (strtotime($member['created_at']) >= strtotime($this->criteria['created_before'])) {
                return false;
            }
        }

        return true;
    }
}

