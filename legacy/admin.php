<?php
// ======================================================
// FAIL PANEL ADMIN (ADMIN BIASA)
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';
checkLogin();
checkRole(['admin']);

$user = getCurrentUser();

// Check if user is super admin - redirect to super admin panel
if ($user['sub_role'] === 'super_admin') {
    header('Location: superAdmin.php');
    exit();
}

// ======================================================
// PROSES CRUD VIA AJAX
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Tindakan tidak sah'];

    switch ($action) {
        // ==================== STUDENT CRUD ====================
        case 'add_student':
            $matric = validateAndSanitize($_POST['matric'] ?? '');
            $name = validateAndSanitize($_POST['name'] ?? '');
            $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
            $phone = validateAndSanitize($_POST['phone'] ?? '');
            $email = validateAndSanitize($_POST['email'] ?? '', 'email');
            $ic_number = validateAndSanitize($_POST['ic_number'] ?? '');

            if (empty($matric) || empty($name) || $class_id <= 0) {
                $response['message'] = 'Sila lengkapkan maklumat required';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare('SELECT id FROM students WHERE matric_no = ?');
            $check->bind_param('s', $matric);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'No. Matrik sudah wujud';
                echo json_encode($response);
                exit;
            }

            $conn->begin_transaction();
            try {
                $username = $matric;
                $password = password_hash('student123', PASSWORD_DEFAULT);
                $role = 'pelajar';

                $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, ?, 'Aktif')");
                $stmt->bind_param('ssss', $username, $password, $email, $role);
                $stmt->execute();
                $user_id = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO students (user_id, matric_no, full_name, ic_number, class_id, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')");
                $stmt->bind_param('isssis', $user_id, $matric, $name, $ic_number, $class_id, $phone);
                $stmt->execute();

                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Pelajar berjaya ditambah';
                logActivity($user['id'], 'ADD_STUDENT', "Menambah pelajar: $matric - $name");
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Ralat: '.$e->getMessage();
            }
            break;

        case 'delete_student':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            if ($id <= 0) {
                $response['message'] = 'ID tidak sah';
                echo json_encode($response);
                exit;
            }

            $student = getStudentById($id);
            if ($student) {
                $stmt = $conn->prepare("UPDATE students SET status = 'Tidak Aktif' WHERE id = ?");
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $stmt2 = $conn->prepare("UPDATE users SET status = 'Tidak Aktif' WHERE id = ?");
                    $stmt2->bind_param('i', $student['user_id']);
                    $stmt2->execute();

                    $response['success'] = true;
                    $response['message'] = 'Pelajar telah ditamatkan';
                    logActivity($user['id'], 'DELETE_STUDENT', 'Menamatkan pelajar: '.$student['matric_no']);
                }
            }
            break;

        case 'edit_student':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            $matric = validateAndSanitize($_POST['matric'] ?? '');
            $name = validateAndSanitize($_POST['name'] ?? '');
            $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
            $phone = validateAndSanitize($_POST['phone'] ?? '');
            $email = validateAndSanitize($_POST['email'] ?? '', 'email');
            $ic_number = validateAndSanitize($_POST['ic_number'] ?? '');

            if ($id <= 0 || empty($matric) || empty($name) || $class_id <= 0) {
                $response['message'] = 'Sila lengkapkan maklumat yang diperlukan';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare('SELECT id FROM students WHERE matric_no = ? AND id != ?');
            $check->bind_param('si', $matric, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'No. Matrik sudah digunakan oleh pelajar lain';
                echo json_encode($response);
                exit;
            }

            $student = getStudentById($id);
            if (! $student) {
                $response['message'] = 'Pelajar tidak dijumpai';
                echo json_encode($response);
                exit;
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare('UPDATE students SET matric_no = ?, full_name = ?, ic_number = ?, class_id = ?, phone = ? WHERE id = ?');
                $stmt->bind_param('sssisi', $matric, $name, $ic_number, $class_id, $phone, $id);
                $stmt->execute();

                $stmt2 = $conn->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                $stmt2->bind_param('ssi', $matric, $email, $student['user_id']);
                $stmt2->execute();

                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Maklumat pelajar berjaya dikemaskini';
                logActivity($user['id'], 'EDIT_STUDENT', "Mengemaskini pelajar: $matric - $name");
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Ralat: '.$e->getMessage();
            }
            break;

            // ==================== TEACHER CRUD ====================
        case 'add_teacher':
            $teacher_id = validateAndSanitize($_POST['teacher_id'] ?? '');
            $name = validateAndSanitize($_POST['name'] ?? '');
            $type = validateAndSanitize($_POST['type'] ?? '');
            $email = validateAndSanitize($_POST['email'] ?? '', 'email');
            $phone = validateAndSanitize($_POST['phone'] ?? '');

            if (empty($teacher_id) || empty($name) || empty($type) || empty($email)) {
                $response['message'] = 'Sila lengkapkan maklumat required';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare('SELECT id FROM teachers WHERE teacher_id = ?');
            $check->bind_param('s', $teacher_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'ID Guru sudah wujud';
                echo json_encode($response);
                exit;
            }

            $conn->begin_transaction();
            try {
                $username = $teacher_id;
                $password = password_hash('guru123', PASSWORD_DEFAULT);
                $role = 'guru';
                $sub_role = $type;

                $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, sub_role, status) VALUES (?, ?, ?, ?, ?, 'Aktif')");
                $stmt->bind_param('sssss', $username, $password, $email, $role, $sub_role);
                $stmt->execute();
                $user_id = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO teachers (user_id, teacher_id, full_name, teacher_type, phone, status) VALUES (?, ?, ?, ?, ?, 'Aktif')");
                $stmt->bind_param('issss', $user_id, $teacher_id, $name, $type, $phone);
                $stmt->execute();

                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Guru berjaya ditambah';
                logActivity($user['id'], 'ADD_TEACHER', "Menambah guru: $teacher_id - $name");
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Ralat: '.$e->getMessage();
            }
            break;

        case 'delete_teacher':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            if ($id <= 0) {
                $response['message'] = 'ID tidak sah';
                echo json_encode($response);
                exit;
            }

            $teacher = getTeacherById($id);
            if ($teacher) {
                $stmt = $conn->prepare("UPDATE teachers SET status = 'Tidak Aktif' WHERE id = ?");
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $stmt2 = $conn->prepare("UPDATE users SET status = 'Tidak Aktif' WHERE id = ?");
                    $stmt2->bind_param('i', $teacher['user_id']);
                    $stmt2->execute();

                    $response['success'] = true;
                    $response['message'] = 'Guru telah ditamatkan';
                    logActivity($user['id'], 'DELETE_TEACHER', 'Menamatkan guru: '.$teacher['teacher_id']);
                }
            }
            break;

        case 'edit_teacher':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            $teacher_id = validateAndSanitize($_POST['teacher_id'] ?? '');
            $name = validateAndSanitize($_POST['name'] ?? '');
            $type = validateAndSanitize($_POST['type'] ?? '');
            $email = validateAndSanitize($_POST['email'] ?? '', 'email');
            $phone = validateAndSanitize($_POST['phone'] ?? '');

            if ($id <= 0 || empty($teacher_id) || empty($name) || empty($type)) {
                $response['message'] = 'Sila lengkapkan maklumat yang diperlukan';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare('SELECT id FROM teachers WHERE teacher_id = ? AND id != ?');
            $check->bind_param('si', $teacher_id, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'ID Guru sudah digunakan oleh guru lain';
                echo json_encode($response);
                exit;
            }

            $teacher = getTeacherById($id);
            if (! $teacher) {
                $response['message'] = 'Guru tidak dijumpai';
                echo json_encode($response);
                exit;
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare('UPDATE teachers SET teacher_id = ?, full_name = ?, teacher_type = ?, phone = ? WHERE id = ?');
                $stmt->bind_param('ssssi', $teacher_id, $name, $type, $phone, $id);
                $stmt->execute();

                $stmt2 = $conn->prepare('UPDATE users SET username = ?, sub_role = ?, email = ? WHERE id = ?');
                $stmt2->bind_param('sssi', $teacher_id, $type, $email, $teacher['user_id']);
                $stmt2->execute();

                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Maklumat guru berjaya dikemaskini';
                logActivity($user['id'], 'EDIT_TEACHER', "Mengemaskini guru: $teacher_id - $name");
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Ralat: '.$e->getMessage();
            }
            break;

            // ==================== SUBJECT CRUD ====================
        case 'add_subject':
            $code = validateAndSanitize($_POST['code'] ?? '');
            $name = validateAndSanitize($_POST['name'] ?? '');
            $stream = validateAndSanitize($_POST['stream'] ?? '');

            if (empty($code) || empty($name)) {
                $response['message'] = 'Sila lengkapkan maklumat required';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare('SELECT id FROM subjects WHERE subject_code = ?');
            $check->bind_param('s', $code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'Kod subjek sudah wujud';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, stream, status) VALUES (?, ?, ?, 'Aktif')");
            $stmt->bind_param('sss', $code, $name, $stream);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Subjek berjaya ditambah';
                logActivity($user['id'], 'ADD_SUBJECT', "Menambah subjek: $code - $name");
            } else {
                $response['message'] = 'Gagal menambah subjek';
            }
            break;

        case 'delete_subject':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            if ($id <= 0) {
                $response['message'] = 'ID tidak sah';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare("UPDATE subjects SET status = 'Tidak Aktif' WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Subjek telah ditamatkan';
                logActivity($user['id'], 'DELETE_SUBJECT', "Menamatkan subjek ID: $id");
            }
            break;

        case 'edit_subject':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            $code = validateAndSanitize($_POST['code'] ?? '');
            $name = validateAndSanitize($_POST['name'] ?? '');
            $stream = validateAndSanitize($_POST['stream'] ?? '');

            if ($id <= 0 || empty($code) || empty($name)) {
                $response['message'] = 'Sila lengkapkan maklumat yang diperlukan';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare('SELECT id FROM subjects WHERE subject_code = ? AND id != ?');
            $check->bind_param('si', $code, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'Kod subjek sudah digunakan oleh subjek lain';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare('UPDATE subjects SET subject_code = ?, subject_name = ?, stream = ? WHERE id = ?');
            $stmt->bind_param('sssi', $code, $name, $stream, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Subjek berjaya dikemaskini';
                logActivity($user['id'], 'EDIT_SUBJECT', "Mengemaskini subjek: $code - $name");
            } else {
                $response['message'] = 'Gagal mengemaskini subjek';
            }
            break;

            // ==================== CLASS CRUD ====================
        case 'add_class':
            $class_name = validateAndSanitize($_POST['class_name'] ?? '');
            $teacher_id = validateAndSanitize($_POST['teacher_id'] ?? 0, 'int');

            if (empty($class_name)) {
                $response['message'] = 'Nama kelas tidak boleh kosong';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare("SELECT id FROM classes WHERE class_name = ? AND status = 'Aktif'");
            $check->bind_param('s', $class_name);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'Nama kelas sudah wujud';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO classes (class_name, teacher_id, status) VALUES (?, ?, 'Aktif')");
            $stmt->bind_param('si', $class_name, $teacher_id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Kelas berjaya ditambah';
                logActivity($user['id'], 'ADD_CLASS', "Menambah kelas: $class_name");
            } else {
                $response['message'] = 'Gagal menambah kelas';
            }
            break;

        case 'edit_class':
            $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
            $class_name = validateAndSanitize($_POST['class_name'] ?? '');
            $teacher_id = validateAndSanitize($_POST['teacher_id'] ?? 0, 'int');

            if ($class_id <= 0 || empty($class_name)) {
                $response['message'] = 'Data tidak lengkap';
                echo json_encode($response);
                exit;
            }

            $check = $conn->prepare("SELECT id FROM classes WHERE class_name = ? AND id != ? AND status = 'Aktif'");
            $check->bind_param('si', $class_name, $class_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $response['message'] = 'Nama kelas sudah digunakan oleh kelas lain';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare('UPDATE classes SET class_name = ?, teacher_id = ? WHERE id = ?');
            $stmt->bind_param('sii', $class_name, $teacher_id, $class_id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Kelas berjaya dikemaskini';
                logActivity($user['id'], 'EDIT_CLASS', "Mengemaskini kelas ID: $class_id kepada: $class_name");
            } else {
                $response['message'] = 'Gagal mengemaskini kelas';
            }
            break;

        case 'delete_class':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            if ($id <= 0) {
                $response['message'] = 'ID tidak sah';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare("UPDATE classes SET status = 'Tidak Aktif' WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Kelas telah ditamatkan';
                logActivity($user['id'], 'DELETE_CLASS', "Menamatkan kelas ID: $id");
            }
            break;

            // ==================== EXAM CRUD ====================
        case 'add_exam':
            $exam_name = validateAndSanitize($_POST['exam_name'] ?? '');
            $exam_type_id = validateAndSanitize($_POST['exam_type_id'] ?? 0, 'int');
            $semester = validateAndSanitize($_POST['semester'] ?? 1, 'int');
            $academic_year = validateAndSanitize($_POST['academic_year'] ?? '');
            $exam_date = validateAndSanitize($_POST['exam_date'] ?? '');
            $start_time = validateAndSanitize($_POST['start_time'] ?? '');
            $end_time = validateAndSanitize($_POST['end_time'] ?? '');
            $venue = validateAndSanitize($_POST['venue'] ?? '');
            $total_marks = validateAndSanitize($_POST['total_marks'] ?? 100, 'int');
            $passing_marks = validateAndSanitize($_POST['passing_marks'] ?? 50, 'int');

            if (empty($exam_name) || $exam_type_id <= 0) {
                $response['message'] = 'Sila lengkapkan maklumat required';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO exams (exam_name, exam_type_id, semester, academic_year, exam_date, start_time, end_time, venue, total_marks, passing_marks, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aktif', ?)");
            $stmt->bind_param('siisssssiii', $exam_name, $exam_type_id, $semester, $academic_year, $exam_date, $start_time, $end_time, $venue, $total_marks, $passing_marks, $user['profile_id']);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Peperiksaan berjaya ditambah';
                logActivity($user['id'], 'ADD_EXAM', "Menambah peperiksaan: $exam_name");
            } else {
                $response['message'] = 'Gagal menambah peperiksaan';
            }
            break;

        case 'delete_exam':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            if ($id <= 0) {
                $response['message'] = 'ID tidak sah';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare("UPDATE exams SET status = 'Batal' WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Peperiksaan telah dibatalkan';
                logActivity($user['id'], 'DELETE_EXAM', "Membatalkan peperiksaan ID: $id");
            }
            break;

        case 'edit_exam':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            $exam_name = validateAndSanitize($_POST['exam_name'] ?? '');
            $exam_type_id = validateAndSanitize($_POST['exam_type_id'] ?? 0, 'int');
            $semester = validateAndSanitize($_POST['semester'] ?? 1, 'int');
            $academic_year = validateAndSanitize($_POST['academic_year'] ?? '');
            $exam_date = validateAndSanitize($_POST['exam_date'] ?? '');
            $start_time = validateAndSanitize($_POST['start_time'] ?? '');
            $end_time = validateAndSanitize($_POST['end_time'] ?? '');
            $venue = validateAndSanitize($_POST['venue'] ?? '');
            $total_marks = validateAndSanitize($_POST['total_marks'] ?? 100, 'int');
            $passing_marks = validateAndSanitize($_POST['passing_marks'] ?? 50, 'int');
            $status = validateAndSanitize($_POST['status'] ?? 'Aktif');

            if ($id <= 0 || empty($exam_name) || $exam_type_id <= 0) {
                $response['message'] = 'Sila lengkapkan maklumat yang diperlukan';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare('UPDATE exams SET exam_name = ?, exam_type_id = ?, semester = ?, academic_year = ?, exam_date = ?, start_time = ?, end_time = ?, venue = ?, total_marks = ?, passing_marks = ?, status = ? WHERE id = ?');
            $stmt->bind_param('siisssssiisi', $exam_name, $exam_type_id, $semester, $academic_year, $exam_date, $start_time, $end_time, $venue, $total_marks, $passing_marks, $status, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Peperiksaan berjaya dikemaskini';
                logActivity($user['id'], 'EDIT_EXAM', "Mengemaskini peperiksaan ID: $id - $exam_name");
            } else {
                $response['message'] = 'Gagal mengemaskini peperiksaan';
            }
            break;

            // ==================== CLASS SUBJECTS CRUD ====================
        case 'get_class_subjects':
            $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
            $semester = validateAndSanitize($_POST['semester'] ?? 0, 'int');

            $sql = "
                SELECT cs.*, s.subject_code, s.subject_name, s.stream,
                       c.class_name, t.full_name as teacher_name, t.teacher_id as teacher_code
                FROM class_subjects cs
                JOIN subjects s ON cs.subject_id = s.id
                JOIN classes c ON cs.class_id = c.id
                LEFT JOIN teachers t ON cs.teacher_id = t.id
                WHERE cs.status = 'Aktif' AND s.status = 'Aktif'
            ";

            $params = [];
            $types = '';

            if ($class_id > 0) {
                $sql .= ' AND cs.class_id = ?';
                $params[] = $class_id;
                $types .= 'i';
            }
            if ($semester > 0) {
                $sql .= ' AND cs.semester = ?';
                $params[] = $semester;
                $types .= 'i';
            }

            $sql .= ' ORDER BY c.class_name, cs.semester, s.subject_name';

            if (! empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }

            $subjectsList = [];
            while ($row = $result->fetch_assoc()) {
                $subjectsList[] = $row;
            }

            $response['success'] = true;
            $response['data'] = $subjectsList;
            break;

        case 'assign_subject_to_class':
            $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
            $subject_id = validateAndSanitize($_POST['subject_id'] ?? 0, 'int');
            $teacher_id = validateAndSanitize($_POST['teacher_id'] ?? 0, 'int');
            $semester = validateAndSanitize($_POST['semester'] ?? 1, 'int');

            if ($class_id <= 0 || $subject_id <= 0 || $teacher_id <= 0) {
                $response['message'] = 'Sila lengkapkan semua maklumat';
                echo json_encode($response);
                exit;
            }

            if (assignSubjectToClass($class_id, $subject_id, $teacher_id, $semester)) {
                $response['success'] = true;
                $response['message'] = 'Subjek berjaya diassign ke kelas';
                logActivity($user['id'], 'ASSIGN_SUBJECT', "Assign subjek ID: $subject_id ke kelas ID: $class_id untuk semester $semester");
            } else {
                $response['message'] = 'Subjek sudah wujud untuk kelas ini pada semester tersebut';
            }
            break;

        case 'remove_class_subject':
            $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
            if ($id <= 0) {
                $response['message'] = 'ID tidak sah';
                echo json_encode($response);
                exit;
            }

            if (removeSubjectFromClass($id)) {
                $response['success'] = true;
                $response['message'] = 'Subjek telah dibuang dari kelas';
                logActivity($user['id'], 'REMOVE_CLASS_SUBJECT', "Buang class_subject ID: $id");
            }
            break;

            // ==================== PROFILE ====================
        case 'update_profile':
            $name = validateAndSanitize($_POST['name'] ?? '');
            $email = validateAndSanitize($_POST['email'] ?? '', 'email');
            $phone = validateAndSanitize($_POST['phone'] ?? '');

            if (empty($name)) {
                $response['message'] = 'Nama tidak boleh kosong';
                echo json_encode($response);
                exit;
            }

            $stmt = $conn->prepare('UPDATE admins SET full_name = ?, phone = ? WHERE user_id = ?');
            $stmt->bind_param('ssi', $name, $phone, $user['id']);

            if ($stmt->execute()) {
                $stmt2 = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
                $stmt2->bind_param('si', $email, $user['id']);
                $stmt2->execute();

                $response['success'] = true;
                $response['message'] = 'Profil berjaya dikemaskini';
                logActivity($user['id'], 'UPDATE_PROFILE', 'Mengemaskini profil admin');
            } else {
                $response['message'] = 'Gagal mengemaskini profil';
            }
            break;

        case 'change_password':
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
            break;
    }

    echo json_encode($response);
    exit;
}

// ======================================================
// AMBIL DATA DARI DATABASE
// ======================================================

// Total students
$totalStudents = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'Aktif'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalStudents = $row['total'] ?? 0;
}

// Total teachers
$totalTeachers = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status = 'Aktif'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalTeachers = $row['total'] ?? 0;
}

// Total subjects
$totalSubjects = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM subjects WHERE status = 'Aktif'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalSubjects = $row['total'] ?? 0;
}

// Total classes
$totalClasses = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM classes WHERE status = 'Aktif'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalClasses = $row['total'] ?? 0;
}

// Total exams
$totalExams = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM exams WHERE status IN ('Aktif', 'Draf')");
if ($result) {
    $row = $result->fetch_assoc();
    $totalExams = $row['total'] ?? 0;
}

// Get exam types
$examTypes = [];
$result = $conn->query('SELECT * FROM exam_types WHERE is_active = 1 ORDER BY exam_order');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $examTypes[] = $row;
    }
}

// Get all teachers for dropdown
$allTeachers = [];
$result = $conn->query("SELECT t.id, t.teacher_id, t.full_name FROM teachers t WHERE t.status = 'Aktif' ORDER BY t.full_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allTeachers[] = $row;
    }
}

// Get all classes for dropdown
$allClasses = [];
$result = $conn->query("SELECT c.id, c.class_name FROM classes c WHERE c.status = 'Aktif' ORDER BY c.class_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allClasses[] = $row;
    }
}

