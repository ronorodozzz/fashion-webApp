<?php
/**
 * FREUDIAN FASHION SHOP - COMMON HEADER PARTIAL
 * Top Bar Layout: Strictly contains logo.png, brand name "Freudian",
 * user account status, sign out link, and bag/cart icon.
 * Zero JavaScript Architecture
 */
require_once __DIR__ . '/db.php';

// Calculate total cart items
$cart_total_quantity = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cartItem) {
        $cart_total_quantity += (int)($cartItem['quantity'] ?? 0);
    }
}

$is_logged_in = !empty($_SESSION['user_id']);
$logged_user_name = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | Freudian' : 'Freudian | Minimalist Fashion Shop'; ?></title>
  <link rel="icon" type="image/x-icon" href="icons8-totoro-100ico.ico">
  <link rel="shortcut icon" type="image/x-icon" href="icons8-totoro-100ico.ico">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- Top Bar: Strictly logo.png, "Freudian", user account status, sign out, bag icon -->
  <header class="site-header">
    <div class="container">
      <div class="header-inner">
        <!-- Brand Identity with logo.png and "Freudian" -->
        <a href="shopping.php" class="brand-wrap">
          <img src="logo.png" alt="Freudian Logo" class="brand-logo-img">
          <span class="brand-title">Freudian</span>
        </a>

        <!-- Right Header Action Controls -->
        <div class="header-actions">
          <?php if ($is_logged_in): ?>
            <div class="user-status">
              <span>Account: <strong class="user-badge"><?php echo htmlspecialchars($logged_user_name); ?></strong></span>
              <a href="logout.php" class="btn btn-sm btn-outline">Sign Out</a>
            </div>

            <a href="cart.php" class="btn-cart">
              <span>Bag</span>
              <span class="cart-count"><?php echo $cart_total_quantity; ?></span>
            </a>
          <?php else: ?>
            <a href="index.php" class="btn btn-sm btn-primary">Sign In / Sign Up</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <main class="site-main">
