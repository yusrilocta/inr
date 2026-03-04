<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/RiwayatModel.php';

$model = new RiwayatModel($conn);

$action = $_GET['action'] ?? null;

if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->create($_POST);
    header("Location: index.php?page=riwayat");
    exit;
}

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->update($_GET['id'], $_POST);
    header("Location: index.php?page=riwayat");
    exit;
}

if ($action == 'delete') {
    $model->delete($_GET['id']);
    header("Location: index.php?page=riwayat");
    exit;
}

// server-side search query and optional filters
$search = trim($_GET['search'] ?? '');
$vehicleFilter = trim($_GET['vehicle_id'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd   = trim($_GET['date_end'] ?? '');
$exportQuery = http_build_query([
    'search' => $search,
    'vehicle_id' => $vehicleFilter,
    'date_start' => $dateStart,
    'date_end' => $dateEnd
]);
$canPrintInvoice = $search !== '' && ($dateStart !== '' || $dateEnd !== '');
// pass all filters to model
$data = $model->getAll($search, $vehicleFilter, $dateStart, $dateEnd);
$vehicleOptions = $model->getVehicleOptions();
$inventoriOptions = $model->getInventoriOptions();

include 'core/header.php';
?>

<style>
.riwayat-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.riwayat-overlay-card {
  width: min(950px, 100%);
}
</style>

<div class="container-fluid py-4">

<div class="card shadow-lg border-0">
  <div class="card-header pb-0 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Data Riwayat Service</h5>

    <a href="index.php?page=riwayat&action=create"
       class="btn bg-gradient-success btn-sm">
       <i class="fas fa-plus me-1"></i> Tambah Riwayat
    </a>
  </div>

  <div class="card-body px-0 pt-3 pb-2">
    <div class="table-responsive p-3">
      <?php if (!empty($vehicleFilter)): ?>
        <div class="alert alert-info mb-3">
          Menampilkan riwayat untuk vehicle <strong><?= htmlspecialchars($vehicleFilter) ?></strong>
          <a href="index.php?page=riwayat" class="btn btn-sm btn-outline-secondary ms-2">Reset</a>
        </div>
      <?php endif; ?>
      <?php if ($dateStart !== '' || $dateEnd !== ''): ?>
        <div class="alert alert-info mb-3">
          Menampilkan riwayat
          <?php if ($dateStart !== ''): ?>dari <strong><?= htmlspecialchars($dateStart) ?></strong><?php endif; ?>
          <?php if ($dateEnd !== ''): ?> sampai <strong><?= htmlspecialchars($dateEnd) ?></strong><?php endif; ?>
          <a href="index.php?page=riwayat" class="btn btn-sm btn-outline-secondary ms-2">Reset</a>
        </div>
      <?php endif; ?>

      <div class="d-flex flex-nowrap justify-content-end align-items-center gap-2 px-1 mb-3 overflow-auto">
        <form method="GET" class="d-flex flex-nowrap align-items-center gap-2 m-0">
          <input type="hidden" name="page" value="riwayat">
          <?php if ($vehicleFilter !== ''): ?>
            <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vehicleFilter) ?>">
          <?php endif; ?>

          <input type="date" name="date_start" class="form-control"
                 value="<?= htmlspecialchars($dateStart) ?>" placeholder="Mulai" style="min-width: 170px;">
          <input type="date" name="date_end" class="form-control"
                 value="<?= htmlspecialchars($dateEnd) ?>" placeholder="Sampai" style="min-width: 170px;">
          <input type="text" name="search" id="searchInput" class="form-control"
                 placeholder="Cari riwayat service..." value="<?= htmlspecialchars($search) ?>" style="min-width: 260px;">
          <button class="btn btn-outline-primary" type="submit">
            <i class="fas fa-search"></i>
          </button>
        </form>

        <a href="index.php?page=riwayat_export&<?= $exportQuery ?>" class="btn btn-sm btn-outline-success" target="_blank">
          <i class="fas fa-file-excel"></i> Export Excel
        </a>

        <?php if ($canPrintInvoice): ?>
          <a href="index.php?page=riwayat_invoice&<?= $exportQuery ?>" class="btn btn-sm btn-outline-danger" target="_blank">
            <i class="fas fa-file-pdf"></i> Cetak PDF
          </a>
        <?php endif; ?>
      </div>


      <table id="riwayatTable" class="table align-items-center mb-0">
        <thead>
          <tr>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">ID</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Tanggal</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Vehicle</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">No Pol</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Driver</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Status</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Kategori</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Masa Pakai</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Barang</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Total</th>
            <th class="text-end text-secondary">Aksi</th>
          </tr>
        </thead>

        <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= !empty($row['tanggal']) ? htmlspecialchars($row['tanggal']) : '-' ?></td>
            <td><?= htmlspecialchars($row['vehicle_id']) ?></td>
            <td><span class="badge bg-gradient-dark"><?= htmlspecialchars($row['nopol']) ?></span></td>
            <td><?= !empty($row['driver_nm']) ? htmlspecialchars($row['driver_nm']) : '?' ?></td>
            <td>
              <span class="badge <?= $row['status'] === 'perbaikan' ? 'bg-gradient-danger' : 'bg-gradient-info' ?>">
                <?= htmlspecialchars($row['status']) ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $row['kategori'] === 'normal' ? 'bg-gradient-success' : 'bg-gradient-warning' ?>">
                <?= htmlspecialchars($row['kategori']) ?>
              </span>
            </td>
            <td><?= (int)$row['masa_pakai_km'] ?> KM</td>
            <td><?= !empty($row['nama_barang']) ? htmlspecialchars($row['nama_barang']) : '-' ?></td>
            <td>Rp <?= number_format((float)$row['total_harga'], 0, ',', '.') ?></td>

            <td class="text-end">
              <button class="btn btn-outline-info" onclick='showDetailModal(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) ?>)'>
                <i class="fa-sharp-duotone fa-solid fa-circle-info"></i>
              </button>

              <a href="index.php?page=riwayat&action=edit&id=<?= (int)$row['id'] ?>"
                 class="btn btn-outline-warning">
                 <i class="fa-sharp-duotone fa-solid fa-file-pen"></i>
              </a>

              <a href="index.php?page=riwayat&action=delete&id=<?= (int)$row['id'] ?>"
                 class="btn btn-outline-danger"
                 onclick="return confirm('Yakin hapus riwayat service?')">
                 <i class="fa-solid fa-delete-left"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="d-flex justify-content-between align-items-center mt-3 px-3">
        <div id="dataInfo" class="text-sm text-secondary"></div>
        <div id="pagination" class="btn-group"></div>
      </div>

    </div>
  </div>
</div>

<?php
if ($action === 'create' || $action === 'edit'):

$data_edit = [
    'vehicle_id' => '',
    'nopol' => '',
    'driver_nm' => '',
    'total_km' => '',
    'last_km_service' => '',
    'status' => 'claim',
    'kategori' => 'normal',
    'keterangan' => '',
    'id_barang' => '',
    'jumlah' => 0,
    'harga_satuan' => 0
];

if ($action === 'edit') {
    $found = $model->getById($_GET['id']);
    if ($found) {
        $data_edit = $found;
    }
}

$isCreateMode = $action === 'create';
?>

<div class="riwayat-overlay">
  <div class="card shadow-lg border-0 riwayat-overlay-card">

    <div class="card-header d-flex justify-content-between align-items-center">
      <h5><?= $action === 'create' ? 'Tambah Riwayat Service' : 'Edit Riwayat Service' ?></h5>
      <a href="index.php?page=riwayat" class="btn btn-sm btn-outline-secondary">Tutup</a>
    </div>

    <div class="card-body" style="max-height: 80vh; overflow-y: auto;">
      <form method="POST" action="index.php?page=riwayat&action=<?= $action === 'edit' ? 'update&id=' . (int)$_GET['id'] : 'create' ?>">

        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Vehicle</label>
            <select id="vehicleSelect" name="vehicle_id" class="form-control" required>
              <option value="">Pilih Vehicle</option>
              <?php foreach ($vehicleOptions as $vehicle): ?>
                <option
                  value="<?= htmlspecialchars($vehicle['vehicle_id']) ?>"
                  data-nopol="<?= htmlspecialchars($vehicle['nopol']) ?>"
                  data-driver="<?= htmlspecialchars($vehicle['driver_nm']) ?>"
                  data-total-km="<?= (int)$vehicle['total_km'] ?>"
                  data-last-km-service="<?= (int)$vehicle['last_km_service'] ?>"
                  <?= $data_edit['vehicle_id'] == $vehicle['vehicle_id'] ? 'selected' : '' ?>
                >
                  <?= htmlspecialchars($vehicle['nopol']) ?> - <?= htmlspecialchars($vehicle['driver_nm']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label>No Polisi</label>
            <input type="text" id="nopolInput" name="nopol" class="form-control"
                   value="<?= htmlspecialchars($data_edit['nopol']) ?>" readonly required>
          </div>

          <div class="col-md-6 mb-3">
            <label>Driver</label>
            <input type="text" id="driverInput" name="driver_nm" class="form-control"
                   value="<?= htmlspecialchars($data_edit['driver_nm']) ?>" readonly>
          </div>

          <div class="col-md-6 mb-3">
            <label>Total KM</label>
            <input type="number" id="totalKmInput" name="total_km" class="form-control"
                   value="<?= (int)$data_edit['total_km'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label>KM Service Terakhir</label>
            <input type="number" id="lastKmInput" name="last_km_service" class="form-control"
                   value="<?= (int)$data_edit['last_km_service'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label>Status</label>
            <select name="status" class="form-control" required>
              <option value="claim" <?= $data_edit['status'] === 'claim' ? 'selected' : '' ?>>claim</option>
              <option value="ganti" <?= $data_edit['status'] === 'ganti' ? 'selected' : '' ?>>ganti</option>
              <option value="perbaikan" <?= $data_edit['status'] === 'perbaikan' ? 'selected' : '' ?>>perbaikan</option>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label>Kategori</label>
            <select name="kategori" class="form-control" required>
              <option value="normal" <?= $data_edit['kategori'] === 'normal' ? 'selected' : '' ?>>normal</option>
              <option value="tidak normal" <?= $data_edit['kategori'] === 'tidak normal' ? 'selected' : '' ?>>tidak normal</option>
            </select>
          </div>

          <?php if ($isCreateMode): ?>
            <div class="col-md-12 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="mb-0">Barang (Batch Opsional)</label>
                <button type="button" id="addBarangItemBtn" class="btn btn-sm btn-outline-primary">
                  + Tambah Barang
                </button>
              </div>

              <div id="barangBatchWrapper">
                <div class="barang-item border rounded p-2 mb-2">
                  <div class="row">
                    <div class="col-md-6 mb-2">
                      <label>Barang</label>
                      <select name="id_barang[]" class="form-control barang-select">
                        <option value="">Tanpa Barang</option>
                        <?php foreach ($inventoriOptions as $barang): ?>
                          <option
                            value="<?= (int)$barang['id'] ?>"
                            data-harga="<?= (float)$barang['harga_satuan'] ?>"
                            data-stok="<?= (int)$barang['stok'] ?>"
                          >
                            <?= htmlspecialchars($barang['nama']) ?> (stok: <?= (int)$barang['stok'] ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-3 mb-2">
                      <label>Jumlah</label>
                      <input type="number" min="0" name="jumlah[]" class="form-control jumlah-input" value="0">
                    </div>

                    <div class="col-md-3 mb-2">
                      <label>Harga Satuan</label>
                      <input type="number" min="0" name="harga_satuan[]" class="form-control harga-input" value="0" readonly>
                    </div>

                    <div class="col-md-12 d-flex justify-content-end">
                      <button type="button" class="btn btn-sm btn-outline-danger remove-barang-item">Hapus Baris</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="col-md-6 mb-3">
              <label>Barang (Opsional)</label>
              <select id="barangSelect" name="id_barang" class="form-control">
                <option value="">Tanpa Barang</option>
                <?php foreach ($inventoriOptions as $barang): ?>
                  <option
                    value="<?= (int)$barang['id'] ?>"
                    data-harga="<?= (float)$barang['harga_satuan'] ?>"
                    data-stok="<?= (int)$barang['stok'] ?>"
                    <?= (int)$data_edit['id_barang'] === (int)$barang['id'] ? 'selected' : '' ?>
                  >
                    <?= htmlspecialchars($barang['nama']) ?> (stok: <?= (int)$barang['stok'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label>Jumlah</label>
              <input type="number" min="0" id="jumlahInput" name="jumlah" class="form-control"
                     value="<?= (int)$data_edit['jumlah'] ?>">
            </div>

            <div class="col-md-3 mb-3">
              <label>Harga Satuan</label>
              <input type="number" min="0" id="hargaInput" name="harga_satuan" class="form-control"
                     value="<?= (float)$data_edit['harga_satuan'] ?>" readonly>
            </div>
          <?php endif; ?>

          <div class="col-md-12 mb-3">
            <label>Total Harga</label>
            <input type="text" id="totalHargaPreview" class="form-control"
                   value="Rp <?= number_format(((int)$data_edit['jumlah'] * (float)$data_edit['harga_satuan']), 0, ',', '.') ?>" readonly>
          </div>

          <div class="col-md-12 mb-3">
            <label>Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($data_edit['keterangan']) ?></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-between">
          <a href="index.php?page=riwayat" class="btn btn-outline-secondary">Kembali</a>
          <button type="submit" class="btn bg-gradient-primary">
            <?= $action === 'create' ? 'Simpan Riwayat' : 'Update Riwayat' ?>
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<?php endif; ?>

<div id="detailModalOverlay" class="riwayat-overlay" style="display: none;">
  <div class="card shadow-lg border-0 riwayat-overlay-card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 id="detailTitle" class="mb-0">Detail Riwayat Service</h5>
      <button class="btn btn-sm btn-outline-secondary" onclick="closeDetailModal()">Tutup</button>
    </div>

    <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
      <div class="row">
        <div class="col-md-4 mb-3"><label class="text-muted">ID</label><p id="detail_id" class="fw-bold">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Tanggal</label><p id="detail_tanggal">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Vehicle ID</label><p id="detail_vehicle_id">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">No. Pol</label><p id="detail_nopol">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Driver</label><p id="detail_driver_nm">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Status</label><p id="detail_status">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Kategori</label><p id="detail_kategori">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Total KM</label><p id="detail_total_km">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Last KM Service</label><p id="detail_last_km_service">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Masa Pakai</label><p id="detail_masa_pakai_km">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Barang</label><p id="detail_nama_barang">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Jumlah</label><p id="detail_jumlah">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Harga Satuan</label><p id="detail_harga_satuan">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Total Harga</label><p id="detail_total_harga">-</p></div>
        <div class="col-md-12 mb-3"><label class="text-muted">Keterangan</label><p id="detail_keterangan">-</p></div>
      </div>
    </div>
  </div>
</div>

<script>
function formatValue(val, fallback = '?') {
  return val === null || val === undefined || val === '' ? fallback : val;
}

function showDetailModal(rowData) {
  document.getElementById('detail_id').textContent = formatValue(rowData.id);
  document.getElementById('detail_tanggal').textContent = formatValue(rowData.tanggal, '-');
  document.getElementById('detail_vehicle_id').textContent = formatValue(rowData.vehicle_id);
  document.getElementById('detail_nopol').textContent = formatValue(rowData.nopol);
  document.getElementById('detail_driver_nm').textContent = formatValue(rowData.driver_nm);
  document.getElementById('detail_status').textContent = formatValue(rowData.status);
  document.getElementById('detail_kategori').textContent = formatValue(rowData.kategori);
  document.getElementById('detail_total_km').textContent = formatValue(rowData.total_km, 0) + ' KM';
  document.getElementById('detail_last_km_service').textContent = formatValue(rowData.last_km_service, 0) + ' KM';
  document.getElementById('detail_masa_pakai_km').textContent = formatValue(rowData.masa_pakai_km, 0) + ' KM';
  document.getElementById('detail_nama_barang').textContent = formatValue(rowData.nama_barang, '-');
  document.getElementById('detail_jumlah').textContent = formatValue(rowData.jumlah, 0);
  document.getElementById('detail_harga_satuan').textContent = 'Rp ' + Number(formatValue(rowData.harga_satuan, 0)).toLocaleString('id-ID');
  document.getElementById('detail_total_harga').textContent = 'Rp ' + Number(formatValue(rowData.total_harga, 0)).toLocaleString('id-ID');
  document.getElementById('detail_keterangan').textContent = formatValue(rowData.keterangan, '-');

  document.getElementById('detailTitle').textContent = 'Detail Riwayat - ' + (rowData.nopol || '?');
  document.getElementById('detailModalOverlay').style.display = 'flex';
}

function closeDetailModal() {
  document.getElementById('detailModalOverlay').style.display = 'none';
}

document.getElementById('detailModalOverlay')?.addEventListener('click', function (e) {
  if (e.target === this) {
    closeDetailModal();
  }
});

let rowsPerPage = 10;
let currentPage = 1;

const table = document.getElementById('riwayatTable');
const tbody = table.querySelector('tbody');
const rows = Array.from(tbody.querySelectorAll('tr'));

function displayTable() {
  // server-side search already applied; just paginate whatever rows are present
  const visibleRows = rows;

  const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
  if (currentPage > totalPages) currentPage = totalPages || 1;

  visibleRows.forEach((row, index) => {
    row.style.display =
      index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage
        ? ''
        : 'none';
  });

  renderPagination(totalPages);
  renderInfo(visibleRows.length);
}

function renderPagination(totalPages) {
  const pagination = document.getElementById('pagination');
  pagination.innerHTML = '';

  if (totalPages <= 1) return;

  const prevBtn = document.createElement('button');
  prevBtn.className = 'btn btn-sm btn-outline-primary';
  prevBtn.innerHTML = '&laquo;';
  prevBtn.disabled = currentPage === 1;
  prevBtn.onclick = function () {
    currentPage--;
    displayTable();
  };

  const nextBtn = document.createElement('button');
  nextBtn.className = 'btn btn-sm btn-outline-primary';
  nextBtn.innerHTML = '&raquo;';
  nextBtn.disabled = currentPage === totalPages;
  nextBtn.onclick = function () {
    currentPage++;
    displayTable();
  };

  pagination.appendChild(prevBtn);
  pagination.appendChild(nextBtn);
}

function renderInfo(totalData) {
  const start = totalData === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
  const end = Math.min(currentPage * rowsPerPage, totalData);

  document.getElementById('dataInfo').innerHTML =
    `Menampilkan ${start} - ${end} dari ${totalData} data`;
}

// initial pagination render

// render the first page of results once everything is defined
displayTable();


const vehicleSelect = document.getElementById('vehicleSelect');
const nopolInput = document.getElementById('nopolInput');
const driverInput = document.getElementById('driverInput');
const totalKmInput = document.getElementById('totalKmInput');
const lastKmInput = document.getElementById('lastKmInput');

if (vehicleSelect) {
  const updateVehicleInfo = () => {
    const selected = vehicleSelect.options[vehicleSelect.selectedIndex];
    if (!selected || !selected.value) {
      nopolInput.value = '';
      driverInput.value = '';
      totalKmInput.value = '';
      lastKmInput.value = '';
      return;
    }

    nopolInput.value = selected.dataset.nopol || '';
    driverInput.value = selected.dataset.driver || '';

    if (!totalKmInput.value || totalKmInput.value === '0') {
      totalKmInput.value = selected.dataset.totalKm || 0;
    }

    if (!lastKmInput.value || lastKmInput.value === '0') {
      lastKmInput.value = selected.dataset.lastKmService || 0;
    }
  };

  vehicleSelect.addEventListener('change', updateVehicleInfo);
  updateVehicleInfo();
}

const totalHargaPreview = document.getElementById('totalHargaPreview');

function bindBatchBarangUI() {
  const batchWrapper = document.getElementById('barangBatchWrapper');
  const addBarangItemBtn = document.getElementById('addBarangItemBtn');

  if (!batchWrapper || !addBarangItemBtn) {
    return false;
  }

  const getHargaFromSelect = (selectEl) => {
    if (!selectEl || !selectEl.value) return 0;
    const selected = selectEl.options[selectEl.selectedIndex];
    return Number(selected?.dataset?.harga || 0);
  };

  const syncItemTotal = (itemEl) => {
    if (!itemEl) return 0;
    const selectEl = itemEl.querySelector('.barang-select');
    const jumlahEl = itemEl.querySelector('.jumlah-input');
    const hargaEl = itemEl.querySelector('.harga-input');

    const harga = getHargaFromSelect(selectEl);
    const jumlah = Number(jumlahEl?.value || 0);

    if (hargaEl) {
      hargaEl.value = harga;
    }

    return harga * jumlah;
  };

  const recalculateBatchTotal = () => {
    const items = batchWrapper.querySelectorAll('.barang-item');
    let grandTotal = 0;

    items.forEach((itemEl) => {
      grandTotal += syncItemTotal(itemEl);
    });

    if (totalHargaPreview) {
      totalHargaPreview.value = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }
  };

  addBarangItemBtn.addEventListener('click', () => {
    const firstItem = batchWrapper.querySelector('.barang-item');
    if (!firstItem) return;

    const clone = firstItem.cloneNode(true);
    clone.querySelectorAll('select').forEach((el) => {
      el.selectedIndex = 0;
    });
    clone.querySelectorAll('input').forEach((el) => {
      el.value = '0';
    });
    batchWrapper.appendChild(clone);
    recalculateBatchTotal();
  });

  batchWrapper.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('.remove-barang-item');
    if (!removeBtn) return;

    const item = removeBtn.closest('.barang-item');
    const totalItems = batchWrapper.querySelectorAll('.barang-item').length;
    if (totalItems <= 1) {
      item.querySelectorAll('select').forEach((el) => {
        el.selectedIndex = 0;
      });
      item.querySelectorAll('input').forEach((el) => {
        el.value = '0';
      });
    } else {
      item.remove();
    }
    recalculateBatchTotal();
  });

  batchWrapper.addEventListener('change', (e) => {
    if (e.target.classList.contains('barang-select')) {
      recalculateBatchTotal();
    }
  });

  batchWrapper.addEventListener('input', (e) => {
    if (e.target.classList.contains('jumlah-input')) {
      recalculateBatchTotal();
    }
  });

  recalculateBatchTotal();
  return true;
}

const isBatchMode = bindBatchBarangUI();
if (!isBatchMode) {
  const barangSelect = document.getElementById('barangSelect');
  const jumlahInput = document.getElementById('jumlahInput');
  const hargaInput = document.getElementById('hargaInput');

  function updateHargaDanTotal() {
    if (!barangSelect) return;

    const selected = barangSelect.options[barangSelect.selectedIndex];
    const harga = selected && selected.value ? Number(selected.dataset.harga || 0) : 0;
    const jumlah = Number(jumlahInput?.value || 0);

    if (hargaInput) {
      hargaInput.value = harga;
    }

    if (totalHargaPreview) {
      const total = harga * jumlah;
      totalHargaPreview.value = 'Rp ' + total.toLocaleString('id-ID');
    }
  }

  if (barangSelect) {
    barangSelect.addEventListener('change', updateHargaDanTotal);
  }
  if (jumlahInput) {
    jumlahInput.addEventListener('input', updateHargaDanTotal);
  }
  updateHargaDanTotal();
}
</script>

<!-- Select2 for searchable vehicle dropdown -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // initialize select2 on vehicle select if present
    var $vehicle = $('#vehicleSelect');
    if ($vehicle.length) {
        $vehicle.select2({
            placeholder: 'Pilih Vehicle',
            allowClear: true,
            width: '100%'
        });

        // when select2 changes we also trigger the native change event
        $vehicle.on('select2:select select2:clear', function () {
            // dispatch a native change so existing listeners run
            var evt = document.createEvent('HTMLEvents');
            evt.initEvent('change', true, false);
            $vehicle[0].dispatchEvent(evt);
        });
    }
});
</script>

<?php
include 'core/footer.php';
?>
