<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/DashboardModel.php';

$dashboardModel = new DashboardModel($conn);
$totals = $dashboardModel->getSummary();
$topSpareparts = $dashboardModel->getTopSparepartRiwayat(3);

$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');

function formatDateLabel($dateValue) {
    if (!$dateValue) {
        return '';
    }
    return date('d M Y', strtotime($dateValue));
}

$filterLabel = 'Semua tanggal';
if ($dateStart && $dateEnd) {
    $filterLabel = formatDateLabel($dateStart) . ' - ' . formatDateLabel($dateEnd);
} elseif ($dateStart) {
    $filterLabel = 'Mulai ' . formatDateLabel($dateStart);
} elseif ($dateEnd) {
    $filterLabel = 'Sampai ' . formatDateLabel($dateEnd);
}

$getcountchart = $dashboardModel->getCountChart($dateStart, $dateEnd);
$chartJadwal = $dashboardModel->getCountChartByStatus('jadwal', $dateStart, $dateEnd);
$chartSedang = $dashboardModel->getCountChartByStatus('sedang dikerjakan', $dateStart, $dateEnd);
$chartSiap = $dashboardModel->getCountChartByStatus('siap operasi', $dateStart, $dateEnd);
$chartSelesai = $dashboardModel->getCountChartByStatus('selesai', $dateStart, $dateEnd);

$labels = [];
$data = [];

foreach ($getcountchart as $row) {
    $labels[] = date('d M Y', strtotime($row['tgl']));
    $data[] = (int)$row['total'];
}

function buildChartSeries($rows) {
    $labels = [];
    $data = [];
    foreach ($rows as $row) {
        $labels[] = date('d M Y', strtotime($row['tgl']));
        $data[] = (int)$row['total'];
    }
    return [$labels, $data];
}

[$labelsJadwal, $dataJadwal] = buildChartSeries($chartJadwal);
[$labelsSedang, $dataSedang] = buildChartSeries($chartSedang);
[$labelsSiap, $dataSiap] = buildChartSeries($chartSiap);
[$labelsSelesai, $dataSelesai] = buildChartSeries($chartSelesai);

include 'core/header.php';
?>

<div class="print-header">
  <div class="print-header__title">Dashboard Service</div>
  <div class="print-header__meta">Periode: <?= htmlspecialchars($filterLabel) ?></div>
</div>

