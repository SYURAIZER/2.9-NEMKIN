<?php
session_start();

// Если пользователь уже авторизован – сразу на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'], $_POST['password'])) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    // Ищем активного пользователя по логину
    $stmt = $pdo->prepare("SELECT id, login, password_hash, last_name, first_name, position FROM employees WHERE login = ? AND is_active = 1");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Успешный вход
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['last_name'] . ' ' . $user['first_name'];
        $_SESSION['user_position'] = $user['position'];

        // Перенаправление на запрошенную страницу или на главную
        $redirect = $_GET['redirect'] ?? 'index.php';
        header("Location: $redirect");
        exit;
    } else {
        $error = 'Неверный логин или пароль. Попробуйте снова.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход – AdAgency Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #e8f0fe 0%, #f4f7fc 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Фоновые декоративные элементы */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(30, 58, 138, 0.06);
            z-index: 0;
        }
        .bg-circle-1 {
            width: 600px;
            height: 600px;
            top: -200px;
            right: -150px;
        }
        .bg-circle-2 {
            width: 400px;
            height: 400px;
            bottom: -100px;
            left: -100px;
            background: rgba(37, 99, 235, 0.05);
        }
        .bg-circle-3 {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 10%;
            background: rgba(30, 58, 138, 0.04);
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(30, 58, 138, 0.12), 0 1px 3px rgba(0,0,0,0.05);
            padding: 40px 36px;
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-wrapper {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-icon {
            display: inline-block;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            border-radius: 16px;
            line-height: 56px;
            font-size: 28px;
            color: #fff;
            margin-bottom: 12px;
        }
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: -0.5px;
        }
        .logo-text span {
            color: #2563eb;
        }
        .subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .error-message::before {
            content: '⚠️';
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        .input-field {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f9fafb;
            outline: none;
        }
        .input-field:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: #fff;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.2s;
            letter-spacing: 0.3px;
            margin-top: 10px;
        }
        .btn-login:hover {
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            transform: translateY(-1px);
        }
        .btn-login:active {
            transform: translateY(0);
        }

        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 0.8rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>
    <div class="bg-circle bg-circle-3"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-wrapper">
                <div class="logo-icon">📊</div>
                <div class="logo-text">AdAgency <span>Pro</span></div>
                <div class="subtitle">Управление рекламным агентством</div>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" class="input-field" required
                           value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                           placeholder="Введите логин">
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" class="input-field" required
                           placeholder="Введите пароль">
                </div>
                <button type="submit" class="btn-login">Войти в систему</button>
            </form>
            <div class="footer-note">AdAgency Pro © <?= date('Y') ?>. Все права защищены.</div>
        </div>
    </div>
</body>
</html>