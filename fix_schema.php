<?php
include "db.php";

// 1. Add column
mysqli_query($conn, "ALTER TABLE revenue_summary ADD COLUMN month_year VARCHAR(20) NULL AFTER id");

// 2. Backfill existing records
mysqli_query($conn, "UPDATE revenue_summary SET month_year = DATE_FORMAT(created_at, '%Y-%m') WHERE month_year IS NULL");

echo "Schema updated successfully!";
