<?php

class EasyGoService {

    private $base_url = "https://vtsapi.easygo-gps.co.id";
    private $token    = "2C46FA731D55460FBDFE4FF8F0F98DD5";

    /**
     * Core request handler
     */
    private function request($endpoint, $data = [])
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->base_url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "Token: " . $this->token
            ],
        ]);

        $response = curl_exec($ch);

        // Handle CURL error
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'ResponseCode' => 0,
                'ResponseMsg'  => 'CURL Error: ' . $error
            ];
        }

        // Handle HTTP error
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            return [
                'ResponseCode' => 0,
                'ResponseMsg'  => 'HTTP Error Code: ' . $httpCode
            ];
        }

        $decoded = json_decode($response, true);

        if (!$decoded) {
            return [
                'ResponseCode' => 0,
                'ResponseMsg'  => 'Invalid JSON Response'
            ];
        }

        return $decoded;
    }

    // ==================================================
    // GET VEHICLES
    // ==================================================
    public function getVehicles($nopol = "")
    {
        return $this->request("/api/master/vehicles", [
            "nopol" => $nopol
        ]);
    }

    // ==================================================
    // GET TOTAL KM
    // ==================================================
    public function getTotalKm($start, $end, $vehicleIds = [], $nopolList = [])
    {
        return $this->request("/api/report/total_km", [
            "start_time"   => $start,             // format: yyyy-MM-dd HH:mm:ss
            "stop_time"    => $end,
            "lstVehicleId" => $vehicleIds,        // array
            "lstNoPol"     => $nopolList,         // array
            "encrypted"    => 0
        ]);
    }

}