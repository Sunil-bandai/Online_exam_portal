<?php

include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$message = "";

// Add question
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q_text  = mysqli_real_escape_string($conn, $_POST['question']);
    $opt_a   = mysqli_real_escape_string($conn, $_POST['option_a']);
    $opt_b   = mysqli_real_escape_string($conn, $_POST['option_b']);
    $opt_c   = mysqli_real_escape_string($conn, $_POST['option_c']);
    $opt_d   = mysqli_real_escape_string($conn, $_POST['option_d']);
    $correct = strtoupper($_POST['correct']);

    $q = "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option)
          VALUES ('$q_text','$opt_a','$opt_b','$opt_c','$opt_d','$correct')";
    $message = mysqli_query($conn, $q)
        ? "✅ Question added successfully!"
        : "❌ Error: " . mysqli_error($conn);
}

// Delete question
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM questions WHERE id=$del_id");
    header("Location: admin.php"); exit();
}

// Fetch data
$questions = mysqli_query($conn, "SELECT * FROM questions ORDER BY id DESC");
$results   = mysqli_query($conn,
    "SELECT u.username, u.profile_image, r.score, r.total, r.attempted_on
     FROM results r
     JOIN users u ON r.user_id = u.id
     ORDER BY r.attempted_on DESC"
);
$studentCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='student'"))['c'];
$qCount       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM questions"))['c'];
$resultCount  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM results"))['c'];

