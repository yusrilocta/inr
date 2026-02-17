<?php
require_once __DIR__ . '/../db/db.php';
class BarangModel {
    private $conn;
    private $table = "stok_barang";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ==============================
    // GET ALL DATA
    // ==============================
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        return $this->conn->query($sql);
    }

    // ==============================
    // GET BY ID
    // ==============================
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM {$this->table} WHERE id = $id";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    // ==============================
    // INSERT DATA
    // ==============================
    public function create($data) {
        $nama_barang   = $this->conn->real_escape_string($data['nama_barang']);
        $jumlah        = (int)$data['jumlah'];
        $satuan        = $this->conn->real_escape_string($data['satuan']);
        $masa_pakai_km = (int)$data['masa_pakai_km'];
        $stok          = (int)$data['stok'];

        $sql = "INSERT INTO {$this->table} 
                (nama_barang, jumlah, satuan, masa_pakai_km, stok)
                VALUES 
                ('$nama_barang', $jumlah, '$satuan', $masa_pakai_km, $stok)";

        return $this->conn->query($sql);
    }

    // ==============================
    // UPDATE DATA
    // ==============================
    public function update($id, $data) {
        $id            = (int)$id;
        $nama_barang   = $this->conn->real_escape_string($data['nama_barang']);
        $jumlah        = (int)$data['jumlah'];
        $satuan        = $this->conn->real_escape_string($data['satuan']);
        $masa_pakai_km = (int)$data['masa_pakai_km'];
        $stok          = (int)$data['stok'];

        $sql = "UPDATE {$this->table} SET
                nama_barang   = '$nama_barang',
                jumlah        = $jumlah,
                satuan        = '$satuan',
                masa_pakai_km = $masa_pakai_km,
                stok          = $stok
                WHERE id = $id";

        return $this->conn->query($sql);
    }

    // ==============================
    // DELETE DATA
    // ==============================
    public function delete($id) {
        $id = (int)$id;
        $sql = "DELETE FROM {$this->table} WHERE id = $id";
        return $this->conn->query($sql);
    }

    // ==============================
    // TAMBAH STOK
    // ==============================
    public function tambahStok($id, $jumlah) {
        $id = (int)$id;
        $jumlah = (int)$jumlah;

        $sql = "UPDATE {$this->table} 
                SET stok = stok + $jumlah 
                WHERE id = $id";

        return $this->conn->query($sql);
    }

    // ==============================
    // KURANGI STOK
    // ==============================
    public function kurangiStok($id, $jumlah) {
        $id = (int)$id;
        $jumlah = (int)$jumlah;

        $sql = "UPDATE {$this->table} 
                SET stok = stok - $jumlah 
                WHERE id = $id AND stok >= $jumlah";

        return $this->conn->query($sql);
    }
}
