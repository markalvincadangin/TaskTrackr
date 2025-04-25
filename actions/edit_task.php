<?php
// Include necessary files
include('../config/db.php');
include('../includes/header.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Check if task ID and project ID are provided
$task_id = $_GET['task_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;

if (!$task_id || !$project_id) {
    $_SESSION['error_message'] = "Invalid task or project ID.";
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}

// Fetch task details from the database
$query = "SELECT * FROM Tasks WHERE task_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Task not found.";
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}

$task = $result->fetch_assoc();
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content flex-grow-1 p-4">
        <h2 class="mb-4 text-center">Edit Task</h2>

        <!-- Display Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="../actions/update_task.php">
                    <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['task_id']) ?>">
                    <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">

                    <!-- Task Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($task['title']) ?>" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required><?= htmlspecialchars($task['description']) ?></textarea>
                    </div>

                    <!-- Deadline -->
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" value="<?= htmlspecialchars($task['due_date']) ?>" required>
                    </div>

                    <!-- Priority -->
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-select" required>
                            <option value="Low" <?= $task['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                            <option value="Medium" <?= $task['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="High" <?= $task['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Done" <?= $task['status'] === 'Done' ? 'selected' : '' ?>>Done</option>
                        </select>
                    </div>

                    <!-- Assign To -->
                    <div class="mb-3">
                        <label for="assign_to" class="form-label">Assign To</label>
                        <select id="assign_to" name="assign_to" class="form-select" required>
                            <option value="">-- Select Member --</option>
                            <?php
                            // Fetch group members and list them
                            $group_query = "SELECT group_id FROM Projects WHERE project_id = ?";
                            $group_stmt = $conn->prepare($group_query);
                            $group_stmt->bind_param("i", $project_id);
                            $group_stmt->execute();
                            $group_result = $group_stmt->get_result();
                            $group_row = $group_result->fetch_assoc();
                            $group_id = $group_row['group_id'];

                            // Fetch members of that group
                            $member_query = "SELECT u.user_id, u.name 
                                             FROM Users u 
                                             INNER JOIN User_Groups ug ON u.user_id = ug.user_id 
                                             WHERE ug.group_id = ?";
                            $member_stmt = $conn->prepare($member_query);
                            $member_stmt->bind_param("i", $group_id);
                            $member_stmt->execute();
                            $members_result = $member_stmt->get_result();

                            while ($member = $members_result->fetch_assoc()):
                            ?>
                                <option value="<?= $member['user_id'] ?>" <?= $task['assigned_to'] == $member['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($member['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100">Update Task</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
