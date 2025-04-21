<?php
// Include necessary files
include('../config/db.php');
include('../includes/header.php');

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

<main>
    <h2>Your Projects</h2>

    <?php
// Display success or error messages if available
if (isset($_SESSION['success_message'])) {
    echo '<p style="color: green;">' . $_SESSION['success_message'] . '</p>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<p style="color: red;">' . $_SESSION['error_message'] . '</p>';
    unset($_SESSION['error_message']);
}
?>


    <?php if ($project_result->num_rows > 0): ?>
        <table /*border="1" cellpadding="10" cellspacing="0"*/>
            <thead>
                <tr>
                    <th>Project Title</th>
                    <th>Description</th>
                    <th>Deadline</th>
                    <th>Category</th>
                    <!--<th>Actions</th> -->
                </tr>
            </thead>
            <tbody>
                <?php while ($project = $project_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($project['title']) ?></td>
                        <td><?= htmlspecialchars($project['description']) ?></td>
                        <td><?= htmlspecialchars($project['deadline']) ?></td>
                        <td><?= htmlspecialchars($project['name']) ?></td>
                        <td>
                            <!-- Edit Link -->
                            <a href="../actions/edit_project.php?project_id=<?= $project['project_id'] ?>">Edit</a> |
                            <!-- Delete Link -->
                            <a href="../actions/delete_project.php?project_id=<?= $project['project_id'] ?>" onclick="return confirm('Are you sure you want to delete this project?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have not created any projects yet.</p>
    <?php endif; ?>

    <!-- Create Project Form -->
    <h3>Create New Project</h3>
    <form action="../actions/create_project.php" method="POST">
        <label for="title">Project Title:</label><br>
        <input type="text" id="title" name="title" required><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" required></textarea><br><br>

        <label for="deadline">Deadline:</label><br>
        <input type="date" id="deadline" name="deadline" required><br><br>

        <label for="category">Category:</label><br>
        <select id="category" name="category" required>
            <option value="">Select Category</option>
            <?php while ($category = $category_result->fetch_assoc()): ?>
                <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Create Project</button>
    </form>
</main>

<?php include('../includes/footer.php'); ?>
