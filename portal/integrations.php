<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');
$pageTitle = 'Integrations';

$db = db();

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider = $_POST['provider'] ?? '';
    $allowed  = ['google_ads', 'meta_ads'];
    if (in_array($provider, $allowed, true)) {
        $configFields = match ($provider) {
            'google_ads' => ['client_id', 'client_secret', 'developer_token', 'customer_id', 'refresh_token'],
            'meta_ads'   => ['app_id', 'app_secret', 'access_token', 'ad_account_id'],
        };
        $config = [];
        foreach ($configFields as $f) {
            $config[$f] = trim($_POST[$f] ?? '');
        }
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $existing = $db->prepare('SELECT id FROM integrations WHERE provider = ?');
        $existing->execute([$provider]);
        if ($existing->fetch()) {
            $db->prepare('UPDATE integrations SET config = ?, is_active = ? WHERE provider = ?')
               ->execute([json_encode($config), $isActive, $provider]);
        } else {
            $db->prepare('INSERT INTO integrations (provider, config, is_active) VALUES (?, ?, ?)')
               ->execute([$provider, json_encode($config), $isActive]);
        }
        flash('success', ucwords(str_replace('_', ' ', $provider)) . ' integration saved.');
    }
    header('Location: ' . BASE . '/integrations.php');
    exit;
}

// Load integrations
$integrations = [];
$stmt = $db->query('SELECT * FROM integrations');
foreach ($stmt->fetchAll() as $row) {
    $integrations[$row['provider']] = $row;
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="display:flex;flex-direction:column;gap:20px;max-width:800px">

    <!-- Google Ads -->
    <?php
    $gConfig = json_decode($integrations['google_ads']['config'] ?? '{}', true) ?: [];
    $gActive = (int)($integrations['google_ads']['is_active'] ?? 0);
    ?>
    <div class="card">
        <div class="card-header">
            <span>Google Ads</span>
            <?php if ($gActive): ?>
                <span style="color:var(--success);font-size:12px;font-weight:600">Connected</span>
            <?php else: ?>
                <span style="color:var(--gray-400);font-size:12px">Not Connected</span>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding:20px">
            <form method="POST">
                <input type="hidden" name="provider" value="google_ads">
                <div class="form-group">
                    <label>Client ID</label>
                    <input type="text" name="client_id" class="form-control" value="<?= h($gConfig['client_id'] ?? '') ?>" placeholder="xxxx.apps.googleusercontent.com">
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="password" name="client_secret" class="form-control" value="<?= h($gConfig['client_secret'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Developer Token</label>
                    <input type="text" name="developer_token" class="form-control" value="<?= h($gConfig['developer_token'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Customer ID</label>
                    <input type="text" name="customer_id" class="form-control" value="<?= h($gConfig['customer_id'] ?? '') ?>" placeholder="123-456-7890">
                </div>
                <div class="form-group">
                    <label>Refresh Token</label>
                    <input type="text" name="refresh_token" class="form-control" value="<?= h($gConfig['refresh_token'] ?? '') ?>">
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:8px">
                    <input type="checkbox" name="is_active" id="ga_active" value="1" <?= $gActive ? 'checked' : '' ?>>
                    <label for="ga_active" style="margin:0">Enable Integration</label>
                </div>
                <button type="submit" class="btn btn-primary">Save Google Ads</button>
            </form>
        </div>
    </div>

    <!-- Meta Ads -->
    <?php
    $mConfig = json_decode($integrations['meta_ads']['config'] ?? '{}', true) ?: [];
    $mActive = (int)($integrations['meta_ads']['is_active'] ?? 0);
    ?>
    <div class="card">
        <div class="card-header">
            <span>Meta Ads (Facebook / Instagram)</span>
            <?php if ($mActive): ?>
                <span style="color:var(--success);font-size:12px;font-weight:600">Connected</span>
            <?php else: ?>
                <span style="color:var(--gray-400);font-size:12px">Not Connected</span>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding:20px">
            <form method="POST">
                <input type="hidden" name="provider" value="meta_ads">
                <div class="form-group">
                    <label>App ID</label>
                    <input type="text" name="app_id" class="form-control" value="<?= h($mConfig['app_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>App Secret</label>
                    <input type="password" name="app_secret" class="form-control" value="<?= h($mConfig['app_secret'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Access Token</label>
                    <input type="text" name="access_token" class="form-control" value="<?= h($mConfig['access_token'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Ad Account ID</label>
                    <input type="text" name="ad_account_id" class="form-control" value="<?= h($mConfig['ad_account_id'] ?? '') ?>" placeholder="act_123456789">
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:8px">
                    <input type="checkbox" name="is_active" id="ma_active" value="1" <?= $mActive ? 'checked' : '' ?>>
                    <label for="ma_active" style="margin:0">Enable Integration</label>
                </div>
                <button type="submit" class="btn btn-primary">Save Meta Ads</button>
            </form>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
