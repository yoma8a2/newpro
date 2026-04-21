<?php
require_once 'db_config.php';

// رسائل الحالة
$status_type = ""; $status_msg = "";

// --- 1. منطق الحذف ---
if (isset($_GET['delete_course'])) {
    $id = filter_var($_GET['delete_course'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $pdo->prepare("DELETE FROM courses WHERE course_id = ?")->execute([$id]);
        $status_type = "success"; $status_msg = "تم حذف المادة بنجاح!";
    } catch (Exception $e) { $status_type = "error"; $status_msg = "فشل الحذف! المادة مرتبطة ببيانات أخرى."; }
}

// --- 2. منطق التعديل (مع منع التكرار عند التعديل أيضاً) ---
if (isset($_POST['update_course'])) {
    $id = filter_var($_POST['course_id'], FILTER_SANITIZE_NUMBER_INT);
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $prog_id = (int)$_POST['program_id'];
    $level_id = (int)$_POST['level_id'];
    $hours = (int)$_POST['credit_hours'];

    try {
        // التأكد أن التعديل الجديد لا يتصادم مع مادة أخرى موجودة مسبقاً
        $check = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE name = ? AND program_id = ? AND level_id = ? AND course_id != ?");
        $check->execute([$name, $prog_id, $level_id, $id]);
        
        if ($check->fetchColumn() > 0) {
            $status_type = "error"; $status_msg = "خطأ: يوجد مادة أخرى بنفس هذه البيانات!";
        } else {
            $sql = "UPDATE courses SET name=?, program_id=?, level_id=?, credit_hours=? WHERE course_id=?";
            $pdo->prepare($sql)->execute([$name, $prog_id, $level_id, $hours, $id]);
            $status_type = "success"; $status_msg = "تم تحديث البيانات بنجاح!";
        }
    } catch (Exception $e) { $status_type = "error"; $status_msg = "حدث خطأ أثناء التحديث!"; }
}

// --- 3. منطق الإضافة المطور (فحص التكرار) ---
if (isset($_POST['add_course'])) {
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $prog_id = (int)$_POST['program_id'];
    $level_id = (int)$_POST['level_id'];
    $hours = (int)$_POST['credit_hours'];

    if (!empty($name)) {
        try {
            // فحص هل المادة موجودة لنفس التخصص والمستوى
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE name = ? AND program_id = ? AND level_id = ?");
            $checkStmt->execute([$name, $prog_id, $level_id]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $status_type = "error"; 
                $status_msg = "عذراً! هذه المادة مضافة مسبقاً لهذا التخصص في هذا المستوى.";
            } else {
                $sql = "INSERT INTO courses (name, program_id, level_id, credit_hours) VALUES (?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$name, $prog_id, $level_id, $hours]);
                $status_type = "success"; $status_msg = "تمت إضافة المادة بنجاح!";
            }
        } catch (PDOException $e) { $status_type = "error"; $status_msg = "خطأ فني في قاعدة البيانات."; }
    }
}

// جلب البيانات
$programs = $pdo->query("SELECT * FROM programs")->fetchAll();
$all_levels = $pdo->query("SELECT l.*, p.name as p_name FROM levels l JOIN programs p ON l.program_id = p.program_id ORDER BY l.level_id ASC")->fetchAll();
$courses = $pdo->query("SELECT c.*, p.name as program_name, l.level_name FROM courses c 
                        LEFT JOIN programs p ON c.program_id = p.program_id 
                        LEFT JOIN levels l ON c.level_id = l.level_id 
                        ORDER BY c.course_id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة المواد | Zahra Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
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
        .input-box input, .input-box select { width: 100%; padding: 12px 15px; border: 2px solid var(--soft); border-radius: 12px; transition: 0.3s; box-sizing: border-box; font-family: 'Cairo'; }
        .btn-grad { background: linear-gradient(to right, var(--accent) 0%, var(--navy) 100%); color: white; border: none; padding: 14px 25px; border-radius: 12px; cursor: pointer; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; justify-content: center; }
        .btn-grad:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(4, 175, 201, 0.2); }
        .table-wrap { border-radius: 25px; overflow: hidden; background: white; box-shadow: 0 15px 40px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f4f7; padding: 20px; color: var(--primary); font-weight: 800; }
        td { padding: 18px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        .level-badge { background: rgba(4, 175, 201, 0.1); color: var(--accent); padding: 5px 15px; border-radius: 50px; font-weight: 700; font-size: 0.85em; }
        #editOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(5px); }
        .modal-box { background: white; border-radius: 30px; width: 550px; max-width: 95%; position: relative; overflow: hidden; animation: slideUp 0.4s ease; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { background: var(--primary); padding: 20px; color: white; display: flex; align-items: center; gap: 15px; }
        .floating-back-btn { position: fixed; bottom: 30px; right: 15px; background: linear-gradient(135deg, #2F4156 0%, #2F496E 100%); color: #ffffff !important; padding: 12px 25px; border-radius: 50px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 700; box-shadow: 0 10px 25px rgba(47, 65, 86, 0.4); z-index: 9999; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="premium-header">
        <h1>إدارة المواد الدراسية</h1>
        <p>توزيع المناهج ومنع تكرار البيانات لضمان دقة الجداول.</p>
        <i class="fas fa-layer-group"></i>
    </div>

    <div class="glass-card">
        <h4 style="margin:0 0 15px 0; color:var(--accent)"><i class="fas fa-plus-circle"></i> إضافة مادة جديدة</h4>
        <form method="POST" class="form-grid">
            <div class="input-box"><label>اسم المادة</label><input type="text" name="name" required placeholder="أدخل اسم المادة"></div>
            <div class="input-box">
                <label>التخصص</label>
                <select name="program_id" onchange="filterLevels(this.value, 'main_level')" required>
                    <option value="">اختر التخصص...</option>
                    <?php foreach($programs as $p): ?><option value="<?= $p['program_id'] ?>"><?= $p['name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="input-box">
                <label>المستوى</label>
                <select name="level_id" id="main_level" required>
                    <option value="">حدد التخصص أولاً</option>
                    <?php foreach($all_levels as $l): ?>
                        <option value="<?= $l['level_id'] ?>" data-prog="<?= $l['program_id'] ?>" style="display:none;"><?= $l['level_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-box"><label>الساعات</label><input type="number" name="credit_hours" value="3"></div>
            <button type="submit" name="add_course" class="btn-grad">إضافة المادة</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>المادة</th><th>التخصص</th><th>المستوى</th><th>الساعات</th><th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($courses as $c): ?>
                <tr>
                    <td><b><?= htmlspecialchars($c['name']) ?></b></td>
                    <td><?= $c['program_name'] ?? '---' ?></td>
                    <td><span class="level-badge"><?= $c['level_name'] ?? 'غير محدد' ?></span></td>
                    <td><?= $c['credit_hours'] ?> ساعة</td>
                    <td>
                        <button onclick="openEdit('<?= $c['course_id'] ?>', '<?= addslashes($c['name']) ?>', '<?= $c['program_id'] ?>', '<?= $c['level_id'] ?>', '<?= $c['credit_hours'] ?>')" class="btn-grad" style="padding:8px 15px; background:var(--accent)"><i class="fas fa-edit"></i></button>
                        <button onclick="confirmDel(<?= $c['course_id'] ?>)" class="btn-grad" style="padding:8px 15px; background:var(--gold)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editOverlay">
    <div class="modal-box">
        <div class="modal-header"><i class="fas fa-edit fa-2x"></i><h3 style="margin:0">تعديل بيانات المادة</h3></div>
        <div style="padding:30px">
            <form method="POST">
                <input type="hidden" name="course_id" id="edit_id">
                <div class="input-box" style="margin-bottom:15px"><label>اسم المادة</label><input type="text" name="name" id="edit_name" required></div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px">
                    <div class="input-box">
                        <label>التخصص</label>
                        <select name="program_id" id="edit_prog" onchange="filterLevels(this.value, 'edit_level')" required>
                            <?php foreach($programs as $p): ?><option value="<?= $p['program_id'] ?>"><?= $p['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-box">
                        <label>المستوى</label>
                        <select name="level_id" id="edit_level" required>
                            <?php foreach($all_levels as $l): ?>
                                <option value="<?= $l['level_id'] ?>" data-prog="<?= $l['program_id'] ?>"><?= $l['level_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="input-box" style="margin-bottom:25px"><label>عدد الساعات</label><input type="number" name="credit_hours" id="edit_hours" required></div>
                <div style="display:flex; gap:10px">
                    <button type="submit" name="update_course" class="btn-grad" style="flex:2">حفظ التغييرات</button>
                    <button type="button" onclick="closeEdit()" class="btn-grad" style="flex:1; background:var(--gold)">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<a href="admin_dashboard.php" class="floating-back-btn"><i class="fas fa-arrow-right"></i><span>رجوع</span></a>

<script>
function filterLevels(progId, levelSelectId) {
    const levelSelect = document.getElementById(levelSelectId);
    const options = levelSelect.options;
    let firstMatch = "";
    for (let i = 0; i < options.length; i++) {
        const opt = options[i];
        if (opt.getAttribute('data-prog') == progId || opt.value == "") {
            opt.style.display = "block";
            if (opt.value != "" && firstMatch === "") firstMatch = opt.value;
        } else { opt.style.display = "none"; }
    }
    levelSelect.value = firstMatch; 
}

function openEdit(id, name, progId, levelId, hours) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_prog').value = progId;
    document.getElementById('edit_hours').value = hours;
    filterLevels(progId, 'edit_level');
    document.getElementById('edit_level').value = levelId;
    document.getElementById('editOverlay').style.display = 'flex';
}

function closeEdit() { document.getElementById('editOverlay').style.display = 'none'; }

function confirmDel(id) {
    Swal.fire({ 
        title: 'حذف المادة؟', text: "لن تتمكن من استعادتها!", icon: 'warning', 
        showCancelButton: true, confirmButtonColor: '#e74c3c', confirmButtonText: 'نعم، احذف', cancelButtonText: 'إلغاء' 
    }).then((result) => { if (result.isConfirmed) window.location.href = '?delete_course=' + id; });
}

<?php if($status_type): ?>
    Swal.fire({ icon: '<?= $status_type ?>', title: '<?= $status_msg ?>', confirmButtonColor: '#04AFC9' });
<?php endif; ?>
</script>
</body>
</html>