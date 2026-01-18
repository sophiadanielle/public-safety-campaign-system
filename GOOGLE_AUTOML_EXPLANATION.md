# How Google AutoML Works in This System

## Overview

The Google AutoML integration provides AI-powered scheduling recommendations for campaigns. The system is designed to work **with or without** Google AutoML configured.

---

## How It Works

### 1. **User Flow**

```
User clicks "Get Prediction" 
  ↓
Frontend sends POST request to /api/v1/campaigns/{id}/ai-recommendation
  ↓
CampaignController::requestAIRecommendation() receives request
  ↓
AutoMLService::predict() is called
  ↓
System checks if Google AutoML is configured
  ↓
  ├─ YES → Calls Google AutoML API
  │         ↓
  │    If successful → Returns AutoML prediction
  │    If fails → Falls back to heuristics
  │
  └─ NO → Uses heuristic prediction immediately
  ↓
Prediction result returned to frontend
  ↓
User sees recommended date/time with confidence score
```

### 2. **Configuration Check**

The system checks for Google AutoML configuration via environment variables:

```php
GOOGLE_AUTOML_ENDPOINT=https://automl.googleapis.com/v1/projects/...
GOOGLE_AUTOML_API_KEY=your-api-key-here
```

**Location**: `.env` file or server environment variables

### 3. **AutoML Service Logic**

**File**: `src/Services/AutoMLService.php`

```php
public function predict(int $campaignId, array $features = []): array
{
    // 1. Get campaign data
    $campaign = $this->getCampaignData($campaignId);
    
    // 2. Prepare features (historical data, engagement patterns)
    $preparedFeatures = $this->prepareFeatures($campaignId, $campaign, $features);
    
    // 3. Check if Google AutoML is configured
    if ($this->useGoogleAutoML) {
        try {
            // Call Google AutoML API
            return $this->predictWithGoogleAutoML($preparedFeatures);
        } catch (\Exception $e) {
            // Fallback to heuristics if AutoML fails
            return $this->predictWithHeuristics($campaignId, $preparedFeatures);
        }
    }
    
    // 4. Use heuristics if AutoML not configured
    return $this->predictWithHeuristics($campaignId, $preparedFeatures);
}
```

### 4. **Heuristic Fallback**

If Google AutoML is **not configured** or **fails**, the system uses intelligent heuristics:

**Data Sources**:
- Historical campaign performance (similar campaigns)
- Engagement patterns (views, attendance, ratings)
- Day-of-week analysis (which days perform best)
- Time-of-day analysis (which times perform best)
- Audience segment data (if provided)

**Algorithm**:
1. Find similar campaigns (same category)
2. Aggregate historical engagement data
3. Calculate best day of week (highest engagement)
4. Calculate best time of day (highest engagement)
5. Generate confidence score based on data quality
6. Return recommended date/time

### 5. **Prediction Response**

The API returns:

```json
{
  "prediction": {
    "suggested_datetime": "2024-01-15 14:30:00",
    "confidence_score": 0.85,
    "model_source": "google_automl" | "heuristic_with_history" | "heuristic",
    "automl_configured": true | false,
    "fallback_reason": "Error message if fallback used",
    "recommended_day": "Monday",
    "recommended_time": "14:30"
  },
  "message": "Google AutoML recommendation generated successfully"
}
```

---

## Current Status

### ✅ **Working Without Google AutoML**

The system **always works** using heuristic predictions, even if Google AutoML is not configured.

**What you'll see**:
- Prediction still generated
- Model source: "Heuristic (with historical data)"
- Status badge: "⚠ Not Configured" (if AutoML not set up)
- Confidence score based on available historical data

### 🔧 **To Enable Google AutoML**

1. **Get Google AutoML Credentials**:
   - Create a project in Google Cloud Console
   - Enable AutoML Tables API
   - Create an API key
   - Set up your AutoML model endpoint

2. **Configure Environment Variables**:
   ```env
   GOOGLE_AUTOML_ENDPOINT=https://automl.googleapis.com/v1/projects/YOUR_PROJECT/locations/us-central1/models/YOUR_MODEL:predict
   GOOGLE_AUTOML_API_KEY=your-api-key-here
   ```

3. **Restart Server**:
   - Restart PHP/Apache to load new environment variables

4. **Test**:
   - Click "Get Prediction" button
   - Check model source - should show "Google AutoML"
   - Status badge should show "✓ Active"

---

## Troubleshooting "Get Prediction" Button

### If Button Does Nothing:

1. **Open Browser Console (F12)**
   - Look for JavaScript errors
   - Check if `handleGetPredictionClick` function exists
   - Check if button click is registered

2. **Check Network Tab**:
   - See if API request is being made
   - Check request URL and payload
   - Check response status and body

3. **Verify Campaign Selection**:
   - Ensure a campaign is selected from dropdown
   - Check dropdown value is not empty

4. **Check API Endpoint**:
   - Verify `apiBase` variable is defined
   - Check if route `/api/v1/campaigns/{id}/ai-recommendation` exists

### Common Issues:

- **"Function not loaded"**: Refresh the page
- **"Please select a campaign"**: Select a campaign from dropdown first
- **401 Unauthorized**: Token expired, log in again
- **500 Server Error**: Check PHP error logs

---

## Testing the Button

### Manual Test in Console:

```javascript
// Test if function exists
console.log(typeof handleGetPredictionClick);
console.log(typeof getAutoMLPrediction);

// Test button click manually
document.getElementById('getPredictionBtn').click();

// Test with campaign selected
const select = document.getElementById('automl_campaign_id');
select.value = '7'; // Use actual campaign ID
document.getElementById('getPredictionBtn').click();
```

### Direct API Test:

```bash
# Replace {campaign_id} and {token} with actual values
curl -X POST "http://localhost/public-safety-campaign-system/index.php/api/v1/campaigns/7/ai-recommendation" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"features": {}}'
```

---

## Summary

- **System always works** - Uses heuristics if AutoML not configured
- **AutoML is optional** - Enhances predictions but not required
- **Fallback is automatic** - If AutoML fails, heuristics take over
- **No configuration needed** - Works out of the box with heuristics

The button should work regardless of AutoML configuration. If it's not working, it's likely a JavaScript/UI issue, not an AutoML configuration issue.







