<?php
// 1. الاتصال الآمن بـ PDO
$host = 'localhost'; $db = 'academic_management_system'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
try { $pdo = new PDO($dsn, $user, $pass, $options); } catch (\PDOException $e) { die("خطأ: " . $e->getMessage()); }

$status_type = ""; $status_msg = "";

// --- منطق الحذف ---
if (isset($_GET['delete_section'])) {
    try {
        $pdo->prepare("DELETE FROM sections WHERE section_id = ?")->execute([$_GET['delete_section']]);
        $status_type = "success"; $status_msg = "تم حذف الشُعبة بنجاح!";
    } catch (Exception $e) { $status_type = "error"; $status_msg = "فشل في الحذف!"; }
}

// --- منطق التعديل الشامل ---
if (isset($_POST['update_section'])) {
    try {
        $sql = "UPDATE sections SET course_id=?, instructor_id=?, level_id=?, group_name=?, lecture_time=?, semester=? WHERE section_id=?";
        $pdo->prepare($sql)->execute([
            $_POST['course_id'], 
            $_POST['instructor_id'], 
            $_POST['level_id'], 
            $_POST['group_name'], 
            $_POST['lecture_time'], 
            $_POST['semester'], 
            $_POST['section_id']
        ]);
        $status_type = "success"; $status_msg = "تم تحديث كافة بيانات الشُعبة بنجاح!";
    } catch (Exception $e) { $status_type = "error"; $status_msg = "حدث خطأ أثناء التحديث!"; }
}

// --- منطق الإضافة ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_section'])) {
    $check = $pdo->prepare("SELECT * FROM sections WHERE instructor_id=? AND lecture_time=? AND semester=? AND year=?");
    $check->execute([$_POST['instructor_id'], $_POST['lecture_time'], $_POST['semester'], $_POST['year']]);
    if ($check->rowCount() > 0) {
        $status_type = "error"; $status_msg = "تعارض زمني: المحاضر مشغول!";
    } else {
        try {
            $sql = "INSERT INTO sections (course_id, instructor_id, level_id, group_name, lecture_time, semester, year) VALUES (?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([$_POST['course_id'], $_POST['instructor_id'], $_POST['level_id'], $_POST['group_name'], $_POST['lecture_time'], $_POST['semester'], $_POST['year']]);
            $status_type = "success"; $status_msg = "تم تسكين المجموعة بنجاح!";
        } catch (Exception $e) { $status_type = "error"; $status_msg = "فشل في الحفظ!"; }
    }
}

