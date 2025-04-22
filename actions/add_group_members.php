<?php
session_start();
include('../config/db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if group ID is passed in the query string
if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
    echo "No group specified.";
    exit();
}

$group_id = $_GET['group_id'];

// Fetch group details to make sure the user is authorized to add members
$group_check_query = "SELECT created_by FROM Groups WHERE group_id = ?";
$group_check_stmt = $conn->prepare($group_check_query);
$group_check_stmt->bind_param("i", $group_id);
$group_check_stmt->execute();
$group_check_result = $group_check_stmt->get_result();

if ($group_check_result->num_rows == 0) {
    echo "Group not found.";
    exit();
}

$group_data = $group_check_result->fetch_assoc();

// Ensure the current user is the creator of the group
if ($group_data['created_by'] != $user_id) {
    echo "You are not authorized to add members to this group.";
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_emails = $_POST['member_emails'];

    // Validate emails and check if users exist
    $valid_emails = [];
    $invalid_emails = [];
    $user_ids = [];

    foreach ($member_emails as $email) {
        // Trim and sanitize email input
        $email = trim($email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check if user exists with this email
            $email_query = "SELECT user_id FROM Users WHERE email = ?";
            $email_stmt = $conn->prepare($email_query);
            $email_stmt->bind_param("s", $email);
            $email_stmt->execute();
            $email_result = $email_stmt->get_result();

            if ($email_result->num_rows > 0) {
                // User exists, add the user_id to the valid list
                $user = $email_result->fetch_assoc();
                $valid_emails[] = $email;
                $user_ids[] = $user['user_id'];
            } else {
                // User does not exist
                $invalid_emails[] = $email;
            }
        } else {
            // Invalid email format
            $invalid_emails[] = $email;
        }
    }

    // If there are invalid emails, display them
    if (count($invalid_emails) > 0) {
        $_SESSION['error_message'] = "The following email addresses are invalid or do not exist: " . implode(", ", $invalid_emails);
        header("Location: add_group_members.php?group_id=$group_id");
        exit();
    }

    // Add the valid members to the group
    $user_group_query = "INSERT INTO User_Groups (user_id, group_id) VALUES (?, ?)";
    $user_group_stmt = $conn->prepare($user_group_query);

    foreach ($user_ids as $member_id) {
        $user_group_stmt->bind_param("ii", $member_id, $group_id);
        $user_group_stmt->execute();
    }

    $_SESSION['success_message'] = "Members added successfully.";
    header("Location: ../public/groups.php");
    exit();
}
?>

<main>
    <h2>Add Members to Group</h2>

    <?php include ('../includes/alerts.php'); // Display success or error messages ?>

    <form action="add_group_members.php?group_id=<?= $group_id ?>" method="POST">
        <label for="member_email">Add Members (Email):</label><br>
        <div id="emailFields">
            <input type="email" name="member_emails[]" id="member_emails" required><br>
        </div>
        
        <button type="button" id="addMemberBtn">Add More Members</button><br><br>
        <button type="submit">Submit</button>
    </form>
</main>

<script>
    // Function to add a new email input field
    document.getElementById('addMemberBtn').addEventListener('click', function() {
        const emailFieldsContainer = document.getElementById('emailFields');
        
        // Create a new input element
        const newEmailField = document.createElement('input');
        newEmailField.type = 'email';
        newEmailField.name = 'member_emails[]'; // Add member_emails[] to handle multiple emails
        emailFieldsContainer.appendChild(newEmailField);
        
        // Add a line break for better formatting
        emailFieldsContainer.appendChild(document.createElement('br'));
    });
</script>

<?php include('../includes/footer.php'); ?>
