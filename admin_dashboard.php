<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الرئيسية </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2F4156; --accent: #04AFC9; --navy: #2F496E;
            --bg: #F8F7F3; --soft: #C8D9E6; --white: #FFFFFF;
            --gold: #C2B2A3; --glass: rgba(255, 255, 255, 0.8);
        }

        body { 
            font-family: 'Cairo', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            padding: 0; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-wrapper { padding: 40px 20px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }

        /* الهيدر الملكي */
        .premium-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--navy) 100%);
            padding: 50px 40px; border-radius: 30px; color: white; text-align: right;
            box-shadow: 0 20px 40px rgba(47, 65, 86, 0.2); position: relative; margin-bottom: 50px;
        }
        .premium-header h1 { font-size: 2.5em; margin: 0; }
        .premium-header p { opacity: 0.8; font-size: 1.1em; margin-top: 10px; }
        .premium-header i { position: absolute; left: 50px; top: 50%; transform: translateY(-50%); font-size: 6em; opacity: 0.1; }

        /* شبكة الأزرار/الكروت */
        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
        }

        .nav-card {
            background: var(--white);
            border-radius: 25px;
            padding: 30px;
            text-align: center;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.02);
        }

        .nav-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(4, 175, 201, 0.15);
            border-color: var(--accent);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: var(--bg);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 2em;
            color: var(--accent);
            transition: 0.3s;
        }

        .nav-card:hover .icon-box {
            background: var(--accent);
            color: white;
        }

        .nav-card h3 { margin: 10px 0; font-size: 1.4em; }
        .nav-card p { font-size: 0.9em; color: #777; margin: 0; line-height: 1.6; }

        /* زر تسجيل الخروج */
        .logout-btn {
            margin-top: 50px;
            text-align: center;
        }
        .logout-link {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="premium-header">
        <h1>لوحة التحكم الإدارية</h1>
        <p>مرحباً بك مجدداً، إليك نظرة سريعة على أدوات الإدارة الأكاديمية.</p>
        <i class="fas fa-shield-halved"></i>
    </div>

    <div class="dashboard-grid">
        
        <a href="users_manage.php" class="nav-card">
            <div class="icon-box"><i class="fas fa-users-cog"></i></div>
            <h3>إدارة المستخدمين</h3>
            <p>إضافة، تعديل، وحذف صلاحيات المستخدمين والمدراء في النظام.</p>
        </a>

        <a href="register_students.php" class="nav-card">
            <div class="icon-box"><i class="fas fa-user-plus"></i></div>
            <h3>تسجيل الطلاب</h3>
            <p>نافذة تسجيل الطلاب الجدد وتوزيعهم على المستويات الأكاديمية.</p>
        </a>

        <a href="sections.php" class="nav-card">
            <div class="icon-box"><i class="fas fa-layer-group"></i></div>
            <h3>إدارة الأقسام</h3>
            <p>تنظيم الكليات والأقسام العلمية التابعة للأكاديمية.</p>
        </a>

        <a href="Manage Courses.php" class="nav-card">
            <div class="icon-box"><i class="fas fa-book-open"></i></div>
            <h3>إدارة الكورسات</h3>
            <p>إدارة المناهج الدراسية، إضافة مواد جديدة وتعديل الحالية.</p>
        </a>

        <a href="manage_structure.php" class="nav-card">
            <div class="icon-box"><i class="fas fa-sitemap"></i></div>
            <h3>الهيكل التنظيمي</h3>
            <p>ضبط الإعدادات الهيكلية للمستويات والبرامج الأكاديمية.</p>
        </a>

    </div>

    <div class="logout-btn">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج من النظام
        </a>
    </div>
</div>

</body>
</html>