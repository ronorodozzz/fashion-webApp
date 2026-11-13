<?php
/**
 * FREUDIAN FASHION SHOP - BILLING & CHECKOUT
 * Payment Schemes: GCash, Visa, Debit Mastercard, Cash on Delivery (COD)
 * Conditional Payment Form in Pure CSS • Zero JavaScript Architecture • No Product Codes
 */
require_once __DIR__ . '/db.php';

// Strict Guard: Unauthenticated users are strictly barred from browsing
if (empty($_SESSION['user_id'])) {
    header('Location: index.php?msg=auth_required');
    exit;
}

$pdo = getDB();

$cart_items = $_SESSION['cart'] ?? [];
$subtotal = 0.00;
foreach ($cart_items as $item) {
    $subtotal += ((float)$item['unit_price'] * (int)$item['quantity']);
}
$free_shipping_threshold = 3000.00;
$shipping_fee = ($subtotal > 0 && $subtotal < $free_shipping_threshold) ? 150.00 : 0.00;
$grand_total = $subtotal + $shipping_fee;

$order_completed = false;
$completed_order = null;
$completed_items = [];
$error_msg = '';

// Prepopulate user info
$user_info = [
    'full_name' => $_SESSION['user_name'] ?? '',
    'email'     => $_SESSION['user_email'] ?? '',
    'phone'     => '',
    'address'   => '',
    'city'      => '',
    'postal'    => ''
];

if (!empty($_SESSION['user_id']) && $pdo) {
    try {
        $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = :uid LIMIT 1");
        $uStmt->execute([':uid' => $_SESSION['user_id']]);
        $fetchedUser = $uStmt->fetch();
        if ($fetchedUser) {
            $user_info['full_name'] = $fetchedUser['full_name'];
            $user_info['email']     = $fetchedUser['email'];
            $user_info['phone']     = $fetchedUser['phone'] ?? '';
            $user_info['address']   = $fetchedUser['address'] ?? '';
        }
    } catch (Exception $e) {}
}

