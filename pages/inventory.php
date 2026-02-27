<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/InventoriModel.php';

$model = new InventoriModel($conn);

$action = $_GET['action'] ?? null;

/* =============================
   HANDLE ACTION
============================= */
if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->create($_POST);
    header("Location: index.php?page=inventory");
    exit;
}

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->update($_GET['id'], $_POST);
    header("Location: index.php?page=inventory");
    exit;
}

if ($action == 'delete') {
    $model->delete($_GET['id']);
    header("Location: index.php?page=inventory");
    exit;
}

$data = $model->getAll();
$stokMenipis = $model->getStokMenipis();

include 'core/header.php';
?>

<div class="container-fluid py-4">

<div class="card shadow-lg border-0">
  <div class="card-header pb-0 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Data Inventory</h5>

    <a href="index.php?page=inventory&action=create" 
       class="btn bg-gradient-success btn-sm">
       <i class="fas fa-plus me-1"></i> Tambah Barang
    </a>
  </div>

  <div class="card-body px-0 pt-3 pb-2">
    <div class="table-responsive p-3">

      <!-- ALERT STOK MENIPIS -->
      <?php if(count($stokMenipis) > 0): ?>
        <div class="alert alert-warning">
          ⚠ Ada <?= count($stokMenipis) ?> barang stok menipis!
        </div>
      <?php endif; ?>

      <!-- SEARCH -->
      <div class="row mb-3 px-3 justify-content-end">
        <div class="col-md-4">
          <input type="text" id="searchInput" 
                 class="form-control" 
                 placeholder="Cari barang...">
        </div>
      </div>

      <table id="inventoryTable" class="table align-items-center mb-0">
        <thead>
          <tr>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Nama</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Kategori</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Stok</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Harga</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Masa Pakai</th>
            <th class="text-end text-secondary">Aksi</th>
          </tr>
        </thead>

        <tbody>
        <?php foreach($data as $row): ?>
          <tr>
            <td><?= $row['nama'] ?></td>

            <td>
              <span class="badge bg-gradient-info">
                <?= $row['kategori'] ?>
              </span>
            </td>

            <td>
              <?php if($row['stok'] <= $row['peringatan_stok']): ?>
                <span class="badge bg-gradient-danger">
                  <?= $row['stok'] ?>
                </span>
              <?php else: ?>
                <span class="badge bg-gradient-success">
                  <?= $row['stok'] ?>
                </span>
              <?php endif; ?>
            </td>

            <td>Rp <?= number_format($row['harga_satuan'],0,',','.') ?></td>

            <td>
              <?= $row['masa_pakai'] ?> KM
            </td>

            <td class="text-end">
              <a href="index.php?page=inventory&action=edit&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-warning btn-sm">
                 Edit
              </a>

              <a href="index.php?page=inventory&action=delete&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-danger btn-sm"
                 onclick="return confirm('Yakin hapus barang?')">
                 Hapus
              </a>
            </td>

          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <!-- PAGINATION -->
      <div class="d-flex justify-content-between align-items-center mt-3 px-3">
        <div id="dataInfo" class="text-sm text-secondary"></div>
        <div>
          <button class="btn btn-outline-secondary btn-sm" id="prevBtn">←</button>
          <button class="btn btn-outline-secondary btn-sm" id="nextBtn">→</button>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

<?php
if ($action === 'create' || $action === 'edit'):

$data_edit = [
    'nama' => '',
    'kategori' => '',
    'stok' => '',
    'peringatan_stok' => '',
    'harga_satuan' => '',
    'masa_pakai' => ''
];

if ($action === 'edit') {
    $data_edit = $model->getById($_GET['id']);
}
?>

<style>
.inventory-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.inventory-overlay-card {
  width: min(800px, 100%);
}
</style>

<div class="inventory-overlay">
  <div class="card shadow-lg border-0 inventory-overlay-card">

    <div class="card-header d-flex justify-content-between align-items-center">
      <h5><?= $action === 'create' ? 'Tambah Barang Inventory' : 'Edit Barang Inventory' ?></h5>
      <a href="index.php?page=inventory" class="btn btn-sm btn-outline-secondary">
        Tutup
      </a>
    </div>

    <div class="card-body">
      <form method="POST" 
            action="index.php?page=inventory&action=<?= $action === 'edit' ? 'update&id='.$_GET['id'] : 'create' ?>">

        <div class="row">

          <div class="col-md-6 mb-3">
            <label>Nama Barang</label>
            <input type="text" name="nama" class="form-control"
                   value="<?= $data_edit['nama'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label>Kategori</label>
            <input type="text" name="kategori" class="form-control"
                   value="<?= $data_edit['kategori'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>Stok</label>
            <input type="number" name="stok" class="form-control"
                   value="<?= $data_edit['stok'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label>Peringatan Stok (Minimum)</label>
            <input type="number" name="peringatan_stok" class="form-control"
                   value="<?= $data_edit['peringatan_stok'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>Harga Satuan</label>
            <input type="number" name="harga_satuan" class="form-control"
                   value="<?= $data_edit['harga_satuan'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label>Masa Pakai (KM)</label>
            <input type="number" name="masa_pakai" class="form-control"
                   value="<?= $data_edit['masa_pakai'] ?>">
          </div>

        </div>

        <div class="d-flex justify-content-between">
          <a href="index.php?page=inventory" class="btn btn-outline-secondary">
            Kembali
          </a>

          <button type="submit" class="btn bg-gradient-primary">
            <?= $action === 'create' ? 'Simpan Barang' : 'Update Barang' ?>
          </button>
        </div>

      </form>
    </div>

  </div>
</div>

<?php endif; ?>


<script>
let rows = document.querySelectorAll("#inventoryTable tbody tr");
let currentPage = 1;
let rowsPerPage = 5;

function displayTable() {
  let start = (currentPage - 1) * rowsPerPage;
  let end = start + rowsPerPage;

  rows.forEach((row, index) => {
    row.style.display = (index >= start && index < end) ? "" : "none";
  });

  document.getElementById("dataInfo").innerText =
    "Menampilkan " + (start + 1) + " - " + 
    Math.min(end, rows.length) + 
    " dari " + rows.length + " data";
}

document.getElementById("prevBtn").addEventListener("click", function(){
  if(currentPage > 1){
    currentPage--;
    displayTable();
  }
});

document.getElementById("nextBtn").addEventListener("click", function(){
  if(currentPage < Math.ceil(rows.length / rowsPerPage)){
    currentPage++;
    displayTable();
  }
});

document.getElementById("searchInput").addEventListener("keyup", function(){
  let value = this.value.toLowerCase();
  rows.forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
  });
});

displayTable();
</script>

<?php include 'core/footer.php'; ?>