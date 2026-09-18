<?php
require_once "db.php";
require_once "pref.php";

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}





// --- 1. HANDLE TIMEFRAME FILTER ---
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'thisYear';
$today = date('Y-m-d');

if ($timeframe == 'last30') {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = $today;
} elseif ($timeframe == 'thisYear') {
    $startDate = date('Y-01-01');
    $endDate = date('Y-12-31');
} elseif ($timeframe == 'lastYear') {
    $startDate = date('Y-01-01', strtotime('-1 year'));
    $endDate = date('Y-12-31', strtotime('-1 year'));
} else {
    $startDate = '1970-01-01'; // allTime
    $endDate = $today;
}

// --- 2. CALCULATE KPI CARDS (Based on selected timeframe) ---
$totalIncome = 0;
$totalExpense = 0;

$kpiQuery = "SELECT type, SUM(amount) as total FROM transactions WHERE email = '$email' AND date BETWEEN '$startDate' AND '$endDate' GROUP BY type";
$kpiResult = mysqli_query($conn, $kpiQuery);

if ($kpiResult) {
    while ($row = mysqli_fetch_assoc($kpiResult)) {
        if ($row['type'] == 'income') {
            $totalIncome = $row['total'];
        } elseif ($row['type'] == 'expense') {
            $totalExpense = $row['total'];
        }
    }
}

$netSavings = $totalIncome - $totalExpense;
$savingsRate = ($totalIncome > 0) ? ($netSavings / $totalIncome) * 100 : 0;

// --- 3. BAR CHART LOGIC: Last 6 Months (Fixed Rolling Window) ---
// Initialize array for the last 6 months so empty months still show up
$barMonths = [];
for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months"));
    $barMonths[$monthKey] = [
        'label' => date('M', strtotime("-$i months")),
        'income' => 0,
        'expense' => 0
    ];
}

$sixMonthsAgo = date('Y-m-01', strtotime('-5 months'));
$barQuery = "SELECT DATE_FORMAT(date, '%Y-%m') as ym, type, SUM(amount) as total FROM transactions WHERE email = '$email' AND date >= '$sixMonthsAgo' GROUP BY ym, type";
$barResult = mysqli_query($conn, $barQuery);

$maxChartValue = 1; // Prevent division by zero
if ($barResult) {
    while ($row = mysqli_fetch_assoc($barResult)) {
        $ym = $row['ym'];
        if (isset($barMonths[$ym])) {
            $barMonths[$ym][$row['type']] = $row['total'];
            // Track highest value to scale the bars up to 100%
            if ($row['total'] > $maxChartValue) {
                $maxChartValue = $row['total'];
            }
        }
    }
}
// Add 10% padding to max value so bars don't hit the absolute ceiling
$maxChartValue = $maxChartValue * 1.1;

// --- 4. DOUGHNUT CHART LOGIC: Expenses by Category (Based on timeframe) ---
$catQuery = "SELECT category, SUM(amount) as total FROM transactions WHERE email = '$email' AND type = 'expense' AND date BETWEEN '$startDate' AND '$endDate' GROUP BY category ORDER BY total DESC";
$catResult = mysqli_query($conn, $catQuery);

$categories = [];
$themeColors = ['#273338', '#2B5748', '#618764', '#9CB080', '#e74c3c', '#f39c12'];
$colorIndex = 0;

if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $percent = ($totalExpense > 0) ? ($row['total'] / $totalExpense) * 100 : 0;
        $categories[] = [
            'name' => $row['category'],
            'total' => $row['total'],
            'percent' => $percent,
            'color' => $themeColors[$colorIndex % count($themeColors)]
        ];
        $colorIndex++;
    }
}

