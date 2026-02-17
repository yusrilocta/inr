<?php 

require_once __DIR__ . '/../model/LaporanModel.php';

$laporanModel = new LaporanModel($conn);
// ======================
// PAGINATION SETTING
// ======================
$limit = 20;
$page  = $_GET['p'] ?? 1;
$page  = max(1, (int)$page);
$offset = ($page - 1) * $limit;

$totalData = $laporanModel->getTotalData();
$totalPages = ceil($totalData / $limit);

$data = $laporanModel->getWithPagination($limit, $offset);

$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? null;

// ======================
// HANDLE CREATE
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $result = $laporanModel->create($_POST);

    if(!$result){
        echo "<script>alert('Stok barang habis!');</script>";
    } else {
        header("Location: index.php?page=laporan_perbaikan");
        exit;
    }
}

// ======================
// HANDLE UPDATE
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $laporanModel->update($id, $_POST);
    header("Location: index.php?page=laporan_perbaikan");
    exit;
}

// ======================
// HANDLE DELETE
// ======================
if ($action === 'delete' && $id) {
    $laporanModel->delete($id);
    header("Location: index.php?page=laporan_perbaikan");
    exit;
}
// ======================
// PAGINATION SETTING
// ======================
$limit = 20;
$page  = $_GET['p'] ?? 1;
$page  = max(1, (int)$page);
$offset = ($page - 1) * $limit;

$totalData = $laporanModel->getTotalData();
$totalPages = ceil($totalData / $limit);

$data = $laporanModel->getWithPagination($limit, $offset);

$data_edit = null;

if ($action === 'edit' && $id) {
    $data_edit = $laporanModel->getById($id);
}

$pelanggan = $laporanModel->getPelanggan();
$barang    = $laporanModel->getBarang();

include 'core/header.php';
?>

<div class="container-fluid py-4 mt-4">
<div class="card">

  <div class="card-header d-flex justify-content-between">
    <h5>Laporan Perbaikan</h5>
    <a href="index.php?page=laporan_perbaikan&action=create" 
       class="btn bg-gradient-success btn-sm">
       + Tambah Laporan
    </a>
  </div>

  <div class="table-responsive">
    <table class="table align-items-center mb-0">
      <thead>
        <tr>
          <th>Pelanggan</th>
          <th>Barang</th>
          <th>Kategori</th>
          <th>Total Harga</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>

      <?php while($row = $data->fetch_assoc()): ?>
        <tr>
          <td><?= $row['nama_supir'] ?></td>
          <td><?= $row['nama_barang'] ?></td>
          <td><?= $row['kategori_service'] ?></td>
          <td>Rp <?= number_format($row['total_harga']) ?></td>
          <td>
            <a href="index.php?page=laporan_perbaikan&action=edit&id=<?= $row['id'] ?>" 
               class="btn btn-warning btn-sm">Edit</a>

            <a href="index.php?page=laporan_perbaikan&action=delete&id=<?= $row['id'] ?>" 
               class="btn btn-danger btn-sm"
               onclick="return confirm('Yakin hapus?')">
               Hapus
            </a>
          </td>
        </tr>
      <?php endwhile; ?>

      </tbody>
    </table>
 

  </div>
</div>
</div>

<!-- ================= FORM CREATE & EDIT ================= -->

<?php if ($action === 'create' || $action === 'edit'): ?>

<div class="card mt-4">
<div class="card-header">
  <h5><?= $action === 'create' ? 'Tambah Laporan' : 'Edit Laporan' ?></h5>
</div>

<div class="card-body">
<form method="POST">

<div class="row">

<!-- PELANGGAN -->
<div class="col-md-6">
  <label>Pelanggan</label>
  <select name="id_pelanggan" class="form-control" required>
    <option value="">-- Pilih Pelanggan --</option>
    <?php while($p = $pelanggan->fetch_assoc()): ?>
      <option value="<?= $p['id'] ?>"
        <?= ($data_edit['id_pelanggan'] ?? '') == $p['id'] ? 'selected' : '' ?>>
        <?= $p['nama_supir'] ?>
      </option>
    <?php endwhile; ?>
  </select>
</div>

<!-- BARANG -->
<div class="col-md-6">
  <label>Barang</label>
  <select name="id_barang" class="form-control" required>
    <option value="">-- Pilih Barang --</option>
    <?php while($b = $barang->fetch_assoc()): ?>
      <option value="<?= $b['id'] ?>"
        <?= ($data_edit['id_barang'] ?? '') == $b['id'] ? 'selected' : '' ?>>
        <?= $b['nama_barang'] ?> (Stok: <?= $b['stok'] ?>)
      </option>
    <?php endwhile; ?>
  </select>
</div>

<div class="col-md-6 mt-3">
  <label>Kategori Service</label>
  <input type="text" name="kategori_service" class="form-control"
    value="<?= $data_edit['kategori_service'] ?? '' ?>">
</div>

<div class="col-md-6 mt-3">
  <label>Tanggal Penggantian Sebelumnya</label>
  <input type="date" name="penggantian_sebelumnya_tgl" class="form-control"
    value="<?= $data_edit['penggantian_sebelumnya_tgl'] ?? '' ?>">
</div>

<div class="col-md-6 mt-3">
  <label>KM Penggantian Sebelumnya</label>
  <input type="number" name="penggantian_sebelumnya_km" class="form-control"
    value="<?= $data_edit['penggantian_sebelumnya_km'] ?? '' ?>">
</div>

<div class="col-md-6 mt-3">
  <label>Harga Satuan</label>
  <input type="number" name="harga_satuan" id="harga_satuan" class="form-control"
    value="<?= $data_edit['harga_satuan'] ?? 0 ?>">
</div>

<div class="col-md-6 mt-3">
  <label>Total Harga</label>
  <input type="number" name="total_harga" id="total_harga" class="form-control"
    value="<?= $data_edit['total_harga'] ?? 0 ?>" readonly>
</div>

<div class="col-md-12 mt-3">
  <label>Deskripsi Kerusakan</label>
  <textarea name="deskripsi_kerusakan" class="form-control"><?= $data_edit['deskripsi_kerusakan'] ?? '' ?></textarea>
</div>

<div class="col-md-12 mt-3">
  <label>Keterangan</label>
  <textarea name="keterangan" class="form-control"><?= $data_edit['keterangan'] ?? '' ?></textarea>
</div>

</div>

<br>

<button type="submit" class="btn bg-gradient-primary">
  <?= $action === 'create' ? 'Simpan' : 'Update' ?>
</button>

<a href="index.php?page=laporan_perbaikan" class="btn btn-secondary">Kembali</a>

</form>
</div>
</div>

<script>
document.getElementById('harga_satuan').addEventListener('input', function(){
    let harga = parseFloat(this.value) || 0;
    document.getElementById('total_harga').value = harga;
});
</script>

<?php return; endif; ?>

<?php include 'core/footer.php'; ?>
