<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";

/* ===============================
   🔐 TOKEN CHECK
================================ */
$headers = getallheaders();

if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Token missing"]);
    exit();
}

$token = str_replace("Bearer ", "", $headers['Authorization']);

if ($token == "") {
    http_response_code(403);
    echo json_encode(["status"=>"error","message"=>"Invalid token"]);
    exit();
}

/* ===============================
   🔓 CLEAR TOKEN
================================ */
$stmt = $conn->prepare(
    "UPDATE users SET api_token = NULL WHERE api_token = ?"
);
$stmt->bind_param("s", $token);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Invalid or expired token"
    ]);
    exit();
}

echo json_encode([
    "status"=>"success",
    "message"=>"Logged out successfully"
]);
