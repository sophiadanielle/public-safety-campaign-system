-- AI Recommendation Acceptance Flow
-- Adds acceptance audit columns to campaign_department_ai_recommendations.
-- converted_campaign_id (already exists) is the idempotency guard.

ALTER TABLE campaign_department_ai_recommendations ADD COLUMN IF NOT EXISTS accepted_at DATETIME NULL AFTER converted_campaign_id;
ALTER TABLE campaign_department_ai_recommendations ADD COLUMN IF NOT EXISTS accepted_by INT NULL AFTER accepted_at;
