// Inventory filter logic for sidebar.php and inventory.php

window.initInventoryFilter = function() {
    // Category filter
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('category-item')) {
            // Remove active from all
            document.querySelectorAll('.category-item').forEach(function(item) {
                item.classList.remove('active');
            });
            // Set active on clicked
            e.target.classList.add('active');
            var catId = e.target.getAttribute('data-category-id');
            filterInventoryTable(catId, getSearchValue());
        }
    });

    // Search filter
    var searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var activeCat = document.querySelector('.category-item.active');
            var catId = activeCat ? activeCat.getAttribute('data-category-id') : 'all';
            filterInventoryTable(catId, getSearchValue());
        });
    }

    function getSearchValue() {
        var searchInput = document.querySelector('.search-bar input');
        return searchInput ? searchInput.value.trim().toLowerCase() : '';
    }

    function filterInventoryTable(categoryId, searchValue) {
        document.querySelectorAll('.inventory-table tbody tr').forEach(function(row) {
            var rowCat = row.getAttribute('data-category-id');
            var text = row.textContent.toLowerCase();
            var catMatch = (categoryId === 'all' || rowCat === categoryId);
            var searchMatch = (searchValue === '' || text.indexOf(searchValue) !== -1);
            if (catMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
};

// Auto-run if loaded on a full page (not via AJAX)
if (document.querySelector('.inventory-table')) {
    window.initInventoryFilter();
} 