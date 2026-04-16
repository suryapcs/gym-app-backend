<?php
function checkAuth($conn) {

    $authHeader = '';

    // 1️⃣ Authorization header read
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
    }

    if ($authHeader === '') {
        return false;
    }

    // 2️⃣ 🔥 Bearer support (IMPORTANT)
    if (stripos($authHeader, 'Bearer ') === 0) {
        $token = trim(substr($authHeader, 7)); // remove "Bearer "
    } else {
        $token = trim($authHeader); // plain token
    }

    if ($token === '') {
        return false;
    }

    // 3️⃣ DB verify
    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE api_token = ? LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res->num_rows > 0;
}
