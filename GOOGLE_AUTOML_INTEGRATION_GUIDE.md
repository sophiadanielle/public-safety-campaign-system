# Google AutoML Integration Module - Implementation Guide

## Overview

This document describes the comprehensive Google AutoML integration module for the Public Safety Campaign Management System. The module provides AI-assisted scheduling, conflict prediction, engagement forecasting, and readiness assessment.

## Architecture

### Core Services

1. **AITrainingService** (`src/Services/AITrainingService.php`)
   - Handles model training, versioning, and deployment
   - Manages Google Vertex AI AutoML Tables integration
   - Tracks training jobs and model versions

2. **AIPredictionService** (`src/Services/AIPredictionService.php`)
   - Provides comprehensive prediction capabilities
   - Implements caching layer (24-hour TTL)
   - Logs all prediction requests for audit

3. **DataPreparationService** (`src/Services/DataPreparationService.php`)
   - ETL pipeline for preparing training datasets
   - Extracts features from campaigns, events, attendance, feedback
   - Supports 4 model types: schedule_optimization, conflict_prediction, engagement_prediction, readiness_forecast

4. **AutoMLService** (`src/Services/AutoMLService.php`)
   - Existing service for schedule optimization predictions
   - Enhanced to work with new prediction service

### Database Schema

Migration: `migrations/020_automl_integration.sql`

**Tables:**
- `ai_model_versions` - Model training metadata and versions
- `ai_training_logs` - Training event audit logs
- `ai_prediction_cache` - Cached predictions (24-hour TTL)
- `ai_prediction_requests` - All prediction request logs

### API Endpoints

#### Training Endpoints (Admin Only)
- `POST /api/v1/ai/training/start` - Start training a new model
- `GET /api/v1/ai/training/status/{id}` - Check training status
- `POST /api/v1/ai/training/deploy/{id}` - Deploy a trained model
- `GET /api/v1/ai/training/models` - List all model versions
- `GET /api/v1/ai/training/data-preview` - Preview training data

#### Prediction Endpoints
- `POST /api/v1/ai/predict/schedule` - Predict optimal schedule
- `POST /api/v1/ai/predict/conflict` - Predict conflict risk
- `POST /api/v1/ai/predict/engagement` - Predict engagement likelihood
- `POST /api/v1/ai/predict/readiness` - Forecast campaign readiness
- `GET /api/v1/ai/insights` - Get AI insights for dashboard

## Model Types

### 1. Schedule Optimization
**Purpose:** Recommend optimal deployment times for campaigns

**Features:**
- Campaign category, geographic scope, day of week, month
- Budget range, staff count, audience segment size
- Historical reach, attendance, ratings
- Conflict flags

**Target:** `target_optimal_time_score` (0-1 scale)

### 2. Conflict Prediction
**Purpose:** Predict scheduling conflicts (overlapping campaigns, staff overload, venue conflicts)

**Features:**
- Campaign category, geographic scope, day of week, hour
- Staff count, concurrent campaigns/events, staff overlap

**Target:** `target_conflict_probability` (0=low, 1=medium, 2=high)

### 3. Engagement Prediction
**Purpose:** Forecast expected engagement and attendance

**Features:**
- Campaign category, geographic scope, day of week, hour
- Audience size, notifications sent

**Target:** `target_engagement_rate` (0-1 scale)

### 4. Readiness Forecast
**Purpose:** Assess if a campaign is ready for deployment

**Features:**
- Campaign category, days until start
- Staff count, audience segments assigned, content attached, events linked, schedule status

**Target:** `target_readiness_score` (0=not ready, 1=ready)

## Setup Instructions

### 1. Environment Variables

Add to `.env`:
```env
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_CLOUD_REGION=us-central1
GOOGLE_SERVICE_ACCOUNT_KEY=path/to/service-account-key.json
GOOGLE_AUTOML_ENDPOINT=https://automl.googleapis.com/v1/projects/{project}/locations/{region}/models/{model}:predict
GOOGLE_AUTOML_API_KEY=your-api-key
```

