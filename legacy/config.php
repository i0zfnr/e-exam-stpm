<?php

// ======================================================
// FAIL KONFIGURASI UTAMA
// Sistem Peperiksaan STPM
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Pangkalan Data
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sistem_peperiksaan_stpm');

// Masa Zon Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================================================
// SAMBUNGAN PANGKALAN DATA
// ======================================================

class Database
{
    private static $instance = null;

    private $connection;

    private function __construct()
    {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if ($this->connection->connect_error) {
            exit('Sambungan pangkalan data gagal: '.$this->connection->connect_error);
        }

        $this->connection->set_charset('utf8mb4');
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database;
        }

        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

$db = Database::getInstance();
$conn = $db->getConnection();

// ======================================================
// FUNGSI-FUNGSI UTAMA
// ======================================================

/**
 * Sanitasi input pengguna
 */
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    return $data;
}

/**
 * Validate dan sanitasi mengikut jenis
 */
function validateAndSanitize($input, $type = 'string')
{
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) ? (int) $input : 0;
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT) ? (float) $input : 0;
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : '';
        case 'string':
        default:
            return sanitize($input);
    }
}

/**
 * Dapatkan maklumat pengguna semasa dari session
 */
function getCurrentUser()
{
    if (! isset($_SESSION['user_id'])) {
        return null;
    }

    global $conn;

    $stmt = $conn->prepare("
        SELECT u.*, 
            CASE 
                WHEN u.role = 'pelajar' THEN s.full_name
                WHEN u.role = 'guru' THEN t.full_name
                WHEN u.role = 'admin' THEN a.full_name
            END as full_name,
            CASE 
                WHEN u.role = 'pelajar' THEN s.class_id
                WHEN u.role = 'guru' THEN t.teacher_type
                WHEN u.role = 'admin' THEN a.admin_level
            END as extra_info,
            CASE 
                WHEN u.role = 'pelajar' THEN s.matric_no
                WHEN u.role = 'guru' THEN t.teacher_id
                WHEN u.role = 'admin' THEN a.admin_id
            END as id_number,
            CASE 
                WHEN u.role = 'pelajar' THEN s.id
                WHEN u.role = 'guru' THEN t.id
                WHEN u.role = 'admin' THEN a.id
            END as profile_id,
            CASE 
                WHEN u.role = 'pelajar' THEN s.phone
                WHEN u.role = 'guru' THEN t.phone
                WHEN u.role = 'admin' THEN a.phone
            END as phone
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN teachers t ON u.id = t.user_id
        LEFT JOIN admins a ON u.id = a.user_id
        WHERE u.id = ?
    ");

    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

/**
 * Semak status login
 */
function checkLogin()
{
    if (! isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Semak role pengguna
 */
function checkRole($roles = [])
{
    if (! isset($_SESSION['role'])) {
        header('Location: login.php');
        exit();
    }

    if (! empty($roles) && ! in_array($_SESSION['role'], $roles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

/**
 * Log aktiviti sistem
 */
function logActivity($user_id, $action, $description)
{
    global $conn;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare('
        INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->bind_param('issss', $user_id, $action, $description, $ip, $user_agent);
    $stmt->execute();
}

/**
 * Dapatkan data pelajar mengikut ID
 */
function getStudentById($id)
{
    global $conn;

    $stmt = $conn->prepare('
        SELECT s.*, c.class_name, u.email 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/**
 * Dapatkan data guru mengikut ID
 */
function getTeacherById($id)
{
    global $conn;

    $stmt = $conn->prepare('
        SELECT t.*, u.email 
        FROM teachers t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/**
 * Dapatkan semua pelajar dalam kelas
 */
function getStudentsByClass($class_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT s.*, u.email 
        FROM students s 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE s.class_id = ? AND s.status = 'Aktif'
        ORDER BY s.full_name
    ");
    $stmt->bind_param('i', $class_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Dapatkan markah pelajar untuk subjek tertentu
 */
function getStudentMarks($student_id, $subject_id, $semester, $exam_id = null)
{
    global $conn;

    $sql = 'SELECT * FROM marks WHERE student_id = ? AND subject_id = ? AND semester = ?';
    $params = [$student_id, $subject_id, $semester];
    $types = 'iii';

    if ($exam_id) {
        $sql .= ' AND exam_id = ?';
        $params[] = $exam_id;
        $types .= 'i';
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/**
 * Kira PNG pelajar untuk semester tertentu
 */
function calculateStudentPNG($student_id, $semester)
{
    global $conn;

    $stmt = $conn->prepare('
        SELECT AVG(pointer) as png, COUNT(*) as total
        FROM marks 
        WHERE student_id = ? AND semester = ? AND marks IS NOT NULL
    ');
    $stmt->bind_param('ii', $student_id, $semester);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? round($result['png'], 2) : 0;
}

/**
 * Dapatkan semua subjek untuk kelas tertentu (dari class_subjects)
 */
function getSubjectsByClass($class_id, $semester = null)
{
    global $conn;

    $sql = "
        SELECT s.*, cs.teacher_id, cs.semester, cs.id as class_subject_id,
               t.full_name as teacher_name, t.teacher_id as teacher_code
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        LEFT JOIN teachers t ON cs.teacher_id = t.id
        WHERE cs.class_id = ? AND cs.status = 'Aktif' AND s.status = 'Aktif'
    ";

    if ($semester) {
        $sql .= ' AND cs.semester = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $class_id, $semester);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $class_id);
    }

    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Dapatkan subjek yang diajar oleh guru tertentu
 */
function getSubjectsByTeacherId($teacher_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT cs.*, s.subject_code, s.subject_name, s.stream,
               c.class_name, c.id as class_id
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        JOIN classes c ON cs.class_id = c.id
        WHERE cs.teacher_id = ? AND cs.status = 'Aktif' AND s.status = 'Aktif'
        ORDER BY cs.semester, c.class_name, s.subject_name
    ");
    $stmt->bind_param('i', $teacher_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Dapatkan subjek untuk pelajar berdasarkan kelas mereka
 */
function getSubjectsForStudent($student_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT DISTINCT s.*, cs.semester, cs.id as class_subject_id,
               t.full_name as teacher_name, t.teacher_id as teacher_code
        FROM students st
        JOIN class_subjects cs ON st.class_id = cs.class_id
        JOIN subjects s ON cs.subject_id = s.id
        LEFT JOIN teachers t ON cs.teacher_id = t.id
        WHERE st.id = ? AND cs.status = 'Aktif' AND s.status = 'Aktif'
        ORDER BY cs.semester, s.subject_name
    ");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Assign subjek ke kelas
 */
function assignSubjectToClass($class_id, $subject_id, $teacher_id, $semester)
{
    global $conn;

    // Check if already exists
    $check = $conn->prepare("
        SELECT id FROM class_subjects 
        WHERE class_id = ? AND subject_id = ? AND semester = ? AND status = 'Aktif'
    ");
    $check->bind_param('iii', $class_id, $subject_id, $semester);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        return false;
    }

    $stmt = $conn->prepare("
        INSERT INTO class_subjects (class_id, subject_id, teacher_id, semester, status) 
        VALUES (?, ?, ?, ?, 'Aktif')
    ");
    $stmt->bind_param('iiii', $class_id, $subject_id, $teacher_id, $semester);

    return $stmt->execute();
}

/**
 * Remove subject from class
 */
function removeSubjectFromClass($class_subject_id)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE class_subjects SET status = 'Tidak Aktif' WHERE id = ?");
    $stmt->bind_param('i', $class_subject_id);

    return $stmt->execute();
}

/**
 * Update teacher for class subject
 */
function updateClassSubjectTeacher($class_subject_id, $teacher_id)
{
    global $conn;

    $stmt = $conn->prepare('UPDATE class_subjects SET teacher_id = ? WHERE id = ?');
    $stmt->bind_param('ii', $teacher_id, $class_subject_id);

    return $stmt->execute();
}

/**
 * Dapatkan semua pengumuman untuk pengguna
 */
function getAnnouncements($target = 'all', $class_id = null)
{
    global $conn;

    $conditions = ['is_published = 1', 'publish_date <= CURDATE()'];
    $targetClauses = ["target = 'all'"];
    $params = [];
    $types = '';

    if ($target !== 'all' && in_array($target, ['students', 'teachers', 'admin'])) {
        $targetClauses[] = 'target = ?';
        $params[] = $target;
        $types .= 's';
    }

    if ($class_id && (int) $class_id > 0) {
        $targetClauses[] = 'class_id = ?';
        $params[] = (int) $class_id;
        $types .= 'i';
    }

    $conditions[] = '('.implode(' OR ', $targetClauses).')';
    $sql = 'SELECT * FROM announcements WHERE '.implode(' AND ', $conditions).' ORDER BY created_at DESC';

    if (! empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Generate laporan prestasi kelas
 */
function generateClassReport($class_id, $semester, $exam_id = null)
{
    global $conn;

    $sql = '
        SELECT 
            s.subject_name,
            ROUND(AVG(m.marks), 1) as avg_marks,
            COUNT(CASE WHEN m.marks >= 50 THEN 1 END) as passed,
            COUNT(m.id) as total_students,
            ROUND((COUNT(CASE WHEN m.marks >= 50 THEN 1 END) / COUNT(m.id)) * 100, 1) as pass_rate
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        LEFT JOIN marks m ON s.id = m.subject_id AND m.class_id = cs.class_id AND m.semester = cs.semester
        WHERE cs.class_id = ? AND cs.semester = ?
    ';
    $params = [$class_id, $semester];
    $types = 'ii';

    if ($exam_id) {
        $sql .= ' AND m.exam_id = ?';
        $params[] = $exam_id;
        $types .= 'i';
    }

    $sql .= ' GROUP BY s.id ORDER BY avg_marks DESC';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Backup pangkalan data
 */
function backupDatabase()
{
    global $conn;
    $backup_file = 'backup_'.DB_NAME.'_'.date('Y-m-d_H-i-s').'.sql';
    $backup_path = __DIR__.DIRECTORY_SEPARATOR.$backup_file;

    // 1. Try finding Laragon mysqldump executable
    $mysqldump_paths = [
        'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe',
        'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
        'mysqldump',
    ];

    $dump_bin = null;
    foreach ($mysqldump_paths as $path) {
        if (file_exists($path)) {
            $dump_bin = $path;
            break;
        }
    }

    if ($dump_bin) {
        $pwdArg = DB_PASSWORD !== '' ? '--password='.escapeshellarg(DB_PASSWORD) : '';
        $command = sprintf(
            '"%s" --user=%s %s --host=%s %s > "%s"',
            $dump_bin,
            escapeshellarg(DB_USER),
            $pwdArg,
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_NAME),
            $backup_path
        );
        exec($command, $output, $return_var);
        if ($return_var === 0 && file_exists($backup_path) && filesize($backup_path) > 0) {
            return $backup_file;
        }
    }

    // 2. Pure PHP fallback export if mysqldump is not available
    try {
        $tables = [];
        $result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }

        $sqlScript = '-- Pangkalan Data: '.DB_NAME."\n";
        $sqlScript .= '-- Dijana pada: '.date('Y-m-d H:i:s')."\n\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $createTable = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_row();
            $sqlScript .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sqlScript .= $createTable[1].";\n\n";

            $rows = $conn->query("SELECT * FROM `{$table}`");
            while ($row = $rows->fetch_assoc()) {
                $values = array_map(function ($val) use ($conn) {
                    return $val === null ? 'NULL' : "'".$conn->real_escape_string($val)."'";
                }, array_values($row));
                $sqlScript .= "INSERT INTO `{$table}` VALUES (".implode(', ', $values).");\n";
            }
            $sqlScript .= "\n";
        }
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (file_put_contents($backup_path, $sqlScript) !== false) {
            return $backup_file;
        }
    } catch (Exception $e) {
        error_log('Backup failed: '.$e->getMessage());
    }

    return false;
}

/**
 * Hantar notifikasi (placeholder untuk integrasi masa depan)
 */
function sendNotification($user_id, $title, $message, $type = 'info')
{
    logActivity($user_id, 'NOTIFICATION', "{$title}: {$message}");

    return true;
}

/**
 * Dapatkan statistik dashboard
 */
function getDashboardStats($role = 'admin')
{
    global $conn;

    $stats = [];

    // Jumlah pelajar aktif
    $result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'Aktif'");
    $stats['total_students'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Jumlah guru aktif
    $result = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status = 'Aktif'");
    $stats['total_teachers'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Jumlah subjek
    $result = $conn->query("SELECT COUNT(*) as total FROM subjects WHERE status = 'Aktif'");
    $stats['total_subjects'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Jumlah kelas aktif
    $result = $conn->query("SELECT COUNT(*) as total FROM classes WHERE status = 'Aktif'");
    $stats['total_classes'] = $result ? $result->fetch_assoc()['total'] : 0;

    // PNG purata keseluruhan
    $result = $conn->query('SELECT AVG(pointer) as avg FROM marks WHERE marks IS NOT NULL');
    $stats['avg_png'] = $result ? round($result->fetch_assoc()['avg'] ?? 0, 2) : 0;

    // Kadar kelulusan
    $result = $conn->query('
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN marks >= 50 THEN 1 ELSE 0 END) as passed 
        FROM marks WHERE marks IS NOT NULL
    ');
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pass_rate'] = $row['total'] > 0 ? round(($row['passed'] / $row['total']) * 100, 1) : 0;
    } else {
        $stats['pass_rate'] = 0;
    }

    return $stats;
}

/**
 * Dapatkan semua jenis peperiksaan
 */
function getAllExamTypes()
{
    global $conn;
    $result = $conn->query('SELECT * FROM exam_types WHERE is_active = 1 ORDER BY exam_order');

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Dapatkan semua peperiksaan
 */
function getAllExams($status = null)
{
    global $conn;
    $sql = 'SELECT e.*, et.exam_name as type_name, et.exam_code 
            FROM exams e 
            JOIN exam_types et ON e.exam_type_id = et.id';
    if ($status) {
        $sql .= ' WHERE e.status = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $status);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// CORS headers untuk API (jika perlu)
if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
