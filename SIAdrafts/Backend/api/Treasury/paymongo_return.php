<?php
/**
 * Where PayMongo sends the browser back after a checkout. No webhook — this
 * page is the source of truth on localhost: it asks PayMongo once for the
 * real status of the session, and if paid, posts the payment to the ledger
 * through the same core the counter uses.
 *
 * Safe to hit repeatedly (refresh / back button): the tracking row's status
 * gates the ledger write, so a payment is recorded at most once.
 */

session_start();
require_once '../../db.php';
require_once '../../roles.php';
require_once '../../require_role.php';
require_once '../../paymongo.php';
require_once '../../Treasury/record_payment_core.php';

// Only Treasury/Admin should land here (it's reached from the Treasury
// screen). Not an API response — render a real page.
if (empty($_SESSION['user_id']) || !current_user_is([ROLE_TREASURY, ROLE_ADMIN])) {
    header('Location: /SIAdrafts/Frontend/View/login.php');
    exit;
}

$db   = new Database();
$conn = $db->connect();

$ref = $_GET['ref'] ?? '';
$state = 'error';           // error | success | failed | pending
$message = 'Something went wrong reading this payment.';
$orNumber = null;
$paymentId = null;

if (!preg_match('/^[a-f0-9]{32}$/', $ref)) {
    $message = 'Invalid payment reference.';
} else {
    $stmt = $conn->prepare("SELECT id, checkout_session_id, payment_id, amount, status, transaction_id FROM paymongo_checkout WHERE local_ref = ?");
    $stmt->bind_param('s', $ref);
    $stmt->execute();
    $co = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$co) {
        $message = 'This payment reference was not found.';
    } elseif ($co['status'] === 'recorded') {
        // Already done on an earlier visit — just show the receipt again.
        $state = 'success';
        $paymentId = (int)$co['payment_id'];
        $orNumber = $co['transaction_id'] ? sprintf('OR-%06d', (int)$co['transaction_id']) : null;
        $message = 'This payment was already recorded.';
    } elseif ($co['status'] === 'failed') {
        $state = 'failed';
        $message = 'This payment did not go through.';
    } else {
        // pending or paid-but-not-recorded: ask PayMongo what actually happened.
        try {
            $session = paymongo_api('GET', '/checkout_sessions/' . rawurlencode($co['checkout_session_id']));
            $verdict = paymongo_checkout_verdict($session);
        } catch (PayMongoError $e) {
            error_log('paymongo_return retrieve: ' . $e->getMessage());
            $verdict = 'pending';
        }

        if ($verdict === 'failed') {
            $conn->query("UPDATE paymongo_checkout SET status = 'failed' WHERE id = " . (int)$co['id']);
            $state = 'failed';
            $message = 'The payment was cancelled or expired. Nothing was charged.';
        } elseif ($verdict === 'paid') {
            $result = record_treasury_payment(
                $conn,
                (int)$co['payment_id'],
                (float)$co['amount'],
                null,                          // no counter staff — online
                'PayMongo (GCash / online)'
            );

            if ($result['success']) {
                $upd = $conn->prepare("UPDATE paymongo_checkout SET status = 'recorded', transaction_id = ? WHERE id = ?");
                $upd->bind_param('ii', $result['transaction_id'], $co['id']);
                $upd->execute();
                $upd->close();

                $state = 'success';
                $paymentId = (int)$co['payment_id'];
                $orNumber = $result['or_number'];
                $message = 'Payment received and recorded.';
            } else {
                // PayMongo took the money but the ledger rejected it (balance
                // changed, etc.). Flag for manual reconciliation — in test
                // mode there's nothing to refund.
                $conn->query("UPDATE paymongo_checkout SET status = 'paid' WHERE id = " . (int)$co['id']);
                error_log('paymongo_return: paid but not recorded, ref ' . $ref . ' — ' . $result['error']);
                $state = 'error';
                $message = 'PayMongo confirmed the payment, but it could not be posted (' . $result['error'] . '). Treasury needs to reconcile this manually.';
            }
        } else {
            $state = 'pending';
            $message = 'This payment is still processing. Refresh this page in a moment, or check the queue shortly.';
        }
    }
}

$conn->close();

$accent = ['success' => '#046A38', 'failed' => '#A6472E', 'pending' => '#8a6d1f', 'error' => '#A6472E'][$state];
$title  = ['success' => 'Payment recorded', 'failed' => 'Payment not completed', 'pending' => 'Still processing', 'error' => 'Needs attention'][$state];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> — Treasury</title>
<style>
  body{ margin:0; font-family:'Inter',system-ui,-apple-system,sans-serif; background:#F8F9F7;
        min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
  .pr-card{ background:#fff; border-radius:20px; padding:40px 36px; max-width:420px; width:100%;
            box-shadow:0 1px 1px rgba(31,46,40,.04), 0 24px 48px -22px rgba(31,46,40,.24); text-align:center; }
  .pr-badge{ width:56px; height:56px; border-radius:50%; margin:0 auto 20px; display:flex;
             align-items:center; justify-content:center; font-size:28px; color:#fff;
             background:<?= $accent ?>; }
  .pr-card h1{ font-size:1.15rem; margin:0 0 8px; color:#1F2E28; }
  .pr-card p{ font-size:.92rem; color:rgba(31,46,40,.75); line-height:1.6; margin:0 0 22px; }
  .pr-or{ display:block; margin:14px 0 22px; font-family:ui-monospace,Menlo,monospace;
          font-weight:700; font-size:1rem; color:#1F2E28; letter-spacing:.03em; }
  .pr-btn{ display:inline-block; background:#1F2E28; color:#fff; text-decoration:none;
           font-weight:700; font-size:.88rem; padding:11px 22px; border-radius:.7rem; }
</style>
</head>
<body>
  <div class="pr-card">
    <div class="pr-badge"><?= $state === 'success' ? '&#10003;' : ($state === 'pending' ? '&#8635;' : '!') ?></div>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <?php if ($orNumber): ?><span class="pr-or"><?= htmlspecialchars($orNumber) ?></span><?php endif; ?>
    <a class="pr-btn" href="/SIAdrafts/Frontend/View/Admission/treasury.php">Back to Treasury</a>
  </div>
</body>
</html>
