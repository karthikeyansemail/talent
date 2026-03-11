<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'dev');
$pageTitle = 'Instances';

$db = db();

// Provisioning context from order-new.php redirect
$provisionOrderId    = (int)($_GET['order_id'] ?? 0);
$provisionCustomerId = (int)($_GET['customer_id'] ?? 0);
$provisionMode       = isset($_GET['provision']) && ($provisionOrderId || $provisionCustomerId);

// Pre-fetch customer name for provisioning banner
$provisionCustomer = null;
if ($provisionCustomerId) {
    $stmt = $db->prepare('SELECT name, company FROM customers WHERE id = ?');
    $stmt->execute([$provisionCustomerId]);
    $provisionCustomer = $stmt->fetch();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $domain     = trim($_POST['domain'] ?? '');
        $name       = trim($_POST['name'] ?? '');
        $env        = $_POST['environment'] ?? 'production';
        $orderId    = (int)($_POST['order_id'] ?? 0) ?: null;
        $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;

        if ($domain && $name && in_array($env, ['production','staging','local'], true)) {
            // Check uniqueness
            $check = $db->prepare('SELECT id FROM instances WHERE domain = ?');
            $check->execute([$domain]);
            if ($check->fetch()) {
                flash('error', 'An instance with this domain already exists.');
            } else {
                $apiKey = bin2hex(random_bytes(32));
                $db->prepare(
                    'INSERT INTO instances (domain, name, customer_id, order_id, api_key, environment) VALUES (?,?,?,?,?,?)'
                )->execute([$domain, $name, $customerId, $orderId, $apiKey, $env]);
                $instanceId = (int)$db->lastInsertId();

                // ── Workflow: link order back to this instance ──
                if ($orderId) {
                    $db->prepare('UPDATE orders SET instance_id = ? WHERE id = ?')->execute([$instanceId, $orderId]);
                }

                flash('success', 'Instance provisioned. API Key (copy now — shown once): ' . $apiKey);
            }
        } else {
            flash('error', 'Domain and name are required.');
        }
        header('Location: ' . BASE . '/instances.php');
        exit;
    }

    if ($action === 'toggle_active') {
        $iid = (int)($_POST['instance_id'] ?? 0);
        if ($iid) {
            $db->prepare('UPDATE instances SET is_active = NOT is_active WHERE id = ?')->execute([$iid]);
            flash('success', 'Instance status toggled.');
        }
        header('Location: ' . BASE . '/instances.php');
        exit;
    }

    if ($action === 'regenerate_key') {
        $iid = (int)($_POST['instance_id'] ?? 0);
        if ($iid) {
            $newKey = bin2hex(random_bytes(32));
            $db->prepare('UPDATE instances SET api_key = ? WHERE id = ?')->execute([$newKey, $iid]);
            flash('success', 'New API Key (copy now — shown once): ' . $newKey);
        }
        header('Location: ' . BASE . '/instances.php');
        exit;
    }
}

// Fetch instances with error counts
$instances = $db->query(
    "SELECT i.*,
        (SELECT COUNT(*) FROM error_logs WHERE instance_id=i.id AND is_resolved=0) as unresolved_errors,
        (SELECT COUNT(*) FROM error_logs WHERE instance_id=i.id) as total_errors
     FROM instances i ORDER BY i.domain"
)->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Modal: Register / Provision Instance -->
<div class="modal-overlay hidden" id="instance-modal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><?= $provisionMode ? 'Provision Customer Instance' : 'Register New Instance' ?></h2>
            <button class="modal-close" onclick="closeInstanceModal()">&times;</button>
        </div>
        <?php if ($provisionMode && $provisionCustomer): ?>
        <div style="margin:16px 24px 0;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:13px;color:#1e40af">
            Provisioning for <strong><?= h($provisionCustomer['name']) ?><?= $provisionCustomer['company'] ? ' (' . h($provisionCustomer['company']) . ')' : '' ?></strong>
            <?php if ($provisionOrderId): ?> — Order #<?= $provisionOrderId ?><?php endif; ?>
            <div style="font-size:12px;margin-top:2px;color:#3b82f6">The instance will be linked to this order automatically.</div>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="order_id" value="<?= $provisionOrderId ?>">
            <input type="hidden" name="customer_id" value="<?= $provisionCustomerId ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>Domain *</label>
                    <input type="text" name="domain" class="form-control" placeholder="app.client.com" required autofocus>
                </div>
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" class="form-control"
                           value="<?= $provisionCustomer ? h($provisionCustomer['company'] ?: $provisionCustomer['name']) : '' ?>"
                           placeholder="Client or server name" required>
                </div>
                <div class="form-group">
                    <label>Environment</label>
                    <select name="environment" class="form-control">
                        <option value="production">Production</option>
                        <option value="staging">Staging</option>
                        <option value="local">Local</option>
                    </select>
                </div>
                <div style="background:var(--gray-50);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;font-size:12px;color:var(--gray-500)">
                    An API key will be generated automatically and shown once after registration.
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($provisionMode): ?>
                <a href="<?= BASE ?>/instances.php" class="btn btn-secondary">Skip</a>
                <?php else: ?>
                <button type="button" class="btn btn-secondary" onclick="closeInstanceModal()">Cancel</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $provisionMode ? 'Provision Instance' : 'Register Instance' ?></button>
            </div>
        </form>
    </div>
