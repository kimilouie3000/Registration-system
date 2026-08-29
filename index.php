<?php

require_once "db.php";

$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $searchValue = "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT id, name, email, profile_picture, created_at
         FROM users
         WHERE name LIKE ? OR email LIKE ?
         ORDER BY id DESC"
    );

    $stmt->bind_param("ss", $searchValue, $searchValue);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query(
        "SELECT id, name, email, profile_picture, created_at
         FROM users
         ORDER BY id DESC"
    );
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container wide">

    <div class="header">

        <div>
            <h1>User Management System</h1>
            <p>Manage registered users.</p>
        </div>

        <div>
            <a class="button" href="create.php">Add User</a>
            <a class="button secondary" href="login.php">Login</a>
        </div>

    </div>

    <?php if (isset($_GET["success"])): ?>

        <div class="alert success">
            <?= htmlspecialchars($_GET["success"]) ?>
        </div>

    <?php endif; ?>

    <?php if (isset($_GET["error"])): ?>

        <div class="alert error">
            <?= htmlspecialchars($_GET["error"]) ?>
        </div>

    <?php endif; ?>

    <form method="GET" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search by name or email"
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">Search</button>

        <?php if ($search !== ""): ?>

            <a class="button secondary" href="index.php">
                Clear
            </a>

        <?php endif; ?>

    </form>

    <div class="table-container">

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if ($result->num_rows > 0): ?>

                <?php while ($user = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($user["id"]) ?>
                        </td>

                        <td>

                            <?php if (!empty($user["profile_picture"])): ?>

                                <img
                                    class="profile-small"
                                    src="uploads/<?= htmlspecialchars($user["profile_picture"]) ?>"
                                    alt="Profile Picture"
                                >

                            <?php else: ?>

                                No image

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($user["name"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($user["email"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($user["created_at"]) ?>
                        </td>

                        <td class="actions">

                            <a
                                class="button small"
                                href="edit.php?id=<?= urlencode($user["id"]) ?>"
                            >
                                Edit
                            </a>

                            <a
                                class="button small danger"
                                href="delete.php?id=<?= urlencode($user["id"]) ?>"
                                onclick="return confirm('Are you sure you want to delete this user?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">
                        No users found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>