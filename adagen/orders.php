<?php
require_once 'auth.php';

// ========== Параметры ==========
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Удаление
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: orders.php?search=" . urlencode($search) . "&p=$page");
    exit;
}

// Добавление / обновление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO orders (client_id, employee_id, order_date, planned_completion_date, status, discount_percent, discount_absolute, vat_rate, notes) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['client_id'], $_POST['employee_id'], $_POST['order_date'], $_POST['planned_completion_date'], $_POST['status'], $_POST['discount_percent'], $_POST['discount_absolute'], $_POST['vat_rate'], $_POST['notes']]);
    } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE orders SET client_id=?, employee_id=?, order_date=?, planned_completion_date=?, status=?, discount_percent=?, discount_absolute=?, vat_rate=?, notes=?, total_amount=(SELECT COALESCE(SUM(total_price),0) FROM order_items WHERE order_id=?) WHERE id=?");
        $stmt->execute([$_POST['client_id'], $_POST['employee_id'], $_POST['order_date'], $_POST['planned_completion_date'], $_POST['status'], $_POST['discount_percent'], $_POST['discount_absolute'], $_POST['vat_rate'], $_POST['notes'], (int)$_POST['id'], (int)$_POST['id']]);
    }
    header("Location: orders.php?search=" . urlencode($search) . "&p=$page");
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

// Выпадающие списки
$clients = $pdo->query("SELECT id, full_name FROM clients WHERE status='active' ORDER BY full_name")->fetchAll();
$employees = $pdo->query("SELECT id, CONCAT(last_name, ' ', first_name) AS fio FROM employees WHERE is_active=1 ORDER BY last_name")->fetchAll();

