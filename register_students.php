<?php
require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$status_type = ""; $status_msg = "";

// دالة مساعدة للتأكد من عدم وجود Transaction مفتوحة
function safeBeginTransaction($pdo) {
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }
}

// --- 1. منطق الحذف ---
if (isset($_GET['delete_id'])) {
    $s_id = (int)$_GET['delete_id'];
    try {
        safeBeginTransaction($pdo);
        
        $stmtGet = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
        $stmtGet->execute([$s_id]);
        $row = $stmtGet->fetch();
        
        if ($row) {
            $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$s_id]);
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$row['user_id']]);
            $pdo->commit();
            $status_type = "success"; $status_msg = "تم حذف الطالب وبيانات دخوله بنجاح!";
        } else {
            if ($pdo->inTransaction()) $pdo->rollBack();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $status_type = "error"; $status_msg = "حدث خطأ أثناء محاولة الحذف!";
    }
}

// --- 2. منطق الإضافة ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_save_student'])) {
    try {
        $stmtCheck = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $stmtCheck->execute([$_POST['email']]);
        if ($stmtCheck->rowCount() > 0) { 
            throw new Exception("هذا البريد الإلكتروني مسجل مسبقاً في النظام!"); 
        }

        safeBeginTransaction($pdo);

        $stmtU = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, 3)");
        $stmtU->execute([$_POST['student_name'], $_POST['email'], password_hash($_POST['password'], PASSWORD_BCRYPT)]);
        $new_u_id = $pdo->lastInsertId();

        $res = $pdo->query("SELECT MAX(student_id) as last_id FROM students")->fetch();
        $next_s_id = ($res['last_id']) ? $res['last_id'] + 1 : 1;

        $stmtS = $pdo->prepare("INSERT INTO students (student_id, user_id, name, program_id, level_id, group_name, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmtS->execute([$next_s_id, $new_u_id, $_POST['student_name'], $_POST['program_id'], $_POST['level_id'], $_POST['group_name']]);

        $pdo->commit();
        $status_type = "success"; $status_msg = "تم تسجيل الطالب " . $_POST['student_name'] . " بنجاح!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $status_type = "error"; $status_msg = $e->getMessage();
    }
}

// --- 3. منطق التعديل المطور ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_update_student'])) {
    try {
        $student_id = $_POST['student_id'];
        
        // نظام الحظر (3 محاولات)
        if (isset($_SESSION['lock_until'][$student_id]) && time() < $_SESSION['lock_until'][$student_id]) {
            $remaining = ceil(($_SESSION['lock_until'][$student_id] - time()) / 60);
            throw new Exception("تم حظر التعديل مؤقتاً لتجاوز محاولات كلمة المرور. حاول بعد $remaining دقيقة.");
        }

        // جلب بيانات المستخدم للتحقق
        $stmtUser = $pdo->prepare("SELECT u.user_id, u.password_hash FROM users u JOIN students s ON u.user_id = s.user_id WHERE s.student_id = ?");
        $stmtUser->execute([$student_id]);
        $userData = $stmtUser->fetch();

        // معالجة تغيير كلمة المرور إن وجدت
        if (!empty($_POST['old_password'])) {
            if (password_verify($_POST['old_password'], $userData['password_hash'])) {
                // تصفير المحاولات عند النجاح
                unset($_SESSION['attempts'][$student_id]);
                
                if (!empty($_POST['new_password'])) {
                    $new_hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$new_hash, $userData['user_id']]);
                }
            } else {
                $_SESSION['attempts'][$student_id] = ($_SESSION['attempts'][$student_id] ?? 0) + 1;
                
                if ($_SESSION['attempts'][$student_id] >= 3) {
                    $_SESSION['lock_until'][$student_id] = time() + (15 * 60); // حظر 15 دقيقة
                    throw new Exception("كلمة المرور القديمة خاطئة. تم حظرك من التعديل لمدة 15 دقيقة!");
                }
                throw new Exception("كلمة المرور القديمة خاطئة! محاولاتك المتبقية: " . (3 - $_SESSION['attempts'][$student_id]));
            }
        }

        // تحديث البيانات الأساسية
        $stmt = $pdo->prepare("UPDATE students SET name = ?, program_id = ?, level_id = ?, group_name = ? WHERE student_id = ?");
        $stmt->execute([
            $_POST['student_name'], 
            $_POST['program_id'], 
            $_POST['level_id'], 
            $_POST['group_name'], 
            $student_id
        ]);
        
        $status_type = "success"; $status_msg = "تم تحديث بيانات الطالب بنجاح!";
    } catch (Exception $e) {
        $status_type = "error"; $status_msg = $e->getMessage();
    }
}

