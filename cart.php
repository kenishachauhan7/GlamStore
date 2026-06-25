<?php
session_start();
include 'db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Add to cart
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    // Check if already in cart
    $check = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id='$user_id' AND product_id='$product_id'");
    $existing = mysqli_fetch_assoc($check);

    if ($existing) {
        $new_qty = $existing['quantity'] + 1;
        mysqli_query($conn, "UPDATE cart SET quantity='$new_qty' WHERE id='{$existing['id']}'");
    } else {
        mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$product_id', 1)");
    }
    header("Location: cart.php");
    exit;
}

// Remove from cart
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $cart_id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM cart WHERE id='$cart_id' AND user_id='$user_id'");
    header("Location: cart.php");
    exit;
}

// Submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = (int)$_POST['product_id'];
    $rating     = (int)$_POST['rating'];
    $comment    = mysqli_real_escape_string($conn, trim($_POST['comment']));

    if ($rating < 1 || $rating > 5) {
        $message = "Please select a rating between 1 and 5.";
    } elseif (empty($comment)) {
        $message = "Review comment cannot be empty.";
    } else {
        $dup = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id='$user_id' AND product_id='$product_id'");
        if (mysqli_num_rows($dup) > 0) {
            $message = "You have already reviewed this product.";
        } else {
            mysqli_query($conn, "INSERT INTO reviews (user_id, product_id, rating, comment) VALUES ('$user_id', '$product_id', '$rating', '$comment')");
            $message = "✅ Review submitted!";
        }
    }
}

