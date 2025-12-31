-- Content Repository Sample Data
-- Comprehensive sample data demonstrating all Content Repository features
-- Run after migrations 001, 011, 012, and 014

SET NAMES utf8mb4;

-- ============================================
-- SAMPLE CONTENT ITEMS (Content Repository)
-- ============================================
-- Demonstrates: Different content types, approval statuses, hazard categories, audiences, sources, and versions
-- Note: Uses IDs 101-112 to avoid conflicts with existing seed data

-- Approved Content (Ready for use in campaigns)
INSERT IGNORE INTO `campaign_department_content_items` (
    id, campaign_id, title, body, content_type, hazard_category, 
    intended_audience_segment, source, approval_status, version_number,
    file_reference, file_path, created_by, approved_by, approval_notes,
    date_uploaded, visibility, created_at, updated_at
) VALUES
-- 1. Fire Safety Poster (APPROVED)
(101, NULL, 'Fire Safety Tips for Households', 
 'Essential fire safety tips including smoke detector maintenance, kitchen safety, and emergency contact numbers. Designed for residential areas.',
 'poster', 'fire', 'households, residential areas', 'barangay-created', 'approved', 2,
 'uploads/content_repository/fire_safety_households_v2.jpg', 'uploads/content_repository/fire_safety_households_v2.jpg',
 1, 1, 'Approved for distribution. Updated version with new contact numbers.',
 '2025-01-15 10:30:00', 'public', '2025-01-10 09:00:00', '2025-01-15 10:30:00'),

-- 2. Flood Preparedness Guideline (APPROVED)
(102, NULL, 'Flood Preparedness Checklist',
 'Comprehensive checklist for flood preparedness including evacuation planning, emergency kit preparation, and post-flood safety measures.',
 'guideline', 'flood', 'flood-prone areas, households', 'inspection-based', 'approved', 1,
 'uploads/content_repository/flood_preparedness_checklist.pdf', 'uploads/content_repository/flood_preparedness_checklist.pdf',
 2, 1, 'Based on recent flood risk assessments in low-lying barangays.',
 '2025-01-20 14:15:00', 'public', '2025-01-20 14:15:00', '2025-01-20 14:15:00'),

-- 3. Earthquake Safety Video (APPROVED)
(103, NULL, 'Earthquake Safety: Drop, Cover, and Hold On',
 'Educational video demonstrating proper earthquake response procedures. Includes animated demonstrations and real-world examples.',
 'video', 'earthquake', 'general public, schools, workplaces', 'training-based', 'approved', 1,
 'uploads/content_repository/earthquake_safety_video.mp4', 'uploads/content_repository/earthquake_safety_video.mp4',
 1, 1, 'High-quality educational content. Suitable for all age groups.',
 '2025-01-25 11:00:00', 'public', '2025-01-25 11:00:00', '2025-01-25 11:00:00'),

-- 4. Typhoon Preparedness Infographic (APPROVED)
(104, NULL, 'Typhoon Preparedness: Before, During, and After',
 'Visual infographic showing typhoon preparedness steps, evacuation routes, and emergency contacts. Easy to understand format.',
 'infographic', 'typhoon', 'coastal areas, general public', 'barangay-created', 'approved', 1,
 'uploads/content_repository/typhoon_preparedness_infographic.png', 'uploads/content_repository/typhoon_preparedness_infographic.png',
 2, 1, 'Clear and visually appealing. Ready for social media distribution.',
 '2025-02-01 09:30:00', 'public', '2025-02-01 09:30:00', '2025-02-01 09:30:00'),

-- 5. Health Safety Poster for Senior Citizens (APPROVED)
(105, NULL, 'Health Safety Tips for Senior Citizens',
 'Poster focusing on health safety measures for senior citizens including medication management, fall prevention, and emergency contacts.',
 'poster', 'health', 'senior citizens, elderly', 'barangay-created', 'approved', 1,
 'uploads/content_repository/health_safety_seniors.jpg', 'uploads/content_repository/health_safety_seniors.jpg',
 1, 1, 'Targeted content for vulnerable population. Approved for distribution.',
 '2025-02-05 13:20:00', 'public', '2025-02-05 13:20:00', '2025-02-05 13:20:00'),

