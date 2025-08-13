<?php
require_once __DIR__ . '/../questions.php';

function admin_add_signup_question($question, $answer) {
    $questions = get_signup_questions();
    $id = empty($questions) ? 1 : max(array_keys($questions)) + 1;
    $questions[$id] = array('q' => $question, 'a' => $answer);
    return save_signup_questions($questions);
}

function admin_delete_signup_question($id) {
    $questions = get_signup_questions();
    if (!isset($questions[$id])) {
        return false;
    }
    unset($questions[$id]);
    return save_signup_questions($questions);
}
?>
