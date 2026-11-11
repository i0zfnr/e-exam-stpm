<?php
require_once 'config.php';

$error = '';
$success = '';
$register_error = '';
$register_success = '';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    $user = getCurrentUser();
    if ($user) {
        redirectBasedOnRole($user);
        exit();
    }
}

// Fungsi redirect berdasarkan role
function redirectBasedOnRole($user)
{
    if ($user['role'] === 'pelajar') {
        header('Location: student.php');
    } elseif ($user['role'] === 'guru') {
        if ($user['sub_role'] === 'guru_kelas') {
            header('Location: gurukelas.php');
        } else {
            header('Location: guruSubjek.php');
        }
    } elseif ($user['role'] === 'admin') {
        if ($user['sub_role'] === 'super_admin') {
            header('Location: superAdmin.php');
        } else {
            header('Location: admin.php');
        }
    }
    exit();
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = validateAndSanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_role = validateAndSanitize($_POST['role'] ?? '');
    $selected_sub_role = validateAndSanitize($_POST['sub_role'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Sila masukkan ID pengguna dan kata laluan.';
    } else {
        $stmt = $conn->prepare("
            SELECT u.*, 
                CASE 
                    WHEN u.role = 'pelajar' THEN s.id
                    WHEN u.role = 'guru' THEN t.id
                    WHEN u.role = 'admin' THEN a.id
                END as profile_id
            FROM users u
            LEFT JOIN students s ON u.id = s.user_id
            LEFT JOIN teachers t ON u.id = t.user_id
            LEFT JOIN admins a ON u.id = a.user_id
            WHERE u.username = ?
        ");

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($user['status'] !== 'Aktif') {
                $error = 'Akaun anda tidak aktif. Sila hubungi pentadbir.';
            } elseif (password_verify($password, $user['password'])) {
                if ($user['role'] !== $selected_role) {
                    $error = 'Peranan tidak sesuai. Anda didaftarkan sebagai '.ucfirst($user['role']);
                } elseif ($user['role'] === 'guru' && $user['sub_role'] !== $selected_sub_role) {
                    $error = 'Jenis guru tidak sesuai. Anda adalah '.($user['sub_role'] === 'guru_kelas' ? 'Guru Kelas' : 'Guru Subjek');
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['sub_role'] = $user['sub_role'];
                    $_SESSION['profile_id'] = $user['profile_id'];

                    $updateStmt = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                    $updateStmt->bind_param('i', $user['id']);
                    $updateStmt->execute();

                    logActivity($user['id'], 'LOGIN', 'Pengguna log masuk sebagai '.$user['role']);

                    redirectBasedOnRole($user);
                    exit();
                }
            } else {
                $error = 'Kata laluan salah!';
            }
        } else {
            $error = 'ID pengguna tidak dijumpai!';
        }
    }
}

// Get all classes for dropdown
$allClasses = [];
$result = $conn->query("SELECT id, class_name FROM classes WHERE status = 'Aktif' ORDER BY class_name");
while ($row = $result->fetch_assoc()) {
    $allClasses[] = $row;
}

// Get security questions for registration
$security_questions = [];
$result = $conn->query('SELECT id, question FROM security_questions WHERE is_active = 1 ORDER BY id');
while ($row = $result->fetch_assoc()) {
    $security_questions[] = $row;
}

// ==================== PROSES PENDAFTARAN ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $register_role = validateAndSanitize($_POST['register_role'] ?? '');

    // ==================== PENDAFTARAN PELAJAR ====================
    if ($register_role === 'pelajar') {
        $matric = validateAndSanitize($_POST['reg_matric'] ?? '');
        $name = validateAndSanitize($_POST['reg_name'] ?? '');
        $ic_number = validateAndSanitize($_POST['reg_ic'] ?? '');
        $email = validateAndSanitize($_POST['reg_email'] ?? '', 'email');
        $phone = validateAndSanitize($_POST['reg_phone'] ?? '');
        $address = validateAndSanitize($_POST['reg_address'] ?? '');
        $class_id = validateAndSanitize($_POST['reg_class'] ?? 0, 'int');
        $password = $_POST['reg_password'] ?? '';
        $confirm_password = $_POST['reg_confirm_password'] ?? '';
        $security_question = validateAndSanitize($_POST['security_question'] ?? 0, 'int');
        $security_answer = validateAndSanitize($_POST['security_answer'] ?? '');

        if (empty($matric) || empty($name) || empty($ic_number) || empty($email) || empty($password)) {
            $register_error = 'Sila lengkapkan semua maklumat required untuk pelajar.';
        } elseif (strlen($password) < 6) {
            $register_error = 'Kata laluan minimum 6 aksara.';
        } elseif ($password !== $confirm_password) {
            $register_error = 'Kata laluan tidak sepadan.';
        } elseif ($security_question <= 0 || empty($security_answer)) {
            $register_error = 'Sila pilih soalan keselamatan dan berikan jawapan.';
        } elseif ($class_id <= 0) {
            $register_error = 'Sila pilih kelas.';
        } else {
            $check = $conn->prepare('SELECT id FROM students WHERE matric_no = ?');
            $check->bind_param('s', $matric);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $register_error = 'No. Matrik sudah wujud.';
            } else {
                $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
                $check->bind_param('s', $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $register_error = 'Emel sudah digunakan.';
                } else {
                    $conn->begin_transaction();
                    try {
                        $username = $matric;
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $role = 'pelajar';
                        $security_answer_hash = md5(strtolower(trim($security_answer)));

                        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, security_question_id, security_answer_hash, status) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')");
                        $stmt->bind_param('ssssis', $username, $hashed_password, $email, $role, $security_question, $security_answer_hash);
                        $stmt->execute();
                        $user_id = $conn->insert_id;

                        $stmt = $conn->prepare("INSERT INTO students (user_id, matric_no, full_name, ic_number, class_id, phone, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')");
                        $stmt->bind_param('isssiss', $user_id, $matric, $name, $ic_number, $class_id, $phone, $address);
                        $stmt->execute();

                        $conn->commit();

                        $subjectsForClass = getSubjectsByClass($class_id);
                        $subjectList = [];
                        foreach ($subjectsForClass as $subj) {
                            $subjectList[] = $subj['subject_name'].' (Semester '.$subj['semester'].')';
                        }
                        $subjectHtml = ! empty($subjectList) ? '<br><strong>Subjek yang akan dipelajari:</strong><br>• '.implode('<br>• ', $subjectList) : '';

                        $register_success = 'Pendaftaran pelajar berjaya! Sila log masuk.'.$subjectHtml;
                        $_POST = [];
                    } catch (Exception $e) {
                        $conn->rollback();
                        $register_error = 'Ralat pendaftaran: '.$e->getMessage();
                    }
                }
            }
        }
    }

    // ==================== PENDAFTARAN GURU ====================
    elseif ($register_role === 'guru') {
        $teacher_id = validateAndSanitize($_POST['reg_teacher_id'] ?? '');
        $name = validateAndSanitize($_POST['reg_teacher_name'] ?? '');
        $teacher_type = validateAndSanitize($_POST['reg_teacher_type'] ?? '');
        $email = validateAndSanitize($_POST['reg_teacher_email'] ?? '', 'email');
        $phone = validateAndSanitize($_POST['reg_teacher_phone'] ?? '');
        $password = $_POST['reg_teacher_password'] ?? '';
        $confirm_password = $_POST['reg_teacher_confirm_password'] ?? '';
        $security_question = validateAndSanitize($_POST['security_question_teacher'] ?? 0, 'int');
        $security_answer = validateAndSanitize($_POST['security_answer_teacher'] ?? '');

        if (empty($teacher_id) || empty($name) || empty($teacher_type) || empty($email) || empty($password)) {
            $register_error = 'Sila lengkapkan semua maklumat required untuk guru.';
        } elseif (strlen($password) < 6) {
            $register_error = 'Kata laluan minimum 6 aksara.';
        } elseif ($password !== $confirm_password) {
            $register_error = 'Kata laluan tidak sepadan.';
        } elseif ($security_question <= 0 || empty($security_answer)) {
            $register_error = 'Sila pilih soalan keselamatan dan berikan jawapan.';
        } else {
            $check = $conn->prepare('SELECT id FROM teachers WHERE teacher_id = ?');
            $check->bind_param('s', $teacher_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $register_error = 'ID Guru sudah wujud.';
            } else {
                $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
                $check->bind_param('s', $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $register_error = 'Emel sudah digunakan.';
                } else {
                    $conn->begin_transaction();
                    try {
                        $username = $teacher_id;
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $role = 'guru';
                        $sub_role = $teacher_type;
                        $security_answer_hash = md5(strtolower(trim($security_answer)));

                        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, sub_role, security_question_id, security_answer_hash, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')");
                        $stmt->bind_param('sssssis', $username, $hashed_password, $email, $role, $sub_role, $security_question, $security_answer_hash);
                        $stmt->execute();
                        $user_id = $conn->insert_id;

                        $stmt = $conn->prepare("INSERT INTO teachers (user_id, teacher_id, full_name, teacher_type, phone, status) VALUES (?, ?, ?, ?, ?, 'Aktif')");
                        $stmt->bind_param('issss', $user_id, $teacher_id, $name, $teacher_type, $phone);
                        $stmt->execute();

                        $conn->commit();

                        $teacher_type_text = ($teacher_type == 'guru_kelas') ? 'Guru Kelas' : 'Guru Subjek';
                        $register_success = "Pendaftaran guru berjaya! ID Guru: $teacher_id <br> Sila log masuk menggunakan ID Guru dan kata laluan yang telah didaftarkan. <br><strong>Jenis Guru:</strong> $teacher_type_text";

                        $_POST = [];
                    } catch (Exception $e) {
                        $conn->rollback();
                        $register_error = 'Ralat pendaftaran: '.$e->getMessage();
                    }
                }
            }
        }
    }

    // ==================== PENDAFTARAN ADMIN (DISEKAT SECARA AWAM) ====================
    elseif ($register_role === 'admin') {
        $register_error = 'Pendaftaran akaun pentadbir (Admin) tidak dibenarkan secara awam demi keselamatan sistem. Sila hubungi Super Admin.';
    }
}