-- Pending Content (Awaiting approval)
(106, NULL, 'Fire Safety for Schools',
 'Fire safety guidelines specifically designed for school environments. Includes evacuation procedures and fire drill protocols.',
 'guideline', 'fire', 'schools, students, teachers', 'training-based', 'pending', 1,
 'uploads/content_repository/fire_safety_schools.pdf', 'uploads/content_repository/fire_safety_schools.pdf',
 2, NULL, NULL,
 '2025-02-10 10:00:00', 'internal', '2025-02-10 10:00:00', '2025-02-10 10:00:00'),

(107, NULL, 'Dengue Prevention Infographic',
 'Infographic showing dengue prevention measures including mosquito breeding prevention and symptoms recognition.',
 'infographic', 'health', 'general public, households', 'inspection-based', 'pending', 1,
 'uploads/content_repository/dengue_prevention.png', 'uploads/content_repository/dengue_prevention.png',
 1, NULL, NULL,
 '2025-02-12 15:45:00', 'public', '2025-02-12 15:45:00', '2025-02-12 15:45:00'),

-- Draft Content (Work in progress)
(108, NULL, 'Earthquake Preparedness for High-Rise Buildings',
 'Guidelines for earthquake preparedness in high-rise residential and commercial buildings. Currently being reviewed.',
 'guideline', 'earthquake', 'residential buildings, commercial buildings', 'inspection-based', 'draft', 1,
 'uploads/content_repository/earthquake_highrise_draft.pdf', 'uploads/content_repository/earthquake_highrise_draft.pdf',
 2, NULL, NULL,
 '2025-02-15 11:30:00', 'internal', '2025-02-15 11:30:00', '2025-02-15 11:30:00'),

(109, NULL, 'Youth Safety Awareness Video',
 'Video targeting youth on various safety topics including road safety, online safety, and emergency response.',
 'video', 'health', 'youth, teenagers, students', 'barangay-created', 'draft', 1,
 'uploads/content_repository/youth_safety_video_draft.mp4', 'uploads/content_repository/youth_safety_video_draft.mp4',
 1, NULL, NULL,
 '2025-02-18 09:15:00', 'internal', '2025-02-18 09:15:00', '2025-02-18 09:15:00'),

-- Rejected Content (Needs revision)
(110, NULL, 'Flood Safety Poster (Rejected)',
 'Initial version of flood safety poster that was rejected due to outdated information and unclear messaging.',
 'poster', 'flood', 'general public', 'barangay-created', 'rejected', 1,
 'uploads/content_repository/flood_safety_rejected.jpg', 'uploads/content_repository/flood_safety_rejected.jpg',
 2, 1, 'Rejected: Contains outdated contact numbers and unclear evacuation instructions. Please revise and resubmit.',
 '2025-01-28 14:00:00', 'internal', '2025-01-28 14:00:00', '2025-01-30 10:00:00'),

-- More Approved Content (for variety)
(111, NULL, 'Emergency Contact Numbers Quick Reference',
 'Quick reference card with all emergency contact numbers including fire, police, medical, and barangay hotlines.',
 'poster', 'emergency', 'general public, households', 'barangay-created', 'approved', 1,
 'uploads/content_repository/emergency_contacts.jpg', 'uploads/content_repository/emergency_contacts.jpg',
 1, 1, 'Essential reference material. Approved for wide distribution.',
 '2025-02-08 10:00:00', 'public', '2025-02-08 10:00:00', '2025-02-08 10:00:00'),

(112, NULL, 'First Aid Basics Video',
 'Basic first aid procedures video covering CPR, wound care, and common emergency responses.',
 'video', 'health', 'general public, community volunteers', 'training-based', 'approved', 1,
 'uploads/content_repository/first_aid_basics.mp4', 'uploads/content_repository/first_aid_basics.mp4',
 2, 1, 'Excellent training material. Suitable for community workshops.',
 '2025-02-14 16:30:00', 'public', '2025-02-14 16:30:00', '2025-02-14 16:30:00');

-- ============================================
-- VERSION HISTORY (Demonstrates version tracking)
-- ============================================

INSERT IGNORE INTO `campaign_department_content_item_versions` (
    content_id, version_number, title, body, file_reference, file_path, changed_by, change_notes, created_at
) VALUES
-- Version 1 of Fire Safety Poster (before update)
(101, 1, 'Fire Safety Tips for Households',
 'Essential fire safety tips including smoke detector maintenance and kitchen safety.',
 'uploads/content_repository/fire_safety_households_v1.jpg', 'uploads/content_repository/fire_safety_households_v1.jpg',
 1, 'Initial version uploaded', '2025-01-10 09:00:00'),

