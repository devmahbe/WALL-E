<?php
session_start();
require_once 'includes/db.php';

if (isset($_GET['id'])) {
    // Sanitize and validate the ID
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($id === false) {
        $_SESSION['error'] = "Invalid ID format";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("DELETE FROM sensor_data WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Record deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting record: " . $conn->error;
        }
        
        $stmt->close();
    }
} else {
    $_SESSION['error'] = "No ID specified";
}

$conn->close();

// Redirect back to the data view page
header("Location: view_data.php");
exit();
?>
