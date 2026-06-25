<?php
include('../db.php');
session_start();
 
$error = "";
$success = "";
 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
 
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered. Please login.";
        } else {
            // Hash password and insert user
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed')";
            if (mysqli_query($conn, $sql)) {
                $success = "Account created! You can now login.";
            } else {
                $error = mysqli_error($conn);
            }
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - GlamStore</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #fff0f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .card { background: white; padding: 2rem; border-radius: 12px; width: 360px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
    h2 { text-align: center; color: #c2185b; margin-bottom: 1.5rem; font-size: 22px; }
    label { font-size: 13px; color: #666; display: block; margin-bottom: 4px; }
    input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; margin-bottom: 1rem; }
    input:focus { outline: none; border-color: #e91e8c; }
    button { width: 100%; padding: 11px; background: #c2185b; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; }
    button:hover { background: #ad1457; }
    .error { background: #fdecea; color: #c62828; padding: 8px 12px; border-radius: 8px; font-size: 13px; margin-bottom: 1rem; }
    .success { background: #e8f5e9; color: #2e7d32; padding: 8px 12px; border-radius: 8px; font-size: 13px; margin-bottom: 1rem; }
    .login-link { text-align: center; font-size: 13px; margin-top: 1rem; color: #666; }
    .login-link a { color: #c2185b; text-decoration: none; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Create Account</h2>
 
    <?php if ($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
 
    <?php if ($success): ?>
      <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
 
    <form method="POST">
      <label>Username</label>
      <input type="text" name="username" placeholder="Enter username" required>
 
      <label>Email</label>
      <input type="email" name="email" placeholder="Enter email" required>
 
      <label>Password</label>
      <input type="password" name="password" placeholder="Min 6 characters" required>
 
      <button type="submit">Register</button>
    </form>
 
    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
</body>
</html>