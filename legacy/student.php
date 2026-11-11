<?php
// ======================================================
// FAIL PANEL PELAJAR
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';
checkLogin();
checkRole(['pelajar']);

$user = getCurrentUser();

// Get student profile
$student_id = $user['profile_id'];
$stmt = $conn->prepare('
    SELECT s.*, c.class_name, u.email, u.last_login
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.id = ?
');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (! $student) {
    header('Location: logout.php');
    exit();
}

// Get student's marks with exam info
$stmt = $conn->prepare('
    SELECT m.*, s.subject_name, s.subject_code, c.class_name,
        e.exam_name, et.exam_name as exam_type_name, et.exam_code as exam_type_code
    FROM marks m
    JOIN subjects s ON m.subject_id = s.id
    JOIN classes c ON m.class_id = c.id
    LEFT JOIN exams e ON m.exam_id = e.id
    LEFT JOIN exam_types et ON e.exam_type_id = et.id
    WHERE m.student_id = ?
    ORDER BY m.semester, m.exam_id, s.subject_name
');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$marks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get student's subjects (from class_subjects)
$studentSubjects = getSubjectsForStudent($student_id);

// Calculate PNG for each semester
$semesterPng = [];
$semesterData = [];
foreach ($marks as $mark) {
    $sem = $mark['semester'];
    if (! isset($semesterData[$sem])) {
        $semesterData[$sem] = ['total_pointer' => 0, 'count' => 0];
    }
    $semesterData[$sem]['total_pointer'] += $mark['pointer'];
    $semesterData[$sem]['count']++;
}

foreach ($semesterData as $sem => $data) {
    $semesterPng[$sem] = $data['count'] > 0 ? round($data['total_pointer'] / $data['count'], 2) : 0;
}

// Calculate overall PNG
$overallPng = ! empty($semesterPng) ? round(array_sum($semesterPng) / count($semesterPng), 2) : 0;

// Get grade distribution
$gradeDist = ['A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D' => 0];
foreach ($marks as $mark) {
    if (isset($gradeDist[$mark['grade']])) {
        $gradeDist[$mark['grade']]++;
    }
}

// Get subject performance
$subjectMarks = [];
foreach ($marks as $mark) {
    if (! isset($subjectMarks[$mark['subject_name']])) {
        $subjectMarks[$mark['subject_name']] = [];
    }
    $subjectMarks[$mark['subject_name']][] = $mark['marks'];
}

$subjectAvg = [];
foreach ($subjectMarks as $subject => $marksList) {
    $subjectAvg[$subject] = round(array_sum($marksList) / count($marksList), 1);
}

// Get announcements
$announcements = getAnnouncements('students', $student['class_id']);

// Get class rank (approximate)
$rank = 0;
$stmt = $conn->prepare('
    SELECT s.id, AVG(m.pointer) as avg_png
    FROM students s
    JOIN marks m ON s.id = m.student_id
    WHERE s.class_id = ?
    GROUP BY s.id
    ORDER BY avg_png DESC
');
$stmt->bind_param('i', $student['class_id']);
$stmt->execute();
$classRanks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($classRanks as $index => $row) {
    if ($row['id'] == $student_id) {
        $rank = $index + 1;
        break;
    }
}

// Get exam type list for filters
$examTypeList = [];
foreach ($marks as $mark) {
    if (! empty($mark['exam_type_name']) && ! in_array($mark['exam_type_name'], $examTypeList)) {
        $examTypeList[] = $mark['exam_type_name'];
    }
}

// Process AJAX requests for profile update and password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Tindakan tidak sah'];

    // Get student's subjects
    if ($action === 'get_student_subjects') {
        $student_id = $user['profile_id'];
        $subjects = getSubjectsForStudent($student_id);
        $response['success'] = true;
        $response['data'] = $subjects;
        echo json_encode($response);
        exit;
    }

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

        $stmt = $conn->prepare('UPDATE students SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('ssi', $name, $phone, $student_id);
        $stmt->execute();

        $stmt = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->bind_param('si', $email, $user['id']);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = 'Profil berjaya dikemaskini';
        logActivity($user['id'], 'UPDATE_PROFILE', 'Mengemaskini profil pelajar');

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
            --primary: #3b82f6;
            --primary-light: #60a5fa;
            --primary-dark: #1d4ed8;
            --accent: #06b6d4;
            --accent-glow: rgba(6, 182, 212, 0.25);
            --bg-dark: #090d16;
            --bg-card: rgba(17, 24, 39, 0.75);
            --bg-card-hover: rgba(24, 34, 53, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glass-focus: rgba(59, 130, 246, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-subtle: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-glass: 0 12px 32px -4px rgba(0, 0, 0, 0.5), 0 4px 12px -2px rgba(0, 0, 0, 0.3);
            --gradient-primary: linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #06b6d4 100%);
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
        .bg-orb-1 { width: 550px; height: 550px; background: radial-gradient(circle, #2563eb 0%, transparent 70%); top: -120px; left: -100px; }
        .bg-orb-2 { width: 500px; height: 500px; background: radial-gradient(circle, #4f46e5 0%, transparent 70%); top: 40%; right: -150px; }
        .bg-orb-3 { width: 450px; height: 450px; background: radial-gradient(circle, #06b6d4 0%, transparent 70%); bottom: -100px; left: 25%; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 1; }

        /* Header Modernization */
        header {
            background: rgba(9, 13, 22, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-glass);
            padding: 0.9rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
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
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }
        
        .nav-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 6px;
            background: rgba(255, 255, 255, 0.04);
            padding: 5px 8px;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            flex-wrap: wrap;
            margin: 0;
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
            background: rgba(255, 255, 255, 0.08);
        }
        
        nav a.active {
            color: #ffffff;
            background: var(--gradient-primary);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            padding: 5px 16px 5px 6px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        
        .user-info:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(59, 130, 246, 0.4);
            transform: translateY(-1px);
        }
        
        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 0.85rem;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.4);
        }
        
        .user-details {
            line-height: 1.3;
        }
        
        .user-details .user-name {
            font-weight: 700;
            font-size: 0.85rem;
            color: #ffffff;
        }
        
        .user-details .user-role {
            font-size: 0.7rem;
            color: var(--text-muted);
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
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 20px 40px -10px rgba(37, 99, 235, 0.25);
            background: var(--bg-card-hover);
        }
        .card:hover::before { opacity: 1; height: 4px; }
        
        .card-title { font-size: 0.78rem; margin-bottom: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
        .card-value { 
            font-size: 2.2rem; 
            font-weight: 800; 
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ffffff 30%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-icon { 
            position: absolute; 
            top: 1.4rem; right: 1.4rem; 
            font-size: 2.4rem; 
            color: #3b82f6; 
            opacity: 0.2; 
            transition: all 0.3s ease;
        }
        .card:hover .card-icon { opacity: 0.4; transform: scale(1.1) rotate(5deg); color: #06b6d4; }

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

        /* Stats Bottom Row */
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
            background: linear-gradient(135deg, #ffffff, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; font-weight: 600; text-transform: uppercase; }

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
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; }
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
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            background: rgba(15, 23, 42, 0.9);
        }
        .form-control option {
            background: #0f172a;
            color: #ffffff;
        }
        .form-control[readonly] {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            cursor: not-allowed;
        }

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
        table { width: 100%; border-collapse: collapse; min-width: 720px; }
        th, td { padding: 1rem 1.2rem; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.85rem; }
        th { 
            background: rgba(255, 255, 255, 0.03); 
            color: #94a3b8; 
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
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
            text-decoration: none;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.45);
            filter: brightness(1.1);
        }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; border-radius: 8px; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3); color: #ffffff; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3); }
        .btn-outline { background: transparent; border: 1.5px solid rgba(255, 255, 255, 0.15); color: #ffffff; box-shadow: none; }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.08); border-color: #3b82f6; }

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
        .badge-good { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-average { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-poor { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        /* Result Slip Styling */
        .result-slip { 
            background: var(--bg-card); 
            backdrop-filter: blur(24px);
            border-radius: 24px; 
            padding: 2.5rem; 
            max-width: 860px; 
            margin: 0 auto; 
            border: 1px solid rgba(59, 130, 246, 0.3); 
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
        .result-table th { background: rgba(37, 99, 235, 0.2); color: #93c5fd; font-weight: 700; }
        .result-table tfoot td { background: rgba(255, 255, 255, 0.02); font-weight: 800; font-size: 1rem; color: #ffffff; }

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
            background: #0f172a; 
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px; 
            width: 90%; 
            max-width: 500px; 
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

        /* Toast Notification */
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
            background: rgba(9, 13, 22, 0.8); 
            border-top: 1px solid var(--border-glass); 
            color: var(--text-muted); 
            padding: 1.4rem 0; 
            text-align: center; 
            margin-top: 3rem; 
            font-size: 0.8rem;
        }
        
        .subjects-table { width: 100%; font-size: 0.85rem; border-collapse: collapse; }
        .subjects-table th, .subjects-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .subjects-table th { background: rgba(255, 255, 255, 0.03); color: var(--text-muted); font-weight: 700; font-size: 0.78rem; text-transform: uppercase; }

        @media (max-width: 992px) {
            .header-content { flex-direction: column; align-items: stretch; }
            .nav-wrapper { flex-direction: column; width: 100%; }
            nav ul { justify-content: center; width: 100%; }
            .user-info { justify-content: center; align-self: center; }
        }
        
        @media (max-width: 768px) {
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
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span>Sistem e-Exam STPM</span>
            </div>
            
            <div class="nav-wrapper">
                <nav>
                    <ul>
                        <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="#" class="nav-link" data-section="results"><i class="fas fa-chart-bar"></i> Keputusan</a></li>
                        <li><a href="#" class="nav-link" data-section="slip"><i class="fas fa-file-alt"></i> Slip</a></li>
                        <li><a href="#" class="nav-link" data-section="profile"><i class="fas fa-user"></i> Profil</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Keluar</a></li>
                    </ul>
                </nav>
                
                <div class="user-info" onclick="showSection('profile')">
                    <div class="avatar"><?php echo strtoupper(substr($student['full_name'], 0, 2)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                        <div class="user-role">Pelajar STPM</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container main-content">
        <!-- Dashboard Section -->
        <section id="dashboard" class="page-section active">
            <h1 class="page-title">Dashboard Pelajar</h1>
            
            <div class="dashboard-cards">
                <div class="card" onclick="showSection('results')">
                    <div class="card-title">PNG Keseluruhan</div>
                    <div class="card-value"><?php echo $overallPng; ?></div>
                    <i class="fas fa-star card-icon"></i>
                </div>
                <div class="card" onclick="showSection('results')">
                    <div class="card-title">Kedudukan Kelas</div>
                    <div class="card-value"><?php echo $rank; ?>/<?php echo count($classRanks); ?></div>
                    <i class="fas fa-trophy card-icon"></i>
                </div>
                <div class="card" onclick="showSection('profile')">
                    <div class="card-title">Subjek Diambil</div>
                    <div class="card-value"><?php echo count(array_unique(array_column($marks, 'subject_name'))); ?></div>
                    <i class="fas fa-book card-icon"></i>
                </div>
                <div class="card">
                    <div class="card-title">Semester</div>
                    <div class="card-value"><?php echo count($semesterData); ?></div>
                    <i class="fas fa-calendar-alt card-icon"></i>
                </div>
            </div>

            <div class="chart-grid">
                <div class="chart-container">
                    <h3 class="chart-title">Prestasi Mengikut Subjek</h3>
                    <canvas id="subjectChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">Taburan Gred</h3>
                    <canvas id="gradeChart"></canvas>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $overallPng; ?></div>
                    <div class="stat-label">PNG Keseluruhan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php
                        $totalMarks = count($marks);
$passedMarks = count(array_filter($marks, function ($m) {
    return $m['marks'] >= 50;
}));
echo $totalMarks > 0 ? round(($passedMarks / $totalMarks) * 100, 1).'%' : '0%';
?></div>
                    <div class="stat-label">Kadar Kelulusan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($marks); ?></div>
                    <div class="stat-label">Jumlah Rekod Markah</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $rank; ?></div>
                    <div class="stat-label">Kedudukan Kelas</div>
                </div>
            </div>
        </section>

        <!-- Results Section -->
        <section id="results" class="page-section">
            <h1 class="page-title">Keputusan Peperiksaan</h1>
            
            <div class="filter-section">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Pilih Semester</label>
                        <select id="filterSemester" class="form-control" onchange="filterResults()">
                            <option value="all">Semua Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Jenis Peperiksaan</label>
                        <select id="filterExamType" class="form-control" onchange="filterResults()">
                            <option value="all">Semua Peperiksaan</option>
                            <?php foreach ($examTypeList as $et) { ?>
                            <option value="<?php echo htmlspecialchars($et); ?>"><?php echo htmlspecialchars($et); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Jenis Peperiksaan</th>
                            <th>Semester</th>
                            <th>Subjek</th>
                            <th>Kod</th>
                            <th>Markah</th>
                            <th>Gred</th>
                            <th>Pointer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody">
                        <?php foreach ($marks as $mark) { ?>
                        <tr class="result-row" data-semester="<?php echo $mark['semester']; ?>" data-exam-type="<?php echo htmlspecialchars($mark['exam_type_name'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($mark['exam_type_name'] ?? ($mark['exam_name'] ?? '-')); ?> <?php echo $mark['exam_name'] ? '('.htmlspecialchars($mark['exam_name']).')' : ''; ?></td>
                            <td><?php echo $mark['semester']; ?></td>
                            <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($mark['subject_code']); ?></td>
                            <td><?php echo $mark['marks']; ?></td>
                            <td><span class="badge <?php echo $mark['pointer'] >= 3.67 ? 'badge-excellent' : ($mark['pointer'] >= 2.00 ? 'badge-good' : 'badge-poor'); ?>"><?php echo $mark['grade']; ?></span></td>
                            <td><?php echo $mark['pointer']; ?></td>
                            <td><?php echo $mark['pointer'] >= 2.00 ? '<span class="badge badge-excellent">Lulus</span>' : '<span class="badge badge-poor">Gagal</span>'; ?></td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($marks)) { ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">Tiada rekod keputusan</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="chart-container">
                <h3 class="chart-title">PNG Mengikut Semester</h3>
                <canvas id="semesterChart"></canvas>
            </div>
        </section>

        <!-- Slip Keputusan Section -->
        <section id="slip" class="page-section">
            <h1 class="page-title">Slip Keputusan Peperiksaan</h1>
            
            <div class="filter-section">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Pilih Semester</label>
                        <select id="slipSemesterSelect" class="form-control" onchange="generateSlip()">
                            <?php foreach (array_keys($semesterData) as $sem) { ?>
                            <option value="<?php echo $sem; ?>">Semester <?php echo $sem; ?> (PNG: <?php echo $semesterPng[$sem]; ?>)</option>
                            <?php } ?>
                            <option value="all">Keseluruhan (Semua Semester)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Jenis Peperiksaan</label>
                        <select id="slipExamTypeSelect" class="form-control" onchange="generateSlip()">
                            <option value="all">Semua Peperiksaan</option>
                            <?php foreach ($examTypeList as $et) { ?>
                            <option value="<?php echo htmlspecialchars($et); ?>"><?php echo htmlspecialchars($et); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <div id="resultSlipContainer">
                <div class="result-slip" id="resultSlip">
                    <div class="result-header">
                        <h2>SLIP KEPUTUSAN PEPERIKSAAN STPM</h2>
                        <p id="slipSubtitle">SESI 2024/2025</p>
                    </div>
                    <div class="result-details">
                        <div>
                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                            <p><strong>No. Matrik:</strong> <?php echo htmlspecialchars($student['matric_no']); ?></p>
                            <p><strong>No. KP:</strong> <?php echo htmlspecialchars($student['ic_number'] ?? '-'); ?></p>
                        </div>
                        <div>
                            <p><strong>Kelas:</strong> <?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></p>
                            <p><strong>Pusat:</strong> SMK Taman Perdana</p>
                            <p><strong>Tarikh:</strong> <span id="slipDate"><?php echo date('d/m/Y'); ?></span></p>
                        </div>
                    </div>
                    <table class="result-table">
                        <thead>
                            <tr>
                                <th>Jenis Peperiksaan</th>
                                <th>Semester</th>
                                <th>Kod</th>
                                <th>Subjek</th>
                                <th>Markah</th>
                                <th>Gred</th>
                                <th>Pointer</th>
                            </tr>
                        </thead>
                        <tbody id="slipTableBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6"><strong>PNG:</strong></td>
                                <td><strong id="slipGPA">-</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="print-btn" style="display: flex; gap: 12px; justify-content: center; margin-top: 20px;">
                        <button class="btn btn-warning" onclick="printSlip()"><i class="fas fa-print"></i> Cetak Slip</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="page-section">
            <h1 class="page-title">Profil Saya</h1>
            <div style="max-width: 700px; margin: 0 auto;">
                <div class="filter-section">
                    <div style="text-align: center;">
                        <div class="avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 1rem;"><?php echo strtoupper(substr($student['full_name'], 0, 2)); ?></div>
                        <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                        <p><span class="badge badge-good">Pelajar STPM - <?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></span></p>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <div class="form-group"><label>No. Matrik</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($student['matric_no']); ?>" readonly></div>
                    <div class="form-group"><label>Nama Penuh</label><input type="text" id="profileName" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>"></div>
                    <div class="form-group"><label>No. Kad Pengenalan</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($student['ic_number'] ?? '-'); ?>" readonly></div>
                    <div class="form-group"><label>Kelas</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($student['class_name'] ?? '-'); ?>" readonly></div>
                    <div class="form-group"><label>Emel</label><input type="email" id="profileEmail" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>"></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="profilePhone" class="form-control" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Alamat</label><textarea class="form-control" rows="2" readonly><?php echo htmlspecialchars($student['address'] ?? '-'); ?></textarea></div>
                    
                    <!-- Subjek yang Diambil Section -->
                    <div class="form-group">
                        <label><i class="fas fa-book"></i> Subjek yang Diambil</label>
                        <div class="table-container" style="margin-top: 10px;">
                            <table class="subjects-table">
                                <thead>
                                    <tr><th>Semester</th><th>Kod</th><th>Nama Subjek</th><th>Guru</th></tr>
                                </thead>
                                <tbody id="studentSubjectsList">
                                    <tr><td colspan="4" style="text-align:center;">Memuatkan...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
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
            <p>&copy; 2025 Sistem Peperiksaan STPM | Hak Cipta Terpelihara</p>
        </div>
    </footer>

    <script>
        // Data from PHP
        const subjectNames = <?php echo json_encode(array_keys($subjectAvg)); ?>;
        const subjectAverages = <?php echo json_encode(array_values($subjectAvg)); ?>;
        const gradeDist = <?php echo json_encode(array_values($gradeDist)); ?>;
        const semesterLabels = <?php echo json_encode(array_keys($semesterPng)); ?>;
        const semesterValues = <?php echo json_encode(array_values($semesterPng)); ?>;
        const marksData = <?php echo json_encode($marks); ?>;

        let charts = {};

        function showSection(sectionId) {
            document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
            const target = document.getElementById(sectionId);
            if (target) target.classList.add('active');
            
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            let activeLink = Array.from(document.querySelectorAll('.nav-link')).find(l => l.dataset.section === sectionId);
            if(activeLink) activeLink.classList.add('active');
            
            if (sectionId === 'slip') generateSlip();
            if (sectionId === 'profile') loadStudentSubjects();
        }

        function filterResults() {
            const semesterFilter = document.getElementById('filterSemester').value;
            const examTypeFilter = document.getElementById('filterExamType').value;
            const rows = document.querySelectorAll('#resultsTableBody tr.result-row');
            
            rows.forEach(row => {
                const semester = row.getAttribute('data-semester');
                const examType = row.getAttribute('data-exam-type');
                let show = true;
                
                if (semesterFilter !== 'all' && semester !== semesterFilter) show = false;
                if (examTypeFilter !== 'all' && examType !== examTypeFilter) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        function generateSlip() {
            const semester = document.getElementById('slipSemesterSelect').value;
            const examTypeFilter = document.getElementById('slipExamTypeSelect').value;
            let filteredMarks = [];
            
            if (semester === 'all') {
                filteredMarks = marksData;
                document.getElementById('slipSubtitle').textContent = 'LAPORAN KESELURUHAN - SESI 2024/2025';
            } else {
                filteredMarks = marksData.filter(m => m.semester == semester);
                document.getElementById('slipSubtitle').textContent = 'SEMESTER ' + semester + ' - SESI 2024/2025';
            }
            
            if (examTypeFilter !== 'all') {
                filteredMarks = filteredMarks.filter(m => m.exam_type_name == examTypeFilter);
                if (semester !== 'all') {
                    document.getElementById('slipSubtitle').textContent = examTypeFilter + ' - SEMESTER ' + semester + ' - SESI 2024/2025';
                } else {
                    document.getElementById('slipSubtitle').textContent = examTypeFilter + ' - LAPORAN KESELURUHAN - SESI 2024/2025';
                }
            }
            
            const tbody = document.getElementById('slipTableBody');
            tbody.innerHTML = '';
            
            if (filteredMarks.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Tiada rekod markah</td></tr>';
                document.getElementById('slipGPA').textContent = '-';
            } else {
                let totalPointer = 0;
                filteredMarks.forEach(mark => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${escapeHtml(mark.exam_type_name || (mark.exam_name || '-'))}${mark.exam_name ? ' (' + escapeHtml(mark.exam_name) + ')' : ''}</td>
                        <td>${mark.semester}</td>
                        <td>${escapeHtml(mark.subject_code)}</td>
                        <td>${escapeHtml(mark.subject_name)}</td>
                        <td>${mark.marks}</td>
                        <td>${mark.grade}</td>
                        <td>${mark.pointer}</td>
                    `;
                    tbody.appendChild(row);
                    totalPointer += parseFloat(mark.pointer);
                });
                const gpa = (totalPointer / filteredMarks.length).toFixed(2);
                document.getElementById('slipGPA').textContent = gpa;
            }
            
            document.getElementById('slipDate').textContent = new Date().toLocaleDateString('ms-MY');
        }

        function printSlip() {
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
                        backgroundColor: 'rgba(59, 130, 246, 0.65)',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: { 
                    responsive: true, 
                    plugins: { legend: { labels: { color: '#cbd5e1', font: { weight: 'bold' } } } },
                    scales: { 
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    } 
                }
            });

            const gradeCtx = document.getElementById('gradeChart').getContext('2d');
            charts.grade = new Chart(gradeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['A', 'A-', 'B+', 'B', 'C+', 'C', 'D'],
                    datasets: [{ 
                        data: gradeDist, 
                        backgroundColor: ['#10b981', '#34d399', '#6ee7b7', '#f59e0b', '#fbbf24', '#fcd34d', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#0f172a'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#cbd5e1', boxWidth: 12, padding: 15 } }
                    }
                }
            });

            const semesterCtx = document.getElementById('semesterChart').getContext('2d');
            if (semesterLabels.length > 0) {
                charts.semester = new Chart(semesterCtx, {
                    type: 'line',
                    data: {
                        labels: semesterLabels.map(l => 'Semester ' + l),
                        datasets: [{
                            label: 'PNG',
                            data: semesterValues,
                            borderColor: '#38bdf8',
                            backgroundColor: 'rgba(56, 189, 248, 0.15)',
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#0284c7',
                            pointBorderColor: '#ffffff',
                            pointRadius: 5
                        }]
                    },
                    options: { 
                        responsive: true, 
                        plugins: { legend: { labels: { color: '#cbd5e1' } } },
                        scales: { 
                            y: { beginAtZero: true, max: 4, grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#94a3b8' } },
                            x: { grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#94a3b8' } }
                        } 
                    }
                });
            }
        }

        // Load subjects for this student
        function loadStudentSubjects() {
            const formData = new FormData();
            formData.append('action', 'get_student_subjects');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const tbody = document.getElementById('studentSubjectsList');
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Tiada subjek yang diambil</td></tr>';
                        return;
                    }
                    
                    let html = '';
                    data.data.forEach(subj => {
                        html += `
                            <tr>
                                <td>Semester ${subj.semester}</td>
                                <td>${escapeHtml(subj.subject_code)}</td>
                                <td>${escapeHtml(subj.subject_name)}</td>
                                <td>${escapeHtml(subj.teacher_name || '-')}</td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
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
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
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
            generateSlip();
            loadStudentSubjects();
            document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); }));
        });
        
        window.onclick = e => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
    </script>
</body>
</html>