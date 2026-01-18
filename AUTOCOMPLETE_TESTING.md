# Autocomplete Testing Guide

## How to Test Autocomplete Features

### 1. Login to the System
- Go to: `http://localhost/public-safety-campaign-system/public/login.php`
- Login with: `admin@barangay1.qc.gov.ph` / `password123`

### 2. Navigate to Campaigns Page
- Go to: `http://localhost/public-safety-campaign-system/public/campaigns.php`
- Or click "Campaigns" in the navigation menu

### 3. Test Each Autocomplete Field

#### Campaign Title Field
1. Click in the "Campaign Title" input field
2. Type at least 2 characters (e.g., "Fire" or "Safety")
3. **Expected:** A dropdown should appear with matching campaign titles from the database
4. **Test:** Type "Fire" - should show "Fire Safety Awareness Week 2025" if it exists

#### Geographic Scope / Barangay Field
1. Click in the "Geographic Scope / Barangay" input field
2. Type "Barangay" or "QC"
3. **Expected:** Dropdown with Quezon City barangays
4. **Test:** Should show "Barangay 1", "Barangay 2", "Barangay 3", etc.
5. **Multi-select:** Type a barangay, select it, add comma, type another

#### Barangay Target Zones Field
1. Click in the "Barangay Target Zones" input field
2. Type "Barangay"
3. **Expected:** Same barangay suggestions as above
4. **Test:** Can select multiple barangays separated by commas

#### Location Field
1. Click in the "Location" input field
2. Type "Barangay Hall" or "Quezon"
3. **Expected:** Dropdown with Quezon City locations from campaigns and events
4. **Test:** Should show previously used locations

#### Assigned Staff Field
1. Click in the "Assigned Staff" input field
2. Type a staff name (e.g., "John" or "Jane")
3. **Expected:** Dropdown with registered staff members
4. **Test:** Should show staff from users table and previously assigned staff
5. **Multi-select:** Can add multiple staff separated by commas

#### Materials Field
1. Click in the "Materials" input field
2. Type "poster" or "flyer"
3. **Expected:** Dropdown with materials from Content Repository and previous campaigns
4. **Test:** Should show content items and previously used materials

### 4. Keyboard Navigation
- **Arrow Down:** Navigate to next suggestion
- **Arrow Up:** Navigate to previous suggestion
- **Enter:** Select highlighted suggestion
- **Escape:** Close dropdown

### 5. Troubleshooting

#### If suggestions don't appear:
1. **Open Browser Console (F12)**
   - Go to Console tab
   - Look for errors or warnings
   - Check for "Autocomplete initialized for:" messages

2. **Check Network Tab**
   - Go to Network tab in Developer Tools
   - Type in an autocomplete field
   - Look for requests to `/api/v1/autocomplete/...`
   - Check if requests return 200 status
   - Verify response contains `{"data": [...]}`

3. **Check API Endpoints**
   - Test directly in browser:
   - `http://localhost/public-safety-campaign-system/index.php/api/v1/autocomplete/campaign-titles?q=Fire`
   - Should return JSON with `{"data": ["Fire Safety..."]}`

4. **Verify Authentication**
   - Make sure you're logged in (JWT token in localStorage)
   - Check: `localStorage.getItem('jwtToken')` in console

5. **Check Database**
   - Verify you have data in the database:
   - Campaigns exist for title suggestions
   - Barangays exist for barangay suggestions
   - Users exist for staff suggestions
   - Content items exist for materials suggestions

### 6. Common Issues

**Issue:** "No suggestions appearing"
- **Solution:** Make sure you type at least 2 characters
- **Solution:** Check browser console for errors
- **Solution:** Verify API endpoints are accessible

**Issue:** "CORS or 401 errors"
- **Solution:** Make sure you're logged in
- **Solution:** Check JWT token is valid
- **Solution:** Verify API path is correct

**Issue:** "Suggestions appear but can't select"
- **Solution:** Try clicking directly on the suggestion
- **Solution:** Use keyboard navigation (Arrow keys + Enter)
- **Solution:** Check if dropdown CSS is blocking clicks

### 7. Expected Behavior

✅ **Working Correctly When:**
- Suggestions appear after typing 2+ characters
- Dropdown shows below the input field
- Suggestions are filtered based on what you type
- Clicking a suggestion fills the input field
- Multi-select fields support comma-separated values
- Keyboard navigation works (Arrow keys, Enter, Escape)

❌ **Not Working When:**
- No suggestions appear at all
- Console shows errors
- Network requests fail (404, 401, 500)
- Suggestions appear but don't match what you typed
- Can't select suggestions

### 8. Data Flow

1. User types in input field
2. JavaScript debounces (waits 300ms)
3. AJAX request to `/api/v1/autocomplete/[type]?q=searchterm`
4. Backend queries database for matching records
5. Backend returns JSON: `{"data": ["suggestion1", "suggestion2", ...]}`
6. Frontend displays suggestions in dropdown
7. User selects suggestion
8. Input field is populated with selected value

















