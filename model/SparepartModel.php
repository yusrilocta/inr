<?php

class SparepartModel {

    private $conn;
    private $table = "spare_parts";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ==========================
    // GET ALL
    // ==========================
    public function getAll() {
        $sql = "SELECT sp.*, c.name as category_name
                FROM {$this->table} sp
                LEFT JOIN spare_part_categories c 
                ON sp.category_id = c.id
                ORDER BY sp.id DESC";

        return $this->conn->query($sql);
    }

    // ==========================
    // GET BY ID
    // ==========================
    public function getById($id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ==========================
    // CREATE
    // ==========================
    public function create($data) {

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
            (part_code, part_name, category_id, brand, vehicle_type, unit,
             purchase_price, selling_price, stock, min_stock,
             replacement_km, replacement_month, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "ssisssddiissi",
            $data['part_code'],
            $data['part_name'],
            $data['category_id'],
            $data['brand'],
            $data['vehicle_type'],
            $data['unit'],
            $data['purchase_price'],
            $data['selling_price'],
            $data['stock'],
            $data['min_stock'],
            $data['replacement_km'],
            $data['replacement_month'],
            $data['is_active']
        );

        return $stmt->execute();
    }

    // ==========================
    // UPDATE
    // ==========================
    public function update($id, $data) {

        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET part_code=?,
                part_name=?,
                category_id=?,
                brand=?,
                vehicle_type=?,
                unit=?,
                purchase_price=?,
                selling_price=?,
                stock=?,
                min_stock=?,
                replacement_km=?,
                replacement_month=?,
                is_active=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "ssisssddiissii",
            $data['part_code'],
            $data['part_name'],
            $data['category_id'],
            $data['brand'],
            $data['vehicle_type'],
            $data['unit'],
            $data['purchase_price'],
            $data['selling_price'],
            $data['stock'],
            $data['min_stock'],
            $data['replacement_km'],
            $data['replacement_month'],
            $data['is_active'],
            $id
        );

        return $stmt->execute();
    }

    // ==========================
    // DELETE
    // ==========================
    public function delete($id) {

        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE id=?"
        );
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ==========================
    // GET LOW STOCK
    // ==========================
    public function getLowStock() {
        $sql = "SELECT * FROM {$this->table}
                WHERE stock <= min_stock
                AND is_active = 1";

        return $this->conn->query($sql);
    }

    // ==========================
    // UPDATE STOCK
    // ==========================
    public function updateStock($id, $qty) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET stock = stock + ?
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $qty, $id);
        return $stmt->execute();
    }
}