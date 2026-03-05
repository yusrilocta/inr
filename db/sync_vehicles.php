<?php

require_once __DIR__ . '/db.php';

// ======================
// CONFIG API
// ======================
$token = "2C46FA731D55460FBDFE4FF8F0F98DD5";
$url = "https://vtsapi.easygo-gps.co.id/api/master/vehicles";

// Body kosong = ambil semua
$postData = json_encode([
    "nopol" => ""
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

$vehicles = $data['Data'];

foreach ($vehicles as $row) {

    $vehicle_id = $conn->real_escape_string($row['vehicle_id']);
    $gps_sn = $conn->real_escape_string($row['gps_sn']);
    $nopol = $conn->real_escape_string($row['nopol']);
    $type = $conn->real_escape_string($row['type']);
    $model = $conn->real_escape_string($row['model']);
    $brand = $conn->real_escape_string($row['brand']);
    $car_group = $conn->real_escape_string($row['car_group']);
    $driver_nm = $conn->real_escape_string($row['driver_nm']);
    $remark = $conn->real_escape_string($row['remark']);
    $engine_no = $conn->real_escape_string($row['engine_no']);
    $engine_capacity = (int)$row['engine_capacity'];
    $kir_no = $conn->real_escape_string($row['kir_no']);
    $stnk_no = $conn->real_escape_string($row['stnk_no']);
    $bpkb_no = $conn->real_escape_string($row['bpkb_no']);
    $chasis_no = $conn->real_escape_string($row['chasis_no']);
    $year_production = $conn->real_escape_string($row['year_production']);

    $legal_date = null;
    if (!empty($row['legal_date'])) {
        $legal_date = date("Y-m-d", strtotime($row['legal_date']));
    }

    $legal_date_value = $legal_date ? "'$legal_date'" : "NULL";

    $sql = "
        INSERT INTO vehicles
        (vehicle_id, gps_sn, nopol, type, model, brand,
         car_group, driver_nm, remark, engine_no, engine_capacity,
         kir_no, stnk_no, bpkb_no, chasis_no,
         year_production, legal_date)
        VALUES
        ('$vehicle_id', '$gps_sn', '$nopol', '$type', '$model', '$brand',
         '$car_group', '$driver_nm', '$remark', '$engine_no', '$engine_capacity',
         '$kir_no', '$stnk_no', '$bpkb_no', '$chasis_no',
         '$year_production', $legal_date_value)
        ON DUPLICATE KEY UPDATE
            gps_sn='$gps_sn',
            nopol='$nopol',
            type='$type',
            model='$model',
            brand='$brand',
            car_group='$car_group',
            driver_nm='$driver_nm',
            remark='$remark',
            engine_no='$engine_no',
            engine_capacity='$engine_capacity',
            kir_no='$kir_no',
            stnk_no='$stnk_no',
            bpkb_no='$bpkb_no',
            chasis_no='$chasis_no',
            year_production='$year_production',
            legal_date=$legal_date_value
    ";

    $conn->query($sql);
}

echo "Sinkronisasi selesai. Total kendaraan: " . count($vehicles);

$conn->close();