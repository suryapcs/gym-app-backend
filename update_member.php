<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "db.php";
include "auth.php";

/* 🔐 AUTH */
if (!checkAuth($conn)) {
    echo json_encode(["status" => "logout", "message" => "Session expired"]);
    exit;
}

/* ✅ Detect content type: JSON (base64 image upload) or form-data (text edit) */
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = strpos($contentType, 'application/json') !== false;

if ($isJson) {
    // --- JSON body (image upload via base64) ---
    $input   = json_decode(file_get_contents('php://input'), true);
    $id      = intval($input['id'] ?? 0);
    $name    = trim($input['name'] ?? '');
    $phone   = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $photoBase64 = $input['photo_base64'] ?? '';
    $photoExt    = preg_replace('/[^a-z0-9]/', '', strtolower($input['photo_ext'] ?? 'jpg'));
} else {
    // --- Form data (text-only edit from bottom sheet) ---
    $id      = intval($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $photoBase64 = '';
    $photoExt    = '';
}

if ($id === 0 || $name === '' || $phone === '') {
    echo json_encode(["status" => "error", "message" => "ID, Name and Phone are required"]);
    exit();
}

/* 📷 Save base64 image to uploads/ */
$photoFileName = null; // null = don't update photo column

if ($photoBase64 !== '') {
    $folder = __DIR__ . "/uploads/";
    if (!file_exists($folder)) {
        mkdir($folder, 0755, true);
    }

    // Decode and save
    $imageData = base64_decode($photoBase64, true);
    if ($imageData === false) {
        echo json_encode(["status" => "error", "message" => "Invalid image data"]);
        exit();
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($photoExt, $allowedExts)) {
        $photoExt = 'jpg';
    }

    $photoFileName = time() . '_' . uniqid() . '.' . $photoExt;
    file_put_contents($folder . $photoFileName, $imageData);
}

/* ✏️ UPDATE MEMBER */
if ($photoFileName !== null) {
    $stmt = $conn->prepare("UPDATE members SET name=?, phone=?, address=?, photo=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $phone, $address, $photoFileName, $id);
} else {
    $stmt = $conn->prepare("UPDATE members SET name=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $phone, $address, $id);
}

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Member updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
