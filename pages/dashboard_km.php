<?php
require_once __DIR__ . '/../services/EasyGoService.php';

$easygo = new EasyGoService();

if (!empty($_GET['start']) && !empty($_GET['end'])) {
    $startDate = $_GET['start'];
    $endDate   = $_GET['end'];
} else {
    $startDate = "2020-01-01";
    $endDate   = date("Y-m-d", strtotime("-1 day"));
}

$start = $startDate . " 00:00:00";
$end   = $endDate . " 23:59:59";

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$rowsPerPage = 10;
$currentPage = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;

$vehicleResponse = $easygo->getVehicles();
$vehicles = [];

if ($vehicleResponse['ResponseCode'] == 1) {
    $vehicles = $vehicleResponse['Data'];
} else {
    include 'core/header.php';
    echo "<div class='container-fluid py-4'><div class='alert alert-danger'>{$vehicleResponse['ResponseMsg']}</div></div>";
    include 'core/footer.php';
    return;
}

if ($search !== '') {
    $searchLower = strtolower($search);
    $vehicles = array_values(array_filter($vehicles, function ($row) use ($searchLower) {
        $nopol = strtolower($row['nopol'] ?? '');
        $driver = strtolower($row['driver_nm'] ?? '');
        $brand = strtolower($row['brand'] ?? '');
        $model = strtolower($row['model'] ?? '');
        $remark = strtolower($row['remark'] ?? '');

        return strpos($nopol, $searchLower) !== false
            || strpos($driver, $searchLower) !== false
            || strpos($brand, $searchLower) !== false
            || strpos($model, $searchLower) !== false
            || strpos($remark, $searchLower) !== false;
    }));
}

$totalRows = count($vehicles);
$totalPages = max(1, (int)ceil($totalRows / $rowsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $rowsPerPage;
$pagedVehicles = array_slice($vehicles, $offset, $rowsPerPage);

$vehicleIds = array_column($pagedVehicles, 'vehicle_id');
$nopolList  = array_column($pagedVehicles, 'nopol');
$totalKmData = [];

if (!empty($vehicleIds)) {
    $kmResponse = $easygo->getTotalKm($start, $end, $vehicleIds, $nopolList);
    if ($kmResponse['ResponseCode'] == 1) {
        foreach ($kmResponse['Data'] as $km) {
            $totalKmData[$km['vehicle_id']] = $km['total_km'];
        }
    }
}

$queryParams = [
    'page' => 'dashboard_km',
    'start' => $startDate,
    'end' => $endDate,
    'q' => $search,
];

$prevParams = $queryParams;
$prevParams['p'] = max(1, $currentPage - 1);
$nextParams = $queryParams;
$nextParams['p'] = min($totalPages, $currentPage + 1);

include 'core/header.php';
?>
<div class="container-fluid py-4">

  <div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Total KM Kendaraan</h5>
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col">
          <form method="GET" class="row g-3 mb-4">
            <input type="hidden" name="page" value="dashboard_km">
            <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">

            <div class="col-md-4">
              <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>" class="form-control">
            </div>

            <div class="col-md-4">
              <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>" class="form-control">
            </div>

            <div class="col-md-4 d-flex align-items-end">
              <button type="submit" class="btn bg-gradient-primary w-100">Filter</button>
            </div>
          </form>
        </div>

        <div class="col">
          <form method="GET" class="row mb-3 justify-content-end">
            <input type="hidden" name="page" value="dashboard_km">
            <input type="hidden" name="start" value="<?= htmlspecialchars($startDate) ?>">
            <input type="hidden" name="end" value="<?= htmlspecialchars($endDate) ?>">
            <div class="col-md-6">
              <input type="text" name="q" class="form-control" placeholder="Cari No Polisi / Driver / Kendaraan..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn bg-gradient-secondary w-100">Cari</button>
            </div>
          </form>
        </div>
      </div>

      <div class="table-responsive">
        <table id="vehicleTable" class="table align-items-center mb-0">
          <thead>
            <tr>
              <th>No</th>
              <th>No Polisi</th>
              <th>Driver</th>
              <th>Kendaraan</th>
              <th>Status</th>
              <th>Total KM</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($pagedVehicles)): ?>
            <tr>
              <td colspan="6" class="text-center">Data tidak ditemukan</td>
            </tr>
          <?php else: ?>
            <?php foreach ($pagedVehicles as $index => $row): ?>
              <tr>
                <td><?= $offset + $index + 1 ?></td>
                <td><?= htmlspecialchars($row['nopol'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['driver_nm'] ?? '-') ?></td>
                <td><?= htmlspecialchars(($row['brand'] ?? '-') . ' - ' . ($row['model'] ?? '-')) ?></td>
                <td>
                  <?php if (($row['remark'] ?? '') === 'Active'): ?>
                    <span class="badge bg-gradient-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-gradient-secondary"><?= htmlspecialchars($row['remark'] ?? '-') ?></span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string)($totalKmData[$row['vehicle_id']] ?? 0)) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
          <?php if ($totalRows === 0): ?>
            <span>Data tidak ditemukan</span>
          <?php else: ?>
            <span>Page <?= $currentPage ?> of <?= $totalPages ?> (<?= $totalRows ?> data)</span>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
          <a href="?<?= http_build_query($prevParams) ?>" class="btn btn-sm bg-gradient-secondary <?= $currentPage <= 1 ? 'disabled' : '' ?>">Prev</a>
          <a href="?<?= http_build_query($nextParams) ?>" class="btn btn-sm bg-gradient-secondary <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">Next</a>
        </div>
      </div>

    </div>
  </div>

</div>
<?php include 'core/footer.php'; ?>
