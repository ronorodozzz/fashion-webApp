<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - PRODUCT CATALOG & DASHBOARD
 * Standalone catalog with multi-category filters, search, and size-pricing badges
 */
require_once __DIR__ . '/db.php';

$pdo = getDB();

// Get filter inputs
$category_filter = trim($_GET['category'] ?? '');
$gender_filter   = trim($_GET['gender'] ?? '');
$search_query    = trim($_GET['search'] ?? '');
$sort_by         = trim($_GET['sort'] ?? 'featured');

$products = [];
$categories = [];

if ($pdo) {
    try {
        // Fetch categories for toolbar
        $catStmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
        $categories = $catStmt->fetchAll();

        // Build product query dynamically
        $sql = "
            SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   (SELECT MIN(price) FROM product_sizes WHERE product_id = p.id) AS min_size_price,
                   (SELECT MAX(price) FROM product_sizes WHERE product_id = p.id) AS max_size_price
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE 1=1
        ";
        $params = [];

        // Category filter
        if (!empty($category_filter)) {
            $sql .= " AND c.slug = :cat_slug";
            $params[':cat_slug'] = $category_filter;
        }

        // Gender filter
        if (!empty($gender_filter)) {
            $sql .= " AND p.gender = :gender";
            $params[':gender'] = $gender_filter;
        }

        // Search query filter
        if (!empty($search_query)) {
            $sql .= " AND (p.name LIKE :q OR p.description LIKE :q OR p.product_code LIKE :q OR c.name LIKE :q)";
            $params[':q'] = '%' . $search_query . '%';
        }

        // Sort order
        switch ($sort_by) {
            case 'price_asc':
                $sql .= " ORDER BY p.base_price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.base_price DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY p.name ASC";
                break;
            case 'code_asc':
                $sql .= " ORDER BY p.product_code ASC";
                break;
            case 'featured':
            default:
                $sql .= " ORDER BY p.is_featured DESC, p.id ASC";
                break;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        $products = [];
    }
}

// Seamless fallback if database is not yet seeded or MySQL is offline
if (empty($products)) {
    $products = getFilteredFallbackCatalog($category_filter, $gender_filter, $search_query, $sort_by);
}

$page_title = 'Curated Catalog & Dashboard';
require_once __DIR__ . '/header.php';
?>

<!-- Minimalist Editorial Hero Section -->
<section class="hero-editorial">
  <div class="container">
    <div class="hero-content">
      <div>
        <div class="badge badge-pink" style="margin-bottom: 0.75rem;">Spring/Summer 2026 Collection</div>
        <h1 class="hero-headline">
          Sharp Lines. <span>Timeless Craft.</span>
        </h1>
        <p class="hero-tagline">
          Understated luxury and architectural proportions for men and women. Hand-selected fabrics, vegetable-tanned leathers, and tailored precision.
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-item">
          <div class="stat-num">12</div>
          <div class="stat-label">Curated Pieces</div>
        </div>
        <div class="stat-item" style="border-left: 1px solid var(--mocha-light); padding-left: 1.5rem;">
          <div class="stat-num">25</div>
          <div class="stat-label">AVIF Angle Views</div>
        </div>
        <div class="stat-item" style="border-left: 1px solid var(--mocha-light); padding-left: 1.5rem;">
          <div class="stat-num">100%</div>
          <div class="stat-label">Sharp Edges</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Main Catalog Container -->
<div class="container">

  <!-- Filter & Search Toolbar -->
  <div class="catalog-toolbar">
    <div class="filter-row">
      <!-- Category & Gender Quick Pills -->
      <div class="filter-categories">
        <a href="shopping.php" class="filter-pill <?php echo (empty($category_filter) && empty($gender_filter)) ? 'active' : ''; ?>">
          All Collections
        </a>
        <a href="shopping.php?gender=Men" class="filter-pill <?php echo ($gender_filter === 'Men') ? 'active' : ''; ?>">
          Men's Apparel
        </a>
        <a href="shopping.php?gender=Women" class="filter-pill <?php echo ($gender_filter === 'Women') ? 'active' : ''; ?>">
          Women's Apparel
        </a>
        <a href="shopping.php?category=bags" class="filter-pill <?php echo ($category_filter === 'bags') ? 'active' : ''; ?>">
          Bags
        </a>
        <a href="shopping.php?category=shoes" class="filter-pill <?php echo ($category_filter === 'shoes') ? 'active' : ''; ?>">
          Shoes
        </a>
        <a href="shopping.php?category=accessories" class="filter-pill <?php echo ($category_filter === 'accessories') ? 'active' : ''; ?>">
          Accessories
        </a>
      </div>

      <!-- Search & Sort Controls -->
      <div class="filter-controls">
        <form method="GET" action="shopping.php" class="search-form">
          <?php if (!empty($category_filter)): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
          <?php endif; ?>
          <?php if (!empty($gender_filter)): ?>
            <input type="hidden" name="gender" value="<?php echo htmlspecialchars($gender_filter); ?>">
          <?php endif; ?>

          <input type="text" name="search" class="search-input" placeholder="Search by name, code..." value="<?php echo htmlspecialchars($search_query); ?>">
          <button type="submit" class="search-btn">Search</button>
        </form>

        <form method="GET" action="shopping.php">
          <?php if (!empty($category_filter)): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
          <?php endif; ?>
          <?php if (!empty($gender_filter)): ?>
            <input type="hidden" name="gender" value="<?php echo htmlspecialchars($gender_filter); ?>">
          <?php endif; ?>
          <?php if (!empty($search_query)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
          <?php endif; ?>

          <select name="sort" class="select-custom" onchange="this.form.submit()">
            <option value="featured" <?php echo ($sort_by === 'featured') ? 'selected' : ''; ?>>Sort: Featured</option>
            <option value="price_asc" <?php echo ($sort_by === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo ($sort_by === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="name_asc" <?php echo ($sort_by === 'name_asc') ? 'selected' : ''; ?>>Alphabetical (A-Z)</option>
            <option value="code_asc" <?php echo ($sort_by === 'code_asc') ? 'selected' : ''; ?>>Product Code</option>
          </select>
        </form>
      </div>
    </div>
  </div>

  <!-- Notification Banner for Added Item or Auth -->
  <?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success">
      <strong>Welcome to Trend & Fashion Atelier!</strong> Your account was created and you are now signed in.
    </div>
  <?php elseif (isset($_GET['auth']) && $_GET['auth'] === 'success'): ?>
    <div class="alert alert-success">
      <strong>Welcome back!</strong> Signed in as <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></strong>.
    </div>
  <?php elseif (isset($_GET['added'])): ?>
    <div class="alert alert-info">
      <strong>Item Added to Bag:</strong> Selection has been recorded in your session cart. <a href="cart.php" class="underline-link" style="font-weight: 800; margin-left: 0.5rem;">View Bag & Checkout &rarr;</a>
    </div>
  <?php endif; ?>

  <!-- Product Catalog Grid -->
  <?php if (empty($products)): ?>
    <div class="cart-items-panel" style="text-align: center; padding: 4rem 2rem;">
      <h2 style="margin-bottom: 1rem;">No Products Found</h2>
      <p style="color: var(--mocha-muted); margin-bottom: 1.5rem;">
        No items matched your current filter criteria<?php echo !empty($search_query) ? ' for "' . htmlspecialchars($search_query) . '"' : ''; ?>.
      </p>
      <a href="shopping.php" class="btn btn-primary">Reset Filters</a>
    </div>
  <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $item): ?>
        <article class="product-card">
          <!-- Image Box with sharp frame -->
          <div class="card-image-wrap">
            <a href="product-detail.php?code=<?php echo urlencode($item['product_code']); ?>">
              <img src="<?php echo htmlspecialchars($item['primary_image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="card-img" loading="lazy">
            </a>

            <!-- Badges -->
            <div class="card-badges">
              <span class="badge badge-sand"><?php echo htmlspecialchars($item['category_name']); ?></span>
              <span class="badge badge-dark"><?php echo htmlspecialchars($item['gender']); ?></span>
              <?php if (!empty($item['has_size_pricing'])): ?>
                <span class="badge badge-pink">Size-Based Pricing</span>
              <?php endif; ?>
            </div>

            <!-- Product Code Badge -->
            <div class="code-badge"><?php echo htmlspecialchars($item['product_code']); ?></div>
          </div>

          <!-- Card Content Body -->
          <div class="card-body">
            <div class="card-category-gender">
              <?php echo htmlspecialchars($item['gender']); ?> • <?php echo htmlspecialchars($item['category_name']); ?>
            </div>

            <h2 class="card-title">
              <a href="product-detail.php?code=<?php echo urlencode($item['product_code']); ?>">
                <?php echo htmlspecialchars($item['name']); ?>
              </a>
            </h2>

            <p class="card-desc">
              <?php echo htmlspecialchars($item['description']); ?>
            </p>

            <div class="card-footer">
              <div class="price-box">
                <span class="price-label">
                  <?php echo !empty($item['has_size_pricing']) ? 'Starting From' : 'Price'; ?>
                </span>
                <span class="price-amount">
                  $<?php echo number_format($item['min_size_price'] ?? $item['base_price'], 2); ?>
                </span>
                <?php if (!empty($item['has_size_pricing'])): ?>
                  <span class="size-pricing-note">Price varies by size</span>
                <?php endif; ?>
              </div>

              <a href="product-detail.php?code=<?php echo urlencode($item['product_code']); ?>" class="btn btn-sm btn-primary">
                View Details
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
