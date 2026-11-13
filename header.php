<?php
/**
 * FREUDIAN FASHION SHOP - COMMON HEADER PARTIAL
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

$current_page = basename($_SERVER['PHP_SELF']);
$current_category = $_GET['category'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | Freudian' : 'Freudian | Minimalist Fashion Boutique'; ?></title>
  <link rel="icon" type="image/x-icon" href="icons8-totoro-100ico.ico">
  <link rel="shortcut icon" type="image/x-icon" href="assets/icons8-totoro-100ico.ico">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- Primary Freudian Boutique Header -->
  <header class="site-header">
    <div class="container">
      <div class="header-inner">
        <!-- Brand Identity with Totoro Logo -->
        <a href="shopping.php" class="brand-wrap">
          <img src="totoro.png" alt="Freudian Totoro Logo" class="brand-logo-img">
          <div class="brand-text-block">
            <div class="brand-title">Freudian<span>.</span></div>
            <div class="brand-subtitle">Fashion Shop • Minimalist Atelier</div>
          </div>
        </a>

        <?php if ($is_logged_in): ?>
          <!-- Main Category Navigation Links (Cleaned; No SQL Setup Tab) -->
          <nav class="header-nav">
            <a href="shopping.php" class="nav-link <?php echo ($current_page === 'shopping.php' && empty($current_category)) ? 'active' : ''; ?>">All Collections</a>
            <a href="shopping.php?category=male_apparel" class="nav-link <?php echo ($current_category === 'male_apparel') ? 'active' : ''; ?>">Male Apparel</a>
            <a href="shopping.php?category=female_apparel" class="nav-link <?php echo ($current_category === 'female_apparel') ? 'active' : ''; ?>">Female Apparel</a>
            <a href="shopping.php?category=bags" class="nav-link <?php echo ($current_category === 'bags') ? 'active' : ''; ?>">Bags</a>
            <a href="shopping.php?category=shoes" class="nav-link <?php echo ($current_category === 'shoes') ? 'active' : ''; ?>">Shoes</a>
            <a href="shopping.php?category=accessories" class="nav-link <?php echo ($current_category === 'accessories') ? 'active' : ''; ?>">Accessories</a>
          </nav>

          <!-- Right Action Controls -->
          <div class="header-actions">
            <div class="user-status">
              <span>Member: <strong class="user-badge"><?php echo htmlspecialchars($logged_user_name); ?></strong></span>
              <a href="logout.php" class="btn btn-sm btn-outline" style="border-width: 1px;">Sign Out</a>
            </div>

            <a href="cart.php" class="btn-cart">
              <span>Bag</span>
              <span class="cart-count"><?php echo $cart_total_quantity; ?></span>
            </a>
          </div>
        <?php else: ?>
          <div class="header-actions">
            <a href="index.php" class="btn btn-sm btn-primary">Sign In / Register</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="site-main">
