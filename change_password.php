<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "sky_first_mobile";
$dbuser = "root";
$dbpass = "";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username) || empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All fields are required!";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match!";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters long!";
    } else {
        // Check if user exists and current password is correct
        $stmt = $conn->prepare("
            SELECT password_hash 
            FROM users 
            WHERE username = ? AND branch_id = 1 AND is_permanent = 1 
            LIMIT 1
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($dbPassHash);
            $stmt->fetch();

            // Verify current password
            if (hash("sha256", $currentPassword) === $dbPassHash) {
                // Update password
                $newPasswordHash = hash("sha256", $newPassword);
                $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ? AND branch_id = 1");
                $updateStmt->bind_param("ss", $newPasswordHash, $username);
                
                if ($updateStmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Error updating password: " . $conn->error;
                }
                $updateStmt->close();
            } else {
                $error = "Current password is incorrect!";
            }
        } else {
            $error = "Username not found or not authorized!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Sky First Mobile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(180deg, #1c2526, #4b5e7e);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: auto;
            position: relative;
            padding: 20px;
        }

        .stars-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><circle cx="50" cy="50" r="3" fill="white" opacity="0.8"/><circle cx="100" cy="200" r="2" fill="white" opacity="0.6"/><circle cx="150" cy="300" r="4" fill="white" opacity="0.9"/><circle cx="200" cy="100" r="2" fill="white" opacity="0.5"/><circle cx="300" cy="400" r="3" fill="white" opacity="0.7"/><circle cx="400" cy="200" r="2" fill="white" opacity="0.6"/><circle cx="500" cy="500" r="4" fill="white" opacity="0.8"/><circle cx="600" cy="300" r="3" fill="white" opacity="0.7"/><circle cx="700" cy="100" r="2" fill="white" opacity="0.5"/><circle cx="750" cy="400" r="3" fill="white" opacity="0.6"/></svg>') repeat;
            animation: twinkle 5s infinite;
            z-index: 1;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }

        .password-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            z-index: 2;
            animation: float 3s ease-in-out infinite;
        }

        .logo {
            margin-bottom: 20px;
        }

        .logo h1 {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
            text-shadow: 0 0 5px rgba(5, 2, 45, 0.5);
        }

        .logo h1 span {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(52, 152, 219, 0.5);
            outline: none;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-10%);
            cursor: pointer;
            color: var(--primary);
            font-size: 18px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .password-toggle:hover {
            color: #2980b9;
            transform: translateY(-10%) scale(1.2);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            background: var(--primary);
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .message {
            color: var(--success);
            font-size: 14px;
            margin-top: 10px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .message.active {
            display: block;
        }

        .error-message {
            color: var(--danger);
            font-size: 14px;
            margin-top: 10px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .error-message.active {
            display: block;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        .branch-indicator {
            background: linear-gradient(90deg, #3498db, #2c3e50);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.3);
        }

        .security-notice {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 12px;
            text-align: left;
            color: #856404;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 16px;
        }

        .form-control.with-icon {
            padding-left: 45px;
        }

        .login-footer {
            margin-top: 20px;
            font-size: 12px;
            color: var(--gray);
        }

        .back-link {
            display: block;
            margin-top: 15px;
            text-align: center;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #2980b9;
            text-decoration: underline;
            transform: translateY(-2px);
        }

        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            background: #eee;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 5px;
        }

        .weak { background: #e74c3c; width: 33%; }
        .medium { background: #f39c12; width: 66%; }
        .strong { background: #2ecc71; width: 100%; }

        .pulse-effect {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }

        @media (max-width: 768px) {
            .password-container {
                width: 90%;
                padding: 20px;
            }

            .logo h1 {
                font-size: 28px;
            }

            .password-toggle {
                right: 10px;
                font-size: 16px;
            }
            
            .branch-indicator {
                font-size: 12px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="stars-bg"></div>
    <div class="password-container">
        <div class="branch-indicator">
            <i class="fas fa-key"></i> Change Password
        </div>
        <div class="logo">
            <h1><span>SKY FIRST MOBILE</span></h1>
        </div>
        
        <div class="security-notice">
            <i class="fas fa-shield-alt"></i> For security, choose a strong password with at least 6 characters
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <div style="position: relative;">
                    <span class="input-icon"><i class="fas fa-user-circle"></i></span>
                    <input type="text" class="form-control with-icon" name="username" id="username" placeholder="Enter username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                <div style="position: relative;">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" class="form-control with-icon" name="current_password" id="current_password" placeholder="Enter current password" required>
                    <span class="password-toggle" data-target="current_password"><i class="fas fa-eye"></i></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                <div style="position: relative;">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" class="form-control with-icon" name="new_password" id="new_password" placeholder="Enter new password" required>
                    <span class="password-toggle" data-target="new_password"><i class="fas fa-eye"></i></span>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="password-strength-bar"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                <div style="position: relative;">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" class="form-control with-icon" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                    <span class="password-toggle" data-target="confirm_password"><i class="fas fa-eye"></i></span>
                </div>
            </div>
            
            <button type="submit" class="btn pulse-effect">
                <i class="fas fa-sync-alt"></i> Change Password
            </button>
            
            <?php if (!empty($message)) : ?>
                <div class="message active">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)) : ?>
                <div class="error-message active">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </form>
        
        <a href="login-branch1.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
        
        <div class="login-footer">
            <i class="fas fa-info-circle"></i> Password must be at least 6 characters long
        </div>
    </div>
    <script>
        // Password toggle functionality for all password fields
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = toggle.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password strength indicator
        const newPasswordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('password-strength-bar');
        
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/\d/)) strength += 1;
            if (password.match(/[^a-zA-Z\d]/)) strength += 1;
            
            strengthBar.className = 'password-strength-bar';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
            } else if (strength <= 1) {
                strengthBar.classList.add('weak');
            } else if (strength <= 2) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });

        // Add focus effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.parentElement.style.transition = 'transform 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>