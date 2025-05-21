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

// Sorting logic
$allowed_sort = [
    'group_name' => 'g.group_name',
    'project_title' => 'p.title',
    'due_date' => 't.due_date',
    // Ascending: Low, Medium, High (FIELD returns 1 for Low, 2 for Medium, 3 for High)
    'priority' => 'FIELD(t.priority, "Low", "Medium", "High")',
    // Ascending: Pending, In Progress, Done (FIELD returns 1 for Pending, 2 for In Progress, 3 for Done, 4 for Overdue)
    'status' => 'FIELD(t.status, "Pending", "In Progress", "Done", "Overdue")'
];
$sort_by = $_GET['sort_by'] ?? 'due_date';
$sort_dir = strtolower($_GET['sort_dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';
$order_by = $allowed_sort[$sort_by] ?? 't.due_date';

// Fetch tasks assigned to the user, grouped by project
$where = "t.assigned_to = ? AND t.project_id IS NOT NULL";
if (isset($_GET['overdue_only'])) {
    $where .= " AND t.status = 'Overdue'";
}
$query = "
    SELECT 
        g.group_id,
        g.group_name,
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
    LEFT JOIN Groups g ON p.group_id = g.group_id
    WHERE $where
    ORDER BY $order_by $sort_dir, g.group_name, p.title, t.due_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Organize tasks by group and project
$tasks_by_group = [];
while ($row = $result->fetch_assoc()) {
    $group_id = $row['group_id'] ?? 0;
    $group_name = $row['group_name'] ?? 'No Group';
    $project_id = $row['project_id'];
    $project_title = $row['project_title'] ?? 'Unassigned Project';

    if (!isset($tasks_by_group[$group_id])) {
        $tasks_by_group[$group_id] = [
            'group_name' => $group_name,
            'projects' => []
        ];
    }
    if (!isset($tasks_by_group[$group_id]['projects'][$project_id])) {
        $tasks_by_group[$group_id]['projects'][$project_id] = [
            'project_title' => $project_title,
            'tasks' => []
        ];
    }
    $tasks_by_group[$group_id]['projects'][$project_id]['tasks'][] = $row;
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
            
            <!-- Sorting Filter -->
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="sort_by" class="form-label mb-0">Sort by:</label>
                    </div>
                    <div class="col-auto">
                        <select name="sort_by" id="sort_by" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="group_name" <?= ($_GET['sort_by'] ?? '') === 'group_name' ? 'selected' : '' ?>>Group Name</option>
                            <option value="project_title" <?= ($_GET['sort_by'] ?? '') === 'project_title' ? 'selected' : '' ?>>Project Title</option>
                            <option value="due_date" <?= ($_GET['sort_by'] ?? '') === 'due_date' ? 'selected' : '' ?>>Due Date</option>
                            <option value="priority" <?= ($_GET['sort_by'] ?? '') === 'priority' ? 'selected' : '' ?>>Priority</option>
                            <option value="status" <?= ($_GET['sort_by'] ?? '') === 'status' ? 'selected' : '' ?>>Status</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="sort_dir" id="sort_dir" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="asc" <?= ($_GET['sort_dir'] ?? '') === 'asc' ? 'selected' : '' ?>>Ascending</option>
                            <option value="desc" <?= ($_GET['sort_dir'] ?? '') === 'desc' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="overdue_only" id="overdue_only" value="1" <?= isset($_GET['overdue_only']) ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label" for="overdue_only">
                                Show Overdue Only
                            </label>
                        </div>
                    </div>
                </div>
            </form>            

            <?php if (empty($tasks_by_group)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center p-5 text-center text-muted">
                    <i class="bi bi-clipboard-x" style="font-size: 2.5rem;"></i>
                    <p class="mt-3 mb-0">No tasks assigned to you yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks_by_group as $group): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header bg-dark text-white">
                            <strong>Group:</strong> <?= htmlspecialchars($group['group_name']) ?>
                        </div>
                        <div class="card-body p-2">
                            <?php foreach ($group['projects'] as $project): ?>
                                <div class="card shadow-sm mb-3">
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
                                                        <?php if ($task['status'] === 'Pending' || $task['status'] === 'Overdue'): ?>
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
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>

