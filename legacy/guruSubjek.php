<?php
// ======================================================
// FAIL PANEL GURU SUBJEK (BOLEH EDIT MARKAH & SUBJEK)
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';
checkLogin();
checkRole(['guru']);

$user = getCurrentUser();

// Check if user is guru subjek
if ($user['sub_role'] !== 'guru_subjek') {
    header('Location: unauthorized.php');
    exit();
}

// ======================================================
// PROSES AJAX REQUEST
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Tindakan tidak sah'];

    // Save or update mark
    if ($action === 'save_mark') {
        $student_id = validateAndSanitize($_POST['student_id'] ?? 0, 'int');
        $subject_id = validateAndSanitize($_POST['subject_id'] ?? 0, 'int');
        $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
        $semester = validateAndSanitize($_POST['semester'] ?? 1, 'int');
        $exam_id = validateAndSanitize($_POST['exam_id'] ?? 0, 'int');
        $marks = validateAndSanitize($_POST['marks'] ?? '', 'float');

        if ($student_id <= 0 || $subject_id <= 0 || $class_id <= 0) {
            $response['message'] = 'Data tidak lengkap';
            echo json_encode($response);
            exit;
        }

        // Calculate grade and pointer based on marks
        if ($marks >= 80) {
            $grade = 'A';
            $pointer = 4.00;
        } elseif ($marks >= 75) {
            $grade = 'A-';
            $pointer = 3.67;
        } elseif ($marks >= 70) {
            $grade = 'B+';
            $pointer = 3.33;
        } elseif ($marks >= 65) {
            $grade = 'B';
            $pointer = 3.00;
        } elseif ($marks >= 60) {
            $grade = 'C+';
            $pointer = 2.67;
        } elseif ($marks >= 50) {
            $grade = 'C';
            $pointer = 2.00;
        } else {
            $grade = 'D';
            $pointer = 0.00;
        }

        // Check if mark already exists
        $check = $conn->prepare('SELECT id FROM marks WHERE student_id = ? AND subject_id = ? AND class_id = ? AND semester = ? AND exam_id = ?');
        $check->bind_param('iiiii', $student_id, $subject_id, $class_id, $semester, $exam_id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            // Update existing mark
            $stmt = $conn->prepare('UPDATE marks SET marks = ?, grade = ?, pointer = ?, updated_at = NOW() WHERE student_id = ? AND subject_id = ? AND class_id = ? AND semester = ? AND exam_id = ?');
            $stmt->bind_param('dssiiiii', $marks, $grade, $pointer, $student_id, $subject_id, $class_id, $semester, $exam_id);
        } else {
            // Insert new mark
            $stmt = $conn->prepare('INSERT INTO marks (student_id, subject_id, class_id, semester, exam_id, marks, grade, pointer, entered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iiiiissii', $student_id, $subject_id, $class_id, $semester, $exam_id, $marks, $grade, $pointer, $user['profile_id']);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Markah berjaya disimpan';
            logActivity($user['id'], 'SAVE_MARK', "Menyimpan markah untuk pelajar ID: $student_id, subjek ID: $subject_id, exam ID: $exam_id");
        } else {
            $response['message'] = 'Gagal menyimpan markah: '.$conn->error;
        }

        echo json_encode($response);
        exit;
    }

    // Get teacher's subjects
    if ($action === 'get_teacher_subjects') {
        $teacher_id = $user['profile_id'];
        $subjects = getSubjectsByTeacherId($teacher_id);
        $response['success'] = true;
        $response['data'] = $subjects;
        echo json_encode($response);
        exit;
    }

    // Change password
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

    // Update profile
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

// ======================================================
// AMBIL DATA DARI DATABASE (Hanya untuk subjek dan kelas yang diajar)
// ======================================================

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

