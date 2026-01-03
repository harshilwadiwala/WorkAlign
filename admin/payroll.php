<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../employee/dashboard.php');
}

$conn = connectDB();

// Handle salary structure update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_salary') {
    $employee_id = sanitizeInput($_POST['employee_id']);
    $basic_salary = floatval($_POST['basic_salary']);
    $hra = floatval($_POST['hra']);
    $da = floatval($_POST['da']);
    $ta = floatval($_POST['ta']);
    $pf_deduction = floatval($_POST['pf_deduction']);
    $tax_deduction = floatval($_POST['tax_deduction']);
    $other_deductions = floatval($_POST['other_deductions']);
    $effective_date = sanitizeInput($_POST['effective_date']);
    
    // Validation
    if (empty($employee_id) || empty($basic_salary) || empty($effective_date)) {
        $error = "Employee ID, Basic Salary, and Effective Date are required";
    } else {
        $stmt = $conn->prepare("INSERT INTO salary_structure (employee_id, basic_salary, hra, da, ta, pf_deduction, tax_deduction, other_deductions, effective_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sddddddss", $employee_id, $basic_salary, $hra, $da, $ta, $pf_deduction, $tax_deduction, $other_deductions, $effective_date);
        
        if ($stmt->execute()) {
            $success = "Salary structure updated successfully!";
        } else {
            $error = "Failed to update salary structure. Please try again.";
        }
        
        $stmt->close();
    }
}

// Handle payroll processing
if (isset($_GET['process_payroll']) && isset($_GET['month'])) {
    $month = sanitizeInput($_GET['month']);
    $admin_employee_id = $_SESSION['employee_id'];
    
    // Get all active employees
    $stmt_employees = $conn->prepare("SELECT employee_id FROM employees WHERE role = 'employee' AND is_active = 1");
    $stmt_employees->execute();
    $employees = $stmt_employees->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $processed_count = 0;
    $skipped_count = 0;
    
    foreach ($employees as $employee) {
        $emp_id = $employee['employee_id'];
        $effective_date = $month . '-01';
        
        // Get latest salary structure (remove effective date constraint for testing)
        $stmt_salary = $conn->prepare("SELECT * FROM salary_structure WHERE employee_id = ? ORDER BY effective_date DESC LIMIT 1");
        $stmt_salary->bind_param("s", $emp_id);
        $stmt_salary->execute();
        $salary = $stmt_salary->get_result()->fetch_assoc();
        
        if ($salary) {
            // Get attendance for the month
            $stmt_attendance = $conn->prepare("SELECT 
                COUNT(*) as working_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
                FROM attendance 
                WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
            $stmt_attendance->bind_param("ss", $emp_id, $month);
            $stmt_attendance->execute();
            $attendance = $stmt_attendance->get_result()->fetch_assoc();
            
            // Debug: Log what we found
            error_log("Employee $emp_id: Salary found, Working days: " . ($attendance['working_days'] ?? 0));
            
            // Check if payroll already exists
            $stmt_check = $conn->prepare("SELECT id FROM payroll WHERE employee_id = ? AND month = ?");
            $stmt_check->bind_param("ss", $emp_id, $month);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows === 0) {
                // Only process if we have attendance data
                if ($attendance['working_days'] > 0) {
                    // Insert payroll record
                    $basic_salary = $salary['basic_salary'];
                    $hra = $salary['hra'];
                    $da = $salary['da'];
                    $ta = $salary['ta'];
                    $pf_deduction = $salary['pf_deduction'];
                    $tax_deduction = $salary['tax_deduction'];
                    $other_deductions = $salary['other_deductions'];
                    $working_days = $attendance['working_days'];
                    $present_days = $attendance['present_days'];
                    $leave_days = $attendance['leave_days'];
                    $absent_days = $attendance['absent_days'];
                    
                    $stmt_payroll = $conn->prepare("INSERT INTO payroll (employee_id, month, basic_salary, hra, da, ta, pf_deduction, tax_deduction, other_deductions, working_days, present_days, leave_days, absent_days, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processed')");
                    $stmt_payroll->bind_param("ssddddddsiiss", $emp_id, $month, $basic_salary, $hra, $da, $ta, $pf_deduction, $tax_deduction, $other_deductions, $working_days, $present_days, $leave_days, $absent_days);
                    
                    if ($stmt_payroll->execute()) {
                        $processed_count++;
                        error_log("Successfully processed payroll for $emp_id");
                    } else {
                        error_log("Failed to insert payroll for $emp_id: " . $stmt_payroll->error);
                    }
                    $stmt_payroll->close();
                } else {
                    $skipped_count++;
                    error_log("Skipped $emp_id: No attendance data for $month");
                }
            } else {
                $skipped_count++;
                error_log("Skipped $emp_id: Payroll already exists for $month");
            }
            
            $stmt_attendance->close();
        } else {
            $skipped_count++;
            error_log("Skipped $emp_id: No salary structure found");
        }
        
        $stmt_salary->close();
    }
    
    $stmt_employees->close();
    
    $total_employees = count($employees);
    $success = "Payroll processed for " . $month . ". Total: " . $total_employees . " employees. Processed: " . $processed_count . ". Skipped: " . $skipped_count . ".";
    header("Location: payroll.php?month_filter=" . urlencode($month) . "&success=" . urlencode($success));
    exit();
}

