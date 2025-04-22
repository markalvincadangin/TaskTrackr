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

<main>
    <h2>Your Projects</h2>

    <?php if ($project_result->num_rows > 0): ?>
        <table border="1" cellpadding="10" cellspacing="0"> 
            <thead>
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

        <label for="group">Group:</label><br>
        <select id="group" name="group_id">
            <option value="" <?= (!isset($project['group_id']) || is_null($project['group_id'])) ? 'selected' : '' ?>>No Group</option>
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
                <option value="<?= $group['group_id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>
        <button type="submit">Create Project</button>
    </form>
</main>

<?php include('../includes/footer.php'); ?>
