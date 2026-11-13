<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - USER AUTHENTICATION PAGE
 * Standalone Login & Registration System using PHP Sessions & PDO
 */
require_once __DIR__ . '/db.php';

// If user is already authenticated, redirect straight to catalog
if (!empty($_SESSION['user_id'])) {
    header('Location: shopping.php');
    exit;
}

$error_msg = '';
$success_msg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $success_msg = 'You have been signed out safely. Come back soon!';
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_action = $_POST['auth_action'] ?? '';
    $pdo = getDB();

    if (!$pdo) {
        $error_msg = 'Database connection is currently inactive. Please ensure MySQL is started in XAMPP.';
    } elseif ($auth_action === 'login') {
        $login_id = trim($_POST['login_identity'] ?? '');
        $password = $_POST['login_password'] ?? '';

        if (empty($login_id) || empty($password)) {
            $error_msg = 'Please enter both your username/email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :id1 OR email = :id2 LIMIT 1");
            $stmt->execute([':id1' => $login_id, ':id2' => $login_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful Login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = 'customer';

                header('Location: shopping.php?auth=success');
                exit;
            } else {
                $error_msg = 'Invalid username/email or password. Please try again.';
            }
        }
    } elseif ($auth_action === 'register') {
        $full_name = trim($_POST['reg_full_name'] ?? '');
        $email = trim($_POST['reg_email'] ?? '');
        $username = trim($_POST['reg_username'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $phone = trim($_POST['reg_phone'] ?? '');
        $address = trim($_POST['reg_address'] ?? '');

        if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
            $error_msg = 'Please fill out all required fields for registration.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please provide a valid email address.';
        } elseif (strlen($password) < 6) {
            $error_msg = 'Password must be at least 6 characters in length.';
        } else {
            // Check for existing user or email
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
                // Insert new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, username, password, phone, address)
                    VALUES (:full_name, :email, :username, :password, :phone, :address)
                ");
                $result = $insertStmt->execute([
                    ':full_name' => $full_name,
                    ':email'     => $email,
                    ':username'  => $username,
                    ':password'  => $hashedPassword,
                    ':phone'     => $phone,
                    ':address'   => $address
                ]);

                if ($result) {
                    $newUserId = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_email'] = $email;

                    header('Location: shopping.php?registered=1');
                    exit;
                } else {
                    $error_msg = 'An unexpected error occurred while creating your account.';
                }
            }
        }
    }
}

$page_title = 'Member Atelier Authentication';
require_once __DIR__ . '/header.php';
?>

<div class="container">
  <div class="auth-wrapper">
    <!-- Toggle Tabs -->
    <div class="auth-tabs">
      <button type="button" class="auth-tab-btn <?php echo (isset($_POST['auth_action']) && $_POST['auth_action'] === 'register') ? '' : 'active'; ?>" data-target="login-panel">
        Sign In
      </button>
      <button type="button" class="auth-tab-btn <?php echo (isset($_POST['auth_action']) && $_POST['auth_action'] === 'register') ? 'active' : ''; ?>" data-target="register-panel">
        Create Account
      </button>
    </div>

    <div class="auth-card-body">
      <div class="auth-header">
        <h1 class="auth-brand">Atelier Access</h1>
        <p class="auth-subtitle">Sign in to access your curated wardrobe, orders, and bag.</p>
      </div>

      <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error">
          <strong>Notice:</strong> <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success">
          <strong>Success:</strong> <?php echo htmlspecialchars($success_msg); ?>
        </div>
      <?php endif; ?>

      <!-- 1. SIGN IN FORM -->
      <div id="login-panel" class="auth-form-panel" style="<?php echo (isset($_POST['auth_action']) && $_POST['auth_action'] === 'register') ? 'display: none;' : 'display: block;'; ?>">
        <form method="POST" action="index.php" class="auth-form">
          <input type="hidden" name="auth_action" value="login">

          <div>
            <label class="form-label" for="login-username">Username or Email *</label>
            <input type="text" id="login-username" name="login_identity" class="form-input" placeholder="e.g. fashionista" value="<?php echo htmlspecialchars($_POST['login_identity'] ?? ''); ?>" required autofocus>
          </div>

          <div>
            <label class="form-label" for="login-password">Password *</label>
            <input type="password" id="login-password" name="login_password" class="form-input" placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            Sign In to Atelier
          </button>
        </form>

        <!-- Fast Demo Credentials Helper -->
        <div class="demo-account-box">
          <div style="font-weight: 800; text-transform: uppercase; margin-bottom: 0.35rem; color: var(--mocha-dark);">
            Instant Testing Credentials:
          </div>
          <div>User: <strong>fashionista</strong> | Password: <strong>password123</strong></div>
          <button type="button" onclick="fillDemoCredentials()" class="btn btn-sm btn-outline" style="margin-top: 0.5rem; width: 100%;">
            Auto-Fill Demo Credentials
          </button>
        </div>
      </div>

      <!-- 2. REGISTER FORM -->
      <div id="register-panel" class="auth-form-panel" style="<?php echo (isset($_POST['auth_action']) && $_POST['auth_action'] === 'register') ? 'display: block;' : 'display: none;'; ?>">
        <form method="POST" action="index.php" class="auth-form">
          <input type="hidden" name="auth_action" value="register">

          <div>
            <label class="form-label" for="reg_full_name">Full Name *</label>
            <input type="text" id="reg_full_name" name="reg_full_name" class="form-input" placeholder="e.g. Elena Vance" value="<?php echo htmlspecialchars($_POST['reg_full_name'] ?? ''); ?>" required>
          </div>

          <div>
            <label class="form-label" for="reg_email">Email Address *</label>
            <input type="email" id="reg_email" name="reg_email" class="form-input" placeholder="elena@example.com" value="<?php echo htmlspecialchars($_POST['reg_email'] ?? ''); ?>" required>
          </div>

          <div>
            <label class="form-label" for="reg_username">Username *</label>
            <input type="text" id="reg_username" name="reg_username" class="form-input" placeholder="Choose a unique username" value="<?php echo htmlspecialchars($_POST['reg_username'] ?? ''); ?>" required>
          </div>

          <div>
            <label class="form-label" for="reg_password">Password (min 6 characters) *</label>
            <input type="password" id="reg_password" name="reg_password" class="form-input" placeholder="••••••••" required>
          </div>

          <div>
            <label class="form-label" for="reg_phone">Contact Phone (Optional)</label>
            <input type="text" id="reg_phone" name="reg_phone" class="form-input" placeholder="+1 (555) 000-0000" value="<?php echo htmlspecialchars($_POST['reg_phone'] ?? ''); ?>">
          </div>

          <div>
            <label class="form-label" for="reg_address">Delivery Address (Optional)</label>
            <input type="text" id="reg_address" name="reg_address" class="form-input" placeholder="Street, Building, City" value="<?php echo htmlspecialchars($_POST['reg_address'] ?? ''); ?>">
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            Register New Account
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