function studentImg($f) {
    if (!$f) return "uploads/profile_images/a.jpg.jpg";
    $p = "uploads/profile_images/$f";
    return file_exists($p) ? $p : "uploads/profile_images/default.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — ExamPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink:    #0f0e17;
            --cream:  #fffcf5;
            --gold:   #e8a838;
            --gold2:  #c47f17;
            --muted:  #6b6672;
            --bg:     #f5f2eb;
            --card:   #ffffff;
            --ok:     #2a9d5c;
            --fail:   #d64045;
            --border: #e8e4dc;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: 240px; height: 100vh;
            background: var(--ink);
            display: flex; flex-direction: column;
            padding: 32px 0;
            z-index: 100;
        }
        .sidebar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 13px; letter-spacing: 4px;
            text-transform: uppercase; color: var(--gold);
            padding: 0 28px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-admin {
            display: flex; align-items: center; gap: 12px;
            padding: 24px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .admin-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            object-fit: cover; border: 2px solid var(--gold);
            flex-shrink: 0;
        }
        .admin-name { font-size: 13px; color: rgba(255,252,245,0.8); font-weight: 500; }
        .admin-role { font-size: 11px; color: var(--gold); letter-spacing: 1px; text-transform: uppercase; }

        .nav-section { padding: 20px 0; flex: 1; }
        .nav-label {
            font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
            color: rgba(255,252,245,0.25); padding: 0 28px 10px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 28px; font-size: 14px;
            color: rgba(255,252,245,0.55); cursor: pointer;
            text-decoration: none;
            transition: color 0.2s, background 0.2s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            color: var(--cream); background: rgba(255,255,255,0.04);
            border-left-color: var(--gold);
        }
        .logout-btn {
            margin: 0 20px 20px;
            padding: 11px 16px; border: 1.5px solid rgba(214,64,69,0.4);
            border-radius: 8px; background: transparent;
            color: #d64045; font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 500; cursor: pointer;
            text-decoration: none; display: block; text-align: center;
            transition: background 0.2s, color 0.2s;
        }
        .logout-btn:hover { background: #d64045; color: #fff; border-color: #d64045; }

        /* ── Main ── */
        .main {
            margin-left: 240px;
            padding: 36px 36px 60px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px; color: var(--ink);
        }
        .page-header p { font-size: 14px; color: var(--muted); margin-top: 4px; }

        /* ── Stats ── */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 30px; }
        .stat-card {
            background: var(--card); border-radius: 12px;
            padding: 22px 24px; border: 1px solid var(--border);
            display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: var(--ink); display: flex;
            align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .stat-value { font-family: 'Playfair Display', serif; font-size: 30px; color: var(--ink); line-height: 1; }
        .stat-label { font-size: 12px; color: var(--muted); margin-top: 3px; letter-spacing: 0.5px; }

        /* ── Cards ── */
        .card {
            background: var(--card); border-radius: 12px;
            border: 1px solid var(--border); margin-bottom: 24px;
            overflow: hidden;
        }
        .card-header {
            padding: 20px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header h3 { font-size: 17px; color: var(--ink); font-weight: 600; }
        .card-body { padding: 24px; }

        /* ── Form ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-grid .full { grid-column: 1 / -1; }
        .field label {
            display: block; font-size: 11px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 6px;
        }
        .field input, .field select {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid var(--border); border-radius: 8px;
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            color: var(--ink); background: #fdfaf4; outline: none;
            transition: border 0.2s;
        }
        .field input:focus, .field select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(232,168,56,0.1);
        }
        .btn-add {
            padding: 11px 28px; background: var(--ink); color: var(--cream);
            border: none; border-radius: 8px;
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            font-weight: 500; cursor: pointer;
            transition: background 0.2s;
        }
        .btn-add:hover { background: #2c2b3a; }

        /* ── Message ── */
        .msg {
            padding: 11px 16px; border-radius: 8px;
            font-size: 13px; margin-bottom: 18px;
            background: #f0faf4; border: 1px solid var(--ok); color: var(--ok);
        }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted); font-weight: 500;
            padding: 12px 16px; border-bottom: 1px solid var(--border);
            text-align: left;
        }
        tbody td {
            padding: 14px 16px; font-size: 14px;
            border-bottom: 1px solid #f5f2eb; color: #333;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fdfaf4; }

        /* Student photo cell */
        .student-cell { display: flex; align-items: center; gap: 12px; }
        .student-photo {
            width: 44px; height: 44px; border-radius: 50%;
            object-fit: cover; border: 2px solid var(--gold);
            flex-shrink: 0;
        }
        .student-username { font-weight: 500; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 500; gap: 5px;
        }
        .badge-pass { background: #edfaf4; color: var(--ok); }
        .badge-fail { background: #fff0f0; color: var(--fail); }

        .btn-del {
            font-size: 12px; color: var(--fail);
            text-decoration: none; font-weight: 500;
            padding: 5px 12px; border: 1px solid rgba(214,64,69,0.3);
            border-radius: 6px; transition: background 0.2s, color 0.2s;
        }
        .btn-del:hover { background: var(--fail); color: white; }

        .correct-badge {
            display: inline-block; padding: 4px 12px;
            background: var(--ink); color: var(--gold);
            border-radius: 6px; font-size: 12px; font-weight: 700;
        }

        @media (max-width: 960px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 24px; }
            .stats { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">ExamPortal</div>
    <div class="sidebar-admin">
        <?php $adminImg = studentImg($_SESSION['profile_image'] ?? null); ?>
        <img class="admin-avatar" src="<?= htmlspecialchars($adminImg) ?>" alt="Admin">
        <div>
            <div class="admin-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <div class="admin-role">Administrator</div>
        </div>
    </div>
    <nav class="nav-section">
        <div class="nav-label">Management</div>
        <a href="#questions" class="nav-item active">📋 Questions</a>
        <a href="#results" class="nav-item">📊 Student Results</a>
    </nav>
    <a href="logout.php" class="logout-btn">⬅ Logout</a>
</aside>

<!-- Main content -->
<main class="main">

    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Manage exam questions and view student results.</p>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div>
                <div class="stat-value"><?= $studentCount ?></div>
                <div class="stat-label">Students Registered</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div>
                <div class="stat-value"><?= $qCount ?></div>
                <div class="stat-label">Questions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div>
                <div class="stat-value"><?= $resultCount ?></div>
                <div class="stat-label">Exams Attempted</div>
            </div>
        </div>
    </div>

    <!-- Add Question -->
    <div class="card" id="questions">
        <div class="card-header">
            <h3>➕ Add New Question</h3>
        </div>
        <div class="card-body">
            <?php if ($message): ?><div class="msg"><?= $message ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-grid">
                    <div class="field full">
                        <label>Question</label>
                        <input type="text" name="question" placeholder="Enter the question text" required>
                    </div>
                    <div class="field">
                        <label>Option A</label>
                        <input type="text" name="option_a" placeholder="Option A" required>
                    </div>
                    <div class="field">
                        <label>Option B</label>
                        <input type="text" name="option_b" placeholder="Option B" required>
                    </div>
                    <div class="field">
                        <label>Option C</label>
                        <input type="text" name="option_c" placeholder="Option C" required>
                    </div>
                    <div class="field">
                        <label>Option D</label>
                        <input type="text" name="option_d" placeholder="Option D" required>
                    </div>
                    <div class="field">
                        <label>Correct Answer</label>
                        <select name="correct" required>
                            <option value="">-- Select --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
                <br>
                <button type="submit" class="btn-add">Add Question</button>
            </form>
        </div>
    </div>

    <!-- All Questions -->
    <div class="card">
        <div class="card-header">
            <h3>📋 All Questions</h3>
            <span style="font-size:13px;color:var(--muted)"><?= $qCount ?> total</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Question</th>
                    <th>Correct</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($questions)): ?>
                <tr>
                    <td style="color:var(--muted);font-size:13px"><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['question_text']) ?></td>
                    <td><span class="correct-badge"><?= htmlspecialchars($row['correct_option']) ?></span></td>
                    <td>
                        <a href="admin.php?delete=<?= $row['id'] ?>" class="btn-del"
                           onclick="return confirm('Delete this question?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Student Results -->
    <div class="card" id="results">
        <div class="card-header">
            <h3>📊 Student Results</h3>
            <span style="font-size:13px;color:var(--muted)"><?= $resultCount ?> attempts</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Score</th>
                    <th>Total</th>
                    <th>Result</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($results)):
                    $imgSrc  = studentImg($row['profile_image']);
                    $pct     = $row['total'] > 0 ? round(($row['score'] / $row['total']) * 100) : 0;
                    $pass    = $pct >= 50;
                ?>
                <tr>
                    <td>
                        <div class="student-cell">
                            <img class="student-photo"
                                 src="<?= htmlspecialchars($imgSrc) ?>"
                                 alt="<?= htmlspecialchars($row['username']) ?>">
                            <span class="student-username"><?= htmlspecialchars($row['username']) ?></span>
                        </div>
                    </td>
                    <td><strong><?= $row['score'] ?></strong></td>
                    <td><?= $row['total'] ?></td>
                    <td>
                        <span class="badge <?= $pass ? 'badge-pass' : 'badge-fail' ?>">
                            <?= $pass ? '✅ Pass' : '❌ Fail' ?> · <?= $pct ?>%
                        </span>
                    </td>
                    <td style="color:var(--muted);font-size:13px"><?= $row['attempted_on'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>
</body>
</html>