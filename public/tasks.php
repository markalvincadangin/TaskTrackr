<?php
session_start();
include('../config/db.php');
include_once('../includes/email_sender.php');

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
    WHERE t.assigned_to = ? AND t.project_id IS NOT NULL
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

// Handle status updates and send notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];

    // Update the task status
    $update_query = "UPDATE Tasks SET status = ? WHERE task_id = ? AND assigned_to = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sii", $new_status, $task_id, $user_id);

    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Task status updated successfully.";

        // Fetch task info for notification
        $task_info_query = "SELECT title FROM Tasks WHERE task_id = ?";
        $task_info_stmt = $conn->prepare($task_info_query);
        $task_info_stmt->bind_param("i", $task_id);
        $task_info_stmt->execute();
        $task_info_result = $task_info_stmt->get_result();
        $task_info = $task_info_result->fetch_assoc();

        // Notify the user (self) about the status update
        $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
        $notify_stmt = $conn->prepare($notify_query);
        $message = "You updated the status of your task '{$task_info['title']}' to '{$new_status}'.";
        $notify_stmt->bind_param("is", $user_id, $message);
        $notify_stmt->execute();

        // Optionally, notify the project creator if not the same as the user
        $creator_query = "SELECT p.created_by FROM Tasks t JOIN Projects p ON t.project_id = p.project_id WHERE t.task_id = ?";
        $creator_stmt = $conn->prepare($creator_query);
        $creator_stmt->bind_param("i", $task_id);
        $creator_stmt->execute();
        $creator_result = $creator_stmt->get_result();
        $creator = $creator_result->fetch_assoc();
        if ($creator && $creator['created_by'] && $creator['created_by'] != $user_id) {
            $notify_stmt = $conn->prepare($notify_query);
            $creator_message = "A task in your project ('{$task_info['title']}') was updated to '{$new_status}'.";
            $notify_stmt->bind_param("is", $creator['created_by'], $creator_message);
            $notify_stmt->execute();
        }

        // Notify the user (self)
        $email_query = "SELECT email FROM Users WHERE user_id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $user_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user_email = $email_result->fetch_assoc()['email'] ?? null;

        if ($user_email) {
            $subject = "Task Status Updated: {$task_info['title']}";
            $body = "You updated the status of your task '{$task_info['title']}' to '{$new_status}'.";
            sendUserEmail($user_email, $subject, $body);
        }

        // Notify project creator if not the same as user
        if ($creator && $creator['created_by'] && $creator['created_by'] != $user_id) {
            $creator_email_query = "SELECT email FROM Users WHERE user_id = ?";
            $creator_email_stmt = $conn->prepare($creator_email_query);
            $creator_email_stmt->bind_param("i", $creator['created_by']);
            $creator_email_stmt->execute();
            $creator_email_result = $creator_email_stmt->get_result();
            $creator_email = $creator_email_result->fetch_assoc()['email'] ?? null;

            if ($creator_email) {
                $subject = "Task Status Updated in Your Project";
                $body = "A task in your project ('{$task_info['title']}') was updated to '{$new_status}'.";
                sendUserEmail($creator_email, $subject, $body);
            }
        }
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
    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <!-- Section Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><i class="bi bi-list-task me-2"></i>Your Assigned Tasks</h2>
            </div>

            <?php if (empty($tasks_by_project)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center p-5 text-center text-muted">
                    <i class="bi bi-clipboard-x" style="font-size: 2.5rem;"></i>
                    <p class="mt-3 mb-0">No tasks assigned to you yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks_by_project as $project): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <strong>Project:</strong> <?= htmlspecialchars($project['project_title']) ?>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($project['tasks'] as $task): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($task['task_title']) ?></h5>
                                            <p class="mb-1"><?= htmlspecialchars($task['description']) ?></p>
                                            <small>
                                                <strong>Due:</strong> <?= $task['due_date'] ?> |
                                                <strong>Priority:</strong> <?= $task['priority'] ?> |
                                                <strong>Status:</strong> <?= $task['status'] ?>
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <!-- Task Actions -->
                                            <?php if ($task['status'] === 'Pending'): ?>
                                                <form action="tasks.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                    <input type="hidden" name="status" value="In Progress">
                                                    <button type="submit" class="btn btn-primary btn-sm">Start Task</button>
                                                </form>
                                            <?php elseif ($task['status'] === 'In Progress'): ?>
                                                <form action="tasks.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                    <input type="hidden" name="status" value="Done">
                                                    <label class="mb-0">
                                                        <input type="checkbox" onchange="this.form.submit()"> Mark as Done
                                                    </label>
                                                </form>
                                            <?php elseif ($task['status'] === 'Done'): ?>
                                                <form action="tasks.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                    <input type="hidden" name="status" value="In Progress">
                                                    <button type="submit" class="btn btn-warning btn-sm">Reopen Task</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>

