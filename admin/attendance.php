<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../employee/dashboard.php');
}

$conn = connectDB();

// Handle attendance correction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'correct_attendance') {
    $employee_id = sanitizeInput($_POST['employee_id']);
    $date = sanitizeInput($_POST['date']);
    $check_in = sanitizeInput($_POST['check_in']);
    $check_out = sanitizeInput($_POST['check_out']);
    $status = sanitizeInput($_POST['status']);
    $notes = sanitizeInput($_POST['notes']);
    
    // Validation
    if (empty($employee_id) || empty($date) || empty($status)) {
        $error = "All required fields must be filled";
    } else {
        // Check if attendance record exists
        $stmt_check = $conn->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt_check->bind_param("ss", $employee_id, $date);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE attendance SET check_in = ?, check_out = ?, status = ?, notes = ? WHERE employee_id = ? AND date = ?");
            $stmt->bind_param("ssssss", $check_in, $check_out, $status, $notes, $employee_id, $date);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, check_in, check_out, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $employee_id, $date, $check_in, $check_out, $status, $notes);
        }
        
        if ($stmt->execute()) {
            $success = "Attendance updated successfully!";
        } else {
            $error = "Failed to update attendance. Please try again.";
        }
        
        $stmt->close();
        $stmt_check->close();
    }
}

// Get attendance records with search and pagination
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? sanitizeInput($_GET['date_filter']) : date('Y-m-d');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "WHERE a.date = ?";
$params = [$date_filter];
$types = "s";

if (!empty($search)) {
    $where_clause .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Get total records
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM attendance a JOIN employees e ON a.employee_id = e.employee_id $where_clause");
$stmt_total->bind_param($types, ...$params);
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get attendance records
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_id, e.department FROM attendance a JOIN employees e ON a.employee_id = e.employee_id $where_clause ORDER BY a.date DESC, e.first_name ASC LIMIT ? OFFSET ?");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get employees for dropdown
$stmt_employees = $conn->prepare("SELECT employee_id, first_name, last_name FROM employees WHERE role = 'employee' AND is_active = 1 ORDER BY first_name, last_name");
$stmt_employees->execute();
$employees = $stmt_employees->get_result()->fetch_all(MYSQLI_ASSOC);

// Get attendance summary for the selected date
$stmt_summary = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_day,
    SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_count
    FROM attendance 
    WHERE date = ?");
$stmt_summary->bind_param("s", $date_filter);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

$stmt_total->close();
$stmt->close();
$stmt_employees->close();
$stmt_summary->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-clock me-2"></i>Attendance Management</h2>
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
    <div class="col-md-2">
        <div class="stats-card">
            <h3><?php echo $summary['total']; ?></h3>
            <p><i class="fas fa-users me-2"></i>Total</p>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h3><?php echo $summary['present']; ?></h3>
            <p><i class="fas fa-calendar-check me-2"></i>Present</p>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h3><?php echo $summary['absent']; ?></h3>
            <p><i class="fas fa-calendar-times me-2"></i>Absent</p>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h3><?php echo $summary['half_day']; ?></h3>
            <p><i class="fas fa-calendar-minus me-2"></i>Half Day</p>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h3><?php echo $summary['leave_count']; ?></h3>
            <p><i class="fas fa-plane me-2"></i>Leave</p>
        </div>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100 h-100" data-bs-toggle="modal" data-bs-target="#correctAttendanceModal">
            <i class="fas fa-edit me-2"></i>Correct Attendance
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Attendance Records</h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="GET">
                    <div class="input-group">
                        <input type="date" class="form-control" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <form method="GET">
                    <input type="hidden" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="attendance.php?date_filter=<?php echo htmlspecialchars($date_filter); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted">Showing <?php echo count($attendance_records); ?> of <?php echo $total_records; ?> records for <?php echo formatDate($date_filter); ?></small>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="attendance-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($record['department'] ?: 'Not Assigned'); ?></td>
                            <td><?php echo $record['check_in'] ?: '-'; ?></td>
                            <td><?php echo $record['check_out'] ?: '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['notes'] ?: '-'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editAttendance('<?php echo $record['employee_id']; ?>', '<?php echo $record['date']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Attendance pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_filter=<?php echo htmlspecialchars($date_filter); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&date_filter=<?php echo htmlspecialchars($date_filter); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&date_filter=<?php echo htmlspecialchars($date_filter); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Correct Attendance Modal -->
<div class="modal fade" id="correctAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Correct Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="correct_attendance">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select class="form-select" id="employee_id" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo htmlspecialchars($employee['employee_id']); ?>">
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="check_in" class="form-label">Check In Time</label>
                                <input type="time" class="form-control" id="check_in" name="check_in">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="check_out" class="form-label">Check Out Time</label>
                                <input type="time" class="form-control" id="check_out" name="check_out">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="half_day">Half Day</option>
                                    <option value="leave">Leave</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAttendance(employeeId, date) {
    // Pre-fill the modal with existing data
    document.getElementById('employee_id').value = employeeId;
    document.getElementById('date').value = date;
    
    // Fetch existing attendance data via AJAX (optional enhancement)
    // For now, just open the modal
    const modal = new bootstrap.Modal(document.getElementById('correctAttendanceModal'));
    modal.show();
}

// Auto-populate time fields based on status
document.getElementById('status').addEventListener('change', function() {
    const status = this.value;
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    
    if (status === 'present') {
        if (!checkIn.value) checkIn.value = '09:00';
        if (!checkOut.value) checkOut.value = '18:00';
    } else if (status === 'half_day') {
        if (!checkIn.value) checkIn.value = '09:00';
        if (!checkOut.value) checkOut.value = '14:00';
    } else if (status === 'absent' || status === 'leave') {
        checkIn.value = '';
        checkOut.value = '';
    }
});
</script>

<?php include '../footer.php'; ?>
