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
$messageClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $oldPassword = $_POST['old-password'];
    $newPassword = $_POST['new-password'];
    $confirmPassword = $_POST['confirm-password'];

    // Fetch user from DB
    $sql = "SELECT user_id, password_hash FROM users WHERE username = ? AND branch_id = 2 AND is_permanent = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $message = "User not found!";
        $messageClass = "error";
    } else {
        $stmt->bind_result($userId, $dbPasswordHash);
        $stmt->fetch();

        if (hash("sha256", $oldPassword) !== $dbPasswordHash) {
            $message = "Old password is incorrect!";
            $messageClass = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New password and confirm password do not match!";
            $messageClass = "error";
        } elseif (strlen($newPassword) < 8) {
            $message = "New password must be at least 8 characters long!";
            $messageClass = "error";
        } else {
            // Update password using SHA-256
            $newHash = hash("sha256", $newPassword);
            $updateSql = "UPDATE users SET password_hash = ? WHERE user_id = ? AND branch_id = 2";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $newHash, $userId);
            if ($updateStmt->execute()) {
                $message = "Password changed successfully!";
                $messageClass = "success";
                header("Location: login-branch2.php");
                exit;
            } else {
                $message = "Something went wrong, please try again.";
                $messageClass = "error";
            }
            $updateStmt->close();
        }
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKY FIRST MOBILE - Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: rgba(255, 255, 255, 0.95);
            --dark: #2c3e50;
            --gradient-start: #1e3a8a;
            --gradient-end: #60a5fa;
            --card-shadow: 0 20px 40px rgba(0,0,0,0.1), 0 10px 20px rgba(0,0,0,0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .circuit-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><path fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="10" d="M100,100H700M100,200H700M100,300H700M100,400H700M100,500H700M100,600H700M100,700H700M100,100V700M200,100V700M300,100V700M400,100V700M500,100V700M600,100V700M700,100V700"/><circle cx="150" cy="150" r="10" fill="rgba(52,152,219,0.5)"/><circle cx="250" cy="250" r="10" fill="rgba(52,152,219,0.5)"/><circle cx="350" cy="350" r="10" fill="rgba(52,152,219,0.5)"/><circle cx="450" cy="450" r="10" fill="rgba(52,152,219,0.5)"/><circle cx="550" cy="550" r="10" fill="rgba(52,152,219,0.5)"/></svg>') repeat;
            opacity: 0.2;
            z-index: 1;
            animation: float 20s infinite linear;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 80%;
            animation-delay: -5s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: -10s;
        }

        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 30%;
            left: 70%;
            animation-delay: -7s;
        }

        .login-container {
            background: var(--light);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            z-index: 2;
            animation: slideInUp 0.8s ease forwards;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-header {
            margin-bottom: 30px;
            position: relative;
        }

        .logo-header h2 {
            margin-bottom: 10px;
            color: var(--secondary);
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .logo-header p {
            color: var(--primary);
            font-size: 14px;
            font-weight: 500;
        }

        .security-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
            animation: pulse 2s infinite;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .input-container {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            z-index: 2;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            font-size: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            transition: var(--transition);
            position: relative;
            z-index: 1;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
            transform: translateY(-2px);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            cursor: pointer;
            z-index: 2;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: #2980b9;
            transform: translateY(-50%) scale(1.1);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), #2980b9);
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.4);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            font-size: 14px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .back-link:hover {
            color: #2980b9;
            text-decoration: underline;
            transform: translateX(-5px);
        }

        .message {
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            animation: slideInDown 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            overflow: hidden;
            position: relative;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: var(--transition);
        }

        .weak { background: var(--danger); width: 33%; }
        .medium { background: var(--warning); width: 66%; }
        .strong { background: var(--success); width: 100%; }

        .password-hints {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
            text-align: left;
        }

        @keyframes slideInUp {
            0% { 
                transform: translateY(50px);
                opacity: 0;
            }
            100% { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideInDown {
            0% { 
                transform: translateY(-20px);
                opacity: 0;
            }
            100% { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(0) rotate(0deg);
            }
            33% { 
                transform: translateY(-20px) rotate(120deg);
            }
            66% { 
                transform: translateY(10px) rotate(240deg);
            }
        }

        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 6px 12px rgba(52, 152, 219, 0.4);
            }
        }

        @keyframes shimmer {
            0% { 
                background-position: -200px 0;
            }
            100% { 
                background-position: 200px 0;
            }
        }

        .input-focus-effect {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: var(--transition);
        }

        .form-group input:focus ~ .input-focus-effect {
            width: 100%;
        }

        @media (max-width: 768px) {
            .login-container {
                width: 90%;
                padding: 30px 20px;
            }
            
            .logo-header h2 {
                font-size: 24px;
            }
            
            .btn {
                padding: 12px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px;
            }
            
            .login-container {
                width: 100%;
                padding: 25px 15px;
            }
            
            .shape {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="circuit-bg"></div>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-container">
        <div class="logo-header">
            <h2><i class="fas fa-mobile-alt"></i> SKY FIRST MOBILE</h2>
            <p>Branch 2 - Password Change Portal</p>
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i> Secure Password Update
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <div class="input-container">
                    <span class="input-icon"><i class="fas fa-user-circle"></i></span>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>
                    <div class="input-focus-effect"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="old-password"><i class="fas fa-lock"></i> Current Password</label>
                <div class="input-container">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" name="old-password" id="old-password" placeholder="Enter current password" required>
                    <span class="password-toggle" onclick="togglePassword('old-password')">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="input-focus-effect"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new-password"><i class="fas fa-lock"></i> New Password</label>
                <div class="input-container">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" name="new-password" id="new-password" placeholder="Enter new password" required oninput="checkPasswordStrength()">
                    <span class="password-toggle" onclick="togglePassword('new-password')">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="input-focus-effect"></div>
                </div>
                <div class="password-strength">
                    <div class="strength-meter" id="strength-meter"></div>
                </div>
                <div class="password-hints" id="password-hints">
                    Password must be at least 8 characters long
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm-password"><i class="fas fa-lock"></i> Confirm New Password</label>
                <div class="input-container">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="Confirm new password" required oninput="checkPasswordMatch()">
                    <span class="password-toggle" onclick="togglePassword('confirm-password')">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="input-focus-effect"></div>
                </div>
                <div class="password-hints" id="match-hint"></div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sync-alt"></i> Update Password
            </button>
            
            <?php if (!empty($message)) : ?>
                <div class="message <?php echo htmlspecialchars($messageClass); ?>">
                    <i class="fas fa-<?php echo $messageClass === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <a href="login-branch2.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentNode.querySelector('.password-toggle i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('new-password').value;
            const meter = document.getElementById('strength-meter');
            const hints = document.getElementById('password-hints');
            
            let strength = 0;
            let hintsText = [];
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            if (password.length === 0) {
                meter.className = 'strength-meter';
                hints.textContent = 'Password must be at least 8 characters long';
            } else if (password.length < 8) {
                meter.className = 'strength-meter weak';
                hints.textContent = 'Password too short (min 8 characters)';
            } else {
                if (strength === 1) {
                    meter.className = 'strength-meter weak';
                    hints.textContent = 'Weak password';
                } else if (strength === 2 || strength === 3) {
                    meter.className = 'strength-meter medium';
                    hints.textContent = 'Medium strength password';
                } else if (strength === 4) {
                    meter.className = 'strength-meter strong';
                    hints.textContent = 'Strong password!';
                }
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const matchHint = document.getElementById('match-hint');
            
            if (confirmPassword.length === 0) {
                matchHint.textContent = '';
            } else if (password !== confirmPassword) {
                matchHint.textContent = 'Passwords do not match';
                matchHint.style.color = 'var(--danger)';
            } else {
                matchHint.textContent = 'Passwords match!';
                matchHint.style.color = 'var(--success)';
            }
        }

        // Add focus effects to inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>