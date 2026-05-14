<?php
// ==================== ПОДКЛЮЧЕНИЕ К БД ====================
require_once 'auth.php';

// ==================== СБОР СТАТИСТИКИ ====================
try {
    $stats = [
        'clients'        => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
        'employees'      => $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn(),
        'services'       => $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn(),
        'orders_active'  => $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('new','negotiation','in_progress')")->fetchColumn(),
        'orders_today'   => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_date = CURDATE()")->fetchColumn(),
        'revenue_month'  => $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status = 'paid' AND MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())")->fetchColumn(),
    ];

    // Последние 5 заказов для таблицы
    $recentOrders = $pdo->query("
        SELECT o.id, o.order_date, o.status, o.total_amount,
               c.full_name AS client,
               CONCAT(e.last_name, ' ', e.first_name) AS manager
        FROM orders o
        JOIN clients c ON o.client_id = c.id
        JOIN employees e ON o.employee_id = e.id
        ORDER BY o.id DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    die('Ошибка получения данных: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>AdAgency Pro – Панель управления</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ===== БАЗОВЫЕ СТИЛИ ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fc; color: #222; }
        a { text-decoration: none; }

        /* ===== ВЕРХНИЙ БАР ===== */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #d9e2ef;
            padding: 0 30px;
            height: 64px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .topbar .logo { font-size: 1.5rem; font-weight: 700; color: #1e3a8a; }
        .topbar .user-menu { color: #555; }
        .topbar .user-menu a {
            margin-left: 20px;
            color: #1e3a8a;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 6px;
            transition: 0.2s;
        }
        .topbar .user-menu a:hover { background: #eaf0fc; }

        /* ===== ОСНОВНАЯ СЕТКА ===== */
        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        .page-title { font-size: 1.8rem; font-weight: 600; color: #1e3a8a; margin-bottom: 25px; }

        /* ===== КАРТОЧКИ СТАТИСТИКИ ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-left: 5px solid #1e3a8a;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .label { font-size: 0.9rem; color: #6b7a99; margin-bottom: 8px; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1e293b; }
        .stat-card .sub { font-size: 0.8rem; color: #5f6b7a; margin-top: 5px; }

        /* ===== МЕНЮ БЫСТРЫХ ДЕЙСТВИЙ ===== */
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 35px;
        }
        .quick-link {
            flex: 1 1 200px;
            background: #fff;
            padding: 20px 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.2s;
            color: #1e3a8a;
            font-weight: 600;
            font-size: 1.05rem;
            border: 1px solid #eee;
        }
        .quick-link:hover {
            border-color: #1e3a8a;
            background: #f0f4fe;
        }
        .quick-link span { display: block; font-size: 2rem; margin-bottom: 6px; }

        /* ===== ТАБЛИЦА ПОСЛЕДНИХ ЗАКАЗОВ ===== */
        .table-wrapper {
            background: #fff;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .table-wrapper h3 { font-size: 1.2rem; color: #1e3a8a; margin-bottom: 15px; }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.data-table th {
            background: #f8fafd;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        table.data-table td {
            padding: 10px 10px;
            border-bottom: 1px solid #edf2f7;
            color: #333;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .badge.new { background: #dbeafe; color: #1e40af; }
        .badge.paid { background: #d1fae5; color: #065f46; }
        .badge.progress { background: #fef3c7; color: #92400e; }
        .badge.cancelled { background: #fee2e2; color: #991b1b; }

        .footer-note { text-align: center; margin-top: 30px; color: #888; font-size: 0.85rem; }
    </style>
</head>
<body>
    <!-- Верхняя панель -->
    <header class="topbar">
        <div class="logo">AdAgency <span style="color:#2563eb;">Pro</span></div>
        <nav class="user-menu">
    <a href="clients.php">Клиенты</a>
    <a href="employees.php">Сотрудники</a>
    <a href="services.php">Услуги</a>
    <a href="orders.php">Заказы</a>
    <span style="margin-left: 20px; color: #374151; font-weight: 500;">
        <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
    </span>
    <a href="logout.php" style="color: #dc2626; font-weight: 600;">Выйти</a>
</nav>
    </header>

    <div class="container">
        <h1 class="page-title">Панель управления</h1>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Всего клиентов</div>
                <div class="value"><?= $stats['clients'] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Сотрудников</div>
                <div class="value"><?= $stats['employees'] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Услуг в прайсе</div>
                <div class="value"><?= $stats['services'] ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #2563eb;">
                <div class="label">Активных заказов</div>
                <div class="value"><?= $stats['orders_active'] ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #16a34a;">
                <div class="label">Заказов сегодня</div>
                <div class="value"><?= $stats['orders_today'] ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #c026d3;">
                <div class="label">Выручка за месяц</div>
                <div class="value"><?= number_format($stats['revenue_month'], 2, '.', ' ') ?> ₽</div>
            </div>
        </div>

        <!-- Быстрые действия -->
        <div class="quick-links">
            <a href="clients.php" class="quick-link"><span>👥</span>Новый клиент</a>
            <a href="orders.php" class="quick-link"><span>🛒</span>Создать заказ</a>
            <a href="services.php" class="quick-link"><span>📋</span>Прайс-лист</a>
            <a href="employees.php" class="quick-link"><span>👤</span>Сотрудники</a>
        </div>

        <!-- Последние заказы -->
        <div class="table-wrapper">
            <h3>📋 Последние заказы</h3>
            <?php if (count($recentOrders) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Клиент</th>
                        <th>Менеджер</th>
                        <th>Статус</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $o):
                        $badgeClass = '';
                        if ($o['status'] == 'new') $badgeClass = 'new';
                        elseif ($o['status'] == 'paid') $badgeClass = 'paid';
                        elseif ($o['status'] == 'in_progress') $badgeClass = 'progress';
                        elseif ($o['status'] == 'cancelled') $badgeClass = 'cancelled';
                        ?>
                    <tr>
                        <td>#<?= $o['id'] ?></td>
                        <td><?= date('d.m.Y', strtotime($o['order_date'])) ?></td>
                        <td><?= htmlspecialchars($o['client']) ?></td>
                        <td><?= htmlspecialchars($o['manager']) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $o['status'] ?></span></td>
                        <td><?= number_format($o['total_amount'], 2, '.', ' ') ?> ₽</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="padding: 20px; color:#666;">Заказов пока нет.</p>
            <?php endif; ?>
        </div>

        <div class="footer-note">AdAgency Pro &copy; <?= date('Y') ?> – Корпоративная CRM для рекламного агентства</div>
    </div>
</body>
</html>