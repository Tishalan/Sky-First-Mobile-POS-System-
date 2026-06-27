<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "sky_first_mobile";   // your database name
$dbuser = "root";          // your DB username
$dbpass = "";              // your DB password

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputUser = trim($_POST['username']);
    $inputPass = $_POST['password'];

    // Only main branch permanent admin
    $stmt = $conn->prepare("
        SELECT password_hash 
        FROM users 
        WHERE username = ? AND branch_id = 1 AND is_permanent = 1 
        LIMIT 1
    ");
    $stmt->bind_param("s", $inputUser);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($dbPassHash);
        $stmt->fetch();

        // Check hashed password
        if (hash("sha256", $inputPass) === $dbPassHash) {
            $_SESSION['admin_main'] = $inputUser;
            header("Location: billing.php");
            exit;
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Invalid username or password!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Shop - Branch 1 Login</title>
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
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><circle cx="50" cy="50" r="3" fill="white" opacity="0.8"/><circle cx="100" cy="200" r="2" fill="white" opacity="0.6"/><circle cx="150" cy="300" r="4" fill="white" opacity="0.9"/><circle cx="200" cy="100" r="2" fill="white" opacity="0.5"/><circle cx="300" cy="400" r="3" fill="white" opacity="0.7"/><circle cx="400" cy="200" r="2" fill="white" opacity="0.6"/><circle cx="500" cy="500" r="4" fill="white" opacity="0.8"/><circle cx="600" cy="300" r="3" fill="white" opacity="0.7"/><circle cx="700" cy="100" r="2" fill="white" opacity="0.5"/><circle cx="750" cy="400" r="3" fill="white" opacity="0.6"/></svg>') repeat;
            animation: twinkle 5s infinite;
            z-index: 1;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
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
            align-items: center;
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

        /* New CSS additions */
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

        .pulse-effect {
            animation: pulse 2s infinite;
        }

        .change-password-link {
            display: block;
            margin-top: 15px;
            text-align: center;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .change-password-link:hover {
            color: #2980b9;
            text-decoration: underline;
            transform: translateY(-2px);
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }

        @media (max-width: 768px) {
            .login-container {
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
    <div class="login-container">
        <div class="branch-indicator">
            <i class="fas fa-building"></i> Main Branch Access
        </div>
        <div class="logo">
            <h1><span>SKY FIRST MOBILE</span></h1>
        </div>
        
        <div class="security-notice">
            <i class="fas fa-shield-alt"></i> Secure login for authorized personnel only
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
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div style="position: relative;">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" class="form-control with-icon" name="password" id="password" placeholder="Enter password" required>
                    <span class="password-toggle"><i class="fas fa-eye"></i></span>
                </div>
            </div>
            <button type="submit" class="btn pulse-effect">
                <i class="fas fa-sign-in-alt"></i> Login to Main Branch
            </button>
            <?php if (!empty($error)) : ?>
                <div class="error-message active">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </form>
        
        <a href="change_password.php" class="change-password-link">
            <i class="fas fa-key"></i> Change Password
        </a>
        
        <div class="login-footer">
            <i class="fas fa-info-circle"></i> Restricted access • Main branch administrators only
        </div>
    </div>
    <script>
        // Password toggle functionality
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.querySelector('.password-toggle i');
        
        passwordToggle.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
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