</div>
<script>
function openInstanceModal()  { document.getElementById('instance-modal').classList.remove('hidden'); }
function closeInstanceModal() { document.getElementById('instance-modal').classList.add('hidden'); }
document.getElementById('instance-modal').addEventListener('click', function(e) { if (e.target === this && !<?= $provisionMode ? 'true' : 'false' ?>) closeInstanceModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && !<?= $provisionMode ? 'true' : 'false' ?>) closeInstanceModal(); });
<?php if ($provisionMode): ?>document.addEventListener('DOMContentLoaded', openInstanceModal);<?php endif; ?>
</script>

<?php if ($provisionMode): ?>
<!-- Provisioning Banner -->
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <svg width="20" height="20" fill="none" stroke="#1e40af" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    <div>
        <strong style="color:#1e40af">Provision Instance for<?= $provisionCustomer ? ' ' . h($provisionCustomer['name'] . ($provisionCustomer['company'] ? ' (' . $provisionCustomer['company'] . ')' : '')) : '' ?></strong>
        <?php if ($provisionOrderId): ?>
        <span style="font-size:12px;color:#3b82f6;margin-left:8px">Order #<?= $provisionOrderId ?></span>
        <?php endif; ?>
    </div>
    <button class="btn btn-primary" style="margin-left:auto" onclick="openInstanceModal()">Provision Instance</button>
    <a href="<?= BASE ?>/instances.php" style="font-size:12px;color:#6b7280">Skip →</a>
</div>
<?php endif; ?>

<?php if (!$provisionMode): ?>
<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button class="btn btn-primary" onclick="openInstanceModal()">+ Register Instance</button>
</div>
<?php endif; ?>

<!-- Instances List -->
<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Domain</th>
                <th>Name</th>
                <th>Environment</th>
                <th>Version</th>
                <th>Health</th>
                <th>Errors</th>
                <th>Status</th>
                <th style="width:180px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($instances as $inst): ?>
            <?php
                // Health dot logic
                $health = 'gray';
                $healthLabel = 'Never seen';
                if ($inst['last_seen_at']) {
                    $diff = time() - strtotime($inst['last_seen_at']);
                    if ($diff < 3600) { $health = 'green'; $healthLabel = time_ago($inst['last_seen_at']); }
                    elseif ($diff < 86400) { $health = 'yellow'; $healthLabel = time_ago($inst['last_seen_at']); }
                    else { $health = 'red'; $healthLabel = time_ago($inst['last_seen_at']); }
                }
            ?>
            <tr>
                <td><strong><?= h($inst['domain']) ?></strong></td>
                <td><?= h($inst['name']) ?></td>
                <td><?= status_badge($inst['environment']) ?></td>
                <td class="td-secondary"><?= h($inst['version'] ?? '-') ?></td>
                <td>
                    <span class="health-dot health-<?= $health ?>"></span>
                    <span class="td-secondary"><?= $healthLabel ?></span>
                </td>
                <td>
                    <?php if ($inst['unresolved_errors'] > 0): ?>
                    <a href="<?= BASE ?>/errors.php?instance=<?= $inst['id'] ?>&resolved=0" style="color:var(--danger);font-weight:600"><?= $inst['unresolved_errors'] ?> open</a>
                    <?php else: ?>
                    <span class="td-secondary">0</span>
                    <?php endif; ?>
                    <span class="td-secondary">/ <?= $inst['total_errors'] ?> total</span>
                </td>
                <td><?= $inst['is_active'] ? status_badge('active') : status_badge('cancelled') ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="instance_id" value="<?= $inst['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-secondary"><?= $inst['is_active'] ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="regenerate_key">
                            <input type="hidden" name="instance_id" value="<?= $inst['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Regenerate API key? The old key will stop working immediately.">New Key</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($instances)): ?>
            <tr><td colspan="8" class="empty-row">No instances registered.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
