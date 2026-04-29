<?php

require_once __DIR__ . '/db.php';

// ======================
// CONFIG API
// ======================
$token = "2C46FA731D55460FBDFE4FF8F0F98DD5";
$url = "https://vtsapi.easygo-gps.co.id/api/driver/masterdata";

// Body kosong untuk ambil semua
$postData = json_encode([
    "code" => "",
    "name" => ""
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Token: $token"
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if ($data['ResponseCode'] != 1) {
    die("API Error: " . $data['ResponseMessage']);
}

$drivers = $data['Data'];

foreach ($drivers as $driver) {

    $autoid = $driver['autoid'];
    $code = $conn->real_escape_string($driver['code']);
    $name = $conn->real_escape_string($driver['name']);
    $addr = $conn->real_escape_string($driver['addr']);
    $rfid = $conn->real_escape_string($driver['rfid']);
    $gol_darah = $conn->real_escape_string($driver['gol_darah']);
    $phone_no = $conn->real_escape_string($driver['phone_no']);
    $sim_no = $conn->real_escape_string($driver['sim_no']);
    $sim_class = $conn->real_escape_string($driver['sim_class']);
    $telegram_usernm = $conn->real_escape_string($driver['telegram_usernm']);
    $deposit = $driver['deposit'];
    $join_date = date("Y-m-d H:i:s", strtotime($driver['join_date']));

    // INSERT atau UPDATE jika sudah ada
    $sql = "
        INSERT INTO drivers 
        (autoid, code, name, addr, rfid, gol_darah, phone_no, sim_no, sim_class, telegram_usernm, deposit, join_date)
        VALUES
        ('$autoid', '$code', '$name', '$addr', '$rfid', '$gol_darah', '$phone_no', '$sim_no', '$sim_class', '$telegram_usernm', '$deposit', '$join_date')
        ON DUPLICATE KEY UPDATE
            name='$name',
            addr='$addr',
            rfid='$rfid',
            gol_darah='$gol_darah',
            phone_no='$phone_no',
            sim_no='$sim_no',
            sim_class='$sim_class',
            telegram_usernm='$telegram_usernm',
            deposit='$deposit',
            join_date='$join_date'
    ";

    $conn->query($sql);
}

echo "Sinkronisasi selesai. Total driver: " . count($drivers);

$conn->close();
?>