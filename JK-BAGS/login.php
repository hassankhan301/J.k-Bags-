<?php
require_once __DIR__ . '/includes/db.php';
if (!empty($_SESSION['user_id'])) redirect('index.php');
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email address and password.';
    } else {
        $stmt = $conn->prepare('SELECT id,name,email,password FROM users WHERE email=? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = stmt_result($stmt)->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            flash('success', 'Welcome back, ' . $user['name'] . '! You are now signed in.');
            $destination = (string)($_SESSION['after_login'] ?? 'index.php');
            unset($_SESSION['after_login']);
            if (!preg_match('/^(index\.php|my-orders\.php|checkout\.php|products\.php|cart\.php)$/', $destination)) $destination = 'index.php';
            redirect($destination);
        }
        $error = 'That email and password do not match. Please try again.';
    }
}
$pageTitle = 'Login | J.K BAGS';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page account-page"><div class="form-box account-box"><div class="account-symbol"><i class="fa-regular fa-user"></i></div><p class="eyebrow">WELCOME BACK</p><h1>Lovely to see you.</h1><p class="form-intro">Sign in to see your orders, save favourite bags and make checkout easier.</p><?php if ($error): ?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif; ?><form method="post" action="login.php"><?=csrf_field()?><label class="form-label" for="login-email">Email address</label><input id="login-email" type="email" name="email" autocomplete="email" maxlength="150" placeholder="you@example.com" value="<?=e($email)?>" required><label class="form-label" for="login-password">Password</label><input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required><button class="btn-gold" type="submit">SIGN IN <i class="fa-solid fa-arrow-right"></i></button></form><p class="account-switch">New to J.K BAGS? <a href="register.php">Create your account</a></p><a class="back-link" href="products.php"><i class="fa-solid fa-arrow-left"></i> Continue shopping</a></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
