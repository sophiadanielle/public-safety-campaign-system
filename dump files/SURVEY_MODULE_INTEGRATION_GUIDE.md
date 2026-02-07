# Feedback & Survey Tools Module - Integration Guide

## Overview

The Feedback and Survey Tools module collects feedback, evaluations, and survey responses from multiple subsystems and provides aggregated summaries and reports.

## Module Structure

### Data Stored
- ✅ **Survey ID** - Unique identifier
- ✅ **Linked Campaign** - `campaign_id` field
- ✅ **Linked Event** - `event_id` field  
- ✅ **Survey Questions** - Stored in `campaign_department_survey_questions`
- ✅ **Response Ratings** - Stored in `campaign_department_survey_response_details`
- ✅ **Open-ended Comments** - Stored as text in response details
- ✅ **Submission Timestamps** - `submission_timestamp` field
- ✅ **Aggregated Results** - Pre-computed in `campaign_department_survey_aggregated_results`

### Functional Features
- ✅ Create surveys and polls
- ✅ Collect feedback
- ✅ View aggregated summaries
- ✅ Export feedback reports (CSV)

## Integration Points

### 1. ← Event and Seminar Management
**Direction:** Receives data FROM Event Management  
**Data Flow:**
- Event Management collects **attendance and participation data**
- Surveys can be linked to events via `event_id` field
- Integration checkpoint: `subsystem_type = 'event_management'`

**Example Use Case:**
```sql
-- Create survey linked to an event
INSERT INTO campaign_department_surveys (event_id, title, status) 
VALUES (1, 'Post-Event Feedback Survey', 'published');

-- Integration checkpoint tracks data exchange
INSERT INTO campaign_department_survey_integration_checkpoints 
(survey_id, subsystem_type, integration_status, sent_data)
VALUES (1, 'event_management', 'sent', '{"attendance_count": 150, "event_id": 1}');
```

### 2. ↔ Disaster Preparedness Training and Simulation
**Direction:** Bidirectional (sends AND receives)  
**Data Flow:**
- **Sends:** Survey results and evaluation scores
- **Receives:** Training evaluation data
- Integration checkpoint: `subsystem_type = 'disaster_preparedness'`

**Example Use Case:**
```sql
-- Survey collects training evaluation
INSERT INTO campaign_department_surveys (campaign_id, title, status)
VALUES (5, 'Disaster Preparedness Training Evaluation', 'published');

-- Integration checkpoint for bidirectional sync
INSERT INTO campaign_department_survey_integration_checkpoints 
(survey_id, subsystem_type, integration_status, sent_data, received_data)
VALUES (2, 'disaster_preparedness', 'confirmed', 
  '{"survey_results": {...}}', 
  '{"training_scores": {...}}');
```

### 3. → Community Policing and Surveillance
**Direction:** Sends data TO Community Policing  
**Data Flow:**
- **Sends:** Engagement feedback from awareness and outreach events
- Integration checkpoint: `subsystem_type = 'community_policing'`

**Example Use Case:**
```sql
-- Survey collects community engagement feedback
INSERT INTO campaign_department_surveys (campaign_id, title, status)
VALUES (3, 'Community Policing Outreach Feedback', 'published');

-- Integration checkpoint tracks data sent to community policing
INSERT INTO campaign_department_survey_integration_checkpoints 
(survey_id, subsystem_type, integration_status, sent_data)
VALUES (3, 'community_policing', 'sent', 
  '{"engagement_metrics": {...}, "feedback_summary": {...}}');
```

### 4. ↔ Emergency Response System
**Direction:** Bidirectional (sends AND receives)  
**Data Flow:**
- **Sends:** Post-event evaluations and feedback
- **Receives:** Operational insights and after-action review data
- Integration checkpoint: `subsystem_type = 'emergency_response'`

**Example Use Case:**
```sql
-- Survey for after-action review
INSERT INTO campaign_department_surveys (campaign_id, title, status)
VALUES (4, 'Emergency Response After-Action Review', 'published');

-- Integration checkpoint for bidirectional sync
INSERT INTO campaign_department_survey_integration_checkpoints 
(survey_id, subsystem_type, integration_status, sent_data, received_data)
VALUES (4, 'emergency_response', 'confirmed',
  '{"post_event_evaluation": {...}}',
  '{"operational_insights": {...}}');
```

