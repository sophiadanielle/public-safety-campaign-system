# How to Run the System in Browser

## Step 1: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** (click "Start" button)
3. Start **MySQL** (click "Start" button)

Both services should show green "Running" status.

## Step 2: Access the System

Open your web browser and navigate to:

### Main Pages:

**Login Page:**
```
http://localhost/public-safety-campaign-system/public/login.php
```

**Campaign Planning Page:**
```
http://localhost/public-safety-campaign-system/public/campaigns.php
```

**Other Pages:**
- Content: `http://localhost/public-safety-campaign-system/public/content.php`
- Events: `http://localhost/public-safety-campaign-system/public/events.php`
- Segments: `http://localhost/public-safety-campaign-system/public/segments.php`
- Surveys: `http://localhost/public-safety-campaign-system/public/surveys.php`
- Partners: `http://localhost/public-safety-campaign-system/public/partners.php`
- Impact: `http://localhost/public-safety-campaign-system/public/impact.php`

### API Endpoints:

**Base URL:**
```
http://localhost/public-safety-campaign-system/index.php
```

**Example API Calls:**
- Login: `POST http://localhost/public-safety-campaign-system/index.php/api/v1/auth/login`
- List Campaigns: `GET http://localhost/public-safety-campaign-system/index.php/api/v1/campaigns`

## Step 3: Login Credentials

After running migrations with seed data, use these test accounts:

### Admin Account (Full Access):
- **Email:** `admin@barangay1.qc.gov.ph`
- **Password:** `password123`

### Staff Account:
- **Email:** `staff@barangay1.qc.gov.ph`
- **Password:** `password123`

### School Partner:
- **Email:** `school@example.com`
- **Password:** `password123`

### NGO Partner:
- **Email:** `ngo@example.com`
- **Password:** `password123`

## Step 4: Test the System

1. **Go to Login Page:**
   ```
   http://localhost/public-safety-campaign-system/public/login.php
   ```

2. **Login with Admin credentials:**
   - Email: `admin@barangay1.qc.gov.ph`
   - Password: `password123`

3. **Navigate to Campaign Planning:**
   - Click on "Campaigns" in the navigation
   - Or go directly to: `http://localhost/public-safety-campaign-system/public/campaigns.php`

4. **Explore Features:**
   - Create a new campaign
   - Request AI recommendation for scheduling
   - View calendar and Gantt chart
   - Manage content and segments

## Troubleshooting

### Error: "Database connection failed"

**Solution:** Check that:
1. MySQL is running in XAMPP
2. Database `LGU` exists (check in phpMyAdmin)
3. Database credentials in `src/Config/db_connect.php` are correct:
   - Database: `LGU`
   - User: `root`
   - Password: (usually empty for XAMPP)

### Error: "404 Not Found"

**Solution:** 
- Make sure Apache is running
- Check the URL path is correct
- Verify the project is in `C:\xampp\htdocs\public-safety-campaign-system\`

### Error: "Access Denied" or Login Fails

**Solution:**
- Make sure you ran the seed data migration (`012_seed_data.sql`)
- Try resetting password in database:
  ```sql
  UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@barangay1.qc.gov.ph';
  ```
  (This sets password to `password123`)

### API Returns JSON Error

**Solution:**
- Check browser console for errors
- Verify database connection
- Make sure JWT secret is set (default is used if not set)

## Quick Test Checklist

- [ ] XAMPP Apache is running
- [ ] XAMPP MySQL is running
- [ ] Database `LGU` exists in phpMyAdmin
- [ ] Can access login page in browser
- [ ] Can login with admin credentials
- [ ] Can see campaigns page after login

## Next Steps

Once the system is running:

1. **Explore the Campaign Planning Interface:**
   - Create campaigns
   - Use AI scheduling recommendations
   - View calendar and timeline

2. **Test API Endpoints:**
   - Use Postman or similar tool
   - Test authentication
   - Test campaign CRUD operations

3. **Customize:**
   - Update database credentials if needed
   - Configure Google AutoML (optional)
   - Add your own data

## Need Help?

If you encounter issues:
1. Check XAMPP error logs: `C:\xampp\apache\logs\error.log`
2. Check MySQL error logs: `C:\xampp\mysql\data\mysql_error.log`
3. Verify database tables exist in phpMyAdmin
4. Check browser console for JavaScript errors















