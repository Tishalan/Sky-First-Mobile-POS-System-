<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sky_first_mobile";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// You can add session start if needed for future login checks
session_start();

// No dynamic content on this page yet, but connection is established
// Close connection at the end if not needed further: $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Shop - Welcome</title>
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        .particles-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(0) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }

        .wave-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg"><path fill="rgba(255,255,255,0.1)" fill-opacity="1" d="M0,160L48,176C96,192,192,224,288,213.3C384,203,480,149,576,144C672,139,768,181,864,181.3C960,181,1056,139,1152,122.7C1248,107,1344,117,1392,122.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            animation: wave 10s infinite linear;
            z-index: 2;
        }

        @keyframes wave {
            0% { transform: translateX(0); }
            50% { transform: translateX(-50%); }
            100% { transform: translateX(0); }
        }

        .container {
            text-align: center;
            z-index: 3;
            position: relative;
            font-size: 30px;
            padding: 30px;
            width: 100%;
            max-width: 1200px;
        }

        .logo {
            background: linear-gradient(to right, var(--secondary), var(--dark));
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 50px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            animation: glow 2s ease-in-out infinite alternate, slideInDown 1s ease-out;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .logo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .shop-logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(52, 152, 219, 0.7);
            margin-bottom: 20px;
            transition: transform 0.5s ease, box-shadow 0.5s ease;
            animation: bounce 1.5s ease-in-out, rotate 10s infinite linear;
            overflow: hidden;
            position: relative;
            z-index: 2;
            border: 3px solid var(--primary);
        }

        .shop-logo img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .shop-logo:hover {
            transform: scale(1.15) rotate(10deg);
            box-shadow: 0 0 35px rgba(52, 152, 219, 0.9);
        }

        .shop-logo:hover img {
            transform: scale(1.1);
        }

        .logo h1 {
            font-size: 65px;
            font-weight: 800;
            background: linear-gradient(45deg, #42a5f5, #bb86fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            letter-spacing: 2px;
        }

        .logo p {
            font-size: 20px;
            color: var(--light);
            margin-bottom: 0;
            position: relative;
            z-index: 2;
            font-style: italic;
        }

        h2 {
            margin-bottom: 60px;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            font-size: 42px;
            font-weight: 300;
            letter-spacing: 3px;
            animation: fadeIn 1.5s ease-out;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, transparent, white, transparent);
            animation: expand 2s infinite;
        }

        @keyframes expand {
            0% { width: 0; }
            50% { width: 150px; }
            100% { width: 0; }
        }

        .branch-container {
            display: flex;
            gap: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .branch-card {
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            padding: 30px 25px;
            width: 280px;
            text-align: center;
            cursor: pointer;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease forwards;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .branch-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.7s;
        }

        .branch-card:hover::before {
            left: 100%;
        }

        .branch-card:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            background: linear-gradient(145deg, var(--primary), #2980b9);
            color: white;
        }

        .branch-card h3 {
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .branch-card i {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--primary);
            transition: all 0.5s ease;
            display: block;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .branch-card:hover i {
            color: white;
            transform: scale(1.2) rotate(10deg);
        }

        .branch-card::after {
            content: '\f138';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 24px;
            color: var(--primary);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateX(-20px);
        }

        .branch-card:hover::after {
            opacity: 1;
            transform: translateX(0);
            color: white;
        }

        .branch-info {
            font-size: 16px;
            margin-top: 10px;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .branch-card:hover .branch-info {
            opacity: 1;
            transform: translateY(5px);
        }

        @keyframes glow {
            0% { 
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            }
            100% { 
                box-shadow: 0 10px 30px rgba(52, 152, 219, 0.6), 0 0 40px rgba(52, 152, 219, 0.4);
            }
        }

        @keyframes slideInDown {
            0% { 
                transform: translateY(-100px);
                opacity: 0;
            }
            100% { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            0% { 
                opacity: 0;
                transform: translateY(50px);
            }
            100% { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Pulse animation for cards */
        .branch-card:nth-child(1) {
            animation-delay: 0.2s;
        }
        .branch-card:nth-child(2) {
            animation-delay: 0.4s;
        }

        .branch-card:hover:nth-child(1) {
            animation: pulse 1s infinite;
        }
        .branch-card:hover:nth-child(2) {
            animation: pulse 1s infinite 0.2s;
        }

        @keyframes pulse {
            0% { transform: translateY(-15px) scale(1.05); }
            50% { transform: translateY(-18px) scale(1.07); }
            100% { transform: translateY(-15px) scale(1.05); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .logo {
                padding: 20px;
            }

            .logo h1 {
                font-size: 45px;
            }

            .shop-logo {
                width: 100px;
                height: 100px;
            }

            h2 {
                font-size: 32px;
                margin-bottom: 50px;
            }

            .branch-container {
                flex-direction: column;
                align-items: center;
                gap: 30px;
            }

            .branch-card {
                width: 85%;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .logo h1 {
                font-size: 36px;
            }

            .shop-logo {
                width: 80px;
                height: 80px;
            }

            h2 {
                font-size: 28px;
                margin-bottom: 40px;
            }

            .branch-card {
                width: 95%;
                padding: 25px 20px;
            }

            .branch-card h3 {
                font-size: 28px;
            }

            .branch-card i {
                font-size: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="particles-container" id="particles"></div>
    <div class="wave-bg"></div>
    <div class="container">
        <div class="logo">
            <div class="shop-logo">
                <img src="assets/images/logo.jpg" alt="Sky First Mobile Logo">
            </div>
            <h1>WELCOME TO SKY FIRST MOBILE</h1>
            <p>Your Trusted Mobile Partner</p>
        </div>
        <h2>Select a Branch</h2>
        <div class="branch-container">
            <div class="branch-card" onclick="window.location.href='login-branch1.php'">
                <i class="fas fa-store"></i>
                <h3>Main Branch</h3>
                <div class="branch-info">Downtown Location</div>
            </div>
            <div class="branch-card" onclick="window.location.href='login-branch2.php'">
                <i class="fas fa-store-alt"></i>
                <h3>Sub Branch</h3>
                <div class="branch-info">Uptown Location</div>
            </div>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size between 3px and 8px
                const size = Math.random() * 5 + 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random animation duration between 10s and 25s
                const duration = Math.random() * 15 + 10;
                particle.style.animationDuration = `${duration}s`;
                
                // Random delay
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                container.appendChild(particle);
            }
        }
        
        // Add click effects to branch cards
        document.querySelectorAll('.branch-card').forEach(card => {
            card.addEventListener('click', function() {
                // Add ripple effect
                const ripple = document.createElement('div');
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.6)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s linear';
                ripple.style.top = '50%';
                ripple.style.left = '50%';
                ripple.style.width = '100%';
                ripple.style.height = '100%';
                
                this.style.position = 'relative';
                this.appendChild(ripple);
                
                // Navigate after animation
                setTimeout(() => {
                    window.location.href = this.onclick.toString().match(/window\.location\.href='([^']+)'/)[1];
                }, 300);
            });
        });
        
        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2.5);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Initialize particles when page loads
        window.addEventListener('load', createParticles);
    </script>
</body>
</html>
<?php
// Close DB connection if not used further
$conn->close();
?>