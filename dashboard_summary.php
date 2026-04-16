<?php
// ===============================
// 🔧 DEBUG (temporary – remove after fix)
// ===============================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===============================
// ✅ CORS (LIVE SAFE)
// ===============================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db.php";
include "auth.php";

/* ===============================
   🔐 TOKEN AUTH CHECK (LIVE SAFE)
================================ */
if (!checkAuth($conn)) {
    echo json_encode([
        "status" => "logout",
        "message" => "Session expired"
    ]);
    exit;
}

/* ===============================
   📊 DASHBOARD DATA
================================ */

// TOTAL MEMBERS
$membersQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM members"
);
$membersRow = mysqli_fetch_assoc($membersQuery);
$members = (int)($membersRow['total'] ?? 0);

// ACTIVE MEMBERS (SAFE)
$activeQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM members
     WHERE active_until IS NOT NULL
     AND active_until >= CURDATE()"
);

if (!$activeQuery) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => mysqli_error($conn)
    ]);
    exit;
}

$activeRow = mysqli_fetch_assoc($activeQuery);
$active = (int)($activeRow['total'] ?? 0);

// TOTAL REVENUE
$revenueQuery = mysqli_query(
    $conn,
    "SELECT SUM(amount) AS total FROM payments"
);
$revenueRow = mysqli_fetch_assoc($revenueQuery);
$revenue = (float)($revenueRow['total'] ?? 0);

/* ===============================
   ✅ RESPONSE
================================ */

echo json_encode([
    "status" => "success",
    "data" => [
        "total_members"  => $members,
        "active_members" => $active,
        "total_revenue"  => $revenue
    ]
]);
