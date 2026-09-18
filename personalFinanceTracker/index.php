<?php
require_once "db.php";
require_once "pref.php";

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

function clean($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
$category = "";
$email = $_SESSION['email'];

// --- 1. HANDLE FORM SUBMISSIONS ---

if (isset($_POST['addIncome'])) {
    $date = $_POST['date'];
    $desc = clean($_POST['description']);
    $category = clean($_POST['category']);
    $amount = clean($_POST['amount']);

    $insert = "INSERT INTO transactions (email, type, date, description, category, amount) 
               VALUES ('$email', 'income', '$date', '$desc', '$category', '$amount')";
    mysqli_query($conn, $insert);
    header("Location: index.php?success=income_added");
    exit();
}

if (isset($_POST['addExpenses'])) {
    $date = $_POST['date'];
    $desc = clean($_POST['description']);
    $category = clean($_POST['category']);
    $amount = clean($_POST['amount']);

    $insert = "INSERT INTO transactions (email, type, date, description, category, amount) 
               VALUES ('$email', 'expense', '$date', '$desc', '$category', '$amount')";
    mysqli_query($conn, $insert);
    header("Location: index.php?success=expense_added");
    exit();
}

// --- HANDLE DELETE ---
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);

    $sql = "DELETE FROM transactions WHERE id='$id'";
    mysqli_query($conn, $sql);

    header("Location: index.php");
    exit();
}

// --- HANDLE EDIT ---
if (isset($_POST['edit_transaction'])) {
    $id = clean($_POST['id']);
    $date = clean($_POST['date']);
    $type = clean($_POST['type']); // Now captures if they swapped it!
    $category = clean($_POST['category']);
    $description = clean($_POST['description']);
    $amount = clean($_POST['amount']);

    $updateQuery = "UPDATE transactions 
                    SET date = '$date', 
                        type = '$type', 
                        category = '$category', 
                        description = '$description', 
                        amount = '$amount' 
                    WHERE id = '$id' AND email = '$email'";

    mysqli_query($conn, $updateQuery);

    // Redirect to prevent form resubmission
    header("Location: index.php?success=transaction_updated");
    exit();
}

// --- 2. CALCULATE DASHBOARD TOTALS ---

$income = 0;
$expenses = 0;
$balance = 0;
$transactions = [];
$dateFilter = isset($_POST['dateFilter']) ? $_POST['dateFilter'] : 'month';

// Fetch ALL transactions for this specific user
$query = "SELECT * FROM transactions WHERE email = '$email'";
$today = date('Y-m-d');

if ($dateFilter == "today") {
    $query .= " AND date = '$today'";
} elseif ($dateFilter == "week") {
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    $query .= " AND date BETWEEN '$startOfWeek' AND '$endOfWeek'";
} elseif ($dateFilter == 'month') {
    $startOfMonth = date('Y-m-01');
    $endOfMonth = date('Y-m-t');
    $query .= " AND date BETWEEN '$startOfMonth' AND '$endOfMonth'";
}

$result = mysqli_query($conn, $query);

// Calculate totals
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['type'] == 'income') {
        $income += $row['amount'];
    } else {
        $expenses += $row['amount'];
    }
}

$tableQuery = $query . " ORDER BY date DESC LIMIT 10";
$tableResult = mysqli_query($conn, $tableQuery);

$transactions = [];

while ($row = mysqli_fetch_assoc($tableResult)) {
    $transactions[] = $row;
}


