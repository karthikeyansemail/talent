<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'Dashboard';

$db = db();

// KPIs
$stats = [
    'total_customers'   => $db->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
    'active_subs'       => $db->query("SELECT COUNT(*) FROM orders WHERE status='active'")->fetchColumn(),
    'mrr'               => $db->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='active' AND billing_period='monthly' AND currency='USD'")->fetchColumn(),
    'open_tickets'      => $db->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open','in_progress')")->fetchColumn(),
    'new_chats_today'   => $db->query("SELECT COUNT(*) FROM chat_sessions WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'revenue_total_usd' => $db->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='active' AND currency='USD'")->fetchColumn(),
];

// Recent orders (last 8)
$recentOrders = $db->query(
    "SELECT o.*, c.name as cname, c.email as cemail
     FROM orders o JOIN customers c ON c.id=o.customer_id
     ORDER BY o.created_at DESC LIMIT 8"
)->fetchAll();

// Recent tickets (last 8)
$recentTickets = $db->query(
    "SELECT t.*, c.name as cname
     FROM tickets t JOIN customers c ON c.id=t.customer_id
     ORDER BY t.created_at DESC LIMIT 8"
)->fetchAll();

// Plan distribution
$planDist = $db->query(
    "SELECT plan, COUNT(*) as cnt FROM orders WHERE status='active' GROUP BY plan"
)->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<!-- KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Subscriptions</div>
        <div class="stat-value"><?= number_format($stats['active_subs']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">MRR (USD)</div>
        <div class="stat-value"><?= format_currency((float)$stats['mrr']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Open Tickets</div>
        <div class="stat-value <?= $stats['open_tickets'] > 5 ? 'stat-warn' : '' ?>"><?= $stats['open_tickets'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">New Chats Today</div>
        <div class="stat-value"><?= $stats['new_chats_today'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Revenue (USD)</div>
        <div class="stat-value"><?= format_currency((float)$stats['revenue_total_usd']) ?></div>
    </div>
</div>

<div class="two-col">
    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <span>Recent Orders</span>
            <a href="<?= BASE ?>/orders.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Customer</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td>
                        <div class="td-primary"><?= h($o['cname']) ?></div>
                        <div class="td-secondary"><?= h($o['cemail']) ?></div>
                    </td>
                    <td><?= plan_badge($o['plan']) ?></td>
                    <td><?= format_currency((float)$o['amount'], $o['currency']) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td class="td-secondary"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
                <tr><td colspan="5" class="empty-row">No orders yet.</td></tr>
            <?php endif; ?>
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
            <thead>
                <tr><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Age</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentTickets as $t): ?>
                <tr>
                    <td><?= h($t['cname']) ?></td>
                    <td>
                        <a href="<?= BASE ?>/ticket-view.php?id=<?= $t['id'] ?>" class="link"><?= h($t['subject']) ?></a>
                    </td>
                    <td><?= status_badge($t['priority']) ?></td>
                    <td><?= status_badge($t['status']) ?></td>
                    <td class="td-secondary"><?= time_ago($t['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentTickets)): ?>
                <tr><td colspan="5" class="empty-row">No tickets yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
