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

$search = trim($_GET['search'] ?? '');
$stokFilter = trim($_GET['stok_filter'] ?? '');
if (!in_array($stokFilter, ['aman', 'peringatan'], true)) {
    $stokFilter = '';
}
$stokFilterLabel = $stokFilter === 'aman' ? 'Stok Aman' : ($stokFilter === 'peringatan' ? 'Peringatan Stok' : '');
$kategoriOptions = $model->getKategoriSummary();
$kategoriFilter = trim($_GET['kategori'] ?? '');
$kategoriValues = array_column($kategoriOptions, 'kategori');
if ($kategoriFilter !== '' && !in_array($kategoriFilter, $kategoriValues, true)) {
    $kategoriFilter = '';
}
$exportQuery = http_build_query([
    'search' => $search,
    'stok_filter' => $stokFilter,
    'kategori' => $kategoriFilter
]);

$data = $model->getAll($search, $stokFilter, $kategoriFilter);
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

      <?php if ($search !== '' || $stokFilter !== '' || $kategoriFilter !== ''): ?>
        <div class="alert alert-info mb-3">
          Menampilkan
          <?php if ($search !== ''): ?>
            pencarian <strong><?= htmlspecialchars($search) ?></strong>
          <?php endif; ?>
          <?php if ($stokFilter !== ''): ?>
            <?= $search !== '' ? 'dan' : '' ?> filter <strong><?= htmlspecialchars($stokFilterLabel) ?></strong>
          <?php endif; ?>
          <?php if ($kategoriFilter !== ''): ?>
            <?= ($search !== '' || $stokFilter !== '') ? 'dan' : '' ?> kategori <strong><?= htmlspecialchars($kategoriFilter) ?></strong>
          <?php endif; ?>
          <a href="index.php?page=inventory" class="btn btn-sm btn-outline-secondary ms-2">Reset</a>
        </div>
      <?php endif; ?>

      <!-- SEARCH -->
      <div class="d-flex flex-nowrap justify-content-end align-items-center gap-2 px-1 mb-3 overflow-auto">
        <form method="GET" class="d-flex flex-nowrap align-items-center gap-2 m-0">
          <input type="hidden" name="page" value="inventory">
          <select name="kategori" class="form-control" style="min-width:190px;">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriOptions as $opt): ?>
              <option value="<?= htmlspecialchars($opt['kategori']) ?>" <?= $kategoriFilter === $opt['kategori'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($opt['kategori']) ?> (<?= (int)$opt['total'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <select name="stok_filter" class="form-control" style="min-width:190px;">
            <option value="">Semua Stok</option>
            <option value="aman" <?= $stokFilter === 'aman' ? 'selected' : '' ?>>Stok Aman</option>
            <option value="peringatan" <?= $stokFilter === 'peringatan' ? 'selected' : '' ?>>Peringatan Stok</option>
          </select>
          <input type="text" name="search" id="searchInput"
                 class="form-control"
                 placeholder="Cari barang..."
                 value="<?= htmlspecialchars($search) ?>"
                 style="min-width:260px;">
          <button class="btn btn-outline-primary mt-3" type="submit"><i class="fas fa-search"></i></button>
        </form>

        <a href="index.php?page=inventory_export&<?= $exportQuery ?>" class="btn btn-sm btn-outline-success mt-3" target="_blank">
          <i class="fas fa-file-excel"></i> Export Excel
        </a>
      </div>

      <table id="inventoryTable" class="table align-items-center mb-0">
        <thead>
          <tr>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Nama</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Kategori</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Stok</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Harga</th>            
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Peringatan Stok</th>
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
                <span class="badge bg-gradient-danger js-stok-badge"
                      data-stok="<?= $row['stok'] ?>"
                      data-peringatan="<?= $row['peringatan_stok'] ?>">
                  <?= $row['stok'] ?>
                </span>
              <?php else: ?>
                <span class="badge bg-gradient-success js-stok-badge"
                      data-stok="<?= $row['stok'] ?>"
                      data-peringatan="<?= $row['peringatan_stok'] ?>">
                  <?= $row['stok'] ?>
                </span>
              <?php endif; ?>
            </td>

            <td>Rp <?= number_format($row['harga_satuan'],0,',','.') ?></td>
             <td>
              <?= $row['peringatan_stok'] ?>
            </td>
            <td>
              <?= $row['masa_pakai'] ?> KM
            </td>

            <td class="text-end">
              <a href="index.php?page=inventory&action=edit&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-warning">
                 <i class="fas fa-edit"></i>
              </a>

              <a href="index.php?page=inventory&action=delete&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-danger"
                 onclick="return confirm('Yakin hapus barang?')">
                 <i class="fas fa-trash"></i>
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

displayTable();

// Client-side warning icon when stock is below threshold
document.querySelectorAll(".js-stok-badge").forEach((badge) => {
  const stok = parseInt(badge.dataset.stok, 10);
  const peringatan = parseInt(badge.dataset.peringatan, 10);
  if (Number.isNaN(stok) || Number.isNaN(peringatan)) return;

  if (stok < peringatan && !badge.nextElementSibling?.classList.contains("stok-warning-icon")) {
    const icon = document.createElement("i");
    icon.className = "fas fa-exclamation-triangle text-warning ms-1 stok-warning-icon";
    icon.title = "Stok menipis";
    badge.insertAdjacentElement("afterend", icon);
  }
});
</script>

<?php include 'core/footer.php'; ?>
