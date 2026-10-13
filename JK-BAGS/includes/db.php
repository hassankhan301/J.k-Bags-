<?php
/* J.K BAGS database bootstrap. XAMPP defaults: MySQL root user, empty password. */
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}

if (!class_exists('mysqli')) {
    http_response_code(500);
    exit('PHP mysqli extension is not enabled. In XAMPP, enable mysqli in php.ini and restart Apache.');
}
mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'jkbags';
if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
    http_response_code(500);
    exit('DB_NAME may contain only letters, numbers and underscores.');
}

$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_errno) {
    /* Local XAMPP often reaches this branch the first time: database not created yet. */
    $bootstrapConn = @new mysqli($dbHost, $dbUser, $dbPass);
    if ($bootstrapConn->connect_errno) {
        http_response_code(500);
        exit('Cannot connect to MySQL. Start MySQL in XAMPP and check DB_HOST / DB_USER / DB_PASS in includes/db.php. MySQL says: ' . htmlspecialchars($bootstrapConn->connect_error, ENT_QUOTES, 'UTF-8'));
    }
    if (!$bootstrapConn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        http_response_code(500);
        exit('Database jkbags could not be created. Import database.sql in phpMyAdmin, or give your database user CREATE DATABASE permission. MySQL says: ' . htmlspecialchars($bootstrapConn->error, ENT_QUOTES, 'UTF-8'));
    }
    if (!$bootstrapConn->select_db($dbName)) {
        http_response_code(500);
        exit('Could not select database ' . htmlspecialchars($dbName, ENT_QUOTES, 'UTF-8') . '. Check the database credentials.');
    }
    $conn = $bootstrapConn;
}
$conn->set_charset('utf8mb4');

