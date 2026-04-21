<?php
require_once 'db_config.php';
session_start(); // لتخزين الرسائل بشكل مؤقت وعرضها بعد إعادة التوجيه

$status_type = ""; $status_msg = "";

// جلب الرسائل من الجلسة إذا وجدت
if (isset($_SESSION['msg'])) {
    $status_type = $_SESSION['type'];
    $status_msg = $_SESSION['msg'];
    unset($_SESSION['msg']); unset($_SESSION['type']);
}

// --- 1. إضافة قسم رئيسي ---
if (isset($_POST['add_main_dept'])) {
    $name = htmlspecialchars($_POST['dept_name']);
    try {
        $pdo->prepare("INSERT INTO colleges (name) VALUES (?)")->execute([$name]);
        $_SESSION['type'] = "success"; $_SESSION['msg'] = "تمت إضافة القسم بنجاح!";
    } catch (Exception $e) { $_SESSION['type'] = "error"; $_SESSION['msg'] = "فشل إضافة القسم!"; }
    header("Location: manage_structure.php"); exit();
}

// --- 2. إضافة تخصص (برنامج) ---
if (isset($_POST['add_sub_prog'])) {
    $prog_name = htmlspecialchars($_POST['prog_name']);
    $main_dept_id = (int)$_POST['main_dept_id'];
    $levels = (int)$_POST['level_count'];
    try {
        $pdo->beginTransaction();
        $stmtD = $pdo->prepare("INSERT INTO departments (name, college_id) VALUES (?, ?)");
        $stmtD->execute([$prog_name, $main_dept_id]);
        $new_dept_id = $pdo->lastInsertId();

        $stmtP = $pdo->prepare("INSERT INTO programs (name, dept_id) VALUES (?, ?)");
        $stmtP->execute([$prog_name, $new_dept_id]);
        $new_prog_id = $pdo->lastInsertId();

        $stmtL = $pdo->prepare("INSERT INTO levels (program_id, level_name) VALUES (?, ?)");
        for($i=1; $i<=$levels; $i++) { $stmtL->execute([$new_prog_id, "المستوى $i"]); }
        $pdo->commit();
        $_SESSION['type'] = "success"; $_SESSION['msg'] = "تم إنشاء التخصص ومستوياته بنجاح!";
    } catch (Exception $e) { $pdo->rollBack(); $_SESSION['type'] = "error"; $_SESSION['msg'] = "فشل إضافة التخصص!"; }
    header("Location: manage_structure.php"); exit();
}

// --- 3. تعديل اسم القسم ---
if (isset($_POST['update_main_dept'])) {
    $id = (int)$_POST['dept_id'];
    $name = htmlspecialchars($_POST['dept_name']);
    try {
        $pdo->prepare("UPDATE colleges SET name = ? WHERE college_id = ?")->execute([$name, $id]);
        $_SESSION['type'] = "success"; $_SESSION['msg'] = "تم تحديث اسم القسم بنجاح!";
    } catch (Exception $e) { $_SESSION['type'] = "error"; $_SESSION['msg'] = "فشل التعديل!"; }
    header("Location: manage_structure.php"); exit();
}

// --- 4. حذف القسم ---
if (isset($_GET['delete_main'])) {
    $id = (int)$_GET['delete_main'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM levels WHERE program_id IN (SELECT program_id FROM programs WHERE dept_id IN (SELECT dept_id FROM departments WHERE college_id = ?))")->execute([$id]);
        $pdo->prepare("DELETE FROM programs WHERE dept_id IN (SELECT dept_id FROM departments WHERE college_id = ?)")->execute([$id]);
        $pdo->prepare("DELETE FROM departments WHERE college_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM colleges WHERE college_id = ?")->execute([$id]);
        $pdo->commit();
        $_SESSION['type'] = "success"; $_SESSION['msg'] = "تم حذف القسم بالكامل!";
    } catch (Exception $e) { $pdo->rollBack(); $_SESSION['type'] = "error"; $_SESSION['msg'] = "فشل الحذف (بيانات مرتبطة بالطلاب)!"; }
    header("Location: manage_structure.php"); exit();
}

