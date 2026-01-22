# Google AutoML Integration Guide

## Overview

The Campaign module includes Google AutoML integration for AI-powered deployment optimization. The system intelligently falls back to heuristic predictions if Google AutoML is not configured or unavailable.

## How It Works

### 1. **Google AutoML (Primary Method)**
   - Uses Google AutoML API to predict optimal campaign deployment times
   - Requires environment variables: `GOOGLE_AUTOML_ENDPOINT` and `GOOGLE_AUTOML_API_KEY`
   - Analyzes campaign features, historical engagement, and audience patterns

### 2. **Heuristic Fallback (Automatic)**
   - Automatically used if Google AutoML is not configured or fails
   - Uses historical data from similar campaigns
   - Analyzes engagement patterns, attendance records, and optimal timing
   - Provides reliable predictions even without AutoML

## Configuration

### To Enable Google AutoML:

1. **Set Environment Variables:**
   ```bash
   GOOGLE_AUTOML_ENDPOINT=https://automl.googleapis.com/v1/projects/YOUR_PROJECT/locations/YOUR_LOCATION/models/YOUR_MODEL:predict
   GOOGLE_AUTOML_API_KEY=your_api_key_here
   ```

2. **For XAMPP (Windows):**
   - Add to `php.ini` or set via `.htaccess`:
   ```apache
   SetEnv GOOGLE_AUTOML_ENDPOINT "https://automl.googleapis.com/v1/projects/YOUR_PROJECT/locations/YOUR_LOCATION/models/YOUR_MODEL:predict"
   SetEnv GOOGLE_AUTOML_API_KEY "your_api_key_here"
   ```

3. **For Production:**
   - Use your server's environment variable configuration
   - Or create a `.env` file (if using a library that supports it)

### Current Status

The system will automatically detect if Google AutoML is configured:
- ✅ **Configured**: Uses Google AutoML for predictions
- ⚠️ **Not Configured**: Uses heuristic fallback (still provides accurate predictions)

## Testing

### Using the Web Interface:

1. Navigate to the Campaigns page
2. Scroll to the "AI-Powered Deployment Optimization" section
3. Select a campaign from the dropdown
4. Optionally enter an Audience Segment ID
5. Click "🔮 Get Prediction"
6. Review the results:
   - **Model Source** shows which method was used
   - **Confidence Score** indicates prediction reliability
   - **Suggested Date & Time** is the recommended deployment time

### Using the Test Script:

```bash
php test_automl.php [campaign_id]
```

This script will:
- Check if Google AutoML is configured
- Run a prediction test
- Display detailed results including which method was used

## Features

### Enhanced Error Handling
- Automatic fallback to heuristics if AutoML fails
- Clear error messages and logging
- Status indicators in the UI

### Improved Logging
- Detailed logs for debugging
- Tracks which prediction method is used
- Logs configuration status

### User-Friendly UI
- Clear indication of which model is being used
- Visual badges for model status (✓ Active, ⚠ Not Configured, ⚠ Fallback)
- Helpful notices when AutoML is not configured
- Detailed recommendation explanations

## API Endpoint

**POST** `/api/v1/campaigns/{id}/ai-recommendation`

**Request Body:**
```json
{
  "features": {
    "audience_segment_id": 1,
    "campaign_category": "safety"
  }
}
```

**Response:**
```json
{
  "prediction_id": 123,
  "prediction": {
    "recommended_day": 3,
    "recommended_time": "14:00",
    "suggested_datetime": "2025-01-22 14:00:00",
    "confidence_score": 0.85,
    "model_source": "google_automl",
    "automl_configured": true
  },
  "message": "Google AutoML recommendation generated successfully"
}
```

## Troubleshooting

### AutoML Not Working?

1. **Check Environment Variables:**
   ```bash
   php -r "echo getenv('GOOGLE_AUTOML_ENDPOINT') ? 'SET' : 'NOT SET';"
   php -r "echo getenv('GOOGLE_AUTOML_API_KEY') ? 'SET' : 'NOT SET';"
   ```

2. **Check Server Logs:**
   - Look for `AutoMLService:` entries in PHP error logs
   - Check for connection errors or API failures

3. **Verify API Credentials:**
   - Ensure your Google AutoML endpoint URL is correct
   - Verify your API key has proper permissions

4. **Test Fallback:**
   - Even without AutoML, the heuristic fallback should work
   - Check that campaigns exist in the database
   - Verify historical data is available

### Common Issues

- **"Google AutoML not configured"**: Set environment variables (see Configuration section)
- **"Connection failed"**: Check network connectivity and endpoint URL
- **"HTTP 401"**: Verify API key is correct
- **"HTTP 404"**: Check endpoint URL format

## Notes

- The heuristic fallback provides reliable predictions even without AutoML
- Both methods use real-time historical data from similar campaigns
- Predictions are saved to the database for tracking
- The system automatically chooses the best available method









