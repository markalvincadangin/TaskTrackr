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
            WHEN t.status = 'Done' THEN 'Done'
            WHEN t.due_date < CURDATE() THEN 'Overdue'
            ELSE t.status
        END AS status_group,
        COUNT(*) AS count 
    FROM Tasks t LEFT JOIN Projects p 
    ON t.project_id = p.project_id
    WHERE t.assigned_to = ? AND t.project_id IS NOT NULL
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
    FROM Tasks t LEFT JOIN Projects p 
    ON t.project_id = p.project_id
    WHERE t.assigned_to = ? AND t.project_id IS NOT NULL
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

// 📈 Assigned Tasks Progress Tracking
$assigned_tasks_query = "SELECT COUNT(*) AS total, SUM(t.status = 'Done') AS done 
FROM Tasks t LEFT JOIN Projects p 
ON t.project_id = p.project_id
WHERE t.assigned_to = ? AND t.project_id IS NOT NULL";

$assigned_tasks_stmt = $conn->prepare($assigned_tasks_query);
$assigned_tasks_stmt->bind_param("i", $user_id);
$assigned_tasks_stmt->execute();
$assigned_tasks_result = $assigned_tasks_stmt->get_result();
$assigned_tasks = $assigned_tasks_result->fetch_assoc();
$total_tasks = (int)$assigned_tasks['total'];
$done_tasks = (int)$assigned_tasks['done'];
$completion_percent = $total_tasks > 0 ? round(($done_tasks / $total_tasks) * 100) : 0;

// 🛠 Project Progress Tracking
$projects_query = "
    SELECT p.project_id, p.title
    FROM Projects p
    WHERE p.created_by = ?
       OR p.group_id IN (SELECT group_id FROM User_Groups WHERE user_id = ?)
";
$projects_stmt = $conn->prepare($projects_query);
$projects_stmt->bind_param("ii", $user_id, $user_id);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();

$project_cards = [];
while ($project = $projects_result->fetch_assoc()) {
    $proj_id = $project['project_id'];
    $tasks_query = "SELECT COUNT(*) AS total, SUM(status = 'Done') AS done FROM Tasks WHERE project_id = ?";
    $tasks_stmt = $conn->prepare($tasks_query);
    $tasks_stmt->bind_param("i", $proj_id);
    $tasks_stmt->execute();
    $tasks_result = $tasks_stmt->get_result();
    $tasks = $tasks_result->fetch_assoc();
    $total = (int)$tasks['total'];
    $done = (int)$tasks['done'];
    $percent = $total > 0 ? round(($done / $total) * 100) : 0;
    $project_cards[] = [
        'title' => $project['title'],
        'percent' => $percent,
        'total' => $total
    ];
}
?>

<?php include('../includes/header.php'); ?>
<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <!-- Welcome Section -->
            <div class="card shadow-sm rounded p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-2"><i class="bi bi-house-door me-2"></i>Hello, <?= htmlspecialchars($_SESSION['name']); ?>!</h2>
                        <p class="text-muted mb-0">Welcome back! Here’s a quick look at your progress.</p>
                    </div>
                </div>
            </div>

            <!-- Task Summary Cards -->
            <div class="row g-3 mb-4">
                <?php
                $statuses = [
                    'Pending' => ['color' => 'bg-secondary', 'icon' => 'bi-hourglass-split'],
                    'In Progress' => ['color' => 'bg-primary', 'icon' => 'bi-arrow-repeat'],
                    'Done' => ['color' => 'bg-success', 'icon' => 'bi-check-circle'],
                    'Overdue' => ['color' => 'bg-danger', 'icon' => 'bi-exclamation-triangle']
                ];
                foreach ($statuses as $status => $details): ?>
                    <div class="col-md-3">
                        <div class="card text-white <?= $details['color'] ?> shadow-sm rounded p-3 h-100 flex-fill">
                            <div class="card-body">
                                <h5 class="card-title mb-2"><i class="bi <?= $details['icon'] ?> me-2"></i><?= $status ?></h5>
                                <h3 class="card-text"><?= $task_summary[$status] ?></h3>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Insights Section: Chart & Progress (Left), Calendar (Right) -->
            <div class="row g-4 mb-4">
                <!-- Left Column -->
                <div class="col-md-6 d-flex flex-column">
                    <div class="card shadow-sm rounded p-3 h-100 flex-fill mb-4">
                        <div class="card-body d-flex flex-column align-items-center position-relative">
                            <h5 class="card-title mb-3 w-100 fw-bold"><i class="bi bi-pie-chart me-2"></i>Tasks Distribution</h5>
                            <?php $no_tasks = array_sum($task_summary) === 0; ?>
                            <?php if ($no_tasks): ?>
                                <div class="d-flex flex-column align-items-center justify-content-center w-100" style="min-height:200px;">
                                    <i class="bi bi-clipboard-x text-muted" style="font-size:2.5rem;"></i>
                                    <div class="mt-2 text-muted">No tasks assigned yet.</div>
                                </div>
                            <?php else: ?>
                                <canvas id="taskChart" style="max-height:250px;width:100%;"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card shadow-sm rounded p-3 h-100 flex-fill">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="card-title mb-3 fw-bold"><i class="bi bi-bar-chart-line me-2"></i>Tasks Completion</h5>
                            <?php if ($total_tasks > 0): ?>
                                <div class="mb-2">
                                    <strong>You have completed <?= $completion_percent ?>% of your assigned tasks.</strong>
                                </div>
                                <div class="progress" style="height: 24px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $completion_percent ?>%;" aria-valuenow="<?= $completion_percent ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?= $completion_percent ?>%
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">No tasks assigned yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Right Column -->
                <div class="col-md-6 d-flex flex-column">
                    <div class="card shadow-sm rounded p-3 h-100 flex-fill">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-3 fw-bold"><i class="bi bi-calendar-event me-2"></i>Upcoming Tasks</h5>
                            <div id="calendar" style="min-height: 300px; max-height: 500px; overflow-y: auto;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Progress Section -->
            <div class="card shadow-sm rounded p-4 mb-4">
                <h5 class="card-title mb-4 fw-bold"><i class="bi bi-graph-up-arrow me-2"></i>Project Progress</h5>
                <div class="row g-3">
                    <?php if (count($project_cards) > 0): ?>
                        <?php foreach ($project_cards as $proj): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card shadow-sm rounded p-3 h-100 flex-fill">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2"><?= htmlspecialchars($proj['title']) ?></h6>
                                        <?php if ($proj['total'] > 0): ?>
                                            <div class="mb-2">
                                                <small>Project is <?= $proj['percent'] ?>% complete.</small>
                                            </div>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: <?= $proj['percent'] ?>%;" aria-valuenow="<?= $proj['percent'] ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?= $proj['percent'] ?>%
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted">No tasks in this project yet.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-muted">No projects yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php if (!$no_tasks): ?>
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
<?php endif; ?>

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