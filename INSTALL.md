# Dayflow HR Management System - Installation Guide

## Quick Start

### 1. Database Setup
```sql
-- Import the database.sql file in phpMyAdmin
-- Or run these commands manually:

CREATE DATABASE IF NOT EXISTS dayflow_hr;
USE dayflow_hr;

-- Then copy all SQL from database.sql file
```

### 2. Access the System
- URL: http://localhost/WorkAlign/
- Admin: admin@dayflow.com / password
- Employee: john.doe@company.com / password

## Complete Setup Instructions

### Prerequisites
- XAMPP/WAMP/MAMP installed
- Apache and MySQL services running
- PHP 8.0+ recommended

### Step-by-Step Installation

1. **Download/Extract Files**
   - Place all files in: `C:/xampp/htdocs/WorkAlign/`

2. **Start Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

3. **Database Setup**
   - Open http://localhost/phpmyadmin
   - Click "Import" tab
   - Select `database.sql` file
   - Click "Go"

4. **Verify Installation**
   - Navigate to http://localhost/WorkAlign/
   - Test login with demo credentials

### File Permissions
Create these directories if they don't exist:
```
uploads/
uploads/profile/
```

Set permissions to 755 (read/write/execute for owner, read/execute for others).

## Testing Checklist

### Authentication Tests
- [ ] Admin login works
- [ ] Employee login works  
- [ ] New user registration works
- [ ] Logout functionality works
- [ ] Session management works

### Employee Features
- [ ] Dashboard displays correctly
- [ ] Profile editing works
- [ ] Check-in/check-out functions
- [ ] Leave application works
- [ ] Payroll view displays

### Admin Features
- [ ] Admin dashboard shows statistics
- [ ] Employee management works
- [ ] Attendance correction works
- [ ] Leave approval workflow works
- [ ] Payroll processing works

## Common Issues & Solutions

### "Connection failed" Error
- Check MySQL service is running
- Verify DB credentials in config.php
- Ensure database `dayflow_hr` exists

### White Screen/500 Error
- Check PHP error logs
- Verify file permissions
- Ensure all PHP files are complete

### Upload Issues
- Create uploads/ directory
- Set proper permissions
- Check PHP upload limits

## Support

For additional help:
1. Check XAMPP error logs
2. Verify all setup steps
3. Test with demo accounts first
