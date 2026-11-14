<?php
/**
 * FREUDIAN FASHION SHOP - PRODUCT DETAIL VIEW
 * Pure CSS Image Collage (All AVIF Variations) • Size-Based Pricing in Pesos (₱)
 * Zero JavaScript Architecture • No Product Codes Displayed
 */
require_once __DIR__ . '/db.php';

// Strict Guard: Unauthenticated users are strictly barred from browsing
if (empty($_SESSION['user_id'])) {
    header('Location: index.php?msg=auth_required');
    exit;
}

$pdo = getDB();
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$images = [];
$sizes = [];
$cart_notification = '';

// Cart Addition Flow (Pure PHP & HTML; Zero JavaScript)
// When a user clicks "Add to Bag", do NOT automatically redirect them to cart.php.
// Process the item addition into the session and display a clear notification/flash message on the page.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['cart_action'] ?? '') === 'add') {
    $add_id        = (int)($_POST['product_id'] ?? $product_id);
    $product_name  = trim($_POST['product_name'] ?? '');
    $primary_image = trim($_POST['primary_image'] ?? '');
    $quantity      = max(1, (int)($_POST['quantity'] ?? 1));

    // Format: "SizeName|Price"
    $size_package  = trim($_POST['selected_size_package'] ?? 'One Size|0');
    $parts         = explode('|', $size_package);
    $size_name     = $parts[0] ?? 'Standard';
    $unit_price    = isset($parts[1]) ? (float)$parts[1] : 0.00;

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $item_key = $add_id . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $size_name);

    if (isset($_SESSION['cart'][$item_key])) {
        $_SESSION['cart'][$item_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$item_key] = [
            'item_key'     => $item_key,
            'product_id'   => $add_id,
            'name'         => $product_name,
            'size'         => $size_name,
            'unit_price'   => $unit_price,
            'quantity'     => $quantity,
            'image'        => $primary_image
        ];
    }

    // Redirect back to this product's detail page with flash confirmation
    header('Location: product-detail.php?id=' . $add_id . '&added=1');
    exit;
}

if (isset($_GET['added'])) {
    $cart_notification = 'Product has been added to the bag.';
}

