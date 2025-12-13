-- Add links and enrichment fields for cross-module wiring
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Content usage can reference events and surveys
ALTER TABLE content_usage
    ADD COLUMN event_id INT UNSIGNED NULL AFTER tag_id,
    ADD COLUMN survey_id INT UNSIGNED NULL AFTER event_id,
    ADD CONSTRAINT fk_content_usage_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_content_usage_survey FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE SET NULL;

-- Surveys can be tied to events
ALTER TABLE surveys
    ADD COLUMN event_id INT UNSIGNED NULL AFTER campaign_id,
    ADD CONSTRAINT fk_surveys_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL;

-- Partner engagements can target a specific event
ALTER TABLE partner_engagements
    ADD COLUMN event_id INT UNSIGNED NULL AFTER campaign_id,
    ADD CONSTRAINT fk_partner_engagements_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL;

-- Audience enrichment
ALTER TABLE audience_segments
    ADD COLUMN demographics_json JSON NULL AFTER criteria,
    ADD COLUMN risk_level VARCHAR(50) NULL AFTER demographics_json,
    ADD COLUMN geographies_json JSON NULL AFTER risk_level,
    ADD COLUMN preferences_json JSON NULL AFTER geographies_json;

ALTER TABLE audience_members
    ADD COLUMN risk_level VARCHAR(50) NULL AFTER channel,
    ADD COLUMN geo VARCHAR(150) NULL AFTER risk_level,
    ADD COLUMN preferences_json JSON NULL AFTER geo;

-- Event logistics/materials
ALTER TABLE events
    ADD COLUMN logistics_json JSON NULL AFTER ends_at,
    ADD COLUMN materials_json JSON NULL AFTER logistics_json;

