<?php

class MekanikModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // =============================
    // GET ALL MEKANIK
    // =============================
    public function getAll($limit = null, $offset = null) {

        $sql = "SELECT * FROM mekanik ORDER BY id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        return $this->conn->query($sql);
    }

    // =============================
    // GET BY ID
    // =============================
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM mekanik WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =============================
    // INSERT
    // =============================
    public function create($data) {

        $stmt = $this->conn->prepare("
            INSERT INTO mekanik
            (nama, no_ktp, alamat)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param(
            "sss",
            $data['nama'],
            $data['no_ktp'],
            $data['alamat']
        );

        return $stmt->execute();
    }

    // =============================
    // UPDATE
    // =============================
    public function update($id, $data) {

        $stmt = $this->conn->prepare("
            UPDATE mekanik SET
                nama=?,
                no_ktp=?,
                alamat=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "sssi",
            $data['nama'],
            $data['no_ktp'],
            $data['alamat'],
            $id
        );

        return $stmt->execute();
    }

    // =============================
    // DELETE
    // =============================
    public function delete($id) {

        $stmt = $this->conn->prepare("DELETE FROM mekanik WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // =============================
    // COUNT
    // =============================
    public function countAll() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM mekanik");
        return $result->fetch_assoc()['total'];
    }

}
