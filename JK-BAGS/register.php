<?php
require_once __DIR__ . '/includes/db.php';
if (!empty($_SESSION['user_id'])) redirect('index.php');
$error = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (text_length($name) < 2 || text_length($name) > 100) $error = 'Please enter your name (2–100 characters).';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || text_length($email) > 150) $error = 'Please enter a valid email address.';
    elseif (strlen($password) < 8) $error = 'Choose a password with at least 8 characters.';
    else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users(name,email,password) VALUES(?,?,?)');
        $stmt->bind_param('sss', $name, $email, $hash);
        if ($stmt->execute()) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$stmt->insert_id;
            $_SESSION['user_name'] = $name;
            flash('success', 'Your account is ready. Welcome to J.K BAGS!');
            redirect('index.php');
        }
        $error = 'This email may already be registered. Try signing in instead.';
    }
}
$pageTitle = 'Create Account | J.K BAGS';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page account-page"><div class="form-box account-box"><div class="account-symbol"><i class="fa-regular fa-heart"></i></div><p class="eyebrow">JOIN THE J.K COMMUNITY</p><h1>Create your account.</h1><p class="form-intro">Keep your favourites close and follow your orders from one place.</p><?php if ($error): ?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif; ?><form method="post" action="register.php"><?=csrf_field()?><label class="form-label" for="reg-name">Full name</label><input id="reg-name" name="name" maxlength="100" autocomplete="name" placeholder="Your name" value="<?=e($name)?>" required><label class="form-label" for="reg-email">Email address</label><input id="reg-email" type="email" name="email" maxlength="150" autocomplete="email" placeholder="you@example.com" value="<?=e($email)?>" required><label class="form-label" for="reg-password">Create password</label><input id="reg-password" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="At least 8 characters" required><button class="btn-gold" type="submit">CREATE ACCOUNT <i class="fa-solid fa-arrow-right"></i></button></form><p class="account-switch">Already have an account? <a href="login.php">Sign in</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
