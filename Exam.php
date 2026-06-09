<?php

include 'db.php';

// Only logged-in students can access this
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

// Fetch all questions from database
$result    = mysqli_query($conn, "SELECT * FROM questions ORDER BY id ASC");
$questions = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total     = count($questions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Exam</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; }
        .container { max-width: 750px; margin: auto; }
        .header { background: #2c3e50; color: white; padding: 20px 25px; border-radius: 10px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; }
        #timer { font-size: 20px; background: #e74c3c; padding: 8px 15px; border-radius: 5px; }
        .question-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .question-card h4 { color: #2c3e50; margin-top: 0; }
        .options label { display: block; padding: 10px 15px; margin: 6px 0; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; transition: background 0.2s; }
        .options label:hover { background: #eaf4fb; }
        .options input[type="radio"] { margin-right: 10px; }
        .submit-btn { width: 100%; padding: 15px; background: #3498db; color: white; font-size: 18px; border: none; border-radius: 8px; cursor: pointer; margin-top: 10px; }
        .submit-btn:hover { background: #2980b9; }
        .logout { color: #aaa; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📝 Online Examination</h2>
        <div>
            <span id="timer">⏱ 10:00</span>
            <br>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>

    <p>Welcome, <strong><?= $_SESSION['username'] ?></strong> | Total Questions: <strong><?= $total ?></strong></p>

    <!-- Exam Form -->
    <form method="POST" action="result.php" id="examForm">
        <?php foreach ($questions as $index => $q): ?>
        <div class="question-card">
            <h4>Q<?= $index + 1 ?>: <?= htmlspecialchars($q['question_text']) ?></h4>
            <div class="options">
                <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="A" required> A) <?= htmlspecialchars($q['option_a']) ?></label>
                <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="B"> B) <?= htmlspecialchars($q['option_b']) ?></label>
                <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="C"> C) <?= htmlspecialchars($q['option_c']) ?></label>
                <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="D"> D) <?= htmlspecialchars($q['option_d']) ?></label>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="submit-btn">✅ Submit Exam</button>
    </form>
</div>

<!-- Countdown Timer: 10 minutes -->
<script>
    let timeLeft = 1 * 60;  // 10 minutes in seconds
    const timerDisplay = document.getElementById('timer');

    const countdown = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerDisplay.textContent = `⏱ ${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;

        if (timeLeft <= 0) {
            clearInterval(countdown);
            alert("⏰ Time is up! Submitting your exam.");
            document.getElementById('examForm').submit();
        }
        timeLeft--;
    }, 1000);
</script>
</body>
</html>