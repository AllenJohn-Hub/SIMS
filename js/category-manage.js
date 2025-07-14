// (No modal logic needed, leave empty or restore previous logic if any) 

document.addEventListener('DOMContentLoaded', function() {
    const categoryList = document.getElementById('categoryList');
    const addForm = document.getElementById('addCategoryForm');
    const modal = document.getElementById('categoryModal');
    let editingId = null;
    if (!categoryList || !addForm || !modal) return;

    // Load categories when modal is shown
    modal.addEventListener('show.bs.modal', function() {
        loadCategories();
    });

    function loadCategories() {
        fetch('inventory.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'get_categories'})
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                renderCategoryList(data.categories);
            }
        });
    }

    // Add or Edit Category
    addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const input = addForm.querySelector('input[name="category_name"]');
        const name = input.value.trim();
        if (!name) return;
        const action = editingId ? 'edit_category' : 'add_category';
        const params = {action, category_name: name};
        if (editingId) params.category_id = editingId;
        fetch('inventory.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(params)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                input.value = '';
                editingId = null;
                addForm.querySelector('button[type="submit"]').textContent = 'Add';
                renderCategoryList(data.categories);
            } else {
                alert(data.message || 'Failed to save category.');
            }
        })
        .catch(() => alert('Failed to save category (AJAX error).'));
    });

    // Edit/Delete Category
    categoryList.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-category-btn')) {
            const li = e.target.closest('li');
            const catId = li.getAttribute('data-category-id');
            const catName = li.querySelector('.category-name').textContent;
            addForm.querySelector('input[name="category_name"]').value = catName;
            editingId = catId;
            addForm.querySelector('button[type="submit"]').textContent = 'Save';
        }
        if (e.target.classList.contains('delete-category-btn')) {
            const li = e.target.closest('li');
            const catId = li.getAttribute('data-category-id');
            if (!confirm('Delete this category?')) return;
            fetch('inventory.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

    // Render category list in modal
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