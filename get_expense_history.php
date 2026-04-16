<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "db.php";
include "auth.php";

function hasMonthYearColumn($conn) {
    $colQ = mysqli_query($conn, "SHOW COLUMNS FROM revenue_summary LIKE 'month_year'");
    return $colQ && mysqli_num_rows($colQ) > 0;
}

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

$useMonthYear = hasMonthYearColumn($conn);
$monthYearSelect = $useMonthYear ? 'month_year' : "DATE_FORMAT(created_at,'%Y-%m') AS month_year";

$q = mysqli_query($conn,
  "SELECT 
    id,
    trainer_fee,
    electricity_fee,
    maintenance_fee,
    equipment_fee,
    other_fee,
    total_expenses,
    total_income,
    balance,
    $monthYearSelect,
    created_at
   FROM revenue_summary
   ORDER BY id DESC"
);

$data = [];

while ($row = mysqli_fetch_assoc($q)) {
  $data[] = $row;
}

echo json_encode([
  "status" => "success",
  "data" => $data
]);
