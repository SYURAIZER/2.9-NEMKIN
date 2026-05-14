<?php
require_once 'auth.php';
// ========== Параметры ==========
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Удаление
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: services.php?search=" . urlencode($search) . "&p=$page");
    exit;
}

// Добавление / обновление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $categoryId = $_POST['category_id'] !== '' ? $_POST['category_id'] : null;
    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO services (category_id, name, unit, base_price, cost_price, min_order_qty, seasonal_coefficient, is_active) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$categoryId, $_POST['name'], $_POST['unit'], $_POST['base_price'], $_POST['cost_price'], $_POST['min_order_qty'], $_POST['seasonal_coefficient'], $isActive]);
    } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE services SET category_id=?, name=?, unit=?, base_price=?, cost_price=?, min_order_qty=?, seasonal_coefficient=?, is_active=? WHERE id=?");
        $stmt->execute([$categoryId, $_POST['name'], $_POST['unit'], $_POST['base_price'], $_POST['cost_price'], $_POST['min_order_qty'], $_POST['seasonal_coefficient'], $isActive, (int)$_POST['id']]);
    }
    header("Location: services.php?search=" . urlencode($search) . "&p=$page");
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

// Выборка категорий для формы
$categories = $pdo->query("SELECT id, name FROM service_categories ORDER BY name")->fetchAll();

// Пагинация и поиск
$where = '';
$params = [];
if ($search !== '') {
    $where = " WHERE s.name LIKE ?";
    $s = "%$search%";
    $params = [$s];
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM services s $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$sql = "SELECT s.*, sc.name AS cat_name FROM services s LEFT JOIN service_categories sc ON s.category_id = sc.id $where ORDER BY s.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Прайс-лист – AdAgency Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Те же стили, что в employees.php (скопированы) */
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
        .container { max-width: 1200px; margin: 25px auto; padding: 0 20px; }
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
        .badge.active { background: #d1fae5; color: #065f46; }
        .badge.inactive { background: #fee2e2; color: #991b1b; }
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
            <div class="breadcrumb"><a href="index.php">Панель</a> / Услуги</div>
            <h1>📋 Прайс-лист</h1>
        </div>
        <div class="toolbar" style="margin:0;">
            <div class="search-box">
                <form method="GET" action="services.php">
                    <input type="text" name="search" placeholder="Поиск по названию услуги" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Найти</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-card">
            <h2><?= $edit ? 'Редактировать услугу' : 'Добавить новую услугу' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Категория</label>
                        <select name="category_id">
                            <option value="">Без категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $edit && $edit['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Наименование</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Ед. измерения</label>
                        <input type="text" name="unit" required value="<?= htmlspecialchars($edit['unit'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Базовая цена</label>
                        <input type="number" step="0.01" name="base_price" required value="<?= htmlspecialchars($edit['base_price'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Себестоимость</label>
                        <input type="number" step="0.01" name="cost_price" value="<?= htmlspecialchars($edit['cost_price'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Мин. объём</label>
                        <input type="number" name="min_order_qty" value="<?= htmlspecialchars($edit['min_order_qty'] ?? '1') ?>">
                    </div>
                    <div class="form-group">
                        <label>Сезонный коэффициент</label>
                        <input type="number" step="0.01" name="seasonal_coefficient" value="<?= htmlspecialchars($edit['seasonal_coefficient'] ?? '1.00') ?>">
                    </div>
                    <div class="form-group" style="flex:0 0 150px; display:flex; align-items:center;">
                        <label style="margin-right:10px;">Активна</label>
                        <input type="checkbox" name="is_active" value="1" <?= $edit && $edit['is_active'] ? 'checked' : '' ?> style="width:auto;">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit"><?= $edit ? 'Сохранить' : 'Добавить' ?></button>
                    <?php if ($edit): ?><a href="services.php?search=<?= urlencode($search) ?>&p=<?= $page ?>">Отмена</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-wrapper">
            <div style="display:flex; justify-content:space-between; align-items:center; margin:10px 0 15px;">
                <div>Всего услуг: <strong><?= $total ?></strong></div>
                <?php if ($search): ?><a href="services.php" style="color:#2563eb;">Сбросить поиск</a><?php endif; ?>
            </div>
            <?php if (count($services) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Категория</th>
                            <th>Услуга</th>
                            <th>Ед.</th>
                            <th>Цена</th>
                            <th>Себест.</th>
                            <th>Коэфф.</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $svc): ?>
                        <tr>
                            <td><?= $svc['id'] ?></td>
                            <td><?= htmlspecialchars($svc['cat_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($svc['name']) ?></td>
                            <td><?= htmlspecialchars($svc['unit']) ?></td>
                            <td><?= number_format($svc['base_price'], 2) ?> ₽</td>
                            <td><?= number_format($svc['cost_price'] ?? 0, 2) ?> ₽</td>
                            <td><?= $svc['seasonal_coefficient'] ?></td>
                            <td><span class="badge <?= $svc['is_active'] ? 'active' : 'inactive' ?>"><?= $svc['is_active'] ? 'Да' : 'Нет' ?></span></td>
                            <td class="actions">
                                <a href="services.php?edit=<?= $svc['id'] ?>&search=<?= urlencode($search) ?>&p=<?= $page ?>">Ред.</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Удалить услугу?')">
                                    <input type="hidden" name="delete_id" value="<?= $svc['id'] ?>">
                                    <button type="submit">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding:20px;">Услуг не найдено.</p>
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