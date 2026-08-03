-- AI Recommendation Acceptance Flow
-- Extends campaign_budgets to hold the full AI budget breakdown (item_type/funding_source
-- must be widened from ENUM so AI types like 'materials' / 'estimated_need' can be stored).

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE campaign_budgets MODIFY item_type VARCHAR(50) NOT NULL DEFAULT 'consumable';
ALTER TABLE campaign_budgets MODIFY funding_source VARCHAR(50) NOT NULL DEFAULT 'government_allocated';

ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS source_recommendation_id INT NULL AFTER id;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS category VARCHAR(120) NULL AFTER item_type;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS item_description TEXT NULL AFTER category;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS sessions_or_days INT NULL AFTER item_description;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS unit_label VARCHAR(80) NULL AFTER sessions_or_days;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS related_action TEXT NULL AFTER unit_label;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS recommendation_reason TEXT NULL AFTER related_action;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS pricing_source VARCHAR(120) NULL AFTER recommendation_reason;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS pricing_confidence VARCHAR(40) NULL AFTER pricing_source;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS calculation_basis TEXT NULL AFTER pricing_confidence;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS is_estimate TINYINT(1) NOT NULL DEFAULT 1 AFTER calculation_basis;
ALTER TABLE campaign_budgets ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0 AFTER is_estimate;

CREATE INDEX IF NOT EXISTS idx_campaign_budgets_recommendation ON campaign_budgets (source_recommendation_id);
