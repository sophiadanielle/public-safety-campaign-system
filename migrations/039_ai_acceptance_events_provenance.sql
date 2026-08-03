-- AI Recommendation Acceptance Flow
-- Adds AI provenance columns to campaign_department_events so accepted
-- AI events keep a traceable link to the source recommendation + sprint.

ALTER TABLE campaign_department_events ADD COLUMN IF NOT EXISTS ai_recommendation_id INT NULL AFTER linked_campaign_id;
ALTER TABLE campaign_department_events ADD COLUMN IF NOT EXISTS ai_sprint_number INT NULL AFTER ai_recommendation_id;
ALTER TABLE campaign_department_events ADD COLUMN IF NOT EXISTS ai_objectives TEXT NULL AFTER ai_sprint_number;

CREATE INDEX IF NOT EXISTS idx_events_ai_recommendation ON campaign_department_events (ai_recommendation_id);