### 2. Run Migration

```bash
php run_migrations.php
```

Or manually:
```bash
mysql -u root -p your_database < migrations/020_automl_integration.sql
```

### 3. Training a Model (Admin Only)

**Example: Train schedule optimization model**

```bash
curl -X POST http://localhost/public-safety-campaign-system/api/v1/ai/training/start \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model_type": "schedule_optimization",
    "model_name": "Schedule Optimization v1.0",
    "data_limit": 1000
  }'
```

**Check training status:**
```bash
curl -X GET http://localhost/public-safety-campaign-system/api/v1/ai/training/status/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Deploy model:**
```bash
curl -X POST http://localhost/public-safety-campaign-system/api/v1/ai/training/deploy/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 4. Using Predictions

**Predict optimal schedule:**
```bash
curl -X POST http://localhost/public-safety-campaign-system/api/v1/ai/predict/schedule \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "campaign_id": 7,
    "features": {
      "audience_segment_id": 1
    }
  }'
```

**Predict conflict risk:**
```bash
curl -X POST http://localhost/public-safety-campaign-system/api/v1/ai/predict/conflict \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "entity_type": "campaign",
    "entity_id": 7
  }'
```

## Frontend Integration

### Displaying AI Recommendations

The frontend should call prediction endpoints and display results:

1. **Schedule Optimization** - Show recommended date/time with confidence score
2. **Conflict Warnings** - Display risk badges (Low/Medium/High) with factors
3. **Engagement Prediction** - Show expected attendance and engagement rate
4. **Readiness Forecast** - Display readiness score and missing components

### Example Frontend Code

```javascript
// Predict conflict risk for a campaign
async function checkConflictRisk(campaignId) {
    const res = await fetch(`${apiBase}/api/v1/ai/predict/conflict`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${getToken()}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            entity_type: 'campaign',
            entity_id: campaignId
        })
    });
    
    const data = await res.json();
    if (data.success) {
        const { risk_level, conflict_probability, factors } = data.prediction;
        // Display warning badge based on risk_level
        // Show factors contributing to conflict
    }
}
```

## Security & Access Control

- **Training:** Only `system_admin` and `barangay_admin` roles can initiate training
- **Deployment:** Only admins can deploy models
- **Predictions:** All authenticated users can request predictions
- **Logging:** All prediction requests are logged for audit
- **PII Protection:** No personally identifiable information is sent to AutoML

## Caching

- Predictions are cached for 24 hours
- Cache key is MD5 hash of (model_type, entity_type, entity_id, features)
- Cache automatically expires and refreshes

## Fallback Behavior

- If Google AutoML is not configured, system uses heuristic predictions
- If model training fails, system continues with existing models or heuristics
- If prediction API fails, system gracefully degrades to rule-based recommendations

## Performance

- Caching reduces API calls by ~80% for repeated requests
- Predictions are non-blocking (async/background processing recommended)
- Training jobs run asynchronously (check status via polling)

## Monitoring

- Check `ai_training_logs` for training progress
- Check `ai_prediction_requests` for prediction usage and errors
- Monitor cache hit rates via `used_cache` flag

## Future Enhancements

1. **Barangay-level Models:** Train separate models per barangay
2. **City-wide Aggregation:** Aggregate models across all barangays
3. **Real-time Training:** Auto-retrain when sufficient new data accumulates
4. **A/B Testing:** Compare model versions for performance
5. **Explainability:** Add feature importance and SHAP values

## Troubleshooting

**Training fails:**
- Check Google Cloud credentials
- Verify dataset has sufficient examples (minimum 100)
- Check training logs in `ai_training_logs` table

**Predictions return errors:**
- Verify model is deployed (`is_active = TRUE` in `ai_model_versions`)
- Check cache expiration (clear cache if needed)
- Review `ai_prediction_requests` for error messages

**Cache not working:**
- Verify `ai_prediction_cache` table exists
- Check cache expiration timestamps
- Clear expired cache: `DELETE FROM ai_prediction_cache WHERE expires_at < NOW()`