if ($pdo && $product_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch();

        if ($product) {
            // Fetch all AVIF variation images for the pure CSS collage
            $imgStmt = $pdo->prepare("
                SELECT * FROM product_images
                WHERE product_id = :pid
                ORDER BY sort_order ASC, version_number ASC
            ");
            $imgStmt->execute([':pid' => $product['id']]);
            $images = $imgStmt->fetchAll();

            // Fetch size-based pricing
            $sizeStmt = $pdo->prepare("
                SELECT * FROM product_sizes
                WHERE product_id = :pid
                ORDER BY id ASC
            ");
            $sizeStmt->execute([':pid' => $product['id']]);
            $sizes = $sizeStmt->fetchAll();
        }
    } catch (PDOException $e) {
        $product = null;
    }
}

// Fallback to Freudian seed catalog
if (!$product && $product_id > 0) {
    $fallbackItem = getFreudianProductById($product_id);
    if ($fallbackItem) {
        $product = $fallbackItem;
        $images  = $fallbackItem['images'] ?? [];
        $sizes   = $fallbackItem['sizes'] ?? [];
    }
}

// Fallback to first item if ID is invalid
if (!$product) {
    $fallbackItem = getFreudianProductById(1);
    $product = $fallbackItem;
    $images  = $fallbackItem['images'] ?? [];
    $sizes   = $fallbackItem['sizes'] ?? [];
}

$page_title = $product ? htmlspecialchars($product['name']) : 'Piece';
require_once __DIR__ . '/header.php';
?>

<div class="container" style="padding-top: 1.5rem;">

  <!-- Breadcrumb Navigation (No Product Codes) -->
  <nav class="breadcrumb">
    <a href="shopping.php">Freudian</a>
    <span>/</span>
    <a href="shopping.php?category=<?php echo urlencode($product['category_slug'] ?? 'male_apparel'); ?>">
      <?php echo htmlspecialchars($product['category_name'] ?? 'Collection'); ?>
    </a>
    <span>/</span>
    <span><?php echo htmlspecialchars($product['name']); ?></span>
  </nav>

  <?php if (!empty($cart_notification)): ?>
    <!-- Clear Notification / Flash Message upon Adding to Bag (Pure PHP Flash; Zero JavaScript) -->
    <div class="alert alert-success" style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; background-color: var(--mocha-dark); color: #fff; font-weight: bold; font-size: 0.9rem;">✓</span>
        <div>
          <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($cart_notification); ?></strong>
          <span style="display: block; font-size: 0.85rem; color: var(--mocha-muted); margin-top: 0.15rem;">Item has been stored in your bag. Feel free to continue browsing our collections.</span>
        </div>
      </div>
      <div style="display: flex; gap: 0.65rem;">
        <a href="shopping.php" class="btn btn-sm btn-outline">Continue Browsing</a>
        <a href="cart.php" class="btn btn-sm btn-primary">View Bag</a>
      </div>
    </div>
  <?php endif; ?>

  <div class="product-detail-grid">

    <!-- LEFT: PURE CSS IMAGE COLLAGE (All AVIF picture variations together in one layout; ZERO JS; No marketing subtitles) -->
    <div class="pure-css-collage">
      <!-- Main Feature Collage Frame -->
      <div class="collage-frame-main">
        <img src="<?php echo htmlspecialchars($images[0]['image_path'] ?? $product['primary_image']); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>">
      </div>

      <!-- Additional Variations Collage Subgrid -->
      <?php if (count($images) > 1): ?>
        <div class="collage-subgrid">
          <?php for ($i = 1; $i < count($images); $i++): ?>
            <div class="collage-subframe">
              <img src="<?php echo htmlspecialchars($images[$i]['image_path']); ?>" 
                   alt="<?php echo htmlspecialchars($product['name']); ?> Angle <?php echo $i + 1; ?>">
            </div>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Product Information, Size-Based Pricing & Bag Submission -->
    <div class="product-info-card">
      <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
        <span class="badge badge-sand"><?php echo htmlspecialchars($product['category_name']); ?></span>
        <?php if (!empty($product['has_size_pricing'])): ?>
          <span class="badge badge-pink">Size-Based Pricing</span>
        <?php endif; ?>
      </div>

      <!-- Title (NO Product Codes) -->
      <h1 class="product-detail-title">
        <?php echo htmlspecialchars($product['name']); ?>
      </h1>

      <div class="product-price-headline">
        <div class="price-headline-val">
          <?php echo formatPeso($sizes[0]['price'] ?? $product['base_price']); ?>
        </div>
        <?php if (!empty($product['has_size_pricing'])): ?>
          <span style="font-size: 0.75rem; color: var(--pink-hover); font-weight: 700; text-transform: uppercase;">
            Base Starting Rate
          </span>
        <?php endif; ?>
      </div>

      <p style="font-size: 0.92rem; color: var(--mocha-muted); line-height: 1.65; margin-bottom: 1.75rem;">
        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
      </p>

      <?php if (!empty($product['has_size_pricing'])): ?>
        <!-- Size-Based Pricing Schedule Table (Philippine Pesos ₱) -->
        <div class="size-pricing-schedule">
          <div class="schedule-title">
            <span>Size-Based Pricing Schedule</span>
            <span>Philippine Pesos (₱)</span>
          </div>
          <table class="schedule-table">
            <thead>
              <tr>
                <th>Proportion / Size</th>
                <th>Tailoring Price</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sizes as $sz): ?>
                <tr>
                  <td><strong>Size <?php echo htmlspecialchars($sz['size_name']); ?></strong></td>
                  <td><span style="color: var(--mocha-dark); font-weight: 800;"><?php echo formatPeso($sz['price']); ?></span></td>
                  <td><span class="badge badge-sand" style="font-size: 0.65rem;">In Stock</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- Purchase Form (Pure HTML5 & PHP; Zero JavaScript) -->
      <form method="POST" action="product-detail.php?id=<?php echo (int)$product['id']; ?>">
        <input type="hidden" name="cart_action" value="add">
        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
        <input type="hidden" name="primary_image" value="<?php echo htmlspecialchars($product['primary_image']); ?>">

        <div style="margin-bottom: 1.25rem;">
          <label class="form-label" for="selected_size_option">
            Select Your Size:
          </label>
          <select name="selected_size_package" id="selected_size_option" class="form-select" required style="padding: 0.8rem; font-weight: 700;">
            <?php foreach ($sizes as $idx => $sizeOption): 
              $compositeValue = $sizeOption['size_name'] . '|' . $sizeOption['price'];
            ?>
              <option value="<?php echo htmlspecialchars($compositeValue); ?>" <?php echo $idx === 0 ? 'selected' : ''; ?>>
                Size <?php echo htmlspecialchars($sizeOption['size_name']); ?> — <?php echo formatPeso($sizeOption['price']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: flex-end;">
          <div style="width: 100px;">
            <label class="form-label" for="quantity_input">Qty</label>
            <input type="number" id="quantity_input" name="quantity" class="form-input" value="1" min="1" max="50" style="padding: 0.75rem; text-align: center; font-weight: 800;" required>
          </div>

          <button type="submit" class="btn btn-primary" style="flex: 1; height: 50px; font-size: 0.9rem;">
            Add to Bag
          </button>
        </div>
      </form>

      <!-- Craft Specifications -->
      <div style="border-top: 1px solid var(--mocha-light); padding-top: 1.25rem; font-size: 0.8rem; color: var(--mocha-muted); line-height: 1.6;">
        <div>• <strong>Payment Methods Accepted:</strong> GCash, Visa, Debit Mastercard, and Cash on Delivery (COD).</div>
      </div>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
