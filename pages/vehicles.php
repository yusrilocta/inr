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

<style>
.vehicle-overlaya {
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
             <th class="text-xs text-uppercase text-secondary font-weight-bolder">Service Terakhir</th>            
             <th class="text-xs text-uppercase text-secondary font-weight-bolder">KM Service</th>
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

            <td><?= !empty($row['brand']) ? $row['brand'] : '?' ?></td>
            <td><?= !empty($row['model']) ? $row['model'] : '?' ?></td>
            <td><?= !empty($row['type']) ? $row['type'] : '?' ?></td>
            <td><?= !empty($row['driver_nm']) ? $row['driver_nm'] : '?' ?></td>
            <td>
              <span class="badge bg-gradient-info">
                <?= !empty($row['total_km']) ? $row['total_km'] : '?' ?>
              </span>
            </td>
              <td><?= !empty($row['last_service']) ? $row['last_service'] : '?' ?></td>
              <td><?= $row['total_km']-$row['last_km_service'] ?></td>
            <td class="text-end">
              <button class="btn btn-outline-info btn-sm" onclick="showDetailModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                Detail
              </button>

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
    'year_production' => '',
    'last_service' => '',
    'last_km_service' => ''
];

if ($action === 'edit') {
    $data_edit = $model->getById($_GET['id']);
}
?>

<style>
.vehicle-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.45);
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
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
          
              <input type="text" name="total_km" class="form-control" value="<?= !isset($data_edit['total_km']) ? 0 : $data_edit['total_km'] ?>">

          <div class="col-md-6 mb-3">
            <label>Tahun Produksi</label>
            <input type="text" name="year_production" class="form-control"
                   value="<?= $data_edit['year_production'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>Tanggal Service Terakhir</label>
            <input type="date" name="last_service" class="form-control"
                   value="<?= $data_edit['last_service'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>KM Service Terakhir</label>
            <input type="number" name="last_km_service" class="form-control"
                   value="<?= $data_edit['last_km_service'] ?>">
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

<!-- DETAIL MODAL -->
<div id="detailModalOverlay" class="vehicle-overlaya" style="display: none;">
  <div class="card shadow-lg border-0 vehicle-overlay-card">

    <div class="card-header d-flex justify-content-between">
      <h5 id="detailTitle" class="mb-0">Detail Vehicle</h5>
      <button class="btn btn-sm btn-outline-secondary" onclick="closeDetailModal()">Tutup</button>
    </div>

    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="text-muted">ID Kendaraan</label>
          <p id="detail_vehicle_id" class="fw-bold">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">No. Polisi</label>
          <p id="detail_nopol" class="fw-bold">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">GPS SN</label>
          <p id="detail_gps_sn">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Brand</label>
          <p id="detail_brand">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Model</label>
          <p id="detail_model">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Type</label>
          <p id="detail_type">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Nama Driver</label>
          <p id="detail_driver_nm">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Kelompok Kendaraan</label>
          <p id="detail_car_group">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Total KM</label>
          <p id="detail_total_km">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">No. Engine</label>
          <p id="detail_engine_no">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Kapasitas Engine</label>
          <p id="detail_engine_capacity">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">No. Chasis</label>
          <p id="detail_chasis_no">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">No. KIR</label>
          <p id="detail_kir_no">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">No. STNK</label>
          <p id="detail_stnk_no">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">No. BPKB</label>
          <p id="detail_bpkb_no">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Tahun Produksi</label>
          <p id="detail_year_production">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Tanggal Legal</label>
          <p id="detail_legal_date">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">Tanggal Service Terakhir</label>
          <p id="detail_last_service">-</p>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted">KM Service Terakhir</label>
          <p id="detail_last_km_service">-</p>
        </div>
        <div class="col-md-12 mb-3">
          <label class="text-muted">Catatan</label>
          <p id="detail_remark">-</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showDetailModal(rowData) {
  const formatValue = (val) => !val || val === '' || val === null ? '?' : val;
  
  document.getElementById('detail_vehicle_id').textContent = formatValue(rowData.vehicle_id);
  document.getElementById('detail_nopol').textContent = formatValue(rowData.nopol);
  document.getElementById('detail_gps_sn').textContent = formatValue(rowData.gps_sn);
  document.getElementById('detail_brand').textContent = formatValue(rowData.brand);
  document.getElementById('detail_model').textContent = formatValue(rowData.model);
  document.getElementById('detail_type').textContent = formatValue(rowData.type);
  document.getElementById('detail_driver_nm').textContent = formatValue(rowData.driver_nm);
  document.getElementById('detail_car_group').textContent = formatValue(rowData.car_group);
  document.getElementById('detail_total_km').textContent = formatValue(rowData.total_km);
  document.getElementById('detail_engine_no').textContent = formatValue(rowData.engine_no);
  document.getElementById('detail_engine_capacity').textContent = formatValue(rowData.engine_capacity);
  document.getElementById('detail_chasis_no').textContent = formatValue(rowData.chasis_no);
  document.getElementById('detail_kir_no').textContent = formatValue(rowData.kir_no);
  document.getElementById('detail_stnk_no').textContent = formatValue(rowData.stnk_no);
  document.getElementById('detail_bpkb_no').textContent = formatValue(rowData.bpkb_no);
  document.getElementById('detail_year_production').textContent = formatValue(rowData.year_production);
  document.getElementById('detail_legal_date').textContent = formatValue(rowData.legal_date);
  document.getElementById('detail_last_service').textContent = formatValue(rowData.last_service);
  document.getElementById('detail_last_km_service').textContent = formatValue(rowData.last_km_service);
  document.getElementById('detail_remark').textContent = formatValue(rowData.remark);
  
  document.getElementById('detailTitle').textContent = `Detail Vehicle - ${rowData.nopol || '?'}`;
  document.getElementById('detailModalOverlay').style.display = 'flex';
}

function closeDetailModal() {
  document.getElementById('detailModalOverlay').style.display = 'none';
}

document.getElementById('detailModalOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeDetailModal();
  }
});
</script>

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
