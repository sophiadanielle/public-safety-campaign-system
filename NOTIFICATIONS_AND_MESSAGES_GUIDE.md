# Notifications & Messages System Guide

## Overview

Both the **Notifications** and **Messages** systems are fully functional and integrated into the Public Safety Campaign System. They serve different purposes:

- **Notifications**: System alerts and updates (one-way communication from system to user)
- **Messages**: User-to-user conversations (two-way communication between users)

---

## 🔔 NOTIFICATIONS SYSTEM

### How It Works

#### 1. **Database Structure**
- **Table**: `notifications`
- **Fields**:
  - `user_id` (NULL = system-wide notification)
  - `type` (campaign, event, content, system, alert, reminder)
  - `title`, `message`, `link_url`, `icon`
  - `is_read`, `read_at`, `created_at`

#### 2. **Backend API Endpoints**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/notifications` | Get user notifications with unread count |
| `PUT` | `/api/v1/notifications/{id}/read` | Mark notification as read |
| `PUT` | `/api/v1/notifications/read-all` | Mark all notifications as read |

#### 3. **Automatic Notification Generation**

Notifications are automatically created when:

- **Campaign Created**: When a user creates a campaign
  ```php
  NotificationController::create($pdo, $userId, 'campaign', 'Campaign Created', ...)
  ```

- **Event Scheduled**: When an event is created
  ```php
  NotificationController::create($pdo, $userId, 'event', 'Event Created', ...)
  ```

- **Content Uploaded**: When content is uploaded
  ```php
  NotificationController::create($pdo, $userId, 'content', 'Content Uploaded', ...)
  ```

- **Content Approved/Rejected**: When content approval status changes
  ```php
  NotificationController::create($pdo, $userId, 'content', 'Content Approved', ...)
  ```

- **Dashboard Alerts**: 
  - Campaigns missing schedules
  - Events happening in next 72 hours

#### 4. **Frontend Behavior**

- **Badge Counter**: Shows unread count in bell icon (updates every 30 seconds)
- **Modal Display**: Click bell icon → Shows list of notifications
- **Click to Read**: Clicking a notification marks it as read
- **Mark All Read**: Button to mark all notifications as read
- **Visual Indicators**: 
  - Unread: Blue background with left border
  - Read: Normal appearance

#### 5. **Notification Types & Icons**

| Type | Icon | Background Color |
|------|------|------------------|
| Campaign | `fa-bullhorn` | Purple (#8b5cf6) |
| Event | `fa-calendar` | Cyan (#06b6d4) |
| Content | `fa-file-alt` | Indigo (#6366f1) |
| System | `fa-info-circle` | Gray (#64748b) |
| Alert | `fa-exclamation-triangle` | Orange (#f97316) |
| Reminder | `fa-clock` | Teal (#14b8a6) |

---

## 💬 MESSAGES SYSTEM

### How It Works

#### 1. **Database Structure**

- **Table**: `conversations`
  - `participant1_id`, `participant2_id`
  - `last_message_at`
  
- **Table**: `messages`
  - `conversation_id`, `sender_id`, `recipient_id`
  - `message_text`
  - `context_type` (campaign, event, content, general)
  - `context_id` (links to campaign/event/content)
  - `is_read`, `read_at`, `created_at`

#### 2. **Backend API Endpoints**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/messages/conversations` | Get user's conversations list |
| `GET` | `/api/v1/messages/conversations/{id}` | Get messages in a conversation |
| `POST` | `/api/v1/messages/send` | Send a new message |
| `PUT` | `/api/v1/messages/conversations/{id}/read` | Mark conversation as read |

#### 3. **Context-Aware Messaging**

Messages can be linked to system modules:

```javascript
// Send message linked to a campaign
{
  "recipient_id": 2,
  "message": "Can you review this campaign?",
  "context_type": "campaign",
  "context_id": 5
}
```

This allows users to discuss specific campaigns, events, or content items.

#### 4. **Frontend Behavior**

- **Badge Counter**: Shows unread message count in envelope icon (updates every 30 seconds)
- **Conversation List**: Click envelope → Shows list of conversations
  - Shows last message preview
  - Shows timestamp
  - Shows unread indicator (red dot)
- **Open Conversation**: Click conversation → Opens message content modal
- **Message Display**: Shows sent/received messages with timestamps
- **Auto-Mark Read**: Messages marked as read when conversation is opened

#### 5. **Conversation Flow**

1. User A sends message to User B
2. System creates conversation (if doesn't exist)
3. Message stored in `messages` table
4. User B sees unread count in badge
5. User B clicks envelope → Sees conversation in list
6. User B clicks conversation → Opens chat modal
7. Messages automatically marked as read

---

## 🔄 AUTO-REFRESH MECHANISM

Both systems refresh automatically:

- **Badge Counters**: Update every 30 seconds
- **On Modal Open**: Data loads when modal is opened
- **After Actions**: Badge updates after marking as read

---

## 🎨 UI FEATURES

### Notifications Modal
- Solid white background (no glassmorphism)
- Clear borders and shadows
- Color-coded icons by type
- Unread highlight (blue background)
- Clickable links to relevant pages

### Messages Modal
- Solid white background (no glassmorphism)
- Conversation list with avatars
- Unread indicators
- Facebook-style chat interface
- Message bubbles (sent/received)

---

## 📊 DATA FLOW

### Notifications Flow:
```
System Event → NotificationController::create() → Database → 
Frontend Polls API → Badge Updates → User Clicks → Modal Shows → 
User Clicks Notification → Marked as Read
```

### Messages Flow:
```
User Sends Message → MessageController::sendMessage() → Database → 
Recipient Badge Updates → Recipient Opens Conversation → 
Messages Loaded → Auto-Marked as Read
```

---

## 🔧 INTEGRATION POINTS

### Notifications are created from:
- `CampaignController::store()` - Campaign creation
- `EventController::store()` - Event creation
- `ContentController::store()` - Content upload
- `ContentController::updateApproval()` - Content approval
- `DashboardController::getAlerts()` - Dashboard alerts

### Messages can be linked to:
- Campaigns (`context_type: 'campaign'`)
- Events (`context_type: 'event'`)
- Content (`context_type: 'content'`)
- General chat (`context_type: 'general'`)

---

## ✅ VERIFICATION CHECKLIST

- [x] Database tables created (`notifications`, `conversations`, `messages`)
- [x] Backend controllers implemented
- [x] API routes registered in `index.php`
- [x] Frontend JavaScript functions implemented
- [x] Badge counters working
- [x] Modals loading data from API
- [x] Auto-refresh every 30 seconds
- [x] Mark as read functionality
- [x] Visual indicators for unread items
- [x] Integration with Campaigns, Events, Content modules

---

## 🚀 USAGE EXAMPLES

### Creating a Notification (Backend)
```php
NotificationController::create(
    $pdo,
    $userId,
    'campaign',
    'Campaign Created',
    "Campaign 'Fire Safety Awareness' has been created.",
    '/public/campaigns.php#list-section',
    'fas fa-bullhorn'
);
```

### Sending a Message (Frontend)
```javascript
fetch('/api/v1/messages/send', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        recipient_id: 2,
        message: 'Can you review this campaign?',
        context_type: 'campaign',
        context_id: 5
    })
});
```

---

## 📝 NOTES

- Both systems require JWT authentication
- Notifications can be system-wide (`user_id = NULL`)
- Messages are always user-to-user
- Badge counters update automatically every 30 seconds
- No glassmorphism - solid white backgrounds for clarity
- All timestamps show relative time ("5 minutes ago")






