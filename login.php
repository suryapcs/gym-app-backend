<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";

$input = json_decode(file_get_contents("php://input"), true);

$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if ($email === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password required"
    ]);
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, password FROM users WHERE email = ? LIMIT 1"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid login"]);
    exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    echo json_encode(["status" => "error", "message" => "Invalid login"]);
    exit();
}

// 🔐 TOKEN GENERATE (simple & safe)
$token = bin2hex(random_bytes(32));

// save token
$update = $conn->prepare("UPDATE users SET api_token = ? WHERE id = ?");
$update->bind_param("si", $token, $user['id']);
$update->execute();

echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "token" => $token,
]);
