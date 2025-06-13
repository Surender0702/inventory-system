<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login - Inventory Management</title>
<style>
  /* Reset & base */
  * {
    box-sizing: border-box;
  }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #4b79a1, #283e51);
    height: 100vh;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  /* Container */
  .login-container {
    background-color: #fff;
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    width: 360px;
    max-width: 90vw;
    text-align: center;
  }

  /* Heading */
  .login-container h2 {
    margin-bottom: 24px;
    font-weight: 700;
    color: #283e51;
  }

  /* Input fields */
  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 14px 16px;
    margin-bottom: 20px;
    border: 1.8px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }
  input[type="email"]:focus,
  input[type="password"]:focus {
    border-color: #4b79a1;
    outline: none;
  }

  /* Submit button */
  button[type="submit"] {
    width: 100%;
    padding: 14px 16px;
    background: #4b79a1;
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.1rem;
    transition: background 0.3s ease;
  }
  button[type="submit"]:hover {
    background: #3a6186;
  }

  /* Error message */
  .error-message {
    color: #e74c3c;
    margin-bottom: 20px;
    font-weight: 600;
  }

  /* Responsive */
  @media (max-width: 400px) {
    .login-container {
      padding: 30px 20px;
      width: 95vw;
    }
  }
</style>
</head>
<body>

<div class="login-container">
  <h2>Login to Inventory System</h2>
  <?php if (isset($_SESSION['error'])): ?>
    <div class="error-message"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  <form action="process_login.php" method="POST" autocomplete="off" novalidate>
    <input type="email" name="email" placeholder="Email Address" required />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit">Login</button>
  </form>
</div>

</body>
</html>
