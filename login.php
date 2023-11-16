<?php
// login.php
session_start();
require_once('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $_POST['username_email'];
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $con->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $stmt->bindParam(':username', $input);
    $stmt->bindParam(':email', $input);
    $stmt->execute();

    // Check row count after executing the query
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $hashed_password = $row['hashed_password'];
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role']; // Assuming 'role' is a column in your 'users' table

            // Redirect to index.php after successful login
            header("Location: index.php?login=success");
            exit();
        } else {
            // Incorrect password
            $error_message = "Incorrect password. Please try again.";
        }
    } else {
        // Username or email not found
        $error_message = "Invalid username or email.";
    }
}
?>

<!-- HTML part of login.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Login</title>
    <style>
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div id="content">
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
            <li><a href="cart.php">Cart</a></li>
        </ul>
    </nav>
        <h2>Login</h2>
        <?php
        if (isset($error_message)) {
            echo "<p class='error'>$error_message</p>";
        } elseif (isset($_GET['login']) && $_GET['login'] === 'success') {
            echo "<p style='color: green;'>Login successful!</p>";
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="username_email">Username or Email:</label>
            <input type="text" name="username_email" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            <br>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>
