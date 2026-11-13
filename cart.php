<?php
/**
 * FREUDIAN FASHION SHOP - SHOPPING CART
 * Itemized Selections, Quantities, Sizes, and Total Calculations in Philippine Pesos (₱)
 * Zero JavaScript Architecture • No Product Codes Displayed
 */
require_once __DIR__ . '/db.php';

// Strict Guard: Unauthenticated users are strictly barred from browsing
if (empty($_SESSION['user_id'])) {
    header('Location: index.php?msg=auth_required');
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Cart Actions (Pure PHP Forms)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_action = $_POST['cart_action'] ?? '';

    if ($cart_action === 'add') {
        $product_id   = (int)($_POST['product_id'] ?? 0);
        $product_name = trim($_POST['product_name'] ?? '');
        $primary_image= trim($_POST['primary_image'] ?? 'assets/men_bag_1_version_1.avif');
        $quantity     = max(1, (int)($_POST['quantity'] ?? 1));

        // Format: "SizeName|Price"
        $size_package = trim($_POST['selected_size_package'] ?? 'One Size|0');
        $parts = explode('|', $size_package);
        $size_name = $parts[0] ?? 'Standard';
        $unit_price = isset($parts[1]) ? (float)$parts[1] : 0.00;

        // Composite key per product + size combination
        $item_key = $product_id . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $size_name);

        if (isset($_SESSION['cart'][$item_key])) {
            $_SESSION['cart'][$item_key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$item_key] = [
                'item_key'     => $item_key,
                'product_id'   => $product_id,
                'name'         => $product_name,
                'size'         => $size_name,
                'unit_price'   => $unit_price,
                'quantity'     => $quantity,
                'image'        => $primary_image
            ];
        }

        header('Location: cart.php');
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
    } elseif ($cart_action === 'remove') {
        $item_key = $_POST['item_key'] ?? '';
        if (isset($_SESSION['cart'][$item_key])) {
            unset($_SESSION['cart'][$item_key]);
        }
        header('Location: cart.php');
        exit;
    } elseif ($cart_action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: cart.php');
        exit;
    }
}

// Compute Totals in Philippine Pesos (₱)
$subtotal = 0.00;
$total_quantity = 0;

foreach ($_SESSION['cart'] as $item) {
    $item_total = (float)$item['unit_price'] * (int)$item['quantity'];
    $subtotal += $item_total;
    $total_quantity += (int)$item['quantity'];
}

$free_shipping_threshold = 3000.00;
$shipping_fee = ($subtotal > 0 && $subtotal < $free_shipping_threshold) ? 150.00 : 0.00;
$grand_total = $subtotal + $shipping_fee;

$page_title = 'Shopping Bag (' . $total_quantity . ')';
require_once __DIR__ . '/header.php';
?>

