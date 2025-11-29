<?php
include_once 'config.php';

try {
    echo "Updating users table...<br>";
    
    // Add missing columns
    $alter_queries = [
        "ALTER TABLE users ADD COLUMN phone VARCHAR(20)",
        "ALTER TABLE users ADD COLUMN institution VARCHAR(255)",
        "ALTER TABLE users ADD COLUMN approved_at TIMESTAMP NULL",
        "ALTER TABLE users ADD COLUMN approved_by INT NULL"
    ];
    
    foreach ($alter_queries as $query) {
        try {
            $pdo->exec($query);
            echo "✓ Added column<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "Column already exists<br>";
            } else {
                echo "Error: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Update status from 'active' to 'approved'
    $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE status = 'active'");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "✓ Updated $updated users from 'active' to 'approved' status<br>";
    
    // Modify status column to use correct ENUM values
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        echo "✓ Updated status column ENUM values<br>";
    } catch (PDOException $e) {
        echo "Status column already updated: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>✅ Database update completed successfully!<br>";
    echo "<a href='login.php' class='btn btn-primary mt-3'>Go to Login</a>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>