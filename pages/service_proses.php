<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/RiwayatModel.php';

$model = new RiwayatModel($conn);
$action = $_GET['action'] ?? null;

if ($action === 'to_siap_operasi') {
    $model->updateStatusToSiapOperasi($_GET['id'] ?? 0);
    header("Location: index.php?page=service_proses");
    exit;
}

if ($action === 'delete') {
    $model->delete($_GET['id'] ?? 0);
    header("Location: index.php?page=riwayat");
    exit;
}

$search = trim($_GET['search'] ?? '');
$vehicleFilter = trim($_GET['vehicle_id'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
$exportQuery = http_build_query([
    'search' => $search,
    'vehicle_id' => $vehicleFilter,
    'date_start' => $dateStart,
    'date_end' => $dateEnd,
    'status' => 'sedang dikerjakan'
]);

$canPrintInvoice = $search !== '' && ($dateStart !== '' || $dateEnd !== '');
$data = $model->getParentList($search, $vehicleFilter, $dateStart, $dateEnd, 'sedang dikerjakan', 'tgl_sedang_dikerjakan');

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
  <div class="card-header pb-0">
    <h5 class="mb-0">Data Service Selesai</h5>
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
            <td><?= !empty($row['tgl_sedang_dikerjakan']) ? htmlspecialchars($row['tgl_sedang_dikerjakan']) : '-' ?></td>
            <td><span class="badge bg-gradient-dark"><?= htmlspecialchars($row['nopol']) ?></span></td>
            <td><?= !empty($row['driver_nm']) ? htmlspecialchars($row['driver_nm']) : '?' ?></td>
            <td>
              <?php
                $statusValue = strtolower((string)($row['status'] ?? ''));
                $statusBadgeClass = 'bg-gradient-secondary';
                if ($statusValue === 'mengunggu') {
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
              <a href="index.php?page=service_proses&action=to_siap_operasi&id=<?= (int)$row['id'] ?>"
                 class="btn btn-outline-primary"
                 onclick="return confirm('Ubah status menjadi Siap Operasi?')"
                 title="Siap Operasi">
                <i class="fas fa-forward"></i>
              </a>
              <button class="btn btn-outline-info" onclick='showDetailModal(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) ?>)'>
                <i class="fa-sharp-duotone fa-solid fa-circle-info"></i>
              </button>
              <a href="index.php?page=riwayat&action=delete&id=<?= (int)$row['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin hapus riwayat service?')">
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
</script>



<?php
include 'core/footer.php';
?>
