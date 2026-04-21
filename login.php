<?php
session_start();
require_once 'db_config.php';

// --- [1] خوارزمية الترميم الذاتي (لضمان عمل الجداول دون أخطاء) ---
try {
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'login_attempts'");
    if (!$check->fetch()) { $pdo->exec("ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0"); }
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'lock_until'");
    if (!$check->fetch()) { $pdo->exec("ALTER TABLE users ADD COLUMN lock_until DATETIME NULL"); }
} catch (Exception $e) { }

// --- [2] منطق تسجيل الخروج ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    header("Location: logout.php"); // تأكد أن ملف logout.php موجود بجانبه
    exit();
}

// --- [3] التوجيه الذكي للمسجلين مسبقاً ---
if (isset($_SESSION['user_id']) && !isset($_POST['login_action'])) {
    if ($_SESSION['role_id'] == 1) {
        header("Location: admin_dashboard.php"); // الأدمن يذهب مباشرة لصفحته
        exit();
    } else {
        // الطلاب والمدرسين تظهر لهم صفحة الاختبار (كما طلبت أن تبقى)
        $role_name = ($_SESSION['role_id'] == 2) ? "المدرس" : "الطالب";
        echo "
        <!DOCTYPE html>
        <html lang='ar' dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { background: #0F172A; color: white; font-family: 'Cairo', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .test-card { background: #1E293B; padding: 50px; border-radius: 35px; text-align: center; border: 1px solid #06B6D4; box-shadow: 0 0 50px rgba(6,182,212,0.1); }
                .user-info { color: #06B6D4; font-size: 1.8em; font-weight: 900; display:block; margin: 10px 0; }
                .badge { background: #F59E0B; padding: 5px 15px; border-radius: 10px; font-size: 0.8em; color: #000; font-weight: bold; }
                .btn-out { display: inline-block; margin-top: 30px; padding: 12px 25px; background: rgba(239, 68, 68, 0.2); color: #FCA5A5; text-decoration: none; border-radius: 12px; border: 1px solid #EF4444; transition: 0.3s; }
                .btn-out:hover { background: #EF4444; color: white; }
            </style>
        </head>
        <body>
            <div class='test-card'>
                <h1 style='margin:0'>فحص النظام: متصل</h1>
                <p>أهلاً بك يا هندسة، بياناتك صحيحة:</p>
                <span class='user-info'>{$_SESSION['username']}</span>
                <span class='badge'>الصلاحية الحالية: {$role_name}</span>
                <hr style='opacity:0.1; margin: 30px 0;'>
                <p style='color: #64748B;'>أنت لست أدمن، لذا تبقى في وضع المعاينة.</p>
                <a href='?action=logout' class='btn-out'>تسجيل خروج آمن</a>
            </div>
        </body>
        </html>";
        exit();
    }
}

// --- [4] معالجة تسجيل الدخول (The Security Core) ---
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_action'])) {
    $username = htmlspecialchars(strip_tags(trim($_POST['username'])));
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        $now = date("Y-m-d H:i:s");

        if ($user) {
            // التحقق من الحظر الأمني (5 محاولات خاطئة = حظر 15 دقيقة)
            if ($user['lock_until'] && $user['lock_until'] > $now) {
                $wait_time = strtotime($user['lock_until']) - strtotime($now);
                $error = "الحساب مقفل أمنياً. حاول بعد $wait_time ثانية.";
            } else {
                if (password_verify($password, $user['password_hash'])) {
                    // تصفير المحاولات عند النجاح وتحديث الجلسة
                    $pdo->prepare("UPDATE users SET login_attempts = 0, lock_until = NULL WHERE user_id = ?")->execute([$user['user_id']]);
                    
                    session_regenerate_id(true); // حماية من Session Fixation
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role_id'] = $user['role_id'];

                    // التوجيه الذكي:
                    if ($_SESSION['role_id'] == 1) {
                        header("Location: admin_dashboard.php"); // الأدمن يطير للوحة التحكم
                    } else {
                        header("Location: login.php"); // الطالب والمدرس يرجع يظهر له كرت الفحص
                    }
                    exit();
                } else {
                    // نظام العد التنازلي للمحاولات
                    $attempts = $user['login_attempts'] + 1;
                    $lock_time = ($attempts >= 5) ? date("Y-m-d H:i:s", strtotime("+15 minutes")) : null;
                    $error = ($attempts >= 5) ? "تم قفل الحساب لـ 15 دقيقة!" : "كلمة مرور خاطئة! متبقي " . (5 - $attempts) . " محاولات.";
                    $pdo->prepare("UPDATE users SET login_attempts = ?, lock_until = ? WHERE user_id = ?")->execute([$attempts, $lock_time, $user['user_id']]);
                }
            }
        } else { $error = "اسم المستخدم غير مسجل."; }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Zahra Pro | Secure Gateway</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap');
        :root { --bg: #0F172A; --card: #1E293B; --accent: #06B6D4; --text: #F8FAFC; }
        body { font-family: 'Cairo', sans-serif; background: var(--bg); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { width: 100%; max-width: 400px; background: var(--card); padding: 45px; border-radius: 40px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5); text-align: center; }
        .logo-ring { width: 80px; height: 80px; background: rgba(6, 182, 212, 0.1); border: 1px solid var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; color: var(--accent); font-size: 2em; box-shadow: 0 0 20px rgba(6, 182, 212, 0.2); }
        .input-group { position: relative; margin-bottom: 25px; text-align: right; }
        .input-group label { display: block; color: #94A3B8; margin-bottom: 8px; font-size: 0.85em; }
        .input-group input { width: 100%; background: #0F172A; border: 1px solid #334155; padding: 15px 45px 15px 15px; border-radius: 15px; color: white; box-sizing: border-box; transition: 0.3s; font-family: 'Cairo'; }
        .input-group i { position: absolute; right: 15px; bottom: 15px; color: var(--accent); }
        .input-group input:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 15px rgba(6, 182, 212, 0.2); }
        .btn-login { width: 100%; background: linear-gradient(45deg, var(--accent), #0891B2); color: white; border: none; padding: 16px; border-radius: 15px; font-weight: 900; cursor: pointer; transition: 0.4s; }
        .error-msg { background: rgba(239, 68, 68, 0.1); color: #FCA5A5; padding: 12px; border-radius: 15px; border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 25px; font-size: 0.85em; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-ring"><i class="fas fa-shield-halved"></i></div>
        <h2 style="color:var(--text); margin:0; font-weight:900;">بوابة الدخول</h2>
        <p style="color:#64748B; margin-bottom:35px; font-size:0.9em;">نظام الإدارة الأكاديمية الآمن</p>
        <?php if($error): ?> <div class="error-msg"><?= $error ?></div> <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="login_action" value="1">
            <div class="input-group">
                <label>اسم المستخدم</label><i class="fas fa-user-shield"></i>
                <input type="text" name="username" placeholder="أدخل اسمك" required>
            </div>
            <div class="input-group">
                <label>كلمة المرور</label><i class="fas fa-key"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">تـشــفـيـر ودخــول</button>
        </form>
    </div>
</body>
</html>

<a href="index.php" class="floating-back-btn" title="العودة للخلف">
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