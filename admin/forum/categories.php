<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
admin_only();

require("../../core/config.php");

// Handle add, update, delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add category
    if (isset($_POST['add'])) {
        $name = trim($_POST['name'] ?? '');
        $position = isset($_POST['position']) ? (int)$_POST['position'] : 0;
        if ($name === '') {
            header('Location: categories.php?msg=' . urlencode('Name is required'));
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO forum_categories (name, position) VALUES (:name, :position)');
        $stmt->execute([':name' => $name, ':position' => $position]);
        header('Location: categories.php?msg=' . urlencode('Category added'));
        exit;
    }

    // Update category
    if (isset($_POST['update'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $position = isset($_POST['position']) ? (int)$_POST['position'] : 0;
        if ($name === '' || $id <= 0) {
            header('Location: categories.php?msg=' . urlencode('Invalid data'));
            exit;
        }
        $stmt = $conn->prepare('UPDATE forum_categories SET name = :name, position = :position WHERE id = :id');
        $stmt->execute([':name' => $name, ':position' => $position, ':id' => $id]);
        header('Location: categories.php?msg=' . urlencode('Category updated'));
        exit;
    }

    // Delete category
    if (isset($_POST['delete'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM forum_categories WHERE id = :id');
            $stmt->execute([':id' => $id]);
            header('Location: categories.php?msg=' . urlencode('Category deleted'));
            exit;
        }
    }
}

// Fetch categories for display
$stmt = $conn->query('SELECT * FROM forum_categories ORDER BY position ASC');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch category to edit if requested
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM forum_categories WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<?php require("../header.php"); ?>
<div class="simple-container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <h1>Forum Categories</h1>
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
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= $cat['position'] ?></td>
                <td>
                    <a href="categories.php?edit=<?= $cat['id'] ?>">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this category?');">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($editCategory): ?>
    <h2>Edit Category</h2>
    <form method="post" class="ctrl-enter-submit">
        <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($editCategory['name']) ?>" required></label>
        <label>Position: <input type="number" name="position" value="<?= $editCategory['position'] ?>" required></label>
        <button type="submit" name="update">Update</button>
    </form>
    <?php endif; ?>

    <h2>Add Category</h2>
    <form method="post" class="ctrl-enter-submit">
        <label>Name: <input type="text" name="name" required></label>
        <label>Position: <input type="number" name="position" value="<?= count($categories) + 1 ?>" required></label>
        <button type="submit" name="add">Add</button>
    </form>
</div>
<?php require("../../public/footer.php"); ?>
