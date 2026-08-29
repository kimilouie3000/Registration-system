<?php

session_start();

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Email and password are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /*
         * Find the user by email.
         */
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, profile_picture
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        /*
         * Check the password against the
         * hashed password stored in MySQL.
         */
        if ($user && password_verify($password, $user["password"])) {

            /*
             * Create a new session ID.
             */
            session_regenerate_id(true);

            /*
             * Store the user's information in the session.
             */
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];

            /*
             * Send the user to the dashboard.
             */
            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Invalid email or password.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

    <h1>Login</h1>

    <?php if ($error !== ""): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="email">
            Email
        </label>

        <input
            type="email"
            id="email"
            name="email"
            required
            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
        >

        <label for="password">
            Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

    <p>
        Don't have an account?
        <a href="create.php">Register here</a>
    </p>

    <p>
        <a href="index.php">Back to User List</a>
    </p>

</div>

</body>

</html>