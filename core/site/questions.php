<?php
function get_signup_questions() {
    $file = __DIR__ . '/../signup_questions.php';
    if (file_exists($file)) {
        return include($file);
    }
    return array();
}

function save_signup_questions($questions) {
    $file = __DIR__ . '/../signup_questions.php';
    $content = "<?php\nreturn " . var_export($questions, true) . ";\n";
    return file_put_contents($file, $content) !== false;
}

function get_random_signup_question() {
    $questions = get_signup_questions();
    if (empty($questions)) {
        return null;
    }
    $id = array_rand($questions);
    return array('id' => $id, 'q' => $questions[$id]['q']);
}

function check_signup_answer($id, $answer) {
    $questions = get_signup_questions();
    if (!isset($questions[$id])) {
        return false;
    }
    return trim(strtolower($questions[$id]['a'])) === trim(strtolower($answer));
}
?>
