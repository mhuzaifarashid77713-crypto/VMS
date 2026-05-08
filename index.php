<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
       header("Location: /admin/dashboard.php");
    } else {
        header("Location: /user/index.php");
    }
    exit();
}

$error = '';
$success = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $pwd_match = password_verify($password, $user['password']) || md5($password) === $user['password'];
if ($pwd_match) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: /admin/dashboard.php");
            } else {
                header("Location: /user/index.php");
            }
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "This email is not registered.";
    }
}

// Handle Signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
        $mode = 'signup';
    } elseif ($password !== $confirm) {
        $error = "The two passwords do not match.";
        $mode = 'signup';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This email is already registered. Please log in.";
            $mode = 'signup';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'user')");
            $ins->bind_param("sss", $full_name, $email, $hashed);
            if ($ins->execute()) {
                $success = "Account created successfully! Please log in now.";
                $mode = 'login';
            } else {
                $error = "Something went wrong. Please try again.";
                $mode = 'signup';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMS — Vaccine Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00b894;
            --primary-dark: #00a381;
            --accent: #0984e3;
            --danger: #d63031;
            --bg: #0a0e1a;
            --surface: #111827;
            --surface2: #1a2235;
            --border: rgba(255,255,255,0.08);
            --text: #e8eaf0;
            --text-muted: #8892a4;
            --glow: 0 0 40px rgba(0,184,148,0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: var(--text);
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(0,184,148,0.12) 0%, transparent 70%);
            top: -200px; left: -200px;
            animation: drift 8s ease-in-out infinite alternate;
        }
        body::after {
            content: '';
            position: fixed;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(9,132,227,0.10) 0%, transparent 70%);
            bottom: -150px; right: -150px;
            animation: drift2 10s ease-in-out infinite alternate;
        }

        @keyframes drift { from { transform: translate(0,0); } to { transform: translate(60px,80px); } }
        @keyframes drift2 { from { transform: translate(0,0); } to { transform: translate(-50px,-60px); } }

        .grid-bg {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            min-height: 600px;
            background: var(--surface);
            border-radius: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--glow), 0 40px 80px rgba(0,0,0,0.5);
            overflow: hidden;
            position: relative;
            z-index: 10;
            margin: 20px;
        }

        /* Left Panel */
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #0d1b2a 0%, #0a2a1f 50%, #0d1b2a 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(0,184,148,0.2) 0%, transparent 70%);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }

        .brand {
            position: relative;
            z-index: 1;
        }

        .brand-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,184,148,0.4);
        }

        .brand h1 {
            font-family: 'Syne', sans-serif;
            font-size: 42px;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 8px;
        }

        .brand h1 span { color: var(--primary); }

        .brand p {
            color: var(--text-muted);
            font-size: 15px;
            margin-bottom: 50px;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .feature-icon {
            width: 36px; height: 36px;
            background: rgba(0,184,148,0.1);
            border: 1px solid rgba(0,184,148,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

       .right-panel {
    width: 100%;
    max-width: 440px;
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: var(--surface);
}
        .tab-switcher {
            display: flex;
            background: var(--surface2);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 36px;
            border: 1px solid var(--border);
        }

        .tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 9px;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 15px rgba(0,184,148,0.3);
        }

        .form-title {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
        }

        .form-subtitle {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 28px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error { background: rgba(214,48,49,0.1); border: 1px solid rgba(214,48,49,0.3); color: #ff7675; }
        .alert-success { background: rgba(0,184,148,0.1); border: 1px solid rgba(0,184,148,0.3); color: var(--primary); }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 16px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: all 0.2s;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,184,148,0.1);
        }

        .form-group input::placeholder { color: var(--text-muted); }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 6px 20px rgba(0,184,148,0.3);
            margin-top: 8px;
            letter-spacing: 0.3px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(0,184,148,0.4);
        }

        .demo-creds {
            margin-top: 24px;
            padding: 14px;
            background: var(--surface2);
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
        }

        .demo-creds strong { color: var(--primary); display: block; margin-bottom: 4px; }

        .form-panel { display: none; }
        .form-panel.active { display: block; }

        @media (max-width: 768px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; }
        }
    </style>
</head>
<body>
<div class="grid-bg"></div>

<div class="container">
    <!-- Left Branding Panel -->
    <div class="left-panel">
        <div class="brand">
            <div class="brand-icon">💉</div>
            <h1>Vaccine<br><span>Management</span><br>System</h1>
            <p>Your complete vaccine inventory in one place</p>
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🛡️</div>
                    <span>Secure Admin Dashboard — everything under control</span>
                </div>
                <div class="feature">
                    <div class="feature-icon">💊</div>
                    <span>Vaccine Inventory — add, edit, and delete easily</span>
                </div>
                <div class="feature">
                    <div class="feature-icon">👥</div>
                    <span>User Management — manage accounts easily</span>
                </div>
                <div class="feature">
                    <div class="feature-icon">📅</div>
                    <span>Expiry Tracking — get alerts before the expiry date</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="right-panel">
        <!-- Tab Switcher -->
        <div class="tab-switcher">
            <button class="tab-btn <?= $mode === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Login</button>
            <button class="tab-btn <?= $mode === 'signup' ? 'active' : '' ?>" onclick="switchTab('signup')">Sign Up</button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="form-panel <?= $mode === 'login' ? 'active' : '' ?>" id="panel-login">
            <h2 class="form-title">Welcome Back!</h2>
            <p class="form-subtitle">Log in to your account</p>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="aapka@email.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn-submit">Login Karein →</button>
            </form>

            <div class="demo-creds">
                <strong>🔑 Admin Demo Account:</strong>
                Email: admin@vms.com &nbsp;|&nbsp; Password: 123456
            </div>
        </div> 
    

        <!-- Signup Form -->
        <div class="form-panel <?= $mode === 'signup' ? 'active' : '' ?>" id="panel-signup">
            <h2 class="form-title">Create an Account</h2>
            <p class="form-subtitle">Register a new account</p>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="Your Name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="aapka@email.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Dobara likhein" required>
                </div>
                <button type="submit" name="signup" class="btn-submit">Create an Account →</button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
    document.getElementById(`panel-${tab}`).classList.add('active');
}
</script>
</body>
</html>
