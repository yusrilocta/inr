<?php
require_once __DIR__ . '/../db/db.php';

class ServiceModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ✅ Ambil semua data
    public function getAll() {
        $sql = "SELECT * FROM service_truck ORDER BY id DESC";
        return $this->conn->query($sql);
    }

    // ✅ Ambil berdasarkan ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM service_truck WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ✅ Insert Data
    public function create($data) {

        $stmt = $this->conn->prepare("
            INSERT INTO service_truck (
                nomor, nomor_pbs, tanggal, gudang, no_pol, nama_truk,
                nama_supir, odo_meter, status, nama_barang, jumlah,
                satuan, masa_pakai_km, penggantian_sebelumnya_tgl,
                penggantian_sebelumnya_km, deskripsi_kerusakan,
                keterangan, kategori_service, harga_satuan, total_harga
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "ssssssssssississssdd",
            $data['nomor'],
            $data['nomor_pbs'],
            $data['tanggal'],
            $data['gudang'],
            $data['no_pol'],
            $data['nama_truk'],
            $data['nama_supir'],
            $data['odo_meter'],
            $data['status'],
            $data['nama_barang'],
            $data['jumlah'],
            $data['satuan'],
            $data['masa_pakai_km'],
            $data['penggantian_sebelumnya_tgl'],
            $data['penggantian_sebelumnya_km'],
            $data['deskripsi_kerusakan'],
            $data['keterangan'],
            $data['kategori_service'],
            $data['harga_satuan'],
            $data['total_harga']
        );

        return $stmt->execute();
    }

    // ✅ Update
    public function update($id, $data) {

        $stmt = $this->conn->prepare("
            UPDATE service_truck SET
                nomor=?, nomor_pbs=?, tanggal=?, gudang=?, no_pol=?, nama_truk=?,
                nama_supir=?, odo_meter=?, status=?, nama_barang=?, jumlah=?,
                satuan=?, masa_pakai_km=?, penggantian_sebelumnya_tgl=?,
                penggantian_sebelumnya_km=?, deskripsi_kerusakan=?,
                keterangan=?, kategori_service=?, harga_satuan=?, total_harga=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "ssssssssssississssddi",
            $data['nomor'],
            $data['nomor_pbs'],
            $data['tanggal'],
            $data['gudang'],
            $data['no_pol'],
            $data['nama_truk'],
            $data['nama_supir'],
            $data['odo_meter'],
            $data['status'],
            $data['nama_barang'],
            $data['jumlah'],
            $data['satuan'],
            $data['masa_pakai_km'],
            $data['penggantian_sebelumnya_tgl'],
            $data['penggantian_sebelumnya_km'],
            $data['deskripsi_kerusakan'],
            $data['keterangan'],
            $data['kategori_service'],
            $data['harga_satuan'],
            $data['total_harga'],
            $id
        );

        return $stmt->execute();
    }

    // ✅ Delete
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM service_truck WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    // ✅ Filter berdasarkan range tanggal
public function getByDateRange($start, $end) {

    $stmt = $this->conn->prepare("
        SELECT * FROM service_truck 
        WHERE tanggal BETWEEN ? AND ?
        ORDER BY tanggal DESC
    ");

    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    return $stmt->get_result();
}


}
?>
