<?php
session_start();
include 'db.php';

// Get selected category from URL, default to 'all'
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch distinct categories for filter buttons
$cat_result = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");

// Fetch products based on category filter
if ($category === 'all') {
    $stmt = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
} else {
    $stmt = mysqli_query($conn, "SELECT * FROM products WHERE category='$category' ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlamStore – Beauty & Skincare</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fdf6f0;
            color: #333;
        }

        /* Navbar */
        nav {
            background: #fff;
            padding: 14px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        nav .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #c0556a;
            text-decoration: none;
        }
        nav .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: #555;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        nav .nav-links a:hover { color: #c0556a; }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #f8c8d4, #fde8c0);
            text-align: center;
            padding: 60px 20px 40px;
        }
        .hero h1 { font-size: 2.4rem; color: #8b2252; margin-bottom: 10px; }
        .hero p  { font-size: 1.1rem; color: #555; }

        /* Category Filter */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            padding: 30px 20px 10px;
        }
        .filter-bar a {
            padding: 8px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.9rem;
            border: 2px solid #c0556a;
            color: #c0556a;
            transition: all 0.2s;
        }
        .filter-bar a:hover,
        .filter-bar a.active {
            background: #c0556a;
            color: #fff;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 24px;
            padding: 30px 40px 60px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .card-body { padding: 16px; }
        .card-body h3 { font-size: 1rem; margin-bottom: 6px; color: #2c2c2c; }
        .card-body .category-tag {
            font-size: 0.75rem;
            background: #fde8c0;
            color: #8b5e00;
            padding: 2px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .card-body .price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #c0556a;
        }
        .card-body .desc {
            font-size: 0.83rem;
            color: #777;
            margin: 6px 0 14px;
            line-height: 1.5;
        }
        .btn-cart {
            display: block;
            width: 100%;
            padding: 9px;
            background: #c0556a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-cart:hover { background: #a03a55; }

        /* Empty State */
        .empty {
            text-align: center;
            padding: 80px 20px;
            color: #aaa;
            font-size: 1.1rem;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 20px;
            font-size: 0.85rem;
            color: #aaa;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav>
    <a class="logo" href="index.php">💄 GlamStore</a>
    <div class="nav-links">
        <a href="index.php">Products</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="cart.php">🛒 Cart</a>
            <a href="auth/logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
        <?php else: ?>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Hero -->
<div class="hero">
    <h1>Beauty & Skincare</h1>
    <p>Discover premium products curated just for you ✨</p>
</div>

<!-- Category Filter -->
<div class="filter-bar">
    <a href="index.php" class="<?= $category === 'all' ? 'active' : '' ?>">All</a>
    <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
        <a href="?category=<?= urlencode($cat['category']) ?>"
           class="<?= $category === $cat['category'] ? 'active' : '' ?>">
            <?= htmlspecialchars(ucfirst($cat['category'])) ?>
        </a>
    <?php endwhile; ?>
</div>

<!-- Product Grid -->
<div class="product-grid">
    <?php if (mysqli_num_rows($stmt) === 0): ?>
        <div class="empty" style="grid-column:1/-1">No products found in this category.</div>
    <?php else: ?>
        <?php while ($product = mysqli_fetch_assoc($stmt)): ?>
            <div class="card">
                <img src="images/<?= htmlspecialchars($product['image_url'] ?? 'placeholder.jpg') ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     onerror="this.src='https://placehold.co/400x200/f8c8d4/8b2252?text=GlamStore'">
                <div class="card-body">
                    <span class="category-tag"><?= htmlspecialchars(ucfirst($product['category'])) ?></span>
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="desc"><?= htmlspecialchars($product['description']) ?></p>
                    <div class="price">₹<?= number_format($product['price'], 2) ?></div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="btn-cart">Add to Cart 🛒</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn-cart">Login to Add</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<footer>&copy; <?= date('Y') ?> GlamStore. Made with 💗</footer>

</body>
</html>