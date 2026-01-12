-- Seed reference data for Quezon City for autocomplete and testing
-- Database: LGU

USE LGU;

SET NAMES utf8mb4;

-- ============================================
-- 1. BARANGAYS (15 QC barangays)
-- ============================================

INSERT IGNORE INTO `campaign_department_barangays` (name, city, province, region) VALUES
('Commonwealth', 'Quezon City', 'Metro Manila', 'NCR'),
('Batasan Hills', 'Quezon City', 'Metro Manila', 'NCR'),
('Payatas', 'Quezon City', 'Metro Manila', 'NCR'),
('Holy Spirit', 'Quezon City', 'Metro Manila', 'NCR'),
('Bagong Silangan', 'Quezon City', 'Metro Manila', 'NCR'),
('Tandang Sora', 'Quezon City', 'Metro Manila', 'NCR'),
('Matandang Balara', 'Quezon City', 'Metro Manila', 'NCR'),
('Culiat', 'Quezon City', 'Metro Manila', 'NCR'),
('Nagkaisang Nayon', 'Quezon City', 'Metro Manila', 'NCR'),
('Krus na Ligas', 'Quezon City', 'Metro Manila', 'NCR'),
('Pansol', 'Quezon City', 'Metro Manila', 'NCR'),
('Teachers Village East', 'Quezon City', 'Metro Manila', 'NCR'),
('Teachers Village West', 'Quezon City', 'Metro Manila', 'NCR'),
('UP Campus', 'Quezon City', 'Metro Manila', 'NCR'),
('Loyola Heights', 'Quezon City', 'Metro Manila', 'NCR');

-- ============================================
-- 2. REFERENCE LOCATIONS (15 QC locations)
-- ============================================

CREATE TABLE IF NOT EXISTS `campaign_department_reference_locations` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    barangay_name VARCHAR(150) NULL,
    city VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `campaign_department_reference_locations` (name, barangay_name, city) VALUES
('Commonwealth Covered Court', 'Commonwealth', 'Quezon City'),
('Phase 8 Covered Court', 'Commonwealth', 'Quezon City'),
('IBP Road Area', 'Batasan Hills', 'Quezon City'),
('Litex Area', 'Batasan Hills', 'Quezon City'),
('Payatas Gymnasium', 'Payatas', 'Quezon City'),
('Payatas Elementary School', 'Payatas', 'Quezon City'),
('Holy Spirit Barangay Hall', 'Holy Spirit', 'Quezon City'),
('Holy Spirit Elementary School', 'Holy Spirit', 'Quezon City'),
('Bagong Silangan MRF', 'Bagong Silangan', 'Quezon City'),
('Bagong Silangan Multi-Purpose Hall', 'Bagong Silangan', 'Quezon City'),
('Youth Center', 'Holy Spirit', 'Quezon City'),
('Senior Citizens Center', 'Payatas', 'Quezon City'),
('Evacuation Center', 'Commonwealth', 'Quezon City'),
('IBP Road Junction', 'Batasan Hills', 'Quezon City'),
('Barangay Hall', 'Commonwealth', 'Quezon City');

-- ============================================
-- 3. REFERENCE STAFF (15 named staff with roles)
-- ============================================

CREATE TABLE IF NOT EXISTS `campaign_department_reference_staff` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    role VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `campaign_department_reference_staff` (name, role) VALUES
('Juan Dela Cruz', 'Barangay Safety Officer'),
('Maria Santos', 'DRRM Coordinator'),
('Pedro Reyes', 'Fire Safety Marshal'),
('Ana Lopez', 'Campaign Officer'),
('Mark Villanueva', 'Traffic Safety Officer'),
('Liza Mendoza', 'Community Organizer'),
('Carlos Ramirez', 'Disaster Preparedness Trainer'),
('Grace Flores', 'Health & Safety Officer'),
('Joseph Lim', 'Barangay Secretary'),
('Noel Bautista', 'Logistics Coordinator'),
('Rhea Cruz', 'Youth Coordinator'),
('Alvin Garcia', 'IT Support'),
('Michelle Tan', 'NGO Liaison'),
('Robert Aquino', 'Seminar Facilitator'),
('Karen Dizon', 'Data Analyst');