<div class="container-fluid py-4 mt-2">
  <div class="row">
    <!-- Count -->
    <div class="col-lg-6 col-12">
      <div class="row">
        <div class="col-lg-6 col-md-6 col-12">
          <div class="card">
            <span class="mask bg-primary opacity-10 border-radius-lg"></span>
            <div class="card-body p-3 position-relative">
              <div class="row">
                <div class="col-8 text-start">
                  <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                    <i class="fa-regular fa-id-card" style="color: rgb(0, 0, 0);"></i>
                  </div>
                  <h5 class="text-white font-weight-bolder mb-0 mt-3"><?= number_format($totals['drivers']) ?></h5>
                  <span class="text-white text-sm">Total Driver</span>
                </div>
                <div class="col-4 d-flex align-items-end justify-content-end">
                  <p class="text-white text-sm text-end font-weight-bolder mb-0">drivers</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6 col-md-6 col-12 mt-4 mt-md-0">
          <div class="card">
            <span class="mask bg-dark opacity-10 border-radius-lg"></span>
            <div class="card-body p-3 position-relative">
              <div class="row">
                <div class="col-8 text-start">
                  <div class="bg-white shadow text-center icon icon-shape border-radius-2xl">
                    <i class="fa-solid fa-truck-moving" style="color: rgb(0, 0, 0);"></i>
                  </div>
                  <h5 class="text-white font-weight-bolder mb-0 mt-3"><?= number_format($totals['vehicles']) ?></h5>
                  <span class="text-white text-sm">Total Vehicle</span>
                </div>
                <div class="col-4 d-flex align-items-end justify-content-end">
                  <div class="text-end">
                    <p class="text-white text-sm text-end font-weight-bolder mb-0">vehicles</p>
                    <p class="text-white text-xs mb-0">jadwal: <?= number_format($totals['riwayat_jadwal']) ?></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-lg-6 col-md-6 col-12">
          <div class="card">
            <span class="mask bg-info opacity-10 border-radius-lg"></span>
            <div class="card-body p-3 position-relative">
              <div class="row">
                <div class="col-8 text-start">
                  <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                    <i class="fa-solid fa-boxes-stacked" style="color: rgb(0, 0, 0);"></i>
                  </div>
                  <h5 class="text-white font-weight-bolder mb-0 mt-3"><?= number_format($totals['inventory']) ?></h5>
                  <span class="text-white text-sm">Total Inventory</span>
                </div>
                <div class="col-4 d-flex align-items-end justify-content-end">
                  <div class="text-end">
                    <p class="text-white text-sm text-end font-weight-bolder mb-0">inventori</p>
                    <p class="text-white text-xs mb-0">peringatan: <?= number_format($totals['inventory_warning']) ?></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6 col-md-6 col-12 mt-4 mt-md-0">
          <div class="card">
            <span class="mask bg-success opacity-10 border-radius-lg"></span>
            <div class="card-body p-3 position-relative">
              <div class="row">
                <div class="col-8 text-start">
                  <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                    <i class="fa-solid fa-cart-flatbed" style="color: rgb(0, 0, 0);"></i>
                  </div>
                  <h5 class="text-white font-weight-bolder mb-0 mt-3"><?= number_format($totals['riwayat']) ?></h5>
                  <span class="text-white text-sm">Total Riwayat</span>
                </div>
                <div class="col-4 d-flex align-items-end justify-content-end">
                  <div class="text-end">
                    <p class="text-white text-sm text-end font-weight-bolder mb-0">riwayat</p>
                    <p class="text-white text-xs mb-0">selesai: <?= number_format($totals['riwayat_selesai']) ?></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 col-12 mt-4 mt-lg-0">
      <div class="card shadow h-100">
        <div class="card-header pb-0 p-3">
          <h6 class="mb-0">Top 3 Sparepart Di Riwayat Service</h6>
        </div>
        <div class="card-body p-3">
          <?php if (count($topSpareparts) > 0): ?>
            <ul class="list-group">
              <?php foreach ($topSpareparts as $index => $item): ?>
                <li class="list-group-item border-0 d-flex justify-content-between align-items-start px-0">
                  <div>
                    <div class="text-sm font-weight-bold text-dark">
                      #<?= $index + 1 ?> <?= htmlspecialchars($item['nama']) ?>
                    </div>
                    <div class="text-xs text-secondary">
                      <?= number_format((int)$item['total_transaksi']) ?> transaksi
                    </div>
                  </div>
                  <span class="badge bg-gradient-primary">
                    <?= number_format((int)$item['total_qty']) ?> pcs
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-sm text-secondary mb-0">Belum ada data sparepart pada riwayat service.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
form input[type="date"]{
  min-width:180px;
}
.print-header{
  display:none;
  margin: 0 16px 12px;
  padding: 8px 0 12px;
  border-bottom: 1px solid #ddd;
}
.print-header__title{
  font-size: 18px;
  font-weight: 700;
  color: #111;
}
.print-header__meta{
  font-size: 12px;
  color: #555;
}
@media print {
  .no-print {
    display:none !important;
  }
  .print-header{
    display:block;
  }
  .card{
    box-shadow:none !important;
  }
  .chart{
    page-break-inside: avoid;
  }
}
</style>
<div class="container-fluid pb-4">
  <div class="row">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header pb-0">
          <h6 class="mb-1">Grafik Service Harian</h6>
          <p class="text-sm text-muted mb-0">Jumlah riwayat service per tanggal</p>
        </div>
        <div class="card-body">
<form method="GET" class="d-flex gap-2 flex-wrap no-print">
  <input type="hidden" name="page" value="dashboard">

  <div>
    <input type="date" name="date_start" class="form-control"
           value="<?= htmlspecialchars($dateStart) ?>">
  </div>

  <div>
    <input type="date" name="date_end" class="form-control"
           value="<?= htmlspecialchars($dateEnd) ?>">
  </div>

  <div class="">
    <button class="btn btn-outline-primary" type="submit">
      <i class="fas fa-filter"></i> Terapkan
    </button>

    <a href="index.php?page=dashboard" class="btn btn-outline-secondary">
      Reset
    </a>

    <a class="btn btn-outline-dark" href="http://localhost/inr/dashboard_prin.php" target="_blank" rel="noopener">
      <i class="fas fa-print"></i> Cetak data grafis
    </a>
  </div>
