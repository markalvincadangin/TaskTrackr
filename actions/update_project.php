<?php
// Include necessary files
include('../config/db.php');
include_once('../includes/email_sender.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect if not logged in
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $category_id = $_POST['category'];
    $group_id = $_POST['group_id']; 
    $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

    $group_id = empty($_POST['group_id']) ? null : $_POST['group_id']; // Set to NULL if not provided

    // Prepare SQL query to update project details
    $query = "UPDATE Projects SET title = ?, description = ?, deadline = ?, category_id = ?, group_id = ? WHERE project_id = ? AND created_by = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssiiii", $title, $description, $deadline, $category_id, $group_id, $project_id, $user_id);
    $result = $stmt->execute();

    if ($result) {
        $_SESSION['success_message'] = 'Project updated successfully.';
        
        // If project is assigned to a group, notify all group members
        if (!empty($group_id)) {
            // Get group name for the message
            $group_query = "SELECT group_name FROM Groups WHERE group_id = ?";
            $group_stmt = $conn->prepare($group_query);
            $group_stmt->bind_param("i", $group_id);
            $group_stmt->execute();
            $group_result = $group_stmt->get_result();
            $group_name = $group_result->fetch_assoc()['group_name'] ?? 'Unknown group';
            
            // Notify all group members about the updated project
            $members_query = "SELECT user_id FROM User_Groups WHERE group_id = ? AND user_id != ?";
            $members_stmt = $conn->prepare($members_query);
            $members_stmt->bind_param("ii", $group_id, $user_id);
            $members_stmt->execute();
            $members_result = $members_stmt->get_result();
            
            while ($member = $members_result->fetch_assoc()) {
                $notify_query = "INSERT INTO Notifications (
                    user_id, 
                    message, 
                    related_project_id, 
                    related_group_id, 
                    related_user_id,
                    notification_type
                ) VALUES (?, ?, ?, ?, ?, ?)";
                
                $notify_stmt = $conn->prepare($notify_query);
                $message = "Project \"" . htmlspecialchars($title) . "\" has been updated with a deadline of " . date('M j, Y', strtotime($deadline));
                $notification_type = "project_update";
                
                $notify_stmt->bind_param("isiisi", 
                    $member['user_id'], 
                    $message, 
                    $project_id, 
                    $group_id, 
                    $user_id,
                    $notification_type
                );
                $notify_stmt->execute();
    
                // Also send email notification
                $email_query = "SELECT email FROM Users WHERE user_id = ?";
                $email_stmt = $conn->prepare($email_query);
                $email_stmt->bind_param("i", $member['user_id']);
                $email_stmt->execute();
                $email_result = $email_stmt->get_result();
                $user_email = $email_result->fetch_assoc()['email'] ?? null;
    
                if ($user_email) {
                    $subject = "Project Updated: $title";
                    $body = "Project \"$title\" in group \"$group_name\" has been updated.\n\n"
                          . "Deadline: " . date('F j, Y', strtotime($deadline)) . "\n\n"
                          . "Description: $description";
                    sendUserEmail($user_email, $subject, $body);
                }
            }
        }
        
        header("Location: /TaskTrackr/public/projects.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Failed to update project.';
        header("Location: /TaskTrackr/actions/edit_project.php?project_id=" . $project_id);
        exit();
    }
}
?>
