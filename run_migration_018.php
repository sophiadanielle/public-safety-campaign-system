<?php
/**
 * Migration Runner - Fix sector_type ENUM
 * Run this file once to update the database schema
 */

require_once __DIR__ . '/config/db_connect.php';

try {
    echo "Starting migration 018: Fix sector_type ENUM...\n";
    
    // Read the migration file
    $migrationSQL = file_get_contents(__DIR__ . '/migrations/018_fix_sector_type_enum.sql');
    
    // Remove the USE LGU; line as we're already connected to the database
    $migrationSQL = str_replace('USE LGU;', '', $migrationSQL);
    
    // Execute the migration
    $pdo->exec($migrationSQL);
    
    echo "✓ Migration completed successfully!\n";
    echo "The sector_type column now supports all 7 values:\n";
    echo "  - Households\n";
    echo "  - Youth\n";
    echo "  - Senior Citizens\n";
    echo "  - Schools\n";
    echo "  - NGOs\n";
    echo "  - Person with Disabilities\n";
    echo "  - Pregnant Women\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
