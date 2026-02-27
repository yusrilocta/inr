<?php

class InventoriModel {

    private $conn;
    private $table = "inventori";

    public function __construct($db) {
        $this->conn = $db;
    }

    /* ===============================
       GET ALL DATA
    =============================== */
    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} ORDER BY id DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /* ===============================
       GET BY ID
    =============================== */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /* ===============================
       CREATE
    =============================== */
    public function create($data) {
        $sql = "INSERT INTO {$this->table}
                (nama, kategori, stok, peringatan_stok, harga_satuan, masa_pakai)
                VALUES
                (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $data['nama'],
            $data['kategori'],
            $data['stok'],
            $data['peringatan_stok'],
            $data['harga_satuan'],
            $data['masa_pakai']
        ]);
    }

    /* ===============================
       UPDATE
    =============================== */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET
                nama = ?,
                kategori = ?,
                stok = ?,
                peringatan_stok = ?,
                harga_satuan = ?,
                masa_pakai = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $data['nama'],
            $data['kategori'],
            $data['stok'],
            $data['peringatan_stok'],
            $data['harga_satuan'],
            $data['masa_pakai'],
            $id
        ]);
    }

    /* ===============================
       DELETE
    =============================== */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /* ===============================
       CEK STOK MENIPIS
    =============================== */
    public function getStokMenipis() {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE stok <= peringatan_stok
            ORDER BY stok ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

}