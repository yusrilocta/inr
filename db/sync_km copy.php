<?php
set_time_limit(0); // Unlimited execution time
ini_set('default_socket_timeout', 300);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "inr";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$token = "2C46FA731D55460FBDFE4FF8F0F98DD5";
$url   = "https://vtsapi.easygo-gps.co.id/api/report/total_km";

// Periode (contoh: bulan berjalan)
$start_time = date("2020-01-01 00:00:00");
$stop_time  = date("2026-02-25 23:59:59");

// Ambil semua kendaraan
$query = $conn->query("SELECT vehicle_id, nopol FROM vehicles");

// Kumpulkan data kendaraan
$vehicles = [];
while ($row = $query->fetch_assoc()) {
    if (!empty($row['nopol']) && !empty($row['vehicle_id'])) {
        $vehicles[] = $row;
    }
}

echo "<h3>Proses Sync Total KM (Batch Processing)...</h3>";
echo "Total kendaraan: " . count($vehicles) . "<br><br>";

// -----------------------------------------------------------------------------
// helper function: call API with retries and configurable timeout
function callApiWithRetry($url, $token, $payload, $timeout = 120, $maxRetries = 3)
{
    $attempt = 0;
    $lastError = null;

    while ($attempt < $maxRetries) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Token: $token"
            ],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 30 // allow some time to connect
        ]);

        $response = curl_exec($ch);
        if (!curl_errno($ch)) {
            curl_close($ch);
            return $response;
        }

        $lastError = curl_error($ch);
        curl_close($ch);

        // exponential backoff before retrying
        $attempt++;
        sleep(pow(2, $attempt));
    }

    throw new Exception("Curl failed after {$maxRetries} attempts: {$lastError}");
}

// Batch processing size can be tuned; smaller batches reduce payload size and
// give the remote server breathing room. 10 is a reasonable default.
$batch_size = 10;
$total_batches = ceil(count($vehicles) / $batch_size);

for ($batch = 0; $batch < $total_batches; $batch++) {
    // collect vehicles for this batch
    $batch_vehicles = array_slice($vehicles, $batch * $batch_size, $batch_size);

    $lstNoPOL = [];
    $lstVehicleId = [];
    foreach ($batch_vehicles as $vehicle) {
        $lstNoPOL[] = $vehicle['nopol'];
        $lstVehicleId[] = $vehicle['vehicle_id'];
    }

    $payload = json_encode([
        "start_time" => $start_time,
        "stop_time"  => $stop_time,
        "lstNoPOL"   => $lstNoPOL,
        "lstVehicleId" => $lstVehicleId,
        "encrypted"  => 0
    ]);

    try {
        $response = callApiWithRetry($url, $token, $payload);
    } catch (Exception $ex) {
        echo "❌ Curl error batch " . ($batch + 1) . ": " . $ex->getMessage() . "<br>";
        continue;
    }

    $result = json_decode($response, true);

    if (!isset($result['ResponseCode']) || $result['ResponseCode'] != 1) {
        echo "❌ API gagal batch " . ($batch + 1) . ": " . ($result['ResponseMessage'] ?? 'Unknown error') . "<br>";
        continue;
    }

    if (!empty($result['Data']) && is_array($result['Data'])) {
        // wrap updates in a transaction for speed
        $conn->begin_transaction();
        foreach ($result['Data'] as $data) {
            $total_km = $data['total_km'] ?? 0;
            $vehicle_id = $data['vehicle_id'] ?? null;
            $car_plate = $data['car_plate'] ?? $data['lstNoPOL'] ?? 'Unknown';

            if ($vehicle_id) {
                $stmt = $conn->prepare("UPDATE vehicles SET total_km = ? WHERE vehicle_id = ?");
                $stmt->bind_param("ds", $total_km, $vehicle_id);
                if ($stmt->execute()) {
                    echo "✔ " . $car_plate . " berhasil update: " . number_format($total_km, 2) . " KM <br>";
                } else {
                    echo "❌ Update gagal untuk " . $car_plate . "<br>";
                }
                $stmt->close();
            }
        }
        $conn->commit();
    }

    echo "<small>Batch " . ($batch + 1) . " dari " . $total_batches . " selesai</small><br>";
}

// -----------------------------------------------------------------------------

echo "<br><strong>✅ Sync Selesai</strong>";