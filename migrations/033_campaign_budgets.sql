-- Campaign Budgets Table for Financial & Budgeting Section
-- This table stores budget line items for campaigns

CREATE TABLE IF NOT EXISTS campaign_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_type ENUM('consumable', 'material') NOT NULL DEFAULT 'consumable',
    quantity INT NOT NULL DEFAULT 1,
    unit_cost DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(12, 2) GENERATED ALWAYS AS (quantity * unit_cost) STORED,
    funding_source ENUM('government_allocated', 'reimbursable') NOT NULL DEFAULT 'government_allocated',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_funding_source (funding_source),
    INDEX idx_item_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add budget_total column to campaigns table if not exists
-- This will store the total budget calculated from budget line items
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS budget_processed TINYINT(1) DEFAULT 0;
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS budget_total_calculated DECIMAL(12, 2) DEFAULT 0.00;
