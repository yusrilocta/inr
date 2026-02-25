<?php
require_once __DIR__ . '/../services/EasyGoService.php';

$easygo = new EasyGoService();

// periode hari ini
$start = date("Y-m-d 00:00:00");
$end   = date("Y-m-d 23:59:59");

// 1️⃣ Ambil semua kendaraan
$vehicleResponse = $easygo->getVehicles();
$vehicles = [];

if ($vehicleResponse['ResponseCode'] == 1) {
    $vehicles = $vehicleResponse['Data'];
} else {
    echo "<div class='alert alert-danger'>{$vehicleResponse['ResponseMsg']}</div>";
    return;
}

// siapkan array untuk total km
$vehicleIds = array_column($vehicles, 'vehicle_id');
$nopolList  = array_column($vehicles, 'nopol');

// 2️⃣ Ambil total KM
$kmResponse = $easygo->getTotalKm($start, $end, $vehicleIds, $nopolList);

$totalKmData = [];

if ($kmResponse['ResponseCode'] == 1) {
    foreach ($kmResponse['Data'] as $km) {
        $totalKmData[$km['vehicle_id']] = $km['total_km'];
    }
}
include 'core/header.php';
?>
<div class="container-fluid py-4">

  <div class="card">
    
    <!-- Header -->
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Total KM Kendaraan</h5>
    </div>

    <!-- Body -->
    <div class="card-body">

<?php
if (!empty($_GET['start']) && !empty($_GET['end'])) {
    $start = $_GET['start'];
    $end   = $_GET['end'];
} else {
    $start = "2016-01-01 00:00:00";
    $end   = date("Y-m-d 23:59:59", strtotime("-1 day"));
}

$startInput = date("Y-m-d\TH:i", strtotime($start));
$endInput   = date("Y-m-d\TH:i", strtotime($end));
      ?>

      <!-- Filter -->
       <div class="row">
        <div class="col">
        <form method="GET" class="row g-3 mb-4">
            <input type="hidden" name="page" value="dashboard_km">

        <div class="col-md-4">
          <input type="datetime-local" 
                 name="start" 
                 value="<?= $startInput ?>" 
                 class="form-control">
        </div>

        <div class="col-md-4">
          <input type="datetime-local" 
                 name="end" 
                 value="<?= $endInput ?>" 
                 class="form-control">
        </div>

        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn bg-gradient-primary w-100">
            Filter
          </button>
        </div>
      </form>
        </div>
        <div class="col">
<div class="row mb-3 justify-content-end">
  <div class="col-md-4">
    <input type="text" 
           id="searchInput" 
           class="form-control" 
           placeholder="Cari No Polisi / Driver / Kendaraan...">
  </div>
</div>
        </div>
       </div>
      

      <!-- Table -->
      <div class="table-responsive">
        <table id="vehicleTable" class="table align-items-center mb-0">
    <thead>
        <tr>
            <th>No</th>
            <th data-column="0">No Polisi</th>
            <th data-column="1">Driver</th>
            <th data-column="2">Brand</th>
            <th data-column="3">Model</th>
            <th data-column="4">Total KM</th>
        </tr>
    </thead>
          <tbody>

          <?php foreach ($vehicles as $index => $row): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= $row['nopol'] ?></td>
              <td><?= $row['driver_nm'] ?></td>
              <td><?= $row['brand'] . ' - ' . $row['model'] ?></td>
              <td>
                <?php if($row['remark'] == "Active"): ?>
                  <span class="badge bg-gradient-success">Active</span>
                <?php else: ?>
                  <span class="badge bg-gradient-secondary">
                    <?= $row['remark'] ?>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <?= $totalKmData[$row['vehicle_id']] ?>
              </td>
            </tr>
          <?php endforeach; ?>

          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
          <span id="paginationInfo"></span>
        </div>
        <div>
          <button id="prevBtn" class="btn btn-sm bg-gradient-secondary">Prev</button>
          <button id="nextBtn" class="btn btn-sm bg-gradient-secondary">Next</button>
        </div>
      </div>

    </div>
  </div>

