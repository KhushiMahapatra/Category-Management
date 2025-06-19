<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Category Manager</title>
</head>
<body>
<div id="main_container">
    <h1>Category Manager</h1>

    <form id="categoryForm">
        <input type="hidden" id="categoryId">
        <input type="text" id="categoryName" placeholder="Category Name" required>
        <select id="parentId">
            <option value="">Select Parent Category</option>
        </select>
        <button type="submit">Save</button>
    </form>
    
    <div>
        <label for="sortOptions">Sort By:</label>
        <select id="sortOptions">
            <option value="name">Category Name</option>
            <option value="parent">Sub-Category</option>
            <option value="status">Status</option>
        </select>
    </div>
    <input type="text" id="search_input" placeholder="Search categories..." onkeyup="filterCategories()">
    
    <table id="categoriesTable" border="1" style="width: 100%; margin-top: 20px; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Category</th>
                <th>Parent Category</th>
                <th>Action</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
    let categories = []; // To store all categories globally

    // Load categories and initialize the table
    async function loadCategories() {
        try {
            const response = await fetch('categories.json?t=' + new Date().getTime()); // Avoid caching
            if (!response.ok) throw new Error('Failed to load categories');
            categories = await response.json();

            populateParentDropdown(categories);
            displayCategoriesTable(categories);
        } catch (error) {
            console.error('Error loading categories:', error);
            alert('Could not load categories. Check console for details.');
        }
    }

    function populateParentDropdown(categories) {
        const parentDropdown = document.getElementById('parentId');
        parentDropdown.innerHTML = '<option value="">Select Parent Category</option>';
        categories.forEach(category => {
            if (!category.parentId) { // Only top-level categories
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                parentDropdown.appendChild(option);
            }
        });
    }

    function displayCategoriesTable(categories) {
        const categoriesTableBody = document.querySelector('#categoriesTable tbody');
        const searchInput = document.getElementById('search_input').value.toLowerCase();

        categoriesTableBody.innerHTML = ''; // Clear the table body

        const filteredCategories = categories.filter(category =>
            category.name.toLowerCase().includes(searchInput)
        );

        // Get the selected sorting option
        const sortOption = document.getElementById('sortOptions').value;
        filteredCategories.sort((a, b) => {
            if (sortOption === 'name') {
                return a.name.localeCompare(b.name); // Sort by category name
            } else if (sortOption === 'parent') {
                const parentA = a.parentId ? categories.find(c => c.id === a.parentId)?.name || '' : '';
                const parentB = b.parentId ? categories.find(c => c.id === b.parentId)?.name || '' : '';
                return parentA.localeCompare(parentB); // Sort by parent category name
            } else if (sortOption === 'status') {
                return a.status.localeCompare(b.status); // Sort by status
            }
            return 0;
        });

        // Render the sorted and filtered categories
        filteredCategories.forEach(category => {
            const row = document.createElement('tr');
            const parentName = category.parentId
                ? categories.find(parent => parent.id === category.parentId)?.name || 'N/A'
                : 'N/A';

            row.innerHTML = `
                <td>${category.name}</td>
                <td>${parentName}</td>
                <td>
                    <button onclick="editCategory('${category.id}', '${category.name}', '${category.parentId}', '${category.status}')">Edit</button>
                    <button onclick="deleteCategory('${category.id}')">Delete</button>
                    <button onclick="activateCategory('${category.id}')">Activate</button>
                    <button onclick="deactivateCategory('${category.id}')">Deactivate</button>
                </td>
                <td>${category.status}</td>
            `;

            categoriesTableBody.appendChild(row);
        });
    }

    function filterCategories() {
        displayCategoriesTable(categories); // Re-render the table with the current search query
    }

    async function saveCategory(e) {
    e.preventDefault();

    const categoryId = document.getElementById('categoryId').value.trim();
    const categoryName = document.getElementById('categoryName').value.trim();
    const parentId = document.getElementById('parentId').value || null;

    if (!categoryName) {
        alert("Category name is required.");
        return;
    }

    try {
        const response = await fetch('category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: categoryId ? 'edit' : 'add',
                id: categoryId,
                name: categoryName,
                parentId: parentId
            })
        });

        const result = await response.json();

        if (result.error) {
            alert(result.error);
        } else {
            alert(categoryId ? 'Category updated successfully!' : 'Category added successfully!');
            loadCategories(); // Reload categories to update the table
        }

        // Reset the form fields
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryName').value = '';
        document.getElementById('parentId').value = '';
    } catch (error) {
        console.error('Error saving category:', error);
        alert('Could not save the category. Check console for details.');
    }
}


    async function deleteCategory(id) {
        if (!confirm('Are you sure you want to delete this category?')) return;

        try {
            const response = await fetch('category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id })
            });

            const result = await response.json();

            if (result.error) {
                alert(result.error);
            } else {
                alert('Category deleted successfully');
                categories = categories.filter(category => category.id !== id);
                displayCategoriesTable(categories);
            }
        } catch (error) {
            console.error('Error deleting category:', error);
            alert('Could not delete category. Check console for details.');
        }
    }

    async function activateCategory(id) {
        try {
            const response = await fetch('category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'activate', id })
            });

            const result = await response.json();

            if (result.error) {
                alert(result.error);
            } else {
                alert(result.message);
                loadCategories();
            }
        } catch (error) {
            console.error('Error activating category:', error);
        }
    }

    async function deactivateCategory(id) {
        try {
            const response = await fetch('category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'deactivate', id })
            });

            const result = await response.json();

            if (result.error) {
                alert(result.error);
            } else {
                alert(result.message);
                loadCategories();
            }
        } catch (error) {
            console.error('Error deactivating category:', error);
        }
    }
 

    function editCategory(id, name, parentId, status) {
        document.getElementById('categoryId').value = id;
        document.getElementById('categoryName').value = name;
        document.getElementById('parentId').value = parentId || '';
    }

    document.getElementById('categoryForm').addEventListener('submit', saveCategory);
    document.getElementById('sortOptions').addEventListener('change', filterCategories);
    document.getElementById('search_input').addEventListener('keyup', filterCategories);

    loadCategories();
    
</script>
</body>
</html>
