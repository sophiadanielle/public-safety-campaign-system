-- Demo campaign insert for quick testing
USE campaign_db;

INSERT INTO campaigns (title, description, status, start_date, end_date, owner_id)
VALUES ('Fire Safety Week', 'Public awareness on fire safety', 'draft', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY),
        (SELECT id FROM users WHERE email = 'test.user@example.com' LIMIT 1))
ON DUPLICATE KEY UPDATE description = VALUES(description), status = VALUES(status), end_date = VALUES(end_date);