// جلب البيانات للعرض
$students = $pdo->query("SELECT s.*, p.name as p_name, l.level_name, d.college_id FROM students s 
                         LEFT JOIN programs p ON s.program_id = p.program_id 
                         LEFT JOIN departments d ON p.dept_id = d.dept_id
                         LEFT JOIN levels l ON s.level_id = l.level_id 
                         ORDER BY s.student_id DESC")->fetchAll();
$colleges = $pdo->query("SELECT * FROM colleges")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الطلاب </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #2F4156; --accent: #04AFC9; --navy: #2F496E; --bg: #F8F7F3; --soft: #C8D9E6; --white: #FFFFFF; --gold: #C2B2A3; --glass: rgba(255, 255, 255, 0.8); --danger: #e74c3c; }
        
        body { font-family: 'Cairo', sans-serif; background: var(--bg); margin: 0; padding: 0; color: var(--primary); }
        .main-wrapper { padding: 40px 20px; max-width: 1300px; margin: 0 auto; }

        .premium-header { background: linear-gradient(135deg, var(--primary) 0%, var(--navy) 100%); padding: 40px; border-radius: 30px; color: white; position: relative; margin-bottom: 50px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .premium-header i { position: absolute; left: 40px; top: 50%; transform: translateY(-50%); font-size: 5em; opacity: 0.1; }

        .glass-card { background: var(--glass); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3); border-radius: 25px; padding: 30px; margin-bottom: 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end; }
        .input-box label { display: block; margin-bottom: 8px; font-weight: 700; color: var(--navy); font-size: 0.9em; }
        .input-box input, .input-box select { width: 100%; padding: 12px 15px; border: 2px solid var(--soft); border-radius: 12px; transition: 0.3s; box-sizing: border-box; font-family: 'Cairo'; }
        .input-box input:focus, .input-box select:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 10px rgba(4, 175, 201, 0.1); }

        .btn-grad { background: linear-gradient(to right, var(--accent) 0%, var(--navy) 100%); color: white; border: none; padding: 14px 25px; border-radius: 12px; cursor: pointer; font-weight: bold; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-grad:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); opacity: 0.9; }

        .table-wrap { border-radius: 25px; overflow: hidden; background: white; box-shadow: 0 15px 40px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f4f7; padding: 20px; color: var(--navy); font-weight: 700; }
        td { padding: 18px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        tr:hover { background-color: #fafbfc; }

        #editModal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(5px); }
        .modal-box { background: white; border-radius: 30px; width: 750px; max-width: 95%; position: relative; overflow: hidden; animation: slideUp 0.4s ease; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { background: var(--primary); padding: 20px; color: white; display: flex; align-items: center; gap: 15px; }

        .floating-back-btn { position: fixed; bottom: 30px; right: 20px; background: linear-gradient(135deg, #2F4156 0%, #2F496E 100%); color: #ffffff !important; padding: 12px 25px; border-radius: 50px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 700; box-shadow: 0 10px 25px rgba(47, 65, 86, 0.4); z-index: 9999; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="premium-header">
        <h1>إدارة شؤون الطلاب</h1>
        <p>تسجيل الطلاب الجدد وتوزيعهم على التخصصات والمستويات.</p>
        <i class="fas fa-user-graduate"></i>
    </div>

    <div class="glass-card">
        <h4 style="margin:0 0 20px 0; color:var(--accent)"><i class="fas fa-plus-circle"></i> إضافة طالب جديد</h4>
        <form method="POST">
            <div class="form-grid">
                <div class="input-box"><label>الاسم الكامل</label><input type="text" name="student_name" placeholder="أدخل اسم الطالب" required></div>
                <div class="input-box"><label>البريد الإلكتروني</label><input type="email" name="email" placeholder="example@mail.com" required></div>
                <div class="input-box"><label>كلمة المرور</label><input type="password" name="password" placeholder="••••••••" required></div>
                <div class="input-box">
                    <label>الكلية</label>
                    <select onchange="fetchData('programs', this.value, 'p_main')" required>
                        <option value="">اختر الكلية...</option>
                        <?php foreach($colleges as $c): ?><option value="<?= $c['college_id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="input-box"><label>التخصص</label><select name="program_id" id="p_main" onchange="fetchData('levels', this.value, 'l_main')" required><option value="">اختر التخصص...</option></select></div>
                <div class="input-box"><label>المستوى</label><select name="level_id" id="l_main" onchange="fetchData('sections', this.value, 'g_main')" required><option value="">اختر المستوى...</option></select></div>
                <div class="input-box"><label>الجروب</label><select name="group_name" id="g_main" required><option value="">اختر الجروب...</option></select></div>
                <button type="submit" name="btn_save_student" class="btn-grad">حفظ البيانات</button>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>اسم الطالب</th>
                    <th>التخصص</th>
                    <th>المستوى</th>
                    <th>الجروب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $s): ?>
                <tr>
                    <td><span style="background:#eee; padding:4px 10px; border-radius:8px">#<?= $s['student_id'] ?></span></td>
                    <td><b><?= htmlspecialchars($s['name']) ?></b></td>
                    <td style="color:var(--accent)"><?= $s['p_name'] ?></td>
                    <td><span style="border: 1px solid var(--soft); padding:3px 8px; border-radius:5px"><?= $s['level_name'] ?></span></td>
                    <td><b><?= $s['group_name'] ?></b></td>
                    <td style="display:flex; justify-content:center; gap:8px">
                        <button onclick='openEdit(<?= json_encode($s) ?>)' class="btn-grad" style="padding:8px 12px; background:var(--accent)"><i class="fas fa-edit"></i></button>
                        <button onclick="confirmDel(<?= $s['student_id'] ?>)" class="btn-grad" style="padding:8px 12px; background:var(--gold)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <i class="fas fa-user-edit fa-2x"></i>
            <h3 style="margin:0">تعديل بيانات الطالب</h3>
        </div>
        <div style="padding:30px">
            <form method="POST">
                <input type="hidden" name="student_id" id="edit_id">
                
                <div class="input-box" style="margin-bottom:15px">
                    <label>الاسم الكامل</label>
                    <input type="text" name="student_name" id="edit_name" required>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom:15px; background: #f9f9f9; padding: 15px; border-radius: 15px; border: 1px solid #eee;">
                    <div class="input-box">
                        <label>كلمة المرور القديمة (للتغيير)</label>
                        <input type="password" name="old_password" placeholder="اتركه فارغاً لعدم التغيير">
                    </div>
                    <div class="input-box">
                        <label>كلمة المرور الجديدة</label>
                        <input type="password" name="new_password" placeholder="أدخل الكلمة الجديدة">
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom:25px">
                    <div class="input-box">
                        <label>الكلية</label>
                        <select id="edit_coll" onchange="fetchData('programs', this.value, 'edit_p')" required>
                            <?php foreach($colleges as $c): ?><option value="<?= $c['college_id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-box"><label>التخصص</label><select name="program_id" id="edit_p" onchange="fetchData('levels', this.value, 'edit_l')" required></select></div>
                    <div class="input-box"><label>المستوى</label><select name="level_id" id="edit_l" onchange="fetchData('sections', this.value, 'edit_g')" required></select></div>
                    <div class="input-box"><label>الجروب</label><select name="group_name" id="edit_g" required></select></div>
                </div>

                <div style="display:flex; gap:10px">
                    <button type="submit" name="btn_update_student" class="btn-grad" style="flex:2">تحديث البيانات</button>
                    <button type="button" onclick="closeModal()" class="btn-grad" style="flex:1; background:var(--gold)">إغلاق</button>
                </div>
            </form>
        </div>
    </div>
</div>

<a href="admin_dashboard.php" class="floating-back-btn"><i class="fas fa-arrow-right"></i><span>الرجوع </span></a>

<script>
function fetchData(type, id, target, selectedValue = null) {
    if(!id) return Promise.resolve();
    return fetch(`get_data.php?type=${type}&id=${id}`)
    .then(r => r.json())
    .then(data => {
        let options = '<option value="">اختر...</option>';
        data.forEach(item => {
            let val = (type === 'sections') ? item.group_name : (type === 'programs' ? item.program_id : item.level_id);
            let text = (type === 'sections') ? item.group_name : (type === 'programs' ? item.name : item.level_name);
            options += `<option value="${val}" ${val == selectedValue ? 'selected' : ''}>${text}</option>`;
        });
        document.getElementById(target).innerHTML = options;
    });
}

async function openEdit(s) {
    document.getElementById('edit_id').value = s.student_id;
    document.getElementById('edit_name').value = s.name;
    document.getElementById('edit_coll').value = s.college_id;
    
    await fetchData('programs', s.college_id, 'edit_p', s.program_id);
    await fetchData('levels', s.program_id, 'edit_l', s.level_id);
    await fetchData('sections', s.level_id, 'edit_g', s.group_name);
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() { document.getElementById('editModal').style.display = 'none'; }

function confirmDel(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيتم حذف الطالب وكافة بيانات الدخول الخاصة به!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#04AFC9',
        cancelButtonColor: '#C2B2A3',
        confirmButtonText: 'نعم، احذف الطالب',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?delete_id=' + id;
        }
    });
}

<?php if($status_type): ?>
    Swal.fire({
        icon: '<?= $status_type ?>',
        title: '<?= $status_msg ?>',
        confirmButtonColor: '#04AFC9'
    });
<?php endif; ?>
</script>

</body>
</html>