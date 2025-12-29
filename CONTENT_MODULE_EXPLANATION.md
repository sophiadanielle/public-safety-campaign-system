# Content Module: Search & Filter - How It Works

## Overview
The **Search & Filter** section is the central hub for finding and managing content in the Content Repository module. It connects to all other features in the module.

---

## How Search & Filter Relates to Other Features

### 1. **Content Library** (Direct Connection)
- **Purpose**: Displays filtered content results
- **Relationship**: Search & Filter directly controls what appears in Content Library
- **Flow**: 
  ```
  User sets filters → Search & Filter → API call → Content Library displays results
  ```
- **What you can do**: View, approve, reject, attach to campaigns, see details

### 2. **Content Templates** (Indirect Connection)
- **Purpose**: Shows approved content that can be reused as templates
- **Relationship**: Templates section automatically filters to show ONLY approved content
- **Flow**:
  ```
  Content Library → Approve content → Content Templates (auto-updates)
  ```
- **What you can do**: Use approved templates to quickly create new content

### 3. **Media Gallery** (Indirect Connection)
- **Purpose**: Visual gallery of media files (images/videos)
- **Relationship**: Media Gallery shows approved media files from filtered content
- **Flow**:
  ```
  Content Library → Filter by type (image/video) → Media Gallery displays visually
  ```
- **What you can do**: Browse media files, view full-size, attach to campaigns

### 4. **Upload Content** (Creation Point)
- **Purpose**: Create new content items
- **Relationship**: New uploads appear in Search & Filter results after creation
- **Flow**:
  ```
  Upload Content → Content saved as Draft → Appears in Search & Filter (if status filter includes "draft")
  ```

---

## Complete Content Workflow

```
┌─────────────────┐
│ Upload Content  │ ← Create new content
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Search & Filter │ ← Find and filter content
└────────┬────────┘
         │
         ├───► Content Library (View/Manage)
         │         ├─ Approve/Reject
         │         ├─ Attach to Campaigns
         │         └─ View Details
         │
         ├───► Content Templates (Approved only)
         │         └─ Use as Template
         │
         └───► Media Gallery (Media files only)
                   └─ Visual Browse
```

---

## Search & Filter Features Explained

### Search Field
- **Searches**: Title and description text
- **Auto-search**: Searches automatically as you type (500ms delay)
- **Examples**: "fire safety", "evacuation", "health tips"

### Content Type Filter
- **Options**: Poster, Video, Guideline, Infographic, Image, File
- **Use case**: Find specific types of content
- **Example**: Select "Video" to see only video content

### Hazard Category Filter
- **Options**: Fire, Flood, Earthquake, Typhoon, Health, Emergency
- **Use case**: Find content related to specific hazards
- **Example**: Select "Fire" to see all fire safety content

### Intended Audience Filter
- **Options**: Free text (households, youth, senior citizens, schools, etc.)
- **Use case**: Find content targeting specific audiences
- **Example**: Type "youth" to find content for young people

### Source Filter
- **Options**: Inspection-based, Training-based, Barangay-created
- **Use case**: Find content by origin
- **Example**: Select "Barangay-created" for locally made content

### Approval Status Filter
- **Options**: Draft, Pending, Approved, Rejected
- **Use case**: Find content by approval state
- **Example**: Select "Pending" to review content awaiting approval

### "Only Approved" Checkbox
- **Purpose**: Quick filter to show only approved content
- **Use case**: When you need content ready for use
- **Note**: Only approved content can be used as templates or attached to campaigns

---

## User Scenarios

### Scenario 1: Finding Fire Safety Posters
1. **Search**: Type "fire safety"
2. **Content Type**: Select "Poster"
3. **Hazard Category**: Select "Fire"
4. **Status**: Select "Approved"
5. **Result**: See all approved fire safety posters in Content Library

### Scenario 2: Reviewing Pending Content
1. **Status**: Select "Pending"
2. **Result**: See all content awaiting approval
3. **Action**: Review and approve/reject items

### Scenario 3: Creating Content from Template
1. **Go to Templates**: Click "Content Templates" in sidebar
2. **Browse**: See approved templates
3. **Use Template**: Click "Use Template" on desired item
4. **Result**: Upload form auto-fills with template data

### Scenario 4: Finding Media for Campaign
1. **Media Gallery**: Click "Media Gallery" in sidebar
2. **Filter**: Use dropdown to filter by image/video
3. **Browse**: Visual gallery of media files
4. **Attach**: Click "Attach to Campaign" on desired media

---

## Key Benefits

1. **Centralized Search**: One place to find all content
2. **Flexible Filtering**: Combine multiple filters for precise results
3. **Real-time Updates**: See results as you type
4. **Visual Feedback**: Active filters shown as chips
5. **Quick Actions**: One-click filters for common searches
6. **Integration**: Results feed into Templates and Media Gallery

---

## Tips for Users

- **Start broad**: Begin with search term, then narrow with filters
- **Use quick filters**: Click quick filter buttons for common searches
- **Check active filters**: See what's filtered via filter chips
- **Combine filters**: Use multiple filters together for best results
- **Clear when needed**: Use "Clear All" to reset and start fresh



