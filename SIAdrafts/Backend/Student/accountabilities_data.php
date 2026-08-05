<?php
/**
 * Query layer for Frontend/View/Student/accountabilities.php.
 */

function get_accountabilities_data(mysqli $conn, int $applicantId): array
{
    $stmt = $conn->prepare(
        "SELECT enrollment_id, school_year, semester
         FROM enrollment
         WHERE student_id = ?
         ORDER BY created_at DESC
         LIMIT 1"
    );
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $payment = null;
    $breakdown = [];
    if ($enrollment) {
        $stmt = $conn->prepare("SELECT payment_id, amount_due, downpayment, balance, due_date, payment_status FROM payment WHERE enrollment_id = ? LIMIT 1");
        $stmt->bind_param('i', $enrollment['enrollment_id']);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($payment) {
            $stmt = $conn->prepare("SELECT label, amount FROM payment_breakdown WHERE payment_id = ? ORDER BY sort_order");
            $stmt->bind_param('i', $payment['payment_id']);
            $stmt->execute();
            $breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }

    $stmt = $conn->prepare("SELECT document_name, status FROM applicant_documents WHERE applicant_id = ?");
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $docRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'enrollment' => $enrollment,
        'payment'    => $payment,
        'breakdown'  => $breakdown,
        'docRows'    => $docRows,
    ];
}
