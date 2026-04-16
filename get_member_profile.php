<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

/* 🔐 AUTH */
if (!checkAuth($conn)) {
    echo json_encode(["status" => "logout"]);
    exit;
}

$id = intval($_GET['id'] ?? 0);

$sql = "SELECT id, name, phone, created_at, active_until, photo FROM members WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {

    // 🔥 BUILD FULL IMAGE URL
    $baseUrl = "http://10.0.2.2/gym/gym_api/uploads/";

    $row['photo'] = !empty($row['photo'])
        ? $baseUrl . $row['photo']
        : "";

    echo json_encode([
        "status" => "success",
        "member" => $row
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Member not found"
    ]);
}
