<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class BudgetValidator
{
    private PDO $pdo;
    private const EXTREME_MULTIPLIER = 10;
    private const DIFFERENCE_WARNING_PERCENT = 20;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function validate(int $recommendationId): array
    {
        $errors = [];
        $warnings = [];
        $flags = [];

        $stmt = $this->pdo->prepare('
            SELECT id, item_name, description, item_type, quantity, unit_cost,
                   COALESCE(sessions_or_days, 1) AS sessions_or_days,
                   subtotal, funding_source
            FROM campaign_ai_recommendation_budget_items
            WHERE recommendation_id = ?
        ');
        $stmt->execute([$recommendationId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'status' => 'warning',
                'errors' => ['No budget items found for validation'],
                'warnings' => [],
                'flags' => [],
                'item_count' => 0,
            ];
        }

        $fundingSources = [];
        $itemNames = [];

        foreach ($items as $item) {
            $qty = $item['quantity'];
            $cost = $item['unit_cost'];
            $subtotal = $item['subtotal'];
            $name = trim($item['item_name']);
            $desc = trim($item['description'] ?? '');

            $itemNames[] = $name;

            if (bccomp((string) $qty, '0', 2) <= 0 && bccomp((string) $cost, '0', 2) <= 0) {
                $errors[] = "Item '{$name}': both quantity ({$qty}) and unit cost ({$cost}) are zero or negative";
            }

            if (bccomp((string) $cost, '0', 2) < 0) {
                $errors[] = "Item '{$name}': negative unit cost ({$cost})";
            }

            if (bccomp((string) $qty, '0', 2) < 0) {
                $errors[] = "Item '{$name}': negative quantity ({$qty})";
            }

            if (bccomp((string) $subtotal, '0', 2) < 0) {
                $errors[] = "Item '{$name}': negative subtotal ({$subtotal})";
            }

            $sessions = (string) ($item['sessions_or_days'] ?? 1);
            $expectedSubtotal = bcmul(bcmul((string) $qty, (string) $cost, 4), $sessions, 2);
            if (bccomp($expectedSubtotal, (string) $subtotal, 2) !== 0) {
                $warnings[] = "Item '{$name}': subtotal ({$subtotal}) does not match quantity * unit_cost * sessions_or_days ({$qty} * {$cost} * {$sessions})";
            }

            $lowerName = mb_strtolower($name);
            $lowerDesc = mb_strtolower($desc);
            if (
                str_contains($lowerName, 'test') || str_contains($lowerName, 'mock') ||
                str_contains($lowerDesc, 'test') || str_contains($lowerDesc, 'mock') ||
                str_contains($lowerName, 'sample') || str_contains($lowerDesc, 'sample')
            ) {
                $warnings[] = "Item '{$name}': description or name contains test/mock/sample indicators";
            }

            if (
                bccomp((string) $cost, '0', 2) > 0 &&
                bccomp((string) $cost, '1000000', 2) > 0
            ) {
                $flags[] = "Item '{$name}': unusually high unit cost (₱" . number_format((float) $cost, 2) . ")";
            }

            if (
                bccomp((string) $qty, '0', 2) > 0 &&
                bccomp((string) $qty, '10000', 2) > 0
            ) {
                $flags[] = "Item '{$name}': unusually high quantity ({$qty})";
            }

            $fundingSources[] = $item['funding_source'];
        }

        $nameCounts = array_count_values($itemNames);
        foreach ($nameCounts as $n => $c) {
            if ($c > 1) {
                $warnings[] = "Duplicate item name '{$n}' appears {$c} times";
            }
        }

        $uniqueSources = array_unique($fundingSources);
        if (count($uniqueSources) > 1) {
            $flags[] = 'Multiple funding sources: ' . implode(', ', $uniqueSources);
        }

        $status = 'verified';
        if (!empty($errors)) {
            $status = 'warning';
        }
        if (!empty($warnings) || !empty($flags)) {
            $status = $status === 'verified' ? 'warning' : $status;
        }

        return [
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings,
            'flags' => $flags,
            'item_count' => count($items),
            'funding_sources' => array_values($uniqueSources),
        ];
    }

    public function updateValidationStatus(int $recommendationId, string $status): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE campaign_department_ai_recommendations
            SET budget_validation_status = ?
            WHERE id = ?
        ');
        $stmt->execute([$status, $recommendationId]);
    }

    public function hasAnomalies(int $recommendationId): bool
    {
        $result = $this->validate($recommendationId);
        return !empty($result['errors']) || !empty($result['flags']);
    }

    public function isExtremeLineItem(array $item): bool
    {
        $qty = (float) $item['quantity'];
        $cost = (float) $item['unit_cost'];
        if ($qty <= 0 || $cost <= 0) return false;
        if ($cost > 1000000) return true;
        if ($qty > 10000) return true;
        return false;
    }
}
