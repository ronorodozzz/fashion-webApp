<?php
/**
 * FREUDIAN FASHION SHOP - DATABASE CONNECTION HANDLER
 * Secure PDO Connection to MySQL (Freudian Database)
 * Zero JavaScript Architecture
 */

if (session_status() === PHP_SESSION_NONE) {
    // Session directory fallback
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
$db_name = 'freudian_fashion';
$db_user = 'root';
$db_pass = '';

$pdo = null;
$db_connected = false;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $db_connected = true;

    // Check if tables exist, auto-seed if needed
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'products'")->fetchAll();
    if (empty($tableCheck)) {
        $sqlFile = __DIR__ . '/database_setup.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $pdo->exec($sqlContent);
        }
    }
} catch (PDOException $e) {
    // If database does not exist, try creating it automatically
    if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
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

            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $db_connected = true;
        } catch (Exception $setupEx) {
            $pdo = null;
            $db_connected = false;
        }
    } else {
        $pdo = null;
        $db_connected = false;
    }
}

/**
 * Safely get PDO instance
 */
function getDB() {
    global $pdo, $db_connected;
    if (!$db_connected || $pdo === null) {
        return null;
    }
    return $pdo;
}

/**
 * Format currency in Philippine Pesos (₱)
 */
function formatPeso($amount) {
    return '₱' . number_format((float)$amount, 2);
}

/**
 * Pure PHP Seed Catalog in Philippine Pesos (₱)
 */