?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk / Daftar - Sistem Peperiksaan STPM</title>
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
            padding: 30px 20px;
            color: var(--text-main);
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient Glowing Mesh Background */
        .ambient-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.45;
            pointer-events: none;
            z-index: 0;
            animation: drift 15s ease-in-out infinite alternate;
        }

        .ambient-glow-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
            top: -100px;
            left: -150px;
        }

        .ambient-glow-2 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, #ec4899 0%, rgba(236, 72, 153, 0) 70%);
            bottom: -150px;
            right: -100px;
            animation-duration: 20s;
        }

        .ambient-glow-3 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, #3b82f6 0%, rgba(59, 130, 246, 0) 70%);
            top: 40%;
            left: 60%;
            opacity: 0.2;
        }

        @keyframes drift {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(30px) scale(1.06); }
            100% { transform: translateY(-20px) scale(0.96); }
        }

        .container {
            width: 100%;
            max-width: 780px;
            position: relative;
            z-index: 1;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .logo-icon-box {
            width: 64px;
            height: 64px;
            margin: 0 auto 12px;
            border-radius: 20px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px var(--primary-glow), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .logo h1 {
            color: #ffffff;
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 4px;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Frosted Glass Tab Container */
        .tab-container {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            border: 1px solid var(--card-border);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 
                        0 0 0 1px rgba(255, 255, 255, 0.05),
                        inset 0 1px 0 rgba(255, 255, 255, 0.15);
            overflow: hidden;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .tabs {
            display: flex;
            background: rgba(0, 0, 0, 0.25);
            padding: 8px;
            gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .tab-btn {
            flex: 1;
            padding: 0.85rem 1rem;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 16px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-btn.active {
            color: #ffffff;
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
        }

        .tab-content {
            padding: 2.2rem;
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-title, .register-title {
            text-align: center;
            margin-bottom: 1.8rem;
            color: #ffffff;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        /* Role Selector Cards */
        .role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 14px 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
        }

        .role-option:hover {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(99, 102, 241, 0.08);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .role-option.active {
            border-color: #818cf8;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.2) 100%);
            color: #ffffff;
            box-shadow: 0 8px 20px -4px var(--primary-glow);
        }

        .role-option i {
            font-size: 1.5rem;
            margin-bottom: 6px;
            display: block;
            color: #a5b4fc;
        }

        .role-option.active i {
            color: #ffffff;
        }

        .role-option span {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .subrole-container {
            margin-bottom: 1.5rem;
            animation: fadeIn 0.4s ease;
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .subrole-label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 700;
            color: #cbd5e1;
            font-size: 0.85rem;
        }

        .subrole-selector {
            display: flex;
            gap: 10px;
        }

        .subrole-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
        }

        .subrole-option:hover {
            border-color: rgba(99, 102, 241, 0.4);
            color: #ffffff;
        }

        .subrole-option.active {
            border-color: #818cf8;
            background: rgba(99, 102, 241, 0.2);
            color: #ffffff;
        }

        .register-role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .register-role-option {
            flex: 1;
            text-align: center;
            padding: 14px 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
        }

        .register-role-option:hover {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(99, 102, 241, 0.08);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .register-role-option.active {
            border-color: #818cf8;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.2) 100%);
            color: #ffffff;
            box-shadow: 0 8px 20px -4px var(--primary-glow);
        }

        .register-role-option i {
            font-size: 1.5rem;
            margin-bottom: 6px;
            display: block;
            color: #a5b4fc;
        }

        .register-role-option.active i {
            color: #ffffff;
        }

        .register-role-option span {
            font-size: 0.85rem;
            font-weight: 700;
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

        select.form-control option {
            background-color: #1e293b;
            color: #ffffff;
        }

        textarea.form-control {
            padding: 13px 16px;
            resize: vertical;
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

        .row-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        .info-panel {
            margin-top: 1.75rem;
            padding: 1.2rem;
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
        }

        .info-panel summary {
            cursor: pointer;
            font-weight: 700;
            color: #a5b4fc;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-panel table {
            width: 100%;
            margin-top: 0.8rem;
            font-size: 0.82rem;
            border-collapse: separate;
            border-spacing: 0 4px;
        }

        .info-panel td {
            padding: 6px 8px;
        }

        .demo-hint {
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
        }

        .demo-hint:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }

        .demo-hint td:last-child {
            font-family: monospace;
            color: #93c5fd;
        }

        .register-fields-group {
            transition: all 0.3s ease;
        }

        small {
            color: #64748b;
            font-size: 0.78rem;
            margin-top: 4px;
            display: block;
        }

        @media (max-width: 768px) {
            .tab-content {
                padding: 1.6rem;
            }
            .role-option span, .register-role-option span {
                font-size: 0.75rem;
            }
            .row-2cols {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Ambient Glow Orbs -->
    <div class="ambient-glow ambient-glow-1"></div>
    <div class="ambient-glow ambient-glow-2"></div>
    <div class="ambient-glow ambient-glow-3"></div>

    <div class="container">
        <div class="logo">
            <div class="logo-icon-box">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>Sistem Peperiksaan STPM</h1>
            <p>Portal Pengurusan Peperiksaan Berpusat STPM</p>
        </div>

        <div class="tab-container">
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('login')">Log Masuk</button>
                <button class="tab-btn" onclick="switchTab('register')">Daftar Akaun</button>
            </div>

            <!-- Login Tab -->
            <div id="loginTab" class="tab-content active">
                <h2 class="login-title">
                    <i class="fas fa-sign-in-alt"></i> Log Masuk
                </h2>
                
                <?php if ($error) { ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php } ?>
                
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="login" value="1">
                    <input type="hidden" name="role" id="selectedRole" value="pelajar">
                    <input type="hidden" name="sub_role" id="selectedSubRole" value="">
                    
                    <div class="role-selector">
                        <div class="role-option active" data-role="pelajar" data-subrole="">
                            <i class="fas fa-user-graduate"></i>
                            <span>Pelajar</span>
                        </div>
                        <div class="role-option" data-role="guru" data-subrole="">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Guru</span>
                        </div>
                        <div class="role-option" data-role="admin" data-subrole="">
                            <i class="fas fa-user-cog"></i>
                            <span>Admin</span>
                        </div>
                    </div>

                    <div id="guruSubRole" class="subrole-container" style="display: none;">
                        <div class="subrole-label">
                            <i class="fas fa-briefcase"></i> Jenis Guru
                        </div>
                        <div class="subrole-selector">
                            <div class="subrole-option active" data-subrole="guru_kelas">
                                <i class="fas fa-users"></i>
                                <span>Guru Kelas</span>
                            </div>
                            <div class="subrole-option" data-subrole="guru_subjek">
                                <i class="fas fa-book-open"></i>
                                <span>Guru Subjek</span>
                            </div>
                        </div>
                    </div>

                    <div id="adminSubRole" class="subrole-container" style="display: none;">
                        <div class="subrole-label">
                            <i class="fas fa-shield-alt"></i> Tahap Admin
                        </div>
                        <div class="subrole-selector">
                            <div class="subrole-option active" data-subrole="admin">
                                <i class="fas fa-user-shield"></i>
                                <span>Admin</span>
                            </div>
                            <div class="subrole-option" data-subrole="super_admin">
                                <i class="fas fa-crown"></i>
                                <span>Super Admin</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> ID Pengguna</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" id="username" class="form-control" 
                                   placeholder="No. Matrik / ID Guru / ID Admin" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Kata Laluan</label>
                        <div class="input-group">
                            <i class="fas fa-key"></i>
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="Kata laluan" required>
                        </div>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Log Masuk
                    </button>
                    
                    <div class="links" style="text-align: center; margin-top: 1rem;">
                        <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-size: 0.85rem;">
                            <i class="fas fa-key"></i> Lupa Kata Laluan?
                        </a>
                    </div>
                </form>
            </div>

            <!-- Register Tab -->
            <div id="registerTab" class="tab-content">
                <h2 class="register-title">
                    <i class="fas fa-user-plus"></i> Daftar Akaun Baru
                </h2>
                
                <?php if ($register_error) { ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $register_error; ?></span>
                    </div>
                <?php } ?>
                
                <?php if ($register_success) { ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $register_success; ?></span>
                    </div>
                <?php } ?>
                
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="register" value="1">
                    <input type="hidden" name="register_role" id="registerRole" value="pelajar">
                    
                    <div class="register-role-selector">
                        <div class="register-role-option active" data-role="pelajar">
                            <i class="fas fa-user-graduate"></i>
                            <span>Pelajar</span>
                        </div>
                        <div class="register-role-option" data-role="guru">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Guru</span>
                        </div>
                    </div>

                    <!-- Field untuk Pelajar -->
                    <div id="studentFields" class="register-fields-group">
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> No. Matrik <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-hashtag"></i>
                                    <input type="text" name="reg_matric" id="reg_matric" class="form-control" placeholder="cth: STPM2024001">
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nama Penuh <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="reg_name" id="reg_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> No. KP <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-id-card"></i>
                                    <input type="text" name="reg_ic" id="reg_ic" class="form-control" placeholder="cth: 010101010101">
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Emel <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="reg_email" id="reg_email" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> No. Telefon</label>
                                <div class="input-group">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="reg_phone" id="reg_phone" class="form-control" placeholder="cth: 0123456789">
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-school"></i> Pilih Kelas <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-school"></i>
                                    <select name="reg_class" id="reg_class" class="form-control">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($allClasses as $class) { ?>
                                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Kata Laluan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-key"></i>
                                    <input type="password" name="reg_password" id="reg_password" class="form-control">
                                </div>
                                <small>Minimum 6 aksara</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-check-circle"></i> Sahkan Kata Laluan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-check"></i>
                                    <input type="password" name="reg_confirm_password" id="reg_confirm_password" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-question-circle"></i> Soalan Keselamatan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-question"></i>
                                    <select name="security_question" id="security_question" class="form-control">
                                        <option value="">Pilih soalan keselamatan</option>
                                        <?php foreach ($security_questions as $sq) { ?>
                                        <option value="<?php echo $sq['id']; ?>"><?php echo htmlspecialchars($sq['question']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-pen"></i> Jawapan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-pen"></i>
                                    <input type="text" name="security_answer" id="security_answer" class="form-control">
                                </div>
                                <small>Jawapan tidak sensitif huruf besar/kecil</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                            <div class="input-group">
                                <i class="fas fa-map-marker-alt"></i>
                                <textarea name="reg_address" id="reg_address" class="form-control" rows="2" style="padding-left: 45px;"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Field untuk Guru -->
                    <div id="guruFields" class="register-fields-group" style="display: none;">
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> ID Guru <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-hashtag"></i>
                                    <input type="text" name="reg_teacher_id" id="reg_teacher_id" class="form-control" placeholder="cth: GU003">
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nama Penuh <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="reg_teacher_name" id="reg_teacher_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-briefcase"></i> Jenis Guru <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-chalkboard-user"></i>
                                    <select name="reg_teacher_type" id="reg_teacher_type" class="form-control">
                                        <option value="guru_subjek">Guru Subjek</option>
                                        <option value="guru_kelas">Guru Kelas</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Emel <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="reg_teacher_email" id="reg_teacher_email" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> No. Telefon</label>
                                <div class="input-group">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="reg_teacher_phone" id="reg_teacher_phone" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Kata Laluan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-key"></i>
                                    <input type="password" name="reg_teacher_password" id="reg_teacher_password" class="form-control">
                                </div>
                                <small>Minimum 6 aksara</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-check-circle"></i> Sahkan Kata Laluan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-check"></i>
                                    <input type="password" name="reg_teacher_confirm_password" id="reg_teacher_confirm_password" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row-2cols">
                            <div class="form-group">
                                <label><i class="fas fa-question-circle"></i> Soalan Keselamatan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-question"></i>
                                    <select name="security_question_teacher" id="security_question_teacher" class="form-control">
                                        <option value="">Pilih soalan keselamatan</option>
                                        <?php foreach ($security_questions as $sq) { ?>
                                        <option value="<?php echo $sq['id']; ?>"><?php echo htmlspecialchars($sq['question']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-pen"></i> Jawapan <span style="color:red;">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-pen"></i>
                                    <input type="text" name="security_answer_teacher" id="security_answer_teacher" class="form-control">
                                </div>
                                <small>Jawapan tidak sensitif huruf besar/kecil</small>
                            </div>
                        </div>
                    </div>


                    
                    <div class="alert alert-info" style="font-size: 0.8rem; margin-top: 1rem;">
                        <i class="fas fa-info-circle"></i>
                        <span>Dengan mendaftar, anda bersetuju dengan Terma & Syarat Penggunaan. Soalan keselamatan akan digunakan untuk menetapkan semula kata laluan jika terlupa.</span>
                    </div>
                    
                    <button type="submit" class="btn" id="registerBtn">
                        <i class="fas fa-user-plus"></i> Daftar Akaun
                    </button>
                </form>
            </div>
        </div>

        <div class="info-panel">
            <details>
                <summary><i class="fas fa-info-circle"></i> Akaun Demo</summary>
                <table style="width:100%">
                    <tr><td colspan="2"><strong>📚 PELAJAR</strong></td></tr>
                    <tr class="demo-hint" data-username="STPM2024001" data-password="student123" data-role="pelajar"><td>STPM2024001</td><td>student123</td></tr>
                    <tr class="demo-hint" data-username="STPM2024002" data-password="student123" data-role="pelajar"><td>STPM2024002</td><td>student123</td></tr>
                    <tr><td colspan="2"><strong>👨‍🏫 GURU</strong></td></tr>
                    <tr class="demo-hint" data-username="GU001" data-password="guru123" data-role="guru" data-subrole="guru_subjek"><td>GU001 (Guru Subjek)</td><td>guru123</td></tr>
                    <tr class="demo-hint" data-username="GU002" data-password="guru123" data-role="guru" data-subrole="guru_kelas"><td>GU002 (Guru Kelas)</td><td>guru123</td></tr>
                    <tr><td colspan="2"><strong>👑 ADMIN</strong></td></tr>
                    <tr class="demo-hint" data-username="AD001" data-password="admin123" data-role="admin" data-subrole="super_admin"><td>AD001 (Super Admin)</td><td>admin123</td></tr>
                    <tr class="demo-hint" data-username="AD002" data-password="admin123" data-role="admin" data-subrole="admin"><td>AD002 (Admin)</td><td>admin123</td></tr>
                </table>
                <p style="font-size: 0.7rem; margin-top: 8px; color: #666;">Klik pada mana-mana akaun untuk auto-fill</p>
            </details>
        </div>

        <div class="footer">
            <p>&copy; 2025 Sistem Peperiksaan STPM. Hak Cipta Terpelihara.</p>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const tabs = document.querySelectorAll('.tab-btn');
            
            if (tab === 'login') {
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                loginTab.classList.remove('active');
                registerTab.classList.add('active');
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
            }
        }
        
        // ==================== LOGIN ROLE SELECTION ====================
        const roleOptions = document.querySelectorAll('.role-option');
        const guruSubRole = document.getElementById('guruSubRole');
        const adminSubRole = document.getElementById('adminSubRole');
        const selectedRoleInput = document.getElementById('selectedRole');
        const selectedSubRoleInput = document.getElementById('selectedSubRole');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        roleOptions.forEach(option => {
            option.addEventListener('click', function() {
                roleOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                const role = this.getAttribute('data-role');
                selectedRoleInput.value = role;
                
                guruSubRole.style.display = 'none';
                adminSubRole.style.display = 'none';
                
                if (role === 'pelajar') {
                    usernameInput.placeholder = "No. Matrik (cth: STPM2024001)";
                    selectedSubRoleInput.value = '';
                } else if (role === 'guru') {
                    usernameInput.placeholder = "ID Guru (cth: GU001)";
                    guruSubRole.style.display = 'block';
                    const activeSub = document.querySelector('#guruSubRole .subrole-option.active');
                    if (activeSub) {
                        selectedSubRoleInput.value = activeSub.getAttribute('data-subrole');
                    }
                } else if (role === 'admin') {
                    usernameInput.placeholder = "ID Admin (cth: AD001)";
                    adminSubRole.style.display = 'block';
                    const activeSub = document.querySelector('#adminSubRole .subrole-option.active');
                    if (activeSub) {
                        selectedSubRoleInput.value = activeSub.getAttribute('data-subrole');
                    }
                }
            });
        });

        document.querySelectorAll('.subrole-option').forEach(option => {
            option.addEventListener('click', function() {
                const parent = this.closest('.subrole-container');
                parent.querySelectorAll('.subrole-option').forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                selectedSubRoleInput.value = this.getAttribute('data-subrole');
            });
        });

        // ==================== REGISTER ROLE SELECTION ====================
        const registerRoleOptions = document.querySelectorAll('.register-role-option');
        const registerRoleInput = document.getElementById('registerRole');
        const studentFields = document.getElementById('studentFields');
        const guruFields = document.getElementById('guruFields');
        const adminFields = document.getElementById('adminFields');

        function updateRegisterFields(role) {
            studentFields.style.display = 'none';
            guruFields.style.display = 'none';
            adminFields.style.display = 'none';
            
            if (role === 'pelajar') {
                studentFields.style.display = 'block';
            } else if (role === 'guru') {
                guruFields.style.display = 'block';
            } else if (role === 'admin') {
                adminFields.style.display = 'block';
            }
        }

        registerRoleOptions.forEach(option => {
            option.addEventListener('click', function() {
                registerRoleOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                const role = this.getAttribute('data-role');
                registerRoleInput.value = role;
                updateRegisterFields(role);
            });
        });

        // ==================== DEMO AUTO-FILL ====================
        document.querySelectorAll('.demo-hint').forEach(row => {
            row.addEventListener('click', function() {
                const username = this.getAttribute('data-username');
                const password = this.getAttribute('data-password');
                const role = this.getAttribute('data-role');
                const subrole = this.getAttribute('data-subrole');
                
                if (username && password) {
                    switchTab('login');
                    
                    usernameInput.value = username;
                    passwordInput.value = password;
                    
                    const roleOption = document.querySelector(`.role-option[data-role="${role}"]`);
                    if (roleOption) {
                        roleOption.click();
                    }
                    
                    if (subrole) {
                        setTimeout(() => {
                            const subroleOption = document.querySelector(`.subrole-option[data-subrole="${subrole}"]`);
                            if (subroleOption) {
                                subroleOption.click();
                            }
                        }, 100);
                    }
                }
            });
        });
        
        // Initialize
        updateRegisterFields('pelajar');
    </script>
</body>
</html>