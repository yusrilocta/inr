<?php
require_once __DIR__ . '/../db/db.php';
class LaporanModel {
    private $conn;
    private $table = "laporan_perbaikan";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ==========================
    // GET ALL (JOIN)
    // ==========================
public function getAll() {
    $sql = "SELECT lp.*, 
                   p.nama_supir,
                   p.no_pol,
                   b.nama_barang
            FROM laporan_perbaikan lp
            JOIN pelanggan p ON lp.id_pelanggan = p.id
            JOIN stok_barang b ON lp.id_barang = b.id
            ORDER BY lp.id DESC";

    return $this->conn->query($sql);
}

    // ==========================
    // GET BY ID
    // ==========================
    public function getById($id) {
        $id = (int)$id;

        $sql = "SELECT * FROM {$this->table} WHERE id = $id";
        $result = $this->conn->query($sql);

        return $result->fetch_assoc();
    }

    // ==========================
    // CREATE + POTONG STOK
    // ==========================
    public function create($data) {

        $id_pelanggan = (int)$data['id_pelanggan'];
        $id_barang    = (int)$data['id_barang'];
        $tgl          = $data['penggantian_sebelumnya_tgl'];
        $km           = (int)$data['penggantian_sebelumnya_km'];
        $deskripsi    = $this->conn->real_escape_string($data['deskripsi_kerusakan']);
        $keterangan   = $this->conn->real_escape_string($data['keterangan']);
        $kategori     = $this->conn->real_escape_string($data['kategori_service']);
        $harga        = (float)$data['harga_satuan'];
        $total        = (float)$data['total_harga'];

        // CEK STOK
        $cek = $this->conn->query("SELECT stok FROM stok_barang WHERE id = $id_barang");
        $stokData = $cek->fetch_assoc();

        if (!$stokData || $stokData['stok'] <= 0) {
            return false; // stok habis
        }

        // INSERT LAPORAN
        $sql = "INSERT INTO {$this->table}
                (id_pelanggan, id_barang, 
                 penggantian_sebelumnya_tgl,
                 penggantian_sebelumnya_km,
                 deskripsi_kerusakan,
                 keterangan,
                 kategori_service,
                 harga_satuan,
                 total_harga)
                VALUES
                ($id_pelanggan, $id_barang,
                 '$tgl',
                 $km,
                 '$deskripsi',
                 '$keterangan',
                 '$kategori',
                 $harga,
                 $total)";

        $insert = $this->conn->query($sql);

        if ($insert) {
            // POTONG STOK 1
            $this->conn->query("UPDATE stok_barang 
                                SET stok = stok - 1 
                                WHERE id = $id_barang");
        }

        return $insert;
    }

    // ==========================
    // UPDATE
    // ==========================
    public function update($id, $data) {
        $id = (int)$id;

        $id_pelanggan = (int)$data['id_pelanggan'];
        $id_barang    = (int)$data['id_barang'];
        $tgl          = $data['penggantian_sebelumnya_tgl'];
        $km           = (int)$data['penggantian_sebelumnya_km'];
        $deskripsi    = $this->conn->real_escape_string($data['deskripsi_kerusakan']);
        $keterangan   = $this->conn->real_escape_string($data['keterangan']);
        $kategori     = $this->conn->real_escape_string($data['kategori_service']);
        $harga        = (float)$data['harga_satuan'];
        $total        = (float)$data['total_harga'];

        $sql = "UPDATE {$this->table} SET
                id_pelanggan = $id_pelanggan,
                id_barang = $id_barang,
                penggantian_sebelumnya_tgl = '$tgl',
                penggantian_sebelumnya_km = $km,
                deskripsi_kerusakan = '$deskripsi',
                keterangan = '$keterangan',
                kategori_service = '$kategori',
                harga_satuan = $harga,
                total_harga = $total
                WHERE id = $id";

        return $this->conn->query($sql);
    }

    // ==========================
    // DELETE
    // ==========================
    public function delete($id) {
        $id = (int)$id;
        return $this->conn->query("DELETE FROM {$this->table} WHERE id = $id");
    }

    // ==========================
    // DROPDOWN PELANGGAN
    // ==========================
    public function getPelanggan() {
        return $this->conn->query("SELECT id, nama_supir FROM pelanggan ORDER BY nama_supir ASC");
    }

    // ==========================
    // DROPDOWN BARANG
    // ==========================
    public function getBarang() {
        return $this->conn->query("SELECT id, nama_barang, stok FROM stok_barang ORDER BY nama_barang ASC");
    }

    // ==========================
// GET TOTAL DATA
// ==========================
public function getTotalData() {
    $result = $this->conn->query("SELECT COUNT(*) as total FROM {$this->table}");
    $row = $result->fetch_assoc();
    return $row['total'];
}

// ==========================
// GET WITH PAGINATION
// ==========================
public function getWithPagination($limit, $offset) {

    $limit = (int)$limit;
    $offset = (int)$offset;

    $sql = "SELECT lp.*, 
                   p.nama_supir AS nama_supir,
                   b.nama_barang
            FROM {$this->table} lp
            JOIN pelanggan p ON lp.id_pelanggan = p.id
            JOIN stok_barang b ON lp.id_barang = b.id
            ORDER BY lp.id DESC
            LIMIT $limit OFFSET $offset";

    return $this->conn->query($sql);
}

}
