<?php
// ======================================================
// FAIL LUPA KATA LALUAN
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';

$error = '';
$success = '';
$step = 'verify_user'; // verify_user, answer_question, reset_password

$user_id = null;
$question = null;

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 1: Verify user by username/email
    if ($action === 'verify_user') {
        $identifier = validateAndSanitize($_POST['identifier'] ?? '');

        if (empty($identifier)) {
            $error = 'Sila masukkan ID Pengguna atau emel.';
        } else {
            // Find user by username or email
            $stmt = $conn->prepare("
                SELECT u.*, 
                    CASE 
                        WHEN u.role = 'pelajar' THEN s.full_name
                        WHEN u.role = 'guru' THEN t.full_name
                        WHEN u.role = 'admin' THEN a.full_name
                    END as full_name
                FROM users u
                LEFT JOIN students s ON u.id = s.user_id
                LEFT JOIN teachers t ON u.id = t.user_id
                LEFT JOIN admins a ON u.id = a.user_id
                WHERE u.username = ? OR u.email = ?
            ");
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && $user['status'] === 'Aktif') {
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_username'] = $user['username'];
                $_SESSION['reset_fullname'] = $user['full_name'];
                $_SESSION['reset_step'] = 'answer_question';

                // Get security question for this user
                $stmt = $conn->prepare('
                    SELECT sq.id, sq.question, usa.answer_hash
                    FROM user_security_answers usa
                    JOIN security_questions sq ON usa.question_id = sq.id
                    WHERE usa.user_id = ?
                    LIMIT 1
                ');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $questionData = $stmt->get_result()->fetch_assoc();

                // If not found, try from users table
                if (! $questionData && $user['security_question_id']) {
                    $stmt = $conn->prepare('
                        SELECT id, question FROM security_questions WHERE id = ?
                    ');
                    $stmt->bind_param('i', $user['security_question_id']);
                    $stmt->execute();
                    $sq = $stmt->get_result()->fetch_assoc();
                    if ($sq) {
                        $questionData = [
                            'id' => $sq['id'],
                            'question' => $sq['question'],
                            'answer_hash' => $user['security_answer_hash'],
                        ];
                    }
                }

                if ($questionData) {
                    $_SESSION['reset_question_id'] = $questionData['id'];
                    $_SESSION['reset_question'] = $questionData['question'];
                    $_SESSION['reset_answer_hash'] = $questionData['answer_hash'];

                    // Redirect to refresh page and show step 2
                    header('Location: forgot_password.php');
                    exit();
                } else {
                    $error = 'Tiada soalan keselamatan untuk akaun ini. Sila hubungi pentadbir.';
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_username']);
                    unset($_SESSION['reset_fullname']);
                    unset($_SESSION['reset_step']);
                }
            } else {
                $error = 'ID Pengguna atau Emel tidak dijumpai atau akaun tidak aktif.';
            }
        }
    }

    // Step 2: Verify security answer
    elseif ($action === 'verify_answer') {
        $answer = validateAndSanitize($_POST['answer'] ?? '');
        $answer_hash = md5(strtolower(trim($answer)));

        if (empty($answer)) {
            $error = 'Sila masukkan jawapan untuk soalan keselamatan.';
        } elseif (isset($_SESSION['reset_answer_hash']) && $answer_hash === $_SESSION['reset_answer_hash']) {
            $_SESSION['reset_step'] = 'reset_password';
            header('Location: forgot_password.php');
            exit();
        } else {
            $error = 'Jawapan yang anda masukkan tidak tepat. Sila cuba lagi.';
            $_SESSION['reset_attempts'] = ($_SESSION['reset_attempts'] ?? 0) + 1;
            if ($_SESSION['reset_attempts'] >= 3) {
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_username']);
                unset($_SESSION['reset_fullname']);
                unset($_SESSION['reset_step']);
                unset($_SESSION['reset_question_id']);
                unset($_SESSION['reset_question']);
                unset($_SESSION['reset_answer_hash']);
                unset($_SESSION['reset_attempts']);
                $error = 'Terlalu banyak percubaan gagal. Sila mulakan semula proses.';
            }
        }
    }

    // Step 3: Reset password
    elseif ($action === 'reset_password') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 6) {
            $error = 'Kata laluan mesti sekurang-kurangnya 6 aksara.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Kata laluan baru dan sahkan kata laluan tidak sepadan.';
        } else {
            $user_id = $_SESSION['reset_user_id'] ?? 0;
            if ($user_id > 0) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->bind_param('si', $hashed_password, $user_id);

                if ($stmt->execute()) {
                    logActivity($user_id, 'PASSWORD_RESET', 'Kata laluan telah direset melalui soalan keselamatan');

                    // Clear reset session
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_username']);
                    unset($_SESSION['reset_fullname']);
                    unset($_SESSION['reset_step']);
                    unset($_SESSION['reset_question_id']);
                    unset($_SESSION['reset_question']);
                    unset($_SESSION['reset_answer_hash']);
                    unset($_SESSION['reset_attempts']);

                    $success = true;
                } else {
                    $error = 'Ralat semasa mereset kata laluan. Sila cuba sebentar lagi.';
                }
            } else {
                $error = 'Sesi tidak sah. Sila mulakan semula.';
            }
        }
    }
}

