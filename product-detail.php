<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - PRODUCT DETAIL VIEW & SWIPE GALLERY
 * Interactive AVIF picture variation carousel and real-time size-based pricing
 */
require_once __DIR__ . '/db.php';

$pdo = getDB();

$product_code = trim($_GET['code'] ?? '');
$product_id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
$images  = [];
$sizes   = [];

if ($pdo) {
    try {
        if (!empty($product_code)) {
            $stmt = $pdo->prepare("
                SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.product_code = :code LIMIT 1
            ");
            $stmt->execute([':code' => $product_code]);
            $product = $stmt->fetch();
        } elseif ($product_id > 0) {
            $stmt = $pdo->prepare("
                SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id LIMIT 1
            ");
            $stmt->execute([':id' => $product_id]);
            $product = $stmt->fetch();
        }

        if ($product) {
            // 1. Fetch all AVIF variation images for this product
            $imgStmt = $pdo->prepare("
                SELECT * FROM product_images
                WHERE product_id = :pid
                ORDER BY sort_order ASC, version_number ASC
            ");
            $imgStmt->execute([':pid' => $product['id']]);
            $images = $imgStmt->fetchAll();

            // 2. Fetch all size options and multi-size pricing
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

// Seamless fallback if database is not yet seeded or MySQL is offline
if (!$product) {
    $fallbackItem = getFallbackProductByCodeOrId($product_code, $product_id);
    if ($fallbackItem) {
        $product = $fallbackItem;
        $images  = $fallbackItem['images'] ?? [];
        $sizes   = $fallbackItem['sizes'] ?? [];
    }
}

// Fallback image detection from assets folder if database records are empty
if ($product && empty($images)) {
    $fallbackImages = [];
    $assetPattern = __DIR__ . '/assets/' . strtolower($product['gender']) . '_' . $product['type_slug'] . '_*.*';
    $foundFiles = glob($assetPattern);
    if (!empty($foundFiles)) {
        foreach ($foundFiles as $index => $filePath) {
            $fallbackImages[] = [
                'image_path'     => 'assets/' . basename($filePath),
                'version_number' => $index + 1
            ];
        }
    }
    if (empty($fallbackImages) && !empty($product['primary_image'])) {
        $fallbackImages[] = [
            'image_path'     => $product['primary_image'],
            'version_number' => 1
        ];
    }
    $images = $fallbackImages;
}

// Fallback size pricing if database sizes table is empty
if ($product && empty($sizes)) {
    if (!empty($product['has_size_pricing'])) {
        if ($product['category_slug'] === 'shoes') {
            $sizes = [
                ['size_name' => 'EU 39', 'price' => $product['base_price']],
                ['size_name' => 'EU 40', 'price' => $product['base_price'] + 5],
                ['size_name' => 'EU 41', 'price' => $product['base_price'] + 10],
                ['size_name' => 'EU 42', 'price' => $product['base_price'] + 15]
            ];
        } else {
            $sizes = [
                ['size_name' => 'S',  'price' => $product['base_price']],
                ['size_name' => 'M',  'price' => $product['base_price'] + 8],
                ['size_name' => 'L',  'price' => $product['base_price'] + 15],
                ['size_name' => 'XL', 'price' => $product['base_price'] + 22]
            ];
        }
    } else {
        $sizes = [
            ['size_name' => 'One Size', 'price' => $product['base_price']]
        ];
    }
}

$page_title = $product ? htmlspecialchars($product['name']) : 'Product Not Found';
require_once __DIR__ . '/header.php';
?>

<div class="container" style="padding-top: 1.5rem;">

  <?php if (!$product): ?>
    <div class="cart-items-panel" style="text-align: center; padding: 4rem 2rem;">
      <h1 style="margin-bottom: 1rem;">Product Not Found</h1>
      <p style="color: var(--mocha-muted); margin-bottom: 2rem;">
        The requested piece could not be located in our atelier inventory.
      </p>
      <a href="shopping.php" class="btn btn-primary">Return to Catalog</a>
    </div>
  <?php else: 
    $initialSize = $sizes[0]['size_name'] ?? 'Standard';
    $initialPrice = (float)($sizes[0]['price'] ?? $product['base_price']);
  ?>

    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb">
      <a href="shopping.php">Atelier</a>
      <span>/</span>
      <a href="shopping.php?category=<?php echo urlencode($product['category_slug'] ?? 'all'); ?>">
        <?php echo htmlspecialchars($product['category_name'] ?? 'Collection'); ?>
      </a>
      <span>/</span>
      <a href="shopping.php?gender=<?php echo urlencode($product['gender']); ?>">
        <?php echo htmlspecialchars($product['gender']); ?>
      </a>
      <span>/</span>
      <span><?php echo htmlspecialchars($product['product_code']); ?></span>
    </nav>

    <!-- Main Detail Layout -->
    <div class="product-detail-layout">

      <!-- LEFT: Interactive AVIF Swipe Carousel & Angle Gallery -->
      <div class="gallery-wrapper">
        <div class="gallery-stage" id="galleryStage">
          <!-- Slider Track -->
          <div class="gallery-slider">
            <?php foreach ($images as $idx => $img): ?>
              <div class="gallery-slide">
                <img src="<?php echo htmlspecialchars($img['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?> - Angle <?php echo $img['version_number']; ?>" 
                     loading="<?php echo $idx === 0 ? 'eager' : 'lazy'; ?>">
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Carousel Controls -->
          <?php if (count($images) > 1): ?>
            <button type="button" class="gallery-nav-btn gallery-prev" aria-label="Previous Image">&larr;</button>
            <button type="button" class="gallery-nav-btn gallery-next" aria-label="Next Image">&rarr;</button>
          <?php endif; ?>

          <!-- Version Indicator Tag -->
          <div class="gallery-meta-tag">
            Version 1 of <?php echo count($images); ?>
          </div>
        </div>

        <!-- Dot Indicators -->
        <?php if (count($images) > 1): ?>
          <div class="gallery-indicator-dots">
            <?php foreach ($images as $i => $img): ?>
              <div class="gallery-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Thumbnail Strip for Immediate Jumps -->
        <?php if (count($images) > 1): ?>
          <div class="gallery-thumbs">
            <?php foreach ($images as $i => $img): ?>
              <div class="gallery-thumb-item <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>">
                <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Thumbnail <?php echo $i + 1; ?>">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div style="margin-top: 1rem; font-size: 0.72rem; color: var(--mocha-muted); text-transform: uppercase; letter-spacing: 0.1em; text-align: center;">
          Swipe, use arrows &larr; &rarr;, or click thumbnails to loop through .avif variations
        </div>
      </div>

      <!-- RIGHT: Product Specifications, Size-Based Pricing, and Cart Action -->
      <div class="product-info-panel">
        <div class="product-code-heading">
          CODE: <?php echo htmlspecialchars($product['product_code']); ?>
        </div>

        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
          <span class="badge badge-sand"><?php echo htmlspecialchars($product['category_name']); ?></span>
          <span class="badge badge-dark"><?php echo htmlspecialchars($product['gender']); ?></span>
          <?php if (!empty($product['has_size_pricing'])): ?>
            <span class="badge badge-pink">Size-Based Pricing</span>
          <?php endif; ?>
        </div>

        <h1 class="product-page-title">
          <?php echo htmlspecialchars($product['name']); ?>
        </h1>

        <!-- Real-Time Price Display -->
        <div class="product-detail-price-box">
          <div id="active-product-price" class="active-price-display">
            $<?php echo number_format($initialPrice, 2); ?>
          </div>

          <?php if (!empty($product['has_size_pricing'])): ?>
            <div class="size-price-alert">
              Dynamic Sizing: Price adjusts with selected size
            </div>
          <?php endif; ?>
        </div>

        <p class="product-editorial-desc">
          <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </p>

        <!-- Add to Cart Form -->
        <form method="POST" action="cart.php" id="addToCartForm">
          <input type="hidden" name="cart_action" value="add">
          <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
          <input type="hidden" name="product_code" value="<?php echo htmlspecialchars($product['product_code']); ?>">
          <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
          <input type="hidden" name="primary_image" value="<?php echo htmlspecialchars($product['primary_image']); ?>">
          <input type="hidden" name="unit_price" id="selected-unit-price" value="<?php echo number_format($initialPrice, 2, '.', ''); ?>">

          <!-- Interactive Size Selector with dynamic price hook -->
          <div class="size-selection-area">
            <label class="option-label">
              Select Size & Proportion:
            </label>

            <div class="sizes-grid">
              <?php foreach ($sizes as $idx => $size): ?>
                <div class="size-pill-option">
                  <input type="radio" 
                         name="selected_size" 
                         id="size-opt-<?php echo $idx; ?>" 
                         value="<?php echo htmlspecialchars($size['size_name']); ?>" 
                         data-price="<?php echo (float)$size['price']; ?>"
                         class="size-radio-input" 
                         <?php echo $idx === 0 ? 'checked' : ''; ?> 
                         required>
                  <label for="size-opt-<?php echo $idx; ?>" class="size-btn-label">
                    <span class="size-name-text"><?php echo htmlspecialchars($size['size_name']); ?></span>
                    <span class="size-price-tag">$<?php echo number_format($size['price'], 2); ?></span>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Quantity Selector & Submit CTA -->
          <div class="purchase-action-group">
            <div class="qty-control">
              <button type="button" class="qty-btn qty-btn-dec" aria-label="Decrease Quantity">-</button>
              <input type="number" name="quantity" class="qty-input" value="1" min="1" max="50">
              <button type="button" class="qty-btn qty-btn-inc" aria-label="Increase Quantity">+</button>
            </div>

            <button type="submit" class="btn btn-primary btn-add-cart">
              Add to Shopping Bag
            </button>
          </div>
        </form>

        <!-- Product Craft Details List -->
        <div class="product-features-list">
          <div class="feature-item">
            <span class="feature-bullet"></span>
            <span>Hand-finished architectural tailoring with sharp edge contouring</span>
          </div>
          <div class="feature-item">
            <span class="feature-bullet"></span>
            <span>High-definition multi-angle AVIF asset inspection verified</span>
          </div>
          <div class="feature-item">
            <span class="feature-bullet"></span>
            <span>Complimentary signature boutique dust bag and wooden hanger included</span>
          </div>
          <div class="feature-item">
            <span class="feature-bullet"></span>
            <span>Worldwide expedited delivery with flexible Cash on Delivery or Card payment</span>
          </div>
        </div>

      </div>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