// Пагинация и поиск
$where = '';
$params = [];
if ($search !== '') {
    $where = " WHERE c.full_name LIKE ? OR o.status LIKE ?";
    $s = "%$search%";
    $params = [$s, $s];
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN clients c ON o.client_id = c.id $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$sql = "SELECT o.*, c.full_name AS client, CONCAT(e.last_name,' ',e.first_name) AS manager
        FROM orders o
        JOIN clients c ON o.client_id = c.id
        JOIN employees e ON o.employee_id = e.id
        $where
        ORDER BY o.id DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

function statusBadge($s) {
    $map = [
        'new' => ['Новый', 'new'],
        'negotiation' => ['Согласование', 'negotiation'],
        'paid' => ['Оплачен', 'paid'],
        'in_progress' => ['В работе', 'progress'],
        'completed' => ['Выполнен', 'completed'],
        'cancelled' => ['Отменён', 'cancelled']
    ];
    [$label, $class] = $map[$s] ?? [$s, ''];
    return "<span class='badge $class'>$label</span>";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы – AdAgency Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Общие стили + кастомные для статусов */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fc; color: #222; }
        .page-header {
            background: #fff;
            padding: 20px 30px;
            border-bottom: 1px solid #d9e2ef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .page-header h1 { font-size: 1.6rem; color: #1e3a8a; }
        .breadcrumb a { color: #1e3a8a; font-weight: 500; margin-right: 10px; }
        .container { max-width: 1300px; margin: 25px auto; padding: 0 20px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px; gap: 15px; }
        .search-box input { padding: 9px 14px; border: 1px solid #ccd7e8; border-radius: 8px; width: 280px; font-size: 0.95rem; }
        .search-box button { padding: 9px 18px; background: #1e3a8a; color: #fff; border: none; border-radius: 8px; margin-left: 5px; cursor: pointer; font-weight: 500; }
        .btn-add { background: #2563eb; color: #fff; padding: 10px 22px; border-radius: 8px; font-weight: 600; display: inline-block; }
        .btn-add:hover { background: #1d4ed8; }
        .form-card { background: #fff; border-radius: 12px; padding: 22px 28px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .form-card h2 { margin-bottom: 18px; color: #1e3a8a; font-size: 1.2rem; }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px 25px; }
        .form-group { flex: 1 1 200px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #374151; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 9px 12px; border: 1px solid #ccd7e8; border-radius: 8px; font-size: 0.95rem; }
        .form-actions { margin-top: 20px; }
        .form-actions button { background: #2563eb; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .form-actions a { margin-left: 12px; color: #555; }
        .table-wrapper { background: #fff; border-radius: 12px; padding: 10px 20px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8fafd; padding: 12px 8px; text-align: left; font-weight: 600; font-size: 0.9rem; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
        table td { padding: 10px 8px; border-bottom: 1px solid #edf2f7; font-size: 0.92rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .badge.new { background: #dbeafe; color: #1e40af; }
        .badge.negotiation { background: #ede9fe; color: #6d28d9; }
        .badge.paid { background: #d1fae5; color: #065f46; }
        .badge.progress { background: #fef3c7; color: #92400e; }
        .badge.completed { background: #bbf7d0; color: #15803d; }
        .badge.cancelled { background: #fee2e2; color: #991b1b; }
        .actions a, .actions button { margin-right: 5px; color: #1e3a8a; background: none; border: none; cursor: pointer; font-weight: 500; }
        .pagination { display: flex; justify-content: center; margin-top: 25px; gap: 6px; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 14px; border-radius: 8px; background: #fff; color: #1e3a8a; border: 1px solid #ccd7e8; }
        .pagination .active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }
        .pagination a:hover { background: #eaf0fc; }
    </style>
</head>
<body>
    <header class="page-header">
        <div>
            <div class="breadcrumb"><a href="index.php">Панель</a> / Заказы</div>
            <h1>🛒 Заказы</h1>
        </div>
        <div class="toolbar" style="margin:0;">
            <div class="search-box">
                <form method="GET" action="orders.php">
                    <input type="text" name="search" placeholder="Поиск по клиенту или статусу" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Найти</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-card">
            <h2><?= $edit ? 'Редактировать заказ' : 'Создать новый заказ' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Клиент</label>
                        <select name="client_id" required>
                            <option value="">— Выберите —</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $edit && $edit['client_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Менеджер</label>
                        <select name="employee_id" required>
                            <option value="">— Выберите —</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $edit && $edit['employee_id'] == $emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['fio']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата оформления</label>
                        <input type="date" name="order_date" required value="<?= htmlspecialchars($edit['order_date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="form-group">
                        <label>План. завершение</label>
                        <input type="date" name="planned_completion_date" value="<?= htmlspecialchars($edit['planned_completion_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Статус</label>
                        <select name="status">
                            <?php
                            $statuses = ['new','negotiation','paid','in_progress','completed','cancelled'];
                            $labels = ['Новый','Согласование','Оплачен','В работе','Выполнен','Отменён'];
                            foreach ($statuses as $i => $st):
                                $sel = $edit && $edit['status'] == $st ? 'selected' : '';
                                echo "<option value=\"$st\" $sel>{$labels[$i]}</option>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Скидка %</label>
                        <input type="number" step="0.01" name="discount_percent" value="<?= htmlspecialchars($edit['discount_percent'] ?? '0') ?>">
                    </div>
                    <div class="form-group">
                        <label>Скидка ₽</label>
                        <input type="number" step="0.01" name="discount_absolute" value="<?= htmlspecialchars($edit['discount_absolute'] ?? '0') ?>">
                    </div>
                    <div class="form-group">
                        <label>Ставка НДС, %</label>
                        <input type="number" step="0.01" name="vat_rate" value="<?= htmlspecialchars($edit['vat_rate'] ?? '20') ?>">
                    </div>
                    <div class="form-group" style="flex:2 1 400px;">
                        <label>Примечания</label>
                        <textarea name="notes" rows="1"><?= htmlspecialchars($edit['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit"><?= $edit ? 'Сохранить' : 'Создать заказ' ?></button>
                    <?php if ($edit): ?><a href="orders.php?search=<?= urlencode($search) ?>&p=<?= $page ?>">Отмена</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-wrapper">
            <div style="display:flex; justify-content:space-between; align-items:center; margin:10px 0 15px;">
                <div>Всего заказов: <strong><?= $total ?></strong></div>
                <?php if ($search): ?><a href="orders.php" style="color:#2563eb;">Сбросить поиск</a><?php endif; ?>
            </div>
            <?php if (count($orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Клиент</th>
                            <th>Менеджер</th>
                            <th>Статус</th>
                            <th>Сумма</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= date('d.m.Y', strtotime($o['order_date'])) ?></td>
                            <td><?= htmlspecialchars($o['client']) ?></td>
                            <td><?= htmlspecialchars($o['manager']) ?></td>
                            <td><?= statusBadge($o['status']) ?></td>
                            <td><?= number_format($o['total_amount'], 2, '.', ' ') ?> ₽</td>
                            <td class="actions">
                                <a href="orders.php?edit=<?= $o['id'] ?>&search=<?= urlencode($search) ?>&p=<?= $page ?>">Ред.</a>
                                <a href="order_items.php?order_id=<?= $o['id'] ?>">Позиции</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Удалить заказ?')">
                                    <input type="hidden" name="delete_id" value="<?= $o['id'] ?>">
                                    <button type="submit">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding:20px;">Заказов не найдено.</p>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?><a href="?search=<?= urlencode($search) ?>&p=<?= $page-1 ?>">«</a><?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?><span class="active"><?= $i ?></span><?php else: ?><a href="?search=<?= urlencode($search) ?>&p=<?= $i ?>"><?= $i ?></a><?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?><a href="?search=<?= urlencode($search) ?>&p=<?= $page+1 ?>">»</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>