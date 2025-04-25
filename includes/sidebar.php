<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\sidebar.php -->
<aside class="sidebar bg-dark text-white vh-100 position-fixed" style="width: 240px;">
    <div class="d-flex flex-column py-4 px-3 h-100">
        <!-- Logo -->
        <a href="/TaskTrackr/public/dashboard.php" class="text-white text-decoration-none mb-4 text-center">
            <h3>TaskTrackr</h3>
        </a>

        <!-- Navigation Links -->
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="/TaskTrackr/public/dashboard.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page === 'dashboard.php' ? 'bg-primary text-white fw-bold' : ''; ?>">
                    <i class="bi bi-house-door me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/tasks.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page === 'tasks.php' ? 'bg-primary text-white fw-bold' : ''; ?>">
                    <i class="bi bi-list-task me-2"></i> My Tasks
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/projects.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page === 'projects.php' ? 'bg-primary text-white fw-bold' : ''; ?>">
                    <i class="bi bi-folder me-2"></i> My Projects
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/groups.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page === 'groups.php' ? 'bg-primary text-white fw-bold' : ''; ?>">
                    <i class="bi bi-people me-2"></i> My Groups
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/settings.php" class="nav-link text-white d-flex align-items-center <?php echo $current_page === 'settings.php' ? 'bg-primary text-white fw-bold' : ''; ?>">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a href="/TaskTrackr/actions/logout.php" class="nav-link text-danger d-flex align-items-center">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</aside>