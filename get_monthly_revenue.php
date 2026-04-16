<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Content-Type: application/json");

include "db.php";
include "auth.php";

function hasMonthYearColumn($conn) {
    $colQ = mysqli_query($conn, "SHOW COLUMNS FROM revenue_summary LIKE 'month_year'");
    return $colQ && mysqli_num_rows($colQ) > 0;
}

if (!checkAuth($conn)) {
    echo json_encode(["status"=>"logout"]);
    exit;
}

$monthYear = date('Y-m');

// 🔹 THIS MONTH PAYMENTS
$payQ = mysqli_query($conn,"
  SELECT IFNULL(SUM(amount),0) AS total
  FROM payments
  WHERE DATE_FORMAT(created_at,'%Y-%m')='$monthYear'
");
$payRow = mysqli_fetch_assoc($payQ);
$thisMonthIncome = floatval($payRow['total']);

$useMonthYear = hasMonthYearColumn($conn);
$prevMonthFilter = $useMonthYear
    ? "month_year < '$monthYear'"
    : "DATE_FORMAT(created_at,'%Y-%m') < '$monthYear'";
$prevOrderBy = $useMonthYear ? 'month_year' : 'created_at';

// 🔹 PREVIOUS BALANCE
$prevQ = mysqli_query($conn,"
  SELECT balance
  FROM revenue_summary
  WHERE $prevMonthFilter
  ORDER BY $prevOrderBy DESC
  LIMIT 1
");
$prevRow = mysqli_fetch_assoc($prevQ);
$previousBalance = floatval($prevRow['balance'] ?? 0);

// 🔹 TOTAL AVAILABLE
$totalIncome = $previousBalance + $thisMonthIncome;

// 🔹 CLOSING BALANCE
$thisMonthFilter = $useMonthYear
    ? "month_year='$monthYear'"
    : "DATE_FORMAT(created_at,'%Y-%m')='$monthYear'";
$revQ = mysqli_query($conn,"
  SELECT balance
  FROM revenue_summary
  WHERE $thisMonthFilter
  LIMIT 1
");
$revRow = mysqli_fetch_assoc($revQ);
$closingBalance = floatval($revRow['balance'] ?? $totalIncome);

echo json_encode([
  "status"=>"success",
  "total_income"=>$totalIncome,
  "closing_balance"=>$closingBalance
]);
