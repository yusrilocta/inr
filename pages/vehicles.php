<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/VehiclesModel.php';

$model = new VehiclesModel($conn);

$action = $_GET['action'] ?? null;

// =============================
// HANDLE ACTION
// =============================
if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->create($_POST);
    header("Location: index.php?page=vehicles");
    exit;
}

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->update($_GET['id'], $_POST);
    header("Location: index.php?page=vehicles");
    exit;
}

if ($action == 'delete') {
    $model->delete($_GET['id']);
    header("Location: index.php?page=vehicles");
    exit;
}

$data = $model->getAll();

include 'core/header.php';
?>

<div class="container-fluid py-4">

<div class="card shadow-lg border-0">
  <div class="card-header pb-0 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Data Vehicles</h5>

    <a href="index.php?page=vehicles&action=create" 
       class="btn bg-gradient-success btn-sm">
       <i class="fas fa-plus me-1"></i> Tambah Vehicle
    </a>
  </div>

  <div class="card-body px-0 pt-3 pb-2">
    <div class="table-responsive p-3">

      <!-- SEARCH -->
      <div class="row mb-3 px-3 justify-content-end">
        <div class="col-md-4">
          <input type="text" id="searchInput" 
                 class="form-control" 
                 placeholder="Cari vehicle...">
        </div>
      </div>

      <table id="vehicleTable" class="table align-items-center mb-0">
        <thead>
          <tr>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">id Kend</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">No pol</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Brand</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Model</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Type</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Driver</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Total KM</th>
            <th class="text-end text-secondary">Aksi</th>
          </tr>
        </thead>

        <tbody>
        <?php while($row = $data->fetch_assoc()): ?>
          <tr>
            <td><?= $row['vehicle_id'] ?></td>
            <td>
              <span class="badge bg-gradient-dark">
                <?= $row['nopol'] ?>
              </span>
            </td>

            <td><?= $row['brand'] ?></td>
            <td><?= $row['model'] ?></td>
            <td><?= $row['type'] ?></td>
            <td><?= $row['driver_nm'] ?></td>
            <td>
              <span class="badge bg-gradient-info">
                <?= $row['total_km'] ?>
              </span>
            </td>

            <td class="text-end">
              <a href="index.php?page=vehicles&action=edit&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-warning btn-sm">
                 Edit
              </a>

              <a href="index.php?page=vehicles&action=delete&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-danger btn-sm"
                 onclick="return confirm('Yakin hapus vehicle?')">
                 Hapus
              </a>
            </td>

          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>

      <div class="d-flex justify-content-between align-items-center mt-3 px-3">
        <div id="dataInfo" class="text-sm text-secondary"></div>
        <div id="pagination" class="btn-group"></div>
      </div>

    </div>
  </div>
</div>
</div>
<?php
if ($action === 'create' || $action === 'edit'):

$data_edit = [
    'nopol' => '',
    'brand' => '',
    'model' => '',
    'type' => '',
    'driver_nm' => '',
    'year_production' => ''
];

if ($action === 'edit') {
    $data_edit = $model->getById($_GET['id']);
}
?>

<style>
.vehicle-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.vehicle-overlay-card {
  width: min(800px, 100%);
}
</style>

<div class="vehicle-overlay">
  <div class="card shadow-lg border-0 vehicle-overlay-card">

    <div class="card-header d-flex justify-content-between">
      <h5><?= $action === 'create' ? 'Tambah Vehicle' : 'Edit Vehicle' ?></h5>
      <a href="index.php?page=vehicles" class="btn btn-sm btn-outline-secondary">Tutup</a>
    </div>

    <div class="card-body">
      <form method="POST" action="index.php?page=vehicles&action=<?= $action === 'edit' ? 'update&id='.$_GET['id'] : 'create' ?>">

        <div class="row">

          <div class="col-md-6 mb-3">
            <label>Nopol</label>
            <input type="text" name="nopol" class="form-control"
                   value="<?= $data_edit['nopol'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>Brand</label>
            <input type="text" name="brand" class="form-control"
                   value="<?= $data_edit['brand'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>Model</label>
            <input type="text" name="model" class="form-control"
                   value="<?= $data_edit['model'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>Type</label>
            <input type="text" name="type" class="form-control"
                   value="<?= $data_edit['type'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>Driver</label>
            <input type="text" name="driver_nm" class="form-control"
                   value="<?= $data_edit['driver_nm'] ?>">
          </div>
          
              <input type="hidden" name="total_km" class="form-control"
                    value="<?= $data_edit['total_km'] ?>">

          <div class="col-md-6 mb-3">
            <label>Tahun Produksi</label>
            <input type="text" name="year_production" class="form-control"
                   value="<?= $data_edit['year_production'] ?>">
          </div>

        </div>

        <div class="d-flex justify-content-between">
          <a href="index.php?page=vehicles" class="btn btn-outline-secondary">Kembali</a>
          <button type="submit" class="btn bg-gradient-primary">
            <?= $action === 'create' ? 'Simpan Vehicle' : 'Update Vehicle' ?>
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<?php endif; ?>
<script>
let rowsPerPage = 10;
let currentPage = 1;

const table = document.getElementById("vehicleTable");
const tbody = table.querySelector("tbody");
const rows = Array.from(tbody.querySelectorAll("tr"));

function displayTable() {

    const searchValue = document.getElementById("searchInput").value.toLowerCase();

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(searchValue) ? "" : "none";
    });

    const visibleRows = rows.filter(row => row.style.display !== "none");

    const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
    if(currentPage > totalPages) currentPage = totalPages || 1;

    visibleRows.forEach((row, index) => {
        row.style.display =
            (index >= (currentPage-1)*rowsPerPage && index < currentPage*rowsPerPage)
            ? ""
            : "none";
    });

    renderPagination(totalPages);
    renderInfo(visibleRows.length);
}

function renderPagination(totalPages) {
    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";

    if(totalPages <= 1) return;

    const prevBtn = document.createElement("button");
    prevBtn.className = "btn btn-sm btn-outline-primary";
    prevBtn.innerHTML = "&laquo;";
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = function(){
        currentPage--;
        displayTable();
    };

    const nextBtn = document.createElement("button");
    nextBtn.className = "btn btn-sm btn-outline-primary";
    nextBtn.innerHTML = "&raquo;";
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = function(){
        currentPage++;
        displayTable();
    };

    pagination.appendChild(prevBtn);
    pagination.appendChild(nextBtn);
}

function renderInfo(totalData) {
    const start = (currentPage - 1) * rowsPerPage + 1;
    const end = Math.min(currentPage * rowsPerPage, totalData);

    document.getElementById("dataInfo").innerHTML =
        `Menampilkan ${start} - ${end} dari ${totalData} data`;
}

document.getElementById("searchInput").addEventListener("keyup", function(){
    currentPage = 1;
    displayTable();
});

displayTable();
</script>
<?php
include 'core/footer.php';
?>