<?php
require_once 'config.php';

// If user is already logged in, show blocked message instead of redirecting
if (isLoggedIn()) {
    // Show blocked page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - WorkAlign</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link href="assets/css/style.css" rel="stylesheet">
        <style>
            /* Fix Access Denied page text visibility */
            .bg-gradient .text-muted {
                color: rgba(255, 255, 255, 0.9) !important;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
            }
            
            .bg-gradient h3 {
                color: white !important;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-gradient">
            <div class="row w-100">
                <div class="col-md-6 mx-auto">
                    <div class="card shadow-lg">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-lock fa-4x text-warning mb-3"></i>
                                <h3>Access Denied</h3>
                                <p class="text-muted">You must log out first before accessing the home page.</p>
                            </div>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="<?php echo isAdmin() ? '/WorkAlign/admin/dashboard.php' : '/WorkAlign/employee/dashboard.php'; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <a href="/WorkAlign/logout.php" class="btn btn-primary">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkAlign - HR Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-gradient">
        <div class="row w-100">
            <div class="col-md-6 mx-auto">
                <div class="text-center mb-4 fade-in">
                    <h1 class="display-4 text-white">
                        <i class="fas fa-briefcase me-3"></i>WorkAlign
                    </h1>
                    <p class="lead text-white">Every workday, perfectly aligned.</p>
                </div>
                
                <div class="card shadow-lg fade-in" style="animation-delay: 0.2s;">
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <div class="dashboard-card">
                                    <div class="icon-container mb-3">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <h4>Employee Portal</h4>
                                    <p class="text-muted">Manage your attendance, leave requests, and payroll</p>
                                    <a href="login.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Employee Login
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="dashboard-card">
                                    <div class="icon-container mb-3">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <h4>Admin Portal</h4>
                                    <p class="text-muted">Manage employees, attendance, and payroll system</p>
                                    <a href="login.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <p class="text-white">
                        <i class="fas fa-user-plus me-2"></i>
                        New to WorkAlign?
                        <a href="signup.php" class="text-decoration-none text-warning fw-bold">Create your account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-gradient {
            background: var(--gradient-primary);
            position: relative;
            overflow: hidden;
        }
        
        .bg-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,96C1248,75,1344,53,1392,42.7L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }
        
        .dashboard-card {
            padding: 2.5rem 2rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .dashboard-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }
        
        .icon-container {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .icon-container i {
            font-size: 1.5rem;
            color: white;
        }
        
        .dashboard-card:hover .icon-container {
            transform: scale(1.1) rotate(5deg);
        }
        
        .btn-lg {
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-lg:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .demo-credentials {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .credential-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .credential-card code {
            background: rgba(0, 0, 0, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
        }
        
        .fade-in {
            animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .icon-container {
                width: 60px;
                height: 60px;
            }
            
            .icon-container i {
                font-size: 1.5rem;
            }
            
            .btn-lg {
                padding: 0.5rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</body>
</html>
