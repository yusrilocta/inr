<?php

class DriverModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // =============================
    // GET ALL DRIVER
    // =============================
    public function getAll($limit = null, $offset = null) {

        $sql = "SELECT * FROM drivers ORDER BY id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        return $this->conn->query($sql);
    }

    // =============================
    // GET BY ID
    // =============================
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM drivers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =============================
    // INSERT
    // =============================
    public function create($data) {

        $stmt = $this->conn->prepare("
            INSERT INTO drivers 
            (code, name, addr, phone_no, sim_no, sim_class, deposit, join_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssds",
            $data['code'],
            $data['name'],
            $data['addr'],
            $data['phone_no'],
            $data['sim_no'],
            $data['sim_class'],
            $data['deposit'],
            $data['join_date']
        );

        return $stmt->execute();
    }

    // =============================
    // UPDATE
    // =============================
    public function update($id, $data) {

        $stmt = $this->conn->prepare("
            UPDATE drivers SET
                code=?,
                name=?,
                addr=?,
                phone_no=?,
                sim_no=?,
                sim_class=?,
                deposit=?,
                join_date=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "ssssssdsi",
            $data['code'],
            $data['name'],
            $data['addr'],
            $data['phone_no'],
            $data['sim_no'],
            $data['sim_class'],
            $data['deposit'],
            $data['join_date'],
            $id
        );

        return $stmt->execute();
    }

    // =============================
    // DELETE
    // =============================
    public function delete($id) {

        $stmt = $this->conn->prepare("DELETE FROM drivers WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // =============================
    // COUNT
    // =============================
    public function countAll() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM drivers");
        return $result->fetch_assoc()['total'];
    }

}