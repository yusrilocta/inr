<?php 

require_once __DIR__ . '/../model/BarangModel.php';

$barangModel = new BarangModel($conn);

$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? null;

// ======================
// HANDLE CREATE
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $barangModel->create($_POST);
    header("Location: index.php?page=barang");
    exit;
}

// ======================
// HANDLE UPDATE
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $barangModel->update($id, $_POST);
    header("Location: index.php?page=barang");
    exit;
}

// ======================
// HANDLE DELETE
// ======================
if ($action === 'delete' && $id) {
    $barangModel->delete($id);
    header("Location: index.php?page=barang");
    exit;
}

// ======================
// AMBIL DATA
// ======================
$data = $barangModel->getAll();

$data_edit = null;
if ($action === 'edit' && $id) {
    $data_edit = $barangModel->getById($id);
}
include 'core/header.php';
?>

<div class="container-fluid py-4 mt-4">
<div class="card">

  <div class="card-header d-flex justify-content-between">
    <h5>Data Barang</h5>
    <a href="index.php?page=barang&action=create" 
       class="btn bg-gradient-success btn-sm">
       + Tambah Barang
    </a>
  </div>

  <div class="table-responsive p-3">
    <table class="table align-items-center mb-0">
      <thead>
        <tr>
          <th>Nama Barang</th>
          <th>Jumlah</th>
          <th>Satuan</th>
          <th>Masa Pakai (KM)</th>
          <th>Stok</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>

      <?php while($row = $data->fetch_assoc()): ?>
        <tr>
          <td><?= $row['nama_barang'] ?></td>
          <td><?= $row['jumlah'] ?></td>
          <td><?= $row['satuan'] ?></td>
          <td><?= number_format($row['masa_pakai_km']) ?></td>
          <td>
            <?php if($row['stok'] <= 5): ?>
              <span class="badge bg-danger"><?= $row['stok'] ?></span>
            <?php else: ?>
              <span class="badge bg-success"><?= $row['stok'] ?></span>
            <?php endif; ?>
          </td>
          <td>
            <a href="index.php?page=barang&action=edit&id=<?= $row['id'] ?>" 
               class="btn btn-warning btn-sm">Edit</a>

            <a href="index.php?page=barang&action=delete&id=<?= $row['id'] ?>" 
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

<!-- ========================= -->
<!-- FORM CREATE & EDIT -->
<!-- ========================= -->
<?php if ($action === 'create' || $action === 'edit'): ?>

<div class="card mt-4">
  <div class="card-header">
    <h5><?= $action === 'create' ? 'Tambah Barang' : 'Edit Barang' ?></h5>
  </div>
  <div class="card-body">

<form method="POST">

<div class="row">

<div class="col-md-6">
  <div class="form-group">
    <label>Nama Barang</label>
    <input type="text" name="nama_barang" class="form-control"
      value="<?= $data_edit['nama_barang'] ?? '' ?>" required>
  </div>
</div>

<div class="col-md-3">
  <div class="form-group">
    <label>Jumlah</label>
    <input type="number" name="jumlah" class="form-control"
      value="<?= $data_edit['jumlah'] ?? 0 ?>" required>
  </div>
</div>

<div class="col-md-3">
  <div class="form-group">
    <label>Satuan</label>
    <input type="text" name="satuan" class="form-control"
      value="<?= $data_edit['satuan'] ?? '' ?>" required>
  </div>
</div>

<div class="col-md-6">
  <div class="form-group">
    <label>Masa Pakai (KM)</label>
    <input type="number" name="masa_pakai_km" class="form-control"
      value="<?= $data_edit['masa_pakai_km'] ?? 0 ?>">
  </div>
</div>

<div class="col-md-6">
  <div class="form-group">
    <label>Stok</label>
    <input type="number" name="stok" class="form-control"
      value="<?= $data_edit['stok'] ?? 0 ?>" required>
  </div>
</div>

</div>

<br>

<button type="submit" class="btn bg-gradient-primary">
  <?= $action === 'create' ? 'Simpan' : 'Update' ?>
</button>

<a href="index.php?page=barang" class="btn btn-secondary">Kembali</a>

</form>

  </div>
</div>

<?php endif; ?>

<?php include 'core/footer.php'; ?>