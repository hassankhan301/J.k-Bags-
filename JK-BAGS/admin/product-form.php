<?php
require_once __DIR__ . '/../includes/db.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$product = ['name'=>'','category'=>'Hand Bag','price'=>'','stock'=>10,'image'=>'assets/images/products/luna-classic.jpg','description'=>'','badge'=>'','featured'=>0];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM products WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $existing = stmt_result($stmt)->fetch_assoc(); $stmt->close();
    if (!$existing) { flash('danger', 'The product was not found.'); redirect('index.php#products'); }
    $product = $existing;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
    $imageInput = trim((string)($_POST['image'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $badge = trim((string)($_POST['badge'] ?? ''));
    $featured = isset($_POST['featured']) ? 1 : 0;
    $image = $imageInput !== '' ? $imageInput : ($product['image'] ?: 'assets/images/products/bag-default.svg');
    $upload = $_FILES['image_file'] ?? null;

    if (strlen($name) < 2 || strlen($name) > 150 ||
        !in_array($category, ['Hand Bag','Shoulder Bag','Crossbody Bag','Tote Bag','Clutch'], true) ||
        $price === false || $price < 0 || $stock === false || $stock < 0) {
        $error = 'Check the product name, category, price and stock quantity.';
    } else {
        if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $error = 'The photo upload failed. Please select the image again.';
            } elseif ((int)$upload['size'] > 5 * 1024 * 1024) {
                $error = 'Image size must be 5 MB or less.';
            } else {
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                $mime = $finfo ? finfo_file($finfo, $upload['tmp_name']) : '';
                if ($finfo) finfo_close($finfo);
                $extByMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($extByMime[$mime]) || !is_uploaded_file($upload['tmp_name'])) {
                    $error = 'Please upload a JPG, PNG or WEBP image only.';
                } else {
                    $relativeDir = 'assets/images/products/uploads';
                    $absoluteDir = __DIR__ . '/../' . $relativeDir;
                    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
                        $error = 'The uploads folder could not be created. Check folder permissions.';
                    } else {
                        $fileName = 'product-' . bin2hex(random_bytes(8)) . '.' . $extByMime[$mime];
                        if (!move_uploaded_file($upload['tmp_name'], $absoluteDir . '/' . $fileName)) {
                            $error = 'The image could not be saved. Check the uploads folder permissions.';
                        } else {
                            $image = $relativeDir . '/' . $fileName;
                        }
                    }
                }
            }
        } elseif ($imageInput !== '') {
            $isRemote = filter_var($imageInput, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($imageInput, PHP_URL_SCHEME)), ['http','https'], true);
            $isLocal = preg_match('#^assets/images/products/[A-Za-z0-9._/-]+$#', $imageInput) && is_file(__DIR__ . '/../' . $imageInput);
            if (!$isRemote && !$isLocal) $error = 'Use an HTTP/HTTPS image URL, a real image in assets/images/products/, or upload a photo below.';
        }

        if ($error === '') {
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE products SET name=?,category=?,price=?,stock=?,image=?,description=?,badge=?,featured=? WHERE id=?');
                $stmt->bind_param('ssdisssii', $name, $category, $price, $stock, $image, $description, $badge, $featured, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO products(name,category,price,stock,image,description,badge,featured) VALUES(?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ssdisssi', $name, $category, $price, $stock, $image, $description, $badge, $featured);
            }
            if ($stmt && $stmt->execute()) { $stmt->close(); flash('success', 'Product saved successfully.'); redirect('index.php#products'); }
            if ($stmt) $stmt->close();
            $error = 'Product could not be saved. Check the database connection and try again.';
        }
    }
    $product = array_merge($product, ['name'=>$name,'category'=>$category,'price'=>$_POST['price'] ?? '', 'stock'=>$_POST['stock'] ?? 0,'image'=>$image,'description'=>$description,'badge'=>$badge,'featured'=>$featured]);
}
$preview = (string)$product['image'];
$previewSrc = preg_match('#^https?://#i', $preview) ? $preview : '../' . ltrim($preview, '/');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=$id ? 'Edit' : 'Add'?> Product | J.K BAGS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head>
<body><section class="form-page"><div class="form-box wide"><p class="eyebrow">STORE MANAGEMENT</p><h1><?=$id ? 'Edit Product' : 'Add a Product'?></h1>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<div class="product-editor-preview"><img id="imagePreview" src="<?=e($previewSrc)?>" alt="Current product photo"><span>Current product image preview</span></div>
<form class="admin-form" method="post" enctype="multipart/form-data" novalidate><?=csrf_field()?>
<label>Product name<input name="name" maxlength="150" value="<?=e($product['name'])?>" required></label>
<label>Category<select name="category"><?php foreach (['Hand Bag','Shoulder Bag','Crossbody Bag','Tote Bag','Clutch'] as $cat): ?><option value="<?=e($cat)?>" <?=$product['category']===$cat?'selected':''?>><?=e($cat)?></option><?php endforeach; ?></select></label>
<div class="row g-3"><div class="col-md-6"><label>Price (PKR)<input type="number" min="0" step="1" name="price" value="<?=e($product['price'])?>" required></label></div><div class="col-md-6"><label>Stock<input type="number" min="0" step="1" name="stock" value="<?=e($product['stock'])?>" required></label></div></div>
<label>Image URL or local path <input id="imageUrl" name="image" value="<?=e($product['image'])?>" placeholder="https://... or assets/images/products/file.jpg"><small class="text-muted">Leave as-is when using the upload below.</small></label>
<label>Or upload a photo from your computer <input id="imageFile" type="file" name="image_file" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG, PNG or WEBP · maximum 5 MB. Uploaded photos are stored in your project.</small></label>
<label>Description<textarea name="description" rows="4"><?=e($product['description'])?></textarea></label>
<label>Badge<input name="badge" maxlength="40" value="<?=e($product['badge'])?>" placeholder="NEW / BEST SELLER"></label>
<label class="d-flex align-items-center gap-2"><input type="checkbox" name="featured" style="width:auto" <?=$product['featured']?'checked':''?>> Show on homepage</label>
<button class="btn-gold" type="submit">SAVE PRODUCT</button><a href="index.php#products">Cancel and return to dashboard</a></form></div></section>
<script>
const fileInput=document.getElementById('imageFile'); const urlInput=document.getElementById('imageUrl'); const preview=document.getElementById('imagePreview');
fileInput.addEventListener('change',()=>{const file=fileInput.files&&fileInput.files[0]; if(file){preview.src=URL.createObjectURL(file);urlInput.dataset.original=urlInput.value;urlInput.value='';}});
urlInput.addEventListener('input',()=>{if(!fileInput.files.length && urlInput.value) preview.src=/^https?:\/\//i.test(urlInput.value)?urlInput.value:'../'+urlInput.value.replace(/^\//,'');});
</script></body></html>
