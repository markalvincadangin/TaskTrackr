<?php
include(__DIR__ . '/../config/db.php');
include_once(__DIR__ . '/../includes/email_sender.php');

// Fetch all users who have tasks assigned to them
$user_query = "SELECT DISTINCT assigned_to FROM Tasks WHERE assigned_to IS NOT NULL";
$user_result = $conn->query($user_query);

while ($user_row = $user_result->fetch_assoc()) {
    $user_id = $user_row['assigned_to'];

    // Fetch user's reminder_days_before setting (default to 1 if not set)
    $settings_query = "SELECT reminder_days_before FROM User_Settings WHERE user_id = ?";
    $settings_stmt = $conn->prepare($settings_query);
    $settings_stmt->bind_param("i", $user_id);
    $settings_stmt->execute();
    $settings_result = $settings_stmt->get_result();
    $settings = $settings_result->fetch_assoc();
    $remind_days_before = $settings['reminder_days_before'] ?? 1;

    // Get user email
    $email_query = "SELECT email FROM Users WHERE user_id = ?";
    $email_stmt = $conn->prepare($email_query);
    $email_stmt->bind_param("i", $user_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $email = $email_result->fetch_assoc()['email'] ?? null;

    // Calculate the date for which to check tasks
    $check_date = date('Y-m-d', strtotime("+{$remind_days_before} days"));

    // Fetch tasks for this user that are due on the check_date
    $tasks_query = "
        SELECT t.*, p.title as project_title, p.project_id
        FROM Tasks t 
        LEFT JOIN Projects p ON t.project_id = p.project_id
        WHERE t.assigned_to = ? 
        AND t.due_date = ? 
        AND (t.last_reminder_sent IS NULL OR t.last_reminder_sent != CURDATE())
        AND t.status != 'Done'
    ";
    $tasks_stmt = $conn->prepare($tasks_query);
    $tasks_stmt->bind_param("is", $user_id, $check_date);
    $tasks_stmt->execute();
    $tasks_result = $tasks_stmt->get_result();

    // For each task found, send a reminder
    while ($task = $tasks_result->fetch_assoc()) {
        // Update the last_reminder_sent field
        $update_query = "UPDATE Tasks SET last_reminder_sent = CURDATE() WHERE task_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $task['task_id']);
        $update_stmt->execute();

        // Format a friendly date for display
        $friendly_date = date('M j, Y', strtotime($task['due_date']));
        $days_text = $remind_days_before == 1 ? "tomorrow" : "in {$remind_days_before} days";

        // Create in-app notification
        $notify_query = "INSERT INTO Notifications (
            user_id, 
            message, 
            related_task_id, 
            related_project_id,
            notification_type
        ) VALUES (?, ?, ?, ?, ?)";
        
        $notify_stmt = $conn->prepare($notify_query);
        $message = "⏰ REMINDER: Task \"" . htmlspecialchars($task['title']) . "\" is due {$days_text} ({$friendly_date})";
        $notification_type = "reminder";
        
        $notify_stmt->bind_param("isiis", 
            $user_id, 
            $message, 
            $task['task_id'], 
            $task['project_id'],
            $notification_type
        );
        $notify_stmt->execute();

        // Send email if email is available
        if ($email) {
            $subject = "Reminder: Task due {$days_text}";
            $body = "⏰ REMINDER: Your task \"{$task['title']}\" is due {$days_text} ({$friendly_date}).\n\n";
            
            if (!empty($task['project_title'])) {
                $body .= "Project: {$task['project_title']}\n";
            }
            
            $body .= "Priority: {$task['priority']}\n"
                  . "Description: {$task['description']}\n\n"
                  . "Please complete this task before the deadline.";
                  
            sendUserEmail($email, $subject, $body);
        }
    }

    // Also check for overdue tasks (tasks where due_date < CURDATE() and status is not 'Done' or 'Overdue')
    $overdue_query = "
        SELECT t.*, p.title as project_title, p.project_id 
        FROM Tasks t 
        LEFT JOIN Projects p ON t.project_id = p.project_id
        WHERE t.assigned_to = ? 
        AND t.due_date < CURDATE() 
        AND t.status NOT IN ('Done', 'Overdue')
        AND (t.last_reminder_sent IS NULL OR t.last_reminder_sent != CURDATE())
    ";
    $overdue_stmt = $conn->prepare($overdue_query);
    $overdue_stmt->bind_param("i", $user_id);
    $overdue_stmt->execute();
    $overdue_result = $overdue_stmt->get_result();

    // For each overdue task, send a notification and update the status
    while ($task = $overdue_result->fetch_assoc()) {
        // Update the task status to 'Overdue'
        $status_query = "UPDATE Tasks SET status = 'Overdue', last_reminder_sent = CURDATE() WHERE task_id = ?";
        $status_stmt = $conn->prepare($status_query);
        $status_stmt->bind_param("i", $task['task_id']);
        $status_stmt->execute();

        // Format a friendly date for display
        $friendly_date = date('M j, Y', strtotime($task['due_date']));
        $days_overdue = floor((strtotime('now') - strtotime($task['due_date'])) / (60 * 60 * 24));
        $days_text = $days_overdue == 1 ? "1 day ago" : "$days_overdue days ago";

        // Create in-app notification
        $notify_query = "INSERT INTO Notifications (
            user_id, 
            message, 
            related_task_id, 
            related_project_id,
            notification_type
        ) VALUES (?, ?, ?, ?, ?)";
        
        $notify_stmt = $conn->prepare($notify_query);
        $message = "❗ OVERDUE: Task \"" . htmlspecialchars($task['title']) . "\" was due {$days_text} ({$friendly_date})";
        $notification_type = "reminder";
        
        $notify_stmt->bind_param("isiis", 
            $user_id, 
            $message, 
            $task['task_id'], 
            $task['project_id'],
            $notification_type
        );
        $notify_stmt->execute();

        // Send email if email is available
        if ($email) {
            $subject = "OVERDUE: Task was due {$days_text}";
            $body = "❗ OVERDUE: Your task \"{$task['title']}\" was due {$days_text} ({$friendly_date}).\n\n";
            
            if (!empty($task['project_title'])) {
                $body .= "Project: {$task['project_title']}\n";
            }
            
            $body .= "Priority: {$task['priority']}\n"
                  . "Description: {$task['description']}\n\n"
                  . "Please address this task as soon as possible.";
                  
            sendUserEmail($email, $subject, $body);
        }
    }
}

echo "Task reminders sent successfully.";
?>