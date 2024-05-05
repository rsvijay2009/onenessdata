<?php
// Assuming you've included your database connection settings
include_once "database.php";

// Check if 'id' is provided
if (isset($_GET['id'])) {
    $itemId = $_GET['id'];

    // Prepare the DELETE statement to avoid SQL injection
    $stmt = $pdo->prepare("DELETE FROM your_table_name WHERE id = :id");
    $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);

    // Execute the deletion
    if ($stmt->execute()) {
        echo "Item deleted successfully.";
    } else {
        echo "Error deleting item.";
    }
} else {
    echo "No item ID provided.";
}
?>