// Build the CSS Conic Gradient string for the Doughnut
$conicGradient = "";
$currentDegree = 0;
foreach ($categories as $cat) {
    $nextDegree = $currentDegree + $cat['percent'];
    $conicGradient .= "{$cat['color']} {$currentDegree}% {$nextDegree}%, ";
    $currentDegree = $nextDegree;
}
$conicGradient = rtrim($conicGradient, ", "); // Remove trailing comma
if (empty($conicGradient)) {
    $conicGradient = "#e0e0e0 0% 100%"; // Gray fallback if no expenses
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Trackery</title>
    <style>
        :root {
            --dark-slate: #273338;
            --deep-green: #2B5748;
            --medium-green: #618764;
            --light-olive: #9CB080;
            --bg-color: #f4f7f6;
            --white: #ffffff;
            --danger: #e74c3c;
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

        /* Sidebar Styles (Matching Theme) */
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: var(--deep-green);
            font-size: 1.8rem;
        }

        .date-filter select {
            padding: 0.6rem 1.2rem;
            border: 2px solid var(--light-olive);
            border-radius: 6px;
            background-color: var(--white);
            color: var(--dark-slate);
            font-size: 1rem;
            font-weight: 600;
            outline: none;
            cursor: pointer;
        }

        /* KPI Summary Cards */
        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .kpi-card h3 {
            font-size: 0.9rem;
            color: var(--dark-slate);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }

        .kpi-card h2 {
            font-size: 1.8rem;
        }

        .text-income {
            color: var(--medium-green);
        }

        .text-expense {
            color: var(--danger);
        }

        .text-net {
            color: var(--deep-green);
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .chart-container h3 {
            color: var(--deep-green);
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        /* Pure CSS Bar Chart Placeholder */
        .css-bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 250px;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--light-olive);
            position: relative;
        }

        .bar-group {
            display: flex;
            gap: 4px;
            align-items: flex-end;
            height: 100%;
            position: relative;
        }

        .bar-group::after {
            content: attr(data-month);
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--dark-slate);
        }

        .bar {
            width: 25px;
            border-radius: 4px 4px 0 0;
            transition: opacity 0.3s;
        }

        .bar:hover {
            opacity: 0.8;
        }

        .bar.inc {
            background-color: var(--medium-green);
        }

        .bar.exp {
            background-color: var(--dark-slate);
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .dot.inc {
            background-color: var(--medium-green);
        }

        .dot.exp {
            background-color: var(--dark-slate);
        }

        /* Pure CSS Doughnut Chart Placeholder */
        .css-doughnut-chart {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(var(--dark-slate) 0% 35%,
                    var(--deep-green) 35% 60%,
                    var(--medium-green) 60% 85%,
                    var(--light-olive) 85% 100%);
            margin: 0 auto 1.5rem auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* The inner circle to make it a doughnut */
        .css-doughnut-inner {
            width: 120px;
            height: 120px;
            background-color: var(--white);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--dark-slate);
        }

        .category-legend {
            list-style: none;
            font-size: 0.9rem;
        }

        .category-legend li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid rgba(156, 176, 128, 0.3);
        }

        .cat-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Responsive */
        @media (max-width: 1024px) {
            .charts-grid {
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
            <li><a href="categories.php">Categories</a></li>
            <li><a href="reports.php" class="active">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="go-back">

        <div class="page-header">
            <h1>Financial Reports</h1>
            <div class="date-filter">
                <!-- Wrap in a form to submit automatically when changed -->
                <form method="GET" action="reports.php">
                    <select name="timeframe" onchange="this.form.submit()">
                        <option value="last30" <?php echo $timeframe == 'last30' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="thisYear" <?php echo $timeframe == 'thisYear' ? 'selected' : ''; ?>>This Year</option>
                        <option value="lastYear" <?php echo $timeframe == 'lastYear' ? 'selected' : ''; ?>>Last Year</option>
                        <option value="allTime" <?php echo $timeframe == 'allTime' ? 'selected' : ''; ?>>All Time</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Top KPI Cards -->
        <div class="kpi-cards">
            <div class="kpi-card">
                <h3>Total Income</h3>
                <h2 class="text-income"><?php echo $sym; ?><?php echo number_format($totalIncome, 2); ?></h2>
            </div>
            <div class="kpi-card">
                <h3>Total Expenses</h3>
                <h2 class="text-expense"><?php echo $sym; ?><?php echo number_format($totalExpense, 2); ?></h2>
            </div>
            <div class="kpi-card">
                <h3>Net Savings</h3>
                <h2 class="text-net"><?php echo $sym; ?><?php echo number_format($netSavings, 2); ?></h2>
            </div>
            <div class="kpi-card">
                <h3>Savings Rate</h3>
                <h2 class="text-net"><?php echo number_format($savingsRate, 1); ?>%</h2>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">

            <!-- Left Chart: Income vs Expense (Bar Chart) -->
            <div class="chart-container">
                <h3>Income vs. Expenses (Last 6 Months)</h3>

                <div class="css-bar-chart">
                    <?php foreach ($barMonths as $data):
                        // Calculate percentage height based on the max value
                        $incHeight = ($data['income'] / $maxChartValue) * 100;
                        $expHeight = ($data['expense'] / $maxChartValue) * 100;
                    ?>
                        <div class="bar-group" data-month="<?php echo $data['label']; ?>">
                            <div class="bar inc" style="height: <?php echo $incHeight; ?>%;" title="Income: $<?php echo number_format($data['income']); ?>"></div>
                            <div class="bar exp" style="height: <?php echo $expHeight; ?>%;" title="Expense: $<?php echo number_format($data['expense']); ?>"></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chart-legend">
                    <div class="legend-item"><span class="dot inc"></span> Income</div>
                    <div class="legend-item"><span class="dot exp"></span> Expense</div>
                </div>
            </div>

            <!-- Right Chart: Expense Breakdown (Doughnut Chart) -->
            <div class="chart-container">
                <h3>Expenses by Category</h3>

                <!-- We inject the dynamic conic-gradient into the background here -->
                <div class="css-doughnut-chart" style="background: conic-gradient(<?php echo $conicGradient; ?>); border-radius: 50%; display: flex; justify-content: center; align-items: center; position: relative;">
                    <!-- Added a hardcoded size inline just in case your CSS needs it for the background trick -->
                    <div class="css-doughnut-inner" style="background-color: var(--white); border-radius: 50%; width: 60%; height: 60%; display: flex; justify-content: center; align-items: center; font-weight: bold;">
                        <?php echo date('M'); ?>
                    </div>
                </div>

                <ul class="category-legend">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <span class="cat-name"><span class="dot" style="background-color: <?php echo $cat['color']; ?>;"></span> <?php echo htmlspecialchars(ucfirst($cat['name'])); ?></span>
                                <strong><?php echo number_format($cat['percent'], 1); ?>%</strong>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><span class="cat-name">No expenses recorded.</span></li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

    </main>
    <a href="#go-back">
        <button class="sticky-nav-btn" title="Go Back">
            <span class="arrow-left"></span>
        </button>
    </a>

</body>

</html>