<div class="container" style="padding-top: 2rem;">

  <div class="breadcrumb">
    <a href="shopping.php">Freudian Atelier</a>
    <span>/</span>
    <span>Shopping Bag</span>
  </div>

  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; border-bottom: var(--border-width) solid var(--mocha-border); padding-bottom: 1rem;">
    <div>
      <div class="badge badge-pink" style="margin-bottom: 0.5rem;">Curated Selections</div>
      <h1 style="font-size: 2.2rem; text-transform: uppercase;">Your Shopping Bag</h1>
    </div>
    <div style="font-size: 0.9rem; color: var(--mocha-muted);">
      Total Pieces: <strong><?php echo $total_quantity; ?></strong>
    </div>
  </div>

  <?php if (empty($_SESSION['cart'])): ?>
    <div class="cart-table-panel" style="text-align: center; padding: 4.5rem 2rem;">
      <h2 style="font-size: 1.8rem; margin-bottom: 0.75rem;">Your Shopping Bag is Empty</h2>
      <p style="color: var(--mocha-muted); max-width: 480px; margin: 0 auto 2rem; font-size: 0.95rem;">
        Browse our tailored apparel, handcrafted shoes, and minimalist leather bags.
      </p>
      <a href="shopping.php" class="btn btn-primary btn-lg">
        Explore Collections
      </a>
    </div>
  <?php else: ?>
    <div class="cart-layout-grid">

      <!-- LEFT: Items Table (NO Product Codes Displayed) -->
      <div class="cart-table-panel">
        <table class="cart-table">
          <thead>
            <tr>
              <th>Curated Item</th>
              <th>Selected Size</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th>Subtotal</th>
              <th>Action</th>
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
                        <a href="product-detail.php?id=<?php echo (int)$item['product_id']; ?>">
                          <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                      </div>
                    </div>
                  </div>
                </td>

                <td>
                  <span class="cart-size-pill">
                    Size <?php echo htmlspecialchars($item['size']); ?>
                  </span>
                </td>

                <td>
                  <strong><?php echo formatPeso($item['unit_price']); ?></strong>
                </td>

                <td>
                  <form method="POST" action="cart.php" class="cart-qty-form-pure">
                    <input type="hidden" name="cart_action" value="update">
                    <input type="hidden" name="item_key" value="<?php echo htmlspecialchars($key); ?>">
                    <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="50" class="cart-qty-input-pure">
                    <button type="submit" class="btn btn-sm btn-outline" style="padding: 0.35rem 0.6rem;">Update</button>
                  </form>
                </td>

                <td>
                  <strong style="font-family: var(--font-display); font-size: 1.05rem;">
                    <?php echo formatPeso($line_total); ?>
                  </strong>
                </td>

                <td>
                  <form method="POST" action="cart.php">
                    <input type="hidden" name="cart_action" value="remove">
                    <input type="hidden" name="item_key" value="<?php echo htmlspecialchars($key); ?>">
                    <button type="submit" class="btn btn-sm btn-outline" style="color: #c92a2a; border-color: #c92a2a;" title="Remove this item">Remove</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Bottom Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; border-top: var(--border-width) solid var(--mocha-border); padding-top: 1rem;">
          <a href="shopping.php" class="btn btn-outline btn-sm">
            &larr; Continue Shopping
          </a>

          <form method="POST" action="cart.php">
            <input type="hidden" name="cart_action" value="clear">
            <button type="submit" class="btn btn-outline btn-sm">
              Clear Entire Bag
            </button>
          </form>
        </div>
      </div>

      <!-- RIGHT: Order Summary (Philippine Pesos ₱) -->
      <div class="cart-summary-card">
        <h2 style="font-size: 1.35rem; margin-bottom: 1.25rem; border-bottom: var(--border-width) solid var(--mocha-border); padding-bottom: 0.65rem;">
          Order Summary
        </h2>

        <div class="summary-row">
          <span>Items Subtotal</span>
          <span><?php echo formatPeso($subtotal); ?></span>
        </div>

        <div class="summary-row">
          <span>Nationwide Shipping</span>
          <span>
            <?php if ($shipping_fee === 0.00): ?>
              <strong style="color: var(--pink-hover);">COMPLIMENTARY</strong>
            <?php else: ?>
              <?php echo formatPeso($shipping_fee); ?>
            <?php endif; ?>
          </span>
        </div>

        <?php if ($subtotal < $free_shipping_threshold): ?>
          <div style="font-size: 0.75rem; color: var(--mocha-muted); margin-bottom: 1rem; background-color: var(--sand-surface); padding: 0.65rem; border: 1px dashed var(--mocha-border);">
            Add <strong><?php echo formatPeso($free_shipping_threshold - $subtotal); ?></strong> more to qualify for complimentary nationwide shipping!
          </div>
        <?php endif; ?>

        <div class="summary-row total-row">
          <span>Total to Settle</span>
          <span class="total-amount-val"><?php echo formatPeso($grand_total); ?></span>
        </div>

        <div style="margin-top: 1.5rem;">
          <a href="checkout.php" class="btn btn-primary btn-full btn-lg">
            Proceed to Checkout &rarr;
          </a>
        </div>

        <div style="margin-top: 1.5rem; border-top: 1px solid var(--mocha-light); padding-top: 1rem; font-size: 0.75rem; color: var(--mocha-muted); line-height: 1.5;">
          • Supported Payments: <strong>GCash, Visa, Debit Mastercard, Cash on Delivery (COD)</strong>.<br>
          • All boutique items are packaged in signature Freudian dust covers.
        </div>
      </div>

    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
