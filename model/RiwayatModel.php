<?php

class RiwayatModel {

    private $conn;
    private $table = "riwayat_service";

    public function __construct($db) {
        $this->conn = $db;
    }

    /* ===============================
       GET ALL (with optional server-side search)
    =============================== */
    public function getAll($search = null, $vehicleId = null) {
        $sql = "
            SELECT r.*, i.nama AS nama_barang
            FROM {$this->table} r
            LEFT JOIN inventori i ON r.id_barang = i.id
        ";

        $conditions = [];
        $params = [];

        if ($search !== null && $search !== '') {
            // match search term against several relevant columns
            $conditions[] = "(
                r.vehicle_id LIKE ? OR
                r.nopol LIKE ? OR
                r.driver_nm LIKE ? OR
                r.status LIKE ? OR
                r.kategori LIKE ? OR
                i.nama LIKE ?
            )";
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        }

        if ($vehicleId !== null && $vehicleId !== '') {
            $conditions[] = "r.vehicle_id = ?";
            $params[] = $vehicleId;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY r.id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /* ===============================
       GET BY ID
    =============================== */
    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, i.nama AS nama_barang
            FROM {$this->table} r
            LEFT JOIN inventori i ON r.id_barang = i.id
            WHERE r.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /* ===============================
       CREATE
    =============================== */
    public function create($data) {

        try {
            $this->conn->begin_transaction();

            $total_km         = (int)$data['total_km'];
            $last_km_service  = (int)$data['last_km_service'];
            $masa_pakai_km    = $total_km - $last_km_service;
            if ($masa_pakai_km < 0) {
                $masa_pakai_km = 0;
            }

            $id_barang    = !empty($data['id_barang']) ? (int)$data['id_barang'] : null;
            $jumlah       = (int)($data['jumlah'] ?? 0);
            $harga_satuan = (float)($data['harga_satuan'] ?? 0);
            $total_harga  = $jumlah * $harga_satuan;

            $sql = "
                INSERT INTO {$this->table}
                (vehicle_id, nopol, driver_nm,
                total_km, last_km_service, masa_pakai_km,
                 status, kategori, keterangan,
                 id_barang, jumlah, harga_satuan, total_harga)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssiiisssiidd",
                $data['vehicle_id'],
                $data['nopol'],
                $data['driver_nm'],
                $total_km,
                $last_km_service,
                $masa_pakai_km,
                $data['status'],
                $data['kategori'],
                $data['keterangan'],
                $id_barang,
                $jumlah,
                $harga_satuan,
                $total_harga
            );
            $stmt->execute();

            $this->syncVehicleServiceInfo($data['vehicle_id'], $total_km);

            // POTONG STOK JIKA PAKAI SPAREPART
            if ($id_barang && $jumlah > 0) {
                $updateStok = $this->conn->prepare("
                    UPDATE inventori 
                    SET stok = stok - ?
                    WHERE id = ?
                ");
                $updateStok->bind_param("ii", $jumlah, $id_barang);
                $updateStok->execute();
            }

            $this->conn->commit();
            return true;

        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /* ===============================
       UPDATE
    =============================== */
    public function update($id, $data) {
        $id = (int)$id;

        try {
            $this->conn->begin_transaction();

            $old = $this->getById($id);
            if (!$old) {
                $this->conn->rollback();
                return false;
            }

            $total_km         = (int)$data['total_km'];
            $last_km_service  = (int)$data['last_km_service'];
            $masa_pakai_km    = $total_km - $last_km_service;
            if ($masa_pakai_km < 0) {
                $masa_pakai_km = 0;
            }

            $id_barang    = !empty($data['id_barang']) ? (int)$data['id_barang'] : null;
            $jumlah       = (int)($data['jumlah'] ?? 0);
            $harga_satuan = (float)($data['harga_satuan'] ?? 0);
            $total_harga  = $jumlah * $harga_satuan;

            // Kembalikan stok lama sebelum update data
            if (!empty($old['id_barang']) && (int)$old['jumlah'] > 0) {
                $rollbackStok = $this->conn->prepare("
                    UPDATE inventori
                    SET stok = stok + ?
                    WHERE id = ?
                ");
                $oldJumlah = (int)$old['jumlah'];
                $oldBarang = (int)$old['id_barang'];
                $rollbackStok->bind_param("ii", $oldJumlah, $oldBarang);
                $rollbackStok->execute();
            }

            $sql = "
                UPDATE {$this->table}
                SET vehicle_id = ?,
                    nopol = ?,
                    driver_nm = ?,
                    total_km = ?,
                    last_km_service = ?,
                    masa_pakai_km = ?,
                    status = ?,
                    kategori = ?,
                    keterangan = ?,
                    id_barang = ?,
                    jumlah = ?,
                    harga_satuan = ?,
                    total_harga = ?
                WHERE id = ?
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssiiisssiiddi",
                $data['vehicle_id'],
                $data['nopol'],
                $data['driver_nm'],
                $total_km,
                $last_km_service,
                $masa_pakai_km,
                $data['status'],
                $data['kategori'],
                $data['keterangan'],
                $id_barang,
                $jumlah,
                $harga_satuan,
                $total_harga,
                $id
            );
            $stmt->execute();

            $this->syncVehicleServiceInfo($data['vehicle_id'], $total_km);

            // Potong stok baru setelah update data
            if ($id_barang && $jumlah > 0) {
                $updateStok = $this->conn->prepare("
                    UPDATE inventori
                    SET stok = stok - ?
                    WHERE id = ?
                ");
                $updateStok->bind_param("ii", $jumlah, $id_barang);
                $updateStok->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /* ===============================
       DELETE
    =============================== */
    public function delete($id) {
        $id = (int)$id;
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /* ===============================
       TOTAL BIAYA PER VEHICLE
    =============================== */
    public function getTotalBiayaByVehicle($vehicle_id) {
        $stmt = $this->conn->prepare("
            SELECT SUM(total_harga) as total_biaya
            FROM {$this->table}
            WHERE vehicle_id = ?
        ");
        $stmt->bind_param("s", $vehicle_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /* ===============================
       LIST VEHICLE UNTUK FORM
    =============================== */
    public function getVehicleOptions() {
        $sql = "SELECT vehicle_id, nopol, driver_nm, total_km, last_km_service
                FROM vehicles
                ORDER BY nopol ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* ===============================
       LIST INVENTORI UNTUK FORM
    =============================== */
    public function getInventoriOptions() {
        $sql = "SELECT id, nama, stok, harga_satuan
                FROM inventori
                ORDER BY nama ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* ===============================
       SYNC SERVICE INFO KE VEHICLE
    =============================== */
    private function syncVehicleServiceInfo($vehicle_id, $total_km) {
        $today = date('Y-m-d');
        $sql = "UPDATE vehicles
                SET last_km_service = ?, last_service = ?
                WHERE vehicle_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $total_km, $today, $vehicle_id);
        $stmt->execute();
    }
}
