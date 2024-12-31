
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkanPolis Logowanie</title>
  <link rel="stylesheet" href="../css/login.css">
</head>
<body>
  <header class="header">
    <div class="header-container">
        <a href="../index.html" class="btn back">POWRÓT</a>
        <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
    </div>
  </header>

  <main>
    <div class="form-container">
      <h2>Logowanie</h2>

      <!-- PHP: Display errors -->
      <?php
      require 'db_connect.php'; // Ensure this file correctly connects to the database
      session_start();
      $errors = [];
      

      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($_POST['action'])){
          $action = $_POST['action'];
          
          if($action === 'login'){

          // Retrieve and sanitize input
          $email = trim($_POST['email'] ?? '');
          $password = trim($_POST['password'] ?? '');

          // Validate inputs
          if (empty($email)) {
              $errors[] = 'E-mail is required.';
          } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $errors[] = 'Invalid e-mail format.';
          }

          if (empty($password)) {
              $errors[] = 'Password is required.';
          }

          // If no validation errors, check the database
          if (empty($errors)) {
              try {
                  $query = "SELECT Users_ID, haslo FROM User WHERE email = :email";
                  $stmt = $pdo->prepare($query);
                  $stmt->execute(['email' => $email]);
                  $user = $stmt->fetch(PDO::FETCH_ASSOC);
                  
                  if ($user && password_verify($password, $user['haslo'])) {
                  

                    
                    $_SESSION['Users_ID'] = $userData['Users_ID'];
                    // Login successful: Redirect to main.html
                    header("Location: ../html/main.html");
                    exit;

                  }else {
                      $errors[] = 'Invalid email or password.';
                  }
              } catch (PDOException $e) {
                  $errors[] = 'Database error: ' . $e->getMessage();
              }
          }
        } elseif ($action === 'forgot_pass'){
          header('Location: password_retrieval.php');
          exit();
        }
      }
    }
      // Display errors if any
      if (!empty($errors)) {
          echo '<div class="error-messages"><ul>';
          foreach ($errors as $error) {
              echo '<li style="color: red;">' . htmlspecialchars($error) . '</li>';
          }
          echo '</ul></div>';
      }
      ?>

      <!-- Login Form -->
      <form action="login.php" method="post">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Wpisz e-mail" value="<?php echo htmlspecialchars($email ?? ''); ?>">

        <label for="password">Hasło</label>
        <input type="password" id="password" name="password" placeholder="Wpisz hasło">

        <!-- Buttons Below the Input Fields -->
        <button type="submit" class="login-btn" name="action" value="login">ZALOGUJ SIĘ</button>
        <button type="submit" class="reset-btn" name="action" value="forgot_pass">Zapomniałem hasła</button>
      </form>
    </div>
  </main>
  <footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>

