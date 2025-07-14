<?php
// Minimal Category Add/Delete Demo
// Adjust DB credentials as needed
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "sims";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Database connection error.");
}
// --- AJAX HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_category') {
        header('Content-Type: application/json');
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '') {
            echo json_encode(['status' => 'error', 'message' => 'Category name required.']);
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO category (category) VALUES (?)');
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            $cats = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
            $cat_list = [];
            while ($row = $cats->fetch_assoc()) $cat_list[] = $row;
            echo json_encode(['status' => 'success', 'categories' => $cat_list]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add category.']);
        }
        exit;
    }
    if ($_POST['action'] === 'delete_category') {
        header('Content-Type: application/json');
        $id = intval($_POST['category_id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid category ID.']);
            exit;
        }
        $stmt = $conn->prepare('DELETE FROM category WHERE category_id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $cats = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
            $cat_list = [];
            while ($row = $cats->fetch_assoc()) $cat_list[] = $row;
            echo json_encode(['status' => 'success', 'categories' => $cat_list]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete category.']);
        }
        exit;
    }
    if ($_POST['action'] === 'edit_category') {
        header('Content-Type: application/json');
        $id = intval($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        if ($id <= 0 || $name === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE category SET category=? WHERE category_id=?');
        $stmt->bind_param('si', $name, $id);
        if ($stmt->execute()) {
            $cats = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
            $cat_list = [];
            while ($row = $cats->fetch_assoc()) $cat_list[] = $row;
            echo json_encode(['status' => 'success', 'categories' => $cat_list]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to edit category.']);
        }
        exit;
    }
}
// --- PAGE RENDER ---
$categories = [];
$res = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
if ($res) while ($row = $res->fetch_assoc()) $categories[] = $row;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Demo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 500px; margin-top: 60px; }
        .edit-input { width: 60%; display: inline-block; margin-right: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h3 class="mb-4">Category Add/Edit/Delete Demo</h3>
    <form id="addCategoryForm" class="d-flex mb-3">
        <input type="text" class="form-control me-2" name="category_name" placeholder="New category name" required>
        <button type="submit" class="btn btn-success">Add</button>
    </form>
    <ul class="list-group" id="categoryList">
        <?php foreach ($categories as $cat): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center" data-category-id="<?php echo $cat['category_id']; ?>">
                <span class="category-name"><?php echo htmlspecialchars($cat['category']); ?></span>
                <div>
                    <button class="btn btn-sm btn-outline-primary edit-category-btn me-1">Edit</button>
                    <button class="btn btn-sm btn-outline-danger delete-category-btn">Delete</button>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryList = document.getElementById('categoryList');
    const addForm = document.getElementById('addCategoryForm');
    // Add Category
    addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const input = addForm.querySelector('input[name="category_name"]');
        const name = input.value.trim();
        if (!name) return;
        fetch('category-demo.php', {
            method: 'POST',
            body: new URLSearchParams({action: 'add_category', category_name: name})
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                input.value = '';
                renderCategoryList(data.categories);
            } else {
                alert(data.message || 'Failed to add category.');
            }
        })
        .catch(() => alert('Failed to add category (AJAX error).'));
    });
    // Edit Category (inline)
    categoryList.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-category-btn')) {
            const li = e.target.closest('li');
            const catId = li.getAttribute('data-category-id');
            const nameSpan = li.querySelector('.category-name');
            const oldName = nameSpan.textContent;
            // Prevent multiple edits
            if (li.querySelector('.edit-input')) return;
            // Replace span with input
            const input = document.createElement('input');
            input.type = 'text';
            input.value = oldName;
            input.className = 'form-control form-control-sm edit-input';
            nameSpan.replaceWith(input);
            input.focus();
            // Save on blur or Enter
            function saveEdit() {
                const newName = input.value.trim();
                if (!newName || newName === oldName) {
                    input.replaceWith(nameSpan);
                    return;
                }
                fetch('category-demo.php', {
                    method: 'POST',
                    body: new URLSearchParams({action: 'edit_category', category_id: catId, category_name: newName})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderCategoryList(data.categories);
                    } else {
                        alert(data.message || 'Failed to edit category.');
                        input.replaceWith(nameSpan);
                    }
                })
                .catch(() => {
                    alert('Failed to edit category (AJAX error).');
                    input.replaceWith(nameSpan);
                });
            }
            input.addEventListener('blur', saveEdit);
            input.addEventListener('keydown', function(ev) {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    input.blur();
                }
                if (ev.key === 'Escape') {
                    input.replaceWith(nameSpan);
                }
            });
        }
        // Delete Category
        if (e.target.classList.contains('delete-category-btn')) {
            const li = e.target.closest('li');
            const catId = li.getAttribute('data-category-id');
            if (!confirm('Delete this category?')) return;
            fetch('category-demo.php', {
                method: 'POST',
                body: new URLSearchParams({action: 'delete_category', category_id: catId})
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderCategoryList(data.categories);
                } else {
                    alert(data.message || 'Failed to delete category.');
                }
            })
            .catch(() => alert('Failed to delete category (AJAX error).'));
        }
    });
    // Render category list
    function renderCategoryList(categories) {
        categoryList.innerHTML = '';
        categories.forEach(cat => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.setAttribute('data-category-id', cat.category_id);
            li.innerHTML = `
                <span class="category-name">${cat.category}</span>
                <div>
                    <button class="btn btn-sm btn-outline-primary edit-category-btn me-1">Edit</button>
                    <button class="btn btn-sm btn-outline-danger delete-category-btn">Delete</button>
                </div>
            `;
            categoryList.appendChild(li);
        });
    }
});
</script>
</body>
</html> 