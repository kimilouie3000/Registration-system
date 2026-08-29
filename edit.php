<?php

require_once "db.php";

$error = "";

/*
 * Get user ID from the URL.
 */
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {

    header("Location: index.php?error=Invalid user ID");
    exit;
}


/*
 * Retrieve the existing user.
 */
$stmt = $conn->prepare(
    "SELECT id, name, email, password, profile_picture
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
 * Process the update.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    /*
     * Basic validation.
     */
    if ($name === "" || $email === "") {

        $error = "Name and email are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password !== "" && strlen($password) < 6) {

        $error = "New password must be at least 6 characters.";

    } else {

        /*
         * Check if another user uses this email.
         */
        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ? AND id != ?"
        );

        $check->bind_param("si", $email, $id);
        $check->execute();

        $emailResult = $check->get_result();


        if ($emailResult->num_rows > 0) {

            $error = "That email address is already being used.";

        } else {

            $newProfilePicture = $user["profile_picture"];
            $uploadedNewPicture = false;
            $newPicturePath = "";


            /*
             * Check if the user selected a new picture.
             */
            if (
                isset($_FILES["profile_picture"]) &&
                $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                $file = $_FILES["profile_picture"];

                /*
                 * Maximum size: 2 MB
                 */
                $maxFileSize = 2 * 1024 * 1024;


                if ($file["error"] !== UPLOAD_ERR_OK) {

                    $error = "There was a problem uploading the file.";

                } elseif ($file["size"] > $maxFileSize) {

                    $error = "Profile picture must be 2 MB or smaller.";

                } else {

                    /*
                     * Allowed image types.
                     */
                    $allowedTypes = [
                        "image/jpeg" => "jpg",
                        "image/png"  => "png",
                        "image/gif"  => "gif",
                        "image/webp" => "webp"
                    ];


                    /*
                     * Check actual MIME type.
                     */
                    $finfo = new finfo(FILEINFO_MIME_TYPE);

                    $mimeType = $finfo->file(
                        $file["tmp_name"]
                    );


                    if (!isset($allowedTypes[$mimeType])) {

                        $error =
                            "Only JPG, PNG, GIF, and WEBP images are allowed.";

                    } else {

                        /*
                         * Create uploads folder if necessary.
                         */
                        $uploadDirectory =
                            __DIR__ . "/uploads/";

                        if (!is_dir($uploadDirectory)) {
                            mkdir($uploadDirectory, 0755, true);
                        }


                        /*
                         * Generate a unique filename.
                         */
                        $newFileName =
                            bin2hex(random_bytes(16))
                            . "."
                            . $allowedTypes[$mimeType];


                        $newPicturePath =
                            $uploadDirectory . $newFileName;


                        /*
                         * Move uploaded image.
                         */
                        if (!move_uploaded_file(
                            $file["tmp_name"],
                            $newPicturePath
                        )) {

                            $error =
                                "Failed to save the profile picture.";

                        } else {

                            $newProfilePicture =
                                $newFileName;

                            $uploadedNewPicture = true;
                        }
                    }
                }
            }


            /*
             * Only continue with database update
             * if there is no upload error.
             */
            if ($error === "") {

                /*
                 * Update password if a new one was provided.
                 */
                if ($password !== "") {

                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $update = $conn->prepare(
                        "UPDATE users
                         SET name = ?,
                             email = ?,
                             password = ?,
                             profile_picture = ?
                         WHERE id = ?"
                    );

                    $update->bind_param(
                        "ssssi",
                        $name,
                        $email,
                        $hashedPassword,
                        $newProfilePicture,
                        $id
                    );

                } else {

                    /*
                     * Keep the existing password.
                     */
                    $update = $conn->prepare(
                        "UPDATE users
                         SET name = ?,
                             email = ?,
                             profile_picture = ?
                         WHERE id = ?"
                    );

                    $update->bind_param(
                        "sssi",
                        $name,
                        $email,
                        $newProfilePicture,
                        $id
                    );
                }


                /*
                 * Execute database update.
                 */
                if ($update->execute()) {

                    /*
                     * If a new picture was uploaded,
                     * remove the old picture.
                     */
                    if (
                        $uploadedNewPicture &&
                        !empty($user["profile_picture"])
                    ) {

                        $oldPicture =
                            __DIR__
                            . "/uploads/"
                            . $user["profile_picture"];

                        if (is_file($oldPicture)) {
                            unlink($oldPicture);
                        }
                    }


                    header(
                        "Location: index.php?success=User updated successfully"
                    );

                    exit;

                } else {

                    /*
                     * If database update failed,
                     * remove the newly uploaded image.
                     */
                    if (
                        $uploadedNewPicture &&
                        is_file($newPicturePath)
                    ) {

                        unlink($newPicturePath);
                    }

                    $error = "Failed to update user.";
                }

                $update->close();
            }
        }

        $check->close();
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

    <title>Edit User</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

    <h1>Edit User</h1>


    <?php if ($error !== ""): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($user["profile_picture"])): ?>

        <p>
            Current Profile Picture:
        </p>

        <img
            class="profile-preview"
            src="uploads/<?= htmlspecialchars($user["profile_picture"]) ?>"
            alt="Current Profile Picture"
        >

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <label for="name">
            Name
        </label>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            required
            value="<?= htmlspecialchars($user["name"]) ?>"
        >


        <label for="email">
            Email
        </label>

        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            required
            value="<?= htmlspecialchars($user["email"]) ?>"
        >


        <label for="password">
            New Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            minlength="6"
        >

        <small>
            Leave blank to keep the current password.
        </small>


        <label for="profile_picture">
            New Profile Picture
        </label>

        <input
            type="file"
            id="profile_picture"
            name="profile_picture"
            accept=".jpg,.jpeg,.png,.gif,.webp"
        >

        <small>
            Leave blank to keep the current picture.
            JPG, PNG, GIF, or WEBP. Maximum size: 2 MB.
        </small>


        <button type="submit">
            Update User
        </button>

    </form>


    <p>
        <a href="index.php">
            Back to User List
        </a>
    </p>

</div>

</body>

</html>