<?php
// Integration test for forum reports.

require_once __DIR__ . '/../core/forum/report.php';

session_start();

$dbFile = __DIR__ . '/forum_reports.db';
@unlink($dbFile);
$dsn = 'sqlite:' . $dbFile;
putenv('DB_DSN=' . $dsn);

$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$conn->exec('CREATE TABLE reports (id INTEGER PRIMARY KEY AUTOINCREMENT, reported_id INTEGER, type TEXT, reason TEXT, reporter_id INTEGER, status TEXT)');

global $conn;

echo "Submit report...\n";
submitReport('post', 1, 2, 'Spam');
$reports = getOpenReports();
if (count($reports) === 1 && $reports[0]['reason'] === 'Spam') {
    echo "Report created\n";
} else {
    echo "Report creation failed\n";
    unlink($dbFile);
    exit(1);
}

echo "Resolve report...\n";
resolveReport($reports[0]['id'], 'delete');
if (count(getOpenReports()) === 0) {
    echo "Report resolved\n";
} else {
    echo "Report resolve failed\n";
    unlink($dbFile);
    exit(1);
}

unlink($dbFile);
?>
