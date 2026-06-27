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

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputUser = trim($_POST['username']);
    $inputPass = $_POST['password'];

    // For Branch 2, only non-permanent users
    $stmt = $conn->prepare("
        SELECT user_id, password_hash 
        FROM users 
        WHERE username = ? AND branch_id = 2 AND is_permanent = 0 
        LIMIT 1
    ");
    $stmt->bind_param("s", $inputUser);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $dbPassHash);
        $stmt->fetch();

        if (hash("sha256", $inputPass) === $dbPassHash) {
            $_SESSION['admin_branch2'] = $inputUser;
            $_SESSION['user_id'] = $userId; // Set user_id in session
            header("Location: billing_b2.php");
            exit;
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Invalid username or password!";
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
    <title>SKY FIRST MOBILE - Branch 2 Login</title>
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
            --gradient-start: #1e3a8a;
            --gradient-end: #60a5fa;
            --card-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .stars-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><circle cx="50" cy="50" r="2" fill="white" opacity="0.8"/><circle cx="150" cy="120" r="1.5" fill="white" opacity="0.6"/><circle cx="300" cy="80" r="2.5" fill="white" opacity="0.9"/><circle cx="400" cy="200" r="1.5" fill="white" opacity="0.5"/><circle cx="550" cy="150" r="2" fill="white" opacity="0.7"/><circle cx="200" cy="300" r="1.5" fill="white" opacity="0.6"/><circle cx="350" cy="350" r="2" fill="white" opacity="0.8"/><circle cx="500" cy="400" r="1.5" fill="white" opacity="0.7"/><circle cx="650" cy="320" r="2" fill="white" opacity="0.5"/><circle cx="750" cy="250" r="1.5" fill="white" opacity="0.6"/></svg>') repeat;
            animation: twinkle 8s infinite;
            z-index: 1;
        }

        .floating-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        .particle:nth-child(1) { width: 4px; height: 4px; top: 20%; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 6px; height: 6px; top: 60%; left: 80%; animation-delay: -3s; }
        .particle:nth-child(3) { width: 3px; height: 3px; top: 80%; left: 20%; animation-delay: -6s; }
        .particle:nth-child(4) { width: 5px; height: 5px; top: 40%; left: 70%; animation-delay: -9s; }
        .particle:nth-child(5) { width: 4px; height: 4px; top: 10%; left: 50%; animation-delay: -12s; }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            z-index: 2;
            animation: slideInUp 0.8s ease forwards;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .branch-header {
            margin-bottom: 30px;
            position: relative;
        }

        .branch-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
            animation: pulse 2s infinite;
        }

        .logo h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--secondary);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 5px;
        }

        .logo h1 span {
            color: var(--primary);
            position: relative;
        }

        .logo h1 span::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            animation: shimmer 3s infinite;
        }

        .logo p {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            transition: var(--transition);
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 1;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
            transform: translateY(-2px);
        }

        .form-control:focus + .input-icon {
            color: var(--secondary);
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            z-index: 2;
            transition: var(--transition);
            padding: 5px;
            border-radius: 50%;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: rgba(52, 152, 219, 0.1);
            transform: translateY(-50%) scale(1.1);
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #2980b9);
            color: white;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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

        .error-message {
            color: var(--danger);
            font-size: 14px;
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.2);
            display: none;
            animation: slideInDown 0.5s ease;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .error-message.active {
            display: flex;
        }

        .change-password-link {
            margin-top: 20px;
            display: block;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .change-password-link:hover {
            color: #2980b9;
            text-decoration: underline;
            transform: translateX(-5px);
        }

        .input-focus-effect {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: var(--transition);
            border-radius: 2px;
        }

        .form-control:focus ~ .input-focus-effect {
            width: 100%;
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

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.8; }
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

        .security-features {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .security-info {
            display: flex;
            justify-content: space-around;
            align-items: center;
            font-size: 12px;
            color: var(--gray);
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .security-item i {
            color: var(--success);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .login-container {
                width: 90%;
                padding: 40px 30px;
                margin: 20px;
            }
            
            .logo h1 {
                font-size: 28px;
            }
            
            .btn {
                padding: 12px 25px;
            }
            
            .security-info {
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                width: 95%;
                padding: 30px 20px;
            }
            
            .logo h1 {
                font-size: 24px;
            }
            
            .branch-badge {
                font-size: 10px;
                padding: 6px 15px;
            }
            
            .particle {
                display: none;
            }
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--primary), var(--success), var(--warning));
            z-index: -1;
            filter: blur(15px);
            opacity: 0.3;
            border-radius: 22px;
            animation: borderGlow 3s infinite alternate;
        }

        @keyframes borderGlow {
            0% { opacity: 0.2; }
            100% { opacity: 0.4; }
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 2px solid white;
            animation: spin 1s linear infinite;
        }

        .btn.loading .loading-spinner {
            display: inline-block;
        }

        .btn.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="stars-bg"></div>
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="login-container">
        <div class="branch-header">
            <div class="branch-badge">
                <i class="fas fa-store"></i> BRANCH 2 ACCESS
            </div>
            <div class="logo">
                <h1><span>SKY FIRST MOBILE</span></h1>
                <p>Secure Staff Login Portal</p>
            </div>
        </div>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username
                </label>
                <div class="input-container">
                    <span class="input-icon"><i class="fas fa-user-circle"></i></span>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Enter your username" required autocomplete="username">
                    <div class="input-focus-effect"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <div class="input-container">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required autocomplete="current-password">
                    <span class="password-toggle" id="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="input-focus-effect"></div>
                </div>
            </div>
            
            <button type="submit" class="btn" id="loginBtn">
                <span class="loading-spinner"></span>
                <span class="btn-text">
                    <i class="fas fa-sign-in-alt"></i> Login to Branch 2
                </span>
            </button>
            
            <?php if (!empty($error)) : ?>
                <div class="error-message active">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <a href="changepassword.php" class="change-password-link">
                <i class="fas fa-key"></i> Change Password
            </a>
        </form>
        
        <div class="security-features">
            <div class="security-info">
                <div class="security-item">
                    <i class="fas fa-shield-alt"></i> Secure Login
                </div>
                <div class="security-item">
                    <i class="fas fa-clock"></i> 24/7 Access
                </div>
                <div class="security-item">
                    <i class="fas fa-user-check"></i> Staff Only
                </div>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('password-toggle');
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        // Password toggle functionality
        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = passwordToggle.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Form submission with loading state
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (username && password) {
                loginBtn.classList.add('loading');
                loginBtn.disabled = true;
                
                // Simulate loading for better UX
                setTimeout(() => {
                    loginBtn.classList.remove('loading');
                    loginBtn.disabled = false;
                }, 2000);
            }
        });

        // Add focus effects to inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            // Auto-focus username field
            document.getElementById('username').focus();
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                loginForm.requestSubmit();
            }
            
            // Tab to navigate between fields
            if (e.key === 'Tab') {
                e.preventDefault();
                const inputs = Array.from(document.querySelectorAll('input'));
                const currentIndex = inputs.indexOf(document.activeElement);
                const nextIndex = (currentIndex + 1) % inputs.length;
                inputs[nextIndex].focus();
            }
        });

        // Add input validation styles
        const usernameInput = document.getElementById('username');
        usernameInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                this.style.borderColor = 'var(--success)';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });

        passwordInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                this.style.borderColor = 'var(--success)';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    </script>
</body>
</html>