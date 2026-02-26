<?php

class SparepartHistoryModel {

    private $conn;
    private $table = "spare_part_usages";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ==============================
    // GET ALL HISTORY
    // ==============================
    public function getAll() {

        $sql = "SELECT u.*, 
                       sp.part_name,
                       sp.part_code,
                       v.nopol
                FROM {$this->table} u
                LEFT JOIN spare_parts sp 
                    ON u.spare_part_id = sp.id
                LEFT JOIN vehicles v 
                    ON u.vehicle_id = v.id
                ORDER BY u.usage_date DESC";

        return $this->conn->query($sql);
    }

    // ==============================
    // GET BY VEHICLE
    // ==============================
    public function getByVehicle($vehicle_id) {

        $stmt = $this->conn->prepare("
            SELECT u.*, sp.part_name
            FROM {$this->table} u
            LEFT JOIN spare_parts sp
            ON u.spare_part_id = sp.id
            WHERE u.vehicle_id = ?
            ORDER BY u.usage_date DESC
        ");

        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();

        return $stmt->get_result();
    }

    // ==============================
    // GET BY SPAREPART
    // ==============================
    public function getBySparepart($spare_part_id) {

        $stmt = $this->conn->prepare("
            SELECT u.*, v.nopol
            FROM {$this->table} u
            LEFT JOIN vehicles v
            ON u.vehicle_id = v.id
            WHERE u.spare_part_id = ?
            ORDER BY u.usage_date DESC
        ");

        $stmt->bind_param("i", $spare_part_id);
        $stmt->execute();

        return $stmt->get_result();
    }

    // ==============================
    // CREATE HISTORY (WITH AUTO STOCK UPDATE)
    // ==============================
    public function create($data) {

        // 1️⃣ Ambil replacement_km dari sparepart
        $stmtSp = $this->conn->prepare("
            SELECT replacement_km, selling_price
            FROM spare_parts
            WHERE id = ?
        ");
        $stmtSp->bind_param("i", $data['spare_part_id']);
        $stmtSp->execute();
        $sparepart = $stmtSp->get_result()->fetch_assoc();

        $replacement_km = $sparepart['replacement_km'];
        $price = $data['price'] ?? $sparepart['selling_price'];

        // 2️⃣ Hitung next replacement km
        $next_km = null;
        if ($replacement_km) {
            $next_km = $data['odometer'] + $replacement_km;
        }

        // 3️⃣ Insert history
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
            (vehicle_id, spare_part_id, qty, price,
             odometer, usage_date, next_replacement_km, notes)
            VALUES (?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "iiidisss",
            $data['vehicle_id'],
            $data['spare_part_id'],
            $data['qty'],
            $price,
            $data['odometer'],
            $data['usage_date'],
            $next_km,
            $data['notes']
        );

        $stmt->execute();

        // 4️⃣ Kurangi stok
        $stmtStock = $this->conn->prepare("
            UPDATE spare_parts
            SET stock = stock - ?
            WHERE id = ?
        ");
        $stmtStock->bind_param("ii", $data['qty'], $data['spare_part_id']);
        $stmtStock->execute();

        return true;
    }

    // ==============================
    // DELETE HISTORY (ROLLBACK STOCK)
    // ==============================
    public function delete($id) {

        // Ambil data dulu untuk rollback stok
        $stmt = $this->conn->prepare("
            SELECT spare_part_id, qty
            FROM {$this->table}
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) return false;

        // Rollback stok
        $stmtStock = $this->conn->prepare("
            UPDATE spare_parts
            SET stock = stock + ?
            WHERE id = ?
        ");
        $stmtStock->bind_param("ii", $data['qty'], $data['spare_part_id']);
        $stmtStock->execute();

        // Hapus history
        $stmtDel = $this->conn->prepare("
            DELETE FROM {$this->table}
            WHERE id = ?
        ");
        $stmtDel->bind_param("i", $id);

        return $stmtDel->execute();
    }

    // ==============================
    // GET BY ID
    // ==============================
    public function getById($id) {

        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

}