<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/RiwayatModel.php';

$model = new RiwayatModel($conn);
$action = $_GET['action'] ?? null;

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $model->create($_POST);
    header("Location: index.php?page=riwayat");
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $model->update($_GET['id'] ?? 0, $_POST);
    header("Location: index.php?page=riwayat");
    exit;
}

if ($action === 'delete') {
    $model->delete($_GET['id'] ?? 0);
    header("Location: index.php?page=riwayat");
    exit;
}

$search = trim($_GET['search'] ?? '');
$nopolFilter = trim($_GET['nopol'] ?? '');
if ($nopolFilter === '') {
    $nopolFilter = trim($_GET['vehicle_id'] ?? '');
}
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
$exportQuery = http_build_query([
    'search' => $search,
    'nopol' => $nopolFilter,
    'date_start' => $dateStart,
    'date_end' => $dateEnd
]);

$canPrintInvoice = $search !== '' && ($dateStart !== '' || $dateEnd !== '');
$data = $model->getParentList($search, $nopolFilter, $dateStart, $dateEnd);
$vehicleOptions = $model->getVehicleOptions();
$inventoriOptions = $model->getInventoriOptions();
$mekanikOptions = $model->getMekanikOptions();

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
  width: min(1050px, 100%);
}
</style>

<div class="container-fluid py-4">

<div class="card shadow-lg border-0">
  <div class="card-header pb-0 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Data Riwayat Service</h5>
    <!-- <a href="index.php?page=riwayat&action=create" class="btn bg-gradient-success btn-sm">
      <i class="fas fa-plus me-1"></i> Tambah Riwayat
    </a> -->
  </div>

  <div class="card-body px-0 pt-3 pb-2">
    <div class="table-responsive p-3">
      <?php if (!empty($nopolFilter)): ?>
        <div class="alert alert-info mb-3">
          Menampilkan riwayat untuk nopol <strong><?= htmlspecialchars($nopolFilter) ?></strong>
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
          <?php if ($nopolFilter !== ''): ?>
            <input type="hidden" name="nopol" value="<?= htmlspecialchars($nopolFilter) ?>">
          <?php endif; ?>
          <input type="date" name="date_start" class="form-control" value="<?= htmlspecialchars($dateStart) ?>" style="min-width:170px;">
          <input type="date" name="date_end" class="form-control" value="<?= htmlspecialchars($dateEnd) ?>" style="min-width:170px;">
          <input type="text" name="search" id="searchInput" class="form-control" placeholder="Cari No Polisi..." value="<?= htmlspecialchars($search) ?>" style="min-width:260px;">
          <button class="btn btn-outline-primary mt-3" type="submit"><i class="fas fa-search"></i></button>
        </form>

        <a href="index.php?page=riwayat_export&<?= $exportQuery ?>" class="btn btn-sm btn-outline-success mt-3" target="_blank">
          <i class="fas fa-file-excel"></i> Export Excel
        </a>

        <?php if ($canPrintInvoice): ?>
          <a href="index.php?page=riwayat_invoice&<?= $exportQuery ?>" class="btn btn-sm btn-outline-danger mt-3" target="_blank">
            <i class="fas fa-file-pdf"></i> Cetak PDF
          </a>
        <?php endif; ?>
      </div>

      <table id="riwayatTable" class="table align-items-center mb-0">
        <thead>
          <tr>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">ID</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Tanggal</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">No Pol</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Driver</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Status</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Kategori</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Masa Pakai</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Qty</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder">Total</th>
            <th class="text-end text-secondary">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= !empty($row['tanggal']) ? htmlspecialchars($row['tanggal']) : '-' ?></td>
            <td><span class="badge bg-gradient-dark"><?= htmlspecialchars($row['nopol']) ?></span></td>
            <td><?= !empty($row['driver_nm']) ? htmlspecialchars($row['driver_nm']) : '?' ?></td>
            <td>
              <?php
                $statusValue = strtolower((string)($row['status'] ?? ''));
                $statusBadgeClass = 'bg-gradient-secondary';
                if ($statusValue === 'pending') {
                    $statusBadgeClass = 'bg-gradient-warning';
                } elseif ($statusValue === 'sedang dikerjakan') {
                    $statusBadgeClass = 'bg-gradient-primary';
                } elseif ($statusValue === 'siap operasi') {
                    $statusBadgeClass = 'bg-gradient-info';
                } elseif ($statusValue === 'selesai') {
                    $statusBadgeClass = 'bg-gradient-success';
                }
              ?>
              <span class="badge <?= $statusBadgeClass ?>">
                <?= htmlspecialchars($row['status']) ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $row['kategori'] === 'normal' ? 'bg-gradient-success' : 'bg-gradient-warning' ?>">
                <?= htmlspecialchars($row['kategori']) ?>
              </span>
            </td>
            <td><?= (int)$row['masa_pakai_km'] ?> KM</td>
            <td><?= (int)($row['total_qty'] ?? 0) ?></td>
            <td>Rp <?= number_format((float)($row['total_harga'] ?? 0), 0, ',', '.') ?></td>
            <td class="text-end">
              <button class="btn btn-outline-info" onclick='showDetailModal(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) ?>)'>
                <i class="fa-sharp-duotone fa-solid fa-circle-info"></i>
              </button>
              <a href="index.php?page=riwayat&action=edit&id=<?= (int)$row['id'] ?>" class="btn btn-outline-warning">
                <i class="fa-sharp-duotone fa-solid fa-file-pen"></i>
              </a>
              <!-- <a href="index.php?page=riwayat&action=delete&id=<?= (int)$row['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin hapus riwayat service?')">
                <i class="fa-solid fa-delete-left"></i>
              </a> -->
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
    $dataEdit = [
        'nopol' => '',
        'driver_nm' => '',
        'total_km' => '',
        'last_km_service' => '',
        'status' => 'mengunggu',
        'kategori' => 'normal',
        'keterangan' => '',
        'items' => []
    ];

    if ($action === 'edit') {
        $found = $model->getById($_GET['id'] ?? 0);
        if ($found) {
            $dataEdit = $found;
        }
    }

    if (empty($dataEdit['items'])) {
        $dataEdit['items'][] = [
            'id_barang' => '',
            'jumlah' => 0,
            'harga_satuan' => 0,
            'mekanik_id' => '',
            'tools' => 'tidak'
        ];
    }

    $existingTotal = 0;
    foreach ($dataEdit['items'] as $item) {
        $existingTotal += ((int)($item['jumlah'] ?? 0)) * ((float)($item['harga_satuan'] ?? 0));
    }
