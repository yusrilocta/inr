<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/model/DashboardModel.php';

$dashboardModel = new DashboardModel($conn);

$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');

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

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">

  <!--     Fonts and icons     -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <!-- Nucleo Icons -->
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/4553990e8e.js" crossorigin="anonymous"></script>
  <!-- CSS Files -->
  <link id="pagestyle" href="assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
  <!-- Nepcha Analytics (nepcha.com) -->
  <!-- Nepcha is a easy-to-use web analytics. No cookies and fully compliant with GDPR, CCPA and PECR. -->
  <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
</head>
<body>
<style>
form input[type="date"]{
  min-width:180px;
}
@media print {
  .no-print {
    display: none !important;
  }
  .card{
    box-shadow: none !important;
  }
  .chart{
    page-break-inside: avoid;
  }
}
</style>
<div class="container-fluid pb-4" id="print_pdf">
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

    <button class="btn btn-outline-dark" type="button" id="btn-print-dashboard">
      <i class="fas fa-print"></i> Print PDF
    </button>
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

<div class="container-fluid pb-4" id="print_pdf">
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

  var printBtn = document.getElementById("btn-print-dashboard");
  if (printBtn) {
    printBtn.addEventListener("click", function () {
      captureChartsForPrint();
      setTimeout(function () {
        window.print();
      }, 0);
    });
  }
});
</script>

  <!--   Core JS Files   -->
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="assets/js/plugins/chartjs.min.js"></script>

  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
</body>

</html>
