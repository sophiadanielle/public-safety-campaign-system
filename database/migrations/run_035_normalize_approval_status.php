<?php
/**
 * Migration: Normalize approval_status values to lowercase without underscores
 * This ensures consistency across the database
 */

require_once __DIR__ . '/../../src/Config/db_connect.php';

try {
    echo "Starting approval_status normalization...\n";
    
    // Normalize pending_review to pending_review (keep underscore for consistency)
    // But ensure all values are lowercase
    $updates = [
        "UPDATE campaign_department_content_items SET approval_status = 'draft' WHERE LOWER(approval_status) = 'draft'",
        "UPDATE campaign_department_content_items SET approval_status = 'pending_review' WHERE LOWER(approval_status) IN ('pending_review', 'pending review', 'under_review', 'under review')",
        "UPDATE campaign_department_content_items SET approval_status = 'approved' WHERE LOWER(approval_status) = 'approved'",
        "UPDATE campaign_department_content_items SET approval_status = 'rejected' WHERE LOWER(approval_status) = 'rejected'",
        "UPDATE campaign_department_content_items SET approval_status = 'archived' WHERE LOWER(approval_status) = 'archived'",
    ];
    
    $totalUpdated = 0;
    foreach ($updates as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rowCount = $stmt->rowCount();
        $totalUpdated += $rowCount;
        if ($rowCount > 0) {
            echo "✓ Updated $rowCount rows: $sql\n";
        }
    }
    
    // Verify the results
    $stmt = $pdo->query("SELECT approval_status, COUNT(*) as count FROM campaign_department_content_items GROUP BY approval_status ORDER BY approval_status");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== Current approval_status distribution ===\n";
    foreach ($results as $row) {
        echo "  {$row['approval_status']}: {$row['count']} items\n";
    }
    
    echo "\n✓ Migration completed successfully! Total rows updated: $totalUpdated\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
