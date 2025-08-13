<?php
// Test the anti-spam signup questions system
require_once __DIR__ . '/../core/site/questions.php';

$q = get_random_signup_question();
if (!$q) {
    echo "No questions configured\n";
    exit(1);
}

$id = $q['id'];
$questions = get_signup_questions();
$correct = $questions[$id]['a'];

echo "Checking incorrect answer...\n";
if (check_signup_answer($id, 'wrong')) {
    echo "Incorrect answer accepted\n";
    exit(1);
}

echo "Checking correct answer...\n";
if (!check_signup_answer($id, $correct)) {
    echo "Correct answer rejected\n";
    exit(1);
}

echo "OK\n";
