<?php
/**
 * Setup script to ensure uploads directory exists and is writable
 * Access: https://pcstech.in/gym/gym_api/setup_uploads.php
 */

// Check if uploads directory exists
$uploadsDir = __DIR__ . '/uploads/';

if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0777, true)) {
        echo json_encode([
            "status" => "success",
            "message" => "✅ Uploads directory created successfully",
            "path" => $uploadsDir,
            "writable" => is_writable($uploadsDir)
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "❌ Failed to create uploads directory",
            "path" => $uploadsDir
        ]);
    }
} else {
    echo json_encode([
        "status" => "success",
        "message" => "✅ Uploads directory already exists",
        "path" => $uploadsDir,
        "writable" => is_writable($uploadsDir),
        "files" => array_diff(scandir($uploadsDir), ['.', '..'])
    ]);
}
?>
