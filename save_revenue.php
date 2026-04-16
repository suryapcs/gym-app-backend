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

include "db.php";
include "auth.php";

function hasMonthYearColumn($conn) {
    $colQ = mysqli_query($conn, "SHOW COLUMNS FROM revenue_summary LIKE 'month_year'");
    return $colQ && mysqli_num_rows($colQ) > 0;
}

function ensureMonthYearColumnExists($conn) {
    if (!hasMonthYearColumn($conn)) {
        mysqli_query($conn, "ALTER TABLE revenue_summary ADD COLUMN month_year VARCHAR(20) NULL AFTER id");
        mysqli_query($conn, "UPDATE revenue_summary SET month_year = DATE_FORMAT(created_at, '%Y-%m') WHERE month_year IS NULL");
    }
}

// 🔥 FORCE MYSQL TO THROW ERRORS
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    if (!checkAuth($conn)) {
        echo json_encode(["status" => "logout"]);
        exit;
    }

    $monthYear = date('Y-m');
    $useMonthYear = hasMonthYearColumn($conn);
    $thisMonthFilter = $useMonthYear
        ? "month_year='$monthYear'"
        : "DATE_FORMAT(created_at,'%Y-%m')='$monthYear'";
    $prevMonthFilter = $useMonthYear
        ? "month_year < '$monthYear'"
        : "DATE_FORMAT(created_at,'%Y-%m') < '$monthYear'";
    $prevOrderBy = $useMonthYear ? 'month_year' : 'created_at';

    // 🔹 INPUTS
    $trainer     = floatval($_POST['trainer_fee'] ?? 0);
    $electricity = floatval($_POST['electricity_fee'] ?? 0);
    $maintenance = floatval($_POST['maintenance_fee'] ?? 0);
    $equipment   = floatval($_POST['equipment_fee'] ?? 0);
    $other       = floatval($_POST['other_fee'] ?? 0);

    $totalExpenses = $trainer + $electricity + $maintenance + $equipment + $other;

    // 🔹 PREVIOUS BALANCE
    $prevQ = mysqli_query($conn, "SELECT balance FROM revenue_summary WHERE $prevMonthFilter ORDER BY $prevOrderBy DESC LIMIT 1");
    $prevRow = mysqli_fetch_assoc($prevQ);
    $previousBalance = floatval($prevRow['balance'] ?? 0);

    // 🔹 THIS MONTH INCOME
    $payQ = mysqli_query($conn,"
        SELECT IFNULL(SUM(amount),0) AS total
        FROM payments
        WHERE DATE_FORMAT(created_at,'%Y-%m') = '$monthYear'
    ");
    $payRow = mysqli_fetch_assoc($payQ);
    $thisMonthIncome = floatval($payRow['total'] ?? 0);

    $totalIncome = $previousBalance + $thisMonthIncome;
    $closingBalance = $totalIncome - $totalExpenses;

    // 🔹 CHECK MONTH EXISTS
    $checkQ = mysqli_query(
        $conn,
        "SELECT id FROM revenue_summary WHERE $thisMonthFilter LIMIT 1"
    );

    if (mysqli_num_rows($checkQ) > 0) {

        // 🔁 UPDATE
        $stmt = mysqli_prepare($conn,"
            UPDATE revenue_summary
            SET trainer_fee=?, electricity_fee=?, maintenance_fee=?, equipment_fee=?, other_fee=?,
                total_expenses=?, total_income=?, previous_balance=?, balance=?
            WHERE $thisMonthFilter
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "ddddddddd",
            $trainer,
            $electricity,
            $maintenance,
            $equipment,
            $other,
            $totalExpenses,
            $totalIncome,
            $previousBalance,
            $closingBalance
        );

    } else {

        if ($useMonthYear) {
            // ➕ INSERT with month_year
            $stmt = mysqli_prepare($conn,"
                INSERT INTO revenue_summary
                (month_year, trainer_fee, electricity_fee, maintenance_fee, equipment_fee, other_fee,
                 total_expenses, total_income, previous_balance, balance)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "sddddddddd",
                $monthYear,
                $trainer,
                $electricity,
                $maintenance,
                $equipment,
                $other,
                $totalExpenses,
                $totalIncome,
                $previousBalance,
                $closingBalance
            );
        } else {
            // ➕ INSERT without month_year
            $stmt = mysqli_prepare($conn,"
                INSERT INTO revenue_summary
                (trainer_fee, electricity_fee, maintenance_fee, equipment_fee, other_fee,
                 total_expenses, total_income, previous_balance, balance)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ddddddddd",
                $trainer,
                $electricity,
                $maintenance,
                $equipment,
                $other,
                $totalExpenses,
                $totalIncome,
                $previousBalance,
                $closingBalance
            );
        }
    }

    mysqli_stmt_execute($stmt);

    echo json_encode([
        "status" => "success",
        "month" => $monthYear,
        "previous_balance" => $previousBalance,
        "this_month_income" => $thisMonthIncome,
        "total_income" => $totalIncome,
        "total_expenses" => $totalExpenses,
        "closing_balance" => $closingBalance
    ]);

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server processing error",
        "debug" => $e->getMessage()
    ]);
}
