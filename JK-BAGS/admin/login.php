<?php
require_once __DIR__ . '/../includes/db.php';
if (!empty($_SESSION['admin_id'])) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter the admin email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id,name,password FROM admins WHERE email=? LIMIT 1');
        $admin = null;
        if ($stmt) {
            $stmt->bind_param('s', $email);
            if ($stmt->execute()) {
                /* Bind results instead of depending on mysqli_stmt::get_result(). */
                $stmt->bind_result($adminId, $adminName, $adminPassword);
                if ($stmt->fetch()) {
                    $admin = ['id'=>(int)$adminId, 'name'=>(string)$adminName, 'password'=>(string)$adminPassword];
                }
            }
            $stmt->close();
        }
        /* If an old database is missing its demo admin, restore it only for the documented local demo login. */
        if (!$admin && $email === 'admin@jkbags.com' && hash_equals('admin123', $password)) {
            $demoHash = password_hash('admin123', PASSWORD_DEFAULT);
            $demoName = 'J.K Admin';
            $restore = $conn->prepare('INSERT INTO admins(name,email,password) VALUES(?,?,?)');
            if ($restore) {
                $restore->bind_param('sss', $demoName, $email, $demoHash);
                if ($restore->execute()) $admin = ['id'=>(int)$restore->insert_id, 'name'=>$demoName, 'password'=>$demoHash];
                $restore->close();
            }
        }
        $passwordIsValid = $admin && password_verify($password, $admin['password']);
        /* Repair only the bundled demo account's old hash when its default password is explicitly entered. */
        if (!$passwordIsValid && $admin && $email === 'admin@jkbags.com' && hash_equals('admin123', $password)) {
            $repairedHash = password_hash('admin123', PASSWORD_DEFAULT);
            $repair = $conn->prepare('UPDATE admins SET password=? WHERE id=?');
            if ($repair) {
                $repair->bind_param('si', $repairedHash, $admin['id']);
                $passwordIsValid = $repair->execute();
                $repair->close();
                if ($passwordIsValid) $admin['password'] = $repairedHash;
            }
        }
        if ($passwordIsValid) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['name'] ?: 'Admin';
            redirect('index.php');
        }
        $error = 'Login failed. Check that MySQL is running and use admin@jkbags.com with password admin123 for the local demo.';
    }
}
$pageTitle = 'Admin Login | J.K BAGS';
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($pageTitle)?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head>
<body><section class="form-page"><div class="form-box"><p class="eyebrow">STORE MANAGEMENT</p><h1>Admin Login</h1><p class="text-muted">Sign in to manage products, orders and customer messages.</p>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<form method="post" autocomplete="on"><?=csrf_field()?>
<label class="d-block mb-2">Admin email<input type="email" name="email" placeholder="admin@jkbags.com" autocomplete="username" required></label>
<label class="d-block mb-3">Password<input type="password" name="password" placeholder="Enter admin password" autocomplete="current-password" required></label>
<button class="btn-gold w-100" type="submit">SIGN IN TO DASHBOARD</button></form>
<div class="admin-demo"><strong>Local demo login</strong><br>Email: <code>admin@jkbags.com</code><br>Password: <code>admin123</code><br><small>Change this password before uploading the website online.</small></div>
<a href="../index.php">← Return to storefront</a></div></section></body></html>