// جلب البيانات للقوائم
$courses = $pdo->query("SELECT * FROM courses")->fetchAll();
$instructors = $pdo->query("SELECT * FROM instructors")->fetchAll();
$levels = $pdo->query("SELECT l.*, p.name as p_name FROM levels l JOIN programs p ON l.program_id=p.program_id")->fetchAll();
$sections = $pdo->query("SELECT s.*, c.name as c_name, i.name as i_name, l.level_name, p.name as p_name FROM sections s 
                         JOIN courses c ON s.course_id=c.course_id JOIN instructors i ON s.instructor_id=i.instructor_id
                         JOIN levels l ON s.level_id=l.level_id JOIN programs p ON l.program_id=p.program_id ORDER BY s.section_id DESC")->fetchAll();

$university_times = ["08:00:00" => "08:00 - 10:00", "10:00:00" => "10:00 - 12:00", "12:00:00" => "12:00 - 02:00", "14:00:00" => "02:00 - 04:00"];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الشُعب | Zahra Pro Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #2F4156; --accent: #04AFC9; --navy: #2F496E; --bg: #F8F7F3; --soft: #C8D9E6; --white: #FFFFFF; --gold: #C2B2A3; --glass: rgba(255, 255, 255, 0.85); }
        body { font-family: 'Cairo', sans-serif; background: var(--bg); margin: 0; padding: 0; }
        .main-wrapper { padding: 40px 20px; max-width: 1300px; margin: 0 auto; }
        
        .premium-header { background: linear-gradient(135deg, var(--primary) 0%, var(--navy) 100%); padding: 40px; border-radius: 30px; color: white; position: relative; margin-bottom: 50px; box-shadow: 0 20px 40px rgba(47, 65, 86, 0.2); }
        .premium-header h1 { font-size: 2.2em; margin: 0; }
        .premium-header i { position: absolute; left: 40px; top: 50%; transform: translateY(-50%); font-size: 4.5em; opacity: 0.1; }

        .glass-card { background: var(--glass); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.4); border-radius: 25px; padding: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); margin-bottom: 50px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; align-items: end; }
        .input-box label { display: block; margin-bottom: 10px; font-weight: 700; color: var(--navy); font-size: 0.9em; }
        .input-box select, .input-box input { width: 100%; padding: 12px 15px; border: 2px solid var(--soft); border-radius: 12px; transition: all 0.3s; background: white; font-size: 0.95em; box-sizing: border-box; }
        
        .btn-grad { background: linear-gradient(to right, var(--accent) 0%, var(--navy) 100%); color: white; border: none; padding: 15px 30px; border-radius: 12px; cursor: pointer; font-weight: bold; width: 100%; grid-column: 1 / -1; margin-top: 10px; transition: 0.4s; }
        .btn-grad:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(4, 175, 201, 0.4); }

        .content-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        .vip-card { background: var(--white); border-radius: 25px; padding: 25px; border: 1px solid var(--soft); position: relative; transition: 0.4s; overflow: hidden; }
        .vip-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: var(--accent); }
        
        .card-badge { position: absolute; top: 0; left: 0; background: var(--accent); color: white; padding: 8px 20px; border-bottom-right-radius: 20px; font-size: 0.85em; font-weight: bold; }
        .card-actions { position: absolute; top: 15px; right: 15px; display: flex; gap: 10px; }
        .action-icon { color: var(--soft); cursor: pointer; transition: 0.3s; font-size: 1.1em; }
        .action-icon:hover { transform: scale(1.2); }
        .edit-icon:hover { color: var(--accent); }
        .delete-icon:hover { color: #e74c3c; }

        .course-name { font-size: 1.2em; font-weight: 800; color: var(--primary); margin: 20px 0 5px 0; padding-top: 10px; }
        .instructor-name { color: var(--gold); font-weight: 600; font-size: 0.95em; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        
        .card-details { background: #F9FAFB; border-radius: 15px; padding: 15px; margin-top: 10px; }
        .detail-item { display: flex; justify-content: space-between; font-size: 0.85em; margin-bottom: 8px; color: var(--navy); }
        .detail-item b { color: var(--accent); }

        .time-footer { margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--soft); display: flex; justify-content: space-between; align-items: center; }
        .time-box { background: #e0f7fa; color: var(--accent); padding: 5px 12px; border-radius: 8px; font-weight: bold; font-size: 0.9em; }

        /* مودال التعديل العريض */
        #editOverlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); display:none; justify-content:center; align-items:center; z-index:1000; backdrop-filter: blur(4px); }
        .modal-box { background: white; border-radius: 30px; width: 700px; max-width: 90%; padding: 35px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); }
        
        .floating-back-btn { position: fixed; bottom: 30px; right: 15px; background: linear-gradient(135deg, #2F4156 0%, #2F496E 100%); color: #ffffff !important; padding: 12px 25px; border-radius: 50px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 700; box-shadow: 0 10px 25px rgba(47, 65, 86, 0.4); z-index: 9999; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="premium-header">
        <h1>إدارة الشُعب والجداول الدراسية</h1>
        <p>التحكم الكامل في كافة بيانات المجموعات الأكاديمية.</p>
        <i class="fas fa-calendar-check"></i>
    </div>

    <div class="glass-card">
        <h3 style="margin-top:0; color:var(--accent)"><i class="fas fa-plus-circle"></i> تسكين مجموعة جديدة</h3>
        <form method="POST" class="form-grid">
            <div class="input-box"><label>المقرر الدراسي</label>
                <select name="course_id" required>
                    <?php foreach($courses as $c): ?><option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="input-box"><label>أستاذ المادة</label>
                <select name="instructor_id" required>
                    <?php foreach($instructors as $i): ?><option value="<?= $i['instructor_id'] ?>"><?= htmlspecialchars($i['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="input-box"><label>المستوى الدراسي</label>
                <select name="level_id" required>
                    <?php foreach($levels as $l): ?><option value="<?= $l['level_id'] ?>"><?= $l['p_name'] ?> - <?= $l['level_name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="input-box"><label>المجموعة (Group)</label>
                <select name="group_name">
                    <option value="A">Group A</option><option value="B">Group B</option>
                    <option value="C">Group C</option><option value="D">Group D</option>
                </select>
            </div>
            <div class="input-box"><label>وقت المحاضرة</label>
                <select name="lecture_time" required>
                    <?php foreach($university_times as $val => $label): ?><option value="<?= $val ?>"><?= $label ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="input-box"><label>الفصل الدراسي</label>
                <select name="semester"><option value="1">الترم الأول</option><option value="2">الترم الثاني</option></select>
            </div>
            <input type="hidden" name="year" value="2026">
            <button type="submit" name="save_section" class="btn-grad"><i class="fas fa-save"></i> تثبيت المجموعة في الجدول</button>
        </form>
    </div>

    <div class="content-grid">
        <?php foreach($sections as $s): ?>
            <div class="vip-card">
                <div class="card-badge">المجموعة <?= $s['group_name'] ?></div>
                <div class="card-actions">
                    <i class="fas fa-pen-to-square action-icon edit-icon" onclick='openEdit(<?= json_encode($s) ?>)'></i>
                    <i class="fas fa-trash-can action-icon delete-icon" onclick="confirmDel(<?= $s['section_id'] ?>)"></i>
                </div>

                <div class="course-name"><?= htmlspecialchars($s['c_name']) ?></div>
                <div class="instructor-name"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($s['i_name'] ?? 'لم يحدد') ?></div>
                
                <div class="card-details">
                    <div class="detail-item"><span>التخصص:</span> <b><?= $s['p_name'] ?></b></div>
                    <div class="detail-item"><span>المستوى:</span> <b><?= $s['level_name'] ?></b></div>
                </div>

                <div class="time-footer">
                    <div class="time-box"><i class="far fa-clock"></i> <?= $university_times[$s['lecture_time']] ?? $s['lecture_time'] ?></div>
                    <span style="font-size: 0.8em; color: var(--gold); font-weight: bold;">ترم <?= $s['semester'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="editOverlay">
    <div class="modal-box">
        <h3 style="color:var(--primary); margin-bottom:25px;"><i class="fas fa-edit"></i> تعديل بيانات الشُعبة الشامل</h3>
        <form method="POST" class="form-grid">
            <input type="hidden" name="section_id" id="edit_id">

            <div class="input-box"><label>المقرر الدراسي</label>
                <select name="course_id" id="edit_course">
                    <?php foreach($courses as $c): ?><option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>

            <div class="input-box"><label>أستاذ المادة</label>
                <select name="instructor_id" id="edit_inst">
                    <?php foreach($instructors as $i): ?><option value="<?= $i['instructor_id'] ?>"><?= htmlspecialchars($i['name']) ?></option><?php endforeach; ?>
                </select>
            </div>

            <div class="input-box"><label>المستوى الدراسي</label>
                <select name="level_id" id="edit_level">
                    <?php foreach($levels as $l): ?><option value="<?= $l['level_id'] ?>"><?= $l['p_name'] ?> - <?= $l['level_name'] ?></option><?php endforeach; ?>
                </select>
            </div>

            <div class="input-box"><label>المجموعة</label>
                <select name="group_name" id="edit_group">
                    <option value="A">Group A</option><option value="B">Group B</option>
                    <option value="C">Group C</option><option value="D">Group D</option>
                </select>
            </div>

            <div class="input-box"><label>وقت المحاضرة</label>
                <select name="lecture_time" id="edit_time">
                    <?php foreach($university_times as $val => $label): ?><option value="<?= $val ?>"><?= $label ?></option><?php endforeach; ?>
                </select>
            </div>

            <div class="input-box"><label>الفصل الدراسي</label>
                <select name="semester" id="edit_sem">
                    <option value="1">الترم الأول</option>
                    <option value="2">الترم الثاني</option>
                </select>
            </div>

            <div style="grid-column: span 2; display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="update_section" class="btn-grad" style="margin:0">تحديث وحفظ التغييرات</button>
                <button type="button" class="btn-grad" style="background:var(--gold); margin:0" onclick="closeEdit()">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<a href="admin_dashboard.php" class="floating-back-btn"><i class="fas fa-arrow-right"></i><span>رجوع</span></a>

<script>
function openEdit(s) {
    document.getElementById('edit_id').value = s.section_id;
    document.getElementById('edit_course').value = s.course_id;
    document.getElementById('edit_inst').value = s.instructor_id;
    document.getElementById('edit_level').value = s.level_id;
    document.getElementById('edit_group').value = s.group_name;
    document.getElementById('edit_time').value = s.lecture_time;
    document.getElementById('edit_sem').value = s.semester;
    document.getElementById('editOverlay').style.display = 'flex';
}
function closeEdit() { document.getElementById('editOverlay').style.display = 'none'; }

function confirmDel(id) {
    Swal.fire({
        title: 'حذف الشُعبة؟', text: "لن تتمكني من استعادة البيانات!", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#04AFC9', cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، احذف', cancelButtonText: 'إلغاء'
    }).then((result) => { if (result.isConfirmed) { window.location.href = "?delete_section=" + id; } });
}

<?php if($status_type): ?>
Swal.fire({ icon: '<?= $status_type ?>', title: '<?= $status_msg ?>', timer: 2000, showConfirmButton: false });
<?php endif; ?>
</script>

</body>
</html>