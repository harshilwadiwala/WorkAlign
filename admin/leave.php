<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../employee/dashboard.php');
}

$conn = connectDB();

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $leave_id = sanitizeInput($_POST['leave_id']);
    $action = sanitizeInput($_POST['action']);
    $admin_comments = sanitizeInput($_POST['admin_comments']);
    $admin_employee_id = $_SESSION['employee_id'];
    
    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } else {
        $status = 'pending';
    }
    
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, admin_comments = ?, approved_by = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $admin_comments, $admin_employee_id, $leave_id);
    
    if ($stmt->execute()) {
        $success = "Leave request " . $status . " successfully!";
    } else {
        $error = "Failed to update leave request. Please try again.";
    }
    
    $stmt->close();
}

// Get leave requests with search and pagination
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitizeInput($_GET['status_filter']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ? OR lr.leave_type LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $where_clause .= " AND lr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Get total records
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM leave_requests lr JOIN employees e ON lr.employee_id = e.employee_id $where_clause");
if (!empty($params)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get leave requests
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare("SELECT lr.*, e.first_name, e.last_name, e.employee_id, e.department FROM leave_requests lr JOIN employees e ON lr.employee_id = e.employee_id $where_clause ORDER BY lr.created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$leave_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get leave statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM leave_requests");
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

$stmt_total->close();
$stmt->close();
$stmt_stats->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-file-alt me-2"></i>Leave Management</h2>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $stats['total']; ?></h3>
            <p><i class="fas fa-file-alt me-2"></i>Total Requests</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $stats['pending']; ?></h3>
            <p><i class="fas fa-clock me-2"></i>Pending</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $stats['approved']; ?></h3>
            <p><i class="fas fa-check-circle me-2"></i>Approved</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $stats['rejected']; ?></h3>
            <p><i class="fas fa-times-circle me-2"></i>Rejected</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Leave Requests</h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="GET">
                    <div class="input-group">
                        <select class="form-select" name="status_filter">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if (!empty($status_filter)): ?>
                            <a href="leave.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <form method="GET">
                    <?php if (!empty($status_filter)): ?>
                        <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="leave.php<?php echo !empty($status_filter) ? '?status_filter=' . urlencode($status_filter) : ''; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted">Showing <?php echo count($leave_requests); ?> of <?php echo $total_records; ?> requests</small>
            </div>
        </div>

        <!-- Leave Requests Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="leave-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leave_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($request['department'] ?: 'Not Assigned'); ?></td>
                            <td><?php echo ucfirst($request['leave_type']); ?></td>
                            <td>
                                <?php echo formatDate($request['start_date']); ?> - 
                                <?php echo formatDate($request['end_date']); ?>
                                <br><small class="text-muted">
                                    <?php 
                                    $start = new DateTime($request['start_date']);
                                    $end = new DateTime($request['end_date']);
                                    $days = $start->diff($end)->days + 1;
                                    echo $days . ' day' . ($days > 1 ? 's' : '');
                                    ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($request['created_at']); ?></td>
                            <td>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-success" onclick="approveLeave(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="rejectLeave(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Leave pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Approve/Reject Modal -->
<div class="modal fade" id="leaveActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Leave Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="leaveActionForm">
                <input type="hidden" name="leave_id" id="leave_id">
                <input type="hidden" name="action" id="action">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin_comments" class="form-label">Comments</label>
                        <textarea class="form-control" id="admin_comments" name="admin_comments" rows="3" placeholder="Add your comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="actionButton">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveLeave(leaveId) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-check-circle me-2"></i>Approve Leave Request';
    document.getElementById('action').value = 'approve';
    document.getElementById('leave_id').value = leaveId;
    document.getElementById('actionButton').className = 'btn btn-success';
    document.getElementById('actionButton').innerHTML = '<i class="fas fa-check me-2"></i>Approve';
    
    const modal = new bootstrap.Modal(document.getElementById('leaveActionModal'));
    modal.show();
}

function rejectLeave(leaveId) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-times-circle me-2"></i>Reject Leave Request';
    document.getElementById('action').value = 'reject';
    document.getElementById('leave_id').value = leaveId;
    document.getElementById('actionButton').className = 'btn btn-danger';
    document.getElementById('actionButton').innerHTML = '<i class="fas fa-times me-2"></i>Reject';
    
    const modal = new bootstrap.Modal(document.getElementById('leaveActionModal'));
    modal.show();
}

function viewDetails(leaveId) {
    // Implement view details functionality
    alert('View details for leave request: ' + leaveId);
}
</script>

<?php include '../footer.php'; ?>
