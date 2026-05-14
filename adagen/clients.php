<?php
require_once 'auth.php';

// ========== Параметры ==========
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Удаление
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: clients.php?search=" . urlencode($search) . "&p=$page");
    exit;
}

// Добавление / обновление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO clients (client_type, full_name, inn, phone, email, contact_person, source, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['client_type'], $_POST['full_name'], $_POST['inn'], $_POST['phone'], $_POST['email'], $_POST['contact_person'], $_POST['source'], $_POST['status']]);
    } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE clients SET client_type=?, full_name=?, inn=?, phone=?, email=?, contact_person=?, source=?, status=? WHERE id=?");
        $stmt->execute([$_POST['client_type'], $_POST['full_name'], $_POST['inn'], $_POST['phone'], $_POST['email'], $_POST['contact_person'], $_POST['source'], $_POST['status'], $_POST['id']]);
    }
    header("Location: clients.php?search=" . urlencode($search) . "&p=$page");
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

// Подсчет количества для пагинации
$where = '';
$params = [];
if ($search !== '') {
    $where = " WHERE full_name LIKE ? OR phone LIKE ? OR inn LIKE ?";
    $s = "%$search%";
    $params = [$s, $s, $s];
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM clients $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Выборка
$sql = "SELECT * FROM clients $where ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты – AdAgency Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
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

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            gap: 15px;
        }
        .search-box input {
            padding: 9px 14px;
            border: 1px solid #ccd7e8;
            border-radius: 8px;
            width: 280px;
            font-size: 0.95rem;
        }
        .search-box button {
            padding: 9px 18px;
            background: #1e3a8a;
            color: #fff;
            border: none;
            border-radius: 8px;
            margin-left: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-add {
            background: #2563eb;
            color: #fff;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
        }
        .btn-add:hover { background: #1d4ed8; }

        .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 22px 28px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .form-card h2 { margin-bottom: 18px; color: #1e3a8a; font-size: 1.2rem; }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px 25px; }
        .form-group { flex: 1 1 200px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #374151; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ccd7e8;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .form-actions { margin-top: 20px; }
        .form-actions button {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .form-actions a { margin-left: 12px; color: #555; }

        .table-wrapper {
            background: #fff;
            border-radius: 12px;
            padding: 10px 20px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: #f8fafd;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        table td {
            padding: 10px 8px;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.92rem;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge.active { background: #d1fae5; color: #065f46; }
        .badge.archived { background: #fee2e2; color: #991b1b; }
        .actions a, .actions button {
            margin-right: 5px;
            color: #1e3a8a;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 6px;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            background: #fff;
            color: #1e3a8a;
            border: 1px solid #ccd7e8;
        }
        .pagination .active {
            background: #1e3a8a;
            color: #fff;
            border-color: #1e3a8a;
        }
        .pagination a:hover { background: #eaf0fc; }
    </style>
</head>
<body>
    <header class="page-header">
        <div>
            <div class="breadcrumb"><a href="index.php">Панель</a> / Клиенты</div>
            <h1>👥 Клиенты</h1>
        </div>
        <div class="toolbar" style="margin:0;">
            <div class="search-box">
                <form method="GET" action="clients.php">
                    <input type="text" name="search" placeholder="Поиск по имени, телефону или ИНН" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Найти</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Форма добавления/редактирования -->
        <div class="form-card" id="form-section">
            <h2><?= $edit ? 'Редактировать клиента' : 'Добавить нового клиента' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Тип лица</label>
                        <select name="client_type">
                            <option value="individual" <?= $edit && $edit['client_type'] == 'individual' ? 'selected' : '' ?>>Физическое лицо</option>
                            <option value="legal_entity" <?= $edit && $edit['client_type'] == 'legal_entity' ? 'selected' : '' ?>>Юридическое лицо</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ФИО / Наименование</label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($edit['full_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>ИНН</label>
                        <input type="text" name="inn" value="<?= htmlspecialchars($edit['inn'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" name="phone" required value="<?= htmlspecialchars($edit['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Контактное лицо</label>
                        <input type="text" name="contact_person" value="<?= htmlspecialchars($edit['contact_person'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Источник привлечения</label>
                        <input type="text" name="source" value="<?= htmlspecialchars($edit['source'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Статус</label>
                        <select name="status">
                            <option value="active" <?= $edit && $edit['status'] == 'active' ? 'selected' : '' ?>>Активный</option>
                            <option value="archived" <?= $edit && $edit['status'] == 'archived' ? 'selected' : '' ?>>Архивный</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit"><?= $edit ? 'Сохранить изменения' : 'Добавить клиента' ?></button>
                    <?php if ($edit): ?><a href="clients.php?search=<?= urlencode($search) ?>&p=<?= $page ?>">Отмена</a><?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Таблица клиентов -->
        <div class="table-wrapper">
            <div style="display:flex; justify-content:space-between; align-items:center; margin:10px 0 15px;">
                <div>Всего записей: <strong><?= $total ?></strong></div>
                <?php if ($search): ?>
                    <a href="clients.php" style="color:#2563eb;">Сбросить поиск</a>
                <?php endif; ?>
            </div>
            <?php if (count($clients) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тип</th>
                            <th>Название</th>
                            <th>ИНН</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= $c['client_type'] == 'individual' ? 'Физ.' : 'Юр.' ?></td>
                            <td><?= htmlspecialchars($c['full_name']) ?></td>
                            <td><?= htmlspecialchars($c['inn'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($c['phone']) ?></td>
                            <td><?= htmlspecialchars($c['email'] ?: '—') ?></td>
                            <td><span class="badge <?= $c['status'] == 'active' ? 'active' : 'archived' ?>"><?= $c['status'] == 'active' ? 'Активен' : 'Архив' ?></span></td>
                            <td class="actions">
                                <a href="clients.php?edit=<?= $c['id'] ?>&search=<?= urlencode($search) ?>&p=<?= $page ?>">Редакт.</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Удалить клиента?')">
                                    <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                                    <button type="submit">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding: 20px;">Клиентов не найдено.</p>
            <?php endif; ?>

            <!-- Пагинация -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?search=<?= urlencode($search) ?>&p=<?= $page-1 ?>">«</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?search=<?= urlencode($search) ?>&p=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?search=<?= urlencode($search) ?>&p=<?= $page+1 ?>">»</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>