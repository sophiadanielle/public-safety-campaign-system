# Public Safety Campaign Management System

## System Overview

A comprehensive web-based Public Safety Campaign Management System for Quezon City barangays, focused on **preparedness and awareness campaigns only**.

## Technology Stack

- **Backend:** PHP 8+ (Plain PHP with MVC architecture)
- **Database:** MySQL 8+
- **Authentication:** JWT (Firebase JWT library)
- **AI Service:** Google AutoML (with fallback to heuristic-based prediction)

## Architecture

```
├── index.php                    # API Gateway (entry point)
├── src/
│   ├── Config/
│   │   └── db_connect.php       # Database connection
│   ├── Controllers/             # MVC Controllers
│   │   ├── AuthController.php
│   │   ├── CampaignController.php
│   │   ├── ContentController.php
│   │   ├── SegmentController.php
│   │   ├── EventController.php
│   │   ├── SurveyController.php
│   │   ├── ImpactController.php
│   │   ├── PartnerController.php
│   │   └── AutoMLController.php
│   ├── Middleware/
│   │   ├── JWTMiddleware.php   # JWT authentication
│   │   └── RoleMiddleware.php  # Role-based access control
│   ├── Services/
│   │   ├── AutoMLService.php   # AI prediction microservice
│   │   ├── AudienceEvaluator.php
│   │   └── ImpactService.php
│   └── Routes/                 # API route definitions
├── migrations/                  # Database migrations
└── public/                     # Frontend files
```

## Authentication & Roles

### Roles
1. **Barangay Administrator** - Full access
2. **Barangay Staff** - Create/manage campaigns, limited admin
3. **School Partner** - View campaigns, coordinate activities
4. **NGO Partner** - View campaigns, coordinate activities

### Authentication
- JWT token-based authentication
- Token in `Authorization: Bearer <token>` header
- Role-based middleware for protected routes

## Core Modules

### 1. Campaign Planning & Scheduling

**Table:** `campaigns`

**Key Features:**
- Create, update, archive campaigns
- Assign content and audience segments
- Manual scheduling
- AI-recommended posting time
- Accept/override AI recommendations
- Calendar-based view
- Conflict checking with events

**API Endpoints:**
- `GET /api/v1/campaigns` - List campaigns
- `POST /api/v1/campaigns` - Create campaign
- `GET /api/v1/campaigns/{id}` - Get campaign
- `PUT /api/v1/campaigns/{id}` - Update campaign
- `POST /api/v1/campaigns/{id}/ai-recommendation` - Request AI recommendation
- `POST /api/v1/campaigns/{id}/final-schedule` - Set final schedule
- `GET /api/v1/campaigns/calendar` - Calendar view
- `POST /api/v1/campaigns/{id}/check-conflicts` - Check scheduling conflicts

### 2. AI Timing Prediction Microservice

**Service:** `AutoMLService`

**Features:**
- Separate microservice (can be HTTP-based)
- Google AutoML integration (with fallback)
- No direct database writes
- Returns recommended day, time, and confidence score

**Input:**
```json
{
  "campaign_category": "fire_safety",
  "audience_segment_id": 1,
  "day_of_week_range": [1, 7],
  "time_window": "09:00-18:00",
  "historical_engagement": {
    "views": [],
    "attendance": [],
    "ratings": []
  }
}
```

**Output:**
```json
{
  "recommended_day": 3,
  "recommended_time": "14:00",
  "suggested_datetime": "2025-01-15 14:00:00",
  "confidence_score": 0.85,
  "model_source": "google_automl"
}
```

### 3. Content Repository

**Table:** `content_items`

**Fields:**
- `hazard_category` - Type of hazard
- `intended_audience` - Target audience
- `source` - Content source
- `approval_status` - pending/approved/rejected
- `file_path` - File location

**Features:**
- Upload files
- Approval workflow
- Version tracking
- Search and filtering

### 4. Target Audience Segmentation

**Table:** `audience_segments`

**Fields:**
- `geographic_scope` - Location coverage
- `sector_type` - Sector classification
- `risk_level` - low/medium/high
- `segmentation_basis` - Criteria description

**Features:**
- Create/edit segments
- Assign risk levels
- Link to campaigns
- View participation history

### 5. Event & Seminar Management

**Table:** `events`

**Fields:**
- `event_type` - seminar/drill/workshop/meeting/other
- `facilitators` - JSON array
- `attendance_count` - Total attendance
- `status` - scheduled/ongoing/completed/cancelled

**Features:**
- Schedule seminars and drills
- Assign facilitators
- Link to campaigns
- Track attendance

### 6. Feedback & Survey Tools

**Tables:** `surveys`, `survey_questions`, `survey_responses`, `feedback`

**Features:**
- Create surveys
- Collect feedback with ratings (1-5)
- Aggregate ratings
- Export summary reports

### 7. Impact Monitoring

**Views:**
- `campaign_engagement_summary` - Engagement metrics
- `timing_effectiveness` - AI vs manual scheduling comparison

**Features:**
- Campaign engagement summaries
- Attendance comparisons
- Timing effectiveness analysis
- Preparedness score summaries

### 8. Partner Management

**Table:** `partners`

**Fields:**
- `organization_type` - school/ngo/government/private/other
- `contact_details` - JSON object

**Features:**
- Register partner organizations
- Coordinate joint campaigns
- Share schedules and content
- Track participation

## Database Schema

See `migrations/011_complete_schema_update.sql` for the complete schema.

## API Gateway

**Entry Point:** `index.php`

**Responsibilities:**
- Single entry point (`/api/v1/*`)
- Route requests to controllers
- Authentication & authorization
- Request validation
- Error handling

## Environment Configuration

Create `.env` file:

```env
DB_HOST=localhost
DB_NAME=LGU
DB_USER=root
DB_PASS=

JWT_SECRET=your_secret_key_here
JWT_ISSUER=public-safety-campaign
JWT_AUDIENCE=public-safety-clients
JWT_EXPIRY_SECONDS=3600

GOOGLE_AUTOML_ENDPOINT=https://automl.googleapis.com/v1/...
GOOGLE_AUTOML_API_KEY=your_api_key_here

UPLOAD_PATH=/path/to/uploads
```

## Installation

1. Install dependencies:
```bash
composer install
```

2. Run migrations:
```bash
mysql -u root -p < migrations/001_initial_schema.sql
mysql -u root -p < migrations/011_complete_schema_update.sql
```

3. Configure `.env` file

4. Set up web server to point to project root

## API Usage Examples

### Authentication
```bash
POST /api/v1/auth/login
{
  "email": "admin@example.com",
  "password": "password"
}
```

### Create Campaign
```bash
POST /api/v1/campaigns
Authorization: Bearer <token>
{
  "title": "Fire Safety Week 2025",
  "description": "Annual fire safety awareness campaign",
  "category": "fire_safety",
  "geographic_scope": "Quezon City",
  "start_date": "2025-03-01",
  "end_date": "2025-03-07",
  "objectives": "Increase fire safety awareness",
  "location": "Barangay Hall",
  "status": "draft"
}
```

### Request AI Recommendation
```bash
POST /api/v1/campaigns/1/ai-recommendation
Authorization: Bearer <token>
{
  "features": {
    "campaign_category": "fire_safety",
    "day_of_week_range": [1, 5]
  }
}
```

## Constraints

⚠️ **This system is NOT:**
- An emergency response system
- A dispatch platform
- A law enforcement tool

It is strictly a **campaign management and preparedness planning system**.

















