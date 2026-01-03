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

// Get employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

// Get attendance summary
$today = date('Y-m-d');
$stmt_attendance = $conn->prepare("SELECT COUNT(*) as total_days, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days, SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days FROM attendance WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE) AND YEAR(date) = YEAR(CURRENT_DATE)");
$stmt_attendance->bind_param("s", $employee_id);
$stmt_attendance->execute();
$attendance_summary = $stmt_attendance->get_result()->fetch_assoc();

// Get today's attendance
$stmt_today = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt_today->bind_param("ss", $employee_id, $today);
$stmt_today->execute();
$today_attendance = $stmt_today->get_result()->fetch_assoc();

// Get recent leave requests
$stmt_leaves = $conn->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt_leaves->bind_param("s", $employee_id);
$stmt_leaves->execute();
$recent_leaves = $stmt_leaves->get_result()->fetch_all(MYSQLI_ASSOC);

// Get leave balance
$current_year = date('Y');
$stmt_balance = $conn->prepare("SELECT * FROM leave_balance WHERE employee_id = ? AND year = ?");
$stmt_balance->bind_param("si", $employee_id, $current_year);
$stmt_balance->execute();
$leave_balance = $stmt_balance->get_result()->fetch_assoc();

// Get payroll info
$current_month = date('Y-m');
$stmt_payroll = $conn->prepare("SELECT * FROM payroll WHERE employee_id = ? AND month = ?");
$stmt_payroll->bind_param("ss", $employee_id, $current_month);
$stmt_payroll->execute();
$payroll = $stmt_payroll->get_result()->fetch_assoc();

if (!$payroll) {
    // Get salary structure
    $stmt_salary = $conn->prepare("SELECT * FROM salary_structure WHERE employee_id = ? ORDER BY effective_date DESC LIMIT 1");
    $stmt_salary->bind_param("s", $employee_id);
    $stmt_salary->execute();
    $salary_structure = $stmt_salary->get_result()->fetch_assoc();
    
    if ($salary_structure) {
        $payroll = [
            'basic_salary' => $salary_structure['basic_salary'],
            'hra' => $salary_structure['hra'],
            'da' => $salary_structure['da'],
            'ta' => $salary_structure['ta'],
            'pf_deduction' => $salary_structure['pf_deduction'],
            'tax_deduction' => $salary_structure['tax_deduction'],
            'total_salary' => $salary_structure['total_salary']
        ];
    }
}

$stmt->close();
$stmt_attendance->close();
$stmt_today->close();
$stmt_leaves->close();
$stmt_balance->close();
$stmt_payroll->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Employee Dashboard</h2>
            <div class="text-muted">
                Welcome back, <?php echo htmlspecialchars($employee['first_name']); ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $attendance_summary['present_days']; ?></h3>
            <p><i class="fas fa-calendar-check me-2"></i>Days Present</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $attendance_summary['absent_days']; ?></h3>
            <p><i class="fas fa-calendar-times me-2"></i>Days Absent</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $leave_balance['paid_leave_balance'] + $leave_balance['sick_leave_balance']; ?></h3>
            <p><i class="fas fa-plane me-2"></i>Leave Balance</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo isset($payroll['total_salary']) ? '₹' . number_format($payroll['total_salary'], 2) : 'N/A'; ?></h3>
            <p><i class="fas fa-money-bill me-2"></i>Monthly Salary</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-th-large me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <button class="quick-action-btn" onclick="window.location.href='attendance.php'">
                            <i class="fas fa-clock fa-2x mb-2"></i><br>
                            Mark Attendance
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="quick-action-btn" onclick="window.location.href='leave.php'">
                            <i class="fas fa-file-alt fa-2x mb-2"></i><br>
                            Apply for Leave
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="quick-action-btn" onclick="window.location.href='profile.php'">
                            <i class="fas fa-user fa-2x mb-2"></i><br>
                            Update Profile
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="quick-action-btn" onclick="window.location.href='payroll.php'">
                            <i class="fas fa-piggy-bank fa-2x mb-2"></i><br>
                            View Payroll
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Recent Leave Requests</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_leaves)): ?>
                    <p class="text-muted">No leave requests found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_leaves as $leave): ?>
                                    <tr>
                                        <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                        <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-user-circle me-2"></i>Today's Attendance</h5>
            </div>
            <div class="card-body text-center">
                <?php if ($today_attendance): ?>
                    <div class="mb-3">
                        <span class="status-badge status-<?php echo $today_attendance['status']; ?>">
                            <?php echo ucfirst($today_attendance['status']); ?>
                        </span>
                    </div>
                    <?php if ($today_attendance['check_in']): ?>
                        <p><strong>Check-in:</strong> <?php echo $today_attendance['check_in']; ?></p>
                    <?php endif; ?>
                    <?php if ($today_attendance['check_out']): ?>
                        <p><strong>Check-out:</strong> <?php echo $today_attendance['check_out']; ?></p>
                    <?php endif; ?>
                    <?php if (!$today_attendance['check_out'] && $today_attendance['status'] === 'present'): ?>
                        <button class="btn btn-warning" onclick="checkOut()">
                            <i class="fas fa-sign-out-alt me-2"></i>Check Out
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Not marked yet</p>
                    <button class="btn btn-success" onclick="checkIn()">
                        <i class="fas fa-sign-in-alt me-2"></i>Check In
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Remember to mark your attendance daily
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $leave_balance['paid_leave_balance']; ?> paid leaves remaining
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkIn() {
    if (confirm('Are you sure you want to check in?')) {
        window.location.href = 'attendance.php?action=checkin';
    }
}

function checkOut() {
    if (confirm('Are you sure you want to check out?')) {
        window.location.href = 'attendance.php?action=checkout';
    }
}
</script>

<?php include '../footer.php'; ?>
