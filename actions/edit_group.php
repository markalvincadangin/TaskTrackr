<?php
session_start();
include('../config/db.php');
include('../includes/header.php');
include_once('../includes/email_sender.php');

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
        
        // After successful group name update
        $members_query = "SELECT u.user_id, u.email FROM Users u
                          JOIN User_Groups ug ON u.user_id = ug.user_id
                          WHERE ug.group_id = ?";
        $members_stmt = $conn->prepare($members_query);
        $members_stmt->bind_param("i", $group_id);
        $members_stmt->execute();
        $members_result = $members_stmt->get_result();

        while ($member = $members_result->fetch_assoc()) {
            $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
            $notify_stmt = $conn->prepare($notify_query);
            $message = "The group name has been changed to '" . htmlspecialchars($new_group_name) . "'.";
            $notify_stmt->bind_param("is", $member['user_id'], $message);
            $notify_stmt->execute();

            if ($member['email']) {
                $subject = "Group Name Updated";
                $body = $message;
                sendUserEmail($member['email'], $subject, $body);
            }
        }

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

                // In-app notification for new group member
                $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
                $notify_stmt = $conn->prepare($notify_query);
                $group_message = "You have been added to the group: " . htmlspecialchars($group['group_name']);
                $notify_stmt->bind_param("is", $user['user_id'], $group_message);
                $notify_stmt->execute();

                // For each $member_id added
                $email_query = "SELECT email FROM Users WHERE user_id = ?";
                $email_stmt = $conn->prepare($email_query);
                $email_stmt->bind_param("i", $user['user_id']);
                $email_stmt->execute();
                $email_result = $email_stmt->get_result();
                $user_email = $email_result->fetch_assoc()['email'] ?? null;

                if ($user_email) {
                    $subject = "Added to Group: " . htmlspecialchars($group['group_name']);
                    $body = "You have been added to the group: " . htmlspecialchars($group['group_name']) . ".";
                    sendUserEmail($user_email, $subject, $body);
                }
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
    if ($remove_user_id == $group['created_by']) {
        $_SESSION['error_message'] = "You cannot remove the group creator.";
        header("Location: edit_group.php?group_id=$group_id");
        exit();
    }

    // Count current members
    $count_stmt = $conn->prepare("SELECT COUNT(*) as member_count FROM User_Groups WHERE group_id = ?");
    $count_stmt->bind_param("i", $group_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $member_count = $count_row['member_count'];

    // Only allow removal if there will be at least 2 members left
    if ($member_count <= 2) {
        $_SESSION['error_message'] = "Cannot remove member. A group must have at least 2 members (including the creator).";
    } else {
        $remove_stmt = $conn->prepare("DELETE FROM User_Groups WHERE user_id = ? AND group_id = ?");
        $remove_stmt->bind_param("ii", $remove_user_id, $group_id);
        $remove_stmt->execute();
        $_SESSION['success_message'] = "Member removed successfully.";

        // After successful removal
        $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
        $notify_stmt = $conn->prepare($notify_query);
        $message = "You have been removed from the group: " . htmlspecialchars($group['group_name']);
        $notify_stmt->bind_param("is", $remove_user_id, $message);
        $notify_stmt->execute();

        $email_query = "SELECT email FROM Users WHERE user_id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $remove_user_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user_email = $email_result->fetch_assoc()['email'] ?? null;

        $settings_query = "SELECT email_notifications FROM User_Settings WHERE user_id = ?";
        $settings_stmt = $conn->prepare($settings_query);
        $settings_stmt->bind_param("i", $remove_user_id);
        $settings_stmt->execute();
        $settings_result = $settings_stmt->get_result();
        $email_enabled = $settings_result->fetch_assoc()['email_notifications'] ?? 0;

        if ($user_email && $email_enabled) {
            $subject = "Removed from Group: {$group['group_name']}";
            $body = "You have been removed from the group: {$group['group_name']}.";
            sendUserEmail($user_email, $subject, $body);
        }
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
        <h2 class="mb-4 text-center">Edit Group</h2>

        <?php include('../includes/alerts.php'); ?>

        <!-- Edit Group Name Card -->
        <div class="card shadow-sm p-4 mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Group Name</h5>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="group_name" class="form-label small">Edit Group Name</label>
                        <input type="text" name="group_name" id="group_name" class="form-control" value="<?= htmlspecialchars($group['group_name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="update_group" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> Update Name
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Members Card -->
        <div class="card shadow-sm p-4 mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Add Members by Email</h5>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-12" id="emailFields">
                        <div class="row g-2 mb-2 align-items-center email-input-row">
                            <div class="col flex-grow-1">
                                <input type="email" name="member_emails[]" class="form-control" placeholder="Enter member email" required>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-secondary add-email-btn" tabindex="-1" title="Add another member">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_members" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus me-1"></i> Add Members
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Members Card -->
        <div class="card shadow-sm p-4 mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Current Members</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Email</th>
                                <th class="text-center">Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset pointer and fetch again for table
                            $members_stmt->data_seek(0);
                            while ($member = $members_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($member['email']) ?></td>
                                    <td class="text-center">
                                        <?php if ($member['user_id'] == $group['created_by']): ?>
                                            <span class="badge bg-success">Creator</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Member</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($member['user_id'] != $group['created_by']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="remove_user_id" value="<?= $member['user_id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Remove" onclick="return confirm('Remove this member?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Add more email fields dynamically with Bootstrap styling
    document.addEventListener('DOMContentLoaded', function () {
        const emailFieldsContainer = document.getElementById('emailFields');
        emailFieldsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.add-email-btn')) {
                const row = document.createElement('div');
                row.className = 'row g-2 mb-2 align-items-center email-input-row';
                row.innerHTML = `
                    <div class="col flex-grow-1">
                        <input type="email" name="member_emails[]" class="form-control" placeholder="Enter member email" required>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-danger remove-email-btn" tabindex="-1" title="Remove this field">
                            <i class="bi bi-dash"></i>
                        </button>
                    </div>
                `;
                emailFieldsContainer.appendChild(row);
            }
            if (e.target.closest('.remove-email-btn')) {
                e.target.closest('.email-input-row').remove();
            }
        });
    });
</script>

<?php include('../includes/footer.php'); ?>
