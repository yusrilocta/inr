<?php
require_once __DIR__ . '/../db/db.php';

class PelangganModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // =========================
    // ✅ Ambil Semua Data
    // =========================
    public function getAll() {
        $sql = "SELECT * FROM pelanggan ORDER BY id DESC";
        return $this->conn->query($sql);
    }

    // =========================
    // ✅ Ambil Berdasarkan ID
    // =========================
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM pelanggan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================
    // ✅ Insert Data
    // =========================
    public function create($data) {

        $stmt = $this->conn->prepare("
            INSERT INTO pelanggan (
                nomor, nomor_pbs, tanggal, gudang,
                no_pol, nama_truk, nama_supir,
                odo_meter, status
            ) VALUES (?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssssssis",
            $data['nomor'],
            $data['nomor_pbs'],
            $data['tanggal'],
            $data['gudang'],
            $data['no_pol'],
            $data['nama_truk'],
            $data['nama_supir'],
            $data['odo_meter'],
            $data['status']
        );

        return $stmt->execute();
    }

    // =========================
    // ✅ Update Data
    // =========================
    public function update($id, $data) {

        $stmt = $this->conn->prepare("
            UPDATE pelanggan SET
                nomor=?,
                nomor_pbs=?,
                tanggal=?,
                gudang=?,
                no_pol=?,
                nama_truk=?,
                nama_supir=?,
                odo_meter=?,
                status=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "sssssssisi",
            $data['nomor'],
            $data['nomor_pbs'],
            $data['tanggal'],
            $data['gudang'],
            $data['no_pol'],
            $data['nama_truk'],
            $data['nama_supir'],
            $data['odo_meter'],
            $data['status'],
            $id
        );

        return $stmt->execute();
    }

    // =========================
    // ✅ Delete Data
    // =========================
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM pelanggan WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

}
?>
