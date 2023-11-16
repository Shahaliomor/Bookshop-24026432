<?php
session_start();
require_once('db.php');

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
        // Your existing logic for clearing the cart
    }

    // Redirect back to the cart page or any other page
    header('Location: cart.php');
    exit();
}

// Calculate total number of products and total price
$totalProducts = 0;
$totalPrice = 0;

foreach ($productDetails as $product) {
    $totalProducts += $_SESSION['cart'][$product['id']];
    $totalPrice += $product['price'] * $_SESSION['cart'][$product['id']];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
</head>
<body>
    <!-- HTML and form for displaying the cart -->

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
        <button type="submit" name="clear_cart">Clear Cart</button>
    </form>

    <!-- Link to proceed to checkout, available only when there are products in the cart -->
    <a href="checkout.php">Proceed to Checkout</a>
</body>
</html>
