<?php
/**
 * Quick script to insert sample content data
 */

declare(strict_types=1);

// Load database configuration
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'LGU';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName),
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Connected to database '{$dbName}'\n\n";
    
    // Make campaign_id nullable if it isn't already
    echo "→ Making campaign_id nullable...\n";
    try {
        $pdo->exec("ALTER TABLE content_items MODIFY COLUMN campaign_id INT UNSIGNED NULL");
        echo "  ✓ campaign_id is now nullable\n";
    } catch (PDOException $e) {
        echo "  ⊙ campaign_id already nullable or error: " . $e->getMessage() . "\n";
    }
    
    // Check approval_status enum
    $statusCheck = $pdo->query("SHOW COLUMNS FROM content_items WHERE Field = 'approval_status'")->fetch(PDO::FETCH_ASSOC);
    $statusEnum = $statusCheck['Type'] ?? '';
    echo "→ Approval status enum: {$statusEnum}\n";
    
    $hasDraft = strpos($statusEnum, 'draft') !== false;
    $hasRejected = strpos($statusEnum, 'rejected') !== false;
    
    // Check which audience column exists
    $columns = $pdo->query("SHOW COLUMNS FROM content_items LIKE 'intended_audience%'")->fetchAll(PDO::FETCH_COLUMN);
    $audienceColumn = !empty($columns) ? $columns[0] : 'intended_audience';
    
    echo "→ Using audience column: {$audienceColumn}\n\n";
    
    // Delete existing sample data (IDs 101-112) to start fresh
    echo "→ Cleaning up old sample data...\n";
    $pdo->exec("DELETE FROM attachments WHERE content_item_id BETWEEN 101 AND 112");
    $pdo->exec("DELETE FROM content_items WHERE id BETWEEN 101 AND 112");
    echo "  ✓ Cleaned up\n\n";
    
    // Sample content items - using NULL for campaign_id (standalone content)
    $contentItems = [
        [101, NULL, 'Fire Safety Tips for Households', 'Essential fire safety tips including smoke detector maintenance, kitchen safety, and emergency contact numbers.', 'image', 'fire', 'households, residential areas', 'barangay-created', 'approved', 'uploads/content_repository/fire_safety.jpg', 1, 'public'],
        [102, NULL, 'Flood Preparedness Checklist', 'Comprehensive checklist for flood preparedness including evacuation planning and emergency kit preparation.', 'file', 'flood', 'flood-prone areas, households', 'inspection-based', 'approved', 'uploads/content_repository/flood_checklist.pdf', 2, 'public'],
        [103, NULL, 'Earthquake Safety Video', 'Educational video demonstrating proper earthquake response procedures.', 'video', 'earthquake', 'general public, schools', 'training-based', 'approved', 'uploads/content_repository/earthquake_video.mp4', 1, 'public'],
        [104, NULL, 'Typhoon Preparedness Infographic', 'Visual infographic showing typhoon preparedness steps and evacuation routes.', 'image', 'typhoon', 'coastal areas, general public', 'barangay-created', 'approved', 'uploads/content_repository/typhoon_infographic.png', 2, 'public'],
        [105, NULL, 'Health Safety Tips for Senior Citizens', 'Poster focusing on health safety measures for senior citizens.', 'image', 'health', 'senior citizens, elderly', 'barangay-created', 'approved', 'uploads/content_repository/health_seniors.jpg', 1, 'public'],
        [106, NULL, 'Fire Safety for Schools', 'Fire safety guidelines specifically designed for school environments.', 'file', 'fire', 'schools, students, teachers', 'training-based', 'pending', 'uploads/content_repository/fire_schools.pdf', 2, 'internal'],
        [107, NULL, 'Dengue Prevention Infographic', 'Infographic showing dengue prevention measures.', 'image', 'health', 'general public, households', 'inspection-based', 'pending', 'uploads/content_repository/dengue.png', 1, 'public'],
        [108, NULL, 'Earthquake Preparedness for High-Rise Buildings', 'Guidelines for earthquake preparedness in high-rise buildings.', 'file', 'earthquake', 'residential buildings', 'inspection-based', $hasDraft ? 'draft' : 'pending', 'uploads/content_repository/earthquake_highrise.pdf', 2, 'internal'],
        [109, NULL, 'Youth Safety Awareness Video', 'Video targeting youth on various safety topics.', 'video', 'health', 'youth, teenagers, students', 'barangay-created', $hasDraft ? 'draft' : 'pending', 'uploads/content_repository/youth_video.mp4', 1, 'internal'],
        [110, NULL, 'Flood Safety Poster (Rejected)', 'Initial version that was rejected due to outdated information.', 'image', 'flood', 'general public', 'barangay-created', $hasRejected ? 'rejected' : 'pending', 'uploads/content_repository/flood_rejected.jpg', 2, 'internal'],
        [111, NULL, 'Emergency Contact Numbers Quick Reference', 'Quick reference card with all emergency contact numbers.', 'image', 'emergency', 'general public, households', 'barangay-created', 'approved', 'uploads/content_repository/emergency_contacts.jpg', 1, 'public'],
        [112, NULL, 'First Aid Basics Video', 'Basic first aid procedures video covering CPR and wound care.', 'video', 'health', 'general public, community volunteers', 'training-based', 'approved', 'uploads/content_repository/first_aid.mp4', 2, 'public'],
    ];
    
    echo "→ Inserting sample content items...\n";
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO content_items (
            id, campaign_id, title, body, content_type, hazard_category, 
            {$audienceColumn}, source, approval_status, file_path, created_by, visibility
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $inserted = 0;
    foreach ($contentItems as $item) {
        try {
            $stmt->execute($item);
            $inserted++;
            echo "  ✓ Inserted: {$item[2]}\n";
        } catch (PDOException $e) {
            echo "  ✗ Error for '{$item[2]}': " . $e->getMessage() . "\n";
        }
    }
    
    // Insert attachments
    echo "\n→ Inserting attachments...\n";
    $attachments = [
        [101, 'uploads/content_repository/fire_safety.jpg', 'image/jpeg', 245760],
        [102, 'uploads/content_repository/flood_checklist.pdf', 'application/pdf', 512000],
        [103, 'uploads/content_repository/earthquake_video.mp4', 'video/mp4', 5242880],
        [104, 'uploads/content_repository/typhoon_infographic.png', 'image/png', 384000],
        [105, 'uploads/content_repository/health_seniors.jpg', 'image/jpeg', 198656],
        [106, 'uploads/content_repository/fire_schools.pdf', 'application/pdf', 456704],
        [107, 'uploads/content_repository/dengue.png', 'image/png', 320000],
        [108, 'uploads/content_repository/earthquake_highrise.pdf', 'application/pdf', 678912],
        [109, 'uploads/content_repository/youth_video.mp4', 'video/mp4', 8388608],
        [110, 'uploads/content_repository/flood_rejected.jpg', 'image/jpeg', 215040],
        [111, 'uploads/content_repository/emergency_contacts.jpg', 'image/jpeg', 153600],
        [112, 'uploads/content_repository/first_aid.mp4', 'video/mp4', 6291456],
    ];
    
    $attachStmt = $pdo->prepare('INSERT INTO attachments (content_item_id, file_path, mime_type, file_size) VALUES (?, ?, ?, ?)');
    $attachCount = 0;
    foreach ($attachments as $att) {
        try {
            $attachStmt->execute($att);
            $attachCount++;
        } catch (PDOException $e) {
            // Ignore duplicates
        }
    }
    
    $pdo->commit();
    
    echo "\n✓ Successfully inserted {$inserted} content items and {$attachCount} attachments!\n";
    echo "\n✓ Content Repository now has sample data!\n";
    echo "  → Refresh your Content module page to see the data.\n\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("✗ Error: " . $e->getMessage() . "\n");
}
