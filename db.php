<?php
/**
 * Trend & Fashion Boutique Shop
 * Database Connection Handler (PDO MySQL)
 */

if (session_status() === PHP_SESSION_NONE) {
    // Fallback to local session folder if default system session path is not writable
    $defaultSavePath = session_save_path();
    if (empty($defaultSavePath) || !@is_writable($defaultSavePath)) {
        $localSessionDir = __DIR__ . '/sessions';
        if (!is_dir($localSessionDir)) {
            @mkdir($localSessionDir, 0777, true);
        }
        if (is_dir($localSessionDir) && is_writable($localSessionDir)) {
            session_save_path($localSessionDir);
        }
    }
    @session_start();
}

$db_host = 'localhost';
$db_port = '3306';
$db_name = 'fashion_boutique';
$db_user = 'root';
$db_pass = '';

$pdo = null;
$db_connected = false;
$db_error_message = null;

try {
    // 1. Attempt standard connection to the specific database
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $db_connected = true;

    // Check if tables exist; if not, try auto-seeding
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'products'")->fetchAll();
    if (empty($tableCheck)) {
        $sqlFile = __DIR__ . '/database_setup.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $pdo->exec($sqlContent);
        }
    }
} catch (PDOException $e) {
    // Check if error is unknown database (error code 1049)
    if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            // Attempt to connect to MySQL server without dbname to create it
            $dsnNoDb = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
            $tempPdo = new PDO($dsnNoDb, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $tempPdo->exec("USE `{$db_name}`");

            $sqlFile = __DIR__ . '/database_setup.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $tempPdo->exec($sqlContent);
            }

            // Reconnect to the created database
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $db_connected = true;
        } catch (Exception $setupEx) {
            $db_error_message = "Database initialization failed: " . $setupEx->getMessage();
        }
    } else {
        $db_error_message = "Could not connect to MySQL server. Please ensure MySQL is started in your XAMPP Control Panel. Error: " . $e->getMessage();
    }
}

/**
 * Helper function to safely get PDO connection
 */
function getDB() {
    global $pdo, $db_connected;
    if (!$db_connected || $pdo === null) {
        return null;
    }
    return $pdo;
}

/**
 * Helper function to render a database warning alert if MySQL is not ready
 */
function renderDbNotice() {
    global $db_connected, $db_error_message;
    if (!$db_connected) {
        echo '<div class="db-alert-banner">';
        echo '<strong>[SYSTEM NOTICE] MySQL Connection Inactive:</strong> ' . htmlspecialchars($db_error_message ?? 'Database unreachable') . '<br>';
        echo '<span>Please ensure MySQL is running in your <strong>XAMPP Control Panel</strong>. You can also import the schema manually via phpMyAdmin using <a href="database_setup.txt" download style="color: var(--pink-accent); font-weight: 700;">database_setup.txt</a>.</span>';
        echo '</div>';
    }
}

/**
 * Catalog Seed Data Definition (Used for MySQL seed & instant fallback)
 */
