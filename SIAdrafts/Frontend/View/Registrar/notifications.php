<?php
$pageTitle  = "NOTIFICATIONS";
$activePage = "notifications";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

// A real activity feed, not mock data: recent admissions + recent
// payments, merged and sorted chronologically. There's no notification
// read/unread tracking table in the schema, so "new" is approximated as
// "happened today" (matches the dot-indicator pattern) rather than a
// persisted read state.
$events = [];

$res = $conn->query("
    SELECT CONCAT(first_name, ' ', last_name) AS title, program AS subtitle, created_at AS ts, 'admission' AS kind
    FROM applicants
    ORDER BY created_at DESC
    LIMIT 25
");
if ($res) while ($row = $res->fetch_assoc()) $events[] = $row;

$res = $conn->query("
    SELECT CONCAT(a.first_name, ' ', a.last_name) AS title,
           CONCAT('₱', FORMAT(p.downpayment, 2), ' received') AS subtitle,
           p.paid_at AS ts, 'payment' AS kind
    FROM payment p
    JOIN enrollment e ON e.enrollment_id = p.enrollment_id
    JOIN applicants a ON a.applicant_id = e.student_id
    WHERE p.paid_at IS NOT NULL
    ORDER BY p.paid_at DESC
    LIMIT 25
");
if ($res) while ($row = $res->fetch_assoc()) $events[] = $row;

$db->close();

usort($events, fn($a, $b) => strtotime($b['ts']) <=> strtotime($a['ts']));
$events = array_slice($events, 0, 25);

$grouped = [];
foreach ($events as $e) {
    $dayKey = date('Y-m-d', strtotime($e['ts']));
    $grouped[$dayKey][] = $e;
}

function notif_day_label(string $ymd): string {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($ymd === $today) return 'Today, ' . date('M j', strtotime($ymd));
    if ($ymd === $yesterday) return 'Yesterday, ' . date('M j', strtotime($ymd));
    return date('F j, Y', strtotime($ymd));
}

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">
        <?php include '../Include/readonly_banner.php'; ?>

        <?php if (empty($grouped)): ?>
            <div class="surface-2 rd-empty-state">
                <div class="rd-empty-icon">✓</div>
                <div class="rd-empty-title">All quiet</div>
                <div class="rd-empty-sub">New admissions and payments will show up here as they happen.</div>
            </div>
        <?php else: ?>
            <?php foreach ($grouped as $dayKey => $dayEvents): ?>
                <div class="row-secondary mono" style="margin:0 0 10px; text-transform:uppercase; letter-spacing:.06em; font-size:11px;">
                    <?= htmlspecialchars(notif_day_label($dayKey)) ?>
                </div>
                <div class="surface-2" style="padding:4px; margin-bottom:22px;">
                    <div class="ledger">
                        <?php $isToday = $dayKey === date('Y-m-d'); ?>
                        <?php foreach ($dayEvents as $e): ?>
                            <div class="ledger-row">
                                <div style="width:7px;height:7px;border-radius:50%;background:<?= $isToday ? 'var(--seal-600)' : 'transparent' ?>;flex-shrink:0;"></div>
                                <div style="flex:1;">
                                    <div class="row-primary" style="<?= $isToday ? '' : 'color:var(--slate-500); font-weight:400;' ?>">
                                        <?= $e['kind'] === 'admission' ? 'New application from ' : 'Payment posted — ' ?><?= htmlspecialchars($e['title']) ?>
                                    </div>
                                    <div class="row-secondary"><?= htmlspecialchars($e['subtitle']) ?></div>
                                </div>
                                <div class="mono row-secondary"><?= date('h:i A', strtotime($e['ts'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>

</div>

<?php include '../Include/footer.php'; ?>