// Get payroll records with search and pagination
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$month_filter = isset($_GET['month_filter']) ? sanitizeInput($_GET['month_filter']) : date('Y-m');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "WHERE p.month = ?";
$params = [$month_filter];
$types = "s";

if (!empty($search)) {
    $where_clause .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

// Get total records
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM payroll p JOIN employees e ON p.employee_id = e.employee_id $where_clause");
$stmt_total->bind_param($types, ...$params);
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get payroll records
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare("SELECT p.*, e.first_name, e.last_name, e.employee_id, e.department FROM payroll p JOIN employees e ON p.employee_id = e.employee_id $where_clause ORDER BY e.first_name, e.last_name ASC LIMIT ? OFFSET ?");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payroll_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Debug: Check if we have records
if (empty($payroll_records)) {
    // Let's check if there are any payroll records at all
    $debug_stmt = $conn->prepare("SELECT COUNT(*) as count FROM payroll");
    $debug_stmt->execute();
    $total_payroll_count = $debug_stmt->get_result()->fetch_assoc()['count'];
    
    // Check for records in the filtered month
    $debug_month_stmt = $conn->prepare("SELECT COUNT(*) as count FROM payroll WHERE month = ?");
    $debug_month_stmt->bind_param("s", $month_filter);
    $debug_month_stmt->execute();
    $month_payroll_count = $debug_month_stmt->get_result()->fetch_assoc()['count'];
    
    $debug_month_stmt->close();
    $debug_stmt->close();
}

// Get employees for salary structure update
$stmt_employees = $conn->prepare("SELECT employee_id, first_name, last_name FROM employees WHERE role = 'employee' AND is_active = 1 ORDER BY first_name, last_name");
$stmt_employees->execute();
$employees = $stmt_employees->get_result()->fetch_all(MYSQLI_ASSOC);

// Get payroll statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_employees,
    SUM(total_salary) as total_payroll,
    SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid
    FROM payroll 
    WHERE month = ?");
$stmt_stats->bind_param("s", $month_filter);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

$stmt_total->close();
$stmt->close();
$stmt_employees->close();
$stmt_stats->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-money-bill me-2"></i>Payroll Management</h2>
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

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $stats['total_employees']; ?></h3>
            <p><i class="fas fa-users me-2"></i>Total Employees</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3>₹<?php echo number_format($stats['total_payroll'] ?? 0, 2); ?></h3>
            <p><i class="fas fa-hand-holding-usd me-2"></i>Total Payroll</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $stats['processed']; ?></h3>
            <p><i class="fas fa-cog me-2"></i>Processed</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo $stats['paid']; ?></h3>
            <p><i class="fas fa-check-circle me-2"></i>Paid</p>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calculator me-2"></i>Update Salary Structure</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_salary">
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
                                <label for="effective_date" class="form-label">Effective Date</label>
                                <input type="date" class="form-control" id="effective_date" name="effective_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="basic_salary" class="form-label">Basic Salary</label>
                                <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hra" class="form-label">HRA</label>
                                <input type="number" class="form-control" id="hra" name="hra" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="da" class="form-label">DA</label>
                                <input type="number" class="form-control" id="da" name="da" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ta" class="form-label">TA</label>
                                <input type="number" class="form-control" id="ta" name="ta" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="pf_deduction" class="form-label">PF Deduction</label>
                                <input type="number" class="form-control" id="pf_deduction" name="pf_deduction" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tax_deduction" class="form-label">Tax Deduction</label>
                                <input type="number" class="form-control" id="tax_deduction" name="tax_deduction" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="other_deductions" class="form-label">Other Deductions</label>
                                <input type="number" class="form-control" id="other_deductions" name="other_deductions" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Salary Structure
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-cogs me-2"></i>Process Payroll</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="mb-3">
                        <label for="process_month" class="form-label">Select Month</label>
                        <input type="month" class="form-control" id="process_month" name="month" value="<?php echo date('Y-m'); ?>" required>
                    </div>
                    <button type="submit" name="process_payroll" class="btn btn-success" onclick="return confirm('Are you sure you want to process payroll for this month? This will create payroll records for all active employees.')">
                        <i class="fas fa-play me-2"></i>Process Payroll
                    </button>
                </form>
                
                <div class="mt-3">
                    <h6>Payroll Processing Information</h6>
                    <ul class="small text-muted">
                        <li>Payroll will be processed for all active employees</li>
                        <li>Latest salary structure will be used</li>
                        <li>Attendance data will be calculated automatically</li>
                        <li>Duplicate payroll records will be skipped</li>
                    </ul>
                    
                    <?php
                    // Diagnostic information
                    $diag_employees = $conn->prepare("SELECT COUNT(*) as count FROM employees WHERE role = 'employee' AND is_active = 1");
                    $diag_employees->execute();
                    $emp_count = $diag_employees->get_result()->fetch_assoc()['count'];
                    
                    $diag_salary = $conn->prepare("SELECT COUNT(DISTINCT employee_id) as count FROM salary_structure");
                    $diag_salary->execute();
                    $salary_count = $diag_salary->get_result()->fetch_assoc()['count'];
                    
                    $current_month = date('Y-m');
                    $diag_attendance = $conn->prepare("SELECT COUNT(DISTINCT employee_id) as count FROM attendance WHERE DATE_FORMAT(date, '%Y-%m') = ?");
                    $diag_attendance->bind_param("s", $current_month);
                    $diag_attendance->execute();
                    $attendance_count = $diag_attendance->get_result()->fetch_assoc()['count'];
                    
                    $diag_employees->close();
                    $diag_salary->close();
                    $diag_attendance->close();
                    ?>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>System Status</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Active Employees:</strong> <?php echo $emp_count; ?><br>
                                <strong>With Salary Structure:</strong> <?php echo $salary_count; ?><br>
                                <strong>With Attendance (<?php echo date('F Y'); ?>):</strong> <?php echo $attendance_count; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if ($salary_count < $emp_count): ?>
                                    <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Some employees lack salary structure</span><br>
                                <?php endif; ?>
                                <?php if ($attendance_count < $emp_count): ?>
                                    <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Some employees lack attendance data</span><br>
                                <?php endif; ?>
                                <?php if ($salary_count >= $emp_count && $attendance_count >= $emp_count): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Ready to process payroll</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Payroll Records</h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="GET">
                    <div class="input-group">
                        <input type="month" class="form-control" name="month_filter" value="<?php echo htmlspecialchars($month_filter); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <form method="GET">
                    <input type="hidden" name="month_filter" value="<?php echo htmlspecialchars($month_filter); ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="payroll.php?month_filter=<?php echo htmlspecialchars($month_filter); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted">Showing <?php echo count($payroll_records); ?> of <?php echo $total_records; ?> records for <?php echo date('F Y', strtotime($month_filter . '-01')); ?></small>
            </div>
        </div>

        <!-- Payroll Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="payroll-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Days Worked</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payroll_records)): ?>
                        <?php foreach ($payroll_records as $payroll): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($payroll['employee_id']); ?></td>
                                <td>₹<?php echo number_format($payroll['basic_salary'] ?? 0, 2); ?></td>
                                <td>₹<?php echo number_format(($payroll['hra'] ?? 0) + ($payroll['da'] ?? 0) + ($payroll['ta'] ?? 0), 2); ?></td>
                                <td>₹<?php echo number_format(($payroll['pf_deduction'] ?? 0) + ($payroll['tax_deduction'] ?? 0) + ($payroll['other_deductions'] ?? 0), 2); ?></td>
                                <td><strong>₹<?php echo number_format($payroll['total_salary'] ?? 0, 2); ?></strong></td>
                                <td>
                                    <?php echo $payroll['present_days']; ?>/<?php echo $payroll['working_days']; ?>
                                    <?php if ($payroll['leave_days'] > 0): ?>
                                        <br><small class="text-muted"><?php echo $payroll['leave_days']; ?>L</small>
                                    <?php endif; ?>
                                    <?php if ($payroll['absent_days'] > 0): ?>
                                        <br><small class="text-muted"><?php echo $payroll['absent_days']; ?>A</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $payroll['status']; ?>">
                                        <?php echo ucfirst($payroll['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewPayroll(<?php echo $payroll['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($payroll['status'] === 'processed'): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="markAsPaid(<?php echo $payroll['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No payroll records found for <?php echo date('F Y', strtotime($month_filter . '-01')); ?>
                                </div>
                                <?php if (isset($total_payroll_count) && $total_payroll_count > 0): ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Debug Info:</strong><br>
                                        Total payroll records in database: <?php echo $total_payroll_count; ?><br>
                                        Records for selected month (<?php echo $month_filter; ?>): <?php echo $month_payroll_count; ?><br>
                                        <small class="text-muted">Try selecting a different month from the filter above.</small>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>No payroll records found in the database.</strong><br>
                                        Please process payroll for the desired month first.
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Payroll pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&month_filter=<?php echo htmlspecialchars($month_filter); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&month_filter=<?php echo htmlspecialchars($month_filter); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&month_filter=<?php echo htmlspecialchars($month_filter); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
function viewPayroll(payrollId) {
    // Implement view payroll details functionality
    alert('View payroll details: ' + payrollId);
}

function markAsPaid(payrollId) {
    if (confirm('Are you sure you want to mark this payroll as paid?')) {
        // Implement mark as paid functionality
        alert('Mark as paid: ' + payrollId);
    }
}

// Auto-calculate total when salary components change
document.querySelectorAll('#basic_salary, #hra, #da, #ta, #pf_deduction, #tax_deduction, #other_deductions').forEach(input => {
    input.addEventListener('input', function() {
        const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
        const hra = parseFloat(document.getElementById('hra').value) || 0;
        const da = parseFloat(document.getElementById('da').value) || 0;
        const ta = parseFloat(document.getElementById('ta').value) || 0;
        const pf = parseFloat(document.getElementById('pf_deduction').value) || 0;
        const tax = parseFloat(document.getElementById('tax_deduction').value) || 0;
        const other = parseFloat(document.getElementById('other_deductions').value) || 0;
        
        const total = basic + hra + da + ta - pf - tax - other;
        
        // You could display this total to the user if needed
        console.log('Net Salary:', total);
    });
});
</script>

<?php
// Close database connection at the end
$conn->close();
?>

<?php include '../footer.php'; ?>
