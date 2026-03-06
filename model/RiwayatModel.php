<?php

class RiwayatModel {

    private $conn;
    private $table = "riwayat_service";
    private $itemTable = "item_list";

    public function __construct($db) {
        $this->conn = $db;
    }

    /* ===============================
       GET ALL (with optional server-side search)
    =============================== */
    public function getAll($search = null, $vehicleId = null, $dateStart = null, $dateEnd = null) {
        $sql = "
            SELECT
                r.*,
                il.id AS item_id,
                il.id_barang,
                il.jumlah,
                il.harga_satuan,
                il.total_harga,
                il.mekanik_id,
                il.tools,
                i.nama AS nama_barang
            FROM {$this->table} r
            LEFT JOIN {$this->itemTable} il ON il.riwayat_id = r.id
            LEFT JOIN inventori i ON il.id_barang = i.id
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

        if ($dateStart !== null && $dateStart !== '') {
            $conditions[] = "r.tanggal >= ?";
            $params[] = $dateStart;
        }
        if ($dateEnd !== null && $dateEnd !== '') {
            $conditions[] = "r.tanggal <= ?";
            $params[] = $dateEnd;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY r.id DESC, il.id ASC";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getParentList($search = null, $vehicleId = null, $dateStart = null, $dateEnd = null) {
        $sql = "
            SELECT
                r.*,
                COALESCE(SUM(il.jumlah), 0) AS total_qty,
                COALESCE(SUM(il.total_harga), 0) AS total_harga,
                GROUP_CONCAT(
                    CONCAT(COALESCE(i.nama, 'Tanpa Barang'), ' x', COALESCE(il.jumlah, 0))
                    ORDER BY il.id ASC SEPARATOR ', '
                ) AS item_summary
            FROM {$this->table} r
            LEFT JOIN {$this->itemTable} il ON il.riwayat_id = r.id
            LEFT JOIN inventori i ON il.id_barang = i.id
        ";

        $conditions = [];
        $params = [];

        if ($search !== null && $search !== '') {
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

        if ($dateStart !== null && $dateStart !== '') {
            $conditions[] = "r.tanggal >= ?";
            $params[] = $dateStart;
        }
        if ($dateEnd !== null && $dateEnd !== '') {
            $conditions[] = "r.tanggal <= ?";
            $params[] = $dateEnd;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= " GROUP BY r.id ORDER BY r.id DESC";
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
            SELECT
                r.*,
                COALESCE(SUM(il.jumlah), 0) AS total_qty,
                COALESCE(SUM(il.total_harga), 0) AS total_harga
            FROM {$this->table} r
            LEFT JOIN {$this->itemTable} il ON il.riwayat_id = r.id
            WHERE r.id = ?
            GROUP BY r.id
            LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if (!$row) {
            return null;
        }

        $row['items'] = $this->getItemsByRiwayatId((int)$id);
        return $row;
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

            $sql = "
                INSERT INTO {$this->table}
                (vehicle_id, nopol, driver_nm,
                total_km, last_km_service, masa_pakai_km,
                 status, kategori, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssiiisss",
                $data['vehicle_id'],
                $data['nopol'],
                $data['driver_nm'],
                $total_km,
                $last_km_service,
                $masa_pakai_km,
                $data['status'],
                $data['kategori'],
                $data['keterangan']
            );
            $stmt->execute();

            $riwayatId = (int)$this->conn->insert_id;
            $items = $this->normalizeItems($data);
            $this->insertItems($riwayatId, $items);

            $this->syncVehicleServiceInfo($data['vehicle_id'], $total_km);

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

            $oldItems = $this->getItemsByRiwayatId($id);
            $total_km         = (int)$data['total_km'];
            $last_km_service  = (int)$data['last_km_service'];
            $masa_pakai_km    = $total_km - $last_km_service;
            if ($masa_pakai_km < 0) {
                $masa_pakai_km = 0;
            }

            $this->rollbackInventoryStock($oldItems);

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
                    keterangan = ?
                WHERE id = ?
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssiiisssi",
                $data['vehicle_id'],
                $data['nopol'],
                $data['driver_nm'],
                $total_km,
                $last_km_service,
                $masa_pakai_km,
                $data['status'],
                $data['kategori'],
                $data['keterangan'],
                $id
            );
            $stmt->execute();

            $deleteItems = $this->conn->prepare("DELETE FROM {$this->itemTable} WHERE riwayat_id = ?");
            $deleteItems->bind_param("i", $id);
            $deleteItems->execute();

            $newItems = $this->normalizeItems($data);
            $this->insertItems($id, $newItems);

            $this->syncVehicleServiceInfo($data['vehicle_id'], $total_km);

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
        try {
            $this->conn->begin_transaction();

            $oldItems = $this->getItemsByRiwayatId($id);
            $this->rollbackInventoryStock($oldItems);

            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /* ===============================
       TOTAL BIAYA PER VEHICLE
    =============================== */
    public function getTotalBiayaByVehicle($vehicle_id) {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(il.total_harga), 0) as total_biaya
            FROM {$this->table} r
            LEFT JOIN {$this->itemTable} il ON il.riwayat_id = r.id
            WHERE r.vehicle_id = ?
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

    public function getMekanikOptions() {
        $check = $this->conn->query("SHOW TABLES LIKE 'mekanik'");
        if (!$check || (int)$check->num_rows === 0) {
            return [];
        }

        $sql = "SELECT id, nama FROM mekanik ORDER BY nama ASC";
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

    private function getItemsByRiwayatId($riwayatId) {
        $stmt = $this->conn->prepare("
            SELECT il.*, i.nama AS nama_barang
            FROM {$this->itemTable} il
            LEFT JOIN inventori i ON i.id = il.id_barang
            WHERE il.riwayat_id = ?
            ORDER BY il.id ASC
        ");
        $stmt->bind_param("i", $riwayatId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function rollbackInventoryStock(array $items) {
        foreach ($items as $item) {
            $idBarang = isset($item['id_barang']) ? (int)$item['id_barang'] : 0;
            $jumlah = isset($item['jumlah']) ? (int)$item['jumlah'] : 0;
            if ($idBarang <= 0 || $jumlah <= 0) {
                continue;
            }

            $rollbackStok = $this->conn->prepare("
                UPDATE inventori
                SET stok = stok + ?
                WHERE id = ?
            ");
            $rollbackStok->bind_param("ii", $jumlah, $idBarang);
            $rollbackStok->execute();
        }
    }

    private function insertItems($riwayatId, array $items) {
        if (empty($items)) {
            return;
        }

        $insertItem = $this->conn->prepare("
            INSERT INTO {$this->itemTable}
            (riwayat_id, id_barang, jumlah, harga_satuan, total_harga, mekanik_id, tools)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $idBarang = $item['id_barang'];
            $jumlah = $item['jumlah'];
            $hargaSatuan = $item['harga_satuan'];
            $totalHarga = $jumlah * $hargaSatuan;
            $mekanikId = $item['mekanik_id'];
            $tools = $item['tools'];

            $insertItem->bind_param(
                "iiiddis",
                $riwayatId,
                $idBarang,
                $jumlah,
                $hargaSatuan,
                $totalHarga,
                $mekanikId,
                $tools
            );
            $insertItem->execute();

            if (!empty($idBarang) && $jumlah > 0) {
                $updateStok = $this->conn->prepare("
                    UPDATE inventori
                    SET stok = stok - ?
                    WHERE id = ?
                ");
                $updateStok->bind_param("ii", $jumlah, $idBarang);
                $updateStok->execute();
            }
        }
    }

    private function normalizeItems($data) {
        $items = [];

        $idBarang = $data['id_barang'] ?? [];
        $jumlah = $data['jumlah'] ?? [];
        $hargaSatuan = $data['harga_satuan'] ?? [];
        $mekanikId = $data['mekanik_id'] ?? [];
        $tools = $data['tools'] ?? [];

        $isBatch =
            is_array($idBarang) ||
            is_array($jumlah) ||
            is_array($hargaSatuan) ||
            is_array($mekanikId) ||
            is_array($tools);

        if (!$isBatch) {
            $idBarang = [$idBarang];
            $jumlah = [$jumlah];
            $hargaSatuan = [$hargaSatuan];
            $mekanikId = [$mekanikId];
            $tools = [$tools];
        }

        $idBarangList = is_array($idBarang) ? $idBarang : [];
        $jumlahList = is_array($jumlah) ? $jumlah : [];
        $hargaList = is_array($hargaSatuan) ? $hargaSatuan : [];
        $mekanikList = is_array($mekanikId) ? $mekanikId : [];
        $toolsList = is_array($tools) ? $tools : [];
        $max = max(count($idBarangList), count($jumlahList), count($hargaList), count($mekanikList), count($toolsList));

        for ($i = 0; $i < $max; $i++) {
            $rawId = $idBarangList[$i] ?? '';
            $rawJumlah = $jumlahList[$i] ?? 0;
            $rawHarga = $hargaList[$i] ?? 0;
            $rawMekanik = $mekanikList[$i] ?? '';
            $rawTools = strtolower(trim((string)($toolsList[$i] ?? 'tidak')));

            $normalizedId = ($rawId !== '' && $rawId !== null) ? (int)$rawId : null;
            $normalizedJumlah = (int)$rawJumlah;
            if ($normalizedJumlah < 0) {
                $normalizedJumlah = 0;
            }
            $normalizedHarga = (float)$rawHarga;
            if ($normalizedHarga < 0) {
                $normalizedHarga = 0;
            }
            $normalizedMekanik = ($rawMekanik !== '' && $rawMekanik !== null) ? (int)$rawMekanik : null;
            $normalizedTools = in_array($rawTools, ['ya', 'tidak'], true) ? $rawTools : 'tidak';

            if (
                $normalizedId === null &&
                $normalizedJumlah === 0 &&
                $normalizedHarga == 0.0 &&
                $normalizedMekanik === null &&
                $normalizedTools === 'tidak'
            ) {
                continue;
            }

            $items[] = [
                'id_barang' => $normalizedId,
                'jumlah' => $normalizedJumlah,
                'harga_satuan' => $normalizedHarga,
                'mekanik_id' => $normalizedMekanik,
                'tools' => $normalizedTools
            ];
        }

        return $items;
    }
}
