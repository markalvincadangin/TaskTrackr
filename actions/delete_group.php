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
    // 1. Get all project IDs in this group
    $project_ids = [];
    $project_query = "SELECT project_id FROM Projects WHERE group_id = ?";
    $project_stmt = $conn->prepare($project_query);
    $project_stmt->bind_param("i", $group_id);
    $project_stmt->execute();
    $project_result = $project_stmt->get_result();
    while ($row = $project_result->fetch_assoc()) {
        $project_ids[] = $row['project_id'];
    }

    // 2. Delete all tasks in those projects
    if (!empty($project_ids)) {
        $in = implode(',', array_fill(0, count($project_ids), '?'));
        $types = str_repeat('i', count($project_ids));
        $delete_tasks_sql = "DELETE FROM Tasks WHERE project_id IN ($in)";
        $delete_tasks_stmt = $conn->prepare($delete_tasks_sql);
        $delete_tasks_stmt->bind_param($types, ...$project_ids);
        $delete_tasks_stmt->execute();

        // 3. Delete the projects
        $delete_projects_sql = "DELETE FROM Projects WHERE project_id IN ($in)";
        $delete_projects_stmt = $conn->prepare($delete_projects_sql);
        $delete_projects_stmt->bind_param($types, ...$project_ids);
        $delete_projects_stmt->execute();
    }

    // 4. Delete from User_Groups
    $delete_user_groups = "DELETE FROM User_Groups WHERE group_id = ?";
    $stmt2 = $conn->prepare($delete_user_groups);
    $stmt2->bind_param("i", $group_id);
    $stmt2->execute();

    // 5. Delete the group
    $delete_group = "DELETE FROM Groups WHERE group_id = ?";
    $stmt3 = $conn->prepare($delete_group);
    $stmt3->bind_param("i", $group_id);
    $stmt3->execute();

    $conn->commit();
    $_SESSION['success_message'] = "Group and all related projects and tasks deleted successfully.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Failed to delete group: " . $e->getMessage();
}

header("Location: ../public/groups.php");
exit();
