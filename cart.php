<?php
session_start();
require_once('db.php');

// Initialize original product availability tracking
if (!isset($_SESSION['original_availability'])) {
    $_SESSION['original_availability'] = [];

    try {
        $stmt = $con->query("SELECT id, availability FROM products");
        $productsAvailability = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productsAvailability as $product) {
            $_SESSION['original_availability'][$product['id']] = $product['availability'];
        }
    } catch (PDOException $e) {
        die("Error fetching product availability: " . $e->getMessage());
    }
}

// Fetch product details based on the product IDs in the cart
$productDetails = [];
if (!empty($_SESSION['cart'])) {
    $placeholders = rtrim(str_repeat('?,', count($_SESSION['cart'])), ',');
    $sql = "SELECT id, name, price, availability FROM products WHERE id IN ($placeholders)";
    $stmt = $con->prepare($sql);
    $stmt->execute(array_values($_SESSION['cart']));    
    $productDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle updating the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $newQuantities = $_POST['quantity'];

        foreach ($newQuantities as $productId => $newQuantity) {
            // Ensure the new quantity is a positive integer
            $newQuantity = max(0, intval($newQuantity));

            // If the new quantity is 0, remove the product from the cart
            if ($newQuantity === 0) {
                $key = array_search($productId, $_SESSION['cart']);
                if ($key !== false) {
                    unset($_SESSION['cart'][$key]);
                }
            } else {
                // Update the quantity in the cart
                $_SESSION['cart'][$productId] = $newQuantity;
            }
        }
    } elseif (isset($_POST['clear_cart'])) {
        // Restore original product availability for all products
        foreach ($_SESSION['original_availability'] as $productId => $originalAvailability) {
            $stmt = $con->prepare("UPDATE products SET availability = ? WHERE id = ?");
            $stmt->execute([$originalAvailability, $productId]);
        }

        // Clear the cart
        $_SESSION['cart'] = [];

        // Display a success message
        $message = "Cart cleared!";
    }

    // Redirect back to the cart page or any other page
    header('Location: cart.php');
    exit();
}

// Calculate total number of products and total price
$totalProducts = 0;
$totalPrice = 0;

foreach ($productDetails as $product) {
    $productId = $product['id'];

    // Check if the product ID exists in the cart before accessing it
    if (isset($_SESSION['cart'][$productId])) {
        $totalProducts += $_SESSION['cart'][$productId];
        $totalPrice += $product['price'] * $_SESSION['cart'][$productId];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Cart</title>
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
            <li><a href="cart.php">Cart (<?php echo $totalProducts; ?>)</a></li>
        </ul>
    </nav>
    
    <h2>Your Cart</h2>

    <?php if ($totalProducts > 0): ?>
        <form action="cart.php" method="post">
            <ul>
                <?php foreach ($productDetails as $product): ?>
                    <li>
                        Product: <?php echo $product['name']; ?> -
                        Price: $<?php echo $product['price']; ?> -
                        Quantity: 
                        <input type="number" name="quantity[<?php echo $product['id']; ?>]" value="<?php echo $_SESSION['cart'][$product['id']]; ?>" min="0">
                    </li>
                <?php endforeach; ?>
            </ul>

            <p>Total Products: <?php echo $totalProducts; ?></p>
            <p>Total Price: $<?php echo $totalPrice; ?></p>

            <button type="submit" name="update_cart">Update Cart</button>
            <!-- Add the Clear Cart button -->
            <button type="submit" name="clear_cart">Clear Cart</button>
        </form>
        <!-- Link to proceed to checkout, available only when there are products in the cart -->
        <a href="checkout.php">Proceed to Checkout</a>

    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>
</body>
</html>
