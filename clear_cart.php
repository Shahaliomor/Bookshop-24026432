<?php
session_start();
require_once('db.php');

// Restore product availability
if (isset($_SESSION['original_availability'])) {
    foreach ($_SESSION['original_availability'] as $product_id => $availability) {
        $stmt = $con->prepare("UPDATE products SET availability = ? WHERE id = ?");
        $stmt->execute([$availability, $product_id]);
    }

    // Clear the cart
    unset($_SESSION['cart']);
    unset($_SESSION['original_availability']);

    // Redirect back to the cart page or any other page
    header('Location: cart.php');
    exit();
}
?>
