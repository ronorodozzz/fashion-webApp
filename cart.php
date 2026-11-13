<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - SHOPPING CART
 * Session-based itemized shopping cart, size-specific pricing, and total calculations
 */
require_once __DIR__ . '/db.php';

// Initialize session cart structure if not present
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$notice_msg = '';

// Handle Cart Actions (Add, Update, Remove, Clear)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_action = $_POST['cart_action'] ?? '';

    if ($cart_action === 'add') {
        $product_id   = (int)($_POST['product_id'] ?? 0);
        $product_code = trim($_POST['product_code'] ?? '');
        $product_name = trim($_POST['product_name'] ?? '');
        $selected_size= trim($_POST['selected_size'] ?? 'Standard');
        $unit_price   = (float)($_POST['unit_price'] ?? 0.00);
        $quantity     = max(1, (int)($_POST['quantity'] ?? 1));
        $primary_image= trim($_POST['primary_image'] ?? 'assets/men_bag_1_version_1.avif');

        // Create composite key for unique combination of product + selected size
        $item_key = $product_id . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $selected_size);

        if (isset($_SESSION['cart'][$item_key])) {
            $_SESSION['cart'][$item_key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$item_key] = [
                'item_key'     => $item_key,
                'product_id'   => $product_id,
                'product_code' => $product_code,
                'name'         => $product_name,
                'size'         => $selected_size,
                'unit_price'   => $unit_price,
                'quantity'     => $quantity,
                'image'        => $primary_image
            ];
        }

        header('Location: cart.php?item_added=1');
        exit;
    } elseif ($cart_action === 'update') {
        $item_key = $_POST['item_key'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 1);

        if (isset($_SESSION['cart'][$item_key])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$item_key]);
            } else {
                $_SESSION['cart'][$item_key]['quantity'] = min(50, $quantity);
            }
        }
        header('Location: cart.php');
        exit;
    }
}

// Handle GET Actions (Remove, Clear)
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'remove' && !empty($_GET['key'])) {
        $remove_key = $_GET['key'];
        if (isset($_SESSION['cart'][$remove_key])) {
            unset($_SESSION['cart'][$remove_key]);
        }
        header('Location: cart.php');
        exit;
    } elseif ($_GET['action'] === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: cart.php');
        exit;
    }
}

// Calculate Calculations
$subtotal = 0.00;
$total_items_count = 0;

foreach ($_SESSION['cart'] as $item) {
    $item_total = (float)$item['unit_price'] * (int)$item['quantity'];
    $subtotal += $item_total;
    $total_items_count += (int)$item['quantity'];
}

$free_shipping_threshold = 150.00;
$standard_shipping_rate  = ($subtotal > 0 && $subtotal < $free_shipping_threshold) ? 15.00 : 0.00;
$grand_total = $subtotal + $standard_shipping_rate;

$page_title = 'Shopping Bag (' . $total_items_count . ')';
require_once __DIR__ . '/header.php';
?>

