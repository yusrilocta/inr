<?php

class DashboardModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function countByTable($table) {
        $sql = "SELECT COUNT(*) AS total FROM {$table}";
        $result = $this->conn->query($sql);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['total'] ?? 0);
    }

    public function getTotalDrivers() {
        return $this->countByTable('drivers');
    }

    public function getTotalVehicles() {
        return $this->countByTable('vehicles');
    }

    public function getTotalInventory() {
        return $this->countByTable('inventori');
    }

    public function getTotalRiwayat() {
        return $this->countByTable('riwayat_service');
    }

    public function getTotalRiwayatByStatus($status) {
        $status = trim((string)$status);
        if ($status === '') {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total FROM riwayat_service WHERE status = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['total'] ?? 0);
    }

    public function getTotalInventoryWarning() {
        $sql = "SELECT COUNT(*) AS total FROM inventori WHERE stok <= peringatan_stok";
        $result = $this->conn->query($sql);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['total'] ?? 0);
    }

    public function getSummary() {
        return [
            'drivers' => $this->getTotalDrivers(),
            'vehicles' => $this->getTotalVehicles(),
            'riwayat_jadwal' => $this->getTotalRiwayatByStatus('jadwal'),
            'inventory' => $this->getTotalInventory(),
            'inventory_warning' => $this->getTotalInventoryWarning(),
            'riwayat_selesai' => $this->getTotalRiwayatByStatus('selesai'),
            'riwayat' => $this->getTotalRiwayat()
        ];
    }

    public function getTopSparepartRiwayat($limit = 3) {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 3;
        }

        $sql = "
            SELECT
                i.id,
                i.nama,
                COUNT(DISTINCT il.riwayat_id) AS total_transaksi,
                COALESCE(SUM(il.jumlah), 0) AS total_qty
            FROM item_list il
            INNER JOIN inventori i ON i.id = il.id_barang
            INNER JOIN riwayat_service r ON r.id = il.riwayat_id
            WHERE il.id_barang IS NOT NULL
            GROUP BY i.id, i.nama
            ORDER BY total_qty DESC, total_transaksi DESC, i.nama ASC
            LIMIT {$limit}
        ";

        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getCountChart($dateStart = null, $dateEnd = null) {
        $sql = "
        SELECT DATE(tanggal) as tgl, COUNT(*) as total
        FROM riwayat_service
        ";

        $conditions = [];
        $params = [];

        if ($dateStart !== null && $dateStart !== '') {
            $conditions[] = "tanggal >= ?";
            $params[] = $dateStart;
        }
        if ($dateEnd !== null && $dateEnd !== '') {
            $conditions[] = "tanggal <= ?";
            $params[] = $dateEnd;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= "
        GROUP BY DATE(tanggal)
        ORDER BY DATE(tanggal) ASC
        ";

        if (empty($params)) {
            $result = $this->conn->query($sql);
            if (!$result) {
                return [];
            }
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getCountChartByStatus($status, $dateStart = null, $dateEnd = null) {
        $status = trim((string)$status);
        if ($status === '') {
            return [];
        }

        $sql = "
        SELECT DATE(tanggal) as tgl, COUNT(*) as total
        FROM riwayat_service
        WHERE status = ?
        GROUP BY DATE(tanggal)
        ORDER BY DATE(tanggal) ASC
        ";

        $conditions = [];
        $params = [$status];

        if ($dateStart !== null && $dateStart !== '') {
            $conditions[] = "tanggal >= ?";
            $params[] = $dateStart;
        }
        if ($dateEnd !== null && $dateEnd !== '') {
            $conditions[] = "tanggal <= ?";
            $params[] = $dateEnd;
        }

        if (!empty($conditions)) {
            $sql = "
            SELECT DATE(tanggal) as tgl, COUNT(*) as total
            FROM riwayat_service
            WHERE status = ?
              AND " . implode(" AND ", $conditions) . "
            GROUP BY DATE(tanggal)
            ORDER BY DATE(tanggal) ASC
            ";
        }

        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
