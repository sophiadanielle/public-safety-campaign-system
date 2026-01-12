-- Seed Data for Public Safety Campaign Management System
-- Run after schema migrations

SET NAMES utf8mb4;

-- ============================================
-- BARANGAYS
-- ============================================

INSERT IGNORE INTO `campaign_department_barangays` (id, name, city, province, region) VALUES
(1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR'),
(2, 'Barangay 2', 'Quezon City', 'Metro Manila', 'NCR'),
(3, 'Barangay 3', 'Quezon City', 'Metro Manila', 'NCR');

-- ============================================
-- ROLES (if not already inserted)
-- ============================================

INSERT IGNORE INTO `campaign_department_roles` (id, name, description) VALUES
(1, 'Barangay Administrator', 'Full access to all campaign management features'),
(2, 'Barangay Staff', 'Can create and manage campaigns, limited administrative access'),
(3, 'School Partner', 'Can view campaigns and coordinate joint activities'),
(4, 'NGO Partner', 'Can view campaigns and coordinate joint activities');

-- ============================================
-- PERMISSIONS (if not already inserted)
-- ============================================

INSERT IGNORE INTO `campaign_department_permissions` (id, name, description) VALUES
(1, 'campaigns.create', 'Create new campaigns'),
(2, 'campaigns.update', 'Update existing campaigns'),
(3, 'campaigns.delete', 'Delete campaigns'),
(4, 'campaigns.approve', 'Approve campaigns'),
(5, 'content.manage', 'Manage content repository'),
(6, 'content.approve', 'Approve content'),
(7, 'events.manage', 'Manage events and seminars'),
(8, 'surveys.manage', 'Create and manage surveys'),
(9, 'partners.manage', 'Manage partner organizations'),
(10, 'reports.view', 'View reports and analytics'),
(11, 'users.manage', 'Manage users (admin only)');

-- ============================================
-- ROLE PERMISSIONS
-- ============================================

-- Barangay Administrator - all permissions
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 1, id FROM `campaign_department_permissions`;

-- Barangay Staff - most permissions except user management
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 2, id FROM `campaign_department_permissions` WHERE id != 11;

-- School Partner - limited permissions
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 3, id FROM `campaign_department_permissions` WHERE id IN (7, 8, 10);

-- NGO Partner - limited permissions
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 4, id FROM `campaign_department_permissions` WHERE id IN (7, 8, 10);

-- ============================================
-- SAMPLE USERS
-- ============================================

-- Default password for all: "password123" (hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
INSERT IGNORE INTO `campaign_department_users` (id, role_id, barangay_id, name, email, password_hash, is_active) VALUES
(1, 1, 1, 'Admin User', 'admin@barangay1.qc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(2, 2, 1, 'Staff Member', 'staff@barangay1.qc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(3, 3, NULL, 'School Partner', 'school@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(4, 4, NULL, 'NGO Partner', 'ngo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- ============================================
-- SAMPLE AUDIENCE SEGMENTS
-- ============================================

INSERT IGNORE INTO `campaign_department_audience_segments` (id, name, geographic_scope, sector_type, risk_level, segmentation_basis, criteria) VALUES
(1, 'Residential Areas - High Risk', 'Quezon City - Barangay 1-5', 'residential', 'high', 'Geographic location and risk assessment', '{"location": "Barangay 1-5", "risk_factors": ["dense_population", "old_buildings"]}'),
(2, 'School Communities', 'Quezon City - All Schools', 'education', 'medium', 'Educational institutions', '{"type": "school", "age_group": "all"}'),
(3, 'Commercial Districts', 'Quezon City - Business Areas', 'commercial', 'medium', 'Business and commercial establishments', '{"type": "commercial", "business_size": "all"}'),
(4, 'Senior Citizens', 'Quezon City - All Areas', 'residential', 'high', 'Age-based segmentation', '{"age_min": 60, "special_needs": true}');

-- ============================================
-- SAMPLE PARTNERS
-- ============================================

INSERT IGNORE INTO `campaign_department_partners` (id, name, organization_type, contact_person, contact_email, contact_phone, contact_details) VALUES
(1, 'Quezon City Elementary School', 'school', 'Principal Juan Dela Cruz', 'principal@qces.edu.ph', '+63-2-1234-5678', '{"address": "123 Main St, Quezon City", "website": "www.qces.edu.ph"}'),
(2, 'Red Cross Quezon City Chapter', 'ngo', 'Maria Santos', 'maria@redcross.qc.ph', '+63-2-2345-6789', '{"address": "456 Relief Ave, Quezon City", "services": ["disaster_relief", "first_aid_training"]}'),
(3, 'Quezon City High School', 'school', 'Principal Pedro Reyes', 'pedro@qchs.edu.ph', '+63-2-3456-7890', '{"address": "789 Education Blvd, Quezon City", "student_count": 1500}'),
(4, 'Save the Children Philippines', 'ngo', 'Ana Garcia', 'ana@savethechildren.ph', '+63-2-4567-8901', '{"address": "321 Hope St, Quezon City", "focus_areas": ["child_safety", "education"]}');

-- ============================================
-- SAMPLE CAMPAIGNS
-- ============================================

INSERT IGNORE INTO `campaign_department_campaigns` (id, title, description, category, geographic_scope, status, start_date, end_date, owner_id, objectives, location, assigned_staff, barangay_target_zones, budget, staff_count, materials_json) VALUES
(1, 'Fire Safety Awareness Week 2025', 'Annual fire safety awareness campaign for residential areas', 'fire_safety', 'Quezon City - Barangay 1-5', 'approved', '2025-03-01', '2025-03-07', 1, 'Increase fire safety awareness, distribute fire safety materials, conduct fire drills', 'Barangay Hall - Barangay 1', '["John Doe", "Jane Smith", "Bob Johnson"]', '["Barangay 1", "Barangay 2", "Barangay 3"]', 50000.00, 5, '{"posters": 100, "flyers": 500, "banners": 5}'),
(2, 'Earthquake Preparedness Seminar', 'Educational seminar on earthquake preparedness and response', 'earthquake', 'Quezon City - All Areas', 'draft', '2025-04-15', '2025-04-15', 1, 'Educate residents on earthquake safety, demonstrate proper response procedures', 'Quezon City Convention Center', '["Maria Santos", "Pedro Reyes"]', '["All Barangays"]', 30000.00, 3, '{"brochures": 300, "videos": 2}'),
(3, 'Flood Preparedness Campaign', 'Campaign to prepare communities for flood season', 'flood', 'Quezon City - Low-lying Areas', 'pending', '2025-05-01', '2025-05-31', 2, 'Distribute flood safety information, identify evacuation routes, coordinate with partners', 'Multiple Locations', '["Ana Garcia", "Carlos Mendoza"]', '["Barangay 1", "Barangay 2"]', 75000.00, 8, '{"sandbags": 200, "information_kits": 1000}');

-- ============================================
-- SAMPLE CAMPAIGN-AUDIENCE LINKS
-- ============================================

INSERT IGNORE INTO `campaign_department_campaign_audience` (campaign_id, segment_id) VALUES
(1, 1), -- Fire Safety -> Residential High Risk
(1, 4), -- Fire Safety -> Senior Citizens
(2, 2), -- Earthquake -> School Communities
(2, 1), -- Earthquake -> Residential High Risk
(3, 1), -- Flood -> Residential High Risk
(3, 3); -- Flood -> Commercial Districts

-- ============================================
-- SAMPLE EVENTS
-- ============================================

INSERT IGNORE INTO `campaign_department_events` (id, campaign_id, name, event_type, description, event_date, event_time, location, venue, facilitators, attendance_count, status) VALUES
(1, 1, 'Fire Safety Seminar - Day 1', 'seminar', 'Introduction to fire safety and prevention', '2025-03-01', '09:00:00', 'Barangay Hall - Barangay 1', 'Main Hall', '["John Doe", "Jane Smith"]', 45, 'completed'),
(2, 1, 'Fire Drill Practice', 'drill', 'Hands-on fire drill practice session', '2025-03-03', '14:00:00', 'Barangay Hall - Barangay 1', 'Outdoor Area', '["Bob Johnson"]', 60, 'completed'),
(3, 2, 'Earthquake Preparedness Workshop', 'workshop', 'Interactive workshop on earthquake response', '2025-04-15', '10:00:00', 'Quezon City Convention Center', 'Conference Room A', '["Maria Santos", "Pedro Reyes"]', 0, 'scheduled');

-- ============================================
-- SAMPLE CONTENT
-- ============================================

INSERT IGNORE INTO `campaign_department_content_items` (id, campaign_id, title, body, content_type, hazard_category, intended_audience, source, approval_status, file_path, created_by) VALUES
(1, 1, 'Fire Safety Tips Poster', 'Essential fire safety tips for home', 'image', 'fire', 'general_public', 'Barangay Safety Office', 'approved', '/uploads/posters/fire_safety_tips.jpg', 1),
(2, 1, 'Fire Evacuation Plan', 'Step-by-step fire evacuation procedures', 'file', 'fire', 'residential', 'Fire Department', 'approved', '/uploads/plans/evacuation_plan.pdf', 1),
(3, 2, 'Earthquake Safety Video', 'Educational video on earthquake preparedness', 'video', 'earthquake', 'general_public', 'NDRRMC', 'pending', '/uploads/videos/earthquake_safety.mp4', 1);

-- ============================================
-- SAMPLE SURVEYS
-- ============================================

INSERT IGNORE INTO `campaign_department_surveys` (id, campaign_id, title, description) VALUES
(1, 1, 'Fire Safety Awareness Survey', 'Survey to measure fire safety awareness after campaign'),
(2, 2, 'Earthquake Preparedness Assessment', 'Assessment of earthquake preparedness knowledge');

-- ============================================
-- SAMPLE SURVEY QUESTIONS
-- ============================================

INSERT IGNORE INTO `campaign_department_survey_questions` (id, survey_id, question_text, question_type, options_json) VALUES
(1, 1, 'How would you rate your fire safety knowledge before this campaign?', 'rating', NULL),
(2, 1, 'How would you rate your fire safety knowledge after this campaign?', 'rating', NULL),
(3, 1, 'What fire safety measures do you have at home?', 'multiple_choice', '["smoke_detector", "fire_extinguisher", "fire_blanket", "evacuation_plan", "none"]'),
(4, 2, 'Do you know the proper "Drop, Cover, and Hold On" procedure?', 'single_choice', '["yes", "no", "unsure"]'),
(5, 2, 'How prepared do you feel for an earthquake?', 'rating', NULL);

-- ============================================
-- SAMPLE FEEDBACK
-- ============================================

INSERT IGNORE INTO `campaign_department_feedback` (feedback_id, survey_id, rating, comment, submitted_at) VALUES
(1, 1, 5, 'Very informative campaign! Learned a lot about fire safety.', '2025-03-05 10:30:00'),
(2, 1, 4, 'Good information, but could use more practical demonstrations.', '2025-03-05 11:15:00'),
(3, 1, 5, 'Excellent! The fire drill was very helpful.', '2025-03-05 14:20:00'),
(4, 2, 4, 'The workshop was informative.', '2025-04-15 12:00:00');

-- ============================================
-- SAMPLE PARTNER ENGAGEMENTS
-- ============================================

INSERT IGNORE INTO `campaign_department_partner_engagements` (id, partner_id, campaign_id, engagement_type, notes) VALUES
(1, 1, 1, 'co_host', 'School provided venue for fire safety seminar'),
(2, 2, 1, 'resource_sharing', 'Red Cross provided first aid training materials'),
(3, 3, 2, 'co_host', 'High school hosted earthquake preparedness workshop'),
(4, 4, 3, 'coordination', 'NGO assisting with flood preparedness materials distribution');















