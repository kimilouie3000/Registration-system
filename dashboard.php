<?php

session_start();

require_once "db.php";

/*
 * Only logged-in users can access this page.
 */
if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


/*
 * Get the logged-in user's ID.
 */
$userId = $_SESSION["user_id"];


/*
 * Retrieve the latest user information
 * from the database.
 */
$stmt = $conn->prepare(
    "SELECT id, name, email, profile_picture, created_at
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();


/*
 * If the account no longer exists,
 * destroy the session and return to login.
 */
if (!$user) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
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

    <title>Dashboard</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container dashboard">

    <h1>Dashboard</h1>

    <p>
        Welcome,
        <strong><?= htmlspecialchars($user["name"]) ?></strong>!
    </p>


    <?php if (!empty($user["profile_picture"])): ?>

        <img
            class="profile-large"
            src="uploads/<?= htmlspecialchars($user["profile_picture"]) ?>"
            alt="Profile Picture"
        >

    <?php endif; ?>


    <div class="profile-info">

        <p>
            <strong>ID:</strong>
            <?= htmlspecialchars($user["id"]) ?>
        </p>

        <p>
            <strong>Name:</strong>
            <?= htmlspecialchars($user["name"]) ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?= htmlspecialchars($user["email"]) ?>
        </p>

        <p>
            <strong>Account Created:</strong>
            <?= htmlspecialchars($user["created_at"]) ?>
        </p>

    </div>


    <div class="dashboard-actions">

        <a
            class="button"
            href="edit.php?id=<?= urlencode($user["id"]) ?>"
        >
            Edit Profile
        </a>

        <a
            class="button secondary"
            href="index.php"
        >
            User List
        </a>

        <a
            class="button danger"
            href="logout.php"
        >
            Logout
        </a>

    </div>

</div>

</body>

</html>