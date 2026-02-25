-- Fix sector_type ENUM to include all valid values
-- This fixes the SQLSTATE error when creating segments with "Person with Disabilities" or "Pregnant Women"

USE LGU;

-- Update sector_type ENUM to include all 7 valid values
ALTER TABLE `campaign_department_audience_segments`
    MODIFY COLUMN sector_type ENUM(
        'Households', 
        'Youth', 
        'Senior Citizens', 
        'Schools', 
        'NGOs', 
        'Person with Disabilities', 
        'Pregnant Women'
    ) NULL;