</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const table = document.getElementById("vehicleTable");
    const allRows = Array.from(table.querySelectorAll("tbody tr"));

    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [...allRows];
    let sortDirection = 1; // 1 = ASC, -1 = DESC
    let currentSortColumn = null;

    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const info = document.getElementById("paginationInfo");
    const searchInput = document.getElementById("searchInput");
    const headers = table.querySelectorAll("th");

    function renderTable() {

        allRows.forEach(row => row.style.display = "none");

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage > totalPages) currentPage = 1;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        for (let i = start; i < end && i < filteredRows.length; i++) {
            filteredRows[i].style.display = "";
        }

        info.innerText = filteredRows.length === 0
            ? "Data tidak ditemukan"
            : "Page " + currentPage + " of " + totalPages;

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage >= totalPages;
    }

    function filterTable() {
        const keyword = searchInput.value.toLowerCase();

        filteredRows = allRows.filter(row => {
            return row.innerText.toLowerCase().includes(keyword);
        });

        currentPage = 1;
        renderTable();
    }

    function sortTable(columnIndex) {

        if (currentSortColumn === columnIndex) {
            sortDirection *= -1;
        } else {
            sortDirection = 1;
            currentSortColumn = columnIndex;
        }

        filteredRows.sort((a, b) => {

            let cellA = a.children[columnIndex].innerText.trim();
            let cellB = b.children[columnIndex].innerText.trim();

            // cek angka
            let numA = parseFloat(cellA.replace(/[^0-9.-]+/g,""));
            let numB = parseFloat(cellB.replace(/[^0-9.-]+/g,""));

            if (!isNaN(numA) && !isNaN(numB)) {
                return (numA - numB) * sortDirection;
            }

            return cellA.localeCompare(cellB) * sortDirection;
        });

        currentPage = 1;
        renderTable();
    }

    // Event Search
    searchInput.addEventListener("keyup", filterTable);

    // Event Pagination
    prevBtn.addEventListener("click", function () {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });

    nextBtn.addEventListener("click", function () {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
        }
    });

    // Event Sorting
    headers.forEach((header, index) => {
        header.style.cursor = "pointer";
        header.addEventListener("click", function () {
            sortTable(index);
        });
    });

    renderTable();
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const table = document.getElementById("vehicleTable");
    const allRows = Array.from(table.querySelectorAll("tbody tr"));

    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [...allRows];

    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const info = document.getElementById("paginationInfo");
    const searchInput = document.getElementById("searchInput");

    function renderTable() {

        allRows.forEach(row => row.style.display = "none");

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage > totalPages) currentPage = 1;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        for (let i = start; i < end && i < filteredRows.length; i++) {
            filteredRows[i].style.display = "";
        }

        info.innerText = filteredRows.length === 0
            ? "Data tidak ditemukan"
            : "Page " + currentPage + " of " + totalPages;

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage >= totalPages;
    }

    function filterTable() {
        const keyword = searchInput.value.toLowerCase();

        filteredRows = allRows.filter(row => {
            return row.innerText.toLowerCase().includes(keyword);
        });

        currentPage = 1;
        renderTable();
    }

    searchInput.addEventListener("keyup", filterTable);

    prevBtn.addEventListener("click", function () {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });

    nextBtn.addEventListener("click", function () {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
        }
    });

    renderTable();

});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const table = document.getElementById("vehicleTable");
    const rows = table.querySelectorAll("tbody tr");

    const rowsPerPage = 10;
    let currentPage = 1;

    const totalPages = Math.ceil(rows.length / rowsPerPage);

    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const info = document.getElementById("paginationInfo");

    function showPage(page) {
        currentPage = page;

        rows.forEach((row, index) => {
            row.style.display = "none";
        });

        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        for (let i = start; i < end && i < rows.length; i++) {
            rows[i].style.display = "";
        }

        info.innerText = "Page " + currentPage + " of " + totalPages;

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages;
    }

    prevBtn.addEventListener("click", function () {
        if (currentPage > 1) {
            showPage(currentPage - 1);
        }
    });

    nextBtn.addEventListener("click", function () {
        if (currentPage < totalPages) {
            showPage(currentPage + 1);
        }
    });

    showPage(1);

});
</script>
<?php include 'core/footer.php'; ?>