<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../employee/dashboard.php');
}

$conn = connectDB();

// Get dashboard statistics
$total_employees = 0;
$present_today = 0;
$absent_today = 0;
$pending_leaves = 0;

// Total employees
$stmt_total = $conn->prepare("SELECT COUNT(*) as count FROM employees WHERE role = 'employee' AND is_active = 1");
$stmt_total->execute();
$total_employees = $stmt_total->get_result()->fetch_assoc()['count'];

// Today's attendance
$today = date('Y-m-d');
$stmt_attendance = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM attendance 
    WHERE date = ?");
$stmt_attendance->bind_param("s", $today);
$stmt_attendance->execute();
$attendance_data = $stmt_attendance->get_result()->fetch_assoc();

$present_today = $attendance_data['present'];
$absent_today = $attendance_data['absent'];

// Pending leave requests
$stmt_leaves = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'");
$stmt_leaves->execute();
$pending_leaves = $stmt_leaves->get_result()->fetch_assoc()['count'];

// Recent employees
$stmt_recent = $conn->prepare("SELECT * FROM employees WHERE role = 'employee' ORDER BY created_at DESC LIMIT 5");
$stmt_recent->execute();
$recent_employees = $stmt_recent->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent leave requests
$stmt_leave_recent = $conn->prepare("SELECT lr.*, e.first_name, e.last_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.employee_id ORDER BY lr.created_at DESC LIMIT 5");
$stmt_leave_recent->execute();
$recent_leaves = $stmt_leave_recent->get_result()->fetch_all(MYSQLI_ASSOC);

// Department-wise employee count
$stmt_dept = $conn->prepare("SELECT department, COUNT(*) as count FROM employees WHERE role = 'employee' AND is_active = 1 GROUP BY department");
$stmt_dept->execute();
$department_stats = $stmt_dept->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_total->close();
$stmt_attendance->close();
$stmt_leaves->close();
$stmt_recent->close();
$stmt_leave_recent->close();
$stmt_dept->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
            <div>
                <h2 class="mb-2"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! Here's what's happening today.</p>
            </div>
            <div class="text-end">
                <div class="badge bg-primary p-2">
                    <i class="fas fa-clock me-1"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card fade-in" style="animation-delay: 0.1s;">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h3 class="mb-1"><?php echo $total_employees; ?></h3>
                    <p class="mb-0"><i class="fas fa-users me-2"></i>Total Employees</p>
                </div>
                <div class="ms-3">
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card fade-in" style="animation-delay: 0.2s;">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h3 class="mb-1"><?php echo $present_today; ?></h3>
                    <p class="mb-0"><i class="fas fa-calendar-check me-2"></i>Present Today</p>
                </div>
                <div class="ms-3">
                    <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card fade-in" style="animation-delay: 0.3s;">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h3 class="mb-1"><?php echo $absent_today; ?></h3>
                    <p class="mb-0"><i class="fas fa-calendar-times me-2"></i>Absent Today</p>
                </div>
                <div class="ms-3">
                    <i class="fas fa-calendar-times fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card fade-in" style="animation-delay: 0.4s;">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h3 class="mb-1"><?php echo $pending_leaves; ?></h3>
                    <p class="mb-0"><i class="fas fa-file-alt me-2"></i>Pending Leaves</p>
                </div>
                <div class="ms-3">
                    <i class="fas fa-file-alt fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4 fade-in" style="animation-delay: 0.5s;">
            <div class="card-header">
                <h5><i class="fas fa-th-large me-2"></i>Quick Actions</h5>
                <small class="text-muted">Manage your HR system efficiently</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button class="quick-action-btn" onclick="window.location.href='employees.php'">
                            <i class="fas fa-users fa-2x mb-2"></i><br>
                            <strong>Manage Employees</strong>
                            <small class="d-block text-muted mt-1">Add, edit, and manage staff</small>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="quick-action-btn" onclick="window.location.href='attendance.php'">
                            <i class="fas fa-clock fa-2x mb-2"></i><br>
                            <strong>View Attendance</strong>
                            <small class="d-block text-muted mt-1">Track daily attendance</small>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="quick-action-btn" onclick="window.location.href='leave.php'">
                            <i class="fas fa-file-alt fa-2x mb-2"></i><br>
                            <strong>Leave Approvals</strong>
                            <small class="d-block text-muted mt-1">Review leave requests</small>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="quick-action-btn" onclick="window.location.href='payroll.php'">
                            <i class="fas fa-money-bill fa-2x mb-2"></i><br>
                            <strong>Payroll Management</strong>
                            <small class="d-block text-muted mt-1">Process salaries</small>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="quick-action-btn" onclick="window.location.href='reports.php'">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                            <strong>Reports</strong>
                            <small class="d-block text-muted mt-1">Generate insights</small>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="quick-action-btn" onclick="window.location.href='settings.php'">
                            <i class="fas fa-cog fa-2x mb-2"></i><br>
                            <strong>Settings</strong>
                            <small class="d-block text-muted mt-1">System configuration</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 fade-in" style="animation-delay: 0.6s;">
            <div class="card-header">
                <h5><i class="fas fa-users me-2"></i>Recent Employees</h5>
                <small class="text-muted">Latest additions to your team</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Employee ID</th>
                                <th>Department</th>
                                <th>Joining Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_employees as $employee): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </div>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($employee['employee_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($employee['department'] ?: 'Not Assigned'); ?></td>
                                    <td><?php echo formatDate($employee['date_of_joining']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $employee['is_active'] ? 'status-present' : 'status-absent'; ?>">
                                            <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card fade-in" style="animation-delay: 0.7s;">
            <div class="card-header">
                <h5><i class="fas fa-file-alt me-2"></i>Recent Leave Requests</h5>
                <small class="text-muted">Latest leave applications</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Applied On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_leaves as $leave): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                <?php echo strtoupper(substr($leave['first_name'], 0, 1) . substr($leave['last_name'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo ucfirst($leave['leave_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $leave['status']; ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($leave['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie me-2"></i>Department Distribution</h5>
            </div>
            <div class="card-body">
                <?php foreach ($department_stats as $dept): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo htmlspecialchars($dept['department'] ?: 'Not Assigned'); ?></span>
                            <span><?php echo $dept['count']; ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo ($dept['count'] / $total_employees) * 100; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
            </div>
            <div class="card-body">
                <?php if ($pending_leaves > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $pending_leaves; ?> leave requests pending approval
                        <a href="leave.php" class="btn btn-sm btn-warning float-end">Review</a>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Today's attendance: <?php echo $present_today; ?> present, <?php echo $absent_today; ?> absent
                </div>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    System running smoothly
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar me-2"></i>Today's Summary</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4><?php echo date('d M Y'); ?></h4>
                </div>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="mb-3">
                            <h3 class="text-success"><?php echo $present_today; ?></h3>
                            <small>Present</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <h3 class="text-danger"><?php echo $absent_today; ?></h3>
                            <small>Absent</small>
                        </div>
                    </div>
                </div>
                
                <?php if ($total_employees > 0): ?>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($present_today / $total_employees) * 100; ?>%">
                            <?php echo round(($present_today / $total_employees) * 100); ?>%
                        </div>
                    </div>
                    <small class="text-muted">Attendance Rate</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
