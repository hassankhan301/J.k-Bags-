<?php
require_once __DIR__ . '/../includes/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'status') {
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
        $status = (string)($_POST['status'] ?? 'Pending');
        $allowed = ['Pending','Confirmed','Processing','Shipped','Delivered','Cancelled'];
        if ($orderId && in_array($status, $allowed, true)) {
            $stmt = $conn->prepare('UPDATE orders SET status=? WHERE id=?');
            $stmt->bind_param('si', $status, $orderId);
            if ($stmt->execute()) flash('success', 'Order status updated.');
            else flash('danger', 'Could not update order status.');
            $stmt->close();
        } else flash('danger', 'Please choose a valid order status.');
    } elseif ($action === 'delete_product') {
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        if ($productId) {
            $stmt = $conn->prepare('DELETE FROM products WHERE id=?');
            $stmt->bind_param('i', $productId);
            if ($stmt->execute() && $stmt->affected_rows > 0) flash('success', 'Product deleted.');
            else flash('danger', 'This product may be attached to an order, or it no longer exists.');
            $stmt->close();
        }
    }
    redirect('index.php');
}

$counts = [];
foreach (['products'=>'Products','users'=>'Customers','orders'=>'Orders','messages'=>'Messages'] as $table => $label) {
    $res = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    $counts[$table] = $res ? (int)$res->fetch_assoc()['total'] : 0;
}
$products = $conn->query('SELECT * FROM products ORDER BY id DESC');
$orders = $conn->query('SELECT o.*,u.email FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 50');
$messages = $conn->query('SELECT * FROM messages ORDER BY id DESC LIMIT 50');
$customers = $conn->query('SELECT id,name,email,created_at FROM users ORDER BY id DESC LIMIT 50');
$pageTitle = 'Admin Dashboard | J.K BAGS';
$flashMessage = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($pageTitle)?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head>
<body class="admin-page"><div class="admin-nav"><div class="container"><div><strong>J.K BAGS <span class="admin-nav-label">STORE ADMIN</span></strong><nav class="admin-nav-links"><a href="#overview">Overview</a><a href="#orders">Orders</a><a href="#products">Products</a><a href="#customers">Customers</a><a href="#messages">Messages</a></nav></div><div class="admin-user">Hi, <?=e($_SESSION['admin_name'] ?? 'Admin')?> <a class="btn btn-sm btn-outline-light ms-2" href="logout.php">Logout</a> <a class="btn btn-sm btn-outline-light ms-1" href="../index.php">View Store</a></div></div></div>
<div class="container admin-content">
<?php if ($flashMessage): ?><div class="alert alert-<?=e($flashMessage['type'])?> alert-dismissible fade show"><?=e($flashMessage['message'])?><button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<section id="overview" class="admin-intro"><div><p class="eyebrow mb-1">YOUR STORE AT A GLANCE</p><h1 class="h2">Dashboard</h1><p class="text-muted mb-0">Manage products, keep orders moving and read customer messages.</p></div><a class="btn-gold" href="product-form.php"><i class="fa-solid fa-plus"></i> ADD PRODUCT</a></section>
<div class="admin-cards"><?php foreach (['products'=>'Products','users'=>'Customers','orders'=>'Orders','messages'=>'Messages'] as $key=>$label): ?><div class="admin-stat"><span><?=e($label)?></span><strong><?=$counts[$key]?></strong><small><?=e(match($key){'products'=>'In your catalogue','users'=>'Registered accounts','orders'=>'Customer orders',default=>'Inbox messages'})?></small></div><?php endforeach; ?></div>
<section id="orders" class="admin-panel"><div class="admin-panel-heading"><div><p class="eyebrow mb-1">ORDER MANAGEMENT</p><h2>Recent orders</h2></div><span class="admin-count"><?=$counts['orders']?> total</span></div><div class="table-responsive"><table class="w-100"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Date</th><th>Status / update</th></tr></thead><tbody>
<?php if (!$orders || !$orders->num_rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No orders received yet.</td></tr><?php else: while ($order=$orders->fetch_assoc()): ?><tr><td><strong>#<?=$order['id']?></strong></td><td><?=e($order['customer_name'])?><div class="small text-muted"><?=e($order['phone'])?></div></td><td><strong><?=money($order['total'])?></strong></td><td><?=e(date('d M Y', strtotime($order['created_at'])))?></td><td><form method="post" class="d-flex gap-2 align-items-center"><?=csrf_field()?><input type="hidden" name="action" value="status"><input type="hidden" name="order_id" value="<?=$order['id']?>"><select name="status" class="form-select form-select-sm"><?php foreach (['Pending','Confirmed','Processing','Shipped','Delivered','Cancelled'] as $status): ?><option value="<?=e($status)?>" <?=$status===$order['status']?'selected':''?>><?=e($status)?></option><?php endforeach; ?></select><button class="btn-mini" type="submit">Save</button></form></td></tr><?php endwhile; endif; ?></tbody></table></div></section>
<section id="products" class="admin-panel"><div class="admin-panel-heading"><div><p class="eyebrow mb-1">CATALOGUE MANAGEMENT</p><h2>Products</h2></div><a class="btn-gold btn-small" href="product-form.php">+ ADD PRODUCT</a></div><div class="table-responsive"><table class="w-100"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody>
<?php if (!$products || !$products->num_rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No products yet. Add your first product above.</td></tr><?php else: while ($product=$products->fetch_assoc()): $productImage=(string)$product['image']; $productImageSrc=preg_match('#^https?://#i',$productImage)?$productImage:'../'.ltrim($productImage,'/'); ?><tr><td><div class="admin-product"><img src="<?=e($productImageSrc)?>" alt="<?=e($product['name'])?>" onerror="this.onerror=null;this.src='../assets/images/products/bag-default.svg';"><div><strong><?=e($product['name'])?></strong><small>#<?=$product['id']?></small></div></div></td><td><?=e($product['category'])?></td><td><?=money($product['price'])?></td><td><span class="stock-pill <?=$product['stock']<5?'low':''?>"><?=$product['stock']?> in stock</span></td><td><div class="admin-actions"><a class="btn-mini" href="product-form.php?id=<?=$product['id']?>">Edit</a><form method="post" onsubmit="return confirm('Delete this product?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_product"><input type="hidden" name="product_id" value="<?=$product['id']?>"><button class="btn-mini btn-danger" type="submit">Delete</button></form></div></td></tr><?php endwhile; endif; ?></tbody></table></div></section>
<section id="customers" class="admin-panel"><div class="admin-panel-heading"><div><p class="eyebrow mb-1">CUSTOMER ACCOUNTS</p><h2>Recent customers</h2></div><span class="admin-count"><?=$counts['users']?> total</span></div><div class="table-responsive"><table class="w-100"><thead><tr><th>Customer</th><th>Email</th><th>Joined</th></tr></thead><tbody><?php if (!$customers || !$customers->num_rows): ?><tr><td colspan="3" class="text-center text-muted py-4">No customer accounts yet.</td></tr><?php else: while ($customer=$customers->fetch_assoc()): ?><tr><td><strong><?=e($customer['name'])?></strong><div class="small text-muted">#<?=$customer['id']?></div></td><td><?=e($customer['email'])?></td><td><?=e(date('d M Y', strtotime($customer['created_at'] ?? 'now')))?></td></tr><?php endwhile; endif; ?></tbody></table></div></section>
<section id="messages" class="admin-panel"><div class="admin-panel-heading"><div><p class="eyebrow mb-1">CUSTOMER SUPPORT</p><h2>Customer messages</h2></div><span class="admin-count"><?=$counts['messages']?> total</span></div><div class="table-responsive"><table class="w-100"><thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Received</th></tr></thead><tbody>
<?php if (!$messages || !$messages->num_rows): ?><tr><td colspan="4" class="text-center text-muted py-4">Your inbox is empty. Messages sent from the contact form will appear here.</td></tr><?php else: while ($message=$messages->fetch_assoc()): ?><tr><td><strong><?=e($message['name'])?></strong><div class="small text-muted"><?=e($message['email'])?></div></td><td><?=e($message['subject'])?></td><td class="admin-message-text"><?=nl2br(e($message['message']))?></td><td><?=e(date('d M Y', strtotime($message['created_at'])))?></td></tr><?php endwhile; endif; ?></tbody></table></div></section>
<div class="admin-footnote"><i class="fa-solid fa-shield-heart"></i> J.K BAGS Admin · Use a unique password before deploying this website online.</div>
</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
