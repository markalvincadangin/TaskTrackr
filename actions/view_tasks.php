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

// Fetch project info (title, group_id, created_by)
$project_query = "SELECT title, group_id, created_by, deadline FROM Projects WHERE project_id = ?";
$project_stmt = $conn->prepare($project_query);
$project_stmt->bind_param("i", $project_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();
$project = $project_result->fetch_assoc();
$project_title = $project['title'] ?? 'Unknown Project';
$group_id = $project['group_id'] ?? null;
$creator_id = $project['created_by'] ?? $user_id;
$project_deadline = $project['deadline'] ?? null;

// After fetching project info
if (!empty($group_id)) {
    $membership_query = "SELECT * FROM User_Groups WHERE user_id = ? AND group_id = ?";
    $membership_stmt = $conn->prepare($membership_query);
    $membership_stmt->bind_param("ii", $user_id, $group_id);
    $membership_stmt->execute();
    $membership_result = $membership_stmt->get_result();
    if ($membership_result->num_rows === 0) {
        $_SESSION['error_message'] = "You are no longer a member of this group.";
        header("Location: /TaskTrackr/public/groups.php");
        exit();
    }
}

// Fetch tasks for the project
$task_query = "SELECT * FROM Tasks WHERE project_id = ?";
$task_stmt = $conn->prepare($task_query);
$task_stmt->bind_param("i", $project_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">
            <!-- Section Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><i class="bi bi-list-task me-2"></i>Tasks for Project: <?= htmlspecialchars($project_title) ?></h2>
                <?php if ($creator_id == $user_id): ?>
                    <!-- Add Task Modal Trigger -->
                    <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="bi bi-plus-circle me-2"></i> Add Task
                    </button>
                <?php endif; ?>
            </div>

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <!-- Tasks Table Card -->
            <div class="card shadow-sm p-4 mb-4">
                <div class="card-body p-0">
                    <h4 class="card-title p-4 pb-0 mb-0">Task List</h4>
                    <?php if ($task_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Task Title</th>
                                        <th>Description</th>
                                        <th>Deadline</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($task = $task_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($task['title']) ?></td>
                                            <td><?= htmlspecialchars($task['description']) ?></td>
                                            <td><?= htmlspecialchars($task['due_date']) ?></td>
                                            <td>
                                                <?php
                                                $priority = htmlspecialchars($task['priority']);
                                                $priority_badge = [
                                                    'Low' => 'success',
                                                    'Medium' => 'warning',
                                                    'High' => 'danger'
                                                ][$priority] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $priority_badge ?>">
                                                    <?= $priority ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status = htmlspecialchars($task['status']);
                                                $status_badge = match($status) {
                                                    'Pending' => 'secondary',
                                                    'In Progress' => 'primary',
                                                    'Completed', 'Done' => 'success',
                                                    'Overdue' => 'danger',
                                                    default => 'light'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $status_badge ?>">
                                                    <?= $status ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                // Fetch assigned user name
                                                $assigned_user_id = $task['assigned_to'];
                                                $user_query = "SELECT CONCAT(first_name, ' ', last_name) AS name FROM Users WHERE user_id = ?";
                                                $user_stmt = $conn->prepare($user_query);
                                                $user_stmt->bind_param("i", $assigned_user_id);
                                                $user_stmt->execute();
                                                $user_result = $user_stmt->get_result();
                                                $user_row = $user_result->fetch_assoc();
                                                echo htmlspecialchars($user_row['name'] ?? 'Unassigned');
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($creator_id == $user_id): ?>
                                                    <div class="d-flex align-items-center gap-2 flex-nowrap justify-content-center">
                                                        <!-- Edit Button -->
                                                        <a href="../actions/edit_task.php?task_id=<?= $task['task_id'] ?>&project_id=<?= $project_id ?>" class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <!-- Delete Button -->
                                                        <form action="../actions/delete_task.php" method="GET" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center p-5 text-center text-muted">
                            <i class="bi bi-clipboard-x" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">No tasks have been created for this project yet.<br>
                            <span class="small">Click <strong>Add Task</strong> to get started!</span></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($creator_id == $user_id): ?>
                <!-- Add Task Modal -->
                <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="/TaskTrackr/actions/add_task.php">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addTaskModalLabel"><i class="bi bi-plus-circle me-2"></i>Add a New Task</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">
                                    <?php if (empty($group_id)): ?>
                                        <input type="hidden" name="assign_to" value="<?= htmlspecialchars($creator_id) ?>">
                                    <?php endif; ?>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="title" class="form-label">Title</label>
                                            <input type="text" id="title" name="title" class="form-control" placeholder="Task title" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="deadline" class="form-label">Deadline</label>
                                            <input type="date"
                                                   id="deadline"
                                                   name="deadline"
                                                   class="form-control"
                                                   required
                                                   min="<?= date('Y-m-d') ?>"
                                                   <?php if ($project_deadline): ?>
                                                       max="<?= htmlspecialchars($project_deadline) ?>"
                                                   <?php endif; ?>
                                            >
                                        </div>
                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Describe the task..." required></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select id="priority" name="priority" class="form-select" required>
                                                <option value="Low">Low</option>
                                                <option value="Medium">Medium</option>
                                                <option value="High">High</option>
                                            </select>
                                        </div>
                                        <?php if (!empty($group_id)): ?>
                                        <div class="col-md-6">
                                            <label for="assign_to" class="form-label">Assign To</label>
                                            <select id="assign_to" name="assign_to" class="form-select" required>
                                                <option value="">Select Member</option>
                                                <?php
                                                // Fetch members of that group
                                                $member_query = "SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) AS name 
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
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Add Task
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>
