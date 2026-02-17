# Custom Modal Migration Guide

## Overview
This guide explains how to replace default browser modals (alert, confirm, prompt) with custom teal-themed modals across the system.

## Files Requiring Updates

Based on grep analysis, the following files have default modals:
- **campaigns.php** - 95 matches (highest priority)
- **content.php** - 31 matches
- **surveys.php** - 25 matches
- **events.php** - 19 matches
- **campaigns_js_functions.js** - 14 matches
- **partners.php** - 9 matches
- **settings.php** - 2 matches

## Step 1: Add Custom Modal Script

Add this line in the `<head>` section of each PHP file (after other CSS/JS includes):

```php
<script src="<?php echo htmlspecialchars($publicPath . '/js/custom-modals.js'); ?>"></script>
```

For standalone JS files, ensure they're loaded after custom-modals.js in the HTML file that includes them.

## Step 2: Replace Modal Calls

### Replace alert()
**Before:**
```javascript
alert('Error: ' + errorMessage);
```

**After:**
```javascript
await customAlert('Error: ' + errorMessage, 'Error');
```

### Replace confirm()
**Before:**
```javascript
if (!confirm('Are you sure you want to delete this?')) {
    return;
}
```

**After:**
```javascript
const confirmed = await customConfirm('Are you sure you want to delete this?', 'Confirm Delete');
if (!confirmed) {
    return;
}
```

### Replace prompt()
**Before:**
```javascript
const name = prompt('Enter name:', 'Default');
if (name === null) return;
```

**After:**
```javascript
const name = await customPrompt('Enter name:', 'Default', 'Input Required');
if (name === null) return;
```

## Step 3: Make Functions Async

Any function using custom modals must be declared as `async`:

**Before:**
```javascript
function deleteItem(id) {
    if (!confirm('Delete?')) return;
    // ... delete logic
}
```

**After:**
```javascript
async function deleteItem(id) {
    const confirmed = await customConfirm('Delete?', 'Confirm');
    if (!confirmed) return;
    // ... delete logic
}
```

## Step 4: Update Event Handlers

For inline event handlers, ensure they call async functions:

**Before:**
```html
<button onclick="deleteItem(123)">Delete</button>
```

**After (no change needed if function is already async):**
```html
<button onclick="deleteItem(123)">Delete</button>
```

## Custom Modal API

### customAlert(message, title)
- **message**: String - The message to display
- **title**: String - Modal title (default: 'Notice')
- **Returns**: Promise<void>

### customConfirm(message, title)
- **message**: String - The confirmation message
- **title**: String - Modal title (default: 'Confirm')
- **Returns**: Promise<boolean> - true if confirmed, false if cancelled

### customPrompt(message, defaultValue, title)
- **message**: String - The prompt message
- **defaultValue**: String - Default input value (default: '')
- **title**: String - Modal title (default: 'Input Required')
- **Returns**: Promise<string|null> - Input value or null if cancelled

## Priority Order

1. ✅ **segments.php** - COMPLETED
2. **campaigns.php** - 95 matches (most critical)
3. **content.php** - 31 matches
4. **surveys.php** - 25 matches
5. **events.php** - 19 matches
6. **campaigns_js_functions.js** - 14 matches
7. **partners.php** - 9 matches
8. **settings.php** - 2 matches

## Testing Checklist

After migrating each file:
- [ ] All alert() calls replaced
- [ ] All confirm() calls replaced
- [ ] All prompt() calls replaced
- [ ] Functions using modals are async
- [ ] Modals display correctly
- [ ] Keyboard shortcuts work (Escape, Enter)
- [ ] Error messages are user-friendly
- [ ] No console errors

## Example: Complete Migration

**Before (campaigns.php):**
```javascript
function deleteCampaign(id) {
    if (!confirm('Delete this campaign?')) {
        return;
    }
    
    fetch('/api/campaigns/' + id, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
                return;
            }
            alert('Campaign deleted successfully!');
            loadCampaigns();
        })
        .catch(err => {
            alert('Failed to delete: ' + err.message);
        });
}
```

**After:**
```javascript
async function deleteCampaign(id) {
    const confirmed = await customConfirm('Delete this campaign?', 'Confirm Delete');
    if (!confirmed) {
        return;
    }
    
    try {
        const res = await fetch('/api/campaigns/' + id, { method: 'DELETE' });
        const data = await res.json();
        
        if (data.error) {
            await customAlert('Error: ' + data.error, 'Error');
            return;
        }
        
        await customAlert('Campaign deleted successfully!', 'Success');
        loadCampaigns();
    } catch (err) {
        await customAlert('Failed to delete: ' + err.message, 'Error');
    }
}
```

## Notes

- Custom modals are non-blocking and return Promises
- Always use `await` when calling custom modals
- The teal theme (#4c8a89, #2d5a59) matches the system logo
- Modals include smooth animations and backdrop blur
- All modals support Escape key to close
- Confirm modals support clicking outside to cancel
