<?php
require_once __DIR__ . '/../model/PelangganModel.php';

$pelangganModel = new PelangganModel($conn);

$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? null;

/* ================================
   HANDLE INSERT
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $pelangganModel->create($_POST);
    header("Location: index.php?page=pelanggan");
    exit;
}

/* ================================
   HANDLE UPDATE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $pelangganModel->update($id, $_POST);
    header("Location: index.php?page=pelanggan");
    exit;
}

/* ================================
   HANDLE DELETE
================================ */
if ($action === 'delete' && $id) {
    $pelangganModel->delete($id);
    header("Location: index.php?page=pelanggan");
    exit;
}

/* ================================
   AMBIL DATA EDIT
================================ */
$data_edit = null;
if ($action === 'edit' && $id) {
    $data_edit = $pelangganModel->getById($id);
}

/* ================================
   LIST DATA
================================ */
$data = $pelangganModel->getAll();

include 'core/header.php';
?>

<div class="container-fluid py-4">

<div class="card">
  <div class="card-header d-flex justify-content-between">
    <h5>Data Pelanggan</h5>
    <a href="index.php?page=pelanggan&action=create" 
       class="btn bg-gradient-success">+ Tambah</a>
  </div>

  <div class="table-responsive p-3">
    <table class="table align-items-center mb-0">
      <thead>
        <tr>
          <th>Nomor</th>
          <th>No Polisi</th>
          <th>Nama Supir</th>
          <th>Nama Truk</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>

      <?php while($row = $data->fetch_assoc()): ?>
        <tr>
          <td><?= $row['nomor'] ?></td>
          <td><?= $row['no_pol'] ?></td>
          <td><?= $row['nama_supir'] ?></td>
          <td><?= $row['nama_truk'] ?></td>
          <td><?= $row['status'] ?></td>
          <td>
            <a href="index.php?page=pelanggan&action=edit&id=<?= $row['id'] ?>" 
               class="btn btn-warning btn-sm">Edit</a>

            <a href="index.php?page=pelanggan&action=delete&id=<?= $row['id'] ?>" 
               class="btn btn-danger btn-sm"
               onclick="return confirm('Yakin hapus?')">Hapus</a>
          </td>
        </tr>
      <?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>

</div>

<!-- ================================
     FORM CREATE & EDIT
================================ -->
<?php if ($action === 'create' || $action === 'edit'): ?>

<div class="container-fluid py-4">
<div class="card">
  <div class="card-header">
    <h5><?= $action === 'create' ? 'Tambah Pelanggan' : 'Edit Pelanggan' ?></h5>
  </div>
  <div class="card-body">

<form method="POST">
<div class="row">

<div class="col-md-6">
  <label>Nomor</label>
  <input type="text" name="nomor" class="form-control"
         value="<?= $data_edit['nomor'] ?? '' ?>" required>
</div>

<div class="col-md-6">
  <label>Nomor PBS</label>
  <input type="text" name="nomor_pbs" class="form-control"
         value="<?= $data_edit['nomor_pbs'] ?? '' ?>">
</div>

<div class="col-md-6">
  <label>Tanggal</label>
  <input type="date" name="tanggal" class="form-control"
         value="<?= $data_edit['tanggal'] ?? '' ?>">
</div>

<div class="col-md-6">
  <label>Gudang</label>
  <input type="text" name="gudang" class="form-control"
         value="<?= $data_edit['gudang'] ?? '' ?>">
</div>

<div class="col-md-6">
  <label>No Polisi</label>
  <input type="text" name="no_pol" class="form-control"
         value="<?= $data_edit['no_pol'] ?? '' ?>">
</div>

<div class="col-md-6">
  <label>Nama Truk</label>
  <input type="text" name="nama_truk" class="form-control"
         value="<?= $data_edit['nama_truk'] ?? '' ?>">
</div>

<div class="col-md-6">
  <label>Nama Supir</label>
  <input type="text" name="nama_supir" class="form-control"
         value="<?= $data_edit['nama_supir'] ?? '' ?>">
</div>

<div class="col-md-6">
  <label>Odo Meter</label>
  <input type="number" name="odo_meter" class="form-control"
         value="<?= $data_edit['odo_meter'] ?? '' ?>">
</div>

<div class="col-md-6">
  <label>Status</label>
  <select name="status" class="form-control">
    <option value="aktif" <?= ($data_edit['status'] ?? '') == 'aktif' ? 'selected' : '' ?>>Aktif</option>
    <option value="nonaktif" <?= ($data_edit['status'] ?? '') == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
  </select>
</div>

</div>

<br>

<button type="submit" class="btn bg-gradient-primary">
  <?= $action === 'create' ? 'Simpan' : 'Update' ?>
</button>

<a href="index.php?page=pelanggan" class="btn btn-secondary">Kembali</a>

</form>

  </div>
</div>
</div>

<?php endif; ?>

<?php include 'core/footer.php'; ?>