<div class="container" style="padding-top: 2rem;">

  <div class="breadcrumb">
    <a href="shopping.php">Atelier</a>
    <span>/</span>
    <span>Shopping Bag</span>
  </div>

  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; border-bottom: var(--border-width) solid var(--mocha-border); padding-bottom: 1rem;">
    <div>
      <div class="badge badge-pink" style="margin-bottom: 0.5rem;">Curated Selection</div>
      <h1 style="font-size: 2.2rem; text-transform: uppercase;">Your Shopping Bag</h1>
    </div>
    <div>
      <span style="font-size: 0.9rem; color: var(--mocha-muted);">
        Total Items: <strong><?php echo $total_items_count; ?></strong>
      </span>
    </div>
  </div>

  <?php if (isset($_GET['item_added'])): ?>
    <div class="alert alert-success">
      <strong>Item added to bag!</strong> Your size selection and price adjustments have been recorded.
    </div>
  <?php endif; ?>

  <?php if (empty($_SESSION['cart'])): ?>
    <!-- Empty Cart State -->
    <div class="cart-items-panel" style="text-align: center; padding: 4.5rem 2rem;">
      <h2 style="font-size: 1.8rem; margin-bottom: 0.75rem;">Your Bag is Currently Empty</h2>
      <p style="color: var(--mocha-muted); max-width: 450px; margin: 0 auto 2rem; font-size: 0.95rem;">
        Discover our architectural apparel, hand-burnished footwear, and minimalist leather accessories.
      </p>
      <a href="shopping.php" class="btn btn-primary btn-lg">
        Explore Catalog
      </a>
    </div>
  <?php else: ?>
    <!-- Populated Cart Table & Summary -->
    <div class="cart-layout">

      <!-- LEFT: Items Table -->
      <div class="cart-items-panel">
        <table class="cart-table">
          <thead>
            <tr>
              <th>Curated Item</th>
              <th>Selected Size</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($_SESSION['cart'] as $key => $item): 
              $line_total = (float)$item['unit_price'] * (int)$item['quantity'];
            ?>
              <tr>
                <td>
                  <div class="cart-item-preview">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-thumb">
                    <div>
                      <div class="cart-item-title">
                        <a href="product-detail.php?code=<?php echo urlencode($item['product_code']); ?>">
                          <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                      </div>
                      <div class="cart-item-meta">
                        <span class="badge badge-dark"><?php echo htmlspecialchars($item['product_code']); ?></span>
                      </div>
                    </div>
                  </div>
                </td>

                <td>
                  <span class="cart-item-size-badge">
                    <?php echo htmlspecialchars($item['size']); ?>
                  </span>
                </td>

                <td>
                  <strong>$<?php echo number_format($item['unit_price'], 2); ?></strong>
                </td>

                <td>
                  <form method="POST" action="cart.php" class="cart-qty-form">
                    <input type="hidden" name="cart_action" value="update">
                    <input type="hidden" name="item_key" value="<?php echo htmlspecialchars($key); ?>">
                    <button type="submit" name="quantity" value="<?php echo $item['quantity'] - 1; ?>" class="cart-qty-btn" title="Decrease">-</button>
                    <span class="cart-qty-val"><?php echo (int)$item['quantity']; ?></span>
                    <button type="submit" name="quantity" value="<?php echo $item['quantity'] + 1; ?>" class="cart-qty-btn" title="Increase">+</button>
                  </form>
                </td>

                <td>
                  <span class="cart-line-price">
                    $<?php echo number_format($line_total, 2); ?>
                  </span>
                </td>

                <td>
                  <a href="cart.php?action=remove&key=<?php echo urlencode($key); ?>" class="btn-remove-item" title="Remove item from bag">&times;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Bottom Cart Actions -->
        <div class="cart-actions-bar">
          <a href="shopping.php" class="btn btn-outline btn-sm">
            &larr; Continue Shopping
          </a>

          <a href="cart.php?action=clear" class="btn btn-outline btn-sm" onclick="return confirm('Are you sure you want to clear your entire bag?');">
            Clear Entire Bag
          </a>
        </div>
      </div>

      <!-- RIGHT: Order Summary -->
      <div class="order-summary-box">
        <h2 class="summary-heading">Order Summary</h2>

        <div class="summary-row">
          <span>Items Subtotal</span>
          <span>$<?php echo number_format($subtotal, 2); ?></span>
        </div>

        <div class="summary-row">
          <span>Worldwide Shipping</span>
          <span>
            <?php if ($standard_shipping_rate === 0.00): ?>
              <strong style="color: var(--pink-hover);">COMPLIMENTARY</strong>
            <?php else: ?>
              $<?php echo number_format($standard_shipping_rate, 2); ?>
            <?php endif; ?>
          </span>
        </div>

        <?php if ($subtotal < $free_shipping_threshold): ?>
          <div style="font-size: 0.72rem; color: var(--mocha-muted); margin-bottom: 1rem; background-color: var(--sand-surface); padding: 0.5rem; border: 1px dashed var(--mocha-border);">
            Add <strong>$<?php echo number_format($free_shipping_threshold - $subtotal, 2); ?></strong> more to unlock complimentary worldwide shipping!
          </div>
        <?php endif; ?>

        <div class="summary-row total-row">
          <span>Estimated Total</span>
          <span class="total-amount">$<?php echo number_format($grand_total, 2); ?></span>
        </div>

        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
          <a href="checkout.php" class="btn btn-primary btn-full btn-lg">
            Proceed to Checkout &rarr;
          </a>
        </div>

        <div style="margin-top: 1.5rem; border-top: 1px solid var(--mocha-light); padding-top: 1rem; font-size: 0.75rem; color: var(--mocha-muted); line-height: 1.5;">
          • Choose between <strong>Cash on Delivery (COD)</strong> or <strong>Credit Card</strong> at next step.<br>
          • All boutique orders are insured and tracked.
        </div>
      </div>

    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
