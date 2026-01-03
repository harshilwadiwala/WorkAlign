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

// Get payroll records
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total records
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM payroll WHERE employee_id = ?");
$stmt_total->bind_param("s", $employee_id);
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get payroll records with pagination
$stmt = $conn->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY month DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $employee_id, $per_page, $offset);
$stmt->execute();
$payroll_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get current month payroll
$current_month = date('Y-m');
$stmt_current = $conn->prepare("SELECT * FROM payroll WHERE employee_id = ? AND month = ?");
$stmt_current->bind_param("ss", $employee_id, $current_month);
$stmt_current->execute();
$current_payroll = $stmt_current->get_result()->fetch_assoc();

// Get salary structure if current payroll not available
if (!$current_payroll) {
    $stmt_salary = $conn->prepare("SELECT * FROM salary_structure WHERE employee_id = ? ORDER BY effective_date DESC LIMIT 1");
    $stmt_salary->bind_param("s", $employee_id);
    $stmt_salary->execute();
    $salary_structure = $stmt_salary->get_result()->fetch_assoc();
    
    if ($salary_structure) {
        $current_payroll = [
            'basic_salary' => $salary_structure['basic_salary'],
            'hra' => $salary_structure['hra'],
            'da' => $salary_structure['da'],
            'ta' => $salary_structure['ta'],
            'pf_deduction' => $salary_structure['pf_deduction'],
            'tax_deduction' => $salary_structure['tax_deduction'],
            'other_deductions' => $salary_structure['other_deductions'],
            'total_salary' => $salary_structure['total_salary'],
            'working_days' => date('t'), // Total days in current month
            'present_days' => 0,
            'leave_days' => 0,
            'absent_days' => 0,
            'status' => 'pending'
        ];
    }
    $stmt_salary->close();
}

$stmt_total->close();
$stmt->close();
$stmt_current->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-money-bill me-2"></i>Payroll Details</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo isset($current_payroll['total_salary']) ? '₹' . number_format($current_payroll['total_salary'], 2) : 'N/A'; ?></h3>
            <p><i class="fas fa-hand-holding-usd me-2"></i>Current Month Salary</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo isset($current_payroll['present_days']) ? $current_payroll['present_days'] : '0'; ?></h3>
            <p><i class="fas fa-calendar-check me-2"></i>Days Present</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo isset($current_payroll['leave_days']) ? $current_payroll['leave_days'] : '0'; ?></h3>
            <p><i class="fas fa-plane me-2"></i>Leave Days</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <h3><?php echo isset($current_payroll['status']) ? ucfirst($current_payroll['status']) : 'Pending'; ?></h3>
            <p><i class="fas fa-info-circle me-2"></i>Payment Status</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calculator me-2"></i>Current Month Breakdown</h5>
            </div>
            <div class="card-body">
                <?php if ($current_payroll): ?>
                    <h6 class="text-primary mb-3"><?php echo date('F Y'); ?></h6>
                    
                    <div class="mb-3">
                        <h6>Earnings</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Basic Salary:</span>
                            <strong>₹<?php echo number_format($current_payroll['basic_salary'], 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>HRA:</span>
                            <strong>₹<?php echo number_format($current_payroll['hra'], 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>DA:</span>
                            <strong>₹<?php echo number_format($current_payroll['da'], 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>TA:</span>
                            <strong>₹<?php echo number_format($current_payroll['ta'], 2); ?></strong>
                        </div>
                        
                        <h6>Deductions</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span>PF Deduction:</span>
                            <strong>₹<?php echo number_format($current_payroll['pf_deduction'], 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Tax Deduction:</span>
                            <strong>₹<?php echo number_format($current_payroll['tax_deduction'], 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Other Deductions:</span>
                            <strong>₹<?php echo number_format($current_payroll['other_deductions'], 2); ?></strong>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <h5>Net Salary:</h5>
                            <h5 class="text-success">₹<?php echo number_format($current_payroll['total_salary'], 2); ?></h5>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No salary structure available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Payroll History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="payroll-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Basic Salary</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Days Worked</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payroll_records as $payroll): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($payroll['month'] . '-01')); ?></td>
                                    <td>₹<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                    <td>₹<?php echo number_format($payroll['hra'] + $payroll['da'] + $payroll['ta'], 2); ?></td>
                                    <td>₹<?php echo number_format($payroll['pf_deduction'] + $payroll['tax_deduction'] + $payroll['other_deductions'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($payroll['total_salary'], 2); ?></strong></td>
                                    <td>
                                        <?php echo $payroll['present_days']; ?> / <?php echo $payroll['working_days']; ?>
                                        <?php if ($payroll['leave_days'] > 0): ?>
                                            <br><small class="text-muted"><?php echo $payroll['leave_days']; ?> leave</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $payroll['status']; ?>">
                                            <?php echo ucfirst($payroll['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Payroll pagination">
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