?>
<div class="riwayat-overlay">
  <div class="card shadow-lg border-0 riwayat-overlay-card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5><?= $action === 'create' ? 'Tambah Riwayat Service' : 'Edit Riwayat Service' ?></h5>
      <a href="index.php?page=riwayat" class="btn btn-sm btn-outline-secondary">Tutup</a>
    </div>

    <div class="card-body" style="max-height: 80vh; overflow-y: auto;">
      <form method="POST" action="index.php?page=riwayat&action=<?= $action === 'edit' ? 'update&id=' . (int)($_GET['id'] ?? 0) : 'create' ?>">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>No Polisi</label>
            <select id="nopolSelect" name="nopol" class="form-control" required>
              <option value="">Pilih No Polisi</option>
              <?php foreach ($vehicleOptions as $vehicle): ?>
                <option
                  value="<?= htmlspecialchars($vehicle['nopol']) ?>"
                  data-driver="<?= htmlspecialchars($vehicle['driver_nm']) ?>"
                  data-total-km="<?= (int)$vehicle['total_km'] ?>"
                  data-last-km-service="<?= (int)$vehicle['last_km_service'] ?>"
                  <?= $dataEdit['nopol'] == $vehicle['nopol'] ? 'selected' : '' ?>
                >
                  <?= htmlspecialchars($vehicle['nopol']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>



          <div class="col-md-6 mb-3">
            <label>Driver</label>
            <input type="text" id="driverInput" name="driver_nm" class="form-control" value="<?= htmlspecialchars($dataEdit['driver_nm']) ?>" readonly>
          </div>

          <div class="col-md-6 mb-3">
            <label>Total KM</label>
            <input type="number" id="totalKmInput" name="total_km" class="form-control" value="<?= (int)$dataEdit['total_km'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label>KM Service Terakhir</label>
            <input type="number" id="lastKmInput" name="last_km_service" class="form-control" value="<?= (int)$dataEdit['last_km_service'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label>Status</label>
            <select name="status" class="form-control" required>
              <option value="pending" <?= strtolower((string)$dataEdit['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="sedang dikerjakan" <?= strtolower((string)$dataEdit['status']) === 'sedang dikerjakan' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
              <option value="siap operasi" <?= strtolower((string)$dataEdit['status']) === 'siap operasi' ? 'selected' : '' ?>>Siap Operasi</option>
              <option value="selesai" <?= strtolower((string)$dataEdit['status']) === 'selesai' ? 'selected' : '' ?>>Selesai</option>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label>Kategori</label>
            <select name="kategori" class="form-control" required>
              <option value="normal" <?= $dataEdit['kategori'] === 'normal' ? 'selected' : '' ?>>normal</option>
              <option value="tidak normal" <?= $dataEdit['kategori'] === 'tidak normal' ? 'selected' : '' ?>>tidak normal</option>
            </select>
          </div>

          <div class="col-md-12 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="mb-0">Child Item Barang Service</label>
              <button type="button" id="addBarangItemBtn" class="btn btn-sm btn-outline-primary">+ Tambah Item</button>
            </div>

            <div id="barangBatchWrapper">
              <?php foreach ($dataEdit['items'] as $item): ?>
                <div class="barang-item border rounded p-2 mb-2">
                  <div class="row">
                    <div class="col-md-4 mb-2">
                      <label>Barang</label>
                      <select name="id_barang[]" class="form-control barang-select">
                        <option value="">Tanpa Barang</option>
                        <?php foreach ($inventoriOptions as $barang): ?>
                          <option
                            value="<?= (int)$barang['id'] ?>"
                            data-harga="<?= (float)$barang['harga_satuan'] ?>"
                            data-stok="<?= (int)$barang['stok'] ?>"
                            <?= (int)($item['id_barang'] ?? 0) === (int)$barang['id'] ? 'selected' : '' ?>
                          >
                            <?= htmlspecialchars($barang['nama']) ?> (stok: <?= (int)$barang['stok'] ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-2 mb-2">
                      <label>Jumlah</label>
                      <input type="number" min="0" name="jumlah[]" class="form-control jumlah-input" value="<?= (int)($item['jumlah'] ?? 0) ?>">
                    </div>

                    <div class="col-md-2 mb-2">
                      <label>Harga Satuan</label>
                      <input type="number" min="0" name="harga_satuan[]" class="form-control harga-input" value="<?= (float)($item['harga_satuan'] ?? 0) ?>" readonly>
                    </div>

                    <div class="col-md-2 mb-2">
                      <label>Mekanik</label>
                      <select name="mekanik_id[]" class="form-control">
                        <option value="">Pilih Mekanik</option>
                        <?php foreach ($mekanikOptions as $mekanik): ?>
                          <option value="<?= (int)$mekanik['id'] ?>" <?= (int)($item['mekanik_id'] ?? 0) === (int)$mekanik['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mekanik['nama']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-2 mb-2">
                      <label>Tools</label>
                      <select name="tools[]" class="form-control">
                        <?php $toolsVal = strtolower((string)($item['tools'] ?? 'tidak')); ?>
                        <option value="tidak" <?= $toolsVal === 'tidak' ? 'selected' : '' ?>>tidak</option>
                        <option value="ya" <?= $toolsVal === 'ya' ? 'selected' : '' ?>>ya</option>
                      </select>
                    </div>

                    <div class="col-md-12 d-flex justify-content-end">
                      <button type="button" class="btn btn-sm btn-outline-danger remove-barang-item">Hapus Baris</button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="col-md-12 mb-3">
            <label>Total Harga</label>
            <input type="text" id="totalHargaPreview" class="form-control" value="Rp <?= number_format($existingTotal, 0, ',', '.') ?>" readonly>
          </div>

          <div class="col-md-12 mb-3">
            <label>Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($dataEdit['keterangan']) ?></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-between">
          <a href="index.php?page=riwayat" class="btn btn-outline-secondary">Kembali</a>
          <button type="submit" class="btn bg-gradient-primary"><?= $action === 'create' ? 'Simpan Riwayat' : 'Update Riwayat' ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="detailModalOverlay" class="riwayat-overlay" style="display:none;">
  <div class="card shadow-lg border-0 riwayat-overlay-card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 id="detailTitle" class="mb-0">Detail Riwayat Service</h5>
      <div class="d-flex gap-2">
        <button id="printDetailBtn" class="btn btn-sm btn-outline-danger" onclick="printDetailInvoice()" disabled>
          <i class="fas fa-file-pdf"></i> Print Invoice PDF
        </button>
        <button class="btn btn-sm btn-outline-secondary" onclick="closeDetailModal()">Tutup</button>
      </div>
    </div>
    <div class="card-body" style="max-height:70vh; overflow-y:auto;">
      <div class="row">
        <div class="col-md-4 mb-3"><label class="text-muted">ID</label><p id="detail_id" class="fw-bold">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Tanggal Dibuat</label><p id="detail_tanggal">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Vehicle ID</label><p id="detail_vehicle_id">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">No. Pol</label><p id="detail_nopol">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Driver</label><p id="detail_driver_nm">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Status</label><p id="detail_status">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Kategori</label><p id="detail_kategori">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Total KM</label><p id="detail_total_km">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Last KM Service</label><p id="detail_last_km_service">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Masa Pakai</label><p id="detail_masa_pakai_km">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Tgl Menunggu</label><p id="detail_tgl_menunngu">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Tgl Sedang Dikerjakan</label><p id="detail_tgl_sedang_dikerjakan">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Tgl Siap Operasi</label><p id="detail_tgl_siap_operasi">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Tgl Selesai</label><p id="detail_tgl_selesai">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Total Qty Item</label><p id="detail_total_qty">-</p></div>
        <div class="col-md-4 mb-3"><label class="text-muted">Total Harga</label><p id="detail_total_harga">-</p></div>
        <div class="col-md-12 mb-3"><label class="text-muted">Item Service</label><p id="detail_item_summary">-</p></div>
        <div class="col-md-12 mb-3"><label class="text-muted">Keterangan</label><p id="detail_keterangan">-</p></div>
      </div>
    </div>
  </div>
</div>

<script>
let currentDetailRowData = null;

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
  document.getElementById('detail_tgl_menunngu').textContent = formatValue(rowData.tgl_menunngu ?? rowData.tgl_menunggu, '-');
  document.getElementById('detail_tgl_sedang_dikerjakan').textContent = formatValue(rowData.tgl_sedang_dikerjakan, '-');
  document.getElementById('detail_tgl_siap_operasi').textContent = formatValue(rowData.tgl_siap_operasi, '-');
  document.getElementById('detail_tgl_selesai').textContent = formatValue(rowData.tgl_selesai, '-');
  document.getElementById('detail_total_km').textContent = formatValue(rowData.total_km, 0) + ' KM';
  document.getElementById('detail_last_km_service').textContent = formatValue(rowData.last_km_service, 0) + ' KM';
  document.getElementById('detail_masa_pakai_km').textContent = formatValue(rowData.masa_pakai_km, 0) + ' KM';
  document.getElementById('detail_total_qty').textContent = formatValue(rowData.total_qty, 0);
  document.getElementById('detail_total_harga').textContent = 'Rp ' + Number(formatValue(rowData.total_harga, 0)).toLocaleString('id-ID');
  document.getElementById('detail_item_summary').textContent = formatValue(rowData.item_summary, '-');
  document.getElementById('detail_keterangan').textContent = formatValue(rowData.keterangan, '-');
  document.getElementById('detailTitle').textContent = 'Detail Riwayat - ' + (rowData.nopol || '?');
  currentDetailRowData = rowData;
  const printBtn = document.getElementById('printDetailBtn');
  if (printBtn) {
    printBtn.disabled = false;
  }
  document.getElementById('detailModalOverlay').style.display = 'flex';
}

function closeDetailModal() {
  document.getElementById('detailModalOverlay').style.display = 'none';
}

function printDetailInvoice() {
  if (!currentDetailRowData) return;
  const id = Number(currentDetailRowData.id || 0);
  if (!id) return;
  window.open('index.php?page=riwayat_invoice_detail&id=' + encodeURIComponent(id), '_blank');
}

document.getElementById('detailModalOverlay')?.addEventListener('click', function (e) {
  if (e.target === this) closeDetailModal();
});

let rowsPerPage = 10;
let currentPage = 1;
const table = document.getElementById('riwayatTable');
const tbody = table.querySelector('tbody');
const rows = Array.from(tbody.querySelectorAll('tr'));

function displayTable() {
  const visibleRows = rows;
  const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
  if (currentPage > totalPages) currentPage = totalPages || 1;

  visibleRows.forEach((row, index) => {
    row.style.display = index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage ? '' : 'none';
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
  prevBtn.onclick = function () { currentPage--; displayTable(); };

  const nextBtn = document.createElement('button');
  nextBtn.className = 'btn btn-sm btn-outline-primary';
  nextBtn.innerHTML = '&raquo;';
  nextBtn.disabled = currentPage === totalPages;
  nextBtn.onclick = function () { currentPage++; displayTable(); };

  pagination.appendChild(prevBtn);
  pagination.appendChild(nextBtn);
}

function renderInfo(totalData) {
  const start = totalData === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
  const end = Math.min(currentPage * rowsPerPage, totalData);
  document.getElementById('dataInfo').innerHTML = `Menampilkan ${start} - ${end} dari ${totalData} data`;
}

displayTable();

const nopolSelect = document.getElementById('nopolSelect');
const driverInput = document.getElementById('driverInput');
const totalKmInput = document.getElementById('totalKmInput');
const lastKmInput = document.getElementById('lastKmInput');

if (nopolSelect) {
  const updateVehicleInfo = () => {
    const selected = nopolSelect.options[nopolSelect.selectedIndex];
    if (!selected || !selected.value) {
      driverInput.value = '';
      totalKmInput.value = '';
      lastKmInput.value = '';
      return;
    }

    driverInput.value = selected.dataset.driver || '';
    totalKmInput.value = selected.dataset.totalKm || 0;
    lastKmInput.value = selected.dataset.lastKmService || 0;
  };

  nopolSelect.addEventListener('change', updateVehicleInfo);
  updateVehicleInfo();
}

const totalHargaPreview = document.getElementById('totalHargaPreview');

function bindBatchBarangUI() {
  const batchWrapper = document.getElementById('barangBatchWrapper');
  const addBarangItemBtn = document.getElementById('addBarangItemBtn');
  if (!batchWrapper || !addBarangItemBtn) return;

  const getHargaFromSelect = (selectEl) => {
    if (!selectEl || !selectEl.value) return 0;
    const selected = selectEl.options[selectEl.selectedIndex];
    return Number(selected?.dataset?.harga || 0);
  };

  const syncItemTotal = (itemEl) => {
    const selectEl = itemEl.querySelector('.barang-select');
    const jumlahEl = itemEl.querySelector('.jumlah-input');
    const hargaEl = itemEl.querySelector('.harga-input');
    const harga = getHargaFromSelect(selectEl);
    const jumlah = Number(jumlahEl?.value || 0);
    if (hargaEl) hargaEl.value = harga;
    return harga * jumlah;
  };

  const recalculateBatchTotal = () => {
    const items = batchWrapper.querySelectorAll('.barang-item');
    let grandTotal = 0;
    items.forEach((itemEl) => { grandTotal += syncItemTotal(itemEl); });
    if (totalHargaPreview) {
      totalHargaPreview.value = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }
  };

  addBarangItemBtn.addEventListener('click', () => {
    const firstItem = batchWrapper.querySelector('.barang-item');
    if (!firstItem) return;

    const clone = firstItem.cloneNode(true);
    clone.querySelectorAll('select').forEach((el) => {
      if (el.name === 'tools[]') {
        el.value = 'tidak';
      } else {
        el.selectedIndex = 0;
      }
    });
    clone.querySelectorAll('input').forEach((el) => { el.value = '0'; });
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
        if (el.name === 'tools[]') {
          el.value = 'tidak';
        } else {
          el.selectedIndex = 0;
        }
      });
      item.querySelectorAll('input').forEach((el) => { el.value = '0'; });
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
}

bindBatchBarangUI();
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  var $nopol = $('#nopolSelect');
  if ($nopol.length) {
    $nopol.select2({
      placeholder: 'Pilih No Polisi',
      allowClear: true,
      width: '100%'
    });

    $nopol.on('select2:select select2:clear', function () {
      var evt = document.createEvent('HTMLEvents');
      evt.initEvent('change', true, false);
      $nopol[0].dispatchEvent(evt);
    });
  }
});
</script>

<?php
include 'core/footer.php';
?>
