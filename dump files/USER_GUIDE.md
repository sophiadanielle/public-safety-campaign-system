# Public Safety Campaign Management System - User Guide

## Table of Contents
1. [System Overview](#system-overview)
2. [Getting Started](#getting-started)
3. [Dashboard](#dashboard)
4. [Campaigns Module](#campaigns-module)
5. [Content Module](#content-module)
6. [Segments Module](#segments-module)
7. [Events Module](#events-module)
8. [Surveys Module](#surveys-module)
9. [Impact Module](#impact-module)
10. [Partners Module](#partners-module)
11. [User Roles & Permissions](#user-roles--permissions)
12. [Best Practices](#best-practices)
13. [Troubleshooting](#troubleshooting)

---

## System Overview

The Public Safety Campaign Management System (PSCM) is a comprehensive web-based platform designed for Quezon City barangays to plan, manage, and track public safety awareness campaigns. The system helps coordinate campaigns with schools, NGOs, and other partner organizations.

### Key Features
- **Campaign Planning**: Create and schedule public safety campaigns
- **Content Management**: Organize and reuse campaign materials
- **Audience Segmentation**: Target specific groups (residents, students, etc.)
- **Event Coordination**: Schedule and manage campaign events
- **Survey Management**: Create surveys to measure campaign impact
- **Partner Collaboration**: Manage partnerships with schools and NGOs
- **Impact Tracking**: Monitor campaign reach and effectiveness
- **AI-Powered Scheduling**: Get AI recommendations for optimal posting times

---

## Getting Started

### Accessing the System

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start **Apache** (click "Start")
   - Start **MySQL** (click "Start")
   - Both should show green "Running" status

2. **Open in Browser**
   - Navigate to: `http://localhost/public-safety-campaign-system/index.php`
   - Or directly to: `http://localhost/public-safety-campaign-system/public/dashboard.php` (if already logged in)

### Login Process

1. **Enter Credentials**
   - Email: Your registered email address
   - Password: Your account password

2. **Default Test Accounts**
   - **Admin**: `admin@barangay1.qc.gov.ph` / `password123`
   - **Staff**: `staff@barangay1.qc.gov.ph` / `password123`
   - **School Partner**: `school@example.com` / `password123`
   - **NGO Partner**: `ngo@example.com` / `password123`

3. **After Login**
   - You'll be redirected to the Dashboard
   - Your role determines which features you can access

### Navigation

- **Top Header**: Contains search bar, notifications, messages, and user profile
- **Left Sidebar**: Main navigation menu with all modules
- **Logo**: Click the logo in the upper left to return to Dashboard
- **Main Content Area**: Displays the current module's content

---

## Dashboard

The Dashboard provides an overview of your campaigns and system activity.

### Dashboard Widgets

1. **Active Campaigns**: Number of currently active campaigns
2. **Total Content**: Count of all content items
3. **Upcoming Events**: Events scheduled in the next 30 days
4. **Survey Responses**: Total survey responses received
5. **Partner Organizations**: Number of registered partners
6. **Campaign Reach**: Total audience reached across all campaigns

### Using the Dashboard

- Click any widget to navigate to the related module
- View quick statistics at a glance
- Access recent activity and notifications

---

## Campaigns Module

The Campaigns module is the core of the system, where you plan and manage public safety campaigns.

### Creating a New Campaign

1. **Navigate to Campaigns**
   - Click "Campaigns" in the left sidebar
   - Click "Plan New Campaign" button

2. **Fill Campaign Details**
   - **Campaign Name**: Enter a descriptive name (e.g., "Fire Safety Awareness 2024")
   - **Category**: Select from dropdown (Fire Safety, Earthquake Preparedness, etc.)
   - **Description**: Provide detailed campaign description
   - **Objective**: State the campaign's main goal
   - **Target Audience**: Select one or more audience segments
   - **Start Date**: Choose when the campaign begins
   - **End Date**: Choose when the campaign ends
   - **Status**: Set initial status (Draft, Active, Completed, etc.)

3. **Assign Resources**
   - **Assigned Staff**: Select staff members responsible for the campaign
   - **Materials**: Link existing content items (posters, videos, etc.)
   - **Partners**: Associate partner organizations

4. **Schedule Campaign**
   - **Manual Scheduling**: Enter preferred dates and times
   - **AI Scheduling** (Optional):
     - Click "Request AI Recommendation"
     - System analyzes audience engagement patterns
     - Review recommended posting times
     - Accept or override recommendations

5. **Save Campaign**
   - Click "Create Campaign" to save
   - Campaign appears in "All Campaigns" list

### Viewing Campaigns

1. **All Campaigns Tab**
   - Lists all campaigns in a table
   - Shows: Name, Category, Status, Dates, Actions
   - Use filters to find specific campaigns

2. **Calendar View**
   - Visual calendar showing all scheduled campaigns
   - Click any campaign to view details
   - See conflicts and overlaps

3. **Campaign Details**
   - Click "View" button to see full campaign information
   - View linked content, assigned staff, and partners
   - See engagement metrics (if campaign is active)

### Editing Campaigns

1. Click "Edit" button next to a campaign
2. Modify any fields
3. Click "Update Campaign" to save changes

### Managing Campaign Status

- **Activate**: Change status from Draft to Active
- **Deactivate**: Pause an active campaign
- **Complete**: Mark finished campaigns as Completed
- **Archive**: Move old campaigns to archive

### AI Scheduling Feature

**How It Works:**
1. System analyzes historical engagement data
2. Considers audience segment preferences
3. Checks for conflicts with other campaigns/events
4. Recommends optimal posting times

**Using AI Recommendations:**
1. Create or edit a campaign
2. Click "Request AI Recommendation"
3. Wait for analysis (usually 2-5 seconds)
4. Review recommended day and time
5. Check confidence score (higher = more reliable)
6. Accept recommendation or manually override

**Note**: AI recommendations are advisory only. You always have final control.

---

## Content Module

The Content module manages all campaign materials (posters, videos, documents, etc.).

### Creating Content

1. **Navigate to Content**
   - Click "Content" in the sidebar
   - Click "Create New Content" button

2. **Fill Content Details**
   - **Title**: Descriptive name for the content
   - **Type**: Select (Poster, Video, Document, Social Media Post, etc.)
   - **Description**: Explain what the content is about
   - **File Upload**: Upload the actual file (if applicable)
   - **Status**: Set status (Draft, Ready, Published, Archived)
   - **Tags**: Add keywords for easy searching

3. **Link to Campaigns**
   - Select which campaigns use this content
   - Content can be linked to multiple campaigns

4. **Save Content**
   - Click "Create Content" to save
   - Content appears in the content library

### Managing Content

**View Content:**
- Browse all content in card view
- Use filters by type, status, or campaign
- Search by title or description

**Edit Content:**
- Click "Edit" on any content card
- Modify details and re-upload files if needed
- Update linked campaigns

**Content Status Workflow:**
1. **Draft**: Initial creation, not ready for use
2. **Ready**: Content is prepared and approved
3. **Published**: Currently in use in active campaigns
4. **Archived**: No longer in use

**Delete Content:**
- Click "Delete" button
- Confirm deletion
- Note: Content linked to active campaigns cannot be deleted

### Content Reusability

- Content items can be reused across multiple campaigns
- Changes to content affect all linked campaigns
- Archive old content instead of deleting to maintain history

---

## Segments Module

Segments define your target audiences (e.g., "Residents 18-35", "Elementary Students", "Senior Citizens").

### Creating a Segment

1. **Navigate to Segments**
   - Click "Segments" in the sidebar
   - Click "Create New Segment" button

2. **Define Segment**
   - **Name**: Descriptive name (e.g., "Young Adults 18-30")
   - **Description**: Explain who this segment includes
   - **Demographics**: Age range, location, etc.
   - **Characteristics**: Interests, behaviors, preferences

3. **Save Segment**
   - Click "Create Segment"
   - Segment is now available for campaign assignment

### Using Segments

- Assign segments to campaigns during campaign creation
- Multiple segments can be assigned to one campaign
- System uses segments for AI scheduling recommendations
- Track which segments respond best to different campaign types

### Managing Segments

- **View**: See all segments in a table
- **Edit**: Modify segment details
- **Delete**: Remove unused segments (only if not linked to campaigns)

---

## Events Module

Events are specific activities or gatherings related to campaigns (workshops, seminars, community meetings).

### Creating an Event

1. **Navigate to Events**
   - Click "Events" in the sidebar
   - Click "Create New Event" button

2. **Event Details**
   - **Event Name**: Descriptive title
   - **Description**: What the event is about
   - **Event Type**: Workshop, Seminar, Meeting, etc.
   - **Date & Time**: When the event occurs
   - **Location**: Venue address
   - **Capacity**: Maximum attendees
   - **Related Campaign**: Link to a campaign (optional)

3. **Save Event**
   - Click "Create Event"
   - Event appears in calendar and events list

### Viewing Events

- **List View**: Table of all events with filters
- **Calendar View**: Visual calendar showing event dates
- Click any event to see full details

### Managing Events

- **Edit**: Modify event details
- **Delete**: Remove events (only if not linked to campaigns)
- **Link to Campaigns**: Associate events with campaigns for better coordination

---

## Surveys Module

Surveys help measure campaign impact and gather feedback from the community.

### Creating a Survey

1. **Navigate to Surveys**
   - Click "Surveys" in the sidebar
   - Click "Create New Survey" button

2. **Survey Details**
   - **Title**: Survey name
   - **Description**: Explain the survey's purpose
   - **Related Campaign**: Link to a campaign
   - **Start Date**: When survey becomes available
   - **End Date**: When survey closes

3. **Add Questions**
   - Click "Add Question"
   - Enter question text
   - Select question type:
     - **Multiple Choice**: Select one option
     - **Checkbox**: Select multiple options
     - **Text**: Open-ended response
     - **Rating**: Scale (1-5, 1-10, etc.)
   - Add answer options (for multiple choice/checkbox)
   - Mark questions as required or optional

4. **Save Survey**
   - Click "Create Survey"
   - Survey status: Draft

### Survey Workflow

1. **Draft**: Creating and editing questions
2. **Published**: Survey is live and accepting responses
3. **Closed**: Survey no longer accepts responses
4. **Archived**: Survey is completed and archived

### Managing Surveys

**Publish Survey:**
- Change status from Draft to Published
- Survey becomes accessible to respondents

**View Results:**
- Click "Results" button
- See response statistics
- View individual responses
- Export data for analysis

**Close Survey:**
- Change status to Closed
- No new responses accepted
- Results remain accessible

### Survey Responses

- Responses are automatically stored
- View aggregated statistics
- Export data for external analysis
- Responses contribute to Impact module metrics

---

## Impact Module

The Impact module tracks and visualizes campaign effectiveness.

### Viewing Impact Data

1. **Navigate to Impact**
   - Click "Impact" in the sidebar
   - Select a campaign from the dropdown

2. **Impact Metrics**
   - **Reach**: Number of people reached
   - **Engagement**: Interactions (views, clicks, shares)
   - **Attendance**: Event attendance numbers
   - **Survey Responses**: Number of survey completions
   - **Conversion Rate**: Percentage of engaged audience

3. **Charts and Visualizations**
   - Line charts showing trends over time
   - Bar charts comparing metrics
   - Pie charts showing audience breakdown

### Impact Data Sources

- **Campaign Engagement**: From campaign activities
- **Event Attendance**: From registered events
- **Survey Responses**: From completed surveys
- **Content Views**: From content interactions

### Using Impact Data

- Identify most effective campaign types
- Understand audience preferences
- Make data-driven decisions for future campaigns
- Report to stakeholders and partners

---

## Partners Module

The Partners module manages relationships with schools, NGOs, and other organizations.

### Adding a Partner

1. **Navigate to Partners**
   - Click "Partners" in the sidebar
   - Click "Add Partner" button

2. **Partner Information**
   - **Organization Name**: Full name
   - **Type**: School, NGO, Government Agency, Private Organization
   - **Contact Person**: Primary contact name
   - **Email**: Contact email
   - **Phone**: Contact number
   - **Address**: Organization address
   - **Description**: Notes about the partnership

3. **Save Partner**
   - Click "Add Partner"
   - Partner is added to the system

### Managing Partners

**View Partners:**
- See all partners in a table
- Filter by type or search by name

**Edit Partner:**
- Click "Edit" to update information
- Modify contact details as needed

**Link to Campaigns:**
- Assign partners to campaigns during campaign creation
- Track which partners are involved in which campaigns

**Delete Partner:**
- Remove partners that are no longer active
- Only if not linked to active campaigns

---

## User Roles & Permissions

### Barangay Administrator

**Full Access:**
- Create, edit, delete all campaigns
- Manage all content, segments, events, surveys
- Add and manage partners
- View all impact data
- Manage user accounts (if applicable)

### Barangay Staff

**Operational Access:**
- Create and manage campaigns
- Create and edit content
- Manage segments and events
- Create surveys
- View impact data
- Cannot delete critical data

### School Partner / NGO Partner

**Viewer Access:**
- View campaigns (read-only)
- View content library
- View events calendar
- View impact reports
- Cannot create or edit content
- Cannot access operational modules (Partners, etc.)

### Access Control

- System automatically enforces role-based permissions
- Buttons and features are hidden based on your role
- Attempts to access restricted features are blocked

---

## Best Practices

### Campaign Planning

1. **Plan Ahead**: Create campaigns 2-4 weeks before launch
2. **Use Segments**: Define clear target audiences
3. **Prepare Content**: Create content before campaign launch
4. **Coordinate Events**: Schedule events in advance
5. **Link Resources**: Connect content, segments, and partners to campaigns

### Content Management

1. **Reuse Content**: Don't recreate similar materials
2. **Organize with Tags**: Use descriptive tags for easy searching
3. **Version Control**: Archive old versions instead of deleting
4. **File Naming**: Use clear, descriptive file names

### Survey Creation

1. **Keep It Short**: 5-10 questions maximum
2. **Clear Questions**: Use simple, direct language
3. **Mix Question Types**: Combine multiple choice and open-ended
4. **Test First**: Review survey before publishing

### Partner Management

1. **Keep Information Updated**: Regularly update contact details
2. **Document Relationships**: Use description field for notes
3. **Link to Campaigns**: Always associate partners with relevant campaigns

### Impact Tracking

1. **Regular Monitoring**: Check impact data weekly during active campaigns
2. **Compare Campaigns**: Use data to identify best practices
3. **Share Results**: Report findings to stakeholders and partners

---

## Troubleshooting

### Login Issues

**Problem**: Cannot log in
- **Solution**: Verify email and password are correct
- **Solution**: Check that MySQL is running in XAMPP
- **Solution**: Clear browser cache and cookies

**Problem**: "Token expired" error
- **Solution**: Log out and log back in
- **Solution**: Clear browser localStorage

### Campaign Issues

**Problem**: Cannot create campaign
- **Solution**: Verify you have Staff or Admin role
- **Solution**: Check that required fields are filled
- **Solution**: Ensure dates are valid (end date after start date)

**Problem**: AI scheduling not working
- **Solution**: AI uses fallback heuristics if AutoML is unavailable
- **Solution**: Check browser console for errors
- **Solution**: Try manual scheduling as alternative

### Content Issues

**Problem**: Cannot upload file
- **Solution**: Check file size (should be under 10MB)
- **Solution**: Verify file type is allowed (images, PDFs, videos)
- **Solution**: Check browser console for errors

**Problem**: Content not appearing in campaign
- **Solution**: Ensure content is linked to the campaign
- **Solution**: Check content status (should be "Ready" or "Published")

### General Issues

**Problem**: Page not loading
- **Solution**: Verify Apache is running in XAMPP
- **Solution**: Check URL is correct
- **Solution**: Clear browser cache

**Problem**: Buttons not working
- **Solution**: Check browser console for JavaScript errors
- **Solution**: Verify you're logged in
- **Solution**: Check your role has permission for the action

**Problem**: Data not saving
- **Solution**: Check all required fields are filled
- **Solution**: Verify database connection (check XAMPP MySQL status)
- **Solution**: Check browser console for API errors

### Getting Help

1. **Check Console**: Open browser Developer Tools (F12) to see errors
2. **Check Network Tab**: Verify API requests are successful
3. **Verify Services**: Ensure XAMPP Apache and MySQL are running
4. **Review Logs**: Check XAMPP error logs if issues persist

---

## Quick Reference

### Common Workflows

**Creating a Complete Campaign:**
1. Create content items (posters, videos)
2. Define audience segments
3. Add partner organizations
4. Create the campaign
5. Link content, segments, and partners
6. Schedule using AI or manually
7. Activate campaign
8. Monitor impact

**Measuring Campaign Success:**
1. Create survey before campaign launch
2. Link survey to campaign
3. Publish survey during campaign
4. View results in Surveys module
5. Check Impact module for metrics
6. Export data for reporting

**Coordinating with Partners:**
1. Add partner organizations
2. Create campaign
3. Assign partners to campaign
4. Schedule events
5. Share campaign details with partners
6. Track partner engagement in Impact module

---

## System URLs (Localhost)

- **Login**: `http://localhost/public-safety-campaign-system/index.php`
- **Dashboard**: `http://localhost/public-safety-campaign-system/public/dashboard.php`
- **Campaigns**: `http://localhost/public-safety-campaign-system/public/campaigns.php`
- **Content**: `http://localhost/public-safety-campaign-system/public/content.php`
- **Segments**: `http://localhost/public-safety-campaign-system/public/segments.php`
- **Events**: `http://localhost/public-safety-campaign-system/public/events.php`
- **Surveys**: `http://localhost/public-safety-campaign-system/public/surveys.php`
- **Impact**: `http://localhost/public-safety-campaign-system/public/impact.php`
- **Partners**: `http://localhost/public-safety-campaign-system/public/partners.php`

---

## Additional Resources

- **System Overview**: See `README_SYSTEM_OVERVIEW.md`
- **Setup Instructions**: See `HOW_TO_RUN.md`
- **API Documentation**: Check API endpoints in `src/Routes/`
- **Database Schema**: See `migrations/` folder for SQL files

---

**Last Updated**: January 2025
**System Version**: 1.0
**For Support**: Contact your system administrator

