<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db.php";

$member_id = $_GET['member_id'] ?? '';

if (!$member_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Member ID required"
    ]);
    exit;
}

$sql = "
SELECT 
  DATE_FORMAT(created_at, '%M %Y') AS month,
  SUM(amount) AS total_amount,
  MIN(created_at) AS paid_date
FROM payments
WHERE member_id = '$member_id'
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY paid_date DESC
";

$res = mysqli_query($conn, $sql);

$payments = [];
while ($row = mysqli_fetch_assoc($res)) {
    $payments[] = $row;
}

echo json_encode([
    "status" => "success",
    "payments" => $payments
]);
