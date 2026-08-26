<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use RuntimeException;

class BudgetController
{
    private PDO $pdo;

    public function __construct(
        PDO $pdo,
        ?string $jwtSecret = null,
        ?string $jwtIssuer = null,
        ?string $jwtAudience = null,
        ?int $jwtExpirySeconds = null
    ) {
        $this->pdo = $pdo;
        $this->ensureTableExists();
        $this->ensureWorkflowSchema();
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
                is_archived TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_by INT,
                INDEX idx_campaign_id (campaign_id),
                INDEX idx_funding_source (funding_source),
                INDEX idx_is_archived (is_archived)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        try {
            $this->pdo->exec("ALTER TABLE campaign_budgets ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
        } catch (\PDOException $e) {
            // Existing column.
        }
    }

    private function ensureWorkflowSchema(): void
    {
        // These are intentionally idempotent fallbacks. A SQL migration is also supplied.
        $columnSql = [
            "ALTER TABLE campaign_budgets ADD COLUMN budget_destination VARCHAR(255) NULL AFTER related_action",
        ];
        foreach ($columnSql as $sql) {
            try { $this->pdo->exec($sql); } catch (\PDOException $e) { /* already migrated */ }
        }

        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS campaign_budget_workflows (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    campaign_id INT UNSIGNED NOT NULL,
                    planning_status ENUM('draft','approved','finalized') NOT NULL DEFAULT 'draft',
                    review_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
                    rejection_reason TEXT NULL,
                    pre_edit_snapshot LONGTEXT NULL,
                    approved_by INT UNSIGNED NULL,
                    approved_at DATETIME NULL,
                    finalized_by INT UNSIGNED NULL,
                    finalized_at DATETIME NULL,
                    reviewed_by INT UNSIGNED NULL,
                    reviewed_at DATETIME NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_campaign_budget_workflow_campaign (campaign_id),
                    KEY idx_budget_workflow_planning (planning_status),
                    KEY idx_budget_workflow_review (review_status),
                    CONSTRAINT fk_budget_workflow_campaign FOREIGN KEY (campaign_id)
                        REFERENCES campaign_department_campaigns(id) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\PDOException $e) {
            error_log('BudgetController::ensureWorkflowSchema - ' . $e->getMessage());
        }
    }

    private function getActiveGovernmentAllocation(): array
    {
        $fiscalYear = date('Y');
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, fiscal_year, total_allocation, status, effective_from, effective_until, notes
                FROM government_budget_allocations
                WHERE status = 'active'
                  AND (fiscal_year = ? OR (effective_from IS NOT NULL AND effective_until IS NOT NULL AND CURDATE() BETWEEN effective_from AND effective_until))
                ORDER BY CASE WHEN fiscal_year = ? THEN 0 ELSE 1 END, effective_from DESC, id DESC
                LIMIT 1
            ");
            $stmt->execute([$fiscalYear, $fiscalYear]);
            $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($allocation) {
                return [
                    'amount' => (float)$allocation['total_allocation'],
                    'fiscal_year' => (string)$allocation['fiscal_year'],
                    'status' => (string)$allocation['status'],
                    'effective_from' => $allocation['effective_from'],
                    'effective_until' => $allocation['effective_until'],
                    'notes' => $allocation['notes'],
                ];
            }
        } catch (\PDOException $e) {
            error_log('BudgetController::getActiveGovernmentAllocation - ' . $e->getMessage());
        }

        return [
            'amount' => 0.0,
            'fiscal_year' => $fiscalYear,
            'status' => null,
            'effective_from' => null,
            'effective_until' => null,
            'notes' => null,
        ];
    }

