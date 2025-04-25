<!-- filepath: c:\xampp\htdocs\TaskTrackr\public\dashboard.php -->
<?php
session_start();
include('../config/db.php');

// 🔐 Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 📊 Fetch Task Summary
$task_summary_query = "
    SELECT 
        CASE 
            WHEN status = 'Done' THEN 'Done'
            WHEN due_date < CURDATE() THEN 'Overdue'
            ELSE status
        END AS status_group,
        COUNT(*) AS count 
    FROM Tasks 
    WHERE assigned_to = ? 
    GROUP BY status_group
";
$task_summary_stmt = $conn->prepare($task_summary_query);
$task_summary_stmt->bind_param("i", $user_id);
$task_summary_stmt->execute();
$task_summary_result = $task_summary_stmt->get_result();

$task_summary = [
    'Pending' => 0,
    'In Progress' => 0,
    'Done' => 0,
    'Overdue' => 0
];
while ($row = $task_summary_result->fetch_assoc()) {
    $task_summary[$row['status_group']] = $row['count'];
}

// 📅 Fetch Upcoming Tasks
$upcoming_tasks_query = "
    SELECT 
        t.title AS task_title, 
        t.due_date,
        CASE 
            WHEN t.due_date < CURDATE() AND t.status != 'Done' THEN 'Overdue'
            ELSE 'Upcoming'
        END AS task_status
    FROM Tasks t
    WHERE t.assigned_to = ? 
    ORDER BY t.due_date ASC
";
$upcoming_tasks_stmt = $conn->prepare($upcoming_tasks_query);
$upcoming_tasks_stmt->bind_param("i", $user_id);
$upcoming_tasks_stmt->execute();
$upcoming_tasks_result = $upcoming_tasks_stmt->get_result();

// Prepare events for FullCalendar
$events = [];
while ($task = $upcoming_tasks_result->fetch_assoc()) {
    $events[] = [
        'title' => htmlspecialchars($task['task_title']),
        'start' => $task['due_date'],
        'color' => $task['task_status'] === 'Overdue' ? '#dc3545' : '#0d6efd' // Red for overdue, blue for upcoming
    ];
}
?>

<?php include('../includes/header.php'); ?>
<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content flex-grow-1 p-4">
        <!-- 👋 Welcome Section -->
        <div class="welcome-section mb-4">
            <h2>Hello, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
            <p class="text-muted">Welcome back! Here’s a quick look at your progress.</p>
        </div>

        <!-- 📊 Task Summary Cards -->
        <div class="row g-4 mb-4">
            <?php
            $statuses = [
                'Pending' => ['color' => 'bg-secondary', 'icon' => 'bi-hourglass-split'],
                'In Progress' => ['color' => 'bg-primary', 'icon' => 'bi-arrow-repeat'],
                'Done' => ['color' => 'bg-success', 'icon' => 'bi-check-circle'],
                'Overdue' => ['color' => 'bg-danger', 'icon' => 'bi-exclamation-triangle']
            ];
            foreach ($statuses as $status => $details): ?>
                <div class="col-md-3">
                    <div class="card text-white <?= $details['color'] ?> shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi <?= $details['icon'] ?> me-2"></i> <?= $status ?></h5>
                            <h3 class="card-text"><?= $task_summary[$status] ?></h3>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 📈 Charts Section -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Task Distribution</h5>
                        <canvas id="taskChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Upcoming Tasks</h5>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Task Distribution Chart
    const taskChartCtx = document.getElementById('taskChart').getContext('2d');
    new Chart(taskChartCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Completed', 'Overdue'],
            datasets: [{
                data: [
                    <?= $task_summary['Pending']; ?>,
                    <?= $task_summary['In Progress']; ?>,
                    <?= $task_summary['Done']; ?>,
                    <?= $task_summary['Overdue']; ?>
                ],
                backgroundColor: ['#6c757d', '#0d6efd', '#198754', '#dc3545']
            }]
        }
    });
</script>

<!-- Include FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: <?= json_encode($events); ?>
        });
        calendar.render();
    });
</script>

<?php include('../includes/footer.php'); ?>