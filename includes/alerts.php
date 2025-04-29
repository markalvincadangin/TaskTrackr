<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\alerts.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderAlert($type, $message) {
    $icon = $type === 'success'
        ? '<i class="bi bi-check-circle-fill me-2"></i>'
        : '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
    $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
    echo <<<HTML
    <div class="alert $alertClass alert-dismissible fade show d-flex align-items-center" role="alert" style="font-size: 1rem;">
        $icon
        <div>$message</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    HTML;
}

if (isset($_SESSION['success_message'])) {
    renderAlert('success', htmlspecialchars($_SESSION['success_message']));
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    renderAlert('danger', htmlspecialchars($_SESSION['error_message']));
    unset($_SESSION['error_message']);
}
?>
