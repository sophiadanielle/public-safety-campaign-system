-- Quick SQL script to insert sample content data
-- Run this directly in phpMyAdmin or MySQL command line
-- Make sure you're using the correct database (usually 'LGU')

USE LGU;

-- Insert sample content items
INSERT IGNORE INTO content_items (
    id, campaign_id, title, body, content_type, hazard_category, 
    intended_audience_segment, source, approval_status, version_number,
    file_reference, file_path, created_by, approved_by, approval_notes,
    date_uploaded, visibility, created_at, updated_at
) VALUES
(101, NULL, 'Fire Safety Tips for Households', 
 'Essential fire safety tips including smoke detector maintenance, kitchen safety, and emergency contact numbers.',
 'poster', 'fire', 'households, residential areas', 'barangay-created', 'approved', 2,
 'uploads/content_repository/fire_safety.jpg', 'uploads/content_repository/fire_safety.jpg',
 1, 1, 'Approved for distribution.',
 NOW(), 'public', NOW(), NOW()),

(102, NULL, 'Flood Preparedness Checklist',
 'Comprehensive checklist for flood preparedness including evacuation planning and emergency kit preparation.',
 'guideline', 'flood', 'flood-prone areas, households', 'inspection-based', 'approved', 1,
 'uploads/content_repository/flood_checklist.pdf', 'uploads/content_repository/flood_checklist.pdf',
 2, 1, 'Based on recent flood risk assessments.',
 NOW(), 'public', NOW(), NOW()),

(103, NULL, 'Earthquake Safety Video',
 'Educational video demonstrating proper earthquake response procedures.',
 'video', 'earthquake', 'general public, schools', 'training-based', 'approved', 1,
 'uploads/content_repository/earthquake_video.mp4', 'uploads/content_repository/earthquake_video.mp4',
 1, 1, 'High-quality educational content.',
 NOW(), 'public', NOW(), NOW()),

(104, NULL, 'Typhoon Preparedness Infographic',
 'Visual infographic showing typhoon preparedness steps and evacuation routes.',
 'infographic', 'typhoon', 'coastal areas, general public', 'barangay-created', 'approved', 1,
 'uploads/content_repository/typhoon_infographic.png', 'uploads/content_repository/typhoon_infographic.png',
 2, 1, 'Ready for social media distribution.',
 NOW(), 'public', NOW(), NOW()),

(105, NULL, 'Health Safety Tips for Senior Citizens',
 'Poster focusing on health safety measures for senior citizens.',
 'poster', 'health', 'senior citizens, elderly', 'barangay-created', 'approved', 1,
 'uploads/content_repository/health_seniors.jpg', 'uploads/content_repository/health_seniors.jpg',
 1, 1, 'Targeted content for vulnerable population.',
 NOW(), 'public', NOW(), NOW()),

(106, NULL, 'Fire Safety for Schools',
 'Fire safety guidelines specifically designed for school environments.',
 'guideline', 'fire', 'schools, students, teachers', 'training-based', 'pending', 1,
 'uploads/content_repository/fire_schools.pdf', 'uploads/content_repository/fire_schools.pdf',
 2, NULL, NULL,
 NOW(), 'internal', NOW(), NOW()),

(107, NULL, 'Dengue Prevention Infographic',
 'Infographic showing dengue prevention measures.',
 'infographic', 'health', 'general public, households', 'inspection-based', 'pending', 1,
 'uploads/content_repository/dengue.png', 'uploads/content_repository/dengue.png',
 1, NULL, NULL,
 NOW(), 'public', NOW(), NOW()),

(108, NULL, 'Earthquake Preparedness for High-Rise Buildings',
 'Guidelines for earthquake preparedness in high-rise buildings.',
 'guideline', 'earthquake', 'residential buildings', 'inspection-based', 'draft', 1,
 'uploads/content_repository/earthquake_highrise.pdf', 'uploads/content_repository/earthquake_highrise.pdf',
 2, NULL, NULL,
 NOW(), 'internal', NOW(), NOW()),

(109, NULL, 'Youth Safety Awareness Video',
 'Video targeting youth on various safety topics.',
 'video', 'health', 'youth, teenagers, students', 'barangay-created', 'draft', 1,
 'uploads/content_repository/youth_video.mp4', 'uploads/content_repository/youth_video.mp4',
 1, NULL, NULL,
 NOW(), 'internal', NOW(), NOW()),

(110, NULL, 'Flood Safety Poster (Rejected)',
 'Initial version that was rejected due to outdated information.',
 'poster', 'flood', 'general public', 'barangay-created', 'rejected', 1,
 'uploads/content_repository/flood_rejected.jpg', 'uploads/content_repository/flood_rejected.jpg',
 2, 1, 'Rejected: Contains outdated contact numbers.',
 NOW(), 'internal', NOW(), NOW()),

(111, NULL, 'Emergency Contact Numbers Quick Reference',
 'Quick reference card with all emergency contact numbers.',
 'poster', 'emergency', 'general public, households', 'barangay-created', 'approved', 1,
 'uploads/content_repository/emergency_contacts.jpg', 'uploads/content_repository/emergency_contacts.jpg',
 1, 1, 'Essential reference material.',
 NOW(), 'public', NOW(), NOW()),

(112, NULL, 'First Aid Basics Video',
 'Basic first aid procedures video covering CPR and wound care.',
 'video', 'health', 'general public, community volunteers', 'training-based', 'approved', 1,
 'uploads/content_repository/first_aid.mp4', 'uploads/content_repository/first_aid.mp4',
 2, 1, 'Excellent training material.',
 NOW(), 'public', NOW(), NOW());

-- Insert attachments
INSERT IGNORE INTO attachments (content_item_id, file_path, mime_type, file_size) VALUES
(101, 'uploads/content_repository/fire_safety.jpg', 'image/jpeg', 245760),
(102, 'uploads/content_repository/flood_checklist.pdf', 'application/pdf', 512000),
(103, 'uploads/content_repository/earthquake_video.mp4', 'video/mp4', 5242880),
(104, 'uploads/content_repository/typhoon_infographic.png', 'image/png', 384000),
(105, 'uploads/content_repository/health_seniors.jpg', 'image/jpeg', 198656),
(106, 'uploads/content_repository/fire_schools.pdf', 'application/pdf', 456704),
(107, 'uploads/content_repository/dengue.png', 'image/png', 320000),
(108, 'uploads/content_repository/earthquake_highrise.pdf', 'application/pdf', 678912),
(109, 'uploads/content_repository/youth_video.mp4', 'video/mp4', 8388608),
(110, 'uploads/content_repository/flood_rejected.jpg', 'image/jpeg', 215040),
(111, 'uploads/content_repository/emergency_contacts.jpg', 'image/jpeg', 153600),
(112, 'uploads/content_repository/first_aid.mp4', 'video/mp4', 6291456);