/* Keep a missing or partially imported local database from breaking the admin panel. */
$schema = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category VARCHAR(80) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        image TEXT NOT NULL,
        description TEXT,
        badge VARCHAR(40) DEFAULT '',
        featured TINYINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        customer_name VARCHAR(100) NOT NULL DEFAULT '',
        phone VARCHAR(40) NOT NULL DEFAULT '',
        address TEXT,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_jkbags_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        CONSTRAINT fk_jkbags_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_jkbags_item_product FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL DEFAULT '',
        email VARCHAR(150) NOT NULL DEFAULT '',
        subject VARCHAR(200) NOT NULL DEFAULT '',
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];
/* Always create missing tables, even when an older database already has some tables. */
foreach ($schema as $ddl) {
    if (!$conn->query($ddl)) {
        http_response_code(500);
        exit('Database setup failed. Import database.sql or check MySQL permissions. MySQL says: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
    }
}

/* Upgrade legacy tables safely. CREATE TABLE IF NOT EXISTS alone does not repair old schemas. */
$requiredColumns = [
    'users' => [
        'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'email' => "VARCHAR(150) NOT NULL DEFAULT ''",
        'password' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ],
    'admins' => [
        'name' => "VARCHAR(100) NOT NULL DEFAULT 'J.K Admin'",
        'email' => "VARCHAR(150) NOT NULL DEFAULT ''",
        'password' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ],
    'products' => [
        'name' => "VARCHAR(150) NOT NULL DEFAULT 'New Bag'",
        'category' => "VARCHAR(80) NOT NULL DEFAULT 'Hand Bag'",
        'price' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
        'stock' => 'INT NOT NULL DEFAULT 0',
        'image' => 'TEXT NULL',
        'description' => 'TEXT NULL',
        'badge' => "VARCHAR(40) NOT NULL DEFAULT ''",
        'featured' => 'TINYINT NOT NULL DEFAULT 0',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ],
    'orders' => [
        'user_id' => 'INT NULL',
        'customer_name' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'phone' => "VARCHAR(40) NOT NULL DEFAULT ''",
        'address' => 'TEXT NULL',
        'total' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
        'status' => "VARCHAR(30) NOT NULL DEFAULT 'Pending'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ],
    'order_items' => [
        'order_id' => 'INT NOT NULL DEFAULT 0',
        'product_id' => 'INT NOT NULL DEFAULT 0',
        'quantity' => 'INT NOT NULL DEFAULT 1',
        'price' => 'DECIMAL(10,2) NOT NULL DEFAULT 0'
    ],
    'messages' => [
        'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'email' => "VARCHAR(150) NOT NULL DEFAULT ''",
        'subject' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'message' => 'TEXT NULL',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ]
];
foreach ($requiredColumns as $table => $columns) {
    $known = [];
    $columnResult = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($columnResult) {
        while ($column = $columnResult->fetch_assoc()) $known[$column['Field']] = true;
    }
    foreach ($columns as $columnName => $definition) {
        if (!isset($known[$columnName])) {
            if (!$conn->query("ALTER TABLE `$table` ADD COLUMN `$columnName` $definition")) {
                http_response_code(500);
                exit('Could not repair the old ' . htmlspecialchars($table, ENT_QUOTES, 'UTF-8') . ' table. Import database.sql or check table permissions. MySQL says: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
            }
        }
    }
}

/* First-run sample catalogue. Product photos are stored in the project, so they work offline. */
$countResult = $conn->query('SELECT COUNT(*) AS total FROM products');
if ($countResult && (int)$countResult->fetch_assoc()['total'] === 0) {
    $seedProducts = [
        ['Luna Classic Bag','Hand Bag',8999,20,'assets/images/products/luna-classic.jpg','Elegant everyday handbag with a refined silhouette.','NEW',1],
        ['Sophia Shoulder Bag','Shoulder Bag',7499,15,'assets/images/products/sophia-shoulder.jpg','A polished shoulder bag for day-to-evening style.','BEST SELLER',1],
        ['Amelia Crossbody','Crossbody Bag',6999,18,'assets/images/products/amelia-crossbody.jpg','Compact crossbody design for effortless everyday use.','NEW',1],
        ['Grace Evening Clutch','Clutch',5499,12,'assets/images/products/grace-evening-clutch.jpg','A sophisticated clutch for evenings and celebrations.','LIMITED',1],
        ['Bella Mini Bag','Hand Bag',6299,25,'assets/images/products/bella-mini.jpg','Chic mini bag with a versatile modern look.','',0],
        ['Victoria Tote','Tote Bag',9999,10,'assets/images/products/victoria-tote.jpg','Spacious premium tote for work and travel.','PREMIUM',1],
        ['Elena Shoulder Bag','Shoulder Bag',7999,14,'assets/images/products/elena-shoulder.jpg','Elegant shoulder style with a timeless finish.','',0],
        ['Rose Party Clutch','Clutch',4799,10,'assets/images/products/rose-party-clutch.jpg','A statement clutch for special occasions.','SALE',0],
        ['Emma Luxury Bag','Hand Bag',10999,8,'assets/images/products/emma-luxury.jpg','Luxury-inspired statement handbag.','LUXURY',1],
        ['Mia Crossbody','Crossbody Bag',5899,16,'assets/images/products/mia-crossbody.jpg','Lightweight crossbody for daily styling.','',0],
        ['Charlotte Tote','Tote Bag',9499,9,'assets/images/products/charlotte-tote.jpg','Classic tote with a modern premium finish.','NEW',1],
        ['Isabella Mini','Crossbody Bag',6799,13,'assets/images/products/isabella-mini.jpg','Minimal mini crossbody for effortless style.','',0]
    ];
    $ins = $conn->prepare('INSERT INTO products(name,category,price,stock,image,description,badge,featured) VALUES(?,?,?,?,?,?,?,?)');
    if ($ins) {
        foreach ($seedProducts as $row) {
            [$name,$category,$price,$stock,$image,$description,$badge,$featured] = $row;
            $ins->bind_param('ssdisssi', $name, $category, $price, $stock, $image, $description, $badge, $featured);
            $ins->execute();
        }
        $ins->close();
    }
}

/* Migrate only bundled sample images from older project versions; preserve uploaded/custom images. */
$conn->query("UPDATE products SET image=CASE
    WHEN image='assets/images/products/luna-classic.svg' THEN 'assets/images/products/luna-classic.jpg'
    WHEN image='assets/images/products/sophia-shoulder.svg' THEN 'assets/images/products/sophia-shoulder.jpg'
    WHEN image='assets/images/products/amelia-crossbody.svg' THEN 'assets/images/products/amelia-crossbody.jpg'
    WHEN image='assets/images/products/grace-evening-clutch.svg' THEN 'assets/images/products/grace-evening-clutch.jpg'
    WHEN image='assets/images/products/bella-mini.svg' THEN 'assets/images/products/bella-mini.jpg'
    WHEN image='assets/images/products/victoria-tote.svg' THEN 'assets/images/products/victoria-tote.jpg'
    WHEN image='assets/images/products/elena-shoulder.svg' THEN 'assets/images/products/elena-shoulder.jpg'
    WHEN image='assets/images/products/rose-party-clutch.svg' THEN 'assets/images/products/rose-party-clutch.jpg'
    WHEN image='assets/images/products/emma-luxury.svg' THEN 'assets/images/products/emma-luxury.jpg'
    WHEN image='assets/images/products/mia-crossbody.svg' THEN 'assets/images/products/mia-crossbody.jpg'
    WHEN image='assets/images/products/charlotte-tote.svg' THEN 'assets/images/products/charlotte-tote.jpg'
    WHEN image='assets/images/products/isabella-mini.svg' THEN 'assets/images/products/isabella-mini.jpg'
    WHEN image LIKE '%images.unsplash.com%' THEN CASE id
        WHEN 1 THEN 'assets/images/products/luna-classic.jpg'
        WHEN 2 THEN 'assets/images/products/sophia-shoulder.jpg'
        WHEN 3 THEN 'assets/images/products/amelia-crossbody.jpg'
        WHEN 4 THEN 'assets/images/products/grace-evening-clutch.jpg'
        WHEN 5 THEN 'assets/images/products/bella-mini.jpg'
        WHEN 6 THEN 'assets/images/products/victoria-tote.jpg'
        WHEN 7 THEN 'assets/images/products/elena-shoulder.jpg'
        WHEN 8 THEN 'assets/images/products/rose-party-clutch.jpg'
        WHEN 9 THEN 'assets/images/products/emma-luxury.jpg'
        WHEN 10 THEN 'assets/images/products/mia-crossbody.jpg'
        WHEN 11 THEN 'assets/images/products/charlotte-tote.jpg'
        WHEN 12 THEN 'assets/images/products/isabella-mini.jpg'
        ELSE image END
    ELSE image END
WHERE image IN ('assets/images/products/luna-classic.svg','assets/images/products/sophia-shoulder.svg','assets/images/products/amelia-crossbody.svg','assets/images/products/grace-evening-clutch.svg','assets/images/products/bella-mini.svg','assets/images/products/victoria-tote.svg','assets/images/products/elena-shoulder.svg','assets/images/products/rose-party-clutch.svg','assets/images/products/emma-luxury.svg','assets/images/products/mia-crossbody.svg','assets/images/products/charlotte-tote.svg','assets/images/products/isabella-mini.svg') OR image LIKE '%images.unsplash.com%'");

/* Default admin is only inserted if the email doesn't already exist. */
$adminEmail = 'admin@jkbags.com';
$adminCheck = $conn->prepare('SELECT id FROM admins WHERE email=? LIMIT 1');
$adminCheck->bind_param('s', $adminEmail);
$adminCheck->execute();
$adminCheck->store_result();
$adminExists = $adminCheck->num_rows > 0;
$adminCheck->close();
if (!$adminExists) {
    $defaultAdminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $adminName = 'J.K Admin';
    $adminSeed = $conn->prepare('INSERT INTO admins(name,email,password) VALUES(?,?,?)');
    if ($adminSeed) {
        $adminSeed->bind_param('sss', $adminName, $adminEmail, $defaultAdminHash);
        $adminSeed->execute();
        $adminSeed->close();
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* Portable buffered result wrapper: keeps prepared SELECTs working even without mysqlnd/get_result(). */
final class JKBagsStatementResult {
    private array $rows;
    private int $cursor = 0;
    public int $num_rows;
    public function __construct(array $rows) {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }
    public function fetch_assoc(): ?array {
        if (!array_key_exists($this->cursor, $this->rows)) return null;
        return $this->rows[$this->cursor++];
    }
}
function stmt_result(mysqli_stmt $stmt): JKBagsStatementResult {
    $rows = [];
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            $result->free();
        }
        return new JKBagsStatementResult($rows);
    }
    $meta = $stmt->result_metadata();
    if (!$meta) return new JKBagsStatementResult([]);
    $fields = $meta->fetch_fields();
    $values = [];
    $bindings = [];
    foreach ($fields as $field) {
        $values[$field->name] = null;
        $bindings[] = &$values[$field->name];
    }
    if ($bindings && call_user_func_array([$stmt, 'bind_result'], $bindings)) {
        while ($stmt->fetch()) {
            $row = [];
            foreach ($values as $key => $value) $row[$key] = $value;
            $rows[] = $row;
        }
    }
    $meta->free();
    return new JKBagsStatementResult($rows);
}
function text_length(string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}
function money($value): string {
    return 'PKR ' . number_format((float)$value);
}
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(419);
        exit('Session expired. Refresh the page and try again.');
    }
}
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) redirect('login.php');
}
