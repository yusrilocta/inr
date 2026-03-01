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

    public function getSummary() {
        return [
            'drivers' => $this->getTotalDrivers(),
            'vehicles' => $this->getTotalVehicles(),
            'inventory' => $this->getTotalInventory(),
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
                COUNT(r.id) AS total_transaksi,
                COALESCE(SUM(r.jumlah), 0) AS total_qty
            FROM riwayat_service r
            INNER JOIN inventori i ON i.id = r.id_barang
            WHERE r.id_barang IS NOT NULL
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
}
