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

// Handle the checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Perform the checkout logic
    // ...

    // Send a confirmation email
    $to = $_SESSION['user_email']; // Assuming you have the user's email stored in the session
    $subject = "Order Confirmation";
    $message = "Hi,\n\nYour order on Bookshop was confirmed. Thank you for your purchase!";
    $headers = "From: omoranam@gmail.com"; // Replace with your email address

    // Assuming you have configured your email settings (like SMTP) and have access to a mail server
    mail($to, $subject, $message, $headers);

    // Clear the cart after successful checkout
    $_SESSION['cart'] = [];

    // Redirect to the home page with a success message
    header('Location: index.php?order=confirmed');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">

    <title>Checkout</title>
</head>
<body>
    <h2>Checkout</h2>

    <?php if ($totalProducts > 0): ?>
        <form action="checkout.php" method="post">
            <ul>
                <?php foreach ($productDetails as $product): ?>
                    <li>
                        Product: <?php echo $product['name']; ?> -
                        Price: $<?php echo $product['price']; ?> -
                        Quantity: <?php echo $_SESSION['cart'][$product['id']]; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p>Total Products: <?php echo $totalProducts; ?></p>
            <p>Total Price: $<?php echo $totalPrice; ?></p>

            <button type="submit" name="checkout">Checkout</button>
        </form>
        
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>
</body>
</html>