$main_depts = $pdo->query("SELECT * FROM colleges ORDER BY college_id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الهيكلية | Zahra Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #2F4156; --accent: #04AFC9; --navy: #2F496E; --bg: #F8F7F3; --soft: #C8D9E6; }
        body { font-family: 'Cairo', sans-serif; background: var(--bg); margin: 0; padding: 20px; }
        .glass-card { background: white; border-radius: 20px; padding: 25px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; }
        .input-box { margin-bottom: 15px; }
        .input-box input, .input-box select { width: 100%; padding: 12px; border: 2px solid var(--soft); border-radius: 12px; outline: none; transition: 0.3s; }
        .input-box input:focus { border-color: var(--accent); }
        .btn-grad { background: linear-gradient(to right, var(--accent), var(--navy)); color: white; border: none; padding: 12px; border-radius: 12px; cursor: pointer; width: 100%; font-weight: bold; transition: 0.3s; }
        .btn-grad:hover { opacity: 0.9; transform: translateY(-2px); }
        .dept-item { background: #fff; border: 1px solid #eee; padding: 15px; border-radius: 15px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .badge { background: #f0f4f8; color: var(--navy); padding: 4px 10px; border-radius: 8px; font-size: 0.85em; margin-left: 5px; border: 1px solid var(--soft); }
        .action-btns { display: flex; gap: 15px; }
        .act-icon { cursor: pointer; font-size: 1.2em; transition: 0.2s; }
        .act-icon.edit { color: var(--accent); }
        .act-icon.del { color: #c2b2a3; }
        #editModal { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; justify-content:center; align-items:center; z-index:99; }
    </style>
</head>
<body>

<div class="glass-card" style="background: linear-gradient(135deg, var(--primary), var(--navy)); color: white;">
    <h2>نظام إدارة الهيكل الأكاديمي</h2>
    <p>بإمكانك إضافة الأقسام الرئيسية وتعديل تخصصاتها بكل سهولة.</p>
</div>

<div class="grid">
    <div class="glass-card">
        <h3><i class="fas fa-plus-circle"></i> إضافة قسم رئيسي</h3>
        <form method="POST">
            <div class="input-box"><input type="text" name="dept_name" placeholder="مثلاً: قسم الحاسبات" required></div>
            <button type="submit" name="add_main_dept" class="btn-grad">حفظ القسم</button>
        </form>
    </div>

    <div class="glass-card">
        <h3><i class="fas fa-layer-group"></i> إضافة تخصص (برنامج)</h3>
        <form method="POST">
            <div class="input-box"><input type="text" name="prog_name" placeholder="مثلاً: أمن سيبراني" required></div>
            <div class="input-box">
                <select name="main_dept_id">
                    <?php foreach($main_depts as $md): ?><option value="<?= $md['college_id'] ?>"><?= $md['name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="input-box"><input type="number" name="level_count" value="4" min="1" max="6"></div>
            <button type="submit" name="add_sub_prog" class="btn-grad">إنشاء التخصص</button>
        </form>
    </div>
</div>

<div class="glass-card">
    <h3><i class="fas fa-list-ul"></i> الهيكل الحالي للجامعة</h3>
    <?php foreach($main_depts as $md): ?>
        <div class="dept-item">
            <div>
                <strong style="color:var(--primary); font-size:1.1em"><?= $md['name'] ?></strong>
                <div style="margin-top:8px">
                    <?php 
                    $subs = $pdo->prepare("SELECT name FROM departments WHERE college_id = ?");
                    $subs->execute([$md['college_id']]);
                    while($s = $subs->fetch()) echo "<span class='badge'>{$s['name']}</span>";
                    ?>
                </div>
            </div>
            <div class="action-btns">
                <i class="fas fa-edit act-icon edit" onclick="openEdit('<?= $md['college_id'] ?>', '<?= $md['name'] ?>')"></i>
                <i class="fas fa-trash act-icon del" onclick="confirmDel(<?= $md['college_id'] ?>)"></i>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="editModal">
    <div class="glass-card" style="width: 400px;">
        <h3 style="margin-top:0">تعديل اسم القسم</h3>
        <form method="POST">
            <input type="hidden" name="dept_id" id="edit_id">
            <div class="input-box"><input type="text" name="dept_name" id="edit_name" required></div>
            <div style="display:flex; gap:10px">
                <button type="submit" name="update_main_dept" class="btn-grad">تحديث الآن</button>
                <button type="button" onclick="closeModal()" class="btn-grad" style="background:#c2b2a3">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() { document.getElementById('editModal').style.display = 'none'; }

function confirmDel(id) {
    Swal.fire({
        title: 'تأكيد الحذف؟',
        text: "سيتم مسح القسم وكل متعلقاته!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#04AFC9',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = '?delete_main=' + id; }
    });
}

// تشغيل الرسالة الصحيحة فور تحميل الصفحة
<?php if($status_type): ?>
    Swal.fire({ 
        icon: '<?= $status_type ?>', 
        title: '<?= $status_msg ?>', 
        showConfirmButton: false, 
        timer: 1800,
        timerProgressBar: true
    });
<?php endif; ?>
</script>

</body>
</html>
<a href="admin_dashboard.php" class="floating-back-btn" title="العودة للخلف">
    <i class="fas fa-arrow-right"></i>
    <span>رجوع</span>
</a>

<style>
    .floating-back-btn {
        /* التثبيت في الشاشة */
        position: fixed;
        bottom: 30px; /* المسافة من الأسفل */
        right: 15px;  /* المسافة من اليمين لأن الموقع عربي */
        
        /* التصميم الملكي */
        background: linear-gradient(135deg, #2F4156 0%, #2F496E 100%);
        color: #ffffff !important;
        padding: 12px 25px;
        border-radius: 50px; /* شكل بيضاوي أنيق */
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: 'Cairo', sans-serif;
        font-weight: 700;
        box-shadow: 0 10px 25px rgba(47, 65, 86, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.1);
        z-index: 9999; /* لضمان ظهوره فوق كل العناصر */
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .floating-back-btn i {
        font-size: 1.2em;
    }

    /* تأثير الحركية عند التمرير */
    .floating-back-btn:hover {
        transform: scale(1.1) translateY(-5px);
        box-shadow: 0 15px 35px rgba(4, 175, 201, 0.4);
        background: linear-gradient(135deg, #04AFC9 0%, #2F4156 100%);
    }

    /* لإخفاء النص في الشاشات الصغيرة وترك الأيقونة فقط (اختياري) */
    @media (max-width: 600px) {
        .floating-back-btn span {
            display: none;
        }
        .floating-back-btn {
            padding: 15px;
            border-radius: 50%;
            bottom: 20px;
            right: 20px;
        }
    }
</style>