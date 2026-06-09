<?php

include 'db.php';

// Only logged-in students can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

// Make sure form was submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['answer'])) {
    header("Location: exam.php");
    exit();
}

$student_answers = $_POST['answer'];  // Array: [question_id => chosen_option]
$score           = 0;
$details         = [];  // To show question-wise breakdown

// Fetch all questions
$result    = mysqli_query($conn, "SELECT * FROM questions ORDER BY id ASC");
$questions = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total     = count($questions);

// Compare each answer with correct answer
foreach ($questions as $q) {
    $qid            = $q['id'];
    $correct        = $q['correct_option'];
    $student_answer = isset($student_answers[$qid]) ? $student_answers[$qid] : 'Not Answered';
    $is_correct     = ($student_answer == $correct);

    if ($is_correct) $score++;

    // Save details for display
    $details[] = [
        'question' => $q['question_text'],
        'options'  => [
            'A' => $q['option_a'],
            'B' => $q['option_b'],
            'C' => $q['option_c'],
            'D' => $q['option_d'],
        ],
        'correct'   => $correct,
        'student'   => $student_answer,
        'is_right'  => $is_correct,
    ];
}
$results = mysqli_query($conn,
    "SELECT u.username, u.profile_image, r.score, r.total, r.attempted_on
     FROM results r
     JOIN users u ON r.user_id = u.id
     ORDER BY r.attempted_on DESC"
);
// Calculate percentage
$percentage = round(($score / $total) * 100);
$grade      = $percentage >= 80 ? 'A' : ($percentage >= 60 ? 'B' : ($percentage >= 40 ? 'C' : 'F'));
$passed     = $percentage >= 40;

// Save result to database
$user_id = $_SESSION['user_id'];
mysqli_query($conn, "INSERT INTO results (user_id, score, total) VALUES ('$user_id', '$score', '$total')");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Result</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; }
        .container { max-width: 750px; margin: auto; }
        .result-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; margin-bottom: 25px; }
        .score-circle { width: 130px; height: 130px; border-radius: 50%; line-height: 130px; font-size: 32px; font-weight: bold; color: white; margin: 20px auto; }
        .pass  { background: #27ae60; }
        .fail  { background: #e74c3c; }
        .detail-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 15px; }
        .correct-answer { color: #27ae60; font-weight: bold; }
        .wrong-answer   { color: #e74c3c; font-weight: bold; }
        .option { display: block; padding: 6px 10px; margin: 4px 0; border-radius: 4px; font-size: 14px; }
        .opt-correct { background: #eafaf1; border-left: 4px solid #27ae60; }
        .opt-wrong   { background: #fdf2f0; border-left: 4px solid #e74c3c; }
        .opt-normal  { background: #f9f9f9; }
        a.btn { display: inline-block; padding: 12px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 6px; margin-top: 15px; font-size: 16px; }
        a.btn:hover { background: #2980b9; }
        h3 { color: #2c3e50; }
    </style>
</head>
<body>
<div class="container">

    <!-- Score Summary -->
    <div class="result-card">
        <h2>📊 Exam Result</h2>
        <p>Student: <strong><?= $_SESSION['username'] ?></strong></p>

        <div class="score-circle <?= $passed ? 'pass' : 'fail' ?>">
            <?= $percentage ?>%
        </div>

        <h3><?= $passed ? '🎉 Congratulations! You Passed!' : '😢 Sorry, You Failed.' ?></h3>
        <p>Score: <strong><?= $score ?> / <?= $total ?></strong></p>
        <p>Grade: <strong><?= $grade ?></strong></p>

        <a href="exam.php" class="btn">🔄 Retake Exam</a>
        &nbsp;
        <a href="logout.php" class="btn" style="background:#95a5a6;">🚪 Logout</a>
    </div>

    <!-- Question-wise Breakdown -->
    <h3>📋 Answer Review</h3>
    <?php foreach ($details as $i => $d): ?>
    <div class="detail-card">
        <p><strong>Q<?= $i + 1 ?>: <?= htmlspecialchars($d['question']) ?></strong></p>

        <?php foreach ($d['options'] as $key => $val): ?>
            <?php
                // Highlight correct and student's wrong answer
                $class = 'opt-normal';
                if ($key == $d['correct']) $class = 'opt-correct';
                elseif ($key == $d['student'] && !$d['is_right']) $class = 'opt-wrong';
            ?>
            <span class="option <?= $class ?>">
                <?= $key ?>) <?= htmlspecialchars($val) ?>
                <?php if ($key == $d['correct']) echo " ✅"; ?>
                <?php if ($key == $d['student'] && !$d['is_right']) echo " ❌ (Your Answer)"; ?>
            </span>
        <?php endforeach; ?>

        <?php if ($d['is_right']): ?>
            <p class="correct-answer">✔ Your answer is correct!</p>
        <?php else: ?>
            <p class="wrong-answer">✘ Correct answer is: <?= $d['correct'] ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

</div>
</body>
</html>