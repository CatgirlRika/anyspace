<?php
require("../../core/conn.php");
require_once("../../core/settings.php");

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

require("../../core/config.php");

// Recursive deletion of forum and its subforums
function deleteForum(PDO $conn, int $id): void {
    // delete children first
    $childStmt = $conn->prepare('SELECT id FROM forums WHERE parent_forum_id = :id');
    $childStmt->execute([':id' => $id]);
    $children = $childStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($children as $childId) {
        deleteForum($conn, (int)$childId);
    }
    // delete this forum
    $delStmt = $conn->prepare('DELETE FROM forums WHERE id = :id');
    $delStmt->execute([':id' => $id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add forum
    if (isset($_POST['add'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $parent_forum_id = isset($_POST['parent_forum_id']) && $_POST['parent_forum_id'] !== '' ? (int)$_POST['parent_forum_id'] : null;
        $position = isset($_POST['position']) ? (int)$_POST['position'] : 0;

        if ($name === '' || $category_id <= 0) {
            header('Location: forums.php?msg=' . urlencode('Name and category are required'));
            exit;
        }

        $stmt = $conn->prepare('INSERT INTO forums (category_id, parent_forum_id, name, description, position) VALUES (:category_id, :parent_forum_id, :name, :description, :position)');
        $stmt->execute([
            ':category_id' => $category_id,
            ':parent_forum_id' => $parent_forum_id,
            ':name' => $name,
            ':description' => $description,
            ':position' => $position
        ]);

        header('Location: forums.php?msg=' . urlencode('Forum added'));
        exit;
    }

    // Update forum
    if (isset($_POST['update'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $parent_forum_id = isset($_POST['parent_forum_id']) && $_POST['parent_forum_id'] !== '' ? (int)$_POST['parent_forum_id'] : null;
        $position = isset($_POST['position']) ? (int)$_POST['position'] : 0;

        if ($name === '' || $category_id <= 0 || $id <= 0) {
            header('Location: forums.php?msg=' . urlencode('Invalid data'));
            exit;
        }

        if ($parent_forum_id === $id) {
            $parent_forum_id = null; // prevent forum being its own parent
        }

        $stmt = $conn->prepare('UPDATE forums SET category_id=:category_id, parent_forum_id=:parent_forum_id, name=:name, description=:description, position=:position WHERE id=:id');
        $stmt->execute([
            ':category_id' => $category_id,
            ':parent_forum_id' => $parent_forum_id,
            ':name' => $name,
            ':description' => $description,
            ':position' => $position,
            ':id' => $id
        ]);

        header('Location: forums.php?msg=' . urlencode('Forum updated'));
        exit;
    }

    // Delete forum
    if (isset($_POST['delete'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            deleteForum($conn, $id);
            header('Location: forums.php?msg=' . urlencode('Forum deleted'));
            exit;
        }
    }
}

// Fetch categories
$catStmt = $conn->query('SELECT * FROM forum_categories ORDER BY position ASC');
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all forums
$forumStmt = $conn->query('SELECT * FROM forums ORDER BY position ASC');
$allForums = $forumStmt->fetchAll(PDO::FETCH_ASSOC);

// Build structures for display and select options
$forumsByCategory = [];
$forumsForSelect = [];
foreach ($allForums as $f) {
    $forumsByCategory[$f['category_id']][$f['parent_forum_id'] ?? 0][] = $f;
    $forumsForSelect[] = $f;
}

// Fetch forum to edit
$editForum = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM forums WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editForum = $stmt->fetch(PDO::FETCH_ASSOC);
}

function renderForumRows(array $forumsByParent, $parentId = 0, $level = 0) {
    if (!isset($forumsByParent[$parentId])) {
        return;
    }
    foreach ($forumsByParent[$parentId] as $forum) {
        $indent = str_repeat('&mdash; ', $level);
        echo '<tr>';
        echo '<td>' . $forum['id'] . '</td>';
        echo '<td>' . $indent . htmlspecialchars($forum['name']) . '</td>';
        echo '<td>' . $forum['position'] . '</td>';
        echo '<td>';
        echo '<a href="forums.php?edit=' . $forum['id'] . '">Edit</a> ';
        echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Delete this forum and its subforums?\');">';
        echo '<input type="hidden" name="id" value="' . $forum['id'] . '">';
        echo '<button type="submit" name="delete">Delete</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
        renderForumRows($forumsByParent, $forum['id'], $level + 1);
    }
}
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <h1>Forums</h1>
    <?php foreach ($categories as $cat): ?>
        <h2><?= htmlspecialchars($cat['name']) ?></h2>
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $forumsByParent = $forumsByCategory[$cat['id']] ?? [];
                renderForumRows($forumsByParent);
                ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <?php if ($editForum): ?>
    <h2>Edit Forum</h2>
    <form method="post" class="ctrl-enter-submit">
        <input type="hidden" name="id" value="<?= $editForum['id'] ?>">
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($editForum['name']) ?>" required></label>
        <label>Description:<br><textarea name="description" required><?= htmlspecialchars($editForum['description']) ?></textarea></label>
        <label>Category:
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $editForum['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Parent Forum:
            <select name="parent_forum_id">
                <option value="">None</option>
                <?php foreach ($forumsForSelect as $f): ?>
                    <?php if ($f['id'] == $editForum['id']) continue; ?>
                    <option value="<?= $f['id'] ?>" <?= $f['id'] == $editForum['parent_forum_id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Position: <input type="number" name="position" value="<?= $editForum['position'] ?>" required></label>
        <button type="submit" name="update">Update</button>
    </form>
    <?php endif; ?>

    <h2>Add Forum</h2>
    <form method="post" class="ctrl-enter-submit">
        <label>Name: <input type="text" name="name" required></label>
        <label>Description:<br><textarea name="description" required></textarea></label>
        <label>Category:
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Parent Forum:
            <select name="parent_forum_id">
                <option value="">None</option>
                <?php foreach ($forumsForSelect as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Position: <input type="number" name="position" value="1" required></label>
        <button type="submit" name="add">Add</button>
    </form>
</div>
<?php require("../../public/footer.php"); ?>
