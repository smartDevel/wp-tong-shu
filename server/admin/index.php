<?php
require_once __DIR__ . '/../db/database.php';

$db = new WPTS_DB(__DIR__ . '/../db/licenses.sqlite');
$message = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $key = $_POST['license_key'] ?: $db->generate_key();
        $type = $_POST['type'] ?? 'single';
        $max_sites = intval($_POST['max_sites'] ?? 1);
        $email = $_POST['email'] ?? '';
        $name = $_POST['customer_name'] ?? '';
        $expires = $_POST['expires_at'] ?: null;

        try {
            $db->create_license($key, $type, $max_sites, $email, $name, $expires);
            $message = "✅ Lizenz erstellt: $key";
        } catch (Exception $e) {
            $message = "❌ Fehler: " . $e->getMessage();
        }
    } elseif ($action === 'status') {
        $db->update_status($_POST['license_key'], $_POST['status']);
        $message = "✅ Status aktualisiert";
    } elseif ($action === 'delete') {
        $db->delete_license($_POST['license_key']);
        $message = "✅ Lizenz gelöscht";
    }
}

$licenses = $db->get_all_licenses();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🏮 Tong Shu — Lizenzverwaltung</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; }
h1 { margin-bottom: 20px; }
.message { background: #d1fae5; border: 1px solid #10b981; padding: 10px 15px; border-radius: 6px; margin-bottom: 20px; }
.create-form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
.create-form h2 { margin-bottom: 15px; }
.form-row { display: flex; gap: 15px; margin-bottom: 10px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 14px; }
.form-group input, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; }
.btn { background: #39b152; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn:hover { background: #2d8a41; }
.btn-danger { background: #dc2626; }
.btn-danger:hover { background: #b91c1c; }
.btn-sm { padding: 5px 10px; font-size: 12px; }
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
th { background: #f9fafb; font-weight: 600; }
.status-active { color: #16a34a; font-weight: 600; }
.status-expired { color: #dc2626; font-weight: 600; }
.status-revoked { color: #6b7280; font-weight: 600; }
</style>
</head>
<body>
<div class="container">
    <h1>🏮 Tong Shu — Lizenzverwaltung</h1>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <!-- Create License -->
    <div class="create-form">
        <h2>Neue Lizenz erstellen</h2>
        <form method="post">
            <input type="hidden" name="action" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Lizenzschlüssel</label>
                    <input type="text" name="license_key" placeholder="Auto-generiert wenn leer">
                </div>
                <div class="form-group">
                    <label>Typ</label>
                    <select name="type">
                        <option value="single">Single Site</option>
                        <option value="multi">Multi Site (5)</option>
                        <option value="unlimited">Unlimited</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Max. Sites</label>
                    <input type="number" name="max_sites" value="1" min="1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kunde</label>
                    <input type="text" name="customer_name">
                </div>
                <div class="form-group">
                    <label>E-Mail</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Gültig bis</label>
                    <input type="date" name="expires_at">
                </div>
            </div>
            <button type="submit" class="btn">Lizenz erstellen</button>
        </form>
    </div>

    <!-- License List -->
    <table>
        <thead>
            <tr>
                <th>Lizenzschlüssel</th>
                <th>Typ</th>
                <th>Status</th>
                <th>Sites</th>
                <th>Kunde</th>
                <th>E-Mail</th>
                <th>Gültig bis</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($licenses as $lic): ?>
            <tr>
                <td><code><?= htmlspecialchars($lic['license_key']) ?></code></td>
                <td><?= htmlspecialchars($lic['type']) ?></td>
                <td class="status-<?= $lic['status'] ?>"><?= $lic['status'] ?></td>
                <td><?= $lic['active_sites'] ?> / <?= $lic['max_sites'] ?></td>
                <td><?= htmlspecialchars($lic['customer_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($lic['email'] ?? '') ?></td>
                <td><?= $lic['expires_at'] ?? '∞' ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="license_key" value="<?= $lic['license_key'] ?>">
                        <select name="status" onchange="this.form.submit()" style="font-size:12px">
                            <option value="active" <?= $lic['status']==='active'?'selected':'' ?>>Active</option>
                            <option value="expired" <?= $lic['status']==='expired'?'selected':'' ?>>Expired</option>
                            <option value="revoked" <?= $lic['status']==='revoked'?'selected':'' ?>>Revoked</option>
                        </select>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Löschen?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="license_key" value="<?= $lic['license_key'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($licenses)): ?>
            <tr><td colspan="8" style="text-align:center;color:#999;padding:30px;">Noch keine Lizenzen erstellt.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