function getFreudianCatalog() {
    return [
        [
            'id' => 1,
            'product_code' => 'MBAG-001',
            'name' => "Artisan Leather Weekender Duffle",
            'category_id' => 3,
            'category_name' => 'Bags',
            'category_slug' => 'bags',
            'gender' => 'Men',
            'type_slug' => 'bag',
            'base_price' => 4500.00,
            'has_size_pricing' => 0,
            'description' => 'Hand-stitched from full-grain pull-up leather, this timeless travel duffle pairs rugged durability with minimalist architectural proportions. Features burnished edges, solid brass hardware, water-resistant canvas lining, and a reinforced bottom panel.',
            'primary_image' => 'assets/men_bag_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_bag_1_version_1.avif', 'version_number' => 1, 'caption' => 'Front Architectural Perspective'],
                ['image_path' => 'assets/men_bag_1_version_2.avif', 'version_number' => 2, 'caption' => 'Side Structural Profile']
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 4500.00]
            ]
        ],
        [
            'id' => 2,
            'product_code' => 'MJCK-001',
            'name' => "Structured Wool Hybrid Jacket",
            'category_id' => 1,
            'category_name' => 'Male Apparel',
            'category_slug' => 'male_apparel',
            'gender' => 'Men',
            'type_slug' => 'jacket',
            'base_price' => 3200.00,
            'has_size_pricing' => 1,
            'description' => 'Tailored from a mid-weight virgin wool blend with a clean boxy silhouette. Sharp notch lapels, concealed horn-button placket, and deep jet pockets capture contemporary masculine minimalism. Size-adjusted fabric tailoring for seamless fit.',
            'primary_image' => 'assets/men_jacket_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_jacket_1_version_1.avif', 'version_number' => 1, 'caption' => 'Full Front Tailoring'],
                ['image_path' => 'assets/men_jacket_1_version_2.avif', 'version_number' => 2, 'caption' => 'Detail Collar & Placket']
            ],
            'sizes' => [
                ['size_name' => 'S', 'price' => 3200.00],
                ['size_name' => 'M', 'price' => 3500.00],
                ['size_name' => 'L', 'price' => 3800.00],
                ['size_name' => 'XL', 'price' => 4100.00]
            ]
        ],
        [
            'id' => 3,
            'product_code' => 'MSHR-001',
            'name' => "Relaxed Linen-Cotton Plain Shirt",
            'category_id' => 1,
            'category_name' => 'Male Apparel',
            'category_slug' => 'male_apparel',
            'gender' => 'Men',
            'type_slug' => 'plainshirt',
            'base_price' => 1450.00,
            'has_size_pricing' => 1,
            'description' => 'Spun from breathable French flax and combed organic cotton. Features an unstructured spread collar, French seams, and a relaxed drape designed for warm afternoons and understated layering.',
            'primary_image' => 'assets/men_plainshirt_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_plainshirt_1_version_1.avif', 'version_number' => 1, 'caption' => 'Front Minimalist Drape'],
                ['image_path' => 'assets/men_plainshirt_1_version_2.avif', 'version_number' => 2, 'caption' => 'Textile Weave & Cuff View']
            ],
            'sizes' => [
                ['size_name' => 'S', 'price' => 1450.00],
                ['size_name' => 'M', 'price' => 1600.00],
                ['size_name' => 'L', 'price' => 1750.00],
                ['size_name' => 'XL', 'price' => 1900.00]
            ]
        ],
        [
            'id' => 4,
            'product_code' => 'MSHO-001',
            'name' => "Hand-Burnished Leather Derby Shoes",
            'category_id' => 4,
            'category_name' => 'Shoes',
            'category_slug' => 'shoes',
            'gender' => 'Men',
            'type_slug' => 'shoe',
            'base_price' => 3900.00,
            'has_size_pricing' => 1,
            'description' => 'Classic five-eyelet open-laced Derby shoes handcrafted in Tuscany. Features vegetable-tanned calfskin, Goodyear welted construction, and stacked leather heels with rubberized grip inserts.',
            'primary_image' => 'assets/men_shoe_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_shoe_1_version_1.avif', 'version_number' => 1, 'caption' => 'Profile Stance & Welt'],
                ['image_path' => 'assets/men_shoe_1_version_2.avif', 'version_number' => 2, 'caption' => 'Overhead Vamp Angle']
            ],
            'sizes' => [
                ['size_name' => 'EU 40', 'price' => 3900.00],
                ['size_name' => 'EU 41', 'price' => 4100.00],
                ['size_name' => 'EU 42', 'price' => 4300.00],
                ['size_name' => 'EU 43', 'price' => 4500.00],
                ['size_name' => 'EU 44', 'price' => 4700.00]
            ]
        ],
        [
            'id' => 5,
            'product_code' => 'MWAT-001',
            'name' => "Bauhaus Monochrome Chronograph Watch",
            'category_id' => 5,
            'category_name' => 'Accessories',
            'category_slug' => 'accessories',
            'gender' => 'Men',
            'type_slug' => 'watch',
            'base_price' => 5200.00,
            'has_size_pricing' => 0,
            'description' => 'A study in functional reductionism. Brushed 316L stainless steel case housing a precision Japanese quartz movement, scratch-resistant sapphire crystal glass, matte mocha dial, and quick-release vegetable-tanned leather band.',
            'primary_image' => 'assets/men_watch_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/men_watch_1_version_1.avif', 'version_number' => 1, 'caption' => 'Dial & Case Front'],
                ['image_path' => 'assets/men_watch_1_version_2.avif', 'version_number' => 2, 'caption' => 'Angled Crown & Strap Perspective']
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 5200.00]
            ]
        ],
        [
            'id' => 6,
            'product_code' => 'WBAG-001',
            'name' => "Sculptural Structured Leather Tote",
            'category_id' => 3,
            'category_name' => 'Bags',
            'category_slug' => 'bags',
            'gender' => 'Women',
            'type_slug' => 'bag',
            'base_price' => 4800.00,
            'has_size_pricing' => 0,
            'description' => 'Defined by sharp architectural geometry and supple Nappa leather. Engineered with magnetic closures, suede interior, detachable crossbody strap, and custom gold-finished hardware for versatile luxury styling.',
            'primary_image' => 'assets/women_bag_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_bag_1_version_1.avif', 'version_number' => 1, 'caption' => 'Main Front View'],
                ['image_path' => 'assets/women_bag_1_version_2.avif', 'version_number' => 2, 'caption' => 'Side Silhouette Depth'],
                ['image_path' => 'assets/women_bag_1_version_3.avif', 'version_number' => 3, 'caption' => 'Interior Gusset Angle']
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 4800.00]
            ]
        ],
        [
            'id' => 7,
            'product_code' => 'WJCK-001',
            'name' => "Minimalist Cropped Utility Jacket",
            'category_id' => 2,
            'category_name' => 'Female Apparel',
            'category_slug' => 'female_apparel',
            'gender' => 'Women',
            'type_slug' => 'jacket',
            'base_price' => 2650.00,
            'has_size_pricing' => 1,
            'description' => 'A clean cropped silhouette tailored from dense organic cotton twill. Features sharp point collar, concealed zipper with horn snap placket, and angular front storm flap detailing for an elevated contemporary edge.',
            'primary_image' => 'assets/women_jacket_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_jacket_1_version_1.avif', 'version_number' => 1, 'caption' => 'Cropped Front Composition'],
                ['image_path' => 'assets/women_jacket_1_version_2.avif', 'version_number' => 2, 'caption' => 'Collar & Storm Flap Angle']
            ],
            'sizes' => [
                ['size_name' => 'XS', 'price' => 2650.00],
                ['size_name' => 'S', 'price' => 2850.00],
                ['size_name' => 'M', 'price' => 3050.00],
                ['size_name' => 'L', 'price' => 3250.00]
            ]
        ],
        [
            'id' => 8,
            'product_code' => 'WJCK-002',
            'name' => "Belted Double-Breasted Wool Coat",
            'category_id' => 2,
            'category_name' => 'Female Apparel',
            'category_slug' => 'female_apparel',
            'gender' => 'Women',
            'type_slug' => 'jacket',
            'base_price' => 3800.00,
            'has_size_pricing' => 1,
            'description' => 'Crafted from double-faced Australian wool in rich mocha-tinged sand tones. Features exaggerated peak lapels, raglan sleeves, a self-tie waist belt, and deep welt pockets.',
            'primary_image' => 'assets/women_jacket_2_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_jacket_2_version_1.avif', 'version_number' => 1, 'caption' => 'Belted Full Front Silhouette'],
                ['image_path' => 'assets/women_jacket_2_version_2.avif', 'version_number' => 2, 'caption' => 'Open Lapel Texture Detail']
            ],
            'sizes' => [
                ['size_name' => 'S', 'price' => 3800.00],
                ['size_name' => 'M', 'price' => 4100.00],
                ['size_name' => 'L', 'price' => 4400.00],
                ['size_name' => 'XL', 'price' => 4700.00]
            ]
        ],
        [
            'id' => 9,
            'product_code' => 'WSHR-001',
            'name' => "Mulberry Silk Drape Blouse Shirt",
            'category_id' => 2,
            'category_name' => 'Female Apparel',
            'category_slug' => 'female_apparel',
            'gender' => 'Women',
            'type_slug' => 'shirt',
            'base_price' => 1850.00,
            'has_size_pricing' => 1,
            'description' => 'Tailored from lustrous 19mm Mulberry silk with fluid drape and subtle matte sheen. Features mother-of-pearl buttons, clean extended cuffs, and a minimal band collar for effortless refinement.',
            'primary_image' => 'assets/women_shirt_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_shirt_1_version_1.avif', 'version_number' => 1, 'caption' => 'Pure Silk Drape Front'],
                ['image_path' => 'assets/women_shirt_1_version_2.avif', 'version_number' => 2, 'caption' => 'Cuff & Pearl Button Detail']
            ],
            'sizes' => [
                ['size_name' => 'XS', 'price' => 1850.00],
                ['size_name' => 'S', 'price' => 2050.00],
                ['size_name' => 'M', 'price' => 2250.00],
                ['size_name' => 'L', 'price' => 2450.00]
            ]
        ],
        [
            'id' => 10,
            'product_code' => 'WSHO-001',
            'name' => "Sculpted Block-Heel Leather Mules",
            'category_id' => 4,
            'category_name' => 'Shoes',
            'category_slug' => 'shoes',
            'gender' => 'Women',
            'type_slug' => 'shoe',
            'base_price' => 3400.00,
            'has_size_pricing' => 1,
            'description' => 'Sleek open-back square-toe mules shaped from glove-soft calfskin. Features a hand-carved geometric wooden block heel (55mm), cushioned leather footbed, and sharp modern welt line.',
            'primary_image' => 'assets/women_shoe_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_shoe_1_version_1.avif', 'version_number' => 1, 'caption' => 'Square Toe & Block Heel Side'],
                ['image_path' => 'assets/women_shoe_1_version_2.avif', 'version_number' => 2, 'caption' => 'Upper Leather Insole Perspective']
            ],
            'sizes' => [
                ['size_name' => 'EU 36', 'price' => 3400.00],
                ['size_name' => 'EU 37', 'price' => 3600.00],
                ['size_name' => 'EU 38', 'price' => 3800.00],
                ['size_name' => 'EU 39', 'price' => 4000.00],
                ['size_name' => 'EU 40', 'price' => 4200.00]
            ]
        ],
        [
            'id' => 11,
            'product_code' => 'WSHO-002',
            'name' => "Architectural Pointed Ankle Boots",
            'category_id' => 4,
            'category_name' => 'Shoes',
            'category_slug' => 'shoes',
            'gender' => 'Women',
            'type_slug' => 'shoe',
            'base_price' => 4200.00,
            'has_size_pricing' => 1,
            'description' => 'Sharp pointed silhouette with an inner ankle zip closure. Handcrafted from smooth Italian leather, resting on an angled kitten heel with rubber non-slip injected sole.',
            'primary_image' => 'assets/women_shoe_2_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_shoe_2_version_1.avif', 'version_number' => 1, 'caption' => 'Pointed Toe Profile Stance'],
                ['image_path' => 'assets/women_shoe_2_version_2.avif', 'version_number' => 2, 'caption' => 'Heel Angle & Inner Zip Finish']
            ],
            'sizes' => [
                ['size_name' => 'EU 36', 'price' => 4200.00],
                ['size_name' => 'EU 37', 'price' => 4450.00],
                ['size_name' => 'EU 38', 'price' => 4700.00],
                ['size_name' => 'EU 39', 'price' => 4950.00],
                ['size_name' => 'EU 40', 'price' => 5200.00]
            ]
        ],
        [
            'id' => 12,
            'product_code' => 'WWAT-001',
            'name' => "Rose Gold Petite Milanese Mesh Watch",
            'category_id' => 5,
            'category_name' => 'Accessories',
            'category_slug' => 'accessories',
            'gender' => 'Women',
            'type_slug' => 'watch',
            'base_price' => 4950.00,
            'has_size_pricing' => 0,
            'description' => 'Delicate 28mm stainless steel casing plated in warm rose gold. Features an ultra-slim profile, sunray sand dial with minimalist indices, and an adjustable Milanese magnetic mesh strap.',
            'primary_image' => 'assets/women_watch_1_version_1.avif',
            'is_featured' => 1,
            'images' => [
                ['image_path' => 'assets/women_watch_1_version_1.avif', 'version_number' => 1, 'caption' => 'Milanese Strap & Dial Face'],
                ['image_path' => 'assets/women_watch_1_version_2.avif', 'version_number' => 2, 'caption' => 'Ultra-Slim Bevel Profile']
            ],
            'sizes' => [
                ['size_name' => 'One Size', 'price' => 4950.00]
            ]
        ]
    ];
}

