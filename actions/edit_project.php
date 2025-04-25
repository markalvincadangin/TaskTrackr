<?php
// Include necessary files
include('../config/db.php');
include('../includes/header.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

// Check if project_id is provided
if (isset($_GET['project_id'])) {
    $project_id = $_GET['project_id'];
    $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

    // Fetch project details from the database
    $query = "SELECT p.*, c.name AS category_name FROM Projects p JOIN Categories c ON p.category_id = c.category_id WHERE p.project_id = ? AND p.created_by = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If project exists, fetch its details
        $project = $result->fetch_assoc();
    } else {
        // If project doesn't belong to the user or doesn't exist
        $_SESSION['error_message'] = 'You are not authorized to edit this project.';
        header("Location: /TaskTrackr/public/projects.php");
        exit();
    }
} else {
    // If no project_id is provided
    $_SESSION['error_message'] = 'Invalid project ID.';
    header("Location: /TaskTrackr/public/projects.php");
    exit();
}

// Fetch categories for the category dropdown
$category_query = "SELECT * FROM Categories";
$category_result = $conn->query($category_query);
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content flex-grow-1 p-4">
        <h2 class="mb-4 text-center">Edit Project</h2>

        <!-- Display Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="../actions/update_project.php" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">

                    <!-- Project Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Project Title</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>

                    <!-- Deadline -->
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" value="<?php echo $project['deadline']; ?>" required>
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select id="category" name="category" class="form-select" required>
                            <?php while ($category = $category_result->fetch_assoc()) { ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php if ($category['category_id'] == $project['category_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Group -->
                    <div class="mb-3">
                        <label for="group" class="form-label">Group</label>
                        <select id="group" name="group_id" class="form-select">
                            <option value="" <?= is_null($project['group_id']) ? 'selected' : '' ?>>No Group</option>
                            <?php
                                // Fetch groups the user belongs to
                                $group_query = "SELECT g.group_id, g.group_name FROM Groups g 
                                                INNER JOIN User_Groups ug ON g.group_id = ug.group_id 
                                                WHERE ug.user_id = ?";
                                $group_stmt = $conn->prepare($group_query);
                                $group_stmt->bind_param("i", $user_id);
                                $group_stmt->execute();
                                $group_result = $group_stmt->get_result();

                                while ($group = $group_result->fetch_assoc()):
                            ?>
                                <option value="<?= $group['group_id'] ?>" <?= $group['group_id'] == $project['group_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['group_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100">Update Project</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
