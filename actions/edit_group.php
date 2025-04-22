<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
    echo "No group specified.";
    exit();
}

$group_id = $_GET['group_id'];

// Fetch group details
$group_query = "SELECT * FROM Groups WHERE group_id = ?";
$stmt = $conn->prepare($group_query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();

if ($group_result->num_rows === 0) {
    echo "Group not found.";
    exit();
}

$group = $group_result->fetch_assoc();

if ($group['created_by'] != $user_id) {
    echo "You are not authorized to edit this group.";
    exit();
}

// Update group name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group'])) {
    $new_group_name = trim($_POST['group_name']);
    if (!empty($new_group_name)) {
        $update_query = "UPDATE Groups SET group_name = ? WHERE group_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_group_name, $group_id);
        $update_stmt->execute();
        $_SESSION['success_message'] = "Group name updated successfully.";
        header("Location: edit_group.php?group_id=$group_id");
        exit();
    }
}

// Add members
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_members'])) {
    $member_emails = $_POST['member_emails'];
    $added_members = [];
    $invalid_members = [];

    $insert_stmt = $conn->prepare("INSERT INTO User_Groups (user_id, group_id) VALUES (?, ?)");

    foreach ($member_emails as $email) {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid_members[] = $email;
            continue;
        }

        $user_stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        $user_stmt->bind_param("s", $email);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();

        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();

            // Check if already in group
            $check_stmt = $conn->prepare("SELECT * FROM User_Groups WHERE user_id = ? AND group_id = ?");
            $check_stmt->bind_param("ii", $user['user_id'], $group_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                $insert_stmt->bind_param("ii", $user['user_id'], $group_id);
                $insert_stmt->execute();
                $added_members[] = $email;
            }
        } else {
            $invalid_members[] = $email;
        }
    }

    $_SESSION['success_message'] = "Members added: " . implode(", ", $added_members);
    if (!empty($invalid_members)) {
        $_SESSION['error_message'] = "These emails were invalid or not found: " . implode(", ", $invalid_members);
    }

    header("Location: edit_group.php?group_id=$group_id");
    exit();
}

// Remove member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user_id'])) {
    $remove_user_id = $_POST['remove_user_id'];

    // Prevent removing the creator
    if ($remove_user_id != $group['created_by']) {
        $remove_stmt = $conn->prepare("DELETE FROM User_Groups WHERE user_id = ? AND group_id = ?");
        $remove_stmt->bind_param("ii", $remove_user_id, $group_id);
        $remove_stmt->execute();
        $_SESSION['success_message'] = "Member removed successfully.";
    } else {
        $_SESSION['error_message'] = "You cannot remove the group creator.";
    }

    header("Location: edit_group.php?group_id=$group_id");
    exit();
}

// Fetch current members
$members_stmt = $conn->prepare("
    SELECT u.user_id, u.email
    FROM Users u
    JOIN User_Groups ug ON u.user_id = ug.user_id
    WHERE ug.group_id = ?
");
$members_stmt->bind_param("i", $group_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();
?>

<main>
    <h2>Edit Group</h2>

    <?php include('../includes/alerts.php'); ?>
    
    <button onclick="window.location.href='../public/groups.php'">Back</button><br><br>

    <!-- Edit Group Name -->
    <form method="POST">
        <label for="group_name">Group Name:</label><br>
        <input type="text" name="group_name" value="<?= htmlspecialchars($group['group_name']) ?>" required><br><br>
        <button type="submit" name="update_group">Update Group Name</button>
    </form>

    <hr>

    <!-- Add Members -->
    <h3>Add Members by Email</h3>
    <form method="POST">
        <div id="emailFields">
            <input type="email" name="member_emails[]" required><br>
        </div>
        <button type="button" id="addMemberBtn">Add More</button><br><br>
        <button type="submit" name="add_members">Add Members</button>
    </form>

    <hr>

    <!-- Current Members -->
    <h3>Current Members</h3>
    <ul>
        <?php while ($member = $members_result->fetch_assoc()): ?>
            <li>
                <?= htmlspecialchars($member['email']) ?>
                <?php if ($member['user_id'] != $group['created_by']): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="remove_user_id" value="<?= $member['user_id'] ?>">
                        <button type="submit" onclick="return confirm('Remove this member?')">Remove</button>
                    </form>
                <?php else: ?>
                    (Creator)
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>
</main>

<script src="../assets/js/add_group_members.js"></script>

<?php include('../includes/footer.php'); ?>
