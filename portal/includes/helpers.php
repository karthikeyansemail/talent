<?php
/**
 * Shared helper functions
 */

function h(mixed $val): string
{
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function plan_label(string $plan): string
{
    return match ($plan) {
        'cloud_enterprise' => 'Cloud Enterprise',
        'self_hosted'      => 'Self-Hosted Enterprise',
        default            => 'Free',
    };
}

function plan_badge(string $plan): string
{
    $label = plan_label($plan);
    $color = match ($plan) {
        'cloud_enterprise' => '#6366f1',
        'self_hosted'      => '#0ea5e9',
        default            => '#6b7280',
    };
    return "<span style=\"display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:{$color};color:#fff\">{$label}</span>";
}

function status_badge(string $status): string
{
    $colors = [
        'active'      => ['bg' => '#d1fae5', 'c' => '#065f46'],
        'pending'     => ['bg' => '#fef3c7', 'c' => '#92400e'],
        'cancelled'   => ['bg' => '#fee2e2', 'c' => '#991b1b'],
        'expired'     => ['bg' => '#f3f4f6', 'c' => '#374151'],
        'refunded'    => ['bg' => '#ede9fe', 'c' => '#5b21b6'],
        'open'        => ['bg' => '#dbeafe', 'c' => '#1e40af'],
        'in_progress' => ['bg' => '#fef3c7', 'c' => '#92400e'],
        'resolved'    => ['bg' => '#d1fae5', 'c' => '#065f46'],
        'closed'      => ['bg' => '#f3f4f6', 'c' => '#374151'],
        'draft'       => ['bg' => '#f3f4f6', 'c' => '#374151'],
        'paused'      => ['bg' => '#fef3c7', 'c' => '#92400e'],
        'completed'   => ['bg' => '#d1fae5', 'c' => '#065f46'],
        'scheduled'   => ['bg' => '#dbeafe', 'c' => '#1e40af'],
        'no_show'     => ['bg' => '#fee2e2', 'c' => '#991b1b'],
        'investigating' => ['bg' => '#ede9fe', 'c' => '#5b21b6'],
    ];
    $c = $colors[$status] ?? ['bg' => '#f3f4f6', 'c' => '#374151'];
    $label = ucfirst(str_replace('_', ' ', $status));
    return "<span style=\"display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:{$c['bg']};color:{$c['c']}\">{$label}</span>";
}

function role_badge(string $role): string
{
    $colors = [
        'admin'   => ['bg' => '#ede9fe', 'c' => '#5b21b6'],
        'sales'   => ['bg' => '#dbeafe', 'c' => '#1e40af'],
        'support' => ['bg' => '#d1fae5', 'c' => '#065f46'],
        'dev'     => ['bg' => '#fef3c7', 'c' => '#92400e'],
    ];
    $c = $colors[$role] ?? ['bg' => '#f3f4f6', 'c' => '#374151'];
    $label = ucfirst($role);
    return "<span style=\"display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:{$c['bg']};color:{$c['c']}\">{$label}</span>";
}

function level_badge(string $level): string
{
    $colors = [
        'critical' => ['bg' => '#fee2e2', 'c' => '#991b1b'],
        'error'    => ['bg' => '#fed7aa', 'c' => '#9a3412'],
        'warning'  => ['bg' => '#fef3c7', 'c' => '#92400e'],
        'notice'   => ['bg' => '#dbeafe', 'c' => '#1e40af'],
    ];
    $c = $colors[$level] ?? ['bg' => '#f3f4f6', 'c' => '#374151'];
    $label = ucfirst($level);
    return "<span style=\"display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:{$c['bg']};color:{$c['c']}\">{$label}</span>";
}

function lead_status_badge(string $status): string
{
    $colors = [
        'new'         => ['bg' => '#dbeafe', 'c' => '#1e40af'],
        'contacted'   => ['bg' => '#e0e7ff', 'c' => '#3730a3'],
        'qualified'   => ['bg' => '#ede9fe', 'c' => '#5b21b6'],
        'proposal'    => ['bg' => '#fef3c7', 'c' => '#92400e'],
        'negotiation' => ['bg' => '#fed7aa', 'c' => '#9a3412'],
        'won'         => ['bg' => '#d1fae5', 'c' => '#065f46'],
        'lost'        => ['bg' => '#fee2e2', 'c' => '#991b1b'],
    ];
    $c = $colors[$status] ?? ['bg' => '#f3f4f6', 'c' => '#374151'];
    $label = ucfirst($status);
    return "<span style=\"display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:{$c['bg']};color:{$c['c']}\">{$label}</span>";
}

function priority_badge(string $priority): string
{
    $colors = [
        'low'      => ['bg' => '#f3f4f6', 'c' => '#374151'],
        'normal'   => ['bg' => '#dbeafe', 'c' => '#1e40af'],
        'high'     => ['bg' => '#fed7aa', 'c' => '#9a3412'],
        'critical' => ['bg' => '#fee2e2', 'c' => '#991b1b'],
        'urgent'   => ['bg' => '#fee2e2', 'c' => '#991b1b'],
    ];
    $c = $colors[$priority] ?? ['bg' => '#f3f4f6', 'c' => '#374151'];
    $label = ucfirst($priority);
    return "<span style=\"display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:{$c['bg']};color:{$c['c']}\">{$label}</span>";
}

function format_currency(float $amount, string $currency = 'USD'): string
{
    if ($currency === 'INR') return '₹' . number_format($amount, 2);
    return '$' . number_format($amount, 2);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}

function platform_badge(string $platform): string
{
    $colors = [
        'google_ads' => ['bg' => '#fef3c7', 'c' => '#92400e'],
        'meta_ads'   => ['bg' => '#dbeafe', 'c' => '#1e40af'],
        'linkedin'   => ['bg' => '#e0e7ff', 'c' => '#3730a3'],
        'manual'     => ['bg' => '#f3f4f6', 'c' => '#374151'],
        'other'      => ['bg' => '#f3f4f6', 'c' => '#374151'],
    ];
    $c = $colors[$platform] ?? ['bg' => '#f3f4f6', 'c' => '#374151'];
    $label = ucwords(str_replace('_', ' ', $platform));
    return "<span style=\"display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:{$c['bg']};color:{$c['c']}\">{$label}</span>";
}
