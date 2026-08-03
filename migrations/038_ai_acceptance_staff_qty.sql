-- AI Recommendation Acceptance Flow
-- Adds qty column to campaign_department_reference_staff (code already SELECTs it,
-- but the live table lacks it — fixes the latent schema mismatch).

ALTER TABLE campaign_department_reference_staff ADD COLUMN IF NOT EXISTS qty INT NOT NULL DEFAULT 1 AFTER role;
