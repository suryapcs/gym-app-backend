<?php
$_POST['trainer_fee'] = 1200;
$_POST['electricity_fee'] = 350;
$_POST['maintenance_fee'] = 100;
$_POST['equipment_fee'] = 0;
$_POST['other_fee'] = 50;
// We must bypass auth check for the CLI script
// Actually I'll just write a cURL script in php to hit it instead of including
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/gym/gym_api/save_revenue.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Wait, we need auth token. 
?>