// Handle Order Placement POST (Pure PHP Processing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $error_msg = 'Your shopping bag is empty. Please add items before checking out.';
    } else {
        $customer_name   = trim($_POST['customer_name'] ?? '');
        $customer_email  = trim($_POST['customer_email'] ?? '');
        $customer_phone  = trim($_POST['customer_phone'] ?? '');
        $shipping_address= trim($_POST['shipping_address'] ?? '');
        $city            = trim($_POST['city'] ?? '');
        $postal_code     = trim($_POST['postal_code'] ?? '');
        $payment_method  = trim($_POST['payment_method'] ?? 'cod');
        $order_notes     = trim($_POST['order_notes'] ?? '');

        if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($shipping_address) || empty($city)) {
            $error_msg = 'Please complete all required shipping and contact fields.';
        } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please provide a valid email address.';
        } else {
            // Checkout Success Messages:
            // "Thank you for shopping". If COD is chosen, additionally "Pay when delivered".
            if ($payment_method === 'cod') {
                $confirmation_msg = "Thank you for shopping. Pay when delivered.";
                $payment_status = 'pending_cod';
            } else {
                $confirmation_msg = "Thank you for shopping. Your purchase has been successfully processed.";
                $payment_status = 'paid';
            }

            // Generate unique Order Number
            $order_number = 'FRD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $user_id = $_SESSION['user_id'];

            if ($pdo) {
                try {
                    $pdo->beginTransaction();

                    $orderStmt = $pdo->prepare("
                        INSERT INTO orders (
                            order_number, user_id, customer_name, customer_email, customer_phone,
                            shipping_address, city, postal_code, payment_method, payment_status,
                            confirmation_message, subtotal, shipping_fee, total_amount, order_notes
                        ) VALUES (
                            :ord_num, :uid, :c_name, :c_email, :c_phone,
                            :s_addr, :city, :p_code, :p_method, :p_status,
                            :c_msg, :subtotal, :shipping, :total, :notes
                        )
                    ");
                    $orderStmt->execute([
                        ':ord_num'  => $order_number,
                        ':uid'      => $user_id,
                        ':c_name'   => $customer_name,
                        ':c_email'  => $customer_email,
                        ':c_phone'  => $customer_phone,
                        ':s_addr'   => $shipping_address,
                        ':city'     => $city,
                        ':p_code'   => $postal_code,
                        ':p_method' => $payment_method,
                        ':p_status' => $payment_status,
                        ':c_msg'    => $confirmation_msg,
                        ':subtotal' => $subtotal,
                        ':shipping' => $shipping_fee,
                        ':total'    => $grand_total,
                        ':notes'    => $order_notes
                    ]);

                    $order_id = $pdo->lastInsertId();

                    $itemStmt = $pdo->prepare("
                        INSERT INTO order_items (
                            order_id, product_id, product_name,
                            selected_size, unit_price, quantity, item_subtotal
                        ) VALUES (
                            :order_id, :pid, :pname,
                            :psize, :price, :qty, :subtotal
                        )
                    ");

                    foreach ($cart_items as $ci) {
                        $line_total = (float)$ci['unit_price'] * (int)$ci['quantity'];
                        $itemStmt->execute([
                            ':order_id' => $order_id,
                            ':pid'      => $ci['product_id'],
                            ':pname'    => $ci['name'],
                            ':psize'    => $ci['size'],
                            ':price'    => $ci['unit_price'],
                            ':qty'      => $ci['quantity'],
                            ':subtotal' => $line_total
                        ]);
                    }

                    $pdo->commit();

                    $completed_order = [
                        'order_number'        => $order_number,
                        'customer_name'       => $customer_name,
                        'customer_email'      => $customer_email,
                        'customer_phone'      => $customer_phone,
                        'shipping_address'    => $shipping_address . ', ' . $city . ' ' . $postal_code,
                        'payment_method'      => $payment_method,
                        'payment_status'      => $payment_status,
                        'confirmation_message'=> $confirmation_msg,
                        'subtotal'            => $subtotal,
                        'shipping_fee'        => $shipping_fee,
                        'total_amount'        => $grand_total,
                        'created_at'          => date('F j, Y, g:i a')
                    ];
                    $completed_items = $cart_items;
                    $order_completed = true;

                    $_SESSION['cart'] = [];

                } catch (Exception $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error_msg = 'Failed to process order: ' . $ex->getMessage();
                }
            } else {
                // Fallback demo receipt
                $completed_order = [
                    'order_number'        => $order_number,
                    'customer_name'       => $customer_name,
                    'customer_email'      => $customer_email,
                    'customer_phone'      => $customer_phone,
                    'shipping_address'    => $shipping_address . ', ' . $city . ' ' . $postal_code,
                    'payment_method'      => $payment_method,
                    'payment_status'      => $payment_status,
                    'confirmation_message'=> $confirmation_msg,
                    'subtotal'            => $subtotal,
                    'shipping_fee'        => $shipping_fee,
                    'total_amount'        => $grand_total,
                    'created_at'          => date('F j, Y, g:i a')
                ];
                $completed_items = $cart_items;
                $order_completed = true;
                $_SESSION['cart'] = [];
            }
        }
    }
}

