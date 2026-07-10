<?php
session_start();
$db_path = __DIR__ . '/clients.db';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Новый',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $db->prepare("INSERT INTO clients (name, phone, status) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['status']]);
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
                break;
            case 'update_status':
                $stmt = $db->prepare("UPDATE clients SET status = ? WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['id']]);
                echo json_encode(['success' => true]);
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                echo json_encode(['success' => true]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$clients = $db->query("SELECT * FROM clients ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$statuses = $db->query("SELECT status, COUNT(*) as count FROM clients GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$status_counts = [];
foreach ($statuses as $s) {
    $status_counts[$s['status']] = $s['count'];
}
$total = array_sum($status_counts);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal CRM</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #635bff;
            --primary-light: #7a73ff;
            --secondary: #00d4ff;
            --accent: #ff6b9d;
            --success: #00c853;
            --warning: #ffab00;
            --danger: #ff1744;
            --bg: #0a0a0f;
            --surface: #12121a;
            --surface-light: #1a1a25;
            --border: #2a2a3a;
            --text: #ffffff;
            --text-secondary: #a0a0b0;
        }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Subtle background */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(99, 91, 255, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(0, 212, 255, 0.06) 0%, transparent 50%);
            z-index: -1;
        }
        
        .header {
            background: rgba(18, 18, 26, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        
        .logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 4px 16px rgba(99, 91, 255, 0.25);
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #ffffff, #a0a0b0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 12px rgba(99, 91, 255, 0.25);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(99, 91, 255, 0.4);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Stats Cards - Clean */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .stat-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 32px rgba(99, 91, 255, 0.15);
        }
        
        .stat-card.total::before { background: linear-gradient(90deg, var(--primary), var(--accent)); }
        .stat-card.new::before { background: var(--primary); }
        .stat-card.work::before { background: var(--warning); }
        .stat-card.closed::before { background: var(--success); }
        .stat-card.cancelled::before { background: var(--danger); }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(135deg, #ffffff, #a0a0b0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-card.total .stat-value { background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; }
        .stat-card.new .stat-value { color: var(--primary); }
        .stat-card.work .stat-value { color: var(--warning); }
        .stat-card.closed .stat-value { color: var(--success); }
        .stat-card.cancelled .stat-value { color: var(--danger); }
        
        /* Table - Clean */
        .table-container {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-title {
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(90deg, #ffffff, #a0a0b0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem 2rem;
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid var(--border);
            background: var(--surface-light);
        }
        
        td {
            padding: 1.25rem 2rem;
            border-bottom: 1px solid rgba(42, 42, 58, 0.5);
            font-size: 0.9rem;
            transition: background 0.15s;
        }
        
        tr:hover td {
            background: rgba(99, 91, 255, 0.03);
        }
        
        .client-name {
            font-weight: 600;
            color: var(--text);
        }
        
        .client-phone {
            color: var(--text-secondary);
            font-family: 'SF Mono', 'Monaco', monospace;
            font-size: 0.85rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.875rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        
        .status-badge:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .status-new {
            background: rgba(99, 91, 255, 0.12);
            color: var(--primary);
            border-color: rgba(99, 91, 255, 0.3);
        }
        
        .status-work {
            background: rgba(255, 171, 0, 0.12);
            color: var(--warning);
            border-color: rgba(255, 171, 0, 0.3);
        }
        
        .status-closed {
            background: rgba(0, 200, 83, 0.12);
            color: var(--success);
            border-color: rgba(0, 200, 83, 0.3);
        }
        
        .status-cancelled {
            background: rgba(255, 23, 68, 0.12);
            color: var(--danger);
            border-color: rgba(255, 23, 68, 0.3);
        }
        
        .btn-delete {
            background: rgba(255, 23, 68, 0.08);
            border: 1px solid rgba(255, 23, 68, 0.2);
            color: var(--danger);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-delete:hover {
            background: rgba(255, 23, 68, 0.15);
            transform: scale(1.05);
        }
        
        /* Modal - Clean */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 2rem;
            width: 90%;
            max-width: 480px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.4);
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            background: linear-gradient(90deg, #ffffff, #a0a0b0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 0.9rem;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 91, 255, 0.1);
        }
        
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        .btn-cancel {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-cancel:hover {
            border-color: var(--text-secondary);
            color: var(--text);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s;
            box-shadow: 0 2px 12px rgba(99, 91, 255, 0.25);
        }
        
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(99, 91, 255, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .container { padding: 1.25rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .header { padding: 1rem 1.25rem; flex-direction: column; gap: 1rem; }
            table { font-size: 0.8rem; }
            th, td { padding: 1rem 1.25rem; }
            .stat-value { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    
    <div class="header">
        <div class="header-left">
            <div class="logo">⚖️</div>
            <div>
                <h1>Legal CRM</h1>
            </div>
        </div>
        <button class="btn-primary" onclick="openModal()">
            <span>+</span> Добавить клиента
        </button>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Всего клиентов</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat-card new">
                <div class="stat-label">Новые</div>
                <div class="stat-value"><?= $status_counts['Новый'] ?? 0 ?></div>
            </div>
            <div class="stat-card work">
                <div class="stat-label">В работе</div>
                <div class="stat-value"><?= $status_counts['В работе'] ?? 0 ?></div>
            </div>
            <div class="stat-card closed">
                <div class="stat-label">Закрыто</div>
                <div class="stat-value"><?= $status_counts['Закрыт'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">Список клиентов</div>
            </div>
            <?php if (empty($clients)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>Нет клиентов. Нажмите «Добавить клиента» чтобы начать.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Телефон</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="client-name"><?= htmlspecialchars($client['name']) ?></td>
                                <td class="client-phone"><?= htmlspecialchars($client['phone']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = match($client['status']) {
                                        'Новый' => 'status-new',
                                        'В работе' => 'status-work',
                                        'Закрыт' => 'status-closed',
                                        'Отменён' => 'status-cancelled',
                                        default => 'status-new'
                                    };
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"
                                          onclick="cycleStatus(<?= $client['id'] ?>, '<?= $client['status'] ?>')">
                                        <?= $client['status'] ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                    <?= date('d.m.Y H:i', strtotime($client['created_at'])) ?>
                                </td>
                                <td>
                                    <button class="btn-delete" onclick="deleteClient(<?= $client['id'] ?>)" title="Удалить">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <div class="modal-title">Новый клиент</div>
            <form id="addForm" onsubmit="addClient(event)">
                <div class="form-group">
                    <label>Имя клиента</label>
                    <input type="text" name="name" required placeholder="Иван Иванов">
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" required placeholder="+7 (999) 123-45-67">
                </div>
                <div class="form-group">
                    <label>Статус дела</label>
                    <select name="status">
                        <option value="Новый">Новый</option>
                        <option value="В работе">В работе</option>
                        <option value="Закрыт">Закрыт</option>
                        <option value="Отменён">Отменён</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn-submit">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const STATUS_FLOW = ['Новый', 'В работе', 'Закрыт'];

        function cycleStatus(id, current) {
            const idx = STATUS_FLOW.indexOf(current);
            const next = STATUS_FLOW[(idx + 1) % STATUS_FLOW.length];
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&id=${id}&status=${encodeURIComponent(next)}`
            }).then(() => location.reload());
        }

        function deleteClient(id) {
            if (!confirm('Удалить клиента?')) return;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&id=${id}`
            }).then(() => location.reload());
        }

        function openModal() {
            document.getElementById('modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
            document.getElementById('addForm').reset();
        }

        function addClient(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&${new URLSearchParams(data)}`
            }).then(() => {
                closeModal();
                location.reload();
            });
        }

        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>
