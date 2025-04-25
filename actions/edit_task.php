<?php
// Include necessary files
include('../config/db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Check if task ID is provided
$task_id = $_GET['task_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;

if (!$task_id || !$project_id) {
    $_SESSION['error_message'] = "Invalid task or project ID.";
    header("Location: /TaskTrackr/public/view_tasks.php");
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
    header("Location: /TaskTrackr/public/view_tasks.php");
    exit();
}

$task = $result->fetch_assoc();
?>

<div class="container">
    <?php include('../includes/alerts.php'); ?>
</div>

<main>
    <h2>Edit Task</h2>
    <form method="POST" action="../actions/update_task.php">
        <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['task_id']) ?>">
        <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">

        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($task['title']) ?>" required><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" rows="4" cols="50" required><?= htmlspecialchars($task['description']) ?></textarea><br><br>

        <label for="deadline">Deadline:</label><br>
        <input type="date" id="deadline" name="deadline" value="<?= htmlspecialchars($task['due_date']) ?>" required><br><br>

        <label for="priority">Priority:</label><br>
        <select id="priority" name="priority" required>
            <option value="Low" <?= $task['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
            <option value="Medium" <?= $task['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
            <option value="High" <?= $task['priority'] === 'High' ? 'selected' : '' ?>>High</option>
        </select><br><br>

        <label for="status">Status:</label><br>
        <select id="status" name="status" required>
            <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Done" <?= $task['status'] === 'Done' ? 'selected' : '' ?>>Done</option>
        </select><br><br>

        <label for="assign_to">Assign To:</label><br>
        <select id="assign_to" name="assign_to" required>
            <option value="">-- Select Member --</option>
            <?php
            // Fetch group members and list them
            $groupQuery = "SELECT group_id FROM Projects WHERE project_id = ?";
            $stmt = $conn->prepare($groupQuery);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $groupResult = $stmt->get_result();
            $groupRow = $groupResult->fetch_assoc();
            $group_id = $groupRow['group_id'];

            // Fetch members of that group
            $memberQuery = "SELECT u.user_id, u.name 
                            FROM Users u 
                            INNER JOIN User_Groups ug ON u.user_id = ug.user_id 
                            WHERE ug.group_id = ?";
            $stmt = $conn->prepare($memberQuery);
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $membersResult = $stmt->get_result();

            while ($member = $membersResult->fetch_assoc()):
            ?>
                <option value="<?= $member['user_id'] ?>" <?= $task['assigned_to'] == $member['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($member['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Update Task</button>
    </form>

    <p><a href="../actions/view_tasks.php?project_id=<?= $project_id ?>">Back</a></p>
</main>
