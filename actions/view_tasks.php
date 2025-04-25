<?php
// Include necessary files
include('../config/db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}


// Check if project id is provided
$project_id = $_POST['project_id'] ?? $_GET['project_id'] ?? null;
$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

// Fetch project details from the database
$query = "SELECT * FROM Tasks WHERE project_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$project_id) {
    $_SESSION['error_message'] = 'Invalid project ID.';
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}

?>
<div class="container">
    <?php include('../includes/alerts.php'); ?>
</div>

<main>
    <h3>Tasks for Project: 
    <?php
        $query = "SELECT title FROM Projects WHERE project_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo htmlspecialchars($row['title']);
        } else {
            echo "Unknown Project";
        }

        $stmt->close();
    ?>
</h3>
    </h3>
    <?php
    if ($result->num_rows > 0):  // If tasks exist for the selected project
    ?>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>Task Title</th>
                <th>Description</th>
                <th>Deadline</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($task = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($task['title']) ?></td>
                    <td><?= htmlspecialchars($task['description']) ?></td>
                    <td><?= htmlspecialchars($task['due_date']) ?></td>
                    <td><?= htmlspecialchars($task['priority']) ?></td>
                    <td><?= htmlspecialchars($task['status']) ?></td>
                    <td>
                        <?php
                        // Fetch assigned user name
                        $assigned_user_id = $task['assigned_to'];
                        $user_query = "SELECT name FROM Users WHERE user_id = ?";
                        $user_stmt = $conn->prepare($user_query);
                        $user_stmt->bind_param("i", $assigned_user_id);
                        $user_stmt->execute();
                        $user_result = $user_stmt->get_result();
                        $user_row = $user_result->fetch_assoc();
                        echo htmlspecialchars($user_row['name']);
                        ?>
                    </td>
                    <td>
                        <!-- Edit Button -->
                        <form action="../actions/edit_task.php" method="GET" style="display:inline;">
                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                            <button type="submit">Edit</button>
                        </form>

                        <!-- Delete Button -->
                        <form action="../actions/delete_task.php" method="GET" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                            <button type="submit" style="background-color:red; color:white;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php else: ?>
       <p>You have not created any tasks for this project yet.</p>
    <?php endif; ?>

    <!-- Add Task Form-->
    <h3>Add a New Task</h3>
    <form method="POST" action="/TaskTrackr/actions/add_task.php">
        <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">

        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title" required><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" rows="4" cols="50" required></textarea><br><br>

        <label for="deadline">Deadline:</label><br>
        <input type="date" id="deadline" name="deadline" required><br><br>

        <label for="priority">Priority:</label><br>
        <select id="priority" name="priority" required>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
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
                <option value="<?= $member['user_id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Add Task</button>
    </form>

    <p><a href="../public/projects.php">Back</a></p>
</main>
