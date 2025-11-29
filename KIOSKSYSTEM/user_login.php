<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'

// Ensure students table exists
try {
    $pdo = get_pdo();
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS `students` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` VARCHAR(64) NOT NULL UNIQUE,
            `name` VARCHAR(255) NOT NULL,
            `department` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `password_hash` VARCHAR(255) DEFAULT NULL,
            `facebook_id` VARCHAR(255) DEFAULT NULL,
            `login_type` ENUM("student","email","facebook") NOT NULL DEFAULT "student",
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_student_id` (`student_id`),
            UNIQUE KEY `idx_email` (`email`),
            INDEX `idx_login_type` (`login_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
} catch (PDOException $e) {
    // Table might already exist, continue
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'register') {
        // Registration - can use either Student ID or Email
        $student_id = trim($_POST['student_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Determine registration type
        $use_student_id = !empty($student_id);
        $use_email = !empty($email);
        
        if (!$use_student_id && !$use_email) {
            $error = 'Please provide either Student ID or Email address.';
        } elseif ($name === '') {
            $error = 'Please enter your full name.';
        } elseif ($use_student_id && $department === '') {
            $error = 'Please enter your department.';
        } elseif ($use_email && ($password === '' || strlen($password) < 6)) {
            $error = 'Password must be at least 6 characters for email registration.';
        } elseif ($use_email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $pdo = get_pdo();
                
                if ($use_student_id) {
                    // Check if student ID already exists
                    $stmt = $pdo->prepare('SELECT id FROM students WHERE student_id = ? LIMIT 1');
                    $stmt->execute([$student_id]);
                    if ($stmt->fetch()) {
                        $error = 'Student ID already registered. Please login instead.';
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO students (student_id, name, department, login_type) VALUES (?, ?, ?, "student")');
                        $stmt->execute([$student_id, $name, $department]);
                        $success = 'Registration successful! You can now login.';
                        $mode = 'login';
                    }
                } else {
                    // Email registration
                    $stmt = $pdo->prepare('SELECT id FROM students WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'Email already registered. Please login instead.';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO students (email, password_hash, name, login_type) VALUES (?, ?, ?, "email")');
                        $stmt->execute([$email, $password_hash, $name]);
                        $success = 'Registration successful! You can now login.';
                        $mode = 'login';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    } else {
        // Login - can use either Student ID or Email
        $student_id = trim($_POST['student_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Determine login type
        $use_student_id = !empty($student_id);
        $use_email = !empty($email);
        
        if (!$use_student_id && !$use_email) {
            $error = 'Please provide either Student ID or Email address.';
        } elseif ($use_student_id) {
            // Student ID login
            if ($name === '' || $department === '') {
                $error = 'Please fill in all fields for student login.';
            } else {
                try {
                    $pdo = get_pdo();
                    $stmt = $pdo->prepare('SELECT * FROM students WHERE student_id = ? AND name = ? AND department = ? LIMIT 1');
                    $stmt->execute([$student_id, $name, $department]);
                    $student = $stmt->fetch();
                    if ($student) {
                        $_SESSION['student'] = [
                            'id' => (int)$student['id'],
                            'student_id' => $student['student_id'],
                            'name' => $student['name'],
                            'department' => $student['department'],
                            'login_type' => 'student'
                        ];
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Invalid credentials. Please check your information or register.';
                    }
                } catch (PDOException $e) {
                    $error = 'Login failed: ' . $e->getMessage();
                }
            }
        } else {
            // Email login
            if ($password === '') {
                $error = 'Please enter your password.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                try {
                    $pdo = get_pdo();
                    $stmt = $pdo->prepare('SELECT * FROM students WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $student = $stmt->fetch();
                    if ($student && password_verify($password, (string)$student['password_hash'])) {
                        $_SESSION['student'] = [
                            'id' => (int)$student['id'],
                            'email' => $student['email'],
                            'name' => $student['name'] ?? '',
                            'login_type' => 'email'
                        ];
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Invalid email or password.';
                    }
                } catch (PDOException $e) {
                    $error = 'Login failed: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $mode === 'register' ? 'Student Registration' : 'Student Login'; ?> - DLSP Registrar Kiosk</title>
    <link rel="icon" type="image/jpeg" href="favicon.php" />
    <link rel="shortcut icon" type="image/jpeg" href="favicon.php" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body.kiosk {
            background: url('assets/images/university-entrance.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
        }
        .mode-toggle {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(10, 60, 46, 0.3);
        }
        .mode-btn {
            flex: 1;
            padding: 12px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: #0f1f1a;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }
        .mode-btn.active {
            border-bottom-color: #0A3C2E;
            color: #0A3C2E;
            background: rgba(13, 176, 122, 0.1);
        }
        .mode-btn:hover {
            background: rgba(13, 176, 122, 0.05);
        }
        .form-section {
            margin-bottom: 20px;
        }
        .form-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #0A3C2E;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(10, 60, 46, 0.2);
        }
        .form-divider {
            text-align: center;
            margin: 20px 0;
            color: #0f1f1a;
            font-size: 14px;
            position: relative;
        }
        .form-divider::before,
        .form-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: rgba(10, 60, 46, 0.2);
        }
        .form-divider::before {
            left: 0;
        }
        .form-divider::after {
            right: 0;
        }
        #userLoginForm input[type="text"],
        #userLoginForm input[type="email"],
        #userLoginForm input[type="password"],
        #userLoginForm input[type="number"],
        #userLoginForm input[type="tel"],
        #userLoginForm select,
        #userLoginForm textarea {
            background: rgba(13, 176, 122, 0.14) !important;
            border: 1px solid #0A3C2E !important;
            color: #0f1f1a;
        }
        #userLoginForm input::placeholder {
            color: rgba(15, 31, 26, 0.6);
        }
        .switch-link {
            text-align: center;
            margin-top: 15px;
            color: #0f1f1a;
            font-size: 14px;
        }
        .switch-link a {
            color: #0A3C2E;
            font-weight: 600;
            text-decoration: none;
        }
        .switch-link a:hover {
            text-decoration: underline;
        }
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 20px;
            border: 1px solid #0A3C2E;
            border-radius: 999px;
            background: rgba(13, 176, 122, 0.14);
            color: #0f1f1a;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 16px;
        }
        .social-btn:hover {
            background: rgba(13, 176, 122, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .social-btn.facebook {
            background: rgba(59, 89, 152, 0.2);
            border-color: #3b5998;
            color: #ffffff;
        }
        .social-btn.facebook:hover {
            background: rgba(59, 89, 152, 0.3);
        }
        .social-btn.email {
            background: rgba(13, 176, 122, 0.2);
        }
        .social-btn.email:hover {
            background: rgba(13, 176, 122, 0.3);
        }
        .social-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        .social-btn.email .social-icon {
            color: #0f1f1a;
        }
        .social-btn.facebook .social-icon {
            color: #ffffff;
        }
    </style>
</head>
<body class="kiosk">
    <div class="container">
        <header class="ticket-header">
            <img class="school-logo" src="assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
            <div class="title-center">
                <h1>Registrar Kiosk</h1>
                <p>Dalubhasaan ng Lungsod ng San Pablo</p>
            </div>
        </header>

        <main class="ticket-card" style="max-width:480px;" role="region" aria-labelledby="loginTitle">
            <div id="loginTitle" class="ticket-heading"><?php echo $mode === 'register' ? 'Student Registration' : 'Student Login'; ?></div>
            
            <?php if ($error !== ''): ?>
                <div class="eta-line" style="color:#b91c1c; margin-bottom:12px; padding:10px; background:rgba(185,28,28,0.1); border-radius:8px;">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success !== ''): ?>
                <div class="eta-line" style="color:#059669; margin-bottom:12px; padding:10px; background:rgba(5,150,105,0.1); border-radius:8px;">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="mode-toggle">
                <a href="?mode=login" class="mode-btn <?php echo $mode === 'login' ? 'active' : ''; ?>">Login</a>
                <a href="?mode=register" class="mode-btn <?php echo $mode === 'register' ? 'active' : ''; ?>">Register</a>
            </div>

            <form id="userLoginForm" method="post" style="display:flex; flex-direction:column; gap:12px;">
                <input type="hidden" name="action" value="<?php echo $mode; ?>" />
                
                <!-- Student Info Section -->
                <div class="form-section">
                    <div class="form-section-title">Student Information</div>
                    <div class="step-field">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" placeholder="e.g. 2023-000123 (Optional if using email)" />
                    </div>
                    <div class="step-field">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required />
                    </div>
                    <div class="step-field">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" placeholder="College / Department (Required if using Student ID)" />
                    </div>
                </div>

                <div class="form-divider">OR</div>

                <!-- Email and Facebook Section -->
                <div class="form-section">
                    <div class="form-section-title">Other Registration (Email or Facebook)</div>
                    
                    <!-- Email Option -->
                    <button type="button" class="social-btn email" onclick="showEmailForm()" id="emailBtn" style="width: 100%;">
                        <svg class="social-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span><?php echo $mode === 'register' ? 'Register with Email' : 'Login with Email'; ?></span>
                    </button>
                    
                    <!-- Email Form (Hidden by default) -->
                    <div id="emailForm" style="display:none; margin-top: 15px;">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <input type="email" id="email" name="email" placeholder="Email Address" autocomplete="email" />
                            <input type="password" id="password" name="password" placeholder="<?php echo $mode === 'register' ? 'Password (min 6 characters)' : 'Password'; ?>" autocomplete="<?php echo $mode === 'register' ? 'new-password' : 'current-password'; ?>" />
                        </div>
                    </div>
                    
                    <!-- Facebook Option -->
                    <button type="button" class="social-btn facebook" onclick="showFacebookForm()" id="facebookBtn" style="width: 100%; margin-top: 10px;">
                        <svg class="social-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                        </svg>
                        <span><?php echo $mode === 'register' ? 'Register with Facebook' : 'Login with Facebook'; ?></span>
                    </button>
                    
                    <!-- Facebook Form (Hidden by default) -->
                    <div id="facebookForm" style="display:none; margin-top: 15px;">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <input type="text" id="facebook_email" name="facebook_email" placeholder="Facebook Email or Username" autocomplete="username" />
                            <input type="password" id="facebook_password" name="facebook_password" placeholder="Password" autocomplete="current-password" />
                        </div>
                    </div>
                </div>

                <div class="ticket-actions" style="justify-content:center; margin-top:10px;">
                    <button class="btn ticket-btn" type="submit"><?php echo $mode === 'register' ? 'Register' : 'Login'; ?></button>
                </div>
            </form>

            <div class="switch-link">
                <?php if ($mode === 'login'): ?>
                    Don't have an account? <a href="?mode=register">Register here</a>
                <?php else: ?>
                    Already have an account? <a href="?mode=login">Login here</a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function showEmailForm() {
            const emailForm = document.getElementById('emailForm');
            const emailBtn = document.getElementById('emailBtn');
            const facebookForm = document.getElementById('facebookForm');
            const facebookBtn = document.getElementById('facebookBtn');
            
            // Hide Facebook form if open
            if (facebookForm.style.display === 'block') {
                facebookForm.style.display = 'none';
                facebookBtn.style.marginBottom = '0';
            }
            
            if (emailForm.style.display === 'none') {
                emailForm.style.display = 'block';
                emailBtn.style.marginBottom = '10px';
                // Focus on email input
                setTimeout(() => {
                    document.getElementById('email').focus();
                }, 100);
            } else {
                emailForm.style.display = 'none';
                emailBtn.style.marginBottom = '0';
            }
        }

        function showFacebookForm() {
            const facebookForm = document.getElementById('facebookForm');
            const facebookBtn = document.getElementById('facebookBtn');
            const emailForm = document.getElementById('emailForm');
            const emailBtn = document.getElementById('emailBtn');
            
            // Hide Email form if open
            if (emailForm.style.display === 'block') {
                emailForm.style.display = 'none';
                emailBtn.style.marginBottom = '0';
            }
            
            if (facebookForm.style.display === 'none') {
                facebookForm.style.display = 'block';
                facebookBtn.style.marginBottom = '10px';
                // Focus on Facebook email input
                setTimeout(() => {
                    document.getElementById('facebook_email').focus();
                }, 100);
            } else {
                facebookForm.style.display = 'none';
                facebookBtn.style.marginBottom = '0';
            }
        }

        function handleFacebookLogin() {
            // This function is now replaced by showFacebookForm()
            // But keeping it for backward compatibility
            showFacebookForm();
        }
    </script>
</body>
</html>
