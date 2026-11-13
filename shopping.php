<?php
/**
 * FREUDIAN FASHION SHOP - MAIN CATALOG & DASHBOARD
 * Displays categorized items with prices in Philippine Pesos (₱)
 * Zero JavaScript Architecture • No Product Codes Displayed
 */
require_once __DIR__ . '/db.php';

// Strict Guard: Unauthenticated users are strictly barred from browsing
if (empty($_SESSION['user_id'])) {
    header('Location: index.php?msg=auth_required');
    exit;
}

$pdo = getDB();

$category_filter = trim($_GET['category'] ?? '');
$search_query    = trim($_GET['search'] ?? '');
$sort_by         = trim($_GET['sort'] ?? 'featured');

$products = [];

if ($pdo) {
    try {
        $sql = "
            SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   (SELECT MIN(price) FROM product_sizes WHERE product_id = p.id) AS min_size_price,
                   (SELECT MAX(price) FROM product_sizes WHERE product_id = p.id) AS max_size_price
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($category_filter)) {
            $sql .= " AND c.slug = :cat_slug";
            $params[':cat_slug'] = $category_filter;
        }

        if (!empty($search_query)) {
            $sql .= " AND (p.name LIKE :q OR p.description LIKE :q OR c.name LIKE :q)";
            $params[':q'] = '%' . $search_query . '%';
        }

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

// Fallback to Freudian seed catalog in Philippine Pesos (₱)
if (empty($products)) {
    $products = getFilteredFreudianCatalog($category_filter, '', $search_query, $sort_by);
}

$page_title = 'Collections Catalog';
require_once __DIR__ . '/header.php';
?>

<!-- Minimalist Editorial Banner -->
<section class="catalog-header-banner">
  <div class="container">
    <div>
      <div class="badge badge-pink" style="margin-bottom: 0.65rem;">Freudian Boutique Atelier</div>
      <h1 class="catalog-main-title">
        Minimalist <span>Elegance.</span>
      </h1>
      <p style="color: var(--mocha-muted); max-width: 580px; font-size: 0.95rem; margin-top: 0.5rem;">
        Architectural silhouettes, handcrafted footwear, and tailored wardrobe essentials. Curated exclusively for registered patrons.
      </p>
    </div>
  </div>
</section>

<div class="container">

  <!-- Integrated Browsing & Category Navigation Toolbar (Pure HTML) -->
  <div class="catalog-toolbar">
    <!-- Integrated Categories -->
    <div class="filter-categories-strip">
      <a href="shopping.php" class="category-tab-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
        All Collections
      </a>
      <a href="shopping.php?category=male_apparel" class="category-tab-btn <?php echo ($category_filter === 'male_apparel') ? 'active' : ''; ?>">
        Male Apparel
      </a>
      <a href="shopping.php?category=female_apparel" class="category-tab-btn <?php echo ($category_filter === 'female_apparel') ? 'active' : ''; ?>">
        Female Apparel
      </a>
      <a href="shopping.php?category=bags" class="category-tab-btn <?php echo ($category_filter === 'bags') ? 'active' : ''; ?>">
        Bags
      </a>
      <a href="shopping.php?category=shoes" class="category-tab-btn <?php echo ($category_filter === 'shoes') ? 'active' : ''; ?>">
        Shoes
      </a>
      <a href="shopping.php?category=accessories" class="category-tab-btn <?php echo ($category_filter === 'accessories') ? 'active' : ''; ?>">
        Accessories
      </a>
    </div>

    <!-- Search & Sort Controls (Pure HTML Submit) -->
    <div class="filter-bottom-row">
      <form method="GET" action="shopping.php" class="filter-form-pure">
        <?php if (!empty($category_filter)): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
        <?php endif; ?>

        <input type="text" name="search" class="form-input" style="min-width: 240px; padding: 0.5rem 0.85rem;" placeholder="Search piece, fabric..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit" class="btn btn-sm btn-dark">Search</button>
        <?php if (!empty($search_query) || !empty($category_filter)): ?>
          <a href="shopping.php" class="btn btn-sm btn-outline">Reset</a>
        <?php endif; ?>
      </form>

      <form method="GET" action="shopping.php" class="filter-form-pure">
        <?php if (!empty($category_filter)): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
        <?php endif; ?>
        <?php if (!empty($search_query)): ?>
          <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
        <?php endif; ?>

        <label for="sort-select" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Sort By:</label>
        <select id="sort-select" name="sort" class="form-select" style="padding: 0.5rem 0.75rem; width: auto;">
          <option value="featured" <?php echo ($sort_by === 'featured') ? 'selected' : ''; ?>>Featured</option>
          <option value="price_asc" <?php echo ($sort_by === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_desc" <?php echo ($sort_by === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
          <option value="name_asc" <?php echo ($sort_by === 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
        </select>
        <button type="submit" class="btn btn-sm btn-outline">Apply</button>
      </form>
    </div>
  </div>

  <!-- Product Catalog Grid (No Product Codes Displayed) -->
  <?php if (empty($products)): ?>
    <div class="cart-table-panel" style="text-align: center; padding: 4rem 2rem;">
      <h2 style="margin-bottom: 0.75rem;">No Pieces Found</h2>
      <p style="color: var(--mocha-muted); margin-bottom: 1.5rem;">
        No items matched your current filter criteria<?php echo !empty($search_query) ? ' for "' . htmlspecialchars($search_query) . '"' : ''; ?>.
      </p>
      <a href="shopping.php" class="btn btn-primary">Browse All Pieces</a>
    </div>
  <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $item): 
        $display_price = $item['min_size_price'] ?? $item['base_price'];
      ?>
        <article class="product-card">
          <!-- Image Box (Strict Sharp Frame) -->
          <div class="card-image-box">
            <a href="product-detail.php?id=<?php echo (int)$item['id']; ?>">
              <img src="<?php echo htmlspecialchars($item['primary_image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="card-img" loading="lazy">
            </a>

            <!-- Badges (Product Code is STRICTLY OMITTED) -->
            <div class="card-badges">
              <span class="badge badge-sand"><?php echo htmlspecialchars($item['category_name']); ?></span>
              <?php if (!empty($item['has_size_pricing'])): ?>
                <span class="badge badge-pink">Size-Based Pricing</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Card Content Body -->
          <div class="card-body">
            <div class="card-category-tag">
              <?php echo htmlspecialchars($item['category_name']); ?>
            </div>

            <h2 class="card-title">
              <a href="product-detail.php?id=<?php echo (int)$item['id']; ?>">
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
                  <?php echo formatPeso($display_price); ?>
                </span>
              </div>

              <a href="product-detail.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-primary">
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
