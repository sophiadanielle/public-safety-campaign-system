<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

class ReferenceStaffController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {}

    public function index(?array $user, array $params = []): array
    {
        $stmt = $this->pdo->query('
            SELECT id, name, role, qty, created_at, updated_at
            FROM `campaign_department_reference_staff`
            ORDER BY name ASC
        ');
        $staff = $stmt->fetchAll();

        return ['success' => true, 'data' => $staff];
    }

    public function store(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $role = trim($input['role'] ?? '');
        $qty = (int) ($input['qty'] ?? 1);

        if ($name === '') {
            http_response_code(400);
            return ['error' => 'Staff name is required'];
        }

        if ($role === '') {
            http_response_code(400);
            return ['error' => 'Position/Role is required'];
        }

        if ($qty < 1) {
            $qty = 1;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_reference_staff` (name, role, qty, created_at, updated_at)
            VALUES (:name, :role, :qty, NOW(), NOW())
        ');
        $stmt->execute([
            'name' => $name,
            'role' => $role,
            'qty' => $qty,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return ['success' => true, 'data' => ['id' => $id, 'name' => $name, 'role' => $role, 'qty' => $qty]];
    }

    public function destroy(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid staff ID'];
        }

        $stmt = $this->pdo->prepare('DELETE FROM `campaign_department_reference_staff` WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return ['error' => 'Staff member not found'];
        }

        return ['success' => true, 'data' => ['deleted' => true]];
    }

    public function roles(?array $user, array $params = []): array
    {
        $stmt = $this->pdo->query('
            SELECT DISTINCT role FROM `campaign_department_reference_staff`
            WHERE role IS NOT NULL AND role != \'\'
            ORDER BY role ASC
        ');
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return ['success' => true, 'data' => $roles];
    }
}
