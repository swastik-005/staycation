<?php
// config/db.php - Database Configuration for Hotel Management System

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'hotel_management_db';
$db_port = 3306;

// Create PDO connection
try {
    $dsn = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
    $pdo_root = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check if database exists
    $stmt = $pdo_root->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$db_name}'");
    $db_exists = $stmt->fetch();

    if ($db_exists) {
        $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        $pdo = null;
    }
} catch (PDOException $e) {
    $db_error = $e->getMessage();
    $pdo = null;
    $pdo_root = null;
}

// Function to ensure DB exists or prompt setup
function getDBConnection() {
    global $pdo, $pdo_root, $db_name;
    if ($pdo !== null) {
        return $pdo;
    }

    if ($pdo_root !== null) {
        // Automatically attempt to include setup
        $setup_file = __DIR__ . '/../setup.php';
        if (file_exists($setup_file)) {
            require_once $setup_file;
            initialize_database();
            return new PDO("mysql:host=localhost;dbname={$db_name};charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
    }

    die("<div style='font-family:sans-serif;padding:30px;background:#1a1a1a;color:#f5f5f5;max-width:600px;margin:50px auto;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.5);'>
        <h2 style='color:#dfba89;'>Database Connection Required</h2>
        <p>Could not connect to MySQL server. Please make sure <strong>XAMPP MySQL</strong> service is running.</p>
        <p>Once MySQL is started in XAMPP, click below to initialize the database:</p>
        <a href='setup.php' style='display:inline-block;padding:12px 24px;background:#c5a880;color:#000;text-decoration:none;font-weight:bold;border-radius:6px;margin-top:10px;'>Run Database Setup</a>
    </div>");
}

