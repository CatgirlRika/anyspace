<?php
/**
 * Test Groups System
 * Tests the groups functionality including creation, joining, and events
 */

// Use in-memory SQLite for testing
$dsn = 'sqlite::memory:';
$conn = new PDO($dsn);

// Create required tables
$sql = "
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    pfp VARCHAR(255) DEFAULT 'default.jpg',
    currentgroup VARCHAR(255) DEFAULT 'None',
    date DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    author VARCHAR(255) NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE group_memberships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    username VARCHAR(255) NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    role VARCHAR(50) DEFAULT 'member',
    UNIQUE(group_id, username)
);

CREATE TABLE group_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE groupcomments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    toid INTEGER NOT NULL,
    author VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP
);
";

$conn->exec($sql);

// Insert test users
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->execute(['testuser1', 'test1@example.com', 'hashedpass1']);
$stmt->execute(['testuser2', 'test2@example.com', 'hashedpass2']);

// Include the helper functions
include __DIR__ . '/../core/site/user.php';

// Test 1: Create a group
echo "Creating a group...\n";
$stmt = $conn->prepare("INSERT INTO groups (name, description, author) VALUES (?, ?, ?)");
$stmt->execute(['Test Group', 'A test group for testing', 'testuser1']);
$group_id = $conn->lastInsertId();
echo "Group created with ID: $group_id\n";

// Test 2: Join a group  
echo "Joining group...\n";
$stmt = $conn->prepare("INSERT INTO group_memberships (group_id, username) VALUES (?, ?)");
$stmt->execute([$group_id, 'testuser2']);
echo "User testuser2 joined group\n";

// Test 3: Check membership
echo "Checking membership...\n";
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM group_memberships WHERE group_id = ? AND username = ?");
$stmt->execute([$group_id, 'testuser2']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result['count'] > 0) {
    echo "✓ Membership verified\n";
} else {
    echo "✗ Membership check failed\n";
}

// Test 4: Create an event
echo "Creating event...\n";
$event_date = date('Y-m-d H:i:s', strtotime('+1 week'));
$stmt = $conn->prepare("INSERT INTO group_events (group_id, title, description, event_date, created_by) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$group_id, 'Test Event', 'A test event', $event_date, 'testuser1']);
echo "Event created\n";

// Test 5: Get group with member count
echo "Getting group with member count...\n";
$stmt = $conn->prepare("
    SELECT g.*, COUNT(gm.username) as member_count 
    FROM groups g 
    LEFT JOIN group_memberships gm ON g.id = gm.group_id 
    WHERE g.id = ? 
    GROUP BY g.id
");
$stmt->execute([$group_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result && $result['member_count'] == 1) {
    echo "✓ Group member count correct: " . $result['member_count'] . "\n";
} else {
    echo "✗ Group member count incorrect\n";
}

// Test 6: Test helper functions
echo "Testing helper functions...\n";
$user_id = getID('testuser1', $conn);
if ($user_id == 1) {
    echo "✓ getID function works\n";
} else {
    echo "✗ getID function failed\n";
}

$pfp = getPFP('testuser1', $conn);
if ($pfp == 'default.jpg') {
    echo "✓ getPFP function works\n";
} else {
    echo "✗ getPFP function failed\n";
}

// Test 7: Get user groups
echo "Getting user groups...\n";
$stmt = $conn->prepare("
    SELECT g.id, g.name 
    FROM groups g 
    JOIN group_memberships gm ON g.id = gm.group_id 
    WHERE gm.username = ?
");
$stmt->execute(['testuser2']);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($groups) == 1 && $groups[0]['name'] == 'Test Group') {
    echo "✓ User groups retrieval works\n";
} else {
    echo "✗ User groups retrieval failed\n";
}

echo "\nAll groups system tests completed!\n";
?>