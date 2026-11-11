<?php
// ======================================================
// FAIL PANEL SUPER ADMIN (LENGKAP)
// Sistem Peperiksaan STPM
// ======================================================

require_once 'config.php';
checkLogin();
checkRole(['admin']);

$user = getCurrentUser();

// Check if user is super admin
if ($user['sub_role'] !== 'super_admin') {
    header('Location: admin.php');
    exit();
}

// ======================================================
// PROSES AJAX REQUEST
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Tindakan tidak sah'];

    // Add Admin
    if ($action === 'add_admin') {
        $admin_id = validateAndSanitize($_POST['admin_id'] ?? '');
        $name = validateAndSanitize($_POST['name'] ?? '');
        $email = validateAndSanitize($_POST['email'] ?? '', 'email');
        $phone = validateAndSanitize($_POST['phone'] ?? '');
        $admin_level = validateAndSanitize($_POST['admin_level'] ?? 'admin');
        $password = $_POST['password'] ?? '';
        $security_question = validateAndSanitize($_POST['security_question'] ?? 0, 'int');
        $security_answer = validateAndSanitize($_POST['security_answer'] ?? '');

        if (empty($admin_id) || empty($name) || empty($email) || empty($password)) {
            $response['message'] = 'Sila lengkapkan maklumat required (ID, Nama, Emel, Kata Laluan)';
            echo json_encode($response);
            exit;
        }
        if (strlen($password) < 6) {
            $response['message'] = 'Kata laluan minimum 6 aksara';
            echo json_encode($response);
            exit;
        }
        if ($security_question <= 0 || empty($security_answer)) {
            $response['message'] = 'Sila pilih soalan keselamatan dan masukkan jawapan';
            echo json_encode($response);
            exit;
        }

        $check = $conn->prepare('SELECT id FROM admins WHERE admin_id = ?');
        $check->bind_param('s', $admin_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $response['message'] = 'ID Admin sudah wujud';
            echo json_encode($response);
            exit;
        }

        $conn->begin_transaction();
        try {
            $username = $admin_id;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin';
            $security_answer_hash = md5(strtolower(trim($security_answer)));

            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, sub_role, security_question_id, security_answer_hash, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')");
            $stmt->bind_param('sssssis', $username, $hashed_password, $email, $role, $admin_level, $security_question, $security_answer_hash);
            $stmt->execute();
            $user_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO admins (user_id, admin_id, full_name, admin_level, phone, status) VALUES (?, ?, ?, ?, ?, 'Aktif')");
            $stmt->bind_param('issss', $user_id, $admin_id, $name, $admin_level, $phone);
            $stmt->execute();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Admin berjaya ditambah. Kata laluan: '.$password;
            logActivity($user['id'], 'ADD_ADMIN', "Menambah admin: $admin_id - $name");
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Ralat: '.$e->getMessage();
        }
        echo json_encode($response);
        exit;
    }

    // Add Teacher
    if ($action === 'add_teacher') {
        $teacher_id = validateAndSanitize($_POST['teacher_id'] ?? '');
        $name = validateAndSanitize($_POST['name'] ?? '');
        $type = validateAndSanitize($_POST['type'] ?? '');
        $email = validateAndSanitize($_POST['email'] ?? '', 'email');
        $phone = validateAndSanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $security_question = validateAndSanitize($_POST['security_question'] ?? 0, 'int');
        $security_answer = validateAndSanitize($_POST['security_answer'] ?? '');

        if (empty($teacher_id) || empty($name) || empty($type) || empty($email) || empty($password)) {
            $response['message'] = 'Sila lengkapkan maklumat required (ID, Nama, Jenis, Emel, Kata Laluan)';
            echo json_encode($response);
            exit;
        }
        if (strlen($password) < 6) {
            $response['message'] = 'Kata laluan minimum 6 aksara';
            echo json_encode($response);
            exit;
        }
        if ($security_question <= 0 || empty($security_answer)) {
            $response['message'] = 'Sila pilih soalan keselamatan dan masukkan jawapan';
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
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'guru';
            $security_answer_hash = md5(strtolower(trim($security_answer)));

            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, sub_role, security_question_id, security_answer_hash, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')");
            $stmt->bind_param('sssssis', $username, $hashed_password, $email, $role, $type, $security_question, $security_answer_hash);
            $stmt->execute();
            $user_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO teachers (user_id, teacher_id, full_name, teacher_type, phone, status) VALUES (?, ?, ?, ?, ?, 'Aktif')");
            $stmt->bind_param('issss', $user_id, $teacher_id, $name, $type, $phone);
            $stmt->execute();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Guru berjaya ditambah. Kata laluan: '.$password;
            logActivity($user['id'], 'ADD_TEACHER', "Menambah guru: $teacher_id - $name");
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Ralat: '.$e->getMessage();
        }
        echo json_encode($response);
        exit;
    }

    // Add Student
    if ($action === 'add_student') {
        $matric = validateAndSanitize($_POST['matric'] ?? '');
        $name = validateAndSanitize($_POST['name'] ?? '');
        $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
        $phone = validateAndSanitize($_POST['phone'] ?? '');
        $email = validateAndSanitize($_POST['email'] ?? '', 'email');
        $ic_number = validateAndSanitize($_POST['ic_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $security_question = validateAndSanitize($_POST['security_question'] ?? 0, 'int');
        $security_answer = validateAndSanitize($_POST['security_answer'] ?? '');

        if (empty($matric) || empty($name) || $class_id <= 0 || empty($password)) {
            $response['message'] = 'Sila lengkapkan maklumat required (Matrik, Nama, Kelas, Kata Laluan)';
            echo json_encode($response);
            exit;
        }
        if (strlen($password) < 6) {
            $response['message'] = 'Kata laluan minimum 6 aksara';
            echo json_encode($response);
            exit;
        }
        if ($security_question <= 0 || empty($security_answer)) {
            $response['message'] = 'Sila pilih soalan keselamatan dan masukkan jawapan';
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
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'pelajar';
            $security_answer_hash = md5(strtolower(trim($security_answer)));

            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, security_question_id, security_answer_hash, status) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')");
            $stmt->bind_param('ssssis', $username, $hashed_password, $email, $role, $security_question, $security_answer_hash);
            $stmt->execute();
            $user_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO students (user_id, matric_no, full_name, ic_number, class_id, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')");
            $stmt->bind_param('isssis', $user_id, $matric, $name, $ic_number, $class_id, $phone);
            $stmt->execute();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Pelajar berjaya ditambah. Kata laluan: '.$password;
            logActivity($user['id'], 'ADD_STUDENT', "Menambah pelajar: $matric - $name");
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Ralat: '.$e->getMessage();
        }
        echo json_encode($response);
        exit;
    }

    // Add Subject
    if ($action === 'add_subject') {
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
        echo json_encode($response);
        exit;
    }

    // Add Class
    if ($action === 'add_class') {
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
        echo json_encode($response);
        exit;
    }

    // Edit Class
    if ($action === 'edit_class') {
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
        echo json_encode($response);
        exit;
    }

    // Delete Class
    if ($action === 'delete_class') {
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
        echo json_encode($response);
        exit;
    }

    // Delete Admin
    if ($action === 'delete_admin') {
        $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
        if ($id <= 0) {
            $response['message'] = 'ID tidak sah';
            echo json_encode($response);
            exit;
        }

        $admin = getAdminById($id);
        if ($admin) {
            $stmt = $conn->prepare("UPDATE admins SET status = 'Tidak Aktif' WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $stmt2 = $conn->prepare("UPDATE users SET status = 'Tidak Aktif' WHERE id = ?");
                $stmt2->bind_param('i', $admin['user_id']);
                $stmt2->execute();

                $response['success'] = true;
                $response['message'] = 'Admin telah ditamatkan';
                logActivity($user['id'], 'DELETE_ADMIN', 'Menamatkan admin: '.$admin['admin_id']);
            }
        }
        echo json_encode($response);
        exit;
    }

    // Delete Teacher
    if ($action === 'delete_teacher') {
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
        echo json_encode($response);
        exit;
    }

    // Delete Student
    if ($action === 'delete_student') {
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
        echo json_encode($response);
        exit;
    }

    // Delete Subject
    if ($action === 'delete_subject') {
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
        echo json_encode($response);
        exit;
    }

    // Add Exam
    if ($action === 'add_exam') {
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
        echo json_encode($response);
        exit;
    }

    // Delete Exam
    if ($action === 'delete_exam') {
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
        echo json_encode($response);
        exit;
    }

    // Change Password
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

    // Update Profile
    if ($action === 'update_profile') {
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
        $stmt->execute();

        $stmt = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->bind_param('si', $email, $user['id']);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = 'Profil berjaya dikemaskini';
        logActivity($user['id'], 'UPDATE_PROFILE', 'Mengemaskini profil admin');

        echo json_encode($response);
        exit;
    }

    // Edit Admin
    if ($action === 'edit_admin') {
        $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
        $admin_id = validateAndSanitize($_POST['admin_id'] ?? '');
        $name = validateAndSanitize($_POST['name'] ?? '');
        $email = validateAndSanitize($_POST['email'] ?? '', 'email');
        $phone = validateAndSanitize($_POST['phone'] ?? '');
        $admin_level = validateAndSanitize($_POST['admin_level'] ?? 'admin');
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || empty($admin_id) || empty($name) || empty($email)) {
            $response['message'] = 'Sila lengkapkan maklumat yang diperlukan (ID, Nama, Emel)';
            echo json_encode($response);
            exit;
        }

        $check = $conn->prepare('SELECT id FROM admins WHERE admin_id = ? AND id != ?');
        $check->bind_param('si', $admin_id, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $response['message'] = 'ID Admin sudah digunakan oleh admin lain';
            echo json_encode($response);
            exit;
        }

        $admin = getAdminById($id);
        if (! $admin) {
            $response['message'] = 'Admin tidak dijumpai';
            echo json_encode($response);
            exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('UPDATE admins SET admin_id = ?, full_name = ?, admin_level = ?, phone = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $admin_id, $name, $admin_level, $phone, $id);
            $stmt->execute();

            if (! empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('Kata laluan minimum 6 aksara');
                }
                $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare('UPDATE users SET username = ?, email = ?, sub_role = ?, password = ? WHERE id = ?');
                $stmt2->bind_param('ssssi', $admin_id, $email, $admin_level, $hashed_pwd, $admin['user_id']);
            } else {
                $stmt2 = $conn->prepare('UPDATE users SET username = ?, email = ?, sub_role = ? WHERE id = ?');
                $stmt2->bind_param('sssi', $admin_id, $email, $admin_level, $admin['user_id']);
            }
            $stmt2->execute();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Maklumat admin berjaya dikemaskini';
            logActivity($user['id'], 'EDIT_ADMIN', "Mengemaskini admin: $admin_id - $name");
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Ralat: '.$e->getMessage();
        }
        echo json_encode($response);
        exit;
    }

    // Edit Teacher
    if ($action === 'edit_teacher') {
        $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
        $teacher_id = validateAndSanitize($_POST['teacher_id'] ?? '');
        $name = validateAndSanitize($_POST['name'] ?? '');
        $type = validateAndSanitize($_POST['type'] ?? '');
        $email = validateAndSanitize($_POST['email'] ?? '', 'email');
        $phone = validateAndSanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

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

            if (! empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('Kata laluan minimum 6 aksara');
                }
                $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare('UPDATE users SET username = ?, sub_role = ?, email = ?, password = ? WHERE id = ?');
                $stmt2->bind_param('ssssi', $teacher_id, $type, $email, $hashed_pwd, $teacher['user_id']);
            } else {
                $stmt2 = $conn->prepare('UPDATE users SET username = ?, sub_role = ?, email = ? WHERE id = ?');
                $stmt2->bind_param('sssi', $teacher_id, $type, $email, $teacher['user_id']);
            }
            $stmt2->execute();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Maklumat guru berjaya dikemaskini';
            logActivity($user['id'], 'EDIT_TEACHER', "Mengemaskini guru: $teacher_id - $name");
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Ralat: '.$e->getMessage();
        }
        echo json_encode($response);
        exit;
    }

    // Edit Student
    if ($action === 'edit_student') {
        $id = validateAndSanitize($_POST['id'] ?? 0, 'int');
        $matric = validateAndSanitize($_POST['matric'] ?? '');
        $name = validateAndSanitize($_POST['name'] ?? '');
        $class_id = validateAndSanitize($_POST['class_id'] ?? 0, 'int');
        $phone = validateAndSanitize($_POST['phone'] ?? '');
        $email = validateAndSanitize($_POST['email'] ?? '', 'email');
        $ic_number = validateAndSanitize($_POST['ic_number'] ?? '');
        $password = $_POST['password'] ?? '';

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

            if (! empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('Kata laluan minimum 6 aksara');
                }
                $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare('UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?');
                $stmt2->bind_param('sssi', $matric, $email, $hashed_pwd, $student['user_id']);
            } else {
                $stmt2 = $conn->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                $stmt2->bind_param('ssi', $matric, $email, $student['user_id']);
            }
            $stmt2->execute();

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Maklumat pelajar berjaya dikemaskini';
            logActivity($user['id'], 'EDIT_STUDENT', "Mengemaskini pelajar: $matric - $name");
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Ralat: '.$e->getMessage();
        }
        echo json_encode($response);
        exit;
    }

    // Edit Subject
    if ($action === 'edit_subject') {
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
        echo json_encode($response);
        exit;
    }

    // Edit Exam
    if ($action === 'edit_exam') {
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
        echo json_encode($response);
        exit;
    }

    // Backup Database
    if ($action === 'backup_database') {
        $file = backupDatabase();
        if ($file) {
            $response['success'] = true;
            $response['message'] = 'Pangkalan data berjaya dibackup';
            $response['filename'] = $file;
            $response['download_url'] = $file;
            logActivity($user['id'], 'BACKUP_DB', "Membuat sandaran database: $file");
        } else {
            $response['message'] = 'Gagal membuat sandaran pangkalan data';
        }
        echo json_encode($response);
        exit;
    }
}

