<?php

include 'db.php';

if (isset($_SESSION['role'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin.php' : 'exam.php'));
    exit();
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username  = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password  = MD5($_POST['password']);
    $imageData = $_POST['captured_image'] ?? ''; // base64 from camera
    $filename  = "default.png";

    // Validate username not taken
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username already exists. Please choose another.";
    } elseif (empty($username) || strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($_POST['password']) < 4) {
        $error = "Password must be at least 4 characters.";
    } else {

        // Handle camera-captured image (base64 PNG)
        if (!empty($imageData) && strpos($imageData, 'data:image') === 0) {
            // Strip base64 header
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imgBinary = base64_decode($base64);

            if ($imgBinary !== false) {
                $filename = 'student_' . uniqid() . '.png';
                $dest     = 'uploads/profile_images/' . $filename;

                if (!file_put_contents($dest, $imgBinary)) {
                    $error    = "Failed to save image. Check folder permissions.";
                    $filename = "default.png";
                }
            }
        }
        // Fallback: handle traditional file upload if no camera image
        elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!in_array($_FILES['profile_image']['type'], $allowed)) {
                $error = "Only JPG, PNG, GIF, WEBP images are allowed.";
            } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                $error = "Image must be less than 2MB.";
            } else {
                $ext      = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $filename = 'student_' . uniqid() . '.' . strtolower($ext);
                $dest     = 'uploads/profile_images/' . $filename;
                if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                    $error    = "Failed to upload image.";
                    $filename = "default.png";
                }
            }
        }

        if (!$error) {
            $sql = "INSERT INTO users (username, password, role, profile_image)
                    VALUES ('$username', '$password', 'student', '$filename')";
            if (mysqli_query($conn, $sql)) {
                $success = "Account created! <a href='index.php'>Sign in here →</a>";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPortal — Register</title>
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
            --ok:    #2a9d5c;
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
            flex: 1; background: var(--ink);
            display: flex; flex-direction: column;
            justify-content: center; align-items: flex-start;
            padding: 60px 64px; position: relative; overflow: hidden;
        }
        .left-panel::before {
            content: ''; position: absolute;
            width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle, rgba(232,168,56,0.18) 0%, transparent 70%);
            top: -100px; right: -100px;
        }
        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 13px; letter-spacing: 4px;
            text-transform: uppercase; color: var(--gold);
            margin-bottom: 48px; position: relative; z-index: 1;
        }
        .left-headline {
            font-family: 'Playfair Display', serif;
            font-size: clamp(36px, 4vw, 56px);
            font-weight: 900; color: var(--cream);
            line-height: 1.1; margin-bottom: 24px;
            position: relative; z-index: 1;
        }
        .left-headline span { color: var(--gold); }
        .decorative-line {
            width: 60px; height: 3px; background: var(--gold);
            margin: 28px 0; position: relative; z-index: 1;
        }
        .left-sub {
            font-size: 14px; color: rgba(255,252,245,0.5);
            line-height: 1.8; max-width: 300px;
            position: relative; z-index: 1;
        }
        .left-sub li { list-style: none; padding: 5px 0; display: flex; align-items: center; gap: 10px; }
        .left-sub li::before { content: '✦'; color: var(--gold); font-size: 10px; }

        /* RIGHT */
        .right-panel {
            width: 520px; flex-shrink: 0;
            background: #fff;
            display: flex; flex-direction: column;
            justify-content: center;
            padding: 48px 52px;
            position: relative;
            overflow-y: auto;
        }
        .right-panel::before {
            content: ''; position: absolute; top: 0; left: 0;
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
            font-size: 30px; font-weight: 700;
            color: var(--ink); margin-bottom: 28px;
        }

        .field { margin-bottom: 18px; }
        .field label {
            display: block; font-size: 11px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 7px;
        }
        .field input[type="text"],
        .field input[type="password"] {
            width: 100%; padding: 12px 16px;
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

        /* ── Camera Section ── */
        .camera-section {
            margin-bottom: 20px;
        }
        .camera-label {
            font-size: 11px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 10px; display: block;
        }
        .camera-box {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            background: #0f0e17;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e8e4dc;
        }
        #videoFeed {
            width: 100%; height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* mirror */
            display: block;
        }
        #capturedCanvas {
            width: 100%; height: 100%;
            object-fit: cover;
            display: none;
            position: absolute; top: 0; left: 0;
        }
        .camera-overlay {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; gap: 10px;
            color: rgba(255,252,245,0.5);
            font-size: 13px;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .camera-overlay svg { width: 40px; height: 40px; opacity: 0.4; }
        .camera-overlay.hidden { opacity: 0; }

        /* Corner guides */
        .guide {
            position: absolute; width: 22px; height: 22px;
            border-color: var(--gold); border-style: solid; opacity: 0.6;
        }
        .guide.tl { top: 10px; left: 10px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
        .guide.tr { top: 10px; right: 10px; border-width: 2px 2px 0 0; border-radius: 0 4px 0 0; }
        .guide.bl { bottom: 10px; left: 10px; border-width: 0 0 2px 2px; border-radius: 0 0 0 4px; }
        .guide.br { bottom: 10px; right: 10px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }

        /* Camera Controls */
        .cam-controls {
            display: flex; gap: 10px; margin-top: 10px;
        }
        .btn-cam {
            flex: 1; padding: 11px;
            border: 1.5px solid var(--ink); border-radius: 8px;
            background: transparent; color: var(--ink);
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            font-weight: 500; cursor: pointer;
            transition: background 0.2s, color 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-cam:hover { background: var(--ink); color: var(--cream); }
        .btn-cam.primary { background: var(--gold); border-color: var(--gold); color: var(--ink); }
        .btn-cam.primary:hover { background: var(--gold2); border-color: var(--gold2); }
        .btn-cam:disabled { opacity: 0.4; cursor: not-allowed; }

        .cam-status {
            font-size: 12px; color: var(--muted);
            margin-top: 6px; text-align: center;
            min-height: 18px;
        }
        .cam-status.ok { color: var(--ok); }
        .cam-status.err { color: var(--error); }

        /* Fallback upload */
        .or-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 14px 0 10px;
            font-size: 11px; color: var(--muted); letter-spacing: 1px;
            text-transform: uppercase;
        }
        .or-divider::before, .or-divider::after {
            content: ''; flex: 1; height: 1px; background: #e8e4dc;
        }
        .upload-label {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border: 1.5px dashed #d0ccc4;
            border-radius: 8px; cursor: pointer;
            font-size: 13px; color: var(--muted);
            transition: border 0.2s, color 0.2s;
        }
        .upload-label:hover { border-color: var(--gold); color: var(--ink); }
        .upload-label input { display: none; }

        /* Messages */
        .error-msg {
            background: #fff0f0; border: 1px solid var(--error);
            color: var(--error); padding: 11px 14px;
            border-radius: 7px; font-size: 13px; margin-bottom: 18px;
        }
        .success-msg {
            background: #f0faf4; border: 1px solid var(--ok);
            color: var(--ok); padding: 11px 14px;
            border-radius: 7px; font-size: 13px; margin-bottom: 18px;
        }
        .success-msg a { color: var(--ok); font-weight: 600; }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 14px;
            background: var(--ink); color: var(--cream);
            border: none; border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 500; cursor: pointer;
            position: relative; overflow: hidden;
            transition: transform 0.15s; margin-top: 6px;
        }
        .btn-submit::after {
            content: ''; position: absolute; inset: 0;
            background: var(--gold); transform: scaleX(0);
            transform-origin: left; transition: transform 0.3s ease; z-index: 0;
        }
        .btn-submit:hover::after { transform: scaleX(1); }
        .btn-submit span { position: relative; z-index: 1; }

        /* Login link */
        .login-prompt {
            margin-top: 24px; padding-top: 22px;
            border-top: 1px solid #f0ece4;
            font-size: 14px; color: var(--muted); text-align: center;
        }
        .login-prompt a { color: var(--gold2); font-weight: 600; text-decoration: none; }
        .login-prompt a:hover { text-decoration: underline; }

        @media (max-width: 860px) {
            body { flex-direction: column; overflow: auto; }
            .left-panel { padding: 36px 28px; }
            .right-panel { width: 100%; padding: 36px 28px; }
        }
    </style>
</head>
<body>

<!-- Left panel -->
<div class="left-panel">
    <div class="brand">ExamPortal</div>
    <h1 class="left-headline">Create your<br><span>student</span><br>profile.</h1>
    <div class="decorative-line"></div>
    <ul class="left-sub">
        <li>Take your photo using your webcam</li>
        <li>Your photo appears in the admin panel</li>
        <li>Secure & private registration</li>
        <li>Start your exam once approved</li>
    </ul>
</div>

<!-- Right panel -->
<div class="right-panel">
    <p class="form-eyebrow">New student</p>
    <h2 class="form-title">Register</h2>

    <?php if ($error):   ?><div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-msg">✅ <?= $success ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="regForm">

        <!-- Hidden field to carry captured image base64 -->
        <input type="hidden" name="captured_image" id="capturedImageData">

        <div class="field">
            <label>Username</label>
            <input type="text" name="username" placeholder="Choose a username" required autocomplete="off">
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" placeholder="Choose a password" required>
        </div>

        <!-- Camera -->
        <div class="camera-section">
            <span class="camera-label">📸 Profile Photo — Take a live photo</span>

            <div class="camera-box">
                <video id="videoFeed" autoplay playsinline></video>
                <canvas id="capturedCanvas"></canvas>
                <div class="camera-overlay" id="camOverlay">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                    Click "Start Camera" below
                </div>
                <!-- Corner guides -->
                <div class="guide tl"></div>
                <div class="guide tr"></div>
                <div class="guide bl"></div>
                <div class="guide br"></div>
            </div>

            <div class="cam-controls">
                <button type="button" class="btn-cam" id="btnStart">▶ Start Camera</button>
                <button type="button" class="btn-cam primary" id="btnCapture" disabled>📷 Capture</button>
                <button type="button" class="btn-cam" id="btnRetake" style="display:none">↺ Retake</button>
            </div>
            <p class="cam-status" id="camStatus">Camera not started.</p>
        </div>

        <!-- Fallback file upload -->
        <div class="or-divider">or upload a photo</div>
        <label class="upload-label">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <span id="uploadFileName">Choose file from device (JPG, PNG, max 2MB)</span>
            <input type="file" name="profile_image" accept="image/*" id="fileInput">
        </label>

        <button type="submit" class="btn-submit"><span>Create Account →</span></button>
    </form>

    <div class="login-prompt">
        Already have an account? <a href="index.php">Sign in →</a>
    </div>
</div>

<script>
    const video       = document.getElementById('videoFeed');
    const canvas      = document.getElementById('capturedCanvas');
    const overlay     = document.getElementById('camOverlay');
    const btnStart    = document.getElementById('btnStart');
    const btnCapture  = document.getElementById('btnCapture');
    const btnRetake   = document.getElementById('btnRetake');
    const camStatus   = document.getElementById('camStatus');
    const hiddenInput = document.getElementById('capturedImageData');
    const fileInput   = document.getElementById('fileInput');
    const uploadName  = document.getElementById('uploadFileName');

    let stream = null;

    // ── Start camera ──
    btnStart.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            video.srcObject = stream;
            overlay.classList.add('hidden');
            btnStart.disabled    = true;
            btnCapture.disabled  = false;
            camStatus.textContent = 'Camera ready. Position yourself and capture.';
            camStatus.className  = 'cam-status ok';
        } catch (err) {
            camStatus.textContent = '⚠ Camera access denied. Please allow camera or upload a photo below.';
            camStatus.className  = 'cam-status err';
        }
    });

    // ── Capture photo ──
    btnCapture.addEventListener('click', () => {
        const ctx = canvas.getContext('2d');
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        // Mirror the capture to match what user sees
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0);

        const dataURL = canvas.toDataURL('image/png');
        hiddenInput.value = dataURL;

        // Show captured image
        canvas.style.display = 'block';
        video.style.display  = 'none';

        // Stop camera
        if (stream) stream.getTracks().forEach(t => t.stop());

        btnCapture.style.display = 'none';
        btnRetake.style.display  = 'inline-flex';
        btnStart.disabled        = true;
        camStatus.textContent    = '✅ Photo captured! You can retake or continue.';
        camStatus.className      = 'cam-status ok';

        // Clear file input since camera takes priority
        fileInput.value = '';
    });

    // ── Retake ──
    btnRetake.addEventListener('click', () => {
        canvas.style.display = 'none';
        video.style.display  = 'block';
        overlay.classList.remove('hidden');
        btnRetake.style.display  = 'none';
        btnCapture.style.display = 'inline-flex';
        btnStart.disabled        = false;
        btnCapture.disabled      = true;
        hiddenInput.value        = '';
        camStatus.textContent    = 'Camera stopped. Click "Start Camera" again.';
        camStatus.className      = 'cam-status';
    });

    // ── File upload label update ──
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            uploadName.textContent = fileInput.files[0].name;
            // Clear captured image if user picks file
            hiddenInput.value    = '';
            canvas.style.display = 'none';
            video.style.display  = 'block';
        }
    });
</script>
</body>
</html>