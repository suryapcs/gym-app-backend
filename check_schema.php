<?php
include "db.php";
$res = mysqli_query($conn, "SHOW COLUMNS FROM revenue_summary");
$cols = [];
while ($row = mysqli_fetch_assoc($res)) {
    $cols[] = $row['Field'];
}
echo implode(", ", $cols);
