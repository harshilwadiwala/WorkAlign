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

// Handle check-in/check-out actions
if (isset($_GET['action'])) {
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    if ($_GET['action'] === 'checkin') {
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, check_in, status) VALUES (?, ?, ?, 'present') ON DUPLICATE KEY UPDATE check_in = ?, status = 'present'");
        $stmt->bind_param("ssss", $employee_id, $today, $current_time, $current_time);
        $stmt->execute();
        $stmt->close();
        
        $success = "Checked in successfully at " . $current_time;
    } elseif ($_GET['action'] === 'checkout') {
        $stmt = $conn->prepare("UPDATE attendance SET check_out = ? WHERE employee_id = ? AND date = ?");
        $stmt->bind_param("sss", $current_time, $employee_id, $today);
        $stmt->execute();
        $stmt->close();
        
        $success = "Checked out successfully at " . $current_time;
    }
    
    // Redirect to prevent form resubmission
    header("Location: attendance.php");
    exit();
}

// Get attendance records
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total records
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE employee_id = ?");
$stmt_total->bind_param("s", $employee_id);
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get attendance records with pagination
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $employee_id, $per_page, $offset);
$stmt->execute();
$attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get today's attendance
$today = date('Y-m-d');
$stmt_today = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt_today->bind_param("ss", $employee_id, $today);
$stmt_today->execute();
$today_attendance = $stmt_today->get_result()->fetch_assoc();

// Get monthly summary
$stmt_monthly = $conn->prepare("SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
    SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_days,
    SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
    FROM attendance 
    WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE) AND YEAR(date) = YEAR(CURRENT_DATE)");
$stmt_monthly->bind_param("s", $employee_id);
$stmt_monthly->execute();
$monthly_summary = $stmt_monthly->get_result()->fetch_assoc();

$stmt_total->close();
$stmt->close();
$stmt_today->close();
$stmt_monthly->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-clock me-2"></i>Attendance Management</h2>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['checkout_required'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['checkout_required']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['checkout_required']); ?>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $monthly_summary['present_days']; ?></h3>
            <p><i class="fas fa-calendar-check me-2"></i>Days Present</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $monthly_summary['absent_days']; ?></h3>
            <p><i class="fas fa-calendar-times me-2"></i>Days Absent</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $monthly_summary['half_days']; ?></h3>
            <p><i class="fas fa-calendar-minus me-2"></i>Half Days</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $monthly_summary['leave_days']; ?></h3>
            <p><i class="fas fa-plane me-2"></i>Leave Days</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-day me-2"></i>Today's Attendance</h5>
            </div>
            <div class="card-body text-center">
                <?php if ($today_attendance): ?>
                    <div class="mb-3">
                        <span class="status-badge status-<?php echo $today_attendance['status']; ?>">
                            <?php echo ucfirst($today_attendance['status']); ?>
                        </span>
                    </div>
                    
                    <?php if ($today_attendance['check_in']): ?>
                        <div class="mb-2">
                            <strong>Check-in:</strong> <?php echo $today_attendance['check_in']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($today_attendance['check_out']): ?>
                        <div class="mb-2">
                            <strong>Check-out:</strong> <?php echo $today_attendance['check_out']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$today_attendance['check_out'] && $today_attendance['status'] === 'present'): ?>
                        <a href="attendance.php?action=checkout" class="btn btn-warning" onclick="return confirm('Are you sure you want to check out?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Check Out
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-3">Not marked yet</p>
                    <a href="attendance.php?action=checkin" class="btn btn-success" onclick="return confirm('Are you sure you want to check in?')">
                        <i class="fas fa-sign-in-alt me-2"></i>Check In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Attendance History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="attendance-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['date']); ?></td>
                                    <td><?php echo $record['check_in'] ?: '-'; ?></td>
                                    <td><?php echo $record['check_out'] ?: '-'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $record['notes'] ?: '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Attendance pagination">
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

<?php include '../footer.php'; ?>
