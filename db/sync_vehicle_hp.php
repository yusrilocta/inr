<?php
require_once __DIR__ . '/db.php';

$token = "2C46FA731D55460FBDFE4FF8F0F98DD5"; // token kamu

$url = "https://vtsapi.easygo-gps.co.id/api/driver/masterdata";

$payload = json_encode([
    "code" => "",
    "name" => ""
]);

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Token: $token"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['ResponseCode'] != 1) {
    die("Gagal ambil data driver: " . $result['ResponseMessage']);
}

$drivers = $result['Data'];

$updated = 0;

foreach ($drivers as $driver) {

    $nama = trim($driver['name']);
    $phone = trim($driver['phone_no']);

    if (!empty($phone)) {

        $stmt = $conn->prepare("
            UPDATE vehicles 
            SET phone_no = ?
            WHERE LOWER(driver_nm) = LOWER(?)
        ");

        $stmt->bind_param("ss", $phone, $nama);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $updated++;
        }
    }
}

echo "Sync selesai. Total update: $updated data";