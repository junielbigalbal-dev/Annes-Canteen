<?php
/**
 * Maintenance script to clean up expired remember me tokens
 * This script should be run periodically (e.g., daily via cron job)
 */

require_once '../config/database.php';
require_once '../config/auth.php';

echo "Starting token cleanup...\n";

// Clean up expired tokens
$cleaned = cleanupExpiredTokens();

echo "Cleanup completed. Removed {$cleaned} expired tokens.\n";

// Optional: Clean up sessions that are too old
// This helps maintain database performance
try {
    $query = "DELETE FROM user_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $sessionCleaned = $stmt->affected_rows;
    
    echo "Cleaned {$sessionCleaned} old session records.\n";
} catch (Exception $e) {
    echo "Note: Session table may not exist or error occurred: " . $e->getMessage() . "\n";
}

echo "Maintenance completed successfully.\n";
?>
