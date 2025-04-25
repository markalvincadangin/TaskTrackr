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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];

    // Update the task status
    $update_query = "UPDATE Tasks SET status = ? WHERE task_id = ? AND assigned_to = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sii", $new_status, $task_id, $user_id);

    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Task status updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update task status.";
    }

    header("Location: tasks.php");
    exit();
}
?>

<?php include('../includes/header.php'); ?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content flex-grow-1 p-4" style="margin-left: 240px;">
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

                                <!-- Task Actions -->
                                <div class="mt-2">
                                    <?php if ($task['status'] === 'Pending'): ?>
                                        <!-- Start Task Button -->
                                        <form action="tasks.php" method="POST" class="d-inline">
                                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                            <input type="hidden" name="status" value="In Progress">
                                            <button type="submit" class="btn btn-primary btn-sm">Start Task</button>
                                        </form>
                                    <?php elseif ($task['status'] === 'In Progress'): ?>
                                        <!-- Mark as Done Checkbox -->
                                        <form action="tasks.php" method="POST" class="d-inline">
                                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                            <input type="hidden" name="status" value="Done">
                                            <label>
                                                <input type="checkbox" onchange="this.form.submit()"> Mark as Done
                                            </label>
                                        </form>
                                    <?php elseif ($task['status'] === 'Done'): ?>
                                        <!-- Reopen Task Button -->
                                        <form action="tasks.php" method="POST" class="d-inline">
                                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                            <input type="hidden" name="status" value="In Progress">
                                            <button type="submit" class="btn btn-warning btn-sm">Reopen Task</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

