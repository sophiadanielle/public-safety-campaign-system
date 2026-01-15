-- Fix: Insert audience segments with correct column names
-- This matches the actual table structure in lgu database

USE `lgu`;

INSERT IGNORE INTO `campaign_department_audience_segments` 
(id, segment_name, geographic_scope, location_reference, sector_type, risk_level, basis_of_segmentation, criteria) 
VALUES
(1, 'Residential Areas - High Risk', 'Barangay', 'Barangay 1-5', 'Households', 'High', 'Historical trend', '{"location": "Barangay 1-5", "risk_factors": ["dense_population", "old_buildings"]}'),
(2, 'School Communities', 'Barangay', 'All Schools', 'Schools', 'Medium', 'Attendance records', '{"type": "school", "age_group": "all"}'),
(3, 'Commercial Districts', 'Zone', 'Business Areas', 'Households', 'Medium', 'Inspection results', '{"type": "commercial", "business_size": "all"}'),
(4, 'Senior Citizens', 'Barangay', 'All Areas', 'Senior Citizens', 'High', 'Historical trend', '{"age_min": 60, "special_needs": true}');

