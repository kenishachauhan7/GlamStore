<?php
include('../db.php');
session_start();

// If already logged in, go to homepage
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        $sql    = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $sql);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: ../index.php");
            exit();
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
  <title>Login - GlamStore</title>
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
    .register-link { text-align: center; font-size: 13px; margin-top: 1rem; color: #666; }
    .register-link a { color: #c2185b; text-decoration: none; }
    .brand { text-align: center; font-size: 26px; color: #c2185b; font-weight: bold; margin-bottom: 0.25rem; letter-spacing: 1px; }
    .tagline { text-align: center; font-size: 12px; color: #aaa; margin-bottom: 1.5rem; }
  </style>
</head>
<body>
  <div class="card">
    <div class="brand">GlamStore</div>
    <div class="tagline">Your beauty, our passion</div>

    <?php if ($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" placeholder="Enter your email" required>

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>

      <button type="submit">Login</button>
    </form>

    <div class="register-link">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</body>
</html>