## Database Schema

### Core Tables
1. **`campaign_department_surveys`**
   - `id`, `campaign_id`, `event_id`, `title`, `description`
   - `status` (draft, published, closed)
   - `published_via` (link, qr_code, both)
   - `created_by`, `published_by`, `published_at`, `closed_at`

2. **`campaign_department_survey_questions`**
   - `id`, `survey_id`, `question_text`, `question_type`
   - `question_order`, `required_flag`
   - `options_json` (for multiple choice)

3. **`campaign_department_survey_responses`**
   - `id`, `survey_id`, `audience_member_id`
   - `respondent_identifier`, `responses_json`
   - `submission_timestamp`

4. **`campaign_department_survey_response_details`**
   - `id`, `response_id`, `question_id`, `response_value`
   - Individual question responses for better querying

5. **`campaign_department_survey_aggregated_results`**
   - `survey_id`, `question_id`
   - `average_rating`, `response_distribution`, `total_responses`
   - Pre-computed for performance

6. **`campaign_department_survey_integration_checkpoints`** ⭐
   - `id`, `survey_id`, `subsystem_type`
   - `integration_status` (pending, sent, acknowledged, confirmed, failed)
   - `sent_data`, `received_data` (JSON)
   - `last_sync_at`, `sync_attempts`, `error_message`

7. **`campaign_department_survey_audit_log`**
   - Tracks all survey changes and actions

## API Endpoints

### Survey Management
- `GET /api/v1/surveys` - List surveys (filter by campaign_id, event_id, status)
- `POST /api/v1/surveys` - Create survey
- `GET /api/v1/surveys/{id}` - Get survey details (public for published)
- `POST /api/v1/surveys/{id}/questions` - Add question
- `POST /api/v1/surveys/{id}/publish` - Publish survey
- `POST /api/v1/surveys/{id}/close` - Close survey

### Response Collection
- `POST /api/v1/surveys/{id}/responses` - Submit response (public)
- `GET /api/v1/surveys/{id}/responses` - View responses (authenticated)
- `GET /api/v1/surveys/{id}/responses/export` - Export CSV (authenticated)

### Integration
- `GET /api/v1/surveys/{id}/qr` - Get QR code for survey

## Integration Status Tracking

The `campaign_department_survey_integration_checkpoints` table tracks integration status:

```sql
-- Check integration status for a survey
SELECT 
    s.title,
    ic.subsystem_type,
    ic.integration_status,
    ic.sent_data,
    ic.received_data,
    ic.last_sync_at,
    ic.sync_attempts
FROM campaign_department_surveys s
JOIN campaign_department_survey_integration_checkpoints ic 
    ON ic.survey_id = s.id
WHERE s.id = 1;
```

## Next Steps

1. ✅ **Database Schema** - Migration 027 applied
2. ✅ **Core Functionality** - Survey creation, questions, responses
3. ✅ **Integration Infrastructure** - Checkpoints table ready
4. ⚠️ **Integration Implementation** - Connect to actual subsystems
5. ⚠️ **Data Sync** - Implement bidirectional data exchange
6. ⚠️ **Reporting** - Enhanced reports with integration data

## Testing Integration Points

To test the integration checkpoints:

```sql
-- Create a test survey
INSERT INTO campaign_department_surveys (campaign_id, title, status, created_by)
VALUES (1, 'Test Integration Survey', 'published', 1);

-- Create integration checkpoint for event management
INSERT INTO campaign_department_survey_integration_checkpoints 
(survey_id, subsystem_type, integration_status, sent_data)
VALUES (LAST_INSERT_ID(), 'event_management', 'sent', 
  '{"attendance_count": 100, "event_id": 1}');

-- Update checkpoint when acknowledged
UPDATE campaign_department_survey_integration_checkpoints
SET integration_status = 'acknowledged',
    last_sync_at = NOW()
WHERE survey_id = LAST_INSERT_ID() 
  AND subsystem_type = 'event_management';
```





