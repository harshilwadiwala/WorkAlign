<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../employee/dashboard.php');
}

$conn = connectDB();

// Get report type and filters
$report_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'attendance';
$month_filter = isset($_GET['month']) ? sanitizeInput($_GET['month']) : date('Y-m');
$department_filter = isset($_GET['department']) ? sanitizeInput($_GET['department']) : '';

// Get departments for filter
$stmt_depts = $conn->prepare("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department");
$stmt_depts->execute();
$departments = $stmt_depts->get_result()->fetch_all(MYSQLI_ASSOC);

// Generate report data based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'attendance':
        $report_title = 'Attendance Report';
        
        $where_clause = "WHERE DATE_FORMAT(a.date, '%Y-%m') = ?";
        $params = [$month_filter];
        $types = "s";
        
        if (!empty($department_filter)) {
            $where_clause .= " AND e.department = ?";
            $params[] = $department_filter;
            $types .= "s";
        }
        
        $stmt = $conn->prepare("SELECT 
            e.employee_id,
            e.first_name,
            e.last_name,
            e.department,
            COUNT(*) as total_days,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) as half_days,
            SUM(CASE WHEN a.status = 'leave' THEN 1 ELSE 0 END) as leave_days,
            ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
            FROM attendance a
            JOIN employees e ON a.employee_id = e.employee_id
            $where_clause
            GROUP BY e.employee_id, e.first_name, e.last_name, e.department
            ORDER BY e.first_name, e.last_name");
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;
        
    case 'leave':
        $report_title = 'Leave Report';
        
        $where_clause = "WHERE DATE_FORMAT(lr.created_at, '%Y-%m') = ?";
        $params = [$month_filter];
        $types = "s";
        
        if (!empty($department_filter)) {
            $where_clause .= " AND e.department = ?";
            $params[] = $department_filter;
            $types .= "s";
        }
        
        $stmt = $conn->prepare("SELECT 
            e.employee_id,
            e.first_name,
            e.last_name,
            e.department,
            lr.leave_type,
            COUNT(*) as leave_count,
            SUM(DATEDIFF(lr.end_date, lr.start_date) + 1) as total_days,
            lr.status
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            $where_clause
            GROUP BY e.employee_id, e.first_name, e.last_name, e.department, lr.leave_type, lr.status
            ORDER BY e.first_name, e.last_name");
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;
        
    case 'payroll':
        $report_title = 'Payroll Report';
        
        $where_clause = "WHERE p.month = ?";
        $params = [$month_filter];
        $types = "s";
        
        if (!empty($department_filter)) {
            $where_clause .= " AND e.department = ?";
            $params[] = $department_filter;
            $types .= "s";
        }
        
        $stmt = $conn->prepare("SELECT 
            e.employee_id,
            e.first_name,
            e.last_name,
            e.department,
            p.basic_salary,
            p.hra,
            p.da,
            p.ta,
            p.pf_deduction,
            p.tax_deduction,
            p.other_deductions,
            p.total_salary,
            p.present_days,
            p.working_days,
            p.status
            FROM payroll p
            JOIN employees e ON p.employee_id = e.employee_id
            $where_clause
            ORDER BY e.first_name, e.last_name");
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;
}

$stmt_depts->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-chart-bar me-2"></i>Reports</h2>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter me-2"></i>Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET">
            <div class="row">
                <div class="col-md-3">
                    <label for="type" class="form-label">Report Type</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="attendance" <?php echo $report_type === 'attendance' ? 'selected' : ''; ?>>Attendance Report</option>
                        <option value="leave" <?php echo $report_type === 'leave' ? 'selected' : ''; ?>>Leave Report</option>
                        <option value="payroll" <?php echo $report_type === 'payroll' ? 'selected' : ''; ?>>Payroll Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label">Month</label>
                    <input type="month" class="form-control" id="month" name="month" value="<?php echo htmlspecialchars($month_filter); ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department_filter === $dept['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label><br>
                    <button type="button" class="btn btn-primary w-100" onclick="exportReport()">
                        <i class="fas fa-download me-2"></i>Export CSV
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($report_title); ?></h5>
        <small class="text-muted">Period: <?php echo date('F Y', strtotime($month_filter . '-01')); ?></small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="report-table">
                <thead>
                    <?php if ($report_type === 'attendance'): ?>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Total Days</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Half Day</th>
                            <th>Leave</th>
                            <th>Attendance %</th>
                        </tr>
                    <?php elseif ($report_type === 'leave'): ?>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th>Requests</th>
                            <th>Total Days</th>
                            <th>Status</th>
                        </tr>
                    <?php elseif ($report_type === 'payroll'): ?>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Days Worked</th>
                            <th>Status</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if (empty($report_data)): ?>
                        <tr>
                            <td colspan="<?php echo $report_type === 'attendance' ? '9' : ($report_type === 'leave' ? '7' : '9'); ?>" class="text-center">
                                No data found for the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report_data as $row): ?>
                            <?php if ($report_type === 'attendance'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?: 'Not Assigned'); ?></td>
                                    <td><?php echo $row['total_days']; ?></td>
                                    <td><?php echo $row['present_days']; ?></td>
                                    <td><?php echo $row['absent_days']; ?></td>
                                    <td><?php echo $row['half_days']; ?></td>
                                    <td><?php echo $row['leave_days']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['attendance_percentage'] >= 90 ? 'success' : ($row['attendance_percentage'] >= 75 ? 'warning' : 'danger'); ?>">
                                            <?php echo $row['attendance_percentage']; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php elseif ($report_type === 'leave'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?: 'Not Assigned'); ?></td>
                                    <td><?php echo ucfirst($row['leave_type']); ?></td>
                                    <td><?php echo $row['leave_count']; ?></td>
                                    <td><?php echo $row['total_days']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php elseif ($report_type === 'payroll'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?: 'Not Assigned'); ?></td>
                                    <td>₹<?php echo number_format($row['basic_salary'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['hra'] + $row['da'] + $row['ta'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['pf_deduction'] + $row['tax_deduction'] + $row['other_deductions'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($row['total_salary'], 2); ?></strong></td>
                                    <td><?php echo $row['present_days']; ?>/<?php echo $row['working_days']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportReport() {
    const table = document.getElementById('report-table');
    let csv = [];
    
    // Get headers
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    csv.push(headers.join(','));
    
    // Get rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowData = Array.from(row.querySelectorAll('td')).map(td => {
            let text = td.textContent.trim();
            // Remove commas and quotes, and wrap in quotes if contains comma
            text = text.replace(/"/g, '""');
            if (text.includes(',')) {
                text = `"${text}"`;
            }
            return text;
        });
        csv.push(rowData.join(','));
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '<?php echo $report_type; ?>_report_<?php echo $month_filter; ?>.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include '../footer.php'; ?>
