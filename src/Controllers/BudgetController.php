<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

class BudgetController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_budgets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                campaign_id INT NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                item_type VARCHAR(50) NOT NULL DEFAULT 'consumable',
                quantity INT NOT NULL DEFAULT 1,
                unit_cost DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                funding_source VARCHAR(50) NOT NULL DEFAULT 'government_allocated',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_by INT,
                INDEX idx_campaign_id (campaign_id),
                INDEX idx_funding_source (funding_source)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function index(?array $user, array $params): array
    {
        $campaignId = $_GET['campaign_id'] ?? null;
        
        if ($campaignId) {
            $stmt = $this->pdo->prepare("
                SELECT cb.*, c.title as campaign_title,
                       (cb.quantity * cb.unit_cost) as total_cost
                FROM campaign_budgets cb 
                LEFT JOIN campaigns c ON cb.campaign_id = c.id 
                WHERE cb.campaign_id = ? 
                ORDER BY cb.created_at DESC
            ");
            $stmt->execute([$campaignId]);
        } else {
            $stmt = $this->pdo->query("
                SELECT cb.*, c.title as campaign_title,
                       (cb.quantity * cb.unit_cost) as total_cost
                FROM campaign_budgets cb 
                LEFT JOIN campaigns c ON cb.campaign_id = c.id 
                ORDER BY cb.created_at DESC
            ");
        }
        
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        $totalBudget = 0;
        $governmentTotal = 0;
        $reimbursableTotal = 0;
        
        foreach ($budgets as $b) {
            $cost = (float)($b['total_cost'] ?? ($b['quantity'] * $b['unit_cost']));
            $totalBudget += $cost;
            if ($b['funding_source'] === 'government_allocated') {
                $governmentTotal += $cost;
            } else {
                $reimbursableTotal += $cost;
            }
        }
        
        return [
            'success' => true, 
            'data' => $budgets,
            'summary' => [
                'total_budget' => $totalBudget,
                'government_allocated' => $governmentTotal,
                'reimbursable' => $reimbursableTotal,
                'item_count' => count($budgets)
            ]
        ];
    }

    public function show(?array $user, array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        
        $stmt = $this->pdo->prepare("
            SELECT cb.*, c.title as campaign_title,
                   (cb.quantity * cb.unit_cost) as total_cost
            FROM campaign_budgets cb 
            LEFT JOIN campaigns c ON cb.campaign_id = c.id 
            WHERE cb.id = ?
        ");
        $stmt->execute([$id]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$budget) {
            http_response_code(404);
            return ['error' => 'Budget item not found'];
        }
        
        return ['success' => true, 'data' => $budget];
    }

    public function store(?array $user, array $params): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['campaign_id']) || !isset($input['item_name'])) {
            http_response_code(400);
            return ['error' => 'campaign_id and item_name are required'];
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO campaign_budgets (campaign_id, item_name, item_type, quantity, unit_cost, funding_source, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['campaign_id'],
            $input['item_name'],
            $input['item_type'] ?? 'consumable',
            $input['quantity'] ?? 1,
            $input['unit_cost'] ?? 0,
            $input['funding_source'] ?? 'government_allocated',
            $input['notes'] ?? null,
            $user['id'] ?? null
        ]);
        
        $newId = $this->pdo->lastInsertId();
        
        // Fetch the created record
        $stmt = $this->pdo->prepare("SELECT *, (quantity * unit_cost) as total_cost FROM campaign_budgets WHERE id = ?");
        $stmt->execute([$newId]);
        $newBudget = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        return ['success' => true, 'data' => $newBudget, 'message' => 'Budget item created successfully'];
    }

    public function update(?array $user, array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updates = [];
        $values = [];
        
        $allowedFields = ['item_name', 'item_type', 'quantity', 'unit_cost', 'funding_source', 'notes'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            return ['error' => 'No fields to update'];
        }
        
        $values[] = $id;
        $sql = "UPDATE campaign_budgets SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        // Fetch updated record
        $stmt = $this->pdo->prepare("SELECT *, (quantity * unit_cost) as total_cost FROM campaign_budgets WHERE id = ?");
        $stmt->execute([$id]);
        $updatedBudget = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'data' => $updatedBudget, 'message' => 'Budget item updated successfully'];
    }

    public function destroy(?array $user, array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        
        $stmt = $this->pdo->prepare("DELETE FROM campaign_budgets WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return ['error' => 'Budget item not found'];
        }
        
        return ['success' => true, 'message' => 'Budget item deleted successfully'];
    }

    public function getByCampaign(?array $user, array $params): array
    {
        $campaignId = (int)($params['id'] ?? 0);
        
        $stmt = $this->pdo->prepare("
            SELECT cb.*, (cb.quantity * cb.unit_cost) as total_cost
            FROM campaign_budgets cb 
            WHERE cb.campaign_id = ? 
            ORDER BY cb.created_at DESC
        ");
        $stmt->execute([$campaignId]);
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        $totalBudget = 0;
        $governmentTotal = 0;
        $reimbursableTotal = 0;
        
        foreach ($budgets as $b) {
            $cost = (float)($b['total_cost'] ?? 0);
            $totalBudget += $cost;
            if ($b['funding_source'] === 'government_allocated') {
                $governmentTotal += $cost;
            } else {
                $reimbursableTotal += $cost;
            }
        }
        
        return [
            'success' => true, 
            'data' => $budgets,
            'campaign_id' => $campaignId,
            'summary' => [
                'total_budget' => $totalBudget,
                'government_allocated' => $governmentTotal,
                'reimbursable' => $reimbursableTotal,
                'item_count' => count($budgets)
            ]
        ];
    }
}
