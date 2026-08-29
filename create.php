<?php

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    /*
     * Basic validation
     */
    if ($name === "" || $email === "" || $password === "") {

        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif (!isset($_FILES["profile_picture"]) ||
              $_FILES["profile_picture"]["error"] === UPLOAD_ERR_NO_FILE) {

        $error = "Please select a profile picture.";

    } else {

        /*
         * Check if email already exists.
         */
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "This email is already registered.";

        } else {

            $file = $_FILES["profile_picture"];

            /*
             * Maximum file size: 2 MB
             */
            $maxFileSize = 2 * 1024 * 1024;

            if ($file["error"] !== UPLOAD_ERR_OK) {

                $error = "There was a problem uploading the file.";

            } elseif ($file["size"] > $maxFileSize) {

                $error = "Profile picture must be 2 MB or smaller.";

            } else {

                /*
                 * Allowed MIME types.
                 */
                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/gif"  => "gif",
                    "image/webp" => "webp"
                ];

                /*
                 * Check the actual file type.
                 */
                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $mimeType = $finfo->file($file["tmp_name"]);

                if (!isset($allowedTypes[$mimeType])) {

                    $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";

                } else {

                    /*
                     * Make sure the uploads folder exists.
                     */
                    $uploadDirectory = __DIR__ . "/uploads/";

                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0755, true);
                    }

                    /*
                     * Generate a random filename.
                     *
                     * This prevents filename conflicts and avoids
                     * using the original filename directly.
                     */
                    $newFileName =
                        bin2hex(random_bytes(16))
                        . "."
                        . $allowedTypes[$mimeType];

                    $destination =
                        $uploadDirectory . $newFileName;

                    /*
                     * Move the uploaded file.
                     */
                    if (!move_uploaded_file(
                        $file["tmp_name"],
                        $destination
                    )) {

                        $error = "Failed to save the profile picture.";

                    } else {

                        /*
                         * Hash the password.
                         */
                        $hashedPassword = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        /*
                         * Insert the user.
                         */
                        $stmt = $conn->prepare(
                            "INSERT INTO users
                            (name, email, password, profile_picture)
                            VALUES (?, ?, ?, ?)"
                        );

                        $stmt->bind_param(
                            "ssss",
                            $name,
                            $email,
                            $hashedPassword,
                            $newFileName
                        );

                        if ($stmt->execute()) {

                            header(
                                "Location: index.php?success=User added successfully"
                            );

                            exit;

                        } else {

                            /*
                             * If database insertion fails,
                             * remove the uploaded file.
                             */
                            if (is_file($destination)) {
                                unlink($destination);
                            }

                            $error = "Failed to add user.";
                        }

                        $stmt->close();
                    }
                }
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

    <title>Add User</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

    <h1>Add User</h1>

    <?php if ($error !== ""): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

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
            value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
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
            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
        >


        <label for="password">
            Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            minlength="6"
            required
        >


        <label for="profile_picture">
            Profile Picture
        </label>

        <input
            type="file"
            id="profile_picture"
            name="profile_picture"
            accept=".jpg,.jpeg,.png,.gif,.webp"
            required
        >

        <small>
            JPG, PNG, GIF, or WEBP. Maximum size: 2 MB.
        </small>


        <button type="submit">
            Add User
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