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
    echo json_encode([
        "status" => "logout",
        "message" => "Session expired"
    ]);
    exit;
}

/* ✅ INPUT */
$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$amount  = floatval($_POST['amount'] ?? 1000);

if ($name === "" || $phone === "" || $address === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Name, Phone & Address required"
    ]);
    exit();
}

/* 📷 PHOTO UPLOAD */
$photoFileName = "";

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

    $folder = "uploads/";
    if (!file_exists($folder)) {
        mkdir($folder, 0755, true);
    }

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoFileName = time() . "_" . uniqid() . "." . $ext;

    move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        $folder . $photoFileName
    );
}

/* 👤 INSERT MEMBER */
$stmt = $conn->prepare(
    "INSERT INTO members (name, phone, address, photo) VALUES (?,?,?,?)"
);
$stmt->bind_param("ssss", $name, $phone, $address, $photoFileName);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
    exit();
}

$member_id = $stmt->insert_id;

/* 🟢 ACTIVATE MEMBERSHIP */
$active_until = date('Y-m-d H:i:s', strtotime('+30 days'));
$update = $conn->prepare(
    "UPDATE members SET active_until=?, status='active' WHERE id=?"
);
$update->bind_param("si", $active_until, $member_id);
$update->execute();

/* 💰 INITIAL PAYMENT */
$stmtPay = $conn->prepare(
    "INSERT INTO payments (member_id, amount) VALUES (?,?)"
);
$stmtPay->bind_param("id", $member_id, $amount);
$stmtPay->execute();

// Revenue is managed dynamically via get_monthly_revenue.php and save_revenue.php 

/* ===============================
   ✅ RESPONSE
================================ */
echo json_encode([
    "status"=>"success",
    "message"=>"Member registered successfully",
    "member_id"=>$member_id,
    "initial_payment"=>$amount,
    "total_income"=>$totalIncome,
    "balance"=>$newBalance
]);
