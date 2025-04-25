<?php
include('../config/db.php');
include('../includes/header.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user groups
$groups_query = "
    SELECT g.group_id, g.group_name, g.created_by, u.name AS creator_name
    FROM Groups g
    JOIN User_Groups ug ON g.group_id = ug.group_id
    JOIN Users u ON g.created_by = u.user_id
    WHERE ug.user_id = ?
";

$stmt = $conn->prepare($groups_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$group_result = $stmt->get_result();
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content flex-grow-1 p-4">
        <h2 class="mb-4 text-center">Your Groups</h2>

        <!-- Display Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <!-- Display Groups -->
        <?php if ($group_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Group Name</th>
                            <th>Created By</th>
                            <th>Members</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($group = $group_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($group['group_name']) ?></td>
                                <td><?= htmlspecialchars($group['creator_name']) ?></td>
                                <td>
                                    <?php
                                    // Fetch members for this group
                                    $members_query = "
                                        SELECT u.name
                                        FROM Users u
                                        JOIN User_Groups ug ON u.user_id = ug.user_id
                                        WHERE ug.group_id = ?
                                    ";
                                    $members_stmt = $conn->prepare($members_query);
                                    $members_stmt->bind_param("i", $group['group_id']);
                                    $members_stmt->execute();
                                    $members_result = $members_stmt->get_result();

                                    while ($member = $members_result->fetch_assoc()):
                                        echo htmlspecialchars($member['name']) . "<br>";
                                    endwhile;
                                    ?>
                                </td>
                                <td>
                                    <!-- Only allow delete if current user created the group -->
                                    <?php if ($group['created_by'] == $user_id): ?>
                                        <a href="../actions/edit_group.php?group_id=<?= $group['group_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="../actions/delete_group.php?group_id=<?= $group['group_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this group?');">Delete</a>
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
            <p class="text-muted text-center">You don't belong to any groups yet.</p>
        <?php endif; ?>

        <!-- Create New Group Form -->
        <h3 class="mt-5 text-center">Create New Group</h3>
        <form action="../actions/create_group.php" method="POST" class="mt-3">
            <div class="mb-3">
                <label for="group_name" class="form-label">Group Name</label>
                <input type="text" name="group_name" id="group_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="member_email" class="form-label">Add Members (Email)</label>
                <div id="emailFields">
                    <input type="email" name="member_emails[]" id="member_emails" class="form-control mb-2" required>
                </div>
                <button type="button" id="addMemberBtn" class="btn btn-secondary btn-sm">Add More Members</button>
            </div>

            <button type="submit" class="btn btn-primary w-100">Create Group</button>
        </form>
    </div>
</div>

<script>
    // Add more email fields dynamically
    document.getElementById('addMemberBtn').addEventListener('click', function () {
        const emailFieldsContainer = document.getElementById('emailFields');
        const newEmailField = document.createElement('input');
        newEmailField.type = 'email';
        newEmailField.name = 'member_emails[]';
        newEmailField.className = 'form-control mb-2';
        newEmailField.required = true;
        emailFieldsContainer.appendChild(newEmailField);
    });
</script>

<?php include('../includes/footer.php'); ?>