// Check current step from session
if (isset($_SESSION['reset_step'])) {
    $step = $_SESSION['reset_step'];
    if ($step === 'answer_question' && isset($_SESSION['reset_question'])) {
        $question = $_SESSION['reset_question'];
        $username = $_SESSION['reset_username'] ?? '';
        $fullname = $_SESSION['reset_fullname'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Laluan - Sistem Peperiksaan STPM</title>
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
            max-width: 520px;
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

        .user-info {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.25);
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .user-info p {
            margin: 4px 0;
            font-size: 0.9rem;
            color: #cbd5e1;
        }

        .user-info strong {
            color: #a5b4fc;
        }

        .question-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-left: 4px solid #818cf8;
            padding: 1.1rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
        }

        .question-box .question-text {
            font-size: 0.98rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.4rem;
        }

        .question-box .question-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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

        .success-box {
            text-align: center;
            padding: 1rem 0;
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

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 12%;
            right: 12%;
            height: 2px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step .step-circle {
            width: 38px;
            height: 38px;
            background: #1e293b;
            border: 2px solid rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 700;
            font-size: 0.88rem;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            background: #6366f1;
            border-color: #818cf8;
            color: #ffffff;
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .step.completed .step-circle {
            background: #10b981;
            border-color: #34d399;
            color: #ffffff;
        }

        .step .step-label {
            font-size: 0.72rem;
            font-weight: 500;
            color: #64748b;
        }

        .step.active .step-label {
            color: #cbd5e1;
            font-weight: 700;
        }

        .step.completed .step-label {
            color: #94a3b8;
        }

        small {
            color: #64748b;
            font-size: 0.78rem;
            margin-top: 4px;
            display: block;
        }

        @media (max-width: 480px) {
            .card {
                padding: 1.8rem 1.4rem;
            }
            .step .step-label {
                font-size: 0.65rem;
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
            <p>Modul Pemulihan Kata Laluan</p>
        </div>

        <div class="card">
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step <?php echo $step == 'verify_user' ? 'active' : ($step != 'verify_user' ? 'completed' : ''); ?>">
                    <div class="step-circle">1</div>
                    <div class="step-label">Sahkan ID</div>
                </div>
                <div class="step <?php echo $step == 'answer_question' ? 'active' : ($step == 'reset_password' ? 'completed' : ''); ?>">
                    <div class="step-circle">2</div>
                    <div class="step-label">Soalan Keselamatan</div>
                </div>
                <div class="step <?php echo $step == 'reset_password' ? 'active' : ''; ?>">
                    <div class="step-circle">3</div>
                    <div class="step-label">Kata Laluan Baru</div>
                </div>
            </div>

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
                
            <?php } elseif ($step === 'verify_user') { ?>
                <div class="card-header">
                    <i class="fas fa-user-check"></i>
                    <h2>Sahkan ID Pengguna</h2>
                    <p>Masukkan ID Pengguna atau emel anda untuk memulakan proses reset kata laluan.</p>
                </div>
                
                <?php if ($error) { ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php } ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify_user">
                    
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> ID Pengguna / Emel</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="identifier" class="form-control" 
                                   placeholder="cth: STPM2024001, GU001, AD001" required autofocus>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-arrow-right"></i> Seterusnya
                    </button>
                </form>
                
                <div class="links">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Log Masuk
                    </a>
                </div>
                
            <?php } elseif ($step === 'answer_question' && $question) { ?>
                <div class="card-header">
                    <i class="fas fa-shield-alt"></i>
                    <h2>Soalan Keselamatan</h2>
                    <p>Sila jawab soalan keselamatan untuk mengesahkan identiti anda.</p>
                </div>
                
                <div class="user-info">
                    <p><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($fullname); ?></strong></p>
                    <p><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($username); ?></p>
                </div>
                
                <?php if ($error) { ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php } ?>
                
                <div class="question-box">
                    <div class="question-label"><i class="fas fa-question-circle"></i> Soalan Keselamatan</div>
                    <div class="question-text"><?php echo htmlspecialchars($question); ?></div>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify_answer">
                    
                    <div class="form-group">
                        <label><i class="fas fa-edit"></i> Jawapan Anda</label>
                        <div class="input-group">
                            <i class="fas fa-pen"></i>
                            <input type="text" name="answer" class="form-control" 
                                   placeholder="Masukkan jawapan anda" required autofocus>
                        </div>
                        <small style="color:#64748b;">Jawapan tidak sensitif huruf besar/kecil</small>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-check"></i> Sahkan Jawapan
                    </button>
                </form>
                
                <div class="links">
                    <a href="forgot_password.php" onclick="return confirm('Mulakan semula proses?')">
                        <i class="fas fa-redo"></i> Mulakan Semula
                    </a>
                </div>
                
            <?php } elseif ($step === 'reset_password') { ?>
                <div class="card-header">
                    <i class="fas fa-lock-open"></i>
                    <h2>Set Semula Kata Laluan</h2>
                    <p>Sila masukkan kata laluan baharu anda.</p>
                </div>
                
                <div class="user-info">
                    <p><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($_SESSION['reset_fullname'] ?? ''); ?></strong></p>
                </div>
                
                <?php if ($error) { ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php } ?>
                
                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="action" value="reset_password">
                    
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
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Set Semula Kata Laluan
                    </button>
                </form>
                
                <div class="links">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Kembali ke Log Masuk
                    </a>
                </div>
                
            <?php } else { ?>
                <?php
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_username']);
                unset($_SESSION['reset_fullname']);
                unset($_SESSION['reset_step']);
                unset($_SESSION['reset_question_id']);
                unset($_SESSION['reset_question']);
                unset($_SESSION['reset_answer_hash']);
                unset($_SESSION['reset_attempts']);
                ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Sesi telah tamat. Sila <a href="forgot_password.php">mulakan semula</a> proses.</span>
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

        if (passwordInput) {
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

            if (confirmInput) {
                confirmInput.addEventListener('input', function() {
                    checkConfirmMatch();
                });
            }

            const resetForm = document.getElementById('resetForm');
            if (resetForm) {
                resetForm.addEventListener('submit', function(e) {
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
            }
        }
    </script>
</body>
</html>