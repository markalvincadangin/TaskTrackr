<?php
session_start();
include('../config/db.php');
include('../includes/header.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if group ID is provided
if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
    $_SESSION['error_message'] = "No group specified.";
    header("Location: ../public/groups.php");
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
    $_SESSION['error_message'] = "Group not found.";
    header("Location: ../public/groups.php");
    exit();
}

$group = $group_result->fetch_assoc();

// Ensure the user is the creator of the group
if ($group['created_by'] != $user_id) {
    $_SESSION['error_message'] = "You are not authorized to edit this group.";
    header("Location: ../public/groups.php");
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

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content flex-grow-1 p-4">
        <h2 class="mb-4 text-center">Edit Group: <?= htmlspecialchars($group['group_name']) ?></h2>

        <!-- Display Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <!-- Edit Group Name -->
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for=group_name" class="form-label">Group Name</label>
                <input type="text" name="group_name" id="group_name" class="form-control" value="<?= htmlspecialchars($group['group_name']) ?>" required>
            </div>
            <button type="submit" name="update_group" class="btn btn-primary">Update Group Name</button>
        </form>

        <hr>

        <!-- Add Members -->
        <h3 class="mt-4">Add Members by Email</h3>
        <form method="POST" class="mb-4">
            <div id="emailFields" class="mb-3">
                <input type="email" name="member_emails[]" class="form-control mb-2" placeholder="Enter member email" required>
            </div>
            <button type="button" id="addMemberBtn" class="btn btn-secondary btn-sm">Add More Field</button><br>
            <button type="submit" name="add_members" class="btn btn-primary mt-3">Add Members</button>
        </form>

        <hr>

        <!-- Current Members -->
        <h3 class="mt-4">Current Members</h3>
        <ul class="list-group">
            <?php while ($member = $members_result->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($member['email']) ?>
                    <?php if ($member['user_id'] != $group['created_by']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="remove_user_id" value="<?= $member['user_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this member?')">Remove</button>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-success">Creator</span>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
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
        newEmailField.placeholder = 'Enter member email';
        newEmailField.required = true;
        emailFieldsContainer.appendChild(newEmailField);
    });
</script>

<?php include('../includes/footer.php'); ?>
