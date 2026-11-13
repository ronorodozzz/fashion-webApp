<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - COMMON HEADER PARTIAL
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

// Determine active page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | Trend & Fashion Boutique' : 'Trend & Fashion Boutique Shop | Minimalist Online Luxury'; ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- Top Announcement Ticker -->
  <div class="top-ticker">
    Complimentary worldwide express shipping on curated orders over $150 • <span>Sharp Architectural Collection</span>
  </div>

  <!-- Primary Boutique Header -->
  <header class="site-header">
    <div class="container">
      <div class="header-inner">
        <!-- Brand Identity -->
        <a href="shopping.php" class="brand-wrap">
          <div class="brand-title">Trend <span>&</span> Fashion</div>
          <div class="brand-subtitle">Est. 2026 • Minimalist Boutique Atelier</div>
        </a>

        <!-- Main Navigation Links -->
        <nav class="header-nav">
          <a href="shopping.php" class="nav-link <?php echo ($current_page === 'shopping.php' && empty($_GET['category']) && empty($_GET['gender'])) ? 'active' : ''; ?>">Catalog</a>
          <a href="shopping.php?gender=Men" class="nav-link <?php echo (isset($_GET['gender']) && $_GET['gender'] === 'Men') ? 'active' : ''; ?>">Men</a>
          <a href="shopping.php?gender=Women" class="nav-link <?php echo (isset($_GET['gender']) && $_GET['gender'] === 'Women') ? 'active' : ''; ?>">Women</a>
          <a href="shopping.php?category=bags" class="nav-link <?php echo (isset($_GET['category']) && $_GET['category'] === 'bags') ? 'active' : ''; ?>">Bags</a>
          <a href="shopping.php?category=shoes" class="nav-link <?php echo (isset($_GET['category']) && $_GET['category'] === 'shoes') ? 'active' : ''; ?>">Shoes</a>
          <a href="shopping.php?category=accessories" class="nav-link <?php echo (isset($_GET['category']) && $_GET['category'] === 'accessories') ? 'active' : ''; ?>">Accessories</a>
          <a href="database_setup.txt" download class="nav-link" style="color: var(--pink-accent);" title="Download MySQL Setup for phpMyAdmin">SQL Setup</a>
        </nav>

        <!-- Right Action Controls -->
        <div class="header-actions">
          <?php if ($is_logged_in): ?>
            <div class="user-status">
              <span>Hi, <strong class="user-badge"><?php echo htmlspecialchars($logged_user_name); ?></strong></span>
              <a href="logout.php" class="btn btn-sm btn-outline" style="border-width: 1px;">Sign Out</a>
            </div>
          <?php else: ?>
            <a href="index.php" class="btn btn-sm btn-outline <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">Account</a>
          <?php endif; ?>

          <a href="cart.php" class="btn-cart">
            <span>Bag</span>
            <span class="cart-count"><?php echo $cart_total_quantity; ?></span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Database Connection Banner if offline -->
  <?php renderDbNotice(); ?>

  <main class="site-main">
