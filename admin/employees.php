<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../employee/dashboard.php');
}

$conn = connectDB();

$errors = [];
$success = '';

// Handle employee addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_employee') {
    $employee_id = sanitizeInput($_POST['employee_id']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $department = sanitizeInput($_POST['department']);
    $designation = sanitizeInput($_POST['designation']);
    $date_of_joining = sanitizeInput($_POST['date_of_joining']);
    $password = sanitizeInput($_POST['password']);
    
    // Validation
    if (empty($employee_id)) $errors[] = "Employee ID is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    
    if (empty($errors)) {
        // Check if employee ID already exists
        $stmt_check = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
        $stmt_check->bind_param("s", $employee_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Employee ID already exists";
        }
        
        // Check if email already exists
        $stmt_email = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt_email->bind_param("s", $email);
        $stmt_email->execute();
        $result_email = $stmt_email->get_result();
        
        if ($result_email->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO employees (employee_id, first_name, last_name, email, phone, address, department, designation, date_of_joining, password, role, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'employee', 1, 1)");
            $stmt->bind_param("ssssssssss", $employee_id, $first_name, $last_name, $email, $phone, $address, $department, $designation, $date_of_joining, $hashed_password);
            
            if ($stmt->execute()) {
                // Create leave balance for new employee
                $current_year = date('Y');
                $stmt_leave = $conn->prepare("INSERT INTO leave_balance (employee_id, paid_leave_balance, sick_leave_balance, unpaid_leave_balance, year) VALUES (?, 12, 8, 0, ?)");
                $stmt_leave->bind_param("si", $employee_id, $current_year);
                $stmt_leave->execute();
                
                $success = "Employee added successfully!";
                $stmt_leave->close();
            } else {
                $errors[] = "Failed to add employee. Please try again.";
            }
            
            $stmt->close();
        }
        
        $stmt_check->close();
        $stmt_email->close();
    }
}

// Handle employee status toggle
if (isset($_GET['toggle_status']) && isset($_GET['employee_id'])) {
    $employee_id = sanitizeInput($_GET['employee_id']);
    
    $stmt = $conn->prepare("UPDATE employees SET is_active = NOT is_active WHERE employee_id = ? AND role = 'employee'");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: employees.php");
    exit();
}

// Get employees with search and pagination
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "WHERE role = 'employee'";
if (!empty($search)) {
    $where_clause .= " AND (first_name LIKE ? OR last_name LIKE ? OR employee_id LIKE ? OR email LIKE ? OR department LIKE ?)";
}

// Get total records
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM employees $where_clause");
    $stmt_total->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM employees $where_clause");
}

$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get employees
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM employees $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sssssii", $search_param, $search_param, $search_param, $search_param, $search_param, $per_page, $offset);
} else {
    $stmt = $conn->prepare("SELECT * FROM employees $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $per_page, $offset);
}

$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_total->close();
$stmt->close();
$conn->close();
?>

<?php include '../header.php'; ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>Employee Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="fas fa-plus me-2"></i>Add Employee
            </button>
        </div>
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

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Employee List</h5>
    </div>
    <div class="card-body">
        <!-- Search -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="employees.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">Showing <?php echo count($employees); ?> of <?php echo $total_records; ?> employees</small>
            </div>
        </div>

        <!-- Employee Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="employees-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($employee['profile_picture']): ?>
                                        <img src="../uploads/profile/<?php echo htmlspecialchars($employee['profile_picture']); ?>" 
                                             alt="Profile" 
                                             class="rounded-circle me-2" 
                                             style="width: 30px; height: 30px; object-fit: cover;">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td><?php echo htmlspecialchars($employee['department'] ?: 'Not Assigned'); ?></td>
                            <td><?php echo htmlspecialchars($employee['designation'] ?: 'Not Assigned'); ?></td>
                            <td><?php echo formatDate($employee['date_of_joining']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $employee['is_active'] ? 'status-present' : 'status-absent'; ?>">
                                    <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewEmployee('<?php echo $employee['employee_id']; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editEmployee('<?php echo $employee['employee_id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="employees.php?toggle_status=1&employee_id=<?php echo $employee['employee_id']; ?>" 
                                       class="btn btn-sm <?php echo $employee['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                       onclick="return confirm('Are you sure you want to <?php echo $employee['is_active'] ? 'deactivate' : 'activate'; ?> this employee?')">
                                        <i class="fas <?php echo $employee['is_active'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Employee pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_employee">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee ID</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control email-field" id="email" name="email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">Select Department</option>
                                    <option value="IT">IT</option>
                                    <option value="HR">HR</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Operations">Operations</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="designation" class="form-label">Designation</label>
                                <input type="text" class="form-control" id="designation" name="designation">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control phone-field" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_joining" class="form-label">Date of Joining</label>
                                <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control password-field" id="password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewEmployee(employeeId) {
    // Implement view employee functionality
    alert('View employee: ' + employeeId);
}

function editEmployee(employeeId) {
    // Implement edit employee functionality
    alert('Edit employee: ' + employeeId);
}
</script>

<?php include '../footer.php'; ?>
