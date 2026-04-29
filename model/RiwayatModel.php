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
            $conditions[] = "r.nopol LIKE ?";
            $like = "%{$search}%";
            $params[] = $like;
        }

        if ($vehicleId !== null && $vehicleId !== '') {
            $conditions[] = "r.nopol = ?";
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

    public function getParentList($search = null, $vehicleId = null, $dateStart = null, $dateEnd = null, $status = null, $dateColumn = 'tanggal') {
        $allowedDateColumns = ['tanggal', 'tgl_sedang_dikerjakan', 'tgl_siap_operasi', 'tgl_selesai'];
        if (!in_array($dateColumn, $allowedDateColumns, true)) {
            $dateColumn = 'tanggal';
        }

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
            $conditions[] = "r.nopol LIKE ?";
            $like = "%{$search}%";
            $params[] = $like;
        }

        if ($vehicleId !== null && $vehicleId !== '') {
            $conditions[] = "r.nopol = ?";
            $params[] = $vehicleId;
        }

        if ($dateStart !== null && $dateStart !== '') {
            $conditions[] = "r.{$dateColumn} >= ?";
            $params[] = $dateStart;
        }
        if ($dateEnd !== null && $dateEnd !== '') {
            $conditions[] = "r.{$dateColumn} <= ?";
            $params[] = $dateEnd;
        }

        if ($status !== null && $status !== '') {
            $conditions[] = "r.status = ?";
            $params[] = $status;
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

    public function getParentExportList($search = null, $vehicleId = null, $dateStart = null, $dateEnd = null, $status = null) {
        $sql = "
            SELECT
                r.id,
                r.tanggal,
                r.nopol,
                r.driver_nm,
                r.status,
                r.kategori,
                r.masa_pakai_km,
                COALESCE(SUM(il.jumlah), 0) AS total_qty,
                COALESCE(SUM(il.total_harga), 0) AS total_harga,
                GROUP_CONCAT(
                    CONCAT(
                        COALESCE(i.nama, 'Tanpa Barang'),
                        ' x', COALESCE(il.jumlah, 0),
                        ' (', COALESCE(il.harga_satuan, 0), ')'
                    )
                    ORDER BY il.id ASC SEPARATOR ', '
                ) AS item_list,
                r.keterangan
            FROM {$this->table} r
            LEFT JOIN {$this->itemTable} il ON il.riwayat_id = r.id
            LEFT JOIN inventori i ON il.id_barang = i.id
        ";

        $conditions = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $conditions[] = "r.nopol LIKE ?";
            $params[] = "%{$search}%";
        }

        if ($vehicleId !== null && $vehicleId !== '') {
            $conditions[] = "r.nopol = ?";
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

        if ($status !== null && $status !== '') {
            $conditions[] = "r.status = ?";
            $params[] = $status;
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

            $nopol = $data['nopol'] ?? '';
            $driver_nm = $data['driver_nm'] ?? '';
            $total_km = (int)($data['total_km'] ?? 0);
            $last_km_service = (int)($data['last_km_service'] ?? 0);
            $masa_pakai_km = $total_km - $last_km_service;
            if ($masa_pakai_km < 0) {
                $masa_pakai_km = 0;
            }

            $tanggal = trim((string)($data['tanggal'] ?? ''));
            $hasTanggal = $tanggal !== '';
            if ($hasTanggal) {
                $sql = "
                    INSERT INTO {$this->table}
                    (nopol, driver_nm,
                    total_km, last_km_service, masa_pakai_km,
                     status, kategori, keterangan, tanggal)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param(
                    "ssiiissss",
                    $nopol,
                    $driver_nm,
                    $total_km,
                    $last_km_service,
                    $masa_pakai_km,
                    $data['status'],
                    $data['kategori'],
                    $data['keterangan'],
                    $tanggal
                );
            } else {
                $sql = "
                    INSERT INTO {$this->table}
                    (nopol, driver_nm,
                    total_km, last_km_service, masa_pakai_km,
                     status, kategori, keterangan)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param(
                    "ssiiisss",
                    $nopol,
                    $driver_nm,
                    $total_km,
                    $last_km_service,
                    $masa_pakai_km,
                    $data['status'],
                    $data['kategori'],
                    $data['keterangan']
                );
            }
            $stmt->execute();

            $riwayatId = (int)$this->conn->insert_id;
            $items = $this->normalizeItems($data);
            $this->insertItems($riwayatId, $items);

            if (strtolower((string)($data['status'] ?? '')) !== 'jadwal') {
                $this->syncVehicleServiceInfo($nopol, $total_km);
            }

            $this->conn->commit();
            return true;

        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function createJadwal($data) {
        try {
            $this->conn->begin_transaction();

            $nopol = trim((string)($data['nopol'] ?? ''));
            $driver_nm = trim((string)($data['driver_nm'] ?? ''));
            $tanggal = trim((string)($data['tanggal'] ?? ''));
            $kategori = trim((string)($data['kategori'] ?? 'normal'));
            $keterangan = trim((string)($data['keterangan'] ?? ''));

            if ($nopol === '' || $tanggal === '') {
                $this->conn->rollback();
                return false;
            }

            $status = 'jadwal';
            $total_km = (int)($data['total_km'] ?? 0);
            $last_km_service = (int)($data['last_km_service'] ?? 0);
            $masa_pakai_km = $total_km - $last_km_service;
            if ($masa_pakai_km < 0) {
                $masa_pakai_km = 0;
            }

            $sql = "
                INSERT INTO {$this->table}
                (nopol, driver_nm, total_km, last_km_service, masa_pakai_km, status, kategori, keterangan, tanggal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "ssiiissss",
                $nopol,
                $driver_nm,
                $total_km,
                $last_km_service,
                $masa_pakai_km,
                $status,
                $kategori,
                $keterangan,
                $tanggal
            );
            $stmt->execute();

            $riwayatId = (int)$this->conn->insert_id;
            $items = $this->normalizeItems($data);
            $this->insertItems($riwayatId, $items);

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
            $tanggal          = trim((string)($data['tanggal'] ?? ''));
            if ($tanggal === '') {
                $tanggal = (string)($old['tanggal'] ?? date('Y-m-d'));
            }
            if ($masa_pakai_km < 0) {
                $masa_pakai_km = 0;
            }

            $this->rollbackInventoryStock($oldItems);

            $sql = "
                UPDATE {$this->table}
                SET nopol = ?,
                    driver_nm = ?,
                    total_km = ?,
                    last_km_service = ?,
                    masa_pakai_km = ?,
                    status = ?,
                    kategori = ?,
                    keterangan = ?,
                    tanggal = ?
                WHERE id = ?
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "ssiiissssi",
                $data['nopol'],
                $data['driver_nm'],
                $total_km,
                $last_km_service,
                $masa_pakai_km,
                $data['status'],
                $data['kategori'],
                $data['keterangan'],
                $tanggal,
                $id
            );
            $stmt->execute();

            $deleteItems = $this->conn->prepare("DELETE FROM {$this->itemTable} WHERE riwayat_id = ?");
            $deleteItems->bind_param("i", $id);
            $deleteItems->execute();

            $newItems = $this->normalizeItems($data);
            $this->insertItems($id, $newItems);

            if (strtolower((string)($data['status'] ?? '')) !== 'jadwal') {
                $this->syncVehicleServiceInfo($data['nopol'], $total_km);
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function updateStatusToSedangDikerjakan($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET status = 'sedang dikerjakan',
                tgl_sedang_dikerjakan = CURDATE()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateStatusToSiapOperasi($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET status = 'siap operasi',
                tgl_siap_operasi = CURDATE()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateStatusToSelesai($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET status = 'selesai',
                tgl_selesai = CURDATE()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function completeServiceWithFotoSelesai($id, array $data) {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        try {
            $this->conn->begin_transaction();

            $old = $this->getById($id);
            if (!$old) {
                $this->conn->rollback();
                return false;
            }

            $oldItems = $this->getItemsByRiwayatId($id);
            $itemsById = [];
            foreach ($oldItems as $item) {
                $itemId = (int)($item['id'] ?? 0);
                if ($itemId > 0) {
                    $itemsById[$itemId] = $item;
                }
            }

            $itemIds = $data['item_id'] ?? [];
            $existingFotoSelesai = $data['existing_foto_selesai'] ?? [];
            $fotoSelesaiFiles = $this->normalizeFileArray($data['foto_selesai'] ?? []);

            if (!is_array($itemIds)) {
                $itemIds = [$itemIds];
            }
            if (!is_array($existingFotoSelesai)) {
                $existingFotoSelesai = [$existingFotoSelesai];
            }

            $updateItem = $this->conn->prepare("
                UPDATE {$this->itemTable}
                SET foto_selesai = ?
                WHERE id = ? AND riwayat_id = ?
            ");

            foreach ($itemIds as $index => $itemIdValue) {
                $itemId = (int)$itemIdValue;
                if ($itemId <= 0 || !isset($itemsById[$itemId])) {
                    continue;
                }

                $currentItem = $itemsById[$itemId];
                $existingName = $existingFotoSelesai[$index] ?? ($currentItem['foto_selesai'] ?? null);
                $savedName = $this->saveUploadFile(
                    $fotoSelesaiFiles[$index] ?? null,
                    is_string($existingName) ? $existingName : null,
                    'foto_selesai'
                );

                $updateItem->bind_param("sii", $savedName, $itemId, $id);
                $updateItem->execute();
            }

            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET status = 'selesai',
                    tgl_selesai = CURDATE()
                WHERE id = ?
            ");
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
            WHERE r.nopol = ?
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
    private function syncVehicleServiceInfo($nopol, $total_km) {
        $today = date('Y-m-d');
        $sql = "UPDATE vehicles
                SET last_km_service = ?, last_service = ?
                WHERE nopol = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $total_km, $today, $nopol);
        $stmt->execute();
    }

    private function getItemsByRiwayatId($riwayatId) {
        $stmt = $this->conn->prepare("
            SELECT
                il.*,
                i.nama AS nama_barang,
                m.nama AS nama_mekanik
            FROM {$this->itemTable} il
            LEFT JOIN inventori i ON i.id = il.id_barang
            LEFT JOIN mekanik m ON m.id = il.mekanik_id
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
            (riwayat_id, id_barang, jumlah, harga_satuan, total_harga, mekanik_id, tools, foto_rusak, foto_selesai)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $idBarang = $item['id_barang'];
            $jumlah = $item['jumlah'];
            $hargaSatuan = $item['harga_satuan'];
            $totalHarga = $jumlah * $hargaSatuan;
            $mekanikId = $item['mekanik_id'];
            $tools = $item['tools'];
            $fotoRusak = $this->saveUploadFile($item['foto_rusak_file'] ?? null, $item['foto_rusak'] ?? null, 'foto_rusak');
            $fotoSelesai = $this->saveUploadFile($item['foto_selesai_file'] ?? null, $item['foto_selesai'] ?? null, 'foto_selesai');

            $insertItem->bind_param(
                "iiiddisss",
                $riwayatId,
                $idBarang,
                $jumlah,
                $hargaSatuan,
                $totalHarga,
                $mekanikId,
                $tools,
                $fotoRusak,
                $fotoSelesai
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
        $fotoRusakFiles = $this->normalizeFileArray($data['foto_rusak'] ?? []);
        $fotoSelesaiFiles = $this->normalizeFileArray($data['foto_selesai'] ?? []);
        $existingFotoRusak = $data['existing_foto_rusak'] ?? [];
        $existingFotoSelesai = $data['existing_foto_selesai'] ?? [];

        $isBatch =
            is_array($idBarang) ||
            is_array($jumlah) ||
            is_array($hargaSatuan) ||
            is_array($mekanikId) ||
            is_array($tools) ||
            is_array($existingFotoRusak) ||
            is_array($existingFotoSelesai);

        if (!$isBatch) {
            $idBarang = [$idBarang];
            $jumlah = [$jumlah];
            $hargaSatuan = [$hargaSatuan];
            $mekanikId = [$mekanikId];
            $tools = [$tools];
            $existingFotoRusak = [$existingFotoRusak];
            $existingFotoSelesai = [$existingFotoSelesai];
        }

        $idBarangList = is_array($idBarang) ? $idBarang : [];
        $jumlahList = is_array($jumlah) ? $jumlah : [];
        $hargaList = is_array($hargaSatuan) ? $hargaSatuan : [];
        $mekanikList = is_array($mekanikId) ? $mekanikId : [];
        $toolsList = is_array($tools) ? $tools : [];
        $existingFotoRusakList = is_array($existingFotoRusak) ? $existingFotoRusak : [];
        $existingFotoSelesaiList = is_array($existingFotoSelesai) ? $existingFotoSelesai : [];
        $max = max(
            count($idBarangList),
            count($jumlahList),
            count($hargaList),
            count($mekanikList),
            count($toolsList),
            count($existingFotoRusakList),
            count($existingFotoSelesaiList)
        );

        for ($i = 0; $i < $max; $i++) {
            $rawId = $idBarangList[$i] ?? '';
            $rawJumlah = $jumlahList[$i] ?? 0;
            $rawHarga = $hargaList[$i] ?? 0;
            $rawMekanik = $mekanikList[$i] ?? '';
            $rawTools = strtolower(trim((string)($toolsList[$i] ?? 'tidak')));
            $existingRusak = $existingFotoRusakList[$i] ?? null;
            $existingSelesai = $existingFotoSelesaiList[$i] ?? null;

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
                'tools' => $normalizedTools,
                'foto_rusak_file' => $fotoRusakFiles[$i] ?? null,
                'foto_selesai_file' => $fotoSelesaiFiles[$i] ?? null,
                'foto_rusak' => is_string($existingRusak) ? $existingRusak : null,
                'foto_selesai' => is_string($existingSelesai) ? $existingSelesai : null,
            ];
        }

        return $items;
    }

    private function normalizeFileArray($fileCollection) {
        if (!is_array($fileCollection) || !isset($fileCollection['name'])) {
            return [];
        }

        $files = [];
        if (is_array($fileCollection['name'])) {
            foreach ($fileCollection['name'] as $i => $name) {
                $files[$i] = [
                    'name' => $name,
                    'type' => $fileCollection['type'][$i] ?? '',
                    'tmp_name' => $fileCollection['tmp_name'][$i] ?? '',
                    'error' => $fileCollection['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $fileCollection['size'][$i] ?? 0,
                ];
            }
        } else {
            $files[0] = $fileCollection;
        }

        return $files;
    }

    private function saveUploadFile($file, $existingName = null, $prefix = 'foto') {
        if (!is_array($file)) {
            return is_string($existingName) && $existingName !== '' ? $existingName : null;
        }

        $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                error_log(sprintf(
                    '[RiwayatModel] Upload %s gagal. error=%d, name=%s',
                    $prefix,
                    $uploadError,
                    (string)($file['name'] ?? '')
                ));
            }
            return is_string($existingName) && $existingName !== '' ? $existingName : null;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            error_log(sprintf(
                '[RiwayatModel] File %s bukan uploaded file yang valid. tmp_name=%s, name=%s',
                $prefix,
                (string)($file['tmp_name'] ?? ''),
                (string)($file['name'] ?? '')
            ));
            return is_string($existingName) && $existingName !== '' ? $existingName : null;
        }

        $uploadDir = dirname(__DIR__) . '/pages/bukti/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0777);
        }

        if (!is_writable($uploadDir)) {
            error_log(sprintf(
                '[RiwayatModel] Folder upload tidak writable. path=%s',
                $uploadDir
            ));
            return is_string($existingName) && $existingName !== '' ? $existingName : null;
        }

        $originalName = $file['name'] ?? '';
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $extension = $extension ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $extension) : '';
        $filename = sprintf('%s_%s_%s%s', $prefix, time(), bin2hex(random_bytes(4)), $extension);
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            error_log(sprintf(
                '[RiwayatModel] move_uploaded_file gagal. prefix=%s, tmp_name=%s, target=%s, name=%s',
                $prefix,
                (string)($file['tmp_name'] ?? ''),
                $targetPath,
                $originalName
            ));
            return is_string($existingName) && $existingName !== '' ? $existingName : null;
        }

        return $filename;
    }
}
