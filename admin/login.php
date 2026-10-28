<?php
// admin/login.php - Staycation Staff & Receptionist Authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $pdo = getDBConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_role'] = $user['role'];

                header('Location: index.php');
                exit;
            } else {
                $error = "Invalid username or password credentials.";
            }
        } catch (Exception $e) {
            $error = "Authentication error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Staycation Hotel Management Software</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #08080b;
            --bg-card: #131318;
            --accent-gold: #c5a880;
            --text-main: #f5f2eb;
            --text-muted: #8c8b96;
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
            background-image: radial-gradient(circle at 50% 20%, rgba(197, 168, 128, 0.08) 0%, transparent 50%);
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 44px 38px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.7);
        }
        .brand-badge {
            display: inline-block;
            font-size: 0.72rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--accent-gold);
            font-weight: 600;
            margin-bottom: 8px;
        }
        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
        }
        p {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent-gold);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px 12px 40px;
            color: #fff;
            font-family: inherit;
            font-size: 0.92rem;
            outline: none;
            transition: all 0.3s;
        }
        input:focus {
            border-color: var(--accent-gold);
            background: rgba(255, 255, 255, 0.05);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: var(--accent-gold);
            color: #08080b;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.92rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn:hover {
            background: #dfba89;
            transform: translateY(-1px);
        }
        .error-alert {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
        .hint-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed var(--border);
            padding: 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 24px;
            text-align: center;
            line-height: 1.6;
        }
        .hint-box code {
            color: var(--accent-gold);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <span class="brand-badge">Hotel Management Software</span>
        <h1>Staycation Staff Portal</h1>
        <p>Sign in to access front-desk operations, room inventory, central reservations, and guest folios.</p>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Username (e.g. admin)" value="admin" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Password" value="admin123" required>
                </div>
            </div>

            <button type="submit" class="btn">Sign In to Staycation <i class="fa-solid fa-arrow-right"></i></button>
        </form>

        <div class="hint-box">
            Default credentials: <code>admin</code> / <code>admin123</code><br>
            <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=users" target="_blank" style="color: var(--accent-gold); text-decoration: none; font-size: 0.78rem;">View users table in phpMyAdmin &nearr;</a>
        </div>
        <div style="text-align: center; margin-top: 18px;">
            <a href="../index.php" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">&larr; Return to Guest Website</a>
        </div>
    </div>
</body>
</html>
