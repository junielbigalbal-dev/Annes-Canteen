<?php
require_once 'config/database.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    
    // Validate input
    $errors = [];
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
        $errors[] = "All fields are required";
    }
    
    if (strlen($username) < 4 || strlen($username) > 50) {
        $errors[] = "Username must be between 4 and 50 characters";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }
    
    // If there are validation errors, redirect back to registration form
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = [
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone
        ];
        header('Location: register.php');
        exit();
    }
    
    // Hash the password
    $password_hash = hashPassword($password);
    
    // Insert new user into database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $email, $password_hash, $first_name, $last_name, $phone);
    
    if ($stmt->execute()) {
        // Get the new user's ID
        $user_id = $conn->insert_id;
        
        // Log the user in
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['is_admin'] = false;
        
        // Set success message
        $_SESSION['success'] = "Registration successful! Welcome to Anne's Canteen!";
        
        // Redirect to home page
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error'] = "Registration failed. Please try again later.";
        header('Location: register.php');
        exit();
    }
} else {
    // If someone tries to access this file directly
    header('Location: register.php');
    exit();
}