// Helper function to get admin by ID
function getAdminById($id)
{
    global $conn;
    $stmt = $conn->prepare('SELECT a.*, a.user_id FROM admins a WHERE a.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

// ======================================================
// GET STATISTICS FROM DATABASE
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

// Total admins
$totalAdmins = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM admins WHERE status = 'Aktif'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalAdmins = $row['total'] ?? 0;
}

// Total classes
$totalClasses = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM classes WHERE status = 'Aktif'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalClasses = $row['total'] ?? 0;
}

// Total subjects
$totalSubjects = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM subjects WHERE status = 'Aktif'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalSubjects = $row['total'] ?? 0;
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

// Get security questions for dropdown
$securityQuestions = [];
$result = $conn->query('SELECT id, question FROM security_questions WHERE is_active = 1 ORDER BY id');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $securityQuestions[] = $row;
    }
}

// Get all exams with proper data
$exams = [];
$result = $conn->query("
    SELECT e.*, et.exam_name as type_name, et.exam_code,
           (SELECT COUNT(*) FROM marks m WHERE m.exam_id = e.id) as marks_count
    FROM exams e
    JOIN exam_types et ON e.exam_type_id = et.id
    WHERE e.status IN ('Aktif', 'Draf')
    ORDER BY e.exam_date DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
}

