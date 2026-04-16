<?php
// ==============================
// 🔐 CORS (LIVE SAFE)
// ==============================
header("Access-Control-Allow-Origin: *"); // 🔒 live la domain specify panna better
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Preflight request handle
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "db.php";
include "auth.php";
/* ===============================
   🔐 TOKEN AUTH CHECK
================================ */
$authHeader = '';

if (!checkAuth($conn)) {
    echo json_encode([
        "status" => "logout",
        "message" => "Session expired"
    ]);
    exit;
}

// ==============================
// 📥 INPUT VALIDATION
// ==============================
$member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
$amount    = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$note      = isset($_POST['note']) ? trim($_POST['note']) : '';

if ($member_id <= 0 || $amount <= 0) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "member_id and valid amount required"
    ]);
    exit();
}

// ==============================
// 🔐 TRANSACTION START
// ==============================
mysqli_begin_transaction($conn);

try {

    $date = date("Y-m-d");


    // ==============================
    // 2️⃣ PAYMENTS TABLE
    // ==============================
    $pay_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO payments (member_id, amount, note) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($pay_stmt, "ids", $member_id, $amount, $note);
    mysqli_stmt_execute($pay_stmt);

    // ==============================
    // 3️⃣ UPDATE MEMBERSHIP (+30 DAYS)
    // ==============================
    $qDate = mysqli_query($conn, "SELECT active_until FROM members WHERE id = $member_id");
    $rowDate = mysqli_fetch_assoc($qDate);
    $current_active = $rowDate['active_until'] ?? null;

    if ($current_active && strtotime($current_active) > time()) {
        $new_active_until = date('Y-m-d H:i:s', strtotime($current_active . ' +30 days'));
    } else {
        $new_active_until = date('Y-m-d H:i:s', strtotime('+30 days'));
    }

    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE members 
         SET active_until = ?, status = 'active' 
         WHERE id = ?"
    );
    mysqli_stmt_bind_param($update_stmt, "si", $new_active_until, $member_id);
    mysqli_stmt_execute($update_stmt);

    // ==============================
    // ✅ COMMIT
    // ==============================
    mysqli_commit($conn);

    echo json_encode([
        "status" => "success",
        "message" => "Payment added successfully",
        "member_id" => $member_id,
        "amount" => $amount,
        "new_active_until" => $new_active_until
    ]);

} catch (Exception $e) {

    // ❌ ROLLBACK IF ERROR
    mysqli_rollback($conn);

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Payment failed. Please try again."
    ]);
}
