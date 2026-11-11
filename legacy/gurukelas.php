<?php
// ======================================================
// FAIL PANEL GURU KELAS (VIEW ONLY)
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';
checkLogin();
checkRole(['guru']);

$user = getCurrentUser();

// Check if user is guru kelas
if ($user['sub_role'] !== 'guru_kelas') {
    header('Location: unauthorized.php');
    exit();
}

// Get teacher profile
$teacher_id = $user['profile_id'];
$stmt = $conn->prepare('
    SELECT t.*, u.email, u.last_login
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
');
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get class taught by this teacher
$stmt = $conn->prepare("
    SELECT c.*, 
        COUNT(DISTINCT s.id) as student_count,
        t.full_name as teacher_name
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id AND s.status = 'Aktif'
    LEFT JOIN teachers t ON c.teacher_id = t.id
    WHERE c.teacher_id = ? AND c.status = 'Aktif'
    GROUP BY c.id
");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (! $class) {
    $class = ['id' => 0, 'class_name' => 'Tiada Kelas', 'student_count' => 0];
}

// Get students in this class
$students = [];
if ($class['id'] > 0) {
    $stmt = $conn->prepare("
        SELECT s.*, u.email
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.class_id = ? AND s.status = 'Aktif'
        ORDER BY s.full_name
    ");
    $stmt->bind_param('i', $class['id']);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get subjects for this class (from class_subjects)
$subjects = [];
if ($class['id'] > 0) {
    $stmt = $conn->prepare("
        SELECT DISTINCT s.*, cs.semester, t.full_name as teacher_name
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        LEFT JOIN teachers t ON cs.teacher_id = t.id
        WHERE cs.class_id = ? AND cs.status = 'Aktif' AND s.status = 'Aktif'
        ORDER BY cs.semester, s.subject_name
    ");
    $stmt->bind_param('i', $class['id']);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get exam types
$examTypes = [];
$result = $conn->query('SELECT id, exam_name, exam_code FROM exam_types WHERE is_active = 1 ORDER BY exam_order');
while ($row = $result->fetch_assoc()) {
    $examTypes[] = $row;
}

// Get marks for students in this class
$marks = [];
if ($class['id'] > 0 && ! empty($students)) {
    $student_ids = array_column($students, 'id');
    $subject_ids = array_column($subjects, 'id');

    if (! empty($student_ids) && ! empty($subject_ids)) {
        $student_placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        $subject_placeholders = implode(',', array_fill(0, count($subject_ids), '?'));
        $types = str_repeat('i', count($student_ids)).str_repeat('i', count($subject_ids));
        $params = array_merge($student_ids, $subject_ids);

        $stmt = $conn->prepare("
            SELECT m.*, s.subject_name, s.subject_code, st.full_name as student_name, st.matric_no,
                   e.exam_name, et.exam_code as exam_type_code, et.exam_name as exam_type_name
            FROM marks m
            JOIN subjects s ON m.subject_id = s.id
            JOIN students st ON m.student_id = st.id
            LEFT JOIN exams e ON m.exam_id = e.id
            LEFT JOIN exam_types et ON e.exam_type_id = et.id
            WHERE m.student_id IN ($student_placeholders) AND m.subject_id IN ($subject_placeholders) AND m.marks IS NOT NULL
            ORDER BY st.full_name, m.semester, m.exam_id, s.subject_name
        ");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $marks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Calculate class statistics
$classAvgPng = 0;
$passCount = 0;
$totalMarks = 0;
$gradeDistribution = ['A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D' => 0];

foreach ($marks as $mark) {
    $totalMarks++;
    if ($mark['pointer'] >= 2.00) {
        $passCount++;
    }
    if (isset($gradeDistribution[$mark['grade']])) {
        $gradeDistribution[$mark['grade']]++;
    }
}
$classAvgPng = $totalMarks > 0 ? round(array_sum(array_column($marks, 'pointer')) / $totalMarks, 2) : 0;
$passRate = $totalMarks > 0 ? round(($passCount / $totalMarks) * 100, 1) : 0;

// Get subject averages
$subjectAverages = [];
$subjectNames = [];
foreach ($subjects as $subject) {
    $subjectMarks = array_filter($marks, function ($m) use ($subject) {
        return $m['subject_id'] == $subject['id'];
    });
    $avg = ! empty($subjectMarks) ? round(array_sum(array_column($subjectMarks, 'marks')) / count($subjectMarks), 1) : 0;
    $subjectAverages[] = $avg;
    $subjectNames[] = $subject['subject_name'];
}

// Get announcements
$announcements = getAnnouncements('all', $class['id']);

// Process AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Tindakan tidak sah'];

    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        if (strlen($new_password) < 6) {
            $response['message'] = 'Kata laluan baru minimum 6 aksara';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_password, $result['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt2->bind_param('si', $new_hash, $user['id']);

            if ($stmt2->execute()) {
                $response['success'] = true;
                $response['message'] = 'Kata laluan berjaya ditukar';
                logActivity($user['id'], 'CHANGE_PASSWORD', 'Menukar kata laluan');
            } else {
                $response['message'] = 'Gagal menukar kata laluan';
            }
        } else {
            $response['message'] = 'Kata laluan semasa tidak tepat';
        }

        echo json_encode($response);
        exit;
    }

    if ($action === 'update_profile') {
        $name = validateAndSanitize($_POST['name'] ?? '');
        $email = validateAndSanitize($_POST['email'] ?? '', 'email');
        $phone = validateAndSanitize($_POST['phone'] ?? '');

        if (empty($name)) {
            $response['message'] = 'Nama tidak boleh kosong';
            echo json_encode($response);
            exit;
        }

        $teacher_id = $user['profile_id'];
        $stmt = $conn->prepare('UPDATE teachers SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('ssi', $name, $phone, $teacher_id);
        $stmt->execute();

        $stmt = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->bind_param('si', $email, $user['id']);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = 'Profil berjaya dikemaskini';
        logActivity($user['id'], 'UPDATE_PROFILE', 'Mengemaskini profil guru');

        echo json_encode($response);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #059669;
            --primary-light: #34d399;
            --primary-dark: #047857;
            --accent: #0d9488;
            --accent-glow: rgba(13, 148, 136, 0.25);
            --bg-dark: #081014;
            --bg-card: rgba(16, 28, 25, 0.75);
            --bg-card-hover: rgba(22, 40, 36, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glass-focus: rgba(16, 185, 129, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-subtle: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-glass: 0 12px 32px -4px rgba(0, 0, 0, 0.5), 0 4px 12px -2px rgba(0, 0, 0, 0.3);
            --gradient-primary: linear-gradient(135deg, #059669 0%, #10b981 50%, #06b6d4 100%);
            --gradient-card: linear-gradient(145deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.01) 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        
        body { 
            background: var(--bg-dark); 
            color: var(--text-main); 
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            letter-spacing: -0.01em;
        }

        /* Ambient Glow Mesh Orbs */
        .bg-mesh {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.35;
        }
        .bg-orb-1 { width: 550px; height: 550px; background: radial-gradient(circle, #059669 0%, transparent 70%); top: -120px; left: -100px; }
        .bg-orb-2 { width: 500px; height: 500px; background: radial-gradient(circle, #0d9488 0%, transparent 70%); top: 40%; right: -150px; }
        .bg-orb-3 { width: 450px; height: 450px; background: radial-gradient(circle, #06b6d4 0%, transparent 70%); bottom: -100px; left: 25%; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 1; }

        /* Header Modernization */
        header {
            background: rgba(8, 16, 20, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-glass);
            padding: 0.9rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        .header-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        
        .logo { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            font-size: 1.15rem; 
            font-weight: 800; 
            letter-spacing: -0.02em;
            color: #ffffff; 
            cursor: pointer; 
            transition: transform 0.2s ease;
        }
        .logo:hover { transform: translateY(-1px); }
        
        .logo-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #059669, #10b981);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
        }

        nav ul { 
            display: flex; 
            list-style: none; 
            gap: 6px; 
            background: rgba(255,255,255,0.04); 
            padding: 5px 8px; 
            border-radius: 9999px; 
            border: 1px solid rgba(255, 255, 255, 0.06);
            flex-wrap: wrap; 
        }
        nav a { 
            color: var(--text-muted); 
            text-decoration: none; 
            font-weight: 600; 
            padding: 8px 18px; 
            border-radius: 9999px; 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            font-size: 0.82rem; 
        }
        nav a:hover { 
            color: #ffffff; 
            background: rgba(255,255,255,0.08); 
        }
        nav a.active { 
            color: #ffffff; 
            background: var(--gradient-primary); 
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35); 
        }
        
        .user-info { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            background: rgba(255,255,255,0.04); 
            border: 1px solid var(--border-glass); 
            padding: 5px 16px 5px 6px; 
            border-radius: 9999px; 
            cursor: pointer; 
            transition: all 0.25s ease;
        }
        .user-info:hover { 
            background: rgba(255,255,255,0.08); 
            border-color: rgba(52, 211, 153, 0.4);
            transform: translateY(-1px);
        }
        .avatar { 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #059669, #10b981); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 800; 
            color: white; 
            font-size: 0.85rem;
            box-shadow: 0 2px 10px rgba(5, 150, 105, 0.4);
        }

        .main-content { padding: 2.2rem 0 3rem; min-height: calc(100vh - 140px); }
        
        .page-title { 
            font-size: 1.85rem; 
            margin-bottom: 1.8rem; 
            color: #ffffff; 
            font-weight: 800;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 1.3em;
            background: var(--gradient-primary);
            border-radius: 4px;
        }

        .page-section { display: none; animation: fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
        .page-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        /* Dashboard Stat Cards */
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 2rem; }
        
        .card { 
            background: var(--bg-card); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px; 
            box-shadow: var(--shadow-glass); 
            padding: 1.4rem 1.5rem; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            position: relative; 
            overflow: hidden;
            cursor: pointer; 
        }
        .card::before { 
            content: ''; 
            position: absolute; 
            top: 0; left: 0; right: 0; 
            height: 3px; 
            background: var(--gradient-primary); 
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .card:hover { 
            transform: translateY(-5px); 
            border-color: rgba(52, 211, 153, 0.4);
            box-shadow: 0 20px 40px -10px rgba(5, 150, 105, 0.25);
            background: var(--bg-card-hover);
        }
        .card:hover::before { opacity: 1; height: 4px; }
        
        .card-title { font-size: 0.78rem; margin-bottom: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
        .card-value { 
            font-size: 2.2rem; 
            font-weight: 800; 
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ffffff 30%, #a7f3d0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-icon { 
            position: absolute; 
            top: 1.4rem; right: 1.4rem; 
            font-size: 2.4rem; 
            color: #10b981; 
            opacity: 0.2; 
            transition: all 0.3s ease;
        }
        .card:hover .card-icon { opacity: 0.4; transform: scale(1.1) rotate(5deg); color: #34d399; }

        /* Charts */
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 24px; margin-bottom: 2rem; }
        .chart-container { 
            background: var(--bg-card); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px; 
            box-shadow: var(--shadow-glass); 
            padding: 1.5rem; 
        }
        .chart-title { font-size: 1.05rem; margin-bottom: 1.2rem; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* Tables */
        .table-container { 
            background: var(--bg-card); 
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px; 
            box-shadow: var(--shadow-glass); 
            overflow: hidden; 
            margin-bottom: 2rem; 
            overflow-x: auto; 
        }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 1rem 1.2rem; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.85rem; }
        th { 
            background: rgba(255, 255, 255, 0.03); 
            color: var(--text-muted); 
            font-weight: 700; 
            font-size: 0.78rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }
        tr:hover td { background: rgba(255, 255, 255, 0.03); }

        /* Buttons */
        .btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 8px; 
            padding: 10px 20px; 
            background: var(--gradient-primary); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-size: 0.85rem; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
            text-decoration: none;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.45);
            filter: brightness(1.1);
        }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; border-radius: 8px; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3); color: #ffffff; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3); }
        .btn-info { background: linear-gradient(135deg, #0d9488, #0f766e); box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3); }

        /* Filter Section */
        .filter-section { 
            background: var(--bg-card); 
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px; 
            padding: 1.4rem 1.6rem; 
            margin-bottom: 1.8rem; 
            box-shadow: var(--shadow-glass); 
        }
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 0; }
        .form-group { margin-bottom: 0.9rem; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.8rem; color: var(--text-muted); }
        .form-control { 
            width: 100%; 
            padding: 10px 14px; 
            background: rgba(15, 23, 42, 0.7); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            color: #ffffff;
            border-radius: 12px; 
            font-size: 0.85rem; 
            transition: all 0.25s ease; 
        }
        .form-control:focus { 
            outline: none; 
            border-color: #34d399; 
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
            background: rgba(15, 23, 42, 0.9);
        }
        .form-control:disabled { 
            background: rgba(255, 255, 255, 0.03); 
            border-color: rgba(255, 255, 255, 0.05); 
            color: #64748b; 
            cursor: not-allowed; 
        }
        .form-control option {
            background: #0f172a;
            color: #ffffff;
        }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 2rem; }
        .stat-card { 
            background: var(--bg-card); 
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 16px; 
            padding: 1.2rem 1rem; 
            text-align: center; 
            box-shadow: var(--shadow-glass); 
            transition: transform 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-value { 
            font-size: 1.7rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #ffffff, #a7f3d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; font-weight: 600; text-transform: uppercase; }

        /* Badges */
        .badge { 
            padding: 4px 10px; 
            border-radius: 9999px; 
            font-size: 0.7rem; 
            font-weight: 700; 
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-excellent { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-good { background: rgba(5, 150, 105, 0.15); color: #6ee7b7; border: 1px solid rgba(5, 150, 105, 0.3); }
        .badge-average { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-poor { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-info { background: rgba(6, 182, 212, 0.15); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.3); }

        .alert { 
            padding: 14px 18px; 
            border-radius: 16px; 
            margin-bottom: 1.8rem; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            font-size: 0.88rem;
            backdrop-filter: blur(16px);
        }
        .alert-warning { background: rgba(245, 158, 11, 0.12); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.25); }
        .alert-info { background: rgba(16, 185, 129, 0.12); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.25); }

        /* Modal */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0, 0, 0, 0.75); 
            backdrop-filter: blur(8px);
            z-index: 1000; 
            justify-content: center; 
            align-items: center; 
            padding: 20px; 
        }
        .modal-content { 
            background: #0d1a16; 
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px; 
            width: 90%; 
            max-width: 520px; 
            max-height: 85vh; 
            overflow-y: auto; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            animation: modalSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); 
        }
        @keyframes modalSlideIn { from { opacity: 0; transform: translateY(-20px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { 
            padding: 1.2rem 1.5rem; 
            background: rgba(255, 255, 255, 0.03); 
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: white; 
            border-radius: 24px 24px 0 0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .modal-title { font-size: 1.1rem; font-weight: 700; }
        .close-modal { background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted); transition: color 0.2s; }
        .close-modal:hover { color: #ffffff; }
        .modal-body { padding: 1.5rem; }

        /* Result Slip Styling */
        .result-slip { 
            background: var(--bg-card); 
            backdrop-filter: blur(24px);
            border-radius: 24px; 
            padding: 2.5rem; 
            max-width: 860px; 
            margin: 0 auto; 
            border: 1px solid rgba(16, 185, 129, 0.3); 
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.7);
            position: relative;
        }
        .result-slip::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 5px;
            background: var(--gradient-primary);
            border-radius: 24px 24px 0 0;
        }
        .result-header { text-align: center; margin-bottom: 2rem; padding-bottom: 1.4rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .result-header h2 { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em; color: #ffffff; }
        .result-header p { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }
        .result-details { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 1.2rem; 
            margin-bottom: 2rem; 
            background: rgba(255, 255, 255, 0.02); 
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1.2rem 1.5rem; 
            border-radius: 16px; 
            font-size: 0.85rem;
        }
        .result-details p { margin-bottom: 6px; color: #cbd5e1; }
        .result-details strong { color: #ffffff; }
        .result-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .result-table th, .result-table td { padding: 0.9rem; border: 1px solid rgba(255, 255, 255, 0.08); text-align: center; font-size: 0.85rem; }
        .result-table th { background: rgba(5, 150, 105, 0.2); color: #6ee7b7; font-weight: 700; }
        .result-table tfoot td { background: rgba(255, 255, 255, 0.02); font-weight: 800; font-size: 1rem; color: #ffffff; }

        .toast-notification { 
            position: fixed; 
            bottom: 24px; right: 24px; 
            padding: 14px 22px; 
            border-radius: 16px; 
            color: white; 
            font-size: 0.88rem;
            font-weight: 600;
            z-index: 1100; 
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: slideInRight 0.3s cubic-bezier(0.16, 1, 0.3, 1); 
        }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        footer { 
            background: rgba(8, 16, 20, 0.8); 
            border-top: 1px solid var(--border-glass); 
            color: var(--text-muted); 
            padding: 1.4rem 0; 
            text-align: center; 
            margin-top: 3rem; 
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .header-content { flex-direction: column; }
            nav ul { justify-content: center; }
            .dashboard-cards { grid-template-columns: 1fr; }
            .chart-grid { grid-template-columns: 1fr; }
            .result-details { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="bg-mesh">
        <div class="bg-orb bg-orb-1"></div>
        <div class="bg-orb bg-orb-2"></div>
        <div class="bg-orb bg-orb-3"></div>
    </div>
    <header>
        <div class="container header-content">
            <div class="logo" onclick="showSection('dashboard')">
                <div class="logo-icon-box">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <span>Sistem e-Exam STPM | Guru Kelas</span>
            </div>
            <nav>
                <ul>
                    <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#" class="nav-link" data-section="students"><i class="fas fa-users"></i> Senarai Pelajar</a></li>
                    <li><a href="#" class="nav-link" data-section="marks"><i class="fas fa-chart-line"></i> Markah Pelajar</a></li>
                    <li><a href="#" class="nav-link" data-section="results"><i class="fas fa-file-alt"></i> Slip Keputusan</a></li>
                    <li><a href="#" class="nav-link" data-section="profile"><i class="fas fa-user-circle"></i> Profil</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Keluar</a></li>
                </ul>
            </nav>
            <div class="user-info" onclick="showSection('profile')">
                <div class="avatar"><?php echo strtoupper(substr($teacher['full_name'], 0, 2)); ?></div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($teacher['full_name']); ?></div>
                    <div style="font-size: 0.7rem;">Guru Kelas - <?php echo htmlspecialchars($class['class_name']); ?></div>
                </div>
            </div>
        </div>
    </header>

    <div class="container main-content">
        <!-- Dashboard Section -->
        <section id="dashboard" class="page-section active">
            <h1 class="page-title">Dashboard Guru Kelas</h1>
            
            <div class="alert alert-warning">
                <i class="fas fa-eye"></i>
                <span><strong>Mod Lihatan Sahaja</strong> — Anda adalah Guru Kelas, hanya boleh melihat markah pelajar. Untuk mengedit markah, sila hubungi Guru Subjek.</span>
            </div>

            <div class="dashboard-cards">
                <div class="card" onclick="showSection('students')">
                    <div class="card-title">Jumlah Pelajar</div>
                    <div class="card-value"><?php echo count($students); ?></div>
                    <i class="fas fa-users card-icon"></i>
                </div>
                <div class="card" onclick="showSection('marks')">
                    <div class="card-title">Purata Kelas (PNG)</div>
                    <div class="card-value"><?php echo $classAvgPng; ?></div>
                    <i class="fas fa-chart-line card-icon"></i>
                </div>
                <div class="card" onclick="showSection('marks')">
                    <div class="card-title">Kadar Kelulusan</div>
                    <div class="card-value"><?php echo $passRate; ?>%</div>
                    <i class="fas fa-check-circle card-icon"></i>
                </div>
                <div class="card">
                    <div class="card-title">Bilangan Subjek</div>
                    <div class="card-value"><?php echo count($subjects); ?></div>
                    <i class="fas fa-book card-icon"></i>
                </div>
            </div>

            <div class="chart-grid">
                <div class="chart-container">
                    <h3 class="chart-title">Prestasi Kelas Mengikut Subjek</h3>
                    <canvas id="subjectChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">Taburan Gred Keseluruhan</h3>
                    <canvas id="gradeChart"></canvas>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($students); ?></div>
                    <div class="stat-label">Jumlah Pelajar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $classAvgPng; ?></div>
                    <div class="stat-label">PNG Purata</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $passRate; ?>%</div>
                    <div class="stat-label">Kadar Kelulusan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalMarks; ?></div>
                    <div class="stat-label">Jumlah Rekod Markah</div>
                </div>
            </div>
        </section>

        <!-- Students Section -->
        <section id="students" class="page-section">
            <h1 class="page-title">Senarai Pelajar - <?php echo htmlspecialchars($class['class_name']); ?></h1>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>Senarai pelajar dalam kelas <?php echo htmlspecialchars($class['class_name']); ?>.</span>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>No. Matrik</th><th>Nama Pelajar</th><th>No. Telefon</th><th>Emel</th><th>Status</th><th>Tindakan</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['matric_no']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                            <td><span class="badge badge-excellent">Aktif</span></td>
                            <td>
                                <button class="btn btn-sm btn-view" onclick="viewStudentResult(<?php echo $student['id']; ?>)">
                                    <i class="fas fa-eye"></i> Lihat Slip
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($students)) { ?>
                        <tr><td colspan="6" style="text-align:center;">Tiada pelajar dalam kelas ini</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Marks Section (View Only) -->
        <section id="marks" class="page-section">
            <h1 class="page-title">Markah Pelajar - <?php echo htmlspecialchars($class['class_name']); ?></h1>
            
            <div class="alert alert-warning">
                <i class="fas fa-lock"></i>
                <span><strong>Mod Lihatan Sahaja</strong> — Anda hanya boleh melihat markah. Untuk mengedit, sila hubungi Guru Subjek.</span>
            </div>

            <div class="filter-section">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Pilih Subjek</label>
                        <select id="filterSubject" class="form-control" onchange="filterMarks()">
                            <option value="">Semua Subjek</option>
                            <?php foreach ($subjects as $subject) { ?>
                            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?> (Semester <?php echo $subject['semester']; ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Semester</label>
                        <select id="filterSemester" class="form-control" onchange="filterMarks()">
                            <option value="">Semua Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Peperiksaan</label>
                        <select id="filterExam" class="form-control" onchange="filterMarks()">
                            <option value="">Semua Peperiksaan</option>
                            <?php foreach ($examTypes as $et) { ?>
                            <option value="<?php echo $et['exam_code']; ?>"><?php echo htmlspecialchars($et['exam_name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-info" onclick="filterMarks()"><i class="fas fa-search"></i> Cari</button>
            </div>

            <div class="table-container">
                <table id="marksTable">
                    <thead>
                        <tr><th>No. Matrik</th><th>Nama Pelajar</th><th>Subjek</th><th>Semester</th><th>Jenis Peperiksaan</th><th>Markah</th><th>Gred</th><th>Pointer</th><th>Status</th></tr>
                    </thead>
                    <tbody id="marksTableBody">
                        <?php foreach ($marks as $mark) { ?>
                        <tr class="mark-row" data-subject="<?php echo $mark['subject_id']; ?>" data-semester="<?php echo $mark['semester']; ?>" data-exam="<?php echo htmlspecialchars($mark['exam_type_code'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($mark['matric_no']); ?></td>
                            <td><?php echo htmlspecialchars($mark['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                            <td><?php echo $mark['semester']; ?></td>
                            <td><?php echo htmlspecialchars($mark['exam_type_name'] ?? '-'); ?></td>
                            <td><?php echo $mark['marks']; ?></td>
                            <td><span class="badge <?php echo $mark['pointer'] >= 3.67 ? 'badge-excellent' : ($mark['pointer'] >= 2.00 ? 'badge-good' : 'badge-poor'); ?>"><?php echo $mark['grade']; ?></span></td>
                            <td><?php echo $mark['pointer']; ?></td>
                            <td><?php echo $mark['pointer'] >= 2.00 ? '<span class="badge badge-excellent">Lulus</span>' : '<span class="badge badge-poor">Gagal</span>'; ?></td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($marks)) { ?>
                        <tr><td colspan="9" style="text-align:center;">Tiada rekod markah</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Results Section -->
        <section id="results" class="page-section">
            <h1 class="page-title">Slip Keputusan Pelajar</h1>
            
            <div class="filter-section">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Pilih Pelajar</label>
                        <select id="resultStudent" class="form-control">
                            <option value="">Pilih Pelajar</option>
                            <?php foreach ($students as $student) { ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['matric_no']); ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Semester</label>
                        <select id="resultSemester" class="form-control">
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Peperiksaan</label>
                        <select id="resultExam" class="form-control">
                            <option value="all">Semua Peperiksaan</option>
                            <?php foreach ($examTypes as $et) { ?>
                            <option value="<?php echo $et['exam_code']; ?>"><?php echo htmlspecialchars($et['exam_name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-success" onclick="generateResultSlip()"><i class="fas fa-file-alt"></i> Jana Slip Keputusan</button>
            </div>

            <div id="resultSlipContainer" style="display:none;">
                <div class="result-slip" id="resultSlip">
                    <div class="result-header">
                        <h2>SLIP KEPUTUSAN PEPERIKSAAN STPM</h2>
                        <p id="slipSubtitle">SEMESTER <span id="slipSemester">1</span> - SESI 2024/2025</p>
                    </div>
                    <div class="result-details">
                        <div>
                            <p><strong>Nama:</strong> <span id="slipName">-</span></p>
                            <p><strong>No. Matrik:</strong> <span id="slipMatric">-</span></p>
                        </div>
                        <div>
                            <p><strong>Kelas:</strong> <span id="slipClass"><?php echo htmlspecialchars($class['class_name']); ?></span></p>
                            <p><strong>Tarikh:</strong> <span id="slipDate">-</span></p>
                        </div>
                    </div>
                    <table class="result-table">
                        <thead><tr><th>Jenis Peperiksaan</th><th>Kod</th><th>Subjek</th><th>Markah</th><th>Gred</th><th>Pointer</th></tr></thead>
                        <tbody id="slipSubjects"></tbody>
                        <tfoot><tr><td colspan="5"><strong>PNG:</strong></td><td><strong id="slipGPA">-</strong></td></tr></tfoot>
                    </table>
                    <div class="print-btn" style="display: flex; gap: 12px; justify-content: center;">
                        <button class="btn btn-warning" onclick="printResultSlip()"><i class="fas fa-print"></i> Cetak</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="page-section">
            <h1 class="page-title">Profil Guru Kelas</h1>
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="filter-section">
                    <div style="text-align: center;">
                        <div class="avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 1rem;"><?php echo strtoupper(substr($teacher['full_name'], 0, 2)); ?></div>
                        <h2><?php echo htmlspecialchars($teacher['full_name']); ?></h2>
                        <p><span class="badge badge-info">Guru Kelas - <?php echo htmlspecialchars($class['class_name']); ?></span></p>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <div class="form-group"><label>ID Guru</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher['teacher_id']); ?>" readonly></div>
                    <div class="form-group"><label>Nama Penuh</label><input type="text" id="profileName" class="form-control" value="<?php echo htmlspecialchars($teacher['full_name']); ?>"></div>
                    <div class="form-group"><label>Kelas Diajar</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($class['class_name']); ?>" readonly></div>
                    <div class="form-group"><label>Emel</label><input type="email" id="profileEmail" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>"></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="profilePhone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>"></div>
                    <button class="btn btn-warning" style="width:100%; margin-top: 1rem;" onclick="openChangePasswordModal()"><i class="fas fa-key"></i> Tukar Kata Laluan</button>
                </div>
            </div>
        </section>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Tukar Kata Laluan</h3><button class="close-modal" onclick="closeModal('changePasswordModal')">&times;</button></div>
            <div class="modal-body">
                <form onsubmit="changePassword(event)">
                    <div class="form-group"><label>Kata Laluan Semasa</label><input type="password" id="currentPwd" class="form-control" required></div>
                    <div class="form-group"><label>Kata Laluan Baru</label><input type="password" id="newPwd" class="form-control" required></div>
                    <div class="form-group"><label>Sahkan</label><input type="password" id="confirmPwd" class="form-control" required></div>
                    <button type="submit" class="btn btn-success" style="width:100%">Tukar</button>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 Sistem Peperiksaan STPM | Guru Kelas Panel</p>
        </div>
    </footer>

    <script>
        const subjectAverages = <?php echo json_encode($subjectAverages); ?>;
        const subjectNames = <?php echo json_encode($subjectNames); ?>;
        const gradeDistribution = <?php echo json_encode(array_values($gradeDistribution)); ?>;
        const studentsData = <?php echo json_encode($students); ?>;
        const marksData = <?php echo json_encode($marks); ?>;
        const classId = <?php echo $class['id']; ?>;

        let charts = {};

        function showSection(sectionId) {
            document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            let activeLink = Array.from(document.querySelectorAll('.nav-link')).find(l => l.dataset.section === sectionId);
            if(activeLink) activeLink.classList.add('active');
        }

        function filterMarks() {
            const subjectFilter = document.getElementById('filterSubject').value;
            const semesterFilter = document.getElementById('filterSemester').value;
            const examFilter = document.getElementById('filterExam').value;
            const rows = document.querySelectorAll('.mark-row');
            
            rows.forEach(row => {
                const subject = row.getAttribute('data-subject');
                const semester = row.getAttribute('data-semester');
                const exam = row.getAttribute('data-exam');
                let show = true;
                
                if (subjectFilter && subject !== subjectFilter) show = false;
                if (semesterFilter && semester !== semesterFilter) show = false;
                if (examFilter && exam !== examFilter) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function viewStudentResult(studentId) {
            const select = document.getElementById('resultStudent');
            select.value = studentId;
            showSection('results');
            generateResultSlip();
        }

        function generateResultSlip() {
            const studentId = document.getElementById('resultStudent').value;
            const semester = document.getElementById('resultSemester').value;
            const examFilter = document.getElementById('resultExam').value;
            
            if (!studentId) {
                showNotification('Sila pilih pelajar', 'error');
                return;
            }
            
            const student = studentsData.find(s => s.id == studentId);
            if (!student) return;
            
            let studentMarks = marksData.filter(m => m.student_id == studentId && m.semester == semester);
            
            if (examFilter !== 'all') {
                studentMarks = studentMarks.filter(m => m.exam_type_code == examFilter);
            }
            
            document.getElementById('slipName').textContent = student.full_name;
            document.getElementById('slipMatric').textContent = student.matric_no;
            document.getElementById('slipSemester').textContent = semester;
            document.getElementById('slipDate').textContent = new Date().toLocaleDateString('ms-MY');
            
            let subtitle = 'SEMESTER ' + semester + ' - SESI 2024/2025';
            if (examFilter !== 'all') {
                const examType = document.getElementById('resultExam').options[document.getElementById('resultExam').selectedIndex]?.text;
                subtitle = examType + ' - SEMESTER ' + semester;
            }
            document.getElementById('slipSubtitle').textContent = subtitle;
            
            const tbody = document.getElementById('slipSubjects');
            tbody.innerHTML = '';
            
            if (studentMarks.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">Tiada markah untuk semester ini</td></tr>';
                document.getElementById('slipGPA').textContent = '-';
            } else {
                let totalPointer = 0;
                studentMarks.forEach(mark => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${escapeHtml(mark.exam_type_name || '-')}${mark.exam_name ? ' (' + mark.exam_name + ')' : ''}</td>
                        <td>${escapeHtml(mark.subject_code || '-')}</td>
                        <td>${escapeHtml(mark.subject_name)}</td>
                        <td>${mark.marks}</td>
                        <td>${mark.grade}</td>
                        <td>${mark.pointer}</td>
                    `;
                    tbody.appendChild(row);
                    totalPointer += parseFloat(mark.pointer);
                });
                const gpa = (totalPointer / studentMarks.length).toFixed(2);
                document.getElementById('slipGPA').textContent = gpa;
            }
            
            document.getElementById('resultSlipContainer').style.display = 'block';
        }

        function printResultSlip() {
            const content = document.getElementById('resultSlip').cloneNode(true);
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html><head><title>Slip Keputusan STPM</title>
                <style>
                    body { font-family: 'Segoe UI', sans-serif; padding: 40px; }
                    .result-slip { max-width: 800px; margin: 0 auto; border: 2px solid #1e3a8a; padding: 20px; border-radius: 16px; }
                    .result-header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; }
                    .result-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
                    .result-table { width: 100%; border-collapse: collapse; }
                    .result-table th, .result-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                    .result-table th { background: #3b82f6; color: white; }
                    @media print { body { padding: 0; } .print-btn { display: none; } }
                </style>
                </head><body>${content.outerHTML}</body></html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        function initCharts() {
            Chart.defaults.color = '#94a3b8';
            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

            const subjectCtx = document.getElementById('subjectChart').getContext('2d');
            charts.subject = new Chart(subjectCtx, {
                type: 'bar',
                data: {
                    labels: subjectNames.length ? subjectNames : ['Tiada Data'],
                    datasets: [{
                        label: 'Purata Markah (%)',
                        data: subjectAverages.length ? subjectAverages : [0],
                        backgroundColor: 'rgba(16, 185, 129, 0.65)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: { 
                    responsive: true, 
                    scales: { 
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    },
                    plugins: { legend: { labels: { color: '#cbd5e1', font: { weight: 'bold' } } } }
                }
            });

            const gradeCtx = document.getElementById('gradeChart').getContext('2d');
            charts.grade = new Chart(gradeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['A', 'A-', 'B+', 'B', 'C+', 'C', 'D'],
                    datasets: [{ 
                        data: gradeDistribution.length ? gradeDistribution : [0,0,0,0,0,0,0], 
                        backgroundColor: ['#10b981', '#34d399', '#6ee7b7', '#f59e0b', '#fbbf24', '#fcd34d', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#0d1a16'
                    }]
                },
                options: { 
                    responsive: true, 
                    plugins: { legend: { position: 'bottom', labels: { color: '#cbd5e1', boxWidth: 12, padding: 15 } } } 
                }
            });
        }

        function openChangePasswordModal() { document.getElementById('changePasswordModal').style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        function changePassword(e) {
            e.preventDefault();
            let newPwd = document.getElementById('newPwd').value;
            let confirmPwd = document.getElementById('confirmPwd').value;
            
            if(newPwd !== confirmPwd) {
                showNotification('Kata laluan tidak sepadan!', 'error');
                return;
            }
            if(newPwd.length < 6) {
                showNotification('Minimum 6 aksara!', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', document.getElementById('currentPwd').value);
            formData.append('new_password', newPwd);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Kata laluan berjaya ditukar!', 'success');
                    closeModal('changePasswordModal');
                    e.target.reset();
                } else {
                    showNotification(data.message || 'Gagal menukar kata laluan', 'error');
                }
            });
        }

        function updateProfile() {
            const formData = new FormData();
            formData.append('action', 'update_profile');
            formData.append('name', document.getElementById('profileName').value);
            formData.append('email', document.getElementById('profileEmail').value);
            formData.append('phone', document.getElementById('profilePhone').value);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Profil dikemaskini', 'success');
                } else {
                    showNotification(data.message || 'Gagal mengemaskini', 'error');
                }
            });
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showNotification(msg, type) { 
            let toast = document.createElement('div'); 
            toast.className = 'toast-notification'; 
            toast.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'; 
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}`;
            document.body.appendChild(toast); 
            setTimeout(() => toast.remove(), 3000); 
        }

        document.getElementById('profileName')?.addEventListener('change', updateProfile);
        document.getElementById('profileEmail')?.addEventListener('change', updateProfile);
        document.getElementById('profilePhone')?.addEventListener('change', updateProfile);

        document.addEventListener('DOMContentLoaded', () => {
            initCharts();
            document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); }));
        });
        
        window.onclick = e => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
    </script>
</body>
</html>