function getFallbackCatalog() {
    return [
        [
            'id' => 1,
            'product_code' => 'MBAG-001',
            'name' => "Men's Artisan Leather Weekender Duffle",
            'category_id' => 2,
            'category_name' => 'Bags',
            'category_slug' => 'bags',
            'gender' => 'Men',
            'type_slug' => 'bag',
            'base_price' => 145.00,
            'has_size_pricing' => 0,
            'description' => 'Hand-stitched from full-grain pull-up leather, this timeless travel duffle pairs rugged durability with minimalist architectural proportions. Features burnished edges, solid brass hardware, water-resistant canvas lining, and a reinforced bottom panel.',
            'primary_image' => 'assets/men_bag_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_bag_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/men_bag_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 145.00]
            ]
        ],
        [
            'id' => 2,
            'product_code' => 'MJCK-001',
            'name' => "Men's Structured Wool Hybrid Jacket",
            'category_id' => 1,
            'category_name' => 'Apparel & Fashion',
            'category_slug' => 'apparel',
            'gender' => 'Men',
            'type_slug' => 'jacket',
            'base_price' => 110.00,
            'has_size_pricing' => 1,
            'description' => 'Tailored from a mid-weight virgin wool blend with a clean boxy silhouette. Sharp notch lapels, concealed horn-button placket, and deep jet pockets capture contemporary masculine minimalism. Size-adjusted fabric tailoring for seamless fit.',
            'primary_image' => 'assets/men_jacket_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_jacket_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/men_jacket_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'S', 'price' => 110.00],
                ['size_name' => 'M', 'price' => 120.00],
                ['size_name' => 'L', 'price' => 128.00],
                ['size_name' => 'XL', 'price' => 138.00]
            ]
        ],
        [
            'id' => 3,
            'product_code' => 'MSHR-001',
            'name' => "Men's Relaxed Linen-Cotton Plain Shirt",
            'category_id' => 1,
            'category_name' => 'Apparel & Fashion',
            'category_slug' => 'apparel',
            'gender' => 'Men',
            'type_slug' => 'plainshirt',
            'base_price' => 42.00,
            'has_size_pricing' => 1,
            'description' => 'Spun from breathable French flax and combed organic cotton. Features an unstructured spread collar, French seams, and a relaxed drape designed for warm afternoons and understated layering.',
            'primary_image' => 'assets/men_plainshirt_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_plainshirt_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/men_plainshirt_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'S', 'price' => 42.00],
                ['size_name' => 'M', 'price' => 48.00],
                ['size_name' => 'L', 'price' => 54.00],
                ['size_name' => 'XL', 'price' => 60.00]
            ]
        ],
        [
            'id' => 4,
            'product_code' => 'MSHO-001',
            'name' => "Men's Hand-Burnished Leather Derby Shoes",
            'category_id' => 3,
            'category_name' => 'Shoes',
            'category_slug' => 'shoes',
            'gender' => 'Men',
            'type_slug' => 'shoe',
            'base_price' => 135.00,
            'has_size_pricing' => 1,
            'description' => 'Classic five-eyelet open-laced Derby shoes handcrafted in Tuscany. Features vegetable-tanned calfskin, Goodyear welted construction, and stacked leather heels with rubberized grip inserts.',
            'primary_image' => 'assets/men_shoe_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_shoe_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/men_shoe_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'EU 40', 'price' => 135.00],
                ['size_name' => 'EU 41', 'price' => 142.00],
                ['size_name' => 'EU 42', 'price' => 150.00],
                ['size_name' => 'EU 43', 'price' => 158.00],
                ['size_name' => 'EU 44', 'price' => 165.00]
            ]
        ],
        [
            'id' => 5,
            'product_code' => 'MWAT-001',
            'name' => "Men's Bauhaus Monochrome Chronograph Watch",
            'category_id' => 4,
            'category_name' => 'Accessories',
            'category_slug' => 'accessories',
            'gender' => 'Men',
            'type_slug' => 'watch',
            'base_price' => 185.00,
            'has_size_pricing' => 0,
            'description' => 'A study in functional reductionism. Brushed 316L stainless steel case housing a precision Japanese quartz movement, scratch-resistant sapphire crystal glass, matte mocha dial, and quick-release vegetable-tanned leather band.',
            'primary_image' => 'assets/men_watch_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_watch_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/men_watch_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 185.00]
            ]
        ],
        [
            'id' => 6,
            'product_code' => 'WBAG-001',
            'name' => "Women's Sculptural Structured Leather Tote",
            'category_id' => 2,
            'category_name' => 'Bags',
            'category_slug' => 'bags',
            'gender' => 'Women',
            'type_slug' => 'bag',
            'base_price' => 165.00,
            'has_size_pricing' => 0,
            'description' => 'Defined by sharp architectural geometry and supple Nappa leather. Engineered with magnetic closures, suede interior, detachable crossbody strap, and custom gold-finished hardware for versatile luxury styling.',
            'primary_image' => 'assets/women_bag_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_bag_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/women_bag_1_version_2.avif', 'version_number' => 2],
                ['image_path' => 'assets/women_bag_1_version_3.avif', 'version_number' => 3]
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 165.00]
            ]
        ],
        [
            'id' => 7,
            'product_code' => 'WJCK-001',
            'name' => "Women's Minimalist Cropped Utility Jacket",
            'category_id' => 1,
            'category_name' => 'Apparel & Fashion',
            'category_slug' => 'apparel',
            'gender' => 'Women',
            'type_slug' => 'jacket',
            'base_price' => 95.00,
            'has_size_pricing' => 1,
            'description' => 'A clean cropped silhouette tailored from dense organic cotton twill. Features sharp point collar, concealed zipper with horn snap placket, and angular front storm flap detailing for an elevated contemporary edge.',
            'primary_image' => 'assets/women_jacket_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_jacket_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/women_jacket_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'XS', 'price' => 95.00],
                ['size_name' => 'S', 'price' => 105.00],
                ['size_name' => 'M', 'price' => 115.00],
                ['size_name' => 'L', 'price' => 125.00]
            ]
        ],
        [
            'id' => 8,
            'product_code' => 'WJCK-002',
            'name' => "Women's Belted Double-Breasted Wool Coat",
            'category_id' => 1,
            'category_name' => 'Apparel & Fashion',
            'category_slug' => 'apparel',
            'gender' => 'Women',
            'type_slug' => 'jacket',
            'base_price' => 140.00,
            'has_size_pricing' => 1,
            'description' => 'Crafted from double-faced Australian wool in rich mocha-tinged sand tones. Features exaggerated peak lapels, raglan sleeves, a self-tie waist belt, and deep welt pockets.',
            'primary_image' => 'assets/women_jacket_2_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_jacket_2_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/women_jacket_2_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'S', 'price' => 140.00],
                ['size_name' => 'M', 'price' => 152.00],
                ['size_name' => 'L', 'price' => 165.00],
                ['size_name' => 'XL', 'price' => 178.00]
            ]
        ],
        [
            'id' => 9,
            'product_code' => 'WSHR-001',
            'name' => "Women's Mulberry Silk Drape Blouse Shirt",
            'category_id' => 1,
            'category_name' => 'Apparel & Fashion',
            'category_slug' => 'apparel',
            'gender' => 'Women',
            'type_slug' => 'shirt',
            'base_price' => 58.00,
            'has_size_pricing' => 1,
            'description' => 'Tailored from lustrous 19mm Mulberry silk with fluid drape and subtle matte sheen. Features mother-of-pearl buttons, clean extended cuffs, and a minimal band collar for effortless refinement.',
            'primary_image' => 'assets/women_shirt_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_shirt_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/women_shirt_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'XS', 'price' => 58.00],
                ['size_name' => 'S', 'price' => 64.00],
                ['size_name' => 'M', 'price' => 70.00],
                ['size_name' => 'L', 'price' => 76.00]
            ]
        ],
        [
            'id' => 10,
            'product_code' => 'WSHO-001',
            'name' => "Women's Sculpted Block-Heel Leather Mules",
            'category_id' => 3,
            'category_name' => 'Shoes',
            'category_slug' => 'shoes',
            'gender' => 'Women',
            'type_slug' => 'shoe',
            'base_price' => 110.00,
            'has_size_pricing' => 1,
            'description' => 'Sleek open-back square-toe mules shaped from glove-soft calfskin. Features a hand-carved geometric wooden block heel (55mm), cushioned leather footbed, and sharp modern welt line.',
            'primary_image' => 'assets/women_shoe_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_shoe_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/women_shoe_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'EU 36', 'price' => 110.00],
                ['size_name' => 'EU 37', 'price' => 118.00],
                ['size_name' => 'EU 38', 'price' => 125.00],
                ['size_name' => 'EU 39', 'price' => 132.00],
                ['size_name' => 'EU 40', 'price' => 140.00]
            ]
        ],
        [
            'id' => 11,
            'product_code' => 'WSHO-002',
            'name' => "Women's Architectural Pointed Ankle Boots",
            'category_id' => 3,
            'category_name' => 'Shoes',
            'category_slug' => 'shoes',
            'gender' => 'Women',
            'type_slug' => 'shoe',
            'base_price' => 145.00,
            'has_size_pricing' => 1,
            'description' => 'Sharp pointed silhouette with an inner ankle zip closure. Handcrafted from smooth Italian leather, resting on an angled kitten heel with rubber non-slip injected sole.',
            'primary_image' => 'assets/women_shoe_2_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_shoe_2_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/women_shoe_2_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'EU 36', 'price' => 145.00],
                ['size_name' => 'EU 37', 'price' => 155.00],
                ['size_name' => 'EU 38', 'price' => 165.00],
                ['size_name' => 'EU 39', 'price' => 175.00],
                ['size_name' => 'EU 40', 'price' => 185.00]
            ]
        ],
        [
            'id' => 12,
            'product_code' => 'WWAT-001',
            'name' => "Women's Rose Gold Petite Milanese Mesh Watch",
            'category_id' => 4,
            'category_name' => 'Accessories',
            'category_slug' => 'accessories',
            'gender' => 'Women',
            'type_slug' => 'watch',
            'base_price' => 175.00,
            'has_size_pricing' => 0,
            'description' => 'Delicate 28mm stainless steel casing plated in warm rose gold. Features an ultra-slim profile, sunray sand dial with minimalist indices, and an adjustable Milanese magnetic mesh strap.',
            'primary_image' => 'assets/women_watch_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_watch_1_version_1.avif', 'version_number' => 1],
                ['image_path' => 'assets/women_watch_1_version_2.avif', 'version_number' => 2]
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 175.00]
            ]
        ]
    ];
}