/**
 * Filter items from catalog
 */
function getFilteredFreudianCatalog($category = '', $gender = '', $search = '', $sort = 'featured') {
    $catalog = getFreudianCatalog();
    $results = [];

    foreach ($catalog as $item) {
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

        // Search filter (name, description, category only; product codes are hidden)
        if (!empty($search)) {
            $term = strtolower($search);
            $match = (strpos(strtolower($item['name']), $term) !== false) ||
                     (strpos(strtolower($item['description']), $term) !== false) ||
                     (strpos(strtolower($item['category_name']), $term) !== false);
            if (!$match) continue;
        }

        $results[] = $item;
    }

    if ($sort === 'price_asc') {
        usort($results, fn($a, $b) => $a['base_price'] <=> $b['base_price']);
    } elseif ($sort === 'price_desc') {
        usort($results, fn($a, $b) => $b['base_price'] <=> $a['base_price']);
    } elseif ($sort === 'name_asc') {
        usort($results, fn($a, $b) => strcmp($a['name'], $b['name']));
    }

    return $results;
}

/**
 * Find single product
 */
function getFreudianProductById($id) {
    $catalog = getFreudianCatalog();
    foreach ($catalog as $item) {
        if ((int)$item['id'] === (int)$id) {
            return $item;
        }
    }
    return null;
}
