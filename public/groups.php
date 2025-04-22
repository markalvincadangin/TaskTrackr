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

<main>
    <h2>Your Groups</h2>

    <?php if ($group_result->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <thead>
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
                                <a href="../actions/edit_group.php?group_id=<?= $group['group_id'] ?>">Edit</a> |
                                <a href="../actions/delete_group.php?group_id=<?= $group['group_id'] ?>" onclick="return confirm('Are you sure you want to delete this group?');">Delete</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You don't belong to any groups yet.</p>
    <?php endif; ?>

    <h3>Create New Group</h3>
    <form action="../actions/create_group.php" method="POST">
        <label for="group_name">Group Name:</label><br>
        <input type="text" name="group_name" id="group_name" required><br><br>

        <label for="member_email">Add Members (Email):</label><br>
        <div id="emailFields">
            <input type="email" name="member_emails[]" id="member_emails" required><br>
        </div>
        
        <button type="button" id="addMemberBtn">Add More Members</button><br><br>

        <button type="submit">Create Group</button>
    </form>
</main>

<script src="../assets/js/add_group_members.js"></script>

<?php include('../includes/footer.php'); ?>