// If cart is empty and order wasn't just completed, redirect to bag
if (!$order_completed && empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$page_title = $order_completed ? 'Order Confirmation' : 'Billing & Checkout';
require_once __DIR__ . '/header.php';
?>

<div class="container" style="padding-top: 2rem;">

  <?php if ($order_completed && $completed_order): ?>
    <!-- =======================================================
         ORDER CONFIRMATION RECEIPT (Zero JavaScript)
         ======================================================= -->
    <div class="confirmation-container">
      <div class="confirmation-header">
        <span class="badge badge-pink" style="margin-bottom: 0.75rem;">Freudian Official Receipt</span>
        <h1 style="font-size: 2.3rem; margin-bottom: 0.5rem; text-transform: uppercase;">Order Confirmed</h1>

        <!-- Confirmation Callout -->
        <div class="confirmation-callout">
          <div>Thank you for shopping</div>
          <?php if ($completed_order['payment_method'] === 'cod'): ?>
            <div class="cod-pay-reminder">Pay when delivered</div>
          <?php else: ?>
            <div style="font-size: 0.95rem; margin-top: 0.5rem; font-weight: 600;">Your purchase has been successfully processed.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Metadata Grid -->
      <div class="order-meta-grid">
        <div>
          <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--mocha-muted);">Order Reference</div>
          <div style="font-size: 1rem; font-weight: 800;"><?php echo htmlspecialchars($completed_order['order_number']); ?></div>
        </div>
        <div>
          <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--mocha-muted);">Date & Time</div>
          <div style="font-size: 0.95rem; font-weight: 700;"><?php echo htmlspecialchars($completed_order['created_at']); ?></div>
        </div>
        <div>
          <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--mocha-muted);">Payment Method</div>
          <div style="font-size: 0.95rem; font-weight: 800;">
            <?php 
              $pm = $completed_order['payment_method'];
              if ($pm === 'gcash') echo '<span class="badge badge-pink">GCash (Paid)</span>';
              elseif ($pm === 'visa') echo '<span class="badge badge-pink">Visa (Paid)</span>';
              elseif ($pm === 'mastercard') echo '<span class="badge badge-pink">Debit Mastercard (Paid)</span>';
              else echo '<span class="badge badge-dark">Cash on Delivery (Pending)</span>';
            ?>
          </div>
        </div>
        <div style="grid-column: 1 / -1; border-top: 1px dashed var(--mocha-light); padding-top: 0.75rem;">
          <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--mocha-muted);">Delivery Destination</div>
          <div style="font-size: 0.9rem;">
            <strong><?php echo htmlspecialchars($completed_order['customer_name']); ?></strong> (<?php echo htmlspecialchars($completed_order['customer_email']); ?> | <?php echo htmlspecialchars($completed_order['customer_phone']); ?>)<br>
            <?php echo htmlspecialchars($completed_order['shipping_address']); ?>
          </div>
        </div>
      </div>

      <!-- Itemized Breakdown (No Product Codes) -->
      <h3 style="font-size: 1.15rem; margin-bottom: 1rem; text-transform: uppercase;">Itemized Order Breakdown</h3>
      <table class="cart-table" style="margin-bottom: 1.5rem;">
        <thead>
          <tr>
            <th>Curated Piece</th>
            <th>Size</th>
            <th>Unit Price</th>
            <th>Quantity</th>
            <th>Line Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($completed_items as $ci): 
            $line_cost = (float)$ci['unit_price'] * (int)$ci['quantity'];
          ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <img src="<?php echo htmlspecialchars($ci['image']); ?>" alt="" style="width: 48px; height: 58px; object-fit: cover; border: 1px solid var(--mocha-border);">
                  <div style="font-weight: 700;"><?php echo htmlspecialchars($ci['name']); ?></div>
                </div>
              </td>
              <td><span class="cart-size-pill">Size <?php echo htmlspecialchars($ci['size']); ?></span></td>
              <td><?php echo formatPeso($ci['unit_price']); ?></td>
              <td><?php echo (int)$ci['quantity']; ?></td>
              <td><strong><?php echo formatPeso($line_cost); ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Totals in Philippine Pesos (₱) -->
      <div style="max-width: 340px; margin-left: auto; border-top: var(--border-width) solid var(--mocha-border); padding-top: 1rem;">
        <div class="summary-row">
          <span>Items Subtotal:</span>
          <span><?php echo formatPeso($completed_order['subtotal']); ?></span>
        </div>
        <div class="summary-row">
          <span>Shipping:</span>
          <span><?php echo $completed_order['shipping_fee'] == 0 ? 'COMPLIMENTARY' : formatPeso($completed_order['shipping_fee']); ?></span>
        </div>
        <div class="summary-row total-row">
          <span>Total Settled:</span>
          <span class="total-amount-val"><?php echo formatPeso($completed_order['total_amount']); ?></span>
        </div>
      </div>

      <div style="margin-top: 2.5rem; text-align: center;">
        <a href="shopping.php" class="btn btn-primary btn-lg">
          Return to Catalog &rarr;
        </a>
      </div>
    </div>

  <?php else: ?>
    <!-- =======================================================
         CHECKOUT FORM (Pure HTML5 & CSS3; Zero JavaScript)
         ======================================================= -->
    <div class="breadcrumb">
      <a href="shopping.php">Freudian</a>
      <span>/</span>
      <a href="cart.php">Shopping Bag</a>
      <span>/</span>
      <span>Checkout</span>
    </div>

    <div style="margin-bottom: 2rem; border-bottom: var(--border-width) solid var(--mocha-border); padding-bottom: 1rem;">
      <div class="badge badge-pink" style="margin-bottom: 0.5rem;">Secure Boutique Checkout</div>
      <h1 style="font-size: 2.2rem; text-transform: uppercase;">Billing & Delivery</h1>
    </div>

    <?php if (!empty($error_msg)): ?>
      <div class="alert alert-error">
        <strong>Notice:</strong> <?php echo htmlspecialchars($error_msg); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php">
      <input type="hidden" name="place_order" value="1">

      <div class="checkout-grid">

        <!-- LEFT: Customer Information & Pure CSS Conditional Payment -->
        <div>

          <!-- Step 1: Destination & Contact -->
          <div class="checkout-box">
            <h2 class="checkout-section-heading">1. Delivery Destination & Contact</h2>

            <div class="form-grid-2">
              <div class="form-full">
                <label class="form-label" for="customer_name">Full Recipient Name *</label>
                <input type="text" id="customer_name" name="customer_name" class="form-input" placeholder="e.g. Darrel Cenir" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
              </div>

              <div>
                <label class="form-label" for="customer_email">Email for Tracking *</label>
                <input type="email" id="customer_email" name="customer_email" class="form-input" placeholder="darrel@example.com" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
              </div>

              <div>
                <label class="form-label" for="customer_phone">Phone Contact *</label>
                <input type="text" id="customer_phone" name="customer_phone" class="form-input" placeholder="+63 917 123 4567" value="<?php echo htmlspecialchars($user_info['phone']); ?>" required>
              </div>

              <div class="form-full">
                <label class="form-label" for="shipping_address">Delivery Address (Street, Building, Unit) *</label>
                <input type="text" id="shipping_address" name="shipping_address" class="form-input" placeholder="Unit 4B, Minimalist Tower, Ayala Avenue" value="<?php echo htmlspecialchars($user_info['address']); ?>" required>
              </div>

              <div>
                <label class="form-label" for="city">City / Municipality *</label>
                <input type="text" id="city" name="city" class="form-input" placeholder="Makati City / Manila" required>
              </div>

              <div>
                <label class="form-label" for="postal_code">Postal Code *</label>
                <input type="text" id="postal_code" name="postal_code" class="form-input" placeholder="1226" required>
              </div>

              <div class="form-full">
                <label class="form-label" for="order_notes">Delivery Notes (Optional)</label>
                <textarea id="order_notes" name="order_notes" class="form-textarea" rows="2" placeholder="Gate access code, preferred delivery time..."></textarea>
              </div>
            </div>
          </div>

          <!-- Step 2: Payment Scheme Selection (Pure CSS Conditional Display) -->
          <div class="checkout-box">
            <h2 class="checkout-section-heading">2. Select Payment Scheme</h2>

            <!-- Pure CSS Conditional Payment Container:
                 Radio inputs placed at root of container so CSS sibling selectors (~)
                 show/hide card fields without ANY JavaScript! -->
            <div class="payment-scheme-container">
              <input type="radio" id="pay_cod" name="payment_method" value="cod" class="payment-option-radio" checked>
              <input type="radio" id="pay_gcash" name="payment_method" value="gcash" class="payment-option-radio">
              <input type="radio" id="pay_visa" name="payment_method" value="visa" class="payment-option-radio">
              <input type="radio" id="pay_mastercard" name="payment_method" value="mastercard" class="payment-option-radio">

              <!-- Clickable Option Cards -->
              <div class="payment-options-pure-grid">
                <!-- COD -->
                <label for="pay_cod" class="payment-option-pure-label">
                  <div class="payment-option-name">Cash on Delivery (COD)</div>
                  <div class="payment-option-desc">Pay directly in cash when your courier delivers your package.</div>
                </label>

                <!-- GCash -->
                <label for="pay_gcash" class="payment-option-pure-label">
                  <div class="payment-option-name">GCash Mobile</div>
                  <div class="payment-option-desc">Instant payment via GCash e-wallet (Philippines).</div>
                </label>

                <!-- Visa -->
                <label for="pay_visa" class="payment-option-pure-label">
                  <div class="payment-option-name">Visa Card</div>
                  <div class="payment-option-desc">Verified by Visa online credit or debit card processing.</div>
                </label>

                <!-- Debit Mastercard -->
                <label for="pay_mastercard" class="payment-option-pure-label">
                  <div class="payment-option-name">Debit Mastercard</div>
                  <div class="payment-option-desc">Secure debit processing with Mastercard Identity Check.</div>
                </label>
              </div>

              <!-- CONDITIONAL PAYMENT FORM:
                   If COD is chosen, card account fields do NOT appear or become required.
                   Appears ONLY when GCash, Visa, or Debit Mastercard is selected via pure CSS! -->
              <div class="conditional-card-details-box">
                <div style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem; color: var(--mocha-dark);">
                  Online Account / Card Verification Reference
                </div>
                <div class="form-grid-2">
                  <div>
                    <label class="form-label" for="pay_ref_name">Account / Cardholder Name</label>
                    <input type="text" id="pay_ref_name" name="pay_ref_name" class="form-input" placeholder="e.g. Peter Paul Basallote">
                  </div>
                  <div>
                    <label class="form-label" for="pay_ref_num">Account / Card / Mobile Number</label>
                    <input type="text" id="pay_ref_num" name="pay_ref_num" class="form-input" placeholder="09XX-XXX-XXXX or 4532 ••••">
                  </div>
                </div>
              </div>

            </div>

          </div>

        </div>

        <!-- RIGHT: Order Review Summary Box -->
        <div>
          <div class="cart-summary-card">
            <h2 style="font-size: 1.35rem; margin-bottom: 1.25rem; border-bottom: var(--border-width) solid var(--mocha-border); padding-bottom: 0.65rem;">
              Order Review
            </h2>

            <div style="margin-bottom: 1.25rem; max-height: 260px; overflow-y: auto; padding-right: 0.5rem;">
              <?php foreach ($cart_items as $ci): ?>
                <div style="display: flex; gap: 0.75rem; margin-bottom: 0.85rem; align-items: center; border-bottom: 1px dashed var(--mocha-light); padding-bottom: 0.65rem;">
                  <img src="<?php echo htmlspecialchars($ci['image']); ?>" alt="" style="width: 44px; height: 54px; object-fit: cover; border: 1px solid var(--mocha-border);">
                  <div style="flex: 1;">
                    <div style="font-size: 0.85rem; font-weight: 700;"><?php echo htmlspecialchars($ci['name']); ?></div>
                    <div style="font-size: 0.72rem; color: var(--mocha-muted);">
                      Size: <strong><?php echo htmlspecialchars($ci['size']); ?></strong> &bull; Qty: <?php echo (int)$ci['quantity']; ?>
                    </div>
                  </div>
                  <div style="font-size: 0.9rem; font-weight: 800;">
                    <?php echo formatPeso((float)$ci['unit_price'] * (int)$ci['quantity']); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="summary-row">
              <span>Items Subtotal:</span>
              <span><?php echo formatPeso($subtotal); ?></span>
            </div>

            <div class="summary-row">
              <span>Shipping Fee:</span>
              <span><?php echo $shipping_fee == 0 ? '<strong style="color: var(--pink-hover);">COMPLIMENTARY</strong>' : formatPeso($shipping_fee); ?></span>
            </div>

            <div class="summary-row total-row">
              <span>Final Total:</span>
              <span class="total-amount-val"><?php echo formatPeso($grand_total); ?></span>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1.5rem;">
              Confirm & Place Order
            </button>

            <div style="text-align: center; margin-top: 0.85rem;">
              <a href="cart.php" class="btn btn-outline btn-sm btn-full">
                &larr; Return to Shopping Bag
              </a>
            </div>
          </div>
        </div>

      </div>
    </form>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
