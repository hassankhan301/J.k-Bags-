<?php
require_once __DIR__ . '/includes/db.php';

$categories = ['Hand Bag', 'Shoulder Bag', 'Crossbody Bag', 'Tote Bag', 'Clutch'];
$category = trim((string)($_GET['category'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'featured');
if (!in_array($category, $categories, true)) $category = '';
if (!in_array($sort, ['featured', 'new', 'price_low', 'price_high', 'name'], true)) $sort = 'featured';

$sql = 'SELECT id,name,category,price,stock,image,badge,description,featured FROM products WHERE 1=1';
$params = [];
$types = '';
if ($category !== '') { $sql .= ' AND category=?'; $types .= 's'; $params[] = $category; }
if ($search !== '') { $sql .= ' AND (name LIKE ? OR category LIKE ? OR description LIKE ?)'; $types .= 'sss'; $term = '%' . $search . '%'; array_push($params, $term, $term, $term); }
if ($sort === 'new') $sql .= ' ORDER BY created_at DESC, id DESC';
elseif ($sort === 'price_low') $sql .= ' ORDER BY price ASC, id DESC';
elseif ($sort === 'price_high') $sql .= ' ORDER BY price DESC, id DESC';
elseif ($sort === 'name') $sql .= ' ORDER BY name ASC';
else $sql .= ' ORDER BY featured DESC, id DESC';
$sql .= ' LIMIT 60';
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = stmt_result($stmt);
$pageTitle = 'Shop All Bags | J.K BAGS';
require __DIR__ . '/includes/header.php';
?>
<section class="shop-hero">
  <div class="container shop-hero-inner">
    <div><p class="eyebrow">THE J.K EDIT</p><h1>Find your <em>everyday favourite.</em></h1><p>Modern silhouettes, lovely details and bags made to go wherever your day takes you.</p></div>
    <div class="shop-hero-bubble"><i class="fa-solid fa-heart"></i><span>Find a bag<br>that feels like you</span></div>
  </div>
</section>
<section class="section shop-page"><div class="container">
  <form class="shop-toolbar" method="get" action="products.php">
    <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?=e($category)?>"><?php endif; ?>
    <label class="searchbar"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?=e($search)?>" placeholder="Search bags, styles and collections..."><button type="submit">SEARCH</button></label>
    <label class="sort-control"><span>Sort by</span><select name="sort" onchange="this.form.submit()"><option value="featured" <?=$sort==='featured'?'selected':''?>>Featured</option><option value="new" <?=$sort==='new'?'selected':''?>>New arrivals</option><option value="price_low" <?=$sort==='price_low'?'selected':''?>>Price: low to high</option><option value="price_high" <?=$sort==='price_high'?'selected':''?>>Price: high to low</option><option value="name" <?=$sort==='name'?'selected':''?>>Name A–Z</option></select></label>
  </form>
  <nav class="category-pills" aria-label="Filter by category"><a class="<?= $category===''?'active':'' ?>" href="products.php<?= $search!==''?'?q='.rawurlencode($search):'' ?>">All bags</a><?php foreach ($categories as $cat): ?><a class="<?= $category===$cat?'active':'' ?>" href="products.php?category=<?=rawurlencode($cat)?><?= $search!==''?'&q='.rawurlencode($search):'' ?>"><?=e($cat==='Hand Bag'?'Handbags':($cat==='Crossbody Bag'?'Crossbody':($cat==='Tote Bag'?'Tote Bags':($cat==='Clutch'?'Clutches':'Shoulder Bags'))))?></a><?php endforeach; ?></nav>
  <div class="shop-results-head"><div><p class="eyebrow">CURATED FOR YOU</p><h2><?= $category!==''?e($category):'The collection' ?></h2></div><span><?= $products->num_rows ?> <?= $products->num_rows===1?'piece':'pieces' ?> found</span></div>
  <?php if ($products->num_rows): ?><div class="product-grid shop-product-grid"><?php while ($p=$products->fetch_assoc()): ?><article class="product-card"><div class="product-img"><img loading="lazy" src="<?=e($p['image'])?>" alt="<?=e($p['name'])?>" onerror="this.onerror=null;this.src='assets/images/products/bag-default.svg';"><?php if (!empty($p['badge'])): ?><small><?=e($p['badge'])?></small><?php endif; ?><button class="heart" type="button" aria-label="Save <?=e($p['name'])?> to wishlist" onclick="addWishlist(<?=$p['id']?>)"><i class="fa-regular fa-heart"></i></button><div class="product-actions"><a href="product.php?id=<?=$p['id']?>">VIEW DETAILS</a><button type="button" onclick="addCart(<?=$p['id']?>)" <?=((int)$p['stock']<1)?'disabled title="Out of stock"':''?>><?=((int)$p['stock']<1)?'SOLD OUT':'ADD TO BAG'?></button></div></div><div class="product-info"><span><?=e($p['category'])?></span><h3><?=e($p['name'])?></h3><strong><?=money($p['price'])?></strong><?php if ((int)$p['stock']<1): ?><p class="sold-out">Currently sold out</p><?php endif; ?></div></article><?php endwhile; ?></div>
  <?php else: ?><div class="empty shop-empty"><i class="fa-solid fa-bag-shopping"></i><h3>No bags found just yet</h3><p>Try another search or browse the full collection.</p><a class="btn-gold" href="products.php">VIEW ALL BAGS</a></div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
