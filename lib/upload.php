<?php
function upload_file(array $file, string $destDir, array $allowed = []) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0775, true);
    }
    $mime = mime_content_type($file['tmp_name']);
    if ($allowed && !in_array($mime, $allowed, true)) {
        return false;
    }
    $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
    $name = uniqid('att_', true) . ($ext ? '.' . $ext : '');
    $path = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;
    $moved = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $path) : rename($file['tmp_name'], $path);
    if (!$moved) {
        return false;
    }
    return ['name' => $name, 'mime' => $mime];
}
?>
