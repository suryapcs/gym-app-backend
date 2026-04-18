<?php
header("Content-Type: application/json");
include "db.php";

$response = [];

// Check members table structure
$result = mysqli_query($conn, "DESCRIBE members");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

$response['members_columns'] = $columns;
$response['has_photo'] = in_array('photo', $columns);

// Auto-fix: Add photo column if missing
if (!$response['has_photo']) {
    if (mysqli_query($conn, "ALTER TABLE members ADD COLUMN photo VARCHAR(255) DEFAULT NULL")) {
        $response['auto_fix'] = "✅ Photo column added!";
        $response['has_photo'] = true;
    } else {
        $response['auto_fix'] = "❌ Failed to add photo column";
    }
}

// Check uploads directory
$uploadsDir = __DIR__ . '/uploads/';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0777, true);
}

$response['uploads_dir_exists'] = is_dir($uploadsDir);
$response['uploads_dir_writable'] = is_writable($uploadsDir);

// Stats
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM members");
$total = mysqli_fetch_assoc($totalQuery)['count'];

$withPhotoQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM members WHERE photo IS NOT NULL AND photo != ''");
$withPhoto = mysqli_fetch_assoc($withPhotoQuery)['count'];

$response['total_members'] = $total;
$response['members_with_photos'] = $withPhoto;
$response['members_without_photos'] = $total - $withPhoto;

// Sample members
$sampleQuery = mysqli_query($conn, "SELECT id, name, phone, photo FROM members LIMIT 5");
$response['sample_members'] = [];
while ($row = mysqli_fetch_assoc($sampleQuery)) {
    $response['sample_members'][] = $row;
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
