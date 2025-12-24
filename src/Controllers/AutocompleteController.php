<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

class AutocompleteController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
    }

    /**
     * Get campaign title suggestions
     */
    public function campaignTitles(?array $user, array $params = []): array
    {
        $query = $_GET['q'] ?? '';
        if (strlen($query) < 2) {
            return ['data' => []];
        }

        $stmt = $this->pdo->prepare('
            SELECT DISTINCT title 
            FROM campaigns 
            WHERE title LIKE :query 
            ORDER BY title ASC 
            LIMIT 10
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $results = $stmt->fetchAll();

        return ['data' => array_column($results, 'title')];
    }

    /**
     * Get Barangay suggestions (Quezon City only)
     */
    public function barangays(?array $user, array $params = []): array
    {
        $query = $_GET['q'] ?? '';
        
        // Real Quezon City Barangays (16 official barangays - Quezon City ONLY)
        $realQuezonCityBarangays = [
            'Barangay Batasan Hills',
            'Barangay Commonwealth',
            'Barangay Holy Spirit',
            'Barangay Payatas',
            'Barangay Bagong Silangan',
            'Barangay Tandang Sora',
            'Barangay UP Campus',
            'Barangay Diliman',
            'Barangay Matandang Balara',
            'Barangay Loyola Heights',
            'Barangay Cubao',
            'Barangay Kamuning',
            'Barangay Project 6',
            'Barangay Project 8',
            'Barangay Fairview',
            'Barangay Nagkaisang Nayon',
        ];
        
        // Real Quezon City Barangay Target Zones (sub-areas for planning and deployment)
        $realBarangayZones = [
            'Sitio Veterans Village (Batasan Hills)',
            'IBP Road Area (Batasan Hills)',
            'Don Antonio Heights (Commonwealth)',
            'Litex Area (Commonwealth)',
            'North Fairview Subdivision',
            'Fairview Center Mall Area',
            'UP Academic Oval Area',
            'Teachers Village East',
            'Teachers Village West',
            'Araneta City Cubao Area',
            'Kamias–E. Rodriguez Area',
            'Balara Filters Area',
            'Payatas A Proper',
            'Payatas B Proper',
            'Novaliches Proper',
            'Nagkaisang Nayon',
        ];
        
        // Combine both lists
        $allOptions = array_merge($realQuezonCityBarangays, $realBarangayZones);
        
        // If query is provided, filter by query
        if (strlen($query) >= 2) {
            $filtered = array_filter($allOptions, function($item) use ($query) {
                return stripos($item, $query) !== false;
            });
            return ['data' => array_values($filtered)];
        }
        
        // If no query or query too short, return all options
        return ['data' => $allOptions];
    }

    /**
     * Get location suggestions (Quezon City only)
     */
    public function locations(?array $user, array $params = []): array
    {
        $query = $_GET['q'] ?? '';
        if (strlen($query) < 2) {
            return ['data' => []];
        }

        // Get from campaigns.location field (Quezon City only)
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT location 
            FROM campaigns 
            WHERE location IS NOT NULL 
            AND location LIKE :query 
            AND (location LIKE "%Quezon City%" OR location LIKE "%QC%" OR location LIKE "Quezon City%")
            ORDER BY location ASC 
            LIMIT 20
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $locationResults = $stmt->fetchAll();

        // Also get from events.venue
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT venue 
            FROM events 
            WHERE venue IS NOT NULL 
            AND venue LIKE :query 
            ORDER BY venue ASC 
            LIMIT 10
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $venueResults = $stmt->fetchAll();

        // And from reference_locations lookup table (name + barangay)
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT name, barangay_name
            FROM reference_locations
            WHERE name LIKE :query
               OR barangay_name LIKE :query
            ORDER BY name ASC
            LIMIT 20
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $refResults = $stmt->fetchAll();

        $allLocations = [];

        foreach ($locationResults as $row) {
            if (!empty($row['location'])) {
                $allLocations[] = $row['location'];
            }
        }

        foreach ($venueResults as $row) {
            if (!empty($row['venue'])) {
                $allLocations[] = $row['venue'];
            }
        }

        foreach ($refResults as $row) {
            if (empty($row['name'])) {
                continue;
            }
            $label = $row['name'];
            if (!empty($row['barangay_name'])) {
                $label .= ' - ' . $row['barangay_name'];
            }
            $allLocations[] = $label;
        }

        // Remove duplicates, nulls, and empty strings
        $allLocations = array_filter(array_unique($allLocations));
        sort($allLocations);

        return ['data' => array_slice($allLocations, 0, 20)];
    }

    /**
     * Get staff suggestions (from users table)
     */
    public function staff(?array $user, array $params = []): array
    {
        $query = $_GET['q'] ?? '';
        if (strlen($query) < 2) {
            return ['data' => []];
        }

        // Get from users table (staff roles: Barangay Staff and Administrator)
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT u.name 
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.is_active = 1 
            AND (r.name LIKE "%Staff%" OR r.name LIKE "%Administrator%")
            AND u.name LIKE :query 
            ORDER BY u.name ASC 
            LIMIT 20
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $userResults = $stmt->fetchAll();

        // Also get from existing campaigns.assigned_staff JSON arrays
        $stmt = $this->pdo->prepare('
            SELECT assigned_staff 
            FROM campaigns 
            WHERE assigned_staff IS NOT NULL 
            AND assigned_staff LIKE :query
            LIMIT 50
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $staffResults = $stmt->fetchAll();

        // And from reference_staff lookup table
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT name, role
            FROM reference_staff
            WHERE name LIKE :query
               OR role LIKE :query
            ORDER BY name ASC
            LIMIT 20
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $refResults = $stmt->fetchAll();

        $allStaff = array_column($userResults, 'name');

        foreach ($staffResults as $row) {
            if ($row['assigned_staff']) {
                $staff = json_decode($row['assigned_staff'], true);
                if (is_array($staff)) {
                    foreach ($staff as $member) {
                        if (is_string($member) && stripos($member, $query) !== false && !in_array($member, $allStaff)) {
                            $allStaff[] = $member;
                        }
                    }
                }
            }
        }

        foreach ($refResults as $row) {
            if (empty($row['name'])) {
                continue;
            }
            $label = $row['name'];
            if (!empty($row['role'])) {
                $label .= ' - ' . $row['role'];
            }
            if (!in_array($label, $allStaff, true)) {
                $allStaff[] = $label;
            }
        }

        // Remove duplicates and sort
        $allStaff = array_unique($allStaff);
        sort($allStaff);

        return ['data' => array_slice($allStaff, 0, 20)];
    }

    /**
     * Get materials suggestions (from content_items)
     */
    public function materials(?array $user, array $params = []): array
    {
        $query = $_GET['q'] ?? '';
        if (strlen($query) < 2) {
            return ['data' => []];
        }

        // Get from content_items.title
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT ci.title, ci.content_type, a.file_path
            FROM content_items ci
            LEFT JOIN attachments a ON a.content_item_id = ci.id
            WHERE ci.title LIKE :query 
            ORDER BY ci.created_at DESC 
            LIMIT 20
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $contentResults = $stmt->fetchAll();

        // Also get from existing campaigns.materials_json
        $stmt = $this->pdo->prepare('
            SELECT materials_json 
            FROM campaigns 
            WHERE materials_json IS NOT NULL 
            AND materials_json LIKE :query
            LIMIT 50
        ');
        $stmt->execute(['query' => '%' . $query . '%']);
        $materialsResults = $stmt->fetchAll();

        $allMaterials = [];
        foreach ($contentResults as $row) {
            $allMaterials[] = $row['title'];
        }

        foreach ($materialsResults as $row) {
            if ($row['materials_json']) {
                $materials = json_decode($row['materials_json'], true);
                if (is_array($materials)) {
                    foreach ($materials as $key => $value) {
                        $materialName = $key . (is_numeric($value) ? ' (' . $value . ')' : '');
                        if (stripos($materialName, $query) !== false && !in_array($materialName, $allMaterials)) {
                            $allMaterials[] = $materialName;
                        }
                    }
                }
            }
        }

        // Remove duplicates and sort
        $allMaterials = array_unique($allMaterials);
        sort($allMaterials);

        return ['data' => array_slice($allMaterials, 0, 20)];
    }
}












