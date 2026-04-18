<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ✅ CORS
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
   🔐 TOKEN AUTH CHECK
================================ */
$authHeader = '';

if (!checkAuth($conn)) {
    echo json_encode(["status"=>"logout"]);
    exit;
}



$query = mysqli_query($conn, "
    SELECT 
        id,
        name,
        phone,
        photo,
        created_at,
        active_until
    FROM members
    ORDER BY id DESC
");

$members = [];

while ($row = mysqli_fetch_assoc($query)) {

    $photo = trim($row['photo']); 

    // 🔥 BUILD FULL IMAGE URL - Use actual domain
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $host . "/gym/gym_api/uploads/";
    
    $photo_url = $photo ? $baseUrl . $photo : "";

    // ✅ STATUS LOGIC (IMPORTANT)
    $todayStart = strtotime(date('Y-m-d'));
    if (empty($row['active_until']) || strtotime($row['active_until']) < $todayStart) {
        $status = "expired";
        $action = "pay_now";
    } else {
        $status = "active";
        $action = "active";
    }

    // Optional: remaining days
    $remainingDays = 0;
    if ($status === "active") {
        $remainingDays = ceil(
            (strtotime($row['active_until']) - time()) / (60 * 60 * 24)
        );
    }

    $members[] = [
        "id"            => $row['id'],
        "name"          => $row['name'],
        "phone"         => $row['phone'],
        "photo"         => $photo_url,
        "created_at"    => $row['created_at'],
        "active_until"  => $row['active_until'],
        "status"        => $status,
        "remainingDays" => $remainingDays,
        "action"        => $action
    ];
}

echo json_encode([
    "status" => "success",
    "members" => $members
]);
