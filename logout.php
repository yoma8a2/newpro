<?php
session_start();

// تدمير الجلسة بالكامل
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم تسجيل الخروج | Zahra Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2F4156; --accent: #04AFC9; --navy: #2F496E;
            --bg: #F8F7F3; --white: #FFFFFF;
        }

        body { 
            font-family: 'Cairo', sans-serif; 
            background: var(--bg); 
            height: 100vh; 
            margin: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            overflow: hidden;
        }

        /* كارت تسجيل الخروج */
        .logout-card {
            background: var(--white);
            padding: 50px 40px;
            border-radius: 40px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(47, 65, 86, 0.1);
            max-width: 450px;
            width: 90%;
            border: 1px solid rgba(0,0,0,0.02);
            position: relative;
            animation: fadeInScale 0.5s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        /* أيقونة تسجيل الخروج المتحركة */
        .icon-wrapper {
            width: 100px;
            height: 100px;
            background: rgba(4, 175, 201, 0.1);
            color: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3em;
            position: relative;
        }

        .icon-wrapper::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        h2 { color: var(--primary); font-weight: 900; margin-bottom: 10px; }
        p { color: #64748B; margin-bottom: 35px; line-height: 1.6; }

        /* زر العودة */
        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--navy) 100%);
            color: white;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1em;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(47, 65, 86, 0.2);
        }

        .btn-login:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(4, 175, 201, 0.3);
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
        }

        .footer-note {
            margin-top: 30px;
            font-size: 0.85em;
            color: var(--gold);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="logout-card">
        <div class="icon-wrapper">
            <i class="fas fa-door-open"></i>
        </div>
        
        <h2>تم تسجيل الخروج!</h2>
        <p>شكراً لاستخدامك نظام  الأكاديمي. لقد تم إنهاء جلستك وتأمين حسابك بنجاح.</p>

        <a href="login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> العودة لتسجيل الدخول
        </a>

        <div class="footer-note">
            ننتظر عودتك قريباً! 
        </div>
    </div>

</body>
</html>