-- ============================================
-- 4. CONTENT / MATERIALS (15 content_items)
-- ============================================

-- Note: Uses existing campaigns (1–3) and admin user (id=1) as creator.
-- content_type is mapped from the "type" label.

INSERT IGNORE INTO `campaign_department_content_items`
    (campaign_id, title, body, content_type, hazard_category, intended_audience_segment, source, approval_status, file_path, created_by)
VALUES
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Fire Safety Poster', 'Poster for basic fire safety reminders.', 'image', 'fire', 'general_public', 'Seed Data', 'approved', '/uploads/materials/fire_safety_poster.jpg', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Flood Preparedness Checklist', 'Checklist for household flood preparedness.', 'file', 'flood', 'general_public', 'Seed Data', 'approved', '/uploads/materials/flood_preparedness_checklist.pdf', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Earthquake Drill Video', 'Video demonstrating earthquake drill procedures.', 'video', 'earthquake', 'general_public', 'Seed Data', 'approved', '/uploads/materials/earthquake_drill_video.mp4', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Emergency Go-Bag Guide', 'Guide on preparing emergency go-bags.', 'file', 'emergency', 'general_public', 'Seed Data', 'approved', '/uploads/materials/emergency_go_bag_guide.pdf', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Road Safety Infographic', 'Infographic about road and traffic safety.', 'image', 'traffic', 'general_public', 'Seed Data', 'approved', '/uploads/materials/road_safety_infographic.png', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Dengue Prevention Poster', 'Poster about dengue prevention measures.', 'image', 'health', 'general_public', 'Seed Data', 'approved', '/uploads/materials/dengue_prevention_poster.jpg', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Fire Drill Guide', 'Step-by-step fire drill guide.', 'file', 'fire', 'general_public', 'Seed Data', 'approved', '/uploads/materials/fire_drill_guide.pdf', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Typhoon Safety Video', 'Video on typhoon safety and preparedness.', 'video', 'typhoon', 'general_public', 'Seed Data', 'approved', '/uploads/materials/typhoon_safety_video.mp4', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Senior Citizen Safety Guide', 'Safety guide tailored for senior citizens.', 'file', 'senior', 'seniors', 'Seed Data', 'approved', '/uploads/materials/senior_citizen_safety_guide.pdf', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Youth Disaster Awareness Poster', 'Poster to raise disaster awareness among youth.', 'image', 'youth', 'youth', 'Seed Data', 'approved', '/uploads/materials/youth_disaster_awareness_poster.jpg', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Community Evacuation Map', 'Map showing community evacuation routes.', 'image', 'evacuation', 'general_public', 'Seed Data', 'approved', '/uploads/materials/community_evacuation_map.png', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Flood Risk Awareness Video', 'Video about flood risks and mitigation.', 'video', 'flood', 'general_public', 'Seed Data', 'approved', '/uploads/materials/flood_risk_awareness_video.mp4', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Fire Extinguisher Manual', 'Manual for using fire extinguishers.', 'file', 'equipment', 'general_public', 'Seed Data', 'approved', '/uploads/materials/fire_extinguisher_manual.pdf', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'School Safety Checklist', 'Checklist for school-based safety checks.', 'file', 'school', 'school_community', 'Seed Data', 'approved', '/uploads/materials/school_safety_checklist.pdf', 1),
((SELECT id FROM `campaign_department_campaigns` ORDER BY id LIMIT 1),
 'Barangay Emergency Contacts', 'List of important barangay emergency contacts.', 'file', 'contacts', 'general_public', 'Seed Data', 'approved', '/uploads/materials/barangay_emergency_contacts.pdf', 1);

