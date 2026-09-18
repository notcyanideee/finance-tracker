<?php
require_once "db.php";
require_once "pref.php";

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

function clean($conn, $data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

$email = $_SESSION['email'];

// --- 1. HANDLE ADDING A CATEGORY ---
if (isset($_POST['add_category'])) {
    $catName = ucwords(clean($conn, $_POST['categoryName']));
    $catType = clean($conn, $_POST['categoryType']);

    $insertQuery = "INSERT INTO categories (email, name, type) VALUES ('$email', '$catName', '$catType')";
    mysqli_query($conn, $insertQuery);

    header("Location: categories.php");
    exit();
}

// --- 2. HANDLE EDITING A CATEGORY ---
if (isset($_POST['edit_category'])) {
    $catId = clean($conn, $_POST['category_id']);
    $catName = ucwords(clean($conn, $_POST['categoryName']));
    $catType = clean($conn, $_POST['editCategoryType']);

    $updateQuery = "UPDATE categories SET name = '$catName', type = '$catType' WHERE id = '$catId' AND email = '$email'";
    mysqli_query($conn, $updateQuery);

    header("Location: categories.php");
    exit();
}

// --- 3. HANDLE DELETING A CATEGORY ---
if (isset($_POST['delete_category'])) {
    $deleteId = clean($conn, $_POST['category_id']);
    $deleteQuery = "DELETE FROM categories WHERE id = '$deleteId' AND email = '$email'";
    mysqli_query($conn, $deleteQuery);

    header("Location: categories.php");
    exit();
}

// --- 4. FETCH CATEGORIES ---
$catQuery = "SELECT * FROM categories WHERE email = '$email' ORDER BY name ASC";
$categoriesResult = mysqli_query($conn, $catQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Trackery</title>
    <style>
        :root {
            --dark-slate: #273338;
            --deep-green: #2B5748;
            --medium-green: #618764;
            --light-olive: #9CB080;
            --bg-color: #f4f7f6;
            --white: #ffffff;
            --danger: #e74c3c;
            --gray: #7f8c8d;
            --light-gray: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--dark-slate);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--dark-slate);
            color: var(--white);
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            margin-bottom: 2rem;
            text-align: center;
            color: var(--light-olive);
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 1rem;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1rem;
            display: block;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background-color: var(--deep-green);
            color: var(--light-olive);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: var(--deep-green);
            font-size: 1.8rem;
        }

        /* Layout Workspace */
        .workspace {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            align-items: start;
        }

        /* Form Card */
        .category-form-card {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-top: 4px solid var(--deep-green);
        }

        .category-form-card h3 {
            margin-bottom: 1.2rem;
            color: var(--deep-green);
        }

        .input-group {
            margin-bottom: 1.2rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid var(--light-olive);
            border-radius: 6px;
            font-size: 0.95rem;
            color: var(--dark-slate);
            outline: none;
            background-color: var(--white);
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: var(--deep-green);
        }

        .btn-save {
            width: 100%;
            padding: 0.8rem;
            background-color: var(--deep-green);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-save:hover {
            background-color: var(--dark-slate);
        }

        /* Categories Display Container */
        .categories-grid-container {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .categories-grid-container h3 {
            margin-bottom: 1.5rem;
            color: var(--deep-green);
        }

        /* Grid Architecture */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }

        .category-item {
            border: 1px solid rgba(156, 176, 128, 0.4);
            background-color: var(--bg-color);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .category-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .category-info h4 {
            font-size: 1.1rem;
            color: var(--dark-slate);
            margin-bottom: 0.25rem;
        }

        .type-badge {
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
        }

        .type-badge.expense {
            background-color: var(--danger);
            color: var(--white);
        }

        .type-badge.income {
            background-color: var(--medium-green);
            color: var(--white);
        }

        .category-actions {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .category-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: right;
            transition: color 0.2s;
        }

        .btn-edit-link {
            color: var(--medium-green);
        }

        .btn-edit-link:hover {
            color: var(--deep-green);
            text-decoration: underline;
        }

        .btn-delete-link {
            color: var(--danger);
        }

        .btn-delete-link:hover {
            text-decoration: underline;
        }

        /* --- Modals Base Setup --- */
        .modal-overlay {
            display: none;
            /* Controlled via JS */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content h3 {
            color: var(--deep-green);
            margin-bottom: 1.5rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-cancel {
            padding: 0.6rem 1.2rem;
            background-color: var(--light-gray);
            color: var(--dark-slate);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-danger-confirm {
            padding: 0.6rem 1.2rem;
            background-color: var(--danger);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .sticky-nav-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            background-color: var(--deep-green, #2b5748);
            /* Fallback to a deep green if your variable isn't globally active here */
            color: #ffffff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
            z-index: 999;
            /* Keeps it layered above your cards and content tables */
        }

        .sticky-nav-btn:hover {
            background-color: var(--dark-slate, #273338);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .sticky-nav-btn:active {
            transform: translateY(0);
        }

        /* Creates a sharp chevron arrow pointing left using CSS borders */
        .arrow-left {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-left: 3px solid #ffffff;
            border-bottom: 3px solid #ffffff;
            transform: rotate(134deg);
            margin-left: 2px;
            /* Slight centering visual optical adjustment */
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @media (max-width: 900px) {
            .workspace {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>Finance Tracker</h2>
        <ul class="nav-links">
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="transactions.php">Transactions</a></li>
            <li><a href="categories.php" class="active">Categories</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="go-back">
        <div class="page-header">
            <h1>Manage Categories</h1>
        </div>

        <div class="workspace">
            <!-- Left Side: Add Form -->
            <div class="category-form-card">
                <h3>Create Category</h3>
                <form method="POST">
                    <div class="input-group">
                        <label for="categoryName">Category Name</label>
                        <input type="text" id="categoryName" name="categoryName" placeholder="e.g., Subscriptions" required>
                    </div>

                    <div class="input-group">
                        <label for="categoryType">Transaction Type</label>
                        <select id="categoryType" name="categoryType" required>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>

                    <button type="submit" name="add_category" class="btn-save">Save Category</button>
                </form>
            </div>

            <!-- Right Side: Existing Categories Grid -->
            <div class="categories-grid-container">
                <h3>Your Categories</h3>
                <div class="categories-grid">

                    <?php
                    if (mysqli_num_rows($categoriesResult) > 0) {
                        while ($row = mysqli_fetch_assoc($categoriesResult)) {
                            $displayType = ucfirst($row['type']);
                    ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                                    <span class="type-badge <?php echo $row['type']; ?>"><?php echo $displayType; ?></span>
                                </div>
                                <div class="category-actions">
                                    <!-- Edit Trigger passes database row details directly to JavaScript functions -->
                                    <button type="button" class="btn-edit-link" onclick="openEditModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>', '<?php echo $row['type']; ?>')">Edit</button>
                                    <button type="button" class="btn-delete-link" onclick="openDeleteModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">Delete</button>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<p>No categories found. Create one to get started!</p>";
                    }
                    ?>

                </div>
            </div>
        </div>
    </main>

    <!-- --- EDIT MODAL OVERLAY --- -->
    <div id="editModal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'editModal')">
        <div class="modal-content">
            <h3>Update Category</h3>
            <form method="POST">
                <input type="hidden" id="edit_category_id" name="category_id">

                <div class="input-group">
                    <label for="editCategoryName">Category Name</label>
                    <input type="text" id="editCategoryName" name="editCategoryName" required>
                </div>

                <div class="input-group">
                    <label for="editCategoryType">Transaction Type</label>
                    <select id="editCategoryType" name="editCategoryType" required>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="edit_category" class="btn-save" style="width: auto; padding: 0.6rem 1.2rem;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- --- DELETE MODAL OVERLAY --- -->
    <div id="deleteModal" class="modal-overlay" onclick="closeModalOnOuterClick(event, 'deleteModal')">
        <div class="modal-content">
            <h3>Delete Category</h3>
            <p>Are you sure you want to delete "<span id="delete_target_name" style="font-weight: bold;"></span>"? This action cannot be undone.</p>

            <form method="POST">
                <input type="hidden" id="delete_category_id" name="category_id">

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" name="delete_category" class="btn-danger-confirm">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Control Panel -->
    <script>
        function openEditModal(id, name, type) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('editCategoryName').value = name;
            document.getElementById('editCategoryType').value = type;
            document.getElementById('editModal').style.display = 'flex';
        }

        function openDeleteModal(id, name) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_target_name').innerText = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Closes modal if clicking the dimmed outer boundary background
        function closeModalOnOuterClick(event, modalId) {
            if (event.target.id === modalId) {
                closeModal(modalId);
            }
        }
    </script>
    <a href="#go-back">
        <button class="sticky-nav-btn" title="Go Back">
            <span class="arrow-left"></span>
        </button>
    </a>
</body>

</html>