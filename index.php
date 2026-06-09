<?php
// =============================================
// index.php — Login Page
// =============================================
include 'db.php';

if (isset($_SESSION['role'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin.php' : 'exam.php'));
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = MD5($_POST['password']);

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $user   = mysqli_fetch_assoc($result);

    if ($user) {
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['profile_image'] = $user['profile_image'];
        header("Location: " . ($user['role'] == 'admin' ? 'admin.php' : 'exam.php'));
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPortal — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink:   #0f0e17;
            --cream: #fffcf5;
            --gold:  #e8a838;
            --gold2: #c47f17;
            --muted: #6b6672;
            --error: #d64045;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* LEFT */
        .left-panel {
            flex: 1;
            background: var(--ink);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 64px;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle, rgba(232,168,56,0.18) 0%, transparent 70%);
            top: -100px; right: -100px;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px; border-radius: 50%;
            background: radial-gradient(circle, rgba(232,168,56,0.10) 0%, transparent 70%);
            bottom: 60px; left: 30px;
        }
        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 13px; letter-spacing: 4px;
            text-transform: uppercase; color: var(--gold);
            margin-bottom: 48px; position: relative; z-index: 1;
        }
        .left-headline {
            font-family: 'Playfair Display', serif;
            font-size: clamp(38px, 4vw, 60px);
            font-weight: 900; color: var(--cream);
            line-height: 1.1; margin-bottom: 24px;
            position: relative; z-index: 1;
        }
        .left-headline span { color: var(--gold); }
        .decorative-line {
            width: 60px; height: 3px;
            background: var(--gold); margin: 28px 0;
            position: relative; z-index: 1;
        }
        .left-sub {
            font-size: 15px; color: rgba(255,252,245,0.5);
            line-height: 1.7; max-width: 320px;
            position: relative; z-index: 1;
        }

        /* RIGHT */
        .right-panel {
            width: 480px; flex-shrink: 0;
            background: #fff;
            display: flex; flex-direction: column;
            justify-content: center;
            padding: 60px 52px;
            position: relative;
        }
        .right-panel::before {
            content: '';
            position: absolute; top: 0; left: 0;
            width: 4px; height: 100%;
            background: linear-gradient(to bottom, var(--gold), var(--gold2));
        }

        .form-eyebrow {
            font-size: 11px; letter-spacing: 3px;
            text-transform: uppercase; color: var(--gold);
            font-weight: 500; margin-bottom: 10px;
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px; font-weight: 700;
            color: var(--ink); margin-bottom: 34px;
        }

        .field { margin-bottom: 20px; }
        .field label {
            display: block; font-size: 11px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 7px;
        }
        .field input {
            width: 100%; padding: 13px 16px;
            border: 1.5px solid #e8e4dc; border-radius: 8px;
            font-family: 'DM Sans', sans-serif; font-size: 15px;
            color: var(--ink); background: #fdfaf4;
            transition: border 0.2s, box-shadow 0.2s; outline: none;
        }
        .field input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(232,168,56,0.12);
            background: #fff;
        }
        .error-msg {
            background: #fff0f0; border: 1px solid var(--error);
            color: var(--error); padding: 11px 14px;
            border-radius: 7px; font-size: 13px; margin-bottom: 20px;
        }

        .btn-login {
            width: 100%; padding: 14px;
            background: var(--ink); color: var(--cream);
            border: none; border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 500; cursor: pointer;
            letter-spacing: 0.5px; margin-top: 6px;
            position: relative; overflow: hidden;
            transition: transform 0.15s;
        }
        .btn-login::after {
            content: ''; position: absolute; inset: 0;
            background: var(--gold); transform: scaleX(0);
            transform-origin: left; transition: transform 0.3s ease; z-index: 0;
        }
        .btn-login:hover::after { transform: scaleX(1); }
        .btn-login span { position: relative; z-index: 1; }
        .btn-login:active { transform: scale(0.98); }

        /* ── Register prompt ── */
        .register-prompt {
            margin-top: 30px; padding-top: 26px;
            border-top: 1px solid #f0ece4;
            display: flex; align-items: center; gap: 16px;
        }
        .register-prompt p {
            font-size: 14px; color: var(--muted); line-height: 1.5; flex: 1;
        }
        .register-prompt strong { color: var(--ink); }
        .btn-register {
            flex-shrink: 0; padding: 11px 22px;
            border: 2px solid var(--ink); background: transparent;
            color: var(--ink); border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 500; cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
            white-space: nowrap;
        }
        .btn-register:hover { background: var(--ink); color: var(--cream); }

        /* Floating ring decorations */
        .ring {
            position: absolute; border-radius: 50%;
            border: 2px solid rgba(232,168,56,0.13);
            pointer-events: none;
        }
        .ring-1 { width: 110px; height: 110px; top: 28px; right: 38px; animation: spin 22s linear infinite; }
        .ring-2 { width: 55px;  height: 55px;  bottom: 90px; right: 70px; animation: spin 14s linear infinite reverse; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Entrance */
        .right-panel > * {
            opacity: 0; transform: translateY(14px);
            animation: fadeUp 0.5s ease forwards;
        }
        .form-eyebrow    { animation-delay: 0.08s; }
        .form-title      { animation-delay: 0.16s; }
        .error-msg       { animation-delay: 0.2s;  }
        .field           { animation-delay: 0.24s; }
        .field + .field  { animation-delay: 0.32s; }
        .btn-login       { animation-delay: 0.40s; }
        .register-prompt { animation-delay: 0.48s; }
        @keyframes fadeUp { to { opacity: 1; transform: none; } }

        @media (max-width: 800px) {
            body { flex-direction: column; overflow: auto; }
            .left-panel { padding: 40px 28px; }
            .right-panel { width: 100%; padding: 40px 28px; }
        }
    </style>
</head>
<body>

<div class="left-panel">
    <div class="brand">ExamPortal</div>
    <h1 class="left-headline">Test your<br><span>knowledge.</span><br>Prove your<br>worth.</h1>
    <div class="decorative-line"></div>
    <p class="left-sub">A secure, modern online examination platform for students and administrators.</p>
</div>

<div class="right-panel">
    <div class="ring ring-1"></div>
    <div class="ring ring-2"></div>

    <p class="form-eyebrow">Welcome back</p>
    <h2 class="form-title">Sign In</h2>

    <?php if ($error): ?>
        <div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter your username" required autofocus>
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn-login"><span>Sign In →</span></button>
    </form>

    <div class="register-prompt">
        <p><strong>Don't have an account?</strong><br>Join as a student today.</p>
        <a href="register.php" class="btn-register">Register →</a>
    </div>
</div>

</body>
</html>