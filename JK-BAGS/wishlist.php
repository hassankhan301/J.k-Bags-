<?php
require "includes/db.php";

$ids = array_keys($_SESSION['wishlist'] ?? []);
$items = [];

if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($in)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $result = stmt_result($stmt);
    while ($product = $result->fetch_assoc()) {
        $items[] = $product;
    }
}

$pageTitle = "Wishlist | J.K BAGS";
require "includes/header.php";
?>

<section class="section white">
    <div class="container">
        <div class="heading">
            <p class="eyebrow">YOUR FAVORITES</p>
            <h2>Wishlist</h2>
            <span></span>
        </div>

        <div class="product-grid">
            <?php if (!$items): ?><div class="empty shop-empty" style="grid-column:1/-1"><i class="fa-regular fa-heart"></i><h3>Your wishlist is waiting for a favourite</h3><p>Tap the heart on any bag to keep it here.</p><a class="btn-gold" href="products.php">EXPLORE BAGS</a></div><?php endif; ?>
            <?php foreach ($items as $p): ?>
                <article class="product-card">
                    <div class="product-img">
                        <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" onerror="this.onerror=null;this.src='assets/images/products/bag-default.svg';">
                    </div>

                    <div class="product-info">
                        <span><?= e($p['category']) ?></span>
                        <h3><?= e($p['name']) ?></h3>
                        <strong>PKR <?= number_format($p['price']) ?></strong>
                        <button class="btn-gold smallbtn" onclick="addCart(<?= $p['id'] ?>)" <?=((int)$p['stock']<1)?'disabled':''?>>
                            <?=((int)$p['stock']<1)?'SOLD OUT':'ADD TO BAG'?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require "includes/footer.php"; ?>