<?php
require_once 'db_config.php';

// رسائل الحالة
$status_type = ""; $status_msg = "";

// --- 1. منطق الحذف ---
if (isset($_GET['delete_id'])) {
    $id = filter_var($_GET['delete_id'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM students WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM instructors WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
        $pdo->commit();
        $status_type = "success"; $status_msg = "تم حذف المستخدم بنجاح!";
    } catch (Exception $e) { $pdo->rollBack(); $status_type = "error"; $status_msg = "فشل الحذف!"; }
}

// --- 2. منطق التعديل المطور ---
if (isset($_POST['update_user'])) {
    $id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $username = htmlspecialchars(strip_tags($_POST['username']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $role_id = filter_var($_POST['role_id'], FILTER_SANITIZE_NUMBER_INT);
    $program_id = isset($_POST['program_id']) ? filter_var($_POST['program_id'], FILTER_SANITIZE_NUMBER_INT) : null;
    
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    try {
        $pdo->beginTransaction();

        $stmtCheck = $pdo->prepare("SELECT password_hash, login_attempts, lock_until FROM users WHERE user_id = ?");
        $stmtCheck->execute([$id]);
        $currentUser = $stmtCheck->fetch();

        if ($currentUser['lock_until'] && strtotime($currentUser['lock_until']) > time()) {
            $diff = strtotime($currentUser['lock_until']) - time();
            $minutes = ceil($diff / 60);
            throw new Exception("هذا الحساب محظور حالياً. تبقى $minutes دقيقة.");
        }

        $update_pass = false;
        if (!empty($new_password)) {
            if (password_verify($old_password, $currentUser['password_hash'])) {
                $update_pass = true;
                $pdo->prepare("UPDATE users SET login_attempts = 0, lock_until = NULL WHERE user_id = ?")->execute([$id]);
            } else {
                $new_attempts = $currentUser['login_attempts'] + 1;
                if ($new_attempts >= 3) {
                    $lock_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $pdo->prepare("UPDATE users SET login_attempts = ?, lock_until = ? WHERE user_id = ?")->execute([$new_attempts, $lock_time, $id]);
                    $pdo->commit();
                    throw new Exception("كلمة المرور القديمة خطأ! تم الحظر لساعة.");
                } else {
                    $pdo->prepare("UPDATE users SET login_attempts = ? WHERE user_id = ?")->execute([$new_attempts, $id]);
                    $pdo->commit();
                    throw new Exception("كلمة المرور القديمة خطأ! محاولة ($new_attempts من 3)");
                }
            }
        }

        if ($update_pass) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role_id = ?, password_hash = ? WHERE user_id = ?");
            $stmt->execute([$username, $email, $role_id, $hashed_password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE user_id = ?");
            $stmt->execute([$username, $email, $role_id, $id]);
        }

        // تحديث البيانات في الجداول الفرعية بناءً على الدور
        if ($role_id == 3) { // طالب
            $pdo->prepare("UPDATE students SET name = ?, program_id = ? WHERE user_id = ?")->execute([$username, $program_id, $id]);
        } elseif ($role_id == 2) { // مدرس
            $pdo->prepare("UPDATE instructors SET name = ? WHERE user_id = ?")->execute([$username, $id]);
        }

        $pdo->commit();
        $status_type = "success"; $status_msg = "تم تحديث بيانات $username بنجاح!";
        
    } catch (Exception $e) { 
        if ($pdo->inTransaction()) $pdo->rollBack(); 
        $status_type = "error"; $status_msg = $e->getMessage(); 
    }
}

// --- 3. منطق الإضافة (التعديل الجوهري هنا) ---
if (isset($_POST['add_user'])) {
    $username = htmlspecialchars(strip_tags($_POST['username']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role_id = (int)$_POST['role_id'];
    $program_id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : null;

    try {
        $pdo->beginTransaction();
        
        // 1. إضافة المستخدم في جدول users
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role_id]);
        $new_id = $pdo->lastInsertId();

        // 2. إذا كان "طالب" (Role 3)
        if ($role_id == 3) {
            $pdo->prepare("INSERT INTO students (student_id, user_id, name, program_id) VALUES (?, ?, ?, ?)")
                ->execute([$new_id, $new_id, $username, $program_id]);
        } 
        // 3. إذا كان "مدرس" (Role 2) - هذا ما كان ينقصك!
        elseif ($role_id == 2) {
            $pdo->prepare("INSERT INTO instructors (instructor_id, user_id, name) VALUES (?, ?, ?)")
                ->execute([$new_id, $new_id, $username]);
        }

        $pdo->commit();
        $status_type = "success"; $status_msg = "تم إنشاء حساب " . ($role_id == 2 ? "المدرس" : "العضو") . " بنجاح!";
    } catch (Exception $e) { 
        $pdo->rollBack(); 
        $status_type = "error"; 
        $status_msg = "فشل الإضافة: " . $e->getMessage(); 
    }
}

// جلب البيانات للعرض
$users = $pdo->query("SELECT u.*, r.role_name, p.name as program_name, p.program_id 
                      FROM users u 
                      INNER JOIN roles r ON u.role_id = r.role_id 
                      LEFT JOIN students s ON u.user_id = s.user_id 
                      LEFT JOIN programs p ON s.program_id = p.program_id 
                      ORDER BY u.user_id DESC")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$programs = $pdo->query("SELECT * FROM programs")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الأعضاء | Zahra Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #2F4156; --accent: #04AFC9; --navy: #2F496E; --bg: #F8F7F3; --soft: #C8D9E6; --white: #FFFFFF; --gold: #C2B2A3; --glass: rgba(255, 255, 255, 0.8); --danger: #e74c3c; }
        body { font-family: 'Cairo', sans-serif; background: var(--bg); margin: 0; padding: 0; }
        .main-wrapper { padding: 40px 20px; max-width: 1300px; margin: 0 auto; }
        .premium-header { background: linear-gradient(135deg, var(--primary) 0%, var(--navy) 100%); padding: 40px; border-radius: 30px; color: white; position: relative; margin-bottom: 50px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .premium-header i { position: absolute; left: 40px; top: 50%; transform: translateY(-50%); font-size: 5em; opacity: 0.1; }
        .glass-card { background: var(--glass); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3); border-radius: 25px; padding: 30px; margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end; }
        .input-box label { display: block; margin-bottom: 8px; font-weight: 700; color: var(--navy); font-size: 0.9em; }
        .input-box input, .input-box select { width: 100%; padding: 12px 15px; border: 2px solid var(--soft); border-radius: 12px; transition: 0.3s; box-sizing: border-box; }
        .btn-grad { background: linear-gradient(to right, var(--accent) 0%, var(--navy) 100%); color: white; border: none; padding: 14px 25px; border-radius: 12px; cursor: pointer; font-weight: bold; }
        .table-wrap { border-radius: 25px; overflow: hidden; background: white; box-shadow: 0 15px 40px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f4f7; padding: 20px; }
        td { padding: 18px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        #editOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(5px); }
        .modal-box { background: white; border-radius: 30px; width: 500px; max-width: 95%; position: relative; overflow: hidden; animation: slideUp 0.4s ease; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { background: var(--primary); padding: 20px; color: white; display: flex; align-items: center; gap: 15px; }
        .security-section { background: #fff5f5; border: 1.5px dashed #ffcfcf; border-radius: 15px; padding: 20px; margin: 20px 0; }
        .security-section.active { background: #f0fbff; border-color: var(--accent); }
        
        .floating-back-btn { position: fixed; bottom: 30px; right: 15px; background: linear-gradient(135deg, #2F4156 0%, #2F496E 100%); color: #ffffff !important; padding: 12px 25px; border-radius: 50px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 700; box-shadow: 0 10px 25px rgba(47, 65, 86, 0.4); z-index: 9999; transition: 0.3s; }
        .floating-back-btn:hover { transform: scale(1.1); background: var(--accent); }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="premium-header">
        <h1>إدارة شؤون المستخدمين</h1>
        <p>إدارة الطلاب والمدرسين والمشرفين من مكان واحد.</p>
        <i class="fas fa-user-lock"></i>
    </div>

    <div class="glass-card">
        <h4 style="margin:0 0 15px 0; color:var(--accent)"><i class="fas fa-plus-circle"></i> إضافة مستخدم</h4>
        <form method="POST" class="form-grid">      
            <div class="input-box"><label>الاسم</label><input type="text" name="username" required></div>
            <div class="input-box"><label>البريد</label><input type="email" name="email" required></div>
            <div class="input-box"><label>كلمة المرور</label><input type="password" name="password" required></div>
            <div class="input-box">
                <label>نوع الحساب</label>
                <select name="role_id" onchange="toggleStudentProg(this.value, 'prog_add')">
                    <?php foreach($roles as $r): ?><option value="<?= $r['role_id'] ?>"><?= $r['role_name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="input-box" id="prog_add" style="display:none;">
                <label>التخصص (للطلاب فقط)</label>
                <select name="program_id">
                    <?php foreach($programs as $p): ?><option value="<?= $p['program_id'] ?>"><?= $p['name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn-grad">إنشاء الحساب</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>الاسم</th><th>الصلاحية</th><th>التخصص</th><th>الإجراءات</th></tr></thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= $u['user_id'] ?></td>
                    <td><b><?= htmlspecialchars($u['username']) ?></b></td>
                    <td><?= $u['role_name'] ?></td>
                    <td style="color:var(--accent)"><?= $u['program_name'] ?? '---' ?></td>
                    <td>
                        <button onclick="openEdit('<?= $u['user_id'] ?>', '<?= $u['username'] ?>', '<?= $u['email'] ?>', '<?= $u['role_id'] ?>', '<?= $u['program_id'] ?>')" class="btn-grad" style="padding:8px 15px; background:var(--accent)"><i class="fas fa-edit"></i></button>
                        <button onclick="confirmDel(<?= $u['user_id'] ?>)" class="btn-grad" style="padding:8px 15px; background:var(--gold)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <i class="fas fa-user-edit fa-2x"></i>
            <h3 style="margin:0">تعديل البيانات</h3>
        </div>
        <div style="padding:30px">
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_id">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px">
                    <div class="input-box"><label>الاسم</label><input type="text" name="username" id="edit_name"></div>
                    <div class="input-box"><label>البريد</label><input type="email" name="email" id="edit_email"></div>
                </div>

                <div class="security-section" id="sec_box">
                    <div style="color:var(--navy); margin-bottom:10px"><i class="fas fa-shield-alt"></i> <b>تغيير كلمة المرور</b></div>
                    <div class="input-box" style="margin-bottom:15px">
                        <label>جديدة</label>
                        <input type="password" name="new_password" id="new_pass" oninput="toggleOldPass(this.value)">
                    </div>
                    <div class="input-box" id="old_pass_container" style="display:none;">
                        <label style="color:var(--danger)">القديمة (تأكيد)</label>
                        <input type="password" name="old_password" id="old_pass">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px">
                    <div class="input-box"><label>الصلاحية</label>
                        <select name="role_id" id="edit_role" onchange="toggleStudentProg(this.value, 'prog_edit')">
                            <?php foreach($roles as $r): ?><option value="<?= $r['role_id'] ?>"><?= $r['role_name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-box" id="prog_edit" style="display:none"><label>التخصص</label>
                        <select name="program_id" id="edit_program">
                            <?php foreach($programs as $p): ?><option value="<?= $p['program_id'] ?>"><?= $p['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:flex; gap:10px">
                    <button type="submit" name="update_user" class="btn-grad" style="flex:2">حفظ التغييرات</button>
                    <button type="button" onclick="closeEdit()" class="btn-grad" style="flex:1; background:var(--gold)">إغلاق</button>
                </div>
            </form>
        </div>
    </div>
</div>

<a href="admin_dashboard.php" class="floating-back-btn"><i class="fas fa-arrow-right"></i><span>رجوع</span></a>

<script>
function toggleStudentProg(roleId, divId) {
    // رقم 3 للطالب - رقم 2 للمدرس (تأكدي من قاعدة بياناتك)
    document.getElementById(divId).style.display = (roleId == "3") ? "block" : "none";
}

function toggleOldPass(val) {
    const container = document.getElementById('old_pass_container');
    container.style.display = (val.length > 0) ? "block" : "none";
    document.getElementById('old_pass').required = (val.length > 0);
}

function openEdit(id, name, email, role, progId) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_program').value = (progId && progId !== 'null') ? progId : "";
    toggleStudentProg(role, 'prog_edit');
    document.getElementById('editOverlay').style.display = 'flex';
}

function closeEdit() { document.getElementById('editOverlay').style.display = 'none'; }

function confirmDel(id) {
    Swal.fire({ title: 'هل أنتِ متأكدة؟', icon: 'warning', showCancelButton: true, confirmButtonColor: '#04AFC9', cancelButtonColor: '#C2B2A3', confirmButtonText: 'نعم، احذف', cancelButtonText: 'إلغاء' })
    .then((result) => { if (result.isConfirmed) window.location.href = '?delete_id=' + id; });
}

<?php if($status_type): ?>
    Swal.fire({ icon: '<?= $status_type ?>', title: '<?= $status_msg ?>', confirmButtonColor: '#04AFC9' });
<?php endif; ?>
</script>
</body>
</html>