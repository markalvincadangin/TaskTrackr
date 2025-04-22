<?php
// Include necessary files
include('../config/db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect if not logged in
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
        header("Location: projects.php");
        exit();
    }
} else {
    // If no project_id is provided
    $_SESSION['error_message'] = 'Invalid project ID.';
    header("Location: projects.php");
    exit();
}

// Fetch categories for the category dropdown
$category_query = "SELECT * FROM Categories";
$category_result = $conn->query($category_query);
?>

<main>
    <h2>Edit Project</h2>

    <!-- Display error or success messages -->
    <?php
    if (isset($_SESSION['error_message'])) {
        echo '<p class="error">' . $_SESSION['error_message'] . '</p>';
        unset($_SESSION['error_message']);
    }
    ?>

    <form action="../actions/update_project.php" method="POST">
        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">

        <label for="title">Project Title:</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($project['description']); ?></textarea>

        <label for="deadline">Deadline:</label>
        <input type="date" id="deadline" name="deadline" value="<?php echo $project['deadline']; ?>" required>

        <label for="category">Category:</label>
        <select id="category" name="category" required>
            <?php while ($category = $category_result->fetch_assoc()) { ?>
                <option value="<?php echo $category['category_id']; ?>" <?php if ($category['category_id'] == $project['category_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php } ?>
        </select>

        <label for="group">Group:</label><br>
        <select id="group" name="group_id">
            <option value="" <?= is_null($project['group_id']) ? 'selected' : '' ?>>No Group</option>
            <?php
                 // Join Groups table to get group names
                $group_query = " SELECT g.group_id, g.group_name FROM Groups g 
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
        </select><br><br>

        <button type="submit">Update Project</button>
    </form>
</main>

<?php include('../includes/footer.php'); ?>
