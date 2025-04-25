<?php
// Include necessary files
include('../config/db.php');
include('../includes/header.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if user is not logged in
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id']; // Assumes session is already started

// Fetch user's projects
$project_query = "
    SELECT p.*, c.name 
    FROM Projects p 
    JOIN Categories c ON p.category_id = c.category_id 
    WHERE p.created_by = ? 
       OR p.group_id IN (
           SELECT group_id FROM User_Groups WHERE user_id = ?
       )";

$stmt = $conn->prepare($project_query);
if (!$stmt) {
    die('Error preparing the SQL query: ' . $conn->error);
}
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$project_result = $stmt->get_result();

// Fetch all categories for dropdown
$category_query = "SELECT * FROM Categories";
$category_result = $conn->query($category_query);
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content flex-grow-1 p-4">
        <h2 class="mb-4">Your Projects</h2>

        <!-- Display Projects -->
        <?php if ($project_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Project Title</th>
                            <th>Description</th>
                            <th>Deadline</th>
                            <th>Category</th>
                            <th>Group</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($project = $project_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['title']) ?></td>
                                <td><?= htmlspecialchars($project['description']) ?></td>
                                <td><?= htmlspecialchars($project['deadline']) ?></td>
                                <td><?= htmlspecialchars($project['name']) ?></td>
                                <?php
                                    // Fetch group name if group_id is set
                                    if ($project['group_id']) {
                                        $group_query = "SELECT group_name FROM Groups WHERE group_id = ?";
                                        $group_stmt = $conn->prepare($group_query);
                                        $group_stmt->bind_param("i", $project['group_id']);
                                        $group_stmt->execute();
                                        $group_result = $group_stmt->get_result();
                                        $group = $group_result->fetch_assoc();
                                        $group_stmt->close();
                                    }
                                ?>
                                <td><?= isset($group['group_name']) ? htmlspecialchars($group['group_name']) : 'N/A' ?></td>
                                <?php
                                    // Fetch user who created the project
                                    $created_by_query = "SELECT name FROM Users WHERE user_id = ?";
                                    $created_by_stmt = $conn->prepare($created_by_query);
                                    $created_by_stmt->bind_param("i", $project['created_by']);
                                    $created_by_stmt->execute();
                                    $created_by_result = $created_by_stmt->get_result();
                                    $created_by = $created_by_result->fetch_assoc();
                                    $created_by_stmt->close();
                                ?>
                                <td><?= htmlspecialchars($created_by['name']) ?></td>
                                <td>
                                    <?php if ($project['created_by'] == $user_id): ?>
                                        <!-- View Tasks Button -->
                                        <form action="../actions/view_tasks.php" method="POST" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                            <button type="submit" class="btn btn-info btn-sm">View Tasks</button>
                                        </form>

                                        <!-- Edit Button -->
                                        <form action="../actions/edit_project.php" method="GET" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                                        </form>

                                        <!-- Delete Button -->
                                        <form action="../actions/delete_project.php" method="GET" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">No Actions Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">You have not created any projects yet.</p>
        <?php endif; ?>

        <!-- Create Project Form -->
        <h3 class="mt-5">Create New Project</h3>
        <form action="../actions/create_project.php" method="POST" class="mt-3">
            <div class="mb-3">
                <label for="title" class="form-label">Project Title</label>
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
                <label for="category" class="form-label">Category</label>
                <select id="category" name="category" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php while ($category = $category_result->fetch_assoc()): ?>
                        <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="group" class="form-label">Group</label>
                <select id="group" name="group_id" class="form-select">
                    <option value="" <?= (!isset($project['group_id']) || is_null($project['group_id'])) ? 'selected' : '' ?>>No Group</option>
                    <?php
                        // Join Groups table to get group names
                        $group_query = "SELECT g.group_id, g.group_name FROM Groups g 
                                        INNER JOIN User_Groups ug ON g.group_id = ug.group_id 
                                        WHERE ug.user_id = ?";
                        $group_stmt = $conn->prepare($group_query);
                        $group_stmt->bind_param("i", $user_id);
                        $group_stmt->execute();
                        $group_result = $group_stmt->get_result();

                        while ($group = $group_result->fetch_assoc()):
                    ?>
                        <option value="<?= $group['group_id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Create Project</button>
        </form>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
