<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\footer.php -->
<footer class="footer bg-light py-3 mt-auto">
    <div class="container text-center">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> TaskTrackr. All rights reserved.</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const collapseBtn = document.getElementById('sidebarCollapse');
    const sidebarToggle = document.getElementById('sidebarToggle');
    let backdrop = document.getElementById('sidebar-backdrop');

    // --- Restore Sidebar State from localStorage ---
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
        const icon = collapseBtn?.querySelector('i');
        if (icon) {
            icon.classList.remove('bi-chevron-double-left');
            icon.classList.add('bi-chevron-double-right');
        }
    }

    // --- Collapsible Sidebar (Desktop) ---
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            const icon = collapseBtn.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('bi-chevron-double-left');
                icon.classList.add('bi-chevron-double-right');
                document.body.classList.add('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', 'true');
            } else {
                icon.classList.remove('bi-chevron-double-right');
                icon.classList.add('bi-chevron-double-left');
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', 'false');
            }
            updateSidebarTooltips();
        });
    }

    // --- Tooltips for Collapsed Sidebar ---
    function updateSidebarTooltips() {
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            const text = link.querySelector('.sidebar-text');
            if (sidebar.classList.contains('collapsed')) {
                link.setAttribute('title', text ? text.textContent.trim() : '');
            } else {
                link.removeAttribute('title');
            }
        });
    }
    updateSidebarTooltips();

    // --- Responsive Mobile Sidebar ---
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'sidebar-backdrop';
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
    }
    function openSidebar() {
        sidebar.classList.add('active');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('active');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
    backdrop.addEventListener('click', closeSidebar);
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
});
</script>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:2000;background:rgba(255,255,255,0.7);align-items:center;justify-content:center;">
    <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show loading overlay on any form submit
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        });
    });
});
</script>

</body>
</html>
