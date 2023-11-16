<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle product management logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Add or update product
        $name = $_POST['name'];
        $price = $_POST['price'];
        $availability = $_POST['availability'];

        // Check if the product with the same name already exists
        $existingProductStmt = $con->prepare("SELECT * FROM products WHERE name = ?");
        $existingProductStmt->execute([$name]);
        $existingProduct = $existingProductStmt->fetch();

        if ($existingProduct) {
            // Update existing product
            $updateStmt = $con->prepare("UPDATE products SET price = ?, availability = ? WHERE id = ?");
            $updateStmt->execute([$price, $availability, $existingProduct['id']]);
        } else {
            // Insert new product
            $insertStmt = $con->prepare("INSERT INTO products (name, price, availability) VALUES (?, ?, ?)");
            $insertStmt->execute([$name, $price, $availability]);
        }
    } elseif (isset($_POST['delete_product'])) {
        // Delete product
        $product_id = $_POST['product_id'];
        $deleteStmt = $con->prepare("DELETE FROM products WHERE id = ?");
        $deleteStmt->execute([$product_id]);
    }
}

// Fetch products
$stmt = $con->query("SELECT * FROM products");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Admin</title>
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="admin.php">Admin</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
            <li><a href="cart.php">Cart</a></li>
        </ul>
    </nav>

    <h2>Admin Panel</h2>
    <p>Welcome, <?php echo $_SESSION['username']; ?>!</p>

    <!-- Add/Edit content form -->
    <h3>Add Product</h3>
    <form action="admin.php" method="post">
        <label for="name">Product Name:</label>
        <input type="text" name="name" required><br>

        <label for="price">Price:</label>
        <input type="text" name="price" required><br>

        <label for="availability">Availability:</label>
        <input type="text" name="availability" required><br>

        <button type="submit" name="add_product">Add Product</button>
    </form>

    <!-- Display product list -->
    <h3>Product List</h3>
    
    <ul>
        <?php foreach ($products as $product): ?>
            <li>
                <?php echo $product['name']; ?> -
                <?php echo '$' . $product['price']; ?> -
                <?php echo $product['availability'] > 0 ? 'Available (' . $product['availability'] . ')' : 'Out of stock'; ?> -
                <form action="admin.php" method="post" style="display: inline;">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" name="delete_product">Delete</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
