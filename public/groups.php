<?php
include('../config/db.php');
session_start();
include('../includes/header.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$allowed_sort = [
    'group_name' => 'g.group_name',
    'creator' => 'u.first_name'
];
$sort_by = $_GET['sort_by'] ?? 'group_name';
$sort_dir = strtolower($_GET['sort_dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';
$order_by = $allowed_sort[$sort_by] ?? 'g.group_name';

// Fetch user groups
$groups_query = "
    SELECT g.group_id, g.group_name, g.created_by, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
    FROM Groups g
    JOIN User_Groups ug ON g.group_id = ug.group_id
    JOIN Users u ON g.created_by = u.user_id
    WHERE ug.user_id = ?
    ORDER BY $order_by $sort_dir
";
$stmt = $conn->prepare($groups_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$group_result = $stmt->get_result();
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <!-- Section Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Your Groups</h2>
                <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <i class="bi bi-plus-circle me-2"></i> Create Group
                </button>
            </div>

            <!-- Sorting Filter -->
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="sort_by" class="form-label mb-0">Sort by:</label>
                    </div>
                    <div class="col-auto">
                        <select name="sort_by" id="sort_by" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="group_name" <?= ($_GET['sort_by'] ?? '') === 'group_name' ? 'selected' : '' ?>>Group Name</option>
                            <option value="creator" <?= ($_GET['sort_by'] ?? '') === 'creator' ? 'selected' : '' ?>>Created By</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="sort_dir" id="sort_dir" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="asc" <?= ($_GET['sort_dir'] ?? '') === 'asc' ? 'selected' : '' ?>>Ascending</option>
                            <option value="desc" <?= ($_GET['sort_dir'] ?? '') === 'desc' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Group List Section -->
            <div class="card shadow-sm p-4 mb-4">
                <div class="card-body p-0">
                    <h4 class="card-title p-4 pb-0 mb-0">Group List</h4>
                    <?php if ($group_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Group Name</th>
                                        <th>Created By</th>
                                        <th>Members</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($group = $group_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($group['group_name']) ?></td>
                                            <td><?= htmlspecialchars($group['creator_name']) ?></td>
                                            <td>
                                                <?php
                                                // Fetch members for this group
                                                $members_query = "
                                                    SELECT CONCAT(u.first_name, ' ', u.last_name) AS name
                                                    FROM Users u
                                                    JOIN User_Groups ug ON u.user_id = ug.user_id
                                                    WHERE ug.group_id = ?";
                                                $members_stmt = $conn->prepare($members_query);
                                                $members_stmt->bind_param("i", $group['group_id']);
                                                $members_stmt->execute();
                                                $members_result = $members_stmt->get_result();

                                                $member_names = [];
                                                while ($member = $members_result->fetch_assoc()) {
                                                    $member_names[] = htmlspecialchars($member['name']);
                                                }
                                                ?>
                                                <ul class="mb-0 ps-3">
                                                    <?php foreach ($member_names as $name): ?>
                                                        <li><?= $name ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                <?php if ($group['created_by'] == $user_id): ?>
                                                    <a href="../actions/edit_group.php?group_id=<?= $group['group_id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="../actions/delete_group.php?group_id=<?= $group['group_id'] ?>"
                                                       class="btn btn-danger btn-sm"
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this group? All projects and tasks in this group will also be deleted. This action cannot be undone.');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center p-5 text-center text-muted">
                            <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                            <p class="mt-3 mb-0">You don't belong to any groups yet.<br>
                            <span class="small">Click <strong>Create Group</strong> to get started!</span></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create Group Modal -->
            <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form action="../actions/create_group.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title" id="createGroupModalLabel"><i class="bi bi-plus-circle me-2"></i>Create New Group</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="group_name" class="form-label">Group Name</label>
                                        <input type="text" name="group_name" id="group_name" class="form-control" placeholder="Enter group name" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Add Members (Email)</label>
                                        <div id="emailFields" class="mb-2">
                                            <div class="input-group mb-2">
                                                <input type="email" name="member_emails[]" class="form-control" placeholder="Enter member email" required>
                                                <button type="button" class="btn btn-outline-secondary add-email-btn" tabindex="-1" title="Add another member">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Create Group
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Create Group Modal -->
        </div>
    </main>
</div>

<script>
    // Add more email fields dynamically with Bootstrap styling
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.add-email-btn').forEach(function(btn) {
            btn.addEventListener('click', function () {
                const emailFieldsContainer = document.getElementById('emailFields');
                const newInputGroup = document.createElement('div');
                newInputGroup.className = 'input-group mb-2';
                newInputGroup.innerHTML = `
                    <input type="email" name="member_emails[]" class="form-control" placeholder="Enter member email" required>
                    <button type="button" class="btn btn-outline-danger remove-email-btn" tabindex="-1" title="Remove this member">
                        <i class="bi bi-dash"></i>
                    </button>
                `;
                emailFieldsContainer.appendChild(newInputGroup);

                // Add remove event
                newInputGroup.querySelector('.remove-email-btn').addEventListener('click', function () {
                    newInputGroup.remove();
                });
            });
        });

        // Delegate remove button for initial field (if user adds more)
        document.getElementById('emailFields').addEventListener('click', function(e) {
            if (e.target.closest('.remove-email-btn')) {
                e.target.closest('.input-group').remove();
            }
        });
    });
</script>

<?php include('../includes/footer.php'); ?>
