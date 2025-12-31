-- Add planning and resource allocation fields to campaigns table
ALTER TABLE `campaign_department_campaigns`
ADD COLUMN objectives TEXT NULL AFTER description,
ADD COLUMN location VARCHAR(255) NULL AFTER objectives,
ADD COLUMN assigned_staff JSON NULL AFTER location,
ADD COLUMN barangay_target_zones JSON NULL AFTER assigned_staff,
ADD COLUMN budget DECIMAL(12,2) NULL AFTER barangay_target_zones,
ADD COLUMN staff_count INT UNSIGNED NULL AFTER budget,
ADD COLUMN materials_json JSON NULL AFTER staff_count;

-- Update status enum to include new statuses
ALTER TABLE `campaign_department_campaigns`
MODIFY COLUMN status ENUM('draft','pending','approved','ongoing','completed','scheduled','active','archived') NOT NULL DEFAULT 'draft';

-- Update existing status values if needed (optional, adjust as needed)
-- UPDATE campaigns SET status = 'draft' WHERE status = 'draft';
-- UPDATE campaigns SET status = 'ongoing' WHERE status = 'active';
-- UPDATE campaigns SET status = 'completed' WHERE status = 'completed';

