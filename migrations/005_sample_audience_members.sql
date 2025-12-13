-- Sample audience members for testing segment evaluation
USE campaign_db;

INSERT INTO audience_segments (name, criteria) VALUES
('Sample Residents', JSON_OBJECT('channel', JSON_ARRAY('email','sms')))
ON DUPLICATE KEY UPDATE criteria = VALUES(criteria);

SET @segment_id = (SELECT id FROM audience_segments WHERE name = 'Sample Residents' LIMIT 1);

INSERT INTO audience_members (segment_id, full_name, contact, channel) VALUES
(@segment_id, 'Ana Santos', 'ana@example.com', 'email'),
(@segment_id, 'Ben Cruz', '09171234567', 'sms'),
(@segment_id, 'Carla Reyes', 'carla@example.com', 'email'),
(@segment_id, 'David Lee', NULL, 'other')
ON DUPLICATE KEY UPDATE contact = VALUES(contact), channel = VALUES(channel);