-- ============================================
-- 5. CAMPAIGN TITLES (15 additional campaigns)
-- ============================================

INSERT IGNORE INTO `campaign_department_campaigns`
    (title, description, status, start_date, end_date, owner_id, category, geographic_scope, location, budget, staff_count)
VALUES
('Fire Safety Awareness Drive', 'Barangay-level fire safety awareness drive in Quezon City.', 'draft', '2025-01-10', '2025-01-31', 1, 'fire_safety', 'Quezon City', 'Commonwealth Covered Court', 40000.00, 5),
('Flood Preparedness Campaign', 'Preparedness campaign for flood-prone barangays.', 'draft', '2025-02-01', '2025-02-28', 1, 'flood', 'Quezon City', 'Evacuation Center', 60000.00, 6),
('Earthquake Readiness Orientation', 'Orientation sessions on earthquake readiness.', 'draft', '2025-03-05', '2025-03-20', 1, 'earthquake', 'Quezon City', 'Payatas Elementary School', 35000.00, 4),
('Road Safety Information Drive', 'Information drive on pedestrian and road safety.', 'draft', '2025-04-01', '2025-04-30', 1, 'road_safety', 'Quezon City', 'Litex Area', 30000.00, 3),
('Dengue Prevention Campaign', 'Campaign on dengue prevention and clean-up activities.', 'draft', '2025-05-01', '2025-05-31', 1, 'health', 'Quezon City', 'Bagong Silangan Multi-Purpose Hall', 45000.00, 5),
('Fire Drill Awareness Program', 'Program to promote regular community fire drills.', 'draft', '2025-06-01', '2025-06-30', 1, 'fire_safety', 'Quezon City', 'Barangay Hall', 38000.00, 4),
('Flood Evacuation Awareness', 'Awareness program on flood evacuation routes and centers.', 'draft', '2025-07-01', '2025-07-31', 1, 'flood', 'Quezon City', 'IBP Road Junction', 42000.00, 4),
('Earthquake Go-Bag Campaign', 'Encouraging households to prepare earthquake go-bags.', 'draft', '2025-08-01', '2025-08-31', 1, 'earthquake', 'Quezon City', 'Holy Spirit Barangay Hall', 28000.00, 3),
('Youth Safety Seminar', 'Seminars focused on youth safety and disaster awareness.', 'draft', '2025-09-01', '2025-09-15', 1, 'youth', 'Quezon City', 'Youth Center', 25000.00, 3),
('Fire Extinguisher Training', 'Hands-on training on using fire extinguishers.', 'draft', '2025-10-01', '2025-10-15', 1, 'fire_safety', 'Quezon City', 'Commonwealth Covered Court', 32000.00, 4),
('Traffic Safety Awareness', 'Traffic safety and pedestrian discipline campaign.', 'draft', '2025-10-16', '2025-10-31', 1, 'road_safety', 'Quezon City', 'Phase 8 Covered Court', 28000.00, 3),
('Senior Citizen Safety Talk', 'Safety talks and orientations for senior citizens.', 'draft', '2025-11-01', '2025-11-15', 1, 'senior_safety', 'Quezon City', 'Senior Citizens Center', 20000.00, 2),
('Community Disaster Orientation', 'Community-wide disaster preparedness orientation.', 'draft', '2025-11-16', '2025-11-30', 1, 'disaster_preparedness', 'Quezon City', 'Bagong Silangan MRF', 50000.00, 6),
('School-Based Fire Safety', 'Fire safety activities focused on schools.', 'draft', '2025-12-01', '2025-12-15', 1, 'fire_safety', 'Quezon City', 'Holy Spirit Elementary School', 30000.00, 3),
('Typhoon Preparedness Campaign', 'Campaign on typhoon preparedness and early warning.', 'draft', '2025-12-16', '2025-12-31', 1, 'typhoon', 'Quezon City', 'Evacuation Center', 55000.00, 5);














