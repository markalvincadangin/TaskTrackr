<?php
// Include necessary files
include('../config/db.php');
session_start();
include('../includes/header.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Sorting logic
$allowed_sort = [
    'deadline' => 'p.deadline',
    'title' => 'p.title',
    'category' => 'c.name',
    'group' => 'g.group_name',
    'creator' => 'u.first_name'
];
$sort_by = $_GET['sort_by'] ?? 'deadline';
$sort_dir = strtolower($_GET['sort_dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';
$order_by = $allowed_sort[$sort_by] ?? 'p.deadline';

// Fetch user's projects with group and creator names in one query
$project_query = "
    SELECT p.*, c.name AS category_name, g.group_name, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
    FROM Projects p
    JOIN Categories c ON p.category_id = c.category_id
    LEFT JOIN `Groups` g ON p.group_id = g.group_id
    JOIN Users u ON p.created_by = u.user_id
    WHERE p.created_by = ?
       OR p.group_id IN (
           SELECT group_id FROM User_Groups WHERE user_id = ?
       )
    ORDER BY $order_by $sort_dir
";
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

// Fetch all groups for dropdown (user is a member of)
$group_query = "SELECT g.group_id, g.group_name FROM Groups g 
                INNER JOIN User_Groups ug ON g.group_id = ug.group_id 
                WHERE ug.user_id = ?";
$group_stmt = $conn->prepare($group_query);
$group_stmt->bind_param("i", $user_id);
$group_stmt->execute();
$group_result = $group_stmt->get_result();
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <!-- Section Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><i class="bi bi-folder me-2"></i>Your Projects</h2>
                <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                    <i class="bi bi-plus-circle me-2"></i> Create Project
                </button>
            </div>

            <!-- Sorting Filter -->
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="sort_by" class="form-label mb-0">Sort by:</label>
                    </div>
                    <div class="col-auto">
                        <select name="sort_by" id="sort_by" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="deadline" <?= ($_GET['sort_by'] ?? '') === 'deadline' ? 'selected' : '' ?>>Deadline</option>
                            <option value="title" <?= ($_GET['sort_by'] ?? '') === 'title' ? 'selected' : '' ?>>Project Title</option>
                            <option value="category" <?= ($_GET['sort_by'] ?? '') === 'category' ? 'selected' : '' ?>>Category</option>
                            <option value="group" <?= ($_GET['sort_by'] ?? '') === 'group' ? 'selected' : '' ?>>Group</option>
                            <option value="creator" <?= ($_GET['sort_by'] ?? '') === 'creator' ? 'selected' : '' ?>>Created By</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="sort_dir" id="sort_dir" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="asc" <?= ($_GET['sort_dir'] ?? '') === 'asc' ? 'selected' : '' ?>>Ascending</option>
                            <option value="desc" <?= ($_GET['sort_dir'] ?? '') === 'desc' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Projects Table Card -->
            <div class="card shadow-sm p-4 mb-4">
                <div class="card-body p-0">
                    <h4 class="card-title p-4 pb-0 mb-0">Project List</h4>
                    <?php if ($project_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
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
                                            <td><?= htmlspecialchars($project['category_name']) ?></td>
                                            <td><?= $project['group_name'] ? htmlspecialchars($project['group_name']) : '<span class="text-muted">-</span>' ?></td>
                                            <td><?= htmlspecialchars($project['creator_name']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2 flex-nowrap">
                                                    <!-- View Tasks Button (always visible) -->
                                                    <form action="../actions/view_tasks.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                                        <button type="submit" class="btn btn-info btn-sm" title="View Tasks">
                                                            <i class="bi bi-list-task"></i>
                                                        </button>
                                                    </form>
                                                    <?php if ($project['created_by'] == $user_id): ?>
                                                        <!-- Edit Button -->
                                                        <form action="../actions/edit_project.php" method="GET" class="d-inline">
                                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                                            <button type="submit" class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        </form>
                                                        <!-- Delete Button -->
                                                        <form action="../actions/delete_project.php" method="GET" class="d-inline"
                                                              onsubmit="return confirm('Are you sure you want to delete this project? All tasks in this project will also be deleted. This action cannot be undone.');">
                                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center p-5 text-center text-muted">
                            <i class="bi bi-folder-x" style="font-size: 2.5rem;"></i>
                            <p class="mt-3 mb-0">You have not created any projects yet.<br>
                            <span class="small">Click <strong>Create Project</strong> to get started!</span></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create Project Modal -->
            <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form action="../actions/create_project.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addProjectModalLabel"><i class="bi bi-plus-circle me-2"></i>Create New Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="title" class="form-label">Project Title</label>
                                        <input type="text" id="title" name="title" class="form-control" placeholder="Project Title" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="deadline" class="form-label">Deadline</label>
                                        <input type="date" id="deadline" name="deadline" class="form-control" required  min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Describe your project..." required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="category" class="form-label">Category</label>
                                        <select id="category" name="category" class="form-select" required>
                                            <option value="" selected>Select Category</option>
                                            <?php
                                            $category_result->data_seek(0);
                                            while ($category = $category_result->fetch_assoc()): ?>
                                                <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="group" class="form-label">Group</label>
                                        <select id="group" name="group_id" class="form-select">
                                            <option value="">Individual</option>
                                            <?php
                                            $group_result->data_seek(0);
                                            while ($group = $group_result->fetch_assoc()): ?>
                                                <option value="<?= $group['group_id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Create Project
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Create Project Modal -->
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>
