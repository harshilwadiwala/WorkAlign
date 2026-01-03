<?php
require_once 'config.php';

if (isLoggedIn()) {
    if (isEmployee()) {
        redirect('employee/dashboard.php');
    } elseif (isAdmin()) {
        redirect('admin/dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    $conn = connectDB();
    
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            
            if ($user['role'] === 'employee') {
                redirect('employee/dashboard.php');
            } elseif ($user['role'] === 'admin') {
                redirect('admin/dashboard.php');
            }
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<?php include 'header.php'; ?>

<style>
/* Fix navigation visibility on dark backgrounds */
.navbar-brand,
.navbar-brand span,
.navbar-brand i {
    color: #1e293b !important;
    text-shadow: none !important;
    font-weight: 600 !important;
}

.navbar-nav .nav-link {
    color: #475569 !important;
    text-shadow: none !important;
    font-weight: 500 !important;
}

.navbar-nav .nav-link:hover {
    color: #2563eb !important;
}

/* Fix toggle switch visibility */
.form-check-input:checked {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
}

.form-check-input:checked::after {
    background-color: white !important;
}

/* Fix gear icon and other nav icons */
.navbar i {
    color: #475569 !important;
    text-shadow: none !important;
}

/* Dark mode specific fixes */
.dark-mode .navbar-brand,
.dark-mode .navbar-brand span,
.dark-mode .navbar-brand i {
    color: white !important;
}

.dark-mode .navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.95) !important;
}

.dark-mode .navbar-nav .nav-link:hover {
    color: white !important;
}

.dark-mode .navbar i {
    color: rgba(255, 255, 255, 0.95) !important;
}

/* Login Button Hover Effect */
.btn-primary {
    border: 2px solid transparent !important;
    transition: all 0.3s ease !important;
}

.btn-primary:hover {
    border-color: var(--primary-color) !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15) !important;
}
</style>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-header text-center">
                <h3><i class="fas fa-sign-in-alt me-2"></i>Login</h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['logout_message'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['logout_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['logout_message']); ?>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control email-field" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control password-field" id="password" name="password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        <span>Login</span>
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                Demo Credentials:<br>
                Admin: admin@dayflow.com / password<br>
                Employee: john.doe@company.com / password
            </small>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
