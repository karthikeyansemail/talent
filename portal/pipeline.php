<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');
$pageTitle = 'Pipeline';

$db = db();

$stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

// Fetch leads grouped by stage
$pipeline = [];
foreach ($stages as $s) {
    $pipeline[$s] = [];
}

$leads = $db->query(
    "SELECT l.*, a.name as assigned_name, c.name as campaign_name
     FROM leads l
     LEFT JOIN admin_users a ON a.id = l.assigned_to
     LEFT JOIN campaigns c ON c.id = l.campaign_id
     ORDER BY l.updated_at DESC"
)->fetchAll();

foreach ($leads as $l) {
    $pipeline[$l['status']][] = $l;
}

// Stats
$totalLeads   = count($leads);
$wonCount     = count($pipeline['won']);
$lostCount    = count($pipeline['lost']);
$activeCount  = $totalLeads - $wonCount - $lostCount;
$conversionRate = $totalLeads > 0 ? round(($wonCount / $totalLeads) * 100, 1) : 0;

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Summary stats -->
<div class="kpi-row" style="margin-bottom:20px">
    <div class="kpi-card">
        <div class="kpi-value"><?= $totalLeads ?></div>
        <div class="kpi-label">Total Leads</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?= $activeCount ?></div>
        <div class="kpi-label">Active Pipeline</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value" style="color:var(--success)"><?= $wonCount ?></div>
        <div class="kpi-label">Won</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value" style="color:var(--danger)"><?= $lostCount ?></div>
        <div class="kpi-label">Lost</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?= $conversionRate ?>%</div>
        <div class="kpi-label">Conversion Rate</div>
    </div>
</div>

<!-- Pipeline board -->
<div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:16px">
    <?php foreach ($stages as $stage): ?>
    <div style="min-width:190px;flex:1">
        <div style="padding:8px 12px;background:var(--gray-100);border-radius:var(--radius) var(--radius) 0 0;font-weight:600;font-size:13px;display:flex;justify-content:space-between;align-items:center">
            <span><?= ucfirst($stage) ?></span>
            <span style="background:var(--gray-300);color:#fff;border-radius:10px;padding:1px 8px;font-size:11px"><?= count($pipeline[$stage]) ?></span>
        </div>
        <div style="background:var(--gray-50);border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);min-height:200px;padding:8px;display:flex;flex-direction:column;gap:8px">
            <?php foreach ($pipeline[$stage] as $l): ?>
            <a href="<?= BASE ?>/lead-view.php?id=<?= $l['id'] ?>" style="text-decoration:none;color:inherit">
                <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:10px;font-size:13px;cursor:pointer;transition:box-shadow 0.15s" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                    <div style="font-weight:600;margin-bottom:4px"><?= h($l['name']) ?></div>
                    <?php if ($l['company']): ?><div style="color:var(--gray-500);font-size:12px"><?= h($l['company']) ?></div><?php endif; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                        <?= platform_badge($l['source']) ?>
                        <span style="color:var(--gray-400);font-size:11px"><?= time_ago($l['created_at']) ?></span>
                    </div>
                    <?php if ($l['assigned_name']): ?>
                    <div style="color:var(--gray-400);font-size:11px;margin-top:4px"><?= h($l['assigned_name']) ?></div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if (empty($pipeline[$stage])): ?>
            <div style="color:var(--gray-400);font-size:12px;text-align:center;padding:20px 0">No leads</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
