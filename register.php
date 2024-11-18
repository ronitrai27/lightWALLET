<?php
include 'connection.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Collect form data
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Initialize an array to hold error messages
    $errors = [];

    // Validate first name
    if (empty($firstname) || !preg_match("/^[a-zA-Z]+$/", $firstname)) {
        $errors[] = "First name must only contain alphabets and cannot be empty.";
    }

    // Validate last name
    if (empty($lastname) || !preg_match("/^[a-zA-Z]+$/", $lastname)) {
        $errors[] = "Last name must only contain alphabets and cannot be empty.";
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email is already registered
        $stmt = $conn->prepare("SELECT id FROM userdetail WHERE email_address = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $stmt->close();
    }

    // Validate password strength
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must be at least 8 characters long, include at least one uppercase letter, and one number.";
    }

    // If there are validation errors, display them
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    } else {
        // If no errors, proceed with registration
        $passwordHashed = password_hash($password, PASSWORD_DEFAULT); // Hash the password

        // Use a prepared statement to insert data
        $stmt = $conn->prepare("INSERT INTO userdetail (firstname, lastname, email_address, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $firstname, $lastname, $email, $passwordHashed);

        if ($stmt->execute()) {
            // User registered successfully
            echo "User registered successfully!";

            $userId = $conn->insert_id;
            $authToken = bin2hex(random_bytes(16)); // Generate a random token for the user

            // Store the token and username in cookies (valid for 1 hour)
            setcookie("auth_token", $authToken, time() + 3600, "/", "localhost", false, true); // HTTPOnly cookie for security
            setcookie("username", $firstname . " " . $lastname, time() + 3600, "/", "localhost", false, true);

            header("Location: http://localhost:4000");
            exit();
        } else {
            // Handle errors
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>