-- Version 2 of Fire Safety Poster (current approved version)
(101, 2, 'Fire Safety Tips for Households',
 'Essential fire safety tips including smoke detector maintenance, kitchen safety, and emergency contact numbers.',
 'uploads/content_repository/fire_safety_households_v2.jpg', 'uploads/content_repository/fire_safety_households_v2.jpg',
 1, 'Updated with new emergency contact numbers and improved layout', '2025-01-15 10:30:00');

-- ============================================
-- ATTACHMENTS (for backward compatibility)
-- ============================================

INSERT IGNORE INTO `campaign_department_attachments` (content_item_id, file_path, mime_type, file_size) VALUES
(101, 'uploads/content_repository/fire_safety_households_v2.jpg', 'image/jpeg', 245760),
(102, 'uploads/content_repository/flood_preparedness_checklist.pdf', 'application/pdf', 512000),
(103, 'uploads/content_repository/earthquake_safety_video.mp4', 'video/mp4', 5242880),
(104, 'uploads/content_repository/typhoon_preparedness_infographic.png', 'image/png', 384000),
(105, 'uploads/content_repository/health_safety_seniors.jpg', 'image/jpeg', 198656),
(106, 'uploads/content_repository/fire_safety_schools.pdf', 'application/pdf', 456704),
(107, 'uploads/content_repository/dengue_prevention.png', 'image/png', 320000),
(108, 'uploads/content_repository/earthquake_highrise_draft.pdf', 'application/pdf', 678912),
(109, 'uploads/content_repository/youth_safety_video_draft.mp4', 'video/mp4', 8388608),
(110, 'uploads/content_repository/flood_safety_rejected.jpg', 'image/jpeg', 215040),
(111, 'uploads/content_repository/emergency_contacts.jpg', 'image/jpeg', 153600),
(112, 'uploads/content_repository/first_aid_basics.mp4', 'video/mp4', 6291456);

-- ============================================
-- TAGS (for content categorization)
-- ============================================

-- Ensure tags exist
INSERT IGNORE INTO `campaign_department_tags` (id, name) VALUES
(101, 'fire-safety'),
(102, 'flood-preparedness'),
(103, 'earthquake'),
(104, 'typhoon'),
(105, 'health'),
(106, 'emergency'),
(107, 'senior-citizens'),
(108, 'schools'),
(109, 'households'),
(110, 'youth');

-- Link tags to content
INSERT IGNORE INTO `campaign_department_content_tags` (content_item_id, tag_id) VALUES
(101, 101), (101, 109), -- Fire safety, households
(102, 102), (102, 109), -- Flood preparedness, households
(103, 103), (103, 108), -- Earthquake, schools
(104, 104), -- Typhoon
(105, 105), (105, 107), -- Health, senior citizens
(106, 101), (106, 108), -- Fire safety, schools
(107, 105), (107, 109), -- Health, households
(108, 103), -- Earthquake
(109, 105), (109, 110), -- Health, youth
(110, 102), (110, 109), -- Flood, households
(111, 106), -- Emergency
(112, 105), (112, 106); -- Health, emergency

-- ============================================
-- CAMPAIGN-CONTENT LINKING (Many-to-many)
-- ============================================
-- Link approved content to existing campaigns

INSERT IGNORE INTO `campaign_department_campaign_content_items` (campaign_id, content_id, attached_by, attached_at) VALUES
-- Link Fire Safety Poster to Fire Safety Campaign (campaign_id = 1)
(1, 101, 1, '2025-02-20 10:00:00'),
-- Link Emergency Contacts to Fire Safety Campaign
(1, 111, 1, '2025-02-20 10:05:00'),
-- Link Flood Preparedness Checklist to Flood Campaign (campaign_id = 3)
(3, 102, 2, '2025-02-21 14:00:00'),
-- Link Earthquake Safety Video to Earthquake Campaign (campaign_id = 2)
(2, 103, 1, '2025-02-22 09:30:00');

-- ============================================
-- CONTENT USAGE TRACKING (Optional)
-- ============================================

INSERT IGNORE INTO `campaign_department_content_usage` (content_item_id, campaign_id, usage_context) VALUES
(101, 1, 'Distributed during Fire Safety Awareness Week'),
(102, 3, 'Included in flood preparedness information packets'),
(103, 2, 'Screened during earthquake preparedness seminar'),
(111, 1, 'Distributed as quick reference cards');
