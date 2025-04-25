<?php
session_start();
include('../config/db.php');
include('../includes/alerts.php'); // Include alert messages

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch tasks assigned to the user, grouped by project
$query = "
    SELECT 
        p.project_id,
        p.title AS project_title,
        t.task_id,
        t.title AS task_title,
        t.description,
        t.due_date,
        t.priority,
        t.status
    FROM Tasks t
    LEFT JOIN Projects p ON t.project_id = p.project_id
    WHERE t.assigned_to = ?
    ORDER BY p.project_id, t.due_date
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Organize tasks by project
$tasks_by_project = [];
while ($row = $result->fetch_assoc()) {
    $project_id = $row['project_id'];
    $project_title = $row['project_title'] ?? 'Unassigned Project';

    if (!isset($tasks_by_project[$project_id])) {
        $tasks_by_project[$project_id] = [
            'project_title' => $project_title,
            'tasks' => []
        ];
    }

    $tasks_by_project[$project_id]['tasks'][] = $row;
}
?>

<?php include('../includes/header.php'); ?>

<main class="container mt-5">
    <h2 class="mb-4">My Assigned Tasks</h2>

    <?php if (empty($tasks_by_project)): ?>
        <p>No tasks assigned to you yet.</p>
    <?php else: ?>
        <?php foreach ($tasks_by_project as $project): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>Project:</strong> <?= htmlspecialchars($project['project_title']) ?>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($project['tasks'] as $task): ?>
                        <li class="list-group-item">
                            <h5><?= htmlspecialchars($task['task_title']) ?></h5>
                            <p><?= htmlspecialchars($task['description']) ?></p>
                            <small>
                                <strong>Due:</strong> <?= $task['due_date'] ?> |
                                <strong>Priority:</strong> <?= $task['priority'] ?> |
                                <strong>Status:</strong> <?= $task['status'] ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include('../includes/footer.php'); ?>

