# LGU Governance Test Accounts

## Test Account Credentials

After running `migrations/031_create_lgu_test_accounts.sql`, the following test accounts will be available:

### All accounts use password: **`pass123`** (same as admin)

| Role | Email | Name | Password | Purpose |
|------|-------|------|----------|---------|
| **Admin** | `admin@barangay1.qc.gov.ph` | Admin User | `pass123` | Technical administrator (EXISTING - DO NOT MODIFY) |
| **Staff** | `staff@barangay1.qc.gov.ph` | Test Staff | `pass123` | Entry-level, can create drafts only |
| **Secretary** | `secretary@barangay1.qc.gov.ph` | Test Secretary | `pass123` | Can mark drafts as "Pending Review" |
| **Kagawad** | `kagawad@barangay1.qc.gov.ph` | Test Kagawad | `pass123` | Can recommend "For Approval" |
| **Captain** | `captain@barangay1.qc.gov.ph` | Test Captain | `pass123` | Final authority, can approve/reject |
| **Partner** | `partner@barangay1.qc.gov.ph` | Test Partner | `pass123` | External partner, limited access |
| **Viewer** | `viewer@barangay1.qc.gov.ph` | Test Viewer | `pass123` | Read-only access |

## How to Create These Accounts

### Step 1: Create LGU Roles (if not already done)

```bash
mysql -u your_username -p your_database < migrations/029_lgu_governance_roles.sql
```

### Step 2: Create Test Accounts

**Option 1: Run the Migration File**

```bash
mysql -u your_username -p your_database < migrations/031_create_lgu_test_accounts.sql
```

**Option 2: Run in phpMyAdmin**

1. Open phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Copy and paste contents of `migrations/031_create_lgu_test_accounts.sql`
5. Click "Go"

## Verification

After creating accounts, verify they exist:

```sql
SELECT 
    u.email,
    u.name,
    r.name as role_name,
    u.is_active
FROM `campaign_department_users` u
JOIN `campaign_department_roles` r ON r.id = u.role_id
WHERE u.email IN (
    'staff@barangay1.qc.gov.ph',
    'secretary@barangay1.qc.gov.ph',
    'kagawad@barangay1.qc.gov.ph',
    'captain@barangay1.qc.gov.ph',
    'partner@barangay1.qc.gov.ph',
    'viewer@barangay1.qc.gov.ph'
)
ORDER BY r.name;
```

## Testing Workflow

### Step 1: Login as Staff
- Email: `staff@barangay1.qc.gov.ph`
- Password: `pass123`
- **Expected:** Can create campaigns as drafts
- **Expected:** Cannot change status to approved (403 error)

### Step 2: Login as Secretary
- Email: `secretary@barangay1.qc.gov.ph`
- Password: `pass123`
- **Expected:** Can mark drafts as "pending_review"
- **Expected:** Cannot approve directly (403 error)

### Step 3: Login as Kagawad
- Email: `kagawad@barangay1.qc.gov.ph`
- Password: `pass123`
- **Expected:** Can change "pending_review" → "for_approval"
- **Expected:** Cannot approve directly (403 error)

### Step 4: Login as Captain
- Email: `captain@barangay1.qc.gov.ph`
- Password: `pass123`
- **Expected:** Can change "for_approval" → "approved" or "rejected"
- **Expected:** Can manage approved campaigns

## Notes

- All accounts are created with `is_active = 1`
- All accounts are assigned to Barangay 1
- Password hash is for "pass123" (same as admin account)
- Accounts use `INSERT IGNORE` so they won't duplicate if run multiple times
- **IMPORTANT:** Make sure to run `migrations/029_lgu_governance_roles.sql` FIRST to create the roles
- The existing admin account (`admin@barangay1.qc.gov.ph`) is NOT modified by this migration