</form>
          <div class="chart" style="height: 300px;">
            <canvas id="line-chart-gradient" class="chart-canvas h-100 w-100"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid pb-4">
  <div class="row g-4">
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header pb-0">
          <h6 class="mb-1">Grafik Service Harian - Jadwal</h6>
          <p class="text-sm text-muted mb-0">Jumlah riwayat service per tanggal (status: jadwal)</p>
        </div>
        <div class="card-body p-3">
          <div class="chart" style="height: 280px;">
            <canvas id="line-chart-jadwal" class="chart-canvas h-100 w-100"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header pb-0">
          <h6 class="mb-1">Grafik Service Harian - Sedang Dikerjakan</h6>
          <p class="text-sm text-muted mb-0">Jumlah riwayat service per tanggal (status: sedang dikerjakan)</p>
        </div>
        <div class="card-body p-3">
          <div class="chart" style="height: 280px;">
            <canvas id="line-chart-sedang" class="chart-canvas h-100 w-100"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header pb-0">
          <h6 class="mb-1">Grafik Service Harian - Siap Operasi</h6>
          <p class="text-sm text-muted mb-0">Jumlah riwayat service per tanggal (status: siap operasi)</p>
        </div>
        <div class="card-body p-3">
          <div class="chart" style="height: 280px;">
            <canvas id="line-chart-siap" class="chart-canvas h-100 w-100"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header pb-0">
          <h6 class="mb-1">Grafik Service Harian - Selesai</h6>
          <p class="text-sm text-muted mb-0">Jumlah riwayat service per tanggal (status: selesai)</p>
        </div>
        <div class="card-body p-3">
          <div class="chart" style="height: 280px;">
            <canvas id="line-chart-selesai" class="chart-canvas h-100 w-100"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<script>
window.addEventListener("load", function () {
  if (typeof Chart === "undefined") {
    return;
  }

  var chartCanvas = document.getElementById("line-chart-gradient");
  if (!chartCanvas) {
    return;
  }

  var ctx1 = chartCanvas.getContext("2d");
  var gradientStroke1 = ctx1.createLinearGradient(0, 230, 0, 50);
  gradientStroke1.addColorStop(1, "rgba(94, 114, 228, 0.2)");
  gradientStroke1.addColorStop(0.2, "rgba(94, 114, 228, 0.0)");
  gradientStroke1.addColorStop(0, "rgba(94, 114, 228, 0)");

  new Chart(ctx1, {
    type: "line",
    data: {
      labels: <?= json_encode($labels); ?>,
      datasets: [{
        label: "Jumlah Service",
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 4,
        borderColor: "#5e72e4",
        backgroundColor: gradientStroke1,
        fill: true,
        data: <?= json_encode($data); ?>,
        maxBarThickness: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true
        }
      },
      interaction: {
        intersect: false,
        mode: "index"
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      }
    }
  });

  function renderLineChart(canvasId, labels, data, color) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) {
      return;
    }

    var ctx = canvas.getContext("2d");
    var gradient = ctx.createLinearGradient(0, 230, 0, 50);
    gradient.addColorStop(1, "rgba(94, 114, 228, 0.2)");
    gradient.addColorStop(0.2, "rgba(94, 114, 228, 0.0)");
    gradient.addColorStop(0, "rgba(94, 114, 228, 0)");

    new Chart(ctx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [{
          label: "Jumlah Service",
          tension: 0.4,
          borderWidth: 3,
          pointRadius: 4,
          borderColor: color,
          backgroundColor: gradient,
          fill: true,
          data: data,
          maxBarThickness: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true
          }
        },
        interaction: {
          intersect: false,
          mode: "index"
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }

  renderLineChart("line-chart-jadwal", <?= json_encode($labelsJadwal); ?>, <?= json_encode($dataJadwal); ?>, "#2dce89");
  renderLineChart("line-chart-sedang", <?= json_encode($labelsSedang); ?>, <?= json_encode($dataSedang); ?>, "#fb6340");
  renderLineChart("line-chart-siap", <?= json_encode($labelsSiap); ?>, <?= json_encode($dataSiap); ?>, "#11cdef");
  renderLineChart("line-chart-selesai", <?= json_encode($labelsSelesai); ?>, <?= json_encode($dataSelesai); ?>, "#5e72e4");

  function captureChartsForPrint() {
    var canvases = document.querySelectorAll("canvas.chart-canvas");
    canvases.forEach(function (canvas) {
      if (!canvas.id) {
        return;
      }
      if (document.querySelector('img.print-chart-image[data-canvas-id="' + canvas.id + '"]')) {
        return;
      }
      var img = document.createElement("img");
      img.className = "print-chart-image";
      img.style.width = "100%";
      img.style.height = "auto";
      img.setAttribute("data-canvas-id", canvas.id);
      try {
        img.src = canvas.toDataURL("image/png", 1.0);
      } catch (e) {
        return;
      }
      canvas.style.display = "none";
      canvas.parentNode.appendChild(img);
    });
  }

  function restoreChartsAfterPrint() {
    document.querySelectorAll("img.print-chart-image").forEach(function (img) {
      var canvasId = img.getAttribute("data-canvas-id");
      var canvas = document.getElementById(canvasId);
      if (canvas) {
        canvas.style.display = "";
      }
      img.remove();
    });
  }

  window.addEventListener("beforeprint", captureChartsForPrint);
  window.addEventListener("afterprint", restoreChartsAfterPrint);

});
</script>
<?php
include 'core/footer.php';
?>
