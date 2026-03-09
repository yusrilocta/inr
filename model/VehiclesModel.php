<?php

class VehiclesModel {

    private $conn;
    private $table = "vehicles";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function esc(array $data, string $key, string $default = ''): string {
        return $this->conn->real_escape_string((string)($data[$key] ?? $default));
    }

    private function buildVehicleId(array $data): string {
        $rawVehicleId = trim((string)($data['vehicle_id'] ?? ''));
        if ($rawVehicleId !== '') {
            return $this->conn->real_escape_string($rawVehicleId);
        }

        $rawNopol = strtoupper((string)($data['nopol'] ?? ''));
        $normalizedNopol = preg_replace('/[^A-Z0-9]/', '', $rawNopol);
        if ($normalizedNopol === '') {
            $normalizedNopol = 'VH' . date('YmdHis');
        }

        return $this->conn->real_escape_string($normalizedNopol);
    }

    /* =========================
       GET ALL DATA
    ========================== */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        return $this->conn->query($sql);
    }

    /* =========================
       GET BY ID (primary key)
    ========================== */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM {$this->table} WHERE id = $id LIMIT 1";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    /* =========================
       GET BY vehicle_id (API ID)
    ========================== */
    public function getByVehicleId($vehicle_id) {
        $vehicle_id = $this->conn->real_escape_string($vehicle_id);
        $sql = "SELECT * FROM {$this->table} WHERE vehicle_id = '$vehicle_id' LIMIT 1";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    /* =========================
       CREATE
    ========================== */
    public function create($data) {
        $vehicle_id     = $this->buildVehicleId($data);
        $gps_sn         = $this->esc($data, 'gps_sn');
        $nopol          = $this->esc($data, 'nopol');
        $type           = $this->esc($data, 'type');
        $model          = $this->esc($data, 'model');
        $brand          = $this->esc($data, 'brand');
        $car_group      = $this->esc($data, 'car_group');
        $driver_nm      = $this->esc($data, 'driver_nm');
        $remark         = $this->esc($data, 'remark');
        $engine_no      = $this->esc($data, 'engine_no');
        $total_km       = $this->esc($data, 'total_km', '0');
        $engine_capacity= (int)($data['engine_capacity'] ?? 0);
        $kir_no         = $this->esc($data, 'kir_no');
        $stnk_no        = $this->esc($data, 'stnk_no');
        $bpkb_no        = $this->esc($data, 'bpkb_no');
        $chasis_no      = $this->esc($data, 'chasis_no');
        $year_production= $this->esc($data, 'year_production');
        $last_service   = !empty($data['last_service']) ? "'" . date('Y-m-d', strtotime($data['last_service'])) . "'" : "NULL";
        $last_km_service= !empty($data['last_km_service']) ? (int)$data['last_km_service'] : "NULL";

        $legal_date = !empty($data['legal_date'])
            ? "'" . date("Y-m-d", strtotime($data['legal_date'])) . "'"
            : "NULL";

        $sql = "
            INSERT INTO {$this->table}
            (vehicle_id, gps_sn, nopol, type, model, brand,
             car_group, driver_nm, remark, engine_no,total_km, engine_capacity,
             kir_no, stnk_no, bpkb_no, chasis_no,
             year_production, legal_date, last_service, last_km_service)
            VALUES
            ('$vehicle_id', '$gps_sn', '$nopol', '$type', '$model', '$brand',
             '$car_group', '$driver_nm', '$remark', '$engine_no','$total_km','$engine_capacity',
             '$kir_no', '$stnk_no', '$bpkb_no', '$chasis_no',
             '$year_production', $legal_date, $last_service, $last_km_service)
        ";

        return $this->conn->query($sql);
    }

    /* =========================
       UPDATE
    ========================== */
    public function update($id, $data) {

        $id = (int)$id;

        $gps_sn         = $this->esc($data, 'gps_sn');
        $nopol          = $this->esc($data, 'nopol');
        $type           = $this->esc($data, 'type');
        $model          = $this->esc($data, 'model');
        $brand          = $this->esc($data, 'brand');
        $car_group      = $this->esc($data, 'car_group');
        $driver_nm      = $this->esc($data, 'driver_nm');
        $remark         = $this->esc($data, 'remark');
        $engine_no      = $this->esc($data, 'engine_no');
        $total_km       = $this->esc($data, 'total_km', '0');
        $engine_capacity= (int)($data['engine_capacity'] ?? 0);
        $kir_no         = $this->esc($data, 'kir_no');
        $stnk_no        = $this->esc($data, 'stnk_no');
        $bpkb_no        = $this->esc($data, 'bpkb_no');
        $chasis_no      = $this->esc($data, 'chasis_no');
        $year_production= $this->esc($data, 'year_production');
        $last_service   = !empty($data['last_service']) ? "'" . date('Y-m-d', strtotime($data['last_service'])) . "'" : "NULL";
        $last_km_service= !empty($data['last_km_service']) ? (int)$data['last_km_service'] : "NULL";

        $legal_date = !empty($data['legal_date'])
            ? "'" . date("Y-m-d", strtotime($data['legal_date'])) . "'"
            : "NULL";

        $sql = "
            UPDATE {$this->table} SET
                gps_sn='$gps_sn',
                nopol='$nopol',
                type='$type',
                model='$model',
                brand='$brand',
                car_group='$car_group',
                driver_nm='$driver_nm',
                remark='$remark',
                engine_no='$engine_no',
                total_km='$total_km',
                engine_capacity='$engine_capacity',
                kir_no='$kir_no',
                stnk_no='$stnk_no',
                bpkb_no='$bpkb_no',
                chasis_no='$chasis_no',
                year_production='$year_production',
                legal_date=$legal_date,
                last_service=$last_service,
                last_km_service=$last_km_service
            WHERE id=$id
        ";

        return $this->conn->query($sql);
    }

    /* =========================
       DELETE
    ========================== */
    public function delete($id) {
        $id = (int)$id;
        $sql = "DELETE FROM {$this->table} WHERE id = $id";
        return $this->conn->query($sql);
    }

}
