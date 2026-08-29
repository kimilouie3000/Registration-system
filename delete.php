<?php

require_once "db.php";

/*
 * Get the user ID from the URL.
 * Example:
 * delete.php?id=1
 */
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php?error=Invalid user ID");
    exit;
}

/*
 * Check if the user exists and get the profile picture.
 * We need the filename so we can remove the image too.
 */
$stmt = $conn->prepare(
    "SELECT profile_picture
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    header("Location: index.php?error=User not found");
    exit;
}

/*
 * Delete the user from the database.
 */
$stmt = $conn->prepare(
    "DELETE FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    /*
     * Delete the user's profile picture
     * if one exists.
     */
    if (!empty($user["profile_picture"])) {

        $file = __DIR__ . "/uploads/" . $user["profile_picture"];

        if (is_file($file)) {
            unlink($file);
        }
    }

    header(
        "Location: index.php?success=User deleted successfully"
    );

    exit;

} else {

    header(
        "Location: index.php?error=Failed to delete user"
    );

    exit;
}

$stmt->close();

?>