// Get all admins
$admins = [];
$result = $conn->query("
    SELECT a.*, u.email, u.status as user_status, u.last_login
    FROM admins a
    JOIN users u ON a.user_id = u.id
    WHERE a.status = 'Aktif'
    ORDER BY a.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Get all teachers
$teachers = [];
$result = $conn->query("
    SELECT t.*, u.email, u.status as user_status, u.last_login
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'Aktif'
    ORDER BY t.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Get all students
$students = [];
$result = $conn->query("
    SELECT s.id, s.matric_no, s.full_name, s.ic_number, s.class_id, s.phone, s.status,
           c.class_name,
           u.email
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.status = 'Aktif'
    ORDER BY s.created_at DESC
    LIMIT 100
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get all subjects with class count
$subjects = [];
$result = $conn->query("
    SELECT s.*, 
        (SELECT COUNT(*) FROM class_subjects cs WHERE cs.subject_id = s.id AND cs.status = 'Aktif') as class_count
    FROM subjects s
    WHERE s.status = 'Aktif'
    ORDER BY s.subject_code
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Get all classes
$classes = [];
$result = $conn->query("
    SELECT c.id, c.class_name, c.teacher_id, c.status,
           COUNT(DISTINCT s.id) as student_count,
           t.full_name as teacher_name,
           t.teacher_id as teacher_code
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id AND s.status = 'Aktif'
    LEFT JOIN teachers t ON c.teacher_id = t.id
    WHERE c.status = 'Aktif'
    GROUP BY c.id
    ORDER BY c.class_name
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Get recent logs
$recentLogs = [];
$result = $conn->query('
    SELECT l.*, u.username, u.role
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 50
');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentLogs[] = $row;
    }
}

// Get system stats
$logs30Days = 0;
$result = $conn->query('SELECT COUNT(*) as total FROM system_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
if ($result) {
    $row = $result->fetch_assoc();
    $logs30Days = $row['total'] ?? 0;
}

$newUsers30Days = 0;
$result = $conn->query('SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
if ($result) {
    $row = $result->fetch_assoc();
    $newUsers30Days = $row['total'] ?? 0;
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
    SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN marks >= 50 THEN 1 ELSE 0 END) as passed 
    FROM marks WHERE marks IS NOT NULL
');
if ($result) {
    $row = $result->fetch_assoc();
    $total = $row['total'] ?? 0;
    $passed = $row['passed'] ?? 0;
    $passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
}

// Get current user data
$currentUser = getCurrentUser();
if (! $currentUser) {
    $currentUser = [
        'full_name' => 'Super Admin',
        'id_number' => 'SA001',
        'email' => 'superadmin@exam.edu.my',
        'phone' => '019-8888888',
    ];
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
            --accent: #d946ef;
            --accent-glow: rgba(217, 70, 239, 0.25);
            --bg-dark: #080612;
            --bg-card: rgba(18, 13, 34, 0.75);
            --bg-card-hover: rgba(26, 18, 48, 0.85);
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
            --gradient-primary: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #d946ef 100%);
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
        .bg-orb-2 { width: 500px; height: 500px; background: radial-gradient(circle, #d946ef 0%, transparent 70%); top: 40%; right: -150px; }
        .bg-orb-3 { width: 450px; height: 450px; background: radial-gradient(circle, #ec4899 0%, transparent 70%); bottom: -100px; left: 25%; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 1; }

        /* Header Modernization */
        header {
            background: rgba(10, 8, 22, 0.82);
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
            background: linear-gradient(135deg, #7c3aed, #d946ef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
        }

        .nav-wrapper { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
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
            background: linear-gradient(135deg, #7c3aed, #d946ef); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 800; 
            color: white; 
            font-size: 0.85rem;
            box-shadow: 0 2px 10px rgba(124, 58, 237, 0.4);
        }
        .user-details { line-height: 1.3; }
        .user-details .user-name { font-weight: 700; font-size: 0.85rem; color: #ffffff; }
        .user-details .user-role { font-size: 0.7rem; color: var(--text-muted); }

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
            background: linear-gradient(135deg, #ffffff 30%, #f0abfc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-icon { 
            position: absolute; 
            top: 1.4rem; right: 1.4rem; 
            font-size: 2.4rem; 
            color: #a855f7; 
            opacity: 0.2; 
            transition: all 0.3s ease; 
        }
        .card:hover .card-icon { opacity: 0.4; transform: scale(1.1) rotate(5deg); color: #d946ef; }

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
        .badge-super { background: rgba(217, 70, 239, 0.15); color: #f0abfc; border: 1px solid rgba(217, 70, 239, 0.3); }
        .badge-admin { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-guru { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-pelajar { background: rgba(236, 72, 153, 0.15); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.3); }
        .badge-info { background: rgba(6, 182, 212, 0.15); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.3); }
        .badge-class { background: rgba(124, 58, 237, 0.15); color: #c4b5fd; border: 1px solid rgba(124, 58, 237, 0.3); }
        .text-muted { color: var(--text-muted); font-size: 0.72rem; }

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
            background: linear-gradient(135deg, #ffffff, #f0abfc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; font-weight: 600; text-transform: uppercase; }

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
            background: #100b22; 
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
            background: rgba(10, 8, 22, 0.8); 
            border-top: 1px solid var(--border-glass); 
            color: var(--text-muted); 
            padding: 1.4rem 0; 
            text-align: center; 
            margin-top: 3rem; 
            font-size: 0.8rem;
        }

        .row-2cols { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        @media (max-width: 1200px) {
            .nav-wrapper { flex-direction: column; width: 100%; }
            nav ul { justify-content: center; width: 100%; }
            .user-info { justify-content: center; }
        }

        @media (max-width: 768px) {
            .header-content { flex-direction: column; }
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
                    <i class="fas fa-crown"></i>
                </div>
                <span>Sistem e-Exam STPM | Super Admin</span>
            </div>
            
            <div class="nav-wrapper">
                <nav>
                    <ul>
                        <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="#" class="nav-link" data-section="admins"><i class="fas fa-user-shield"></i> Admin</a></li>
                        <li><a href="#" class="nav-link" data-section="teachers"><i class="fas fa-chalkboard-user"></i> Guru</a></li>
                        <li><a href="#" class="nav-link" data-section="students"><i class="fas fa-user-graduate"></i> Pelajar</a></li>
                        <li><a href="#" class="nav-link" data-section="subjects"><i class="fas fa-book"></i> Subjek</a></li>
                        <li><a href="#" class="nav-link" data-section="classes"><i class="fas fa-school"></i> Kelas</a></li>
                        <li><a href="#" class="nav-link" data-section="exams"><i class="fas fa-calendar-alt"></i> Peperiksaan</a></li>
                        <li><a href="#" class="nav-link" data-section="system"><i class="fas fa-cog"></i> Sistem</a></li>
                        <li><a href="#" class="nav-link" data-section="logs"><i class="fas fa-history"></i> Log</a></li>
                        <li><a href="#" class="nav-link" data-section="profile"><i class="fas fa-user"></i> Profil</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Keluar</a></li>
                    </ul>
                </nav>
                
                <div class="user-info" onclick="showSection('profile')">
                    <div class="avatar"><?php echo strtoupper(substr($currentUser['full_name'] ?? 'SA', 0, 2)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Super Admin'); ?></div>
                        <div class="user-role">Super Admin</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container main-content">
        <!-- Dashboard Section -->
        <section id="dashboard" class="page-section active">
            <h1 class="page-title">Dashboard Super Admin</h1>
            <div class="dashboard-cards">
                <div class="card" onclick="showSection('students')"><div class="card-title">Jumlah Pelajar</div><div class="card-value"><?php echo $totalStudents; ?></div><i class="fas fa-user-graduate card-icon"></i></div>
                <div class="card" onclick="showSection('teachers')"><div class="card-title">Jumlah Guru</div><div class="card-value"><?php echo $totalTeachers; ?></div><i class="fas fa-chalkboard-user card-icon"></i></div>
                <div class="card" onclick="showSection('admins')"><div class="card-title">Jumlah Admin</div><div class="card-value"><?php echo $totalAdmins; ?></div><i class="fas fa-user-shield card-icon"></i></div>
                <div class="card" onclick="showSection('exams')"><div class="card-title">Peperiksaan</div><div class="card-value"><?php echo $totalExams; ?></div><i class="fas fa-calendar-alt card-icon"></i></div>
            </div>

            <div class="chart-grid">
                <div class="chart-container"><h3 class="chart-title">Statistik Pengguna</h3><canvas id="userStatsChart" style="height: 300px; width: 100%;"></canvas></div>
                <div class="chart-container"><h3 class="chart-title">Prestasi Akademik</h3><canvas id="academicChart" style="height: 300px; width: 100%;"></canvas></div>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value"><?php echo $avgPng; ?></div><div class="stat-label">PNG Purata</div></div>
                <div class="stat-card"><div class="stat-value"><?php echo $passRate; ?>%</div><div class="stat-label">Kadar Kelulusan</div></div>
                <div class="stat-card"><div class="stat-value"><?php echo $newUsers30Days; ?></div><div class="stat-label">Pengguna Baru (30 hari)</div></div>
                <div class="stat-card"><div class="stat-value"><?php echo $logs30Days; ?></div><div class="stat-label">Aktiviti Sistem (30 hari)</div></div>
            </div>
        </section>

        <!-- Admins Section -->
        <section id="admins" class="page-section">
            <h1 class="page-title">Pengurusan Admin</h1>
            <div class="filter-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                    <h3><i class="fas fa-user-shield"></i> Senarai Admin</h3>
                    <button class="btn btn-success" onclick="openAddAdminModal()"><i class="fas fa-plus"></i> Tambah Admin</button>
                </div>
                <div class="filter-row">
                    <div class="form-group"><label>Cari</label><input type="text" id="searchAdmin" class="form-control" placeholder="Nama / ID Admin" onkeyup="filterAdmins()"></div>
                    <div class="form-group"><label>Tahap</label><select id="filterAdminLevel" class="form-control" onchange="filterAdmins()"><option value="">Semua</option><option value="super_admin">Super Admin</option><option value="admin">Admin Biasa</option></select></div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID Admin</th><th>Nama</th><th>Tahap</th><th>Emel</th><th>Telefon</th><th>Tindakan</th></tr>
                    </thead>
                    <tbody id="adminsTable">
                        <?php foreach ($admins as $admin) { ?>
                        <tr data-level="<?php echo $admin['admin_level']; ?>">
                            <td><strong><?php echo htmlspecialchars($admin['admin_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                            <td><span class="badge <?php echo $admin['admin_level'] === 'super_admin' ? 'badge-super' : 'badge-admin'; ?>"><?php echo $admin['admin_level'] === 'super_admin' ? 'Super Admin' : 'Admin'; ?></span></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['phone'] ?? '-'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" 
                                    data-id="<?php echo $admin['id']; ?>"
                                    data-admin-id="<?php echo htmlspecialchars($admin['admin_id']); ?>"
                                    data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                    data-level="<?php echo htmlspecialchars($admin['admin_level']); ?>"
                                    data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                    data-phone="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>"
                                    onclick="openEditAdminModalFromBtn(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteAdmin(<?php echo $admin['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($admins)) { ?>
                        <tr><td colspan="6" style="text-align:center;">Tiada data admin</span></td><?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Teachers Section -->
        <section id="teachers" class="page-section">
            <h1 class="page-title">Pengurusan Guru</h1>
            <div class="filter-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                    <h3><i class="fas fa-chalkboard-user"></i> Senarai Guru</h3>
                    <button class="btn btn-success" onclick="openAddTeacherModal()"><i class="fas fa-plus"></i> Tambah Guru</button>
                </div>
                <div class="filter-row">
                    <div class="form-group"><label>Cari</label><input type="text" id="searchTeacher" class="form-control" placeholder="Nama / ID Guru" onkeyup="filterTeachers()"></div>
                    <div class="form-group"><label>Jenis</label><select id="filterTeacherType" class="form-control" onchange="filterTeachers()"><option value="">Semua</option><option value="guru_kelas">Guru Kelas</option><option value="guru_subjek">Guru Subjek</option></select></div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>ID Guru</th><th>Nama</th><th>Jenis</th><th>Emel</th><th>Telefon</th><th>Tindakan</th></tr>
                    </thead>
                    <tbody id="teachersTable">
                        <?php foreach ($teachers as $teacher) { ?>
                        <tr data-type="<?php echo $teacher['teacher_type']; ?>">
                            <td><strong><?php echo htmlspecialchars($teacher['teacher_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                            <td><?php echo $teacher['teacher_type'] === 'guru_kelas' ? '<span class="badge badge-guru">Guru Kelas</span>' : '<span class="badge badge-info">Guru Subjek</span>'; ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['phone'] ?? '-'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning"
                                    data-id="<?php echo $teacher['id']; ?>"
                                    data-teacher-id="<?php echo htmlspecialchars($teacher['teacher_id']); ?>"
                                    data-name="<?php echo htmlspecialchars($teacher['full_name']); ?>"
                                    data-type="<?php echo htmlspecialchars($teacher['teacher_type']); ?>"
                                    data-email="<?php echo htmlspecialchars($teacher['email']); ?>"
                                    data-phone="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>"
                                    onclick="openEditTeacherModalFromBtn(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?php echo $teacher['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($teachers)) { ?>
                        <tr><td colspan="6" style="text-align:center;">Tiada data guru</span></td><?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Students Section -->
        <section id="students" class="page-section">
            <h1 class="page-title">Pengurusan Pelajar</h1>
            <div class="filter-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                    <h3><i class="fas fa-users"></i> Senarai Pelajar</h3>
                    <button class="btn btn-success" onclick="openAddStudentModal()"><i class="fas fa-plus"></i> Tambah Pelajar</button>
                </div>
                <div class="filter-row">
                    <div class="form-group"><label><i class="fas fa-search"></i> Cari</label><input type="text" id="searchStudent" class="form-control" placeholder="Nama / No. Matrik" onkeyup="filterStudents()"></div>
                    <div class="form-group"><label><i class="fas fa-school"></i> Kelas</label><select id="filterClass" class="form-control" onchange="filterStudents()"><option value="">Semua Kelas</option><?php foreach ($allClasses as $class) { ?><option value="<?php echo htmlspecialchars($class['class_name']); ?>"><?php echo htmlspecialchars($class['class_name']); ?></option><?php } ?></select></div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>No. Matrik</th><th>Nama Pelajar</th><th>Kelas</th><th>Tindakan</th></tr>
                    </thead>
                    <tbody id="studentsTable">
                        <?php foreach ($students as $student) { ?>
                        <tr data-class="<?php echo htmlspecialchars($student['class_name'] ?? ''); ?>">
                            <td><strong><?php echo htmlspecialchars($student['matric_no']); ?></strong></td>
                            <td><i class="fas fa-user-graduate" style="color: #7c3aed; margin-right: 8px;"></i><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><span class="badge badge-class"><i class="fas fa-school"></i> <?php echo htmlspecialchars($student['class_name'] ?? 'Tiada Kelas'); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning"
                                    data-id="<?php echo $student['id']; ?>"
                                    data-matric="<?php echo htmlspecialchars($student['matric_no']); ?>"
                                    data-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                    data-class-id="<?php echo $student['class_id'] ?? 0; ?>"
                                    data-phone="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"
                                    data-email="<?php echo htmlspecialchars($student['email'] ?? ''); ?>"
                                    data-ic="<?php echo htmlspecialchars($student['ic_number'] ?? ''); ?>"
                                    onclick="openEditStudentModalFromBtn(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($students)) { ?>
                        <tr><td colspan="4" style="text-align:center;">Tiada data pelajar</span></td><?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Subjects Section -->
        <section id="subjects" class="page-section">
            <h1 class="page-title">Pengurusan Subjek</h1>
            <div class="filter-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
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
                    <thead>
                        <tr>
                            <th style="width: 5%;">Bil</th>
                            <th style="width: 15%;">Kod Subjek</th>
                            <th style="width: 35%;">Nama Subjek</th>
                            <th style="width: 15%;">Aliran</th>
                            <th style="width: 10%;">Kelas Digunakan</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 10%;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody id="subjectsTable">
                        <?php if (empty($subjects)) { ?>
                        <tr><td colspan="7" style="text-align:center;">Tiada data subjek</span></td><?php } else { ?>
                        <?php $bil = 1;
                            foreach ($subjects as $subject) { ?>
                        <tr data-stream="<?php echo htmlspecialchars($subject['stream']); ?>" data-code="<?php echo strtolower(htmlspecialchars($subject['subject_code'])); ?>" data-name="<?php echo strtolower(htmlspecialchars($subject['subject_name'])); ?>">
                            <td style="text-align:center;"><?php echo $bil++; ?></td>
                            <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td><span class="badge <?php echo $subject['stream'] == 'Sains' ? 'badge-info' : ($subject['stream'] == 'Sastera' ? 'badge-guru' : 'badge-admin'); ?>"><?php echo htmlspecialchars($subject['stream']); ?></span></td>
                            <td style="text-align:center;"><?php echo $subject['class_count'] ?? 0; ?> kelas</td>
                            <td style="text-align:center;"><span class="badge badge-active"><?php echo $subject['status']; ?></span></td>
                            <td style="text-align:center;">
                                <button class="btn btn-sm btn-warning"
                                    data-id="<?php echo $subject['id']; ?>"
                                    data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                    data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                    data-stream="<?php echo htmlspecialchars($subject['stream']); ?>"
                                    onclick="openEditSubjectModalFromBtn(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteSubject(<?php echo $subject['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php if (! empty($subjects)) { ?>
            <div class="filter-section" style="margin-top: 1rem; background: #f8fafc;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div><i class="fas fa-chart-line"></i> <strong>Jumlah Subjek:</strong> <?php echo count($subjects); ?> subjek</div>
                    <div><i class="fas fa-flask"></i> <strong>Aliran Sains:</strong> <?php echo count(array_filter($subjects, function ($s) {
                        return $s['stream'] == 'Sains';
                    })); ?> subjek</div>
                    <div><i class="fas fa-book-open"></i> <strong>Aliran Sastera:</strong> <?php echo count(array_filter($subjects, function ($s) {
                        return $s['stream'] == 'Sastera';
                    })); ?> subjek</div>
                    <div><i class="fas fa-tools"></i> <strong>Aliran Teknikal:</strong> <?php echo count(array_filter($subjects, function ($s) {
                        return $s['stream'] == 'Teknikal';
                    })); ?> subjek</div>
                </div>
            </div>
            <?php } ?>
        </section>

        <!-- Classes Section -->
        <section id="classes" class="page-section">
            <h1 class="page-title">Pengurusan Kelas</h1>
            <div class="filter-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                    <h3><i class="fas fa-school"></i> Senarai Kelas</h3>
                    <button class="btn btn-success" onclick="openAddClassModal()"><i class="fas fa-plus"></i> Tambah Kelas</button>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Bil</th><th>Nama Kelas</th><th>Bil. Pelajar</th><th>Guru Kelas</th><th>Status</th><th>Tindakan</th></tr></thead>
                    <tbody id="classesTable">
                        <?php $bil = 1;
foreach ($classes as $class) { ?>
                        <tr>
                            <td><?php echo $bil++; ?></td>
                            <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                            <td><?php echo $class['student_count']; ?> orang</span></td>
                            <td><?php if ($class['teacher_name']) { ?><i class="fas fa-user"></i> <?php echo htmlspecialchars($class['teacher_name']); ?><br><small>(<?php echo htmlspecialchars($class['teacher_code'] ?? '-'); ?>)</small><?php } else { ?><span class="badge" style="background:#fef3c7; color:#92400e;">Tiada Guru Kelas</span><?php } ?></td>
                            <td><span class="badge badge-active"><?php echo $class['status']; ?></span></td>
                            <td><button class="btn btn-sm btn-warning" onclick="openEditClassModal(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['class_name']); ?>', <?php echo $class['teacher_id'] ?? 0; ?>)"><i class="fas fa-edit"></i> Edit</button> <button class="btn btn-sm btn-danger" onclick="deleteClass(<?php echo $class['id']; ?>)"><i class="fas fa-trash"></i> Hapus</button></td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($classes)) { ?><td><td colspan="6" style="text-align:center;">Tiada data kelas</span></td><?php } ?>
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
            </div>
            <div class="table-container">
                <table style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th>Nama Peperiksaan</th>
                            <th>Jenis</th>
                            <th>Semester</th>
                            <th>Tarikh</th>
                            <th>Masa</th>
                            <th>Tempat</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody id="examsTable">
                        <?php if (empty($exams)) { ?>
                        <tr><td colspan="8" style="text-align:center;">Tiada data peperiksaan</span></td>
                        <?php } else { ?>
                        <?php foreach ($exams as $exam) { ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($exam['type_name']); ?> (<?php echo htmlspecialchars($exam['exam_code']); ?>)</td>
                            <td style="text-align:center;">Semester <?php echo $exam['semester']; ?></td>
                            <td style="text-align:center;"><?php echo date('d/m/Y', strtotime($exam['exam_date'])); ?></td>
                            <td style="text-align:center;"><?php echo date('H:i', strtotime($exam['start_time'])).' - '.date('H:i', strtotime($exam['end_time'])); ?></td>
                            <td><?php echo htmlspecialchars($exam['venue'] ?? '-'); ?></td>
                            <td style="text-align:center;"><span class="badge badge-active"><?php echo $exam['status']; ?></span></td>
                            <td style="text-align:center;">
                                <button class="btn btn-sm btn-warning"
                                    data-id="<?php echo $exam['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($exam['exam_name']); ?>"
                                    data-type="<?php echo $exam['exam_type_id']; ?>"
                                    data-semester="<?php echo $exam['semester']; ?>"
                                    data-year="<?php echo htmlspecialchars($exam['academic_year']); ?>"
                                    data-date="<?php echo $exam['exam_date']; ?>"
                                    data-start="<?php echo $exam['start_time']; ?>"
                                    data-end="<?php echo $exam['end_time']; ?>"
                                    data-venue="<?php echo htmlspecialchars($exam['venue'] ?? ''); ?>"
                                    data-total="<?php echo $exam['total_marks']; ?>"
                                    data-passing="<?php echo $exam['passing_marks']; ?>"
                                    data-status="<?php echo htmlspecialchars($exam['status']); ?>"
                                    onclick="openEditExamModalFromBtn(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteExam(<?php echo $exam['id']; ?>)"><i class="fas fa-trash"></i> Batal</button>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- System Section -->
        <section id="system" class="page-section">
            <h1 class="page-title">Pengurusan Sistem</h1>
            <div class="dashboard-cards">
                <div class="card" onclick="backupDatabase()"><div class="card-title">Backup Database</div><div class="card-value"><i class="fas fa-database"></i> Backup</div><i class="fas fa-download card-icon"></i></div>
                <div class="card" onclick="clearCache()"><div class="card-title">Cache Sistem</div><div class="card-value"><i class="fas fa-broom"></i> Clear</div><i class="fas fa-trash-alt card-icon"></i></div>
            </div>
        </section>

        <!-- Logs Section -->
        <section id="logs" class="page-section">
            <h1 class="page-title">Log Sistem</h1>
            <div class="table-container">
                <table>
                    <thead><tr><th>Tarikh</th><th>Pengguna</th><th>Tindakan</th><th>Keterangan</th></tr></thead>
                    <tbody id="logsTable">
                        <?php foreach ($recentLogs as $log) { ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'Sistem'); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                        </tr>
                        <?php } ?>
                        <?php if (empty($recentLogs)) { ?><tr><td colspan="4" style="text-align:center;">Tiada log sistem</span></td><?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="page-section">
            <h1 class="page-title">Profil Super Admin</h1>
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="filter-section">
                    <div style="text-align: center;">
                        <div class="avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 1rem;"><?php echo strtoupper(substr($currentUser['full_name'] ?? 'SA', 0, 2)); ?></div>
                        <h2 id="adminName"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Super Admin'); ?></h2>
                        <p><span class="badge badge-super">Super Admin</span></p>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <div class="form-group"><label>ID Admin</label><input type="text" id="adminId" class="form-control" value="<?php echo htmlspecialchars($currentUser['id_number'] ?? 'SA001'); ?>" readonly></div>
                    <div class="form-group"><label>Nama Penuh</label><input type="text" id="adminFullName" class="form-control" value="<?php echo htmlspecialchars($currentUser['full_name'] ?? 'Super Admin'); ?>"></div>
                    <div class="form-group"><label>Emel</label><input type="email" id="adminEmail" class="form-control" value="<?php echo htmlspecialchars($currentUser['email'] ?? 'superadmin@exam.edu.my'); ?>"></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="adminPhone" class="form-control" value="<?php echo htmlspecialchars($currentUser['phone'] ?? '019-8888888'); ?>"></div>
                    <button class="btn btn-warning" style="width:100%; margin-top: 1rem;" onclick="openChangePasswordModal()"><i class="fas fa-key"></i> Tukar Kata Laluan</button>
                </div>
            </div>
        </section>
    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- Add Admin Modal -->
    <div class="modal" id="addAdminModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Tambah Admin</h3><button class="close-modal" onclick="closeModal('addAdminModal')">&times;</button></div>
            <div class="modal-body">
                <form id="addAdminForm" onsubmit="addAdmin(event)">
                    <div class="form-group"><label>ID Admin <span style="color:red;">*</span></label><input type="text" id="newAdminId" class="form-control" required></div>
                    <div class="form-group"><label>Nama Penuh <span style="color:red;">*</span></label><input type="text" id="newAdminName" class="form-control" required></div>
                    <div class="form-group"><label>Emel <span style="color:red;">*</span></label><input type="email" id="newAdminEmail" class="form-control" required></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="newAdminPhone" class="form-control"></div>
                    <div class="form-group"><label>Tahap Admin <span style="color:red;">*</span></label><select id="newAdminLevel" class="form-control"><option value="admin">Admin Biasa</option><option value="super_admin">Super Admin</option></select></div>
                    <div class="form-group"><label>Kata Laluan <span style="color:red;">*</span></label><input type="text" id="newAdminPassword" class="form-control" value="admin123" required><small>Minimum 6 aksara</small></div>
                    <div class="form-group"><label>Soalan Keselamatan <span style="color:red;">*</span></label><select id="newAdminSecurityQuestion" class="form-control" required><option value="">Pilih soalan keselamatan</option><?php foreach ($securityQuestions as $sq) { ?><option value="<?php echo $sq['id']; ?>"><?php echo htmlspecialchars($sq['question']); ?></option><?php } ?></select></div>
                    <div class="form-group"><label>Jawapan Keselamatan <span style="color:red;">*</span></label><input type="text" id="newAdminSecurityAnswer" class="form-control" placeholder="cth: John" required><small>Jawapan tidak sensitif huruf besar/kecil</small></div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal" id="addTeacherModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Tambah Guru</h3><button class="close-modal" onclick="closeModal('addTeacherModal')">&times;</button></div>
            <div class="modal-body">
                <form id="addTeacherForm" onsubmit="addTeacher(event)">
                    <div class="form-group"><label>ID Guru <span style="color:red;">*</span></label><input type="text" id="newTeacherId" class="form-control" required></div>
                    <div class="form-group"><label>Nama Penuh <span style="color:red;">*</span></label><input type="text" id="newTeacherName" class="form-control" required></div>
                    <div class="form-group"><label>Jenis Guru <span style="color:red;">*</span></label><select id="newTeacherType" class="form-control" required><option value="guru_subjek">Guru Subjek</option><option value="guru_kelas">Guru Kelas</option></select></div>
                    <div class="form-group"><label>Emel <span style="color:red;">*</span></label><input type="email" id="newTeacherEmail" class="form-control" required></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="newTeacherPhone" class="form-control"></div>
                    <div class="form-group"><label>Kata Laluan <span style="color:red;">*</span></label><input type="text" id="newTeacherPassword" class="form-control" value="guru123" required><small>Minimum 6 aksara</small></div>
                    <div class="form-group"><label>Soalan Keselamatan <span style="color:red;">*</span></label><select id="newTeacherSecurityQuestion" class="form-control" required><option value="">Pilih soalan keselamatan</option><?php foreach ($securityQuestions as $sq) { ?><option value="<?php echo $sq['id']; ?>"><?php echo htmlspecialchars($sq['question']); ?></option><?php } ?></select></div>
                    <div class="form-group"><label>Jawapan Keselamatan <span style="color:red;">*</span></label><input type="text" id="newTeacherSecurityAnswer" class="form-control" placeholder="cth: John" required><small>Jawapan tidak sensitif huruf besar/kecil</small></div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal" id="addStudentModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Tambah Pelajar</h3><button class="close-modal" onclick="closeModal('addStudentModal')">&times;</button></div>
            <div class="modal-body">
                <form id="addStudentForm" onsubmit="addStudent(event)">
                    <div class="form-group"><label>No. Matrik <span style="color:red;">*</span></label><input type="text" id="newMatric" class="form-control" required></div>
                    <div class="form-group"><label>Nama Penuh <span style="color:red;">*</span></label><input type="text" id="newStudentName" class="form-control" required></div>
                    <div class="form-group"><label>Kelas <span style="color:red;">*</span></label><select id="newStudentClass" class="form-control" required><option value="">Pilih Kelas</option><?php foreach ($allClasses as $class) { ?><option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option><?php } ?></select></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="newStudentPhone" class="form-control"></div>
                    <div class="form-group"><label>Emel</label><input type="email" id="newStudentEmail" class="form-control"></div>
                    <div class="form-group"><label>No. KP</label><input type="text" id="newStudentIC" class="form-control"></div>
                    <div class="form-group"><label>Kata Laluan <span style="color:red;">*</span></label><input type="text" id="newStudentPassword" class="form-control" value="student123" required><small>Minimum 6 aksara</small></div>
                    <div class="form-group"><label>Soalan Keselamatan <span style="color:red;">*</span></label><select id="newStudentSecurityQuestion" class="form-control" required><option value="">Pilih soalan keselamatan</option><?php foreach ($securityQuestions as $sq) { ?><option value="<?php echo $sq['id']; ?>"><?php echo htmlspecialchars($sq['question']); ?></option><?php } ?></select></div>
                    <div class="form-group"><label>Jawapan Keselamatan <span style="color:red;">*</span></label><input type="text" id="newStudentSecurityAnswer" class="form-control" placeholder="cth: John" required><small>Jawapan tidak sensitif huruf besar/kecil</small></div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal" id="addSubjectModal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-book"></i> Tambah Subjek Baru</h3><button class="close-modal" onclick="closeModal('addSubjectModal')">&times;</button></div>
            <div class="modal-body">
                <form id="addSubjectForm" onsubmit="addSubject(event)">
                    <div class="form-group"><label>Kod Subjek <span style="color:red;">*</span></label><input type="text" id="newSubjectCode" class="form-control" placeholder="cth: 9001, 9010, STPM001" required><small>Contoh: 9001 untuk Bahasa Melayu, 9010 untuk Pengajian Am</small></div>
                    <div class="form-group"><label>Nama Subjek <span style="color:red;">*</span></label><input type="text" id="newSubjectName" class="form-control" placeholder="cth: Bahasa Melayu, Pengajian Am" required></div>
                    <div class="form-group"><label>Aliran <span style="color:red;">*</span></label><select id="newSubjectStream" class="form-control" required><option value="">Pilih Aliran</option><option value="Sains">Sains</option><option value="Sastera">Sastera</option><option value="Teknikal">Teknikal</option><option value="Semua">Semua (Untuk semua aliran)</option></select></div>
                    <div class="alert alert-info" style="font-size: 0.8rem; margin-top: 1rem;"><i class="fas fa-info-circle"></i> <span>Subjek yang ditambah akan tersedia untuk diassign ke kelas.</span></div>
                    <button type="submit" class="btn btn-success" style="margin-top: 10px;"><i class="fas fa-save"></i> Simpan Subjek</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Class Modal -->
    <div class="modal" id="addClassModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Tambah Kelas</h3><button class="close-modal" onclick="closeModal('addClassModal')">&times;</button></div>
            <div class="modal-body">
                <form id="addClassForm" onsubmit="addClass(event)">
                    <div class="form-group"><label>Nama Kelas <span style="color:red;">*</span></label><input type="text" id="newClassName" class="form-control" required></div>
                    <div class="form-group"><label>Guru Kelas</label><select id="newClassTeacher" class="form-control"><option value="">-- Tiada Guru Kelas --</option><?php foreach ($allTeachers as $teacher) { ?><option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name'].' ('.$teacher['teacher_id'].')'); ?></option><?php } ?></select></div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal" id="editClassModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Kelas</h3><button class="close-modal" onclick="closeModal('editClassModal')">&times;</button></div>
            <div class="modal-body">
                <form id="editClassForm" onsubmit="editClass(event)">
                    <input type="hidden" id="editClassId">
                    <div class="form-group"><label>Nama Kelas <span style="color:red;">*</span></label><input type="text" id="editClassName" class="form-control" required></div>
                    <div class="form-group"><label>Guru Kelas</label><select id="editClassTeacher" class="form-control"><option value="">-- Tiada Guru Kelas --</option><?php foreach ($allTeachers as $teacher) { ?><option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name'].' ('.$teacher['teacher_id'].')'); ?></option><?php } ?></select></div>
                    <button type="submit" class="btn btn-success">Kemaskini</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal" id="editAdminModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Admin</h3><button class="close-modal" onclick="closeModal('editAdminModal')">&times;</button></div>
            <div class="modal-body">
                <form id="editAdminForm" onsubmit="editAdmin(event)">
                    <input type="hidden" id="editAdminId">
                    <div class="form-group"><label>ID Admin <span style="color:red;">*</span></label><input type="text" id="editAdminCode" class="form-control" required></div>
                    <div class="form-group"><label>Nama Penuh <span style="color:red;">*</span></label><input type="text" id="editAdminName" class="form-control" required></div>
                    <div class="form-group"><label>Emel <span style="color:red;">*</span></label><input type="email" id="editAdminEmail" class="form-control" required></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="editAdminPhone" class="form-control"></div>
                    <div class="form-group"><label>Tahap Admin <span style="color:red;">*</span></label><select id="editAdminLevel" class="form-control"><option value="admin">Admin Biasa</option><option value="super_admin">Super Admin</option></select></div>
                    <div class="form-group"><label>Kata Laluan Baru (kosongkan jika tidak ubah)</label><input type="password" id="editAdminPassword" class="form-control" placeholder="Biarkan kosong jika tidak ubah"><small>Minimum 6 aksara jika diisi</small></div>
                    <button type="submit" class="btn btn-success">Kemaskini</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal" id="editTeacherModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Guru</h3><button class="close-modal" onclick="closeModal('editTeacherModal')">&times;</button></div>
            <div class="modal-body">
                <form id="editTeacherForm" onsubmit="editTeacher(event)">
                    <input type="hidden" id="editTeacherRecordId">
                    <div class="form-group"><label>ID Guru <span style="color:red;">*</span></label><input type="text" id="editTeacherId" class="form-control" required></div>
                    <div class="form-group"><label>Nama Penuh <span style="color:red;">*</span></label><input type="text" id="editTeacherName" class="form-control" required></div>
                    <div class="form-group"><label>Jenis Guru <span style="color:red;">*</span></label><select id="editTeacherType" class="form-control" required><option value="guru_subjek">Guru Subjek</option><option value="guru_kelas">Guru Kelas</option></select></div>
                    <div class="form-group"><label>Emel <span style="color:red;">*</span></label><input type="email" id="editTeacherEmail" class="form-control" required></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="editTeacherPhone" class="form-control"></div>
                    <div class="form-group"><label>Kata Laluan Baru (kosongkan jika tidak ubah)</label><input type="password" id="editTeacherPassword" class="form-control" placeholder="Biarkan kosong jika tidak ubah"><small>Minimum 6 aksara jika diisi</small></div>
                    <button type="submit" class="btn btn-success">Kemaskini</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal" id="editStudentModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Edit Pelajar</h3><button class="close-modal" onclick="closeModal('editStudentModal')">&times;</button></div>
            <div class="modal-body">
                <form id="editStudentForm" onsubmit="editStudent(event)">
                    <input type="hidden" id="editStudentId">
                    <div class="form-group"><label>No. Matrik <span style="color:red;">*</span></label><input type="text" id="editMatric" class="form-control" required></div>
                    <div class="form-group"><label>Nama Penuh <span style="color:red;">*</span></label><input type="text" id="editStudentName" class="form-control" required></div>
                    <div class="form-group"><label>Kelas <span style="color:red;">*</span></label><select id="editStudentClass" class="form-control" required><option value="">Pilih Kelas</option><?php foreach ($allClasses as $class) { ?><option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option><?php } ?></select></div>
                    <div class="form-group"><label>No. Telefon</label><input type="text" id="editStudentPhone" class="form-control"></div>
                    <div class="form-group"><label>Emel</label><input type="email" id="editStudentEmail" class="form-control"></div>
                    <div class="form-group"><label>No. KP</label><input type="text" id="editStudentIC" class="form-control"></div>
                    <div class="form-group"><label>Kata Laluan Baru (kosongkan jika tidak ubah)</label><input type="password" id="editStudentPassword" class="form-control" placeholder="Biarkan kosong jika tidak ubah"><small>Minimum 6 aksara jika diisi</small></div>
                    <button type="submit" class="btn btn-success">Kemaskini</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal" id="editSubjectModal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-book"></i> Edit Subjek</h3><button class="close-modal" onclick="closeModal('editSubjectModal')">&times;</button></div>
            <div class="modal-body">
                <form id="editSubjectForm" onsubmit="editSubject(event)">
                    <input type="hidden" id="editSubjectId">
                    <div class="form-group"><label>Kod Subjek <span style="color:red;">*</span></label><input type="text" id="editSubjectCode" class="form-control" required></div>
                    <div class="form-group"><label>Nama Subjek <span style="color:red;">*</span></label><input type="text" id="editSubjectName" class="form-control" required></div>
                    <div class="form-group"><label>Aliran <span style="color:red;">*</span></label><select id="editSubjectStream" class="form-control" required><option value="Sains">Sains</option><option value="Sastera">Sastera</option><option value="Teknikal">Teknikal</option><option value="Semua">Semua (Untuk semua aliran)</option></select></div>
                    <button type="submit" class="btn btn-success" style="margin-top: 10px;"><i class="fas fa-save"></i> Kemaskini Subjek</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Exam Modal -->
    <div class="modal" id="editExamModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header"><h3>Edit Peperiksaan</h3><button class="close-modal" onclick="closeModal('editExamModal')">&times;</button></div>
            <div class="modal-body">
                <form id="editExamForm" onsubmit="editExam(event)">
                    <input type="hidden" id="editExamId">
                    <div class="row-2cols">
                        <div class="form-group"><label>Nama Peperiksaan <span style="color:red;">*</span></label><input type="text" id="editExamName" class="form-control" required></div>
                        <div class="form-group"><label>Jenis Peperiksaan <span style="color:red;">*</span></label><select id="editExamType" class="form-control" required><option value="">Pilih Jenis</option><?php foreach ($examTypes as $et) { ?><option value="<?php echo $et['id']; ?>"><?php echo htmlspecialchars($et['exam_name'].' ('.$et['exam_code'].')'); ?></option><?php } ?></select></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Semester <span style="color:red;">*</span></label><select id="editExamSemester" class="form-control" required><option value="1">Semester 1</option><option value="2">Semester 2</option><option value="3">Semester 3</option></select></div>
                        <div class="form-group"><label>Tahun Akademik <span style="color:red;">*</span></label><input type="text" id="editExamYear" class="form-control" placeholder="2024/2025" required></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Tarikh Peperiksaan <span style="color:red;">*</span></label><input type="date" id="editExamDate" class="form-control" required></div>
                        <div class="form-group"><label>Tempat</label><input type="text" id="editExamVenue" class="form-control" placeholder="Dewan / Bilik Darjah"></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Masa Mula</label><input type="time" id="editExamStart" class="form-control"></div>
                        <div class="form-group"><label>Masa Tamat</label><input type="time" id="editExamEnd" class="form-control"></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Jumlah Markah</label><input type="number" id="editExamTotalMarks" class="form-control" value="100"></div>
                        <div class="form-group"><label>Markah Lulus</label><input type="number" id="editExamPassing" class="form-control" value="50"></div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="editExamStatus" class="form-control">
                            <option value="Aktif">Aktif</option>
                            <option value="Draf">Draf</option>
                            <option value="Selesai">Selesai</option>
                            <option value="Batal">Batal</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Kemaskini Peperiksaan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Exam Modal -->
    <div class="modal" id="addExamModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header"><h3>Tambah Peperiksaan</h3><button class="close-modal" onclick="closeModal('addExamModal')">&times;</button></div>
            <div class="modal-body">
                <form id="addExamForm" onsubmit="addExam(event)">
                    <div class="row-2cols">
                        <div class="form-group"><label>Nama Peperiksaan <span style="color:red;">*</span></label><input type="text" id="newExamName" class="form-control" required></div>
                        <div class="form-group"><label>Jenis Peperiksaan <span style="color:red;">*</span></label><select id="newExamType" class="form-control" required><option value="">Pilih Jenis</option><?php foreach ($examTypes as $et) { ?><option value="<?php echo $et['id']; ?>"><?php echo htmlspecialchars($et['exam_name'].' ('.$et['exam_code'].')'); ?></option><?php } ?></select></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Semester <span style="color:red;">*</span></label><select id="newExamSemester" class="form-control" required><option value="1">Semester 1</option><option value="2">Semester 2</option><option value="3">Semester 3</option></select></div>
                        <div class="form-group"><label>Tahun Akademik <span style="color:red;">*</span></label><input type="text" id="newExamYear" class="form-control" placeholder="2024/2025" required></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Tarikh Peperiksaan <span style="color:red;">*</span></label><input type="date" id="newExamDate" class="form-control" required></div>
                        <div class="form-group"><label>Tempat</label><input type="text" id="newExamVenue" class="form-control" placeholder="Dewan / Bilik Darjah"></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Masa Mula <span style="color:red;">*</span></label><input type="time" id="newExamStart" class="form-control" required></div>
                        <div class="form-group"><label>Masa Tamat <span style="color:red;">*</span></label><input type="time" id="newExamEnd" class="form-control" required></div>
                    </div>
                    <div class="row-2cols">
                        <div class="form-group"><label>Jumlah Markah</label><input type="number" id="newExamTotalMarks" class="form-control" value="100" min="0" max="100"></div>
                        <div class="form-group"><label>Markah Lulus</label><input type="number" id="newExamPassing" class="form-control" value="50" min="0" max="100"></div>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top: 10px;"><i class="fas fa-save"></i> Simpan</button>
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
                    <div class="form-group"><label>Kata Laluan Semasa <span style="color:red;">*</span></label><input type="password" id="currentPwd" class="form-control" required></div>
                    <div class="form-group"><label>Kata Laluan Baru <span style="color:red;">*</span></label><input type="password" id="newPwd" class="form-control" required></div>
                    <div class="form-group"><label>Sahkan <span style="color:red;">*</span></label><input type="password" id="confirmPwd" class="form-control" required></div>
                    <button type="submit" class="btn btn-success">Tukar</button>
                </form>
            </div>
        </div>
    </div>

    <footer><div class="container"><p>&copy; 2025 Sistem Peperiksaan STPM | Super Admin Panel</p></div></footer>

    <script>
        // Init Charts
        function initCharts() {
            if (typeof Chart === 'undefined') return;
            Chart.defaults.color = '#a1a1aa';
            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

            const userStats = [<?php echo $totalStudents; ?>, <?php echo $totalTeachers; ?>, <?php echo $totalAdmins; ?>];
            const userCanvas = document.getElementById('userStatsChart');
            if (userCanvas) {
                new Chart(userCanvas.getContext('2d'), { 
                    type: 'doughnut', 
                    data: { 
                        labels: ['Pelajar', 'Guru', 'Admin'], 
                        datasets: [{ 
                            data: userStats, 
                            backgroundColor: ['#7c3aed', '#10b981', '#f59e0b'],
                            borderColor: 'rgba(255, 255, 255, 0.08)',
                            borderWidth: 2,
                            hoverOffset: 6
                        }] 
                    }, 
                    options: { 
                        responsive: true,
                        plugins: {
                            legend: {
                                labels: { color: '#e4e4e7', font: { weight: 500 } }
                            }
                        }
                    } 
                });
            }

            const academicCanvas = document.getElementById('academicChart');
            if (academicCanvas) {
                new Chart(academicCanvas.getContext('2d'), { 
                    type: 'bar', 
                    data: { 
                        labels: ['PNG Purata', 'Kadar Kelulusan (%)'], 
                        datasets: [{ 
                            label: 'Prestasi', 
                            data: [<?php echo $avgPng; ?>, <?php echo $passRate; ?>], 
                            backgroundColor: ['#7c3aed', '#ec4899'], 
                            borderRadius: 8 
                        }] 
                    }, 
                    options: { 
                        responsive: true, 
                        plugins: {
                            legend: {
                                labels: { color: '#e4e4e7' }
                            }
                        },
                        scales: { 
                            y: { 
                                beginAtZero: true,
                                grid: { color: 'rgba(255, 255, 255, 0.06)' },
                                ticks: { color: '#a1a1aa' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#a1a1aa' }
                            }
                        } 
                    } 
                });
            }
        }

        // Navigation
        function showSection(sectionId) {
            document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
            const target = document.getElementById(sectionId);
            if (target) target.classList.add('active');
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            let activeLink = Array.from(document.querySelectorAll('.nav-link')).find(l => l.dataset.section === sectionId);
            if (activeLink) activeLink.classList.add('active');
        }

        // Filter Functions
        function filterAdmins() {
            const search = document.getElementById('searchAdmin').value.toLowerCase();
            const level = document.getElementById('filterAdminLevel').value;
            const rows = document.querySelectorAll('#adminsTable tr');
            rows.forEach(row => {
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const id = row.cells[0]?.textContent.toLowerCase() || '';
                const rowLevel = row.getAttribute('data-level') || '';
                let show = true;
                if (search && !name.includes(search) && !id.includes(search)) show = false;
                if (level && rowLevel !== level) show = false;
                row.style.display = show ? '' : 'none';
            });
        }

        function filterTeachers() {
            const search = document.getElementById('searchTeacher').value.toLowerCase();
            const type = document.getElementById('filterTeacherType').value;
            const rows = document.querySelectorAll('#teachersTable tr');
            rows.forEach(row => {
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const id = row.cells[0]?.textContent.toLowerCase() || '';
                const rowType = row.getAttribute('data-type') || '';
                let show = true;
                if (search && !name.includes(search) && !id.includes(search)) show = false;
                if (type && rowType !== type) show = false;
                row.style.display = show ? '' : 'none';
            });
        }

        function filterStudents() {
            const search = document.getElementById('searchStudent').value.toLowerCase();
            const kelas = document.getElementById('filterClass').value;
            const rows = document.querySelectorAll('#studentsTable tr');
            rows.forEach(row => {
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const matric = row.cells[0]?.textContent.toLowerCase() || '';
                const rowKelas = row.getAttribute('data-class') || '';
                let show = true;
                if (search && !name.includes(search) && !matric.includes(search)) show = false;
                if (kelas && rowKelas !== kelas) show = false;
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

        // Modal Functions
        function openAddAdminModal() { document.getElementById('addAdminModal').style.display = 'flex'; }
        function openAddTeacherModal() { document.getElementById('addTeacherModal').style.display = 'flex'; }
        function openAddStudentModal() { document.getElementById('addStudentModal').style.display = 'flex'; }
        function openAddSubjectModal() { document.getElementById('addSubjectModal').style.display = 'flex'; }
        function openAddClassModal() { document.getElementById('addClassModal').style.display = 'flex'; }
        function openAddExamModal() { document.getElementById('addExamModal').style.display = 'flex'; }
        function openChangePasswordModal() { document.getElementById('changePasswordModal').style.display = 'flex'; }
        function openEditClassModal(id, name, teacherId) {
            document.getElementById('editClassId').value = id;
            document.getElementById('editClassName').value = name;
            document.getElementById('editClassTeacher').value = teacherId || '';
            document.getElementById('editClassModal').style.display = 'flex';
        }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        // CRUD Functions
        function addAdmin(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'add_admin');
            fd.append('admin_id', document.getElementById('newAdminId').value);
            fd.append('name', document.getElementById('newAdminName').value);
            fd.append('email', document.getElementById('newAdminEmail').value);
            fd.append('phone', document.getElementById('newAdminPhone').value);
            fd.append('admin_level', document.getElementById('newAdminLevel').value);
            fd.append('password', document.getElementById('newAdminPassword').value);
            fd.append('security_question', document.getElementById('newAdminSecurityQuestion').value);
            fd.append('security_answer', document.getElementById('newAdminSecurityAnswer').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addAdminModal'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); });
        }

        function addTeacher(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'add_teacher');
            fd.append('teacher_id', document.getElementById('newTeacherId').value);
            fd.append('name', document.getElementById('newTeacherName').value);
            fd.append('type', document.getElementById('newTeacherType').value);
            fd.append('email', document.getElementById('newTeacherEmail').value);
            fd.append('phone', document.getElementById('newTeacherPhone').value);
            fd.append('password', document.getElementById('newTeacherPassword').value);
            fd.append('security_question', document.getElementById('newTeacherSecurityQuestion').value);
            fd.append('security_answer', document.getElementById('newTeacherSecurityAnswer').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addTeacherModal'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); });
        }

        function addStudent(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'add_student');
            fd.append('matric', document.getElementById('newMatric').value);
            fd.append('name', document.getElementById('newStudentName').value);
            fd.append('class_id', document.getElementById('newStudentClass').value);
            fd.append('phone', document.getElementById('newStudentPhone').value);
            fd.append('email', document.getElementById('newStudentEmail').value);
            fd.append('ic_number', document.getElementById('newStudentIC').value);
            fd.append('password', document.getElementById('newStudentPassword').value);
            fd.append('security_question', document.getElementById('newStudentSecurityQuestion').value);
            fd.append('security_answer', document.getElementById('newStudentSecurityAnswer').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => { if(d.success) { showNotification(d.message, 'success'); closeModal('addStudentModal'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); });
        }

        function addSubject(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'add_subject');
            fd.append('code', document.getElementById('newSubjectCode').value);
            fd.append('name', document.getElementById('newSubjectName').value);
            fd.append('stream', document.getElementById('newSubjectStream').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => { if(d.success) { showNotification('Subjek ditambah!', 'success'); closeModal('addSubjectModal'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); });
        }

        function addClass(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'add_class');
            fd.append('class_name', document.getElementById('newClassName').value);
            fd.append('teacher_id', document.getElementById('newClassTeacher').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => { if(d.success) { showNotification('Kelas ditambah!', 'success'); closeModal('addClassModal'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); });
        }

        function editClass(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'edit_class');
            fd.append('class_id', document.getElementById('editClassId').value);
            fd.append('class_name', document.getElementById('editClassName').value);
            fd.append('teacher_id', document.getElementById('editClassTeacher').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => { if(d.success) { showNotification('Kelas dikemaskini!', 'success'); closeModal('editClassModal'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); });
        }

        function addExam(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'add_exam');
            fd.append('exam_name', document.getElementById('newExamName').value);
            fd.append('exam_type_id', document.getElementById('newExamType').value);
            fd.append('semester', document.getElementById('newExamSemester').value);
            fd.append('academic_year', document.getElementById('newExamYear').value);
            fd.append('exam_date', document.getElementById('newExamDate').value);
            fd.append('start_time', document.getElementById('newExamStart').value);
            fd.append('end_time', document.getElementById('newExamEnd').value);
            fd.append('venue', document.getElementById('newExamVenue').value);
            fd.append('total_marks', document.getElementById('newExamTotalMarks').value);
            fd.append('passing_marks', document.getElementById('newExamPassing').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => { if(d.success) { showNotification('Peperiksaan ditambah!', 'success'); closeModal('addExamModal'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); });
        }

        // Delete Functions
        function deleteAdmin(id) { if(confirm('Padam admin ini?')) { const fd = new FormData(); fd.append('action', 'delete_admin'); fd.append('id', id); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Admin dipadam','success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
        function deleteTeacher(id) { if(confirm('Padam guru ini?')) { const fd = new FormData(); fd.append('action', 'delete_teacher'); fd.append('id', id); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Guru dipadam','success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
        function deleteStudent(id) { if(confirm('Padam pelajar ini?')) { const fd = new FormData(); fd.append('action', 'delete_student'); fd.append('id', id); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Pelajar dipadam','success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
        function deleteSubject(id) { if(confirm('Padam subjek ini?')) { const fd = new FormData(); fd.append('action', 'delete_subject'); fd.append('id', id); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Subjek dipadam','success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
        function deleteClass(id) { if(confirm('Padam kelas ini?')) { const fd = new FormData(); fd.append('action', 'delete_class'); fd.append('id', id); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Kelas dipadam','success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }
        function deleteExam(id) { if(confirm('Batalkan peperiksaan ini?')) { const fd = new FormData(); fd.append('action', 'delete_exam'); fd.append('id', id); fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Peperiksaan dibatalkan','success'); setTimeout(()=>location.reload(),1000); } else showNotification(d.message,'error'); }); } }

        function openEditAdminModalFromBtn(btn) {
            document.getElementById('editAdminId').value = btn.dataset.id;
            document.getElementById('editAdminCode').value = btn.dataset.adminId;
            document.getElementById('editAdminName').value = btn.dataset.name;
            document.getElementById('editAdminLevel').value = btn.dataset.level;
            document.getElementById('editAdminEmail').value = btn.dataset.email;
            document.getElementById('editAdminPhone').value = btn.dataset.phone || '';
            document.getElementById('editAdminPassword').value = '';
            document.getElementById('editAdminModal').style.display = 'flex';
        }

        function editAdmin(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'edit_admin');
            fd.append('id', document.getElementById('editAdminId').value);
            fd.append('admin_id', document.getElementById('editAdminCode').value);
            fd.append('name', document.getElementById('editAdminName').value);
            fd.append('admin_level', document.getElementById('editAdminLevel').value);
            fd.append('email', document.getElementById('editAdminEmail').value);
            fd.append('phone', document.getElementById('editAdminPhone').value);
            fd.append('password', document.getElementById('editAdminPassword').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => {
                if(d.success) {
                    showNotification(d.message, 'success');
                    closeModal('editAdminModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(d.message, 'error');
                }
            });
        }

        function openEditTeacherModalFromBtn(btn) {
            document.getElementById('editTeacherRecordId').value = btn.dataset.id;
            document.getElementById('editTeacherId').value = btn.dataset.teacherId;
            document.getElementById('editTeacherName').value = btn.dataset.name;
            document.getElementById('editTeacherType').value = btn.dataset.type;
            document.getElementById('editTeacherEmail').value = btn.dataset.email;
            document.getElementById('editTeacherPhone').value = btn.dataset.phone || '';
            document.getElementById('editTeacherPassword').value = '';
            document.getElementById('editTeacherModal').style.display = 'flex';
        }

        function editTeacher(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'edit_teacher');
            fd.append('id', document.getElementById('editTeacherRecordId').value);
            fd.append('teacher_id', document.getElementById('editTeacherId').value);
            fd.append('name', document.getElementById('editTeacherName').value);
            fd.append('type', document.getElementById('editTeacherType').value);
            fd.append('email', document.getElementById('editTeacherEmail').value);
            fd.append('phone', document.getElementById('editTeacherPhone').value);
            fd.append('password', document.getElementById('editTeacherPassword').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => {
                if(d.success) {
                    showNotification(d.message, 'success');
                    closeModal('editTeacherModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(d.message, 'error');
                }
            });
        }

        function openEditStudentModalFromBtn(btn) {
            document.getElementById('editStudentId').value = btn.dataset.id;
            document.getElementById('editMatric').value = btn.dataset.matric;
            document.getElementById('editStudentName').value = btn.dataset.name;
            document.getElementById('editStudentClass').value = btn.dataset.classId || '';
            document.getElementById('editStudentPhone').value = btn.dataset.phone || '';
            document.getElementById('editStudentEmail').value = btn.dataset.email || '';
            document.getElementById('editStudentIC').value = btn.dataset.ic || '';
            document.getElementById('editStudentPassword').value = '';
            document.getElementById('editStudentModal').style.display = 'flex';
        }

        function editStudent(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('action', 'edit_student');
            fd.append('id', document.getElementById('editStudentId').value);
            fd.append('matric', document.getElementById('editMatric').value);
            fd.append('name', document.getElementById('editStudentName').value);
            fd.append('class_id', document.getElementById('editStudentClass').value);
            fd.append('phone', document.getElementById('editStudentPhone').value);
            fd.append('email', document.getElementById('editStudentEmail').value);
            fd.append('ic_number', document.getElementById('editStudentIC').value);
            fd.append('password', document.getElementById('editStudentPassword').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json()).then(d => {
                if(d.success) {
                    showNotification(d.message, 'success');
                    closeModal('editStudentModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(d.message, 'error');
                }
            });
        }

        function openEditSubjectModalFromBtn(btn) {
            document.getElementById('editSubjectId').value = btn.dataset.id;
            document.getElementById('editSubjectCode').value = btn.dataset.code;
            document.getElementById('editSubjectName').value = btn.dataset.name;
            document.getElementById('editSubjectStream').value = btn.dataset.stream;
            document.getElementById('editSubjectModal').style.display = 'flex';
        }

        function editSubject(e) {
            e.preventDefault();
            const fd = new FormData();
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
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(d.message, 'error');
                }
            });
        }

        function openEditExamModalFromBtn(btn) {
            document.getElementById('editExamId').value = btn.dataset.id;
            document.getElementById('editExamName').value = btn.dataset.name;
            document.getElementById('editExamType').value = btn.dataset.type;
            document.getElementById('editExamSemester').value = btn.dataset.semester;
            document.getElementById('editExamYear').value = btn.dataset.year;
            document.getElementById('editExamDate').value = btn.dataset.date;
            document.getElementById('editExamStart').value = btn.dataset.start;
            document.getElementById('editExamEnd').value = btn.dataset.end;
            document.getElementById('editExamVenue').value = btn.dataset.venue || '';
            document.getElementById('editExamTotalMarks').value = btn.dataset.total || 100;
            document.getElementById('editExamPassing').value = btn.dataset.passing || 50;
            document.getElementById('editExamStatus').value = btn.dataset.status || 'Aktif';
            document.getElementById('editExamModal').style.display = 'flex';
        }

        function editExam(e) {
            e.preventDefault();
            const fd = new FormData();
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
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(d.message, 'error');
                }
            });
        }

        // System Functions
        function backupDatabase() {
            showNotification('Memulakan sandaran pangkalan data...', 'info');
            const fd = new FormData();
            fd.append('action', 'backup_database');
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    showNotification(d.message, 'success');
                    const a = document.createElement('a');
                    a.href = d.download_url;
                    a.download = d.filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                } else {
                    showNotification(d.message, 'error');
                }
            })
            .catch(err => {
                showNotification('Ralat semasa membuat sandaran pangkalan data', 'error');
            });
        }
        function clearCache() { showNotification('Cache sistem berjaya dikemaskini', 'success'); }

        // Profile Functions
        function changePassword(e) {
            e.preventDefault();
            let newPwd = document.getElementById('newPwd').value;
            let confirmPwd = document.getElementById('confirmPwd').value;
            if(newPwd !== confirmPwd) { showNotification('Kata laluan tidak sepadan!', 'error'); return; }
            if(newPwd.length < 6) { showNotification('Minimum 6 aksara!', 'error'); return; }
            const fd = new FormData();
            fd.append('action', 'change_password');
            fd.append('current_password', document.getElementById('currentPwd').value);
            fd.append('new_password', newPwd);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Kata laluan ditukar!','success'); closeModal('changePasswordModal'); e.target.reset(); } else showNotification(d.message,'error'); });
        }

        function updateProfile() {
            const fd = new FormData();
            fd.append('action', 'update_profile');
            fd.append('name', document.getElementById('adminFullName').value);
            fd.append('email', document.getElementById('adminEmail').value);
            fd.append('phone', document.getElementById('adminPhone').value);
            fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r=>r.json()).then(d=>{ if(d.success){ showNotification('Profil dikemaskini','success'); document.getElementById('adminName').textContent = document.getElementById('adminFullName').value; } else showNotification(d.message,'error'); });
        }

        // Utilities
        function showNotification(msg, type) { 
            let toast = document.createElement('div'); 
            toast.className = 'toast-notification'; 
            toast.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'; 
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}`;
            document.body.appendChild(toast); 
            setTimeout(() => toast.remove(), 3000); 
        }

        // Event Listeners
        document.getElementById('adminFullName')?.addEventListener('change', updateProfile);
        document.getElementById('adminEmail')?.addEventListener('change', updateProfile);
        document.getElementById('adminPhone')?.addEventListener('change', updateProfile);
        document.querySelectorAll('.nav-link').forEach(link => { link.addEventListener('click', function(e) { e.preventDefault(); const sectionId = this.getAttribute('data-section'); if (sectionId) showSection(sectionId); }); });
        document.addEventListener('DOMContentLoaded', () => { initCharts(); showSection('dashboard'); });
        window.onclick = e => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
    </script>
</body>
</html>