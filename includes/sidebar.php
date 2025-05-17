<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\sidebar.php -->
<aside id="sidebar" class="sidebar">
    <button id="sidebarCollapse" class="sidebar-toggle-btn btn btn-outline-light" type="button" aria-label="Collapse sidebar">
        <i class="bi bi-chevron-double-left"></i>
    </button>
    <div class="d-flex flex-column py-4 px-3 h-100">
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="/TaskTrackr/public/dashboard.php" class="nav-link d-flex align-items-center <?php echo $current_page === 'dashboard.php' ? 'bg-primary active' : ''; ?>" aria-label="Dashboard">
                    <i class="bi bi-house-door me-2"></i> <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/tasks.php" class="nav-link d-flex align-items-center <?php echo $current_page === 'tasks.php' ? 'bg-primary active' : ''; ?>" aria-label="My Tasks">
                    <i class="bi bi-list-task me-2"></i> <span class="sidebar-text">My Tasks</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/projects.php" class="nav-link d-flex align-items-center <?php echo $current_page === 'projects.php' ? 'bg-primary active' : ''; ?>" aria-label="My Projects">
                    <i class="bi bi-folder me-2"></i> <span class="sidebar-text">My Projects</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/groups.php" class="nav-link d-flex align-items-center <?php echo $current_page === 'groups.php' ? 'bg-primary active' : ''; ?>" aria-label="My Groups">
                    <i class="bi bi-people me-2"></i> <span class="sidebar-text">My Groups</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/TaskTrackr/public/settings.php" class="nav-link d-flex align-items-center <?php echo $current_page === 'settings.php' ? 'bg-primary active' : ''; ?>" aria-label="Settings">
                    <i class="bi bi-gear me-2"></i> <span class="sidebar-text">Settings</span>
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a href="/TaskTrackr/actions/logout.php" class="nav-link text-danger d-flex align-items-center" aria-label="Logout">
                    <i class="bi bi-box-arrow-right me-2"></i> <span class="sidebar-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</aside>