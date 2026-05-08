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

/* ✅ INPUT */
$id      = intval($_POST['id'] ?? 0);
$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($id === 0 || $name === '' || $phone === '') {
    echo json_encode(["status" => "error", "message" => "ID, Name and Phone are required"]);
    exit();
}

/* 📷 PHOTO UPLOAD (optional) */
$photoUpdate = '';
$photoParam  = '';

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
    $folder = "uploads/";
    if (!file_exists($folder)) {
        mkdir($folder, 0755, true);
    }
    $ext           = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoFileName = time() . "_" . uniqid() . "." . $ext;
    move_uploaded_file($_FILES['photo']['tmp_name'], $folder . $photoFileName);
    $photoUpdate = ", photo=?";
    $photoParam  = $photoFileName;
}

/* ✏️ UPDATE MEMBER */
if ($photoParam !== '') {
    $stmt = $conn->prepare("UPDATE members SET name=?, phone=?, address=?$photoUpdate WHERE id=?");
    $stmt->bind_param("ssssi", $name, $phone, $address, $photoParam, $id);
} else {
    $stmt = $conn->prepare("UPDATE members SET name=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $phone, $address, $id);
}

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Member updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
