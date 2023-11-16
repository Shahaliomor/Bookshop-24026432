<?php
session_start();
require_once('db.php');

// Fetch and display products
try {
    $stmt = $con->query("SELECT * FROM products");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

// Initialize product availability tracking
if (!isset($_SESSION['original_availability'])) {
    $_SESSION['original_availability'] = [];

    foreach ($products as $product) {
        $_SESSION['original_availability'][$product['id']] = $product['availability'];
    }
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['add_to_cart'];

    // Check product availability
    $stmt = $con->prepare("SELECT availability, price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $productInfo = $stmt->fetch();

    if ($productInfo['availability'] > 0) {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Add product to cart
        $_SESSION['cart'][] = $product_id;

        // Update product availability
        $stmt = $con->prepare("UPDATE products SET availability = availability - 1 WHERE id = ?");
        $stmt->execute([$product_id]);

        // Display a success message
        $message = "Product added to cart!";
    } else {
        // Display a request message for out-of-stock products
        $message = "Product is out of stock. You can request it!";
    }
}

// Handle clearing the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    // Restore original product availability
    foreach ($_SESSION['cart'] as $productId) {
        $stmt = $con->prepare("UPDATE products SET availability = ? WHERE id = ?");
        $stmt->execute([$_SESSION['original_availability'][$productId], $productId]);
    }

    // Clear the cart
    $_SESSION['cart'] = [];

    // Display a success message
    $message = "Cart cleared!";
}

// Calculate total number of products and total price in the cart
$totalProducts = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$totalPrice = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productId) {
        $stmt = $con->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $productPrice = $stmt->fetchColumn();
        $totalPrice += $productPrice;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">


    <title>Bookshop</title>
</head>
<body>
    <h1>Welcome to the Bookshop!</h1>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
            <li><a href="cart.php">Cart (<?php echo $totalProducts; ?>)</a></li>
        </ul>
    </nav>

    <!-- Display product list here -->
    <h2>Product List</h2>
    <form action="index.php" method="post">
        <ul>
            <?php foreach ($products as $product): ?>
                <li>
                    <?php echo $product['name']; ?> -
                    <?php echo '$' . $product['price']; ?> -
                    <?php
                    if ($product['availability'] > 0) {
                        echo 'Available (' . $product['availability'] . ')';
                        ?>
                        <button type="submit" name="add_to_cart" value="<?php echo $product['id']; ?>">Add to Cart</button>
                    <?php } else {
                        echo 'Out of stock';
                        ?>
                        <button type="button" disabled>Request Product</button>
                    <?php } ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <!-- Add the Clear Cart button -->
        <button type="submit" name="clear_cart">Clear Cart</button>
    </form>

    <!-- Display cart info -->
    <div>
        <h3>Cart</h3>
        <p>Total Products: <?php echo $totalProducts; ?></p>
        <p>Total Price: $<?php echo $totalPrice; ?></p>
    </div>

    <!-- Display success or request message -->
    <?php if (isset($message)): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <?php
    // Check if the order is confirmed
    if (isset($_GET['order']) && $_GET['order'] === 'confirmed') {
        echo '<p>Your order has been confirmed. Thank you for shopping with us!</p>';
    }
    ?>

</body>
</html>
