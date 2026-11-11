<?php
// ======================================================
// FAIL SET SEMULA KATA LALUAN (via email token)
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';

$error = '';
$success = '';
$valid_token = false;
$user_id = null;

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function getDashboardRedirect()
{
    if (! isset($_SESSION['role'])) {
        return 'login.php';
    }

    switch ($_SESSION['role']) {
        case 'pelajar': return 'student.php';
        case 'guru':
            return $_SESSION['sub_role'] === 'guru_kelas' ? 'gurukelas.php' : 'guruSubjek.php';
        case 'admin':
            return $_SESSION['sub_role'] === 'super_admin' ? 'superAdmin.php' : 'admin.php';
        default: return 'login.php';
    }
}

// Get token from URL
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: forgot_password.php');
    exit();
}

// Verify token
$stmt = $conn->prepare("
    SELECT pr.*, u.id as user_id, u.username, u.role,
        CASE 
            WHEN u.role = 'pelajar' THEN s.full_name
            WHEN u.role = 'guru' THEN t.full_name
            WHEN u.role = 'admin' THEN a.full_name
        END as full_name
    FROM password_resets pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN teachers t ON u.id = t.user_id
    LEFT JOIN admins a ON u.id = a.user_id
    WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
");
$stmt->bind_param('s', $token);
$stmt->execute();
$reset = $stmt->get_result()->fetch_assoc();

if ($reset) {
    $valid_token = true;
    $user_id = $reset['user_id'];
    $user_name = $reset['full_name'];
} else {
    $error = 'Pautan reset kata laluan tidak sah atau telah tamat tempoh. Sila mohon semula.';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = 'Kata laluan mesti sekurang-kurangnya 6 aksara.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Kata laluan baru dan sahkan kata laluan tidak sepadan.';
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $conn->begin_transaction();
        try {
            // Update user password
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hashed_password, $user_id);
            $stmt->execute();

            // Mark token as used
            $stmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
            $stmt->bind_param('s', $token);
            $stmt->execute();

            // Log activity
            logActivity($user_id, 'PASSWORD_RESET', 'Kata laluan telah direset melalui email');

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Ralat semasa mereset kata laluan. Sila cuba sebentar lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Semula Kata Laluan - Sistem Peperiksaan STPM</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #090d16;
            --card-bg: rgba(17, 24, 39, 0.78);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --primary-color: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
            color: var(--text-main);
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient Glow Mesh */
        .ambient-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.4;
            pointer-events: none;
            z-index: 0;
            animation: drift 15s ease-in-out infinite alternate;
        }

        .ambient-glow-1 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
            top: -100px;
            left: -120px;
        }

        .ambient-glow-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #ec4899 0%, rgba(236, 72, 153, 0) 70%);
            bottom: -150px;
            right: -100px;
            animation-duration: 20s;
        }

        @keyframes drift {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(25px) scale(1.05); }
            100% { transform: translateY(-20px) scale(0.96); }
        }

        .container {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
        }

        .logo {
            text-align: center;
            margin-bottom: 1.8rem;
            animation: fadeInDown 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .logo-icon-box {
            width: 58px;
            height: 58px;
            margin: 0 auto 10px;
            border-radius: 18px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px var(--primary-glow), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .logo h1 {
            color: #ffffff;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-top: 4px;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Glass Card */
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 26px;
            border: 1px solid var(--card-border);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 
                        0 0 0 1px rgba(255, 255, 255, 0.05),
                        inset 0 1px 0 rgba(255, 255, 255, 0.15);
            padding: 2.4rem 2rem;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-header {
            text-align: center;
            margin-bottom: 1.6rem;
        }

        .card-header i {
            font-size: 2.4rem;
            color: #818cf8;
            margin-bottom: 0.6rem;
            display: inline-block;
        }

        .card-header h2 {
            color: #ffffff;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .card-header p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-top: 0.4rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #cbd5e1;
            font-size: 0.88rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .form-control {
            width: 100%;
            padding: 13px 16px 13px 46px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            background: rgba(255, 255, 255, 0.04);
            color: #ffffff;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .form-control:focus {
            outline: none;
            border-color: #818cf8;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        .input-group:focus-within i {
            color: #a5b4fc;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            filter: brightness(1.08);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 1.4rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.12);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3);
        }

        .success-icon i {
            font-size: 2.2rem;
            color: #34d399;
        }

        .links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .links a {
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .links a:hover {
            color: #ffffff;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #64748b;
            font-size: 0.82rem;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.78rem;
        }

        .strength-weak { color: #f87171; }
        .strength-medium { color: #fbbf24; }
        .strength-strong { color: #34d399; }

        @media (max-width: 480px) {
            .card {
                padding: 1.8rem 1.4rem;
            }
        }
    </style>
</head>
<body>
    <!-- Ambient Glow Orbs -->
    <div class="ambient-glow ambient-glow-1"></div>
    <div class="ambient-glow ambient-glow-2"></div>

    <div class="container">
        <div class="logo">
            <div class="logo-icon-box">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>Sistem Peperiksaan STPM</h1>
            <p>Tetapan Semula Kata Laluan</p>
        </div>

        <div class="card">
            <?php if ($success === true) { ?>
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-header">
                    <h2>Berjaya!</h2>
                    <p>Kata laluan anda telah berjaya ditukar.</p>
                </div>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Kata laluan baharu anda telah disimpan. Sila log masuk menggunakan kata laluan baharu.</span>
                </div>
                
                <div class="links">
                    <a href="login.php" class="btn" style="display: inline-block; text-decoration: none;">
                        <i class="fas fa-sign-in-alt"></i> Log Masuk Sekarang
                    </a>
                </div>
                
            <?php } elseif ($valid_token) { ?>
                <div class="card-header">
                    <i class="fas fa-lock-open"></i>
                    <h2>Set Semula Kata Laluan</h2>
                    <p>Hi <strong><?php echo htmlspecialchars($user_name); ?></strong>, sila masukkan kata laluan baharu anda.</p>
                </div>
                
                <?php if ($error) { ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php } ?>
                
                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Kata Laluan Baru</label>
                        <div class="input-group">
                            <i class="fas fa-key"></i>
                            <input type="password" name="new_password" id="new_password" class="form-control" 
                                   placeholder="Minimum 6 aksara" required>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-check-circle"></i> Sahkan Kata Laluan Baru</label>
                        <div class="input-group">
                            <i class="fas fa-check"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                   placeholder="Taip semula kata laluan baharu" required>
                        </div>
                        <div class="password-strength" id="confirmMatch"></div>
                    </div>
                    
                    <button type="submit" class="btn" id="submitBtn">
                        <i class="fas fa-save"></i> Set Semula Kata Laluan
                    </button>
                </form>
                
                <div class="links">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Log Masuk
                    </a>
                </div>
                
            <?php } else { ?>
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Pautan Tidak Sah</h2>
                    <p>Maaf, pautan reset kata laluan ini tidak sah atau telah tamat tempoh.</p>
                </div>
                
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                
                <div class="links">
                    <a href="forgot_password.php" class="btn" style="display: inline-block; text-decoration: none;">
                        <i class="fas fa-paper-plane"></i> Mohon Semula
                    </a>
                    <br><br>
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Log Masuk
                    </a>
                </div>
            <?php } ?>
        </div>

        <div class="footer">
            <p>&copy; 2025 Sistem Peperiksaan STPM. Hak Cipta Terpelihara.</p>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('new_password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthDiv = document.getElementById('passwordStrength');
        const confirmMatchDiv = document.getElementById('confirmMatch');

        function checkPasswordStrength(password) {
            let strength = 0;
            let message = '';
            let className = '';
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength <= 2) {
                message = 'Lemah - Gunakan kombinasi huruf besar, kecil, nombor dan simbol';
                className = 'strength-weak';
            } else if (strength <= 4) {
                message = 'Sederhana - Tambah lagi variasi untuk keselamatan lebih baik';
                className = 'strength-medium';
            } else {
                message = 'Kuat - Kata laluan yang baik!';
                className = 'strength-strong';
            }
            
            return { message, className };
        }

        function checkConfirmMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length === 0) {
                confirmMatchDiv.innerHTML = '';
                return false;
            }
            
            if (password === confirm) {
                confirmMatchDiv.innerHTML = '<i class="fas fa-check-circle"></i> Kata laluan sepadan';
                confirmMatchDiv.className = 'password-strength strength-strong';
                return true;
            } else {
                confirmMatchDiv.innerHTML = '<i class="fas fa-times-circle"></i> Kata laluan tidak sepadan';
                confirmMatchDiv.className = 'password-strength strength-weak';
                return false;
            }
        }

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            const result = checkPasswordStrength(password);
            strengthDiv.innerHTML = result.message;
            strengthDiv.className = 'password-strength ' + result.className;
            checkConfirmMatch();
        });

        confirmInput.addEventListener('input', function() {
            checkConfirmMatch();
        });

        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Kata laluan baru dan sahkan kata laluan tidak sepadan!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Kata laluan mesti sekurang-kurangnya 6 aksara!');
                return false;
            }
        });
    </script>
</body>
</html>