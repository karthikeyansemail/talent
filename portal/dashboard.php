<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'Dashboard';

$db = db();
$role = $_SESSION['admin_role'] ?? 'admin';

// Common KPIs
$stats = [];

// Admin + Sales KPIs
if (has_role('admin', 'sales')) {
    $stats['total_customers']   = $db->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    $stats['active_subs']       = $db->query("SELECT COUNT(*) FROM orders WHERE status='active'")->fetchColumn();
    $stats['mrr']               = $db->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='active' AND billing_period='monthly' AND currency='USD'")->fetchColumn();
    $stats['revenue_total_usd'] = $db->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='active' AND currency='USD'")->fetchColumn();
    $stats['total_leads']       = $db->query('SELECT COUNT(*) FROM leads')->fetchColumn();
    $stats['leads_this_month']  = $db->query("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
    $stats['pipeline_active']   = $db->query("SELECT COUNT(*) FROM leads WHERE status NOT IN ('won','lost')")->fetchColumn();
    $stats['upcoming_appointments'] = $db->query("SELECT COUNT(*) FROM appointments WHERE status='scheduled' AND scheduled_at >= NOW()")->fetchColumn();
}

// Admin + Support KPIs
if (has_role('admin', 'support')) {
    $stats['open_tickets']    = $db->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open','in_progress')")->fetchColumn();
    $stats['new_chats_today'] = $db->query("SELECT COUNT(*) FROM chat_sessions WHERE DATE(created_at)=CURDATE()")->fetchColumn();
}

// Admin + Dev KPIs
if (has_role('admin', 'dev')) {
    $stats['unresolved_errors'] = $db->query("SELECT COUNT(*) FROM error_logs WHERE is_resolved=0")->fetchColumn();
    $stats['open_dev_tickets']  = $db->query("SELECT COUNT(*) FROM dev_tickets WHERE status NOT IN ('resolved','closed')")->fetchColumn();
    $stats['active_instances']  = $db->query("SELECT COUNT(*) FROM instances WHERE is_active=1")->fetchColumn();
}

// Data for tables
$recentLeads = [];
$upcomingAppointments = [];
$recentOrders = [];
$recentTickets = [];
$recentErrors = [];
$openDevTickets = [];

if (has_role('admin', 'sales')) {
    $recentLeads = $db->query(
        "SELECT l.*, a.name as assigned_name FROM leads l
         LEFT JOIN admin_users a ON a.id=l.assigned_to
         ORDER BY l.created_at DESC LIMIT 8"
    )->fetchAll();

    $upcomingAppointments = $db->query(
        "SELECT ap.*, l.name as lead_name, l.company as lead_company
         FROM appointments ap JOIN leads l ON l.id=ap.lead_id
         WHERE ap.status='scheduled' AND ap.scheduled_at >= NOW()
         ORDER BY ap.scheduled_at ASC LIMIT 8"
    )->fetchAll();

    $recentOrders = $db->query(
        "SELECT o.*, c.name as cname FROM orders o
         JOIN customers c ON c.id=o.customer_id
         ORDER BY o.created_at DESC LIMIT 6"
    )->fetchAll();
}

if (has_role('admin', 'support')) {
    $recentTickets = $db->query(
        "SELECT t.*, c.name as cname FROM tickets t
         JOIN customers c ON c.id=t.customer_id
         ORDER BY t.created_at DESC LIMIT 8"
    )->fetchAll();
}

if (has_role('admin', 'dev')) {
    $recentErrors = $db->query(
        "SELECT e.*, i.domain FROM error_logs e
         LEFT JOIN instances i ON i.id=e.instance_id
         WHERE e.is_resolved=0
         ORDER BY e.last_seen_at DESC LIMIT 8"
    )->fetchAll();

    $openDevTickets = $db->query(
        "SELECT dt.*, a.name as assigned_name FROM dev_tickets dt
         LEFT JOIN admin_users a ON a.id=dt.assigned_to
         WHERE dt.status NOT IN ('resolved','closed')
         ORDER BY dt.created_at DESC LIMIT 8"
    )->fetchAll();
}

include __DIR__ . '/includes/layout-start.php';
?>

<!-- KPI Cards -->
<div class="stats-grid">
    <?php if (has_role('admin', 'sales')): ?>
    <div class="stat-card">
        <div class="stat-label">Leads This Month</div>
        <div class="stat-value"><?= number_format($stats['leads_this_month']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Pipeline</div>
        <div class="stat-value"><?= number_format($stats['pipeline_active']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Upcoming Appointments</div>
        <div class="stat-value"><?= number_format($stats['upcoming_appointments']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">MRR (USD)</div>
        <div class="stat-value"><?= format_currency((float)$stats['mrr']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Revenue (USD)</div>
        <div class="stat-value"><?= format_currency((float)$stats['revenue_total_usd']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (has_role('admin', 'support')): ?>
    <div class="stat-card">
        <div class="stat-label">Open Tickets</div>
        <div class="stat-value <?= $stats['open_tickets'] > 5 ? 'stat-warn' : '' ?>"><?= $stats['open_tickets'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">New Chats Today</div>
        <div class="stat-value"><?= $stats['new_chats_today'] ?></div>
    </div>
    <?php endif; ?>

    <?php if (has_role('admin', 'dev')): ?>
    <div class="stat-card">
        <div class="stat-label">Unresolved Errors</div>
        <div class="stat-value <?= $stats['unresolved_errors'] > 0 ? 'stat-warn' : '' ?>"><?= $stats['unresolved_errors'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Open Dev Tickets</div>
        <div class="stat-value"><?= $stats['open_dev_tickets'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Instances</div>
        <div class="stat-value"><?= $stats['active_instances'] ?></div>
    </div>
    <?php endif; ?>
</div>

<?php if (has_role('admin')): ?>
<!-- Admin: 3-column grid -->
<div class="three-col">
    <!-- Recent Leads -->
    <div class="card">
        <div class="card-header">
            <span>Recent Leads</span>
            <a href="<?= BASE ?>/leads.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Status</th><th>Age</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($recentLeads, 0, 6) as $l): ?>
            <tr>
                <td><a href="<?= BASE ?>/lead-view.php?id=<?= $l['id'] ?>" class="link"><?= h($l['name']) ?></a></td>
                <td><?= lead_status_badge($l['status']) ?></td>
                <td class="td-secondary"><?= time_ago($l['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentLeads)): ?><tr><td colspan="3" class="empty-row">No leads yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Tickets -->
    <div class="card">
        <div class="card-header">
            <span>Recent Tickets</span>
            <a href="<?= BASE ?>/tickets.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Subject</th><th>Status</th><th>Age</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($recentTickets, 0, 6) as $t): ?>
            <tr>
                <td><a href="<?= BASE ?>/ticket-view.php?id=<?= $t['id'] ?>" class="link"><?= h($t['subject']) ?></a></td>
                <td><?= status_badge($t['status']) ?></td>
                <td class="td-secondary"><?= time_ago($t['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentTickets)): ?><tr><td colspan="3" class="empty-row">No tickets yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Errors -->
    <div class="card">
        <div class="card-header">
            <span>Recent Errors</span>
            <a href="<?= BASE ?>/errors.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Error</th><th>Level</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($recentErrors, 0, 6) as $e): ?>
            <tr>
                <td>
                    <a href="<?= BASE ?>/error-view.php?id=<?= $e['id'] ?>" class="link"><?= h(mb_substr($e['exception_class'] ?: $e['message'], 0, 40)) ?></a>
                    <div class="td-secondary"><?= h($e['domain'] ?? 'Unknown') ?></div>
                </td>
                <td><?= level_badge($e['level']) ?></td>
                <td class="td-secondary"><?= $e['occurrence_count'] ?>×</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentErrors)): ?><tr><td colspan="3" class="empty-row">No errors.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif (has_role('sales')): ?>
<!-- Sales: Leads + Appointments -->
<div class="two-col">
    <div class="card">
        <div class="card-header">
            <span>Recent Leads</span>
            <a href="<?= BASE ?>/leads.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Company</th><th>Status</th><th>Source</th><th>Age</th></tr></thead>
            <tbody>
            <?php foreach ($recentLeads as $l): ?>
            <tr>
                <td><a href="<?= BASE ?>/lead-view.php?id=<?= $l['id'] ?>" class="link"><?= h($l['name']) ?></a></td>
                <td class="td-secondary"><?= h($l['company'] ?? '—') ?></td>
                <td><?= lead_status_badge($l['status']) ?></td>
                <td><?= platform_badge($l['source']) ?></td>
                <td class="td-secondary"><?= time_ago($l['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentLeads)): ?><tr><td colspan="5" class="empty-row">No leads yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">
            <span>Upcoming Appointments</span>
            <a href="<?= BASE ?>/appointments.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Lead</th><th>Type</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($upcomingAppointments as $ap): ?>
            <tr>
                <td>
                    <div class="td-primary"><?= h($ap['lead_name']) ?></div>
                    <?php if ($ap['lead_company']): ?><div class="td-secondary"><?= h($ap['lead_company']) ?></div><?php endif; ?>
                </td>
                <td class="td-secondary"><?= ucfirst(str_replace('_', ' ', $ap['type'])) ?></td>
                <td>
                    <div class="td-primary"><?= date('d M', strtotime($ap['scheduled_at'])) ?></div>
                    <div class="td-secondary"><?= date('H:i', strtotime($ap['scheduled_at'])) ?></div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($upcomingAppointments)): ?><tr><td colspan="3" class="empty-row">No upcoming appointments.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif (has_role('support')): ?>
<!-- Support: Tickets -->
<div class="two-col">
    <div class="card" style="flex:2">
        <div class="card-header">
            <span>Recent Tickets</span>
            <a href="<?= BASE ?>/tickets.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Age</th></tr></thead>
            <tbody>
            <?php foreach ($recentTickets as $t): ?>
            <tr>
                <td><?= h($t['cname']) ?></td>
                <td><a href="<?= BASE ?>/ticket-view.php?id=<?= $t['id'] ?>" class="link"><?= h($t['subject']) ?></a></td>
                <td><?= status_badge($t['priority']) ?></td>
                <td><?= status_badge($t['status']) ?></td>
                <td class="td-secondary"><?= time_ago($t['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentTickets)): ?><tr><td colspan="5" class="empty-row">No tickets yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif (has_role('dev')): ?>
<!-- Dev: Errors + Dev Tickets -->
<div class="two-col">
    <div class="card">
        <div class="card-header">
            <span>Unresolved Errors</span>
            <a href="<?= BASE ?>/errors.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Error</th><th>Instance</th><th>Level</th><th>Count</th><th>Last Seen</th></tr></thead>
            <tbody>
            <?php foreach ($recentErrors as $e): ?>
            <tr>
                <td>
                    <a href="<?= BASE ?>/error-view.php?id=<?= $e['id'] ?>" class="link"><?= h(mb_substr($e['exception_class'] ?: $e['message'], 0, 50)) ?></a>
                    <div class="td-secondary"><?= h(mb_substr($e['file'] ?? '', -40)) ?>:<?= $e['line'] ?></div>
                </td>
                <td class="td-secondary"><?= h($e['domain'] ?? '—') ?></td>
                <td><?= level_badge($e['level']) ?></td>
                <td class="td-secondary"><?= $e['occurrence_count'] ?>×</td>
                <td class="td-secondary"><?= time_ago($e['last_seen_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentErrors)): ?><tr><td colspan="5" class="empty-row">No unresolved errors.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">
            <span>Open Dev Tickets</span>
            <a href="<?= BASE ?>/dev-tickets.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Title</th><th>Priority</th><th>Status</th><th>Assigned</th></tr></thead>
            <tbody>
            <?php foreach ($openDevTickets as $dt): ?>
            <tr>
                <td><a href="<?= BASE ?>/dev-ticket-view.php?id=<?= $dt['id'] ?>" class="link"><?= h(mb_substr($dt['title'], 0, 50)) ?></a></td>
                <td><?= priority_badge($dt['priority']) ?></td>
                <td><?= status_badge($dt['status']) ?></td>
                <td class="td-secondary"><?= h($dt['assigned_name'] ?? 'Unassigned') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($openDevTickets)): ?><tr><td colspan="4" class="empty-row">No open dev tickets.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
