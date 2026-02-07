# How to Generate Campaign Impact Data

## Overview

Campaign Impact data is automatically calculated from real activities in your system. The Impact module shows metrics based on:
- **Notifications sent** (Reach)
- **Event attendance** (Attendance Count)
- **Survey responses** (Survey Responses)

**Important:** A campaign must have actual activity (notifications sent, events with attendance, or survey responses) before impact data will appear.

---

## 📊 What Data Sources Feed Into Impact Metrics?

Based on `src/Services/ImpactService.php`, the Impact module calculates:

### 1. **Reach** (Total Reach)
- **Source:** `campaign_department_notification_logs` table
- **Query:** Count of notifications where `campaign_id = X` AND `status = "sent"`
- **How to Generate:**
  - Create campaign schedules in the Campaigns module
  - Send notifications through the campaign schedule system
  - Each successfully sent notification increases the "Reach" count

### 2. **Attendance Count** (Event Attendance)
- **Source:** `campaign_department_attendance` table
- **Query:** Count of attendance records linked to events where `linked_campaign_id = X`
- **How to Generate:**
  - Create events in the Events module
  - **Link the event to your campaign** (set `linked_campaign_id` when creating the event)
  - Have people check in/attend the event (via QR code or manual check-in)
  - Each attendance record increases the "Attendance Count"

### 3. **Survey Responses**
- **Source:** `campaign_department_survey_responses` table
- **Query:** Count of survey responses where:
  - Survey has `campaign_id = X` OR
  - Survey is linked to an event with `linked_campaign_id = X`
- **How to Generate:**
  - Create surveys in the Surveys module
  - **Link the survey to your campaign** (set `campaign_id` when creating the survey) OR
  - Link the survey to an event that is linked to your campaign
  - Have people submit survey responses
  - Each completed survey response increases the "Survey Responses" count

### 4. **Average Rating**
- **Source:** `campaign_department_survey_aggregated_results` table
- **Query:** Average rating from surveys linked to the campaign
- **How to Generate:**
  - Same as Survey Responses above
  - Surveys must have rating-type questions
  - System automatically calculates average from all responses

### 5. **Targeted Segments**
- **Source:** `campaign_department_campaign_audience` table
- **Query:** Count of distinct audience segments assigned to the campaign
- **How to Generate:**
  - Assign audience segments to your campaign in the Campaigns module
  - This is set during campaign creation or editing

### 6. **Engagement Rate & Response Rate**
- **Calculated:** Automatically computed from Reach, Attendance, and Survey Responses
- **Engagement Rate:** `(Attendance + Survey Responses) / Reach`
- **Response Rate:** `Survey Responses / Reach`

---

## 🚀 Step-by-Step Guide to Generate Impact Data

### Step 1: Create and Approve Your Campaign
1. Go to **Campaigns** module
2. Create a new campaign or use an existing one
3. Ensure campaign is in "approved", "ongoing", or "scheduled" status

### Step 2: Generate Reach Data (Notifications)

**Option A: Via Campaign Schedules**
1. In the Campaigns module, go to your campaign
2. Navigate to the "Schedules" section
3. Create a campaign schedule:
   - Select target audience segments
   - Set scheduled date/time
   - Choose notification channel (SMS, Email, etc.)
4. **Send the schedule** (click "Send" button)
5. Each successfully sent notification will be logged in `campaign_department_notification_logs`
6. This increases your campaign's "Reach" metric

**Option B: Via Notification System**
- Notifications can also be sent through the notification system if integrated
- Check `campaign_department_notification_logs` table to verify sent notifications

### Step 3: Generate Attendance Data (Events)

1. Go to **Events** module
2. Create a new event:
   - Fill in event details (title, date, venue, etc.)
   - **IMPORTANT:** Set "Linked Campaign" to your campaign ID
   - Save the event
3. When the event happens:
   - Have attendees check in using QR code OR
   - Manually record attendance in the Events module
4. Each attendance record linked to an event with `linked_campaign_id = your_campaign_id` will count toward "Attendance Count"

**Note:** Events must have `linked_campaign_id` set to your campaign for attendance to count!

### Step 4: Generate Survey Response Data

1. Go to **Surveys** module
2. Create a new survey:
   - Add questions (include rating questions for Average Rating metric)
   - **IMPORTANT:** Set "Campaign" to your campaign ID OR
   - Link the survey to an event that is linked to your campaign
   - Publish the survey
3. Have people respond to the survey:
   - Viewers can answer published surveys
   - Each completed response increases "Survey Responses"
   - Rating questions contribute to "Average Rating"

