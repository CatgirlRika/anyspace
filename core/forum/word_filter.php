<?php
require_once(__DIR__ . '/../helper.php');

function bad_words_all(): array {
    global $conn;
    try {
        $stmt = $conn->query('SELECT word FROM bad_words');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

function add_bad_word(string $word): void {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO bad_words (word) VALUES (:w)');
    $stmt->execute([':w' => $word]);
}

function remove_bad_word(string $word): void {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM bad_words WHERE word = :w');
    $stmt->execute([':w' => $word]);
}

function isFiltered(string $text): array {
    $matches = [];
    $words = bad_words_all();
    foreach ($words as $w) {
        if ($w !== '' && stripos($text, $w) !== false) {
            $matches[] = $w;
        }
    }
    return $matches;
}
?>
