<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/DashboardModel.php';

$dashboardModel = new DashboardModel($conn);
$totals = $dashboardModel->getSummary();
$topSpareparts = $dashboardModel->getTopSparepartRiwayat(3);
$getcountchart = $dashboardModel->getCountChart();

$labels = [];
$data = [];

foreach ($getcountchart as $row) {
    $labels[] = date('d M Y', strtotime($row['tgl']));
    $data[] = (int)$row['total'];
}

include 'core/header.php';
?>

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
                    <i class="ni ni-circle-08 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
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
                  <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                    <i class="ni ni-delivery-fast text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                  </div>
                  <h5 class="text-white font-weight-bolder mb-0 mt-3"><?= number_format($totals['vehicles']) ?></h5>
                  <span class="text-white text-sm">Total Vehicle</span>
                </div>
                <div class="col-4 d-flex align-items-end justify-content-end">
                  <p class="text-white text-sm text-end font-weight-bolder mb-0">vehicles</p>
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
                    <i class="ni ni-box-2 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                  </div>
                  <h5 class="text-white font-weight-bolder mb-0 mt-3"><?= number_format($totals['inventory']) ?></h5>
                  <span class="text-white text-sm">Total Inventory</span>
                </div>
                <div class="col-4 d-flex align-items-end justify-content-end">
                  <p class="text-white text-sm text-end font-weight-bolder mb-0">inventori</p>
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
                    <i class="ni ni-single-copy-04 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                  </div>
                  <h5 class="text-white font-weight-bolder mb-0 mt-3"><?= number_format($totals['riwayat']) ?></h5>
                  <span class="text-white text-sm">Total Riwayat</span>
                </div>
                <div class="col-4 d-flex align-items-end justify-content-end">
                  <p class="text-white text-sm text-end font-weight-bolder mb-0">riwayat</p>
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

<div class="container-fluid pb-4">
  <div class="row">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header pb-0">
          <h6 class="mb-1">Grafik Service Harian</h6>
          <p class="text-sm text-muted mb-0">Jumlah riwayat service per tanggal</p>
        </div>
        <div class="card-body p-3">
          <div class="chart" style="height: 300px;">
            <canvas id="line-chart-gradient" class="chart-canvas h-100 w-100"></canvas>
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
});
</script>
<?php
include 'core/footer.php';
?>