// Fetch cart items with product details
$cart_result = mysqli_query($conn, "
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id,
           p.name, p.price, p.image_url, p.category
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = '$user_id'
    ORDER BY c.id DESC
");

// Build items array and calculate total
$total = 0;
$items_array = [];
while ($row = mysqli_fetch_assoc($cart_result)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $items_array[] = $row;
}

// Fetch reviews for products in cart
$reviews_map = [];
if (!empty($items_array)) {
    $product_ids = array_column($items_array, 'product_id');
    $ids_string  = implode(',', $product_ids);
    $rev_result  = mysqli_query($conn, "
        SELECT r.*, u.username
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id IN ($ids_string)
        ORDER BY r.created_at DESC
    ");
    while ($rev = mysqli_fetch_assoc($rev_result)) {
        $reviews_map[$rev['product_id']][] = $rev;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart – GlamStore</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #fdf6f0; color: #333; }

        nav {
            background: #fff; padding: 14px 40px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07); position: sticky; top: 0; z-index: 100;
        }
        nav .logo { font-size: 1.5rem; font-weight: 700; color: #c0556a; text-decoration: none; }
        nav .nav-links a { margin-left: 20px; text-decoration: none; color: #555; font-size: 0.95rem; }
        nav .nav-links a:hover { color: #c0556a; }

        .page { max-width: 960px; margin: 40px auto; padding: 0 20px 60px; }
        h2 { font-size: 1.8rem; color: #8b2252; margin-bottom: 24px; }

        .msg { background: #d4edda; border: 1px solid #b8dfc5; color: #2d6a4f; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; }
        .msg.error { background: #fde8e8; border-color: #f5c6c6; color: #842029; }

        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 30px; }
        th { background: #f8c8d4; color: #8b2252; padding: 14px 16px; text-align: left; font-size: 0.9rem; }
        td { padding: 14px 16px; border-bottom: 1px solid #f5e6e8; font-size: 0.95rem; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        td img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }

        .remove-btn { color: #c0556a; text-decoration: none; font-size: 0.85rem; border: 1px solid #c0556a; padding: 4px 10px; border-radius: 6px; transition: all 0.2s; }
        .remove-btn:hover { background: #c0556a; color: #fff; }

        .total-box { text-align: right; background: #fff; padding: 20px 24px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 40px; }
        .total-box span { font-size: 1.3rem; font-weight: 700; color: #c0556a; }
        .checkout-btn { display: inline-block; margin-top: 14px; padding: 12px 36px; background: #c0556a; color: #fff; border-radius: 8px; text-decoration: none; font-size: 1rem; transition: background 0.2s; }
        .checkout-btn:hover { background: #a03a55; }

        .empty { text-align: center; padding: 60px; color: #aaa; font-size: 1.1rem; }
        .empty a { color: #c0556a; text-decoration: none; font-weight: 600; }

        .reviews-section { margin-top: 50px; }
        .review-card { background: #fff; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .review-card h4 { color: #8b2252; margin-bottom: 16px; font-size: 1rem; }
        .stars { color: #f4a500; font-size: 1.1rem; }
        .existing-review { background: #fdf6f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 10px; font-size: 0.9rem; }
        .existing-review .meta { color: #999; font-size: 0.8rem; margin-bottom: 4px; }

        .review-form { margin-top: 16px; border-top: 1px solid #f0e0e5; padding-top: 16px; }
        .review-form label { display: block; font-size: 0.85rem; color: #666; margin-bottom: 6px; }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 6px; margin-bottom: 12px; }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label { font-size: 1.6rem; cursor: pointer; color: #ddd; transition: color 0.15s; }
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #f4a500 !important; }
        .star-rating input[type="radio"]:checked ~ label { color: #f4a500; }

        textarea { width: 100%; padding: 10px 14px; border: 1px solid #e0cdd2; border-radius: 8px; font-size: 0.9rem; resize: vertical; font-family: inherit; background: #fdf6f0; }
        textarea:focus { outline: none; border-color: #c0556a; }
        .submit-btn { margin-top: 10px; padding: 9px 24px; background: #c0556a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: background 0.2s; }
        .submit-btn:hover { background: #a03a55; }

        footer { text-align: center; padding: 20px; font-size: 0.85rem; color: #aaa; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<nav>
    <a class="logo" href="index.php">💄 GlamStore</a>
    <div class="nav-links">
        <a href="index.php">Products</a>
        <a href="cart.php">🛒 Cart (<?= count($items_array) ?>)</a>
        <a href="auth/logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
    </div>
</nav>

<div class="page">

    <?php if ($message): ?>
        <div class="msg <?= str_starts_with($message, '✅') ? '' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>🛒 Your Cart</h2>

    <?php if (empty($items_array)): ?>
        <div class="empty">
            <p>Your cart is empty.</p><br>
            <a href="index.php">← Continue Shopping</a>
        </div>
    <?php else: ?>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_array as $item): ?>
                <tr>
                    <td>
                        <img src="images/<?= htmlspecialchars($item['image_url'] ?? 'placeholder.jpg') ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>"
                             onerror="this.src='https://placehold.co/60x60/f8c8d4/8b2252?text=G'">
                    </td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₹<?= number_format($item['subtotal'], 2) ?></td>
                    <td>
                        <a href="cart.php?action=remove&id=<?= $item['cart_id'] ?>" class="remove-btn">Remove</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-box">
            Total: <span>₹<?= number_format($total, 2) ?></span><br>
            <a href="#" class="checkout-btn">Proceed to Checkout</a>
        </div>

        <!-- Reviews -->
        <div class="reviews-section">
            <h2>⭐ Leave a Review</h2>

            <?php foreach ($items_array as $item): ?>
            <div class="review-card">
                <h4><?= htmlspecialchars($item['name']) ?></h4>

                <?php if (!empty($reviews_map[$item['product_id']])): ?>
                    <?php foreach ($reviews_map[$item['product_id']] as $rev): ?>
                    <div class="existing-review">
                        <div class="meta">
                            <strong><?= htmlspecialchars($rev['username']) ?></strong> &nbsp;
                            <span class="stars"><?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?></span>
                            &nbsp; <?= date('d M Y', strtotime($rev['created_at'])) ?>
                        </div>
                        <div><?= htmlspecialchars($rev['comment']) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#bbb; font-size:0.85rem; margin-bottom:10px;">No reviews yet. Be the first!</p>
                <?php endif; ?>

                <form class="review-form" method="POST" action="cart.php">
                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                    <label>Your Rating</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="star<?= $i ?>_<?= $item['product_id'] ?>" value="<?= $i ?>">
                            <label for="star<?= $i ?>_<?= $item['product_id'] ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <label>Your Review</label>
                    <textarea name="comment" rows="3" placeholder="Share your thoughts about this product..."></textarea>
                    <button type="submit" name="submit_review" class="submit-btn">Submit Review</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<footer>&copy; <?= date('Y') ?> GlamStore. Made with 💗</footer>

</body>
</html>