<?php
/**
 * FREUDIAN FASHION SHOP - AUTHENTICATION HUB
 * Sign In & Sign Up System (Pure PHP, HTML5, CSS3 - Zero JavaScript)
 */
require_once __DIR__ . '/db.php';

// If user is already authenticated, redirect straight to catalog
if (!empty($_SESSION['user_id'])) {
    header('Location: shopping.php');
    exit;
}

$error_msg = '';
$success_msg = '';

$active_tab = $_GET['tab'] ?? 'login';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logged_out') {
        $success_msg = 'You have been successfully signed out of Freudian Fashion Shop.';
    } elseif ($_GET['msg'] === 'auth_required') {
        $error_msg = 'Access Restricted: You must register or log in first before browsing our boutique.';
    }
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_action = $_POST['auth_action'] ?? '';
    $pdo = getDB();

    if ($auth_action === 'login') {
        $active_tab = 'login';
        $login_id = trim($_POST['login_identity'] ?? '');
        $password = $_POST['login_password'] ?? '';

        if (empty($login_id) || empty($password)) {
            $error_msg = 'Please enter both your username/email and password.';
        } else {
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :id1 OR email = :id2 LIMIT 1");
                $stmt->execute([':id1' => $login_id, ':id2' => $login_id]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];

                    header('Location: shopping.php');
                    exit;
                } else {
                    $error_msg = 'Invalid username/email or password. Please try again.';
                }
            } else {
                // In-memory authentication fallback if database is starting
                if ($login_id === 'customer' && $password === 'password') {
                    $_SESSION['user_id'] = 1;
                    $_SESSION['user_name'] = 'Boutique Member';
                    $_SESSION['user_email'] = 'member@freudian.com';
                    header('Location: shopping.php');
                    exit;
                }
                $error_msg = 'Database connection is temporarily inactive. Please ensure MySQL is started in XAMPP.';
            }
        }
    } elseif ($auth_action === 'register') {
        $active_tab = 'register';
        $full_name = trim($_POST['reg_full_name'] ?? '');
        $email = trim($_POST['reg_email'] ?? '');
        $username = trim($_POST['reg_username'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $phone = trim($_POST['reg_phone'] ?? '');
        $address = trim($_POST['reg_address'] ?? '');

        if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
            $error_msg = 'Please fill out all required fields to register.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please provide a valid email address.';
        } elseif (strlen($password) < 6) {
            $error_msg = 'Password must be at least 6 characters in length.';
        } else {
            if ($pdo) {
                // Check if username or email is already taken
                $checkStmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = :u OR email = :e LIMIT 1");
                $checkStmt->execute([':u' => $username, ':e' => $email]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    if ($existing['email'] === $email) {
                        $error_msg = 'This email address is already registered. Please sign in.';
                    } else {
                        $error_msg = 'This username is already taken. Please choose another.';
                    }
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $insertStmt = $pdo->prepare("
                        INSERT INTO users (full_name, email, username, password, phone, address)
                        VALUES (:full_name, :email, :username, :password, :phone, :address)
                    ");
                    $res = $insertStmt->execute([
                        ':full_name' => $full_name,
                        ':email'     => $email,
                        ':username'  => $username,
                        ':password'  => $hashedPassword,
                        ':phone'     => $phone,
                        ':address'   => $address
                    ]);

                    if ($res) {
                        $newId = $pdo->lastInsertId();
                        $_SESSION['user_id'] = $newId;
                        $_SESSION['user_name'] = $full_name;
                        $_SESSION['user_email'] = $email;

                        header('Location: shopping.php');
                        exit;
                    } else {
                        $error_msg = 'An error occurred while creating your account. Please try again.';
                    }
                }
            } else {
                // Fallback session registration if database is initializing
                $_SESSION['user_id'] = 999;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                header('Location: shopping.php');
                exit;
            }
        }
    }
}

$page_title = 'Member Authentication';
require_once __DIR__ . '/header.php';
?>

<div class="container">
  <div class="auth-wrapper">
    <!-- Pure HTML Tabs -->
    <div class="auth-tabs">
      <a href="index.php?tab=login" class="auth-tab-link <?php echo ($active_tab === 'login') ? 'active' : ''; ?>">
        Sign In
      </a>
      <a href="index.php?tab=register" class="auth-tab-link <?php echo ($active_tab === 'register') ? 'active' : ''; ?>">
        Sign Up
      </a>
    </div>

    <div class="auth-card-body">
      <div class="auth-header">
        <img src="totoro.png" alt="Freudian Logo" class="auth-logo-center">
        <h1 class="auth-title">Freudian Fashion</h1>
        <p class="auth-subtitle">Minimalist Luxury Wardrobe Atelier</p>
      </div>

      <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error">
          <strong>Notice:</strong> <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success">
          <strong>Notice:</strong> <?php echo htmlspecialchars($success_msg); ?>
        </div>
      <?php endif; ?>

      <?php if ($active_tab === 'login'): ?>
        <!-- SIGN IN FORM (Strict User Input; No Demo Auto-Fill) -->
        <form method="POST" action="index.php?tab=login" class="auth-form">
          <input type="hidden" name="auth_action" value="login">

          <div>
            <label class="form-label" for="login_identity">Username or Email *</label>
            <input type="text" id="login_identity" name="login_identity" class="form-input" placeholder="Enter your username or email" value="<?php echo htmlspecialchars($_POST['login_identity'] ?? ''); ?>" required autofocus>
          </div>

          <div>
            <label class="form-label" for="login_password">Password *</label>
            <input type="password" id="login_password" name="login_password" class="form-input" placeholder="Enter your password" required>
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            Sign In to Freudian
          </button>
        </form>
      <?php else: ?>
        <!-- SIGN UP / REGISTRATION FORM -->
        <form method="POST" action="index.php?tab=register" class="auth-form">
          <input type="hidden" name="auth_action" value="register">

          <div>
            <label class="form-label" for="reg_full_name">Full Name *</label>
            <input type="text" id="reg_full_name" name="reg_full_name" class="form-input" placeholder="e.g. Ronald Domagco" value="<?php echo htmlspecialchars($_POST['reg_full_name'] ?? ''); ?>" required autofocus>
          </div>

          <div>
            <label class="form-label" for="reg_email">Email Address *</label>
            <input type="email" id="reg_email" name="reg_email" class="form-input" placeholder="e.g. ronald@example.com" value="<?php echo htmlspecialchars($_POST['reg_email'] ?? ''); ?>" required>
          </div>

          <div>
            <label class="form-label" for="reg_username">Username *</label>
            <input type="text" id="reg_username" name="reg_username" class="form-input" placeholder="Choose a unique username" value="<?php echo htmlspecialchars($_POST['reg_username'] ?? ''); ?>" required>
          </div>

          <div>
            <label class="form-label" for="reg_password">Password (min 6 characters) *</label>
            <input type="password" id="reg_password" name="reg_password" class="form-input" placeholder="Create a secure password" required>
          </div>

          <div>
            <label class="form-label" for="reg_phone">Contact Phone</label>
            <input type="text" id="reg_phone" name="reg_phone" class="form-input" placeholder="+63 900 000 0000" value="<?php echo htmlspecialchars($_POST['reg_phone'] ?? ''); ?>">
          </div>

          <div>
            <label class="form-label" for="reg_address">Delivery Address</label>
            <input type="text" id="reg_address" name="reg_address" class="form-input" placeholder="House/Unit, Street, Barangay, City, Province" value="<?php echo htmlspecialchars($_POST['reg_address'] ?? ''); ?>">
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            Register Account
          </button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