    private function findCampaign(int $campaignId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaigns WHERE id = ? LIMIT 1');
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$campaign) {
            throw new RuntimeException('Campaign not found');
        }
        return $campaign;
    }

    private function getWorkflow(int $campaignId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campaign_budget_workflows WHERE campaign_id = ? LIMIT 1');
        $stmt->execute([$campaignId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function createWorkflowIfMissing(int $campaignId, string $planningStatus = 'finalized'): array
    {
        $workflow = $this->getWorkflow($campaignId);
        if ($workflow) return $workflow;

        $stmt = $this->pdo->prepare("INSERT INTO campaign_budget_workflows (campaign_id, planning_status, review_status) VALUES (?, ?, 'none')");
        $stmt->execute([$campaignId, $planningStatus]);
        return $this->getWorkflow($campaignId) ?? [];
    }

    private function userRoleName(?array $user): string
    {
        $role = strtolower(trim((string)($user['role'] ?? '')));
        if ($role !== '') return $role;
        $roleId = (int)($user['role_id'] ?? 0);
        if ($roleId > 0) {
            try {
                $stmt = $this->pdo->prepare('SELECT name FROM campaign_department_roles WHERE id = ? LIMIT 1');
                $stmt->execute([$roleId]);
                return strtolower(trim((string)$stmt->fetchColumn()));
            } catch (\PDOException $e) {
                // Ignore and fall through.
            }
        }
        return '';
    }

    private function canApproveBudget(?array $user): bool
    {
        $role = $this->userRoleName($user);
        if ($role === '') return false;
        foreach (['captain', 'admin', 'administrator', 'super admin', 'system_admin'] as $needle) {
            if (str_contains($role, $needle)) return true;
        }
        return false;
    }

    private function fetchCampaignItems(int $campaignId, bool $includeArchived = false): array
    {
        $sql = "SELECT cb.*, (cb.quantity * cb.unit_cost * COALESCE(NULLIF(cb.sessions_or_days,0),1)) AS total_cost
                FROM campaign_budgets cb
                WHERE cb.campaign_id = ?";
        if (!$includeArchived) $sql .= ' AND COALESCE(cb.is_archived,0)=0';
        $sql .= ' ORDER BY cb.sort_order ASC, cb.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function snapshotCampaignItems(int $campaignId): string
    {
        return json_encode($this->fetchCampaignItems($campaignId, false), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    private function insertBudgetItem(int $campaignId, array $item, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO campaign_budgets
            (campaign_id,item_name,item_type,quantity,unit_cost,funding_source,notes,created_by,is_archived,source_recommendation_id,category,item_description,sessions_or_days,unit_label,related_action,budget_destination,recommendation_reason,pricing_source,pricing_confidence,calculation_basis,is_estimate,sort_order)
            VALUES
            (:campaign_id,:item_name,:item_type,:quantity,:unit_cost,:funding_source,:notes,:created_by,0,:source_recommendation_id,:category,:item_description,:sessions_or_days,:unit_label,:related_action,:budget_destination,:recommendation_reason,:pricing_source,:pricing_confidence,:calculation_basis,:is_estimate,:sort_order)
        ");
        $stmt->execute([
            'campaign_id' => $campaignId,
            'item_name' => trim((string)($item['item_name'] ?? '')),
            'item_type' => trim((string)($item['item_type'] ?? 'consumable')) ?: 'consumable',
            'quantity' => max(1, (int)($item['quantity'] ?? 1)),
            'unit_cost' => max(0, (float)($item['unit_cost'] ?? 0)),
            'funding_source' => trim((string)($item['funding_source'] ?? 'government_allocated')) ?: 'government_allocated',
            'notes' => trim((string)($item['notes'] ?? '')) ?: null,
            'created_by' => $userId,
            'source_recommendation_id' => !empty($item['source_recommendation_id']) ? (int)$item['source_recommendation_id'] : null,
            'category' => trim((string)($item['category'] ?? '')) ?: null,
            'item_description' => trim((string)($item['item_description'] ?? $item['description'] ?? '')) ?: null,
            'sessions_or_days' => max(1, (int)($item['sessions_or_days'] ?? 1)),
            'unit_label' => trim((string)($item['unit_label'] ?? '')) ?: null,
            'related_action' => trim((string)($item['related_action'] ?? '')) ?: null,
            'budget_destination' => trim((string)($item['budget_destination'] ?? '')) ?: null,
            'recommendation_reason' => trim((string)($item['recommendation_reason'] ?? '')) ?: null,
            'pricing_source' => trim((string)($item['pricing_source'] ?? '')) ?: null,
            'pricing_confidence' => trim((string)($item['pricing_confidence'] ?? '')) ?: null,
            'calculation_basis' => trim((string)($item['calculation_basis'] ?? '')) ?: null,
            'is_estimate' => isset($item['is_estimate']) ? ((bool)$item['is_estimate'] ? 1 : 0) : 0,
            'sort_order' => max(0, (int)($item['sort_order'] ?? 0)),
        ]);
    }

    public function index(?array $user, array $params): array
    {
        $campaignId = $_GET['campaign_id'] ?? null;
        $where = "WHERE COALESCE(cb.is_archived,0)=0 AND (bw.campaign_id IS NULL OR bw.planning_status='finalized' OR cb.source_recommendation_id IS NOT NULL)";
        $bindings = [];
        if ($campaignId) {
            $where .= ' AND cb.campaign_id = ?';
            $bindings[] = (int)$campaignId;
        }

        $stmt = $this->pdo->prepare("
            SELECT cb.*, c.title AS campaign_title,
                   (cb.quantity * cb.unit_cost * COALESCE(NULLIF(cb.sessions_or_days,0),1)) AS total_cost,
                   COALESCE(bw.planning_status, CASE WHEN cb.source_recommendation_id IS NOT NULL THEN 'finalized' ELSE 'finalized' END) AS budget_planning_status,
                   COALESCE(bw.review_status,'none') AS budget_review_status,
                   bw.rejection_reason AS budget_rejection_reason,
                   bw.approved_at AS budget_approved_at,
                   bw.finalized_at AS budget_finalized_at,
                   bw.reviewed_at AS budget_reviewed_at
            FROM campaign_budgets cb
            LEFT JOIN campaign_department_campaigns c ON cb.campaign_id = c.id
            LEFT JOIN campaign_budget_workflows bw ON bw.campaign_id = cb.campaign_id
            {$where}
            ORDER BY cb.campaign_id DESC, cb.sort_order ASC, cb.id ASC
        ");
        $stmt->execute($bindings);
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalBudget = 0.0;
        $governmentCommitted = 0.0;
        $reimbursableTotal = 0.0;
        foreach ($budgets as $b) {
            $cost = (float)($b['total_cost'] ?? 0);
            $totalBudget += $cost;
            if (($b['funding_source'] ?? '') === 'government_allocated') $governmentCommitted += $cost;
            elseif (($b['funding_source'] ?? '') !== 'partner_contribution') $reimbursableTotal += $cost;
        }

        $governmentAllocation = $this->getActiveGovernmentAllocation();
        $governmentAllocated = (float)$governmentAllocation['amount'];

        return [
            'success' => true,
            'data' => $budgets,
            'summary' => [
                'total_budget' => $totalBudget,
                'government_allocated' => $governmentAllocated,
                'government_committed' => $governmentCommitted,
                'government_remaining' => max(0, $governmentAllocated - $governmentCommitted),
                'government_fiscal_year' => $governmentAllocation['fiscal_year'],
                'government_allocation_status' => $governmentAllocation['status'],
                'government_effective_from' => $governmentAllocation['effective_from'],
                'government_effective_until' => $governmentAllocation['effective_until'],
                'reimbursable' => $reimbursableTotal,
                'item_count' => count($budgets),
            ],
        ];
    }

    public function show(?array $user, array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        $stmt = $this->pdo->prepare("
            SELECT cb.*, c.title AS campaign_title,
                   (cb.quantity * cb.unit_cost * COALESCE(NULLIF(cb.sessions_or_days,0),1)) AS total_cost
            FROM campaign_budgets cb
            LEFT JOIN campaign_department_campaigns c ON cb.campaign_id = c.id
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
        // Kept for backward compatibility. The UI no longer exposes standalone budget creation.
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['campaign_id']) || !isset($input['item_name'])) {
            http_response_code(400);
            return ['error' => 'campaign_id and item_name are required'];
        }
        $campaignId = (int)$input['campaign_id'];
        $this->findCampaign($campaignId);
        $this->insertBudgetItem($campaignId, $input, isset($user['id']) ? (int)$user['id'] : null);
        $id = (int)$this->pdo->lastInsertId();
        $this->createWorkflowIfMissing($campaignId, 'draft');
        $stmt = $this->pdo->prepare('SELECT *, (quantity * unit_cost * COALESCE(NULLIF(sessions_or_days,0),1)) AS total_cost FROM campaign_budgets WHERE id = ?');
        $stmt->execute([$id]);
        http_response_code(201);
        return ['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC), 'message' => 'Budget draft item created'];
    }

    public function update(?array $user, array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $allowedFields = ['item_name','item_type','quantity','unit_cost','funding_source','notes','is_archived','category','item_description','sessions_or_days','unit_label','related_action','budget_destination'];
        $updates = [];
        $values = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $updates[] = "$field = ?";
                $values[] = $field === 'is_archived' ? ($input[$field] ? 1 : 0) : $input[$field];
            }
        }
        if (!$updates) {
            http_response_code(400);
            return ['error' => 'No fields to update'];
        }
        $values[] = $id;
        $stmt = $this->pdo->prepare('UPDATE campaign_budgets SET ' . implode(', ', $updates) . ' WHERE id = ?');
        $stmt->execute($values);
        $stmt = $this->pdo->prepare('SELECT *, (quantity * unit_cost * COALESCE(NULLIF(sessions_or_days,0),1)) AS total_cost FROM campaign_budgets WHERE id = ?');
        $stmt->execute([$id]);
        return ['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC), 'message' => 'Budget item updated'];
    }

    public function destroy(?array $user, array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        $stmt = $this->pdo->prepare('DELETE FROM campaign_budgets WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return ['error' => 'Budget item not found'];
        }
        return ['success' => true, 'message' => 'Budget item deleted'];
    }

    public function getByCampaign(?array $user, array $params): array
    {
        $campaignId = (int)($params['id'] ?? 0);
        $items = $this->fetchCampaignItems($campaignId, false);
        $total = 0.0; $gov = 0.0; $reimb = 0.0;
        foreach ($items as $b) {
            $cost = (float)($b['total_cost'] ?? 0);
            $total += $cost;
            if (($b['funding_source'] ?? '') === 'government_allocated') $gov += $cost;
            else $reimb += $cost;
        }
        return ['success' => true, 'data' => $items, 'campaign_id' => $campaignId, 'workflow' => $this->getWorkflow($campaignId), 'summary' => ['total_budget'=>$total,'government_allocated'=>$gov,'reimbursable'=>$reimb,'item_count'=>count($items)]];
    }

    public function getWorkflowStatus(?array $user, array $params): array
    {
        $campaignId = (int)($params['id'] ?? 0);
        $this->findCampaign($campaignId);
        $workflow = $this->getWorkflow($campaignId);
        if (!$workflow && count($this->fetchCampaignItems($campaignId, false)) > 0) {
            // Existing pre-workflow data is treated as already finalized so it remains visible.
            $workflow = $this->createWorkflowIfMissing($campaignId, 'finalized');
        }
        return ['success' => true, 'data' => $workflow ?: ['campaign_id'=>$campaignId,'planning_status'=>'draft','review_status'=>'none','rejection_reason'=>null]];
    }

    public function workflowAction(?array $user, array $params): array
    {
        $campaignId = (int)($params['id'] ?? 0);
        $this->findCampaign($campaignId);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = strtolower(trim((string)($input['action'] ?? '')));
        $userId = isset($user['id']) ? (int)$user['id'] : null;
        $workflow = $this->getWorkflow($campaignId) ?: $this->createWorkflowIfMissing($campaignId, 'draft');
        $itemCount = count($this->fetchCampaignItems($campaignId, false));

        if (in_array($action, ['approve_planner','finalize_planner','approve_edit','reject_edit'], true) && !$this->canApproveBudget($user)) {
            http_response_code(403);
            return ['error' => 'Only the Captain or an Administrator can approve, finalize, or reject budget changes.'];
        }

        try {
            if ($action === 'approve_planner') {
                if ($itemCount < 1) throw new RuntimeException('Add at least one budget line item before approval.');
                if (($workflow['planning_status'] ?? 'draft') === 'finalized') throw new RuntimeException('This budget is already finalized.');
                $stmt = $this->pdo->prepare("UPDATE campaign_budget_workflows SET planning_status='approved', review_status='none', rejection_reason=NULL, approved_by=?, approved_at=NOW() WHERE campaign_id=?");
                $stmt->execute([$userId, $campaignId]);
                return ['success'=>true,'message'=>'Budget plan approved. It can now be finalized into Financial & Budgeting.','data'=>$this->getWorkflow($campaignId)];
            }

            if ($action === 'finalize_planner') {
                if (($workflow['planning_status'] ?? '') !== 'approved') throw new RuntimeException('Approve the budget plan before finalizing it.');
                $stmt = $this->pdo->prepare("UPDATE campaign_budget_workflows SET planning_status='finalized', review_status='none', rejection_reason=NULL, finalized_by=?, finalized_at=NOW() WHERE campaign_id=?");
                $stmt->execute([$userId, $campaignId]);
                return ['success'=>true,'message'=>'Budget finalized and inserted into Financial & Budgeting.','data'=>$this->getWorkflow($campaignId)];
            }

            if ($action === 'approve_edit') {
                if (($workflow['review_status'] ?? '') !== 'pending') throw new RuntimeException('There is no pending budget revision to approve.');
                $stmt = $this->pdo->prepare("UPDATE campaign_budget_workflows SET review_status='approved', rejection_reason=NULL, pre_edit_snapshot=NULL, reviewed_by=?, reviewed_at=NOW() WHERE campaign_id=?");
                $stmt->execute([$userId, $campaignId]);
                return ['success'=>true,'message'=>'Pending budget revision approved.','data'=>$this->getWorkflow($campaignId)];
            }

            if ($action === 'reject_edit') {
                if (($workflow['review_status'] ?? '') !== 'pending') throw new RuntimeException('There is no pending budget revision to reject.');
                $reason = trim((string)($input['reason'] ?? ''));
                if ($reason === '') throw new RuntimeException('Rejection reason is required.');
                $snapshot = json_decode((string)($workflow['pre_edit_snapshot'] ?? '[]'), true);
                if (!is_array($snapshot)) $snapshot = [];

                $this->pdo->beginTransaction();
                $stmt = $this->pdo->prepare('DELETE FROM campaign_budgets WHERE campaign_id=? AND COALESCE(is_archived,0)=0');
                $stmt->execute([$campaignId]);
                $sort = 0;
                foreach ($snapshot as $item) {
                    if (trim((string)($item['item_name'] ?? '')) === '') continue;
                    $item['sort_order'] = ++$sort;
                    $this->insertBudgetItem($campaignId, $item, isset($item['created_by']) ? (int)$item['created_by'] : $userId);
                }
                $stmt = $this->pdo->prepare("UPDATE campaign_budget_workflows SET review_status='rejected', rejection_reason=?, pre_edit_snapshot=NULL, reviewed_by=?, reviewed_at=NOW() WHERE campaign_id=?");
                $stmt->execute([$reason, $userId, $campaignId]);
                $this->pdo->commit();
                return ['success'=>true,'message'=>'Budget revision rejected and the last finalized values were restored.','data'=>$this->getWorkflow($campaignId)];
            }

            http_response_code(422);
            return ['error' => 'Unknown budget workflow action'];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            http_response_code(422);
            return ['error' => $e->getMessage()];
        }
    }

    public function replaceCampaignBudget(?array $user, array $params): array
    {
        $campaignId = (int)($params['id'] ?? 0);
        $this->findCampaign($campaignId);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        if (!$items) {
            http_response_code(422);
            return ['error' => 'At least one budget item is required.'];
        }
        $userId = isset($user['id']) ? (int)$user['id'] : null;
        $workflow = $this->getWorkflow($campaignId) ?: $this->createWorkflowIfMissing($campaignId, 'finalized');
        if (($workflow['planning_status'] ?? 'finalized') !== 'finalized') {
            http_response_code(422);
            return ['error' => 'Only finalized budgets can be edited from Financial & Budgeting.'];
        }

        try {
            $this->pdo->beginTransaction();
            $workflow = $this->getWorkflow($campaignId) ?? $workflow;
            $snapshot = (string)($workflow['pre_edit_snapshot'] ?? '');
            if (($workflow['review_status'] ?? 'none') !== 'pending' || $snapshot === '') {
                $snapshot = $this->snapshotCampaignItems($campaignId);
            }

            $stmt = $this->pdo->prepare('DELETE FROM campaign_budgets WHERE campaign_id=? AND COALESCE(is_archived,0)=0');
            $stmt->execute([$campaignId]);
            $sort = 0;
            foreach ($items as $item) {
                if (trim((string)($item['item_name'] ?? '')) === '') continue;
                $item['sort_order'] = ++$sort;
                $this->insertBudgetItem($campaignId, $item, $userId);
            }

            $stmt = $this->pdo->prepare("UPDATE campaign_budget_workflows SET planning_status='finalized', review_status='pending', rejection_reason=NULL, pre_edit_snapshot=?, reviewed_by=NULL, reviewed_at=NULL WHERE campaign_id=?");
            $stmt->execute([$snapshot, $campaignId]);
            $this->pdo->commit();
            return ['success'=>true,'message'=>'Budget changes saved as Pending and require approval.','workflow'=>$this->getWorkflow($campaignId),'data'=>$this->fetchCampaignItems($campaignId,false)];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            http_response_code(500);
            return ['error' => 'Failed to save pending budget revision: ' . $e->getMessage()];
        }
    }

    public function analysis(?array $user, array $params): array
    {
        $campaignId = (int)($params['id'] ?? 0);
        try {
            $campaign = $this->findCampaign($campaignId);
        } catch (RuntimeException $e) {
            http_response_code(404);
            return ['error' => $e->getMessage()];
        }
        $items = $this->fetchCampaignItems($campaignId, false);
        $workflow = $this->getWorkflow($campaignId);

        $total = 0.0;
        $categories = [];
        $actions = [];
        $locations = [];
        $contingencyAmount = 0.0;
        $partnerOffset = 0.0;
        $staffSupportTotal = 0.0;
        $lineItems = [];
        foreach ($items as $idx => $item) {
            $sessions = max(1, (int)($item['sessions_or_days'] ?? 1));
            $subtotal = (float)$item['quantity'] * (float)$item['unit_cost'] * $sessions;
            $total += $subtotal;
            $category = trim((string)($item['category'] ?? '')) ?: ucfirst((string)($item['item_type'] ?? 'Other'));
            $action = trim((string)($item['related_action'] ?? '')) ?: 'General campaign implementation';
            $location = trim((string)($item['budget_destination'] ?? '')) ?: (trim((string)($campaign['location'] ?? '')) ?: 'Campaign-wide');
            $search = strtolower($category . ' ' . ($item['item_type'] ?? '') . ' ' . ($item['item_name'] ?? ''));
            if (str_contains($search, 'contingency')) $contingencyAmount += $subtotal;
            if (str_contains($search, 'staff') || str_contains($search, 'personnel')) $staffSupportTotal += $subtotal;
            if (($item['funding_source'] ?? '') === 'partner_contribution') $partnerOffset += $subtotal;

            if (!isset($categories[$category])) $categories[$category] = ['category'=>$category,'total'=>0.0,'item_count'=>0];
            $categories[$category]['total'] += $subtotal;
            $categories[$category]['item_count']++;

            if (!isset($actions[$action])) $actions[$action] = ['action'=>$action,'action_budget_total'=>0.0,'items'=>[]];
            $actions[$action]['action_budget_total'] += $subtotal;
            $actions[$action]['items'][] = ['item_name'=>$item['item_name'],'subtotal'=>$subtotal];

            if (!isset($locations[$location])) $locations[$location] = ['location'=>$location,'activities'=>0,'staff_qty'=>0,'material_allocation'=>0.0,'transportation_cost'=>0.0,'other_cost'=>0.0,'total_estimated_cost'=>0.0,'basis'=>'Budget destination entered in the campaign plan'];
            $locations[$location]['activities']++;
            $locations[$location]['total_estimated_cost'] += $subtotal;
            $typeSearch = strtolower($category . ' ' . ($item['item_type'] ?? ''));
            if (str_contains($typeSearch, 'material') || str_contains($typeSearch, 'supply') || str_contains($typeSearch, 'equipment')) $locations[$location]['material_allocation'] += $subtotal;
            elseif (str_contains($typeSearch, 'transport') || str_contains($typeSearch, 'logistic')) $locations[$location]['transportation_cost'] += $subtotal;
            else $locations[$location]['other_cost'] += $subtotal;

            $lineItems[] = [
                'number'=>$idx+1,
                'id'=>(int)$item['id'],
                'category'=>$category,
                'item_name'=>$item['item_name'],
                'description'=>$item['item_description'] ?? '',
                'related_action'=>$action,
                'quantity'=>(int)$item['quantity'],
                'unit_label'=>$item['unit_label'] ?? '',
                'unit_cost'=>(float)$item['unit_cost'],
                'sessions_or_days'=>$sessions,
                'subtotal'=>$subtotal,
                'funding_source'=>$item['funding_source'] ?? '',
                'budget_destination'=>$location,
                'pricing_source'=>$item['pricing_source'] ?? 'Manual Campaign Plan',
                'notes'=>$item['notes'] ?? '',
                'item_type'=>$item['item_type'] ?? '',
                'source_recommendation_id'=>$item['source_recommendation_id'] ?? null,
            ];
        }

        $categoryRows = array_values($categories);
        foreach ($categoryRows as &$row) $row['percentage_of_total'] = $total > 0 ? round(($row['total']/$total)*100,2) : 0;
        unset($row);

        $participantsStmt = $this->pdo->prepare('SELECT staff_role_snapshot, selected_qty, deployment_location FROM campaign_department_campaign_participants WHERE campaign_id=? ORDER BY id');
        $participantsStmt->execute([$campaignId]);
        $participants = $participantsStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalStaff = array_sum(array_map(fn($r)=>(int)($r['selected_qty'] ?? 0), $participants));
        $staffRows = [];
        foreach ($participants as $p) {
            $qty = max(1, (int)($p['selected_qty'] ?? 1));
            $estimated = $totalStaff > 0 ? ($staffSupportTotal * $qty / $totalStaff) : 0.0;
            $staffRows[] = [
                'staff_role'=>$p['staff_role_snapshot'] ?: 'Campaign Staff',
                'required_qty'=>$qty,
                'existing_matched_qty'=>$qty,
                'missing_qty'=>0,
                'deployment_days'=>max(1, (int)ceil((strtotime((string)($campaign['end_date'] ?? $campaign['start_date'] ?? date('Y-m-d'))) - strtotime((string)($campaign['start_date'] ?? date('Y-m-d'))))/86400)+1),
                'estimated_support_cost'=>round($estimated,2),
                'cost_type'=>'Deployment support only (not salary)',
                'deployment_location'=>$p['deployment_location'] ?: ($campaign['location'] ?? ''),
            ];
        }
        foreach ($locations as &$loc) {
            $loc['staff_qty'] = $totalStaff;
        }
        unset($loc);

        $partnerStmt = $this->pdo->prepare("SELECT p.name, p.organization_type, pe.engagement_type, pe.notes FROM campaign_department_partner_engagements pe LEFT JOIN campaign_department_partners p ON p.id=pe.partner_id WHERE pe.campaign_id=? ORDER BY pe.id");
        $partnerStmt->execute([$campaignId]);
        $partnerRows = [];
        foreach ($partnerStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $meta = json_decode((string)($row['notes'] ?? ''), true);
            if (!is_array($meta)) $meta = [];
            $contribution = trim((string)($meta['role'] ?? $meta['notes'] ?? $row['notes'] ?? ''));
            $partnerRows[] = [
                'partner'=>$row['name'] ?: 'Partner',
                'recommended_contribution'=>$contribution ?: 'Campaign support / coordination',
                'contribution_type'=>$row['organization_type'] ?: $row['engagement_type'],
                'estimated_budget_impact'=>'Recorded partner-funded line items: ' . number_format($partnerOffset,2),
                'verification_status'=>'Recorded / manual confirmation',
            ];
        }

        return [
            'success'=>true,
            'data'=>[
                'campaign'=>['id'=>$campaignId,'title'=>$campaign['title'],'location'=>$campaign['location'],'status'=>$campaign['status']],
                'workflow'=>$workflow ?: ['planning_status'=>'finalized','review_status'=>'none','rejection_reason'=>null],
                'summary'=>['total_budget'=>$total,'line_item_count'=>count($items),'partner_contribution_total'=>$partnerOffset,'staff_support_total'=>$staffSupportTotal],
                'line_items'=>$lineItems,
                'category_breakdown'=>$categoryRows,
                'action_breakdown'=>array_values($actions),
                'location_breakdown'=>array_values($locations),
                'staff_cost_impact'=>$staffRows,
                'partner_contribution_impact'=>[
                    'budget_before_confirmed_partner_contributions'=>$total,
                    'budget_after_confirmed_partner_contributions'=>max(0,$total-$partnerOffset),
                    'recorded_partner_contribution'=>$partnerOffset,
                    'note'=>'Only line items explicitly funded as Partner Contribution are treated as a budget offset.',
                    'items'=>$partnerRows,
                ],
                'contingency'=>[
                    'contingency_percentage'=>$total > 0 ? round(($contingencyAmount/$total)*100,2) : 0,
                    'contingency_amount'=>$contingencyAmount,
                    'reason'=>$contingencyAmount > 0 ? 'Derived from budget line items categorized or named as Contingency.' : 'No contingency line item is recorded yet.',
                    'may_cover'=>['Price changes','Additional transport','Reprinting','Weather delays','Emergency supplies'],
                ],
            ],
        ];
    }
}
