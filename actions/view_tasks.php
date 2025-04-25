<?php
// Include necessary files
include('../config/db.php');
include('../includes/header.php');


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Check if project ID is provided
$project_id = $_POST['project_id'] ?? $_GET['project_id'] ?? null;
$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

if (!$project_id) {
    $_SESSION['error_message'] = 'Invalid project ID.';
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}

// Fetch project title
$project_query = "SELECT title FROM Projects WHERE project_id = ?";
$project_stmt = $conn->prepare($project_query);
$project_stmt->bind_param("i", $project_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();
$project = $project_result->fetch_assoc();
$project_title = $project['title'] ?? 'Unknown Project';

// Fetch tasks for the project
$task_query = "SELECT * FROM Tasks WHERE project_id = ?";
$task_stmt = $conn->prepare($task_query);
$task_stmt->bind_param("i", $project_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>

    <div class="main-content flex-grow-1 p-4">    
        <h2 class="mb-4">Tasks for Project: <?= htmlspecialchars($project_title) ?></h2>

        <!-- Display Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <!-- Display Tasks -->
        <?php if ($task_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
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
                        <?php while ($task = $task_result->fetch_assoc()): ?>
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
                                    echo htmlspecialchars($user_row['name'] ?? 'Unassigned');
                                    ?>
                                </td>
                                <td>
                                    <!-- Edit Button -->
                                    <a href="../actions/edit_task.php?task_id=<?= $task['task_id'] ?>&project_id=<?= $project_id ?>" class="btn btn-warning btn-sm">Edit</a>

                                    <!-- Delete Button -->
                                    <form action="../actions/delete_task.php" method="GET" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                        <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                        <input type="hidden" name="project_id" value="<?= $project_id ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No tasks have been created for this project yet.</p>
        <?php endif; ?>

        <!-- Add Task Form -->
        <h3 class="mt-5">Add a New Task</h3>
        <form method="POST" action="/TaskTrackr/actions/add_task.php" class="mt-3">
            <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">

            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>

            <div class="mb-3">
                <label for="deadline" class="form-label">Deadline</label>
                <input type="date" id="deadline" name="deadline" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select id="priority" name="priority" class="form-select" required>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

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
                        <option value="<?= $member['user_id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Add Task</button>
        </form>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
