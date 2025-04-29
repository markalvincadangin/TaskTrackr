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
    $user_email = $email_result->fetch_assoc()['email'] ?? null;

    // --- UPCOMING TASKS ---
    $upcoming_query = "
        SELECT t.task_id, t.title, t.due_date, t.last_reminder_sent
        FROM Tasks t
        WHERE t.assigned_to = ?
          AND t.due_date > CURDATE()
          AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND t.status != 'Done'
    ";
    $upcoming_stmt = $conn->prepare($upcoming_query);
    $upcoming_stmt->bind_param("ii", $user_id, $remind_days_before);
    $upcoming_stmt->execute();
    $upcoming_result = $upcoming_stmt->get_result();

    while ($task = $upcoming_result->fetch_assoc()) {
        // Prevent duplicate reminders
        if ($task['last_reminder_sent'] === date('Y-m-d')) continue;

        $due_date = new DateTime($task['due_date']);
        $today = new DateTime();
        $interval = $today->diff($due_date);
        $days_left = (int)$interval->format('%a');

        if ($days_left === 1) {
            $message = "Reminder: Your task '{$task['title']}' is due tomorrow (" . $task['due_date'] . ").";
        } elseif ($days_left === 0) {
            $message = "Reminder: Your task '{$task['title']}' is due today (" . $task['due_date'] . ").";
        } else {
            $message = "Reminder: Your task '{$task['title']}' is due in {$days_left} days (" . $task['due_date'] . ").";
        }

        // In-app notification
        $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
        $notify_stmt = $conn->prepare($notify_query);
        $notify_stmt->bind_param("is", $user_id, $message);
        $notify_stmt->execute();

        // Email notification
        if ($user_email) {
            $subject = "Task Reminder: {$task['title']}";
            $body = $message;
            sendUserEmail($user_email, $subject, $body);
        }

        // Update last_reminder_sent
        $update_stmt = $conn->prepare("UPDATE Tasks SET last_reminder_sent = CURDATE() WHERE task_id = ?");
        $update_stmt->bind_param("i", $task['task_id']);
        $update_stmt->execute();
    }

    // --- OVERDUE TASKS ---
    $overdue_query = "
        SELECT t.task_id, t.title, t.due_date, t.last_reminder_sent
        FROM Tasks t
        WHERE t.assigned_to = ?
          AND t.due_date < CURDATE()
          AND t.status != 'Done'
    ";
    $overdue_stmt = $conn->prepare($overdue_query);
    $overdue_stmt->bind_param("i", $user_id);
    $overdue_stmt->execute();
    $overdue_result = $overdue_stmt->get_result();

    while ($task = $overdue_result->fetch_assoc()) {
        // Prevent duplicate reminders
        if ($task['last_reminder_sent'] === date('Y-m-d')) continue;

        $message = "Alert: Your task '{$task['title']}' is overdue (was due {$task['due_date']}).";

        // In-app notification
        $notify_query = "INSERT INTO Notifications (user_id, message) VALUES (?, ?)";
        $notify_stmt = $conn->prepare($notify_query);
        $notify_stmt->bind_param("is", $user_id, $message);
        $notify_stmt->execute();

        // Email notification
        if ($user_email) {
            $subject = "Task Overdue: {$task['title']}";
            $body = $message;
            sendUserEmail($user_email, $subject, $body);
        }

        // Update last_reminder_sent
        $update_stmt = $conn->prepare("UPDATE Tasks SET last_reminder_sent = CURDATE() WHERE task_id = ?");
        $update_stmt->bind_param("i", $task['task_id']);
        $update_stmt->execute();
    }
}

echo "Reminders processed.";
?>