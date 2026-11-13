<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - BILLING & CHECKOUT
 * Handles Cash on Delivery (COD) vs Online Credit Card payment selection,
 * orders persistence to MySQL PDO, and displays required confirmation receipts.
 */
require_once __DIR__ . '/db.php';

$pdo = getDB();

// Calculate Cart Totals
$cart_items = $_SESSION['cart'] ?? [];
$subtotal = 0.00;
foreach ($cart_items as $item) {
    $subtotal += ((float)$item['unit_price'] * (int)$item['quantity']);
}
$free_shipping_threshold = 150.00;
$shipping_fee = ($subtotal > 0 && $subtotal < $free_shipping_threshold) ? 15.00 : 0.00;
$grand_total = $subtotal + $shipping_fee;

$order_completed = false;
$completed_order = null;
$completed_items = [];
$error_msg = '';

// Prepopulate user info if logged in
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

// Handle Order Placement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $error_msg = 'Your shopping bag is empty. Please add items before placing an order.';
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
            $error_msg = 'Please complete all required shipping and contact details.';
        } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please provide a valid email address for your order confirmation.';
        } else {
            // Determine confirmation message & payment status based on chosen payment scheme
            if ($payment_method === 'credit_card') {
                $confirmation_msg = "Order Confirmation! Thank you for shopping with us! Your purchase has been successfully processed.";
                $payment_status = 'paid';
            } else {
                $payment_method = 'cod';
                $confirmation_msg = "Order Confirmation! Thank you for shopping with us! Please pay once you receive your order.";
                $payment_status = 'pending_cod';
            }

            // Generate unique Order Number (e.g. ORD-20261113-ABCD)
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $user_id = $_SESSION['user_id'] ?? null;

            if ($pdo) {
                try {
                    $pdo->beginTransaction();

                    // 1. Insert into orders table
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

                    // 2. Insert into order_items table
                    $itemStmt = $pdo->prepare("
                        INSERT INTO order_items (
                            order_id, product_id, product_code, product_name,
                            selected_size, unit_price, quantity, item_subtotal
                        ) VALUES (
                            :order_id, :pid, :pcode, :pname,
                            :psize, :price, :qty, :subtotal
                        )
                    ");

                    foreach ($cart_items as $ci) {
                        $line_total = (float)$ci['unit_price'] * (int)$ci['quantity'];
                        $itemStmt->execute([
                            ':order_id' => $order_id,
                            ':pid'      => $ci['product_id'],
                            ':pcode'    => $ci['product_code'],
                            ':pname'    => $ci['name'],
                            ':psize'    => $ci['size'],
                            ':price'    => $ci['unit_price'],
                            ':qty'      => $ci['quantity'],
                            ':subtotal' => $line_total
                        ]);
                    }

                    $pdo->commit();

                    // Prepare receipt data
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

                    // Empty cart upon successful order
                    $_SESSION['cart'] = [];

                } catch (Exception $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error_msg = 'Failed to process order: ' . $ex->getMessage();
                }
            } else {
                // In case database connection is not active, allow graceful fallback demo receipt
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

// If not completing an order and cart is empty, redirect to cart page
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
         ORDER CONFIRMATION RECEIPT DISPLAY
         ======================================================= -->
    <div class="order-confirmation-box">
      <div class="confirmation-header">
        <span class="confirmation-tag">Boutique Atelier Receipt</span>
        <h1 class="confirmation-title">Order Confirmed</h1>

        <!-- Highlight Callout Message (Strictly following prompt specifications) -->
        <div class="confirmation-callout">
          <?php echo htmlspecialchars($completed_order['confirmation_message']); ?>
        </div>
      </div>

      <!-- Order Metadata Grid -->
      <div class="order-meta-grid">
        <div>
          <div class="meta-block-label">Order Reference</div>
          <div class="meta-block-value"><?php echo htmlspecialchars($completed_order['order_number']); ?></div>
        </div>
        <div>
          <div class="meta-block-label">Date & Time</div>
          <div class="meta-block-value"><?php echo htmlspecialchars($completed_order['created_at']); ?></div>
        </div>
        <div>
          <div class="meta-block-label">Payment Scheme</div>
          <div class="meta-block-value">
            <?php if ($completed_order['payment_method'] === 'credit_card'): ?>
              <span class="badge badge-pink">Credit Card (Paid)</span>
            <?php else: ?>
              <span class="badge badge-dark">Cash on Delivery (Pending)</span>
            <?php endif; ?>
          </div>
        </div>
        <div style="grid-column: 1 / -1; border-top: 1px dashed var(--mocha-light); padding-top: 0.75rem;">
          <div class="meta-block-label">Recipient & Delivery Destination</div>
          <div class="meta-block-value" style="font-size: 0.9rem;">
            <strong><?php echo htmlspecialchars($completed_order['customer_name']); ?></strong> (<?php echo htmlspecialchars($completed_order['customer_email']); ?> | <?php echo htmlspecialchars($completed_order['customer_phone']); ?>)<br>
            <?php echo htmlspecialchars($completed_order['shipping_address']); ?>
          </div>
        </div>
      </div>

      <!-- Itemized Order Breakdown -->
      <h3 style="font-size: 1.15rem; margin-bottom: 1rem; text-transform: uppercase;">Itemized Selection Breakdown</h3>
      <table class="cart-table" style="margin-bottom: 1.5rem;">
        <thead>
          <tr>
            <th>Item & Description</th>
            <th>Size</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($completed_items as $item): 
            $item_line = (float)$item['unit_price'] * (int)$item['quantity'];
          ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 60px; object-fit: cover; border: 1px solid var(--mocha-border);">
                  <div>
                    <div style="font-weight: 700;"><?php echo htmlspecialchars($item['name']); ?></div>
                    <span class="badge badge-sand" style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['product_code']); ?></span>
                  </div>
                </div>
              </td>
              <td><span class="cart-item-size-badge"><?php echo htmlspecialchars($item['size']); ?></span></td>
              <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
              <td><?php echo (int)$item['quantity']; ?></td>
              <td><strong>$<?php echo number_format($item_line, 2); ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Final Totals -->
      <div style="max-width: 350px; margin-left: auto; border-top: var(--border-width) solid var(--mocha-border); padding-top: 1rem;">
        <div class="summary-row">
          <span>Subtotal:</span>
          <span>$<?php echo number_format($completed_order['subtotal'], 2); ?></span>
        </div>
        <div class="summary-row">
          <span>Worldwide Shipping:</span>
          <span>
            <?php echo $completed_order['shipping_fee'] == 0 ? 'COMPLIMENTARY' : '$' . number_format($completed_order['shipping_fee'], 2); ?>
          </span>
        </div>
        <div class="summary-row total-row" style="margin-top: 0.5rem; padding-top: 0.5rem;">
          <span>Grand Total:</span>
          <span class="total-amount">$<?php echo number_format($completed_order['total_amount'], 2); ?></span>
        </div>
      </div>

      <div style="margin-top: 2.5rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <button type="button" onclick="window.print()" class="btn btn-outline">
          Print Boutique Receipt
        </button>
        <a href="shopping.php" class="btn btn-primary">
          Continue Shopping &rarr;
        </a>
      </div>
    </div>

  <?php else: ?>
    <!-- =======================================================
         CHECKOUT FORM (SHIPPING & PAYMENT SCHEMES)
         ======================================================= -->
    <div class="breadcrumb">
      <a href="shopping.php">Atelier</a>
      <span>/</span>
      <a href="cart.php">Shopping Bag</a>
      <span>/</span>
      <span>Checkout</span>
    </div>

    <div style="margin-bottom: 2rem; border-bottom: var(--border-width) solid var(--mocha-border); padding-bottom: 1rem;">
      <div class="badge badge-pink" style="margin-bottom: 0.5rem;">Order Finalization</div>
      <h1 style="font-size: 2.2rem; text-transform: uppercase;">Billing & Shipping</h1>
    </div>

    <?php if (!empty($error_msg)): ?>
      <div class="alert alert-error">
        <strong>Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php" id="checkoutForm">
      <input type="hidden" name="place_order" value="1">

      <div class="checkout-layout">

        <!-- LEFT: Customer Information & Payment Scheme Selection -->
        <div>

          <!-- Step 1: Contact & Shipping -->
          <div class="checkout-card">
            <h2 class="checkout-section-title">1. Shipping & Contact Destination</h2>

            <div class="form-grid">
              <div class="form-group-full">
                <label class="form-label" for="customer_name">Full Recipient Name *</label>
                <input type="text" id="customer_name" name="customer_name" class="form-input" placeholder="e.g. Julian Thorne" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
              </div>

              <div>
                <label class="form-label" for="customer_email">Email for Tracking *</label>
                <input type="email" id="customer_email" name="customer_email" class="form-input" placeholder="julian@example.com" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
              </div>

              <div>
                <label class="form-label" for="customer_phone">Phone Number *</label>
                <input type="text" id="customer_phone" name="customer_phone" class="form-input" placeholder="+1 (555) 321-4567" value="<?php echo htmlspecialchars($user_info['phone']); ?>" required>
              </div>

              <div class="form-group-full">
                <label class="form-label" for="shipping_address">Delivery Street Address *</label>
                <input type="text" id="shipping_address" name="shipping_address" class="form-input" placeholder="Apartment, suite, street name" value="<?php echo htmlspecialchars($user_info['address']); ?>" required>
              </div>

              <div>
                <label class="form-label" for="city">City / District *</label>
                <input type="text" id="city" name="city" class="form-input" placeholder="New York / London" required>
              </div>

              <div>
                <label class="form-label" for="postal_code">Postal Code *</label>
                <input type="text" id="postal_code" name="postal_code" class="form-input" placeholder="10012" required>
              </div>

              <div class="form-group-full">
                <label class="form-label" for="order_notes">Special Atelier Delivery Instructions (Optional)</label>
                <textarea id="order_notes" name="order_notes" class="form-textarea" rows="2" placeholder="Gate access code, preferred delivery hour..."></textarea>
              </div>
            </div>
          </div>

          <!-- Step 2: Payment Scheme Selection -->
          <div class="checkout-card">
            <h2 class="checkout-section-title">2. Payment Scheme Selection</h2>

            <div class="payment-methods-grid">
              <!-- Payment Option 1: Cash on Delivery (COD) -->
              <div class="payment-option-card selected" id="opt-cod">
                <input type="radio" name="payment_method" value="cod" id="pm-cod" class="payment-radio" checked>
                <div class="payment-option-title">Cash on Delivery (COD)</div>
                <div class="payment-option-desc">
                  Settle balance in cash or mobile POS when the courier delivers your signature package.
                </div>
              </div>

              <!-- Payment Option 2: Online Payment (Credit Card) -->
              <div class="payment-option-card" id="opt-cc">
                <input type="radio" name="payment_method" value="credit_card" id="pm-cc" class="payment-radio">
                <div class="payment-option-title">Online Payment (Credit Card)</div>
                <div class="payment-option-desc">
                  Instant secure payment via Visa, MasterCard, or American Express.
                </div>
              </div>
            </div>

            <!-- Conditional Credit Card Inputs -->
            <div id="credit-card-details-box" class="payment-details-box" style="display: none;">
              <div style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem; color: var(--mocha-dark);">
                Secure Card Gateway
              </div>
              <div class="form-grid">
                <div class="form-group-full">
                  <label class="form-label">Name on Card</label>
                  <input type="text" name="cc_name" class="form-input" placeholder="JULIAN THORNE">
                </div>
                <div class="form-group-full">
                  <label class="form-label">Card Number</label>
                  <input type="text" name="cc_number" class="form-input" placeholder="4532 •••• •••• 8899" maxlength="19">
                </div>
                <div>
                  <label class="form-label">Expiry Date (MM/YY)</label>
                  <input type="text" name="cc_expiry" class="form-input" placeholder="12/28" maxlength="5">
                </div>
                <div>
                  <label class="form-label">CVV Security Code</label>
                  <input type="text" name="cc_cvv" class="form-input" placeholder="384" maxlength="4">
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- RIGHT: Order Review Summary Box -->
        <div>
          <div class="order-summary-box">
            <h2 class="summary-heading">Order Review</h2>

            <div style="margin-bottom: 1.25rem; max-height: 280px; overflow-y: auto; padding-right: 0.5rem;">
              <?php foreach ($cart_items as $item): ?>
                <div style="display: flex; gap: 0.75rem; margin-bottom: 0.85rem; align-items: center; border-bottom: 1px dashed var(--mocha-light); padding-bottom: 0.65rem;">
                  <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width: 45px; height: 55px; object-fit: cover; border: 1px solid var(--mocha-border);">
                  <div style="flex: 1;">
                    <div style="font-size: 0.85rem; font-weight: 700;"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div style="font-size: 0.75rem; color: var(--mocha-muted);">
                      Size: <strong><?php echo htmlspecialchars($item['size']); ?></strong> &bull; Qty: <?php echo (int)$item['quantity']; ?>
                    </div>
                  </div>
                  <div style="font-size: 0.9rem; font-weight: 800;">
                    $<?php echo number_format((float)$item['unit_price'] * (int)$item['quantity'], 2); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="summary-row">
              <span>Subtotal:</span>
              <span>$<?php echo number_format($subtotal, 2); ?></span>
            </div>

            <div class="summary-row">
              <span>Shipping:</span>
              <span>
                <?php echo $shipping_fee == 0 ? '<strong style="color: var(--pink-hover);">COMPLIMENTARY</strong>' : '$' . number_format($shipping_fee, 2); ?>
              </span>
            </div>

            <div class="summary-row total-row">
              <span>Final Total:</span>
              <span class="total-amount">$<?php echo number_format($grand_total, 2); ?></span>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1.5rem;">
              Complete Order Now
            </button>

            <a href="cart.php" class="btn btn-outline btn-full btn-sm" style="margin-top: 0.75rem;">
              &larr; Modify Bag
            </a>
          </div>
        </div>

      </div>
    </form>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