// Get classes taught by this teacher from class_subjects (only classes where teacher teaches)
$stmt = $conn->prepare("
    SELECT DISTINCT c.*, 
        COUNT(DISTINCT s.id) as student_count
    FROM class_subjects cs
    JOIN classes c ON cs.class_id = c.id
    LEFT JOIN students s ON c.id = s.class_id AND s.status = 'Aktif'
    WHERE cs.teacher_id = ? AND c.status = 'Aktif'
    GROUP BY c.id
");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get subjects taught by this teacher from class_subjects (only subjects teacher teaches)
$stmt = $conn->prepare("
    SELECT DISTINCT s.*, cs.class_id, cs.semester, c.class_name
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    WHERE cs.teacher_id = ? AND s.status = 'Aktif'
    ORDER BY cs.semester, s.subject_name
");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all subjects taught by this teacher (for display in profile)
$teacherSubjects = getSubjectsByTeacherId($teacher_id);

// Get exam types for the dropdown
$examTypes = [];
$result = $conn->query('SELECT id, exam_name, exam_code FROM exam_types WHERE is_active = 1 ORDER BY exam_order');
while ($row = $result->fetch_assoc()) {
    $examTypes[] = $row;
}

// Get exams for the selected subject/class
$exams = [];
$result = $conn->query("
    SELECT e.*, et.exam_name, et.exam_code 
    FROM exams e
    JOIN exam_types et ON e.exam_type_id = et.id
    WHERE e.status IN ('Aktif', 'Draf')
    ORDER BY e.exam_date DESC
");
while ($row = $result->fetch_assoc()) {
    $exams[] = $row;
}

// Get all students from classes taught by this teacher
$students = [];
if (! empty($classes)) {
    $class_ids = array_column($classes, 'id');
    $placeholders = implode(',', array_fill(0, count($class_ids), '?'));
    $types = str_repeat('i', count($class_ids));

    $stmt = $conn->prepare("
        SELECT s.*, c.class_name, u.email
        FROM students s
        JOIN classes c ON s.class_id = c.id
        JOIN users u ON s.user_id = u.id
        WHERE s.class_id IN ($placeholders) AND s.status = 'Aktif'
        ORDER BY c.class_name, s.full_name
    ");
    $stmt->bind_param($types, ...$class_ids);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get marks for subjects taught by this teacher
$marks = [];
if (! empty($students) && ! empty($subjects)) {
    $student_ids = array_column($students, 'id');
    $subject_ids = array_column($subjects, 'id');
    $student_placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    $subject_placeholders = implode(',', array_fill(0, count($subject_ids), '?'));

    $stmt = $conn->prepare("
        SELECT m.*, s.subject_name, s.subject_code, st.full_name as student_name, st.matric_no, c.class_name,
            e.exam_name as exam_name, et.exam_code, et.exam_name as exam_type_name
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        JOIN students st ON m.student_id = st.id
        JOIN classes c ON m.class_id = c.id
        LEFT JOIN exams e ON m.exam_id = e.id
        LEFT JOIN exam_types et ON e.exam_type_id = et.id
        WHERE m.student_id IN ($student_placeholders) AND m.subject_id IN ($subject_placeholders)
        ORDER BY st.full_name, m.semester, m.exam_id
    ");
    $types = str_repeat('i', count($student_ids)).str_repeat('i', count($subject_ids));
    $params = array_merge($student_ids, $subject_ids);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $marks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Create marks lookup array for easy access
$marksLookup = [];
foreach ($marks as $mark) {
    $key = $mark['student_id'].'_'.$mark['subject_id'].'_'.$mark['semester'].'_'.$mark['exam_id'];
    $marksLookup[$key] = $mark;
}

// Calculate statistics
$totalStudents = count($students);
$totalMarksEntered = count($marks);
$avgMark = $totalMarksEntered > 0 ? round(array_sum(array_column($marks, 'marks')) / $totalMarksEntered, 1) : 0;
$passedMarks = count(array_filter($marks, function ($m) {
    return $m['marks'] >= 50;
}));
$passRate = $totalMarksEntered > 0 ? round(($passedMarks / $totalMarksEntered) * 100, 1) : 0;

// Grade distribution
$gradeDist = ['A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D' => 0];
foreach ($marks as $mark) {
    if (isset($gradeDist[$mark['grade']])) {
        $gradeDist[$mark['grade']]++;
    }
}

// Calculate pending marks
$pendingCount = 0;
foreach ($students as $student) {
    foreach ($subjects as $subject) {
        foreach ($exams as $exam) {
            $key = $student['id'].'_'.$subject['id'].'_'.$subject['semester'].'_'.$exam['id'];
            if (! isset($marksLookup[$key])) {
                $pendingCount++;
            }
        }
    }
}

// Prepare data for charts
$subjectNames = array_unique(array_column($subjects, 'subject_name'));
$subjectAvgs = [];
$uniqueSubjects = array_unique(array_column($subjects, 'id'));
foreach ($uniqueSubjects as $subId) {
    $subMarks = array_filter($marks, function ($m) use ($subId) {
        return $m['subject_id'] == $subId;
    });
    $subjectAvgs[] = ! empty($subMarks) ? round(array_sum(array_column($subMarks, 'marks')) / count($subMarks), 1) : 0;
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
            --primary: #7c3aed;
            --primary-light: #a78bfa;
            --primary-dark: #6d28d9;
            --accent: #ec4899;
            --accent-glow: rgba(236, 72, 153, 0.25);
            --bg-dark: #090915;
            --bg-card: rgba(20, 16, 38, 0.75);
            --bg-card-hover: rgba(29, 23, 56, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glass-focus: rgba(124, 58, 237, 0.5);
            --text-main: #f8fafc;
            --text-muted: #a1a1aa;
            --text-subtle: #71717a;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --shadow-glass: 0 12px 32px -4px rgba(0, 0, 0, 0.5), 0 4px 12px -2px rgba(0, 0, 0, 0.3);
            --gradient-primary: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #ec4899 100%);
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
        .bg-orb-1 { width: 550px; height: 550px; background: radial-gradient(circle, #7c3aed 0%, transparent 70%); top: -120px; left: -100px; }
        .bg-orb-2 { width: 500px; height: 500px; background: radial-gradient(circle, #ec4899 0%, transparent 70%); top: 40%; right: -150px; }
        .bg-orb-3 { width: 450px; height: 450px; background: radial-gradient(circle, #6366f1 0%, transparent 70%); bottom: -100px; left: 25%; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 1; }

        /* Header Modernization */
        header {
            background: rgba(12, 10, 26, 0.82);
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
            background: linear-gradient(135deg, #7c3aed, #ec4899);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
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
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35); 
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
            border-color: rgba(167, 139, 250, 0.4);
            transform: translateY(-1px);
        }
        .avatar { 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #7c3aed, #ec4899); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 800; 
            color: white; 
            font-size: 0.85rem;
            box-shadow: 0 2px 10px rgba(124, 58, 237, 0.4);
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
            border-color: rgba(167, 139, 250, 0.4);
            box-shadow: 0 20px 40px -10px rgba(124, 58, 237, 0.25);
            background: var(--bg-card-hover);
        }
        .card:hover::before { opacity: 1; height: 4px; }
        
        .card-title { font-size: 0.78rem; margin-bottom: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
        .card-value { 
            font-size: 2.2rem; 
            font-weight: 800; 
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ffffff 30%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-icon { 
            position: absolute; 
            top: 1.4rem; right: 1.4rem; 
            font-size: 2.4rem; 
            color: #8b5cf6; 
            opacity: 0.2; 
            transition: all 0.3s ease;
        }
        .card:hover .card-icon { opacity: 0.4; transform: scale(1.1) rotate(5deg); color: #ec4899; }

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
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
            text-decoration: none;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.45);
            filter: brightness(1.1);
        }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; border-radius: 8px; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3); color: #ffffff; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3); }
        .btn-info { background: linear-gradient(135deg, #06b6d4, #0284c7); box-shadow: 0 4px 14px rgba(6, 182, 212, 0.3); }

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
            border-color: #a78bfa; 
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.25);
            background: rgba(15, 23, 42, 0.9);
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
            background: linear-gradient(135deg, #ffffff, #c4b5fd);
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
        .badge-good { background: rgba(124, 58, 237, 0.15); color: #c4b5fd; border: 1px solid rgba(124, 58, 237, 0.3); }
        .badge-average { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-poor { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-pending { background: rgba(6, 182, 212, 0.15); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.3); }
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
        .alert-success { background: rgba(16, 185, 129, 0.12); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.25); }
        .alert-info { background: rgba(124, 58, 237, 0.12); color: #c4b5fd; border: 1px solid rgba(124, 58, 237, 0.25); }

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
            background: #110d24; 
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
            background: rgba(12, 10, 26, 0.8); 
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
        }
        
        .subjects-table { width: 100%; font-size: 0.85rem; border-collapse: collapse; }
        .subjects-table th, .subjects-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .subjects-table th { background: rgba(255, 255, 255, 0.03); color: var(--text-muted); font-weight: 700; }
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
                <span>Sistem e-Exam STPM | Guru Subjek</span>
            </div>
            <nav>
                <ul>
                    <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#" class="nav-link" data-section="marks"><i class="fas fa-pen-fancy"></i> Isi Markah</a></li>
                    <li><a href="#" class="nav-link" data-section="analysis"><i class="fas fa-chart-line"></i> Analisis</a></li>
                    <li><a href="#" class="nav-link" data-section="profile"><i class="fas fa-user-circle"></i> Profil</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Keluar</a></li>
                </ul>
            </nav>
            <div class="user-info" onclick="showSection('profile')">
                <div class="avatar"><?php echo strtoupper(substr($teacher['full_name'] ?? 'GS', 0, 2)); ?></div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($teacher['full_name'] ?? 'Guru Subjek'); ?></div>
                    <div style="font-size: 0.7rem;">Guru Subjek</div>
                </div>
            </div>
        </div>
    </header>

    <div class="container main-content">
        <!-- Dashboard Section -->
        <section id="dashboard" class="page-section active">
            <h1 class="page-title">Dashboard Guru Subjek</h1>
            
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Selamat datang, <strong><?php echo htmlspecialchars($teacher['full_name'] ?? 'Guru'); ?></strong>! Anda boleh mengisi markah untuk subjek yang diajar.</span>
            </div>

            <div class="dashboard-cards">
                <div class="card" onclick="showSection('marks')">
                    <div class="card-title">Jumlah Pelajar</div>
                    <div class="card-value"><?php echo $totalStudents; ?></div>
                    <i class="fas fa-users card-icon"></i>
                </div>
                <div class="card" onclick="showSection('marks')">
                    <div class="card-title">Markah Belum Diisi</div>
                    <div class="card-value"><?php echo $pendingCount; ?></div>
                    <i class="fas fa-clock card-icon"></i>
                </div>
                <div class="card">
                    <div class="card-title">Markah Telah Diisi</div>
                    <div class="card-value"><?php echo $totalMarksEntered; ?></div>
                    <i class="fas fa-check-circle card-icon"></i>
                </div>
                <div class="card">
                    <div class="card-title">Purata Markah</div>
                    <div class="card-value"><?php echo $avgMark; ?>%</div>
                    <i class="fas fa-chart-line card-icon"></i>
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
                    <div class="stat-value"><?php echo $totalStudents; ?></div>
                    <div class="stat-label">Jumlah Pelajar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $passRate; ?>%</div>
                    <div class="stat-label">Kadar Kelulusan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($subjects); ?></div>
                    <div class="stat-label">Subjek Diajar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($classes); ?></div>
                    <div class="stat-label">Kelas Diajar</div>
                </div>
            </div>
        </section>

        <!-- Isi Markah Section -->
        <section id="marks" class="page-section">
            <h1 class="page-title">Isi Markah Pelajar</h1>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>Anda boleh mengisi markah untuk subjek yang diajar mengikut jenis peperiksaan. Markah akan dikira gred dan pointer secara automatik.</span>
            </div>

            <div class="filter-section">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Pilih Kelas</label>
                        <select id="marksClass" class="form-control" onchange="loadMarksTable()">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class) { ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?> (<?php echo $class['student_count']; ?> pelajar)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Subjek</label>
                        <select id="marksSubject" class="form-control" onchange="loadMarksTable()">
                            <option value="">Pilih Subjek</option>
                            <?php foreach ($subjects as $subject) { ?>
                            <option value="<?php echo $subject['id']; ?>" data-semester="<?php echo $subject['semester']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?> (Semester <?php echo $subject['semester']; ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Peperiksaan</label>
                        <select id="marksExam" class="form-control" onchange="loadMarksTable()">
                            <option value="">Pilih Peperiksaan</option>
                            <?php foreach ($exams as $exam) { ?>
                            <option value="<?php echo $exam['id']; ?>"><?php echo htmlspecialchars($exam['exam_name'].' ('.$exam['exam_code'].') - '.date('d/m/Y', strtotime($exam['exam_date']))); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select id="marksSemester" class="form-control" onchange="loadMarksTable()">
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table id="marksTable">
                    <thead>
                        <tr><th>No. Matrik</th><th>Nama Pelajar</th><th>Kelas</th><th>Jenis Peperiksaan</th><th>Markah</th><th>Gred</th><th>Pointer</th><th>Status</th><th>Tindakan</th></tr>
                    </thead>
                    <tbody id="marksTableBody">
                        <tr><td colspan="9" style="text-align:center;">Pilih kelas, subjek, dan peperiksaan untuk melihat senarai pelajar</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Analysis Section -->
        <section id="analysis" class="page-section">
            <h1 class="page-title">Analisis Prestasi</h1>
            
            <div class="filter-section">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Pilih Kelas</label>
                        <select id="analysisClass" class="form-control" onchange="updateAnalysis()">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($classes as $class) { ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Subjek</label>
                        <select id="analysisSubject" class="form-control" onchange="updateAnalysis()">
                            <?php foreach ($subjects as $subject) { ?>
                            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?> (Semester <?php echo $subject['semester']; ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Peperiksaan</label>
                        <select id="analysisExam" class="form-control" onchange="updateAnalysis()">
                            <option value="all">Semua Peperiksaan</option>
                            <?php foreach ($exams as $exam) { ?>
                            <option value="<?php echo $exam['id']; ?>"><?php echo htmlspecialchars($exam['exam_name'].' ('.$exam['exam_code'].')'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <h3 class="chart-title">Taburan Markah Pelajar</h3>
                <canvas id="distributionChart"></canvas>
            </div>

            <div class="table-container">
                <h3 style="padding: 1rem 1rem 0 1rem;">Senarai Markah Pelajar</h3>
                <table>
                    <thead>
                        <tr><th>No. Matrik</th><th>Nama Pelajar</th><th>Kelas</th><th>Jenis Peperiksaan</th><th>Markah</th><th>Gred</th><th>Pointer</th><th>Status</th></tr>
                    </thead>
                    <tbody id="analysisTableBody"></tbody>
                </table>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="page-section">
            <h1 class="page-title">Profil Guru Subjek</h1>
            <div style="max-width: 700px; margin: 0 auto;">
                <div class="filter-section">
                    <div style="text-align: center;">
                        <div class="avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 1rem;"><?php echo strtoupper(substr($teacher['full_name'] ?? 'GS', 0, 2)); ?></div>
                        <h2><?php echo htmlspecialchars($teacher['full_name'] ?? 'Guru Subjek'); ?></h2>
                        <p><span class="badge badge-info">Guru Subjek</span></p>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <div class="form-group"><label>ID Guru</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher['teacher_id'] ?? '-'); ?>" readonly></div>
                    <div class="form-group"><label>Nama Penuh</label><input type="text" id="profileName" class="form-control" value="<?php echo htmlspecialchars($teacher['full_name'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Emel</label><input type="email" id="profileEmail" class="form-control" value="<?php echo htmlspecialchars($teacher['email'] ?? ''); ?>"></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="profilePhone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>"></div>
                    
                    <!-- Subjek Diajar Section -->
                    <div class="form-group">
                        <label><i class="fas fa-book-open"></i> Subjek & Kelas Diajar</label>
                        <div class="table-container" style="margin-top: 10px;">
                            <table class="subjects-table">
                                <thead>
                                    <tr><th>Kelas</th><th>Semester</th><th>Kod</th><th>Nama Subjek</th></tr>
                                </thead>
                                <tbody id="teacherSubjectsList">
                                    <tr><td colspan="4" style="text-align:center;">Memuatkan...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <button class="btn btn-warning" style="width:100%; margin-top: 1rem;" onclick="openChangePasswordModal()">
                        <i class="fas fa-key"></i> Tukar Kata Laluan
                    </button>
                </div>
            </div>
        </section>
    </div>

    <!-- Edit Mark Modal -->
    <div class="modal" id="editMarkModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Markah</h3><button class="close-modal" onclick="closeModal('editMarkModal')">&times;</button></div>
            <div class="modal-body">
                <form id="editMarkForm" onsubmit="saveMark(event)">
                    <input type="hidden" id="editStudentId">
                    <input type="hidden" id="editSubjectId">
                    <input type="hidden" id="editClassId">
                    <input type="hidden" id="editSemester">
                    <input type="hidden" id="editExamId">
                    <div class="form-group"><label>Nama Pelajar</label><input type="text" id="editStudentName" class="form-control" readonly></div>
                    <div class="form-group"><label>Subjek</label><input type="text" id="editSubjectName" class="form-control" readonly></div>
                    <div class="form-group"><label>Jenis Peperiksaan</label><input type="text" id="editExamName" class="form-control" readonly></div>
                    <div class="form-group"><label>Markah (0-100)</label><input type="number" id="editMarks" class="form-control" min="0" max="100" step="0.5" required></div>
                    <div class="form-group"><label>Gred</label><input type="text" id="editGrade" class="form-control" readonly></div>
                    <div class="form-group"><label>Pointer</label><input type="text" id="editPointer" class="form-control" readonly></div>
                    <button type="submit" class="btn btn-success" style="width:100%"><i class="fas fa-save"></i> Simpan</button>
                </form>
            </div>
        </div>
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
            <p>&copy; 2025 Sistem Peperiksaan STPM | Guru Subjek Panel</p>
        </div>
    </footer>

    <script>
        const studentsData = <?php echo json_encode($students); ?>;
        const subjectsData = <?php echo json_encode($subjects); ?>;
        const classesData = <?php echo json_encode($classes); ?>;
        const examsData = <?php echo json_encode($exams); ?>;
        const marksLookup = <?php echo json_encode($marksLookup); ?>;
        const gradeDist = <?php echo json_encode(array_values($gradeDist)); ?>;
        const subjectNames = <?php echo json_encode(! empty($subjectNames) ? array_values($subjectNames) : ['Tiada Data']); ?>;
        const subjectAvgs = <?php echo json_encode(! empty($subjectAvgs) ? $subjectAvgs : [0]); ?>;

        let charts = {};

        function showSection(sectionId) {
            document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            let activeLink = Array.from(document.querySelectorAll('.nav-link')).find(l => l.dataset.section === sectionId);
            if(activeLink) activeLink.classList.add('active');
            
            if(sectionId === 'analysis') updateAnalysis();
            if(sectionId === 'profile') loadTeacherSubjects();
        }

        function loadMarksTable() {
            const classId = document.getElementById('marksClass').value;
            const subjectId = document.getElementById('marksSubject').value;
            const examId = document.getElementById('marksExam').value;
            const semester = document.getElementById('marksSemester').value;
            
            if (!classId || !subjectId || !examId) {
                document.getElementById('marksTableBody').innerHTML = '<tr><td colspan="9" style="text-align:center;">Sila pilih kelas, subjek, dan peperiksaan</td></tr>';
                return;
            }
            
            const classInfo = classesData.find(c => c.id == classId);
            const subjectInfo = subjectsData.find(s => s.id == subjectId);
            const examInfo = examsData.find(e => e.id == examId);
            
            if (!classInfo || !subjectInfo || !examInfo) return;
            
            const classStudents = studentsData.filter(s => s.class_id == classId);
            
            let tableHtml = '';
            for (const student of classStudents) {
                const key = student.id + '_' + subjectId + '_' + semester + '_' + examId;
                const existingMark = marksLookup[key];
                const marks = existingMark ? existingMark.marks : '';
                const grade = existingMark ? existingMark.grade : '-';
                const pointer = existingMark ? existingMark.pointer : '-';
                const status = existingMark ? (existingMark.marks >= 50 ? '<span class="badge badge-excellent">Lulus</span>' : '<span class="badge badge-poor">Gagal</span>') : '<span class="badge badge-pending">Belum Diisi</span>';
                const examType = examInfo.exam_name + ' (' + examInfo.exam_code + ')';
                
                tableHtml += `
                    <tr>
                        <td>${escapeHtml(student.matric_no)}</td>
                        <td>${escapeHtml(student.full_name)}</td>
                        <td>${escapeHtml(classInfo.class_name)}</td>
                        <td>${escapeHtml(examType)}</td>
                        <td>${marks || '-'}</td>
                        <td>${grade}</td>
                        <td>${pointer}</td>
                        <td>${status}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" 
                                data-student-id="${student.id}"
                                data-subject-id="${subjectId}"
                                data-class-id="${classId}"
                                data-semester="${semester}"
                                data-exam-id="${examId}"
                                data-student-name="${escapeHtml(student.full_name)}"
                                data-subject-name="${escapeHtml(subjectInfo.subject_name)}"
                                data-exam-name="${escapeHtml(examType)}"
                                data-marks="${marks !== null && marks !== undefined ? marks : ''}"
                                onclick="handleEditMarkClick(this)">
                                <i class="fas fa-edit"></i> ${marks !== null && marks !== undefined && marks !== '' ? 'Edit' : 'Isi'}
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            document.getElementById('marksTableBody').innerHTML = tableHtml || '<tr><td colspan="9" style="text-align:center;">Tiada pelajar dalam kelas ini</td></tr>';
        }

        function handleEditMarkClick(btn) {
            const d = btn.dataset;
            openEditMarkModal(
                parseInt(d.studentId),
                parseInt(d.subjectId),
                parseInt(d.classId),
                parseInt(d.semester),
                parseInt(d.examId),
                d.studentName,
                d.subjectName,
                d.examName,
                d.marks !== '' ? parseFloat(d.marks) : ''
            );
        }

        function openEditMarkModal(studentId, subjectId, classId, semester, examId, studentName, subjectName, examName, currentMarks) {
            document.getElementById('editStudentId').value = studentId;
            document.getElementById('editSubjectId').value = subjectId;
            document.getElementById('editClassId').value = classId;
            document.getElementById('editSemester').value = semester;
            document.getElementById('editExamId').value = examId;
            document.getElementById('editStudentName').value = studentName;
            document.getElementById('editSubjectName').value = subjectName;
            document.getElementById('editExamName').value = examName;
            document.getElementById('editMarks').value = currentMarks || '';
            
            if (currentMarks) {
                updateGradeAndPointer(currentMarks);
            } else {
                document.getElementById('editGrade').value = '-';
                document.getElementById('editPointer').value = '-';
            }
            
            document.getElementById('editMarkModal').style.display = 'flex';
        }

        function updateGradeAndPointer(marks) {
            marks = parseFloat(marks);
            let grade = '-';
            let pointer = '-';
            
            if (!isNaN(marks)) {
                if (marks >= 80) { grade = 'A'; pointer = 4.00; }
                else if (marks >= 75) { grade = 'A-'; pointer = 3.67; }
                else if (marks >= 70) { grade = 'B+'; pointer = 3.33; }
                else if (marks >= 65) { grade = 'B'; pointer = 3.00; }
                else if (marks >= 60) { grade = 'C+'; pointer = 2.67; }
                else if (marks >= 50) { grade = 'C'; pointer = 2.00; }
                else { grade = 'D'; pointer = 0.00; }
            }
            
            document.getElementById('editGrade').value = grade;
            document.getElementById('editPointer').value = pointer;
        }

        document.getElementById('editMarks')?.addEventListener('input', function(e) {
            updateGradeAndPointer(e.target.value);
        });

        function saveMark(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'save_mark');
            formData.append('student_id', document.getElementById('editStudentId').value);
            formData.append('subject_id', document.getElementById('editSubjectId').value);
            formData.append('class_id', document.getElementById('editClassId').value);
            formData.append('semester', document.getElementById('editSemester').value);
            formData.append('exam_id', document.getElementById('editExamId').value);
            formData.append('marks', document.getElementById('editMarks').value);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Markah berjaya disimpan!', 'success');
                    closeModal('editMarkModal');
                    loadMarksTable();
                    updateAnalysis();
                } else {
                    showNotification(data.message || 'Gagal menyimpan markah', 'error');
                }
            })
            .catch(error => showNotification('Ralat: ' + error, 'error'));
        }

        function updateAnalysis() {
            const classId = document.getElementById('analysisClass').value;
            const subjectId = document.getElementById('analysisSubject').value;
            const examId = document.getElementById('analysisExam').value;
            
            let filteredMarks = Object.values(marksLookup).filter(m => m.subject_id == subjectId);
            
            if (classId !== 'all') {
                filteredMarks = filteredMarks.filter(m => m.class_id == classId);
            }
            
            if (examId !== 'all') {
                filteredMarks = filteredMarks.filter(m => m.exam_id == examId);
            }
            
            const marksValues = filteredMarks.map(m => m.marks);
            const ranges = [0, 0, 0, 0, 0];
            
            marksValues.forEach(m => {
                if (m >= 80) ranges[0]++;
                else if (m >= 70) ranges[1]++;
                else if (m >= 60) ranges[2]++;
                else if (m >= 50) ranges[3]++;
                else ranges[4]++;
            });
            
            if (charts.distribution) charts.distribution.destroy();
            const distCtx = document.getElementById('distributionChart').getContext('2d');
            charts.distribution = new Chart(distCtx, {
                type: 'bar',
                data: {
                    labels: ['80-100', '70-79', '60-69', '50-59', '<50'],
                    datasets: [{ 
                        label: 'Bilangan Pelajar', 
                        data: ranges, 
                        backgroundColor: 'rgba(124, 58, 237, 0.7)', 
                        borderColor: '#a855f7',
                        borderWidth: 1,
                        borderRadius: 8 
                    }]
                },
                options: { 
                    responsive: true, 
                    scales: { 
                        y: { beginAtZero: true, stepSize: 1, grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#a1a1aa' } },
                        x: { grid: { display: false }, ticks: { color: '#a1a1aa' } }
                    },
                    plugins: { legend: { labels: { color: '#cbd5e1' } } }
                }
            });
            
            let tableHtml = '';
            for (const mark of filteredMarks) {
                const student = studentsData.find(s => s.id == mark.student_id);
                const classInfo = classesData.find(c => c.id == mark.class_id);
                const examInfo = examsData.find(e => e.id == mark.exam_id);
                const examType = examInfo ? examInfo.exam_name + ' (' + examInfo.exam_code + ')' : '-';
                const status = mark.marks >= 50 ? '<span class="badge badge-excellent">Lulus</span>' : '<span class="badge badge-poor">Gagal</span>';
                
                tableHtml += `
                    <tr>
                        <td>${student ? escapeHtml(student.matric_no) : '-'}</td>
                        <td>${student ? escapeHtml(student.full_name) : '-'}</td>
                        <td>${classInfo ? escapeHtml(classInfo.class_name) : '-'}</td>
                        <td>${escapeHtml(examType)}</td>
                        <td>${mark.marks}</td>
                        <td>${mark.grade}</td>
                        <td>${mark.pointer}</td>
                        <td>${status}</td>
                    </tr>
                `;
            }
            
            document.getElementById('analysisTableBody').innerHTML = tableHtml || '<tr><td colspan="8" style="text-align:center;">Tiada rekod markah</td></tr>';
        }

        function initCharts() {
            Chart.defaults.color = '#a1a1aa';
            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

            const subjectCtx = document.getElementById('subjectChart').getContext('2d');
            charts.subject = new Chart(subjectCtx, {
                type: 'bar',
                data: {
                    labels: subjectNames,
                    datasets: [{ 
                        label: 'Purata Markah (%)', 
                        data: subjectAvgs, 
                        backgroundColor: 'rgba(124, 58, 237, 0.65)',
                        borderColor: '#a855f7',
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: { 
                    responsive: true, 
                    scales: { 
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#a1a1aa' } },
                        x: { grid: { display: false }, ticks: { color: '#a1a1aa' } }
                    },
                    plugins: { legend: { labels: { color: '#cbd5e1', font: { weight: 'bold' } } } }
                }
            });

            const gradeCtx = document.getElementById('gradeChart').getContext('2d');
            const gradeLabels = ['A', 'A-', 'B+', 'B', 'C+', 'C', 'D'];
            charts.grade = new Chart(gradeCtx, {
                type: 'doughnut',
                data: {
                    labels: gradeLabels,
                    datasets: [{ 
                        data: gradeDist.length ? gradeDist : [0,0,0,0,0,0,0], 
                        backgroundColor: ['#10b981', '#34d399', '#6ee7b7', '#f59e0b', '#fbbf24', '#fcd34d', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#110d24'
                    }]
                },
                options: { 
                    responsive: true, 
                    plugins: { legend: { position: 'bottom', labels: { color: '#cbd5e1', boxWidth: 12, padding: 15 } } } 
                }
            });
        }

        // Load subjects taught by this teacher
        function loadTeacherSubjects() {
            const formData = new FormData();
            formData.append('action', 'get_teacher_subjects');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const tbody = document.getElementById('teacherSubjectsList');
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Tiada subjek yang diajar</td></tr>';
                        return;
                    }
                    
                    let html = '';
                    data.data.forEach(subj => {
                        html += `
                            <tr>
                                <td>${escapeHtml(subj.class_name)}</td>
                                <td>Semester ${subj.semester}</td>
                                <td>${escapeHtml(subj.subject_code)}</td>
                                <td>${escapeHtml(subj.subject_name)}</td>
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
            loadTeacherSubjects();
            document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); }));
        });
        
        window.onclick = e => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
    </script>
</body>
</html>