// Calculate total balance
$balance = $income - $expenses;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trackery</title>
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

        /* Top Bar & Filters */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .filter-group select {
            padding: 0.5rem 1rem;
            border: 2px solid var(--light-olive);
            border-radius: 6px;
            background-color: var(--white);
            color: var(--dark-slate);
            font-size: 1rem;
            outline: none;
            cursor: pointer;
        }

        .action-buttons button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            color: var(--white);
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-left: 0.5rem;
        }

        .btn-income {
            background-color: var(--medium-green);
        }

        .btn-expense {
            background-color: var(--danger);
        }

        .action-buttons button:hover {
            opacity: 0.9;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-top: 4px solid var(--deep-green);
        }

        .card h3 {
            font-size: 0.9rem;
            color: var(--medium-green);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .card h2 {
            font-size: 2rem;
            color: var(--dark-slate);
        }

        /* Transactions Table */
        .transactions-section {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .transactions-section h3 {
            margin-bottom: 1.5rem;
            color: var(--deep-green);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light-olive);
        }

        th {
            background-color: rgba(156, 176, 128, 0.15);
            color: var(--deep-green);
            font-weight: 600;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background-color: var(--light-olive);
            color: var(--dark-slate);
        }

        .amount.income {
            color: var(--medium-green);
            font-weight: bold;
        }

        .amount.expense {
            color: var(--danger);
            font-weight: bold;
        }

        .table-actions button {
            background: none;
            border: none;
            cursor: pointer;
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-edit {
            color: var(--medium-green);
        }

        .btn-delete {
            color: var(--danger);
        }

        .table-actions button:hover {
            text-decoration: underline;
        }

        /* --- Modal Component Custom Styles --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(39, 51, 56, 0.6);
            /* Translucent dark slate overlay */
            z-index: 2000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-box {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 12px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-box {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--bg-color);
            padding-bottom: 0.75rem;
        }

        .modal-header h3 {
            color: var(--deep-green);
            font-size: 1.25rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        /* Form Controls Inside Modals */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark-slate);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-gray);
            border-radius: 6px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--medium-green);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.75rem;
        }

        .modal-footer button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-cancel {
            background-color: var(--light-gray);
            color: var(--dark-slate);
        }

        .btn-submit {
            background-color: var(--medium-green);
            color: var(--white);
        }

        .btn-submit.btn-danger-action {
            background-color: var(--danger);
        }

        .modal-footer button:hover {
            opacity: 0.9;
        }

        /* Alert Styling */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .alert-danger {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #facdca;
        }

        .alert-success {
            background-color: rgba(97, 135, 100, 0.15);
            color: var(--deep-green);
            border: 1px solid var(--light-olive);
        }

        .sticky-nav-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            background-color: var(--deep-green, #2b5748);
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

        }

        .sticky-nav-btn:hover {
            background-color: var(--dark-slate, #273338);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .sticky-nav-btn:active {
            transform: translateY(0);
        }


        .arrow-left {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-left: 3px solid #ffffff;
            border-bottom: 3px solid #ffffff;
            transform: rotate(134deg);
            margin-left: 2px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>Finance Tracker</h2>
        <ul class="nav-links">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="transactions.php">Transactions</a></li>
            <li><a href="categories.php">Categories</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="go-back">

        <!-- Header & Controls -->
        <div class="header-actions">
            <div class="filter-group">
                <form method="POST" id="filterForm">
                    <select id="dateFilter" name="dateFilter" onchange="this.form.submit()">
                        <option value="today" <?php echo (isset($_POST['dateFilter']) && $_POST['dateFilter'] == "today") ? "selected" : "" ?>>Today</option>
                        <option value="week" <?php echo (isset($_POST['dateFilter']) && $_POST['dateFilter'] == "week") ? "selected" : "" ?>>This Week</option>
                        <option value="month" <?php echo (!isset($_POST['dateFilter']) || $_POST['dateFilter'] == "month") ? "selected" : "" ?>>This Month</option>
                        <option value="all" <?php echo (isset($_POST['dateFilter']) && $_POST['dateFilter'] == "all") ? "selected" : "" ?>>All Time</option>
                    </select>
                </form>
            </div>

            <div class="action-buttons">
                <button class="btn-income" onclick="openModal('modalAddIncome')">+ Add Income</button>
                <button class="btn-expense" onclick="openModal('modalAddExpense')">- Add Expense</button>
            </div>
        </div>

        <!-- Dashboard Summary -->
        <div class="summary-cards">
            <div class="card">
                <h3>Current Balance</h3>
                <h2><?php echo $sym; ?><?php echo number_format($balance, 2) ?></h2>
            </div>
            <div class="card" style="border-top-color: var(--medium-green);">
                <h3>Total Income</h3>
                <h2 style="color: #2B5748;"><?php echo $sym; ?><?php echo number_format($income, 2) ?></h2>
            </div>
            <div class="card" style="border-top-color: var(--dark-slate);">
                <h3>Total Expenses</h3>
                <h2 style="color: #e74c3c;"><?php echo $sym; ?><?php echo number_format($expenses, 2) ?></h2>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="transactions-section">
            <h3>Recent Transactions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Loop through the array we built earlier
                    foreach ($transactions as $t) {
                    ?>
                        <tr id="row-<?php echo $t['id']; ?>">
                            <!-- FIX: Added data-raw-date so JS grabs YYYY-MM-DD format for the Date input -->
                            <td class="t-date" data-raw-date="<?php echo $t['date']; ?>">
                                <?php echo date($dateFormat, strtotime($t['date'])); ?>
                            </td>
                            <td class="t-desc">
                                <?php echo $t['description']; ?>
                            </td>
                            <td>
                                <span class="badge t-cat"><?php echo $t['category']; ?></span>
                            </td>
                            <td class="amount <?php echo $t['type']; ?> t-amt" data-raw-amt="<?php echo $t['amount']; ?>">
                                <?php echo $t['type'] == 'income' ? '+' : '-'; ?><?php echo $sym; ?><?php echo number_format($t['amount'], 2); ?>
                            </td>
                            <td class="table-actions">
                                <button class="btn-edit" onclick="prepEditModal(<?php echo $t['id']; ?>, '<?php echo $t['type']; ?>')">Edit</button>
                                <button class="btn-delete" onclick="prepDeleteModal(<?php echo $t['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- ================= MODALS CONTAINERS ================= -->

    <!-- Add Income Modal -->
    <div id="modalAddIncome" class="modal-overlay" onclick="closeModalOnOverlay(event, 'modalAddIncome')">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Add Income Record</h3>
                <button class="modal-close" onclick="closeModal('modalAddIncome')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="type" value="income">
                <div class="form-group">
                    <label for="incomeDate">Date</label>
                    <input type="date" id="incomeDate" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="incomeDesc">Description</label>
                    <input type="text" id="incomeDesc" name="description" class="form-control" placeholder="e.g., Monthly Salary" required>
                </div>
                <div class="form-group">
                    <label for="incomeCat">Category</label>
                    <select id="incomeCat" name="category" class="form-control" required>
                        <option value="" disabled selected>Select an income category</option>
                        <?php
                        $incomeQuery = "SELECT name FROM categories WHERE email = '$email' AND type = 'income' ORDER BY name ASC";
                        $incomeResult = mysqli_query($conn, $incomeQuery);

                        if (mysqli_num_rows($incomeResult) > 0) {
                            while ($row = mysqli_fetch_assoc($incomeResult)) {
                                $catName = htmlspecialchars($row['name']);
                                echo "<option value='$catName'>$catName</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No income categories found. Please create one.</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="incomeAmt">Amount (<?php echo $sym; ?>)</label>
                    <input type="number" step="0.01" id="incomeAmt" name="amount" class="form-control" placeholder="0.00" min="0.01" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalAddIncome')">Cancel</button>
                    <button type="submit" class="btn-submit" name="addIncome">Save Income</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="modalAddExpense" class="modal-overlay" onclick="closeModalOnOverlay(event, 'modalAddExpense')">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Add Expense Record</h3>
                <button class="modal-close" onclick="closeModal('modalAddExpense')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="type" value="expense">
                <div class="form-group">
                    <label for="expenseDate">Date</label>
                    <input type="date" id="expenseDate" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="expenseDesc">Description</label>
                    <input type="text" id="expenseDesc" name="description" class="form-control" placeholder="e.g., Grocery Shopping" required>
                </div>
                <div class="form-group">
                    <label for="expenseCat">Category</label>
                    <select id="expenseCat" name="category" class="form-control" required>
                        <option value="" disabled selected>Select an expense category</option>
                        <?php
                        // Fetch ONLY categories where type = 'expense'
                        $expenseQuery = "SELECT name FROM categories WHERE email = '$email' AND type = 'expense' ORDER BY name ASC";
                        $expenseResult = mysqli_query($conn, $expenseQuery);

                        if (mysqli_num_rows($expenseResult) > 0) {
                            while ($row = mysqli_fetch_assoc($expenseResult)) {
                                $catName = htmlspecialchars($row['name']);
                                echo "<option value='$catName'>$catName</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No expense categories found. Please create one.</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="expenseAmt">Amount (<?php echo $sym; ?>)</label>
                    <input type="number" step="0.01" id="expenseAmt" name="amount" class="form-control" placeholder="0.00" min="0.01" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalAddExpense')">Cancel</button>
                    <button type="submit" class="btn-submit" style="background-color: var(--dark-slate);" name="addExpenses">Save Expense</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="modalEditTransaction" class="modal-overlay" onclick="closeModalOnOverlay(event, 'modalEditTransaction')">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Edit Transaction</h3>
                <button class="modal-close" onclick="closeModal('modalEditTransaction')">&times;</button>
            </div>
            <form method="POST">
                <!-- Inputs are now empty. JS will fill them! -->
                <input type="hidden" name="id" id="editId">

                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" id="editDate" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Type:</label>
                    <select name="type" id="edit_type" class="form-control" required>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Category:</label>
                    <select name="category" id="edit_category" class="form-control" required>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>

                <div class="form-group">
                    <label>Description:</label>
                    <input type="text" name="description" id="editDesc" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Amount:</label>
                    <input type="number" step="0.01" name="amount" id="editAmt" class="form-control" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalEditTransaction')">Cancel</button>
                    <button type="submit" class="btn-submit" name="edit_transaction">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="modalDeleteConfirm" class="modal-overlay" onclick="closeModalOnOverlay(event, 'modalDeleteConfirm')">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button class="modal-close" onclick="closeModal('modalDeleteConfirm')">&times;</button>
            </div>
            <p style="margin-bottom: 1rem; line-height: 1.5;">Are you sure you want to permanently delete this transaction? This process cannot be undone.</p>
            <form method="POST">
                <input type="hidden" id="deleteId" name="id">
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalDeleteConfirm')">No</button>
                    <button type="submit" class="btn-submit btn-danger-action" name="delete">Yes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Control Logic -->
    <script>
        // Open Modal Function
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        // Close Modal Function
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close when clicking outside content area
        function closeModalOnOverlay(event, modalId) {
            if (event.target.id === modalId) {
                closeModal(modalId);
            }
        }

        // Gather existing table row values and drop them seamlessly into Edit Modal fields
        function prepEditModal(rowId, type) {
            const row = document.getElementById('row-' + rowId);

            // Fetch the raw values that Javascript needs
            const currentRawDate = row.querySelector('.t-date').getAttribute('data-raw-date');
            const currentDesc = row.querySelector('.t-desc').innerText.trim();
            const currentCat = row.querySelector('.t-cat').innerText.trim();
            const currentRawAmt = row.querySelector('.t-amt').getAttribute('data-raw-amt');

            // Set Form Values
            document.getElementById('editId').value = rowId;
            document.getElementById('editDate').value = currentRawDate; // Now works perfectly!
            document.getElementById('editDesc').value = currentDesc;
            document.getElementById('editAmt').value = currentRawAmt;

            // Set the correct Type (Income/Expense)
            const typeSelect = document.getElementById('edit_type');
            typeSelect.value = type;

            // Automatically generate the right category list based on the type
            updateCategories();

            // Finally, select the specific category they had saved previously
            document.getElementById('edit_category').value = currentCat;

            openModal('modalEditTransaction');
        }

        // Set up the context variables for deletion request form validation target
        function prepDeleteModal(rowId) {
            document.getElementById('deleteId').value = rowId;
            openModal('modalDeleteConfirm');
        }

        // 1. Define your categories
        const incomeCategories = ['Salary', 'Allowance', 'Business', 'Bonus', 'Other Income'];
        const expenseCategories = ['Food', 'Transport', 'Utilities', 'Shopping', 'Other Expenses'];

        const typeSelect = document.getElementById('edit_type');
        const categorySelect = document.getElementById('edit_category');

        // 2. Create a function to update the dropdown
        function updateCategories() {
            const selectedType = typeSelect.value;

            // Clear out the old options
            categorySelect.innerHTML = '';

            // Decide which list to use
            const optionsToLoad = (selectedType === 'income') ? incomeCategories : expenseCategories;

            // Add the new options
            optionsToLoad.forEach(function(cat) {
                const optionElement = document.createElement('option');
                optionElement.value = cat;
                optionElement.textContent = cat;
                categorySelect.appendChild(optionElement);
            });
        }

        // 3. Listen for changes when the user manually clicks the Type dropdown
        typeSelect.addEventListener('change', updateCategories);
    </script>
    <a href="#go-back">
        <button class="sticky-nav-btn" title="Go Back">
            <span class="arrow-left"></span>
        </button>
    </a>
</body>

</html>