/**
 * Filter catalog for fallback usage
 */
function getFilteredFallbackCatalog($category = '', $gender = '', $search = '', $sort = 'featured') {
    $catalog = getFallbackCatalog();
    $results = [];

    foreach ($catalog as $item) {
        // Calculate min/max price for size-based pricing
        $prices = array_column($item['sizes'], 'price');
        $item['min_size_price'] = !empty($prices) ? min($prices) : $item['base_price'];
        $item['max_size_price'] = !empty($prices) ? max($prices) : $item['base_price'];

        // Category filter
        if (!empty($category) && $item['category_slug'] !== $category) {
            continue;
        }

        // Gender filter
        if (!empty($gender) && $item['gender'] !== $gender) {
            continue;
        }

        // Search filter
        if (!empty($search)) {
            $term = strtolower($search);
            $match = (strpos(strtolower($item['name']), $term) !== false) ||
                     (strpos(strtolower($item['description']), $term) !== false) ||
                     (strpos(strtolower($item['product_code']), $term) !== false) ||
                     (strpos(strtolower($item['category_name']), $term) !== false);
            if (!$match) continue;
        }

        $results[] = $item;
    }

    // Sort
    if ($sort === 'price_asc') {
        usort($results, fn($a, $b) => $a['base_price'] <=> $b['base_price']);
    } elseif ($sort === 'price_desc') {
        usort($results, fn($a, $b) => $b['base_price'] <=> $a['base_price']);
    } elseif ($sort === 'name_asc') {
        usort($results, fn($a, $b) => strcmp($a['name'], $b['name']));
    } elseif ($sort === 'code_asc') {
        usort($results, fn($a, $b) => strcmp($a['product_code'], $b['product_code']));
    }

    return $results;
}

/**
 * Find single product from fallback
 */
function getFallbackProductByCodeOrId($code = '', $id = 0) {
    $catalog = getFallbackCatalog();
    foreach ($catalog as $item) {
        if (!empty($code) && strcasecmp($item['product_code'], $code) === 0) {
            return $item;
        }
        if ($id > 0 && (int)$item['id'] === (int)$id) {
            return $item;
        }
    }
    return null;
}
