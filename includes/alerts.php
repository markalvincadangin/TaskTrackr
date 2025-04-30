<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\alerts.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderAlert($type, $message) {
    $icon = $type === 'success'
        ? '<i class="bi bi-check-circle-fill me-2"></i>'
        : '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
    $alertClass = $type === 'success' ? 'alert-success border-success shadow-sm' : 'alert-danger border-danger shadow-sm';
    echo <<<HTML
    <div class="alert $alertClass alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert" style="font-size: 1rem;">
        $icon
        <div class="flex-grow-1">$message</div>
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
