<?php
/**
 * The one place a tuition payment gets posted to the ledger. Both the
 * counter path (api/Treasury/record_payment.php, staff records cash) and
 * the online path (api/Treasury/paymongo_return.php, GCash via PayMongo)
 * call this so the balance maths, status transitions, OR numbering and
 * unpaid-flag resolution can't drift between the two.
 *
 * Does NOT emit output or touch $_SESSION — pure DB work, returns a result
 * array. Runs its own transaction and re-validates the amount under a row
 * lock, so callers only need to have authenticated the actor first.
 */

require_once __DIR__ . '/../db.php';

const TREASURY_MIN_DOWNPAYMENT = 3000.00;

/**
 * @param mysqli   $conn
 * @param int      $payment_id
 * @param float    $amount        pesos, > 0
 * @param int|null $received_by   users.user_id for a counter payment; null for online
 * @param string   $remarks       shown in payment history (e.g. 'Cash', 'PayMongo (GCash)')
 * @return array {
 *   success: bool,
 *   error?: string,            // set when success is false
 *   payment_status?: string,
 *   applicant_status?: string,
 *   balance?: float,           // remaining balance after this payment
 *   or_number?: string,
 *   transaction_id?: int
 * }
 */
function record_treasury_payment(
    mysqli $conn,
    int $payment_id,
    float $amount,
    ?int $received_by,
    string $remarks = ''
): array {
    $amount = round($amount, 2);

    if ($payment_id <= 0) {
        return ['success' => false, 'error' => 'Invalid payment record.'];
    }
    if ($amount <= 0) {
        return ['success' => false, 'error' => 'Enter a valid amount greater than 0.'];
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT payment_id, enrollment_id, amount_due, downpayment, balance
            FROM payment
            WHERE payment_id = ?
            FOR UPDATE
        ");
        if (!$stmt) {
            throw new DbError($conn->error);
        }
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            throw new Exception("Payment record not found.");
        }

        $currentBalance = (float)$payment['balance'];
        $isFirstPayment = ((float)$payment['downpayment'] <= 0);

        if ($currentBalance <= 0) {
            throw new Exception("This payment is already fully paid.");
        }
        if ($amount > $currentBalance) {
            throw new Exception(
                "Amount exceeds the remaining balance of \u{20B1}" . number_format($currentBalance, 2)
            );
        }
        if ($isFirstPayment && $amount < TREASURY_MIN_DOWNPAYMENT && $amount < $currentBalance) {
            throw new Exception(
                "The minimum down payment is \u{20B1}" . number_format(TREASURY_MIN_DOWNPAYMENT, 2)
            );
        }

        // --- payment_transactions row (received_by is NULL for online) ---
        if ($received_by === null) {
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions (payment_id, amount, paid_at, received_by, remarks)
                VALUES (?, ?, NOW(), NULL, ?)
            ");
            if (!$stmt) {
                throw new DbError($conn->error);
            }
            $stmt->bind_param("ids", $payment_id, $amount, $remarks);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions (payment_id, amount, paid_at, received_by, remarks)
                VALUES (?, ?, NOW(), ?, ?)
            ");
            if (!$stmt) {
                throw new DbError($conn->error);
            }
            $stmt->bind_param("idis", $payment_id, $amount, $received_by, $remarks);
        }
        $stmt->execute();
        $transactionId = $conn->insert_id;
        $stmt->close();

        // OR/receipt number is the transaction's own auto-increment id,
        // zero-padded — no separate sequence.
        $orNumber = sprintf('OR-%06d', $transactionId);

        $newDownpayment = (float)$payment['downpayment'] + $amount;
        $newBalance     = (float)$payment['amount_due'] - $newDownpayment;

        $paymentStatus   = ($newBalance <= 0) ? "Fully Paid" : "Down Payment Paid";
        $applicantStatus = ($newBalance <= 0) ? "Fully Paid" : "Downpayment Paid";

        // --- payment table ---
        if ($received_by === null) {
            $stmt = $conn->prepare("
                UPDATE payment
                SET downpayment = ?, payment_status = ?, paid_at = NOW()
                WHERE payment_id = ?
            ");
            if (!$stmt) {
                throw new DbError($conn->error);
            }
            $stmt->bind_param("dsi", $newDownpayment, $paymentStatus, $payment_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE payment
                SET downpayment = ?, payment_status = ?, paid_at = NOW(), received_by = ?
                WHERE payment_id = ?
            ");
            if (!$stmt) {
                throw new DbError($conn->error);
            }
            $stmt->bind_param("dsii", $newDownpayment, $paymentStatus, $received_by, $payment_id);
        }
        $stmt->execute();
        $stmt->close();

        // --- applicant status ---
        $stmt = $conn->prepare("
            UPDATE applicants a
            INNER JOIN enrollment e ON e.student_id = a.applicant_id
            INNER JOIN payment p    ON e.enrollment_id = p.enrollment_id
            SET a.status = ?
            WHERE p.payment_id = ?
        ");
        if (!$stmt) {
            throw new DbError($conn->error);
        }
        $stmt->bind_param("si", $applicantStatus, $payment_id);
        $stmt->execute();
        $stmt->close();

        // --- enrollment status ---
        $stmt = $conn->prepare("
            UPDATE enrollment e
            INNER JOIN payment p ON p.enrollment_id = e.enrollment_id
            SET e.status = 'Enrolled'
            WHERE p.payment_id = ?
        ");
        if (!$stmt) {
            throw new DbError($conn->error);
        }
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $stmt->close();

        // --- resolve a prior unpaid flag, if any ---
        $stmt = $conn->prepare("
            UPDATE unpaid_students SET status = 'Resolved'
            WHERE payment_id = ? AND status = 'Pending'
        ");
        if (!$stmt) {
            throw new DbError($conn->error);
        }
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return [
            'success'          => true,
            'payment_status'   => $paymentStatus,
            'applicant_status' => $applicantStatus,
            'balance'          => $newBalance,
            'or_number'        => $orNumber,
            'transaction_id'   => $transactionId,
        ];

    } catch (DbError $e) {
        $conn->rollback();
        error_log('record_treasury_payment DB error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'A database error occurred. Please try again.'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
