<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

class AiRecommendationSchemaService
{
    public static function ensure(PDO $pdo): void
    {
        self::ensureMainTable($pdo);
        self::ensurePlanningColumns($pdo);
        self::ensureChildTables($pdo);
        self::backfillCompatibilityColumns($pdo);
    }

    private static function ensureMainTable(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_department_ai_recommendations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category VARCHAR(50) NOT NULL DEFAULT 'crime',
                campaign_title VARCHAR(255) NOT NULL,
                main_trend VARCHAR(255) NULL,
                trend_key VARCHAR(120) NULL,
                description TEXT NULL,
                report_count INT NOT NULL DEFAULT 0,
                cluster_report_ids LONGTEXT NULL,
                affected_locations LONGTEXT NULL,
                earliest_date DATETIME NULL,
                latest_date DATETIME NULL,
                severity_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                frequency_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                recency_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                geographic_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                priority_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                priority_level VARCHAR(20) NOT NULL DEFAULT 'Low',
                scoring_breakdown LONGTEXT NULL,
                ai_reasoning TEXT NULL,
                ai_recommended_actions LONGTEXT NULL,
                ai_target_audience VARCHAR(500) NULL,
                generated_by VARCHAR(50) NOT NULL DEFAULT 'rule-based',
                recommendation_hash VARCHAR(64) NOT NULL,
                data_snapshot LONGTEXT NULL,
                is_test_data TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_recommendation_hash (recommendation_hash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private static function ensurePlanningColumns(PDO $pdo): void
    {
        $columns = [
            'campaign_description' => 'TEXT NULL',
            'incident_category' => "VARCHAR(255) NULL",
            'source_report_ids' => 'LONGTEXT NULL',
            'main_trend' => 'VARCHAR(255) NULL',
            'trend_key' => 'VARCHAR(120) NULL',
            'description' => 'TEXT NULL',
            'cluster_report_ids' => 'LONGTEXT NULL',
            'affected_locations' => 'LONGTEXT NULL',
            'earliest_date' => 'DATETIME NULL',
            'latest_date' => 'DATETIME NULL',
            'severity_score' => 'DECIMAL(5,2) NOT NULL DEFAULT 0.00',
            'frequency_score' => 'DECIMAL(5,2) NOT NULL DEFAULT 0.00',
            'recency_score' => 'DECIMAL(5,2) NOT NULL DEFAULT 0.00',
            'geographic_score' => 'DECIMAL(5,2) NOT NULL DEFAULT 0.00',
            'scoring_breakdown' => 'LONGTEXT NULL',
            'ai_reasoning' => 'TEXT NULL',
            'ai_recommended_actions' => 'LONGTEXT NULL',
            'ai_target_audience' => 'VARCHAR(500) NULL',
            'is_test_data' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'estimated_budget' => 'DECIMAL(14,2) NULL',
            'system_budget_max' => 'DECIMAL(14,2) NULL',
            'final_recommended_budget' => 'DECIMAL(14,2) NULL',
            'approved_budget' => 'DECIMAL(14,2) NULL',
            'approved_budget_by' => 'INT NULL',
            'approved_budget_at' => 'DATETIME NULL',
            'approval_status' => "VARCHAR(32) NOT NULL DEFAULT 'recommended'",
            'budget_validation_status' => "VARCHAR(40) NOT NULL DEFAULT 'unchecked'",
            'planning_status' => "VARCHAR(40) NOT NULL DEFAULT 'not_generated'",
            'planning_source' => 'VARCHAR(60) NULL',
            'planning_error_code' => 'VARCHAR(80) NULL',
            'planning_error_message' => 'TEXT NULL',
            'planning_version' => 'INT NOT NULL DEFAULT 0',
            'planning_started_at' => 'DATETIME NULL',
            'planning_generated_at' => 'DATETIME NULL',
            'last_recalculated_at' => 'DATETIME NULL',
            'effective_start_date' => 'DATE NULL',
            'effective_end_date' => 'DATE NULL',
            'approved_start_date' => 'DATE NULL',
            'approved_end_date' => 'DATE NULL',
            'approved_dates_by' => 'INT NULL',
            'approved_dates_at' => 'DATETIME NULL',
            'converted_campaign_id' => 'INT NULL',
            'recommended_duration' => 'INT NOT NULL DEFAULT 30',
        ];

        foreach ($columns as $name => $definition) {
            self::addColumnIfMissing($pdo, 'campaign_department_ai_recommendations', $name, $definition);
        }
    }

    private static function ensureChildTables(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_ai_recommendation_budget_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recommendation_id INT NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                category VARCHAR(120) NULL,
                item_type VARCHAR(120) NULL,
                quantity DECIMAL(12,2) NOT NULL DEFAULT 1.00,
                unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                sessions_or_days INT NOT NULL DEFAULT 1,
                subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                funding_source VARCHAR(120) NULL,
                related_action TEXT NULL,
                recommendation_reason TEXT NULL,
                pricing_source VARCHAR(120) NULL,
                is_estimate TINYINT(1) NOT NULL DEFAULT 1,
                validation_status VARCHAR(40) NOT NULL DEFAULT 'estimated',
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_ai_budget_recommendation (recommendation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_ai_recommendation_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recommendation_id INT NOT NULL,
                staff_id INT NULL,
                staff_name_snapshot VARCHAR(255) NULL,
                staff_role_snapshot VARCHAR(255) NULL,
                match_method VARCHAR(60) NOT NULL DEFAULT 'unmatched',
                recommendation_reason TEXT NULL,
                availability_status VARCHAR(60) NOT NULL DEFAULT 'Not Recorded',
                conflict_status VARCHAR(60) NOT NULL DEFAULT 'unknown',
                conflict_note TEXT NULL,
                is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
                confirmed_by INT NULL,
                confirmed_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_ai_participants_recommendation (recommendation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_ai_recommendation_partners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recommendation_id INT NOT NULL,
                partner_id INT NULL,
                partner_name_snapshot VARCHAR(255) NULL,
                organization_type_snapshot VARCHAR(120) NULL,
                capability_match_basis TEXT NULL,
                capability_is_inferred TINYINT(1) NOT NULL DEFAULT 1,
                recommended_role VARCHAR(255) NULL,
                recommendation_reason TEXT NULL,
                is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
                confirmed_by INT NULL,
                confirmed_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_ai_partners_recommendation (recommendation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_ai_recommendation_partner_suggestions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recommendation_id INT NOT NULL,
                organization_type VARCHAR(120) NOT NULL,
                capability_description TEXT NULL,
                rationale TEXT NULL,
                expected_contribution TEXT NULL,
                search_criteria TEXT NULL,
                acquisition_priority VARCHAR(30) NOT NULL DEFAULT 'medium',
                proposed_onboarding_date DATE NULL,
                proposal_status VARCHAR(40) NOT NULL DEFAULT 'proposed',
                reviewed_by INT NULL,
                reviewed_at DATETIME NULL,
                created_partner_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_ai_partner_suggestions_recommendation (recommendation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_ai_recommendation_schedule_phases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recommendation_id INT NOT NULL,
                sprint_number INT NOT NULL,
                sprint_title VARCHAR(255) NOT NULL,
                start_date DATE NULL,
                end_date DATE NULL,
                duration_days INT NOT NULL DEFAULT 1,
                objectives TEXT NULL,
                activities LONGTEXT NULL,
                assigned_staff LONGTEXT NULL,
                assigned_partners LONGTEXT NULL,
                locations LONGTEXT NULL,
                phase_budget DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                outputs TEXT NULL,
                completion_criteria TEXT NULL,
                dependencies LONGTEXT NULL,
                status VARCHAR(40) NOT NULL DEFAULT 'recommended',
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_ai_schedule_recommendation (recommendation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_ai_report_snapshots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recommendation_id INT NOT NULL,
                source_type VARCHAR(40) NOT NULL,
                external_report_id VARCHAR(120) NOT NULL,
                source_local_id VARCHAR(120) NULL,
                incident_title VARCHAR(255) NULL,
                description TEXT NULL,
                category VARCHAR(120) NULL,
                severity VARCHAR(60) NULL,
                incident_status VARCHAR(80) NULL,
                report_date DATE NULL,
                report_datetime DATETIME NULL,
                location VARCHAR(255) NULL,
                barangay_or_area VARCHAR(255) NULL,
                source_system VARCHAR(120) NULL,
                snapshot_version INT NOT NULL DEFAULT 1,
                source_updated_at DATETIME NULL,
                synchronized_at DATETIME NULL,
                is_missing_from_source TINYINT(1) NOT NULL DEFAULT 0,
                snapshot_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_ai_report_snapshot (recommendation_id, source_type, external_report_id),
                KEY idx_ai_reports_recommendation (recommendation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS government_budget_allocations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fiscal_year VARCHAR(20) NOT NULL,
                total_allocation DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(40) NOT NULL DEFAULT 'draft',
                effective_from DATE NULL,
                effective_until DATE NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private static function backfillCompatibilityColumns(PDO $pdo): void
    {
        self::tryExec($pdo, "
            UPDATE campaign_department_ai_recommendations
            SET main_trend = COALESCE(NULLIF(main_trend, ''), NULLIF(incident_category, ''), campaign_title),
                description = COALESCE(NULLIF(description, ''), NULLIF(campaign_description, '')),
                cluster_report_ids = COALESCE(NULLIF(cluster_report_ids, ''), NULLIF(source_report_ids, '')),
                trend_key = COALESCE(NULLIF(trend_key, ''), LOWER(REPLACE(COALESCE(NULLIF(incident_category, ''), campaign_title), ' ', '_'))),
                planning_status = COALESCE(NULLIF(planning_status, ''), 'not_generated'),
                approval_status = COALESCE(NULLIF(approval_status, ''), 'recommended'),
                budget_validation_status = COALESCE(NULLIF(budget_validation_status, ''), 'unchecked')
        ");
    }

    private static function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private static function tryExec(PDO $pdo, string $sql): void
    {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log('AI recommendation schema compatibility SQL failed: ' . $e->getMessage());
        }
    }
}
