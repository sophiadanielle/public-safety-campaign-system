-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 22, 2026 at 06:06 PM
-- Server version: 8.0.45-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `LGU`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaign_department_content_items`
--

CREATE TABLE `campaign_department_content_items` (
  `id` int UNSIGNED NOT NULL,
  `campaign_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `body` text,
  `content_type` enum('text','image','video','link','file','poster','guideline','infographic') NOT NULL DEFAULT 'text',
  `hazard_category` varchar(100) DEFAULT NULL,
  `intended_audience_segment` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `approval_status` varchar(50) DEFAULT 'draft',
  `version_number` int UNSIGNED NOT NULL DEFAULT '1',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approval_notes` text,
  `date_uploaded` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `visibility` enum('public','internal','restricted') NOT NULL DEFAULT 'public',
  `file_path` varchar(500) DEFAULT NULL,
  `file_reference` varchar(500) DEFAULT NULL,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `campaign_department_content_items`
--

INSERT INTO `campaign_department_content_items` (`id`, `campaign_id`, `title`, `body`, `content_type`, `hazard_category`, `intended_audience_segment`, `source`, `approval_status`, `version_number`, `approved_by`, `approval_notes`, `date_uploaded`, `visibility`, `file_path`, `file_reference`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Fire Safety Tips Poster', 'Essential fire safety tips for home', 'image', 'fire', 'general_public', 'Barangay Safety Office', 'approved', 1, NULL, NULL, '2026-01-08 12:49:57', 'public', '/uploads/posters/fire_safety_tips.jpg', NULL, 1, '2026-01-08 12:45:15', '2026-01-08 12:45:15'),
(2, 1, 'Fire Evacuation Plan', 'Step-by-step fire evacuation procedures', 'file', 'fire', 'residential', 'Fire Department', 'approved', 1, NULL, NULL, '2026-01-08 12:49:57', 'public', '/uploads/plans/evacuation_plan.pdf', NULL, 1, '2026-01-08 12:45:15', '2026-01-08 12:45:15'),
(3, 2, 'Earthquake Safety Video', 'Educational video on earthquake preparedness', 'video', 'earthquake', 'general_public', 'NDRRMC', 'pending', 1, NULL, NULL, '2026-01-08 12:49:57', 'public', '/uploads/videos/earthquake_safety.mp4', NULL, 1, '2026-01-08 12:45:15', '2026-01-08 12:45:15'),
(101, NULL, 'Fire Safety Tips for Households', 'Essential fire safety tips including smoke detector maintenance, kitchen safety, and emergency contact numbers. Designed for residential areas.', 'poster', 'fire', 'households, residential areas', 'barangay-created', 'approved', 2, 1, 'Approved for distribution. Updated version with new contact numbers.', '2025-01-15 10:30:00', 'public', 'uploads/content_repository/fire_safety_households_v2.jpg', 'uploads/content_repository/fire_safety_households_v2.jpg', 1, '2025-01-10 09:00:00', '2025-01-15 10:30:00'),
(102, NULL, 'Flood Preparedness Checklist', 'Comprehensive checklist for flood preparedness including evacuation planning, emergency kit preparation, and post-flood safety measures.', 'guideline', 'flood', 'flood-prone areas, households', 'inspection-based', 'approved', 1, 1, 'Based on recent flood risk assessments in low-lying barangays.', '2025-01-20 14:15:00', 'public', 'uploads/content_repository/flood_preparedness_checklist.pdf', 'uploads/content_repository/flood_preparedness_checklist.pdf', 2, '2025-01-20 14:15:00', '2025-01-20 14:15:00'),
(103, NULL, 'Earthquake Safety: Drop, Cover, and Hold On', 'Educational video demonstrating proper earthquake response procedures. Includes animated demonstrations and real-world examples.', 'video', 'earthquake', 'general public, schools, workplaces', 'training-based', 'approved', 1, 1, 'High-quality educational content. Suitable for all age groups.', '2025-01-25 11:00:00', 'public', 'uploads/content_repository/earthquake_safety_video.mp4', 'uploads/content_repository/earthquake_safety_video.mp4', 1, '2025-01-25 11:00:00', '2025-01-25 11:00:00'),
(104, NULL, 'Typhoon Preparedness: Before, During, and After', 'Visual infographic showing typhoon preparedness steps, evacuation routes, and emergency contacts. Easy to understand format.', 'infographic', 'typhoon', 'coastal areas, general public', 'barangay-created', 'approved', 1, 1, 'Clear and visually appealing. Ready for social media distribution.', '2025-02-01 09:30:00', 'public', 'uploads/content_repository/typhoon_preparedness_infographic.png', 'uploads/content_repository/typhoon_preparedness_infographic.png', 2, '2025-02-01 09:30:00', '2025-02-01 09:30:00'),
(105, NULL, 'Health Safety Tips for Senior Citizens', 'Poster focusing on health safety measures for senior citizens including medication management, fall prevention, and emergency contacts.', 'poster', 'health', 'senior citizens, elderly', 'barangay-created', 'approved', 1, 1, 'Targeted content for vulnerable population. Approved for distribution.', '2025-02-05 13:20:00', 'public', 'uploads/content_repository/health_safety_seniors.jpg', 'uploads/content_repository/health_safety_seniors.jpg', 1, '2025-02-05 13:20:00', '2025-02-05 13:20:00'),
(106, NULL, 'Fire Safety for Schools', 'Fire safety guidelines specifically designed for school environments. Includes evacuation procedures and fire drill protocols.', 'guideline', 'fire', 'schools, students, teachers', 'training-based', 'pending', 1, NULL, NULL, '2025-02-10 10:00:00', 'internal', 'uploads/content_repository/fire_safety_schools.pdf', 'uploads/content_repository/fire_safety_schools.pdf', 2, '2025-02-10 10:00:00', '2025-02-10 10:00:00'),
(107, NULL, 'Dengue Prevention Infographic', 'Infographic showing dengue prevention measures including mosquito breeding prevention and symptoms recognition.', 'infographic', 'health', 'general public, households', 'inspection-based', 'pending', 1, NULL, NULL, '2025-02-12 15:45:00', 'public', 'uploads/content_repository/dengue_prevention.png', 'uploads/content_repository/dengue_prevention.png', 1, '2025-02-12 15:45:00', '2025-02-12 15:45:00'),
(108, NULL, 'Earthquake Preparedness for High-Rise Buildings', 'Guidelines for earthquake preparedness in high-rise residential and commercial buildings. Currently being reviewed.', 'guideline', 'earthquake', 'residential buildings, commercial buildings', 'inspection-based', 'draft', 1, NULL, NULL, '2025-02-15 11:30:00', 'internal', 'uploads/content_repository/earthquake_highrise_draft.pdf', 'uploads/content_repository/earthquake_highrise_draft.pdf', 2, '2025-02-15 11:30:00', '2025-02-15 11:30:00'),
(109, NULL, 'Youth Safety Awareness Video', 'Video targeting youth on various safety topics including road safety, online safety, and emergency response.', 'video', 'health', 'youth, teenagers, students', 'barangay-created', 'draft', 1, NULL, NULL, '2025-02-18 09:15:00', 'internal', 'uploads/content_repository/youth_safety_video_draft.mp4', 'uploads/content_repository/youth_safety_video_draft.mp4', 1, '2025-02-18 09:15:00', '2025-02-18 09:15:00'),
(110, NULL, 'Flood Safety Poster (Rejected)', 'Initial version of flood safety poster that was rejected due to outdated information and unclear messaging.', 'poster', 'flood', 'general public', 'barangay-created', 'rejected', 1, 1, 'Rejected: Contains outdated contact numbers and unclear evacuation instructions. Please revise and resubmit.', '2025-01-28 14:00:00', 'internal', 'uploads/content_repository/flood_safety_rejected.jpg', 'uploads/content_repository/flood_safety_rejected.jpg', 2, '2025-01-28 14:00:00', '2025-01-30 10:00:00'),
(111, NULL, 'Emergency Contact Numbers Quick Reference', 'Quick reference card with all emergency contact numbers including fire, police, medical, and barangay hotlines.', 'poster', 'emergency', 'general public, households', 'barangay-created', 'approved', 1, 1, 'Essential reference material. Approved for wide distribution.', '2025-02-08 10:00:00', 'public', 'uploads/content_repository/emergency_contacts.jpg', 'uploads/content_repository/emergency_contacts.jpg', 1, '2025-02-08 10:00:00', '2025-02-08 10:00:00'),
(112, NULL, 'First Aid Basics Video', 'Basic first aid procedures video covering CPR, wound care, and common emergency responses.', 'video', 'health', 'general public, community volunteers', 'training-based', 'approved', 1, 1, 'Excellent training material. Suitable for community workshops.', '2025-02-14 16:30:00', 'public', 'uploads/content_repository/first_aid_basics.mp4', 'uploads/content_repository/first_aid_basics.mp4', 2, '2025-02-14 16:30:00', '2025-02-14 16:30:00'),
(113, 1, 'Fire Safety Poster', 'Poster for basic fire safety reminders.', 'image', 'fire', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/fire_safety_poster.jpg', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(114, 1, 'Flood Preparedness Checklist', 'Checklist for household flood preparedness.', 'file', 'flood', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/flood_preparedness_checklist.pdf', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(115, 1, 'Earthquake Drill Video', 'Video demonstrating earthquake drill procedures.', 'video', 'earthquake', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/earthquake_drill_video.mp4', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(116, 1, 'Emergency Go-Bag Guide', 'Guide on preparing emergency go-bags.', 'file', 'emergency', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/emergency_go_bag_guide.pdf', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(117, 1, 'Road Safety Infographic', 'Infographic about road and traffic safety.', 'image', 'traffic', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/road_safety_infographic.png', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(118, 1, 'Dengue Prevention Poster', 'Poster about dengue prevention measures.', 'image', 'health', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/dengue_prevention_poster.jpg', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(119, 1, 'Fire Drill Guide', 'Step-by-step fire drill guide.', 'file', 'fire', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/fire_drill_guide.pdf', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(120, 1, 'Typhoon Safety Video', 'Video on typhoon safety and preparedness.', 'video', 'typhoon', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/typhoon_safety_video.mp4', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(121, 1, 'Senior Citizen Safety Guide', 'Safety guide tailored for senior citizens.', 'file', 'senior', 'seniors', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/senior_citizen_safety_guide.pdf', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(122, 1, 'Youth Disaster Awareness Poster', 'Poster to raise disaster awareness among youth.', 'image', 'youth', 'youth', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/youth_disaster_awareness_poster.jpg', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(123, 1, 'Community Evacuation Map', 'Map showing community evacuation routes.', 'image', 'evacuation', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/community_evacuation_map.png', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(124, 1, 'Flood Risk Awareness Video', 'Video about flood risks and mitigation.', 'video', 'flood', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/flood_risk_awareness_video.mp4', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(125, 1, 'Fire Extinguisher Manual', 'Manual for using fire extinguishers.', 'file', 'equipment', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/fire_extinguisher_manual.pdf', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(126, 1, 'School Safety Checklist', 'Checklist for school-based safety checks.', 'file', 'school', 'school_community', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/school_safety_checklist.pdf', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(127, 1, 'Barangay Emergency Contacts', 'List of important barangay emergency contacts.', 'file', 'contacts', 'general_public', 'Seed Data', 'approved', 1, NULL, NULL, '2026-01-08 14:10:59', 'public', '/uploads/materials/barangay_emergency_contacts.pdf', NULL, 1, '2026-01-08 14:10:59', '2026-01-08 14:10:59'),
(128, 6, 'Earthquake Safety Video', 'test', 'poster', 'Earthquake', 'households', 'Inspection-based', 'pending_review', 1, NULL, 'Restored from archive - needs review', '2026-02-10 13:31:33', 'public', NULL, 'uploads/content_repository/content_698b33353106d1.56301201.png', 1, '2026-02-10 13:31:33', '2026-02-22 16:39:57'),
(129, 8, 'Dengue Prevention Infographic', NULL, 'infographic', 'Health', 'general public', 'Inspection-based', 'approved', 1, NULL, NULL, '2026-02-11 22:32:21', 'public', NULL, 'uploads/content_repository/content_698d03756f0114.75164076.png', 1, '2026-02-11 22:32:21', '2026-02-11 22:32:49'),
(130, 24, 'Fire Safety Poster', NULL, 'poster', 'Fire', 'general public', 'Barangay-created', 'draft', 1, NULL, NULL, '2026-02-12 04:18:25', 'public', NULL, 'uploads/content_repository/content_698d549105efd9.32213165.png', 1, '2026-02-12 04:18:25', '2026-02-12 04:18:25'),
(131, 24, 'Fire Safety Poster', NULL, 'poster', 'Fire', 'general public', 'Barangay-created', 'approved', 1, NULL, NULL, '2026-02-12 04:18:29', 'public', NULL, 'uploads/content_repository/content_698d5495311227.53412420.png', 1, '2026-02-12 04:18:29', '2026-02-12 04:19:00'),
(132, 32, 'Flood Preparedness Checklist', 'test', 'poster', 'Fire', 'residential areas', 'Training-based', 'pending_review', 1, NULL, 'Restored from archive - needs review', '2026-02-22 11:42:24', 'public', NULL, 'uploads/content_repository/content_699aeba0bfdec4.08303258.jpg', 6, '2026-02-22 11:42:24', '2026-02-22 16:40:14'),
(133, 31, 'Fire Safety Tips for Households', 'ok', 'poster', 'Fire', 'commercial districts', 'Inspection-based', 'archived', 1, NULL, NULL, '2026-02-22 11:55:59', 'internal', NULL, 'uploads/content_repository/content_699aeecf63e588.29826581.png', 6, '2026-02-22 11:55:59', '2026-02-22 12:32:30'),
(134, 25, 'Flood Safety Guidelines', 'test', 'poster', 'Earthquake', 'caregivers', 'Inspection-based', 'approved', 1, 6, 'Content rejected', '2026-02-22 17:18:06', 'public', NULL, 'uploads/content_repository/content_699b3a4e44ac11.89998567.jpg', 6, '2026-02-22 17:18:06', '2026-02-22 17:55:58'),
(135, 29, 'Flood Safety Guidelines', 'test', 'poster', 'Fire', 'flood-prone areas', 'Inspection-based', 'archived', 1, NULL, NULL, '2026-02-22 17:50:50', 'public', NULL, 'uploads/content_repository/content_699b41fa7d72c6.66669327.jpg', 6, '2026-02-22 17:50:50', '2026-02-22 17:57:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaign_department_content_items`
--
ALTER TABLE `campaign_department_content_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_content_creator` (`created_by`),
  ADD KEY `idx_content_approval` (`approval_status`),
  ADD KEY `idx_content_hazard` (`hazard_category`),
  ADD KEY `idx_content_campaign` (`campaign_id`),
  ADD KEY `fk_content_approver` (`approved_by`),
  ADD KEY `idx_content_items_type` (`content_type`),
  ADD KEY `idx_content_items_hazard` (`hazard_category`),
  ADD KEY `idx_content_items_status` (`approval_status`),
  ADD KEY `idx_content_items_audience` (`intended_audience_segment`(100));

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaign_department_content_items`
--
ALTER TABLE `campaign_department_content_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaign_department_content_items`
--
ALTER TABLE `campaign_department_content_items`
  ADD CONSTRAINT `fk_content_approver` FOREIGN KEY (`approved_by`) REFERENCES `campaign_department_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_content_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaign_department_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_content_creator` FOREIGN KEY (`created_by`) REFERENCES `campaign_department_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
