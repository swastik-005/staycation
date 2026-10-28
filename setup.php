<?php
// setup.php - Automatic Database Initializer & Migration for Staycation Hotel Management Software

function initialize_database() {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_port = 3306;
    $db_name = 'hotel_management_db';

    $is_cli = (php_sapi_name() === 'cli');

    try {
        $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $sql_file = __DIR__ . '/database/schema.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("Schema file not found at: {$sql_file}");
        }

        $sql = file_get_contents($sql_file);
        $pdo->exec($sql);

        $pdo->exec("USE `{$db_name}`");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'success' => true,
            'message' => 'Staycation database initialized successfully with schema and seed data!',
            'tables' => $tables,
            'db_name' => $db_name
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// If invoked directly in browser or CLI
$result = null;
if (isset($_POST['run_setup']) || isset($_GET['auto']) || (php_sapi_name() === 'cli')) {
    $result = initialize_database();
    if (php_sapi_name() === 'cli') {
        if ($result['success']) {
            echo "SUCCESS: " . $result['message'] . PHP_EOL;
            echo "Tables created: " . implode(', ', $result['tables']) . PHP_EOL;
            exit(0);
        } else {
            echo "ERROR: " . $result['message'] . PHP_EOL;
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup | Staycation Hotel Management Software</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #0b0b0e;
            --bg-card: #141419;
            --accent-gold: #c5a880;
            --accent-gold-light: #e0c8a8;
            --text-main: #f5f2eb;
            --text-muted: #8e8d99;
            --border: rgba(255, 255, 255, 0.08);
            --border-active: rgba(197, 168, 128, 0.4);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(197, 168, 128, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(197, 168, 128, 0.05) 0%, transparent 40%);
        }
        .container {
            width: 100%;
            max-width: 660px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 44px 40px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            backdrop-filter: blur(12px);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(197, 168, 128, 0.1);
            border: 1px solid var(--border-active);
            color: var(--accent-gold);
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-gold);
        }
        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
            color: #fff;
        }
        p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .info-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 0.88rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--text-muted); }
        .info-value { font-weight: 600; color: #fff; font-family: monospace; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 16px 24px;
            background: var(--accent-gold);
            color: #0b0b0e;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.05em;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            gap: 8px;
        }
        .btn:hover {
            background: var(--accent-gold-light);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(197, 168, 128, 0.25);
        }
        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border);
            margin-top: 12px;
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border-active);
            box-shadow: none;
        }
        .success-box {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .tag {
            background: rgba(255, 255, 255, 0.06);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            color: var(--accent-gold);
            font-family: monospace;
            text-decoration: none;
        }
        .tag:hover {
            background: var(--accent-gold);
            color: #000;
        }
        .nav-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">Staycation HMS • XAMPP SQL Integration</div>
        <h1>Staycation Database Setup</h1>
        <p>Initialize or reset the Staycation Hotel Management Software database tables, admin credentials, suite inventory, and sample guest bookings.</p>

        <?php if ($result && $result['success']): ?>
            <div class="success-box">
                <strong><i class="fa-solid fa-circle-check"></i> Setup Complete!</strong><br>
                <?= htmlspecialchars($result['message']) ?><br>
                <div style="margin-top: 10px; font-size: 0.85rem; color: #fff;">Click below to view each table in XAMPP phpMyAdmin:</div>
                <div class="tag-list">
                    <?php foreach ($result['tables'] as $tbl): ?>
                        <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=<?= urlencode($tbl) ?>" target="_blank" class="tag" title="View in phpMyAdmin">
                            <i class="fa-solid fa-table"></i> <?= htmlspecialchars($tbl) ?> &nearr;
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Database Name</span>
                    <span class="info-value">hotel_management_db</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Admin Username</span>
                    <span class="info-value">admin</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Admin Password</span>
                    <span class="info-value">admin123</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sample Booking Reference</span>
                    <span class="info-value">STAY-892104</span>
                </div>
                <div class="info-row">
                    <span class="info-label">XAMPP phpMyAdmin</span>
                    <span class="info-value"><a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=hotel_management_db" target="_blank" style="color:var(--accent-gold); text-decoration:underline;">View in phpMyAdmin &nearr;</a></span>
                </div>
            </div>

            <div class="nav-links">
                <a href="index.php" class="btn"><i class="fa-solid fa-hotel"></i> Guest Website</a>
                <a href="admin/login.php" class="btn btn-outline" style="margin-top:0;"><i class="fa-solid fa-gauge"></i> Staff Console</a>
            </div>

        <?php elseif ($result && !$result['success']): ?>
            <div class="error-box">
                <strong>Database Error:</strong><br>
                <?= htmlspecialchars($result['message']) ?><br><br>
                Please verify that the <strong>MySQL</strong> service is started in your <strong>XAMPP Control Panel</strong>.
            </div>

            <form method="POST">
                <button type="submit" name="run_setup" class="btn">Retry Database Installation</button>
            </form>
        <?php else: ?>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">MySQL Host</span>
                    <span class="info-value">localhost:3306</span>
                </div>
                <div class="info-row">
                    <span class="info-label">MySQL User</span>
                    <span class="info-value">root</span>
                </div>
                <div class="info-row">
                    <span class="info-label">MySQL Password</span>
                    <span class="info-value"><em>(empty default)</em></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Target Database</span>
                    <span class="info-value">hotel_management_db</span>
                </div>
                <div class="info-row">
                    <span class="info-label">phpMyAdmin Link</span>
                    <span class="info-value"><a href="http://localhost/phpmyadmin/" target="_blank" style="color:var(--accent-gold);">Open phpMyAdmin &nearr;</a></span>
                </div>
            </div>

            <form method="POST">
                <button type="submit" name="run_setup" class="btn"><i class="fa-solid fa-database"></i> Install & Populate Database</button>
            </form>
            <a href="index.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
        <?php endif; ?>
    </div>
</body>
</html>
