<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isEmployee()) {
    redirect('../admin/dashboard.php');
}

$conn = connectDB();
$employee_id = $_SESSION['employee_id'];

$errors = [];
$success = '';

// Handle leave application
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = sanitizeInput($_POST['leave_type']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    $reason = sanitizeInput($_POST['reason']);
    
    // Validation
    if (empty($leave_type)) $errors[] = "Leave type is required";
    if (empty($start_date)) $errors[] = "Start date is required";
    if (empty($end_date)) $errors[] = "End date is required";
    if (empty($reason)) $errors[] = "Reason is required";
    
    if (strtotime($start_date) > strtotime($end_date)) {
        $errors[] = "End date must be after start date";
    }
    
    if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Start date cannot be in the past";
    }
    
    // Check for overlapping leave requests
    $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND status != 'rejected' AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?) OR (start_date >= ? AND end_date <= ?))");
    $stmt_check->bind_param("sssssss", $employee_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
    $stmt_check->execute();
    $overlap_count = $stmt_check->get_result()->fetch_assoc()['count'];
    
    if ($overlap_count > 0) {
        $errors[] = "You already have a leave request for this period";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $employee_id, $leave_type, $start_date, $end_date, $reason);
        
        if ($stmt->execute()) {
            $success = "Leave application submitted successfully!";
        } else {
            $errors[] = "Failed to submit leave application. Please try again.";
        }
        
        $stmt->close();
    }
    
    $stmt_check->close();
}

// Get leave balance
$current_year = date('Y');
$stmt_balance = $conn->prepare("SELECT * FROM leave_balance WHERE employee_id = ? AND year = ?");
$stmt_balance->bind_param("si", $employee_id, $current_year);
$stmt_balance->execute();
$leave_balance = $stmt_balance->get_result()->fetch_assoc();

// Get leave requests
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total records
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM leave_requests WHERE employee_id = ?");
$stmt_total->bind_param("s", $employee_id);
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get leave requests with pagination
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $employee_id, $per_page, $offset);
$stmt->execute();
$leave_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_balance->close();
$stmt_total->close();
$stmt->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-plane me-2"></i>Leave Management</h2>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $leave_balance['paid_leave_balance']; ?></h3>
            <p><i class="fas fa-briefcase me-2"></i>Paid Leaves</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $leave_balance['sick_leave_balance']; ?></h3>
            <p><i class="fas fa-medkit me-2"></i>Sick Leaves</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $leave_balance['unpaid_leave_balance']; ?></h3>
            <p><i class="fas fa-dollar-sign me-2"></i>Unpaid Leaves</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $leave_balance['paid_leave_balance'] + $leave_balance['sick_leave_balance']; ?></h3>
            <p><i class="fas fa-calendar me-2"></i>Total Balance</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-file-alt me-2"></i>Apply for Leave</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="leave_type" class="form-label">Leave Type</label>
                        <select class="form-select" id="leave_type" name="leave_type" required>
                            <option value="">Select Leave Type</option>
                            <option value="paid">Paid Leave</option>
                            <option value="sick">Sick Leave</option>
                            <option value="unpaid">Unpaid Leave</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i>Submit Application
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Leave History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="leave-table">
                        <thead>
                            <tr>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Applied On</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leave_requests as $request): ?>
                                <tr>
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
                                    <td><?php echo $request['admin_comments'] ?: '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Leave pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    if (this.value < document.getElementById('start_date').value) {
        this.value = document.getElementById('start_date').value;
    }
});
</script>

<?php include '../footer.php'; ?>
