<?php
session_start();
include('../config/db.php');
include_once('../includes/email_sender.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate group_id
if (!isset($_GET['group_id']) || !is_numeric($_GET['group_id'])) {
    $_SESSION['error_message'] = "Invalid group ID.";
    header("Location: ../public/groups.php"); // adjust if different
    exit();
}

$group_id = (int) $_GET['group_id'];

// Check if the user is the creator of the group
$check_query = "SELECT * FROM Groups WHERE group_id = ? AND created_by = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "You are not authorized to delete this group.";
    header("Location: ../public/groups.php");
    exit();
}

// Before deleting the group
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
    $message = "The group you belonged to has been deleted.";
    $notify_stmt->bind_param("is", $member['user_id'], $message);
    $notify_stmt->execute();

    if ($member['email']) {
        $subject = "Group Deleted";
        $body = $message;
        sendUserEmail($member['email'], $subject, $body);
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Set group_id to NULL in related Projects (if allowed by foreign key)
    $nullify_projects = "UPDATE Projects SET group_id = NULL WHERE group_id = ?";
    $stmt1 = $conn->prepare($nullify_projects);
    $stmt1->bind_param("i", $group_id);
    $stmt1->execute();

    // 2. Delete from User_Groups
    $delete_user_groups = "DELETE FROM User_Groups WHERE group_id = ?";
    $stmt2 = $conn->prepare($delete_user_groups);
    $stmt2->bind_param("i", $group_id);
    $stmt2->execute();

    // 3. Delete from Groups
    $delete_group = "DELETE FROM Groups WHERE group_id = ?";
    $stmt3 = $conn->prepare($delete_group);
    $stmt3->bind_param("i", $group_id);
    $stmt3->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['success_message'] = "Group deleted successfully.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Failed to delete group: " . $e->getMessage();
}

header("Location: ../public/groups.php");
exit();