**Note:** Surveys must have `campaign_id` set OR be linked to an event with `linked_campaign_id = your_campaign_id`

### Step 5: View Impact Data

1. Go to **Impact** module
2. Select your campaign from any dropdown
3. Click the appropriate button:
   - "View Campaign Performance" - See all metrics
   - "View Key Metrics" - See detailed metrics overview
   - "Analyze Performance" - See performance analysis
   - "Download Data (CSV)" - Export data

---

## 🔍 Troubleshooting: Why Is There No Data?

### Issue: "No data loaded yet" or "Campaign found, but no engagement data available yet"

**Possible Causes:**

1. **No Notifications Sent**
   - Check `campaign_department_notification_logs` table
   - Query: `SELECT COUNT(*) FROM campaign_department_notification_logs WHERE campaign_id = X AND status = 'sent'`
   - If count is 0, you need to send notifications via campaign schedules

2. **No Events Linked to Campaign**
   - Check `campaign_department_events` table
   - Query: `SELECT * FROM campaign_department_events WHERE linked_campaign_id = X`
   - If no results, create events and set `linked_campaign_id`

3. **No Attendance Records**
   - Check `campaign_department_attendance` table
   - Query: `SELECT COUNT(*) FROM campaign_department_attendance a INNER JOIN campaign_department_events e ON e.id = a.event_id WHERE e.linked_campaign_id = X`
   - If count is 0, you need to record attendance for events linked to your campaign

4. **No Survey Responses**
   - Check `campaign_department_survey_responses` table
   - Query: `SELECT COUNT(*) FROM campaign_department_survey_responses sr INNER JOIN campaign_department_surveys s ON s.id = sr.survey_id WHERE s.campaign_id = X`
   - If count is 0, create surveys linked to your campaign and have people respond

5. **Campaign Not Linked Properly**
   - Events must have `linked_campaign_id = your_campaign_id`
   - Surveys must have `campaign_id = your_campaign_id` OR be linked to events with `linked_campaign_id = your_campaign_id`

---

## 📝 Quick Checklist

To see impact data for a campaign, ensure:

- [ ] Campaign exists and is approved/ongoing/scheduled
- [ ] At least one notification has been sent (for Reach)
- [ ] At least one event is linked to the campaign (`linked_campaign_id` set)
- [ ] At least one person has attended the event (attendance recorded)
- [ ] At least one survey is linked to the campaign (`campaign_id` set) OR linked to an event that's linked to the campaign
- [ ] At least one person has responded to the survey

---

## 💡 Example Workflow

1. **Create Campaign:** "Fire Safety Awareness 2024"
2. **Create Schedule:** Schedule notifications to be sent to target audience
3. **Send Notifications:** Click "Send" on the schedule → Reach = 100
4. **Create Event:** "Fire Safety Seminar" → Link to campaign
5. **Record Attendance:** 50 people attend → Attendance Count = 50
6. **Create Survey:** "Fire Safety Feedback" → Link to campaign
7. **Collect Responses:** 30 people respond → Survey Responses = 30
8. **View Impact:** Go to Impact module → Select campaign → See all metrics!

---

## 🔗 Database Tables Reference

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `campaign_department_notification_logs` | Notification delivery logs | `campaign_id`, `status` |
| `campaign_department_events` | Events | `linked_campaign_id` |
| `campaign_department_attendance` | Event attendance | `event_id` (via events table) |
| `campaign_department_surveys` | Surveys | `campaign_id`, `event_id` |
| `campaign_department_survey_responses` | Survey responses | `survey_id` (via surveys table) |
| `campaign_department_campaign_audience` | Campaign-segment links | `campaign_id`, `segment_id` |

---

## ⚠️ Important Notes

1. **Data is Real-Time:** Impact metrics are calculated in real-time from database records
2. **No Test Data:** The system doesn't generate fake data - you need actual activity
3. **Linking is Critical:** Events and surveys must be properly linked to campaigns
4. **Status Matters:** Notifications must have `status = "sent"` to count toward Reach
5. **Viewer Role:** Viewers can see impact data but cannot create campaigns, events, or surveys

---

## 🎯 Summary

**To see campaign impact data:**
1. Send notifications for your campaign (via schedules)
2. Create events and link them to your campaign
3. Record attendance at those events
4. Create surveys and link them to your campaign (or to events linked to your campaign)
5. Collect survey responses

Once you have at least one of these activities (notifications sent, attendance recorded, or survey responses), the Impact module will display the data!