// Get all subjects for dropdown
$allSubjects = [];
$result = $conn->query("SELECT s.id, s.subject_code, s.subject_name FROM subjects s WHERE s.status = 'Aktif' ORDER BY s.subject_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allSubjects[] = $row;
    }
}

// Get all exams
$exams = [];
$result = $conn->query("
    SELECT e.*, et.exam_name as type_name, et.exam_code
    FROM exams e
    JOIN exam_types et ON e.exam_type_id = et.id
    WHERE e.status IN ('Aktif', 'Draf', 'Selesai')
    ORDER BY e.exam_date DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
}

// Get all students with class info
$students = [];
$result = $conn->query("
    SELECT s.id, s.matric_no, s.full_name, s.phone, s.status, s.class_id,
           c.class_name,
           u.email
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.status = 'Aktif' 
    ORDER BY s.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get all teachers
$teachers = [];
$result = $conn->query("
    SELECT t.id, t.teacher_id, t.full_name, t.teacher_type, t.phone, t.status,
           u.email
    FROM teachers t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.status = 'Aktif' 
    ORDER BY t.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Get all subjects
$subjects = [];
$result = $conn->query("
    SELECT s.*
    FROM subjects s 
    WHERE s.status = 'Aktif'
    ORDER BY s.subject_code
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Get all classes with stats
$classes = [];
$result = $conn->query("
    SELECT c.id, c.class_name, c.teacher_id, c.status,
           (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status = 'Aktif') as student_count,
           t.full_name as teacher_name,
           t.teacher_id as teacher_code
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id = t.id
    WHERE c.status = 'Aktif'
    ORDER BY c.class_name
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Get average PNG
$avgPng = 0;
$result = $conn->query('SELECT ROUND(AVG(pointer), 2) as avg FROM marks WHERE marks IS NOT NULL');
if ($result) {
    $row = $result->fetch_assoc();
    $avgPng = $row['avg'] ?? 0;
}

// Get pass rate
$passRate = 0;
$result = $conn->query('
    SELECT COUNT(*) as total, SUM(CASE WHEN marks >= 50 THEN 1 ELSE 0 END) as passed 
    FROM marks WHERE marks IS NOT NULL
');
if ($result) {
    $row = $result->fetch_assoc();
    $total = $row['total'] ?? 0;
    $passed = $row['passed'] ?? 0;
    $passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
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
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --accent: #f59e0b;
            --accent-glow: rgba(245, 158, 11, 0.25);
            --bg-dark: #090d16;
            --bg-card: rgba(17, 24, 39, 0.75);
            --bg-card-hover: rgba(24, 34, 53, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glass-focus: rgba(99, 102, 241, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-subtle: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-glass: 0 12px 32px -4px rgba(0, 0, 0, 0.5), 0 4px 12px -2px rgba(0, 0, 0, 0.3);
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #f59e0b 100%);
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
        .bg-orb-1 { width: 550px; height: 550px; background: radial-gradient(circle, #6366f1 0%, transparent 70%); top: -120px; left: -100px; }
        .bg-orb-2 { width: 500px; height: 500px; background: radial-gradient(circle, #f59e0b 0%, transparent 70%); top: 40%; right: -150px; }
        .bg-orb-3 { width: 450px; height: 450px; background: radial-gradient(circle, #3b82f6 0%, transparent 70%); bottom: -100px; left: 25%; }

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
            background: linear-gradient(135deg, #6366f1, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        nav ul { 
            display: flex; 
            list-style: none; 
            gap: 5px; 
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
            padding: 7px 15px; 
            border-radius: 9999px; 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            font-size: 0.8rem; 
        }
        nav a:hover { 
            color: #ffffff; 
            background: rgba(255,255,255,0.08); 
        }
        nav a.active { 
            color: #ffffff; 
            background: var(--gradient-primary); 
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35); 
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
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }
        .avatar { 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #6366f1, #f59e0b); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 800; 
            color: white; 
            font-size: 0.85rem;
            box-shadow: 0 2px 10px rgba(99, 102, 241, 0.4);
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
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 2rem; }
        
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
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.25);
            background: var(--bg-card-hover);
        }
        .card:hover::before { opacity: 1; height: 4px; }
        
        .card-title { font-size: 0.78rem; margin-bottom: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
        .card-value { 
            font-size: 2.2rem; 
            font-weight: 800; 
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ffffff 30%, #c7d2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-icon { 
            position: absolute; 
            top: 1.4rem; right: 1.4rem; 
            font-size: 2.4rem; 
            color: #6366f1; 
            opacity: 0.2; 
            transition: all 0.3s ease; 
        }
        .card:hover .card-icon { opacity: 0.4; transform: scale(1.1) rotate(5deg); color: #f59e0b; }

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
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
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
            padding: 10px 18px; 
            background: var(--gradient-primary); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-size: 0.85rem; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
            text-decoration: none;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.45);
            filter: brightness(1.1);
        }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; border-radius: 8px; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3); color: #ffffff; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3); }
        .btn-primary { background: var(--gradient-primary); }

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
            border-color: #818cf8; 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
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
            background: linear-gradient(135deg, #ffffff, #c7d2fe);
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
        .badge-active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-exam { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }
        .badge-poor { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-class { background: rgba(245, 158, 11, 0.15); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.3); }
        .text-muted { color: var(--text-muted); font-size: 0.72rem; }

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
            max-width: 550px; 
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
        .modal-body { padding: 1.5rem; }
        .close-modal { background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted); transition: color 0.2s; }
        .close-modal:hover { color: #ffffff; }

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

        .row-2cols { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        @media (max-width: 768px) {
            .header-content { flex-direction: column; }
            nav ul { justify-content: center; }
            .dashboard-cards { grid-template-columns: 1fr; }
            .chart-grid { grid-template-columns: 1fr; }
            .row-2cols { grid-template-columns: 1fr; }
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
            <span>Sistem Peperiksaan STPM | Pentadbir</span>
        </div>
        <nav>
            <ul>
                <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#" class="nav-link" data-section="students"><i class="fas fa-users"></i> Pelajar</a></li>
                <li><a href="#" class="nav-link" data-section="teachers"><i class="fas fa-chalkboard-user"></i> Guru</a></li>
                <li><a href="#" class="nav-link" data-section="subjects"><i class="fas fa-book"></i> Subjek</a></li>
                <li><a href="#" class="nav-link" data-section="classes"><i class="fas fa-school"></i> Kelas</a></li>
                <li><a href="#" class="nav-link" data-section="exams"><i class="fas fa-calendar"></i> Peperiksaan</a></li>
                <li><a href="#" class="nav-link" data-section="class_subjects"><i class="fas fa-layer-group"></i> Subjek Kelas</a></li>
                <li><a href="#" class="nav-link" data-section="profile"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Keluar</a></li>
            </ul>
        </nav>
        <div class="user-info" onclick="showSection('profile')">
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></div>
            <div><strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br><small>Pentadbir</small></div>
        </div>
    </div>
</header>

<div class="container main-content">
    <!-- Dashboard Section -->
    <section id="dashboard" class="page-section active">
        <h1 class="page-title">Dashboard Pentadbir</h1>
        <div class="dashboard-cards">
            <div class="card" onclick="showSection('students')"><div class="card-title">Jumlah Pelajar</div><div class="card-value"><?php echo $totalStudents; ?></div><i class="fas fa-user-graduate card-icon"></i></div>
            <div class="card" onclick="showSection('teachers')"><div class="card-title">Jumlah Guru</div><div class="card-value"><?php echo $totalTeachers; ?></div><i class="fas fa-chalkboard-user card-icon"></i></div>
            <div class="card" onclick="showSection('subjects')"><div class="card-title">Subjek Aktif</div><div class="card-value"><?php echo $totalSubjects; ?></div><i class="fas fa-book card-icon"></i></div>
            <div class="card" onclick="showSection('exams')"><div class="card-title">Peperiksaan</div><div class="card-value"><?php echo $totalExams; ?></div><i class="fas fa-calendar-alt card-icon"></i></div>
        </div>
        <div class="chart-grid">
            <div class="chart-container"><h3 class="chart-title">Statistik Pengguna</h3><canvas id="userStatsChart" style="height: 250px; width: 100%;"></canvas></div>
            <div class="chart-container"><h3 class="chart-title">Prestasi Akademik</h3><canvas id="academicChart" style="height: 250px; width: 100%;"></canvas></div>
        </div>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-value"><?php echo $avgPng; ?></div><div class="stat-label">PNG Purata</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo $passRate; ?>%</div><div class="stat-label">Kadar Kelulusan</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo $totalClasses; ?></div><div class="stat-label">Kelas Aktif</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo $totalSubjects; ?></div><div class="stat-label">Subjek Aktif</div></div>
        </div>
    </section>

    <!-- Students Section -->
    <section id="students" class="page-section">
        <h1 class="page-title">Pengurusan Pelajar</h1>
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <h3><i class="fas fa-users"></i> Senarai Pelajar</h3>
                <button class="btn btn-success" onclick="openAddStudentModal()"><i class="fas fa-plus"></i> Tambah Pelajar</button>
            </div>
            <div class="filter-row">
                <div class="form-group"><label><i class="fas fa-search"></i> Cari Pelajar</label><input type="text" id="searchStudent" class="form-control" placeholder="Nama / No. Matrik" onkeyup="filterStudents()"></div>
                <div class="form-group"><label><i class="fas fa-filter"></i> Kelas</label><select id="filterClass" class="form-control" onchange="filterStudents()"><option value="">Semua Kelas</option><?php foreach ($allClasses as $c) { ?><option value="<?php echo htmlspecialchars($c['class_name']); ?>"><?php echo htmlspecialchars($c['class_name']); ?></option><?php } ?></select></div>
            </div>
        </div>
        <div class="table-container">
            <table style="min-width: 1000px; width: 100%; border-collapse: collapse;">
                <thead><tr><th style="width: 15%;">No. Matrik</th><th style="width: 25%;">Nama Pelajar</th><th style="width: 15%;">Kelas</th><th style="width: 15%;">No. Telefon</th><th style="width: 20%;">Emel</th><th style="width: 10%;">Tindakan</th></tr></thead>
                <tbody id="studentsTable">
                    <?php if (empty($students)) { ?><tr><td colspan="6" style="text-align:center;">Tiada data pelajar</span></td>
                    <?php } else { ?>
                    <?php foreach ($students as $s) { ?>
                    <tr data-class="<?php echo htmlspecialchars($s['class_name'] ?? ''); ?>" data-name="<?php echo strtolower(htmlspecialchars($s['full_name'])); ?>" data-matric="<?php echo strtolower(htmlspecialchars($s['matric_no'])); ?>">
                        <td><strong><?php echo htmlspecialchars($s['matric_no']); ?></strong></td>
                        <td><i class="fas fa-user-graduate" style="color: #1e3a8a; margin-right: 8px;"></i><?php echo htmlspecialchars($s['full_name']); ?></td>
                        <td><span class="badge badge-class"><?php echo htmlspecialchars($s['class_name'] ?? '-'); ?></span></td>
                        <td><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" 
                                data-id="<?php echo $s['id']; ?>"
                                data-matric="<?php echo htmlspecialchars($s['matric_no']); ?>"
                                data-name="<?php echo htmlspecialchars($s['full_name']); ?>"
                                data-class-id="<?php echo $s['class_id'] ?? 0; ?>"
                                data-phone="<?php echo htmlspecialchars($s['phone'] ?? ''); ?>"
                                data-email="<?php echo htmlspecialchars($s['email'] ?? ''); ?>"
                                data-ic="<?php echo htmlspecialchars($s['ic_number'] ?? ''); ?>"
                                onclick="openEditStudentModal(this)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $s['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                        </td>
                    </tr>
                    <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if (! empty($students)) { ?>
        <div class="filter-section" style="margin-top: 1rem; background: #f8fafc;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div><i class="fas fa-users"></i> <strong>Jumlah Pelajar:</strong> <?php echo count($students); ?> orang</div>
                <div><i class="fas fa-school"></i> <strong>Jumlah Kelas:</strong> <?php echo count(array_unique(array_column($students, 'class_name'))); ?> kelas</div>
            </div>
        </div>
        <?php } ?>
    </section>

    <!-- Teachers Section -->
    <section id="teachers" class="page-section">
        <h1 class="page-title">Pengurusan Guru</h1>
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <h3><i class="fas fa-chalkboard-user"></i> Senarai Guru</h3>
                <button class="btn btn-success" onclick="openAddTeacherModal()"><i class="fas fa-plus"></i> Tambah Guru</button>
            </div>
            <div class="filter-row">
                <div class="form-group"><label><i class="fas fa-search"></i> Cari</label><input type="text" id="searchTeacher" class="form-control" placeholder="Nama / ID Guru" onkeyup="filterTeachers()"></div>
                <div class="form-group"><label><i class="fas fa-filter"></i> Jenis Guru</label><select id="filterTeacherType" class="form-control" onchange="filterTeachers()"><option value="">Semua Jenis</option><option value="guru_kelas">Guru Kelas</option><option value="guru_subjek">Guru Subjek</option></select></div>
            </div>
        </div>
        <div class="table-container">
            <table style="min-width: 800px;">
                <thead><tr><th>ID Guru</th><th>Nama Penuh</th><th>Jenis Guru</th><th>Emel</th><th>No. Telefon</th><th>Status</th><th>Tindakan</th></tr></thead>
                <tbody id="teachersTable">
                    <?php if (empty($teachers)) { ?><td><td colspan="7" style="text-align:center;">Tiada data guru</span></td>
                    <?php } else { ?>
                    <?php foreach ($teachers as $t) { ?>
                    <tr data-type="<?php echo $t['teacher_type']; ?>" data-name="<?php echo strtolower(htmlspecialchars($t['full_name'])); ?>" data-id="<?php echo strtolower(htmlspecialchars($t['teacher_id'])); ?>">
                        <td><strong><?php echo htmlspecialchars($t['teacher_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                        <td><span class="badge <?php echo $t['teacher_type'] == 'guru_kelas' ? 'badge-active' : 'badge-exam'; ?>"><?php echo $t['teacher_type'] == 'guru_kelas' ? 'Guru Kelas' : 'Guru Subjek'; ?></span></span></td>
                        <td><?php echo htmlspecialchars($t['email'] ?? '-'); ?></span></td>
                        <td><?php echo htmlspecialchars($t['phone'] ?? '-'); ?></span></td>
                        <td><span class="badge badge-active"><?php echo $t['status']; ?></span></span></td>
                        <td>
                            <button class="btn btn-sm btn-warning" 
                                data-id="<?php echo $t['id']; ?>"
                                data-teacher-id="<?php echo htmlspecialchars($t['teacher_id']); ?>"
                                data-name="<?php echo htmlspecialchars($t['full_name']); ?>"
                                data-type="<?php echo htmlspecialchars($t['teacher_type']); ?>"
                                data-phone="<?php echo htmlspecialchars($t['phone'] ?? ''); ?>"
                                data-email="<?php echo htmlspecialchars($t['email'] ?? ''); ?>"
                                onclick="openEditTeacherModal(this)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?php echo $t['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                        </td>
                    </tr>
                    <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Subjects Section -->
    <section id="subjects" class="page-section">
        <h1 class="page-title">Pengurusan Subjek</h1>
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3><i class="fas fa-book"></i> Senarai Subjek</h3>
                <button class="btn btn-success" onclick="openAddSubjectModal()"><i class="fas fa-plus"></i> Tambah Subjek</button>
            </div>
            <div class="filter-row">
                <div class="form-group"><label><i class="fas fa-search"></i> Cari Subjek</label><input type="text" id="searchSubject" class="form-control" placeholder="Kod / Nama Subjek" onkeyup="filterSubjects()"></div>
                <div class="form-group"><label><i class="fas fa-filter"></i> Aliran</label><select id="filterSubjectStream" class="form-control" onchange="filterSubjects()"><option value="">Semua Aliran</option><option value="Sains">Sains</option><option value="Sastera">Sastera</option><option value="Teknikal">Teknikal</option><option value="Semua">Semua</option></select></div>
            </div>
        </div>
        <div class="table-container">
            <table style="min-width: 700px;">
                <thead><tr><th>Kod</th><th>Nama Subjek</th><th>Aliran</th><th>Status</th><th>Tindakan</th></tr></thead>
                <tbody id="subjectsTable">
                    <?php if (empty($subjects)) { ?><tr><td colspan="5" style="text-align:center;">Tiada data subjek</span></td>
                    <?php } else { ?>
                    <?php foreach ($subjects as $sub) { ?>
                    <tr data-stream="<?php echo htmlspecialchars($sub['stream']); ?>" data-code="<?php echo strtolower(htmlspecialchars($sub['subject_code'])); ?>" data-name="<?php echo strtolower(htmlspecialchars($sub['subject_name'])); ?>">
                        <td><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                        <td><span class="badge <?php echo $sub['stream'] == 'Sains' ? 'badge-exam' : ($sub['stream'] == 'Sastera' ? 'badge-active' : 'badge-poor'); ?>"><?php echo htmlspecialchars($sub['stream']); ?></span></span></td>
                        <td><span class="badge badge-active"><?php echo $sub['status']; ?></span></span></td>
                        <td>
                            <button class="btn btn-sm btn-warning" 
                                data-id="<?php echo $sub['id']; ?>"
                                data-code="<?php echo htmlspecialchars($sub['subject_code']); ?>"
                                data-name="<?php echo htmlspecialchars($sub['subject_name']); ?>"
                                data-stream="<?php echo htmlspecialchars($sub['stream']); ?>"
                                onclick="openEditSubjectModal(this)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSubject(<?php echo $sub['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                        </td>
                    </tr>
                    <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Classes Section -->
    <section id="classes" class="page-section">
        <h1 class="page-title">Pengurusan Kelas</h1>
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <h3><i class="fas fa-school"></i> Senarai Kelas</h3>
                <button class="btn btn-success" onclick="openAddClassModal()"><i class="fas fa-plus"></i> Tambah Kelas</button>
            </div>
            <div class="filter-row">
                <div class="form-group"><label><i class="fas fa-search"></i> Cari Kelas</label><input type="text" id="searchClass" class="form-control" placeholder="Nama Kelas" onkeyup="filterClasses()"></div>
                <div class="form-group"><label><i class="fas fa-filter"></i> Status</label><select id="filterClassStatus" class="form-control" onchange="filterClasses()"><option value="">Semua Status</option><option value="Aktif">Aktif</option><option value="Tidak Aktif">Tidak Aktif</option></select></div>
            </div>
        </div>
        <div class="table-container">
            <table style="min-width: 800px;">
                <thead><tr><th>Bil</th><th>Nama Kelas</th><th>Bil. Pelajar</th><th>Guru Kelas</th><th>Status</th><th>Tindakan</th></tr></thead>
                <tbody id="classesTable">
                    <?php if (empty($classes)) { ?><td><td colspan="6" style="text-align:center;">Tiada data kelas</span></td>
                    <?php } else { ?>
                    <?php $bil = 1;
                        foreach ($classes as $cls) { ?>
                    <tr data-status="<?php echo $cls['status']; ?>" data-name="<?php echo strtolower(htmlspecialchars($cls['class_name'])); ?>">
                        <td style="text-align:center;"><?php echo $bil++; ?></td>
                        <td><strong><?php echo htmlspecialchars($cls['class_name']); ?></strong></td>
                        <td style="text-align:center;"><span class="badge badge-active"><i class="fas fa-users"></i> <?php echo $cls['student_count']; ?> orang</span></span></td>
                        <td><?php if ($cls['teacher_name']) { ?><div style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-chalkboard-user" style="color: #1e3a8a;"></i><div><strong><?php echo htmlspecialchars($cls['teacher_name']); ?></strong><br><small class="text-muted">ID: <?php echo htmlspecialchars($cls['teacher_code'] ?? '-'); ?></small></div></div><?php } else { ?><span class="badge" style="background:#fef3c7; color:#92400e;"><i class="fas fa-exclamation-triangle"></i> Tiada Guru Kelas</span><?php } ?></span></td>
                        <td style="text-align:center;"><span class="badge <?php echo $cls['status'] == 'Aktif' ? 'badge-active' : 'badge-poor'; ?>"><?php echo $cls['status']; ?></span></span></td>
                        <td style="text-align:center;"><button class="btn btn-sm btn-warning" onclick="openEditClassModal(<?php echo $cls['id']; ?>, '<?php echo htmlspecialchars($cls['class_name']); ?>', <?php echo $cls['teacher_id'] ?? 0; ?>)"><i class="fas fa-edit"></i> Edit</button> <button class="btn btn-sm btn-danger" onclick="deleteClass(<?php echo $cls['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button></span></td>
                    </tr>
                    <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Exams Section -->
    <section id="exams" class="page-section">
        <h1 class="page-title">Pengurusan Peperiksaan</h1>
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                <h3><i class="fas fa-calendar-alt"></i> Senarai Peperiksaan</h3>
                <button class="btn btn-success" onclick="openAddExamModal()"><i class="fas fa-plus"></i> Tambah Peperiksaan</button>
            </div>
            <div class="filter-row">
                <div class="form-group"><label><i class="fas fa-search"></i> Cari</label><input type="text" id="searchExam" class="form-control" placeholder="Nama / Jenis" onkeyup="filterExams()"></div>
                <div class="form-group"><label><i class="fas fa-filter"></i> Status</label><select id="filterExamStatus" class="form-control" onchange="filterExams()"><option value="">Semua Status</option><option value="Aktif">Aktif</option><option value="Draf">Draf</option><option value="Selesai">Selesai</option><option value="Batal">Batal</option></select></div>
            </div>
        </div>
        <div class="table-container">
            <table style="min-width: 1000px;">
                <thead><tr><th>ID</th><th>Nama Peperiksaan</th><th>Jenis</th><th>Semester</th><th>Tahun Akademik</th><th>Tarikh</th><th>Masa</th><th>Tempat</th><th>Markah</th><th>Status</th><th>Tindakan</th></tr></thead>
                <tbody id="examsTable">
                    <?php if (empty($exams)) { ?><td><td colspan="11" style="text-align:center;">Tiada data peperiksaan</span></td>
                    <?php } else { ?>
                    <?php foreach ($exams as $e) { ?>
                    <tr data-status="<?php echo $e['status']; ?>" data-name="<?php echo strtolower(htmlspecialchars($e['exam_name'])); ?>" data-type="<?php echo strtolower(htmlspecialchars($e['type_name'])); ?>">
                        <td style="text-align:center;"><?php echo $e['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($e['exam_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['type_name']); ?> (<?php echo htmlspecialchars($e['exam_code']); ?>)</span></td>
                        <td style="text-align:center;">Semester <?php echo $e['semester']; ?></span></td>
                        <td style="text-align:center;"><?php echo htmlspecialchars($e['academic_year']); ?></span></td>
                        <td style="text-align:center;"><?php echo date('d/m/Y', strtotime($e['exam_date'])); ?></span></td>
                        <td style="text-align:center;"><?php echo date('H:i', strtotime($e['start_time'])).' - '.date('H:i', strtotime($e['end_time'])); ?></span></td>
                        <td><?php echo htmlspecialchars($e['venue'] ?? '-'); ?></span></td>
                        <td style="text-align:center;"><?php echo $e['total_marks']; ?> / <?php echo $e['passing_marks']; ?></span></td>
                        <td style="text-align:center;"><span class="badge <?php echo $e['status'] == 'Aktif' ? 'badge-active' : ($e['status'] == 'Selesai' ? 'badge-exam' : 'badge-poor'); ?>"><?php echo $e['status']; ?></span></td>
                        <td style="text-align:center;">
                            <button class="btn btn-sm btn-warning" 
                                data-id="<?php echo $e['id']; ?>"
                                data-name="<?php echo htmlspecialchars($e['exam_name']); ?>"
                                data-type-id="<?php echo $e['exam_type_id']; ?>"
                                data-semester="<?php echo $e['semester']; ?>"
                                data-academic-year="<?php echo htmlspecialchars($e['academic_year']); ?>"
                                data-exam-date="<?php echo $e['exam_date']; ?>"
                                data-start-time="<?php echo $e['start_time']; ?>"
                                data-end-time="<?php echo $e['end_time']; ?>"
                                data-venue="<?php echo htmlspecialchars($e['venue'] ?? ''); ?>"
                                data-total-marks="<?php echo $e['total_marks']; ?>"
                                data-passing-marks="<?php echo $e['passing_marks']; ?>"
                                data-status="<?php echo $e['status']; ?>"
                                onclick="openEditExamModal(this)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteExam(<?php echo $e['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                        </td>
                    </tr>
                    <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Class Subjects Section (TANPA BUTTON EDIT) -->
    <section id="class_subjects" class="page-section">
        <h1 class="page-title">Pengurusan Subjek Mengikut Kelas</h1>
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <h3><i class="fas fa-layer-group"></i> Senarai Subjek Kelas</h3>
                <button class="btn btn-success" onclick="openAssignSubjectModal()"><i class="fas fa-plus"></i> Assign Subjek ke Kelas</button>
            </div>
            <div class="filter-row">
                <div class="form-group"><label><i class="fas fa-school"></i> Pilih Kelas</label><select id="filterClassSubject" class="form-control" onchange="loadClassSubjects()"><option value="">Semua Kelas</option><?php foreach ($allClasses as $class) { ?><option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option><?php } ?></select></div>
                <div class="form-group"><label><i class="fas fa-calendar-alt"></i> Pilih Semester</label><select id="filterSemesterSubject" class="form-control" onchange="loadClassSubjects()"><option value="">Semua Semester</option><option value="1">Semester 1</option><option value="2">Semester 2</option><option value="3">Semester 3</option></select></div>
            </div>
        </div>
        <div class="table-container">
            <table style="min-width: 800px; width: 100%; border-collapse: collapse;">
                <thead><tr><th style="width: 20%;">Kelas</th><th style="width: 10%;">Semester</th><th style="width: 15%;">Kod Subjek</th><th style="width: 30%;">Nama Subjek</th><th style="width: 20%;">Guru Subjek</th><th style="width: 5%;">Tindakan</th></tr></thead>
                <tbody id="classSubjectsTable">
                    <tr><td colspan="6" style="text-align:center;">Pilih kelas untuk melihat subjek</span></td>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Profile Section -->
    <section id="profile" class="page-section">
        <h1 class="page-title">Profil Pentadbir</h1>
        <div style="max-width: 500px; margin: 0 auto;">
            <div class="filter-section">
                <div style="text-align:center">
                    <div class="avatar" style="width:70px;height:70px;font-size:2rem;margin:0 auto 1rem;"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></div>
                    <h2 id="adminName"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p><span class="badge badge-active">Pentadbir</span></p>
                </div>
                <hr style="margin:1rem 0">
                <div class="form-group"><label>ID Admin</label><input type="text" id="adminId" class="form-control" value="<?php echo htmlspecialchars($user['id_number']); ?>" readonly></div>
                <div class="form-group"><label>Nama Penuh</label><input type="text" id="adminFullName" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>"></div>
                <div class="form-group"><label>Emel</label><input type="email" id="adminEmail" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>"></div>
                <div class="form-group"><label>Telefon</label><input type="text" id="adminPhone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
                <button class="btn btn-warning" style="width:100%" onclick="openChangePasswordModal()"><i class="fas fa-key"></i> Tukar Kata Laluan</button>
            </div>
        </div>
    </section>
</div>

<!-- MODALS -->
<div class="modal" id="addStudentModal"><div class="modal-content"><div class="modal-header"><h3>Tambah Pelajar</h3><button class="close-modal" onclick="closeModal('addStudentModal')">&times;</button></div><div class="modal-body"><form onsubmit="addStudent(event)"><div class="form-group"><label>No. Matrik</label><input type="text" id="newMatric" class="form-control" required></div><div class="form-group"><label>Nama Penuh</label><input type="text" id="newStudentName" class="form-control" required></div><div class="form-group"><label>Kelas</label><select id="newStudentClass" class="form-control"><?php foreach ($allClasses as $c) { ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option><?php } ?></select></div><div class="form-group"><label>Telefon</label><input type="text" id="newStudentPhone" class="form-control"></div><div class="form-group"><label>Emel</label><input type="email" id="newStudentEmail" class="form-control"></div><div class="form-group"><label>No. KP</label><input type="text" id="newStudentIC" class="form-control"></div><button type="submit" class="btn btn-success">Simpan</button></form></div></div></div>

<div class="modal" id="addTeacherModal"><div class="modal-content"><div class="modal-header"><h3>Tambah Guru</h3><button class="close-modal" onclick="closeModal('addTeacherModal')">&times;</button></div><div class="modal-body"><form onsubmit="addTeacher(event)"><div class="form-group"><label>ID Guru</label><input type="text" id="newTeacherId" class="form-control" required></div><div class="form-group"><label>Nama Penuh</label><input type="text" id="newTeacherName" class="form-control" required></div><div class="form-group"><label>Jenis Guru</label><select id="newTeacherType" class="form-control"><option value="guru_subjek">Guru Subjek</option><option value="guru_kelas">Guru Kelas</option></select></div><div class="form-group"><label>Emel</label><input type="email" id="newTeacherEmail" class="form-control" required></div><div class="form-group"><label>Telefon</label><input type="text" id="newTeacherPhone" class="form-control"></div><button type="submit" class="btn btn-success">Simpan</button></form></div></div></div>

<div class="modal" id="addSubjectModal"><div class="modal-content"><div class="modal-header"><h3>Tambah Subjek</h3><button class="close-modal" onclick="closeModal('addSubjectModal')">&times;</button></div><div class="modal-body"><form onsubmit="addSubject(event)"><div class="form-group"><label>Kod Subjek</label><input type="text" id="newSubjectCode" class="form-control" required></div><div class="form-group"><label>Nama Subjek</label><input type="text" id="newSubjectName" class="form-control" required></div><div class="form-group"><label>Aliran</label><select id="newSubjectStream" class="form-control"><option>Sains</option><option>Sastera</option><option>Teknikal</option><option>Semua</option></select></div><button type="submit" class="btn btn-success">Simpan</button></form></div></div></div>

<div class="modal" id="addClassModal"><div class="modal-content"><div class="modal-header"><h3>Tambah Kelas</h3><button class="close-modal" onclick="closeModal('addClassModal')">&times;</button></div><div class="modal-body"><form onsubmit="addClass(event)"><div class="form-group"><label>Nama Kelas</label><input type="text" id="newClassName" class="form-control" required></div><div class="form-group"><label>Guru Kelas</label><select id="newClassTeacher" class="form-control"><option value="">-- Tiada Guru Kelas --</option><?php foreach ($allTeachers as $t) { ?><option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name'].' ('.$t['teacher_id'].')'); ?></option><?php } ?></select></div><button type="submit" class="btn btn-success">Simpan</button></form></div></div></div>

<div class="modal" id="editClassModal"><div class="modal-content"><div class="modal-header"><h3>Edit Kelas</h3><button class="close-modal" onclick="closeModal('editClassModal')">&times;</button></div><div class="modal-body"><form onsubmit="editClass(event)"><input type="hidden" id="editClassId"><div class="form-group"><label>Nama Kelas</label><input type="text" id="editClassName" class="form-control" required></div><div class="form-group"><label>Guru Kelas</label><select id="editClassTeacher" class="form-control"><option value="">-- Tiada Guru Kelas --</option><?php foreach ($allTeachers as $t) { ?><option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name'].' ('.$t['teacher_id'].')'); ?></option><?php } ?></select></div><button type="submit" class="btn btn-success">Kemaskini</button></form></div></div></div>

<!-- Edit Student Modal -->
<div class="modal" id="editStudentModal"><div class="modal-content"><div class="modal-header"><h3>Edit Pelajar</h3><button class="close-modal" onclick="closeModal('editStudentModal')">&times;</button></div><div class="modal-body"><form onsubmit="editStudent(event)"><input type="hidden" id="editStudentId"><div class="form-group"><label>No. Matrik</label><input type="text" id="editStudentMatric" class="form-control" required></div><div class="form-group"><label>Nama Penuh</label><input type="text" id="editStudentName" class="form-control" required></div><div class="form-group"><label>Kelas</label><select id="editStudentClass" class="form-control"><?php foreach ($allClasses as $c) { ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option><?php } ?></select></div><div class="form-group"><label>Telefon</label><input type="text" id="editStudentPhone" class="form-control"></div><div class="form-group"><label>Emel</label><input type="email" id="editStudentEmail" class="form-control"></div><div class="form-group"><label>No. KP</label><input type="text" id="editStudentIC" class="form-control"></div><button type="submit" class="btn btn-success">Kemaskini</button></form></div></div></div>

<!-- Edit Teacher Modal -->
<div class="modal" id="editTeacherModal"><div class="modal-content"><div class="modal-header"><h3>Edit Guru</h3><button class="close-modal" onclick="closeModal('editTeacherModal')">&times;</button></div><div class="modal-body"><form onsubmit="editTeacher(event)"><input type="hidden" id="editTeacherIdPk"><div class="form-group"><label>ID Guru</label><input type="text" id="editTeacherId" class="form-control" required></div><div class="form-group"><label>Nama Penuh</label><input type="text" id="editTeacherName" class="form-control" required></div><div class="form-group"><label>Jenis Guru</label><select id="editTeacherType" class="form-control"><option value="guru_subjek">Guru Subjek</option><option value="guru_kelas">Guru Kelas</option></select></div><div class="form-group"><label>Emel</label><input type="email" id="editTeacherEmail" class="form-control" required></div><div class="form-group"><label>Telefon</label><input type="text" id="editTeacherPhone" class="form-control"></div><button type="submit" class="btn btn-success">Kemaskini</button></form></div></div></div>

<!-- Edit Subject Modal -->
<div class="modal" id="editSubjectModal"><div class="modal-content"><div class="modal-header"><h3>Edit Subjek</h3><button class="close-modal" onclick="closeModal('editSubjectModal')">&times;</button></div><div class="modal-body"><form onsubmit="editSubject(event)"><input type="hidden" id="editSubjectId"><div class="form-group"><label>Kod Subjek</label><input type="text" id="editSubjectCode" class="form-control" required></div><div class="form-group"><label>Nama Subjek</label><input type="text" id="editSubjectName" class="form-control" required></div><div class="form-group"><label>Aliran</label><select id="editSubjectStream" class="form-control"><option value="Sains">Sains</option><option value="Sastera">Sastera</option><option value="Teknikal">Teknikal</option><option value="Semua">Semua</option></select></div><button type="submit" class="btn btn-success">Kemaskini</button></form></div></div></div>

<!-- Edit Exam Modal -->
<div class="modal" id="editExamModal"><div class="modal-content" style="max-width: 600px;"><div class="modal-header"><h3>Edit Peperiksaan</h3><button class="close-modal" onclick="closeModal('editExamModal')">&times;</button></div><div class="modal-body"><form onsubmit="editExam(event)"><input type="hidden" id="editExamId"><div class="row-2cols"><div class="form-group"><label>Nama Peperiksaan <span style="color:red;">*</span></label><input type="text" id="editExamName" class="form-control" required></div><div class="form-group"><label>Jenis Peperiksaan <span style="color:red;">*</span></label><select id="editExamType" class="form-control" required><option value="">Pilih Jenis</option><?php foreach ($examTypes as $et) { ?><option value="<?php echo $et['id']; ?>"><?php echo htmlspecialchars($et['exam_name'].' ('.$et['exam_code'].')'); ?></option><?php } ?></select></div></div><div class="row-2cols"><div class="form-group"><label>Semester <span style="color:red;">*</span></label><select id="editExamSemester" class="form-control" required><option value="1">Semester 1</option><option value="2">Semester 2</option><option value="3">Semester 3</option></select></div><div class="form-group"><label>Tahun Akademik <span style="color:red;">*</span></label><input type="text" id="editExamYear" class="form-control" required></div></div><div class="row-2cols"><div class="form-group"><label>Tarikh Peperiksaan <span style="color:red;">*</span></label><input type="date" id="editExamDate" class="form-control" required></div><div class="form-group"><label>Tempat</label><input type="text" id="editExamVenue" class="form-control"></div></div><div class="row-2cols"><div class="form-group"><label>Masa Mula <span style="color:red;">*</span></label><input type="time" id="editExamStart" class="form-control" required></div><div class="form-group"><label>Masa Tamat <span style="color:red;">*</span></label><input type="time" id="editExamEnd" class="form-control" required></div></div><div class="row-2cols"><div class="form-group"><label>Jumlah Markah</label><input type="number" id="editExamTotalMarks" class="form-control" min="0" max="100"></div><div class="form-group"><label>Markah Lulus</label><input type="number" id="editExamPassing" class="form-control" min="0" max="100"></div></div><div class="form-group"><label>Status</label><select id="editExamStatus" class="form-control"><option value="Draf">Draf</option><option value="Aktif">Aktif</option><option value="Selesai">Selesai</option><option value="Batal">Batal</option></select></div><button type="submit" class="btn btn-success" style="margin-top: 10px;"><i class="fas fa-save"></i> Kemaskini</button></form></div></div></div>

<div class="modal" id="addExamModal"><div class="modal-content" style="max-width: 600px;"><div class="modal-header"><h3>Tambah Peperiksaan</h3><button class="close-modal" onclick="closeModal('addExamModal')">&times;</button></div><div class="modal-body"><form onsubmit="addExam(event)"><div class="row-2cols"><div class="form-group"><label>Nama Peperiksaan <span style="color:red;">*</span></label><input type="text" id="newExamName" class="form-control" placeholder="cth: Ujian Bulanan 1" required></div><div class="form-group"><label>Jenis Peperiksaan <span style="color:red;">*</span></label><select id="newExamType" class="form-control" required><option value="">Pilih Jenis</option><?php foreach ($examTypes as $et) { ?><option value="<?php echo $et['id']; ?>"><?php echo htmlspecialchars($et['exam_name'].' ('.$et['exam_code'].')'); ?></option><?php } ?></select></div></div><div class="row-2cols"><div class="form-group"><label>Semester <span style="color:red;">*</span></label><select id="newExamSemester" class="form-control" required><option value="1">Semester 1</option><option value="2">Semester 2</option><option value="3">Semester 3</option></select></div><div class="form-group"><label>Tahun Akademik <span style="color:red;">*</span></label><input type="text" id="newExamYear" class="form-control" placeholder="2024/2025" required><small>Contoh: 2024/2025</small></div></div><div class="row-2cols"><div class="form-group"><label>Tarikh Peperiksaan <span style="color:red;">*</span></label><input type="date" id="newExamDate" class="form-control" required></div><div class="form-group"><label>Tempat</label><input type="text" id="newExamVenue" class="form-control" placeholder="Dewan Utama / Bilik Darjah"></div></div><div class="row-2cols"><div class="form-group"><label>Masa Mula <span style="color:red;">*</span></label><input type="time" id="newExamStart" class="form-control" required></div><div class="form-group"><label>Masa Tamat <span style="color:red;">*</span></label><input type="time" id="newExamEnd" class="form-control" required></div></div><div class="row-2cols"><div class="form-group"><label>Jumlah Markah</label><input type="number" id="newExamTotalMarks" class="form-control" value="100" min="0" max="100"></div><div class="form-group"><label>Markah Lulus</label><input type="number" id="newExamPassing" class="form-control" value="50" min="0" max="100"></div></div><div class="alert alert-info" style="font-size: 0.8rem; margin-top: 1rem;"><i class="fas fa-info-circle"></i> <span>Peperiksaan akan disimpan dengan status "Aktif".</span></div><button type="submit" class="btn btn-success" style="margin-top: 10px;"><i class="fas fa-save"></i> Simpan</button></form></div></div></div>

<div class="modal" id="assignSubjectModal"><div class="modal-content"><div class="modal-header"><h3>Assign Subjek ke Kelas</h3><button class="close-modal" onclick="closeModal('assignSubjectModal')">&times;</button></div><div class="modal-body"><form onsubmit="assignSubjectToClass(event)"><div class="form-group"><label>Pilih Kelas</label><select id="assignClass" class="form-control" required><option value="">-- Pilih Kelas --</option><?php foreach ($allClasses as $class) { ?><option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option><?php } ?></select></div><div class="form-group"><label>Pilih Subjek</label><select id="assignSubject" class="form-control" required><option value="">-- Pilih Subjek --</option><?php foreach ($allSubjects as $subject) { ?><option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_code'].' - '.$subject['subject_name']); ?></option><?php } ?></select></div><div class="form-group"><label>Pilih Guru Subjek</label><select id="assignTeacher" class="form-control" required><option value="">-- Pilih Guru Subjek --</option><?php foreach ($allTeachers as $teacher) { ?><option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name'].' ('.$teacher['teacher_id'].')'); ?></option><?php } ?></select></div><div class="form-group"><label>Semester</label><select id="assignSemester" class="form-control" required><option value="1">Semester 1</option><option value="2">Semester 2</option><option value="3">Semester 3</option></select></div><button type="submit" class="btn btn-success">Assign</button></form></div></div></div>

<div class="modal" id="changePasswordModal"><div class="modal-content"><div class="modal-header"><h3>Tukar Kata Laluan</h3><button class="close-modal" onclick="closeModal('changePasswordModal')">&times;</button></div><div class="modal-body"><form onsubmit="changePassword(event)"><div class="form-group"><label>Kata Laluan Semasa</label><input type="password" id="currentPwd" class="form-control" required></div><div class="form-group"><label>Kata Laluan Baru</label><input type="password" id="newPwd" class="form-control" required></div><div class="form-group"><label>Sahkan</label><input type="password" id="confirmPwd" class="form-control" required></div><button type="submit" class="btn btn-success">Tukar</button></form></div></div></div>

<footer><div class="container"><p>&copy; 2025 Sistem Peperiksaan STPM</p></div></footer>

<script>
// ======================================================
// VARIABLES
// ======================================================
const allClasses = <?php echo json_encode($allClasses); ?>;

// ======================================================
// CHARTS INITIALIZATION
// ======================================================
function initCharts() {
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

    const userStats = [<?php echo $totalStudents; ?>, <?php echo $totalTeachers; ?>];
    const userCtx = document.getElementById('userStatsChart');
    if (userCtx) { 
        new Chart(userCtx, { 
            type: 'doughnut', 
            data: { 
                labels: ['Pelajar', 'Guru'], 
                datasets: [{ 
                    data: userStats, 
                    backgroundColor: ['#6366f1', '#10b981'], 
                    borderWidth: 2,
                    borderColor: '#0f172a'
                }] 
            }, 
            options: { 
                responsive: true, 
                maintainAspectRatio: true, 
                plugins: { legend: { position: 'bottom', labels: { color: '#cbd5e1', boxWidth: 12, padding: 15 } } } 
            } 
        }); 
    }

    const academicCtx = document.getElementById('academicChart');
    if (academicCtx) { 
        new Chart(academicCtx, { 
            type: 'bar', 
            data: { 
                labels: ['PNG Purata', 'Kadar Kelulusan (%)'], 
                datasets: [{ 
                    label: 'Prestasi', 
                    data: [<?php echo $avgPng; ?>, <?php echo $passRate; ?>], 
                    backgroundColor: ['#818cf8', '#f59e0b'], 
                    borderRadius: 8,
                    borderWidth: 1,
                    borderColor: '#6366f1'
                }] 
            }, 
            options: { 
                responsive: true, 
                maintainAspectRatio: true, 
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255, 255, 255, 0.06)' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }, 
                plugins: { legend: { labels: { color: '#cbd5e1', font: { weight: 'bold' } } } } 
            } 
        }); 
    }
}

// ======================================================
// NAVIGATION
// ======================================================
function showSection(sectionId) {
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    document.getElementById(sectionId).classList.add('active');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    let active = document.querySelector(`.nav-link[data-section="${sectionId}"]`);
    if(active) active.classList.add('active');
}

// ======================================================
// FILTER FUNCTIONS
// ======================================================
function filterStudents() {
    let search = document.getElementById('searchStudent').value.toLowerCase();
    let kelas = document.getElementById('filterClass').value;
    let rows = document.querySelectorAll('#studentsTable tr');
    rows.forEach(row => {
        let name = row.getAttribute('data-name') || '';
        let matric = row.getAttribute('data-matric') || '';
        let rowKelas = row.getAttribute('data-class') || '';
        let show = true;
        if(search && !name.includes(search) && !matric.includes(search)) show = false;
        if(kelas && rowKelas !== kelas) show = false;
        row.style.display = show ? '' : 'none';
    });
}

function filterTeachers() {
    let search = document.getElementById('searchTeacher').value.toLowerCase();
    let type = document.getElementById('filterTeacherType').value;
    let rows = document.querySelectorAll('#teachersTable tr');
    rows.forEach(row => {
        let name = row.getAttribute('data-name') || '';
        let id = row.getAttribute('data-id') || '';
        let rowType = row.getAttribute('data-type') || '';
        let show = true;
        if(search && !name.includes(search) && !id.includes(search)) show = false;
        if(type && rowType !== type) show = false;
        row.style.display = show ? '' : 'none';
    });
}

function filterSubjects() {
    const search = document.getElementById('searchSubject').value.toLowerCase();
    const stream = document.getElementById('filterSubjectStream').value;
    const rows = document.querySelectorAll('#subjectsTable tr');
    rows.forEach(row => {
        const code = row.getAttribute('data-code') || '';
        const name = row.getAttribute('data-name') || '';
        const rowStream = row.getAttribute('data-stream') || '';
        let show = true;
        if (search && !code.includes(search) && !name.includes(search)) show = false;
        if (stream && rowStream !== stream) show = false;
        row.style.display = show ? '' : 'none';
    });
}

function filterClasses() {
    const search = document.getElementById('searchClass').value.toLowerCase();
    const status = document.getElementById('filterClassStatus').value;
    const rows = document.querySelectorAll('#classesTable tr');
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        let show = true;
        if (search && !name.includes(search)) show = false;
        if (status && rowStatus !== status) show = false;
        row.style.display = show ? '' : 'none';
    });
}

function filterExams() {
    const search = document.getElementById('searchExam').value.toLowerCase();
    const status = document.getElementById('filterExamStatus').value;
    const rows = document.querySelectorAll('#examsTable tr');
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const type = row.getAttribute('data-type') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        let show = true;
        if (search && !name.includes(search) && !type.includes(search)) show = false;
        if (status && rowStatus !== status) show = false;
        row.style.display = show ? '' : 'none';
    });
}

// ======================================================
// MODAL FUNCTIONS
// ======================================================
function openAddStudentModal() { document.getElementById('addStudentModal').style.display = 'flex'; }
function openAddTeacherModal() { document.getElementById('addTeacherModal').style.display = 'flex'; }
function openAddSubjectModal() { document.getElementById('addSubjectModal').style.display = 'flex'; }
function openAddClassModal() { document.getElementById('addClassModal').style.display = 'flex'; }
function openAddExamModal() { document.getElementById('addExamModal').style.display = 'flex'; }
function openChangePasswordModal() { document.getElementById('changePasswordModal').style.display = 'flex'; }
function openAssignSubjectModal() { document.getElementById('assignSubjectModal').style.display = 'flex'; }
function openEditClassModal(id, name, teacherId) {
    document.getElementById('editClassId').value = id;
    document.getElementById('editClassName').value = name;
    document.getElementById('editClassTeacher').value = teacherId || '';
    document.getElementById('editClassModal').style.display = 'flex';
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// ======================================================
// CRUD FUNCTIONS
// ======================================================
function addStudent(e) { e.preventDefault(); let fd = new FormData(); fd.append('action', 'add_student'); fd.append('matric', document.getElementById('newMatric').value); fd.append('name', document.getElementById('newStudentName').value); fd.append('class_id', document.getElementById('newStudentClass').value); fd.append('phone', document.getElementById('newStudentPhone').value); fd.append('email', document.getElementById('newStudentEmail').value); fd.append('ic_number', document.getElementById('newStudentIC').value); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addStudentModal'); document.getElementById('addStudentModal').querySelector('form').reset(); setTimeout(()=>location.reload(),1000); } else { showNotification(d.message, 'error'); } }); }
function addTeacher(e) { e.preventDefault(); let fd = new FormData(); fd.append('action', 'add_teacher'); fd.append('teacher_id', document.getElementById('newTeacherId').value); fd.append('name', document.getElementById('newTeacherName').value); fd.append('type', document.getElementById('newTeacherType').value); fd.append('email', document.getElementById('newTeacherEmail').value); fd.append('phone', document.getElementById('newTeacherPhone').value); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addTeacherModal'); document.getElementById('addTeacherModal').querySelector('form').reset(); setTimeout(()=>location.reload(),1000); } else { showNotification(d.message, 'error'); } }); }
function addSubject(e) { e.preventDefault(); let fd = new FormData(); fd.append('action', 'add_subject'); fd.append('code', document.getElementById('newSubjectCode').value); fd.append('name', document.getElementById('newSubjectName').value); fd.append('stream', document.getElementById('newSubjectStream').value); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addSubjectModal'); document.getElementById('addSubjectModal').querySelector('form').reset(); setTimeout(()=>location.reload(),1000); } else { showNotification(d.message, 'error'); } }); }
function addClass(e) { e.preventDefault(); let fd = new FormData(); fd.append('action', 'add_class'); fd.append('class_name', document.getElementById('newClassName').value); fd.append('teacher_id', document.getElementById('newClassTeacher').value); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addClassModal'); document.getElementById('addClassModal').querySelector('form').reset(); setTimeout(()=>location.reload(),1000); } else { showNotification(d.message, 'error'); } }); }
function editClass(e) { e.preventDefault(); let fd = new FormData(); fd.append('action', 'edit_class'); fd.append('class_id', document.getElementById('editClassId').value); fd.append('class_name', document.getElementById('editClassName').value); fd.append('teacher_id', document.getElementById('editClassTeacher').value); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('editClassModal'); setTimeout(()=>location.reload(),1000); } else { showNotification(d.message, 'error'); } }); }
function addExam(e) { e.preventDefault(); let fd = new FormData(); fd.append('action', 'add_exam'); fd.append('exam_name', document.getElementById('newExamName').value); fd.append('exam_type_id', document.getElementById('newExamType').value); fd.append('semester', document.getElementById('newExamSemester').value); fd.append('academic_year', document.getElementById('newExamYear').value); fd.append('exam_date', document.getElementById('newExamDate').value); fd.append('start_time', document.getElementById('newExamStart').value); fd.append('end_time', document.getElementById('newExamEnd').value); fd.append('venue', document.getElementById('newExamVenue').value); fd.append('total_marks', document.getElementById('newExamTotalMarks').value); fd.append('passing_marks', document.getElementById('newExamPassing').value); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addExamModal'); document.getElementById('addExamModal').querySelector('form').reset(); setTimeout(()=>location.reload(),1000); } else { showNotification(d.message, 'error'); } }); }

function deleteStudent(id) { if(confirm('Padam pelajar ini?')) { let fd = new FormData(); fd.append('action', 'delete_student'); fd.append('id', id); fetch(window.location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification(d.message,'success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
function deleteTeacher(id) { if(confirm('Padam guru ini?')) { let fd = new FormData(); fd.append('action', 'delete_teacher'); fd.append('id', id); fetch(window.location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification(d.message,'success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
function deleteSubject(id) { if(confirm('Padam subjek ini?')) { let fd = new FormData(); fd.append('action', 'delete_subject'); fd.append('id', id); fetch(window.location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification(d.message,'success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
function deleteClass(id) { if(confirm('Padam kelas ini?')) { let fd = new FormData(); fd.append('action', 'delete_class'); fd.append('id', id); fetch(window.location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification(d.message,'success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
function deleteExam(id) { if(confirm('Batalkan peperiksaan ini?')) { let fd = new FormData(); fd.append('action', 'delete_exam'); fd.append('id', id); fetch(window.location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification(d.message,'success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }

function changePassword(e) { e.preventDefault(); let newPwd = document.getElementById('newPwd').value; let confirmPwd = document.getElementById('confirmPwd').value; if(newPwd !== confirmPwd) { showNotification('Kata laluan tidak sepadan!','error'); return; } if(newPwd.length < 6) { showNotification('Minimum 6 aksara!','error'); return; } let fd = new FormData(); fd.append('action', 'change_password'); fd.append('current_password', document.getElementById('currentPwd').value); fd.append('new_password', newPwd); fetch(window.location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification(d.message,'success'); closeModal('changePasswordModal'); e.target.reset(); } else showNotification(d.message,'error'); }); }
function updateProfile() { let fd = new FormData(); fd.append('action', 'update_profile'); fd.append('name', document.getElementById('adminFullName').value); fd.append('email', document.getElementById('adminEmail').value); fd.append('phone', document.getElementById('adminPhone').value); fetch(window.location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification(d.message,'success'); document.getElementById('adminName').textContent = document.getElementById('adminFullName').value; } else showNotification(d.message,'error'); }); }

// ======================================================
// CLASS SUBJECTS FUNCTIONS (TANPA BUTTON EDIT)
// ======================================================
function loadClassSubjects() {
    const classId = document.getElementById('filterClassSubject').value;
    const semester = document.getElementById('filterSemesterSubject').value;
    const tbody = document.getElementById('classSubjectsTable');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Memuatkan data...</span></td>';
    
    const fd = new FormData();
    fd.append('action', 'get_class_subjects');
    if (classId) fd.append('class_id', classId);
    if (semester) fd.append('semester', semester);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Tiada subjek untuk kelas ini</span></td>';
                return;
            }
            
            let html = '';
            data.data.forEach(cs => {
                const teacherName = cs.teacher_name ? cs.teacher_name : '<span class="badge" style="background:#fef3c7;">Tiada Guru</span>';
                html += `
                    <tr>
                        <td><strong>${escapeHtml(cs.class_name)}</strong></td>
                        <td>Semester ${cs.semester}</td>
                        <td>${escapeHtml(cs.subject_code)}</span></td>
                        <td>${escapeHtml(cs.subject_name)}</span></td>
                        <td>${teacherName}</td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="removeClassSubject(${cs.id})">
                                <i class="fas fa-trash"></i> Buang
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Gagal memuatkan data</span></td>';
            showNotification(data.message || 'Gagal memuatkan data', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Ralat memuatkan data</span></tr>';
        showNotification('Ralat memuatkan data', 'error');
    });
}

function assignSubjectToClass(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'assign_subject_to_class');
    fd.append('class_id', document.getElementById('assignClass').value);
    fd.append('subject_id', document.getElementById('assignSubject').value);
    fd.append('teacher_id', document.getElementById('assignTeacher').value);
    fd.append('semester', document.getElementById('assignSemester').value);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Subjek berjaya diassign!', 'success');
            closeModal('assignSubjectModal');
            document.getElementById('assignSubjectModal').querySelector('form').reset();
            loadClassSubjects();
        } else {
            showNotification(data.message, 'error');
        }
    });
}

function removeClassSubject(id) {
    if (confirm('Buang subjek ini dari kelas?')) {
        const fd = new FormData();
        fd.append('action', 'remove_class_subject');
        fd.append('id', id);
        
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Subjek dibuang dari kelas', 'success');
                loadClassSubjects();
            } else {
                showNotification(data.message, 'error');
            }
        });
    }
}

// ======================================================
// UTILITIES
// ======================================================
function openEditStudentModal(btn) {
    const d = btn.dataset;
    document.getElementById('editStudentId').value = d.id;
    document.getElementById('editStudentMatric').value = d.matric;
    document.getElementById('editStudentName').value = d.name;
    document.getElementById('editStudentClass').value = d.classId;
    document.getElementById('editStudentPhone').value = d.phone;
    document.getElementById('editStudentEmail').value = d.email;
    document.getElementById('editStudentIC').value = d.ic;
    document.getElementById('editStudentModal').style.display = 'flex';
}

function editStudent(e) {
    e.preventDefault();
    let fd = new FormData();
    fd.append('action', 'edit_student');
    fd.append('id', document.getElementById('editStudentId').value);
    fd.append('matric', document.getElementById('editStudentMatric').value);
    fd.append('name', document.getElementById('editStudentName').value);
    fd.append('class_id', document.getElementById('editStudentClass').value);
    fd.append('phone', document.getElementById('editStudentPhone').value);
    fd.append('email', document.getElementById('editStudentEmail').value);
    fd.append('ic_number', document.getElementById('editStudentIC').value);
    fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(r => r.json()).then(d => {
        if(d.success) {
            showNotification(d.message, 'success');
            closeModal('editStudentModal');
            setTimeout(()=>location.reload(), 1000);
        } else {
            showNotification(d.message, 'error');
        }
    });
}

function openEditTeacherModal(btn) {
    const d = btn.dataset;
    document.getElementById('editTeacherIdPk').value = d.id;
    document.getElementById('editTeacherId').value = d.teacherId;
    document.getElementById('editTeacherName').value = d.name;
    document.getElementById('editTeacherType').value = d.type;
    document.getElementById('editTeacherPhone').value = d.phone;
    document.getElementById('editTeacherEmail').value = d.email;
    document.getElementById('editTeacherModal').style.display = 'flex';
}

function editTeacher(e) {
    e.preventDefault();
    let fd = new FormData();
    fd.append('action', 'edit_teacher');
    fd.append('id', document.getElementById('editTeacherIdPk').value);
    fd.append('teacher_id', document.getElementById('editTeacherId').value);
    fd.append('name', document.getElementById('editTeacherName').value);
    fd.append('type', document.getElementById('editTeacherType').value);
    fd.append('email', document.getElementById('editTeacherEmail').value);
    fd.append('phone', document.getElementById('editTeacherPhone').value);
    fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(r => r.json()).then(d => {
        if(d.success) {
            showNotification(d.message, 'success');
            closeModal('editTeacherModal');
            setTimeout(()=>location.reload(), 1000);
        } else {
            showNotification(d.message, 'error');
        }
    });
}

function openEditSubjectModal(btn) {
    const d = btn.dataset;
    document.getElementById('editSubjectId').value = d.id;
    document.getElementById('editSubjectCode').value = d.code;
    document.getElementById('editSubjectName').value = d.name;
    document.getElementById('editSubjectStream').value = d.stream;
    document.getElementById('editSubjectModal').style.display = 'flex';
}

function editSubject(e) {
    e.preventDefault();
    let fd = new FormData();
    fd.append('action', 'edit_subject');
    fd.append('id', document.getElementById('editSubjectId').value);
    fd.append('code', document.getElementById('editSubjectCode').value);
    fd.append('name', document.getElementById('editSubjectName').value);
    fd.append('stream', document.getElementById('editSubjectStream').value);
    fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(r => r.json()).then(d => {
        if(d.success) {
            showNotification(d.message, 'success');
            closeModal('editSubjectModal');
            setTimeout(()=>location.reload(), 1000);
        } else {
            showNotification(d.message, 'error');
        }
    });
}

function openEditExamModal(btn) {
    const d = btn.dataset;
    document.getElementById('editExamId').value = d.id;
    document.getElementById('editExamName').value = d.name;
    document.getElementById('editExamType').value = d.typeId;
    document.getElementById('editExamSemester').value = d.semester;
    document.getElementById('editExamYear').value = d.academicYear;
    document.getElementById('editExamDate').value = d.examDate;
    document.getElementById('editExamStart').value = d.startTime;
    document.getElementById('editExamEnd').value = d.endTime;
    document.getElementById('editExamVenue').value = d.venue;
    document.getElementById('editExamTotalMarks').value = d.totalMarks;
    document.getElementById('editExamPassing').value = d.passingMarks;
    document.getElementById('editExamStatus').value = d.status;
    document.getElementById('editExamModal').style.display = 'flex';
}

function editExam(e) {
    e.preventDefault();
    let fd = new FormData();
    fd.append('action', 'edit_exam');
    fd.append('id', document.getElementById('editExamId').value);
    fd.append('exam_name', document.getElementById('editExamName').value);
    fd.append('exam_type_id', document.getElementById('editExamType').value);
    fd.append('semester', document.getElementById('editExamSemester').value);
    fd.append('academic_year', document.getElementById('editExamYear').value);
    fd.append('exam_date', document.getElementById('editExamDate').value);
    fd.append('start_time', document.getElementById('editExamStart').value);
    fd.append('end_time', document.getElementById('editExamEnd').value);
    fd.append('venue', document.getElementById('editExamVenue').value);
    fd.append('total_marks', document.getElementById('editExamTotalMarks').value);
    fd.append('passing_marks', document.getElementById('editExamPassing').value);
    fd.append('status', document.getElementById('editExamStatus').value);
    fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(r => r.json()).then(d => {
        if(d.success) {
            showNotification(d.message, 'success');
            closeModal('editExamModal');
            setTimeout(()=>location.reload(), 1000);
        } else {
            showNotification(d.message, 'error');
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
function showNotification(msg, type) { let toast = document.createElement('div'); toast.className = 'toast-notification'; toast.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'; toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> ${msg}`; document.body.appendChild(toast); setTimeout(() => toast.remove(), 3000); }

// ======================================================
// EVENT LISTENERS
// ======================================================
document.getElementById('adminFullName')?.addEventListener('change', updateProfile);
document.getElementById('adminEmail')?.addEventListener('change', updateProfile);
document.getElementById('adminPhone')?.addEventListener('change', updateProfile);
document.querySelectorAll('.nav-link').forEach(l => { l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); }); });
document.addEventListener('DOMContentLoaded', () => { showSection('dashboard'); loadClassSubjects(); initCharts(); });
window.onclick = e => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
</script>
</body>
</html>